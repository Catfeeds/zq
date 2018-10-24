<?php
/**
 * 空控制器
 * @author dengweijun <406516482@qq.com> 2016.02.26
 */
use Think\Controller;

class EmptyController extends Controller
{
    /**
     * 空操作,用于输出404页面.
     */
    public function _empty(){
        $this->redirect('Public/error');
    }
}

?>