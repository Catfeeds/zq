<?php
/**
 * 篮球推荐记录视图模型
 * 
 * @author dengweijun <406516482@qq.com>
 */
use Think\Model\ViewModel;

class GamblebkViewModel extends ViewModel
{
    public $viewFields = array(
    	'gamblebk' => array(
    		'id',
    		'user_id',
    		'game_id',
			'union_name',
			'game_date',
			'game_time',
			'home_team_name',
			'away_team_name',
			'play_type',
			'chose_side',
			'odds',
			'handcp',
			'is_impt',
			'result',
			'vote_point',
			'earn_point',
			'tradeCoin',
			'quiz_number',
			'desc'=>'analysis',
			'_as'=>'g',
			'_type'=>'LEFT',
		),
		'game_bkinfo' => array(
			'game_id',
			'score',
			'game_state',
			'half_score',
			'union_id',
			'_as'=>'gf',
			'_on' => 'g.game_id = gf.game_id',
		),
		'bkUnion' => array(
			'union_color',
			'_as'=>'u',
			'_on' => 'u.union_id = gf.union_id',
		),
	);
}

?>