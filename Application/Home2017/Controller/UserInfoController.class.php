<?php
/**
 * 用户中心控制器程序
 *
 * @author dengweijun <406516482@qq.com>
 * @since  2015-11-28
 */

use Think\Tool\Tool;
/**
 * 用户中心控制器
 */
class UserInfoController extends HomeController {

	/* 用户中心首页 */
	public function index(){
		$id = is_login();
		$this->assign('id',$id);
		$UserData = M('frontUser')->where(array('id'=>$id))->field("username,head,point,coin,coin+unable_coin as total_coin,nick_name,login_time,identfy,bank_card_id,descript,weixin_unionid,qq_unionid,sina_unionid")->find();
		//获取头像
		$UserData['UserFace'] = frontUserFace($UserData['head']);
		$UserData['followNum'] = M('FollowUser')->where(['follow_id'=>$id])->count();
		$this->assign('UserData',$UserData);
		
		$this->assign('position','个人中心');
		$this->display();
	}
	/**
	 * 球王--个人中心--我的关注
	 *Liangzk 《Liamgzk@qc.com》
	 * 2017-04-26
	 */
	public function qw_focus()
	{
		
		$user_id = is_login();
		//获取登录用户关注的产品
		$followList = M('IntroFollow ifo')
			->join('INNER JOIN qc_intro_products ip ON ifo.product_id = ip.id')
			->where(['ifo.user_id'=>$user_id,'ip.status'=>1])
			->field('ifo.product_id,ip.name,ip.desc,ip.logo,ip.sale,ip.total_num,ip.game_num,ip.pay_num,ip.total_rate,ip.ten_num,ip.create_time')
			->order('ifo.id desc')
			->select();
		//统计关注多少条
		$this->assign('count',M('IntroFollow ifo')->join('INNER JOIN qc_intro_products ip ON ifo.product_id = ip.id')->where(['ifo.user_id'=>$user_id,'ip.status'=>1])->count('ifo.id'));
		
		//获取产品id、并拼接图片服务器ip
		$productIdArr = array();
		foreach ($followList as $key => $value)
		{
			$followList[$key]['logo'] = Tool::imagesReplace($value['logo']);
			$productIdArr[] = $value['product_id'];
		}

		if (!empty($productIdArr))
		{
			$blockTime = getBlockTime(1, true);
			//获取区间内的推介ID
			$listIdArr = M('IntroLists')->where(['create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]]])->getField('id',true);
			$listIdArr[] = 0;

			//匹配产品的购买情况
			$buyListTemp = M('IntroBuy')
				->where([
					'product_id'=>['IN',$productIdArr],
					'_complex'=>['create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]],
								 'list_id' => ['IN',$listIdArr],
								 '_logic'=>'OR'
					]
				])
				->field('product_id,count(id) as buyNum')
				->group('product_id')
				->select();
			//匹配产品的购买情况
			$buyList = M('IntroLists')->where(['product_id'=>['IN',$productIdArr],'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]]])->field('product_id,remain_num')->select();
			$buyProductId = array();
			foreach ($buyList as $key => $value)
			{
				$buyProductId[] = $value['product_id'];
			}
			//登录用户的购买情况
			$myBuyList = M('IntroBuy')
				->where(['user_id'=>$user_id,
						 'product_id'=>['IN',$productIdArr],
						 '_complex'=>['create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]],
									  'list_id' => ['IN',$listIdArr],
									  '_logic'=>'OR'
						 ]
				])
				->getField('product_id',true);
			
			//获取用户购买的产品的推介赛事
			$introGamble = M('IntroGamble ig')
				->join('INNER JOIN qc_intro_lists il ON ig.list_id = il.id')
				->where(['il.status'=>1,'il.product_id'=>['IN',$productIdArr],'il.pub_time'=>['lt',time()],'il.create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]]])
				->field('ig.product_id,ig.union_name,ig.home_team_name,ig.away_team_name,ig.score,ig.result,ig.gtime,ig.handcp,ig.odds,ig.play_type,ig.chose_side')
				->order('gtime asc')
				->select();
		
			if (!empty($introGamble)) $introGamble = HandleGamble($introGamble,0,true);
			
			
			foreach ($followList as $key => $value)
			{
				//匹配产品推介的赛事
				foreach ($introGamble as $k => $v)
				{
					if ($value['product_id'] == $v['product_id'])
					{
						$followList[$key]['introGamble'][] = $v;
						$followList[$key]['is_introGamble'] = 1; //有推荐
					}
				}
				
				//登录用户购买情况
				foreach ($myBuyList as $k => $v)
				{
					if ($v == $value['product_id'])
					{
						$followList[$key]['is_check'] = 1;//已查看
						break;
					}
				}
				$tmepBuyNum = 0;
				//先判断是否有推介
				if (in_array($value['product_id'],$buyProductId))
				{
					//匹配产品的购买情况
					foreach ($buyList as $k => $v)
					{
						if ($v['product_id'] == $value['product_id'])
						{
							$tmepBuyNum = $value['total_num'] - $v['remain_num'];
							break;
						}
					}
					//判断是否已被抢购完
					$followList[$key]['is_soldOut'] = $value['total_num'] > $tmepBuyNum ? 1 : 0; //1：未售完 0：售完
					//查看人数
					$followList[$key]['buyNum'] = $tmepBuyNum;
					//查看百分比
					$followList[$key]['buyPercent'] = round($tmepBuyNum/$value['total_num']*100);
				}
				else
				{
					foreach ($buyListTemp as $k => $v)
					{
						if ($v['product_id'] == $value['product_id'])
						{
							$tmepBuyNum = $v['buyNum'];
							break;
						}
					}
					//后台手动设置了预购数量加实际购买。
					$buy_num = $value['pay_num'] + $tmepBuyNum;
					//判断是否已被抢购完
					$followList[$key]['is_soldOut'] = $value['total_num'] > $buy_num ? 1 : 0; //1：未售完 0：售完
					//查看人数
					$followList[$key]['buyNum'] = $buy_num;
					//查看百分比
					$followList[$key]['buyPercent'] = round($buy_num/$value['total_num']*100);
				}
			}
			
		}
		
		$this->assign('followList',$followList);
		
		$this->display();
	}
	
	/**
	 * 球王--个人中心--我的订购
	 *Liangzk 《Liamgzk@qc.com》
	 * 2017-04-26
	 */
	public function qw_order()
	{
		$user_id = is_login();
		$page = I('p',1,'int');
		$limit = 5;
		$startTime = I('startTime',0,'int');
		$endTime = I('endTime',0,'int');
		//时间查询
		if($startTime > 0 || $endTime > 0)
		{
			if($startTime > 0 && $endTime > 0){
				$startTime = strtotime(date('Y-m-d',$startTime));
				$endTime   = strtotime(date('Y-m-d',$endTime))+86400;
				$map['ib.create_time'] = array('BETWEEN',array($startTime,$endTime));
			} elseif ($startTime > 0) {
				$startTime = strtotime(date('Y-m-d',$startTime));
				$map['ib.create_time'] = array('EGT',$startTime);
			} elseif ($endTime > 0) {
				$endTime   = strtotime(date('Y-m-d',$endTime))+86400;
				$map['ib.create_time'] = array('ELT',$endTime);
			}
		}
		$map['ib.user_id'] = $user_id;
		
		//获取开启的产品ID
		$tempProIdArr = M('IntroProducts')->where(['status'=>1])->getField('id',true);
		$map['ib.product_id'] = ['IN',empty($tempProIdArr) ? array() : $tempProIdArr];
		//登录用户的购买情况
		$myBuyList = M('IntroBuy ib')
			->join('LEFT JOIN qc_intro_lists il ON ib.list_id = il.id')
			->where($map)
			->field('ib.id,ib.product_id,ib.create_time,il.id as listId,il.status,il.pub_time')
			->order('ib.create_time desc')
			->limit(($page - 1)*$limit,$limit)
			->select();
	
		//获取有推介的产品ID 、推介ID
		$productIdArr = $listIdArr = $tempIdArr = $notListId =  array();
		foreach ($myBuyList as $key => $value)
		{
			if ($value['status'] != 1 || !$value['listId'] )
			{
				$tempIdArr[] = $value['product_id'];
				continue;
			}
			//记录有推介还不能发布的
			if ($value['pub_time'] > time()) $notListId[] = $value['listId'];
			
			$productIdArr[] = $value['product_id'];
			$listIdArr[] = $value['listId'];
		}
		//获取有推介的赛事赛程、产品信息
		$introGamble = M('IntroGamble ig')
			->join('INNER JOIN qc_intro_products ip ON ig.product_id = ip.id')
			->where(['ip.id'=>['IN',$productIdArr],'ig.list_id'=>['IN',$listIdArr],'ip.status'=>1])
			->field('ip.name,ip.logo,ip.sale,ip.total_rate,ip.game_num,ip.ten_num,
			ig.id,ig.list_id,ig.product_id,ig.union_name,ig.home_team_name,ig.away_team_name,ig.score,ig.result,ig.gtime,ig.handcp,ig.odds,ig.play_type,ig.chose_side')
			->order('gtime asc')
			->select();
		//获取还不能显示的推介赛事
		$notListIdArr = M('IntroLists')->where(['id'=>['IN',$notListId],'pub_time'=>['GT',time()],])->getField('id',true);
		if (empty($notListIdArr)) $notListIdArr = array();
		
		//获取没有推荐或被关闭推荐的产品信息
		if (!empty($tempIdArr))
		{
			$tempProducts = M('IntroProducts')->where(['status'=>1,'id'=>['IN',$tempIdArr]])->field('id,name,logo,sale,total_rate,game_num,ten_num')->select();
		}
		
		if (!empty($introGamble)) $introGamble = HandleGamble($introGamble,0,true);
		
		foreach ($myBuyList as $key => $value)
		{
			if ($value['status'] != 1 || !$value['listId'])
			{
				foreach ($tempProducts as $k => $v)
				{
					if ($value['product_id'] == $v['id'])
					{
						$myBuyList[$key]['name'] = $v['name'];
						$myBuyList[$key]['logo'] = Tool::imagesReplace($v['logo']);;
						$myBuyList[$key]['sale'] = $v['sale'];
						$myBuyList[$key]['total_rate'] = $v['total_rate'];
						$myBuyList[$key]['game_num'] = $v['game_num'];
						$myBuyList[$key]['ten_num'] = $v['ten_num'];
						
					}
				}
				
			}
			else
			{
				foreach ($introGamble as $k => $v)
				{
					if ($value['product_id'] == $v['product_id'] && $value['listId'] == $v['list_id'] )
					{
						$myBuyList[$key]['name'] = $v['name'];
						$myBuyList[$key]['logo'] = Tool::imagesReplace($v['logo']);;
						$myBuyList[$key]['sale'] = $v['sale'];
						$myBuyList[$key]['total_rate'] = $v['total_rate'];
						$myBuyList[$key]['game_num'] = $v['game_num'];
						$myBuyList[$key]['ten_num'] = $v['ten_num'];
						
						unset($v['name'],$v['logo'],$v['sale'],$v['total_rate'],$v['game_num'],$v['ten_num'],$v['product_id']);
						//判断定时发布时间是否大于当前时间
						if (in_array($value['listId'],$notListIdArr)) break;
						
						$myBuyList[$key]['introGamble'][] = $v;
						
					}
				}
				
			}
			
		}
		
		//实例化分页类
		$count = M('IntroBuy ib')->where($map)->count('id');
		
		$page = new \Think\Page ( $count, $limit );
		
		//自定义分页样式
		$page->config  = array(
			'header' => '<span class="rows">共 %TOTAL_ROW% 条记录</span>',
			'prev'   => '<span aria-hidden="true">上一页</span>',
			'next'   => '<span aria-hidden="true">下一页</span>',
			'first'  => '首页',
			'last'   => '...%TOTAL_PAGE%',
			'theme'  => '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%',
		);
		//设置分页路由链接
		if ($startTime > 0 && $endTime > 0)
		{
			$page->url = "/UserInfo/qw_order/startTime/".$startTime."/endTime/".$endTime."/p/%5BPAGE%5D.html";
		}
		elseif ($startTime > 0)
		{
			$page->url = "/UserInfo/qw_order/startTime/".$startTime."/p/%5BPAGE%5D.html";
		}
		elseif ($endTime > 0)
		{
			$page->url = "/UserInfo/qw_order/endTime/".$endTime."/p/%5BPAGE%5D.html";
		}
		else
		{
			$page->url = "/UserInfo/qw_order/p/%5BPAGE%5D.html";
		}
		
		//模板赋值显示
		$this->assign ( "show", $page->showJs());
		//页数
		$this->assign ( "pageCount", $count/$page->listRows);
		$this->assign ( "count", $count);
		
		$this->assign('myBuyList',$myBuyList);
		$this->display();
	}
	
	/*
	 *@User liangzk <liangzk@qc.com>
	 * @DateTime 2016-08-01
	 * 绑定手机号
	 */
	public function bind_phone()
	{
        $token = md5(uniqid(rand(), TRUE));
        session('token',$token);
        $this->assign('token',$token);
		$userData = M('FrontUser')->where(['id'=>is_login()])->field('username')->find();
		if (IS_POST)
		{
			if (! empty($userData['username'])) $this->error('已绑定过手机！');

			$username = I('mobile');
			$password = I('password');

			$isTrue = A('User')->checkMobileVerify(I('captcha'),$username);
			if(! $isTrue) $this->error('验证码错误或已超时');
				
			if (M('FrontUser')->where(['username'=>$username])->find()) $this->error('该手机号码已经注册，请更换');
				
			if (! preg_match('/^[a-z\d]{6,15}$/i',$password)) $this->error('非法数据');
				
			$data = [
				'username' => $username,
				'password' => md5($password),
			];
			if (M('FrontUser')->where(['id'=>is_login()])->save($data))
			{
				$this->success('手机号绑定成功');
			}
			else
			{
				$this->error('绑定失败');
			}
		}
		$this->assign('userData',$userData);
		$this->assign('position','个人中心');
		$this->display();
	}

	/* 基本信息 */
	public function basic_infor(){
		
		if(IS_POST){
			$descript = I('descript');
			if(!matchFilterWords('FilterWords',$descript)) $this->error("内容不可包含敏感词，请检查后重试！");
			$rs = M('frontUser')->where(array('id'=>is_login()))->save(['descript'=>$descript]);
			if(!is_bool($rs)){
				$rs = true;
			}
			if($rs){
				$this->success('保存成功');
			}else{
				$this->error('保存失败');
			}
		}else{
			$UserData = M('frontUser')->where(array('id'=>is_login()))->field("nick_name,head,reg_time,descript")->find();
			//获取头像
			$id = is_login();
			$UserData['UserFace'] = frontUserFace($UserData['head']);
			$this->assign('UserData',$UserData);
			$this->assign('position','个人中心');
			$this->display();
		}
	}

	/* 身份认证 */
	public function identity()
	{
		$user_id = is_login();
		
		if(IS_POST){
			$true_name = I('true_name');
			$identfy   = I('identfy');
			$rs = M('frontUser')->where(array('id'=>$user_id))->save(['true_name'=>$true_name,'identfy'=>$identfy]);
			if($rs){
				$this->success('保存成功');
			}else{
				$this->error('保存失败');
			}
		}else{
			$data = M('frontUser')->where(array('id'=>$user_id))->field("true_name,identfy")->find();
			$this->assign('data',$data);
			$this->assign('position','个人中心');
			$this->display();
		}
	}

	/* 修改密码 */
	public function change_password(){
        $token = md5(uniqid(rand(), TRUE));
        session('token',$token);
        $this->assign('token',$token);
		$user_id = is_login();
		if (empty($user_id)) $this->error('请登录后再操作！');
		$userData = M('FrontUser')->where(['id'=>$user_id])->field('username')->find();
		if(IS_POST){
			if (empty($userData)) $this->error('请绑定手机');
			if (empty($userData)) $this->error('操作失败！');
			$isTrue = A('User')->checkMobileVerify(I('captcha','','htmlspecialchars'),$userData['username']);//比对验证码
			if(! $isTrue)
			{
				$this->error('验证码错误或已超时！');
				exit;
			}
			else
			{
				$oldpass  = I('oldpass');
				$newpass  = I('newpass');
				$repass  = I('repass');
				if (! preg_match('/^[a-z\d]{6,15}$/i',$oldpass) || ! preg_match('/^[a-z\d]{6,15}$/i',$newpass) || ! preg_match('/^[a-z\d]{6,15}$/i',$repass))
					$this->error('密码输入格式不正确！');
				if (! M('FrontUser')->where(['id'=>$user_id,'password'=>md5($oldpass)])->find())
					$this->error('原密码不正确！');
				if ($newpass !== $repass)
					$this->error('新提款密码与确定密码不一致！');
				$rs = M('frontUser')->where(['id'=>$user_id])->save(['password'=>md5($repass)]);
				if($rs)
				{
					//销毁登录状态
					session('user_auth',null);
					$this->success('修改成功,请重新登录！');
				}else
				{
					$this->error('修改失败,您没有更改！');
				}
			}

		}else{
			
			$this->assign('userData',$userData);
			$this->assign('position','个人中心');
			$this->display();
		}
		
	}
	/**
	 * 修改提款密码
	 * @User liangzk <liangzk.qc.com>
	 * @DateTime 2016-08-02 09:54
	 * version 2.0
	 */
	public function draw_password()
	{
        $token = md5(uniqid(rand(), TRUE));
        session('token',$token);
        $this->assign('token',$token);
		$user_id = is_login();
		if (empty($user_id)) $this->error('非法操作！');
		$userData = M('FrontUser')
					->where(['id'=>is_login()])->field('username,true_name,identfy,bank_name,bank_card_id,alipay_id')
					->find();
		//判断是否身份认证
		if (! $userData['true_name'] || !$userData['identfy'])
			$this->noTrueName = true;
        //判断是否绑定银行卡或支付宝账号
        if (! $userData['bank_name'] || !$userData['bank_card_id'])
        {
            if (! $userData['alipay_id']) $this->noBindBank = true;
        }
        //判断是否绑定手机
		if (! $userData['username'])
			$this->noUsername = true;
		if (isset($this->noTrueName) || isset($this->noBindBank) || isset($this->noUsername))
		{
			$this->assign('position','个人中心');
		}
		else
		{
			if (IS_POST)
			{
				if (empty($userData)) $this->error('操作失败！');
				$isTrue = A('User')->checkMobileVerify(I('captcha','','htmlspecialchars'),$userData['username']);//比对验证码
				if(! $isTrue)
				{
					$this->error('验证码错误或已超时！');
					exit;
				}
				else
				{
					$drawOldPass = I('drawOldPass','','htmlspecialchars');
					$drawNewPass = I('drawNewPass','','htmlspecialchars');
					$drawPwdOk = I('drawPwdOk','','htmlspecialchars');
					if (! preg_match('/^\d{6}$/',$drawOldPass) || ! preg_match('/^\d{6}$/',$drawNewPass) || ! preg_match('/^\d{6}$/',$drawPwdOk))
						$this->error('参数出错！');
					$userData = M('FrontUser')->where(['id'=>$user_id])->field('bank_extract_pwd')->find();
					if ($userData['bank_extract_pwd'] !== md5($drawOldPass)) $this->error('原提款密码不正确！');
					if ($drawNewPass === $drawPwdOk)
					{
						if (M('FrontUser')->where(['id'=>$user_id])->save(['bank_extract_pwd'=>md5($drawPwdOk)]))
							$this->success('提款密码修改成功');
					}
					else
					{
						$this->error('新提款密码与确定密码不一致！');
					}
				}

			}
			else
			{

				$this->assign('userData',$userData);
				$this->assign('position','个人中心');

			}
		}
		$this->display();

	}
	/**
	 * 检查原提款密码
	 * @User liangzk <liangzk.qc.com>
	 * @DateTime 2016-08-02 09:54
	 * version 2.0
	 */
	public function check_draw_pass()
	{
		
		$drawOldPass = I('drawOldPass');
		$userId = is_login();
		$bank_extract_pwd = M('FrontUser')->where(['id'=>$userId])->getField('bank_extract_pwd');
		if(md5($drawOldPass) === $bank_extract_pwd){
			echo "true";
		}else{
			echo "false";
		}
	}
	/* 站内通知 */
	public function station_notice(){
		
		//生成查询数据
		$map = $this->_search("Msg");
		//当前登录用户的消息
		$map['front_user_id'] = is_login();
		//获取列表
		$list= $this->_list(M("Msg"),$map,7,'send_time desc','','',"/UserInfo/station_notice/p/%5BPAGE%5D.html");
		if($list){
			//把消息设置成已读
			M("Msg")->where(array('front_user_id'=>is_login(),'is_read'=>0))->save(array('is_read'=>1));
		}
		$this->assign('list',$list);
		$this->assign('position','个人中心');
		$this->display();
	}

	//检查原始密码
	public function checkOldPass(){
		
		$oldPass = I('oldpass');
		$userId = is_login();
		$password = M('frontUser')->where(array('id'=>$userId))->getField('password');
		if(md5($oldPass) === $password){
			echo "true";
		}else{
			echo "false";
		}
	}
	/*验证用户昵称是否存在,除了自己*/
	public function checkNickname(){
		
		$nickname = I('nick_name');
		$where['nick_name'] = $nickname;
		$where['id'] = array('neq',is_login());
		$isNickname = M('frontUser')->where($where)->find();
		if($isNickname){
			echo "false";
		}else{
			echo "true";
		}
	}
	/*验证身份证号码是否存在*/
	public function checkIdentfy(){
		
		$identfy = I('identfy');
		$isIdentfy = M('frontUser')->where(array('identfy'=>$identfy))->find();
		if($isIdentfy){
			echo "false";
		}else{
			echo "true";
		}
	}
	/**
     * 上传用户头像
     *
     * @return array  #
    */
	public function uploadFace() {
		$imgData = I('imgData');
		if (empty($imgData)) {
			$this->error('没有要上传的数据');
		}
		$user_id = is_login();
		$return = D('Uploads')->uploadFileBase64($imgData, "user", "face", "200", $user_id, "[[200,200,200]]");
		//路径存数据库
		if($return['status'] == 1){
			M("frontUser")->where(['id'=>$user_id])->save(['head'=>$return['url']]);
			$_SESSION[C('SESSION_PREFIX')]['user_auth']['head'] = frontUserFace($return['url']);
		}
		echo json_encode($return);
        exit;
	}
}