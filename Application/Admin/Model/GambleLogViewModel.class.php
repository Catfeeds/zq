<?php
/**
 * ETC管理的足球竞猜记录精彩比赛视图模型
 *
 * @author liangzk <1343724998@qq.com>
 */
use Think\Model\ViewModel;

class GambleLogViewModel extends ViewModel
{
    public $viewFields = array(

        'etc_quiz' => array(
            'id',
            'user_id',
            'game_id',
            'bet_coin',
            'res_coin',
            'res',
            '_as'=>'e',
            '_type'=>'LEFT',

        ),
        'gamble' => array(
            'id',
            'game_id',
            'union_name',
            'game_date',
            'game_time',
            'home_team_name',
            'away_team_name',
            'score',
            'half_score',
            'play_type',
            'vote_point',
            'create_time',
            '_as'=>'g',
            '_type'=>'LEFT',
            '_on' => 'e.game_id = g.game_id',
        ),
        'front_user' => array(
            'nick_name',
            '_as'=>'f',
            '_type'=>'LEFT',
            '_on' => 'f.id = e.user_id',
        ),
    );
}

?>