<?php
ob_end_clean();
/**
 * 前台公共控制器
 * @author dengwj <406516482@qq.com>
 * @since  2015-11-27
 */
use Think\Controller;
use Think\Tool\Tool;
class CommonController extends Controller {
    //商城与qqty共用cookie
    public $u_info = array();
    private $seo  = [
            'seo_title' => '足球比赛_足球比分网_足球即时比分_足球推荐_足球比分直播网-全球体育网',
            'seo_keys'  => '足球比赛,足球比分网,足球即时比分,足球推荐,足球比分直播网',
            'seo_desc'  => '全球体育网为您提供专业足球推荐,足球比赛新闻，是广大球迷们获取足球比分，足球即时比分资讯的足球比分直播网。',
        ];

	protected function _initialize(){
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
                $publishClass = getPublishClass(0) ?:[];  //资讯
                $videoClass   = getVideoClass(0)   ?:[];  //集锦
                $galleryClass = getGalleryClass(0) ?:[];  //图库
                $pathClass = array_merge($publishClass,$videoClass,$galleryClass);
                $redirect301Path = ['news'];
                foreach ($pathClass as $k => $v) {
                    if(!empty($v['path'])){
                        $redirect301Path[] = $v['path'];
                    }
                }
                $redirect301Path = array_unique($redirect301Path);
                S('redirect301Path',$redirect301Path,86400);
            }
            //资讯，图库，集锦目录301跳转
            $redirectPath = $pathInfoArr[0];
            if(in_array($redirectPath, $redirect301Path)){
                $url = SITE_URL.$_SERVER['HTTP_HOST'].'/'.$redirectPath.'.html';
                redirect301($url);
            }else{
                self::_empty();
            }
        }
        header_remove('Pragma');
        //将加密数据进行格式转换
        $this->u_info = json_decode(D('FrontUser')->decrypt($_COOKIE['u_k']),true);
        if($this->u_info['q_log_status'])
        {
            $this->shoplogin();
            cookie('us_fjs',$this->u_info['u_k']);
        }else{
            cookie('us_fjs',null);
        }
        $this->userId = is_login();
        //客服链接
        $this->assign('liveChatUrl',getLivezillaUrl());

        //获取站点配置
        if(!$setting = S('web_site_setting')){
            $setting = getWebConfig('setting');
            S('web_site_setting',$setting,86400);
        }
        $this->assign('site_setting',$setting);

        //当前url
        $canonical = 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $this->assign('canonical',$canonical);
    }

    //设置站点优化
    protected function setSeo($info = []){
	    foreach($info as $k => $v){
	        if($v) $this->seo[$k] = $v;
        }
        $this->assign('seo',$this->seo);
    }

    /**
     * 根据表单生成查询条件
     * @param object    $dwz_db_name  数据对象
     * 进行列表过滤-
     */
    protected function _search($dwz_db_name = '') {
        $dwz_db_name=$dwz_db_name ? $dwz_db_name : $this->getActionName();
        //生成查询条件
        $model = D($dwz_db_name);
        $map = array();
        foreach ($model->getDbFields() as $key => $val) {
            if (isset($_REQUEST [$val]) && $_REQUEST [$val] != '') {
                //特别指定一些字段进行模糊查询
                $likeArray = array(
                    'id',
                    'game_id',
                    'team_name',
                    'union_name',
                    'name',
                    'title',
                    'username',
                    'nick_name',
                    'true_name',
                );
                if (in_array($val, $likeArray)){
                    //模糊查询
                    $map [$val] = array('like', '%'.$_REQUEST [$val].'%');
                } else {
                    //精确查询
                    $map [$val] = $_REQUEST[$val];
                }
            }
        }
        return $map;
    }

    /**
     * 根据表单生成查询条件-进行列表过滤
     *
     * @param object    $model      数据对象
     * @param array     $map        过滤条
     * @param array     $listRows   每页显示条数
     * @param string    $order      排序
     * @param string    $field      提取字段
     * @param string    $style      自定义样式
     * @param string    $url        指定分页链接
     *
     * @return array  #
    */
    protected function _list($model, $map, $listRows, $order = '', $field="*",$style=false, $url,$pageType=1) {

        //取得满足条件的记录数
        $count = $model->where ( $map )->count ();
        if ($count > 0) {
            //创建分页对象
            if (! empty ( $listRows )) {
                $listRows = $listRows;
            } else {
                $listRows = 15;
            }
            //实例化分页类
            $page = new \Think\Page ( $count, $listRows );
            //处理排序
            if (empty($order)) {
                $order = $_REQUEST['order'];
            }
            if (empty($order)) {
                $order = "id desc";
                $_REQUEST['order'] = $order;
            }
            //分页查询数据
            $voList = $model->where($map)->group("id")->field($field)->order($order)->limit($page->firstRow . ',' . $page->listRows)->select();
            //分页跳转的时候保证查询条件
            foreach ( $map as $key => $val ) {
              if (! is_array ( $val )) {
                $page->parameter .= "$key=" . urlencode ( $val ) . "&";
              }
            }
            //是否使用自定义样式
            if($style){
                $page->config  = array(
                    'header' => '<span class="rows">共 %TOTAL_ROW% 条记录</span>',
                    'prev'   => '<span aria-hidden="true">上一页</span>',
                    'next'   => '<span aria-hidden="true">下一页</span>',
                    'first'  => '首页',
                    'last'   => '...%TOTAL_PAGE%',
                    'theme'  => '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%',
                );
            }
            if (!empty($url)) {
                $page->url = $url;
            }
            //模板赋值显示
            if($pageType==1){
                $this->assign ( "show", $page->showJs());
            }else{
                $this->assign ( "show", $page->showJump());
            }
            $this->assign('totalCount', $count );
            $this->assign('numPerPage', $page->listRows );
            //同时返回，以便对需要重新组装的数据进行操作
            return $voList;
        } else {
            return false;
        }
    }

    /**
     * 计算推荐胜率或更多详情
     *
     * @param int  $id        会员id
     * @param int  $gameType  赛事类型(1:足球   2:篮球   默认为1)
     * @param int  $dateType  时间类型(1:周胜率 2:月胜率 3:季胜率 默认为1)
     * @param bool $more      更多详情记录(flase:否 true:是 默认为否)
     * @param bool $isCount   是否只计算推荐场数(flase:否 true:是 默认为否)
     *
     * @return int or array  #
    */
    public function CountWinrate($id,$gameType=1,$dateType=1,$more=false,$isCount=false,$playType=0,$gambleType=1){
        return D('GambleHall')->CountWinrate($id,$gameType,$dateType,$more,$isCount,$playType,$gambleType);
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

    /**
     * 获取最新推荐
     *
     * @param int  $userId    会员id
     * @param int  $gameType  赛事类型(1:足球   2:篮球   默认为1)
     *
     * @return  array
    */
    public function getNewGamble($userId,$gameType){
        $GambleModel = $gameType == 1 ? D('GambleView') : D('GamblebkView');
        //根据推荐时间
        $blockTime = getBlockTime($gameType,$gamble=true);
        $where = ['user_id'=>$userId,'create_time'=>['between',[$blockTime['beginTime'],$blockTime['endTime']]]];
        $gamble = $GambleModel->where($where)->order('id desc')->select();
        return $gamble;
    }

    /**
     * 获取排行榜(读取数据表)
     *
     * @param int  $gameType     赛事类型(1:足球   2:篮球   默认为1)
     * @param int  $dateType     时间类型(1:周胜率 2:月胜率 3:季胜率 默认为1)
     * @param int  $user_id      是否查找指定用户,默认为否
     * @param int  $more         是否近10场和连胜,默认为是
     * @param int  $number       提取前多少名(默认false全部)
     *
     * @return  array
    */
    public function getRankingData($gameType=1,$dateType=1,$user_id=null,$more=true,$number=false){
        list($begin,$end)    = getRankDate($dateType);
        $where['gameType']   = $gameType;
        $where['dateType']   = $dateType;
        $where['begin_date'] = array("between",array($begin,$end));
        $where['end_date']   = array("between",array($begin,$end));
        //查看是否有上周/月/季的数据
        $count = M('rankingList')->where($where)->count();
        if (!$count)
        {
            list($begin,$end) = getTopRankDate($dateType);  //获取上上周的数据
            $where['begin_date'] = array("between",array($begin,$end));
            $where['end_date']   = array("between",array($begin,$end));
        }
        if($user_id !== null){
            $where['user_id']   = $user_id;
        }
        if($number){
            $where['ranking']   = array('elt',$number);
        }
        //从数据表获取上周/月/季排行榜
        $Ranking = D('rankingList')->where($where)->order('ranking asc')->select();
        if(!empty($Ranking)){
            foreach ($Ranking as $k => $v) {
   				if($more){
                    //当前连胜与最大连胜
                    $Ranking[$k]['Winning']  = D('GambleHall')->getWinning($v['user_id'],$gameType,0,1,0);
                }
            }
        }
        return $Ranking;
    }

    /**
     * 获取红人榜(读取数据表)
     *
     * @param int  $gameType     赛事类型(1:足球   2:篮球   默认为1)
     * @param int  $number       提取前多少名(默认false全部)
     *
     * @return  array
    */
    public function getRedList($gameType=1,$number=false){
        $where['game_type']   = $gameType;
        $where['list_date']  = date('Ymd',strtotime("-1 day"));
        if($number){
            $where['ranking']   = array('elt',$number);
        }
        //从数据表获取昨日红人榜
        $Ranking = D('redList')->where($where)->order('ranking asc')->select();
        if(!$Ranking){
            //没有就获取前天的
            $where['list_date']  = date('Ymd',strtotime("-2 day"));
            //从数据表获取昨日红人榜
            $Ranking = D('redList')->where($where)->order('ranking asc')->select();
        }
        foreach ($Ranking as $k => $v) {
            $Ranking[$k]['face'] = frontUserFace($v['head']);
        }
        return $Ranking;
    }

    /**
     * 取消关注
     *
     * @param int  $id    被关注人id
     *
     * @return  json
    */
    public function cancelFollow(){
        $id = I('id');
        if(empty($id)){
            $this->error("参数错误！");
        }
        $rs = M('followUser')->where(array('follow_id'=>$id,'user_id'=>is_login()))->delete();
        if($rs){
            $this->success("取消关注成功！");
        }else{
            $this->error("取消关注失败，请稍后再试！");
        }
    }

    /**
     * 添加关注
     *
     * @param int  $id    被关注人id
     *
     * @return  json
    */
    public function addFollow(){
        $id = I('id');
        $user_id = is_login();

		if (empty($user_id)) $this->error("请登录！");

        if($id == $user_id) $this->error("您不能关注自己噢！");

        if(empty($id)) $this->error("参数错误！");

        if(M('followUser')->where(array('user_id'=>$user_id,'follow_id'=>$id))->getField('id')) $this->error("您已关注！");

        $rs = M('followUser')->add(array('user_id'=>$user_id,'follow_id'=>$id,"follow_time"=>time()));
        if($rs){
            $this->success("关注成功！");
        }else{
            $this->error("关注失败，请稍后再试！");
        }
    }

    //弹框登录获取token
    public function modalLogin(){
        $token = get_form_token(true);
        $this->success($token);
    }

    /* Ajax登录页面 */
    public function login(){
        if(IS_AJAX)//登录验证
        {
            if(!check_form_token()){
                $this->error("登录失败！");
            }
            if(checkShieldIp()){
                $this->error('登录失败，请联系管理员');
            }
            $username = I('username');
            $password = I('password');
            if(empty($username) || empty($password)){
                $this->error("帐号或密码不能为空");
            }
            $id = D('FrontUser')->login($username, $password);
            if(0 < $id)//UC登录成功
            {
                $remember = I('remember');
                if(!empty($remember)){
                    $UserArray = ['username'=>$username,'password'=>$password];
                    //如果用户选择了，记录登录状态就把用户名和加了密的密码放到cookie里面
                    Tool::setArrayCookie('u_p',$UserArray,86400*30);
                }
                /* 登录用户 */
                if(D('FrontUser')->autoLogin($id))//登录用户
                {
                    //TODO:跳转到登录前页面
                    $this->success(U('/'));
                } else {
                    $this->error('登录失败！',U('User/login'));
                }
            } else {
                //登录失败
                switch($id) {
                    case -1: $error = '用户不存在或被禁用'; break; //系统级别禁用
                    case -2: $error = '账户名与密码不匹配，请重新输入'; break;
                    default: $error = '未知错误'; break; // 0-接口参数错误（调试阶段使用）
                }
                $this->error($error);
            }
        } else {
            //显示登录表单
            $this->assign('position','欢迎登录');
            $this->display();
        }
    }

    //查看推荐（交易）
    public function trade()
    {
        $user_auth = session('user_auth');
        if(!$user_auth) $this->error('请先登录!');

        $gamble_id = I('gamble_id');
        $game_type = I('game_type') ? I('game_type') : 1;

        //执行查看
        $tradeRes = D('Common')->trade(
            $user_auth['id'],
            $gamble_id,
            1,
            $game_type
        );

        if ($tradeRes['code'] != 'success' && $tradeRes['data'] == ''){
            $this->error(C('errorCode')[$tradeRes['code']]);
        }
        //推荐记录信息
        $this->success($tradeRes['data']);
    }

    //积分兑换
    public function exchange()
    {
        if (!IS_AJAX)
            return;

        if (!$userid = is_login())
            $this->error(-1);
        $key=I('post.key');
        if($key<1 || $key>4)
            $this->error('参数有误!');

        $config = getWebConfig('platformSetting');
        $userPoint = M('FrontUser')->where(['id'=>$userid])->getField('point');
        if ($userPoint < $config['pointLimit'])
            $this->error('您的积分不足'.$config['pointLimit'].',无法兑换');

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
            'desc'        => '您已使用'.$config['point2Coin'.$key].'积分兑换'.$config['coin'.$key].'金币'
        ]);

        //添加球币记录
        $userCoin = M('FrontUser')->master(true)->field(['coin','unable_coin'])->where(['id'=>$userid])->find();
        $insertCoinLog = M('AccountLog')->add([
            'user_id'    =>  $userid,
            'log_time'   =>  time(),
            'log_type'   =>  6,
            'log_status' =>  1,
            'change_num' =>  $config['coin'.$key],
            'total_coin' =>  $userCoin['coin']+$userCoin['unable_coin'],
            'desc'       =>  '您已使用'.$config['point2Coin'.$key].'积分兑换'.$config['coin'.$key].'金币',
            'platform'   =>  1,
            'operation_time' => time()
        ]);

        $this->success('您已成功兑换金币！');
    }


    //获取视频直播
    public function getVideoLive()
    {
        if(!$live = S('web_live'))
        {
            //获取足球赛事直播
            $footLive = M('gameFbinfo')->where(['is_video'=>1,'is_recommend'=>1,'game_state'=>['in',[0,1,2,3,4]]])->field("game_id,union_name,home_team_id,away_team_id,gtime,game_state,game_date,game_time,home_team_name,score,away_team_name,web_video")->limit(6)->order("gtime asc")->select();
            foreach ($footLive as $k => $v) {
                $footLive[$k]['game_type'] = 1;
            }
            if(!$footLive){
                $footLive = array();
            }
            //获取蓝球赛事直播
            $baskLive = M('gameBkinfo')->where(['is_video'=>1,'is_recommend'=>1,'game_state'=>['in',[0,1,2,3,4]]])->field("game_id,union_name,home_team_id,away_team_id,gtime,game_state,game_date,game_time,home_team_name,score,away_team_name,web_video")->limit(6)->order("gtime asc")->select();
            foreach ($baskLive as $k => $v) {
                $baskLive[$k]['game_type'] = 2;
            }
            if(!$baskLive){
                $baskLive = array();
            }
            $live = array_merge($footLive,$baskLive);
            foreach ($live as $k => $v) {
                $live[$k]['web_video'] = json_decode($v['web_video'],true);
                //球队logo
                $live[$k]['homeTeamLogo'] = getLogoTeam($v['home_team_id'],1,$v['game_type']);
                $live[$k]['awayTeamLogo'] = getLogoTeam($v['away_team_id'],2,$v['game_type']);
            }
            //对数组进行排序
            foreach ($live as $v) {
                $game_state[] = $v['game_state'];
                $gtime[]      = $v['gtime'];
            }
            array_multisort($game_state, SORT_DESC,$gtime, SORT_ASC, $live);
            S('web_live', json_encode($live), 60);
        }
        return $live;
    }

    //获取视频推荐集锦
    public function getRecommendJJ($number=3)
    {
        if(!$highlights = S('web_highlights'.$number))
        {
            $prefix = C('IMG_SERVER');
            $highlights = M('Highlights')->where(['is_recommend'=>2,'status'=>1])->order("add_time desc")->field("id,game_id,class_id,add_time,game_type,title,concat('$prefix',img) img,web_url,web_ischain")->limit($number)->select();
            $classArr = getVideoClass(0); //视频分类数组
            foreach($highlights as $key=>$val)
            {
                $highlights[$key]['href'] = videoUrl($val,$classArr);
            }
            S('web_highlights'.$number, json_encode($highlights), 60);
        }
        return $highlights;
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
        $userid = is_login();
        if($userid){
            $userArr = M('FrontUser')->master(true)->where(['id'=>$userid])->field('id,head,coin+unable_coin as balance,status,nick_name,is_expert,password,session_id')->find();
            //是否在别处登录
            if(session_id() !== $userArr['session_id']){
                D('FrontUser')->logout();
                $this->jsonpReturn(-1,"您已在别处登录！",'logincallback');
            }
            //获取禁用状态
            if($userArr['status'] == 0){
                D('FrontUser')->logout();
                $this->jsonpReturn(-1,"您的帐号已被禁用，不能登录！",'logincallback');
            }
            //比对密码
            if($userArr['password'] !== session('user_auth')['password']){
                D('FrontUser')->logout();
                $this->jsonpReturn(-1,"您的帐号密码已被修改，请重新登录！",'logincallback');
            }
            $is_complete = I('is_complete');
            if(empty($is_complete) && empty($userArr['nick_name'])){
                $this->jsonpReturn(-1,"-1",'logincallback');
            }
            //获取未读的消息
            $userArr['msg'] = M("Msg")->where(array('front_user_id'=>$userid,'is_read'=>0))->count();
            unset($userArr['status'],$userArr['password'],$userArr['session_id']);
            $userArr['head'] = frontUserFace($userArr['head']);
            $this->jsonpReturn(1,$userArr,'logincallback');
        }else{
            $this->jsonpReturn(-1,'0','logincallback');
        }
    }

    /**
     * jsonp返回
     * @param  int          $code    成功或失败 1成功  -1失败
     * @param  array/int    $data    要返回的数据
     * @param  int          $name    jsonp名称
     */
    public function jsonpReturn($status,$data,$name){
        echo htmlspecialchars($_GET[$name]) . "(".json_encode(['status'=>$status,'info'=>$data]).")";
        exit;
    }

    /* 空操作，用于输出404页面 */
    public function _empty(){
        header("HTTP/1.1 404 Not Found");
        header("Status: 404 Not Found");
        $this->display('Public/error');
        die;
    }

    /**
     * CURL方法
     *
     * @param string $url 链接地址
     * @param string $data 传输的变量 多个使用&拼接
     * @param string $domain 接口地址
     * @param string $type 传输方式，默认post
     * @return string 返回数据
     */
    protected function get_curl($url, $data,$domain,$type = 'post') {
        import('Vendor.Signature.SignatureHelper');
        $signObj = new \SignatureHelper();
        $param = explode('&', $data);
        $arr = [];
        foreach ($param as $v) {
            $val = explode('=', $v);
            $arr[$val[0]] = $val[1];
        }
        $arr['t']=time();
        $sign =$signObj->sign($arr, 'quancaiappppa');
        if(!$sign){
            return array('status'=>1,'error'=>403);
        }
        $data .= '&t='.$arr['t'].'&sign='.$sign;
        $curlobj = curl_init();
        curl_setopt($curlobj, CURLOPT_URL, $domain.$url);
        curl_setopt($curlobj, CURLOPT_HEADER, 0);
        curl_setopt($curlobj, CURLOPT_RETURNTRANSFER, 1);
        if ($type == 'post') {
            curl_setopt($curlobj, CURLOPT_POST, 1);
            curl_setopt($curlobj, CURLOPT_POSTFIELDS, $data);
            curl_setopt($curlobj, CURLOPT_HTTPHEADER, array("application/x-www-form-urlencoded; charset=utf-8", "Content-length: " . strlen($data)));
        }
        $rtn = curl_exec($curlobj);

        if (curl_errno($curlobj) != 0) {
            return false;
        }
        curl_close($curlobj);
        return json_decode($rtn, true);
    }

	/**
	 * 获取推荐结果
	 * @User liangzk <liangzk@qc.com>
	 * @param int $game_type    1:足球 2：篮球
     * @param int $user_id      用户id
     * @param int $gamble_type  1:亚盘 2：竞彩
	 * @DateTime 2016-08-02
	 * @version 2.0
	 */
	public function get_gamble_result($game_type=1,$user_id=0,$gamble_type=1)
	{
		if (empty($user_id)) $user_id = is_login();
        $where['result'] = ['IN',[1,0.5,-1,-0.5,2]];
        $where['user_id'] = $user_id;
        if($game_type == 1){
            $where['play_type'] = $gamble_type == 1 ? ['in',[1,-1]] : ['in',[2,-2]];
        }
		$gambleResult = M($game_type == 1 ? 'Gamble' : 'Gamblebk')
			->where($where)
			->Field('count(result) as resultCount , result')
			->group('result')
			->select();
		$resultArr = array();
		foreach ($gambleResult as $k => $v)
		{
			if ($game_type == 1 ? $v['result'] == 1 || $v['result'] == 0.5 : $v['result'] == 1)
				$resultArr['winCount'] += $v['resultCount'];//胜
			if ($game_type == 1 ? $v['result'] == -1 || $v['result'] == -0.5 : $v['result'] == -1)
				$resultArr['loseCount'] += $v['resultCount'];//输
			if ($v['result'] == 2)
				$resultArr['flatCount'] = $v['resultCount'];//平
		}
		unset($gambleResult);
		return $resultArr;
	}
    /**
     * @User liangzk <liangzk@qc.com>
     * @DataTime 2016-08-22
     * 获取用户连胜多的用户ID
     * @param int $num 返回连胜多的前多少名（默认返回前五十名）
     * @return array 用户ID
     */

    public function getUserWinning($num = 50)
    {
        //获取有输过的推荐的用户ID（连胜多的前50个）(足球)
        $list_lost = M()
            ->query('SELECT g1.user_id,COUNT( g1.id) AS \'num\' FROM qc_gamble g1
                        LEFT JOIN
                        ( SELECT user_id,MAX(create_time) AS \'create_time\'
                            FROM qc_gamble WHERE id > 0 AND (result =- 1 OR result =- 0.5) GROUP BY user_id ) g2
                         ON g1.user_id = g2.user_id
                        WHERE
                            g1.id > 0
                        AND g1.create_time > g2.create_time
                        AND (g1.result = 1 OR g1.result = 0.5)
                        GROUP BY
                            g1.user_id
                        HAVING num > 2
                        ORDER BY num DESC
                        LIMIT 0,'.$num);
        //获取没有输过的推荐的用户ID（连胜多的前50个）(足球)
        $list = M()->query('SELECT g1.user_id ,COUNT(g1.id) AS \'num\' FROM qc_gamble g1 WHERE  g1.id > 0 AND
                              NOT EXISTS ( SELECT 1 FROM qc_gamble g2 WHERE g1.user_id = g2.user_id AND result =- 0.5 GROUP BY user_id
                              UNION ALL SELECT 1 FROM qc_gamble g2 WHERE g1.user_id = g2.user_id AND result =- 1 GROUP BY user_id )
                              GROUP BY user_id HAVING num > 2 ORDER BY num DESC LIMIT 0,'.$num);

        $list = array_merge($list,$list_lost);
        foreach ($list as $k => $v) {
            $winningNum[] = $v['num'];
        }
        array_multisort($winningNum,SORT_DESC,$list);
        $list = array_chunk($list,$num);//合并和根据连胜多的排序
        return $list[0];
    }

    //推荐数据重置
    public function resetGambleData()
    {
        $user_id = is_login(); //是否登录

        if(!$user_id) $this->jsonpReturn('-1','请先登录','resetdata');
        $gamble_type = I('gamble_type') ? : 1;
        $result = D('GambleHall')->resetGambleData($user_id,1,$gamble_type);

        switch ($result) {
            case '1072':
                $this->jsonpReturn('-1','金币不足','resetdata');
                break;
            case '1073':
                $this->jsonpReturn('-1','重置失败','resetdata');
                break;
            case '1074':
                $this->jsonpReturn('-1','无数据重置哦','resetdata');
                break;
            case '1':
                $this->jsonpReturn('1','重置成功','resetdata');
                break;
        }
    }

    /**
     * 根据商城登入状态自动登入
     */
    public function shoplogin()
    {
        $u_k = $this->u_info['u_k'];
        if($u_k && $this->u_info['q_log_status'] == 2)
        {
            D('FrontUser')->autoLogin($u_k);
        }elseif(!$u_k){
            D('FrontUser')->logout();
            D('FrontUser')->set_u_k('q_log_status',0);
        }
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
    /**
     * Liangzk 《Liangzk@qc.com》
     * date 2017-02-22
     * @param string $table_name
     * @param array  $data
     * @param string $field
     * @return bool|false|int
     */
//批量修改  data二维数组 field关键字段  参考ci 批量修改函数 传参方式
    function batch_update($table_name='',$data=array(),$field=''){
        if(!$table_name||!$data||!$field){
            return false;
        }else{
            $sql='UPDATE '.$table_name;
        }
        $con=array();
        $con_sql=array();
        $fields=array();
        foreach ($data as $key => $value) {
            $x=0;
            foreach ($value as $k => $v) {
                if($k!=$field&&!$con[$x]&&$x==0){
                    $con[$x]=" set {$k} = (CASE {$field} ";
                }elseif($k!=$field&&!$con[$x]&&$x>0){
                    $con[$x]="  {$k} = (CASE {$field} ";
                }
                if($k!=$field){
                    $temp=$value[$field];
                    $con_sql[$x].=   " WHEN '{$temp}' THEN '{$v}' ";
                    $x++;
                }
            }
            $temp=$value[$field];
            if(!in_array($temp,$fields)){
                $fields[]=$temp;
            }
        }
        $num=count($con)-1;
        foreach ($con as $key => $value) {
            foreach ($con_sql as $k => $v) {
                if($k==$key&&$key<$num){
                    $sql.=$value.$v.' end),';
                }elseif($k==$key&&$key==$num){
                    $sql.=$value.$v.' end)';
                }
            }
        }
        $str=implode(',',$fields);
        $sql.=" where {$field} in({$str})";
        $res=M($table_name)->execute($sql);
        return $res;
    }

    //广告转跳
    public function adver()
    {
        $adver_id = I('adver_id');
        $url = D('Common')->getAdverUrl($adver_id);
        header("location:".$url);
    }

    public function curlCapture($requestUrl,$headers,$resFormat = false)
    {
        // 初始化 CURL
        $ch = curl_init();

//			$headers = array(
//				'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
//				'Upgrade-Insecure-Requests:1',
//				'User-Agent:Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36',
//				'Referer:'.$referer,
//				'Accept-Encoding:gzip, deflate, sdch',
//				'Accept-Language:zh-CN,zh;q=0.8',
//			);

        // 设置 URL
        curl_setopt($ch, CURLOPT_URL,$requestUrl);
        // 让 curl_exec() 获取的信息以数据流的形式返回，而不是直接输出。
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        // 在发起连接前等待的时间，如果设置为0，则不等待
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 0);
        if(substr($requestUrl,0,5) == "https")
        {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        }
        // 设置 CURL 最长执行的秒数
        curl_setopt ($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
        if (!empty($headers))
        {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        // 尝试取得文件内容
        $store = curl_exec ($ch);


        // 检查文件是否正确取得
        if (curl_errno($ch)){
            //"无法取得 URL 数据";
            return null;
            exit;
        }

        // 关闭 CURL
        curl_close($ch);

        return $resFormat ? json_decode($store,true) : $store;
    }


    //获取资讯列表相关推荐数据
    public function getrecommend()
    {
        //视频数据
        $video = $this->getRecommendJJ(30);
        shuffle($video);
        $data['video'] = array_slice($video,0,3);
        //图片数据
        $photo = $this->getRecommendTK(42,30);
        shuffle($photo);
        $data['photo'] = array_slice($photo,0,3);
        $this->ajaxReturn($data);
    }
    //获取图库列表数据
    public function getRecommendTK($c_id,$number=3)
    {
        if(!$data = S('web_Gallery'.$number))
        {
            $where['G.status'] = 1;
            //获取所有分类
            $class_id = M('galleryClass')->where(['status' => 1,'pid'=>$c_id])->order("sort asc")->getField('id',true);
            $class_id[] = $c_id;
            $where['G.class_id'] = ['IN',$class_id];
            //获取图库
            $gallery = M('Gallery')
                ->alias('G')
                ->field('G.id,G.class_id,G.title,G.img_array,G.click_number,G.like_num,G.add_time,C.path')
                ->where($where)
                ->join('LEFT JOIN qc_gallery_class C ON  C.id = G.class_id')
                ->order('G.add_time DESC,G.like_num DESC')
                ->limit($number)
                ->select();
            $data = [];
            foreach ($gallery as $kk => $vv) {
                $data[$kk]['title'] = $vv['title'];
                $data[$kk]['cover_img'] = setImgThumb(json_decode($vv['img_array'], true)[1],'240');
                $data[$kk]['info_url'] = U('/' . $vv['path'] . '/' . date('Ymd', $vv['add_time']) . '/' . $vv['id'] . '@photo', '', 'html');
                unset($gallery[$kk]['img_array']);
            }
            S('web_Gallery'.$number, $data, 60);
        }
        return $data;
    }

}