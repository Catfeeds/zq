<?php
/**
 *Etc管理的粤卡通竞猜记录视图
 *@author liangzk <1343724998@qq.com>
 */
use Think\Model\ViewModel;
class EtcQuizViewModel extends ViewModel
{

    public $viewFields =array(
        'etc_quiz'=>array(
            'id',
            'user_id',
            'game_id',
            'bet_coin',
            'res_coin',
            'bet_type',
            'res',
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