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
class AppdataController extends Controller
{
    private $secretKey = 'quancaiappppa';
    public $param = null;
    public $errorArr = [];

    public function _initialize()
    {
        $this->errorArr = C('errorCode');
        $this->param = getParam(); //获取传入的参数
        if($this->param['nosign'] != C('nosignStr') && ACTION_NAME != 'animate' && ACTION_NAME != 'aniOver' && ACTION_NAME != 'animateId')
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
       /*  parent::ajaxReturn([
            'code' => $msgCode,
            'time' => time(),
            'msg'  => C('errorCode')[$msgCode],
            'data' => $data
        ],$type); */
    }

    //校验签名
    public function verifySignature()
    {
        //验证请求的时间
        if (!$this->param['t'] || $this->param['t'] < time() - 300 || $this->param['t'] > time() + 300)
            $this->ajaxReturn(array('status'=>0,'ErrorCode'=>103,'msg'=>$this->errorArr['103'],'data'=>null));

        //验证参数和请求的时间
        if (!$this->param['t'] || !$this->param['sign'] || $this->param['t'] < time() - 300 || $this->param['t'] > time() + 300)
            $this->ajaxReturn(array('status'=>0,'ErrorCode'=>101,'msg'=>$this->errorArr['101'],'data'=>null));

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
            $this->ajaxReturn(array('status'=>0,'ErrorCode'=>403,'msg'=>$this->errorArr['403'],'data'=>null));
    }


     /**
     * 当日所有赛事
     * @return json 当日赛事数据
     */
    public function fb()
    {
        $unionId = !empty($_REQUEST['unionId'])? $_REQUEST['unionId'] : null;
        $subId = !empty($_REQUEST['subId'])? $_REQUEST['subId'] : null;
        $key = !empty($_REQUEST['key'])? $_REQUEST['key'] : 'yes';
        /*$appService = new \Home\Services\AppdataService();
        $res = $appService->fbtodayList($unionId,$subId,$key);*/
        $fbService = new \Home\Services\AppfbService();
        $res = $fbService->fbtodayList($unionId,$subId);

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
     * 今日赛事变化数据，比分、红黄牌，比赛时间
     * @return json
     */
    public function change()
    {
        $key = !empty($_REQUEST['key'])? $_REQUEST['key'] : 'yes';
        $appService = new \Home\Services\AppdataService();
        //$res = $appService->getChange($key);
        $res = $appService->getChangeB();  //数据库取数据
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = [];
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
     * 赔率变化
     * @param  int  公司ID
     * @return json
     */
    public function goal()
    {
        $companyID = !empty($_REQUEST['id'])?$_REQUEST['id']:null;
        $key = !empty($_REQUEST['key'])? $_REQUEST['key'] : 'yes';
        if(empty($companyID))
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 101;
            $data['msg'] = $this->errorArr['101'];
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
     * 赔率变化（弃用）
     * @param  int  公司ID
     * @return json
     */
    public function goals()
    {
        $companyID = !empty($_REQUEST['id'])?$_REQUEST['id']:null;
        $key = !empty($_REQUEST['key'])? $_REQUEST['key'] : 'yes';
        if(empty($companyID))
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 101;
            $data['msg'] = $this->errorArr['101'];
            $this->ajaxReturn($data);
        }

        $appService = new \Home\Services\AppdataService();
        $res = $appService->getGoals($companyID,$key);
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
            $data['data'] = null;
            $data['ErrorCode'] = 101;
            $data['msg'] = $this->errorArr['101'];
            $this->ajaxReturn($data);
        }

        // $appService = new \Home\Services\AppdataService();
        // $res = $appService->getGoalHistroy($gameId,$companyID,$type);

        $fbService = new \Home\Services\AppfbService();
        $res = $fbService->getHisOdds($gameId,$companyID,$type);

        if(empty($res))
        {
            #旧SB历史赔率接口
            $appService = new \Home\Services\AppdataService();
            $res = $appService->getGoalHistroy($gameId,$companyID,$type);
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
     * app滚球界面赔率历史变化（SB公司）
     * @param  gameId  赛事ID
     * @param  type  类别：1亚，2欧，3大小
     * @return json
     */
    public function goalRoll()
    {
        $gameId = !empty($_REQUEST['gameId'])?$_REQUEST['gameId']:null;
        $type = !empty($_REQUEST['type'])?$_REQUEST['type']:1;
        if(empty($gameId))
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 101;
            $data['msg'] = $this->errorArr['101'];
            $this->ajaxReturn($data);
        }

        $fbService = new \Home\Services\AppfbService();
        $res = $fbService->getSBhisOdds($gameId,$type);

        /*if(empty($res))
        {
            #旧SB历史赔率接口(数据会缺失)
            $appService = new \Home\Services\AppdataService();
            $res = $appService->getGoalRoll($gameId,$type);
        }*/

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
     * 总赔率数据————全场、半场亚盘、欧盘、大小的即时、滚球、初盘赔率
     * @return json
     */
    public function oddsDataDiv()
    {
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
     * 当天赛事现场数据————技术统计（红黄牌、角球、射门次数等）
     * @return json
     */
    public function skill()
    {
        $gameId = !empty($_REQUEST['gameId'])?$_REQUEST['gameId']:null;
        $appService = new \Home\Services\AppdataService();
        //$res = $appService->getSkillApp($gameId);
        $res = $appService->getSkillAppB($gameId);
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
            $data['msg'] = $this->errorArr['101'];
            $this->ajaxReturn($data);
        }

        if($lang != 1) $lang = 2;

        $appService = new \Home\Services\AppfbService();
        $res = $appService->getAnaForFile($gameId,$lang);

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
            $data['msg'] = $this->errorArr['101'];
            $this->ajaxReturn($data);
        }
        // $appService = new \Home\Services\AppdataService();
        // $res = $appService->getAsianOdds($gameId,$key);
        $appService = new \Home\Services\AppfbService();
        //$res = $appService->getAllOdds($gameId,1);
        $res = $appService->getAllOddsNew($gameId,1);

        /*if(empty($res))
        {
            #旧接口
            $appService = new \Home\Services\AppdataService();
            $res = $appService->getAsianOdds($gameId,$key);
        }*/

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
     * 大小界面（各公司初盘指数、即时指数）
     * @return json 大小赔率数据
     */
    public function ballOdds()
    {
        $gameId = !empty($_REQUEST['gameId'])?$_REQUEST['gameId']:null;
        $key = !empty($_REQUEST['key'])? $_REQUEST['key'] : 'yes';
        $data = [];
        if(empty($gameId))
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 101;
            $data['msg'] = $this->errorArr['101'];
            $this->ajaxReturn($data);
        }
        // $appService = new \Home\Services\AppdataService();
        // $res = $appService->getBallOdds($gameId,$key);

        $appService = new \Home\Services\AppfbService();
        //$res = $appService->getAllOdds($gameId,3);
        $res = $appService->getAllOddsNew($gameId,3);

        /*if(empty($res))
        {
            #旧接口
            $appService = new \Home\Services\AppdataService();
            $res = $appService->getBallOdds($gameId,$key);
        }*/

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
            $data['msg'] = $this->errorArr['101'];
        }

        $appService = new \Home\Services\AppfbService();
        //$res = $appService->getAllOdds($gameId,2);
        $res = $appService->getAllOddsNew($gameId,2);

        if(empty($res))
        {
            #旧接口
            $appService = new \Home\Services\AppdataService();
            $res = $appService->getEuropeOdds($gameId,$key);
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
     * 欧赔界面历史赔率接口（百家欧赔历史）
     * @return json 欧盘赔率数据
     */
    public function euroHistory()
    {
        $year = !empty($_REQUEST['year'])?$_REQUEST['year']:date('Y');
        $gameId = !empty($_REQUEST['gameId'])?$_REQUEST['gameId']:null;
        $company = !empty($_REQUEST['company'])?$_REQUEST['company']:null;
        if(empty($gameId) || !$company)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 101;
            $data['msg'] = $this->errorArr['101'];
            $this->ajaxReturn($data);
        }
        $appService = new \Home\Services\AppfbService();
        $company=  urldecode($company);
        $res = $appService->getEuroHistory($gameId,$company,$year);

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
            $data['msg'] = $this->errorArr['101'];
            $this->ajaxReturn($data);
        }
        $appService = new \Home\Services\AppdataService();
        $res = $appService->getUpdown($unionId);
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
            $data['msg'] = $this->errorArr['101'];
            $this->ajaxReturn($data);
        }
        $appService = new \Home\Services\AppdataService();
        $res = $appService->getLeagueIntegral($unionId,$key);
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
            $data['msg'] = $this->errorArr['101'];
            $this->ajaxReturn($data);
        }

        $appService = new \Home\Services\AppdataService();
        $res = $appService->getLeagueMatch($unionId,$runId,$key);
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
            $data['msg'] = $this->errorArr['101'];
            $this->ajaxReturn($data);
        }
        $appService = new \Home\Services\AppdataService();
        $res = $appService->getLetGoal($unionId,$key);
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
            $data['msg'] = $this->errorArr['101'];
            $this->ajaxReturn($data);
        }
        $appService = new \Home\Services\AppdataService();
        $res = $appService->getBigSmall($unionId,$key);
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
            $data['msg'] = $this->errorArr['101'];
            $this->ajaxReturn($data);
        }
        $appService = new \Home\Services\AppdataService();
        $res = $appService->getArcher($unionId,$key);

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
            $data['msg'] = $this->errorArr['101'];
            $this->ajaxReturn($data);
        }

        $appService = new \Home\Services\AppdataService();
        $res = $appService->getCupGroupIntegral($unionId,$key);
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
            $data['msg'] = $this->errorArr['101'];
            $this->ajaxReturn($data);
        }

        $appService = new \Home\Services\AppdataService();
        $res = $appService->getCupMatch($unionId,$runId,$key);
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

        $appService = new \Home\Services\AppdataService();
        $res = $appService->getTeamLogo($gameId);
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
      * 足球必发数据
      * @return json
      */
    public function BifaValue(){
        $gameId = !empty($_REQUEST['gameId'])? $_REQUEST['gameId'] : null;
        if(!$gameId){
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 101;
            $data['msg'] = $this->errorArr['101'];
            $this->ajaxReturn($data);
        }
        $appService = new \Home\Services\AppdataService();
        $res = $appService->BifaValue($gameId);
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
            $data['msg'] = $this->errorArr['101'];
            $this->ajaxReturn($data);
        }
        $appService = new \Home\Services\AppdataService();
        $res = $appService->BifaTrade($gameId,$limit);
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
            $data['msg'] = $this->errorArr['102'];
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }
}