<?php
/**
 * 空控制器
 */
use Think\Controller;

class EmptyController extends Controller
{
    /* 空操作，用于输出404页面 */
    public function _empty(){
        header("HTTP/1.1 404 Not Found");  
        header("Status: 404 Not Found");  
        $this->display('Public/error');
        die;
    }
}

?>