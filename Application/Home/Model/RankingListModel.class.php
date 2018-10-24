<?php
/**
 * 排行榜榜视图模型
 * 
 * @author dengweijun <406516482@qq.com>
 */
use Think\Model\ViewModel;

class RankingListModel extends ViewModel
{
    public $viewFields = array(
		'ranking_list' => array(
			'user_id',
			'id',
			'gameType',
			'ranking',
			'gameCount',
			'win',
			'half',
			'level',
			'transport',
			'donate',
			'winrate',
			'pointCount',
			'_as'=>'r',
			'_type'=>'LEFT',
		),
		'front_user' => array(
			'head',
			'lv',
			'lv_bk',
			'nick_name',
			'_as'=>'f',
			'_on' => 'f.id = r.user_id',
		),
		
	);
}

?>