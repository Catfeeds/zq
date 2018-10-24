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

    // 生成PC验证码
    public function verify(){
        $Verify = new \Think\Verify();
        // 设置验证码字符为纯数字
        $Verify->codeSet = '0123456789';
        $Verify->length   = 4;
        $Verify->entry();
    }
    
    public function privacyc(){
        $this->display();
    }

    //app下载h5页面
    public function appDownload(){
        $this->display('Api@Index:download');
    }

    //app下载web页面
    public function appIntroduce(){
        $this->display('Api@Index:introduce');
    }
}

 ?>