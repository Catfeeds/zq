<?php
/**
 * 竞猜赛程对阵
 * User: liangzk
 * Date: 2016/7/19
 * Time: 11:01
 * version 1.0
 */
use Think\Model\ViewModel;
class BetoddsAgainstViewModel extends ViewModel
{
    public $viewFields = array(
        'game_fbinfo' => array(
            'id',
            'union_id',
            'union_name',
            'game_id',
            'gtime',
            'game_state',

            'home_team_name',
            'away_team_name',
            'score',
            '_as' => 'g',
            '_type' => 'LEFT',
        ),
        'fb_betodds' => array(
            'id',
            'game_id',
            'bet_code',
            'home_odds',
            'draw_odds',
            'away_odds',
            'let_exp',
            'bet_code',
            'home_letodds',
            'draw_letodds',
            'away_letodds',
            '_as' => 'f',
            '_on' => 'f.game_id = g.game_id',
        ),

    );
}
?>