<?php
use Think\Model\ViewModel;

class MobileMsgModel extends ViewModel{

    public $viewFields=array(
        'mobile_msg'=>array(
            'id',
            'user_id',
            'content',
            'state',
            'is_send',
            'send_time',
            'send_type',
            '_as'=>'mm',
            '_type'=>'LEFT',
            ),
        'mobile_log'=>array(
            'name',
            'mobile',
            '_as'=>'ml',
            '_type'=>'LEFT',
            '_on'=>'ml.id = mm.mobile_id',
            ),
        'front_user'=>array(
            'nick_name',
            '_as'=>'fu',
            '_type'=>'LEFT',
            '_on'=>'fu.id = mm.user_id',
            )
        );
}
 ?>