<?php
/**
 * 评论视图模型
 * 
 * @author dengweijun <406516482@qq.com>
 */
use Think\Model\ViewModel;

class CommentViewModel extends ViewModel
{
    public $viewFields = array(
		'comment' => array(
			'id',
			'pid',
			'top_id',
			'publish_id',
			'user_id',
			'by_user',
			'filter_content',
			'like_num',
			'like_user',
			'status',
			'create_time',
			'_type'=>'LEFT',
		),
		'front_user' => array(
			'head',
			'nick_name',
			'is_gag',
			'_on' => 'front_user.id = comment.user_id',
		),
		
	);
}

?>