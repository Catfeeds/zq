<?php
/**
 * 欧洲杯视图模型
 * 
 * @author dengweijun <406516482@qq.com>
 */
use Think\Model\ViewModel;

class EuropeanCupViewModel extends ViewModel
{
    public $viewFields = array(
    	'EuropeanCup' => array(
    		'id',
    		'type',
    		'game_id',
    		'group_letter',
    		'status',
    		'_as'=>'s',
    		'_type'=>'LEFT',
    	),	
		'game_fbinfo' => array(
			'union_name',
			'game_date',
			'game_time',
			'game_state',
			'home_team_name',
			'home_team_id',
			'away_team_name',
			'away_team_id',
			'fsw_exp_home',
			'fsw_exp',
			'fsw_exp_away',
			'fsw_ball_home',
			'fsw_ball',
			'fsw_ball_away',
			'score',
			'half_score',
			'_as'=>'g',
			'_on' => 's.game_id = g.game_id',
		),
		'union' => array(
			'is_sub',
			'_as'=>'u',
			'_on' => 'u.union_id = g.union_id',
		),	
	);
}

?>