<?php
use Think\Model;
class LinkModel extends CommonModel {

    protected $_auto		=	array(
        array('add_time','time',self::MODEL_INSERT,'function'),
        );

}
 ?>