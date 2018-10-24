<?php
/**
 * 竞猜记录列表视图模型
 * 
 * @author dengweijun <406516482@qq.com>
 */
use Think\Model\ViewModel;

class CommentViewModel extends ViewModel
{
    public $viewFields = array(
		'Comment' => array(
			'id',
			'pid',
			'publish_id',
			'user_id',
			'content',
			'status',
			'reg_ip',
			'report_num',
			'report_content',
			'report_user',
			'is_report',
			'platform',
			'create_time',
			'_type'=>'LEFT',
			'_as'=>'c',
		),
		'front_user' => array(
			'username',
			'nick_name',
			'is_gag',
			'is_robot',
			'is_expert',
			'_as'=>'f',
			'_type'=>'LEFT',
			'_on' => 'f.id = c.user_id',
		),	
		'publish_list' => array(
			'title',
			'_as'=>'p',
			'_type'=>'LEFT',
			'_on' => 'p.id = c.publish_id',
		),	
	);
}

?>