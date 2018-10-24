<?php
/**
 * 接口共用类
 */

use Think\Controller;

class NormalController extends Controller
{
    /**
     * APP文字验证码
     */
    public function verify(){
        $Verify = new \Think\Verify();
        // 设置验证码字符为纯数字
        $Verify->codeSet = '0123456789';
        $Verify->length   = 4;
        $Verify->entry();
        $value = $_SESSION['sess_']['d2d977c58444271d9c780187e93f80e5']['verify_code'];
        $deviceID = I('deviceID');
        $redis = connRedis();
        $redis->set($deviceID, $value, 60);
    }

}


 ?>