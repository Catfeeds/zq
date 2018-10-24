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
class AppBkController extends Controller
{
    private $secretKey = 'quancaiappppa';
    public $param = null;

    public function _initialize()
    {
        $this->param = getParam(); //获取传入的参数

        if ($this->param['nosign'] != C('nosignStr') && ACTION_NAME != 'animate'  && ACTION_NAME != 'aniOver')
        {
            $this->verifySignature();  //校验签名
        }
    }

    /**
     * 返回接口数据
     * @param  array/int    $data       要返回的数据
     * @param  int          $msgCode    指定提示信息的状态码
     * @param  string       $type       返回数据的格式 json xml...
     */
    function ajaxReturn($data,$msgCode='',$type='')
    {
        if (is_array($data))
        {
            $code = 200;
        }
        else
        {
            $code = $data;
            $data = '';
        }

        $msgCode = $msgCode ?: $code;
        parent::ajaxReturn($data,$type);
    }

    //校验签名
    public function verifySignature()
    {
        //验证参数和请求的时间
        if (!$this->param['t'] || !$this->param['sign'] || $this->param['t'] < time() - 1800 || $this->param['t'] > time() + 1800)
            $this->ajaxReturn(array('status'=>0,'ErrorCode'=>101,'data'=>null));

        //验证签名
        import('Vendor.Signature.SignatureHelper');
        $signObj = new \SignatureHelper();

        $params = array();

        foreach ($this->param as $key => $value)
        {
            if($key != 'sign' && strpos($key, '/') === false && $value !== '' && $value !== false)
            {
                $params[$key] = $signObj->urlDecode($value);
            }
        }

        if(!$signObj->verifySignature($params, $this->param['sign'], $this->secretKey))
            $this->ajaxReturn(array('status'=>0,'ErrorCode'=>403,'data'=>null));
    }

    /**
      * 篮球当日赛事
      * @return json
      */
    public function bk()
    {
        $unionId = !empty($_REQUEST['unionId'])? $_REQUEST['unionId'] : null;
        $subId = !empty($_REQUEST['subId'])? $_REQUEST['subId'] : null;
        $appService = new \Home\Services\AppdatabkService();
        $res = $appService->bktodayList($unionId,$subId);
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 103;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
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
            $data['ErrorCode'] = 103;
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
        $companyId = !empty($_REQUEST['companyId'])? $_REQUEST['companyId'] : 2;
        $appService = new \Home\Services\AppdatabkService();
        $res = $appService->getbkodds($companyId);
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 103;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }
    /**
     * 篮球历史总赔率
     * @return json
     */
    public function bkMatchOdds()
    {
        $gameId = !empty($_REQUEST['gameId'])? $_REQUEST['gameId'] : null;
        $type = !empty($_REQUEST['type'])? $_REQUEST['type'] : '1';
        if(!$gameId){
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
            $this->ajaxReturn($data);
        }
        $appService = new \Home\Services\AppdatabkService();
        $res = $appService->getbkMatchOdds($gameId,$type);
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 103;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
     * 篮球历史总赔率
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
            $data['ErrorCode'] = 102;
            $this->ajaxReturn($data);
        }
        $appService = new \Home\Services\AppdatabkService();
        $res = $appService->getbkOddsHistory($gameId,$companyId,$type);
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 103;
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
            $data['ErrorCode'] = 102;
            $this->ajaxReturn($data);
        }
        $appService = new \Home\Services\AppdatabkService();
        $res = $appService->bkOverList($date);
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 103;
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
            $data['ErrorCode'] = 102;
            $this->ajaxReturn($data);
        }
        $appService = new \Home\Services\AppdatabkService();
        $res = $appService->bkFutureList($date);
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 103;
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
            $data['ErrorCode'] = 102;
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
            $data['ErrorCode'] = 103;
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
            $data['ErrorCode'] = 102;
            $this->ajaxReturn($data);
        }
        $appService = new \Home\Services\AppdatabkService();
        $res = $appService->bkSituationList($gameId);
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 103;
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
            $data['ErrorCode'] = 102;
            $this->ajaxReturn($data);
        }
        $appService = new \Home\Services\AppdatabkService();
        $res = $appService->bkTechList($gameId);
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 103;
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
            $data['ErrorCode'] = 102;
            $this->ajaxReturn($data);
        }
        $appService = new \Home\Services\AppdatabkService();
        $res = $appService->bkSquadList($gameId);
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 103;
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
            $data['ErrorCode'] = 103;
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
            $data['ErrorCode'] = 102;
            $this->ajaxReturn($data);
        }

        $appService = new \Home\Services\AppdatabkService();
        $res = $appService->getTeamLogo($gameId);
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 103;
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

}