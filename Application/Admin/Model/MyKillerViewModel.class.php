<?php
use Think\Model\ViewModel;

class MyKillerViewModel extends ViewModel{

    public $viewFields=array(
        'my_killer'=>array(
            'id',
            'user_id',
            'fb_sub_change',
            'fb_sub_win',
            'fb_color_change',
            'fb_color_win',
            'curr_victs',
            'status',
            '_as'=>'m',
            '_type'=>'LEFT',
            ),
        'front_user'=>array(
            'nick_name',
            'username',
            'is_robot',
            'is_push',
            '_as'=>'f',
            '_type'=>'LEFT',
            '_on'=>'f.id = m.user_id',
            ),
        
        );
}
 ?>