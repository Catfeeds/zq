<?php
use Think\Model\ViewModel;

class InviteViewModel extends ViewModel{

    public $viewFields = array(
        'invite_info' => array(
            'id',
            'user_id',
            'first_num',
            'second_num',
            'third_num',
            'total_num',
            'first_coin',
            'second_coin',
            'third_coin',
            'total_coin',
            'valid_coin',
            'invalid_coin',
            'await_coin',
            'create_time',
            'update_time',
            'is_get',
            'register_coin',
            '_as' => 'i',
            '_type' => 'LEFT',
            ),
        'front_user' => array(
            'id' => 'frontuser_id',
            'head',
            'username',
            'nick_name',
            'true_name',
            'reg_time',
            'login_time',
            'is_robot',
            'reg_ip',
            'last_ip',
            'login_count',
            'invitation_code',
            '_as' => 'f',
            '_type' => 'LEFT',
            '_on' => 'i.user_id = f.id'
            ),
        );
}
 ?>