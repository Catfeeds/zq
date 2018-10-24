<?php
/**
 +------------------------------------------------------------------------------
 * App接口控制器
 +------------------------------------------------------------------------------
 * Copyright (c) 2015 http://www.qqw.com All rights reserved.
 +------------------------------------------------------------------------------
 * @author Knight <39383198@qq.com>
 +------------------------------------------------------------------------------
*/
use Think\Controller;
use Api520\Services\AppbkService;

class AppBkController extends PublicController
{
    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 返回接口数据
     * @param  array/int    $data       要返回的数据
     */
    function ajaxReturn($data,$msgCode='',$type='')
    {
        if (is_array($data))
        {
            if($data['status'] == 0){
                $data['msg'] = C('errorCode')[$data['ErrorCode']];
            }
        }
        else
        {
            $rdata['status']    = 0;
            $rdata['data']      = null;
            $rdata['ErrorCode'] = $data;
            $rdata['msg']       = $msgCode ? : C('errorCode')[$data];
            unset($data);
        }

        header('Content-Type:application/json; charset=utf-8');
        exit(json_encode($data ?: $rdata,JSON_UNESCAPED_UNICODE));
    }

    /**
      * 篮球当日赛事
      * @return json
      */
    public function bk()
    {
        $unionId = !empty($_REQUEST['unionId'])? $_REQUEST['unionId'] : null;
        $subId = !empty($_REQUEST['subId'])? $_REQUEST['subId'] : null;
        $platform = !empty($_REQUEST['platform'])? $_REQUEST['platform'] : '';

        $appService = new \Home\Services\AppdatabkService();
        $res = $appService->bktodayList($unionId,$subId,$platform);
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
            $data['msg'] = $this->errorArr['102'];
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        header('content-type:application/json');
        echo json_encode($data);
        //$this->ajaxReturn($data);
    }

    /**
     * 篮球今日赛事变化数据，比分、比赛时间、赛事球员信息
     * @return json
     */
    public function bkchange()
    {
        $appService = new \Home\Services\AppdatabkService();
        $res = $appService->getbkChange();
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
            $data['msg'] = $this->errorArr['102'];
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
     * 篮球今日赛事赔率变化
     * @return json
     */
    public function bkodds()
    {
        $companyId = !empty($_REQUEST['companyId'])? $_REQUEST['companyId'] : 3;
        $appService = new \Home\Services\AppdatabkService();
        $res = $appService->getbkodds($companyId);
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
            $data['msg'] = $this->errorArr['102'];
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }
    /**
     * 篮球亚欧大界面赔率
     * @return json
     */
    public function bkMatchOdds()
    {
        $gameId = !empty($_REQUEST['gameId'])? $_REQUEST['gameId'] : null;
        $type = !empty($_REQUEST['type'])? $_REQUEST['type'] : '1';
        if(!$gameId){
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 101;
            $data['msg'] = $this->errorArr['101'];
            $this->ajaxReturn($data);
        }
        //$appService = new \Home\Services\AppdatabkService();
        $appService = new AppbkService();
        if($type == 2)
        {
            $res = $appService->getbkEurroOdds($gameId);
        }
        else
        {
            $res = $appService->getbkMatchOdds($gameId,$type);
        }

        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
            $data['msg'] = $this->errorArr['102'];
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
     * 篮球历史赔率记录
     * @return json
     */
    public function bkOddsHistory()
    {
        $gameId = !empty($_REQUEST['gameId'])? $_REQUEST['gameId'] : null;
        $type = !empty($_REQUEST['type'])? $_REQUEST['type'] : '1';
        $companyId = !empty($_REQUEST['companyId'])? $_REQUEST['companyId'] : null;
        if(!$gameId || !$companyId){
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 101;
            $data['msg'] = $this->errorArr['101'];
            $this->ajaxReturn($data);
        }

        if($type == 2)
        {
            $appService = new AppbkService();
            $res = $appService->getbkEuroOddsHis($gameId,$companyId);
        }
        else
        {
            $appService = new \Home\Services\AppdatabkService();
            $res = $appService->getbkOddsHistory($gameId,$companyId,$type);
        }
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
            $data['msg'] = $this->errorArr['102'];
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
      * 篮球赛果赛事
      * @return json
      */
    public function bkOver()
    {
        $date = !empty($_REQUEST['date'])? $_REQUEST['date'] : null;

        if(!$date)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 101;
            $data['msg'] = $this->errorArr['101'];
            $this->ajaxReturn($data);
        }
        $appService = new \Home\Services\AppdatabkService();
        $res = $appService->bkOverList($date);
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
            $data['msg'] = $this->errorArr['102'];
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }
    /**
      * 篮球赛程赛事
      * @return json
      */
    public function bkFuture(){
        $date = !empty($_REQUEST['date'])? $_REQUEST['date'] : null;
        if(!$date){
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 101;
            $data['msg'] = $this->errorArr['101'];
            $this->ajaxReturn($data);
        }
        $appService = new \Home\Services\AppdatabkService();
        $res = $appService->bkFutureList($date);
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
            $data['msg'] = $this->errorArr['102'];
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }
    /**
     * 对阵数据(app数据界面)
     * @param  int  赛事ID
     * @return json
     */
    public function analysis()
    {
        $gameId = !empty($_REQUEST['gameId'])?$_REQUEST['gameId']:null;
        $lang = !empty($_REQUEST['lang'])?$_REQUEST['lang']:1;
        if(empty($gameId))
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 101;
            $data['msg'] = $this->errorArr['101'];
            $this->ajaxReturn($data);
        }
        $appService = new \Home\Services\AppdatabkService();
        //$res = $appService->getAnalysis($gameId ,$lang);        //数据库
        //$res = $appService->getAnaForFile($gameId ,$lang);     //web接口数据文件
        $res = $appService->getAnaForAppNs($gameId ,$lang);      //app接口数据文件

//        if(empty($res))
//        {
//            $value = S('analysis_'.$gameId,'',array('type'=>'file','expire'=>600));
//            if($value === false)
//            {
//                $fb = new \Home\Services\AppfbService();
//                $res = $fb->getAnalysis($gameId ,$lang);
//                S('analysis_'.$gameId,$res,array('type'=>'file','expire'=>600));
//            }
//            else
//            {
//                $res = $value;
//            }
//        }

        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
            $data['msg'] = $this->errorArr['102'];
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }
      /**
      * 篮球赛况界面数据
      * @return json
      */
    public function bkSituation(){
        $gameId = !empty($_REQUEST['gameId'])? $_REQUEST['gameId'] : null;
        if(!$gameId){
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 101;
            $data['msg'] = $this->errorArr['101'];
            $this->ajaxReturn($data);
        }
        $appService = new \Home\Services\AppdatabkService();
        $res = $appService->bkSituationList($gameId);
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
            $data['msg'] = $this->errorArr['102'];
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

      /**
      * 篮球技术界面数据
      * @return json
      */
    public function bkTech(){
        $gameId = !empty($_REQUEST['gameId'])? $_REQUEST['gameId'] : null;
        if(!$gameId){
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 101;
            $data['msg'] = $this->errorArr['101'];
            $this->ajaxReturn($data);
        }
        $appService = new \Home\Services\AppdatabkService();
        $res = $appService->bkTechList($gameId);
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
            $data['msg'] = $this->errorArr['102'];
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

      /**
      * 篮球阵容界面数据
      * @return json
      */
    public function bkSquad(){
        $gameId = !empty($_REQUEST['gameId'])? $_REQUEST['gameId'] : null;
        if(!$gameId){
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 101;
            $data['msg'] = $this->errorArr['101'];
            $this->ajaxReturn($data);
        }
        //$appService = new \Home\Services\AppdatabkService();
        $appService = new AppbkService();
        $res = $appService->bkSquadList($gameId);
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
            $data['msg'] = $this->errorArr['102'];
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
      * 篮球指数界面数据
      * @return json
      */
    public function bkchodds(){
        $appService = new \Home\Services\AppdatabkService();
        $res = $appService->bkChoddsList();
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
            $data['msg'] = $this->errorArr['102'];
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

     /**
     * 根据赛事ID获取球队logo地址
     * @return json
     */
    public function teamLogo()
    {
        $gameId = !empty($_REQUEST['gameId'])? $_REQUEST['gameId'] : null;
        if(empty($gameId))
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 101;
            $data['msg'] = $this->errorArr['101'];
            $this->ajaxReturn($data);
        }

        $appService = new \Home\Services\AppdatabkService();
        $res = $appService->getTeamLogo($gameId,true);
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
            $data['msg'] = $this->errorArr['102'];
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
     +------------------------------------------------------------------------------
     * 以下开始为app篮球3.0
     +------------------------------------------------------------------------------
    */

     /**
     * 篮球动画直播接口
     * @return json
     */
    public function animate()
    {

        $gameId = !empty($_REQUEST['gameId'])?$_REQUEST['gameId']:null;
        $flashId = !empty($_REQUEST['flashId'])?$_REQUEST['flashId']:null;
        $uptime = isset($_REQUEST['uptime'])?$_REQUEST['uptime']:0;   //最后游戏时间

        $appService = new \Home\Services\AppdatabkService();
        $res = $appService->getAnimate($gameId,$uptime);
        if($res === false)
        {
            $data['status']  = 0;
        }
        else
        {
            $data = $res;
            $data['status']  = 1;

        }
        $this->ajaxReturn($data);
    }

    /**
     * 篮球动画完成数据接口
     * @return json
     */
    public function aniOver()
    {
        $gameId = !empty($_REQUEST['gameId'])?$_REQUEST['gameId']:null;
        $flashId = !empty($_REQUEST['flashId'])?$_REQUEST['flashId']:null;
        $uptime = isset($_REQUEST['uptime'])?$_REQUEST['uptime']:0;   //最后游戏时间

        $appService = new \Home\Services\AppdatabkService();
        $res = $appService->getFlashOver($gameId,$uptime);
        if($res === false)
        {
            $data['status']  = 0;
        }
        else
        {
            $data = $res;
            $data['status']  = 1;

        }
        $this->ajaxReturn($data);
    }

     /**
     +------------------------------------------------------------------------------
     * 以下开始为app篮球4.0
     +------------------------------------------------------------------------------
    */

    /**
     * 球队资料接口
     * @return json
     */
    public function teamData()
    {
        $teamId = !empty($_REQUEST['teamId'])?$_REQUEST['teamId']:null;
        $unionId = !empty($_REQUEST['unionId'])?$_REQUEST['unionId']:null;

        $appService = new \Home\Services\AppdatabkService();
        $res = $appService->getTeamData($teamId,$unionId);
        if($res === false)
        {
            $data['status']  = 0;
            $data['ErrorCode'] = 101;
            $data['msg'] = $this->errorArr['101'];
        }
        else
        {
            $data = $res;
            $data['status']  = 1;
            $data['ErrorCode'] = 102;
            $data['msg'] = $this->errorArr['102'];
        }
        $this->ajaxReturn($data);
    }
}