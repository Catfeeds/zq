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

        $userInfo = $this->master(true)->field($field)->where(['id'=>$userid])->find();
        $userInfo['face'] = frontUserFace($userInfo['head']);

        return $userInfo;
    }
}


 ?>