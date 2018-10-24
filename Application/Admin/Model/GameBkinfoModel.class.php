<?php
/**
 * 精彩比赛视图模型
 *
 * @author dengweijun <406516482@qq.com>
 */
use Think\Model\ViewModel;

class GameBkinfoModel extends ViewModel
{
    public $viewFields = array(
		'game_bkinfo' => array(
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
			'list_score',
			'fsw_exp_home',
			'fsw_exp',
			'fsw_exp_away',
			'fsw_total_home',
			'fsw_total',
			'fsw_total_away',
			'psw_exp_home',
			'psw_exp',
			'psw_exp_away',
			'psw_total_home',
			'psw_total',
			'psw_total_away',
			'show_date',
			'web_video',
			'app_video',
			'is_recommend',
			'is_video',
			'is_go',
			'is_show',
			'is_gamble',
			'status',
			'total',
			'_as'=>'g',
			'_type'=>'LEFT',
		),
		'bkUnion' => array(
			'is_sub',
			'union_id',
			'_as'=>'u',
			'_type'=>'LEFT',
			'_on' => 'u.union_id = g.union_id',
		),
	);
}

?>