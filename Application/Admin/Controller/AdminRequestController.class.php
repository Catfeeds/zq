<?php
/**
 * 后台操作日志模块
 */

class AdminRequestController extends CommonController
{
    public function index($dwz_db_name='')
    {
        //列表过滤器，生成查询Map对象
        $map = $this->_search($dwz_db_name);

        //时间请求
        if (!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime']))
        {
            $map['request_time'] = array('BETWEEN',array(strtotime($_REQUEST ['startTime']),strtotime($_REQUEST ['endTime'])));
        }
        elseif (!empty($_REQUEST ['startTime']))
        {
            $map['request_time'] = array('EGT',strtotime($_REQUEST ['startTime']));
        }
        else if (!empty($_REQUEST ['endTime']))
        {
            $map['request_time'] = array('ELT',strtotime($_REQUEST ['endTime']));
        }

        $this->assign("map", $map);
        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }

        $list = $this->_list(D('AdminRequest'), $map);

        $this->assign('list',$list);
        $this->display();
    }

    //配置页面
    public function logConf()
    {
        $this->vo = getWebConfig('adminRequest');
        $this->display();
    }

    //保存配置
    public function saveLogConf()
    {
        $config = json_encode(['adminLogList'=>I('adminLogList')]);

        if (M('Config')->where(['sign'=>'adminRequest'])->save(['config'=>$config]) !== false)
        {
            $this->success('保存成功');
        }

        $this->error('保存失败');
    }

    public function DataAnalysis()
    {
        $startTime = strtotime(I('startTime'));
        $endTime   = strtotime(I('endTime'));

        $type  =  I('type');

        if($startTime != '' || $endTime != '')
        {
            //发布竞猜数据：发布竞猜场次的用户分布-0场、0-100场以内、100-300场，300场以上（百分比显示）
            $userArr = M('FrontUser')->field('id')->where(['is_robot'=>0,'user_type'=>1])->select();
            //$userArrId = array_map('array_shift', $userArr);
            //$where['user_id'] = ['in',$userArrId];
            $where = ['f.is_robot'=>0,'f.user_type'=>1];
            switch ($type) {
                case '1':
                    if(!empty($startTime) && !empty($endTime)){
                        $where['g.create_time'] = array('BETWEEN',array($startTime,$endTime+86399));
                    } elseif (!empty($startTime)) {
                        $where['g.create_time'] = array('EGT',$startTime);
                    } elseif (!empty($endTime)) {
                        $where['g.create_time'] = array('ELT',$endTime+86399);
                    }
                    $gamble = M('gamble g')
                        ->join("LEFT JOIN qc_front_user f on f.id = g.user_id")
                        ->field("g.user_id,count(g.id) as userNum")->where($where)->group('user_id')->select();
                    $gameData = $this->doQuizData($userArr,$gamble,1);
                    $this->assign('gameData',implode(',', $gameData));
                    break;
                case '2':
                    if(!empty($startTime) && !empty($endTime)){
                        $where['g.log_time'] = array('BETWEEN',array($startTime,$endTime+86399));
                    } elseif (!empty($startTime)) {
                        $where['g.log_time'] = array('EGT',$startTime);
                    } elseif (!empty($endTime)) {
                        $where['g.log_time'] = array('ELT',$endTime+86399);
                    }
                    //购买用户分布-购买0场、0-50场、50-200场、200场以上（免费和收费两个版本，百分比显示）
                    $quizLog = M('quizLog g')
                        ->join("LEFT JOIN qc_front_user f on f.id = g.user_id")
                        ->field("g.user_id,count(g.id) as userNum")->where($where)->where(['g.coin'=>['gt',0]])->group('g.user_id')->select();
                    $quizData = $this->doQuizData($userArr,$quizLog,2);
                    $this->assign('quizData',implode(',', $quizData));

                    $quizLog_b = M('quizLog g')
                        ->join("LEFT JOIN qc_front_user f on f.id = g.user_id")
                        ->field("g.user_id,count(g.id) as userNum")->where($where)->where(['g.coin'=>['eq',0]])->group('g.user_id')->select();
                    $quizData_b = $this->doQuizData($userArr,$quizLog_b,2);
                    $this->assign('quizData_b',implode(',', $quizData_b));
                    break;
                case '3':
                    if(!empty($startTime) && !empty($endTime)){
                        $where['g.log_time'] = array('BETWEEN',array($startTime,$endTime+86399));
                    } elseif (!empty($startTime)) {
                        $where['g.log_time'] = array('EGT',$startTime);
                    } elseif (!empty($endTime)) {
                        $where['g.log_time'] = array('ELT',$endTime+86399);
                    }
                    //充值用户分布-充值10元以下、10-128元、128-1000元、1000元以上（附带充值用户名单，百分比显示）
                    $accountLog = M('accountLog g')
                        ->join("LEFT JOIN qc_front_user f on f.id = g.user_id")
                        ->field("g.user_id,sum(g.change_num) as userNum")->where($where)->where(['g.log_type'=>8])->group('g.user_id')->select();
                    $coinData = $this->doQuizData($userArr,$accountLog,3);
                    $this->assign('coinData',implode(',', $coinData));
                    break;
            }
            
        }
        $this->display();
    }

    public function doQuizData($userArr,$quizLog,$type)
    {
        foreach ($userArr as $k => $v) {
            foreach ($quizLog as $kk => $vv) {
                if($v['id'] == $vv['user_id']){
                    $userArr[$k]['userNum'] = $vv['userNum'];
                }
            }
        }
        $a = $b = $c = $d = 0;
        switch ($type) {
            case '1':
                //发布竞猜数据：发布竞猜场次的用户分布-0场、0-100场以内、100-300场，300场以上（百分比显示）
                foreach ($userArr as $k => $v) {
                    if($v['userNum'] == 0){
                        $a++;
                    }
                    if($v['userNum'] > 0 && $v['userNum'] <= 100){
                        $b++;
                    }
                    if($v['userNum'] > 100 && $v['userNum'] < 300){
                        $c++;
                    }
                    if($v['userNum'] >= 300){
                        $d++;
                    }
                }
                break;
            case '2':
                //购买用户分布-购买0场、0-50场、50-200场、200场以上（免费和收费两个版本，百分比显示）
                foreach ($userArr as $k => $v) {
                    if($v['userNum'] == 0){
                        $a++;
                    }
                    if($v['userNum'] > 0 && $v['userNum'] <= 50){
                        $b++;
                    }
                    if($v['userNum'] > 50 && $v['userNum'] < 200){
                        $c++;
                    }
                    if($v['userNum'] >= 200){
                        $d++;
                    }
                }
                break;
            case '3':
                //充值用户分布-充值10元以下、10-128元、128-1000元、1000元以上（附带充值用户名单，百分比显示）
                foreach ($userArr as $k => $v) {
                    if($v['userNum'] < 10){
                        $a++;
                    }
                    if($v['userNum'] >= 10 && $v['userNum'] <= 128){
                        $b++;
                    }
                    if($v['userNum'] > 128 && $v['userNum'] < 1000){
                        $c++;
                    }
                    if($v['userNum'] >= 1000){
                        $d++;
                    }
                }
                break;
        }
        
        $quizData = [$a,$b,$c,$d];
        return $quizData;
    }
}