<?php

/**
 * 前台公共控制器
 * 为防止多分组Controller名称冲突，公共Controller名称统一使用分组名称
 * @author chenzj <443629770@qq.com>
 * @since  2016-4-19
 */
use Think\Controller;
use Think\Tool\Tool;

class CommonController extends Controller {
    private $_qctKey = 'quancaiappppa';
    private $_etcKey='i9acei83';
    private $seo  = [
        'seo_title' => '全球体育_足球比分、国际足球新闻、国内足球、篮球新闻、综合体育、电竞_全球体育手机网',
        'seo_keys'  => '足球比分,国际足球新闻,国内足球,篮球新闻,综合体育,电竞,彩票资讯,足球比分直播、视频集锦,英超,西甲,德甲,意甲,欧冠杯,亚冠,世界杯直播',
        'seo_desc'  => '全球体育网是一家涵盖国际足球、国内足球、篮球、NBA、综合体育类的大型体育新闻网，包括电竞、彩票资讯、足球比分直播等一系列体育竞技类最新资讯，还有英雄联盟、DOTA2、绝地求生、王者荣耀的精彩手游类视频集锦。',
    ];

    protected function _initialize() {
        //屏蔽ip跳转
        if(iosIpCheck()){
            header('location:http://a.app.qq.com/o/simple.jsp?pkgname=cn.qqw.app');
            die;
        }
        //不允许直接通过index访问
        $PATH_INFO = explode('/', $_SERVER['PATH_INFO']);
        if(count($PATH_INFO) == 1){
            if(strtolower($PATH_INFO[0]) == 'index'){
                self::_empty();
            }
        }
        if(count($PATH_INFO) > 1){
            if(strtolower($PATH_INFO[0]) == 'index' && strtolower($PATH_INFO[1]) == 'index'){
                self::_empty();
            }
        }
        //是否需要301转跳
        if(checkUrlExt() && $_SERVER['PATH_INFO'] != '' && IS_GET && preg_match("/^text\/html,(.*)/i", $_SERVER['HTTP_ACCEPT'])){
            $pathInfoArr = explode('.', $_SERVER['PATH_INFO']);
            if(count($pathInfoArr) > 1){
                self::_empty();
            }
            if(!$redirect301Path = S('redirect301Path')){
                $pathClass = getPublishClass() ?:[];  //资讯
                $redirect301Path = ['general','photo','video','Sporttery'];
                foreach ($pathClass as $k => $v) {
                    if(!empty($v['domain'])){
                        $redirect301Path[] = $v['domain'];
                    }
                }
                $redirect301Path = array_unique($redirect301Path);
                S('redirect301Path',$redirect301Path,86400);
            }
            //资讯目录301跳转
            $redirectPath = $pathInfoArr[0];
            if(in_array($redirectPath, $redirect301Path)){
                $url = SITE_URL.$_SERVER['HTTP_HOST'].'/'.$redirectPath.'.html';
                redirect301($url);
            }else{
                self::_empty();
            }
        }
        header_remove('Pragma');

        $this->assign('back_url',$_SERVER['HTTP_REFERER']);
        $nav = explode('/',$_SERVER['REQUEST_URI'])[1];
        $_nav = strtolower($nav);
        $this->assign('footer_nav',$_nav?$_nav:'index');
		$user_auth = session('user_auth');
        $this->assign('user_authId',$user_auth['id']);
        $this->assign('is_log',is_login()?'1':'0');

        $code=I('get.channel_code','');
        if($code){
            cookie('login_code',$code);
        }

		if (!empty($user_auth) && !in_array(ACTION_NAME, ['nickname','tpperfect']))
		{
			//判断昵称是否为空
            $userInfo = M('FrontUser')->master(true)->field('id,nick_name')->where(['id'=>$user_auth['id']])->find();
            if ($userInfo['id'] == '')
            {
                session_destroy();
                echo "<script>alert('请重新登录！');window.location.href='".U('User/login')."'</script>";
                exit;
            }
			if ($userInfo['nick_name'] == '')
			{
                echo "<script>alert('请设置您的昵称！');window.location.href='".U('User/nickname')."'</script>";
				exit;
			}
			if (empty($user_auth['nick_name']))
			{
				D('FrontUser')->autoLogin($user_auth['id']);
			}
			$user_auth = session('user_auth');
			$this->assign('user_auth',$user_auth);
		}

        if(ACTION_NAME != 'nickname') {
            //注册赠送礼包
            $gift1 = M('GiftsConf')->field('id, name, before_img, after_img')
                ->where(['type' => 1, 'start_time' => ['lt', NOW_TIME], 'end_time' => ['gt', NOW_TIME], 'status' => 1])
                ->order(' id desc ')->limit(1)->find();

            if ($gift1) {
                $gift1['after_img'] = (string)Tool::imagesReplace($gift1['after_img']);
                $gift1['open'] = (string)S("gift1_open_{$user_auth['id']}_{$gift1['id']}");//弹框标志
                $gift1['close'] = (string)S("gift1_close_{$user_auth['id']}_{$gift1['id']}");
            } else {
                $gift1['after_img'] = '';
                $gift1['open'] = '';
                $gift1['close'] = '';
            }
            $this->assign('gift1', $gift1);

            //活动赠送礼包
            $gift2 = M('GiftsConf')->field('id, name, before_img, after_img')
                ->where(['type' => 3, 'start_time' => ['lt', NOW_TIME], 'end_time' => ['gt', NOW_TIME], 'status' => 1])
                ->order(' id desc ')->limit(1)->find();

            if ($gift2) {
                $gift2['before_img'] = (string)Tool::imagesReplace($gift2['before_img']);
                $gift2['after_img'] = (string)Tool::imagesReplace($gift2['after_img']);
                $gift2['close'] = (string)S("gift2_close_{$user_auth['id']}_{$gift2['id']}");
            } else {
                $gift2['before_img'] = '';
                $gift2['after_img'] = '';
                $gift2['close'] = '';
            }

            $this->assign('gift2', $gift2);

            //注册后要弹活动的框
            $gift2_frame = I('gift2_frame', '', 'strval');
            $this->assign('gift2_frame', $gift2_frame);
        }
        //获取站点配置
        if(!$setting = S('web_site_setting')){
            $setting = getWebConfig('setting');
            S('web_site_setting',$setting,86400);
        }
        $this->assign('site_setting',$setting);

        //当前url
        $canonical = 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $this->assign('canonical',$canonical);
        
        //客服链接
        $this->assign('liveChatUrl',getLivezillaUrl());
    }
    
    protected function curl_init(array $params, $secretKey){
        $sortString = $this->buildSortString($params);
        $signature = hash_hmac('sha1', $sortString, $secretKey,FALSE);
        return $signature;
    }
        //获取banner图片
    protected function get_recommend($sign,$limit){
        $recommend = Tool::getRecommend($sign,$limit,2);
        return $recommend;
    }
        /**
     * 构造排序字符串
     * @param array $params
     * @return string
     */
    protected function buildSortString(array $params) {
        if(empty($params)){
            return '';
        }

        ksort($params);

        $fields = array();

        foreach ($params as $key => $value) {
            $fields[] = $key . '=' . $value;
        }

        return implode('&',$fields);
    }
    /**
     * 全球体育CURL方法
     *
     * @param string $url 链接地址
     * @param string $data 传输的变量 多个使用&拼接
     * @param string $domain 接口地址
     * @param string $type 传输方式，默认post
     * @return string 返回数据
     */
    protected function get_curl($url, $data,$domain,$type = 'POST') {
        import('Vendor.Signature.SignatureHelper');
        $signObj = new \SignatureHelper();
        $arr = [];
        if($data){
            $param = explode('&', $data);
            foreach ($param as $v) {
                $val = explode('=', $v);
                $arr[$val[0]] = $val[1];
            }
            $data.='&';
        }
        $arr['t']=time();
        $sign =$signObj->sign($arr, $this->_qctKey);
        if(!$sign){
            return array('status'=>1,'error'=>403);
        }
        $data .= 't='.$arr['t'].'&sign='.$sign.'&nosign='.C('nosignStr');
        $header=array();
        $rsl=$this->do_curl($domain.$url,$data,$header,$type);
        if($rsl){
            return json_decode($rsl, true);
        }else{
            return false;
        }
    }
    
    /**
     * ETC CURL方法
     *
     * @param string $url 接口地址
     * @param array $data 传输的变量 数组
     * @param string $type 传输方式，默认post
     * @return string 返回数据
     */
    protected function etc_curl($url,$data,$type = 'POST') {
//        if(!$data){
//            return array('status'=>1,'error'=>402);
//        }
        $data['ts']=time();
        ksort($data);
        $sign=$param='';
        foreach ($data as $key=>$val){
            $sign.=$key.$val;
            $param.=$key.'='.$val.'&';
        }
        $sign.=$this->_etcKey;
        $sign=md5($sign);
        if(!$sign){
            return array('status'=>1,'error'=>403);
        }
        $param.='sign='.$sign;
        $header[]='KEY:ouzhoubei';
        $rsl=$this->do_curl($url,$param,$header,$type);
        if($rsl){
            return json_decode($rsl, true);
        }else{
            return false;
        }
    }
    
    /**
     * CURL方法
     *
     * @param string $url 链接地址
     * @param string $data 传输的变量
     * @param string $type 传输方式，默认post
     * @param string $for 来自
     * @return string 返回数据
     */
    protected function do_curl($url, $data,$header,$type = 'POST',$for) {
        $curlobj = curl_init();
        if($type=='POST'){
            curl_setopt($curlobj, CURLOPT_URL, $url);
        }else{
            if($data){
                curl_setopt($curlobj, CURLOPT_URL, $url.'?'.$data);
            }else{
                curl_setopt($curlobj, CURLOPT_URL, $url);
            }
        }
        curl_setopt($curlobj, CURLOPT_HEADER, 0);
        curl_setopt($curlobj, CURLOPT_RETURNTRANSFER, 1);
        if($for=='wx'){
            curl_setopt($curlobj,CURLOPT_SSL_VERIFYPEER,FALSE);
            curl_setopt($curlobj,CURLOPT_SSL_VERIFYHOST,FALSE);//严格校验2
        }
        if ($type == 'POST') {
            curl_setopt($curlobj, CURLOPT_POST, 1);
            curl_setopt($curlobj, CURLOPT_POSTFIELDS, $data);
            $header[]="Content-length: ".strlen($data);
        }
        if (substr($url, 0, 5) == "https") {
            curl_setopt($curlobj, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
            curl_setopt($curlobj, CURLOPT_SSL_VERIFYHOST, FALSE);
        }
       $header[]="application/x-www-form-urlencoded; charset=utf-8";
        curl_setopt($curlobj, CURLOPT_HTTPHEADER, $header);
        $rtn = curl_exec($curlobj);
        if (curl_errno($curlobj) != 0) {
            return false;
        }
        curl_close($curlobj);
        return $rtn;
    }
    /**
     * 提取广告
     *
     * @param int $class_id 分类id 
     * @return arr 返回数据
     */
    protected function get_adver($class_id, $limit = 5) {
        $class_id = intval($class_id);
        if ($class_id < 1) {
            return false;
        }
        $time = NOW_TIME;
        $list = M('adver_list')->where(" ( `status` = 1 ) AND ( `class_id` = $class_id ) AND ( $time BETWEEN `online_time` AND `end_time`)")->order('sort desc')->limit($limit)->select();
        return $list;
    }
    
    
     /**
     * 获取近十场推荐结果
     *
     * @param int  $id        会员id
     * @param int  $gameType  赛事类型(1:足球   2:篮球   默认为1)
     *
     * @return  array
    */
    public function getTenGamble($id,$gameType=1,$playType=1){
        //条件会员id
        $where['user_id']    = $id;
        //赛事类型
        $Model = $gameType == 1 ? M('gamble') : M('gamblebk');
        //过滤掉未出结果的
        $where['result']     = array('in',[1,0.5,2,-1,-0.5]);
        if($gameType == 1){
            $where['play_type'] = $playType == 1 ? ['in',[1,-1]] : ['in',[2,-2]];
        }
        //近10场比赛结果
        $tenArray = $Model->where($where)->order("id desc")->limit(10)->field('result')->select();
        return $tenArray;
    }
    
    //兑换中心
    public function exchange()
    {
        if (!IS_AJAX)
            return;

        if (!$userid = is_login())
            $this->error('请先登录!');
        
        $key=I('post.key');
        if($key<1 || $key>4)
            $this->error('参数有误!');
        
        $config = getWebConfig('platformSetting');
        $userPoint = M('FrontUser')->where(['id'=>$userid])->getField('point');
        if ($userPoint < 5000)
            $this->error('您的积分不足5000,无法兑换');
        
        $leftPoint = $userPoint - $config['point2Coin'.$key];

        if ($leftPoint < 0)
            $this->error('积分不够');

        if (M('FrontUser')->where(['id'=>$userid])->save(['point'=>$leftPoint,'coin'=>['exp','coin+'.$config['coin'.$key]]]) == false)
            $this->error('兑换失败');

        //增加积分记录
        $insertPointLog = M('PointLog')->add([
            'user_id'     => $userid,
            'log_time'    => time(),
            'log_type'    => 6,
            'change_num'  => $config['point2Coin'.$key],
            'total_point' => $leftPoint,
            'desc'        => '积分兑换'
        ]);

        //添加球币记录
        $userCoin = M('FrontUser')->master(true)->field(['point','coin','unable_coin'])->where(['id'=>$userid])->find();
        $insertCoinLog = M('AccountLog')->add([
            'user_id'    =>  $userid,
            'log_time'   =>  time(),
            'log_type'   =>  6,
            'log_status' =>  1,
            'change_num' =>  $config['coin'.$key],
            'total_coin' =>  $userCoin['coin']+$userCoin['unable_coin'],
            'desc'       =>  "积分兑换",
            'platform'   =>  4,
            'operation_time' => time()
        ]);
        $info=array(
          'message'=>  '您已成功兑换金币！',
          'point'=> $userCoin['point'],
          'total_coin'=>$userCoin['coin']+$userCoin['unable_coin'],
        );
        $this->success($info);
    }
    //检查是否登陆
    public function ajaxCheckLogin()
    {
        //session是否已登录
        if(session('user_auth') == NULL){
            $u_p = Tool::getArrayCookie('u_p');
            if($u_p){
                //cookie存在使用cookie登录
                $id = D('FrontUser')->login($u_p['username'], $u_p['password']);
                if(0 < $id){
                    D('FrontUser')->autoLogin($id);
                }
            }
        }
        //获取登录id
        if(is_login()){
            $userArr = M('FrontUser')->where(['id'=>is_login()])->field('id,status,nick_name,password,session_id')->find();
            //是否在别处登录
            if(session_id() !== $userArr['session_id']){
                D('FrontUser')->logout();
                $this->error("您已在别处登录！");
            }
            //获取禁用状态
            if($userArr['status'] == 0){
                D('FrontUser')->logout();
                $this->error("您的帐号已被禁用，不能登录！");
            }
            //比对密码
            if($userArr['password'] !== session('user_auth')['password']){
                D('FrontUser')->logout();
                $this->error("您的帐号密码已被修改，请重新登录！");
            }
            //获取未读的消息
            $userArr['msg'] = M("Msg")->where(array('front_user_id'=>is_login(),'is_read'=>0))->count();
            unset($userArr['status'],$userArr['password'],$userArr['session_id']);
            $this->success($userArr);
        }else{
            $this->error("0");
        }
    }
    //获取城市
    public function getCity()
    {
        if (!IS_AJAX)
            return;

        $regionid = I('post.regionid');

        if (!$regionid)
        {
            $this->error('获取失败!');
        }

        $city = M('Area')->field('id,region_name')->where(['parent_id'=>$regionid])->select();
        $this->success($city);
    }
        /**
     * 获取红人榜(读取数据表)
     *
     * @param int  $gameType     赛事类型(1:足球   2:篮球   默认为1)
     * @param int  $number       提取前多少名(默认false全部)
     *
     * @return  array
    */
    public function getRedList($gameType=1,$page=1,$limit=30){
        $where['game_type']  = $gameType;
        $where['list_date']  = date('Ymd',strtotime("-1 day"));
        //从数据表获取昨日红人榜
        $_redList=M('redList r');
        $Ranking = $_redList
                   ->join("LEFT JOIN qc_front_user f on f.id=r.user_id")
                   ->field("r.*,f.nick_name,f.head")
                   ->where($where)->page($page,$limit)->order('ranking asc')->select();
        $is_login=is_login();
        $myRank=[];
        if(!$Ranking){
            //没有就获取前天的
            $where['list_date']  = date('Ymd',strtotime("-2 day"));
            //从数据表获取昨日红人榜
            $Ranking = M('redList r')
                       ->join("LEFT JOIN qc_front_user f on f.id=r.user_id")
                       ->field("r.*,f.nick_name,f.head")
                       ->where($where)->page($page,$limit)->order('ranking asc')->select();
        }
        if($is_login){
            $where['user_id']=$is_login;
            $data=$_redList->field('ranking,win,half,level,transport,donate,winrate,pointCount')->where($where)->order('list_date desc')->find();
            if($data){
                $myRank['ranking']=$data['ranking'];
                $myRank['win']=$data['win']+$data['half'];
                $myRank['level']=$data['level'];
                $myRank['transport']=$data['transport']+$data['donate'];
                $myRank['winrate']=$data['winrate'];
                $myRank['pointCount']=$data['pointCount'];
            }
        }
        foreach ($Ranking as $k => $v) {
            $Ranking[$k]['face'] = frontUserFace($v['head']);
        }
        return [$myRank,$Ranking];
    }
    
    /*
     *      微信分享內容控制 
     */
    public function wxShar(){
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            $wxpay_config = C('wxpay.wxpay_config');
            //获取token
            $token=S('WxAccessToken');
           if(!$token){
                $tokenInfo = $this->do_curl('https://api.weixin.qq.com/cgi-bin/token', 'grant_type=client_credential&appid=' . $wxpay_config['appid'] . '&secret=' . $wxpay_config['appsecret']);
                $tokenInfo = json_decode($tokenInfo, true);
                S('WxAccessToken',$tokenInfo['access_token'],7000);
                $token=$tokenInfo['access_token'];
            }
            $ticket=S('WxTicket');
            if(!$ticket){
                $jsapi = $this->do_curl('https://api.weixin.qq.com/cgi-bin/ticket/getticket', 'access_token=' . $token . '&type=jsapi', array(), 'GET', 'wx');
                $jsapi = json_decode($jsapi, true);
                S('WxTicket',$jsapi['ticket'],7000);
                $ticket=$jsapi['ticket'];
            }
            $rankstr=GetRandStr(16);
            $url='https://m.qqty.com'.$_SERVER["REQUEST_URI"];
            $params=array(
                'noncestr'=>$rankstr,
                'jsapi_ticket'=>$ticket,
                'timestamp'=>NOW_TIME,
                'url'=>$url,
            );
            ksort($params);
            foreach($params as $key => $value){
                $string[]=$key.'='.$value;
            }

            $string=implode('&',$string);
            $signature=sha1($string);
            $this->assign('link',$url);
            $this->assign('time',NOW_TIME);
            $this->assign('appid',$wxpay_config['appid']);
            $this->assign('nonceStr',$rankstr);
            $this->assign('signature',$signature);
        }
    }
	
	//批量更新
	/**
	 * User liangzk <Liangzk@qc.com>
	 * @param $datas 数据--二维数组-----数组里一定传ID
	 * @return false|int
	 *
	 *
	 * 注意---数组里一定传ID
	 */
	public function saveAll($datas,$model){
		$model || $model=$this->name;
		$sql   = ''; //Sql
		$lists = []; //记录集$lists
		foreach ($datas as $data) {
			foreach ($data as $key=>$value) {
				if('id' ===$key){
					$ids[]=$value;
				}else{
					$lists[$key].= sprintf("WHEN %u THEN '%s' ",$data['id'],$value);
				}
			}
		}
		foreach ($lists as $key => $value) {
			$sql.= sprintf("`%s` = CASE `%s` %s END,",$key,'id',$value);
		}
		
		$sql = sprintf('UPDATE __%s__ SET %s WHERE %s IN ( %s )',$model,rtrim($sql,','),'id',implode(',',$ids));
		
		return M()->query($sql);
	}
	
    /* 空操作，用于输出404页面 */
    public function _empty(){
        header("HTTP/1.1 404 Not Found");  
        header("Status: 404 Not Found");  
        $this->display('Public/error');
        die;
    }

    /**
     * PC端--球完订购弹框检验公共接口（该方法结合球王购买的JS弹框）
     *Liangzk 《Liamgzk@qc.com》
     * 2017-04-26
     */
    public function qwBuyCheck()
    {
        $userId = is_login();
        $productId = I('productId',0,'int');

        //判断是否登录
        if (!$userId) $this->AjaxReturn(['status'=>-1,'errorCode'=>1111,'msg'=>'请登录！']);

        //判断产品ID类型、范围是否合法
        if ($productId < 1) $this->AjaxReturn(['status'=>-1,'errorCode'=>0000,'msg'=>'操作失败！']);

        $products = M('introProducts')->master(true)->field('id,name,total_num,sale,create_time')->where(['id' => $productId])->find();
        if (empty($products)) $this->AjaxReturn(['status'=>-1,'errorCode'=>8011,'msg'=>'操作失败！']);

        //金币是否足够
        $frontUser = M('FrontUser')->master(true)->field('coin, username, unable_coin')->where(['id' => $userId])->find();
        $total_coin = $frontUser['coin'] + $frontUser['unable_coin'];
        if ($total_coin <= 0 || $total_coin < $products['sale']) $this->AjaxReturn(['status'=>-1,'errorCode'=>8009,'msg'=>'余额不足！','saleCoin'=>$products['sale']]);

        $this->AjaxReturn(['status'=>1,'msg'=>'查看该服务需要花费'.$products['sale'].'金币','saleCoin'=>$products['sale']]);
    }
    /**
     * PC端--球完订购公共接口（该方法结合球王购买的JS弹框）
     *Liangzk 《Liamgzk@qc.com》
     * 2017-04-26
     */
    public function setQwBuy()
    {
        $userId = is_login();
        $productId = I('productId',0,'int');
        //判断产品ID类型、范围是否合法
        if ($productId < 1) $this->AjaxReturn(['status'=>-1,'errorCode'=>0000,'msg'=>'操作失败！']);
        //判断是否登录
        if (!$userId) $this->AjaxReturn(['status'=>-1,'errorCode'=>1111,'msg'=>'请登录！']);

        //购买操作
        $buyRes = D('GambleHall')->introOrder($userId,$productId,1);

        if ($buyRes['status'] == true)
        {
            $blockTime = getBlockTime(1, true);
            //获取用户购买的产品的推介赛事
            $introGamble = M('IntroGamble ig')
                ->join('INNER JOIN qc_intro_lists il ON ig.list_id = il.id')
                ->where(['il.status'=>1,'il.product_id'=>$productId,'il.pub_time'=>['lt',time()],'il.create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]]])
                ->field('ig.product_id,ig.union_name,ig.home_team_name,ig.away_team_name,ig.score,ig.result,ig.gtime,ig.handcp,ig.odds,ig.play_type,ig.chose_side')
                ->order('gtime asc')
                ->select();
            if (!empty($introGamble))
            {
                foreach ($introGamble as $key => $value)
                {
                    $introGamble[$key]['gtime'] = date('m/d H:i',$value['gtime']);
                }
                $introGamble = HandleGamble($introGamble,0,true);
            }
        }

        $this->AjaxReturn(['status'=>1,'res'=>$buyRes,'introGamble'=>empty($introGamble) ? null : $introGamble]);
    }

    //广告转跳
    public function adver()
    {
        $adver_id = I('adver_id');
        $url = D('Common')->getAdverUrl($adver_id,2);
        header("location:".$url);
    }

    //设置站点优化
    protected function setSeo($info = []){
        foreach($info as $k => $v){
            if($v) $this->seo[$k] = $v;
        }
        $this->assign('seo',$this->seo);
    }
}
