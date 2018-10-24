<?php
/**
 * 用户逻辑层类
 * @author huangjiezhen <418832673@qq.com> 2015.12.15
 */

class UserLogic extends \Think\Model
{
    //登陆成功返回用户信息与配置信息
    public function loginData($userid,$mobile,$platform)
    {
        M('FrontUser')->where(['id'=>$userid])->save([
                'login_count'    => ['exp','login_count+1'],
                'login_time'     => time(),
                'last_ip'        => get_client_ip(),
                'last_login_ver' => MODULE_NAME
            ]);

        $userInfo  = D('FrontUser')->getUserInfo($userid); //用户信息

        //标识旧token被其他客户端登陆
        if ($oldToken = S('userToken:'.$mobile))
        {
            S($oldToken,-1);
        }

        //设置新token
        $userToken = md5('user'.$userid.time().mt_rand(10000,99999));
        S('userToken:'.$mobile,$userToken,C('loginLifeTime'));

        $data = [
            'userid'   => $userid,
            'username' => $mobile,
            'platform' => $platform,
            'password' => $userInfo['password']
        ];

        S($userToken,$data,C('loginLifeTime'));

        return ['userToken'=>$userToken,'userInfo'=>$userInfo];
    }
}


 ?>