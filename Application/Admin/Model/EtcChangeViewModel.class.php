<?php
/**
 * ETC管理的积分兑换视图模型
 *
 * @author liangzk <1343724998@qq.com>
 */
use Think\Model\ViewModel;

class EtcChangeViewModel extends ViewModel
{
    public $viewFields = array(
        'etc_change' => array(
            'id',
            'user_id',
            'change_integral',
            'add_time',
            '_as'=>'e',
            '_type'=>'LEFT',
        ),
        'etc_user' => array(
            'nick_name',
            '_on' => 'etc_user.id = e.user_id',
        ),

    );
}

?>