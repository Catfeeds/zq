<?php
use Think\Model\ViewModel;

class GambleCountViewModel extends ViewModel{

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
            'is_betting',
            'score',
            '_as'=>'g',
            '_type'=>'LEFT',
            ),
        'union'=>array(
            'is_sub',
            'union_id',
            'union_name',
            '_as'=>'u',
            '_type'=>'LEFT',
            '_on'=>'u.union_id = g.union_id',
        ),
        'gamble_number'=>array(
            'let_home_num',
            'let_away_num',
            'size_big_num',
            'size_small_num',
            '_as'=>'gn',
            '_type'=>'LEFT',
            '_on'=>'gn.game_id = g.game_id',
            ),
        );
}
 ?>