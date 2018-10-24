<?php
/**
 * web端足球数据接口
 * @author hmg <huangmg@qc.mail>
 * @since  2016-12-20
 */
use Think\Controller;
class WebfbController extends Controller
{
    private $secretKey = 'quancaiappppa';
    public $param = null;

    /*public function _initialize()
    {
        $this->param = getParam(); //获取传入的参数

        if ($this->param['nosign'] != C('nosignStr'))
        {
            $this->verifySignature();  //校验签名
        }
    }*/

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
        if (!$this->param['t'] || !$this->param['sign'] || $this->param['t'] < time() - 300 || $this->param['t'] > time() + 60)
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
     * 足球当日所有赛事
     * @return json 当日赛事数据
     * @author huangmg 2016-12-27
     */
    public function fb()
    {
        $unionId = !empty($_REQUEST['unionId'])? $_REQUEST['unionId'] : null;
        $subId = !empty($_REQUEST['subId'])? $_REQUEST['subId'] : null;

        $webfbService = new \Home\Services\WebfbService();
        $res = $webfbService->fbtodayList($unionId,$subId);

        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = [];
            $data['ErrorCode'] = 101;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
     * 足球完场赛事
     * @return json 足球完场赛事
     * @author huangmg 2016-12-27
     */
    public function fbOver()
    {
        $date = !empty($_REQUEST['date'])?$_REQUEST['date']:null;
        $unionId = !empty($_REQUEST['unionId'])? $_REQUEST['unionId'] : null;
        $subId = !empty($_REQUEST['subId'])? $_REQUEST['subId'] : null;

        $webfbService = new \Home\Services\WebfbService();
        $res = $webfbService->fbOverList($date,$unionId,$subId);

        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = [];
            $data['ErrorCode'] = 101;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
     * 足球近日赛程
     * @return json 足球近日赛程
     * @author huangmg 2016-12-28
     */
    public function fbFixture()
    {
        $date = !empty($_REQUEST['date'])?$_REQUEST['date']:null;
        $unionId = !empty($_REQUEST['unionId'])? $_REQUEST['unionId'] : null;
        $subId = !empty($_REQUEST['subId'])? $_REQUEST['subId'] : null;

        $webfbService = new \Home\Services\WebfbService();
        $res = $webfbService->fbFixtureList($date,$unionId,$subId);

        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = [];
            $data['ErrorCode'] = 101;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
     * 足球即时指数赛事
     * @return json 足球即时指数赛事
     * @author huangmg 2016-12-28
     */
    public function fbInstant()
    {
        $date = !empty($_REQUEST['date'])?$_REQUEST['date']:null;
        $unionId = !empty($_REQUEST['unionId'])? $_REQUEST['unionId'] : null;
        $subId = !empty($_REQUEST['subId'])? $_REQUEST['subId'] : null;

        $webfbService = new \Home\Services\WebfbService();
        $res = $webfbService->fbInstant($date,$unionId,$subId);

        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = [];
            $data['ErrorCode'] = 101;
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
     * @author huangmg 2016-12-27
     */
    public function change()
    {
        $webfbService = new \Home\Services\WebfbService();
        $res = $webfbService->getChange();  //数据库取数据

        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = [];
            $data['ErrorCode'] = 101;
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
     * @author huangmg 2016-12-27
     */
    public function change2()
    {
        $webfbService = new \Home\Services\WebfbService();
        $res = $webfbService->getChangeTwo();  //数据库取数据
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = [];
            $data['ErrorCode'] = 101;
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
     * @author huangmg 2016-12-27
     */
    public function goal()
    {
        $companyID = !empty($_REQUEST['cid'])?$_REQUEST['cid']:null;

        if(empty($companyID))
        {
            $data['status']  = 0;
            $data['data'] = [];
            $data['ErrorCode'] = 102;
            $this->ajaxReturn($data);
        }

        $webfbService = new \Home\Services\WebfbService();
        $res = $webfbService->getGoal($companyID);   //数据库取数据

        $bres = $webfbService->getPswGoal($companyID);   //数据库取数据

        if($res === false || $bres === false)
        {
            $data['status']  = 0;
            $data['data'] = [];
            $data['ErrorCode'] = 101;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
            $data['bdata'] = $bres;
        }
        $this->ajaxReturn($data);
    }

     /**
     * 半场赔率变化
     * @param  int  公司ID
     * @return json
     * @author huangmg 2016-12-27
     */
    public function pswGoal()
    {
        $companyID = !empty($_REQUEST['cid'])?$_REQUEST['cid']:null;

        if(empty($companyID))
        {
            $data['status']  = 0;
            $data['data'] = [];
            $data['ErrorCode'] = 102;
            $this->ajaxReturn($data);
        }

        $webfbService = new \Home\Services\WebfbService();
        $res = $webfbService->getPswGoal($companyID);   //数据库取数据

        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = [];
            $data['ErrorCode'] = 101;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
     * 赔率弹框赔率数据
     * @param  int  公司ID
     * @return json
     * @author huangmg 2016-12-27
     */
    public function oddsData()
    {
        $companyID = !empty($_REQUEST['cid'])?$_REQUEST['cid']:3;

        $webfbService = new \Home\Services\WebfbService();
        $res = $webfbService->getOddsData($companyID);   //数据库取数据

        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = [];
            $data['ErrorCode'] = 101;
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
     * @author huangmg 2017-01-04
     */
    public function chodds()
    {
        //$gameId = !empty($_REQUEST['gameId'])?$_REQUEST['gameId']:null;
        $webfbService = new \Home\Services\WebfbService();
        $res = $webfbService->getChodds();

        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = [];
            $data['ErrorCode'] = 101;
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
     * @author huangmg 2016-12-28
     */
    public function detail()
    {
        $gameId = !empty($_REQUEST['gameId'])?$_REQUEST['gameId']:null;
        $webfbService = new \Home\Services\WebfbService();
        $res = $webfbService->getDetailWeb($gameId);   //数据库取值

        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = [];
            $data['ErrorCode'] = 101;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
     * 当天赛事角球数据
     * @return json
     * @author huangmg 2016-12-28
     */
    public function corner()
    {
        $gameId = !empty($_REQUEST['gameId'])?$_REQUEST['gameId']:null;
        $webfbService = new \Home\Services\WebfbService();
        $res = $webfbService->getCornerWeb($gameId);   //数据库取值

        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 101;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
     * 当天赛事盘路数据
     * @return json
     * @author huangmg 2017-01-23
     */
    public function panlu()
    {
        $num = !empty($_REQUEST['count'])?$_REQUEST['count']:10;
        $webfbService = new \Home\Services\WebfbService();
        $res = $webfbService->getPanluWeb($num);   //数据库取值

        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 101;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
     * 亚赔界面（各公司初盘指数、即时指数、历史赔率数据）
     * @return json
     * @author huangmg 2016-12-29
     */
    public function asianOdds()
    {
        $gameId = !empty($_REQUEST['gameId'])?$_REQUEST['gameId']:null;

        if(empty($gameId))
        {
            $data['status']  = 0;
            $data['data'] = [];
            $data['ErrorCode'] = 102;
            $this->ajaxReturn($data);
        }

        $webfbService = new \Home\Services\WebfbService();
        $res = $webfbService->getAllOdds($gameId,1);

        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = [];
            $data['ErrorCode'] = 101;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
     * 大小界面（各公司初盘指数、即时指数、历史赔率数据）
     * @return json
     * @author huangmg 2016-12-29
     */
    public function ballOdds()
    {
        $gameId = !empty($_REQUEST['gameId'])?$_REQUEST['gameId']:null;

        if(empty($gameId))
        {
            $data['status']  = 0;
            $data['data'] = [];
            $data['ErrorCode'] = 102;
            $this->ajaxReturn($data);
        }

        $webfbService = new \Home\Services\WebfbService();
        $res = $webfbService->getAllOdds($gameId,3);

        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = [];
            $data['ErrorCode'] = 101;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
     * 欧赔界面（各公司初盘指数、即时指数、历史赔率数据）
     * @return json
     * @author huangmg 2016-12-29
     */
    public function europeOdds()
    {
        $gameId = !empty($_REQUEST['gameId'])?$_REQUEST['gameId']:null;

        if(empty($gameId))
        {
            $data['status']  = 0;
            $data['data'] = [];
            $data['ErrorCode'] = 102;
            $this->ajaxReturn($data);
        }

        $webfbService = new \Home\Services\WebfbService();
        $res = $webfbService->getAllOdds($gameId,2);

        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = [];
            $data['ErrorCode'] = 101;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
     * 对阵数据(数据分析界面)
     * @return json
     * @author huangmg 2016-12-30
     */
    public function analysis()
    {
        $gameId = !empty($_REQUEST['gameId'])?$_REQUEST['gameId']:null;
        $lang = !empty($_REQUEST['lang'])?$_REQUEST['lang']:1;

        if(empty($gameId))
        {
            $data['status']  = 0;
            $data['data'] = [];
            $data['ErrorCode'] = 102;
            $this->ajaxReturn($data);
        }

        $webfbService = new \Home\Services\WebfbService();
        $res = $webfbService->getAnaForFile($gameId,$lang);

        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = [];
            $data['ErrorCode'] = 101;
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
     * @return json
     * @author huangmg 2016-12-30
     */
    public function lineup()
    {
        $gameId = !empty($_REQUEST['gameId'])?$_REQUEST['gameId']:null;

        if(empty($gameId))
        {
            $data['status']  = 0;
            $data['data'] = [];
            $data['ErrorCode'] = 102;
            $this->ajaxReturn($data);
        }

        $webfbService = new \Home\Services\WebfbService();
        $res = $webfbService->getLineup($gameId);
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 101;
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
     * @return json
     * @author huangmg 2016-12-30
     */
    public function dealAnimate()
    {
        $flashId = !empty($_REQUEST['flashId'])?$_REQUEST['flashId']:null;
        $txt = !empty($_REQUEST['txt'])?$_REQUEST['txt']:null;
        $status = !empty($_REQUEST['status'])?$_REQUEST['status']:null;

        if(empty($flashId) || empty($txt))
        {
            $data['status']  = 0;
            $data['data'] = [];
            $data['ErrorCode'] = 102;
            $this->ajaxReturn($data);
        }
        $txt = base64_decode($txt);

        $webfbService = new \Home\Services\WebfbService();
        $res = $webfbService->dealforAnimate($flashId, $txt,$status);
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = [];
            $data['ErrorCode'] = 101;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = [];
        }
        $this->ajaxReturn($data);
    }

    /**
     * 足球比分盘路
     * @return json
     * @author dengwj 2017-08-22
     */
    public function getFbPanlu()
    {
        //半场的前6场的历史战绩
        $webfbService = new \Home\Services\WebfbService();
        $game_id = $_REQUEST['game_id'];
        $panlu = $webfbService->getPanluWeb(10,$game_id);   //数据库取值
        //dump($panlu);
        if (!empty($panlu)) {
            $key = array_keys($panlu)[0];
            $val = $panlu[$key];
            foreach ($val as $k => $v) {
                $time[] = $v[1];
            }
            array_multisort($time,SORT_DESC,$val);//根据赛事时间排序
            $panlu[$key] = $val;
            foreach ($panlu as $key => $value) {
                foreach ($value as $k => $v) {
                    //比赛时间的转换
                    $panlu[$key][$k][1] = date('y-m-d', $v[1]);
                    //联赛名格式转换
                    $panlu[$key][$k][2] = explode(',', $v[2])[0];
                    //主队名格式转换
                    $panlu[$key][$k][4] = explode(',', $v[4])[0];
                    //客队名格式转换
                    $panlu[$key][$k][5] = explode(',', $v[5])[0];
                    //赔率格式转换
                    $panlu[$key][$k][8] = $v[8] != '' ? changeExp($v[8]) : '-';
                }
            }
        }
        $this->ajaxReturn($panlu);
    }
}
