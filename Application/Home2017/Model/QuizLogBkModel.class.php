<?php
/**
 * 篮球查看记录视图模型
 * 
 * @author dengweijun <406516482@qq.com>
 */
use Think\Model\ViewModel;

class QuizLogBkModel extends ViewModel
{
    public $viewFields = array(
		'quiz_log' => array(
			'id',
			'user_id',
			'cover_id',
			'gamble_id',
			'log_time',
			'_type'=>'LEFT',
		),
		'front_user' => array(
			'nick_name',
			'_on' => 'front_user.id = quiz_log.cover_id',
		),
		'gamblebk' => array(
			'union_name',
			'home_team_name',
			'away_team_name',
			'play_type',
			'chose_side',
			'handcp',
			'odds',
			'result',
			'vote_point',
			'game_date',
			'game_time',
			'earn_point',
			'tradeCoin',
			'desc'=>'analysis',
			'_on' => 'gamblebk.id = quiz_log.gamble_id',
		),
		'game_bkinfo' => array(
			'score',
			'half_score',
			'game_state',
			'_as'=>'gb',
			'_on' => 'gamblebk.game_id = gb.game_id',
		),
		'bkUnion' => array(
			'union_color',
			'_as'=>'u',
			'_on' => 'u.union_id = gb.union_id',
		),
	);
}

?>