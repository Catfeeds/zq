<?php
use Think\Model;
class CommunityModel extends CommonModel {

    protected $_auto		=	array(
        array('create_time','time',self::MODEL_INSERT,'function'),
        array('update_time','time',self::MODEL_BOTH,'function'),
        );
}
 ?>