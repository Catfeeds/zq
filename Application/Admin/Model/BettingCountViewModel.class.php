<?php
use Think\Model\ViewModel;

class BettingCountViewModel extends ViewModel{

    public $viewFields=array(
        'game_fbinfo'=>array(
            'id',
            'game_id',
            'game_date',
            'game_time',
            'union_name',
            'show_date',
            'half_score',
            'bet_code',
            'home_team_name',
            'away_team_name',
            'score',
            '_as'=>'g',
            '_type'=>'LEFT',
            ),
        'union'=>array(
            'is_sub',
            '_as'=>'u',
            '_type'=>'LEFT',
            '_on'=>'u.union_id = g.union_id',
        ),
        'gamble_number'=>array(
            'let_home_num',
            'let_away_num',
            'size_big_num',
            'size_small_num',
            'let_win_num',
            'let_draw_num',
            'let_lose_num',
            'not_win_num',
            'not_draw_num',
            'not_lose_num',
            '_as'=>'gn',
            '_type'=>'LEFT',
            '_on'=>'gn.game_id = g.game_id',
            ),
        );
}
 ?>