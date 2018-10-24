<?php
use Think\Model;
class GalleryModel extends CommonModel {

    protected $_auto		=	array(
        array('add_time','time',self::MODEL_INSERT,'function'),
        array('update_time','time',self::MODEL_BOTH,'function'),
        );

    protected $_link =  array(
        'GalleryClass' => [
            'mapping_type'   => self::BELONGS_TO,
            'foreign_key'    => 'class_id',
            'mapping_fields' => 'name,path',
            'as_fields'      => 'name,path',
        ],
        'User' => [
            'mapping_type'   => self::BELONGS_TO,
            'foreign_key'    => 'editor',
            'mapping_fields' => 'nickname',
            'as_fields'      => 'nickname',
        ],
    );
}
 ?>