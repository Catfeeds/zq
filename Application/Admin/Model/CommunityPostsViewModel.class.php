<?php
/**
 * 帖子列表视图模型
 *
 * @author dengweijun <406516482@qq.com>
 */
use Think\Model\ViewModel;

class CommunityPostsViewModel extends ViewModel
{
    public $viewFields = array(
		'CommunityPosts' => array(
			'id',
			'cid',
			'editor_id',
			'user_id',
			'from_base64(base64_title)'=>'base64_title',
			'from_base64(base64_content)'=>'base64_content',
			'img',
			'status',
			'is_capture',
			'comment_num',
			'report_num',
			'like_num',
			'home_recommend',
			'top_recommend',
			'create_time',
			'_type'=>'LEFT',
			'_as'=>'p',
		),
		'front_user' => array(
			'username',
			'nick_name',
			'community_status',
			'status' => 'user_status',
			'_as'=>'f',
			'_type'=>'LEFT',
			'_on' => 'f.id = p.user_id',
		),
		'community' => array(
			'name',
			'_as'=>'c',
			'_type'=>'LEFT',
			'_on' => 'c.id = p.cid',
		),
		'user' => array(
		    'nickname',
		    '_as' => 'u',
		    '_type' => 'LEFT',
		    '_on' => 'u.id = p.editor_id',
		),
	);
}

?>