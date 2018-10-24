<?php
/** SEO
 *
 * Created by PhpStorm.
 * User: liangzk <liangzk@qc.com>
 * Date: 2016/8/31
 * Time: 11:13
 */
use Think\Tool\Tool;
class SeoController extends CommonController
{
    public function index()
    {
        //获取推荐高手
        $killer = D('Common')->getRankingData(1,1,null,false,60);
        foreach ($killer as $k => $v) {
            $WinNum = D('Common')->getWinNum($v['user_id']);
            if($WinNum['win'] < 6){
                unset($killer[$k]);
            }else{
                //近十中几
                $killer[$k]['tenNum'] = $WinNum['num'];
                $killer[$k]['WinNum'] = $WinNum['win'];
                //当前连胜
                $winning = D('GambleHall')->getWinning($v['user_id']);
                $killer[$k]['winning']= $winning['curr_victs'];
            }
        }
        foreach ($killer as $k => $v) {
            $win_sort[] = $v['WinNum'];
            $is_robot[] = $v['is_robot'];
        }
        array_multisort($win_sort,SORT_DESC,$is_robot,SORT_ASC,$killer);
        $this->assign('killer', $killer);

        //免费推荐
        $publishEliteList = M('PublishList')
                            ->where(['class_id'=>6,'status'=>1,'_string'=>'seo_title is not null'])
                            ->field('id,seo_title')
                            ->order('update_time desc')
                            ->limit(0,10)
                            ->select();
        $this->assign('publishEliteList',$publishEliteList);

        //北单推荐
        $publishNorthList = M('PublishList')
                            ->where(['class_id'=>55,'status'=>1,'_string'=>'seo_title is not null'])
                            ->field('id,seo_title')
                            ->order('update_time desc')
                            ->limit(0,10)
                            ->select();
        $this->assign('publishNorthList',$publishNorthList);

        //足球情报
        $publishClass = M('PublishClass')->where("status=1")->field("id,pid,level")->select();
        $worldClassIds = Tool::getAllSubCategoriesID( $publishClass, 1 );
        $chinaClassIds = Tool::getAllSubCategoriesID( $publishClass, 2 );
        $publishClassIds = array_merge($worldClassIds,$chinaClassIds);
        $publishFbList = M('PublishList')
                        ->where(['status'=>1,'_string'=>'seo_title is not null','class_id'=>['IN',$publishClassIds]])
                        ->field('id,seo_title')
                        ->order('update_time desc')
                        ->limit(0,10)
                        ->select();
        $this->assign('publishFbList',$publishFbList);

        //获取足球排行榜
        $this->assign('footWinWeek', D('Common')->getRankingData(1,1,null,6));//周榜
        $this->assign('footWinMonth', D('Common')->getRankingData(1,2,null,6));//月榜
        $this->assign('footWinSeason', D('Common')->getRankingData(1,3,null,6));//季榜
        //获取足球红人榜
        $this->assign('footRedList', D('Common')->getRedList(1,6));
    
        //友情链接
        $this->assign('foxSeo', M('config')->where(['sign'=>'FoxSeo'])->getField('config'));
        
        
        $user_id = is_login();
        if (! empty($user_id))
        {
            $this->assign('focus', 1);//判断是否已关注
            $this->assign('registerBtn', 1);//改变注册按钮或免费推荐
            
        }
        $this->display();
    }
    /**
     * 防爬--登录、注册的前端代码
     * @User liangzk 《liangzk@qc.com》
     * @DateTime 2016-10-10
     */
    public function loginForm()
    {
        $this->display();
    }
    public function reisterForm()
    {
        $this->display();
    }
    /**
     * 注册
     * @User liangzk 《liangzk@qc.com》
     * @DateTime 2016-09-01
     */
    public function seo_register()
    {
        $mobile = I('mobile');
        $captcha = I('captcha');
        $nick_name = I('nick_name');
        $passd = I('passd');
        $com_passd = I('com_passd');

        if (empty($mobile) || empty($captcha) || empty($nick_name) || empty($passd) || empty($com_passd))
            $this->error('参数错误！');
        
        
        if (! preg_match('#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^16[6]{1}\d{8}|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$|^19[8,9]{1}\d{8}$#', $mobile))
            $this->error(['msg'=>'手机号码格式错误！','sign'=>1]);
        
        if (M('FrontUser')->where(['username'=>$mobile])->getField('username'))
            $this->error(['msg'=>'手机号码已经被注册过！','sign'=>1]);
        
        
        if (strlen($nick_name) > 10 || strlen($nick_name) < 2)
            $this->error(['msg'=>'昵称为2-10个字符','sign'=>3]);
    
        if (M('FrontUser')->where(['nick_name'=>$nick_name])->getField('$nick_name'))
            $this->error(['msg'=>'昵称已经被注册过！','sign'=>3]);
        
        //验证密码
        if (! preg_match('/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,15}$/',$passd))
            $this->error(['msg'=>'输入密码格式不正确！','sign'=>4]);

        if ($passd !== $com_passd)
            $this->error(['msg'=>'密码不一致！','sign'=>5]);
    
        /* 检测验证码 */
        $isTrue = self::checkMobileVerify($captcha,$mobile);
        if(!$isTrue)
        {
            //删除cookie里的验证码
            S(cookie('verifyCode'),null);
            cookie('verifyCode', null);
            cookie('verifySign', null);
            $this->error(['msg'=>'验证码错误或已超时!','sign'=>2]);
            exit;
        }

        
        
        $res = M('FrontUser')->add(['username'  => $mobile,
                                    'nick_name' => $nick_name,
                                    'password'  => md5($passd),
                                    'reg_time'  => time(),
                                    'reg_ip'    => get_client_ip(),
                                    'channel_code'  => 'fox008',
                                    'platform'  => 1]);
        if ($res === false)
            $this->error('注册失败！');

        //登录
        if (D('FrontUser')->autoLogin($res))
        {
            //成功后删除cookie里的验证码
            S(cookie('verifyCode'),null);
            cookie('verifyCode', null);
            cookie('verifySign', null);
            $this->success('注册成功');
        }

        $this->error('操作失败！');
    }
    
    /**分步注册***第一步****/
    public function first_register()
    {
        $reg_username = I('reg_username');
        $reg_captcha = I('reg_captcha');
        
        if (empty($reg_username) || empty($reg_captcha) )
            $this->error('参数错误！');
        if (! preg_match('#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^16[6]{1}\d{8}|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$|^19[8,9]{1}\d{8}$#', $reg_username))
        {
            $this->error('手机号格式不正确！');
            exit;
        }
        
        /* 检测验证码 */
        $isTrue = self::checkMobileVerify($reg_captcha,$reg_username);
        if(!$isTrue)
        {
            //删除cookie里的验证码
            S(cookie('verifyCode'),null);
            cookie('verifyCode', null);
            cookie('verifySign', null);
            $this->error('验证码错误或已超时！');
            exit;
        }
        
        S('reg_username'.MODULE_NAME.'first_register',$reg_username,60*5);
        S('reg_captcha'.MODULE_NAME.'first_register',$reg_captcha,60*5);
        $this->success('第一步完成');
        
    }
    /**注册*****第二步*/
    public function sec_register()
    {
        $reg_nick_name = I('reg_nick_name','','string');
        $reg_pass = I('reg_pass');
        $reg_pass_comf = I('reg_pass_comf');
        if (empty($reg_nick_name) || empty($reg_pass) || empty($reg_pass_comf))
            $this->error('参数错误！');
    
        $reg_username = S('reg_username'.MODULE_NAME.'first_register');
        $reg_captcha = S('reg_captcha'.MODULE_NAME.'first_register');
        
        if (empty($reg_username) || empty($reg_captcha))
            $this->error('验证码错误或已超时！');
        
        /* 检测验证码 */
        $isTrue = self::checkMobileVerify($reg_captcha,$reg_username);
        if(!$isTrue)
        {
            //删除手机号、验证码
            S('reg_username'.MODULE_NAME.'first_register',null);
            S('reg_captcha'.MODULE_NAME.'first_register',null);
            //删除cookie里的验证码
            S(cookie('verifyCode'),null);
            cookie('verifyCode', null);
            cookie('verifySign', null);
            $this->error(['msg'=>'验证码错误或已超时!','sign'=>2]);
            exit;
        }
        
        //验证密码
        if (! preg_match('/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,15}$/',$reg_pass))
            $this->error('输入密码格式不正确！');
    
        if ($reg_pass !== $reg_pass_comf)
            $this->error('密码不一致！');
    
    
        $res = M('FrontUser')->add(['username'  => $reg_username,
                                    'nick_name' => $reg_nick_name,
                                    'password'  => md5($reg_pass),
                                    'reg_time'  => time(),
                                    'reg_ip'    => get_client_ip(),
                                    'channel_code'  => 'fox008',
                                    'platform'  => 1]);
        if ($res === false)
            $this->error('注册失败！');
    
        //登录
        if (D('FrontUser')->autoLogin($res))
        {
            //删除手机号、验证码
            S('reg_username'.MODULE_NAME.'first_register',null);
            S('reg_captcha'.MODULE_NAME.'first_register',null);
            //成功后删除cookie里的验证码
            S(cookie('verifyCode'),null);
            cookie('verifyCode', null);
            cookie('verifySign', null);
            $this->success('注册成功');
        }
    
        $this->error('操作失败！');
        
    }
    /**
     * 关注
     */
    public function focus()
    {
        $user_id = is_login();
        if (! empty($user_id))
        {
            if (! S('focus:'.$user_id))
            {
                S('focus:'.$user_id,1,3600*240);
            }
            $this->success('关注成功！');
        }
        else
        {
            $this->error('关注失败！');
        }

    }
    /**
     * 校验手机验证码
     * @param string $verifyNum 	#待验证的验证码
     * @param string $verifyNum  #待验证的手机号
     * @return  #
     */
    public function checkMobileVerify($verifyNum,$mobile)
    {
        //获取验证码
        $verify = S(cookie('verifyCode'));
        if (empty($verify)){
            //验证码超时
            return false;
        } elseif($verify['rank'] != $verifyNum || $verify['mobile'] != $mobile){
            //验证码错误
            return false;
        } else {
            //验证通过
            cookie('verifySign',['mobile'=>$mobile,'rank'=>$verifyNum],C('verifyCodeTime'));
            return true;
        }
    }
    /*验证手机号是否存在或被禁用false,不存在或已已被禁用返回false*/
    public function loginMobile()
    {
        $mobile = I('p_username','','string');
        $isMobile = M('frontUser')->where(array('username'=>$mobile,'status'=>1))->find();
        if(! empty($isMobile)){
            echo "true";
        }else{
            echo "false";
        }
    }
    /*验证手机是否注册,已经注册返回false*/
    public function checkMobile()
    {
        $mobile = I('mobile');
        if (empty($mobile))
        {
            $mobile = I('reg_username');
        }
        $isMobile = M('frontUser')->field('id')->where(array('username'=>$mobile))->find();
        if(empty($isMobile)){
            echo "true";
        }else{
            echo "false";
        }
    }
    /*验证昵称是否注册,已经注册返回false*/
    public function checkNickName()
    {
        $nick_name = I('nick_name');
        $isMobile = M('frontUser')->field('id')->where(array('nick_name'=>$nick_name))->find();
        if(empty($isMobile)){
            echo "true";
        }else{
            echo "false";
        }
    }
    /*发送手机验证码*/
    public function sendMobileMsg()
    {
        $mobile = I('mobile');
        $msgType = I('msgType');
        $isMobile = M('FrontUser')->where(['username'=>$mobile])->find();
        if ($msgType === 'registe')//注册
        {
            //是否已注册
            if($isMobile){
                $this->error('该手机号码已经注册，不能再注册！');
                exit;
            }
        }
        else
        {
            $this->error('无法操作');
        }
        $_POST['platform'] = 1;
        $result = sendCode($mobile,$msgType);
        if($result == '-1'){
            //已经发送过,需等待60秒
            $this->error('您已经发送过验证码,请等待'.C('reSendCodeTime').'秒后重试!');
            exit;
        }
        if ($result)
        {
            cookie('verifyCode', $result['token'], C('verifyCodeTime'));  //存返回值
            //发送成功
            $msg = $result['mobileSMS'] == 3 ? '请留意稍后的电话语音通知' : '请留意下发的短信通知';
            //发送成功
            $this->success('发送成功，'.$msg.'，验证码'.(C('verifyCodeTime')/60).'分钟内有效，请尽快完成验证！');
        }
        else
        {
            //发送失败
            $this->error('你发送太频繁了，请稍后重试！');
        }
        
    }

}