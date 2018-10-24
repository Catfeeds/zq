<?php
/**
 * 空控制器
 * @author dengweijun <406516482@qq.com> 2016.02.26
 */
use Think\Controller;

class EmptyController extends PublicController
{
    public function _empty(){
        $this->ajaxReturn(404);
    }
}

?>