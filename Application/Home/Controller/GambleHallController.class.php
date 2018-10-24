<?php
/**
 * 推荐大厅
 * @author huangjiezhen <418832673@qq.com> 2015.12.02
 */

use Think\Controller;

class GambleHallController extends CommonController
{
    protected function _initialize()
    {
        C('HTTP_CACHE_CONTROL','no-cache,no-store');
        parent::_initialize();
        if(cookie('lang') == NULL){
            //默认简体cookie
            cookie("lang",'0', 86400*30);
        }
        $domain = explode('.', $_SERVER['HTTP_HOST'])[0];
        if($domain != 'jc' && !in_array(ACTION_NAME, ['setGamble','doTGamble','doIntroList','getRankSort'])){
            parent::_empty();
        }
    }

    public function index()
    {
        $info = I('get.info') ?: 'football';
        $this->info = $info;
        $user_id = is_login();
        if($user_id){
            //用户等级与剩余推荐的次数
            switch ($info) {
                case 'football':
                    $gameType = 1;
                    $playType = 1;
                    $Lv       = 'lv';
                    break;
                case 'basketball':
                    $gameType = 2;
                    $playType = 1;
                    $Lv       = 'lv_bk';
                    break;
                case 'betting':
                    $gameType = 1;
                    $playType = 2;
                    $Lv       = 'lv_bet';
                    break;
            }
            list($this->normLeftTimes,$this->imptLeftTimes,$this->gameConf,$this->gambleList) = D('GambleHall')->gambleLeftTimes($user_id,$gameType,$playType);
            //查询等级
            $userInfo = M('FrontUser')->where(['id'=>$user_id])->field([$Lv,'head','nick_name','point','coin+unable_coin as coin'])->find();
            $this->lv = $userInfo[$Lv];
            $this->userInfo = $userInfo;
        }
        else
        {
            $this->gambleList = [];
        }
        if ($info == 'football')
        {
            list($game, $union) = D('GambleHall')->matchList();
            //联盟赛事总数
            $unionSum = 0;
            foreach ($union as $k => $v) {
                $unionSum += $v['union_num'];
            }
            //选中某个联盟
            if ($unionid = I('get.unionid'))
            {
                foreach ($game as $k => $v)
                {
                    if ($v['union_id'] != $unionid)
                        unset($game[$k]);
                }
            }
            $this->union    = $union;
            $this->unionSum = $unionSum;
            $this->game     = $game;
        }
        else if ($info == 'basketball')
        {
            $map = I('get.unionid') ? I('get.unionid') : '';
            list($this->game,$this->union) = D('GambleHall')->basketballList($map);
        }
        else if ($info == 'betting')
        {
            list($game, $union) = D('GambleHall')->matchList(2);
            //联盟赛事总数
            $unionSum = 0;
            foreach ($union as $k => $v) {
                $unionSum += $v['union_num'];
            }
            //选中某个联盟
            if ($unionid = I('get.unionid'))
            {
                foreach ($game as $k => $v)
                {
                    if ($v['union_id'] != $unionid)
                        unset($game[$k]);
                }
            }
            $this->union    = $union;
            $this->unionSum = $unionSum;
            $this->game     = $game;
        }
        else
        {
            $this->error('error');
        }
        //mqtt 配置
        $mqtt = C('Mqtt');
        $this->assign('mqttOpt', $mqtt);
        $this->assign('mqttUser', setMqttUser());
        $this->display($info);
    }

    //ajax推荐推荐
    public function gamble()
    {
        if (!IS_AJAX)
            return;

        //判断登陆
        if ((new PublicController)->checkLogin() == false)
            $this->error(-1);
        $gameType = I('gameType') ? : 1;
        $res = D('GambleHall')->gamble($userid=is_login(),$param=getParam(),$platform=1,$gameType);

        if (is_numeric($res))
            $this->error(getErrorMsg($res));

        $this->success(['normLeftTimes'=>$res['normLeftTimes'],'imptLeftTimes'=>$res['imptLeftTimes']]);
    }

    /**
     * 足球推荐
     */
    public function setGamble()
    {
        // $param = [
        //     'user_id'    => 81,
        //     'game_id'    => 1352334,
        //     'play_type'  => 1,
        //     'chose_side' => 1,
        //     'desc'       => '分析',
        //     'tradeCoin'  => 256,
        // ];  
        $ip = get_client_ip();

        if($ip != '183.3.152.226'){
            $this->ajaxReturn(['code'=>0,'msg'=>'ip错误']);
        }

        $param['user_id']    = I('user_id');
        $param['game_id']    = I('game_id');
        $param['play_type']  = I('play_type');
        $param['chose_side'] = I('chose_side');
        $param['desc']       = I('desc');
        $param['tradeCoin']  = I('tradeCoin');
        $param['out_handcp'] = I('out_handcp');

        $res = $this->doTGamble($param);

        if (is_numeric($res)){
            $this->ajaxReturn(['code'=>$res,'msg'=>getErrorMsg($res)]);
        }

        $this->ajaxReturn(['code'=>1,'msg'=>'请求成功']);
    }

    public function doTGamble($param)
    {
        //获取盘口和赔率
        switch ($param['play_type']) 
        {
            case '1':
            case '-1':
                $Lv         = 'lv';
                $playType   = 1;
                $min_odds   = 0.6;
                $error_code = 2016;
                D('GambleHall')->getHandcpAndOdds($param);
                break;

            case '2':
            case '-2':
                $Lv         = 'lv_bet';
                $playType   = 2;
                $min_odds   = 1.4;
                $error_code = 2017;
                D('GambleHall')->getHandcpAndOddsBet($param);
                break;
        }

        //推荐字段不能为空
        if (
            $param['game_id']       == null
            || $param['user_id']    == null
            || $param['play_type']  == null
            || $param['chose_side'] == null
            || $param['odds']       == null
            || !isset($param['handcp'])
        )
        {
            return 201;
        }

        //获取赛事是否能推荐
        $game = D('GambleHall')->getGameFbinfo($playType);
        $gameIdArr = array_map('array_shift', $game);
        if(!in_array($param['game_id'], $gameIdArr)){
            return 2001;
        }

        foreach ($game as $k => $v) {
            if($v['game_id'] == $param['game_id']){
                $gameInfo = $v;
            }
        }

        //判断推荐时间
        if (time() > $gameInfo['gtime'])
            return 2002;

        //亚盘不能低于0.6，竞彩不能低于1.3
        if($param['odds'] < $min_odds)
            return $error_code;

        //若提交盘口，与实时盘口比较，不一致不提交
        if($param['out_handcp'] != ''){
            if($param['handcp'] != $param['out_handcp']){
                return 2018;
            }
        }
        unset($param['out_handcp']);

        $gameModel   = M('GameFbinfo');
        $GambleModel = M('Gamble');

        //获取剩余推荐次数，推荐配置
        list($normLeftTimes,$imptLeftTimes,$gameConf,$gambleList) = D('GambleHall')->gambleLeftTimes($param['user_id'],1,$playType);

        //判断推荐的次数是否已达上限
        if ($normLeftTimes <= 0)
            return 2004;

        //判断推荐的类型，不可重复、冲突推荐
        foreach ($gambleList as $v)
        {
            if ($v['play_type'] == $param['play_type'] && $v['game_id'] == $param['game_id'])
                return 2003;
        }

        if ($imptLeftTimes <= 0 && $param['is_impt'] == 1)
            return 2006;

        //如果有推荐分析、需要大于10字小于50字
        if ($param['desc'])
        {
            $descLenth = Think\Tool\Tool::utf8_strlen($param['desc']);

            if ($descLenth < 10 || $descLenth > 400)
                return 2011;
        }

        //是否有推荐查看和推荐分析
        $userInfo = M('FrontUser')->field(['id',$Lv,'point','nick_name'])->where(['id'=>$param['user_id']])->find();

        if ($param['tradeCoin'])
        {
            //如果设置推荐查看、判断是否符合用户等级,不够的话去最大推荐金币
            $maxCoin = $gameConf['userLv'][$userInfo[$Lv]]['letCoin'];
            if ($param['tradeCoin'] > $maxCoin)
                $param['tradeCoin'] = $maxCoin;
        }

        //增加推荐记录
        $param['vote_point']     = $gameConf['norm_point'];
        $param['create_time']    = time();
        $param['platform']       = 2;
        $param['tradeCoin']      = (int)$param['tradeCoin'];
        $param['union_id']       = $gameInfo['union_id'];
        $param['union_name']     = $gameInfo['union_name'];
        $param['home_team_name'] = $gameInfo['home_team_name'];
        $param['away_team_name'] = $gameInfo['away_team_name'];
        $param['game_id']        = $gameInfo['game_id'];
        $param['game_date']      = date('Ymd',$gameInfo['gtime']);
        $param['game_time']      = date('H:i',$gameInfo['gtime']);
        $param['sign']           = $param['user_id'].'^'.$param['game_id'].'^'.$param['play_type'];

        $insertId = $GambleModel->add($param);

        if (!$insertId)
            return 2007;

        //添加推荐数量
        D('GambleHall')->setGambleNumber($param,1);

        //增加推荐分析的积分记录,0积分跳过
        if (!empty($param['desc']) && $gameConf['gamble_desc'] != 0)
        {
            $changePoint = $gameConf['gamble_desc'];
            $totalPoint  = $userInfo['point'] + $changePoint;

            M('FrontUser')->where(['id'=>$param['user_id']])->setInc('point',$changePoint);

            $descType = '足球'; 
            switch ($param['play_type']) {
                case  '1':
                case '-1': $descPlay = '亚盘';  break;
                case  '2': 
                case '-2': $descPlay = '竞彩';  break;
            }

            M('PointLog')->add([
                'user_id'     => $param['user_id'],
                'log_time'    => NOW_TIME,
                'log_type'    => 12,
                'gamble_id'   => $param['game_id'],
                'change_num'  => $changePoint,
                'total_point' => $totalPoint,
                'desc'        => $descType.$descPlay.'推荐分析'
            ]);
        }

        //推送相关
        D('GambleHall')->gamblePush($userInfo,$gameInfo);

        $normLeftTimes--;
        return ['normLeftTimes'=>$normLeftTimes,'imptLeftTimes'=>$imptLeftTimes];
    }

    //产品发布接口
    /*
     * $product_id  产品id
     * $data 赛事数据  例如：1404135^1^1^1,1403645^-1^1^0.5,1292544^1^-1^1.25
     *                （赛事id^玩法^选择^盘口） 数量需要与产品设定推介数相同
    */
    public function doIntroList()
    {
        $ip = get_client_ip();

        if(!in_array($ip, ['183.3.152.226','192.168.1.250'])){
            $this->ajaxReturn(['code'=>1,'msg'=>'error：'.$ip]);
        }
        $author_id  = I('author_id');  //发布者id
        if(empty($author_id)) $this->ajaxReturn(['code'=>12,'msg'=>'请输入发布者id！']);

        $product_id = I('product_id'); //产品id

        $product = M('IntroProducts')->field("name,pay_num,total_num,game_num")->where(['id'=>$product_id,'status'=>1])->find();

        if (empty($product)) $this->ajaxReturn(['code'=>2,'msg'=>'产品id参数错误！']);

        $blockTime  = getBlockTime(1, true);
        $lists = M('IntroLists')
            ->where([ 'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]], 'product_id' => $product_id])
            ->find();

        if($lists) $this->ajaxReturn(['code'=>3,'msg'=>'该产品今天已经发过推介，请明天再发布！']);

        $paramData = I('data'); //赛事数据

        $gameData = explode(',', $paramData);

        if(count($gameData) != $product['game_num'])
            $this->ajaxReturn(['code'=>4,'msg'=>"该产品需要推荐{$product['game_num']}场赛事！"]);

        //获取今天能推荐的赛事
        $Gamble = D('GambleHall')->getGameFbinfo(1);

        $gameIdArr = array_map('array_shift', $Gamble);

        $IntroGamble  = [];
        foreach ($gameData as $k => $v) 
        {
            $game = explode('^', $v);
            //是否能推荐
            if(!in_array($game[0], $gameIdArr)){
                $game_not[] = $game[0];
            }
            //对应赛事
            foreach ($Gamble as $kk => $vv) {
                if($game[0] == $vv['game_id']){
                    $gameInfo = $vv;
                }
            }
            //比赛开场前20分钟不能推介
            if($gameInfo['gtime'] - time() <= 1200){
                $gameTime[] = $game[0];
            }
            $gameT[$game[0].$game[1]] = $game[0];
            $param = [
                    'product_id'    => $product_id,
                    'game_id'       => $game[0],
                    'play_type'     => $game[1],
                    'chose_side'    => $game[2],
                    'union_id'      => $gameInfo['union_id'],
                    'union_name'    => $gameInfo['union_name'],
                    'gtime'         => $gameInfo['gtime'],
                    'home_team_name'=> $gameInfo['home_team_name'],
                    'away_team_name'=> $gameInfo['away_team_name'],
                    'create_time'   => time(),
                ];
            D('GambleHall')->getHandcpAndOdds($param);
            //dump($param);
            //亚盘赔率不能低于0.6
            if($param['odds'] < 0.6){
                $handcp_dy[] = $game[0];
            }

            //若提交盘口，与实时盘口比较，不一致不提交,
            $out_handcp = $game[3];
            if($out_handcp != ''){
                if($param['handcp'] != $out_handcp){
                    $handcp_bd[] = $game[0];
                }
            }
            
            $IntroGamble[] = $param;
        }
        if(!empty($game_not))  
            $this->ajaxReturn(['code'=>6,'msg'=>"有赛事不能推荐！",'game_id'=>$game_not]);
        if(!empty($gameTime))  
            $this->ajaxReturn(['code'=>5,'msg'=>"请检查赛事,比赛开场前20分钟不能推介！",'game_id'=>$gameTime]);

        if(!empty($handcp_dy)) $this->ajaxReturn(['code'=>7,'msg'=>'比赛的赔率不能小于0.6','game_id'=>$handcp_dy]);
        if(!empty($handcp_bd)) $this->ajaxReturn(['code'=>8,'msg'=>'比赛的盘口有变动','game_id'=>$handcp_bd]);

        if (count($gameT) != $product['game_num']) $this->ajaxReturn(['code'=>9,'msg'=>'请检查是否有相同的赛事,玩法不能相同！']);

        //今天已预购数量
        $IntroBuyNum = M('IntroBuy')->field('user_id')->where(['product_id'=>$product_id,'list_id'=>0,'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]]])->count();

        //剩余数量计算
        $remain_num = $product['total_num']-($product['pay_num'] + $IntroBuyNum);

        $pub_time = time();
        //添加推介记录
        $IntroLists = [
            'product_id' => $product_id,
            'pub_time'   => $pub_time,
            'create_time'=> $pub_time,
            'remain_num' => $remain_num,
            'admin_id'   => $author_id,
        ];

        $list_id = M('IntroLists')->add($IntroLists);

        if(!$list_id) $this->ajaxReturn(['code'=>10,'msg'=>'添加推介失败！']);

        //查询是否有预购
        $IntroBuy = M('IntroBuy')->field('user_id')->where(['product_id'=>$product_id,'list_id'=>0])->select();

        $IntroBuy_user = [];
        if($IntroBuy)
        {
            //修改最新推介list_id
            M('IntroBuy')->where(['product_id'=>$product_id,'list_id'=>0])->save(['list_id'=>$list_id]);
            //发送短信和app推送
            $msg = "您订阅的{$product['name']}已经发布推荐信息，请登陆全球体育进行查看。"; //消息内容
            foreach ($IntroBuy as $k => $v) {
                $IntroBuy[$k]['list_id']   = $list_id;
                $IntroBuy[$k]['content']   = $msg;
                $IntroBuy[$k]['send_type'] = 0;
                $IntroBuy[$k]['state']     = 0;
                $IntroBuy[$k]['is_send']   = 0;
                $IntroBuy[$k]['module']    = 16;
                $IntroBuy[$k]['module_value']   = $product_id;
                $IntroBuy[$k]['send_time'] = $pub_time;
                $IntroBuy_user[] = $v['user_id'];
            }
            M('mobileMsg')->addAll($IntroBuy);
        }
        
        //是否有关注
        $FollowMap['product_id'] = $product_id;
        if(!empty($IntroBuy_user)){
            $FollowMap['user_id'] = ['not in',$IntroBuy_user];
        }
        $introFollow = M('IntroFollow')->field('user_id')->where($FollowMap)->select();

        if($introFollow){
            $message = "您关注的{$product['name']}已发布赛事，请前往查看！";
            foreach ($introFollow as $k => $v) {
                $introFollow[$k]['list_id']   = $list_id;
                $introFollow[$k]['content']   = $message;
                $introFollow[$k]['send_type'] = 2;
                $introFollow[$k]['state']     = 0;
                $introFollow[$k]['is_send']   = 0;
                $introFollow[$k]['module']    = 16;
                $introFollow[$k]['module_value']   = $product_id;
                $introFollow[$k]['send_time'] = $pub_time;
            }
            M('mobileMsg')->addAll($introFollow);
        }
        
        //添加推介赛事
        foreach ($IntroGamble as $k => $v) 
        {
            $IntroGamble[$k]['list_id']    = $list_id;
        }

        if( M('IntroGamble')->addAll($IntroGamble) )
        {
            $this->ajaxReturn(['code'=>0,'msg'=>'推介成功！']);
        }
        else
        {
            $this->ajaxReturn(['code'=>11,'msg'=>'添加推介赛事失败！']);
        }

    }

    //根据用户排序排序
    public function getRankSort(){
        $type = I('t',1,'intval');
        // $qqty_uid_array = [6228,6227,6216,6209,6191,6175,6014,6000,5970,5950,5873,5872,5871,5868,5867,5866,5865,5864,5863,5861,5858,5857,5855,5853,5850,5824,5818,5816,5808,5803,5780,5775,5757,5705,5606,5605,5604,5603,5602,5600,5598,5595,7936,7935,7933,7932,7931,7929,7927,7925,7920,7918,7916,7913,7912,7907,7906,7901,7898,7803,7780,7766,7746,7739,7729,7692,7687,7685,7682,7678,7672,7664,7661,7660,7658,7657,7656,7654,7653,7651,7652,7645,7643,7640,7628,7627,7624,7619,7612,7592,7469,7468,7457,7431,7429,7421,7420,7418,7417,7303,7294,7276,7274,7264,7247,7192,7190,7167,7164,7160,7161,7159,7157,12620,9892,8727,8046,8028,8006,7992,7972,7961,7937,7156,7155,7154,7152,7151,7149,7148,7146,7147,7143,7139,7138,7136,7135,7134,7132,7131,7127,7120,7119,7117,7111,7104,7092,7091,7084,7062,7038,7030,7009,6949,6899,6897,6894,6892,6890,6889,6888,6887,6885,6883,6881,6880,6645,6643,6640,6638,6635,6629,6626,6625,6624,6604,6397,6393,6387,6376,6374,6369,6365,6364,6363,6307,6236,2060,2027,778,777,715,650,624,613,606,578,563,561,555,552,523,507,504,502,501,500,496,495,490,489,488,483,480,478,476,475,474,458,453,452,415,400,348,336,330,316,312,294,280,259,257,252,251,246,245,243,242,240,235,231,229,227,222,221,220,213,212,203,188,138,108,79,77,45,36,24,5594,5484,5481,5135,5129,5125,5124,5119,5118,5025,4975,4926,4909,4907,4905,4706,2527,2526,2524,2523,2522,2511,2509,2507,2506,2505,2487,2306,2305,2304,2302,2267,2109,2108,2107,2106,2105,2104,2102,2100,2099,2097,2096,2095,2094,2092,2087,2083,2079,2075,2073];
        $qqty_uid_array = [2524,5811,2526,5957,6331,5786,7247,2487,7685,5855,6179,6894,2087,502,7429,6393,2099,5865,7138,613,5481,7117,5872,415,242,496,7136,2527,7149,6334,2060,6177,2505,5596,7146,6178,2109,5757,458,240,6201,501,4926,138,6601,2094,7682,6387,7120,6629,312,6228,7624,6197,257,2100,5866];
        $where['gameType'] = 1;
        $where['dateType'] = 1;
        //获取每周日上榜
        $end_date = date('Ymd', strtotime('-1 sunday'));
        if($type == 1){
            $where['end_date'] = $end_date;
            $where['user_id'] = ['in',$qqty_uid_array];
            $ranking = M('rankingList')->field('user_id,ranking,winrate')->where($where)->order("ranking asc")->select();
            if(!$ranking){
                $end_date = date('Ymd', strtotime('-2 sunday'));
                $where['end_date'] = $end_date;
                $ranking = M('rankingList')->field('user_id,ranking,winrate')->where($where)->order("ranking asc")->select();
            }
        }else{
            $where['listDate'] = $end_date;
            $where['user_id'] = ['in',$qqty_uid_array];
            $ranking = M('rankBetting')->field('user_id,ranking,winrate')->where($where)->order("ranking asc")->select();
            if(!$ranking){
                $end_date = date('Ymd', strtotime('-2 sunday'));
                $where['listDate'] = $end_date;
                $ranking = M('rankBetting')->field('user_id,ranking,winrate')->where($where)->order("ranking asc")->select();
            }
        }
        
        foreach ($ranking as $k => $v) {
            $rank[$v['user_id']] = $v;
        }
        $userArr = [];
        foreach ($qqty_uid_array as $k => $v) {
            $val = [];
            $val['user_id'] = $v;
            $ranking = $rank[$v]['ranking'] ? : 9999;
            $val['ranking'] = $ranking;
            $val['winrate'] = $rank[$v]['winrate'];
            $userArr[] = $val;
            $sort1[] = $ranking;
            $sort2[] = $v;
        }
        array_multisort($sort1,SORT_ASC,$sort2,SORT_ASC,$userArr);
        $userIdArr = [];
        foreach ($userArr as $k => $v) {
            $userIdArr[] = $v['user_id'].'-'.($v['winrate'] ?:0);
        }
        $this->ajaxReturn(['code'=>1,'data'=>$userIdArr]);
    }

    //胜率排行榜
    public function rank()
    {
        $pageNum   = 30; //每页页数
        $gameType  = I('gameType') ? I('gameType') : 1; //赛事类型
        $dateType  = I('dateType') ? I('dateType') : 4; //日期类型
        $this->assign('gameType',$gameType);
        $this->assign('pageNum',$pageNum);
        $ModelName = $gameType == 1 ? 'gamble' : 'gamblebk';
        $blockTime = getBlockTime($gameType,$gamble=true); //推荐时间

        $user_id   = is_login(); 
        $nick_name = I('post.nick_name');
        $is_quiz   = I('post.is_quiz');
        if($nick_name != '' && isset($nick_name)) //昵称搜索
        {
            $FrontUser = M('FrontUser')->field('id')->where(['status'=>1,'nick_name'=>['like','%'.$nick_name.'%']])->select();
            $idArr = array_map('array_shift', $FrontUser);
            $map['r.user_id'] = ['in',$idArr];
        }
        if($dateType == 4) //获取日榜
        {
            $map['game_type']  = $where['game_type'] = $gameType;
            $map['list_date']  = $where['list_date'] =  date('Ymd',strtotime("-1 day"));
            //获取昨日红人榜
            $is_has = M('redList')->where ( $where )->count();

            if(!$is_has) $map['list_date']  = date('Ymd',strtotime("-2 day"));

            if($is_quiz == ''){
                $Ranking = D('redList')->where($map)->order('ranking asc')->select();
            }else{
                if($gameType == 1) $map['g.play_type'] = ['in',[1,-1]];
                $Ranking = M('RedList r')
                    ->field("r.user_id,r.id,r.ranking,r.winrate,r.pointCount,f.head,f.lv,f.lv_bk,f.nick_name,g.id is_quiz")
                    ->join('left join qc_front_user f on f.id = r.user_id')
                    ->join('left join qc_'.$ModelName.' g on g.user_id = r.user_id')
                    ->where(array_merge($map,['g.create_time'=>['between',[$blockTime['beginTime'],$blockTime['endTime']]]]))
                    ->group('r.user_id')
                    ->order('r.ranking')
                    ->select();
            }
        }
        else //获取排行榜
        {
            list($begin,$end)  = getRankDate($dateType);
            $map['gameType']   = $where['gameType']   = $gameType;
            $map['dateType']   = $where['dateType']   = $dateType;
            $map['begin_date'] = $where['begin_date'] = array("between",array($begin,$end));
            $map['end_date']   = $where['end_date']   = array("between",array($begin,$end));
            //查看是否有上周/月/季的数据
            $is_has = M('rankingList')->where($where)->count();

            if(!$is_has){
                list($begin,$end) = getTopRankDate($dateType);  //获取上上周的数据
                $map['begin_date'] = array("between",array($begin,$end));
                $map['end_date']   = array("between",array($begin,$end));
            }
            
            if($is_quiz == ''){
                $Ranking = D('rankingList')->where($map)->order('ranking asc')->select();
            }else{
                if($gameType == 1) $map['g.play_type'] = ['in',[1,-1]];
                $Ranking = M('rankingList r')
                    ->field("r.user_id,r.id,r.ranking,r.gameCount,r.win,r.half,r.level,r.transport,r.donate,r.winrate,r.pointCount,f.head,f.lv,f.lv_bk,f.nick_name,g.id is_quiz")
                    ->join('left join qc_front_user f on f.id = r.user_id')
                    ->join('left join qc_'.$ModelName.' g on g.user_id = r.user_id')
                    ->where(array_merge($map,['g.create_time'=>['between',[$blockTime['beginTime'],$blockTime['endTime']]]]))
                    ->group('r.user_id')
                    ->order('r.ranking')
                    ->select();
            }
        }
        
        $count     = count($Ranking); 
        $rankArr   = $count > 0 ? array_map('array_shift', $Ranking) : array(); //已上榜用户id
        $notRankid = array_merge(array_diff($idArr,$rankArr),array_diff($rankArr,$idArr)); //去掉已上榜用户id

        if($notRankid) //找出未上榜用户成绩
        {         
            foreach ($notRankid as $k => $v) {
                $notRank[] = $dateType == 4 
                        ? D('Common')->YestWinrate($v,$gameType) 
                        : D('GambleHall')->CountWinrate($v,$gameType,$dateType,true); //获取成绩
            }
            foreach ($notRank as $k => $v) {
                $userInfo = M('FrontUser')->field('nick_name,head,lv,lv_bk')->where(['id'=>$v['user_id']])->find();
                $notRank[$k]['nick_name'] = $userInfo['nick_name'];
                $notRank[$k]['head']      = $userInfo['head'];
                $notRank[$k]['lv']        = $userInfo['lv'];
                $notRank[$k]['lv_bk']     = $userInfo['lv_bk'];
            }
            //对数组进行排序,胜率>盈利积分>推荐场次数>全赢场次数>赢半场次数＞后台生成的会员编号
            $winrate = $pointCount = $gameCount = $win = $half = $userid = array();
            foreach ($notRank as $v) {
                $winrate   [] = $v['winrate'];
                $pointCount[] = $v['pointCount'];
                $gameCount [] = $v['gameCount'];
                $win       [] = $v['win'];
                $half      [] = $v['half'];
                $userid    [] = $v['user_id'];
            }
            array_multisort($winrate, SORT_DESC,
                            $pointCount, SORT_DESC,
                            $gameCount, SORT_DESC,
                            $win, SORT_DESC,
                            $half, SORT_DESC,
                            $userid, SORT_ASC, 
                            $notRank);
            if(!$Ranking) $Ranking = array();
            if(!$notRank) $notRank = array();
            //上榜与无上榜合并
            $Ranking = array_merge_recursive($Ranking,$notRank);
        }

        //实例化分页类
        $rankCount = count($Ranking);
        $page = new \Think\Page ( $rankCount, $pageNum );
        $Ranking = array_slice($Ranking, $page->firstRow,$page->listRows);
        
        foreach ($Ranking as $k => $v) //处理数据
        {
            //获取粉丝数量
            $Ranking[$k]['follow'] = M('followUser')->where(array('follow_id'=>$v['user_id']))->count();
            //获取头像
            $Ranking[$k]['face'] = frontUserFace($v['head']);
            if($v['is_quiz'] == ''){
                //今天是否有最近推荐
                $where['user_id']       = $v['user_id'];
                $where['create_time']   = ['between',[$blockTime['beginTime'],$blockTime['endTime']]];
                if($gameType == 1) $where['play_type']     = ['in',[1,-1]];
                $Ranking[$k]['is_quiz'] = M($ModelName)->where($where)->getField('id');
            }
            if($is_quiz != '') //是否有推荐筛选
            {
                if($Ranking[$k]['is_quiz'] == '') unset($Ranking[$k]);
            }
            $rank_id[] = $v['user_id'];
        }

        $this->assign('Ranking',$Ranking);
        
        if($user_id) //我的排名
        {
            $myRank = $dateType == 4 
                    ? D('Common')->getRedList($gameType,false,$user_id) 
                    : D('Common')->getRankingData($gameType,$dateType,$user_id); 
            $myRank = $myRank[0];
            if(!$myRank) //未上榜获取实时成绩
            {
                $myRank = $dateType == 4 
                        ? D('Common')->YestWinrate($user_id,$gameType) 
                        : D('GambleHall')->CountWinrate($user_id,$gameType,$dateType,true); //获取成绩
            }
            //获取粉丝数量
            $myRank['follow'] = M('followUser')->where(array('follow_id'=>$myRank['user_id']))->count();
            $this->assign('myRank',$myRank);
            //获取我关注的人
            $followIdArr = M("FollowUser")->where(['user_id'=>$user_id,'follow_id'=>['in',$rank_id]])->field("follow_id")->select();
            $followIds = array_map('array_shift', $followIdArr);
            $this->assign('followIds',$followIds);
        }
        //模板赋值显示
        $page->config  = array(
            'header' => '<span class="rows">共 %TOTAL_ROW% 条记录</span>',
            'prev'   => '<span aria-hidden="true">上一页</span>',
            'next'   => '<span aria-hidden="true">下一页</span>',
            'first'  => '首页',
            'last'   => '...%TOTAL_PAGE%',
            'theme'  => '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%',
        );
        $this->assign( "show", $page->showJump());
        $this->assign('totalCount', $count );
        $this->display();
    }

    /**
     * 日、周、月、季积分盈利榜 dwj
     */
    public function profit()
    {
        $pageNum   = 30; //每页页数
        $gameType  = I('gameType') ? I('gameType') : 1; //赛事类型
        $dateType  = I('dateType') ? I('dateType') : 4; //日期类型
        $this->assign('gameType',$gameType);
        $this->assign('pageNum',$pageNum);

        $ModelName = $gameType == 1 ? 'gamble' : 'gamblebk';
        $blockTime = getBlockTime($gameType,$gamble=true); //推荐时间

        $user_id   = is_login(); 
        $nick_name = I('post.nick_name');
        $is_quiz   = I('post.is_quiz');

        if($nick_name != '' && isset($nick_name)) //昵称搜索
        {
            $FrontUser = M('FrontUser')->field('id')->where(['status'=>1,'nick_name'=>['like','%'.$nick_name.'%']])->select();
            $idArr = array_map('array_shift', $FrontUser);
            $map['r.user_id'] = ['in',$idArr];
        }

        $map['gameType']   = $where['gameType'] = $gameType;
        $map['dateType']   = $where['dateType'] = $dateType;
        if($dateType == 4){
            $map['listDate']  = $where['listDate'] = date('Ymd', strtotime("-1 day"));
        }else{
            list($begin,$end) = getRankDate($dateType);
            $map['listDate']  = $where['listDate'] =  $end;
        }
        //查看是否有上周/月/季的数据
        $is_has = M('earnPointList')->where($where)->count();

        if(!$is_has){
            if($dateType == 4){
                $map['listDate']   = date('Ymd', strtotime("-2 day"));
            }else{
                list($begin,$end)  = getTopRankDate($dateType);
                $map['listDate']   = $end;
            }
        }

        if($is_quiz == ''){
            $Ranking = M('earnPointList r')
                ->field("r.user_id,r.id,r.ranking,r.gameCount,r.pointCount,f.head,f.lv,f.lv_bk,f.nick_name")
                ->join('left join qc_front_user f on f.id = r.user_id')
                ->where($map)
                ->group('r.user_id')
                ->order('r.ranking')
                ->select();
        }else{
            if($gameType == 1) $map['g.play_type'] = ['in',[1,-1]];
            $Ranking = M('earnPointList r')
                ->field("r.user_id,r.id,r.ranking,r.gameCount,r.pointCount,f.head,f.lv,f.lv_bk,f.nick_name,g.id is_quiz")
                ->join('left join qc_front_user f on f.id = r.user_id')
                ->join('left join qc_'.$ModelName.' g on g.user_id = r.user_id')
                ->where(array_merge($map,['g.create_time'=>['between',[$blockTime['beginTime'],$blockTime['endTime']]]]))
                ->group('r.user_id')
                ->order('r.ranking')
                ->select();
        }

        $count     = count($Ranking); 
        $rankArr   = $count > 0 ? array_map('array_shift', $Ranking) : array(); //已上榜用户id
        $notRankid = array_merge(array_diff($idArr,$rankArr),array_diff($rankArr,$idArr)); //去掉已上榜用户id

        if($notRankid) //找出未上榜用户成绩
        {         
            foreach ($notRankid as $k => $v) {
                $notRank[] = $dateType == 4 
                        ? D('Common')->YestWinrate($v,$gameType) 
                        : D('GambleHall')->CountWinrate($v,$gameType,$dateType,true); //我的排名
            }
            foreach ($notRank as $k => $v) {
                $userInfo = M('FrontUser')->field('nick_name,head,lv,lv_bk')->where(['id'=>$v['user_id']])->find();
                $notRank[$k]['nick_name'] = $userInfo['nick_name'];
                $notRank[$k]['head']      = $userInfo['head'];
                $notRank[$k]['lv']        = $userInfo['lv'];
                $notRank[$k]['lv_bk']     = $userInfo['lv_bk'];
            }
            //对数组进行排序,胜率>盈利积分>推荐场次数>全赢场次数>赢半场次数＞后台生成的会员编号
            $winrate = $pointCount = $gameCount = $win = $half = $userid = array();
            foreach ($notRank as $v) {
                $winrate   [] = $v['winrate'];
                $pointCount[] = $v['pointCount'];
                $gameCount [] = $v['gameCount'];
                $win       [] = $v['win'];
                $half      [] = $v['half'];
                $userid    [] = $v['user_id'];
            }
            array_multisort($winrate, SORT_DESC,
                            $pointCount, SORT_DESC,
                            $gameCount, SORT_DESC,
                            $win, SORT_DESC,
                            $half, SORT_DESC,
                            $userid, SORT_ASC, 
                            $notRank);
            if(!$Ranking) $Ranking = array();
            if(!$notRank) $notRank = array();
            //上榜与无上榜合并
            $Ranking = array_merge_recursive($Ranking,$notRank);
        }

        //实例化分页类
        $rankCount = count($Ranking);
        $page = new \Think\Page ( $rankCount, $pageNum );
        $Ranking = array_slice($Ranking, $page->firstRow,$page->listRows);

        foreach ($Ranking as $k => $v) //处理数据
        {
            //获取粉丝数量
            $Ranking[$k]['follow'] = M('followUser')->where(array('follow_id'=>$v['user_id']))->count();
            //获取头像
            $Ranking[$k]['face'] = frontUserFace($v['head']);
            if($v['is_quiz'] == ''){
                //今天是否有最近推荐
                $where['user_id']       = $v['user_id'];
                $where['create_time']   = ['between',[$blockTime['beginTime'],$blockTime['endTime']]];
                if($gameType == 1) $where['play_type']     = ['in',[1,-1]];
                $Ranking[$k]['is_quiz'] = M($ModelName)->where($where)->getField('id');
            }
            if($is_quiz != '') //是否有推荐筛选
            {
                if($Ranking[$k]['is_quiz'] == '') unset($Ranking[$k]);
            }
            $rank_id[] = $v['user_id'];
        }
        $this->assign('Ranking',$Ranking);

        if($user_id)
        {
            $myRank = D('Common')->getProfitData($gameType,$dateType,$user_id); //我的排名
            $myRank = $myRank[0];
            if(!$myRank) //未上榜获取实时成绩
            {
                $myRank = $dateType == 4 
                        ? D('Common')->YestWinrate($user_id,$gameType) 
                        : D('GambleHall')->CountWinrate($user_id,$gameType,$dateType,true); //我的排名
            }
            //获取粉丝数量
            $myRank['follow'] = M('followUser')->where(array('follow_id'=>$myRank['user_id']))->count();
            $this->assign('myRank',$myRank);
            //获取我关注的人
            $followIdArr = M("FollowUser")->where(['user_id'=>$user_id,'follow_id'=>['in',$rank_id]])->field("follow_id")->select();
            $followIds = array_map('array_shift', $followIdArr);
            $this->assign('followIds',$followIds);
        }
        //模板赋值显示
        $page->config  = array(
            'header' => '<span class="rows">共 %TOTAL_ROW% 条记录</span>',
            'prev'   => '<span aria-hidden="true">上一页</span>',
            'next'   => '<span aria-hidden="true">下一页</span>',
            'first'  => '首页',
            'last'   => '...%TOTAL_PAGE%',
            'theme'  => '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%',
        );
        $this->assign( "show", $page->showJump());
        $this->assign('totalCount', $count );
        $this->display();
    }

    /**
     * 日、周、月、季竞彩排行榜 dwj
     */
    public function rank_bet()
    {
        $pageNum   = 30; //每页页数
        $gameType  = I('gameType') ? I('gameType') : 1; //赛事类型
        $dateType  = I('dateType') ? I('dateType') : 4; //日期类型
        $this->assign('gameType',$gameType);
        $this->assign('pageNum',$pageNum);

        $ModelName = $gameType == 1 ? 'gamble' : 'gamblebk';
        $blockTime = getBlockTime($gameType,$gamble=true); //推荐时间

        $user_id   = is_login(); 
        $nick_name = I('post.nick_name');
        $is_quiz   = I('post.is_quiz');

        if($nick_name != '' && isset($nick_name)) //昵称搜索
        {
            $FrontUser = M('FrontUser')->field('id')->where(['status'=>1,'nick_name'=>['like','%'.$nick_name.'%']])->select();
            $idArr = array_map('array_shift', $FrontUser);
            $map['r.user_id'] = ['in',$idArr];
        }

        $map['gameType']   = $where['gameType'] = $gameType;
        $map['dateType']   = $where['dateType'] = $dateType;
        if($dateType == 4){
            $map['listDate']  = $where['listDate'] = date('Ymd', strtotime("-1 day"));
        }else{
            list($begin,$end) = getRankDate($dateType);
            $map['listDate']  = $where['listDate'] =  $end;
        }
        //查看是否有上周/月/季的数据
        $is_has = M('rankBetting')->where($where)->count();
        if(!$is_has){
            if($dateType == 4){
                $map['listDate']   = date('Ymd', strtotime("-2 day"));
            }else{
                list($begin,$end)  = getTopRankDate($dateType);
                $map['listDate']   = $end;
            }
        }

        if($is_quiz == ''){
            $Ranking = M('rankBetting r')
                ->field("r.user_id,r.id,r.ranking,r.gameCount,r.win,r.transport,r.winrate,r.pointCount,f.head,f.lv_bet,f.nick_name")
                ->join('left join qc_front_user f on f.id = r.user_id')
                ->where($map)
                ->group('r.user_id')
                ->order('r.ranking')
                ->select();
        }else{
            if($gameType == 1) $map['g.play_type'] = ['in',[2,-2]];
            $Ranking = M('rankBetting r')
                ->field("r.user_id,r.id,r.ranking,r.gameCount,r.win,r.transport,r.winrate,r.pointCount,f.head,f.lv_bet,f.nick_name,g.id is_quiz")
                ->join('left join qc_front_user f on f.id = r.user_id')
                ->join('left join qc_'.$ModelName.' g on g.user_id = r.user_id')
                ->where(array_merge($map,['g.create_time'=>['between',[$blockTime['beginTime'],$blockTime['endTime']]]]))
                ->group('r.user_id')
                ->order('r.ranking')
                ->select();
        }

        $count     = count($Ranking); 
        $rankArr   = $count > 0 ? array_map('array_shift', $Ranking) : array(); //已上榜用户id
        $notRankid = array_merge(array_diff($idArr,$rankArr),array_diff($rankArr,$idArr)); //去掉已上榜用户id

        if($notRankid) //找出未上榜用户成绩
        {     
            foreach ($notRankid as $k => $v) {
                $notRank[] = $dateType == 4 
                        ? D('Common')->YestWinrate($v,$gameType,0,2) 
                        : D('GambleHall')->CountWinrate($v,$gameType,$dateType,true,false,0,2); //我的排名
            }
            foreach ($notRank as $k => $v) {
                $userInfo = M('FrontUser')->field('nick_name,head,lv_bet')->where(['id'=>$v['user_id']])->find();
                $notRank[$k]['nick_name'] = $userInfo['nick_name'];
                $notRank[$k]['head']      = $userInfo['head'];
                $notRank[$k]['lv_bet']    = $userInfo['lv_bet'];
            }
            //对数组进行排序,胜率>盈利积分>推荐场次数>全赢场次数>后台生成的会员编号
            $winrate = $pointCount = $gameCount = $win = $userid = array();
            foreach ($notRank as $v) {
                $winrate   [] = $v['winrate'];
                $pointCount[] = $v['pointCount'];
                $gameCount [] = $v['gameCount'];
                $win       [] = $v['win'];
                $userid    [] = $v['user_id'];
            }
            array_multisort($winrate, SORT_DESC,
                            $pointCount, SORT_DESC,
                            $gameCount, SORT_DESC,
                            $win, SORT_DESC,
                            $userid, SORT_ASC, 
                            $notRank);
            if(!$Ranking) $Ranking = array();
            if(!$notRank) $notRank = array();
            //上榜与无上榜合并
            $Ranking = array_merge_recursive($Ranking,$notRank);
        }
        //实例化分页类
        $rankCount = count($Ranking);
        $page = new \Think\Page ( $rankCount, $pageNum );
        $Ranking = array_slice($Ranking, $page->firstRow,$page->listRows);

        foreach ($Ranking as $k => $v) //处理数据
        {
            //获取粉丝数量
            $Ranking[$k]['follow'] = M('followUser')->where(array('follow_id'=>$v['user_id']))->count();
            //获取头像
            $Ranking[$k]['face'] = frontUserFace($v['head']);
            if($v['is_quiz'] == ''){
                //今天是否有最近推荐
                $where['user_id']       = $v['user_id'];
                if($gameType == 1) $where['play_type']     = ['in',[2,-2]];
                $where['create_time']   = ['between',[$blockTime['beginTime'],$blockTime['endTime']]];
                $Ranking[$k]['is_quiz'] = M($ModelName)->where($where)->getField('id');
            }
            if($is_quiz != '') //是否有推荐筛选
            {
                if($Ranking[$k]['is_quiz'] == '') unset($Ranking[$k]);
            }
            $rank_id[] = $v['user_id'];
        }
        $this->assign('Ranking',$Ranking);

        if($user_id)
        {
            $myRank = D('Common')->getRankBetting($gameType,$dateType,$user_id); //我的排名
            $myRank = $myRank[0];
            if(!$myRank) //未上榜获取实时成绩
            {
                $myRank = $dateType == 4 
                        ? D('Common')->YestWinrate($user_id,$gameType,0,2) 
                        : D('GambleHall')->CountWinrate($user_id,$gameType,$dateType,true,false,0,2); //我的排名
            }
            //获取粉丝数量
            $myRank['follow'] = M('followUser')->where(array('follow_id'=>$myRank['user_id']))->count();
            $this->assign('myRank',$myRank);
            //获取我关注的人
            $followIdArr = M("FollowUser")->where(['user_id'=>$user_id,'follow_id'=>['in',$rank_id]])->field("follow_id")->select();
            $followIds = array_map('array_shift', $followIdArr);
            $this->assign('followIds',$followIds);
        }
        //模板赋值显示
        $page->config  = array(
            'header' => '<span class="rows">共 %TOTAL_ROW% 条记录</span>',
            'prev'   => '<span aria-hidden="true">上一页</span>',
            'next'   => '<span aria-hidden="true">下一页</span>',
            'first'  => '首页',
            'last'   => '...%TOTAL_PAGE%',
            'theme'  => '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%',
        );
        $this->assign( "show", $page->showJump());
        $this->assign('totalCount', $count );
        $this->display();
    }

    /**
     * 日、周、月、季竞彩积分盈利榜 dwj
     */
    public function profit_bet()
    {
        $pageNum   = 30; //每页页数
        $gameType  = I('gameType') ? I('gameType') : 1; //赛事类型
        $dateType  = I('dateType') ? I('dateType') : 4; //日期类型
        $this->assign('gameType',$gameType);
        $this->assign('pageNum',$pageNum);

        $ModelName = $gameType == 1 ? 'gamble' : 'gamblebk';
        $blockTime = getBlockTime($gameType,$gamble=true); //推荐时间

        $user_id   = is_login(); 
        $nick_name = I('post.nick_name');
        $is_quiz   = I('post.is_quiz');

        if($nick_name != '' && isset($nick_name)) //昵称搜索
        {
            $FrontUser = M('FrontUser')->field('id')->where(['status'=>1,'nick_name'=>['like','%'.$nick_name.'%']])->select();
            $idArr = array_map('array_shift', $FrontUser);
            $map['r.user_id'] = ['in',$idArr];
        }

        $map['gameType']   = $where['gameType'] = $gameType;
        $map['dateType']   = $where['dateType'] = $dateType;
        if($dateType == 4){
            $map['listDate']  = $where['listDate'] = date('Ymd', strtotime("-1 day"));
        }else{
            list($begin,$end) = getRankDate($dateType);
            $map['listDate']  = $where['listDate'] =  $end;
        }
        //查看是否有上周/月/季的数据
        $is_has = M('rankBetprofit')->where($where)->count();

        if(!$is_has){
            if($dateType == 4){
                $map['listDate']   = date('Ymd', strtotime("-2 day"));
            }else{
                list($begin,$end)  = getTopRankDate($dateType);
                $map['listDate']   = $end;
            }
        }

        if($is_quiz == ''){
            $Ranking = M('rankBetprofit r')
                ->field("r.user_id,r.id,r.ranking,r.gameCount,r.pointCount,f.head,f.lv_bet,f.nick_name")
                ->join('left join qc_front_user f on f.id = r.user_id')
                ->where($map)
                ->group('r.user_id')
                ->order('r.ranking')
                ->select();
        }else{
            if($gameType == 1) $map['g.play_type'] = ['in',[2,-2]];
            $Ranking = M('rankBetprofit r')
                ->field("r.user_id,r.id,r.ranking,r.gameCount,r.pointCount,f.head,f.lv_bet,f.nick_name,g.id is_quiz")
                ->join('left join qc_front_user f on f.id = r.user_id')
                ->join('left join qc_'.$ModelName.' g on g.user_id = r.user_id')
                ->where(array_merge($map,['g.create_time'=>['between',[$blockTime['beginTime'],$blockTime['endTime']]]]))
                ->group('r.user_id')
                ->order('r.ranking')
                ->select();
        }

        $count     = count($Ranking); 
        $rankArr   = $count > 0 ? array_map('array_shift', $Ranking) : array(); //已上榜用户id
        $notRankid = array_merge(array_diff($idArr,$rankArr),array_diff($rankArr,$idArr)); //去掉已上榜用户id

        if($notRankid) //找出未上榜用户成绩
        {         
            foreach ($notRankid as $k => $v) {
                $notRank[] = $dateType == 4 
                        ? D('Common')->YestWinrate($v,$gameType,0,2) 
                        : D('GambleHall')->CountWinrate($v,$gameType,$dateType,true,false,0,2); //我的排名
            }
            foreach ($notRank as $k => $v) {
                $userInfo = M('FrontUser')->field('nick_name,head,lv_bet')->where(['id'=>$v['user_id']])->find();
                $notRank[$k]['nick_name'] = $userInfo['nick_name'];
                $notRank[$k]['head']      = $userInfo['head'];
                $notRank[$k]['lv']        = $userInfo['lv_bet'];
            }
            //对数组进行排序,胜率>盈利积分>推荐场次数>全赢场次数>后台生成的会员编号
            $winrate = $pointCount = $gameCount = $win = $userid = array();
            foreach ($notRank as $v) {
                $winrate   [] = $v['winrate'];
                $pointCount[] = $v['pointCount'];
                $gameCount [] = $v['gameCount'];
                $win       [] = $v['win'];
                $userid    [] = $v['user_id'];
            }
            array_multisort($winrate, SORT_DESC,
                            $pointCount, SORT_DESC,
                            $gameCount, SORT_DESC,
                            $win, SORT_DESC,
                            $userid, SORT_ASC, 
                            $notRank);
            if(!$Ranking) $Ranking = array();
            if(!$notRank) $notRank = array();
            //上榜与无上榜合并
            $Ranking = array_merge_recursive($Ranking,$notRank);
        }

        //实例化分页类
        $rankCount = count($Ranking);
        $page = new \Think\Page ( $rankCount, $pageNum );
        $Ranking = array_slice($Ranking, $page->firstRow,$page->listRows);
        foreach ($Ranking as $k => $v) //处理数据
        {
            //获取粉丝数量
            $Ranking[$k]['follow'] = M('followUser')->where(array('follow_id'=>$v['user_id']))->count();
            //获取头像
            $Ranking[$k]['face'] = frontUserFace($v['head']);
            if($v['is_quiz'] == ''){
                //今天是否有最近推荐
                $where['user_id']       = $v['user_id'];
                if($gameType == 1) $where['play_type']     = ['in',[2,-2]];
                $where['create_time']   = ['between',[$blockTime['beginTime'],$blockTime['endTime']]];
                $Ranking[$k]['is_quiz'] = M($ModelName)->where($where)->getField('id');
            }
            if($is_quiz != '') //是否有推荐筛选
            {
                if($Ranking[$k]['is_quiz'] == '') unset($Ranking[$k]);
            }
            $rank_id[] = $v['user_id'];
        }
        $this->assign('Ranking',$Ranking);

        if($user_id)
        {
            $myRank = D('Common')->getRankBetprofit($gameType,$dateType,$user_id); //我的排名
            $myRank = $myRank[0];
            if(!$myRank) //未上榜获取实时成绩
            {
                $myRank = $dateType == 4 
                        ? D('Common')->YestWinrate($user_id,$gameType,0,2) 
                        : D('GambleHall')->CountWinrate($user_id,$gameType,$dateType,true,false,0,2); //我的排名
            }
            //获取粉丝数量
            $myRank['follow'] = M('followUser')->where(array('follow_id'=>$myRank['user_id']))->count();
            $this->assign('myRank',$myRank);
            //获取我关注的人
            $followIdArr = M("FollowUser")->where(['user_id'=>$user_id,'follow_id'=>['in',$rank_id]])->field("follow_id")->select();
            $followIds = array_map('array_shift', $followIdArr);
            $this->assign('followIds',$followIds);
        }
        //模板赋值显示
        $page->config  = array(
            'header' => '<span class="rows">共 %TOTAL_ROW% 条记录</span>',
            'prev'   => '<span aria-hidden="true">上一页</span>',
            'next'   => '<span aria-hidden="true">下一页</span>',
            'first'  => '首页',
            'last'   => '...%TOTAL_PAGE%',
            'theme'  => '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%',
        );
        $this->assign( "show", $page->showJump());
        $this->assign('totalCount', $rankCount );
        $this->display();
    }

    //兑换中心
    public function exchange()
    {
        if (IS_AJAX){
            parent::exchange();
            return;
        }
        $userid = is_login();
        $userInfo = M('FrontUser')->where(['id'=>$userid])->field('point,head')->find();
        $this->point = $userInfo['point'];
        $this->face = frontUserFace($userInfo['head']);
        $config = getWebConfig('platformSetting');
        $this->assign('config',$config);
        $this->display();
    }

    //推荐规则
    public function rule()
    {
        $this->display();
    }

    //我的推荐
    public function myGamble()
    {
		$userId = is_login();
		if (!$userId)
		{
			$this->redirect('User/login');
		}

        $game_type   = I('get.game_type') ? : 1;
        $gamble_type = I('gamble_type') ? : 1;
        $ModelName   = $game_type == 1 ? 'GambleView' : 'GamblebkView';
        
        //获取用户等级
        $userLv = M('FrontUser')->where(['id'=>$userId])->field("lv,lv_bk,lv_bet")->find();
        switch ($game_type) {
            case '1':
                $lv = $gamble_type == 1 ? $userLv['lv'] : $userLv['lv_bet'];
                break;
            case '2':
                $lv = $userLv['lv_bk'];
                break;
        }
        $this->assign('lv',$lv);
		//统计用户推荐的赢、平、输的场数(篮球、足球)
        $resultArr = $this->get_gamble_result($game_type,'',$gamble_type);
		$this->assign('resultArr',$resultArr);
        //最近10场
        $TenGamble = $this->getTenGamble($userId,$game_type,$gamble_type);
        $this->assign('TenGamble',$TenGamble);
        //获取连胜
        $winning = D('GambleHall')->getWinning($userId,$game_type,0,$gamble_type,0);
        $this->assign('winning',$winning);
        //周推荐记录
        $ballWeek = $this->CountWinrate($userId,$game_type,1,true,false,0,$gamble_type);
        $this->assign('ballWeek',$ballWeek);
        //月推荐记录
        $ballMonth = $this->CountWinrate($userId,$game_type,2,true,false,0,$gamble_type);
        $this->assign('ballMonth',$ballMonth);
        //季推荐记录
        $ballSeason = $this->CountWinrate($userId,$game_type,3,true,false,0,$gamble_type);
        $this->assign('ballSeason',$ballSeason);
        //推荐记录
        $map['user_id']   = $userId;
        $map['play_type'] = $gamble_type == 1 ? ['in',[1,-1]] : ['in',[2,-2]];
        $history = $this->_list(D($ModelName),$map,10,'id desc','','',"",2);
        $history = HandleGamble($history);
        $this->assign('history',$history);
        $this->display();
    }

    //足球高手推荐
    public function statistics(){
        $game_type = 1;
        $game_id   = I("get.game_id");
        $play_type = I("get.play_type") ?:1;
        //获取赛程信息
        $gameFbinfo = M('gameFbinfo')->alias('g')
        ->join("LEFT JOIN qc_union u on u.union_id = g.union_id")
        ->where(['game_id'=>$game_id])->field("g.game_id,g.score,g.game_half_time,g.half_score,g.game_state,g.fsw_exp,g.fsw_ball,g.gtime,g.union_name,g.home_team_id,g.home_team_name,g.away_team_id,g.away_team_name,u.union_color")->find();
        $gameFbinfo = getTeamLogo($gameFbinfo);
        $this->assign('gameArr',$gameFbinfo);

        //获取参与推荐用户数据
        if(!$QuizUser = S('web_statistics_'.$game_id.$play_type.$game_type)){
            $QuizUser = $this->getUserRank($game_id,$play_type);
            S('web_statistics_'.$game_id.$play_type.$game_type,$QuizUser,120);
        }

        //获取关联该赛事id的推荐资讯
        $news = M("PublishList")->field('id,title')->where(['game_id'=>$game_id,'status'=>1])->order('update_time desc')->find();
        $this->assign('news',$news);
        //实例化分页类
        $count = count($QuizUser);
        $page = new \Think\Page ( $count, 20 );
        //分页跳转的时候保证查询条件
        $map = array(
                'game_id'=>$game_id,
                'play_type'=>$play_type,
            );
        foreach ( $map as $key => $val ) {
          if (! is_array ( $val )) {
            $page->parameter .= "$key=" . urlencode ( $val ) . "&";
          }
        }
        //自定义分页样式
        $page->config  = array(
            'header' => '<span class="rows">共 %TOTAL_ROW% 条记录</span>',
            'prev'   => '<span aria-hidden="true">上一页</span>',
            'next'   => '<span aria-hidden="true">下一页</span>',
            'first'  => '首页',
            'last'   => '...%TOTAL_PAGE%',
            'theme'  => '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%',
        );
        //设置分页路由链接
        $page->url = "/statistics/{$game_id}/{$play_type}/%5BPAGE%5D.html";
        $QuizUser = array_slice($QuizUser,$page->firstRow,$page->listRows);
        switch ($game_type) {
            case '1':
                switch ($play_type) 
                {
                    case '1':
                    case '-1':
                        $gambleType = 1;
                        break;
                    case '2':
                    case '-2':
                        $gambleType = 2;
                        break;
                }
                break;
        }
        foreach ($QuizUser as $k => $v) {
            $QuizUser[$k]['monthWin'] = D('GambleHall')->CountWinrate($v['user_id'],$game_type,2,false,false,0,$gambleType);
            //处理头像
            $QuizUser[$k]['face'] = frontUserFace($v['head']);
            //当前连胜
            $Winning = D('GambleHall')->getWinning($v['user_id'],$game_type,0,$gambleType);
            $QuizUser[$k]['Winning'] = $Winning;
            //近10场记录
            $QuizUser[$k]['tenArray'] = $Winning['tenGambleArr'];
        }
        //dump($QuizUser);
        $gambleIdArr = array_map("array_shift", $QuizUser);
        if(is_login()){
            //是否已被查看
            $quizLog = M('quizLog')->master(true)->where(array('game_type'=>$game_type,'user_id'=>is_login(),'gamble_id'=>['in',$gambleIdArr]))->getField('gamble_id',true);
            foreach ($QuizUser as $k => $v) {
                if(in_array($v['id'], $quizLog)){
                    $QuizUser[$k]['is_check'] = 1;
                }
            }
        }
        //获取用户ID，并获取用户的粉丝数
        $userIdArr = array();
        foreach ($QuizUser as $key => $value) {
            $userIdArr[] = $value['user_id'];
        }
        $followUser = M('FollowUser')->where(['follow_id' => ['in', $userIdArr]])->field('follow_id,count(id) as FollowNumber')->group('follow_id')->select();
        if (!empty($followUser)) {
            foreach ($QuizUser as $key => $value) {
                foreach ($followUser as $k => $v) {
                    if ($value['user_id'] == $v['follow_id']) {
                        $QuizUser[$key]['FollowNumber'] = $v['FollowNumber'];
                    }
                }
            }
        }
        //模板赋值显示
        $this->assign ( "show", $page->showJs());
        //页数
        $this->assign ( "pageCount", $count/$page->listRows);
        $this->assign('QuizUser',$QuizUser);

        //获取我关注的人
        $followIdArr = M("FollowUser")->where(array('user_id'=>is_login()))->field("follow_id")->select();
        foreach ($followIdArr as $key => $value) {
            $followIds[] = $value['follow_id'];
        }
        $this->assign('followIds',$followIds);
        $this->display();
    }

    //篮球高手推荐
    public function statistics_bk(){
        $game_type = 2;
        $game_id   = I("get.game_id");
        $play_type = I("get.play_type") ?:1;
        //获取赛程信息
        $gameBkinfo = M('gameBkinfo')->alias('g')
        ->join("LEFT JOIN qc_bk_union u on u.union_id = g.union_id")
        ->where(['game_id'=>$game_id])->field("g.game_id,g.score,g.game_half_time,g.half_score,g.game_state,g.fsw_exp,g.fsw_total,g.psw_exp,g.psw_total,total,g.gtime,g.union_name,g.home_team_id,g.home_team_name,g.away_team_id,g.away_team_name,u.union_color")->find();
        $gameBkinfo = getTeamLogo($gameBkinfo,2);
        $this->assign('gameArr',$gameBkinfo);

        //获取参与推荐用户数据
        if(!$QuizUser = S('web_statisticsbk_'.$game_id.$play_type.$game_type)){
            $QuizUser = $this->getUserRank($game_id,$play_type,$game_type);
            S('web_statisticsbk_'.$game_id.$play_type.$game_type,$QuizUser,120);
        }

        //获取关联该赛事id的推荐资讯
        $news = M("PublishList")->field('id,title')->where(['gamebk_id'=>$game_id,'status'=>1])->order('update_time desc')->find();
        $this->assign('news',$news);
        //实例化分页类
        $count = count($QuizUser);
        $page = new \Think\Page ( $count, 20 );
        //分页跳转的时候保证查询条件
        $map = array(
                'game_id'=>$game_id,
                'play_type'=>$play_type,
            );
        foreach ( $map as $key => $val ) {
          if (! is_array ( $val )) {
            $page->parameter .= "$key=" . urlencode ( $val ) . "&";
          }
        }
        //自定义分页样式
        $page->config  = array(
            'header' => '<span class="rows">共 %TOTAL_ROW% 条记录</span>',
            'prev'   => '<span aria-hidden="true">上一页</span>',
            'next'   => '<span aria-hidden="true">下一页</span>',
            'first'  => '首页',
            'last'   => '...%TOTAL_PAGE%',
            'theme'  => '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%',
        );
        //设置分页路由链接
        $page->url = "/statistics_bk/{$game_id}/{$play_type}/%5BPAGE%5D.html";
        $QuizUser = array_slice($QuizUser,$page->firstRow,$page->listRows);
        foreach ($QuizUser as $k => $v) {
            $QuizUser[$k]['monthWin'] = D('GambleHall')->CountWinrate($v['user_id'],$game_type,2);
            //处理头像
            $QuizUser[$k]['face'] = frontUserFace($v['head']);
            //当前连胜
            $Winning = D('GambleHall')->getWinning($v['user_id'],$game_type,0,$gambleType);
            $QuizUser[$k]['Winning'] = $Winning;
            //近10场记录
            $QuizUser[$k]['tenArray'] = $Winning['tenGambleArr'];
        }
        $gambleIdArr = array_map("array_shift", $QuizUser);
        if(is_login()){
            //是否已被查看
            $quizLog = M('quizLog')->master(true)->where(array('game_type'=>$game_type,'user_id'=>is_login(),'gamble_id'=>['in',$gambleIdArr]))->getField('gamble_id',true);
            foreach ($QuizUser as $k => $v) {
                if(in_array($v['id'], $quizLog)){
                    $QuizUser[$k]['is_check'] = 1;
                }
            }
        }
        //获取用户ID，并获取用户的粉丝数
        $userIdArr = array();
        foreach ($QuizUser as $key => $value) {
            $userIdArr[] = $value['user_id'];
        }
        $followUser = M('FollowUser')->where(['follow_id' => ['in', $userIdArr]])->field('follow_id,count(id) as FollowNumber')->group('follow_id')->select();
        if (!empty($followUser)) {
            foreach ($QuizUser as $key => $value) {
                foreach ($followUser as $k => $v) {
                    if ($value['user_id'] == $v['follow_id']) {
                        $QuizUser[$key]['FollowNumber'] = $v['FollowNumber'];
                    }
                }
            }
        }
        //模板赋值显示
        $this->assign ( "show", $page->showJs());
        //页数
        $this->assign ( "pageCount", $count/$page->listRows);
        $this->assign('QuizUser',$QuizUser);

        //获取我关注的人
        $followIdArr = M("FollowUser")->where(array('user_id'=>is_login()))->field("follow_id")->select();
        foreach ($followIdArr as $key => $value) {
            $followIds[] = $value['follow_id'];
        }
        $this->assign('followIds',$followIds);
        $this->display();
    }

    /**
     * 获取参与赛程推荐的用户记录
     *
     * @param int  $game_id     赛程id
     * @param int  $play_type   玩法(1:让分 -1:大小) 为篮球时(1:全场让分 -1:全场大小 2:半场让分 -2:半场大小)
     * @param int  $game_type   赛事类型 1:足球  2:篮球
     *
     * @return  array
    */
    public function getUserRank($game_id,$play_type,$game_type=1){
        $gameModel   = $game_type == 1 ? M('gamble g') : M('gamblebk g');
        switch ($game_type) {
            case '1':
                switch ($play_type) 
                {
                    case '1':
                    case '-1':
                        $Lv = 'lv';
                        $gambleType = 1;
                        break;
                    case '2':
                    case '-2':
                        $Lv = 'lv_bet';
                        $gambleType = 2;
                        break;
                }
                break;
            case '2':
                $Lv = 'lv_bk';
                break;
        }
        //获取参与该赛程推荐的记录
        $gamble = $gameModel
                ->join("left join qc_front_user f on f.id=g.user_id")
                ->where(['g.game_id'=>$game_id,'g.play_type'=>$play_type])
                ->field("g.id,g.game_id,g.user_id,g.is_impt,g.union_name,g.home_team_name,g.away_team_name,g.result,g.play_type,g.chose_side,g.handcp,g.odds,g.tradeCoin,g.desc,g.create_time,f.nick_name,f.{$Lv} lv,f.head")
                ->select();
        if(!$gamble){
            return;
        }
        $gamble = HandleGamble($gamble,0,true,$game_type);
        foreach ($gamble as $k => $v) {
            //获取比赛结果和推荐信息
            //周胜率
            $gamble[$k]['weekWin'] = D('GambleHall')->CountWinrate($v['user_id'],$game_type,1,false,false,0,$gambleType);
            //该场销量
            $gamble[$k]['check_number'] = M('quizLog')->where(array('gamble_id'=>$v['id']))->count();
            
        }
        //分开付费和免费
        $freeGamble = array();
        $payGamble  = array();
        foreach ($gamble as $k => $v) {
            if($v['tradeCoin'] == 0){
                $freeGamble[] = $v; //免费
            }else{
                $payGamble[] = $v;  //付费
            }
        }

        //付费排序
        $payGamble  = $this->sortGamble($payGamble);
        //免费排序
        $freeGamble = $this->sortGamble($freeGamble);
        //付费与免费合并
        $rankArr = array_merge_recursive($payGamble,$freeGamble);
        
        return $rankArr;
    }

    //排序 "等级＞周胜率＞该场销量＞发布时间" 排序
    public function sortGamble($Gamble)
    {
        foreach ($Gamble as $k => $v) 
        {
            $sort_lv[]      = $v['lv'];        //等级
            $sort_weekWin[] = $v['weekWin'];  //周胜率
            $sort_check[]   = $v['check_number']; //该场销量
            $sort_time[]    = $v['create_time']; //发布时间
        }
        array_multisort($sort_lv,SORT_DESC,$sort_weekWin,SORT_DESC,$sort_check,SORT_DESC,$sort_time,SORT_DESC,$Gamble);
        return $Gamble;
    }

    //充值页面
    public function recharge()
    {
        $userId = is_login();
        if($userId){
            $coin = M('FrontUser')->field("sum(coin+unable_coin) as coin")->where(['id'=>$userId])->find();
            $this->assign('coin',$coin['coin']);
        }

        //获取支付赠送
        $rechargeConfig = M('config')->where(['sign' => 'recharge'])->getField('config');
        $recharge = json_decode($rechargeConfig, true);
        $rechargeBind = $recharge['recharge'][0]['account'];
        $rechargeNum = 0;
        foreach ($recharge['recharge'] as $value) {
            if ($value['account']  == $rechargeBind) {
                $rechargeNum = $value['number'];
            }
        }
        $this->assign("rechargeBind", $rechargeBind);
        $this->assign("rechargeNum", $rechargeNum);
        $this->assign('recharge' ,$recharge['recharge']);
        $this->display();
    }


}


 ?>