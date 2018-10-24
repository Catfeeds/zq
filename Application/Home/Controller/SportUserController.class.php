<?php
/**
 * @author longs <longs@qc.com>
 * @Date 2018-2-1
 */


/**
 * 体育号专用入口
 */
class SportUserController extends CommonController
{

    public function _initialize()
    {
        C('HTTP_CACHE_CONTROL', 'no-cache,no-store');
        parent::_initialize();
    }

    /**
     * 第一步:
     * 用户体育号申请前瞻入口
     */
    public function index ()
    {
        $this->assign("status", I('status'));

        // 如果session状态为已登录 那么调到体育号申请页面
        if (is_login()) {
            $rs = is_login();
            $user= M('FrontUser')->field('expert_status,is_expert')->where(['id' => $rs])->find();
            if ($user['expert_status'] == 2 || $user['expert_status'] == 1 || $user['is_expert'] == 1) {
                redirect(U("SportUser/loginSucce")."?".rand(0,9999));
            } else {
                redirect(U("SportUser/sportExpertInfo")."?".rand(0,9999));
            }
        }

        // Post提交数据后服务端验证
        if(IS_POST){

            //预防CSRF攻击
            if(!check_form_token()){
                $this->error("非法操作！");
            }

            //注册防刷
            if(!D('FrontUser')->checkReg(I('deviceID'))) $this->error('该设备注册次数过多!');
            // 图文验证码
            $verify = I('verify');
            if(!checkVerify($verify))$this->error('图文验证码错误或已过时!');
            //用户手机验证
            $nickname = I('nick_name');
            $isNickname = M('frontUser')->where(array('nick_name'=>$nickname))->find();
            if($isNickname){
                $this->error('该用户名已经存在');
            }

            //用户昵称验证
            $length = mb_strlen($nickname, 'utf-8');
            if ($length < 2 || $length > 10) {
                $this->error('用户昵称必须大于2位,小于10位');
            }
            $nickNameTrue = matchFilterWords('nickFilter', $nickname);
            if (!$nickNameTrue) {
                $this->error('您的名字不合法,请重新输入!');
            }

            //手机注册验证码验证
            $isTrue = A("Home/User")->checkMobileVerify(I('captcha'),I('mobile'));
            if(!$isTrue){
                $this->error('验证码错误或已超时');
            }

            $UserArray=array(
                'username'  =>  I('mobile'),
                'nick_name' =>  $nickname,
                'password'  =>  md5(I('password')),
                'reg_time'  =>  time(),
                'reg_ip'    =>  get_client_ip(),
                'platform'  =>  1, //注册的平台
                'channel_code' => 'web',
                'mac_addr'  =>  I('deviceID'),//注册mac地址
            );
            $FrontUserId = M('frontUser')->add($UserArray);
            if($FrontUserId){
                D('FrontUser')->autoLogin($FrontUserId);
                $this->success('注册成功！');
            }else{
                $this->error('注册失败！');
            }
        } else {
            $this->assign('position','欢迎注册');
            $this->display();
        }
    }


    /**
     * 首次欢迎页
     */
    public function preparation()
    {
        if (is_login()) {
            redirect(U("SportUser/index")."?".rand(0,9999));
        }
        $this->display();
    }


    /**
     * 第一步:
     * 用户注册成功 跳转到中转页面 显示自动登录状态的用户
     */
    public function loginSucce()
    {
        $rs = is_login();
        if (!$rs) {
            redirect(U("SportUser/index"));
        }
        $user= M('FrontUser')->field('username, nick_name, head, is_expert, expert_status')->where(['id' => $rs])->find();
        $user['face'] = frontUserFace($user['head']);
        $user['url'] = getLivezillaUrl();
        $this->assign("user", $user);
        $this->display();
    }


    /**
     * 体育号入驻页面
     */
    public function sportExpertInfo()
    {
        //防止未登录用户进入此页面
        if (!is_login()) {
            redirect(U("SportUser/index"));
        }

        //用户id
        $rs = is_login();

        //在页面上需要回显的数据
        $user= M('FrontUser')->field('username, nick_name, head, descript, true_name, identfy')->where(['id' => $rs])->find();
        $user['face'] = frontUserFace($user['head']);
        $this->assign("user", $user);

        //Post提交
        if (IS_POST) {

            //预防CSRF攻击
            if(!check_form_token()){
                $this->error("非法操作！");
            }

            $data['descript']= I("descript");
            $data['true_name'] = I("true_name");
            $data['identfy'] = I("identfy");

            if (!$user['username']) {
                $data['username'] = I('mobile');
            }

            //服务端验证简介
            if (!Think\Tool\Tool::utf8_strlen($data['descript']) > 10 && Think\Tool\Tool::utf8_strlen($data['descript']) < 100)
                $this->error("简介不能少于10 大于 100");
            if (!matchFilterWords("FilterWords", $data['descript']))
                $this->error("简介中包含敏感词汇");

            //服务端验证真实姓名
            if (!$data['true_name'])
                $this->error("真实姓名不能为空");

            //服务端验证身份证号码
            if ($data['identfy']) {
                $isIdentfy = M('FrontUser')->where(['identfy'=>$data['identfy'], 'id' => ['neq', $rs]])->find();
                if ($isIdentfy)
                    $this->error("身份证不可以重复");
            } else {
                $this->error("身份证号码不能为空");
            }

            //服务端验证手机
            $isTrue = A("Home/User")->checkMobileVerify(I('captcha'),I('mobile'));
            if(!$isTrue){
                $this->error('验证码错误或已超时');
            }

            //上传身份证照片到服务端
            if (!empty($_FILES['identfy_pic']['tmp_name'])) {
                $return = D('Uploads')->uploadImg("identfy_pic", "identfy_pic", $rs);
                if ($return['status'] == 1)
                    $data['identfy_pic'] = $return['url'];
            }

            //上传人物头像到服务端
            if (!empty($_FILES["face"]["tmp_name"])) {
                $face = $this->base64EncodeImage($_FILES['face']["tmp_name"]);
                $result = D('Uploads')->uploadFileBase64($face, "user", "face", "200", $rs, "[[200,200,200]]");
                if ($result['status'] == 1)
                    $data['head'] = $result['url'];
                else
                    $this->error("头像上传失败");
            }

            //添加正在审核的状态
            $data['expert_status'] = 2;
            $data['expert_register_time'] = time();
            $res = M('FrontUser')->where(['id'=>$rs])->save($data);
            if($res === false)
                $this->error("提交失败, 请重新尝试");
            $this->success("提交成功，审核通过将会以站内消息通知您", U("UserInfo/index"), 3);
        } else {
            $this->assign('position','欢迎注册');
            $this->display();
        }
    }


    /**
     * 图片转换Base64数据流
     * @param $image_file 图片文件
     * @return string
     */
    function base64EncodeImage ($image_file)
    {
        $base64_image = '';
        $image_info = getimagesize($image_file);
        $image_data = fread(fopen($image_file, 'r'), filesize($image_file));
        $base64_image = 'data:' . $image_info['mime'] . ';base64,' . chunk_split(base64_encode($image_data));
        return $base64_image;
    }


    public function checkVerify()
    {
        $isTrue = A("Home/User")->checkMobileVerify(I('captcha'),I('mobile'));
        if(!$isTrue){
            $this->error('验证码错误或已超时');
        }
    }


    /**
     * 入驻体育号使用手册
     */
    public function mediaManual()
    {
        $this->display();
    }


    public function callback()
    {
        A("Home/User")->callback(I('type'), I("code"));
    }


}