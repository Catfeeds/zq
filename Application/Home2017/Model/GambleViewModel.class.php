<?php
/**
 * 足球推荐记录视图模型
 * 
 * @author dengweijun <406516482@qq.com>
 */
use Think\Model\ViewModel;

class GambleViewModel extends ViewModel
{
    public $viewFields = array(
    	'gamble' => array(
    		'id',
    		'user_id',
    		'game_id',
			'union_name',
			'show_date',
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
		'game_fbinfo' => array(
			'game_id',
			'gtime',
			'game_state',
			'union_id',
			'score',
			'half_score',
			'_as'=>'gf',
			'_on' => 'g.game_id = gf.game_id',
		),
		'union' => array(
			'union_color',
			'_as'=>'u',
			'_on' => 'u.union_id = gf.union_id',
		),
		'front_user' => array(
			'lv',
			'head',
			'nick_name',
			'_on' => 'front_user.id = g.user_id',
		),
	);
}

?>