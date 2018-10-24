<?php
/**
 * 交易管理的销售统计
 * @author liangzk <liangzk@qc.com>
 * @since v1.3 2016-06-30
 */

class MarketCountController extends CommonController
{

    /**
     * index
     */
    public function index()
    {
        $_REQUEST['numPerPage'] = 30;
        $Export = (int)I('Export');
        //过滤
        $map = $this->_search('AccountLog');
         //时间查询
        if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
            if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                $startTime = strtotime($_REQUEST ['startTime']);
                $endTime   = strtotime($_REQUEST ['endTime'])+86400;
                $map['log_time'] = array('BETWEEN',array($startTime,$endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']);
                $map['log_time'] = array('EGT',$strtotime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime'])+86400;
                $map['log_time'] = array('ELT',$endTime);
            }
        }

        //判断足球还是篮球
        $game_type = I('game_type') == 1 ?  1 : 2;
        //初始化Model
        $accountLogModel = M('AccountLog');
        $quizLogModel = M('QuizLog');

        if($game_type == 1)//足球
        {
            $whereArr['id']         = ['GT',0];
            $whereArr['game_type']  = 1;
            $whereArr['coin']       = ['GT',0];

        }elseif ($game_type == 2) //篮球
        {
            $whereArr['id']         = ['GT',0];
            $whereArr['game_type']  = 2;
            $whereArr['coin']       = ['GT',0];
        }
        //获取列表记录的数量
        $querySql = $accountLogModel
                    ->where($map)
                    ->group("FROM_UNIXTIME(log_time,'%Y%m%d')")
                    ->Field("id")
                    ->buildSql();
        $timeDayCount = M()->table($querySql.' a')->count('a.id');
        //获取每页显示的条数
        $pageNum = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
        //获取当前的页码
        $currentPage =! empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1;

        //列表
        $map['id'] = ['GT',0];//目的优化sql
        $list = $accountLogModel
                ->where($map)
                ->group("FROM_UNIXTIME(log_time,'%Y%m%d')")
                ->Field("FROM_UNIXTIME(log_time,'%Y%m%d') as log_time")
                ->order("FROM_UNIXTIME(log_time,'%Y%m%d') desc")
                ->limit($pageNum*($currentPage-1),$pageNum)
                ->select();
        $logTimeArr = array_map(function ($element){return $element['log_time'];},$list);//获取日期
        if (! empty($logTimeArr)) $whereIn = "(".implode(',',$logTimeArr).")";
        //每天的自动充值金额统计、每天的手动充值金额统计、每天的赠送金额统计
        $changeNumRes = $accountLogModel
                        ->where( empty($whereIn) ? array() : ['_string' => "FROM_UNIXTIME(log_time,'%Y%m%d') IN ".$whereIn])
                        ->where(['log_type' => ['in',[1,8,5,7]],'id' => ['GT',0]])
                        ->Field('change_num,log_type,FROM_UNIXTIME(log_time,\'%Y%m%d\') as log_time')
                        ->select();
        $changeNumClass = arr_val_grouping($changeNumRes,'log_time');//按日期分组
        unset($changeNumRes);
        //每天的竞猜数量、每天总消费金额、购买竞猜总人数
        $quizGamleRes = $quizLogModel
                        ->where( empty($whereIn) ? array() : ['_string' => "FROM_UNIXTIME(log_time,'%Y%m%d') IN ".$whereIn])
                        ->where($whereArr)
                        ->Field('id,user_id,coin,FROM_UNIXTIME(log_time,\'%Y%m%d\') as log_time,ticket_id')
                        ->select();
                    $quizGamleClass = arr_val_grouping($quizGamleRes,'log_time');//按日期分组
        unset($quizGamleRes);
        //购买正式用户的竞猜和机器人的竞猜的数量、购买正式用户的竞猜和机器人的竞猜的消费金额
        $quizRes = $quizLogModel
                    ->alias('q')
                    ->join('LEFT JOIN qc_front_user f on q.cover_id = f.id')
                    ->where( empty($whereIn) ? array() : ['_string' => "FROM_UNIXTIME(log_time,'%Y%m%d') IN ".$whereIn])
                    ->where(['q.id' => ['GT',0],'q.game_type' => $game_type,'q.coin' => ['GT',0]])
                    ->group("FROM_UNIXTIME(q.log_time,'%Y%m%d'),f.is_robot")
                    ->Field('count(q.id) as gambleCount ,sum(q.coin) as gambleSum,
                            f.is_robot,FROM_UNIXTIME(q.log_time,\'%Y%m%d\') as log_time')
                    ->select();
        $quizResClass = arr_val_grouping($quizRes,'log_time');//按日期分组
        unset($quizRes);
        //购买正式用户的竞猜和机器人的竞猜的分成金额

        $sql = 'SELECT SUM(q.cover_coin) AS intoCoinSum,f.is_robot,FROM_UNIXTIME(q.log_time,\'%Y%m%d\') AS log_time FROM'
                .' qc_quiz_log q LEFT JOIN qc_front_user f ON q.cover_id = f.id'
                .' WHERE FROM_UNIXTIME(log_time,\'%Y%m%d\') IN '.$whereIn.' AND q.id > 0 '
                .' AND q.coin > 0 AND q.game_type = '.$game_type
                .' GROUP BY FROM_UNIXTIME(q.log_time,\'%Y%m%d\'),f.is_robot';
        $intoCoinSum = M()->query($sql);
        $intoCoinSumClass = arr_val_grouping($intoCoinSum,'log_time');//按日期分组
        unset($intoCoinSum);
        //购买正式用户的竞猜和机器人的竞猜的赢、赢半、输、输半、平的场数的统计
        $where = empty($whereIn)
                    ?
                    ['q.id' => ['GT',0],'q.coin'=>['GT',0],'q.game_type'=>$game_type,['g.result'=>['IN',$game_type == 1 ? [1,0.5,-1,-0.5,2] : [1,-1,2]],]]
                    :
                    ['q.id' => ['GT',0],'q.coin'=>['GT',0],'q.game_type'=>$game_type,['g.result'=>['IN',$game_type == 1 ? [1,0.5,-1,-0.5,2] : [1,-1,2]],],['_string' => "FROM_UNIXTIME(q.log_time,'%Y%m%d') IN ".$whereIn]];
        if ($game_type == 1)
        {
            $resultRes = $quizLogModel
                        ->alias('q')
                        ->join(' INNER JOIN qc_front_user f on q.cover_id = f.id')
                        ->join(' INNER JOIN qc_gamble g on q.gamble_id = g.id')
                        ->union(['field'=>'count(g.result) as resultCount,g.result,f.is_robot,FROM_UNIXTIME(q.log_time,\'%Y%m%d\') as log_time',
                                 'where'=>$where,
                                 'group'=>"FROM_UNIXTIME(q.log_time,'%Y%m%d'),f.is_robot,g.result",
                                 'table'=>'qc_quiz_log q INNER JOIN qc_front_user f ON q.cover_id = f.id  INNER JOIN qc_gamble_reset g ON q.gamble_id = g.id'],
                            true)
                        ->where($where)
                        ->group("FROM_UNIXTIME(q.log_time,'%Y%m%d'),f.is_robot,g.result")
                        ->field('count(g.result) as resultCount,g.result,f.is_robot,FROM_UNIXTIME(q.log_time,\'%Y%m%d\') as log_time')
                        ->select();
        }
        elseif ($game_type == 2)
        {
            $resultRes = $quizLogModel
                        ->alias('q')
                        ->join('LEFT JOIN qc_front_user f on q.cover_id = f.id')
                        ->join('LEFT JOIN qc_gamblebk g on q.gamble_id = g.id')
                        ->where($where)
                        ->group("FROM_UNIXTIME(q.log_time,'%Y%m%d'),f.is_robot,g.result")
                        ->field('count(g.result) as resultCount,g.result,f.is_robot,FROM_UNIXTIME(q.log_time,\'%Y%m%d\') as log_time')
                        ->select();
        }

        $resultResClass = arr_val_grouping($resultRes,'log_time');//按日期分组
        unset($where);
        unset($resultRes);
        foreach ($list as $key => $value)
        {
            //每天的自动充值金额统计、每天的手动充值金额统计、每天的赠送金额统计
            foreach ($changeNumClass as $k => $v)
            {
                if ($value['log_time'] == $k)
                {
                    $logTypeClass = arr_val_grouping($v,'log_type');//根据金额类型分组（自动充值、手动充值、赠送金币）
                    //每天的自动充值金额统计
                    $list[$key]['autoRecharge'] = array_sum(array_map('array_shift',$logTypeClass['8'])) + array_sum(array_map('array_shift',$logTypeClass['7']));
                    //每天的手动充值金额统计
                    $list[$key]['manualRecharge'] = array_sum(array_map('array_shift',$logTypeClass['1']));
                    //每天的赠送金额统计
                    $list[$key]['givingCount'] = array_sum(array_map('array_shift',$logTypeClass['5']));
                }
                unset($logTypeClass);
            }
            //每天的竞猜数量、每天总消费金额、购买竞猜总人数
            foreach ($quizGamleClass  as $k => $v)
            {
                if ($value['log_time'] == $k)
                {
                    //每天的竞猜数量
                    $list[$key]['gambleSum'] = count($this->get_arr_column($v,'id'));
                    //每天总消费金额
                    $consumeCoin = $ticketCoin = 0;
                    foreach ($v as $i => $ii) {
                        if($ii['ticket_id'] == 0){
                            $consumeCoin += $ii['coin'];
                        }
                        if($ii['ticket_id'] > 0){
                            $ticketCoin += $ii['coin'];
                        }
                    }
                    $list[$key]['consumeCoin'] = $consumeCoin;
                    $list[$key]['ticketCoin']  = $ticketCoin;
                    //购买竞猜总人数
                    $list[$key]['gambleCount'] = count(array_unique($this->get_arr_column($v,'user_id')));
                }
            }
            foreach ($quizResClass as $k => $v)
            {
                //竞猜的数量、竞猜的消费金额
                if ($value['log_time'] == $k)
                {

                    $isRobotClass = arr_val_grouping($v,'is_robot');//根据是否为机器人进行分组
                    //购买正式用户的竞猜的数量
                    $list[$key]['gambleNotRobotSum'] =  $this->get_arr_column($isRobotClass['0'],'gambleCount')['0'];
                    //购买正式用户的竞猜的消费金额
                    $list[$key]['gambleBuySum'] = $this->get_arr_column($isRobotClass['0'],'gambleSum')['0'];
                    //机器人被购买的竞猜的数量
                    $list[$key]['gambleIsRobotSum'] = $this->get_arr_column($isRobotClass['1'],'gambleCount')['0'];
                    //机器人被购买的竞猜的消费金额
                    $list[$key]['gambleIsBuySum'] = $this->get_arr_column($isRobotClass['1'],'gambleSum')['0'];
                }
                unset($isRobotClass);
            }
//            //竞猜的分成金额
//            foreach ($intoCoinSumClass as $k => $v)
//            {
//                if ($value['log_time'] == $k)
//                {
//                    $isRobotClass = arr_val_grouping($v,'is_robot');//根据是否为机器人进行分组
//                    //购买正式用户的竞猜的分成金额
//                    $list[$key]['gambleIntoSum'] = $this->get_arr_column($isRobotClass['0'],intoCoinSum)['0'];
//                    //机器人被购买的竞猜的分成金额
//                    $list[$key]['gambleIntoIsIntoSum'] = $this->get_arr_column($isRobotClass['1'],intoCoinSum)['0'];
//                }
//                unset($isRobotClass);
//            }
            //竞猜的赢、赢半、输、输半、平的场数的统计
            $gambleWinCount = $gambleLoseCount = $gambleFlatCount = $gambleWinIRCount = $gambleLoseIRCount = $gambleFlatIRCount = 0;
            foreach ($resultResClass as $k => $v)
            {
                if ($value['log_time'] == $k)
                {
                    $isRobotClass = arr_val_grouping($v,'is_robot');//根据是否为机器人进行分组
                    //正式用户
                    foreach ($isRobotClass['0'] as $k => $v)
                    {
                        if ($game_type == 1 ? $v['result'] == 1 || $v['result'] == 0.5 : $v['result'] == 1){
                            $gambleWinCount += $v['resultCount'];//胜(购买正式用户的竞猜的赢、赢半的场数)
                        }
                        if ($game_type == 1 ? $v['result'] == -1 || $v['result'] == -0.5 : $v['result'] == -1){
                            $gambleLoseCount += $v['resultCount'];//输(购买正式用户的竞猜的输、输半的场数)
                        }
                        if ($v['result'] == 2){
                            $gambleFlatCount += $v['resultCount'];//平(购买正式用户的竞猜的平的场数)
                        }

                    }
                    //机器人
                    foreach ($isRobotClass['1'] as $k => $v)
                    {
                        if ($game_type == 1 ? $v['result'] == 1 || $v['result'] == 0.5 : $v['result'] == 1)
                            $gambleWinIRCount  += $v['resultCount'];//胜(购买机器人的竞猜的赢、赢半的场数)
                        if ($game_type == 1 ? $v['result'] == -1 || $v['result'] == -0.5 : $v['result'] == -1)
                            $gambleLoseIRCount += $v['resultCount'];//输(购买机器人的竞猜的输、输半的场数)
                        if ($v['result'] == 2)
                            $gambleFlatIRCount += $v['resultCount'];//平(购买机器人的竞猜的平的场数)
                    }
                }
                unset($isRobotClass);
            }
            $list[$key]['gambleWinCount']  = $gambleWinCount;
            $list[$key]['gambleLoseCount'] = $gambleLoseCount;
            $list[$key]['gambleWin']   = round($gambleWinCount/($gambleWinCount + $gambleLoseCount) * 100);
            $list[$key]['gambleFlatCount'] = $gambleFlatCount;

            $list[$key]['gambleWinIRCount']  = $gambleWinIRCount;
            $list[$key]['gambleLoseIRCount'] = $gambleLoseIRCount;
            $list[$key]['gambleWinIR']   = round($gambleWinIRCount/($gambleWinIRCount + $gambleLoseIRCount) * 100);
            $list[$key]['gambleFlatIRCount'] = $gambleFlatIRCount;
        }
        unset($changeNumClass);
        foreach ($list as $k => $v) {
            $totalAmount += $v['autoRecharge'];
        }
        $this->assign('totalAmount',$totalAmount);
        if($Export == 1)
        {
           $this->excelMarketExport($list,$game_type);//导出；
        }


        $this->assign ( 'totalCount', $timeDayCount );//当前条件下数据的总条数
        $pageNum =empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
        $this->assign ( 'numPerPage', $pageNum ); //每页显示多少条
        $this->assign ( 'currentPage', !empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1);//当前页码
        $this->setJumpUrl();
        $this->assign('desc_pag',I('pageNum') ? I('pageNum')-1 : 0 );//用来页面序号的记录
        $this->assign('list',$list);
        $this->display();
    }
    public function excelMarketExport($list,$gameType)
    {
        $filename = empty($filename) ? date('Y-m-d') : $filename;
        $strTable ='<table width="500" border="1">';
        if($gameType == 1)
        {
            $strTable .= '<tr>';
            $strTable .= '<th style="text-align:center;font-size:12px;width:"*">序号</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">日期</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*";>充值金额</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">营销支出</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">赠送金额</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">被购买竞猜的数量</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">金币总消费金额</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">体验券消费金额</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">总购买人数</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">真实用户被购买数量</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">消费金额</th>';
//            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">分成金额</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">赢</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">输</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">平</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">机器人被购买数量</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">消费金额</th>';
//            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">分成金额</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">赢</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">输</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">平</th>';
            $strTable .= '</tr>';
            foreach($list as $k=>$vo)
            {
                $strTable .= '<tr>';
                $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.++$i.'</td>';
                $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$vo['log_time'].'</td>';
                $autoRecharge = ! empty($vo['autoRecharge']) ? $vo['autoRecharge'] : 0 ;
                $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$autoRecharge.'</td>';
                $manualRecharge = ! empty($vo['manualRecharge']) ? $vo['manualRecharge'] : 0 ;
                $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$manualRecharge.'</td>';
                $givingCount = ! empty($vo['givingCount']) ? $vo['givingCount'] : 0 ;
                $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$givingCount.'</td>';
                $gambleSum = ! empty($vo['gambleSum']) ? $vo['gambleSum'] : 0 ;
                $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$gambleSum.'</td>';
                $consumeCoin = ! empty($vo['consumeCoin']) ? $vo['consumeCoin'] : 0 ;
                $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$consumeCoin.'</td>';
                $ticketCoin = ! empty($vo['ticketCoin']) ? $vo['ticketCoin'] : 0 ;
                $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$ticketCoin.'</td>';
                $gambleCount = ! empty($vo['gambleCount']) ? $vo['gambleCount'] : 0 ;
                $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$gambleCount.'</td>';
                $gambleNotRobotSum = ! empty($vo['gambleNotRobotSum']) ? $vo['gambleNotRobotSum'] : 0 ;
                $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$gambleNotRobotSum.'</td>';
                $gambleBuySum = ! empty($vo['gambleBuySum']) ? $vo['gambleBuySum'] : 0 ;
                $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$gambleBuySum.'</td>';
//                $gambleIntoSum = ! empty($vo['gambleIntoSum']) ? $vo['gambleIntoSum'] : 0 ;
//                $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$gambleIntoSum.'</td>';
                $gambleWinCount = ! empty($vo['gambleWinCount']) ? $vo['gambleWinCount'] : 0 ;
                $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$gambleWinCount.'</td>';
                $gambleLoseCount = ! empty($vo['gambleLoseCount']) ? $vo['gambleLoseCount'] : 0 ;
                $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$gambleLoseCount.'</td>';
                $gambleFlatCount = ! empty($vo['gambleFlatCount']) ? $vo['gambleFlatCount'] : 0 ;
                $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$gambleFlatCount.'</td>';
                $gambleIsRobotSum = ! empty($vo['gambleIsRobotSum']) ? $vo['gambleIsRobotSum'] : 0 ;
                $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$gambleIsRobotSum.'</td>';
                $gambleIsBuySum = ! empty($vo['gambleIsBuySum']) ? $vo['gambleIsBuySum'] : 0 ;
                $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$gambleIsBuySum.'</td>';
//                $gambleIntoIsIntoSum = ! empty($vo['gambleIntoIsIntoSum']) ? $vo['gambleIntoIsIntoSum'] : 0 ;
//                $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$gambleIntoIsIntoSum.'</td>';
                $gambleWinIRCount = ! empty($vo['gambleWinIRCount']) ? $vo['gambleWinIRCount'] : 0 ;
                $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$gambleWinIRCount.'</td>';
                $gambleLoseIRCount = ! empty($vo['gambleLoseIRCount']) ? $vo['gambleLoseIRCount'] : 0 ;
                $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$gambleLoseIRCount.'</td>';
                $gambleFlatIRCount = ! empty($vo['gambleFlatIRCount']) ? $vo['gambleFlatIRCount'] : 0 ;
                $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$gambleFlatIRCount.'</td>';
                $strTable .= '</tr>';
            }
        }
        $strTable .='</table>';
        downloadExcel($strTable,$filename);
        exit();
    }
    /**
     * @User: lianzk <1343724998@qq.com>
     * @date 2016-07-14 @time 15:29
     * 获取数组中的某一列
     * @param type $arr 数组
     * @param type $key_name  列名
     * @return type  返回那一列的数组
     */
    function get_arr_column($arr, $key_name)
    {
        $arr_column = array();
        foreach($arr as $key => $val){
            $arr_column[] = $val[$key_name];
        }
        return $arr_column;
    }

    /**
     * 销售统计的查看操作
     */
    public function check()
    {
        //用户销售统计---购买人数--分篮球、足球
        $accountSign = I('accountSign');
        if ($accountSign == 1)
        {
            $user_id = I('user_id');
            $startTime = I('startTime',0);
            $startTime = $startTime == 0 ? 0 : strtotime($startTime);
            $endTime = I('endTime',0);
            $endTime = $endTime == 0 ? strtotime(date("Y-m-d"))+86400 : strtotime($endTime)+86400;
            $timeWhere = ' log_time between '.$startTime.' and '.$endTime;
        }
        else
        {
            $log_time = I('log_time');
        }
        $game_type = I('game_type') ? : 2;
        $gambleClass = $game_type == 1 ? 'MarketView' : 'MarketBkView';//判断是足球还是篮球
        
        $nick_name = I('nick_name');
        //初始化Model
        $frontUserModel = M('FrontUser');
        //购买者昵称查询
        if(! empty($nick_name))
        {
            $userArr = $frontUserModel->where(['nick_name' => ['LIKE',$nick_name.'%']])->getField('id',true);
            $whereUser = ' and q.user_id IN (';
            foreach ($userArr as $key => $value)
            {
                $whereUser = $whereUser."'".$value."',";
            }
            $whereUser = substr($whereUser, 1,-1);
            $whereUser .= ')';


        }
        else
        {
            $whereUser = '';
        }

        //被购买者昵称查询
        $nick_nameBy = I('nick_nameBy');
        if(! empty($nick_nameBy))
        {
            $userByArr = $frontUserModel->where(['nick_name' => ['LIKE',$nick_nameBy.'%']])->getField('id',true);
            $whereUserBy = ' and q.cover_id IN (';
            foreach ($userByArr as $key => $value)
            {
                $whereUserBy = $whereUserBy."'".$value."',";
            }
            $whereUserBy = substr($whereUserBy, 1,-1);
            $whereUserBy .= ')';


        }
        else
        {
            $whereUserBy = '';
        }

        //获取sql
        if ($accountSign == 1)
        {
            $where = ['coin'=>['GT',0],'q.user_id'=>$user_id,'game_type'=>$game_type,'_string'=>$timeWhere.' '.$whereUser.' '.$whereUserBy];
        }
        else
        {
            $where = ['coin'=>['GT',0],'game_type'=>$game_type,'_string'=>"FROM_UNIXTIME(log_time,'%Y%m%d') = ".mysql_real_escape_string($log_time).' '.$whereUser.' '.$whereUserBy];
        }
        //是否使用体验券筛选
        $is_ticket = I('is_ticket');
        if($is_ticket != ''){
            $where['ticket_id'] = $is_ticket == 0 ? 0 : ['gt',0];
        }
        if ($game_type == 1)
        {
            $querySql = D($gambleClass)
                        ->union(['field'=>'q.id,q.user_id AS user_id, q.cover_id AS cover_id, q.game_type AS game_type,
                                            q.gamble_id AS gamble_id, q.platform AS platform, q.log_time AS log_time,
                                            q.coin AS coin,q.ticket_id, g.union_name AS union_name, g.game_date AS game_date, g.game_time AS game_time,
                                            g.home_team_name AS home_team_name, g.score AS score, g.play_type AS play_type,
                                            g.chose_side AS chose_side, g.away_team_name AS away_team_name, g.game_id AS game_id,
                                            g.handcp AS handcp, g.vote_point AS vote_point, g.tradeCoin AS tradeCoin, g.result AS result',
                                 'where'=>$where,
                                 'table'=>'qc_quiz_log q INNER JOIN qc_gamble_reset g ON q.gamble_id = g.id '
                        ])
                        ->where($where)
                        ->buildSql();
        }
        else if ($game_type == 2)
        {
            $querySql = D($gambleClass)
                ->where($where)
                ->buildSql();
        }

        //统计记录的数量
        $timeDayCount = M()->table($querySql.' a')->count('user_id');
        //获取每页显示的条数
        $pageNum = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
        //获取当前的页码
        $currentPage =! empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1;

        //导出操作
        $export = I('Export');
        if(!empty($export))
        {
             $list = M()->table($querySql.' b')->order('log_time desc')->select();
        }
        else
        {
            //列表
            $list = M()->table($querySql.' b')->order('log_time desc')->limit($pageNum*($currentPage-1),$pageNum)->select();
        }

        foreach ($list as $key => $value)
        {
            $userIdArr[] = $value['user_id'];
            $userIdArr[] = $value['cover_id'];
        }
        $userIdArr = array_unique($userIdArr);
        $frontUserRes = $frontUserModel->where(['id'=>['in',$userIdArr]])->field('id,nick_name,is_robot,username')->select();
        foreach ($list as $k => $v)
        {
            foreach ($frontUserRes as $kk => $vv) {
                if($v['user_id'] == $vv['id']){
                    $list[$k]['nick_nameIng'] = $vv['nick_name'];
                    $list[$k]['is_robotIng'] = $vv['is_robot'];
                }
                if($v['cover_id'] == $vv['id']){
                    $list[$k]['nick_nameBy'] = $vv['nick_name'];
                    $list[$k]['usernameBy'] = $vv['username'];
                    $list[$k]['is_robotBy'] = $vv['is_robot'];
                }
            }
        }
        $this->assign ( 'totalCount', $timeDayCount );//当前条件下数据的总条数
        $pageNum =empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
        $this->assign ( 'numPerPage', $pageNum ); //每页显示多少条
        $this->assign ( 'currentPage', !empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1);//当前页码
        $this->setJumpUrl();

        if(!empty($export))
        {
            $this->excelExport($list,'',$game_type);//导出；
        }
        $list = HandleGamble($list,0,false,$game_type);
        $this->assign('list',$list);
        $gambleModel = $game_type == 1 ? 'gamble' : 'gamblebk';
        $result = M("quizLog q")->join("LEFT JOIN qc_".$gambleModel." g on g.id = q.gamble_id")->field("result,count(1) resultNum")->where($where)->group("result")->select();
        $win   = 0;
        $half  = 0;
        $level = 0;
        $lose  = 0;
        $lhalf = 0;
        foreach ($result as $k => $v) {
            switch ($v['result']) {
                case    '1': $win   += $v['resultNum']; break;
                case  '0.5': $half  += $v['resultNum']; break;
                case    '2': $level += $v['resultNum']; break;
                case   '-1': $lose  += $v['resultNum']; break;
                case '-0.5': $lhalf += $v['resultNum']; break;
            }
        }
        $this->assign('win',$win);
        $this->assign('half',$half);
        $this->assign('level',$level);
        $this->assign('lose',$lose);
        $this->assign('lhalf',$lhalf);
        $this->display();
    }
    /*
     * @param        $list  列表
     * @param string $filename  导出的文件名
     * @param int $gameType  1:足球；2：篮球
     */
    public function excelExport($list,$filename="",$game_type = 1)
    {
        $filename = empty($filename) ? date('Y-m-d') : $filename;
        $strTable ='<table width="500" border="1">';
        $strTable .= '<tr>';
        $strTable .= '<th style="text-align:center;font-size:12px;width:"*">购买人的名称</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width=120px;>购买日期</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">购买渠道</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">被购买人的名称</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">比赛时间</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">竞猜玩法</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">主队</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">全场</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">客队</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">竞猜球队</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">盘口</th>';
//        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">竞猜积分</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">金币</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">目前结果</th>';
        $strTable .= '</tr>';

        foreach($list as $k=>$val){
            $strTable .= '<tr>';
            $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['nick_nameIng'].'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.date('Y/m/d H:i:s',$val['log_time']).' </td>';
            $platform = '';
            if ($val['platform'] == 1 ) $platform = 'Web';
            if ($val['platform'] == 2 ) $platform = 'IOS';
            if ($val['platform'] == 3 ) $platform = 'Android';
            if ($val['platform'] == 4 ) $platform = 'M站';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$platform.'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['nick_nameBy'].'('.$val['usernameBy'].')'.'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['game_date']." ".$val['game_time'].'</td>';
            $play_type = '';
            if ($val['play_type'] == 1) $play_type = '全场让分';
            if ($val['play_type'] == -1) $game_type == 1 ? $play_type = '竞猜大小' : '全场大小' ;
            if ($val['play_type'] == 2) $play_type = '半场让分';
            if ($val['play_type'] == -2) $play_type = '半场大小';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$play_type.'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['home_team_name'].'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.
                            substr_replace($val['score'],"--",stripos($val['score'],'-'),1)
                        .'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['away_team_name'].'</td>';
            $gambleTeam = "";
            if ($val['play_type'] == 1) {

                $val['chose_side'] == 1 ? $gambleTeam = $val['home_team_name'] : $gambleTeam = $val['away_team_name'];
            }
            else {
                $val['chose_side'] == 1 ? $gambleTeam = '大球' : $gambleTeam = '小球';
            }
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$gambleTeam.'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['handcp'].'</td>';
//            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['vote_point'].'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['tradeCoin'].'</td>';
            switch ($val['result'])
            {
                case 1:$result = '赢';   $color = 'color:red;';  break;
                case 0.5:$result = '赢半'; $color = 'color:red;'; break;
                case 2:$result = '平';   $color = 'color:green;';   break;
                case -1:$result = '输';  $color = 'color:blue;';  break;
                case -0.5:$result = '输半';$color = 'color:blue;';break;
                case -10:$result = '取消'; $color = 'color:black;'; break;
                case -11:$result = '待定'; $color = 'color:black;'; break;
                case -12:$result = '腰斩'; $color = 'color:black;'; break;
                case -13:$result = '中断'; $color = 'color:black;'; break;
                case -14:$result = '推迟'; $color = 'color:black;'; break;
                default:$result = '--';$color = 'color:black;';
            }
            $strTable .= '<td style="text-align:left;font-size:12px;'.$color.'">'.$result.'</td>';
            $strTable .= '</tr>';
        }
        $strTable .='</table>';
        unset($orderList);
        downloadExcel($strTable,$filename);
        exit();
    }

}



?>