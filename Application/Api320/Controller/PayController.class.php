<?php
/**1
 * app支付接口类
 * @author Hmg<huangmg@qc.mail> 2016.03.25
 */

use Think\Controller;

class PayController extends CommonController
{
    /**
     * app支付接口数据
     * @param  string       $ordtotal_fee       金额
     * @param  string       $userToken          用户token
     * @param  json         $data               返回数据
     */
    function appAlipay()
    {
        $res = D('Pay')->appAlipay($this->userInfo, $this->param);

        $this->ajaxReturn($res);
    }

    /**
     * app微信接口数据
     * @param  array/int    $data       要返回的数据
     * @param  int          $msgCode    指定提示信息的状态码
     * @param  string       $type       返回数据的格式 json xml...
     */
    function appWxpay()
    {
        $res = D('Pay')->appWxpay($this->userInfo, $this->param);

        $this->ajaxReturn($res);
    }

    /**
     * 易宝支付
     * @param bool|false $isJump
     */
    public function appYeepay($isJump=false)
    {
        $res = D('Pay')->appYeepay($this->userInfo, $this->param, $isJump);

        if(is_numeric($res) || $isJump == false){
            $this->ajaxReturn($res);
        }else{
            redirect($res);
        } 
    }

    /**
     * 移动网页订购
     * @param bool|false $isJump
     */
    public function appWabpPay($isJump=false)
    {
        $res = D('Pay')->appWabpPay($this->userInfo, $this->param, $isJump);

        if(is_numeric($res) || $isJump == false){
            $this->ajaxReturn($res);
        }else{
            echo $res;
        }
    }

    /**
     * 安卓移动+接口数据
     * @param  string       $ordtotal_fee       金额
     * @param  string       $userToken          用户token
     * @return json         $data               返回数据
     */
    function androidMovePay()
    {
        $res = D('Pay')->androidMovePay($this->userInfo, $this->param);

        $this->ajaxReturn($res);
    }

	//h5充值页面
    public function html5Pay()
    {
        //获取登录用户信息
        $userInfo = $this->getInfo();
        $userInfo['userToken'] = $this->param['userToken'];
        $this->assign('userInfo',$userInfo);
        $this->assign('pkg',$this->param['pkg']);
        //获取充值配置
        $config = getWebConfig('recharge');
        $rechargeConfig = $config['recharge'];
        //判断有无空
        foreach($rechargeConfig as $rk => $rv){
            if($rv['account'] == ''){
                unset($rechargeConfig[$rk]);
            }
        }
        $fromUrl = SITE_URL.$_SERVER['HTTP_HOST'].'/'.MODULE_NAME.'/'.'PayNotify/html5Pay';
        $this->assign('fromUrl',$fromUrl);
        $this->assign('rechargeConfig',$rechargeConfig);
        $this->display("Home/html5Pay");
    }

}
 ?>