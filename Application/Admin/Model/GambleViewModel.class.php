<?php
/**
 * 竞猜记录列表视图模型
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
			'extra_number',
			'income',
			'is_change',
			'`desc`',
			'platform',
			'voice',
			'is_voice',
            'is_reset',
            '_as'=>'g',
            '_type'=>'LEFT',
		),
		'front_user' => array(
			'nick_name',
			'username',
			'is_robot',
			'_type'=>'LEFT',
            '_as'=> 'f',
			'_on' => 'f.id = g.user_id',
		),
	);
}

?>