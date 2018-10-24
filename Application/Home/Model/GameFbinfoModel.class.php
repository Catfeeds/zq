<?php
/**
 * 精彩比赛视图模型
 * 
 * @author dengweijun <406516482@qq.com>
 */
use Think\Model\ViewModel;

class GameFbinfoModel extends ViewModel
{
    public $viewFields = array(
		'game_fbinfo' => array(
			'game_id',
			'union_name',
			'game_date',
			'game_time',
			'game_state',
			'home_team_id',
			'home_team_name',
			'away_team_id',
			'away_team_name',
			'score',
            'gtime',
			'fsw_exp_home',
			'fsw_ball_home',
			'fsw_exp',
			'fsw_ball',
			'fsw_exp_away',
			'fsw_ball_away',
			'_type'=>'LEFT',
		),
		'union' => array(
			'is_sub',
			'_as'=>'u',
			'_on' => 'u.union_id = game_fbinfo.union_id',
		),	
	);
}

?>