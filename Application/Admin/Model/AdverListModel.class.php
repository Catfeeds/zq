<?php
use Think\Model;
class AdverListModel extends CommonModel {

    protected $_auto		=	array(
        array('add_time','time',self::MODEL_INSERT,'function'),
        );

    protected $_link =  array(
        'AdverClass' => [
            'mapping_type'   => self::BELONGS_TO,
            'foreign_key'    => 'class_id',
            'mapping_fields' => 'name',
            'as_fields'      => 'name',
        ]
    );
}
 ?>