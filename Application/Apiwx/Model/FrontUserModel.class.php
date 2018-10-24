<?php
/**
 * 前台用户类
 * @author huangjiezhen <418832673@qq.com> 2015.12.23
 */

class FrontUserModel extends \Think\Model
{
    //检查注册ip：相同IP一个小时注册不能超过20个，注册不限制时间间隔
    public function checkReg()
    {
        $ip = get_client_ip();
        $loginConf = getWebConfig('loginGift');
        $regList = M('FrontUser')->field('reg_time')->where(['reg_ip'=>$ip,'reg_time'=>['between', [NOW_TIME-60*60, NOW_TIME]],'mac_addr'=>I('deviceID')])->order('reg_time desc')->select();

        if ((isset($regList[0]['reg_time']) && (NOW_TIME - $regList[0]['reg_time'] <= $loginConf['reg_limit_time']*60)) || count($regList) > $loginConf['reg_limit_count'])
            return false;

        return $ip;
    }

    //获取用户信息
    function getUserInfo($userid)
    {
        $field = [
            'id',
            'username',
            'password',
            'nick_name',
            'lv',
            'lv_bet',
            'head as face',
            'point',
            'descript',
            'coin',
            'frozen_coin',
            'unable_coin',
            'true_name',
//            'identfy',
            'reg_time',
            'login_time',
//            'bank_name',
//            'bank_card_id',
//            'bank_region',
//            'alipay_id'
        ];

        $userInfo = $this->master(true)->field($field)->where(['id'=>$userid])->find();
        if(!$userInfo)
            return '';

        $userInfo['fansNum'] = M('FollowUser')->where(['follow_id'=>$userid])->count();
        $userInfo['face']    = frontUserFace($userInfo['face']);
//        $userInfo['alipay_id'] = (string) $userInfo['alipay_id'];

        //处理身份证号码
        /*
        $len   = strlen($userInfo['identfy']);
        $start = $length = floor($len/3);
        $str   = '';
        for($i=0;$i<$start;$i++){
            $str .= '*';
        }
        $userInfo['identfy'] = substr_replace($userInfo['identfy'], $str, $start, $length);
*/
        return $userInfo;
    }

    /**
     * 登陆成功后的相关处理与返回信息
     * @param $userid
     * @param $lastLoginTime
     * @param $point
     * @param $mobile
     * @param $platform
     * @return array
     */
    public function loginData($userid,$lastLoginTime,$point,$mobile,$platform)
    {
        //判断一个小时内重复不算登录
        if(NOW_TIME - $lastLoginTime >= 60*60) {
            //更新用户信息
            M('FrontUser')->where(['id' => $userid])->save([
                'login_count' => ['exp', 'login_count+1'],
                'login_time' => NOW_TIME,
                'last_ip' => get_client_ip(),
                'last_login_ver' => MODULE_NAME,
                'point' => $point
            ]);
        }else{
            M('FrontUser')->where(['id' => $userid])->save([
                'point' => $point,
                'last_ip' => get_client_ip(),
                'last_login_ver' => MODULE_NAME,
            ]);
        }

        $userInfo = $this->getUserInfo($userid); //用户信息

        //标识旧token被其他客户端登陆
        if ($oldToken = S('userToken:'.$userid))
        {
            S($oldToken,-1);
        }

        //设置新token
        $userToken = md5('user'.$userid.NOW_TIME.mt_rand(10000,99999));
        S('userToken:'.$userid, $userToken, C('loginLifeTime'));

        $data = [
            'userid'   => $userid,
            'username' => $mobile,
            'platform' => $platform,
            'password' => $userInfo['password']
        ];

        S($userid, $data, C('loginLifeTime'));
/*
        $userInfo['userToken'] = $userToken;
        $userInfo['platform']  = $platform;
        $res[0] = $userInfo;
*/
        return $userid;
    }


}


 ?>