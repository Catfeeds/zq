<?php
use Think\Model;
class RecruitListModel extends CommonModel {
    protected $_link =  array(
        'RecruitClass' => [
            'mapping_type'   => self::BELONGS_TO,
            'foreign_key'    => 'class_id',
            'mapping_fields' => 'name',
            'as_fields'      => 'name',
        ]
    );
}
 ?>