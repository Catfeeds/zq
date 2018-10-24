<?php
/**
 * 接口共用类
 * @author huangjiezhen <418832673@qq.com> 2015.12.12
 */
class CommonController extends PublicController
{
    public $userInfo = null;

    //检测是否登陆
    public function _initialize()
    {
        parent::_initialize();
        $this->userInfo = parent::getInfo();
    }

}


 ?>