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
			'id',
			'game_id',
			'union_name',
			'gtime',
			'game_date',
			'game_time',
			'game_state',
			'home_team_id',
			'home_team_name',
			'away_team_id',
			'away_team_name',
			'score',
			'half_score',
			'fsw_exp_home',
			'fsw_ball_home',
			'fsw_exp',
			'fsw_ball',
			'fsw_exp_away',
			'fsw_ball_away',
			'show_date',
			'web_video',
			'app_video',
			'runno',
			'rno',
			'is_recommend',
			'is_video',
			'is_go',
			'is_show',
			'is_gamble',
			'is_color',
			'is_betting',
			'bet_error',
			'bet_flag',
			'status',
			'_as'=>'g',
			'_type'=>'LEFT',
		),
		'union' => array(
			'is_sub',
			'union_id',
			'union_name',
			'_as'=>'u',
			'_type'=>'LEFT',
			'_on' => 'u.union_id = g.union_id',
		),	
	);
}

?>