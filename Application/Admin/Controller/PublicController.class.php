<?php
use Think\Controller;
use Org\Util\Rbac;

class PublicController extends Controller {

    // 检查用户是否登录
    protected function checkUser() {
        if(!isset($_SESSION[C('USER_AUTH_KEY')])) {
            $this->error('没有登录','Public/login/');
        }
    }

    // 顶部页面
    public function top() {
        C('SHOW_RUN_TIME',false);			// 运行时间显示
        C('SHOW_PAGE_TRACE',false);
        $model	=	M("Group");
        $list	=	$model->where('status=1')->getField('id,title');
        $this->assign('nodeGroupList',$list);
        $this->display();
    }

    public function drag(){
        C('SHOW_PAGE_TRACE',false);
        C('SHOW_RUN_TIME',false);			// 运行时间显示
        $this->display();
    }

    // 尾部页面
    public function footer() {
        C('SHOW_RUN_TIME',false);			// 运行时间显示
        C('SHOW_PAGE_TRACE',false);
        $this->display();
    }

    // 菜单页面
    public function menu() {
        $this->checkUser();
        if(isset($_SESSION[C('USER_AUTH_KEY')])) {
            //显示菜单项
            $menu  = array();
                //读取数据库模块列表生成菜单项
                $node    =   M("Node");
                $id =   $node->getField("id");
                $where['level']=2;
                $where['status']=1;
                $where['pid']=$id;
                $list   =   $node->where($where)->field('id,name,group_id,title')->order('sort asc')->select();
                $accessList = $_SESSION['_ACCESS_LIST'];

                foreach($list as $key=>$module) {
                     if(isset($accessList[strtoupper(MODULE_NAME)][strtoupper($module['name'])]) || $_SESSION['administrator']) {
                        //设置模块访问权限
                        $module['access'] =   1;

                        $menu[$module['group_id']][$key]  = $module;
                    }
                }
                //缓存菜单访问
                $_SESSION['menu'.$_SESSION[C('USER_AUTH_KEY')]] =   $menu;

            if(!empty($_GET['tag'])){
                $this->assign('menuTag',$_GET['tag']);
            }
            //获取导航菜单下的目录结构
            $groups=M("Group")->where(array('group_menu'=>"{$_GET['menu']}",'status'=>"1"))->order("sort asc")->select();
            $this->assign("groups",$groups);
            $this->assign('menu',$menu);
            $PublishClass = M('PublishClass')->where(['status'=>1])->field("id,pid,name,level")->select();
            $PublishClass = Think\Tool\Tool::getTree($PublishClass, $pid = 0, $col_id = 'id', $col_pid = 'pid', $col_cid = 'level');
            $this->assign('PublishClass',$PublishClass);
        }
        // C('SHOW_RUN_TIME',false);           // 运行时间显示
        // C('SHOW_PAGE_TRACE',false);
        $this->display();
    }

    // 后台首页 查看系统信息
    public function main() {
        $info = array(
            '操作系统'=>PHP_OS,
            '运行环境'=>$_SERVER["SERVER_SOFTWARE"],
            'PHP运行方式'=>php_sapi_name(),
            'ThinkPHP版本'=>THINK_VERSION.' [ <a href="http://thinkphp.cn" target="_blank">查看最新版本</a> ]',
            '上传附件限制'=>ini_get('upload_max_filesize'),
            '执行时间限制'=>ini_get('max_execution_time').'秒',
            '服务器时间'=>date("Y年n月j日 H:i:s"),
            '北京时间'=>gmdate("Y年n月j日 H:i:s",time()+8*3600),
            '服务器域名/IP'=>$_SERVER['SERVER_NAME'].' [ '.gethostbyname($_SERVER['SERVER_NAME']).' ]',
            '剩余空间'=>round((@disk_free_space(".")/(1024*1024)),2).'M',
            'register_globals'=>get_cfg_var("register_globals")=="1" ? "ON" : "OFF",
            'magic_quotes_gpc'=>(1===get_magic_quotes_gpc())?'YES':'NO',
            'magic_quotes_runtime'=>(1===get_magic_quotes_runtime())?'YES':'NO',
            );
        $this->assign('info',$info);
        $this->display();
    }

    // 用户登录页面
    public function login() {
        if(!isset($_SESSION[C('USER_AUTH_KEY')])) {
            $this->display();
        }else{
            $this->redirect('Index/index');
        }
    }

    public function index() {
        //如果通过认证跳转到首页
        redirect(__MODULE__);
    }

    // 用户登出
    public function logout() {
        unset($_SESSION[C('USER_AUTH_KEY')]);
        unset($_SESSION);
        session_destroy();
        redirect(__CONTROLLER__.'/login/');
    }

    // 登录检测
    public function checkLogin() {
        if(empty($_POST['account'])) {
            $this->error('请输入用户名！');
        }elseif (empty($_POST['password'])){
            $this->error('请输入密码！');
        }elseif (empty($_POST['verify'])){
            $this->error('请输入验证码！');
        }
        //生成认证条件
        $map            =   array();
        // 支持使用绑定帐号登录
        $map['account']	= $_POST['account'];
        $map["status"]	= 1;


        //3.2.1 的 验证码 检验方法
        $verify = $_POST['verify'] ;
        if(!$this->check_verify($verify)){
            $this->error('验证码输入错误！');
        }

        $authInfo = Rbac::authenticate($map);
        //使用用户名、密码和状态 的方式进行认证
        if(false === $authInfo) {
            $this->error('帐号不存在或密码错误！');
        }else {
            if($authInfo['password'] != md5($authInfo['hash'].$_POST['password'])) {
                $this->error('帐号不存在或密码错误！');
            }
            $_SESSION[C('USER_AUTH_KEY')]	=	$authInfo['id'];
            $_SESSION['email']	=	$authInfo['email'];
            $_SESSION['loginUserName']		=	$authInfo['nickname'];
            $_SESSION['lastLoginTime']		=	$authInfo['last_login_time'];
            $_SESSION['login_count']	    =	$authInfo['login_count'];
            $_SESSION['user_power']         =   $authInfo;
            if($authInfo['account']=='admin') {
                $_SESSION['administrator']		=	true;
            }


            $log['vc_operation']="用户登录：登录成功！";
            $log['vc_module']="系统管理";
            $log['creator_id']=$authInfo['id'];
            $log['creator_name']=$authInfo['account'];
            $log['vc_ip']=get_client_ip();
            $log['createtime']=time();
            M("Log")->add($log);


            //保存登录信息
            $User	=	M('User');
            $ip		=	get_client_ip();
            $time	=	time();
            $data = array();
            $data['id']	=	$authInfo['id'];
            $data['last_login_time']	=	$time;
            $data['login_count']	=	array('exp','login_count+1');
            $data['last_login_ip']	=	$ip;
            $User->save($data);

            // 缓存访问权限
            //RBAC::saveAccessList();
            $this->redirect(C('TMPL_PARSE_STRING')['__ADMIN__'].'/Index/index');

        }
    }

    // 更换密码
    public function changePwd() {
        $this->checkUser();
        //对表单提交处理进行处理或者增加非表单数据
        //3.2.1 的 验证码 检验方法
        $verify = $_POST['verify'] ;
        if(!$this->check_verify($verify)){
            $this->error('验证码输入错误！');
        }
        $map	=	array();
        $oldpassword = I('oldpassword');
        $password    = I('password');
        $repassword  = I('repassword');
        if($password != $repassword){
            $this->error('两次密码输入不一致！');
        }
        if(isset($_POST['account'])) {
            $map['account']	 =	 $_POST['account'];
        }elseif(isset($_SESSION[C('USER_AUTH_KEY')])) {
            $map['id']		=	$_SESSION[C('USER_AUTH_KEY')];
        }
        //检查用户
        $User    =   M("User");
        $UserInfo = $User->where($map)->field('id,hash,password')->find();
        if(md5($UserInfo['hash'].$oldpassword) != $UserInfo['password']){
            $this->error('原始密码不符！');
        }
        list($hash,$pwdHash) = pwdHash($repassword);
        $User->hash     =   $hash;
        $User->password	=	$pwdHash;
        $User->id       =   $_SESSION[C('USER_AUTH_KEY')];
        $User->save();
        $this->success('密码修改成功！');
    }

    // 用户资料
    public function profile() {
        $this->checkUser();
        $User	 =	 M("User");
        $vo	=	$User->getById($_SESSION[C('USER_AUTH_KEY')]);
        $this->assign('vo',$vo);
        $this->display();
    }

    // 检测输入的验证码是否正确，$code为用户输入的验证码字符串
	public function check_verify($code, $id = ''){
		$verify = new \Think\Verify();
		return $verify->check($code, $id);
	}

	//生成  验证码 图片的方法
	public function verify() {
        //3.2.1  中的生成 验证码 图片的方法
        $Verify = new \Think\Verify();
        // 设置验证码字符为纯数字
        $Verify->codeSet = '0123456789';
        $Verify->length   = 4;
        $Verify->fontSize = 50;
        $Verify->entry();
    }

    // 修改资料
    public function change() {
        $this->checkUser();
        $User	 =	 D("User");
        if(!$User->create()) {
            $this->error($User->getError());
        }
        $result	=	$User->save();
        if(false !== $result) {
            $this->success('资料修改成功！');
        }else{
            $this->error('资料修改失败!');
        }
    }

    public function nav(){
        $volist=M("GroupClass")->where(array('status'=>1))->order("sort desc, id desc")->select();
        $this->volist=$volist;
        $this->display();
    }

    public function ClearRuntime(){
        $path = "./Application/Html/";
        $RuntimePath = "./Runtime/";
        self::delDirAndFile($path);
        self::delDirAndFile($RuntimePath);
        $this->success('清除成功！');
    }

    /**
     * 循环删除目录和文件函数
     *
     * @param $dirName  #目录名称
     *
     * @return void
     */
    public function delDirAndFile($dirName)
    {
        $isSucess = false;
        if ( $handle = opendir($dirName) ) {
            while ( false !== ( $item = readdir( $handle ) ) ) {
                if ( $item != "." && $item != ".." ) {
                    if (is_dir("$dirName/$item")) {
                        $this->delDirAndFile("$dirName/$item");
                    } else {
                        if( unlink("$dirName/$item") ) $isSucess=true;
                    }
                }
            }
            closedir( $handle );
            if( rmdir( $dirName ) ) $isSucess = true;
        }
        return $isSucess;
    }
    /**
     * 网站配置设置
     */
    public function commonConf()
    {
        $sign = 'common';
        $data = getWebConfig($sign);
        if(IS_POST)
        {
            $rechargeLimit = I('rechargeLimit', 0, 'int'); //充值限制金额
            $iosExtractMoney = I('iosExtractMoney', 0, 'int');
            $mobileSMS = I('mobileSMS');         //短信运营商
            $invite    = I('invite',0,'int');    //邀请好友
            $chatroom    = I('chatroom',0,'int');    //邀请好友
            $shopping  = I('shopping',0,'int'); //商城
            $description = I('description');//推荐描述

            //客服信息
            $service = I('service');
            $service['tel']     = !empty($service['tel'])  ? (string)$service['tel']  : '';
            $service['address'] = !empty($service['address']) ? (string)$service['address'] : '';

            //IOS内购外跳地址
            $iosAddress = I('iosAddress');
            $iosDocuments = I('iosDocuments');//IOS内购文字描述
            $iosHighLight = I('iosHighLight');//IOS内购文字高亮

            //支付显示
            $payment   = I('payment');
            $payment['ali']  = !empty($payment['ali'])  ? (int)$payment['ali']  : 0;
            $payment['wx']   = !empty($payment['wx'])   ? (int)$payment['wx']   : 0;
            $payment['yee']  = !empty($payment['yee'])  ? (int)$payment['yee']  : 0;
            $payment['wabp'] = !empty($payment['wabp']) ? (int)$payment['wabp'] : 0;

            //第三方登陆显示
            $sdklogin   = I('sdklogin');
            $sdklogin['weixin'] = !empty($sdklogin['weixin']) ? (int)$sdklogin['weixin']  : 0;
            $sdklogin['sina']   = !empty($sdklogin['sina'])   ? (int)$sdklogin['sina']    : 0;
            $sdklogin['qq']     = !empty($sdklogin['qq'])     ? (int)$sdklogin['qq']      : 0;
            $sdklogin['mm']     = !empty($sdklogin['mm'])     ? (int)$sdklogin['mm']      : 0;

            //提款配置
            $extract   = I('extract');
            $extract['ios'] = !empty($extract['ios']) ? (int)$extract['ios']  : 0;
            $extract['apk'] = !empty($extract['apk']) ? (int)$extract['apk']  : 0;
            $extract['web'] = !empty($extract['web']) ? (int)$extract['web']  : 0;
            $extract['m']   = !empty($extract['m'])   ? (int)$extract['m']    : 0;

            $app_cache   = I('app_cache');  //客户端缓存控制

            foreach ($app_cache as $k => $v) {
                foreach ($v as $kk => $vv) {
                    $app_cache[$k][$kk] = (int)($vv);
                }
            }

            $iosCheck  = I('iosCheck',0,'int');  //ios审核
            $module    = I('module');            //审核接口模块
            foreach ($module as $k => $v) {
                if($v['api_name'] != ''){
                    $ios_check_module[$v['api_name']] = $v['api'] ? : '';
                }
            }

            $ios_character = I('ios_character'); //ios审核配置文字
            $ios_version = I('ios_version'); //ios的版本更新
            $ios_version_personal = I('ios_version_personal');
            $ios_version_master = I('ios_version_master');
            $ios_version_worldCup = I('ios_version_worldCup');
            $ios_ip_shield = I('ios_ip_shield');//ios屏蔽IP段

            $configArr = [
                            'service'          => $service,
                            'iosAddress'       => $iosAddress,
                            'iosDocuments'     => (string)$iosDocuments,
                            'iosHighLight'     => (string)$iosHighLight,
                            'rechargeLimit'    => $rechargeLimit,
                            'iosExtractMoney'  => $iosExtractMoney,
                            'mobileSMS'        => $mobileSMS,
                            'invite'           => $invite,
                            'shopping'         => $shopping,
                            'chatroom'         => $chatroom,
                            'payment'          => $payment,
                            'sdklogin'         => $sdklogin,
                            'extract'          => $extract,
                            'app_cache'        => $app_cache,
                            'iosCheck'         => $iosCheck,
                            'ios_check_module' => $ios_check_module,
                            'ios_version'      => $ios_version,
                            'ios_version_personal' => $ios_version_personal,
                            'ios_version_master'   => $ios_version_master,
                            'ios_version_worldCup' => $ios_version_worldCup,
                            'ios_ip_shield'    => $ios_ip_shield,
                            'ios_character' => $ios_character,
                            'description'   => $description
                         ];

            $config['sign'] = $sign; //配置标记
            $config['config'] = json_encode($configArr);

            if($data){
                //修改
                $rs = M('config')->where(['sign'=>$sign])->save($config);
                if(!is_bool($rs))
                    $rs = true;
            }else{
                //新增
                $rs = M('config')->add($config);
            }
            if($rs){
                $this->success("设置成功！");
            }else{
                $this->error("设置失败！");
            }
        }

        $this->assign('data',$data);
        $this->display();
    }

    /**
     * 关于我们
     */
    public function aboutUs(){
        $sign = 'aboutUs';
        $data = getWebConfig($sign);

        if(IS_POST) {
            $aboutUs   = I('aboutUs');
            $config['sign']   = $sign; //配置标记
            $config['config'] = json_encode($aboutUs);
            if($data){
                //修改
                $rs = M('config')->where(['sign'=>$sign])->save(['config' => $config['config']]);

                if(!is_bool($rs))
                    $rs = true;
            }else{
                //新增
                $rs = M('config')->add($config);
            }

            if($rs){
                $this->success("设置成功！");
            }else{
                $this->error("设置失败！");
            }
        }

        $this->assign('data', $data);
        $this->display();
    }

    /*
     * ajax查询菜单列表
     */
    public function menulist()
    {
        $map['no.name'] = array('neq','index');
        $map['gr.group_menu'] = $_GET['menu'];
        $list = M('Node no')
            ->join('LEFT JOIN qc_group gr ON gr.id = no.group_id')
            ->field('no.name')
            ->where($map)
            ->select();
        echo json_encode($list);exit;
    }

    /**
     * 判断是否有异常操作未处理
     * @user liangzk <liangzk@qc.com>
     * @DateTime 2016-09-06 15:48
     *  @version v2.1
     */
    public function getNotIsException()
    {
        //新增的异常数量
        $newException = M('ExceptionLog')->count('id');
        $lastException = S('ExceptionLog'.$_SESSION['authId'].':ExceptionCount');
        if (($newException - $lastException) > 0)
        {
            $data['new_count'] = $newException - $lastException;//新增的异常数量
        }
        else
        {
            $data['new_count'] = 0;
        }
        S('ExceptionLog'.$_SESSION['authId'].':ExceptionCount',$newException);
        
        //未处理的异常数量
        $untreatedException = M('ExceptionLog')->where(['status'=>0])->count('id');//未处理的异常数
        if ($untreatedException > 0)
        {
            $data['excepLogCount'] = $untreatedException;
        }
        else
        {
            $data['excepLogCount'] = 0;
        }
        $this->ajaxReturn(['info' => $data, 'status' => 1,]);
    }

    /**
     * 网站配置
     */
    public function setting(){
        $sign = 'setting';
        $data = getWebConfig($sign);

        if(IS_POST) {
            $setting   = I('setting');
            $config['sign']   = $sign; //配置标记
            $config['config'] = json_encode($setting);
            if($data){
                //修改
                $rs = M('config')->where(['sign'=>$sign])->save(['config' => $config['config']]);
                if(!is_bool($rs))
                    $rs = true;
            }else{
                //新增
                $rs = M('config')->add($config);
            }

            if($rs){
                S('web_site_setting',null);
                $this->success("设置成功！");
            }else{
                $this->error("设置失败！");
            }
        }
        $this->assign('data', $data);
        $this->display();
    }

}