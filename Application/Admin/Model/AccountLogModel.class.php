<?php
use Think\Model\ViewModel;

class AccountLogModel extends ViewModel{

    public $viewFields=array(
        'account_log'=>array(
            'id',
            'user_id',
            'log_type',
            'game_type',
            'log_status',
            'log_time',
            'model_overtime',
            'change_num',
            'poundage',
            'transfer_way',
            'pay_no',
            'total_coin',
            'desc'=>'descc',
            'platform',
            'pay_way',
            'admin_id',
            'order_id',
            'gamble_id',
            'operation_time',
            '_as'=>'a',
            '_type'=>'LEFT',
            ),
        'front_user'=>array(
            'nick_name',
            'username',
            'bank_name',
            'bank_full_name',
            'bank_card_id',
            'true_name',
            'alipay_id',
            'point',
            'coin',
            'unable_coin',
            'reg_time',
            'login_time',
            'login_count',
            'device_token',
            'last_ip',
            '_as'=>'f',
            '_type'=>'LEFT',
            '_on'=>'f.id = a.user_id',
            ),
        'user'=>array(
            'account',
            'nickname',
            '_as'=>'u',
            '_type'=>'LEFT',
            '_on'=>'u.id = a.admin_id',

            ),
        
        );
}
 ?>