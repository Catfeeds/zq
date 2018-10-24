<?php
class EtcGachalogModel extends CommonModel {

    protected $_link =  array(
        'FrontUser' => [
            'mapping_type'   => self::BELONGS_TO,
            'foreign_key'    => 'user_id',
            'mapping_fields' => 'username,nick_name',
            'as_fields'      => 'username,nick_name',
        ],

    );
}
 ?>