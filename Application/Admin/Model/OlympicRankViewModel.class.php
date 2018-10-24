<?php
/**
 * 奥运竞猜排行列表视图模型
 *
 * @author dengweijun <406516482@qq.com>
 */
use Think\Model\ViewModel;

class OlympicRankViewModel extends ViewModel
{
    public $viewFields = array(
		'OlympicRank' => array(
			'id',
			'user_id',
			'year_date',
			'ranking',
			'gameCount',
			'win',
			'transport',
			'winrate',
			'pointCount',
			'_as'=>'o',
			'_type'=>'LEFT',
		),
		'front_user' => array(
			'nick_name',
			'username',
			'is_robot',
			'_type'=>'LEFT',
			'_on' => 'front_user.id = o.user_id',
		),
	);
}

?>