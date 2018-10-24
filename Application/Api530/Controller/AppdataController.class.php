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
use Api530\Services\AppfbService;

class AppdataController extends PublicController
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
     * 当日所有赛事
     * @return json 当日赛事数据
     */
    public function fb()
    {
        $unionId = !empty($_REQUEST['unionId'])? $_REQUEST['unionId'] : null;
        $subId = !empty($_REQUEST['subId'])? $_REQUEST['subId'] : null;
        $platform = !empty($_REQUEST['platform'])? $_REQUEST['platform'] : '';
        /*$appService = new \Home\Services\AppdataService();
        $res = $appService->fbtodayList($unionId,$subId,$key);*/

        $fbService = new \Home\Services\AppfbService();
        $res = $fbService->fbtodayList($unionId,$subId,$platform);

        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }

        $this->ajaxReturn($data);
    }

     /**
     * 当日滚球赛事
     * @return json 当日滚球赛事数据
     */
    public function fbRoll()
    {
        $unionId = !empty($_REQUEST['unionId'])?$_REQUEST['unionId']:null;
        $subId = !empty($_REQUEST['subId'])? $_REQUEST['subId'] : null;
        $key = !empty($_REQUEST['key'])? $_REQUEST['key'] : 'yes';
        //$appService = new \Home\Services\AppdataService();
        //$res = $appService->fbRollList($unionId, $subId, $key);
        $fbService = new \Home\Services\AppfbService();
        $res = $fbService->fbRollList( $unionId,$subId);

        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
     * 当日完场赛事
     * @return json 当日完场赛事数据
     */
    public function fbOver()
    {
        $date = !empty($_REQUEST['date'])?$_REQUEST['date']:null;
        $unionId = !empty($_REQUEST['unionId'])?$_REQUEST['unionId']:null;
        $subId = !empty($_REQUEST['subId'])? $_REQUEST['subId'] : null;
        $key = !empty($_REQUEST['key'])? $_REQUEST['key'] : 'yes';
        //$appService = new \Home\Services\AppdataService();
        //$res = $appService->fbOverList($date,$unionId,$subId,$key);
        $fbService = new \Home\Services\AppfbService();
        $res = $fbService->fbOverList($date,$unionId,$subId);

        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
     * 近期日赛事
     * @return json 近期日赛事数据
     */
    public function fbFixtureList()
    {
        $date = !empty($_REQUEST['date'])?$_REQUEST['date']:null;
        $unionId = !empty($_REQUEST['unionId'])?$_REQUEST['unionId']:null;
        $subId = !empty($_REQUEST['subId'])? $_REQUEST['subId'] : null;
        $key = !empty($_REQUEST['key'])? $_REQUEST['key'] : 'yes';
        //$appService = new \Home\Services\AppdataService();
        //$res = $appService->fbFixtureList($date,$unionId,$subId,$key);
        $fbService = new \Home\Services\AppfbService();
        $res = $fbService->fbFixtureList($date,$unionId,$subId);

        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
     * 即时指数界面数据接口
     * @param  int $unionId 赛事ID，多个以‘,’隔开
     * @param  int $subId   级别ID，多个以‘,’隔开
     * @return json 即时指数数据
     */
    public function fbInstant()
    {
        $unionId = !empty($_REQUEST['unionId'])?$_REQUEST['unionId']:null;
        $subId = !empty($_REQUEST['subId'])? $_REQUEST['subId'] : null;
        $key = !empty($_REQUEST['key'])? $_REQUEST['key'] : 'yes';
        // $appService = new \Home\Services\AppdataService();
        // $res = $appService->fbInstant($unionId,$subId,$key);
        $fbService = new \Home\Services\AppfbService();
		$res = $fbService->fbInstant($unionId,$subId);
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
     * 即时指数界面即时赔率变化接口
     * @return json
     */
    public function chodds()
    {
        $gameId = !empty($_REQUEST['gameId'])?$_REQUEST['gameId']:null;
        /*$appService = new \Home\Services\AppdataService();
        $res = $appService->getChodds($gameId);*/

        $fbService = new \Home\Services\AppfbService();
        $res = $fbService->getChoddsB($gameId);

        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
     * 今日赛事变化数据，比分、红黄牌，比赛时间
     * @return json
     * 已弃用
     */
    public function change()
    {
	    // 已弃用接口
	    /*
        $key = !empty($_REQUEST['key'])? $_REQUEST['key'] : 'yes';
        $appService = new \Home\Services\AppdataService();
        //$res = $appService->getChange($key);
        $res = $appService->getChangeB();  //数据库取数据
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = [];
            $data['ErrorCode'] = 102;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
	    */
    }

    /**
     * 赔率变化
     * @param  int  公司ID
     * @return json
     * 已弃用
     */
    public function goal()
    {
	    // 已弃用接口
	    /*
        $companyID = !empty($_REQUEST['id'])?$_REQUEST['id']:null;
        $key = !empty($_REQUEST['key'])? $_REQUEST['key'] : 'yes';
        if(empty($companyID))
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 101;
            $this->ajaxReturn($data);
        }

        $appService = new \Home\Services\AppdataService();
        //$res = $appService->getGoal($companyID,$key);
        $res = $appService->getGoalB($companyID,$key);   //数据库取数据
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
	    */
    }

    /**
     * 赔率变化（弃用）
     * @param  int  公司ID
     * @return json
     * 已弃用
     */
    public function goals()
    {
	    // 已弃用接口
	    /*
        $companyID = !empty($_REQUEST['id'])?$_REQUEST['id']:null;
        $key = !empty($_REQUEST['key'])? $_REQUEST['key'] : 'yes';
        if(empty($companyID))
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 101;
            $this->ajaxReturn($data);
        }

        $appService = new \Home\Services\AppdataService();
        $res = $appService->getGoals($companyID,$key);
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
	    */
    }

    /**
     * 各公司赔率历史变化
     * @param  gameId  赛事ID
     * @param  int  公司ID
     * @param  type  类别：1亚，2欧，3大小
     * @return json
     */
    public function goalHistory()
    {
        $companyID = !empty($_REQUEST['id'])?$_REQUEST['id']:null;
        $gameId = !empty($_REQUEST['gameId'])?$_REQUEST['gameId']:null;
        $type = !empty($_REQUEST['type'])?$_REQUEST['type']:1;
        if(empty($companyID) || empty($gameId))
        {
            $data['status']  = 0;
            $data['data'] = [];
            $data['ErrorCode'] = 101;
            $this->ajaxReturn($data);
        }

        $appService = new AppfbService();
        //$res = $appService->getAbhisByIdMon($gameId,$companyID,$type);
        $res = $appService->getAbhisByIdMonT($gameId,$companyID,$type);

        $res = errorMsgToNull($res);

        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = [];
            $data['ErrorCode'] = 102;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
     * app滚球界面赔率历史变化（SB公司）
     * @param  gameId  赛事ID
     * @return json
     */
    public function goalRoll()
    {
        $gameId = I('gameId','','int');
        if(empty($gameId))
        {
            $this->ajaxReturn(101);
        }

        $fbService = new AppfbService();
        $res = $fbService->getSBhisOdds($gameId);

        if($res === false)
        {
            $this->ajaxReturn(102);
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
     * 根据赛事ID获取最新赔率数据
     * @return json
     */
    public function oddsById()
    {
        $gameId = !empty($_REQUEST['gameId'])?$_REQUEST['gameId']:null;
        $type = !empty($_REQUEST['type'])? $_REQUEST['type'] : '2';

        $fbService = new \Home\Services\AppfbService();
        $res = $fbService->getOddsById($gameId,$type);
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);

    }

    /**
     * 总赔率数据————全场、半场亚盘、欧盘、大小的即时、滚球、初盘赔率
     * @return json
     * 已弃用
     */
    public function oddsDataDiv()
    {
	    // 已弃用接口
	    /*
        $gameId = !empty($_REQUEST['gameId'])?$_REQUEST['gameId']:null;
        $type = !empty($_REQUEST['type'])? $_REQUEST['type'] : '2';
        $key = !empty($_REQUEST['key'])? $_REQUEST['key'] : 'yes';
        $appService = new \Home\Services\AppdataService();
        $res = $appService->getOdds($gameId,$type,$key);
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
	    */
    }

    /**
     * 滚球界面赔率接口(比赛中的赛事)————全场、半场亚盘、欧盘、大小的即时、滚球、初盘赔率
     * @return json
     */
    public function oddsRoll()
    {
        $gameId = !empty($_REQUEST['gameId'])?$_REQUEST['gameId']:null;
        $type = !empty($_REQUEST['type'])? $_REQUEST['type'] : '2';

        // $appService = new \Home\Services\AppdataService();
        // $res = $appService->getOddsRoll($gameId,$type);

        $fbService = new \Home\Services\AppfbService();
        $res = $fbService->getOddsRoll($gameId,$type);

        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
     * 总赔率数据————全场、半场亚盘、欧盘、大小的即时、滚球赔率（弃用）
     * @return json
     */
    public function oddsDynamic()
    {
        $gameId = !empty($_REQUEST['gameId'])?$_REQUEST['gameId']:null;
        $appService = new \Home\Services\AppdataService();
        $res = $appService->getOddsDynamic($gameId);
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
     * 总赔率数据————全场、半场亚盘、欧盘、大小的初盘赔率（弃用）
     * @return json
     */
    public function oddsStatic()
    {
        $gameId = !empty($_REQUEST['gameId'])?$_REQUEST['gameId']:null;
        $appService = new \Home\Services\AppdataService();
        $res = $appService->getOddsStatic($gameId);
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
     * 当天赛事现场数据————技术统计（红黄牌、角球、射门次数等）
     * @return json
     */
    public function detail()
    {
        $gameId = !empty($_REQUEST['gameId'])?$_REQUEST['gameId']:null;
        $appService = new \Home\Services\AppdataService();
        //$res = $appService->getDetailApp($gameId);
        $res = $appService->getDetailAppB($gameId);   //数据库取值
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
     * 当天赛事现场数据————技术统计（红黄牌、角球、射门次数等）
     * @return json
     * 已弃用
     */
    public function skill()
    {
	    // 已弃用接口
	    /*
        $gameId = !empty($_REQUEST['gameId'])?$_REQUEST['gameId']:null;
        $appService = new \Home\Services\AppdataService();
        //$res = $appService->getSkillApp($gameId);
        $res = $appService->getSkillAppB($gameId);
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
	    */
    }

     /**
     * 球队阵容
     * @param  int  赛事ID
     * @return json
     */
    public function lineup()
    {
        $gameId = !empty($_REQUEST['gameId'])?$_REQUEST['gameId']:null;
        $appService = new \Home\Services\AppdataService();
        $res = $appService->getLineup($gameId);
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
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
     * @param  int  gameId 赛事ID
     * @param  int  lang   简繁体：1简体、2繁体
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
            $this->ajaxReturn($data);
        }

        if($lang != 1) $lang = 2;

        $appService = new \Home\Services\AppfbService();
        $res = $appService->getAnaForFile($gameId,$lang,$is_corner=1);

        /*$value = S('analysis_'.$gameId.'_'.$lang,'',array('type'=>'file','expire'=>600));
        $value = false;
        if($value === false)
        {
            $fb = new \Home\Services\AppfbService();
            $res = $fb->getAnalysis($gameId ,$lang);
            S('analysis_'.$gameId.'_'.$lang,$res,array('type'=>'file','expire'=>600));
        }
        else
        {
            $res = $value;
        }*/

        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
     * 亚赔界面（各公司初盘指数、即时指数）
     * @return json
     */
    public function asianOdds()
    {
        $gameId = !empty($_REQUEST['gameId'])?$_REQUEST['gameId']:null;
        $key = !empty($_REQUEST['key'])? $_REQUEST['key'] : 'no';
        $data = [];
        if(empty($gameId))
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 101;
            $this->ajaxReturn($data);
        }

        $appService = new AppfbService();
        //$res = $appService->getAbByIdMon($gameId,1);
        $res = $appService->getAbByIdMonT($gameId,1);

        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
     * 大小界面（各公司初盘指数、即时指数）
     * @return json 大小赔率数据
     */
    public function ballOdds()
    {
        $gameId = !empty($_REQUEST['gameId'])?$_REQUEST['gameId']:null;
        $key = !empty($_REQUEST['key'])? $_REQUEST['key'] : 'no';
        $data = [];
        if(empty($gameId))
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 101;
            $this->ajaxReturn($data);
        }

        $appService = new AppfbService();
        //$res = $appService->getAbByIdMon($gameId,2);
        $res = $appService->getAbByIdMonT($gameId,2);

        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
     * 欧赔界面（各公司初盘指数、即时指数）
     * @return json 欧盘赔率数据
     */
    public function europeOdds()
    {
        $gameId = !empty($_REQUEST['gameId'])?$_REQUEST['gameId']:null;
        $key = !empty($_REQUEST['key'])? $_REQUEST['key'] : 'yes';
        $data = [];
        if(empty($gameId))
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 101;
        }

        //$appService = new \Api510\Services\AppfbService();
        //$res = $appService->getAllOdds($gameId,2);
        $appService = new AppfbService();
        $res = $appService->getEuroByIdMon($gameId);

        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
     * 欧赔界面历史赔率接口（百家欧赔历史）
     * @return json 欧盘赔率数据
     */
    public function euroHistory()
    {
        $gameId = !empty($_REQUEST['gameId'])?$_REQUEST['gameId']:null;
        $company = !empty($_REQUEST['company'])?$_REQUEST['company']:null;
        if(empty($gameId) || !$company)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 101;
            $this->ajaxReturn($data);
        }
        //$appService = new \Home\Services\AppfbService();
        //$company=  urldecode($company);
        //$res = $appService->getEuroHistory($gameId,$company);

        $appService = new AppfbService();
        $res = $appService->getEurohisByIdMon($gameId,$company);

        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
     * 洲数据
     * @return json
     */
    public function continent()
    {
        $appService = new \Home\Services\AppdataService();
        $res = $appService->getContinent();

        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
     * 国家数据
     * @return json
     */
    public function country()
    {
        $continentId = isset($_REQUEST['continentId'])?$_REQUEST['continentId']:null;
        $appService = new \Home\Services\AppdataService();
        $res = $appService->getCountry($continentId);

        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
     * 联赛数据
     * @return json
     */
    public function union()
    {
        $countryId = !empty($_REQUEST['countryId'])?$_REQUEST['countryId']:null;
        $unionId = !empty($_REQUEST['unionId'])?$_REQUEST['unionId']:null;

        $appService = new \Home\Services\AppdataService();
        $res = $appService->getUnion($unionId,$countryId);

        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
     * 资料库联赛总积分升降级颜色其它数据
     * @return json
     */
    public function leagueIntOther()
    {
        $unionId = !empty($_REQUEST['unionId'])?$_REQUEST['unionId']:null;

        if(empty($unionId))
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 101;
            $this->ajaxReturn($data);
        }
        $appService = new \Home\Services\AppdataService();
        $res = $appService->getUpdown($unionId);
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
     * 资料库联赛总积分
     * @return json
     */
    public function leagueIntegral()
    {
        $unionId = !empty($_REQUEST['unionId'])?$_REQUEST['unionId']:null;
        $key = !empty($_REQUEST['key'])? $_REQUEST['key'] : 'yes';
        if(empty($unionId))
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 101;
            
            $this->ajaxReturn($data);
        }
        $appService = new \Home\Services\AppdataService();
        $res = $appService->getLeagueIntegral($unionId,$key);
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
            
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

     /**
     * 资料库联赛赛程
     * @return json
     */
    public function leagueMatch()
    {
        $unionId = !empty($_REQUEST['unionId'])?$_REQUEST['unionId']:null;
        $runId = !empty($_REQUEST['runId'])?$_REQUEST['runId']:null;
        $key = !empty($_REQUEST['key'])? $_REQUEST['key'] : 'yes';
        if(empty($unionId))
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 101;
            
            $this->ajaxReturn($data);
        }

        $appService = new \Home\Services\AppdataService();
        $res = $appService->getLeagueMatch($unionId,$runId,$key);
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
            
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
     * 资料库联赛让球盘路
     * @return json
     */
    public function letGoal()
    {
        $unionId = !empty($_REQUEST['unionId'])?$_REQUEST['unionId']:null;
        $key = !empty($_REQUEST['key'])? $_REQUEST['key'] : 'yes';
        if(empty($unionId))
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 101;
            
            $this->ajaxReturn($data);
        }
        $appService = new \Home\Services\AppdataService();
        $res = $appService->getLetGoal($unionId,$key);
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
            
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
     * 资料库联赛大小盘路
     * @return json
     */
    public function bigSmall()
    {
        $unionId = !empty($_REQUEST['unionId'])?$_REQUEST['unionId']:null;
        $key = !empty($_REQUEST['key'])? $_REQUEST['key'] : 'yes';
        if(empty($unionId))
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 101;
            
            $this->ajaxReturn($data);
        }
        $appService = new \Home\Services\AppdataService();
        $res = $appService->getBigSmall($unionId,$key);
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
            
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
     * 资料库联赛射手榜
     * @return json
     */
    public function archer()
    {
        $unionId = !empty($_REQUEST['unionId'])?$_REQUEST['unionId']:null;
        $key = !empty($_REQUEST['key'])? $_REQUEST['key'] : 'yes';
        if(empty($unionId))
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 101;
            
            $this->ajaxReturn($data);
        }
        $appService = new \Home\Services\AppdataService();
        $res = $appService->getArcher($unionId,$key);

        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
            
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
     * 资料库杯赛积分
     * @return json
     */
    public function cupGroupIntegral()
    {
        $unionId = !empty($_REQUEST['unionId'])?$_REQUEST['unionId']:null;
        $key = !empty($_REQUEST['key'])? $_REQUEST['key'] : 'yes';
        if(empty($unionId))
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 101;
            
            $this->ajaxReturn($data);
        }

        $appService = new \Home\Services\AppdataService();
        $res = $appService->getCupGroupIntegral($unionId,$key);
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
            
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
     * 资料库杯赛积分
     * @return json
     */
    public function cupMatch()
    {
        $unionId = !empty($_REQUEST['unionId'])?$_REQUEST['unionId']:null;
        $runId = !empty($_REQUEST['runId'])?$_REQUEST['runId']:null;
        $key = !empty($_REQUEST['key'])? $_REQUEST['key'] : 'yes';
        if(empty($unionId))
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 101;
            
            $this->ajaxReturn($data);
        }

        $appService = new \Home\Services\AppdataService();
        $res = $appService->getCupMatch($unionId,$runId,$key);
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
            
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
            
            $this->ajaxReturn($data);
        }

        $appService = new \Home\Services\AppdataService();
        $res = $appService->getTeamLogo($gameId,true);
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
            
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

     /**
     * 文字直播
     * @return json
     */
    public function textliving()
    {
        $gameId = !empty($_REQUEST['gameId'])?$_REQUEST['gameId']:null;
        $web = !empty($_REQUEST['web'])?$_REQUEST['web']:2;
        $appService = new \Home\Services\AppfbService();
        $res = $appService->getTextliving($gameId, $web);
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
            
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
      * 足球必发数据
      * @return json
      */
    public function BifaValue()
    {
        $gameId = !empty($_REQUEST['gameId'])? $_REQUEST['gameId'] : null;
        if(!$gameId){
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 101;
            
            $this->ajaxReturn($data);
        }
        //$appService = new \Home\Services\AppdataService();
        //$res = $appService->BifaValue($gameId);

        $appService = new AppfbService();
        $res = $appService->getBifaValue($gameId);

        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }
    /**
      * 足球必发数据
      * @return json
      */
    public function BifaTrade(){
        $gameId = !empty($_REQUEST['gameId'])? $_REQUEST['gameId'] : null;
        $limit = !empty($_REQUEST['limit'])? $_REQUEST['limit'] : null;
        if(!$gameId){
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 101;
            
            $this->ajaxReturn($data);
        }
        $appService = new \Home\Services\AppdataService();
        $res = $appService->BifaTrade($gameId,$limit);
        //$appService = new AppfbService();
        //$res = $appService->getBifaTrade($gameId);

        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 102;
            
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

     /**
     * 动画直播接口
     * @return json
     */
    public function animate()
    {
        $gameId = !empty($_REQUEST['gameId'])?$_REQUEST['gameId']:null;
        $flashId = !empty($_REQUEST['flashId'])?$_REQUEST['flashId']:null;
        $uptime = isset($_REQUEST['uptime'])?$_REQUEST['uptime']:0;   //最后游戏时间

        $appService = new \Home\Services\AppfbService();
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
     * 动画完成数据接口
     * @return json
     */
    public function aniOver()
    {
        $gameId = !empty($_REQUEST['gameId'])?$_REQUEST['gameId']:null;
        $flashId = !empty($_REQUEST['flashId'])?$_REQUEST['flashId']:null;
        $uptime = isset($_REQUEST['uptime'])?$_REQUEST['uptime']:0;   //最后游戏时间

        $appService = new \Home\Services\AppfbService();
        $res = $appService->getFlashOver($gameId,$uptime);
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = [];
            $data['errorCode'] = 102;
            $data['emsg'] = '系统内部错误';
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
            $data['errorCode'] = 200;

        }
        $this->ajaxReturn($data);
    }

    /**
     * 获取动画mongodb表id
     * @return json
     */
    public function animateId()
    {
        $gameId = !empty($_REQUEST['gameId'])?$_REQUEST['gameId']:null;
        $type = !empty($_REQUEST['type'])?$_REQUEST['type']:1;

        $appService = new \Home\Services\AppfbService();
        $res = $appService->getAnimateId($gameId,$type);
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = [];
            $data['errorCode'] = 102;
            $data['emsg'] = '系统内部错误';
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
            $data['errorCode'] = 200;

        }
        $this->ajaxReturn($data);
    }

    /**
     +------------------------------------------------------------------------------
     * 以下开始为app足球3.0
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

        $appService = new \Home\Services\AppfbService();
        $res = $appService->getTeamData($teamId,$unionId);
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
     * 概率界面数据
     * @return json
     */
    public function probability()
    {
        $gameId = !empty($_REQUEST['gameId'])?$_REQUEST['gameId']:null;

        $appService = new \Home\Services\AppfbService();
        $res = $appService->getProbability($gameId);
        if($res === false)
        {
            $data['status']  = 0;
            $data['data']  = [];
            $data['errorCode'] = 102;
            
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
     * 赛事前瞻
     * @return json
     */
    public function preMatchinfo()
    {
        $gameId = !empty($_REQUEST['gameId'])?$_REQUEST['gameId']:null;
        $from = !empty($_REQUEST['from'])?$_REQUEST['from']:1;

        $appService = new \Home\Services\AppfbService();
        $res = $appService->getPreMatchinfo($gameId,$from);
        if($res === false)
        {
            $data['status']  = 0;
            $data['data']  = [];
            $data['errorCode'] = 102;
            
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
     * 以下开始为app足球5.0
     +------------------------------------------------------------------------------
    */

     /**
     * 实况
     * @return json
     */
    public function textSkill()
    {
        $gameId = !empty($_REQUEST['gameId'])?$_REQUEST['gameId']:null;
        if(empty($gameId))
        {
            $data['status']  = 0;
            $data['data']  = [];
            $data['errorCode'] = 101;
            
        }

        $appService = new \Home\Services\AppfbService();
        $res = $appService->textSkill($gameId);

        if($res === false)
        {
            $data['status']  = 0;
            $data['data']  = [];
            $data['errorCode'] = 102;
            
        }
        else
        {
            $data['status']  = 1;
            $data['errorCode'] = 200;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

     /**
     * 实况
     * @return json
     */
    public function strength()
    {
        $gameId = !empty($_REQUEST['gameId'])?$_REQUEST['gameId']:null;
        if(empty($gameId))
        {
            $data['status']  = 0;
            $data['data']  = [];
            $data['errorCode'] = 101;
            
        }

        //$appService = new \Home\Services\AppfbService();
        //$res = $appService->getStrength($gameId);
        $appService = new AppfbService();  //mongodb
        $res = $appService->getStrengthMon($gameId);

        if($res === false)
        {
            $data['status']  = 0;
            $data['data']  = [];
            $data['errorCode'] = 102;
            
        }
        else
        {
            $data['status']  = 1;
            $data['errorCode'] = 200;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }
    
    
    public function strengthTest()
    {
	    $gameId = !empty($_REQUEST['gameId'])?$_REQUEST['gameId']:null;
	    if(empty($gameId))
	    {
		    $data['status']  = 0;
		    $data['data']  = [];
		    $data['errorCode'] = 101;
	    }
	
	    $appService = new AppfbService();  //mongodb
	    $appService->getStrengthMontest($gameId);
    }
    

    /**
     * 赛况走势
     * @return json
     */
    public function gameTrend()
    {
        $gameId  = I('gameId','','int');
        if(empty($gameId)){
            $this->ajaxReturn(101);
        }
        $mongodb = mongoService();
        //365动画数据统计
        $_map = ['$or' => [
            ['jbh_id' => (int)$gameId],
            ['jb_id' => (int)$gameId]],
        ];
        $game_365 = $mongodb->select('fb_game_365',$_map,['statistics','events_statistics','game_time']);
        $donghua      = $game_365[0]['events_statistics']; //进攻数据
        $statistics   = $game_365[0]['statistics']; //进攻数据数量
        $homeCount    = $statistics['home_team'];   //主队数量
        $awayCount    = $statistics['away_team'];   //客队数量

        $shootInside = $shootOutside = $dangerAttack = $attack = [];
        $arr1 = $arr2 = $arr3 = $arr4 = $arr5 = $arr6 = $arr7 = $arr8 = [];
        foreach ($donghua as $k => $v) {
            $home_team = $v['home_team'];
            $away_team = $v['away_team'];
            if($home_team['shootInside'] > 0){
                //主队射正数据
                if(!in_array($home_team['shootInside'], $arr1)){
                    $shootInside['home'][] = ['t'=>$k,'n'=>(int)$home_team['shootInside']];
                    $arr1[] = $home_team['shootInside'];
                }
            }
            if($away_team['shootInside'] > 0){
                //客队射正数据
                if(!in_array($away_team['shootInside'], $arr2)){
                    $shootInside['away'][] = ['t'=>$k,'n'=>(int)$away_team['shootInside']];
                    $arr2[] = $away_team['shootInside'];
                }
            }
            if($home_team['shootOutside'] > 0){
                //主队射偏数据
                if(!in_array($home_team['shootOutside'], $arr3)){
                    $shootOutside['home'][] = ['t'=>$k,'n'=>(int)$home_team['shootOutside']];
                    $arr3[] = $home_team['shootOutside'];
                }
            }
            if($away_team['shootOutside'] > 0){
                //客队射偏数据
                if(!in_array($away_team['shootOutside'], $arr4)){
                    $shootOutside['away'][] = ['t'=>$k,'n'=>(int)$away_team['shootOutside']];
                    $arr4[] = $away_team['shootOutside'];
                }
            }
            if($home_team['dangerAttack'] > 0){
                //主队危险进攻数据
                if(!in_array($home_team['dangerAttack'], $arr5)){
                    $dangerAttack['home'][] = ['t'=>$k,'n'=>(int)$home_team['dangerAttack']];
                    $arr5[] = $home_team['dangerAttack'];
                }
            }
            if($away_team['dangerAttack'] > 0){
                //客队危险进攻数据
                if(!in_array($away_team['dangerAttack'], $arr6)){
                    $dangerAttack['away'][] = ['t'=>$k,'n'=>(int)$away_team['dangerAttack']];
                    $arr6[] = $away_team['dangerAttack'];
                }
            }
            if($home_team['attack'] > 0){
                //主队危险进攻数据
                if(!in_array($home_team['attack'], $arr7)){
                    $attack['home'][] = ['t'=>$k,'n'=>(int)$home_team['attack']];
                    $arr7[] = $home_team['attack'];
                }
            }
            if($away_team['attack'] > 0){
                //客队危险进攻数据
                if(!in_array($away_team['attack'], $arr8)){
                    $attack['away'][] = ['t'=>$k,'n'=>(int)$away_team['attack']];
                    $arr8[] = $away_team['attack'];
                }
            }
        }

        //主队矩形条走势数据
        $homeData = $this->attackCount($shootInside,$shootOutside,$dangerAttack,1);
        //客队矩形条走势数据
        $awayData = $this->attackCount($shootInside,$shootOutside,$dangerAttack,2);
        //获取事件
        $gameDetail = $this->getTrendDetail($gameId);

        //比赛进行时间,比赛进行中才显示进度条
        $game_time  = $game_365[0]['game_time'] ?: '-1';
        //矩形条数据走势图
        $data['trend'] = [
            'home'       => $homeData,
            'away'       => $awayData,
            'homeDetail' => $gameDetail['homeDetail'],
            'awayDetail' => $gameDetail['awayDetail'],
            'time'       => $game_time,
        ]; 
        //射正走势图
        $shootInside['ratio']  = $homeCount['shootInside'].':'.$awayCount['shootInside'];
        //射偏走势图
        $shootOutside['ratio'] = $homeCount['shootOutside'].':'.$awayCount['shootOutside'];
        //危险进攻走势图
        $dangerAttack['ratio'] = $homeCount['dangerAttack'].':'.$awayCount['dangerAttack'];
        //进攻走势图
        $attack['ratio']       = $homeCount['attack'].':'.$awayCount['attack'];
        
        $data['shootIn']  = $shootInside;
        $data['shootOut'] = $shootOutside;
        $data['dAttack']  = $dangerAttack;
        $data['attack']   = $attack;
        $this->ajaxReturn(['status'=>1,'data'=>$data]);
    }

    //获取捷报数据事件
    public function getTrendDetail($gameId){
        $mongodb = mongoService();
        $shijian = $mongodb->select('fb_game',['game_id'=>$gameId],['home_team_id','away_team_id','corner_sb','detail']);
        $home_team_id = $shijian[0]['home_team_id']; //主队id
        $away_team_id = $shijian[0]['away_team_id']; //客队id
        $corner_sb    = $shijian[0]['corner_sb'][3]; //角球
        $detail       = $shijian[0]['detail'];       //事件
        //角球
        $homeDetail = $awayDetail = $homeSort = $awaySort = [];
        foreach ($corner_sb as $k => $v) {
            //为空跳过
            if(empty($v)) continue;

            if($v[0] == $home_team_id){
                //主队
                $homeDetail[] = ['t'=>(int)$v[1],'l'=>99];
                $homeSort[]   = (int)$v[1];
            }else{
                //客队
                $awayDetail[] = ['t'=>(int)$v[1],'l'=>99];
                $awaySort[]   = (int)$v[1];
            }
        }
        //事件
        foreach ($detail as $k => $v) {
            if(in_array($v[1], [1,2,3,4,5,7,8,9,11])){
                //1:进球,2:红牌,3:黄牌,7:点球,8:乌龙,11:换人
                if(in_array($v[1], [4,5])){
                    $v[1] == 11;//换入和换出转为换人
                }
                if($v[1] == 9){
                    $v[1] == 2;//两黄变红转为红牌
                }
                if($v[0] == 1){
                    //主队
                    $homeDetail[] = ['t'=>(int)$v[2],'l'=>(int)$v[1]];
                    $homeSort[]   = (int)$v[2];
                }else{
                    //客队
                    $awayDetail[] = ['t'=>(int)$v[2],'l'=>(int)$v[1]];
                    $awaySort[]   = (int)$v[2];
                }
            }
        }
        array_multisort($homeSort,SORT_ASC,$homeDetail);
        array_multisort($awaySort,SORT_ASC,$awayDetail);
        return ['homeDetail'=>$homeDetail,'awayDetail'=>$awayDetail];
    }

    //危险进攻数据统计
    public function attackCount($shootInside,$shootOutside,$dangerAttack,$type=1){
        $team1 = $type == 1 ? $shootInside['home']  : $shootInside['away']; //射正
        $team2 = $type == 1 ? $shootOutside['home'] : $shootOutside['away'];//射偏
        $team3 = $type == 1 ? $dangerAttack['home'] : $dangerAttack['away'];//危险进攻
        
        foreach ($team1 as $k => $v) {
            $team1[$k]['l'] = 3;//射正为3
        }
        foreach ($team2 as $k => $v) {
            $team2[$k]['l'] = 2;//射偏为2
        }
        foreach ($team3 as $k => $v) {
            $team3[$k]['l'] = 1;//危险进攻1
        }

        //合并数据
        $dataArr = array_merge_recursive($team1,$team2,$team3);
        $sort1 = $sort2 = [];
        foreach ($dataArr as $k => $v) {
            $sort1[] = $v['t']; //时间
            $sort2[] = $v['l']; //类型（矩形条长度计算：射正为3，射偏为2，危险进攻1）
        }
        //先按时间顺序，然后类型顺序
        array_multisort($sort1,SORT_ASC,$sort2,SORT_ASC,$dataArr);
        $data = $sign = [];
        foreach ($dataArr as $k => $v) {
            if(!in_array($v['t'], $sign)){
                $data[] = ['t'=>$v['t'],'l'=>$v['l']];
            }
            $sign[] = $v['t']; //标记是否已取用
        }
        return $data;
    }

    /**
     * 路珠走势
     * @return json
     */
    public function teamLzTrend()
    {
        try{
            $gameId = I('gameId', '', 'int');
            $data = [];

            if (empty($gameId)) {
                throw new \Think\Exception(0, 101);
            }

            $mongodb = mongoService();

            //赛程信息
            $gameInfo = $mongodb->fetchRow('fb_game',
                ['game_id' => $gameId],
                ['game_id', 'home_team_id', 'away_team_id', 'home_team_name', 'away_team_name']
            );
            $gameInfo['home_team_name'] = (string)$gameInfo['home_team_name'][0];
            $gameInfo['away_team_name'] = (string)$gameInfo['away_team_name'][0];

            //路珠走势
            $teamIds = ['home' => $gameInfo['home_team_id'], 'away' => $gameInfo['away_team_id']];

            foreach ($teamIds as $tk => $team_id) {
                $tabMap = [
                    'asia_exp_let_all' => ['让球综合', 'tab_all', 0],
                    'asia_exp_let'=> ['让球盘', 'tab_letgoal', 1],
                    'asia_exp_no_let'=> ['受让盘', 'tab_recgoal', 2],
                    'asia_exp_let_home'=> ['主场', 'tab_home', 3],
                    'asia_exp_let_away'=> ['客场', 'tab_away', 4],

                    'asia_bigsmall_all'=> ['大小综合', 'tab_all', 0],
                    'asia_bigsmall_let'=> ['让球盘', 'tab_letgoal', 1],
                    'asia_bigsmall_no_let'=> ['受让盘', 'tab_recgoal', 2],
                    'asia_bigsmall_home'=> ['主场', 'tab_home', 3],
                    'asia_bigsmall_away'=> ['客场', 'tab_away', 4],
                ];

                if ($team_id) {
                    //获取球队路珠
                    $teamInfo = $mongodb->fetchRow('fb_team', ['team_id' => (int)$team_id], ['team_id', 'team_luzhu']);
                    $teamLuzhu = $teamInfo['team_luzhu'];

                    if($teamLuzhu['luzhu']){
                        $data[$tk] = $this->setCol($teamLuzhu);
                        //返回动态菜单tab

                        $goodTrend1 = $goodTrend2 = [];
                        foreach($teamLuzhu['haolu'] as $k => $v){
                            if(strpos($k, 'asia_exp') !== false){
                                $goodTrend1[] = ['goodTrend' => $v, 'name' => $tabMap[$k][1], 'type' => $k];
                            }else{
                                $goodTrend2[] = ['goodTrend' => $v, 'name' => $tabMap[$k][1], 'type' => $k];
                            }
                        }

                        //动态返回tab菜单
                        $tab_list = array_keys($teamLuzhu['luzhu']);
                        $tab_list_gt = $teamLuzhu['haolu'];
                        $menuTabs = [];
                        foreach($tab_list as $k => $v){
                            $mn = ['name' => $tabMap[$v][0], 'good_rule' => $tab_list_gt[$v], 'index' =>  $tabMap[$v][1]];
                            if(strpos($v, 'asia_exp') !== false){
                                $menuTabs['asia_exp'][$tabMap[$v][2]] = $mn;
                                $sort1[] = $tabMap[$v][2];
                            }else{

                                $menuTabs['asia_bigsmall'][$tabMap[$v][2]] = $mn;
                                $sort2[] = $tabMap[$v][2];
                            }
                        }
                        $asia_exp_menu = $menuTabs['asia_exp'];
                        $asia_bigsmall_menu = $menuTabs['asia_bigsmall'];
                        ksort($asia_exp_menu);
                        ksort($asia_bigsmall_menu);

                        $menuTabs['asia_exp'] = array_values($asia_exp_menu);
                        $menuTabs['asia_bigsmall'] = array_values($asia_exp_menu);

                        $data[$tk]['menu_tabs'] = $menuTabs;
                    }
                }
            }
            $data['ruleLayer'] = DOMAIN == 'qw.com' ?  'http://183.3.152.226:8099/' : SITE_URL.$_SERVER['HTTP_HOST'];
            $data['ruleLayer'] .= '/Public/Api/Home/roadRule/images/rule.png?1234563';
            $return['status']  = 1;
            $return['errorCode'] = 200;
            $return['data'] = $data;
        }catch (\Think\Exception $e){
            $return['status']  = 0;
            $return['data']  = [];
            $return['errorCode'] = $e->getCode();
        }
        $this->ajaxReturn($return);
    }

    /**
     * 结果分列
     * @param $data [[‘赛事id’，‘盘口’，‘比分’，‘结果’]]
     * @return mixed
     */
    public function setCol($data){
        $luzhu = $data['luzhu'];
        $haolu = $data['haolu'];
        $retData = [];

        foreach($luzhu as $lzk => $tabList){
            if(strpos($lzk, 'asia_exp') !== false){
                $retDataKey = 'asia_exp';
                $type = 1;
            }else{
                $retDataKey = 'asia_bigsmall';
                $type = 2;
            }

            $tabHaolu = $haolu[$lzk];

            //好路位置标记
            if($tabHaolu == 1){//长龙
                $lzLen = 0;
                $lzResult = $tabList[0][3];
                foreach($tabList as $kk => $vv){
                    if($vv[3] != $lzResult) break;
                    $lzLen ++;
                }
            }elseif($tabHaolu == 2){//单跳
                $lzLen = 0;
                $lzResult = $tabList[0][3];
                $lzResult1 = $tabList[1][3];
                foreach($tabList as $kk => $vv){
                    if($kk != 0){
                        if(($kk % 2 != 0 && $vv[3] == $lzResult) || ($kk % 2 == 0 && $vv[3] != $lzResult) || !in_array($vv[3], [$lzResult, $lzResult1]))
                            break;

                    }
                    $lzLen ++;
                }
            }elseif($tabHaolu == 3){//双跳
                foreach($tabList as $kk => $vv){
                    if($kk % 2 == 0){
                        $arr1[] = $vv[3];
                    }else{
                        $arr2[] = $vv[3];
                    }
                }

                if($tabList[0][3] != $tabList[1][3]){
                    $lzLen = 6;
                }else{
                    $lzLen = 5;
                }
            }elseif($tabHaolu == 4){//两房一厅
                $lzResult0 = $tabList[0][3];
                $lzResult1 = $tabList[1][3];
                $lzResult2 = $tabList[2][3];

                $lzLen = 9;
                if($lzResult1 != $lzResult0 && $lzResult1 != $lzResult2){
                    $lzLen = 10;
                }
            }

            //处理每个tab的路珠趋势
            $temp_res = '';
            $temp_index = 0;
            $trendList = $tempResCount =  [];
           foreach($tabList as $lk => $lv){
               //根据结果按列分组
               if($type == 1){
                   $lv[3] = $lv[3] == 1 ? '赢' : ($lv[3] == '0' ? '走' : '输');
               }elseif($type == 2){
                   $lv[1] = changeExp($lv[1]);
                   $lv[3] = $lv[3] == 1 ? '大' : ($lv[3] == '0' ? '走' : '小');
               }

               $retStruct = [
                   'game_id' => (string)$lv[0],
                   'score' => $lv[2],
                   'result' => $lv[3],
                   'handcp' => $lv[1],
                   'highLight' => '0'
               ];

               //好路判断，高亮显示
               if($tabHaolu == 1 && $lk < $lzLen){//长龙
                   $retStruct['highLight'] = '1';
                   $retStruct['len'] = $lzLen;
               }elseif($tabHaolu == 2 && $lk < $lzLen){//单跳
                   $retStruct['highLight'] = '1';
               }elseif($tabHaolu == 3 && $lk <= $lzLen){//双跳
                   $retStruct['highLight'] = '1';
               }elseif($tabHaolu == 4 && $lk < $lzLen){//两房一厅
                   $retStruct['highLight'] = '1';
               }

               //分组
               if($temp_res === '' || ($temp_res !== '' && $temp_res == $lv[3])){
                   $temp_res = $lv[3];
                   $trendList[$temp_index][] = $retStruct;
               }elseif($temp_res !== '' && $temp_res != $lv[3]){
                   $temp_index ++;
                   $trendList[$temp_index][] = $retStruct;
                   $temp_res = $lv[3];
               }
            }

            $tempData[$lzk] = array_reverse($trendList);

            //亚盘让球、主客
            if($type == 1){
                if($tempData['asia_exp_let_all']) $retData[$retDataKey]['tab_all'] = $tempData['asia_exp_let_all'];
                if($tempData['asia_exp_let']) $retData[$retDataKey]['tab_letgoal'] = $tempData['asia_exp_let'];
                if($tempData['asia_exp_no_let']) $retData[$retDataKey]['tab_recgoal'] = $tempData['asia_exp_no_let'];
                if($tempData['asia_exp_let_home']) $retData[$retDataKey]['tab_home'] = $tempData['asia_exp_let_home'];
                if($tempData['asia_exp_let_away']) $retData[$retDataKey]['tab_away'] = $tempData['asia_exp_let_away'];
            }

            //亚盘大小球、让球主客场
            if($type == 2){
                if($tempData['asia_bigsmall_all']) $retData[$retDataKey]['tab_all'] = $tempData['asia_bigsmall_all'];
                if($tempData['asia_bigsmall_let']) $retData[$retDataKey]['tab_letgoal'] = $tempData['asia_bigsmall_let'];
                if($tempData['asia_bigsmall_no_let']) $retData[$retDataKey]['tab_recgoal'] = $tempData['asia_bigsmall_no_let'];
                if($tempData['asia_bigsmall_home']) $retData[$retDataKey]['tab_home'] = $tempData['asia_bigsmall_home'];
                if($tempData['asia_bigsmall_away']) $retData[$retDataKey]['tab_away'] = $tempData['asia_bigsmall_away'];
            }

        }

        return $retData;
    }

     /**
     * 实况
     * @return json
     */
    public function test()
    {
        $gameId = !empty($_REQUEST['gameId'])?$_REQUEST['gameId']:null;
        if(empty($gameId))
        {
            $data['status']  = 0;
            $data['data']  = [];
            $data['errorCode'] = 101;
            
        }

        $appService = new \Home\Services\AppfbService();
        $res = $appService->getStrengthTest($gameId);

        if($res === false)
        {
            $data['status']  = 0;
            $data['data']  = [];
            $data['errorCode'] = 102;
            
        }
        else
        {
            $data['status']  = 1;
            $data['errorCode'] = 200;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
     * 实况
     * @return json
     */
    public function test2()
    {
        echo "测试";
        exit;
    }
}