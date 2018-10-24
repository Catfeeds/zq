<?php
/**
 * 高手筛选
 * User: liangzk
 * Date: 2016/9/12
 * Time: 20:24
 */

use Think\Model\ViewModel;
class MasterSearchViewModel extends ViewModel{
    
    public $viewFields=array(
        'ranking_list'=>array(
            'winrate',
            '_as'=>'r',
            '_type'=>'INNER',
        ),
        'gamble' => array(
            'id',
            'user_id',
            'union_name',
            'game_id',
            'game_date',
            'game_time',
            'home_team_name',
            'away_team_name',
            'show_date',
            'play_type',
            'chose_side',
            'odds',
            'handcp',
            'is_impt',
            'vote_point',
            'result',
            'earn_point',
            'tradeCoin',
            'create_time',
            'quiz_number',
            'is_change',
            'platform',
            '_as'=>'g',
            '_on' => 'r.user_id = g.user_id',
            '_type'=>'INNER',
        ),
        'front_user' => array(
            'nick_name',
            'username',
            'lv',
            '_type'=>'INNER',
            '_as'=> 'f',
            '_on' => 'f.id = r.user_id',
        ),
        'game_fbinfo' => array(
            'game_state',
            'score',
            'bet_code',
            'half_score',
            '_as'=>'gf',
            '_on' => 'gf.game_id = g.game_id',
        ),
    );
}
 ?>