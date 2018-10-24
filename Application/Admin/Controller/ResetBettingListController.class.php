<?php
set_time_limit(0);//0表示不限时
/**
 * 重置竞彩记录列表控制器
 *
 * @author
 *
 * @since
 */
class ResetBettingListController extends CommonController {
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
        $map = $this->_search('GambleResetView');
        //时间查询
        if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
            if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                $startTime = strtotime($_REQUEST ['startTime']);
                $endTime   = strtotime($_REQUEST ['endTime']);
                $map['create_time'] = array('BETWEEN',array($startTime,$endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']);
                $map['create_time'] = array('EGT',$strtotime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime']);
                $map['create_time'] = array('ELT',$endTime);
            }
        }

        $nick_name = I('nick_name');
        if($nick_name != ''){
            $map['nick_name'] = $nick_name;
        }

        //竞彩条件
        $map['play_type'] = ['in',[2,-2]];

        $_order = I('_order');
        $order = empty($_order) ? 'id desc' : $_order.' '.I('_sort');
        $field = empty($_order) || $_order == 'id' ? '' : ','.$_order;
        //统计记录的数量
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

        //导出操作
        $export = I('Export');
        if ($export == 1)
        {
            if (I('totalCount') > 20000)
                $this->error('导出数据量过大请在20000条以内，请根据条件筛选后再导出');
                $list = M()->query('select id,user_id,union_name,game_id,game_date,game_time,home_team_name,
                                            away_team_name,show_date,play_type,chose_side,odds,handcp,is_impt,vote_point,
                                            result,earn_point,tradeCoin,create_time,quiz_number,is_change,platform,is_reset,
                                            nick_name,username,game_state,score,half_score,bet_code from ( '.
                    D('GambleResetView')->where($map)->buildSql().' ) g order by  '.$order );
        }
        $list = HandleGamble($list);
        foreach ($list as $k => $v)
        {
            if($v['game_state'] == -1)
            {
                $result = getTheWin($v['score'],$v['play_type'],$v['handcp'],$v['chose_side']);
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
            $this->excelExport($list,'');
        }

        $this->assign('list', $list);
        unset($list);
        $this->setJumpUrl();
        $this->display();
    }

    /**
     * @author
     * @version
     * @param
     * @param
     * @param
     * @param
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
            if ($val['play_type'] == 2) $play_type = '非让球';
            if ($val['play_type'] == -2) $play_type = '让球';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$play_type.'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['home_team_name'].'</td>';

            $score = $val['score'] ? substr_replace($val['score'],"--",stripos($val['score'],'-'),1) : '--';
            $half_score = $val['half_score'] ? substr_replace($val['half_score'],"--",stripos($val['half_score'],'-'),1) : '--';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$score."(<span style=\"color: red;\">".$half_score."</span>)".'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['away_team_name'].'</td>';
            $gambleTeam = "";
            if ($val['play_type'] == 2 || $val['play_type'] == -2) {
                if($val['chose_side'] == 1)
                {
                    $gambleTeam = '赢';
                }
                elseif($val['chose_side'] == 0)
                {
                    $gambleTeam = '平';
                }
                elseif($val['chose_side'] == -1)
                {
                    $gambleTeam = '负';
                }
            }
            $strTable .= '<td style="text-align:left;font-size:12px;">'.'&nbsp&nbsp'.$gambleTeam.'</td>';
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