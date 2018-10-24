<?php
/**
 * 足球/篮球统计列表控制器
 *
 * @author dengweijun <406516482@qq.com>
 *
 * @since  2015-12-16
 */
class GameCountController extends CommonController {
    /**
    *构造函数
    *
    * @return  #
    */
    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * Index页显示
     *
     */
    public function index() {
        $_REQUEST ['numPerPage'] = 999999;
        $gameType = I('gameType');
        if($gameType == 1)
        {
            $map = $this->_search('GambleCountView');
            //大小与让分不为空
            $map['g.fsw_exp']        = ['neq',''];
            $map['g.fsw_ball']       = ['neq',''];
            $map['g.fsw_exp_home']   = ['neq',''];
            $map['g.fsw_exp_away']   = ['neq',''];
            $map['g.fsw_ball_home']  = ['neq',''];
            $map['g.fsw_ball_away']  = ['neq',''];
            $blockTime = getBlockTime(1,true);
            $map['gtime'] = array('BETWEEN',array($blockTime['beginTime'],$blockTime['endTime']));
            $map['_string'] = "((u.is_sub < 3 or g.is_show =1) AND g.is_gamble = 1 AND g.status = 1)";

            //时间查询
            if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
                if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                    $startTime = strtotime($_REQUEST ['startTime']);
                    $endTime   = strtotime($_REQUEST ['endTime'])+86400;
                    $map['gtime'] = array('BETWEEN',array($startTime,$endTime));
                } elseif (!empty($_REQUEST['startTime'])) {
                    $strtotime = strtotime($_REQUEST ['startTime']);
                    $map['gtime'] = array('EGT',$strtotime);
                } elseif (!empty($_REQUEST['endTime'])) {
                    $endTime = strtotime($_REQUEST['endTime'])+86400;
                    $map['gtime'] = array('ELT',$endTime);
                }
            }
            //赛程id查询
            $game_id = I('game_id');
            if (! empty($game_id)) $map['g.game_id'] = $game_id;

            $list = $this->_list(D('GambleCountView'),$map,'gtime asc');
            foreach ($list as $key => $value) {
                $gameidArr[] = $value['game_id'];
            }
            $quizLog = M('quizLog q')
                    ->join('left join qc_gamble g on g.id = q.gamble_id')
                    ->where(['game_type'=>$gameType,'coin'=>['gt',0],'q.game_id'=>['in',$gameidArr],'g.play_type'=>['in',[1,-1]]])
                    ->select();
            $marketAccount = 0;
            foreach ($list as $key => $value)
            {
                $list[$key]['letCount'] = $value['let_home_num'] + $value['let_away_num'];
                $list[$key]['sizeCount'] = $value['size_big_num'] + $value['size_small_num'];
                $marketCoin = 0;
                foreach ($quizLog as $k=> $v)
                {
                    if($value['game_id'] == $v['game_id'])
                    {
                        $marketCoin += $v['coin'];
                    }
                }
                $marketAccount += $marketCoin;
                $list[$key]['marketCoin'] = $marketCoin;
            }

            $this->assign('list', $list);
            $this->assign('marketAccount',$marketAccount);
            $this->display();
        }
        else
        {
            //列表过滤器，生成查询Map对象
            $map = $this->_search('GambleCountbkView');

            $blockTime = getBlockTime(2);
            $date = date("Ymd",strtotime("-1 day")).",".date('Ymd');
            //时间查询
            if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
                if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                    $blockTime['beginTime'] = strtotime($_REQUEST ['startTime']);
                    $blockTime['endTime']   = strtotime($_REQUEST ['endTime'])+86400;
                } elseif (!empty($_REQUEST['startTime'])) {
                    $blockTime['beginTime'] = strtotime($_REQUEST ['startTime']);
                } elseif (!empty($_REQUEST['endTime'])) {
                    $blockTime['endTime']   = strtotime($_REQUEST['endTime'])+86400;
                }
            }
            $date = date("Ymd",strtotime("-1 day")).",".date('Ymd');
            $map['_string'] = "(
                                (g.gtime between {$blockTime['beginTime']} AND {$blockTime['endTime']})
                                or (g.game_date in ({$date}) and g.game_state in (1,2,50,3,4,5,6,7))
                              )
                                and g.status = 1
                                and (u.is_sub <= 3 or g.is_show = 1)
                                and g.is_gamble = 1
                                and ( g.fsw_exp!='' or g.fsw_total!='' or g.psw_exp!='' or g.psw_total!='' )";
            //手动获取列表
            $list = $this->_list(D("GambleCountbkView"), $map , 'gtime','asc');
            $quizLog= M('quizLog')->where(['game_type'=>$gameType,'coin'=>['gt',0]])->select();
            foreach ($list as $key => $value)
            {
                //竞猜全场让分
                $list[$key]['homeAwayAll'] = ($value['all_home_num'] + $value['all_away_num']);
                //竞猜全场大小
                $list[$key]['bigSmallAll'] = ($value['all_big_num'] + $value['all_small_num']);
                //竞猜半场让球
                $list[$key]['halfHomeAwayAll'] = ($value['half_home_num'] + $value['half_away_num']);
                //竞猜半场大小
                $list[$key]['halfBigSmall'] = ($value['half_big_num'] + $value['half_small_num']);
            }
            //计算销售总金额
            $game = D('GambleCountbkView')->where($map)->field('game_id')->select();
            foreach ($game as $key => $value) {
                $gameidArr[] = $value['game_id'];
            }
            $marketAccount= M('quizLog')->where(['game_type'=>$gameType,'game_id'=>['in',$gameidArr]])->sum('coin');
            $this->assign('list', $list);
            $this->assign('marketAccount',$marketAccount);
            $this->display('bask_index');
        }
    }
    /**
     *足球和篮球的竞猜配置管理操作
     */
    public function gameConfig()
    {
        $gameType=I('gameType');
        $sign = $gameType == 1 ? "fbConfig" : 'bkConfig';
        $gameConf=M('config')->where(['sign'=>$sign])->find();

        if(IS_POST)
        {
            //获取等级组装数据
            $arr['gameTimes']    =   I('gameTimes');  //满足等级的条件:竞猜场次、胜率、销售分成、让球金币、大小球金币
            $arr['winPercent']   =   I('winPercent');
            $arr['split']        =   I('split');
            $arr['letCoin']      =   I('letCoin');
            foreach ($arr as $k => $v) {
                $count = count($arr['gameTimes']);
                for($i=0;$i<$count;$i++){
                    $userLv[$i]['gameTimes']   = $arr['gameTimes'][$i];
                    $userLv[$i]['winPercent']  = $arr['winPercent'][$i];
                    $userLv[$i]['split']       = $arr['split'][$i];
                    $userLv[$i]['letCoin']     = $arr['letCoin'][$i];
                }
            }
            $config['userLv']             = $userLv;
            $config['userLvDays']         = I('userLvDays');
            $config['gamble_desc']        = I('gamble_desc');
            $config['gamble_voice']       = I('gamble_voice');
            $config['gamble_desc_tip']    = I('gamble_desc_tip');
            $config['gamble_share']       = I('gamble_share');
            $config['gamble_share_tip']   = I('gamble_share_tip');
            $config['userSales']          = I('userSales');
            $config['webSales']           = I('webSales');
            $config['norm_point']         = I('norm_point');
            $config['impt_point']         = I('impt_point');
            $config['weekday_norm_times'] = I('weekday_norm_times');
            $config['weekend_norm_times'] = I('weekend_norm_times');
            $config['weekday_impt_times'] = I('weekday_impt_times');
            $config['weekend_impt_times'] = I('weekend_impt_times');

            if($gameType == 2){
                $config['bk_time'] = I('bk_time');
            }

            $configArr['sign'] = $sign;
            $configArr['config'] = json_encode($config);
            if(!$gameConf){
                //新增
                $rs = M('config')->add($configArr);
            }else{
                //修改
                $rs = M('config')->where(['sign'=>$sign])->save($configArr);
                if(!is_bool($rs)){
                    $rs = true;
                }
            }
            if ($rs)
                $this->success('修改成功');

            $this->error('修改失败!');
        }else{
            $this->assign('gameConf',json_decode($gameConf['config'],true));
        }
        $this->display();
    }

    /**
     *足球和篮球的竞猜配置管理操作
     */
    public function betConfig()
    {
        $sign = 'betConfig';
        $gameConf=M('config')->where(['sign'=>$sign])->find();

        if(IS_POST)
        {
            //获取等级组装数据
            $arr['gameTimes']    =   I('gameTimes');  //满足等级的条件:竞猜场次、胜率、销售分成、让球金币、大小球金币
            $arr['winPercent']   =   I('winPercent');
            $arr['split']        =   I('split');
            $arr['letCoin']      =   I('letCoin');
            foreach ($arr as $k => $v) {
                $count = count($arr['gameTimes']);
                for($i=0;$i<$count;$i++){
                    $userLv[$i]['gameTimes']   = $arr['gameTimes'][$i];
                    $userLv[$i]['winPercent']  = $arr['winPercent'][$i];
                    $userLv[$i]['split']       = $arr['split'][$i];
                    $userLv[$i]['letCoin']     = $arr['letCoin'][$i];
                }
            }
            $config['userLv']             = $userLv;
            $config['userLvDays']         = I('userLvDays');
            $config['gamble_desc']        = I('gamble_desc');
            $config['gamble_voice']       = I('gamble_voice');
            $config['gamble_desc_tip']    = I('gamble_desc_tip');
            $config['gamble_share']       = I('gamble_share');
            $config['gamble_share_tip']   = I('gamble_share_tip');
            $config['userSales']          = I('userSales');
            $config['webSales']           = I('webSales');
            $config['norm_point']         = I('norm_point');
            $config['impt_point']         = I('impt_point');
            $config['weekday_norm_times'] = I('weekday_norm_times');
            $config['weekend_norm_times'] = I('weekend_norm_times');
            $config['weekday_impt_times'] = I('weekday_impt_times');
            $config['weekend_impt_times'] = I('weekend_impt_times');
            $configArr['sign'] = $sign;
            $configArr['config'] = json_encode($config);
            if(!$gameConf){
                //新增
                $rs = M('config')->add($configArr);
            }else{
                //修改
                $rs = M('config')->where(['sign'=>$sign])->save($configArr);
                if(!is_bool($rs)){
                    $rs = true;
                }
            }
            if ($rs)
                $this->success('修改成功');

            $this->error('修改失败!');
        }else{
            $this->assign('gameConf',json_decode($gameConf['config'],true));
        }
        $this->display();
    }

}