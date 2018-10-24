<?php
/**
 * 赢盘雷达
 * @author longs<longs@qc.mail>
 * @Date 2018-1-29
 */
use Think\Controller;

/**
 * Class WinDiskRadar 赢盘雷达
 */
class WinDiskRadarController extends CommonController
{
    public $week = [];
    public $today = '';

    public function _initialize(){
        parent::_initialize();
        //设置足球比赛日期
        if (time() > strtotime('10:32:00')) {
            $this->today = date('Y-m-d');
        } else {
            $this->today = date('Y-m-d', strtotime('-1 day'));
        }

        //设置一周日期
        $weekArray = array("周日", "周一", "周二", "周三", "周四", "周五", "周六"); //日期数组
        $this->week[date('d').' '.$weekArray[date('w')]] = date('Y-m-d');
        $this->week[date('d', strtotime('-1 day')).' '.$weekArray[date('w', strtotime('-1 day'))]] = date('Y-m-d', strtotime('-1 day'));
        $this->week[date('d', strtotime('-2 day')).' '.$weekArray[date('w', strtotime('-2 day'))]] = date('Y-m-d', strtotime('-2 day'));
        $this->week[date('d', strtotime('-3 day')).' '.$weekArray[date('w', strtotime('-3 day'))]] = date('Y-m-d', strtotime('-3 day'));
        $this->week[date('d', strtotime('-4 day')).' '.$weekArray[date('w', strtotime('-4 day'))]] = date('Y-m-d', strtotime('-4 day'));
        $this->week[date('d', strtotime('-5 day')).' '.$weekArray[date('w', strtotime('-5 day'))]] = date('Y-m-d', strtotime('-5 day'));
        $this->week[date('d', strtotime('-6 day')).' '.$weekArray[date('w', strtotime('-6 day'))]] = date('Y-m-d', strtotime('-6 day'));
        $this->assign('week', $this->week);
    }

    public function index()
    {
        if (!$data = S('web_YpRadar_web_askTheBall')) {
            $interfaceType = "web_askTheBall";
            $data = $this->getInterFaceData($interfaceType, null);
            S('web_YpRadar_web_askTheBall', $data, 60 * 5);
        }

        $param['urlSign'] = 'all';
        $this->assign('param', $param);
        $this->assign('data', $data);
        $this->display(T('YpRadar/all'));
    }

    /**
     * 冷热交易
     */
    public function getHotColdTrade()
    {
        $interfaceType = "hotColdTrade";
        $param['date'] = I("date") ? I("date") : $this->today;
        $param['type'] = I("type") ? I("type") : 1;
        $data = $this->getInterFaceData($interfaceType, $param);

        $param['urlSign'] = 'lrjy';
        $this->assign('param', $param);
        $this->assign('data', $data);
        $this->display(T('YpRadar/lrjy'));
    }


    /**
     * 每日极限
     */
    public function getDailyMax()
    {
        $interfaceType = "dailyMax";
        $param['date'] = I("date") ? I("date") : $this->today;
        $param['playType'] = I("playType") ? I("playType") : 1;
        $param['winType'] = I("winType") ? I("winType") : 1;
        $data = $this->getInterFaceData($interfaceType, $param);

        $param['urlSign'] = 'mrjx';
        $this->assign('param', $param);
        $this->assign('data', $data);
        $this->display(T('YpRadar/mrjx'));
    }


    /**
     * 竞猜差异
     */
    public function getBettingDifference()
    {
        $interfaceType = "bettingDifference";
        $param['date'] = I("date") ? I("date") : $this->today;
        $param['chooseSide'] = I("chooseSide") ? I("chooseSide") : 1;
        $param['playType'] = I("playType") ? I("playType") : 1;
        $data = $this->getInterFaceData($interfaceType, $param);

        $param['urlSign'] = 'jccy';
        $this->assign('param', $param);
        $this->assign('data', $data);
        $this->display(T('YpRadar/jccy'));
    }


    /**
     * 赢盘对抗
     */
    public function getCompete()
    {
        $interfaceType = "compete";
        $param['date'] = I("date") ? I("date") : $this->today;
        $param['type'] = I("type") ? I("type") : 1;
        $data = $this->getInterFaceData($interfaceType, $param);

        $param['urlSign'] = 'ypdk';
        $this->assign('param', $param);
        $this->assign('data', $data);
        $this->display(T('YpRadar/ypdk'));
    }


    /**
     * 获取接口数据
     * @param $interfaceType 接口名
     * @param $param 参数
     * @return mixed array
     */
    public function getInterFaceData($interfaceType, $param)
    {
        $nosign = C('nosignStr');
        $RequestUrl = "https://www.qqty.com/Api510/BigData/".$interfaceType."?nosign=".$nosign;
        $requestData  = $this->InterWarning($RequestUrl, $param);
        $data = json_decode($requestData['data'], true)['data'];
        return $data;
    }


    /**
     * 忽略超时请求
     * @param $request 请求地址
     * @param $param 参数
     * @return array 数据
     */
    public function InterWarning($request, $param)
    {
        if ($param != null) {
            $data = httpPost($request, $param);
            if (!$data['data']) {
                return $this->InterWarning($request, $param);
            }
            return $data;
        } else {
            $data = httpPost($request);
            if (!$data['data']) {
                return $this->InterWarning($request, null);
            }
            return $data;
        }
    }

}