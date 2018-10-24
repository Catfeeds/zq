<?php
use Think\Model\ViewModel;
class PointLogModel extends ViewModel{

    public $viewFields=array(
        'point_log'=>array(
            'id',
            'user_id',
            'log_time',
            'log_type',
            'change_num',
            'total_point',
            'gamble_id',
            'desc'=>'descc',
            '_as'=>'p',
            '_type'=>'LEFT',
            ),
        'front_user'=>array(
            'nick_name',
            'username',
            '_as'=>'f',
            '_type'=>'LEFT',
            '_on'=>'f.id = p.user_id',
            ),
        );
}
 ?>