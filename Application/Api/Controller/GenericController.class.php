<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/10
 * Time: 14:29
 */
class GenericController extends PublicController
{
    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 获取用户信息
     */
    public function getUserInfo()
    {
        $info = getUserToken($this->param['userToken']);

        if (isset($info['userid'])) {
            $where = ['id' => $info['userid']];
        } else {
            if ($username = $this->param['username']) {
                $where = ['username' => $username];
            }

            if ($username = $this->param['tel']) {
                $where = ['username' => $username];
            }
        }

        if ($where) {
            $userInfo = M('FrontUser')
                ->field(['id as lnm', 'nick_name', 'username', 'lv', 'lv_bet', 'lv_bk', 'head face', 'descript', 'status', 'customer_msg'])
                ->where($where)
                ->find();

            $userInfo['face'] = frontUserFace($userInfo['face']);
            $userInfo['username'] = (string)$userInfo['username'];

            //是否登录
            $userInfo['lstatus'] = S('userToken:' . $userInfo['lnm']) ? 1 : 0;
        }

        $this->ajaxReturn($userInfo ?: 1001);
    }
}