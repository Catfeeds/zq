<?php
/**
 * 足球查看记录视图模型
 * 
 * @author dengweijun <406516482@qq.com>
 */
use Think\Model\ViewModel;

class QuizLogModel extends ViewModel
{
    public $viewFields = array(
		'quiz_log' => array(
			'id',
			'user_id',
			'cover_id',
			'gamble_id',
			'log_time',
			'_as'=>'q',
			'_type'=>'LEFT',
		),
		'front_user' => array(
			'nick_name',
			'_as'=>'f',
			'_on' => 'f.id = q.cover_id',
			'_type'=>'LEFT',
		),
		'game_fbinfo' => array(
			'union_name',
			'home_team_name',
			'away_team_name',
			'game_date',
			'game_time',
			'score',
			'half_score',
			'game_state',
			'_as'=>'gf',
			'_on' => 'q.game_id = gf.game_id',
			'_type'=>'LEFT',
		),
	);
}

?>