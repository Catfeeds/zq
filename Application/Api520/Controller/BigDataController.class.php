<?php
/**
 * 大数据接口
 * @author dengwj@qqty.com 2017.11.20
 */

use Think\Tool\Tool;
use Api520\Services\AppfbService;

class BigDataController extends PublicController
{

    public $today = '';

    public function _initialize(){
        parent::_initialize();
        //设置足球比赛日期
        if (time() > strtotime('10:32:00')) {
            $this->today = date('Y-m-d');
        } else {
            $this->today = date('Y-m-d', strtotime('-1 day'));
        }
    }

    /**
     * 5.1 新首页上半部分
     */
    public function index()
    {
        //滚动的banner
        if (!$banner = S('BigDataBanner'. MODULE_NAME.$this->param['platform'])) {
            $banner = Tool::getAdList(1, 5, $this->param['platform']) ?: [];

            if (!empty($banner)) {
                $banner = D('Home')->getBannerShare($banner);
                foreach ($banner as $k => &$v) {
                    //大于5.1首页图片尺寸更换
                    $v['img'] = str_replace('321P', '246P', $v['img']);
                    unset($v['app_isbrowser'], $v['remark'], $v['value'], $v['shareTitle'], $v['shareImg']);
                }
                S('BigDataBanner'. MODULE_NAME.$this->param['platform'], json_encode($banner), 60 * 5);
            }
        }

        //大数据模型
        //类型模块
        if(!$modelList = S('modelListIndex'.MODULE_NAME)) {
            $modelList = M('BigdataClass')->field('name, sign, img, remark, url, isLogin')->where(['status' => 1])->order('sort asc')->select();
            foreach ($modelList as $k => &$v) {
                $v['img'] = $v['img'] ? Tool::imagesReplace($v['img']) : '';
            }
            S('modelListIndex'.MODULE_NAME, json_encode($modelList), 60 * 20);
        }

        //模型赛事
        //冷热交易
        if(!$bigData = S('bigDataIndex' . MODULE_NAME)){
            $date = $this->today;
            //冷热交易
            $lengre = D('BigData')->getHotColdTrade(1);
            $bigData['lengRe'] = isset($lengre[0]) ? $lengre[0] : (object)[];

            //每日极限：亚，连胜或连输最高
            $jiXian1 = D('BigData')->getDailyMax(2, 1) ?: D('BigData')->getDailyMax(2, 3);
            $bigData['jiXian1'] = isset($jiXian1[0]) ? $jiXian1[0] : (object)[];
            if(is_array($bigData['jiXian1'])) unset($bigData['jiXian1']['handcp']);

            //历史同赔：亚，最高
            $tongPei = D('BigData')->getAlikeHistory(2);
            $bigData['tongPei'] = isset($tongPei[0]) ? $tongPei[0] : (object)[];

            //赢盘对抗
            $fbService = new AppfbService();
            $yingPan = $fbService->getStrengthListMon($date);
            $bigData['yingPan'] = isset($yingPan[0]) ? $yingPan[0] : (object)[];
            if(is_array($bigData['yingPan'])) unset($bigData['yingPan']['handcp']);

            //竞彩误差
            $data = D('BigData')->getJingcaiDiff(1, 1, $date);
            if(isset($data[0])) $data[0]['handcp'] = $data[0]['handcp'] ? '竞彩' : '';
            $bigData['jingCai'] = isset($data[0]) ? $data[0] : (object)[];

            S('bigDataIndex' . MODULE_NAME, json_encode($bigData), 60 * 10);
        }else{
            foreach($bigData as $k => &$v){
                if(empty($v)) $v = (object)$v;
            }
        }

        //直播比赛聊天记录
        $liveGame = D('Home')->getLiveGame();

        //商城标题banner
        if(!$shopTopicBanner = S('shopTopicBannerIndex' . MODULE_NAME)){
            $shopTopicBanner = Tool::getAdList(99, 1, $this->param['platform']) ?: [];
            unset($shopTopicBanner[0]['app_isbrowser'], $shopTopicBanner[0]['remark'], $shopTopicBanner[0]['value']);
            S('shopTopicBannerIndex' . MODULE_NAME, json_encode($shopTopicBanner), 60 * 10);
        }

        //商城banner
        if(!$shopBanner = S('shopBannerIndex' . MODULE_NAME)){
            $shopBanner = Tool::getAdList(98, 1, $this->param['platform']) ?: [];
            unset($shopBanner[0]['app_isbrowser'], $shopBanner[0]['remark'], $shopBanner[0]['value']);
            S('shopBannerIndex' . MODULE_NAME, json_encode($shopBanner), 60 * 10);
        }

        //热销商品
        if(!$shopProduct = S('$shopProductIndex' . MODULE_NAME)){
            $shopProduct = D('Home')->getShopProduct();

            S('$shopProductIndex' . MODULE_NAME, json_encode($shopProduct), 60 * 10);
        }

        $this->ajaxReturn([
            'banner'    => $banner,
            'modelList' => (array)$modelList,
            'bigData'   => $bigData,
            'liveGame'  => $liveGame,
            'shopTopicBanner' => $shopTopicBanner,
            'shopBanner'=> $shopBanner,
            'shopProduct' => $shopProduct,
        ]);
    }

    /**
     * 5.1 新首页：篮球和足球分析
     */
    public function newsAnalyze(){
        $gameType = $this->param['gameType'] ?: 0;//赛事类型：1足球，2篮球

        if($gameType == 0) $this->ajaxReturn(101);

        //每日精选
        if(!$newHotList = S('newsExpert'.MODULE_NAME.$gameType)){
            $hotList = D('Home')->getHotList($gameType);
            if($gameType == 2){
                $hotList['yapan'] = $hotList['basketball'];
                unset($hotList['basketball']);
            }

            $yapan = $jingcai = [];
            foreach($hotList['yapan'] as $hk => &$hv){
				//足球亚盘10中8以下过滤
                if(explode('10中', $hv['tenGamble'])[1] < 7 && $gameType == 1){
                    continue;
                }
                $yapan[$hk]['face']     = $hv['face'];
                $yapan[$hk]['nickname'] = $hv['nick_name'];
                $yapan[$hk]['userId']   = $hv['user_id'];
                $yapan[$hk]['tenGambleRate'] = $hv['tenGamble'];
                $yapan[$hk]['gameType'] = $hv['gameType'];
                $yapan[$hk]['hotType']  = $hv['hotType'];
            }
            if($gameType == 1){//足球有竞彩
                foreach($hotList['jingcai'] as $jk => &$jv){
                    $jingcai[$jk]['face']     = $jv['face'];
                    $jingcai[$jk]['nickname'] = $jv['nick_name'];
                    $jingcai[$jk]['userId']   = $jv['user_id'];
                    $jingcai[$jk]['tenGambleRate'] = $jv['tenGamble'];
                    $jingcai[$jk]['gameType'] = $jv['gameType'];
                    $jingcai[$jk]['hotType']  = $jv['hotType'];
                }
            }

            if($gameType == 1) {
                $newHotList = ['yapan' => array_slice($yapan, 0, 20), 'jingcai' => array_slice($jingcai, 0, 20)];
            }else {
                $newHotList = ['yapan' => array_slice($yapan, 0, 20)];
            }
            unset($hotList);
            S('newsExpert'.MODULE_NAME.$gameType, json_encode($newHotList), 60*2);
        }

        //资讯：首页推荐优化再时间倒序
        $newList = D('Home')->getNewsList($gameType);

        $this->ajaxReturn(['expertList' => $newHotList, 'newsList' => $newList]);
    }

    /**
     *  亚赔对抗
     * @param  json         $data               返回数据
     */
    function compete()
    {
        $date = !empty($_REQUEST['date'])?$_REQUEST['date']:$this->today;
        $type = !empty($_REQUEST['type'])?$_REQUEST['type']:1;

        $fbService = new AppfbService();

        if($type == 1)
        {
            $res = $fbService->getStrengthListMon($date);
        }
        else if($type == 2)
        {
            //$res = $fbService->getAsianCompeteMon($date,1);
            $res = $fbService->getAsianCompeteMonT($date,1);
        }
        else if($type == 3)
        {
            //$res = $fbService->getAsianCompeteMon($date,2);
            $res = $fbService->getAsianCompeteMonT($date,2);
        }

        if(empty($res))
            $this->ajaxReturn(200);
        else
            $this->ajaxReturn($res);
    }

    /**
     * 竞彩差异
     */
    public function bettingDifference(){
        $chooseSide = $this->param['chooseSide'] ?: 1;//选择胜平负：主胜：1，平局：2，客胜：3
        $playType   = $this->param['playType'] ?: 1;//玩法：不让球1，让球2
        $date       = $this->param['date'] ?: $this->today;

        $data = D('BigData')->getJingcaiDiff($chooseSide, $playType, $date);

        $this->ajaxReturn($data ?: []);
    }

    /**
     * 冷热交易
     */
    public function hotColdTrade()
    {
        $type = $this->param['type'] ?: 1;             //1:必发交易 2:庄家盈利 3:庄家亏损  默认1
        $date = $this->param['date'] ?: $this->today; //年月日，yyyy-mm-dd 默认今天

        $data = D('BigData')->getHotColdTrade($type, $date);

        $this->ajaxReturn($data ?: []);
    }

    /**
     * 每日极限
     */
    public function dailyMax()
    {
        $playType = $this->param['playType'] ?: 1; //玩法：1胜平负，2亚盘，3大小球
        $winType  = $this->param['winType']  ?: 1; //类型：1连胜/连大 ，2连平 ， 3连负/连小
        $date = $this->param['date'] ?: $this->today; //年月日，yyyy-mm-dd 默认今天

        $data = D('BigData')->getDailyMax($playType, $winType, $date);

        $this->ajaxReturn($data ?: []);
    }

    /**
     * 大数据问球首页
     */
    public function askTheBall()
    {
        $date = $this->today;
        $DataModel = D('BigData');

        //模型信息
        if(!$info = S('askTheBall_info')){
            $info = getWebConfig('bigDataAsk') ? : [];
            S('askTheBall_info',json_encode($info),30*60);
        }

        //模型列表
        if(!$classArray = S('askTheBall_class'.MODULE_NAME)){
            $classArray = M('bigdataList')->where(['status'=>1])->field('name as className,img,sign,url')->select() ? : [];
            if($classArray){
                foreach ($classArray as $k => $v) {
                    $classArray[$k]['img'] = $v['img'] ? Tool::imagesReplace($v['img']) : '';
                }
            }
            S('askTheBall_class'.MODULE_NAME,json_encode($classArray),30*60);
        }

        $jiXian = $lengRe = $yingPan = $tongPei = $jingCai1 = $jingCai2 = [];

        //历史同赔（竞彩同赔、亚盘同赔、大小同赔）分别取排第一的那场，共三条内容
        $tongPei1 = $DataModel->getAlikeHistory(1);
        $tongPei2 = $DataModel->getAlikeHistory(2);
        $tongPei3 = $DataModel->getAlikeHistory(3);
        if(isset($tongPei1[0])) {
            $tongPei1[0]['type'] = 1;
            $tongPei[] = $tongPei1[0];
        }
        if(isset($tongPei2[0])) {
            $tongPei2[0]['type'] = 2;
            $tongPei[] = $tongPei2[0];
        }
        if(isset($tongPei3[0])){
            $tongPei3[0]['type'] = 3;
            $tongPei[] = $tongPei3[0];
        }

        //极限精选（胜负平极限、亚盘极限、大小极限）分别取排第一的那场，共三条内容
        $jiXian1 = $DataModel->getDailyMax(1,1);
        $jiXian2 = $DataModel->getDailyMax(2,1);
        $jiXian3 = $DataModel->getDailyMax(3,1);
        if(isset($jiXian1[0])) {
            $jiXian1[0]['type'] = 1;
            $jiXian[] = $jiXian1[0];
        }
        if(isset($jiXian2[0])) {
            $jiXian2[0]['type'] = 2;
            $jiXian[] = $jiXian2[0];
        }
        if(isset($jiXian3[0])){
            $jiXian3[0]['type'] = 3;
            $jiXian[] = $jiXian3[0];
        }

        //冷热交易（必发交易、机构盈利、机构亏损）分别取排第一的那场，共三条内容
        $lengRe1 = $DataModel->getHotColdTrade(1);
        $lengRe2 = $DataModel->getHotColdTrade(2);
        $lengRe3 = $DataModel->getHotColdTrade(3);
        if(isset($lengRe1[0])) {
            $lengRe1[0]['type'] = 1;
            $lengRe[] = $lengRe1[0];
        }
        if(isset($lengRe2[0])) {
            $lengRe2[0]['type'] = 2;
            $lengRe[] = $lengRe2[0];
        }
        if(isset($lengRe3[0])) {
            $lengRe3[0]['type'] = 3;
            $lengRe[] = $lengRe3[0];
        }

        //赢盘对抗（实力对抗、亚盘对抗、大小对抗）分别取排第一的那场，共三条内容
        if(!$yingPan = S('askTheBall_yingPan')){
            $fbService = new AppfbService();
            $yingPan1 = $fbService->getStrengthListMon($date);
            $yingPan2 = $fbService->getAsianCompeteMon($date,1);
            $yingPan3 = $fbService->getAsianCompeteMon($date,2);
            if(isset($yingPan1[0])) {
                $yingPan1[0]['type'] = 1;
                $yingPan[] = $yingPan1[0];
            }
            if(isset($yingPan2[0])) {
                $yingPan2[0]['type'] = 2;
                $yingPan[] = $yingPan2[0];
            }
            if(isset($yingPan3[0])) {
                $yingPan3[0]['type'] = 3;
                $yingPan[] = $yingPan3[0];
            }
            S('askTheBall_yingPan',json_encode($yingPan),10*60);
        }

        //不让球误差（胜误差、平误差、负误差）分别取排第一的那场，共三条内容
        $notBall1 = $DataModel->getJingcaiDiff(1,1,$date);
        $notBall2 = $DataModel->getJingcaiDiff(2,1,$date);
        $notBall3 = $DataModel->getJingcaiDiff(3,1,$date);
        if(isset($notBall1[0])) {
            $notBall1[0]['type'] = 1;
            $jingCai1[] = $notBall1[0];
        }
        if(isset($notBall2[0])) {
            $notBall2[0]['type'] = 2;
            $jingCai1[] = $notBall2[0];
        }
        if(isset($notBall3[0])) {
            $notBall3[0]['type'] = 3;
            $jingCai1[] = $notBall3[0];
        }

        //让球误差（让球胜误差、让球平误差、让球负误差）分别取排第一的那场，共三条内容
        $Ball1 = $DataModel->getJingcaiDiff(1,2,$date);
        $Ball2 = $DataModel->getJingcaiDiff(2,2,$date);
        $Ball3 = $DataModel->getJingcaiDiff(3,2,$date);
        if(isset($Ball1[0])) {
            $Ball1[0]['type'] = 1;
            $jingCai2[] = $Ball1[0];
        }
        if(isset($Ball2[0])) {
            $Ball2[0]['type'] = 2;
            $jingCai2[] = $Ball2[0];
        }
        if(isset($Ball3[0])) {
            $Ball3[0]['type'] = 3;
            $jingCai2[] = $Ball3[0];
        }

        $this->ajaxReturn([
            'info'       => $info,
            'classArray' => $classArray,
            'tongPei'    => $tongPei,
            'jiXian'     => $jiXian   ?: [],
            'lengRe'     => $lengRe   ?: [],
            'yingPan'    => $yingPan  ?: [],
            'jingCai1'   => $jingCai1 ?: [],
            'jingCai2'   => $jingCai2 ?: [],
        ]);
    }

    /**
     * 赢盘雷达web首页专用
     * @author longs <longs@qc.mail>
     */
    public function web_askTheBall()
    {
        $date = $this->today;
        $DataModel = D('BigData');
        $jiXian = $lengRe = $yingPan = $jingCai1 = $jingCai2 = $jiXian_array1 = $jiXian_array2 = $jiXian_array3 = $lengRe_array1 = $lengRe_array2 = $lengRe_array3 = $yingPan_array1 = $yingPan_array2 = $yingPan_array3 =[];

        //极限精选（胜负平极限、亚盘极限、大小极限）分别取排第一的那场，共三条内容
        $jiXian1 = $DataModel->getDailyMax(1,1);
        $jiXian2 = $DataModel->getDailyMax(2,1);
        $jiXian3 = $DataModel->getDailyMax(3,1);

        if(isset($jiXian1[0])) $jiXian_array1[] = $jiXian1[0];

        if(isset($jiXian1[1])) $jiXian_array2[] = $jiXian1[1];

        if(isset($jiXian2[0])) $jiXian_array1[] = $jiXian2[0];

        if(isset($jiXian2[1])) $jiXian_array2[] = $jiXian2[1];

        if(isset($jiXian3[0])) $jiXian_array1[] = $jiXian3[0];

        if(isset($jiXian3[1])) $jiXian_array2[] = $jiXian3[1];


        //冷热交易（必发交易、机构盈利、机构亏损）分别取排第一的那场，共三条内容
        $lengRe1 = $DataModel->getHotColdTrade(1);
        $lengRe2 = $DataModel->getHotColdTrade(2);
        $lengRe3 = $DataModel->getHotColdTrade(3);
        if(isset($lengRe1[0])) $lengRe_array1[] = $lengRe1[0];

        if(isset($lengRe1[1])) $lengRe_array2[] = $lengRe1[1];

        if(isset($lengRe2[0])) $lengRe_array1[] = $lengRe2[0];

        if(isset($lengRe2[1])) $lengRe_array2[] = $lengRe2[1];

        if(isset($lengRe3[0])) $lengRe_array1[] = $lengRe3[0];

        if(isset($lengRe3[1])) $lengRe_array2[] = $lengRe3[1];

        //赢盘对抗（实力对抗、亚盘对抗、大小对抗）分别取排第一的那场，共三条内容
        $fbService = new AppfbService();
        $yingPan1 = $fbService->getStrengthListMon($date);
        $yingPan2 = $fbService->getAsianCompeteMonT($date,1);
        $yingPan3 = $fbService->getAsianCompeteMonT($date,2);

        if(isset($yingPan1[0])) {
            $yingPan1[0]['type'] = 1;
            $yingPan_array1[] = $yingPan1[0];
        }

        if(isset($yingPan1[1])) {
            $yingPan1[1]['type'] = 1;
            $yingPan_array2[] = $yingPan1[1];
        }

        if(isset($yingPan2[0])) {
            $yingPan2[0]['type'] = 2;
            $yingPan_array1[] = $yingPan2[0];
        }

        if(isset($yingPan2[1])) {
            $yingPan2[1]['type'] = 2;
            $yingPan_array2[] = $yingPan2[1];
        }

        if(isset($yingPan3[0])) {
            $yingPan3[0]['type'] = 3;
            $yingPan_array1[] = $yingPan3[0];
        }

        if(isset($yingPan3[1])) {
            $yingPan3[1]['type'] = 3;
            $yingPan_array2[] = $yingPan3[1];
        }

        //竞彩差异
        //不让球误差（胜误差、平误差、负误差）分别取排第一的那场，共三条内容
        $notBall1 = $DataModel->getJingcaiDiff(1, 1, $date);
        $notBall2 = $DataModel->getJingcaiDiff(2, 1, $date);
        $notBall3 = $DataModel->getJingcaiDiff(3, 1, $date);
        if (isset($notBall1[0])) {
            $notBall1[0]['type'] = 1;
            $jingCai1[] = $notBall1[0];
        }
        if (isset($notBall2[0])) {
            $notBall2[0]['type'] = 2;
            $jingCai1[] = $notBall2[0];
        }
        if (isset($notBall3[0])) {
            $notBall3[0]['type'] = 3;
            $jingCai1[] = $notBall3[0];
        }

        //让球误差（让球胜误差、让球平误差、让球负误差）分别取排第一的那场，共三条内容
        $Ball1 = $DataModel->getJingcaiDiff(1, 2, $date);
        $Ball2 = $DataModel->getJingcaiDiff(2, 2, $date);
        $Ball3 = $DataModel->getJingcaiDiff(3, 2, $date);
        if (isset($Ball1[0])) {
            $Ball1[0]['type'] = 1;
            $jingCai2[] = $Ball1[0];
        }
        if (isset($Ball2[0])) {
            $Ball2[0]['type'] = 2;
            $jingCai2[] = $Ball2[0];
        }
        if (isset($Ball3[0])) {
            $Ball3[0]['type'] = 3;
            $jingCai2[] = $Ball3[0];
        }

        $this->ajaxReturn([
            'jiXian1'  => $jiXian_array1 ?: [],
            'jiXian2'  => $jiXian_array2 ?: [],
            'lengRe1'  => $lengRe_array1 ?: [],
            'lengRe2'  => $lengRe_array2 ?: [],
            'yingPan1' => $yingPan_array1 ?: [],
            'yingPan2' => $yingPan_array2 ?: [],
            'jingCai1'=> $jingCai1 ?: [],
            'jingCai2'=> $jingCai2 ?: [],
        ]);
    }

    /**
     * 历史同赔
     * @author dengwj
     */
    public function alikeHistory()
    {
        $type   = I('type',1,'int'); //类型 1:欧指 2:亚指 3:大小 默认1
        $date   = $this->param['date'] ?: $this->today; //年月日，yyyy-mm-dd 默认今天

        $history = D('bigData')->getAlikeHistory($type,$date);
        
        $this->ajaxReturn($history);
    }

    /**
     * 历史同赔详情页
     * @author dengwj
     */
    public function alikeHistoryDetail()
    {
        $gameId = I('gameId','','int'); //赛程id
        $type   = I('type',1,'int'); //类型 1:欧指 2:亚指 3:大小 默认1

        $history = D('bigData')->getAlikeHistoryDetail($gameId,$type);
        
        $this->ajaxReturn($history);
    }

    /**
     * 滚球预警
     */
    public function rollingBallWarning() {
        $rollBallData = S("rollingBallWarning");
        if (!$rollBallData) {
	        $date   = $this->today; //年月日，yyyy-mm-dd
	        $fbService = new AppfbService();
	        $data = $fbService->getNowGameData($date);
	        $rollBallData = json_encode($data);
	        S("rollingBallWarning", $rollBallData, 120);
	        $this->ajaxReturn($data);
        } else {
	        $this->ajaxReturn($rollBallData);
        }
    }


	//清空redis方法
    public function clearCache(){
        echo connRedis()->flushdb();
    }
}
 ?>