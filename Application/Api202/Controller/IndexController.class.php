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
        //只为了统计查看
        if (I('tradeCount'))
        {
            //配置要查询的时间
            $months    = ['06','07','08','09','10']; //月份
            $startDate = '20160601';
            $endDate   = '20161031';

            //初始化每个月的统计
            foreach ($months as $v)
            {
                $list[$v] = []; //每个时间段购买的人数

                for ($i=0; $i <= 22; $i++)
                {

                    if ($i %2 == 0)
                    {
                        $key = $i.'-'.($i+2);
                        $list[$v][$key] = 0;
                    }
                }

                $coin[$v] = [0=>0,2=>0,4=>0,8=>0,16=>0,32=>0,64=>0,128=>0,256=>0,512=>0]; //每个购买金币数的人数

                //每个购买金币数的人数输赢统计
                $count[$v] = [
                    0   => ['win'=>0,'lost'=>0],
                    2   => ['win'=>0,'lost'=>0],
                    4   => ['win'=>0,'lost'=>0],
                    8   => ['win'=>0,'lost'=>0],
                    16  => ['win'=>0,'lost'=>0],
                    32  => ['win'=>0,'lost'=>0],
                    64  => ['win'=>0,'lost'=>0],
                    128 => ['win'=>0,'lost'=>0],
                    256 => ['win'=>0,'lost'=>0],
                    512 => ['win'=>0,'lost'=>0]
                ];
            }

            //查询出购买记录
            $data = M('QuizLog q')
                    ->field(['q.log_time','q.coin','g.result'])
                    ->join('left join (select id, result from __GAMBLE__ UNION  select id,result from __GAMBLE_RESET__) g on g.id = q.gamble_id')
                    ->where(['q.log_time'=>['between',[strtotime($startDate),strtotime($endDate)+24*3600]],'q.game_type'=>1,'q.is_back'=>0])
                    ->order('q.log_time desc')
                    ->select();

            //遍历筛选数据
            foreach ($data as $k => $v)
            {
                $data[$k]['date'] = date('Y-m-d H:i:s',$v['log_time']);
                $data[$k]['m'] = $vm = date('m',$v['log_time']);
                $data[$k]['h'] = $vh = date('H',$v['log_time']);

                foreach ($list as $m => $h)
                {
                    if ($vm == $m)
                    {
                        foreach ($h as $kh => $kv)
                        {
                            list($startH,$endH) = explode('-',$kh);

                            if ($vh >= $startH && $vh < $endH)
                            {
                                $list[$m][$kh]++;
                            }
                        }
                    }
                }

                foreach ($coin as $m => $c)
                {
                    if ($vm == $m)
                    {
                        foreach ($c as $num => $cv)
                        {
                            if ($v['coin'] == $num)
                            {
                                $coin[$m][$num]++;

                                if ($v['result'] == '1' || $v['result'] == '0.5')
                                    $count[$m][$num]['win']++;

                                if ($v['result'] == '-1' || $v['result'] == '-0.5')
                                    $count[$m][$num]['lost']++;
                            }
                        }
                    }
                }
            }

            foreach ($count as $k => $v)
            {
                foreach ($v as $kk => $vv)
                {
                    $coin[$k][$kk] = $coin[$k][$kk].'('.$vv['win'] .'/'.$vv['lost'].')';
                    $count[$k][$kk] =  $vv['win'] .'/'.$vv['lost'];
                }
            }

            // echo M()->_sql();
            dump(count($data));
            pr($list);
            pr($coin);
            // pr($count);
            // pr($data);
            die;
        }

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
        if(iosCheck()){
            $this->display(T('Index/help_ios'));
        }else{
            $this->display(T('Index/help'));
        }
    }

    //服务协议页面
    public function agreement()
    {
        if(iosCheck()){
            $this->display(T('Index/agreement_ios'));
        }else{
            $this->display(T('Index/agreement'));
        }
    }

    //下载页面
    public function download()
    {
        $this->code = $this->param['code'] ?: '';
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

    /**
     * 获得二维码
     */
    public function getEWM(){
        if(in_array($_SERVER['HTTP_HOST'], array('www.qt.com', '183.3.152.226:8088', 'beta-dev.qqty.com:8088'))){
            $urlHost = 'beta-dev.qqty.com:8088';
        }else if(in_array($_SERVER['HTTP_HOST'], array('www.qw.com', '183.3.152.226:8099', 'beta-dev.qqty.com:8099'))){
            $urlHost = 'beta-dev.qqty.com:8099';
        }else{
            $urlHost = $_SERVER['HTTP_HOST'];
        }
        qrcode('http://'.$urlHost.'/'.MODULE_NAME.'/Index/download.html?code='.$this->param['code']);
    }

}


 ?>