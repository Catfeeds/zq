<?php
/**
 * 销售统计的足球查看操作的视图
 * @author liangzk <1343724998@qq.com>
 * @since v1.3 2016-07-01
 *
 */
use Think\Model\ViewModel;

class MarketViewModel extends ViewModel
{
    public $viewFields = array(
        'quiz_log' => array(
            'id',
            'user_id',
            'cover_id',
            'game_type',
            'gamble_id',
            'platform',
            'log_time',
            'coin',
            'ticket_id',
            '_as'=>'q',
            '_type'=>'LEFT',

            ),

        'gamble' => array(
            'union_name',
            'game_date',
            'game_time',
            'home_team_name',
            'score',
            'play_type',
            'chose_side',
            'away_team_name',
            'game_id',
            'handcp',
            'vote_point',
            'tradeCoin',
            'result',
            '_as'=>'g',
            '_type'=>'LEFT',
            '_on'=>'q.gamble_id = g.id',
            ),
    );
}




?>