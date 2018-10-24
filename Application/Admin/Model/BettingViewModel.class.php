<?php
/**
 * 竞猜记录列表视图模型
 *
 * @author dengweijun <406516482@qq.com>
 */
use Think\Model\ViewModel;

class BettingViewModel extends ViewModel
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
			'platform',
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
		'game_fbinfo' => array(
			'game_state',
			'score',
			'half_score',
			'_as'=>'gf',
			'_type'=>'LEFT',
			'_on' => 'gf.game_id = g.game_id',
		),
		'fb_betodds' => array(
			'bet_code',
			'win_num',
			'letwin_num',
			'lose_num',
			'letlose_num',
			'_as'=>'fb',
			'_type'=>'LEFT',
			'_on' => 'fb.game_id = g.game_id',
		),
	);
}

?>