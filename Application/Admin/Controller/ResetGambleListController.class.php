<?php
set_time_limit(0);//0表示不限时
/**
 * 竞猜记录列表控制器
 *
 * @author dengweijun <406516482@qq.com>
 *
 * @since  2015-12-16
 */
class ResetGambleListController extends CommonController {
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
        $gameType = I('gameType');
        $modelView = $gameType == 1 ? 'GambleResetView' : 'GamblebkResetView';
        //列表过滤器，生成查询Map对象
        $map = $this->_search($modelView);
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

        $nick_name = I('nick_name');
        if($nick_name != ''){
            $map['nick_name'] = $nick_name;
        }

        if ($gameType == 1)//足球
        {
            $map['play_type'] = ['in',[1,-1]];
            $_order = I('_order');
            $order = empty($_order) ? 'id desc' : $_order.' '.I('_sort');
            $field = empty($_order) || $_order == 'id' ? '' : ','.$_order;
            $ModelResetView = D('GambleResetView');
            $username = I('username');
            $nick_name = I('nick_name');
            if($username != '' || $nick_name != ''){
                $gambleResetCount = D('GambleReset g')->join("LEFT JOIN qc_front_user f on f.id = g.user_id")->where($map)->count('g.id');
            }else{
                $gambleResetCount = D('GambleReset g')->where($map)->count('g.id');
            }
            
            //获取每页显示的条数
            $pageNum = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
            //获取当前的页码
            $currentPage =! empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1;
            if ($gambleResetCount > 0)
            {
                $list = $ModelResetView
                    ->where($map)
                    ->limit($pageNum*($currentPage-1),$pageNum)
                    ->order($order)
                    ->select();
            }
            $this->assign ( 'totalCount', $gambleResetCount );//当前条件下数据的总条数
            $this->assign ( 'numPerPage', $pageNum ); //每页显示多少条
            $this->assign ( 'currentPage', $currentPage);//当前页码
        }
        elseif ($gameType == 2)//篮球
        {
            $list = $this->_list(D($modelView), $map ,'create_time');//获取列表
        }

        $export = I('Export');
        if ($export == 1) //判断是否为导出操作
        {
            if (I('totalCount') > 20000)
                $this->error('导出数据量过大，请根据条件筛选后再导出');

            if ($gameType == 1)//足球
            {
                $list = $ModelResetView->where($map)->order($order)->select();

            }
            elseif ($gameType == 2)//篮球
            {
                $list = D($modelView)->where($map)->order('create_time desc')->select();
            }

        }
        $list = HandleGamble($list);
        foreach ($list as $k => $v)
        {
            if($v['game_state'] == -1)
            {
                if($gameType == 1)
                {
                    $result = getTheWin($v['score'],$v['play_type'],$v['handcp'],$v['chose_side']);
                }
                else
                {
                    $result = getTheWinbk($v['score'],$v['half_score'],$v['play_type'],$v['handcp'],$v['chose_side']);
                }
            }
            else
            {
                $result = $v['game_state'];
            }
            $list[$k]['show_result'] = $result;
        }
        //导出操作
        if ($export == 1 )
        {
            $this->excelExport($list,'',$gameType,$gambleCount == 1 ? $resultArr : 1);
        }
        $this->assign('list', $list);
        $this->setJumpUrl();
        $this->display();
    }

    /**
     * @author liangzk <1343724998@qq.com>
     * @version 2.0
     * @date 2016-07-13  @time 10:41
     * @param        $list  列表
     * @param string $filename  导出的文件名
     * @param int $gameType  1:足球；2：篮球
     * @param array $percentage  比率
     */
    public function excelExport($list,$filename="",$gameType = 1,$percentage )
    {
        $filename = empty($filename) ? date('Y-m-d') : $filename;
        $strTable ='<table width="500" border="1">';

        if (is_array($percentage))
        {
            $strTable .= '<tr>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">未出场数</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">赢的人数</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">胜率</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">输的人数</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">输率</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">平的人数</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">平率</th>';
            $strTable .= '</tr>';
            $strTable .= '<tr>';
            if (empty($percentage['notOutCount'])) $notOutCount = 0;
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$notOutCount.'</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$percentage['winCount'].'</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$percentage['winpercentage'].'</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$percentage['loseCount'].'</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$percentage['losepercentage'].'</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$percentage['flatCount'].'</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$percentage['flatpercentage'].'</th>';
            $strTable .= '</tr>';
            $strTable .= '<tr>';
            $strTable .= '</tr>';
        }

        $strTable .= '<tr>';
        $strTable .= '<th style="text-align:center;font-size:12px;width:"*">ID</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">赛程ID</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width=120px;>赛事名称</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">比赛时间</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">用户昵称(<span  style="color: red;">用户名</span>)</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">竞猜时间</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">竞猜玩法</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">主队</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">全场(<span  style="color: red;">半场</span>)</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">客队</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">竞猜球队</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">盘口(<span  style="color: red;">指数</span>)</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">竞猜积分</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">金币</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">结算结果</th>';
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
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['game_date']." ".$val['game_time'].'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['nick_name']."(<span style=\"color: red;\">".is_show_mobile($val['username'])."</span>)".'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.date('Y-m-d H:i',$val['create_time']).'</td>';
            $play_type = '';
            if ($val['play_type'] == 1) $play_type = '全场让分';
            if ($val['play_type'] == -1) $gameType == 1 ? $play_type = '竞猜大小' : '全场大小' ;
            if ($val['play_type'] == 2) $play_type = '半场让分';
            if ($val['play_type'] == -2) $play_type = '半场大小';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$play_type.'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['home_team_name'].'</td>';

            $score = $val['score'] ? substr_replace($val['score'],"--",stripos($val['score'],'-'),1) : '--';
            $half_score = $val['half_score'] ? substr_replace($val['half_score'],"--",stripos($val['half_score'],'-'),1) : '--';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$score."(<span style=\"color: red;\">".$half_score."</span>)".'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['away_team_name'].'</td>';
            $gambleTeam = "";
            if ($val['play_type'] == 1) {

                $val['chose_side'] == 1 ? $gambleTeam = $val['home_team_name'] : $gambleTeam = $val['away_team_name'];
            }
            else {
                $val['chose_side'] == 1 ? $gambleTeam = '大球' : $gambleTeam = '小球';
            }
            $is_impt = !empty($val['is_impt']) ? '(<span style="color: red;">重点</span>)' : '';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$gambleTeam.$is_impt.'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['handcp']."(<span style=\"color: red;\">".$val['odds']."</span>)".'</td>';
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
            $earn_point = !empty($val['earn_point']) ? $val['earn_point'] : "--";
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$earn_point.'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['quiz_number'].'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['tradeCoin']*$val['quiz_number'].'</td>';
            switch ($val['platform'])
            {
                case 1: $platform = 'web'; break;
                case 2: $platform = 'IOS'; break;
                case 3: $platform = 'ANDRIOD'; break;
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