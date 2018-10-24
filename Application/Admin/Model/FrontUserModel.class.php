<?php
/**
 * 账户管理
 *
 * @author wangkaimao <527993759@qq.com>
 * @since  2015-11-28
 */

use Think\Model;

// 用户模型
class FrontUserModel extends CommonModel {
    public $_validate	=	array(
        array('nick_name','','该昵称已经存在',self::EXISTS_VALIDATE,'unique',self::MODEL_BOTH),
        array('qq_unionid','','该QQ帐号已被绑定',self::VALUE_VALIDATE,'unique',self::MODEL_BOTH),
        array('weixin_unionid','','该微信帐号已被绑定',self::VALUE_VALIDATE,'unique',self::MODEL_BOTH),
        array('sina_unionid','','该微博帐号已被绑定',self::VALUE_VALIDATE,'unique',self::MODEL_BOTH),
    );
    public $_auto		=	array(
        array('password','md5',self::MODEL_BOTH,'function'),
        array('reg_time','time',self::MODEL_INSERT,'function'),
        array('reg_ip','get_client_ip',self::MODEL_INSERT,'function'),
        );
}