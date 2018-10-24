<?php
/**
 * 前台用户类
 * 2018.1.9
 */

use Think\Model;
use Think\Tool\Tool;

class FrontUserModel extends Model
{
    /**
     * 用户登陆 自动登录
     * @param $param array
     * @return array
     */
    public function login($param)
    {
        //判断IP黑名单

        if(checkShieldIp()) return 401;

        $user = array();
        $field = ['id', 'nick_name', 'head', 'status', 'login_time', 'point', 'sign_num', 'sign_time'];
        if ($param['mobile'] && $param['password']) {//普通登录
            $area_code = $param['area_code'] ? '00'.$param['area_code'] : '';
            $user = M('FrontUser')->field($field)->where(['username' => $param['mobile'], ['password' => md5($param['password'])], 'area_code' => $area_code])->find();
        }

        if (!$user) return 1009;

        if ($user['status'] != 1) return 1005;

        $msgCode = $user['nick_name'] ? '' : 1010;
        $loginData = $this->loginData($user['id'], $param);

        return ['loginData' => $loginData, 'msgCode' => $msgCode];
    }

    /**
     * 注册用户
     * @param $param array
     * @return array
     */
    public function register($param)
    {
        //判断IP黑名单
        if(checkShieldIp()) return 401;


        if (M('FrontUser')->where(array('username' => $param['mobile']))->find()) return 1003;

        if (!Tool::checkPassword($param['password'])) return 1006;

        if ($param['smsCode'] == null || S(md5(C('smsPrefix') . $param['mobile']))['rank'] != $param['smsCode']) return 1007;

        if (!$ip = $this->checkReg()) return 1088;

        if($param['platform'] != 2){
            if(isset($param['verify_code'])){
                if (!$this->checkVerifyCode($param['deviceID'], $param['verify_code'])) return 1087;
            }
        }

        $area_code = $param['area_code'] ? (string)$param['area_code'] : '';
        if(!in_array($area_code, array('', '852', '853', '886'))) return 101;

        $data = [
            'username'     => $param['mobile'],
            'password'     => md5($param['password']),
            'reg_time'     => NOW_TIME,
            'reg_ip'       => $ip,
            'platform'     => $param['platform'],
            'channel_code' => $param['channel_code'],
            'area_code'    => $area_code ? '00'.$area_code : '',
            'mac_addr'     => $param['deviceID'],//注册设备号
        ];

        $userid = M('FrontUser')->add($data);
        if (!$userid) return 1008;

        //绑定第三方帐号
//        $this->bindSdkUser($userid);

        //增加注册赠送金币
        $this->loginGift($userid, $param['platform']);

        $loginData = $this->loginData($userid, $param);

        //注册赠送大礼包，活动时间内
        $this->giftBag($userid, $param['platform'], 1, 3);

        return $loginData;
    }

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
            'status',
            'username',
            'password',
            'nick_name',
            'lv',
            'lv_bk',
            'lv_bet',
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
            'alipay_id',
            'is_expert',
            'expert_status',
            'reason',
            'sign_num',
            'sign_time',
        ];

        $userInfo = M('FrontUser')->master(true)->field($field)->where(['id'=>$userid])->find();
        if(!$userInfo) return '';

        $userInfo['fansNum'] = M('FollowUser')->where(['follow_id'=>$userid])->count();
        $userInfo['face']    = frontUserFace($userInfo['head']);
        $userInfo['alipay_id'] = (string) $userInfo['alipay_id'];
	
        // 模型预测 是否付费
	    $predictivePayUser = M("predictivePayUser")->where(['user_id' => $userid])->field('user_id, paid_start_time, paid_end_time')->find();
	    if (!empty($predictivePayUser) && ($predictivePayUser['paid_start_time'] < time() && $predictivePayUser['paid_end_time'] > time())) {
		    $predictivePayStatus = 1;
	    } else {
		    $predictivePayStatus = 0;
	    }
	    $userInfo['perdictivePayStatus'] = $predictivePayStatus;
        
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

    /**
     * 登陆成功后的相关处理与返回信息
     * @param $userid
     * @param $lastLoginTime
     * @param $mobile
     * @param $platform
     * @param $deviceID
     * @return array
     */
    public function loginData($userid, $param)
    {
        $userInfo      = $this->getUserInfo($userid); //用户信息
        $userid        = $userInfo['id'];         //用户id
        $lastLoginTime = $userInfo['login_time']; //最后登录时间
        $mobile        = $param['mobile'];    //用户手机号
        $platform      = $param['platform'];  //登录平台
        $deviceID      = $param['deviceID'];  //登录设备号
        //每天首次登陆赠送积分
        if ($lastLoginTime < strtotime(date('Ymd')))
        {
            //判断用户是否连续签到,不是就连续签到次数为0,签到时间为0（每天登录检查）
            //今天是否签到
            $isSign = D('Mission')->isSign($userid, strtotime(date('Y-m-d 00:00:00', time())), strtotime(date('Y-m-d 23:59:59', time()))) ? 1 : 0;
            if($isSign == 0 && strtotime(date('Ymd', $userInfo['sign_time'])) != strtotime(date('Ymd',  strtotime('-1 day')))){
                $saveUser['sign_num']  = 0;
                $saveUser['sign_time'] = 0;
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

        //判断一个小时内重复不算登录
        if(NOW_TIME - $lastLoginTime >= 60*60) {
            //更新用户信息
            $saveUser['login_count'] = ['exp', 'login_count+1'];
            $saveUser['login_time']  = NOW_TIME;
            $userInfo['login_time']  = NOW_TIME;

            //用密码登录或切换登录时候检查是否达到邀请好友的有效条件
            $res = M('InviteLoginInfo')->where(['user_id' => $userid])->find();
            if (!$res)//没有入库的才入库
                $this->checkLoginData($userid);

            //判断邀请注册时获得金币是否已给，有邀请金币且没有给的才进入方法
            $getInfo = M('InviteInfo')->field('register_coin, is_get')->where(['user_id' => $userid])->find();
            if($getInfo['is_get'] == 0 && $getInfo['register_coin'] > 0)
                $this->checkRegisterCoin($userid);
        }
        $saveUser['last_ip']         = get_client_ip();
        $saveUser['last_login_ver']  = MODULE_NAME;
        if($deviceID){
            $saveUser['device_token']    = $deviceID;
        }
        
        M('FrontUser')->where(['id' => $userid])->save($saveUser);

        //标识旧token被其他客户端登陆
        if ($oldToken = S('userToken:'.$userid)) {
            //在别的地方登录就通知旧的客户端
            $oldDeviceID = S('deviceID:'.$userid);
            if($oldDeviceID != $deviceID){
                S($oldDeviceID, -1);
                $opt['clientid'] = $userid.rand(0, 1000);
                $opt['topic'] = 'qqty/api500/'.$userid.'_'.$oldDeviceID.'/off_line';
                $opt['payload'] = ['status' => 1, 'msg' => C('errorCode')[1051], 'data' => ['offLine' => 1]];
                $opt['qos'] = 1;
                Mqtt($opt);
            }
            S($oldToken, -1);
        }

        //设置新token
        $userToken = md5('user'.$userid.NOW_TIME.mt_rand(10000,99999));
        S('userToken:'.$userid, $userToken, C('loginLifeTime'));
        S('deviceID:'.$userid, $deviceID, C('loginLifeTime'));//缓存设备号

        $data = [
            'userid'   => $userid,
            'username' => $mobile,
            'user_nick'=> $userInfo['nick_name'],
            'platform' => $platform,
            'password' => $userInfo['password']
        ];

        S($userToken,$data,C('loginLifeTime'));

        $loginJwtToken = D("Jwt")->getJwt($userid);

        $data = [
            'userToken' => $userToken,
            'jwtToken'  => $loginJwtToken,
            'userInfo'  => $userInfo,
            'givePoint' => isset($changeNum) ? $changeNum : 0
        ];

        if($deviceID){
            $redis = connRedis();
            $setData = ['userid' => $userid,'nick_name' => $userInfo['nick_name'],'head' => $userInfo['face'],'userToken' =>$userToken];
            $redis->hmset('ws_' . $deviceID, $setData);
            $redis->expire('ws_' . $deviceID, 3600 * 24);
        }
        unset($data['password']);

        return $data;
    }

    /**
     * 判断用户是否达到邀请好友登录的有效条件
     */
    public function checkLoginData($userid){
        $info = M('FrontUser')->field('login_time, login_count, reg_time')->where(['id' => $userid])->find();

        //20161101之前注册不需要考核
        if($info['reg_time'] < strtotime('2016-11-01 00:00:00'))
            return false;

        //没有邀请人不需要考核
        $recommend_id = M('FrontUser')->where(['id' => $userid])->getField('recommend_id');
        if(!$recommend_id)
            return false;

        $inviteConfig = getWebConfig('invite');
        //30天是否达到30次或以上；小于30天，且未到30次则待考核；大于30天都是无效（没有到31天都是算30天那天）
        if((NOW_TIME - $info['reg_time']) <= $inviteConfig['login_days']*3600*24){
            if($info['login_count'] >= $inviteConfig['login_times']){
                $data['type'] = 1;//有效
            }
        }else{
            $data['type'] = 2;//无效
        }

        if(isset($data['type'])){
            $data['user_id']       = $userid;
            $data['register_time'] = $info['reg_time'];
            $data['login_time']    = $info['login_time'];
            $data['login_num']     = $info['login_count'];
            $data['create_time']   = NOW_TIME;

            $rs = M('InviteLoginInfo')->add($data);

            if($rs === false)
                return false;
        }

        return true;
    }

    /**
     * 判断邀请注册时获得金币是否已给
     */
    public function checkRegisterCoin($userid){
        //20161101之前注册不需要考核
        $reg_time = M('FrontUser')->where(['id' => $userid])->getField('reg_time');
        if($reg_time < strtotime('2016-11-01 00:00:00'))
            return false;

        $getInfo = M('InviteInfo')->field('register_coin, is_get')->where(['user_id' => $userid])->find();

        //已给或者没有邀请金币的过滤
        if($getInfo['is_get'] == 1 || $getInfo['register_coin'] == 0)
            return false;

        $info = M('FrontUser')->field('login_time, login_count')->where(['id' => $userid])->find();
        $inviteConfig = getWebConfig('invite');

        //自己的金币达到条件直接提出，不限时间（受邀方）
        if($info['login_count'] >= $inviteConfig['login_times']){
            //没有给的用户就给
            if($getInfo['is_get'] == 0 && $getInfo['register_coin'] > 0) {
                try{
                    M()->startTrans();

                    $getTotalCion = M('FrontUser')->where(['id' => $userid])->getField('(coin+unable_coin) as total');
                    $res1 = M('FrontUser')->where(['id' => $userid])->save(['coin' => ['exp', 'coin+' . $getInfo['register_coin']]]);
                    if($res1) {
                        $res2 = M('AccountLog')->add([
                            'user_id'    => $userid,
                            'log_time'   => NOW_TIME,
                            'log_type'   => 13,
                            'log_status' => 1,
                            'change_num' => $getInfo['register_coin'],
                            'total_coin' => $getTotalCion + $getInfo['register_coin'],
                            'desc'       => "邀请好友",
                            'platform'   => 1,
                        ]);

                        $getData['valid_coin'] = ['exp', 'valid_coin+' . $getInfo['register_coin']];
                        $getData['await_coin'] = ['exp', 'await_coin-' . $getInfo['register_coin']];
                        $getData['is_get']     = 1;
                        $res3 = M('InviteInfo')->where(['user_id' => $userid])->save($getData);

                        if($res2 === false || $res3 === false){
                            throw new Exception();
                        }
                    }else{
                        throw new Exception();
                    }

                    M()->commit();
                    return true;
                }catch(Exception $e) {
                    M()->rollback();
                    return false;
                }
            }
        }
    }

    /**
     * 注册或者活动赠送大礼包
     */
    public function giftBag($userid, $platform, $type, $get_type, $gift_id=0){
        //检验是否在活动时间内
        if($type == 3)
            $where['id'] = $gift_id;

        $where['type']       = $type;
        $where['start_time'] = ['lt', NOW_TIME];
        $where['end_time']   = ['gt', NOW_TIME];
        $where['status']     = 1;

        if(M('GiftsConf')->where($where)->count() == 0) return false;

        //不能重复领取
        if($gift_id){
            if(M('TicketLog')->master(true)->where(['gift_id' => $gift_id, 'user_id' => $userid])->count())
                return false;
        }

        unset($where['id']);

        $res = M('GiftsConf')->field('id, game_ticket, coin_ticket, over_time, over_day, start_time, end_time, remark ')->where($where)->order(' id desc ')->find();

        //推荐和充值体验券
        $data = $cdata = [];
        $game = json_decode($res['game_ticket'], true);

        // 3:注册赠送  4:活动赠送
        $msg1 = $get_type == 3 ? '推荐体验券-注册赠送' : '推荐体验券-活动赠送';
        foreach($game as $k => $v){
            for($i=0; $i < $v; $i++){
                $data[$i]['name']        = $k.C('giftPrice');
                $data[$i]['user_id']     = $userid;
                $data[$i]['type']        = 1;
                $data[$i]['price']       = $k;
                $data[$i]['get_time']    = NOW_TIME;
                $data[$i]['over_time']   = NOW_TIME + $res['over_day']*3600*24;
                $data[$i]['plat_form']   = $platform;
                $data[$i]['get_type']    = $get_type;
                $data[$i]['remark']      = $msg1;
                $data[$i]['gift_id']     = $res['id'];
            }

            M('TicketLog')->addAll($data);
            unset($data);
        }

        $coin = json_decode($res['coin_ticket'], true);
        $msg2 = $get_type == 3 ? '充值体验券-注册赠送' : '充值体验券-活动赠送';
        foreach($coin as $k => $v){
            $kk = explode('_', $k);
            for($i=0; $i < $v; $i++){
                $cdata[$i]['name']        = '满'.$kk[0].'送'.$kk[1].'金币';
                $cdata[$i]['user_id']     = $userid;
                $cdata[$i]['type']        = 2;
                $cdata[$i]['price']       = $kk[0];
                $cdata[$i]['give_coin']   = $kk[1];
                $cdata[$i]['get_time']    = NOW_TIME;
                $cdata[$i]['over_time']   = NOW_TIME + $res['over_day']*3600*24;
                $cdata[$i]['plat_form']   = $platform;
                $cdata[$i]['get_type']    = $get_type;
                $cdata[$i]['remark']      = $msg2;
                $cdata[$i]['gift_id']     = $res['id'];
            }

            M('TicketLog')->addAll($cdata);
            unset($cdata);
        }

        return true;
    }

    /**
     * 获得邀请码
     * @param $user_id int 注册用户id
     * @return string 邀请码
     */
    public function getInvitationCode($user_id){
        $letter = [
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
            'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'
        ];

        //两个大写字母+6位随机数
        $code = $letter[array_rand($letter)].$letter[array_rand($letter)].mt_rand(100000, 999999);

        //判断有无重复，有则重新调用方法获取邀请码,保证唯一
        if(M('FrontUser')->where(['invitation_code' => $code])->count()){
            $code = $this->getInvitationCode($user_id);
        }

        return $code;
    }

    /**
     * 邀请好友注册过程
     * @param $userid int 注册用户id
     * @param $recommend_id int 推荐人id
     * $param $inviteConf array 配置信息
     * @return int
     */
    public function inviteProcess($userid, $recommend_id, $inviteConf){
        try{
            M()->startTrans();

            $third_id = 0;
            //查询推荐人的层级关系
            $second_id  = M('FrontUser')->where(['id' => $recommend_id])->getField('recommend_id');
            if($second_id)
            {
                $third_id  = M('FrontUser')->where(['id' => $second_id])->getField('recommend_id');
                if($third_id) {
                    //三级关系
                    $data['user_id']       = $third_id;
                    $data['coin']          = $inviteConf[3]['top'];
                    $data['first_lv_uid']  = $second_id;
                    $data['first_coin']    = $inviteConf[3]['first'];
                    $data['second_lv_uid'] = $recommend_id;
                    $data['second_coin']   = $inviteConf[3]['second'];
                    $data['third_lv_uid']  = $userid;
                    $data['third_coin']    = $inviteConf[3]['third'];
                    $data['create_time']   = NOW_TIME;
                }else {
                    //两级关系
                    $data['user_id']       = $second_id;
                    $data['coin']          = $inviteConf[2]['top'];
                    $data['first_lv_uid']  = $recommend_id;
                    $data['first_coin']    = $inviteConf[2]['first'];
                    $data['second_lv_uid'] = $userid;
                    $data['second_coin']   = (int)$inviteConf[2]['second'];
                    $data['third_lv_uid']  = 0;
                    $data['third_coin']    = (int)$inviteConf[2]['third'];
                    $data['create_time']   = NOW_TIME;
                }
            }else{//只有一级关系
                $data['user_id']       = $recommend_id;
                $data['coin']          = $inviteConf[1]['top'];
                $data['first_lv_uid']  = $userid;
                $data['first_coin']    = $inviteConf[1]['first'];
                $data['second_lv_uid'] = 0;
                $data['second_coin']   = (int)$inviteConf[1]['second'];
                $data['third_lv_uid']  = 0;
                $data['third_coin']    = (int)$inviteConf[1]['third'];
                $data['create_time']   = NOW_TIME;
            }

            //入库qc_invite_log：记录表
            $res1 = M('InviteLog')->add($data);
            if($res1 === false){
                throw new Exception();
            }

            //入库qc_invite_relation：关系表
            if($third_id)
            {//三级
                $relationData[0]['user_id']    = $data['user_id'];
                $relationData[0]['invited_id'] = $data['third_lv_uid'];
                $relationData[0]['lv']         = 3;

                $relationData[1]['user_id']    = $data['first_lv_uid'];
                $relationData[1]['invited_id'] = $data['third_lv_uid'];
                $relationData[1]['lv']         = 2;

                $relationData[2]['user_id']    = $data['second_lv_uid'];
                $relationData[2]['invited_id'] = $data['third_lv_uid'];
                $relationData[2]['lv']         = 1;
            }else if($second_id)
            {//二级
                $relationData[0]['user_id']    = $data['user_id'];
                $relationData[0]['invited_id'] = $data['second_lv_uid'];
                $relationData[0]['lv']         = 2;

                $relationData[1]['user_id']    = $data['first_lv_uid'];
                $relationData[1]['invited_id'] = $data['second_lv_uid'];
                $relationData[1]['lv']         = 1;
            }else
            {//一级
                $relationData[0]['user_id']    = $data['user_id'];
                $relationData[0]['invited_id'] = $data['first_lv_uid'];
                $relationData[0]['lv']         = 1;
            }

            foreach($relationData as $k => $v){
                $relationData[$k]['create_time'] = NOW_TIME;
            }

            $res2 = M('InviteRelation')->addAll($relationData);
            if($res2 === false){
                throw new Exception();
            }

            //入库qc_invite_info：信息表
            if($third_id)
            {
                $infoData[0]['user_id']    = $data['user_id'];
                $infoData[0]['third_num']  = ['exp', 'third_num+1'];
                $infoData[0]['total_num']  = ['exp', 'total_num+1'];
                $infoData[0]['third_coin'] = ['exp', 'third_coin+'.$inviteConf[3]['top']];
                $infoData[0]['total_coin'] = ['exp', 'total_coin+'.$inviteConf[3]['top']];
                $infoData[0]['await_coin'] = ['exp', 'await_coin+'.$inviteConf[3]['top']];

                $infoData[1]['user_id']     = $data['first_lv_uid'];
                $infoData[1]['second_num']  = ['exp', 'second_num+1'];
                $infoData[1]['total_num']   = ['exp', 'total_num+1'];
                $infoData[1]['second_coin'] = ['exp', 'second_coin+'.$inviteConf[3]['first']];
                $infoData[1]['total_coin']  = ['exp', 'total_coin+'.$inviteConf[3]['first']];
                $infoData[1]['await_coin']  = ['exp', 'await_coin+'.$inviteConf[3]['first']];

                $infoData[2]['user_id']    = $data['second_lv_uid'];
                $infoData[2]['first_num']  = ['exp', 'first_num+1'];
                $infoData[2]['total_num']  = ['exp', 'total_num+1'];
                $infoData[2]['first_coin'] = ['exp', 'first_coin+'.$inviteConf[3]['second']];
                $infoData[2]['total_coin'] = ['exp', 'total_coin+'.$inviteConf[3]['second']];
                $infoData[2]['await_coin'] = ['exp', 'await_coin+'.$inviteConf[3]['second']];
            }else if($second_id)
            {
                $infoData[0]['user_id']     = $data['user_id'];
                $infoData[0]['second_num']  = ['exp', 'second_num+1'];
                $infoData[0]['total_num']   = ['exp', 'total_num+1'];
                $infoData[0]['second_coin'] = ['exp', 'second_coin+'.$inviteConf[2]['top']];
                $infoData[0]['total_coin']  = ['exp', 'total_coin+'.$inviteConf[2]['top']];
                $infoData[0]['await_coin']  = ['exp', 'await_coin+'.$inviteConf[2]['top']];

                $infoData[1]['user_id']    = $data['first_lv_uid'];
                $infoData[1]['first_num']  = ['exp', 'first_num+1'];
                $infoData[1]['total_num']  = ['exp', 'total_num+1'];
                $infoData[1]['first_coin'] = ['exp', 'first_coin+'.$inviteConf[2]['first']];
                $infoData[1]['total_coin'] = ['exp', 'total_coin+'.$inviteConf[2]['first']];
                $infoData[1]['await_coin'] = ['exp', 'await_coin+'.$inviteConf[2]['first']];
            }else{
                $infoData[0]['user_id']    = $data['user_id'];
                $infoData[0]['first_num']  = ['exp', 'first_num+1'];
                $infoData[0]['total_num']  = ['exp', 'total_num+1'];
                $infoData[0]['first_coin'] = ['exp', 'first_coin+'.$inviteConf[1]['top']];
                $infoData[0]['total_coin'] = ['exp', 'total_coin+'.$inviteConf[1]['top']];
                $infoData[0]['await_coin'] = ['exp', 'await_coin+'.$inviteConf[1]['top']];
            }

            //各级用户信息入库，金币不放入自己的账户
            foreach($infoData as $k => $v){
                $v['update_time'] = NOW_TIME;
                $res3 = M('InviteInfo')->where(['user_id' => $v['user_id']])->save($v);

                if($res3 === false){
                    throw new Exception();
                }
            }

            //注册人信息入库，获得金币先存起来
            $r = M('InviteInfo')->where(['user_id' => $userid])->find();//先入库，以便后来邀请的人使用
            if($r){//有记录则更新
                $res6 = M('InviteInfo')->where(['user_id' => $userid])->save(['total_coin' => ['exp', 'total_coin+'.$inviteConf[0]], 'await_coin' => ['exp', 'await_coin+'.$inviteConf[0]], 'register_coin' => ['exp', 'register_coin+'.$inviteConf[0]]]);
            }else{//没记录则新建
                $res6 = M('InviteInfo')->add(['user_id' => $userid, 'create_time' => NOW_TIME, 'total_coin' => ['exp', 'total_coin+'.$inviteConf[0]], 'await_coin' => ['exp', 'await_coin+'.$inviteConf[0]], 'register_coin' => ['exp', 'register_coin+'.$inviteConf[0]]]);
            }

            $res7 = M('FrontUser')->where(['id'=>$userid])->save(['recommend_id' => $recommend_id]);

            if($res6 === false || $res7 === false){
                throw new Exception();
            }

            M()->commit();
            return 1;
        }catch(Exception $e) {
            M()->rollback();
            return 2;
        }
    }

    /*
     * 检验文字验证码
     */
    public function checkVerifyCode($deviceID, $verify_code){
        if(!$deviceID || !$verify_code){
            return false;
        }
        $seKey = 'ThinkPHP.CN';
        $redis = connRedis();
        $value = $redis->get($deviceID);
        if(!$value){
            return false;
        }
        $str = strtoupper($verify_code);
        $key = substr(md5($seKey), 5, 8);
        $str = substr(md5($str), 8, 10);
        $md5_value = md5($key . $str);
        if($value == $md5_value){
            return true;
        }else{
            return false;
        }
    }


    /**
     * @param $clientId     接口返回的clientId
     * @param $userId       用户Id
     */
    public function saveClientId($clientId,$userId)
    {
        $saveData = [];
        $is_save = 0;
        if($userId)
        {
            $data = M('EasemobClient')->where(['user_id'=>$userId])->find();
            if($data)
            {
                M('EasemobClient')->where(['user_id'=>$userId])->save(['client_id'=>$clientId]);
                $is_save = 1;
            }else{
                $saveData = [
                    'user_id'   =>  $userId,
                    'platform'  =>  I('platform'),
                    'client_id' =>  $clientId,
                    'add_time'  => time()
                ];
            }
        }else{
            $saveData = [
                'platform'  =>  I('platform'),
                'client_id' =>  $clientId,
                'add_time'  => time()
            ];
        }
        if(!$is_save) $res =  M('EasemobClient')->add($saveData);

    }
}


 ?>