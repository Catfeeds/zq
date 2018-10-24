<?php
/**
 * 前台用户类
 * @author huangjiezhen <418832673@qq.com> 2015.12.23
 */

class FrontUserModel extends \Think\Model
{
    //获取用户信息
    function getUserInfo($userid)
    {
        $field = [
            'id',
            'username',
            'password',
            'nick_name',
            'lv',
            'head',
            'point',
            'descript',
            'coin',
            'frozen_coin',
            'unable_coin',
            'true_name',
            'identfy',
            'reg_time',
            'login_time',
            'bank_name',
            'bank_card_id',
            'bank_region',
        ];

        $userInfo            = $this->master(true)->field($field)->where(['id'=>$userid])->find();
        $userInfo['fansNum'] = M('FollowUser')->where(['follow_id'=>$userid])->count();
        $userInfo['face']    = frontUserFace($userInfo['head']);

        return $userInfo;
    }

    //登陆成功后的相关处理与返回信息
    public function loginData($userid,$lastLoginTime,$point,$mobile,$platform)
    {
        //每天首次登陆赠送积分
        if ($lastLoginTime < strtotime(date('Ymd')))
        {
            $changeNum = C('givePoint')['login'];
            $point += $changeNum;

            M('PointLog')->add([
                'user_id'     => $userid,
                'log_time'    => NOW_TIME,
                'log_type'    => 11,
                'change_num'  => $changeNum,
                'total_point' => $point,
                'desc'        => '登陆赠送'
            ]);
            //发送系统消息通知
            sendMsg($userid,'积分赠送通知','您好！今日首次登录赠送'.$changeNum.'积分，详情请查看积分明细。');
        }

        //更新用户信息
        M('FrontUser')->where(['id'=>$userid])->save([
                'login_count'    => ['exp','login_count+1'],
                'login_time'     => NOW_TIME,
                'last_ip'        => get_client_ip(),
                'last_login_ver' => MODULE_NAME,
                'point'          => $point
            ]);

        $userInfo = $this->getUserInfo($userid); //用户信息

        //标识旧token被其他客户端登陆
        if ($oldToken = S('userToken:'.$mobile))
        {
            S($oldToken,-1);
        }

        //设置新token
        $userToken = md5('user'.$userid.NOW_TIME.mt_rand(10000,99999));
        S('userToken:'.$mobile,$userToken,C('loginLifeTime'));

        $data = [
            'userid'   => $userid,
            'username' => $mobile,
            'platform' => $platform,
            'password' => $userInfo['password']
        ];

        S($userToken,$data,C('loginLifeTime'));

        $data = [
            'userToken' => $userToken,
            'userInfo'  => $userInfo,
            'givePoint' => isset($changeNum) ? $changeNum : 0
        ];

        return $data;
    }
}


 ?>