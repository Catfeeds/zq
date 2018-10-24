<?php
/**
 * 结合微信、app、等h5页面
 *
 * @author chenzj <443629770@qq.com>
 *
 * @since  2016-08-11
 */

class H5Controller extends CommonController
{
    public function index()
    {
        $this->show('h5');
    }

    /**
     * 微信介绍页
     */
    public function intro()
    {
        //微信分享內容控制
        $this->wxShar();
        $this->introUrl = getWebConfig('msite')['intro']; //获取上榜专家新闻地址
        $this->display();
    }

    //下载安装ipa文件
    public function ipa()
    {
        $this->plistUlr = 'https://oc3kjjk30.qnssl.com/manifest1.plist';
        $this->display();
    }
    
    public function referral(){
        $this->display();
    }
    
    //进入APP下载页统计
    public function appJump()
    {
        $sign = I('sign');
        $ip   = get_client_ip();

        if(!empty($sign) && !empty($ip))
        {
            list($start,$end) = [strtotime(date(Ymd)),strtotime(date(Ymd))+86400]; //今天时间段

            //是否已记录
            $is_has = M("appJump")->where(['sign'=>$sign,'ip'=>$ip,'time'=>['BETWEEN',[$start,$end]]])->find();

            if(!$is_has)
            {
                //新增新点击记录
                M('appJump')->add([
                        'sign'   => $sign,
                        'ip'     => $ip,
                        'number' => 1,
                        'time'   => NOW_TIME
                    ]);
            }
            else
            {
                //点击次数加1
                M('appJump')->where(['sign'=>$sign,'ip'=>$ip,'time'=>['BETWEEN',[$start,$end]]])->setInc('number');
            }
        }
        
        $this->display();
    }
	
	/**
	 * 自媒体
	 * @User liangzk <liangzk@qc.com>
	 * @Date 2016-12-27 16:53
	 *
	 */
    public function selfMedia()
	{
		$this->display();
	}

    //车展活动专题页
    public function carShow()
    {
        $this->display();
    }

    //竞彩专家推广活动banner
    public function adgame(){
        $this->display();
    }
}