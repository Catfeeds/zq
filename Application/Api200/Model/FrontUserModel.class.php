<?php
/**
 * 前台用户类
 * @author huangjiezhen <418832673@qq.com> 2015.12.23
 */

class FrontUserModel extends \Think\Model
{
    //检查注册ip
    public function checkReg()
    {
        $ip = get_client_ip();
        $loginConf = getWebConfig('loginGift');
        $regList = M('FrontUser')->field('reg_time')->where(['reg_ip'=>$ip,'reg_time'=>['gt',strtotime(date('Y-m-d'))],'mac_addr'=>I('deviceID')])->order('reg_time desc')->select();

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

    //增加注册赠送金币
    public function loginGift($userid,$platform)
    {
        $config = M('Config')->where(['sign' => 'loginGift'])->getField('config');
        $config = json_decode($config, true);

        if ($config['is_on'] && $config['giftCoin'] > 0 && NOW_TIME >= $config['begin'] && NOW_TIME <= $config['end']) {
            $coinField = $config['coinType'] == 0 ? 'unable_coin' : 'coin';

            if (M('FrontUser')->where(['id' => $userid])->save([$coinField => $config['giftCoin']]) !== false) //赠送金币
            {
                //添加账户明细记录
                M('AccountLog')->add([
                    'user_id'    => $userid,
                    'log_time'   => NOW_TIME,
                    'log_type'   => 5,
                    'log_status' => 1,
                    'change_num' => $config['giftCoin'],
                    'total_coin' => $config['giftCoin'],
                    'desc'       => "注册赠送",
                    'platform'   => $platform,
                ]);
            }
        }
    }

    //登陆成功后的相关处理与返回信息
    public function loginData($userid,$lastLoginTime,$point,$mobile,$platform)
    {

        //每天首次登陆赠送积分
        if ($lastLoginTime < strtotime(date('Ymd')))
        {
            //判断用户是否连续签到,不是就连续签到次数为0,签到时间为0（每天登录检查）
            $user = M('FrontUser')->field('login_time, sign_num, sign_time')->where(['id'=>$userid])->find();
            //今天是否签到
            $isSign = D('Mission')->isSign($userid, strtotime(date('Y-m-d 00:00:00', time())), strtotime(date('Y-m-d 23:59:59', time()))) ? 1 : 0;
            if($isSign == 0 && strtotime(date('Ymd', $user['sign_time'])) != strtotime(date('Ymd',  strtotime('-1 day')))){
                M('FrontUser')->where(['id' => $userid])->save(['sign_num' => 0, 'sign_time' => 0]);
            }

            // $changeNum = C('givePoint')['login'];
            // $point += $changeNum;

            // M('PointLog')->add([
            //     'user_id'     => $userid,
            //     'log_time'    => NOW_TIME,
            //     'log_type'    => 11,
            //     'change_num'  => $changeNum,
            //     'total_point' => $point,
            //     'desc'        => '登陆赠送'
            // ]);
            // //发送系统消息通知
            // sendMsg($userid,'积分赠送通知','您好！今日首次登录赠送'.$changeNum.'积分，详情请查看积分明细。');
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
        if ($oldToken = S('userToken:'.$userid))
        {
            S($oldToken,-1);
        }

        //设置新token
        $userToken = md5('user'.$userid.NOW_TIME.mt_rand(10000,99999));
        S('userToken:'.$userid,$userToken,C('loginLifeTime'));

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

    /**
     * 获得邀请码
     * @param $user_id int 注册用户id
     * @return string 邀请码
     */
    public function getInvitationCode($user_id){
        $len = strlen($user_id);
        $num = abs(8-$len);

        $start = $end = '';
        for($i=1;$i<$num;$i++){//计算需要位数
            $start .= '0';
            $end .= '9';
        }

        //QC+8位数
        $code = 'QC'.strval($user_id).mt_rand(intval('1'.$start), intval('9'.$end));
        return $code;
    }


    /**
     * 邀请好友注册过程
     * @param $userid int 注册用户id
     * @param $recommend_id int 推荐人id
     * @return 1
     */
    public function inviteProcess($userid, $recommend_id){
        //查询推荐人的层级关系
        $second_id  = M('FrontUser')->where(['id' => $recommend_id])->getField('recommend_id');
        if($second_id)
        {
            $third_id  = M('FrontUser')->where(['id' => $second_id])->getField('recommend_id');
            if($third_id) {
                //三级关系
                $data['user_id'] = $third_id;
                $data['coin'] = 1;
                $data['first_lv_uid'] = $second_id;
                $data['first_coin'] = 1;
                $data['second_lv_uid'] = $recommend_id;
                $data['second_coin'] = 5;
                $data['third_lv_uid'] = $userid;
                $data['third_coin'] = 5;
                $data['create_time'] = time();
            }else {
                //两级关系
                $data['user_id'] = $second_id;
                $data['coin'] = 1;
                $data['first_lv_uid'] = $recommend_id;
                $data['first_coin'] = 5;
                $data['second_lv_uid'] = $userid;
                $data['second_coin'] = 5;
                $data['third_lv_uid'] = 0;
                $data['third_coin'] = 0;
                $data['create_time'] = time();
            }
        }else{//只有一级关系
            $data['user_id'] = $recommend_id;
            $data['coin'] = 5;
            $data['first_lv_uid'] = $userid;
            $data['first_coin'] = 5;
            $data['second_lv_uid'] = 0;
            $data['second_coin'] = 0;
            $data['third_lv_uid'] = 0;
            $data['third_coin'] = 0;
            $data['create_time'] = time();
        }

        //入库qc_invite_log：记录表
        M('InviteLog')->add($data);

        //入库qc_invite_relation：关系表
        if($third_id)
        {//三级
            $relationData[0]['user_id'] = $data['user_id'];
            $relationData[0]['invited_id'] = $data['third_lv_uid'];
            $relationData[0]['lv'] = 3;

            $relationData[1]['user_id'] = $data['first_lv_uid'];
            $relationData[1]['invited_id'] = $data['third_lv_uid'];
            $relationData[1]['lv'] = 2;

            $relationData[2]['user_id'] = $data['second_lv_uid'];
            $relationData[2]['invited_id'] = $data['third_lv_uid'];
            $relationData[2]['lv'] = 1;
        }else if($second_id)
        {//二级
            $relationData[0]['user_id'] = $data['user_id'];
            $relationData[0]['invited_id'] = $data['second_lv_uid'];
            $relationData[0]['lv'] = 2;

            $relationData[1]['user_id'] = $data['first_lv_uid'];
            $relationData[1]['invited_id'] = $data['second_lv_uid'];
            $relationData[1]['lv'] = 1;
        }else
        {//一级
            $relationData[0]['user_id'] = $data['user_id'];
            $relationData[0]['invited_id'] = $data['first_lv_uid'];
            $relationData[0]['lv'] = 1;
        }

        foreach($relationData as $k => $v){
            $v['create_time'] = time();
            M('InviteRelation')->add($v);
        }

        //入库qc_invite_info：信息表
        if($third_id)
        {
            $infoData[0]['user_id'] = $data['user_id'];
            $infoData[0]['third_num'] = ['exp', 'third_num+1'];
            $infoData[0]['total_num'] = ['exp', 'total_num+1'];
            $infoData[0]['third_coin'] = ['exp', 'third_coin+1'];
            $infoData[0]['total_coin'] = ['exp', 'total_coin+1'];
            $change_num[0] = 1;

            $infoData[1]['user_id'] = $data['first_lv_uid'];
            $infoData[1]['second_num'] = ['exp', 'second_num+1'];
            $infoData[1]['total_num'] = ['exp', 'total_num+1'];
            $infoData[1]['second_coin'] = ['exp', 'second_coin+1'];
            $infoData[1]['total_coin'] = ['exp', 'total_coin+1'];
            $change_num[1] = 1;

            $infoData[2]['user_id'] = $data['second_lv_uid'];
            $infoData[2]['first_num'] = ['exp', 'first_num+1'];
            $infoData[2]['total_num'] = ['exp', 'total_num+1'];
            $infoData[2]['first_coin'] = ['exp', 'first_coin+5'];
            $infoData[2]['total_coin'] = ['exp', 'total_coin+5'];
            $change_num[2] = 5;
        }else if($second_id)
        {
            $infoData[0]['user_id'] = $data['user_id'];
            $infoData[0]['second_num'] = ['exp', 'second_num+1'];
            $infoData[0]['total_num'] = ['exp', 'total_num+1'];
            $infoData[0]['second_coin'] = ['exp', 'second_coin+1'];
            $infoData[0]['total_coin'] = ['exp', 'total_coin+1'];
            $change_num[0] = 1;

            $infoData[1]['user_id'] = $data['first_lv_uid'];
            $infoData[1]['first_num'] = ['exp', 'first_num+1'];
            $infoData[1]['total_num'] = ['exp', 'total_num+1'];
            $infoData[1]['first_coin'] = ['exp', 'first_coin+5'];
            $infoData[1]['total_coin'] = ['exp', 'total_coin+5'];
            $change_num[1] = 5;
        }else{
            $infoData[0]['user_id'] = $data['user_id'];
            $infoData[0]['first_num'] = ['exp', 'first_num+1'];
            $infoData[0]['total_num'] = ['exp', 'total_num+1'];
            $infoData[0]['first_coin'] = ['exp', 'first_coin+5'];
            $infoData[0]['total_coin'] = ['exp', 'total_coin+5'];
            $change_num[0] = 5;
        }

        foreach($infoData as $k => $v){
            $v['update_time'] = NOW_TIME;
            M('InviteInfo')->where(['user_id' => $v['user_id']])->save($v);

            //入库各级用户信息
            M('FrontUser')->where(['id'=>$v['user_id']])->save(['coin' => ['exp', 'coin+'.$change_num[$k]]]);
            $userCion = M('FrontUser')->where(['id'=>$v['user_id']])->getField('coin');
            M('AccountLog')->add([
                'user_id'    => $v['user_id'],
                'log_time'   => NOW_TIME,
                'log_type'   => 13,
                'log_status' => 1,
                'change_num' => $change_num[$k],
                'total_coin' => $userCion,
                'desc'       => "邀请好友/好友邀请",
                'platform'   => $this->userInfo['platform'],
            ]);
        }

        //入库注册人信息
        M('FrontUser')->where(['id'=>$userid])->save(['coin' => ['exp', 'coin+5'], 'recommend_id' => $recommend_id]);
        $userCion = M('FrontUser')->where(['id'=>$userid])->getField('coin');
        M('AccountLog')->add([
            'user_id'    => $userid,
            'log_time'   => NOW_TIME,
            'log_type'   => 13,
            'log_status' => 1,
            'change_num' => 5,
            'total_coin' => $userCion,
            'desc'       => "邀请好友/好友邀请",
            'platform'   => $this->userInfo['platform'],
        ]);

        return 1;
    }

}


 ?>