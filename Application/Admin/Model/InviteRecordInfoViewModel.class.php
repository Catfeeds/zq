<?php

/**
 * Date: 2016/10/20
 */

use Think\Model\ViewModel;

class InviteRecordInfoViewModel extends ViewModel{

    public $viewFields=array(
        'invite_record_info'=>array(
            'id',
            'superior_id',
            'coin',
            'lv'=>'userlv',
            'user_id',
            'type',
            'before_coin',
            'after_coin',
            'before_await',
            'after_await',
            'create_time',
            '_as'=>'iri',
            '_type'=>'LEFT',
        ),
        'front_user'=>array(
            'username',
            'nick_name',
            '_as'=>'fu',
            '_type'=>'LEFT',
            '_on'=>'fu.id = iri.user_id',
        ),
    );
}
 ?>
