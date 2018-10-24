<?php
/**
 * 待结算金币视图模型
 * 
 * @author dengweijun <406516482@qq.com>
 */
use Think\Model\ViewModel;

class WaitDetailsModel extends ViewModel
{
    public $viewFields = array(
		'quiz_log' => array(
			'id',
			'log_time',
			'cover_coin',
			'_as'=>'q',
			'_type'=>'LEFT',
		),
		'gamble' => array(
			'game_date',
			'game_time',
			'home_team_name',
			'away_team_name',
			'income',
			'_as'=>'g',
			'_on' => 'g.id = q.gamble_id',
			'_type'=>'LEFT',
		),
	);
}

?>