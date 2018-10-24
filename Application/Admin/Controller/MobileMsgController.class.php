<?php

/**
 * 短信推送记录
 *
 * @author liuweitao <cytusc@foxmail.com>
 *
 * @since
 */
use Think\Controller;

class MobileMsgController extends CommonController
{
    //推送内容全局变量
    public $content = '';
    //平台变量,0:全平台 2:IOS 3:安卓
    public $platform = '';
    //消息打开方式  0:进入App;1进入资讯；2进入图集；9打开外链；10进入个人中心；11进入足球赛事详情，12进入篮球赛事详情，13进入帖子详情；14进入个人系统通知。15进入产品详情
    public $module = '';
    //指定参数值
    public $module_value = '';

    public function index()
    {
        //生成查询条件
        $map = $this->_search("MobileMsg");
        //名字查询
        $name = trim(I('name'));
        if (!empty($name)) {
            $where['name'] = ['Like', $name . '%'];
            $where['nick_name'] = ['Like', $name . '%'];
            $where['_logic'] = 'OR';
            $map['_complex'] = $where;
        }
        //手机号查询
        $mobile = trim(I('mobile'));
        if (!empty($mobile)) {
            $map['mobile'] = ['Like', $mobile . '%'];
        }
        $list = $this->_list(D('MobileMsg'), $map, 'id');
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 弹窗查找（手机号码）
     * return #
     */
    public function mobileLog()
    {
        $map = $this->_search("MobileLog");
        //多昵称搜索
        $more_name = trim(I('more_name'), ",");
        $more_name = implode("|", explode(',', $more_name));
        if (!empty($more_name)) {
            $map['name'] = ['exp', "regexp '{$more_name}'"];
        }
        //获取列表
        $list = $this->_list(CM('MobileLog'), $map, '', false, '', '*', false);
        $Multiselect = I('Multiselect') ?: 1;
        $this->assign('Multiselect', $Multiselect);
        $this->assign('list', $list);
        $tp = "findMobileLogDialog";
        $this->display($tp);
    }

    /**
     * 对数据进行保存
     *
     */
    public function save()
    {
        ini_set('memory_limit', '250M'); //内存限制
        set_time_limit(150); //修改服务器响应时间
        $is_send = $_POST['is_send'];
        //对数据进行处理,包括发送消息
        $res = $this->data_proces($_POST);
        //判断发送安卓的服务器ip是否可用
        if ($res['ip']) {
            $ip = $res['ip'];
            unset($res['ip']);
        }
        if ($is_send == 1) {
            $str = '消息正在发送';
            if ($ip) {
                $str .= ",安卓推送失败，请将IP：$ip 加入白名单";
            } elseif ($ip == -1) {
                $str .= ",安卓发送失败";
            }
        } elseif ($is_send == 2) {
            $str = '定时任务已添加';
        }
        $rs = M('MobileMsg')->addAll($res);
        if ($rs) $this->success('添加成功，' . $str . '!');
        $this->error('添加失败!');
    }

    /**
     * 添加手机号
     *
     */
    public function add_phone()
    {
        $this->display();
    }

    /**
     * 对手机号进行保存
     *
     */
    public function save_phone()
    {
        $mobile = I('mobile');
        $res = M('MobileLog')->where(['mobile'=>$mobile])->select();
        if($res) $this->error('手机号已存在!');
        $data = $_POST;
        $data['add_time'] = time();
        $rs = M('MobileLog')->add($data);
        if ($rs) $this->success('添加成功!');
        $this->error('添加失败!');
    }

    /**
     * 对数据进行处理
     */
    public function data_proces($arr)
    {
        //定义发送内容成员属性
        $this->content = $arr['content'];
        $data = array();
        $num = 0;
        if ($arr['is_send'] == 2) {
            $arr['send_time'] = strtotime($arr['send_time']);
            if ($arr['send_time'] <= time()) {
                $this->error('定时时间需要大于当前时间!');
            }
        }
        //短信发送数据处理
        if ($arr['MobileLog_id']) {
            $map['id'] = array('in', $arr['MobileLog_id']);
            //获取手机号
            $mobile_res = M('MobileLog')->where($map)->getField('id,mobile', true);
            foreach ($mobile_res as $key => $val) {
                $data[$num]['mobile_id'] = $key;
                $data[$num]['content'] = $this->content;
                $data[$num]['user_id'] = '';
                $data[$num]['send_type'] = 1;
                $data[$num]['platform'] = '';
                $data[$num]['module'] = '';
                $data[$num]['module_value'] = '';
                //判断是否为定时发送
                if ($arr['is_send'] == 1) {
                    $data[$num]['is_send'] = 1;
                    $data[$num]['send_time'] = time();
                    //调用接口发送短信
                    $sms_res = sendingSMS($val, $this->content);
                    if ($sms_res > 0) {
                        $data[$num]['state'] = 1;
                    } else {
                        $data[$num]['state'] = 0;
                    }
                } elseif ($arr['is_send'] == 2) {
                    $data[$num]['is_send'] = 0;
                    $data[$num]['state'] = 0;
                    $data[$num]['send_time'] = $arr['send_time'];
                }
                $num++;
            }
        }

        //推送发送数据处理
        if ($arr['FrontUser_id']) {
            $this->platform = $arr['platform'];
            $this->module = $arr['module'];
            $this->module_value = $arr['mValue'];
            $push_res = $this->_push($arr['FrontUser_id']);//执行推送
            $front_user_id = explode(',', $arr['FrontUser_id']);
            foreach ($front_user_id as $val) {
                $data[$num]['mobile_id'] = '';
                $data[$num]['content'] = $this->content;
                $data[$num]['user_id'] = $val;
                $data[$num]['send_type'] = 2;
                $data[$num]['platform'] = $this->platform;
                $data[$num]['module'] = $this->module;
                $data[$num]['module_value'] = $this->module_value;
                //判断是否为定时发送
                if ($arr['is_send'] == 1) {
                    $data[$num]['is_send'] = 1;
                    $data[$num]['send_time'] = time();
                    $data[$num]['state'] = $push_res[$val] ? $push_res[$val] : 0;//判断推送返回值,主要用于安卓返回ip时无数据
                } elseif ($arr['is_send'] == 2) {
                    $data[$num]['is_send'] = 0;
                    $data[$num]['state'] = 0;
                    $data[$num]['send_time'] = $arr['send_time'];
                }
                $num++;
            }
            if ($push_res[0]) $data['ip'] = $push_res[0];
        }
        return $data;
    }

    /**
     * 推送功能
     */
    public function _push($user_arr)
    {
        $android_user = array();
        $ios_user = array();
        $map['id'] = array('in', $user_arr);
        $map['platform'] = array(2, 3, 'or');
        $res = M('FrontUser')->where($map)->getField('id,platform', true);
        //对ios平台与安卓平台用户进行分类处理
        foreach ($res as $key => $val) {
            if ($val == 2) {
                $ios_user[] = $key;
            } elseif ($val == 3) {
                $android_user[] = $key;
            }
        }
        //对ios平台与安卓平台的返回值进行合并
        if ($this->platform == 0 || $this->platform == 3) $android = $this->android_push($android_user);
        $android_res = $android_user ? $android : $android_user;
        if ($this->platform == 0 || $this->platform == 2) $ios = $this->ios_push($ios_user);
        $ios_res = $ios_user ? $ios : $ios_user;
        $push_res = $android_res + $ios_res;
        return $push_res;

    }

    /**
     * 安卓推送
     */
    public function android_push($android_user)
    {
        $push_res = array();
        foreach ($android_user as $val) {
            //用for循环对推送请求进行错误处理
            for ($i = 1; $i <= 3; $i++) {
                $error_push = $this->android_push_on($val);
                //获取错误返回值的ip
                if ($error_push['ip']) {
                    $error[0] = $error_push['ip'];
                    return $error;
                }
                if ($error_push['ret'] == 'SUCCESS') {
                    $push_res[$val] = 1;
                    break;
                } elseif ($i == 3) {
                    $push_res[$val] = 0;
                    break;
                }
            }
        }
        return $push_res;
    }

    /**
     * 执行安卓推送
     */
    public function android_push_on($user_id)
    {
        //发送推送请求
        import('Vendor.umeng.Umeng');
        $config = C('umeng');
        $Umeng = new Umeng($config['AppKey'], $config['AppMasterSecret']);
        $options = [
            'ticker' => $this->content,
            'title' => $this->content,
            'text' => $this->content,
            'alias' => $user_id,
            'alias_type' => 'QQTY',
            'after_open' => 'go_custom',
            'custom' => json_encode(['um_module' => ['module' => $this->module, 'value' => $this->module_value, 'alias_type' => 'QQTY', 'alias' => $user_id]]),
            'production_mode' => 'true'
        ];
        $res = $Umeng->sendAndroidCustomizedcast($options);
        $rs = json_decode($res, true);
        //判断错误返回值内是否包含ip
        if (!$rs) {
            $error = explode('details:', $res);
            $error_arr = json_decode($error[1], true);
            $error_res['ip'] = $error_arr['data']['ip'] ? $error_arr['data']['ip'] : -1;
            return $error_res;
        }
        return $rs;
    }

    /**
     * ios推送
     */
    public function ios_push($user_res)
    {
        $push_res = array();
        $map['user_id'] = array('in', implode(',', $user_res));
        $device_token = M('ApnsUsers')->where($map)->getField('user_id,device_token', true);//获取需要推送的用户设备号
        import('Vendor.apns.ApnsPush');
        $apns = new ApnsPush(C('apns_env'), 'qqty888');
        $apns->connect();
        //推送消息体
        $payload = ['aps' => [
            'alert' => ["body" => $this->content]],
            'e' => [
                'em_module' => ['module' => $this->module, 'value' => $this->module_value, 'url' => $this->module],
                'show_type' => 1,
                'msg_id' => I('msg_id') ? I('msg_id') : 1
            ]

        ];
        $apns->setBody($payload);
        foreach ($device_token as $key => $val) {
            //for循环对错误进行处理
            for ($i = 1; $i <= 3; $i++) {
                $res = $apns->send($val, 1);
                $err = $apns->readErrMsg();
                if ($res == true && !is_array($err)) {
                    $push_res[$key] = 1;
                    break;
                } elseif (is_array($err) && $i == 3) {
                    $push_res[$key] = 0;
                    break;
                }
            }
        }
        $apns->close();
        return $push_res;
    }

}