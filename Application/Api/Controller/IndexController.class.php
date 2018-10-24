<?php
/**
 * 首页其他
 * @author huangjiezhen <418832673@qq.com> 2015.12.25
 */

class IndexController extends PublicController
{
    public function _initialize()
    {
        // $this->ajaxReturn(405);    //停止接口访问、提示更新到最新的版本
        $this->param = getParam(); //获取传入的参数
        // $this->verifySignature();  //校验签名
    }

    //todo
    public function index()
    {
        $this->ajaxReturn('hello,this is the api index');
    }

    //获取配置参数
    public function config()
    {
        $config = getConfig();
        $this->ajaxReturn(['config'=>$config]);
    }

    //意见反馈
    public function feedback()
    {
        if (!$this->param['content'])
            $this->ajaxReturn(4001);

        $userToken = getUserToken($this->param['userToken']);

        $data = [
            'user_id' => $userToken ? $userToken['userid'] : null,
            'create_time' => time(),
            'content' => $this->param['content'],
        ];

        if (!M('Feedback')->add($data))
            $this->ajaxReturn(4002);

        $this->ajaxReturn(['result'=>1]);
    }

    //版本更新
    public function version()
    {
        $appChannel = I('channel') == 'f30' ? 'f30' : 'official';
        $field = ['app_type','app_name','app_pkg_name','app_version','app_url','descript','is_upgrade','update_time'];
        $version = M('AppVersion')->field($field)->where(['status'=>1,'app_channel'=>$appChannel])->order('id desc')->limit(1)->find();

        $this->ajaxReturn(['version'=>$version]);
    }

    //ios版本更新
    public function version_ios()
    {
        $pkg = $this->param['pkg'] != '' && $this->param['pkg'] != 'company' ? '_personal' : '';
        $this->ajaxReturn(['version'=>getWebConfig('common')['ios_version'.$pkg]]);
    }

    //分享
    public function share()
    {
        $share = getWebConfig('share');
        $shareImage = \Think\Tool\Tool::imagesReplace($share['img']).'?'.rand(1000,9999);
        $share['shareImage'] = $shareImage ? $shareImage : '';
        unset($share['img']);
        $this->ajaxReturn(['share'=>$share]);
    }

    //规则帮助页面
    public function help()
    {
        $this->display();
    }

    //服务协议页面
    public function agreement()
    {
        $this->display();
    }

    //h5下载介绍页面
    public function download()
    {
        redirect301(U('/App/download'));
        $this->display();
    }

    //web下载介绍页面
    public function introduce()
    {
        redirect301(U('/App/introduce'));
        $this->display();
    }

    //启动页广告
    public function startAdver()
    {
        $adver = @Think\Tool\Tool::getAdList(16,2,$this->param['platform']);

        if (isset($adver[1]))
            $adver[0]['img4s'] = $adver[1]['img'];

        $this->ajaxReturn(['adver'=>$adver[0]]);
    }

    //比分页面的广告
    public function adverList()
    {
        switch ($this->param['pageType'])
        {
            case '1': $classId = 17; break;
            case '2': $classId = 18; break;
            case '3': $classId = 19; break;
            default: $this->ajaxReturn(101);
        }

        $adver = @Think\Tool\Tool::getAdList($classId,20,$this->param['platform']);

        foreach ($adver as $k => $v)
        {
            unset($adver[$k]['id']);
        }

        $this->ajaxReturn(['adver'=>$adver]);
    }

    public function checkIp(){
        $ip = get_client_ip();
        echo $ip;
        $postId = I('ip');
        if($postId){
            $ip = $postId;
        }
        dump(iosIpCheck($ip));
        $server = I('server');
        if($server == 'show'){
            dump($_SERVER);
            die;
        }
    }
}


 ?>