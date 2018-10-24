<?php

/**
 * Date: 2016/10/20
 */

use Think\Model\ViewModel;

class InviteLoginInfoViewModel extends ViewModel{

    public $viewFields=array(
        'invite_login_info'=>array(
            'id',
            'user_id',
            'register_time',
            'login_time',
            'login_num',
            'type',
            'status',
            'create_time',
            'update_time',
            'pay_no',
            '_as'=>'ili',
            '_type'=>'LEFT',
        ),
        'front_user'=>array(
            'username',
            'nick_name',
            '_as'=>'fu',
            '_type'=>'LEFT',
            '_on'=>'fu.id = ili.user_id',
        ),
    );
}
 ?>
