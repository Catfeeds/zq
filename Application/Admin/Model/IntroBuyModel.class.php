<?php
use Think\Model\ViewModel;

class IntroBuyModel extends ViewModel{

    public $viewFields=array(
        'intro_buy'=>array(
            'id',
            'user_id',
            'product_id',
            'list_id',
            'create_time',
            'price',
            'platform',
            '_as'=>'i',
            '_type'=>'LEFT',
            ),
        'intro_lists'=>array(
            'is_win',
            'pub_time',
            'admin_id',
            '_as'=>'l',
            '_type'=>'LEFT',
            '_on'=>'i.list_id = l.id',
            ),
        'front_user'=>array(
            'nick_name',
            'username',
            'true_name',
            'point',
            'coin',
            '_as'=>'f',
            '_type'=>'LEFT',
            '_on'=>'f.id = i.user_id',
            ),
        'user'=>array(
            'nickname',
            '_as'=>'u',
            '_type'=>'LEFT',
            '_on'=>'u.id = l.admin_id',
            ),
        'intro_products'=>array(
            'name',
            'game_num',
            '_as'=>'ip',
            '_type'=>'LEFT',
            '_on'=>'ip.id = i.product_id',
            )
        );
}
 ?>