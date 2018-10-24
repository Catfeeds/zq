<?php
/**
 * 空控制器
 * @author chenzj <443629770@qq.com> 2016.04.22
 */
use Think\Controller;

class EmptyController extends CommonController
{
    /**
     * 空操作,用于输出404页面.
     */
    public function _empty(){
        $this->redirect('Public/error');
    }
}

?>