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
		$UserData = M('frontUser')->where(array('id'=>$id))->field("username,head,is_expert,point,coin,coin+unable_coin as total_coin,nick_name,login_time,identfy,bank_card_id,descript,weixin_unionid,qq_unionid,sina_unionid,descript")->find();
		//获取头像
		$UserData['UserFace'] = frontUserFace($UserData['head']);
		$UserData['followNum'] = M('FollowUser')->where(['follow_id'=>$id])->count();
		$this->assign('UserData',$UserData);

        //主页横幅广告
        if(!$adver1 = S('userIndexAd'))
        {
            $adver1 = Tool::getAdList(100,1)[0];
            S('userIndexAd',json_encode($adver1),5*60);
        }
        $this->assign('adver1', $adver1);

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
			->field('ifo.product_id,ip.name,ip.desc,ip.logo,ip.sale,ip.total_num,ip.game_num,ip.pay_num,ip.total_rate,ip.ten_num,ip.create_time,ip.path')
			->order('ifo.id desc')
			->select();
		//统计关注多少条
		$this->assign('count',M('IntroFollow ifo')->join('INNER JOIN qc_intro_products ip ON ifo.product_id = ip.id')->where(['ifo.user_id'=>$user_id,'ip.status'=>1])->count('ifo.id'));
		
		//获取产品id、并拼接图片服务器ip
		$productIdArr = array();
		foreach ($followList as $key => $value)
		{
			$followList[$key]['logo'] = Tool::imagesReplace($value['logo']);
			$followList[$key]['href'] = introUrl($value['path']);
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
		$introClass = getIntroClass();

		//获取有推介的产品ID 、推介ID
		$productIdArr = $listIdArr = $tempIdArr = $notListId =  array();
		foreach ($myBuyList as $key => $value)
		{
			$myBuyList[$key]['href'] = introUrl($introClass[$value['product_id']]['path']);
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

    //足球推荐记录
    public function gambleFtball()
    {
        $id = is_login();
        $gamble_type = I('gamble_type') ? : 1;
        //获取用户等级
        $userLv = M('FrontUser')->field("lv,lv_bet")->where(['id'=>$id])->find();
        $lv = $gamble_type == 1 ? $userLv['lv'] : $userLv['lv_bet'];
        $this->assign('lv',$lv);
        //生成查询条件
        $map  = $this->_search("gamble");
        $map['user_id'] = $id;
        $map['play_type'] = $gamble_type == 1 ? ['in',[1,-1]] : ['in',[2,-2]];
        //获取列表
        if($gamble_type == 1){
            $url = "/UserInfo/gambleFtball/p/%5BPAGE%5D.html";
        }else{
            $url = "/UserInfo/gambleFtball/gamble_type/{$gamble_type}/p/%5BPAGE%5D.html";
        }
        $list = $this->_list(D("gambleView"),$map,10,"id desc",'','',$url);
        $list = HandleGamble($list);
        //统计用户推荐足球的赢、平、输的场数
        $resultArr = $this->get_gamble_result(1,'',$gamble_type);
        $this->assign('resultArr',$resultArr);
        //连胜记录
        $winning = D('GambleHall')->getWinning($id,1,0,$gamble_type,0);
        $this->assign('winning',$winning);
        //近十场足球推荐结果
        $TenGamble = $this->getTenGamble($id,1,$gamble_type);
        $this->assign('TenGamble',$TenGamble);
        //周推荐记录
        $footWeek = $this->CountWinrate($id,1,1,true,false,0,$gamble_type);
        $this->assign('footWeek',$footWeek);
        //月推荐记录
        $footMonth = $this->CountWinrate($id,1,2,true,false,0,$gamble_type);
        $this->assign('footMonth',$footMonth);
        //季推荐记录
        $footSeason = $this->CountWinrate($id,1,3,true,false,0,$gamble_type);
        $this->assign('footSeason',$footSeason);
        $this->assign('list',$list);
        $this->position = '个人中心';

        $this->display();
    }

    //篮球推荐记录
    public function gambleBktball()
    {
        $id = is_login();
        //获取用户等级
        $lv = M('FrontUser')->where(['id'=>$id])->getField('lv_bk');
        $this->assign('lv',$lv);
        //生成查询条件
        $map  = $this->_search("gamblebk");
        $map['user_id'] = $id;
        //获取列表
        $list = $this->_list(D("gamblebkView"),$map,10,"id desc",'','',"/UserInfo/gambleBktball/p/%5BPAGE%5D.html");
        $list = HandleGamble($list);
        //统计用户推荐篮球的赢、平、输的场数
        $this->assign('resultArr',$this->get_gamble_result(2));
        //连胜记录
        $winning = D('GambleHall')->getWinning($id,2,0,1,0);
        $this->assign('winning',$winning);
        //近十场蓝球推荐结果
        $TenGamble = $this->getTenGamble($id,2);
        $this->assign('TenGamble',$TenGamble);
        //周推荐记录
        $footWeek = $this->CountWinrate($id,2,1,true);
        $this->assign('footWeek',$footWeek);
        //月推荐记录
        $footMonth = $this->CountWinrate($id,2,2,true);
        $this->assign('footMonth',$footMonth);
        //季推荐记录
        $footSeason = $this->CountWinrate($id,2,3,true);
        $this->assign('footSeason',$footSeason);
        $this->assign('list',$list);
        $this->position = '个人中心';

        $this->display();
    }

    //查看推荐记录
    public function adviseTrade()
    {
        $id               = is_login();
        $gameType         = I('gameType') ? I('gameType') : 1;
        //生成查询条件
        $map              = $this->_search("quizLog");
        $map['user_id']   = $id;
        $map['game_type'] = $gameType;
        //获取列表
        $Model = $gameType == 1 ? D('quizLog') : D('quizLogBk');
        $list = $this->_list($Model,$map,15,"log_time desc",'','',"/UserInfo/adviseTrade/gameType/{$gameType}/p/%5BPAGE%5D.html");
        if($gameType == 1) //足球时获取新旧表一起的数据
        {
            foreach ($list as $k => $v)
            {
                $gamble = D('GambleHall')->getGambleInfo($v['gamble_id']);
                $list[$k]['play_type']   = $gamble['play_type'];
                $list[$k]['chose_side']  = $gamble['chose_side'];
                $list[$k]['handcp']      = $gamble['handcp'];
                $list[$k]['odds']        = $gamble['odds'];
                $list[$k]['result']      = $gamble['result'];
                $list[$k]['tradeCoin']   = $gamble['tradeCoin'];
                $list[$k]['analysis']    = $gamble['desc'];
                $list[$k]['union_color'] = $gamble['union_color'];
            }
        }
        //处理数据
        $list = HandleGamble($list,0,false,$gameType);
        $this->assign('list',$list);
        $this->position = '个人中心';

        $this->display();
    }


    //账户明细
    public function details()
    {
        //生成查询数据
        $map = $this->_search("accountLog");
        $userid = is_login();
        //日期筛选,默认一周
        $dateType = I('get.dateType') ?: '3months';
        $this->assign('dateType',$dateType);
        switch ($dateType)
        {
            case 'aweek':   $searchTime = 7 * 86400;    break;
            case 'amonth':  $searchTime = 30 * 86400;   break;
            case '3months': $searchTime = 90 * 86400;   break;
        }
        $map['log_time'] = ['egt',time() - $searchTime];
        //当前登录用户明细记录
        $map['user_id']    = $userid;
        //获取列表
        $list= $this->_list(D("accountLog"),$map,12,'','','',"/UserInfo/details/dateType/{$dateType}/p/%5BPAGE%5D.html");
        $this->assign('list',$list);
        $this->assign('payAccountType',C('payAccountType'));
        $this->position = '个人中心';

        $this->display();
    }

    //待结算账户明细
    public function wait_details()
    {
        $userid = is_login();

        //当前登录用户明细记录
        $map['user_id']     = $userid;
        $map['result']      = ['eq',0];
        $map['tradeCoin']   = ['gt',0];
        $map['quiz_number'] = ['gt',0];
        $map['is_back']     = ['eq',0];

        //获取列表总数
        $countfb = M('gamble')->where ( $map )->count ();
        $countbk = M('gamblebk')->where ( $map )->count ();
        $count = $countfb+$countbk;
        //实例化分页类
        $page = new \Think\Page ( $count, 12 );
        //足球待结算
        $fb_list = M('gamble')->where($map)->field("game_date,game_time,union_name,home_team_name,away_team_name,play_type,chose_side,quiz_number,income")->group('id')->order('game_date desc,game_time desc')->select();
        foreach ($fb_list as $k => $v) {
            $fb_list[$k]['gameType'] = in_array($v['play_type'], ['2', '-2']) ? '足球竞彩 - ':'足球亚盘 - ';
            $fb_list[$k]['playDesc'] = C('fb_play_type')[$v['play_type']];
        }
        //篮球待结算
        $bk_list = M('gamblebk')->where($map)->field("game_date,game_time,union_name,home_team_name,away_team_name,play_type,chose_side,quiz_number,income")->group('id')->order('game_date desc,game_time desc')->select();
        foreach ($bk_list as $k => $v) {
            $bk_list[$k]['gameType'] = '篮球 - ';
            $bk_list[$k]['playDesc'] = C('bk_play_type')[$v['play_type']];
        }
        //合并
        $list = array_merge($fb_list,$bk_list);
        foreach ($list as $k => $v) {
            $game_date[] = $v['game_date'];
            $game_time[] = $v['game_time'];
        }
        array_multisort($game_date,SORT_ASC,$game_time,SORT_ASC,$list);
        $list = array_slice($list, $page->firstRow,$page->listRows);
        $list = HandleGamble($list);
        $page->url = "/UserInfo/wait_details/p/%5BPAGE%5D.html";
        //模板赋值显示
        $this->assign ( "show", $page->showJs());
        $this->assign('totalCount', $count );
        $this->assign('numPerPage', $page->listRows );
        $this->assign('list', $list );
        //待结算总金币
        $fb_income  = (int)M('gamble')
            ->where("result = 0 AND tradeCoin > 0 AND quiz_number > 0 AND is_back = 0 AND user_id = ".$userid)
            ->sum('income');
        $bk_income  = (int)M('gamblebk')
            ->where("result = 0 AND tradeCoin > 0 AND quiz_number > 0 AND is_back = 0 AND user_id = ".$userid)
            ->sum('income');
        $this->assign('income',$fb_income + $bk_income);

        $this->display();
    }

    /**
     * @author :junguo
     * @desc:充值
     * */
    public function charge()
    {
        $this->position = '个人中心';
        $user  =session('user_auth');
        $this->assign('user_id',$user['username']);

        // 获取剩余金币
        $userId = is_login();
        if($userId){
            $coin = M('FrontUser')->field("sum(coin+unable_coin) as coin")->where(['id'=>$userId])->find();
            $this->assign('coin',$coin['coin']);
        }

        //获取支付赠送
        $rechargeConfig = M('config')->where(['sign' => 'recharge'])->getField('config');
        $recharge = json_decode($rechargeConfig, true);
	    $rechargeBind = $recharge['recharge'][0]['account'];
	    $rechargeNum = 0;
	    foreach ($recharge['recharge'] as $value) {
		    if ($value['account']  == $rechargeBind) {
			    $rechargeNum = $value['number'];
		    }
	    }
        $this->assign("rechargeBind", $rechargeBind);
        $this->assign("rechargeNum", $rechargeNum);
        $this->assign('recharge' ,$recharge['recharge']);

        $this->display();
    }

    //充值记录
    public function chargeLog()
    {
        //生成查询数据
        $map = $this->_search("accountLog");
        //条件
        $map = ['user_id'=>is_login(),'log_status'=>['gt',0],'log_type'=>1];
        //获取列表
        $list= $this->_list(D("accountLog"),$map,13,'','','',"/UserInfo/chargeLog/p/%5BPAGE%5D.html");
        $this->assign('list',$list);
        $this->position = '个人中心';

        $this->display();
    }

    //提款
    public function extract()
    {
        $minMoney = getWebConfig('common')['iosExtractMoney'];
        $user_id = is_login();
        $user = M('FrontUser')
            ->field('username,coin,unable_coin,alipay_id,true_name,identfy,bank_name,bank_card_id,alipay_id,bank_extract_pwd')
            ->find($user_id);
        $this->exMoney = $user['coin']; //可提款
        //判断是否身份认证、绑定银行卡、绑定手机
        if (! $user['true_name'] || !$user['identfy'])
            $this->noTrueName = true;
        //判断是否绑定银行卡或支付宝账号
        if (! $user['bank_name'] || !$user['bank_card_id'])
        {
            if (! $user['alipay_id']) $this->noBindBank = true;
        }
        //判断是否绑定手机
        if (! $user['username'])
            $this->noUsername = true;

        if (isset($this->noTrueName) || isset($this->noBindBank) || isset($this->noUsername)){
            $tpl = 'extractNotice';
        }else{
            if(IS_AJAX && IS_POST){
                $bank_extract_pwd = I('bank_extract_pwd');
                //验证提款密码
                if(md5($bank_extract_pwd) !== $user['bank_extract_pwd']){
                    $this->error("提款密码错误！");
                    exit;
                }
                $coin = I('coin');
                //验证金额
                if($coin>$user['coin']){
                    $this->error("可提现金额为{$user['coin']}元");
                    exit;
                }
                if($coin<$minMoney || $coin>10000){
                    $this->error("每次提款金额最小为".$minMoney."元,最大为10000元");
                    exit;
                }
                //每天只能申请提款一次
                $begin = strtotime("today");
                $end   = strtotime("today")+86400;
                $where['user_id'] = $user_id;
                $where['log_type'] = 2;
                $where['log_time'] = array('BETWEEN',array($begin,$end));
                $is_true = M("accountLog")->where($where)->select();
                if($is_true){
                    $this->error("亲，每天只能提款一次哦，明天再来吧！");
                    exit;
                }
                M("accountLog")->startTrans();
                //添加提款申请
                $rs = M("accountLog")->add(
                    array(
                        'user_id'=>$user_id,
                        'log_time'=>time(),
                        'log_type'=>2,
                        'change_num'=>$coin,
                        'total_coin'=>($user['coin']+$user['unable_coin'])-$coin,
                        'desc'=>"提款申请",
                        'platform'=>1,
                    )
                );
                if($rs){
                    //减去金额
                    $rs2 = M("FrontUser")->where(array('id'=>$user_id))->setDec('coin',$coin);
                    //添加到冻结提款金额
                    $rs3 = M("FrontUser")->where(array('id'=>$user_id))->setInc('frozen_coin',$coin);
                }
                if($rs && $rs2 && $rs3){
                    M("accountLog")->commit();
                    $this->success("申请提款成功，请等待审核！");
                }else{
                    M("accountLog")->rollback();
                    $this->error("申请提款失败！");
                }
            }
            $tpl = 'extract';
            $this->assign('user',$user);
        }
        $this->position = '个人中心';
        $this->assign('minMoney',$minMoney);
        $this->display($tpl);
    }
    /**
     * 绑定支付宝
     * @User liangzk <liangzk@qc.com>
     * @DateTime 2016-0-25
     * @versoin v2.1
     */
    public function bindAlipay()
    {
        $this->position = '个人中心';
        $user_id = is_login();

        if (IS_AJAX)
        {

            $alipay_id = I('alipay_id','char');
            $true_name = I('true_name','char');
            $bank_extract_pwd = I('bank_extract_pwd','int');
            $re_bank_extract_pwd = I('re_bank_extract_pwd','int');
            if (! preg_match('/^\d{6}$/',$bank_extract_pwd))
                $this->error('输入格式不正确！');
            if (! preg_match('/^\d{6}$/',$re_bank_extract_pwd))
                $this->error('输入格式不正确！');

            $userData = M('FrontUser')->where(['id'=>$user_id])->field('identfy,bank_card_id,alipay_id,true_name')->find();

            //是否已经认证身份
            if (empty($userData['true_name']) || empty($userData['identfy']))
                $this->error('请身份认证！');
            if (empty($alipay_id))
                $this->error('支付宝账号不能为空！');
            if ($true_name !== $userData['true_name'])
                $this->error('真实姓名必须与身份证姓名一致！');
            if ($bank_extract_pwd !== $re_bank_extract_pwd)
                $this->error('提款密码不一致！');
            if (! empty($userData['bank_card_id']))
                $this->error('已绑定过银行卡号！');
            if (! empty($userData['alipay_id']))
                $this->error('已绑定支付宝账号！');

            $res = M('FrontUser')->where(['id'=>$user_id])->save(['alipay_id'=>$alipay_id,'bank_extract_pwd'=>md5($bank_extract_pwd)]);

            if ($res === false)
                $this->error('操作失败');

            $this->success('操作成功');
        }

        $userData = M('FrontUser')->where(['id'=>$user_id])->field('identfy,true_name,alipay_id,bank_card_id')->find();

        //是否已经认证身份
        if (empty($userData['true_name']) || empty($userData['identfy']))
            $this->redirect('UserInfo/identity');

        if (! empty($userData['alipay_id']))//判断是否绑定过
        {
            $this->assign('alipay_id',hideStar($userData['alipay_id']));
            $this->display('bankCardInfo');
            exit;
        }
        else
        {
            if (! empty($userData['bank_card_id']))
            {
                $this->assign('bank_card_id',$userData['bank_card_id']);
                $this->display();
                exit;
            }
        }
        $this->display();
    }
    //绑定银行卡
    public function bindBankCard()
    {
        $this->position = '个人中心';
        $user_id = is_login();
        //是否ajax请求绑定银行卡
        if (IS_AJAX)
        {
            $user = M('FrontUser')->field('true_name,alipay_id,bank_card_id')->find($user_id);

            //判断是否已绑定支付宝账号
            if (! empty($user['alipay_id']))
                $this->error('已经绑定过支付宝账号');
            if (! empty($user['bank_card_id']))
                $this->error('已经绑定过银行卡号');

            $post = I('post.');

            if ($user['true_name'] != $post['true_name'])
                $this->error('真实姓名不一致');

            if ($post['bank_extract_pwd'] != $post['re_bank_extract_pwd'])
                $this->error('两次密码输入不一致');

            $data = [
                'bank_name'        => $post['bank_name'],
                'bank_card_id'     => $post['bank_card_id'],
                'bank_region'      => $post['province'] .' '. $post['city'],
                'bank_extract_pwd' => md5($post['bank_extract_pwd'])
            ];

            $update = M('FrontUser')->where(['id'=>$user_id])->save($data);

            if ($update)
                $this->success('绑定成功');
            else
                $this->error('绑定失败');
        }

        $user = M('FrontUser')->field('alipay_id,true_name,identfy,bank_name,bank_card_id,bank_region')->find($user_id);
        //判断是否已绑定支付宝账号
        if (! empty($user['alipay_id']))
        {
            $this->assign('alipay_id',$user['alipay_id']);
            $this->display();
            exit;
        }
        //是否已经认证身份
        if (!$user['true_name'] || !$user['identfy'])
            $this->redirect('UserInfo/identity');

        //是否已经绑定银行卡
        if ($user['bank_name'] && $user['bank_card_id'])
        {
            $this->user = $user;
            $this->display('bankCardInfo');
            exit;
        }
        else
        {
            $this->bank = M('Bank')->field('bank_name')->select();
            $this->province = M('Area')->field('id,region_name')->where(['parent_id'=>1])->select();
        }

        $this->display();
    }

    //ajax获取各省的城市
    public function getCity()
    {
        if (!IS_AJAX)
            return;

        $regionid = I('post.regionid');

        if (!$regionid)
        {
            echo 0;
            exit;
        }

        $city = M('Area')->field('id,region_name')->where(['parent_id'=>$regionid])->select();
        $this->ajaxReturn($city);
    }

    //ajax校验绑定银行卡的姓名与身份证是否一致
    public function verifyTrueName ()
    {
        if (!IS_AJAX)
            return;

        $postName = I('post.true_name');
        $user = M('FrontUser')->field('true_name')->find(is_login());

        if ($postName == $user['true_name'])
            echo 'true';
        else
            echo 'false';
    }

    //积分明细
    public function pointLog()
    {
        //生成查询数据
        $map = $this->_search("PointLog");

        //日期筛选,默认一周
        $dateType = I('get.dateType') ?: 'aweek';
        $this->assign('dateType',$dateType);
        switch ($dateType)
        {
            case 'aweek':   $searchTime = 7 * 86400;    break;
            case 'amonth':  $searchTime = 30 * 86400;   break;
            case '3months': $searchTime = 90 * 86400;   break;
        }
        $map['dateType'] = $dateType;
        $map['log_time'] = ['egt',time() - $searchTime];
        //当前登录用户明细记录
        $map['user_id']    = is_login();
        //获取列表
        $list= $this->_list(D("PointLog"),$map,12,'','','',"/UserInfo/pointLog/dateType/{$dateType}/p/%5BPAGE%5D.html");
        $this->assign('list',$list);

        $this->position = '个人中心';

        $this->display();
    }

    //关注的比赛
    public function followGame()
    {
        //获取我关注的人
        $followUser = M('followUser')->where(['user_id'=>is_login()])->select();
        foreach ($followUser as $key => $value) {
            $followId[] = $value['follow_id'];
        }
        //获取我关注的人今天推荐的比赛
        $where['user_id'] = ['in',$followId];
        $blockTime   = getBlockTime(1,$gamble=true);
        $where['create_time'] = ['between',[$blockTime['beginTime'],$blockTime['endTime']]];
        $gamble = $this->_list(D('GambleView'),$where,12,"id desc",'','',"/UserInfo/followGame/p/%5BPAGE%5D.html");
        $gamble = HandleGamble($gamble);
        $user_id = is_login();
        foreach ($gamble as $k => $v) {
            if($v['user_id'] != $user_id && $v['result'] == 0){
                //是否已被查看
                $gamble[$k]['is_check'] = M('quizLog')->where(['user_id'=>$user_id,'gamble_id'=>$v['id'],'game_type'=>1])->getField('id');
            }
        }
        $this->assign('gamble',$gamble);
        $this->position = '个人中心';

        $this->display();
    }

    //我的关注
    public function followUser()
    {
        $map            = $this->_search('followUser');
        $map['user_id'] = is_login();
        $list           = $this->_list(D('followUser'),$map,12,"follow_time desc",'','',"/UserInfo/followUser/p/%5BPAGE%5D.html");
        foreach ($list as $k => $v) {
            //获取今天推荐数量
            $blockTime = getBlockTime(1, $gamble = true);
            $list[$k]['gambleCount']   = M('gamble')->where(['user_id'=>$v['follow_id'],'create_time'=>['between',[$blockTime['beginTime'], $blockTime['endTime']]]])->count();
            //获取被关注人足球胜率
            $list[$k]['footballWin']   = $this->CountWinrate($v['follow_id']);
            //获取被关注人篮球胜率
            $list[$k]['basketballWin'] = $this->CountWinrate($v['follow_id'],2);
            $userInfo = M('FrontUser')->where(array('id'=>$v['follow_id']))->field('nick_name,head')->find();
            //获取被关注人昵称
            $list[$k]['nickname']      = $userInfo['nick_name'];
            //获取被关主人头像
            $list[$k]['face'] = FrontUserFace($userInfo['head']);
        }
        $this->assign('list',$list);
        $this->position = '个人中心';

        $this->display();
    }

    //我的粉丝
    public function myFans()
    {
        $map              = $this->_search('followUser');
        $map['follow_id'] = is_login();
        $list             = $this->_list(D('followUser'),$map,12,"follow_time desc",'','',"/UserInfo/myFans/p/%5BPAGE%5D.html");
        foreach ($list as $k => $v) {
            //获取今天推荐数量
            $blockTime = getBlockTime(1,$gamble=true);
            $where = ['user_id'=>$v['user_id'],'create_time'=>['between',[$blockTime['beginTime'],$blockTime['endTime']]]];
            $list[$k]['gambleCount']   = M('gamble')->where($where)->count();
            //获取关注人足球胜率
            $list[$k]['footballWin']   = $this->CountWinrate($v['user_id']);
            //获取关注人篮球胜率
            $list[$k]['basketballWin'] = $this->CountWinrate($v['user_id'],2);
            $userInfo = M('FrontUser')->where(array('id'=>$v['user_id']))->field('nick_name,head')->find();
            //获取关注人昵称
            $list[$k]['nickname']      = $userInfo['nick_name'];
            //获取关主人头像
            $list[$k]['face'] = FrontUserFace($userInfo['head']);
        }
        //我关注的人
        $followIdArr = M("FollowUser")->where(array('user_id'=>is_login()))->field("follow_id")->select();
        foreach ($followIdArr as $key => $value) {
            $followIds[] = $value['follow_id'];
        }
        $this->assign('followIds',$followIds);
        $this->assign('list',$list);
        $this->position = '个人中心';

        $this->display();
    }
    //文章列表
    public function list_e()
    {
        $user_id = is_login();
        $class_type = I("listType",1,'int');
        $user = M("FrontUser f")->field("f.expert_status,f.is_expert,f.id,f.nick_name,f.head,f.descript,(select count(fu.id) from qc_follow_user fu where fu.follow_id = f.id) as follow,(select count(pl.id) from qc_publish_list pl where pl.user_id = f.id) as publish_total")->where(['f.id'=>$user_id])->find();
        $user['head'] = frontUserFace($user['head']);
        if($user['is_expert'] != 1) {
            $this->ident();
            exit;
        }

        //内容列表
        $where['user_id'] = $user_id;
        $where['add_time'] = ['lt',time()];
        $url = '';
        $table = M('PublishList');
        $class = M('PublishClass');
        $field = 'click_number,content';
        switch($class_type) {
            case 2:
                $classArr = getPublishClass(0); //资讯分类数组
                $where['class_id'] = ['eq', 10];
                $url = '/listType/2';
                break;
            case 3:
//                $where['class_id'] = ['eq', 10];
                $classArr = getVideoClass(0);
                $url = '/listType/3';
                $table = M('Highlights');
                $class = M('HighlightsClass');
                $field = 'click_num as click_number,web_ischain';
                break;
            default:
                $classArr = getPublishClass(0); //资讯分类数组
                $where['class_id'] = ['neq', 10];
                $url = '/listType/1';
                break;
        }
        $list = $this->_list($table,$where,'5','add_time desc','id,class_id,title,remark,img,add_time,status,remark,'.$field,'',"/UserInfo/list_e".$url."/p/%5BPAGE%5D.html");
        $class_id = [];
        foreach($list as $key=>$value)
        {
            $class_id[] = $value['class_id'];
            if(!empty($value['img'])){
                $list[$key]['img'] = Tool::imagesReplace($value['img']);
            }else{
                //获取第一张图片
                $list[$key]['img']  = Tool::getTextImgUrl(htmlspecialchars_decode($value['content']),false)[0] ?:staticDomain('/Public/Home/images/common/loading.png');
            }
            $list[$key]['add_time'] = Tool::processTime($value['add_time']);
            $list[$key]['click_number'] = addClickConfig(1, $value['class_id'],$value['click_number'], $value['id']);
            //处理简介
            if(empty($value['remark']) && !empty($value['content']))
            {
                $content = strip_tags(htmlspecialchars_decode($value['content']));
                $list[$key]['remark'] = mb_substr($content,0,90,'utf-8').'...';
            }
            unset($list[$key]['content']);

            //组装url链接
            switch($class_type) {
                case 3:
                    $list[$key]['href'] = videoUrl($value,$classArr);
                    break;
                default:
                    $list[$key]['href'] = newsUrl($value['id'],$value['add_time'],$value['class_id'],$classArr);
                    break;
            }
        }
        $user['type'] = 'index';
        $userInfoTmp =  A("Home/Video")->author_info($user_id);
        $user['followNum'] = $userInfoTmp['followNum'];
        $user['new_num'] = $userInfoTmp['total'];
        $this->assign('user',$user);
        $this->assign('list',$list);
        $this->assign('class_type',$class_type);
        $this->display();
    }

    public function publish()
    {
        $user_info = M('FrontUser')->where(['id'=>is_login()])->find();
        if($user_info['is_expert'] != 1) {
            $this->ident();
            exit;
        }

		//每天不能超过10篇
		$new_num   = M('PublishList')->where(['user_id'=>is_login(), 'add_time'=>['between', [strtotime('00:00'), strtotime('23:59:59')]]])->count();
		$video_num = M('Highlights')->where(['user_id'=>is_login(), 'add_time'=>['between', [strtotime('00:00'), strtotime('23:59:59')]]])->count();
		if(($new_num + $video_num) >= 10){
			$this->redirect('UserInfo/list_e');
			exit;
		}

		$user_info['type'] = 'article';
        $this->assign('user',$user_info);
        if(I('type') != 2)
        {
            $new_class = M('PublishClass')->field('id,name')->where(['level'=>1,'status'=>1])->select();
            $video_class = M('HighlightsClass')->field('id,name,pid')->where(['status'=>1])->select();
            foreach($new_class as $key=>$val)
            {
                $new_class[$key]['type'] = 1;
            }
            $video_tmp_p = $video_tmp_son = [];
            foreach($video_class as $key=>$val)
            {
                $tmp = [];
                if($val['pid'] == 0)
                {
                    $tmp['id'] = $val['id'];
                    $tmp['name'] = $val['name'];
                    $tmp['type'] = 2;
                    $video_tmp_p[] = $tmp;
                }else{
                    $tmp['id'] = $val['id'];
                    $tmp['name'] = $val['name'];
                    $tmp['pid'] = $val['pid'];
                    $tmp['type'] = 2;
                    $video_tmp_son[] = $tmp;
                }
            }
            $class_p = array_merge((array)$new_class,(array)$video_tmp_p);
            $this->assign('class_p',$class_p);
            $this->assign('class_s',$video_tmp_son);

        }else{

            $game =  D('Cover')->findGameData(1);
            $bk =  D('Cover')->findGameData(2);
            $this->assign('game',$game);
            $this->assign('bk',$bk);
        }

        $this->display('list_e');
    }

    public function ident()
    {
        $token = md5(uniqid(rand(), TRUE));
        session('token',$token);
        $this->assign('token',$token);
        $user_info = M('FrontUser')->where(['id'=>is_login()])->find();
        if($user_info['is_expert'] == 1) $this->redirect('UserInfo/list_e');
        $user['status'] = $user_info['expert_status'];
        if($user_info['expert_status'] == 2)
        {
            $user['type'] = 'prompt';
            $this->assign('user',$user);
            $this->display('list_e');
            exit;
        }
        if($user_info['expert_status'] == 3)
        {
            $user['type'] = 'prompt';
            $user['reason'] = $user_info['reason'];
            $this->assign('user',$user);
            $this->display('list_e');
            exit;
        }
        $this->redirect('SportUser/index');
        exit;
    }

    /**
     * 保存/修改记录
     *
     * @return #
     */
    public function save(){
        if(empty($_POST['title'])) $this->redirect('UserInfo/list_e');
        if($_POST['class_st'] !== 'video')
        {

            $label = D('Cover')->contGetKey($_POST['content'],2);
            $where['user_id'] = is_login();
            $where['add_time'] = ['gt',time()-30];
            $res = M('PublishList')->where($where)->order('add_time desc')->limit(1)->find();
            if(isset($res))
            {
                if(($res['game_id'] == $_POST['game_id'] || $res['gamebk_id'] == $_POST['game_id']) && $res['title'] == $_POST['title'] && $res['play_type'] == $_POST['play_type']) $this->redirect('UserInfo/list_e');
            }
            $_POST['user_id'] = is_login();
            if($_POST['game_id'])
            {
                $listType = 2;
                $_POST['class_id'] = 10;
                $isCheck = M('Config')->where(['sign'=>'dujiaCheck'])->getField('config');
            }else{
                $isCheck = M('Config')->where(['sign'=>'newCheck'])->getField('config');
                $listType = 1;
                $_POST['class_id'] = $_POST['class_nd'];
                unset($_POST['class_st'],$_POST['class_nd'],$_POST['class_rd'],$_POST['weburl'],$_POST['remark']);
            }
            $_POST['is_original'] = 1;
            $_POST['ajax'] = 1;
            $model = D('PublishList');
            if (false === $model->create()) {
                $this->error($model->getError());
            }
            $model->label = $label;
            $model->add_time = time();
            $model->title = htmlspecialchars_decode($_POST['title']);
            $model->short_title = htmlspecialchars_decode($_POST['title']);
            /*if (!empty($_FILES['fileInput']['tmp_name'])) {
                $filetype = pathinfo($_FILES["fileInput"]["name"], PATHINFO_EXTENSION);//获取后缀
            }*/
            $model->update_time = time();
            $model->is_home = 1;
            if($listType == 2){

                switch ($_POST['play_type'])
                {
                    case '1':
                    case '-1':
                        if($_POST['gtype'] == 'bk')
                        {
                            $tmp[] = $_POST;
                            D('GambleHall')->getBkGoal($tmp,9);
                            $_POST = $tmp[0];
                            if(empty($_POST['fsw_exp_home']) && empty($_POST['fsw_exp_away']) && empty($_POST['fsw_total_home']) && empty($_POST['fsw_total_away']))
                            {
                                $tmp = M("GameBkinfo")->field('fsw_exp_home,fsw_exp,fsw_exp_away,fsw_total_home,fsw_total,fsw_total_away')->where(['game_id'=>$_POST['game_id']])->find();
                                $_POST = array_merge((array)$_POST,(array)$tmp);
                            }
                            switch ($_POST['play_type'])
                            {
                                case '1':
                                    $fsw_home = $_POST['fsw_exp_home'];
                                    $fsw = $_POST['fsw_exp'];
                                    $fsw_away = $_POST['fsw_exp_away'];
                                    break;
                                case '-1':
                                    $fsw_home = $_POST['fsw_total_home'];
                                    $fsw = $_POST['fsw_total'];
                                    $fsw_away = $_POST['fsw_total_away'];
                                    break;
                            }
                            switch ($_POST['chose_side'])
                            {
                                case '1':
                                    $model->odds = $fsw_home;
                                    $model->handcp = $fsw;
                                    $model->odds_other = $fsw_away;
                                    break;

                                case '-1':
                                    $model->odds = $fsw_away;
                                    $model->handcp = $fsw;
                                    $model->odds_other = $fsw_home;
                                    break;
                            }
                        }else{
                            D('GambleHall')->getHandcpAndOdds($_POST);
                            switch ($_POST['chose_side'])
                            {
                                case '1':
                                    $model->odds = $_POST['odds'];
                                    $model->handcp = $_POST['handcp'];
                                    $model->odds_other = $_POST['odds_other'];
                                    break;

                                case '-1':
                                    $model->odds = $_POST['odds_other'];
                                    $model->handcp = $_POST['handcp'];
                                    $model->odds_other = $_POST['odds'];
                                    break;
                            }
                        }
                        break;

                    case '2':
                    case '-2':
                        D('GambleHall')->getHandcpAndOddsBet($_POST);
                        $model->odds = $_POST['odds'];
                        $model->odds_other = $_POST['odds_other'];
                        $model->handcp = $_POST['handcp'];
                        break;
                }
                if($model->odds == ''){
                    $this->error('数据异常，请稍后再试',U('/UserInfo/publish',['type'=>2]));
                }
                if($_POST['gtype'] == 'bk')
                {
                    $gameinfo = M('GameBkinfo');
                    $model->gamebk_id = $_POST['game_id'];
                    $model->game_id = 0;
                    $gtype = 1;
                    unset($_POST['gtype'],$_POST['exp_value'],$_POST['fsw_exp_home'],$_POST['fsw_exp'],$_POST['fsw_exp_away'],$_POST['fsw_total_home'],$_POST['fsw_total'],$_POST['fsw_total_away']);
                }else{
                    $gameinfo = M('GameFbinfo');
                }
                //保存比赛时间为APP显示时间
                $app_time = $gameinfo->where(['game_id' => $_POST['game_id']])->getField('gtime');
            }
            $model->app_time = $app_time?$app_time:time();
            if(empty($_POST['add_time'])) $model->add_time = time();
            //为新增
            if($isCheck == 1)
                $model->status = 2;
            else
                $model->status = 1;
            $model->author = $_SESSION['authId'];
            $rs = $model->add();
            if($_POST['game_id'] || $_POST['gamebk_id'])
            {
                //上传图片
                if (!empty($_FILES['fileInput']['tmp_name'])) {
                    $return = D('Uploads')->uploadImg("fileInput", "publish", $rs,'',"[[400,400,{$rs}]]");
                }else{
                    $_FILES['fileInput'] = D("Cover")->cover($rs,$_POST['game_id'],$gtype);
                    $return = D('Uploads')->uploadImg("fileInput", "publish", $rs,'',"[[400,400,{$rs}]]");
                }
            }else{
                //上传图片
                if (!empty($_FILES['fileInput']['tmp_name'])) {
                    $return = D('Uploads')->uploadImg("fileInput", "publish", $rs,'',"[[400,400,{$rs}]]");
                }
            }
            $img_tab = M("PublishList");
            //百度主动推送
            $classArr = getPublishClass(0);
            $urls = array(
                newsUrl($rs, time(), $_POST['class_id'], $classArr),
            );
            $result = baiduPushNews($urls);
        }else{

            $label = D('Cover')->contGetKey($_POST['remark']);
            $listType = 3;
            $class_id = $_POST['class_rd']?$_POST['class_rd']:$_POST['class_nd'];
            $pname = M('HighlightsClass')->field('name')->where(['id'=>$class_id])->find();
            $game_type = 0;
            switch ($pname)
            {
                case '足球':
                    $game_type = 1;
                    break;
                case '篮球':
                    $game_type = 2;
                    break;
            }
            $isCheck = M('Config')->where(['sign'=>'videoCheck'])->getField('config');
            if($isCheck == 1)
                $status = 2;
            else
                $status = 1;
            $data = [
                'class_id'  =>  $class_id,
                'ame_type'  =>  $game_type,
                'web_url'   =>  $_POST['weburl'],
                'title'     =>  $_POST['title'],
                'remark'    =>  $_POST['remark'],
                'add_time'  =>  time(),
                'status'    =>  $status,
                'label'     =>  $label,
                'user_id'   =>  is_login(),
                'is_home'   =>  1
            ];
            $rs = M('Highlights')->add($data);
            $img_tab = M("Highlights");
            //上传图片
            if (!empty($_FILES['fileInput']['tmp_name'])) {
                $return = D('Uploads')->uploadImg("fileInput", "highlights", $rs,'',"[[600,600,\"{$rs}\"],[200,200,\"{$rs}_200\"]]");
            }
        }
        if($return['status'] == 1)
            $img_tab->where(['id'=>$rs])->save(['img'=>$return['url']]);
        
        sleep(2);
        $this->redirect('UserInfo/list_e',['listType'=>$listType]);
    }

    public function arraySequence($array, $field, $sort = 'SORT_DESC')
    {
        $arrSort = array();
        foreach ($array as $uniqid => $row) {
            foreach ($row as $key => $value) {
                $arrSort[$key][$uniqid] = $value;
            }
        }
        array_multisort($arrSort[$field], constant($sort), $array);
        return $array;
    }

    public function checkVerify()
    {
        $username = I('mobile');
        $isTrue = A('User')->checkMobileVerify(I('captcha'),$username);
        if(! $isTrue)
        {
            $this->error('验证码错误或已超时');
        }else{
            $this->success('验证成功！');
        }
    }

    public function saveIdent()
    {
        $rs = is_login();
        //上传图片
        if (!empty($_FILES['fileInput']['tmp_name'])) {
            $return = D('Uploads')->uploadImg("fileInput", "identfypic", $rs);
            if($return['status'] == 1)
                M("FrontUser")->where(['id'=>$rs])->save(['identfy_pic'=>$return['url'],'descript'=>$_POST['descript'],'expert_status'=>2]);
            $this->redirect('UserInfo/ident');
        }
    }

    //重新申请专家
    public function re_repert()
    {
        $user_id = is_login();
        M("FrontUser")->where(['id'=>$user_id])->save(['expert_status'=>0,'reason'=>'']);
        $this->redirect('SportUser/index');
    }

    //判断是否能够发布竞猜
    public function is_gamble()
    {
        if(IS_POST)
        {
            $play_type = I("game_type");
            $game_id = I("game_id");
            $gtype = I("gtype");
            $where['user_id'] = is_login();
            $where['play_type'] = $play_type;
            if($gtype == 'bk')
            {
                $where['gamebk_id'] = $game_id;
            }else{
                $where['game_id'] = $game_id;
            }
            $rs = M("PublishList")->where($where)->getField('id');
            if($rs)
            {
                $this->error('该场赛事玩法已经推荐过啦!');
            }else{
                $this->success('可以推荐');
            }
        }else{
            $this->error('数据异常!');
        }
    }




    /*
         * 推介发布时查询是否含有盘口
         */
    public function ajaxHaveOdda()
    {
        if(empty($_POST['play_type']) || empty($_POST['game_id'])) $this->ajaxReturn(['status'=>200,'msg'=>'']);
        unset($_POST['title'],$_POST['content']);
        $res = false;
        switch ($_POST['play_type'])
        {
            case '1':
            case '-1':
                if($_POST['gtype'] == 'bk')
                {
                    $tmp[] = $_POST;
                    D('GambleHall')->getBkGoal($tmp,9);
                    $_POST = $tmp[0];
                    if(empty($_POST['fsw_exp_home']) && empty($_POST['fsw_exp_away']) && empty($_POST['fsw_total_home']) && empty($_POST['fsw_total_away']))
                    {
                        $tmp = M("GameBkinfo")->field('fsw_exp_home,fsw_exp,fsw_exp_away,fsw_total_home,fsw_total,fsw_total_away')->where(['game_id'=>$_POST['game_id']])->find();
                        $_POST = array_merge((array)$_POST,(array)$tmp);
                    }
                    switch ($_POST['play_type'])
                    {
                        case '1':
                            if($_POST['fsw_exp'] === '') $res = true;
                            break;
                        case '-1':
                            if($_POST['fsw_total'] === '') $res = true;
                            break;
                    }
                }else{
                    D('GambleHall')->getHandcpAndOdds($_POST);
                    if($_POST['handcp'] === '') $res = true;
                }
                break;

            case '2':
            case '-2':
                D('GambleHall')->getHandcpAndOddsBet($_POST);
                if($_POST['handcp'] === '') $res = true;
                break;
        }
        if($res)
            $this->ajaxReturn(['status'=>201,'msg'=>'该赛事暂无赔率盘口,请稍候重试!!!']);
        else
            $this->ajaxReturn(['status'=>200,'msg'=>'']);
    }

	/**
	 * 判断资讯，推荐，视频是否超过10篇
	 */
	public function checkPublish(){
		$new_num   = M('PublishList')->where(['user_id'=>is_login(), 'add_time'=>['between', [strtotime('00:00'), strtotime('23:59:59')]]])->count();
		$video_num = M('Highlights')->where(['user_id'=>is_login(), 'add_time'=>['between', [strtotime('00:00'), strtotime('23:59:59')]]])->count();
		if(($new_num + $video_num) >= 10){
			$this->ajaxReturn(['status'=>1, 'msg'=>'感谢您的辛勤付出，由于您发布的文章已经达到今日的上限（10篇），请明日再继续坚持']);
		}else{
			$this->ajaxReturn(['status'=>0, 'msg'=>'']);
		}
	}

	//直播设置
    public function liveSet(){
        $userId = is_login();
        //查询房间信息
        $roomInfo = M('LiveUser')->where(['user_id'=>$userId])->find();
        $liveInfo = M('LiveLog')->where(['user_id'=>$userId,'live_status'=>['gt',0],'status'=>1])->order('add_time desc')->find();
        //获取赛事
        $mongo = mongoService();
        //获取今日赛事列表
        $dataService = new \Common\Services\DataService();
        $gameIdArr = $dataService->getGameTodayGids(1);

        if($gameIdArr) $baseRes = $mongo->select('fb_game',['game_id'=>['$in'=>$gameIdArr],'union_level'=>['$in'=>[0,1,2]],'game_state'=>['$in'=>[0,1,2,3,4]]],['game_id','game_start_datetime','union_name','union_color','home_team_name','away_team_name','home_team_id','away_team_id','union_id','start_time','game_start_timestamp','game_state']);
        $tmpGameListId = array_column($baseRes,'game_id');
        if($liveInfo['game_id'] > 0 && !in_array($liveInfo['game_id'],$tmpGameListId))
        {
            $gameChange = $mongo->select('fb_game',['game_id'=>(int)$liveInfo['game_id']],['game_id','game_start_datetime','union_name','union_color','home_team_name','away_team_name','home_team_id','away_team_id','union_id','start_time','game_start_timestamp','game_state']);
            $baseRes = array_merge($gameChange,$baseRes);
        }
        //處理賽事獲取聯賽列表
        $game = $union = $sort = [];
        foreach($baseRes as $key=>$val)
        {
                unset($val['_id']);
                $val['union_name'] = $val['union_name'][0];
                $val['home_team_name'] = $val['home_team_name'][0];
                $val['away_team_name'] = $val['away_team_name'][0];
                $game[$val['game_id']] = $val;
                $tmp = [];
                $tmp['union_id'] = $val['union_id'];
                $tmp['union_color'] = $val['union_color'];
                $tmp['union_name'] = $val['union_name'];
                $tmp['num'] = (int)$union[$val['union_id']]['num'] + 1;
                $baseRes[$key]['game_start_timestamp'] = strtotime($val['game_start_datetime']);
                $union[$val['union_id']] = $tmp;
                $sort[] = $val['game_start_timestamp'];
                if($liveInfo['game_id'] == $val['game_id']) $liveInfo['game'] = $val['home_team_name'].' vs '.$val['away_team_name'];
        }
        array_multisort($sort,SORT_ASC,$game);
        $this->assign('game',['game'=>$game,'union'=>$union]);
        if($liveInfo['id']) $liveInfo['replay_url'] =  D('Live')->getLiveUrl($liveInfo['room_id'],$liveInfo['start_time'],1);
        //查詢賽事關聯的直播人數
        $liveGame = M('LiveLog')->where(['game_id'=>['gt',0],'live_status'=>['gt',0],'status'=>1])->getField('game_id',true);
        $nowLive = [];
        foreach($liveGame as $val){
            $nowLive[$val] = (int)$nowLive[$val]+1;
        }
        if($liveInfo){
            $liveInfo['notice'] = [];
            $notice = M('LiveNotice')->where(['log_id'=>$liveInfo['id'],'status'=>1])->getField('id,content',true);
            if($notice) $liveInfo['notice'] = $notice;
        }
        if($liveInfo['img'])
            $liveLogo = Tool::imagesReplace($liveInfo['img']);
        else
            $liveLogo = Tool::imagesReplace($roomInfo['img']);
//        $liveLogo = imagesReplace($liveLogo);
        $this->assign('liveLogo',$liveLogo);
        $this->assign('nowLive',$nowLive);
        $this->assign('liveInfo',$liveInfo);
        $this->assign('roomInfo',$roomInfo);
        $this->display('UserInfo/liveSet');
    }

    //直播记录
    public function liveHistory(){
        $userId = is_login();
        $isLive = M('LiveUser')->where(['user_id'=>$userId])->getField('id');
        if(!$isLive) parent::_empty();
        //獲取直播記錄
        if(!empty($_POST['startTime']) || !empty($_POST['endTime'])){
            if(!empty($_POST ['startTime']) && !empty($_POST ['endTime'])){
                $startTime = strtotime($_POST['startTime']);
                $endTime   = strtotime($_POST['endTime']);
                $map['start_time'] = array('BETWEEN',array($startTime,$endTime));
            } elseif (!empty($_POST['startTime'])) {
                $strtotime = strtotime($_POST ['startTime']);
                $map['start_time'] = array('EGT',$strtotime);
            } elseif (!empty($_POST['endTime'])) {
                $endTime = strtotime($_POST['endTime']);
                $map['start_time'] = array('ELT',$endTime);
            }
        }
        $map['user_id'] = $userId;
//        $map['status'] = 1;
        if($_POST['title']) $map['title'] = ['like','%'.$_POST['title'].'%'];
        $list= $this->_list(M("LiveLog"),$map,10,'add_time desc','id,title,game_id,img,start_time,end_time,room_id,live_time,live_status','',"/UserInfo/liveHistory/p/%5BPAGE%5D.html");
        if($list !== false && !$list[0]['end_time']) $list[0]['live_time'] = intval((time() - (int)$list[0]['start_time'])/60);
        foreach($list as $key=>$val){
            $list[$key]['img'] = $val['img'] ? (string)Tool::imagesReplace($val['img']) : '';
        }
        $this->assign('liveLog',$list);
        $this->display('UserInfo/liveHistory');
    }

    //开启直播
    public function startLive(){
        //对数据进行验证
        $game_id = I('gameId');
        $title = I('title');
        if(mb_strlen($title) > 20) $this->ajaxReturn(['code'=>302,'msg'=>'直播标题字数超出!']);
        $img64 = I('img');
        //开播时间戳
        $startTime = time();
        $userId = is_login();
        if(!$userId) $this->ajaxReturn(['code'=>303,'请先登入!']);

        $user = M('LiveUser')->where(['user_id'=>$userId])->find();
        if($user['status'] != 1) $this->ajaxReturn(['code'=>304,'msg'=>'您没有权限开启直播!']);

        $liveInfo = M('LiveLog')->where(['user_id'=>$userId,'live_status'=>['gt',0],'status'=>1])->find();
        if($liveInfo) $this->ajaxReturn(['code'=>305,'msg'=>'您已开启了一场直播,请勿重复开启!']);
        if(strpos($img64,'add.png') !== false) $this->ajaxReturn(['code'=>301,'msg'=>'请上传封面图!']);
        if(strpos($img64,'data:image') !== false){
            $img = D('Uploads')->uploadFileBase64($img64, "liveimg", '', $userId.date('_YmdHi',$startTime));
            if($img['status'] != 1) $this->ajaxReturn(['code'=>301,'msg'=>'封面图设置失败!']);
            $img = $img['url'];
        }else{
            $img = M('LiveUser')->where(['user_id'=>$userId])->getField('img');
        }
        $data['user_id'] = $userId;
        $data['title'] = $title;
        $data['img'] = $img;
        $data['live_status'] = 3;
        if($game_id > 0) $data['game_id'] = $game_id;
        $data['start_time'] = $startTime;
        $data['add_time'] = time();
        $rs = M('LiveLog')->add($data);
        $tmp['room_id'] = $rs.$startTime;
        //保存房间号
        M('LiveLog')->where(['id'=>$rs])->save($tmp);
        //保存上传的封面图
        M('LiveUser')->where(['id'=>$user['id']])->save(['img'=>$img,'live_status'=>1]);
        //推流地址
        $url = D('Live')->getLiveUrl($tmp['room_id'],$startTime,1);
        $this->ajaxReturn(['code'=>200,'url'=>$url,'room_id'=>$tmp['room_id']]);
    }

    //直播暫停/繼續操作
    public function stopLive(){
        $liveInfo = $this->liveVerification();
        if($liveInfo['live_status'] == 1 || $liveInfo['live_status'] == 2){
            //设置是否暂停/继续
            D('Live')->ResumeLiveStream($liveInfo['room_id'],$liveInfo['start_time'],$liveInfo['live_status']);
            $status = 3-(int)$liveInfo['live_status'];
            if($status == 1){
                $saveData['live_status'] = 1;
            }else{
                $saveData['live_status'] = 2;
                $saveData['stop_time'] = time();
            }
            M('LiveLog')->where(['id'=>$liveInfo['id']])->save($saveData);
            //同步修改liveuser表数据
            $user = M('LiveUser')->where(['user_id'=>$liveInfo['user_id']])->find();
            M('LiveUser')->where(['id'=>$user['id']])->save(['live_status'=>$saveData['live_status']]);
            $this->ajaxReturn(['code'=>200,'data'=>$saveData['live_status']]);
        }else{
            $this->ajaxReturn(['code'=>203,'msg'=>'请在OBS推流后再进行操作!']);
        }
    }

    //直播暂停/继续操作推送
    public function mqttStopLive(){
        $liveInfo = $this->liveVerification();
        if($liveInfo['live_status'] == 1){
            sleep(10);
            $msg = ['notice_str'=>"主播直播进行中"];
            $action = 'liveContinue';
            $status = 1;
        }else{
            $msg = ['notice_str'=>"主播暂停直播中"];
            $action = 'livePause';
            $status = 2;
        }
        $this->liveHandle($msg,$action,$liveInfo['room_id']);


        //直播状态更改
        $data['notice_str'] = '主播状态描述!';
        $data['room_id'] = $liveInfo['room_id'];
        $data['live_status'] = $status;
        $this->liveHandle($data,'liveStatusChange',$liveInfo['room_id'],'qqty/live/notify');

        $this->ajaxReturn(['code'=>200,'data'=>'So Cooooooooool!']);
    }

    //消息重發接口
    public function reSend(){
        $liveInfo = $this->liveVerification();
        $key = I('id');
        $ad = M('LiveNotice')->where(['id'=>$key,'status'=>1])->find();
        if(!$ad) $this->ajaxReturn(['code'=>306,'msg'=>'该条信息不存在']);
        M('LiveNotice')->where(['id'=>$key])->setInc('count');
        $this->liveHandle(['notice_str'=>$ad['content']],'liveNotice',$liveInfo['room_id']);
        //处理要发送的消息
        $this->ajaxReturn(['code'=>200,'msg'=>'发送成功']);
    }

    //消息刪除接口
    public function delMsg(){
        $liveInfo = $this->liveVerification();
        $key = I('id');
        $ad = M('LiveNotice')->where(['id'=>$key,'status'=>1])->find();
        if(!$ad) $this->ajaxReturn(['code'=>306,'msg'=>'该条信息不存在']);
        M('LiveNotice')->where(['id'=>$key])->save(['status'=>0]);
        M('LiveLog')->where(['id'=>$liveInfo['id']])->save(['notice'=>json_encode($ad)]);
        //处理要发送的消息
        $this->ajaxReturn(['code'=>200,'msg'=>'删除成功']);
    }

    //消息发送
    public function toSend(){
        $liveInfo = $this->liveVerification();
        $msg = I('msg');
        $data['content'] = $msg;
        $data['log_id'] = $liveInfo['id'];
        $data['add_time'] = time();
        $data['status'] = 1;
        $data['count'] = 1;
        $res = M('LiveNotice')->add($data);
        if($res){
            $this->liveHandle(['notice_str'=>$msg],'liveNotice',$liveInfo['room_id']);
            $return = ['code'=>200,'msg'=>'发送成功!','key'=>$res];
        }else{
            $return = ['code'=>307,'msg'=>'发送失败!'];
        }
        //处理要发送的消息
        $this->ajaxReturn($return);
    }

    //直播设置验证
    public function liveVerification(){
        $userId = is_login();
        if(!$userId) $this->ajaxReturn(['code'=>303,'请先登入!']);
        $liveInfo = M('LiveLog')->where(['user_id'=>$userId,'live_status'=>['gt',0],'status'=>1])->order('start_time desc')->find();
        if(!$liveInfo) $this->ajaxReturn(['code'=>305,'msg'=>'当前直播已结束!']);
        return $liveInfo;
    }

    //停止直播
    public function overLive()
    {
        $liveInfo = $this->liveVerification();
        //保存推流时间为直播时间,保存回播地址
        $tmp = D('Live')->CreateLiveStreamRecordIndexFiles($liveInfo['room_id'], $liveInfo['start_time']);
        if($tmp){
            $data['replay_url'] = $tmp['RecordUrl'];
            $Duration = ceil($tmp['Duration'] / 60);
            $data['live_time'] = $Duration;
        }else{
            $data['live_time'] = 0;
            $data['status'] = 0;

        }
        $data['live_status'] = 0;
        $data['end_time'] = time();

        $res = M('LiveLog')->where(['id' => $liveInfo['id']])->save($data);
        if ($res) {
            $return = ['code' => 200, 'msg' => '直播已结束!'];
            $this->liveHandle(['notice_str'=>'主播停止直播！'],'liveStop',$liveInfo['room_id']);
            //同步修改liveuser表数据
            $user = M('LiveUser')->where(['user_id'=>$liveInfo['user_id']])->find();
            M('LiveUser')->where(['id'=>$user['id']])->save(['live_status'=>0]);
            D('Live')->ResumeLiveStream($liveInfo['room_id'],$liveInfo['start_time'],1);
            //处理要发送的消息

            //直播状态更改
            $data['notice_str'] = '主播状态描述!';
            $data['room_id'] = $liveInfo['room_id'];
            $data['live_status'] = 0;
            $this->liveHandle($data,'liveStatusChange',$liveInfo['room_id'],'qqty/live/notify');

        } else {
            $return = ['code' => 307, 'msg' => '直播结束异常!'];
        }
        $this->ajaxReturn($return);
    }

    //直播时修改标题,关联赛事信息
    public function updataLive(){
        $liveInfo = $this->liveVerification();
        $title = I('title');
        $game_id = I('game_id',0,'int');
        if(!empty($title)) $data['title'] = $title;
        $mongo = mongoService();
        $msg = [];

        //查询原关联赛事,进行推送修改
        $oldGameId = M('LiveLog')->field('game_id')->where(['id'=>$liveInfo['id']])->find();
        if($game_id == -1){
            $msg['msg'] = $liveInfo['titl'];
            $data['game_id'] = NULL;
            $this->liveHandle(['notice_str'=>'主播取消赛事关联啦','title'=>$liveInfo['title'],'room_id'=>$liveInfo['room_id']],'liveCancelGameLink',$liveInfo['room_id']);
            $this->liveHandle(['notice_str'=>'主播取消赛事关联啦','title'=>$liveInfo['title'],'room_id'=>$liveInfo['room_id']],'liveCancelGameLink',$liveInfo['room_id'],'qqty/live/notify');
            if($oldGameId['game_id'] > 0) $this->footballIcon($oldGameId['game_id'],'hidden',$liveInfo);
//            var_dump($oldGameId);exit;
        }elseif($game_id > 0){
            $data['game_id'] = $game_id;
            $game = $mongo->select('fb_game',['game_id'=>$game_id],['game_id','home_team_name','away_team_name'])[0];
//            $msg['msg'] = $game['home_team_name'][0].' <i>VS</i> '. $game['away_team_name'][0];
//            $msg['game_id'] = $game_id;

            $gameData['notice_str'] = '主播切换赛事啦';
            $gameData['game_id'] = (string)$game_id;
            $gameData['game_type'] = '1';
            $gameData['home_name'] = $game['home_team_name'][0];
            $gameData['away_name'] = $game['away_team_name'][0];
            $this->liveHandle($gameData,'liveSwitchGame',$liveInfo['room_id']);
            //主播列表入口切换关联赛事推送
            $gameData['room_id'] = $liveInfo['room_id'];
            $gameData['live_status'] = $liveInfo['live_status'];

            $this->liveHandle($gameData,'liveSwitchGame',$liveInfo['room_id'],'qqty/live/notify');
            $this->footballIcon($gameData['game_id'],'show',$liveInfo);
            if($oldGameId['game_id'] > 0) $this->footballIcon($oldGameId['game_id'],'hidden',$liveInfo);
        }

        if($oldGameId['game_id'] > 0) $this->pushLiveList($oldGameId['game_id']);//针对该场赛事推送变化
        if($data) $res = M('LiveLog')->where(['id'=>$liveInfo['id']])->save($data);
        if($game_id > 0) $this->pushLiveList($game_id);//针对该场赛事推送变化
        if($res)
            $return = ['code'=>200,'msg'=>'修改成功!'];
        else
            $return = ['code'=>307,'msg'=>'修改失败!'];
        //处理要发送的消息
        $this->ajaxReturn($return);
    }

    //主播切换赛事icon动态推送
    public function footballIcon($game_id,$type,$liveInfo){
	    $where['game_id'] = $game_id;
	    $where['live_status'] = ['in',[1,2]];
	    $where['status'] = 1;
	    $hasLive = M('LiveLog')->where($where)->count();
//	    var_dump($hasLive);
	    $data = [];
        switch($type){
            case 'show':
                if($hasLive == 0){
                    $data['notice_str'] = '有主播切换赛事!';
                    $data['game_id'] = $game_id;
                }
                break;
            case 'hidden':
                if($hasLive == 1){
                    $data['notice_str'] = '有主播切换赛事!';
                    $data['game_id'] = $game_id;
                }
                break;
        }
//        var_dump($data);
        if($data) $this->liveHandle($data,$type,$liveInfo['room_id'],'qqty/live/notify');
    }

    //推送该场赛事主播人员变化
    public function pushLiveList($game_id){
        $liveList = M('LiveLog lg')->field('lg.id,lg.room_id,lg.img,lg.start_time,lg.live_status,fu.id,fu.nick_name')->join('LEFT JOIN qc_front_user fu ON fu.id = lg.user_id')->where(['lg.game_id'=>$game_id,'lg.live_status'=>['gt',0],'lg.status'=>1])->select();
        if($liveList){
            $sort = [];
            foreach($liveList as $key=>$val){
                $liveList[$key]['live_url'] = D('Live')->getLiveUrl($val['room_id'], $val['start_time']);
                $liveList[$key]['mqtt_room_topic'] = 'qqty/live_' . $val['room_id'] . '/#';//mqtt room topic
                $liveList[$key]['img'] = (string)Tool::imagesReplace($val['img']);
                $sort[] = $val['live_status'];
            }
            array_multisort($sort,SORT_ASC,$liveList);
        }
        $this->liveHandle($liveList,'gameLiveList',$game_id,'qqty/woman_' .$game_id.'/list');
    }

    //主播操作時進行推送
    public function liveHandle($msg,$action,$id,$topic = ''){
        //mqtt
        $say['action'] = $action;
        $say['data'] = $msg;
        $say['status'] = 1;
        $say["dataType"] = "text";
        if($topic == '') $topic = 'qqty/live_' . $id . '/chat';
        $options = [
            'topic' => $topic,
            'payload' => $say,
            'clientid' => md5(time() . $id),
        ];
//        var_Dump($topic);
//        Mqtt($options);//mqtt推送
        mqttPub($options);//mqtt推送
    }

    //实时保存主播默认封面图
    public function saveLiveUserImg(){
	    $userId = is_login();
	    if(!$userId) $this->ajaxReturn(['code'=>303,'请先登入!']);
        $res = M('LiveUser')->where(['user_id'=>$userId])->find();
        $img64 = I('img');
        if(strpos($img64,'data:image') !== false){
            $img = D('Uploads')->uploadFileBase64($img64, "LiveUser", '', $res['id']);
            if($img['status'])
            {
                $img = $img['url'];
                M('LiveUser')->where(['id'=>$res['id']])->save(['img'=>$img]);
            }
        }
    }

    //获取直播状态
    public function getLiveStatus(){
        $liveInfo = $this->liveVerification();
        //判断是否因该结束直播
        switch((int)$liveInfo['live_status']){
            case 1:
                $data['status'] = 1;
                break;
            case 2:
            case 3:
                $data['status'] = 2;
                break;
        }
        $data['code'] = 200;
        $this->ajaxReturn($data);
    }


}