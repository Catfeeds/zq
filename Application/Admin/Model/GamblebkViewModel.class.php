<?php
/**
 * 竞猜记录列表视图模型
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
			'`desc`',
			'platform',
			'voice',
			'is_voice',
			'_as'=>'g',
			'_type'=>'LEFT',
		),
		'front_user' => array(
			'nick_name',
			'username',
			'_type'=>'LEFT',
			'_on' => 'front_user.id = g.user_id',
		),
		'game_bkinfo' => array(
			'game_state',
			'score',
			'half_score',
			'_as'=>'gf',
			'_type'=>'LEFT',
			'_on' => 'gf.game_id = g.game_id',
		)
	);
}

?>