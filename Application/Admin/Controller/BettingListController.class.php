<?php
set_time_limit(0);//0表示不限时
/**
 * 竞彩记录列表控制器
 *
 * @author
 *
 * @since
 */
class BettingListController extends CommonController {
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
    public function index()
    {
        //列表过滤器，生成查询Map对象
        $map = $this->_search("BettingView");
        $modelView = 'BettingView';
        //时间查询
        if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
            if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                $startTime = strtotime($_REQUEST ['startTime']);
                $endTime   = strtotime($_REQUEST ['endTime'])+86400;
                $map['create_time'] = array('BETWEEN',array($startTime,$endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']);
                $map['create_time'] = array('EGT',$strtotime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime'])+86400;
                $map['create_time'] = array('ELT',$endTime);
            }
        }

        $rank_gamble = I('rank_gamble');
        if($rank_gamble != ''){
            //竞彩榜前50
            $j_weekRank = M('rankBetting r')
            ->join('LEFT JOIN qc_front_user f on f.id=r.user_id')
            ->field("r.user_id,r.ranking,r.dateType,r.listDate as end_date,'2' as type")
            ->where("r.gameType = 1 and r.ranking <= 100 and (f.is_robot = 1 or f.user_type = 2)")
            ->order("r.listDate desc, r.ranking asc")->limit(400)->select();
            $j_date = $j_weekRank[0]['end_date'];
            foreach ($j_weekRank as $k => $v) {
                if($v['end_date'] != $j_date){
                    unset($j_weekRank[$k]);
                }
            }

            //获取唯一榜单 优先规则：日榜->周榜->月榜->季榜
            $j_weekRank = A('GambleList')->getUniqueRank($j_weekRank);
            $userIdArr = [];
            foreach ($j_weekRank as $k => $v) {
                switch ($rank_gamble) {
                    case '1':
                        if($v['ranking'] <= 20){
                            $userIdArr[] = $v['user_id'];
                        }
                        break;
                    case '2':
                        if($v['ranking'] <= 50){
                            $userIdArr[] = $v['user_id'];
                        }
                        break;
                    case '3':
                        if($v['ranking'] <= 100){
                            $userIdArr[] = $v['user_id'];
                        }
                        break;
                    case '4':
                        if($v['ranking'] > 20 && $v['ranking'] <= 50){
                            $userIdArr[] = $v['user_id'];
                        }
                        break;
                    case '5':
                        if($v['ranking'] > 50 && $v['ranking'] <= 100){
                            $userIdArr[] = $v['user_id'];
                        }
                        break;
                }
            }
            $map['user_id'] = ['in',$userIdArr];
            $blockTime = getBlockTime(1);
            $map['create_time'] = ['between',[$blockTime['beginTime'],$blockTime['endTime']]];
        }

        //竞彩条件
        $play_type = I('play_type','',int);
        switch ($play_type)
        {
            case '-2': $map['play_type'] = ['eq',-2];     break;
            case '2' : $map['play_type'] = ['eq',2];      break;
            default  : $map['play_type'] = ['in',[2,-2]]; break;
        }

        $nick_name = I('nick_name');
        if($nick_name != ''){
            $map['nick_name'] = $nick_name;
        }

        //赛事百分比(竞彩赛程统计传过来的)
        $BettingCount = I('BettingCount','',int);
        if($BettingCount === 1)
        {
            $BettingSubjoinGamble = M('Gamble')
                        ->field('count(id) as resultCount,result')
                        ->where($where)
                        ->where($map)
                        ->group('result')
                        ->select();
            foreach ($BettingSubjoinGamble as $k => $v) {
                if($v['result'] == 1)
                {
                    $resultArrBetting['BettingWinCount'] += $v['resultCount'];//赢的场次
                }
                elseif($v['result'] == -1)
                {
                    $resultArrBetting['BettingLoseCount'] += $v['resultCount'];//输的场次
                }
                elseif($v['result'] === 0)
                {
                    $resultArrBetting['BettingNotOutCount'] += $v['resultCount'];//未出场次
                }
                //以结算的场数
                $totleNumBetting = $resultArrBetting['BettingWinCount'] + $resultArrBetting['BettingLoseCount'];
                //赢的百分比 以结算的场数/赢的场数
                $resultArrBetting['winPercentageBetting'] = round($resultArrBetting['BettingWinCount']/$totleNumBetting*100)."%";
                //输的百分比 以结算的场数/输的场数
                $resultArrBetting['losePercentageBetting'] = round($resultArrBetting['BettingLoseCount']/$totleNumBetting*100)."%";

            }
            $this->assign('resultArrBetting',$resultArrBetting);
        }
        
        //排序字段 默认为主键名
        $order = $_REQUEST ['_order'] ? : 'id';
        if($order == 'quiz_number') $order = 'quiz_number+extra_number';
        //排序方式默认按照倒序排列
        $sort = $_REQUEST ['_sort'] == 'asc' ? 'asc' : 'desc';
        $order_by = $order." ".$sort;
        $gamble_id = I('id');
        if($gamble_id!=''){
            unset($map['id']);
            $map['g.id'] = ['eq',$gamble_id];
        }
        $username = I('username');
        $nick_name = I('nick_name');
        if($username != '' || $nick_name != ''){
            $totalCount = M('gamble g')->join("LEFT JOIN qc_front_user f on f.id = g.user_id")->where($map)->count('g.id');
        }else{
            $totalCount = M('gamble g')->where($map)->count('g.id');
        }
        //获取每页显示的条数
        $pageNum = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
        //获取当前的页码
        $currentPage = ! empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1;

        if ($totalCount > 0)
        {
            $list = D($modelView)
                  ->where($map)
                  ->order($order_by)
                  ->limit($pageNum*($currentPage-1),$pageNum)
                  ->select();
        }

        $this->assign ( 'totalCount', $totalCount );//当前条件下数据的总条数
        $this->assign ( 'numPerPage', $pageNum ); //每页显示多少条
        $this->assign ( 'currentPage', $currentPage);//当前页码

        //导出操作
        $export = I('Export');
        if ($export == 1)
        {
            if (I('totalCount') > 20000)
                $this->error('导出数据量过大请在20000条以内，请根据条件筛选后再导出');
                $list = D($modelView)->where($map)->order($order_by)->select();
        }

        //从mongo获取赛程信息
        $gameIdArr   = array_column($list,'game_id');
        $DataService = new \Common\Services\DataService();
        $mongoGame   = $DataService->getMongoGameData($gameIdArr);

        $list = HandleGamble($list);
        foreach ($list as $k => $v)
        {
            $mongoGameArr = $mongoGame[$v['game_id']];
            $list[$k]['score'] = $mongoGameArr['score'];
            $list[$k]['half_score'] = $mongoGameArr['half_score'];
            if(in_array($mongoGameArr['game_state'], [-1,4,5]))
            {
                $result = getTheWin($mongoGameArr['score'],$v['play_type'],$v['handcp'],$v['chose_side']);
            }else{
                $result = '';
            }
            $list[$k]['show_result'] = $result;
        }
        //导出操作
        if ($export == 1 )
        {
            $this->excelExport($list,'');
        }

        $this->assign('list', $list);
        unset($list);
        $this->assign('resultArrBetting',$resultArrBetting);
        $this->setJumpUrl();
        $this->display();
    }

    /**
     * @param 数据
     * @param 导出文件名
     */
    public function excelExport($list,$filename="" )
    {
        $filename = empty($filename) ? date('Y-m-d') : $filename;
        $strTable ='<table width="500" border="1">';
        $strTable .= '<tr>';
        $strTable .= '<th style="text-align:center;font-size:12px;width:"*">ID</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">赛程ID</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width=120px;>赛事名称</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">标识码</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">比赛时间</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">用户昵称(<span  style="color: red;">用户名</span>)</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">竞猜时间</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">竞猜玩法</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">主队</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">全场(<span  style="color: red;">半场</span>)</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">客队</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">竞猜内容</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">让球(<span  style="color: red;">赔率</span>)</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">竞猜积分</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">金币</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">结算结果</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">目前结果</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">获得积分</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">购买人数</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">销售金币</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">渠道类型</th>';
        $strTable .= '</tr>';
        foreach($list as $k=>$val){
            $strTable .= '<tr>';
            $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['id'].'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['game_id'].' </td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['union_name'].'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['bet_code'].'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['game_date']." ".$val['game_time'].'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['nick_name']."(<span style=\"color: red;\">".is_show_mobile($val['username'])."</span>)".'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.date('Y-m-d H:i',$val['create_time']).'</td>';
            $play_type = '';
            if ($val['play_type'] == 2) $play_type = '不让球胜平负';
            if ($val['play_type'] == -2) $play_type = '让球胜平负';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$play_type.'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['home_team_name'].'</td>';

            $score = $val['score'] ? substr_replace($val['score'],"--",stripos($val['score'],'-'),1) : '--';
            $half_score = $val['half_score'] ? substr_replace($val['half_score'],"--",stripos($val['half_score'],'-'),1) : '--';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$score."(<span style=\"color: red;\">".$half_score."</span>)".'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['away_team_name'].'</td>';
            $gambleTeam = (getUserPower()['is_show_answer'] == 1 || $val['result'] != 0) ? $val['Answer'] : '--'; 
            $strTable .= '<td style="text-align:left;font-size:12px;">'.'&nbsp&nbsp'.$gambleTeam.'</td>';
            $oddsStr = (getUserPower()['is_show_answer'] == 1 || $val['result'] != 0) ? $val['handcp']."(<span style=\"color: red;\">".$val['odds']."</span>)" : '--'; 
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$oddsStr.'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['vote_point'].'</td>';
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
             switch ($val['show_result'])
            {
                case 1:$showResult = '赢';   $color = 'color:red;';  break;
                case 0.5:$showResult = '赢半'; $color = 'color:red;'; break;
                case 2:$showResult = '平';   $color = 'color:green;';   break;
                case -1:$showResult = '输';  $color = 'color:blue;';  break;
                case -0.5:$showResult = '输半';$color = 'color:blue;';break;
                case -10:$showResult = '取消'; $color = 'color:black;'; break;
                case -11:$showResult = '待定'; $color = 'color:black;'; break;
                case -12:$showResult = '腰斩'; $color = 'color:black;'; break;
                case -13:$showResult = '中断'; $color = 'color:black;'; break;
                case -14:$showResult = '推迟'; $color = 'color:black;'; break;
                case 0:$showResult = '未开'; $color = 'color:black'; break;
                default:$result = '--';$color = 'color:black;';
            }
            $strTable .= '<td style="text-align:left;font-size:12px;'.$color.'">'.$showResult.'</td>';
            $earn_point = !empty($val['earn_point']) ? $val['earn_point'] : "--";
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$earn_point.'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['quiz_number'].'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['tradeCoin']*$val['quiz_number'].'</td>';
            switch ($val['platform'])
            {
                case 1: $platform = 'web'; break;
                case 2: $platform = 'IOS'; break;
                case 3: $platform = 'ANDRIOD'; break;
                case 4: $platform = 'M站'; break;
                default: $platform = '未知';
            }
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$platform.'</td>';
            $strTable .= '</tr>';
        }
        $strTable .='</table>';
        unset($orderList);
        downloadExcel($strTable,$filename);
        exit();
    }
}