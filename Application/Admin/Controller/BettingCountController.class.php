<?php
/**
 * 足球竞彩统计列表控制器
 *
 * @author
 *
 * @since
 */
class BettingCountController extends CommonController {
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
            //赛事名称查询
            $map = $this->_search('BettingCountView');
            $blockTime = getBlockTime(1);
            $map['gtime'] = array('BETWEEN',array($blockTime['beginTime'],$blockTime['endTime']));
            $map['_string'] = "(and g.is_color = 1 and g.status = 1)";
            $map['is_betting']=['eq',1];
            //客队名称查询
            $away_team_name = trim(I('away_team_name'));
            if (! empty($away_team_name)) $map['away_team_name'] = ['like',$away_team_name.'%'];
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
            $list = $this->_list(D('BettingCountView'),$map,'game_time asc,game_id asc');
            foreach ($list as $k => $v)
            {
                $gameidArr[] = $v['game_id'];
            }
            
            $quizLog = M('quizLog q')
                    ->join('left join qc_gamble g on q.gamble_id = g.id')
                    ->where(['game_type'=>1,'coin'=>['gt',0],'q.game_id'=>['in',$gameidArr],'g.play_type'=>['in',[2,-2]]])
                    ->select();

            $bettingMarketAccount = 0;
            foreach ($list as $key => $value)
            {
                $list[$key]['totleNum'] = $value['let_win_num'] + $value['let_draw_num'] + $value['let_lose_num'];
                $list[$key]['totleNotNum'] = $value['not_win_num'] + $value['not_draw_num'] + $value['not_lose_num'];
                $marketCoin = 0;
                foreach ($quizLog as $k => $v)
                {
                    if($value['game_id'] == $v['game_id'])
                    {
                        $marketCoin += $v['coin'];
                    }
                }
                $bettingMarketAccount += $marketCoin;
                $list[$key]['marketCoin'] = $marketCoin;
            }
            $this->assign('bettingMarketAccount',$bettingMarketAccount);
            $this->assign('list', $list);
            $this->assign('marketAccount',$marketAccount);
            $this->display();

    }
}