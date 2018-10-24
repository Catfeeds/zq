<?php
/**
 * 极验验证码处理类
 * @author liuweitao <906742852@qq.com>
 */

use Think\Model;
vendor('Geetest.lib.geetestlib');
vendor('Geetest.config.config');
use Think\Tool\Tool;
use Think\Tool\Curl;
class GeetestModel extends Model
{
    //定义gee所需数据
    public $geeData = [];

    public function _initialize(){
        parent::_initialize();
        if(I('user_id'))
        {
            $user_id = I('user_id');
        }elseif(I('userToken')){
            $userToken = getUserToken(I('userToken'));
            $user_id   = $userToken['userid'];
        }
        $this->geeData['user_id'] = $user_id;
        $this->geeData['ip_address'] = get_client_ip();
        $this->geeData['client_type'] = I('client_type','web');#web:电脑上的浏览器；h5:手机上的浏览器，包括移动应用内完全内置的web_view；native：通过原生SDK植入APP应用的方式
    }

    public function getKey()
    {
        $GtSdk = new GeetestLib(CAPTCHA_ID, PRIVATE_KEY);
        session_start();
        $data = $this->geeData;
        $status = $GtSdk->pre_process($data, 1);
        $_SESSION['gtserver'] = $status;
        $_SESSION['user_id'] = $data['user_id'];
        $res = json_decode($GtSdk->get_response_str());
        return $res;
    }

    public function validatesKey()
    {
        session_start();
        $GtSdk = new GeetestLib(CAPTCHA_ID, PRIVATE_KEY);
        $data = $this->geeData;
        if ($_SESSION['gtserver'] == 1) {   //服务器正常
            $result = $GtSdk->success_validate($_POST['geetest_challenge'], $_POST['geetest_validate'], $_POST['geetest_seccode'], $data);
            if ($result) {
//                echo '{"status":"success"}';
                $res['status'] = 'success';
            } else {
//                echo '{"status":"fail"}';
                $res['status'] = 'fail';
            }
        } else {  //服务器宕机,走failback模式
            if ($GtSdk->fail_validate($_POST['geetest_challenge'],$_POST['geetest_validate'],$_POST['geetest_seccode'])) {
//                echo '{"status":"success"}';
                $res['status'] = 'success';
            } else {
//                echo '{"status":"fail"}';
                $res['status'] = 'fail';
            }
        }
        return $res;
    }
}