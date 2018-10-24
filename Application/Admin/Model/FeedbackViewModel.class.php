<?php
use Think\Model\ViewModel;

class FeedbackViewModel extends ViewModel{

    public $viewFields=array(
        'Feedback'=>array(
            'id',
            'user_id',
            'create_time',
            'content',
            'phone',
            'reply',
            'admin_id',
            'do_type',
            'reply_time',
            '_as'=>'fb',
            '_type'=>'LEFT',
            ),
        'FrontUser'=>array(
            'username',
            'nick_name',
            '_as'=>'f',
            '_type'=>'LEFT',
            '_on'=>'f.id = fb.user_id',
            ),
        'user'=>array(
            'account',
            'nickname',
            '_as'=>'u',
            '_type'=>'LEFT',
            '_on'=>'u.id = fb.admin_id',
        ),
    );
}