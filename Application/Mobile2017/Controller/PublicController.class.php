<?php

use Think\Controller;

/**
 * 前台公用控制器
 */
class PublicController extends Controller
{
    //检查是否登陆
    public function checkLogin()
    {
        return is_login();
    }

    //检查是否登陆
    public function ajaxCheckLogin()
    {
        echo is_login();
    }
}

 ?>