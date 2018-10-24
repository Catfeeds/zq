<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/9/30
 * Time: 14:35
 */

use Think\Model\ViewModel;

class AdminRequestModel extends ViewModel{

    public $viewFields=array(
        'admin_request'=>array(
            'id',
            'user_id',
            'last_ip',
            'request',
            'response',
            'request_time',
            'response_time',
            'module',
            'controller',
            'action',
            '_as'=>'a',
            '_type'=>'LEFT',
        ),
        'user'=>array(
            'account',
            'nickname',
            '_as'=>'u',
            '_type'=>'LEFT',
            '_on'=>'u.id = a.user_id',
        ),

    );
}
 ?>