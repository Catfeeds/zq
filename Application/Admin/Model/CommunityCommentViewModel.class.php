<?php
/**
 * 帖子列表视图模型
 *
 * @author dengweijun <406516482@qq.com>
 */
use Think\Model\ViewModel;

class CommunityCommentViewModel extends ViewModel
{
    public $viewFields = array(
		'CommunityComment' => array(
			'id',
			'user_id',
			'post_id',
			'content',
			'status',
			'is_capture',
			'report_num',
			'report_content',
			'report_user',
			'is_report',
			'create_time',
			'_type'=>'LEFT',
			'_as'=>'c',
		),
		'CommunityPosts' => array(
			'from_base64(base64_title)'=>'base64_title',
			'_type'=>'LEFT',
			'_as'=>'p',
			'_on' => 'p.id = c.post_id',
		),
		'front_user' => array(
			'username',
			'nick_name',
			'community_status',
			'status' => 'user_status',
			'_as'=>'f',
			'_type'=>'LEFT',
			'_on' => 'f.id = c.user_id',
		),
	);
}

?>