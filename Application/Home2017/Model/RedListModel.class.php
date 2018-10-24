<?php
/**
 * 红人榜视图模型
 * 
 * @author dengweijun <406516482@qq.com>
 */
use Think\Model\ViewModel;

class RedListModel extends ViewModel
{
    public $viewFields = array(
		'red_list' => array(
			'user_id',
			'id',
			'ranking',
			'win',
			'half',
			'level',
			'transport',
			'donate',
			'winrate',
			'pointCount',
			'_as' => 'r',
			'_type'=>'LEFT',
		),
		'front_user' => array(
			'head',
			'lv',
			'lv_bk',
			'nick_name',
			'_as' => 'f',
			'_on' => 'f.id = r.user_id',
		),
		
	);
}

?>