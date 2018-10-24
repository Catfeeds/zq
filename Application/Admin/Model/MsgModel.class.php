<?php
use Think\Model;
class MsgModel extends CommonModel {

    protected $_auto		=	array(
        );

    protected $_link =  array(
        'User' => [
            'mapping_type'   => self::BELONGS_TO,
            'foreign_key'    => 'user_id',
            'mapping_fields' => 'nickname,account',
            'as_fields'      => 'nickname,account',
        ],
        'FrontUser' => [
            'mapping_type'   => self::BELONGS_TO,
            'foreign_key'    => 'front_user_id',
            'mapping_fields' => 'nick_name,username',
            'as_fields'      => 'nick_name,username',
        ]
    );
}
 ?>