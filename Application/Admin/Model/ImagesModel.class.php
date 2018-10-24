<?php
use Think\Model;
class ImagesModel extends CommonModel {
    protected $_link =  array(
        'ImagesClass' => [
            'mapping_type'   => self::BELONGS_TO,
            'foreign_key'    => 'type_id',
            'mapping_fields' => 'name',
            'as_fields'      => 'name',
        ]
    );
}
 ?>