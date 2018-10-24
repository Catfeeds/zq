<?php
/**
 * 竞猜大厅
 * @author huangjiezhen <418832673@qq.com> 2015.12.02
 */

class GambleHallController extends PublicController
{
    //首页
    public function index()
    {
        $info = $this->param['info'] ?: 'football';

        if ($info == 'football')
        {
            list($game, $union) = D('GambleHall')->matchList();
            $adver = @Think\Tool\Tool::getAdList($classId=20,20,$this->param['platform']) ?: [];
            $adver = D('Home')->getBannerShare($adver);//添加分享图片和标题

            foreach ($adver as $k => $v)
            {
                unset($adver[$k]['id']);
                unset($adver[$k]['img']);
            }
            $this->ajaxReturn(['matchList'=>$game,'union'=>$union,'adver'=>$adver]);
        }
        else if ($info == 'basketball')
        {
            $this->ajaxReturn();
        }
        else
        {
            $this->ajaxReturn(2001);
        }
    }

    //赛事是否有竞猜
    public function hasGamble()
    {
        $blockTime = getBlockTime(1);            /*AND g.gtime between {$blockTime['beginTime']} AND {$blockTime['endTime']}*/
        $gameId = $this->param['game_id'];

        $sql = "
            SELECT g.id,g.fsw_exp_home,g.fsw_exp,g.fsw_exp_away,g.fsw_ball_home,g.fsw_ball,g.fsw_ball_away,g.gtime
            FROM __PREFIX__game_fbinfo g
            LEFT JOIN __PREFIX__union u ON g.union_id = u.union_id
            WHERE
                g.game_id = ".$gameId."
            AND g.status = 1
            AND ((g.is_show = 1 AND u.is_sub < 3) or g.is_gamble = 1)
            AND g.fsw_exp       != ''
            AND g.fsw_ball      != ''
            AND g.fsw_exp_home  != ''
            AND g.fsw_exp_away  != ''
            AND g.fsw_ball_home != ''
            AND g.fsw_ball_away != ''
        ";
        $game = M()->query($sql);

        if ($game && $game[0]['gtime'] >= $blockTime['beginTime'] && $game[0]['gtime'] <= $blockTime['endTime'])
            $has = 1;
        else
            $has = 0;

        //查询实时盘口，赔率
        $pcData = new \Home\Services\PcdataService();
        $res = $pcData->getOddsById($gameId, 2)[$gameId];

        $data = [
            'has'           => $has,
            'fsw_exp_home'  => $res[18] != '' ? $res[18] : ( $res[9] != '' ?  $res[9] : ($res[0] != '' ? $res[0] : $game[0]['fsw_exp_home'])),  //让球主队的赔率
            'fsw_exp'       => $res[19] != '' ? $res[19] : ($res[10] != '' ? $res[10] : ($res[1] != '' ? $res[1] : $game[0]['fsw_exp'])),      //让球盘口
            'fsw_exp_away'  => $res[20] != '' ? $res[20] : ($res[11] != '' ? $res[11] : ($res[2] != '' ? $res[2] : $game[0]['fsw_exp_away'])), //让球客队赔率
            'fsw_ball_home' => $res[21] != '' ? $res[21] : ($res[12] != '' ? $res[12] : ($res[3] != '' ? $res[3] : $game[0]['fsw_ball_home'])),//大小球 大的赔率
            'fsw_ball'      => $res[22] != '' ? $res[22] : ($res[13] != '' ? $res[13] : ($res[4] != '' ? $res[4] : $game[0]['fsw_ball'])),     //大小球盘口
            'fsw_ball_away' => $res[23] != '' ? $res[23] : ($res[14] != '' ? $res[14] : ($res[5] != '' ? $res[5] : $game[0]['fsw_ball_away'])),//大小球 小的赔率
        ];

        $data['fsw_exp']  = changeExp($data['fsw_exp']);
        $data['fsw_ball'] = changeExp($data['fsw_ball']);

        $gamble = (array)M('Gamble')->field(['play_type','chose_side'])->where(['game_id'=>$this->param['game_id']])->select();

        //计算竞猜玩法百分比
        $spreadNum  = 0; //让分
        $spreadHome = 0; //让分(主)
        $totalNum   = 0; //大小球
        $totalBig   = 0; //大小球(大)

        foreach ($gamble as $v)
        {
            if ($v['play_type'] == 1)
            {
                $spreadNum++;

                if ($v['chose_side'] == 1)
                    $spreadHome++;
            }
            else
            {
                $totalNum++;

                if ($v['chose_side'] == 1)
                    $totalBig++;
            }
        }
/*
        $data['persent']['spreadNum']  = $spreadNum;
        $data['persent']['spreadHome'] = $spreadHome;
        $data['persent']['spreadAway'] = $spreadNum - $spreadHome;
        $data['persent']['totalNum']   = $totalNum;
        $data['persent']['totalBig']   = $totalBig;
        $data['persent']['totalSmall'] = $totalNum - $totalBig;
*/

        $data['persent']['spreadNum']  = 0;
        $data['persent']['spreadHome'] = 0;
        $data['persent']['spreadAway'] = 0;
        $data['persent']['totalNum']   = 0;
        $data['persent']['totalBig']   = 0;
        $data['persent']['totalSmall'] = 0;

        $this->ajaxReturn($data);
    }

    //竞猜统计
    public function gambleCount()
    {
        $page = $this->param['page'] ?: 1;
        $pageNum = 20;

        $list = (array)M('Gamble')->alias("g")
                                  ->join("left join qc_front_user f on f.id = g.user_id")
                                  ->field(['g.id gamble_id','g.user_id','g.play_type','g.chose_side','g.handcp','g.odds','g.is_impt','g.result','g.tradeCoin','g.desc','g.create_time','f.head face','f.nick_name','f.lv'])
                                  ->where(['game_id'=>$this->param['game_id'],'play_type'=>$this->param['play_type']])
                                  ->select();

        $lv = $weekSort = $tradeCoin = $tradeCount = $sortTime = []; //用户排序依据数组

        foreach ($list as $k => $v)
        {
            $lv[]                      = $v['lv'];
            $sortTime[]                = $v['create_time'];
            $tradeCoin[]               = $v['tradeCoin'];
            $tradeCount[]              = D('QuizLog')->where(['game_type' => 1, 'gamble_id'=>$v['gamble_id']])->count();
            $list[$k]['weekPercnet']   = $weekSort[] = D('GambleHall')->CountWinrate($v['user_id'],1,1);
            $list[$k]['monthPercnet']  = D('GambleHall')->CountWinrate($v['user_id'],1,2);
            $list[$k]['seasonPercnet'] = D('GambleHall')->CountWinrate($v['user_id'],1,3);
        }

        array_multisort($lv,SORT_DESC, $weekSort,SORT_DESC, $tradeCoin,SORT_DESC, $tradeCount,SORT_DESC, $sortTime,SORT_DESC, $list);
        $list = array_slice($list, ($page-1)*$pageNum, $pageNum);

        $gambleId = []; //本赛程查看过的竞猜记录
        $userToken = getUserToken($this->param['userToken']);

        if ($userToken && $userToken != -1)
            $gambleId = (array)M('QuizLog')->where(['game_type' => 1, 'user_id'=>$userToken['userid'],'game_id'=>$this->param['game_id']])->getField('gamble_id',true);

        foreach ($list as $k => $v)
        {
            //用户信息
            $list[$k]['face']          = frontUserFace($v['face']);
            $list[$k]['tenGamble']     = D('GambleHall')->getTenGamble($v['user_id'],1);
            $list[$k]['is_trade']      = in_array($v['gamble_id'],$gambleId) ? 1 : 0;
            $list[$k]['desc'] = (string)$list[$k]['desc'];
            unset($list[$k]['create_time']);
        }

        $this->ajaxReturn(['gambleList'=>$list]);
    }

    //个人中心
    public function user()
    {
        $winnig                    = D('GambleHall')->getWinning($this->param['user_id'],$gameType=1); //竞猜统计信息
        $userInfo                  = M('FrontUser')->field(['nick_name','lv','descript','head face'])->where(['id'=>$this->param['user_id']])->find();
        $userInfo                  = array_merge($userInfo,$winnig);
        $userInfo['fansNum']       = M('FollowUser')->where(['follow_id'=>$this->param['user_id']])->count();
        $userInfo['face']          = frontUserFace($userInfo['face']);
        $userInfo['weekPercnet']   = D('GambleHall')->CountWinrate($this->param['user_id'],1,1);
        $userInfo['monthPercnet']  = D('GambleHall')->CountWinrate($this->param['user_id'],1,2);
        $userInfo['seasonPercnet'] = D('GambleHall')->CountWinrate($this->param['user_id'],1,3);
        $tenGamble                 = D('GambleHall')->getTenGamble($this->param['user_id'], 1);
        $userInfo['tenGambleRate'] = countTenGambleRate($tenGamble); //近十场的胜率

        $userToken = getUserToken($this->param['userToken']);

        if ($userToken)
        {
            $isFollow = M('FollowUser')->where(['user_id'=>$userToken['userid'],'follow_id'=>$this->param['user_id']])->find(); //是否已经关注
            $userInfo['isFollow'] = $isFollow ? 1 : 0;
        }
        else
        {
            $userInfo['isFollow'] = 0;
        }
        $gamble_id = isset($this->param['gamble_id']) ? (int)$this->param['gamble_id'] : 0;
        $page = $this->param['page'] ?: 1;
        $gambleList = D('GambleHall')->getGambleList($this->param['user_id'],$this->param['play_type'],$page , $gamble_id);
//        $gambleList = D('GambleHall')->getGambleList($this->param['user_id']); //竞猜记录

        foreach ($gambleList as $k => $v)
        {
            if ($userToken) //如已经登陆
            {
                $isTrade = M('QuizLog')->master(true)->master(true)->where(['game_type' => 1, 'user_id'=>$userToken['userid'],'gamble_id'=>$v['gamble_id']])->getField('id');
                $gambleList[$k]['is_trade'] = $isTrade ? 1 : 0;
            }
            else
            {
                $gambleList[$k]['is_trade'] = 0;
            }
        }
        $returnArr = ['userInfo'=>$userInfo,'gambleList'=>$gambleList];
        if($page > 1)
            unset($returnArr['userInfo']);
        $this->ajaxReturn($returnArr);
    }

    //用户竞猜的列表
    public function userGamble()
    {
        $gamble_id = isset($this->param['gamble_id']) ? (int)$this->param['gamble_id'] : 0;
        $userToken = getUserToken($this->param['userToken']);
        $gambleList = D('GambleHall')->getGambleList($this->param['user_id'],$this->param['play_type'], $this->param['page'] ?: 1, $gamble_id); //竞猜记录

        foreach ($gambleList as $k => $v)
        {
            if ($userToken) //如已经登陆
            {
                $isTrade = M('QuizLog')->where(['game_type' => 1, 'user_id'=>$userToken['userid'],'gamble_id'=>$v['gamble_id']])->getField('id');
                $gambleList[$k]['is_trade'] = $isTrade ? 1 : 0;
            }
            else
            {
                $gambleList[$k]['is_trade'] = 0;
            }
        }

        $this->ajaxReturn(['gambleList'=>$gambleList]);
    }

    //排行榜
    public function rank()
    {
        $myRank     = [];
        $pageNum    = 20;
        $page       = $this->param['page'] ?: 1;
        $blockTime  = getBlockTime(1,$gamble=true);
        $userToken  = getUserToken($this->param['userToken']);
        $myRankKey  = MODULE_NAME.'myRank:' . $this->param['dateType'] . $userToken['userid'];

        if ($this->param['dateType'] == 4) //红人榜
        {
            $listDate = date('Ymd',strtotime("-1 day"));
            $exist = M('RedList')->where(['list_date'=>$listDate,'game_type'=>1])->field('id')->find();

            if (!$exist)
                $listDate = date('Ymd',strtotime("-2 day"));

            $cacheKey = MODULE_NAME.'_game_rank:' . $listDate . $this->param['todayGamble'] . $page . $pageNum;
            $expire = 600;

            $field = ['r.user_id','r.ranking','r.gameCount','r.win','r.half','r.`level`','r.transport','r.donate','r.winrate','r.pointCount'];
            $where = ['r.list_date'=>$listDate,'r.game_type'=>1];

            if(!$rank = S($cacheKey)){//读取缓存

                if ($this->param['todayGamble'])
                {
                    $rank = (array)M('RedList r')
                        ->cache($cacheKey, $expire ,'Redis')
                        ->field($field)
                        ->join('left join __GAMBLE__ g on g.user_id = r.user_id')
                        ->where(array_merge($where,['g.create_time'=>['between',[$blockTime['beginTime'],$blockTime['endTime']]]]))
                        ->group('r.user_id')
                        ->order('r.ranking')
                        ->page($page.','.$pageNum)
                        ->select();
                }
                else
                {
                    $rank = (array)M('RedList r')->cache($cacheKey, $expire ,'Redis')->field($field)->where($where)->page($page.','.$pageNum)->select();
                }
            }

            //我的排名
            if( $userToken ){

                if(!$myRank = S($myRankKey)){

                    $where['r.user_id'] = $userToken['userid'];
                    $rankData = (array) M('RedList r')->field($field)->where($where)->select();

                    if($rankData){
                        $myRank = $rankData[0];
                        $myRank['ranking'] .= '名';
                    }
                    else
                    {
                        $myRank = ydayGambleRate($userToken['userid']);
                        $myRank['ranking'] = '未上榜';
                    }

                    if( $myRank )
                        S($myRankKey, $myRank, 300);
                }

            }

        }
        else
        {
            //我的排名
            if( $userToken ) {
                if(!$myRank = S($myRankKey)){
                    $rankData = (array) D('GambleHall')->getRankingData($gameType = 1, $this->param['dateType'], $user_id = $userToken['userid']);

                    if($rankData){
                        $myRank = $rankData[0];
                        $myRank['ranking'] .= '名';
                    }
                    else
                    {
                        $myRank = D('GambleHall')->CountWinrate($userToken['userid'], 1, $this->param['dateType'], true);
                        $myRank['ranking'] = '未上榜';
                    }

                    if( $myRank )
                        S($myRankKey,$myRank,300);
                }
            }

            //周、月、季
            $rank = (array)D('GambleHall')->getRankingData($gameType=1,$this->param['dateType'],$user_id=null,$more=false,$page,$pageNum,$this->param['todayGamble']);

        }

        foreach ($rank as $k => $v)
        {
            //用户信息
            $userInfo = M('FrontUser')->where(['id'=>$v['user_id']])->field('nick_name,head')->find();
            $rank[$k]['nick_name']    = $userInfo['nick_name'];
            $rank[$k]['face']         = frontUserFace($userInfo['head']);
            $rank[$k]['today_gamble'] = M('Gamble')->where(['user_id'=>$v['user_id'],'create_time'=>['between',[$blockTime['beginTime'],$blockTime['endTime']]]])->getField('id') ? 1 : 0;

            //是否已经关注
            $isFollow = 0;

            if ($userToken)
                $isFollow = M('FollowUser')->where(['user_id'=>$userToken['userid'],'follow_id'=>$v['user_id']])->find();

            $rank[$k]['isFollow'] = $isFollow ? 1 : 0;
        }

        //我的排名数据重构
        $myRankRet = $userToken ? $myRankRet = [
            'ranking'   => (string) $myRank['ranking'],
            'win'       => (string) ($myRank['win'] + $myRank['half']),
            'level'     => (string) $myRank['level'],
            'transport' => (string) ($myRank['donate'] + $myRank['transport']),
            'winrate'   => (string) $myRank['winrate']
        ] : [];


        $this->ajaxReturn(['myRank' => $myRankRet, 'rankList' => $rank]);
    }

    //兑换中心
    public function exchange()
    {
        //banner图
        $class_id = M('recommendClass')->where(['sign'=>'exchange'])->getField('id');
        $Recommend = (array)M("Recommend")
                    ->field(['id','title','type','url','img'])
                    ->where(['class_id'=>$class_id,'status'=>1])
                    ->order("sort desc")
                    ->select();

        foreach( $Recommend as $k => $v )
        {
            $Recommend[$k]['img']  = Think\Tool\Tool::imagesReplace( $v['img'] ) ?: '';
            unset($Recommend[$k]['id']);
        }

        //礼品兑换列表
        $prizeList = (array)M('Prize')->field(['name','coin','point','url','img','valid'])->where(['status'=>1])->order('sort')->select();

        foreach ($prizeList as $k => $v)
        {
            $prizeList[$k]['img']  = Think\Tool\Tool::imagesReplace( $v['img'] ) ?: '';
        }

        if (iosCheck()) //ios审核不显示礼品兑换
        {
            $prizeList = [];
        }

        $this->ajaxReturn(['bannerList'=>$Recommend,'prizeList'=>$prizeList]);
    }

    /**
     * 最新发布信息接口
     */
    public function hotPush(){
        $page = $this->param['page'] ? (int)$this->param['page'] : 1;//页码大于1表示翻页,只返回竞猜数据
        $blockTime = getBlockTime(1, $gamble = true);//获取竞猜分割日期的区间时间

        if($page == 1){
            //图片地址
            $img = M('recommendClass')->alias("rc")->join("left join qc_recommend re on rc.id = re.class_id")->where(['rc.sign' => 'appHotPush'])->field('re.img')->find();
            $img  = @Think\Tool\Tool::imagesReplace($img['img']);

            //先查缓存
            $dakaCahe = S('dakaCahe'.MODULE_NAME);
            if(empty($dakaCahe)) {
                //热门大咖,热门大咖，取值周榜连胜50名中取7个
                $paramType = 1;//取周榜
                $rankDate = getRankDate($paramType);//获取上周的日期

                $sql1 = " SELECT count(*) AS num from qc_ranking_list WHERE dateType = {$paramType} AND gameType = 1 AND begin_date >= {$rankDate[0]} AND end_date <= {$rankDate[1]} ";
                $count = M()->query($sql1);
                if (!$count[0]['num']){
                    $rankDate = getTopRankDate($paramType);//获取上上周的数据
                }

                $sql = " SELECT r.user_id, u.nick_name, u.head
                    FROM qc_ranking_list AS r
                    LEFT JOIN qc_front_user AS u ON r.user_id = u.id
                    WHERE r.dateType = {$paramType}
                    AND r.gameType = 1
                    AND r.begin_date >= {$rankDate[0]} AND r.end_date <= {$rankDate[1]} AND r.id > 0
                    ORDER BY  r.ranking ASC LIMIT 50 ";

                $arr = M()->query($sql);
                $currArr = array();//排序数组

                foreach ($arr as $k => $v) {
                    $arr[$k]['face'] = frontUserFace($v['head']);
                    $winnig = D('GambleHall')->getWinning($v['user_id'], $gameType = 1); //连胜记录
                    $arr[$k]['curr_victs_ftball'] = $currArr[] = $winnig['curr_victs'];//当前连胜场数

                    unset($arr[$k]['head']);
                }
                array_multisort($currArr, SORT_DESC, $arr);

                $data1 =  array_slice($arr, 0, 7);//取前七

                //缓存3个小时
                S('dakaCahe'.MODULE_NAME, json_encode($data1), 60 * 5);
                $daka = $data1;
                unset($data1);
            }else{//返回缓存
                $daka = $dakaCahe;
            }
        }else{
            $daka = array();
            $img = '';
        }

        //最新竞猜
        $pageNum = 10;
        $pageSize = ($page-1)*$pageNum;
        $playType = $this->param['playType'] ? (int)$this->param['playType'] : 0;//0:全部;1:让分;-1:大小
        $orderType = $this->param['orderType'] ? (int)$this->param['orderType'] : 0;//0:默认按时间倒序;1:价格低到高;2:价格高到低;3:销量高到低

        $where = " g.create_time between {$blockTime['beginTime']} AND {$blockTime['endTime']} AND g.result = 0 AND g.id > 0 ";//竞猜赛程期间内，且未出结果的
        if($playType){
            $where .=  " AND g.play_type = {$playType} " ;
        }

        $order = ' g.create_time DESC ';
        if($orderType == 1 || $orderType == 2){//价格
            $d = ($orderType == 1) ? 'ASC' : 'DESC';
            $order =  " g.tradeCoin {$d}, g.create_time DESC ";
        }else if($orderType == 3){//销量,只出现金币不为0的，先按销量排，再按竞猜时间排
            $where .= ' AND g.tradeCoin > 0 ';
            $order = ' g.quiz_number DESC, g.create_time DESC ';
        }

        $userToken = getUserToken($this->param['userToken']);
        $sql4 = " SELECT g.id as gamble_id, g.user_id, g.union_name, g.game_date, g.game_time, g.home_team_name, g.away_team_name,
                  g.play_type, g.chose_side, g.tradeCoin, g.handcp, g.odds, g.result, g.`desc`, g.create_time, u.nick_name, u.head, u.lv, qu.union_color
                  FROM qc_gamble AS g
                  LEFT JOIN qc_front_user AS u ON g.user_id = u.id
                  LEFT JOIN qc_union AS qu ON g.union_id = qu.union_id
                  WHERE {$where}
                  ORDER BY  {$order} limit {$pageSize}, {$pageNum} ";
        $jingcaiArr = (array)M()->query($sql4);

        if(!empty($jingcaiArr)){
            foreach($jingcaiArr as $k2 => $v2){
                $jingcaiArr[$k2]['face'] = frontUserFace($v2['head']);
                $jingcaiArr[$k2]['union_name']     = explode(',', $v2['union_name']);
                $jingcaiArr[$k2]['home_team_name'] = explode(',', $v2['home_team_name']);
                $jingcaiArr[$k2]['away_team_name'] = explode(',', $v2['away_team_name']);
                $jingcaiArr[$k2]['desc'] = (string)$v2['desc'];

                if ($userToken) {//如已经登陆
                    //判断当前用户是否有购买当前信息
                    $jingcaiArr[$k2]['is_trade'] = M('QuizLog')->where(['game_type' => 1, 'gamble_id'=>$v2['gamble_id'], 'user_id'=>$userToken['userid']])->getField('id') ? 1 : 0;//是否已查看购买过
                }else{
                    //无登录则全部没有购买
                    $jingcaiArr[$k2]['is_trade'] = 0;
                }
                unset($jingcaiArr[$k2]['head']);
            }
        }


        if($page == 1){
            $this->ajaxReturn(['daka' => $daka, 'dakaImg' => $img,  'jingcai' => $jingcaiArr]);
        }else{
            $this->ajaxReturn(['jingcai' => $jingcaiArr]);
        }
    }


    /**
     * 热门大咖--更多信息
     */
    public function bigShotInfo(){
        $page = $this->param['page'] ? (int)$this->param['page'] : 1;//页码大于1表示翻页
        $pageNum = 10;
        $pageSize = ($page-1)*$pageNum;
        $playType = $this->param['playType'] ? (int)$this->param['playType'] : 0;//0:全部;1:让分;-1:大小
        $recordType = $this->param['recordType'] ? (int)$this->param['recordType'] : 1;//1:默认周胜率;2:月胜率;3:人气;4:等级；5：当前连胜；6：命中

        if($page >= 5){//不能大于等于5，只取前50名
            $this->ajaxReturn(['bigShotInfo' => array()]);
        }
        if(!in_array($playType, array(-1,0,1)) || !in_array($recordType, array(1,2,3,4,5,6))){
            $this->ajaxReturn(101);
        }

        if(in_array($recordType, array(1,2,4,5,6))){//周胜率，月胜率
            $paramType = in_array($recordType, array(1,4,5,6)) ? 1 : 2;//4,5,6取周榜
            $rankDate = getRankDate($paramType);//获取上周的日期

            $sql1 = " SELECT count(*) AS num from qc_ranking_list WHERE dateType = {$paramType} AND gameType = 1 AND begin_date >= {$rankDate[0]} AND end_date <= {$rankDate[1]} ";
            $count = M()->query($sql1);
            if (!$count[0]['num']){
                $rankDate = getTopRankDate($paramType);//获取上上周的数据
            }

            $sql = " SELECT r.user_id, r.winrate, u.nick_name,u.head,u.lv,r.ranking
                    FROM qc_ranking_list AS r
                    LEFT JOIN qc_front_user AS u ON r.user_id = u.id
                    WHERE r.dateType = {$paramType}
                    AND r.gameType = 1
                    AND r.begin_date >= {$rankDate[0]} AND r.end_date <= {$rankDate[1]} AND r.id > 0
                    ORDER BY  r.ranking ASC LIMIT 50 ";
        }else if($recordType == 3){//人气
            $listDate = date('Ymd', strtotime("-1 day"));
            $exist = M('RedList')->where(['list_date' => $listDate, 'game_type' => 1])->field('id')->find();

            if (!$exist) {//昨天不存在就找前天
                $listDate = date('Ymd', strtotime("-2 day"));
            }
            $sql = " SELECT l.user_id, l.winrate, u.nick_name, u.head, u.lv,l.ranking
                    FROM qc_red_list as l
                    LEFT JOIN qc_front_user as u on l.user_id = u.id
                    WHERE l.list_date = {$listDate} AND l.game_type = 1 AND l.id > 0
                    ORDER BY l.ranking ASC LIMIT 50 ";
        }
        $arr = M()->query($sql);
        $blockTime = getBlockTime(1, $gamble = true);//获取赛程分割日期的区间时间
        $rankSort = $lvArr = $currArr = $tenGambleRateArr = $createTimeSort = $weekSort = $monthSort = $redList = $playTypeArr = $backupArr = $backupTimeArr = array();//排序数组

        foreach ($arr as $k => $v) {
            $arr[$k]['face'] = frontUserFace($v['head']);
            $arr[$k]['tenGamble'] = D('GambleHall')->getTenGamble($v['user_id'], 1);
            $arr[$k]['tenGambleRate'] = $tenGambleRateArr[] = countTenGambleRate($arr[$k]['tenGamble']);//近十场的胜率
            $winnig = D('GambleHall')->getWinning($v['user_id'], $gameType=1); //连胜记录
            $arr[$k]['curr_victs_ftball'] = $currArr[] = $winnig['curr_victs'];//连胜场数
            $arr[$k]['win']  = $winnig['win'];//胜数
            $rankSort[] = $redList[] = $v['ranking'];//排名
            $lvArr[] = $v['lv'];//等级

            if(in_array($recordType, array(1, 4, 5, 6)) && $playType == 0){//周胜率
                $arr[$k]['weekPercnet'] = $weekSort[] = $arr[$k]['winrate'];//周胜率
                $arr[$k]['monthPercnet'] = $monthSort[] = D('GambleHall')->CountWinrate($v['user_id'], 1, 2);//月胜率
            }else if($recordType == 2 && $playType == 0){//月胜率
                $arr[$k]['weekPercnet'] = $weekSort[] = D('GambleHall')->CountWinrate($v['user_id'], 1, 1);//周胜率
                $arr[$k]['monthPercnet'] = $monthSort[] = $arr[$k]['winrate'];//月胜率
            }else if(in_array($recordType, array(1, 2, 4, 5, 6)) && in_array($playType, array(-1, 1))){
                $arr[$k]['weekPercnet'] = $weekSort[] = D('GambleHall')->CountWinrate($v['user_id'], 1, 1, false, false, $playType);//周胜率
                $arr[$k]['monthPercnet'] = $monthSort[] = D('GambleHall')->CountWinrate($v['user_id'], 1, 2, false, false, $playType);//月胜率
                $winnig = D('GambleHall')->getWinning($v['user_id'], $gameType=1, $playType); //连胜记录
                $arr[$k]['win']  = $winnig['win'];//胜数
                $arr[$k]['winrate'] = (in_array($recordType, array(1, 4, 5, 6))) ? $arr[$k]['weekPercnet'] : $arr[$k]['monthPercnet'];
            }else if($recordType == 3 && in_array($playType, array(-1, 1))){//人气
                $arr[$k]['weekPercnet'] = $weekSort[] = D('GambleHall')->CountWinrate($v['user_id'], 1, 1, false, false, $playType);//周胜率
                $arr[$k]['monthPercnet'] = $monthSort[] = D('GambleHall')->CountWinrate($v['user_id'], 1, 2, false, false, $playType);//月胜率
                $winnig = D('GambleHall')->getWinning($v['user_id'], $gameType=1, $playType); //连胜记录
                $arr[$k]['win']  = $winnig['win'];//胜数
                //计算昨天的胜率
                $where['user_id']   = $v['user_id'];
                $where['create_time'] = ['between',[$blockTime['beginTime']-86400, $blockTime['endTime']-86400]];
                $where['result'] = ['NEQ', 0];
                $where['play_type'] = $playType;
                $gameArray = M('gamble')->where($where)->select();
                //计算昨日胜率
                $win = $half = $transport = $donate = 0;
                foreach ($gameArray as $vv)
                {
                    if($vv['result'] == '1')     $win++;
                    if($vv['result'] == '0.5')   $half++;
                    if($vv['result'] == '-1')    $transport++;
                    if($vv['result'] == '-0.5')  $donate++;
                }

                $winTotal    = $win + $half*0.5;
                $gambleTotal = $winTotal + $transport + $donate*0.5;
                $arr[$k]['winrate'] = $redList[] = $gambleTotal ? round(($winTotal/$gambleTotal)*100) : 0;
            }else if($recordType == 3 && $playType == 0) {
                $arr[$k]['weekPercnet'] = $weekSort[] = D('GambleHall')->CountWinrate($v['user_id'], 1, 1);//周胜率
                $arr[$k]['monthPercnet'] = $monthSort[] = D('GambleHall')->CountWinrate($v['user_id'], 1, 2);//月胜率
            }

            $playWhere = ($playType != 0) ? $playType : ['in', [-1,1]];
            $arr[$k]['todayNum'] = M('Gamble')->where(['user_id' => $v['user_id'], 'play_type'=>$playWhere, 'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]]])->count();//当天推荐场数
            $one = M('Gamble')->where(['user_id' => $v['user_id'], 'play_type'=>$playWhere, 'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]]])->order('id desc')->find();

            if($one){//若当天推荐存在
                $arr[$k]['todayHomeName'] = explode(',', $one['home_team_name']);//当天推荐主队名称
                $arr[$k]['todayAwayName'] = explode(',', $one['away_team_name']);//当天推荐客队名称
                $arr[$k]['createTime'] = $createTimeSort[] = $one['create_time'];

                if(in_array($playType, array(-1, 1))){
                    $playTypeArr[] = $arr[$k];
                }
            }else{
                $arr[$k]['todayHomeName'] = '';//当天推荐主队名称
                $arr[$k]['todayAwayName'] = '';//当天推荐客队名称
                $arr[$k]['createTime'] = $createTimeSort[] = 0;
            }
            unset($one);
        }

        if(in_array($playType, array(-1, 1))){//如果选玩法，则只出现玩法的内容
            $arr = $playTypeArr;
            foreach ($arr as $k => $v) {
                if($recordType == 4){
                    $backupArr[] = $v['lv'];
                }else if($recordType == 5){
                    $backupArr[] = $v['curr_victs_ftball'];
                }else if($recordType == 6){
                    $backupArr[] = $v['tenGambleRate'];
                }else{
                    $backupArr[] = ($recordType == 1) ? $v['weekPercnet'] : (($recordType == 2) ? $v['monthPercnet'] : $v['winrate']);
                }
                $backupTimeArr[] = $v['createTime'];
            }
            array_multisort($backupArr, SORT_DESC, $backupTimeArr, SORT_DESC, $arr);
        }else{
            if($recordType == 4){
                array_multisort($lvArr, SORT_DESC, $createTimeSort, SORT_DESC, $arr);
            }else if($recordType == 5){
                array_multisort($currArr, SORT_DESC, $createTimeSort, SORT_DESC, $arr);
            }else if($recordType == 6){
                array_multisort($tenGambleRateArr, SORT_DESC, $createTimeSort, SORT_DESC, $arr);
            }else{//全部情况下，都是按照排名排序
                array_multisort($rankSort, SORT_ASC, $createTimeSort, SORT_DESC, $arr);
            }
        }

        //释放无用的数据
        foreach ($arr as $k => $v) {
            unset($arr[$k]['head'], $arr[$k]['winrate'], $arr[$k]['createTime'], $arr[$k]['ranking'], $arr[$k]['tenGambleRate']);
        }
        unset($rankSort, $createTimeSort, $weekSort, $monthSort, $redList, $backupArr, $backupTimeArr, $lvArr, $currArr, $tenGambleRateArr);
        $arr = array_slice($arr, $pageSize, $pageNum);

        $this->ajaxReturn(['bigShotInfo' => $arr]);
    }

    /**
     *  高手竞猜
     */
    public function masterGamble(){
        $userToken = getUserToken($this->param['userToken']);
        $page = $this->param['page'] ? (int)$this->param['page'] : 1;//页码大于1表示翻页
        $pageNum = 10;
        $pageSize = ($page-1)*$pageNum;
        $sortType = $this->param['sortType'] ? (string)$this->param['sortType'] : 'highHit';//默认高命中

        $list = D('Home')->getMasterGamble($userToken, $sortType, $pageSize, $pageNum);

        $this->ajaxReturn(['list' => (array)$list]);
    }

    /**
     * 热门高手
     */
    public function hotMaster ()
    {
         $cacheKey = MODULE_NAME.'_hotMaster';
        if( !$lists = S($cacheKey) ){

            //获取高命中的用户及发布竞猜
            $masters = D('Home')->rankGambleList('highHit', 1, 100);
            foreach ($masters as $k => $v) {
                $tempList[] = $v['user_id'] . ',' . $v['nick_name'] . ',' . preg_replace('/\?([a-z0-9A-Z_]+)/', '', $v['face']);
            }

            //去重获取用户
            $tempList = array_unique($tempList);
            $blockTime = getBlockTime(1,$gamble=true);

            foreach ($tempList as $k => $v) {
                //今日是否有竞猜
                $options = explode(',', $v);
                $today_gamble = M('Gamble')->where(['user_id'=>$options[0],'create_time'=>['between',[$blockTime['beginTime'],$blockTime['endTime']]]])->getField('id') ? 1 : 0;
                $lists[] = ['user_id' => $options[0], 'nick_name' => $options[1], 'guess' => (string) $today_gamble, 'face' => $options[2] . '?' . time()];
            }

            $lists =  $lists ? array_slice($lists, 0, 8) : [];

            //缓存
            if($lists)
                S($cacheKey, $lists, 600);
        }

        $this->ajaxReturn(['lists' => $lists]);
    }

    /**
     * 昵称搜索（待优化）hzl
     **/
    public function queryByNick()
    {

        if (!$this->param['keyword'])
            $this->ajaxReturn(2015);

        $limit = 20;
        $page = $this->param['page'] ? $this->param['page'] : 1;
        $startRow = ($page - 1) * $limit;

        //模糊匹配
        $sql = "SELECT `id` AS user_id, `nick_name`, `head`, `lv`, LOCATE('{$this->param['keyword']}',nick_name ) AS pos FROM `qc_front_user` WHERE  LOCATE('{$this->param['keyword']}', nick_name) > 0 ";
        $FrontUser = M()->query($sql);

        foreach ($FrontUser as $k => $v) {

            //排序数组
            $tenGamble          = D('GambleHall')->getTenGamble($v['user_id'], 1);
            $tenGambleRate      = countTenGambleRate($tenGamble);                       //近十场的胜率;
            $matchDegreeArr[]   = $v['pos'];                                            //比配度（靠前）
            $lvArr[]            = $v['lv'];                                             //等级
            $tenGambleRateArr[] = $tenGambleRate;

            //拼接返回结果
            $FrontUser[$k]['face']          = (string) frontUserFace($v['head']);
            $FrontUser[$k]['lv']            = (string) $v['lv'];
            $FrontUser[$k]['weekPercnet']   = (string) D('GambleHall')->CountWinrate($v['user_id'], 1, 1);  //周胜率
            $FrontUser[$k]['monthPercnet']  = (string) D('GambleHall')->CountWinrate($v['user_id'], 1, 2);  //月胜率
            $FrontUser[$k]['tenGambleRate'] = (string) $tenGambleRate;
            $winnig                         =  D('GambleHall')->getWinning($v['user_id'], $gameType = 1);   //连胜记录
            $FrontUser[$k]['curr_victs']    = (string) $winnig['curr_victs'];                               //连胜场数
            $FrontUser[$k]['win']           = (string) $winnig['win'];                                      //胜场

            unset($FrontUser[$k]['head']);

        }
        array_multisort($matchDegreeArr, SORT_ASC, $lvArr, SORT_DESC, $tenGambleRateArr, SORT_DESC, $FrontUser);
        $lists =  $FrontUser ? array_slice($FrontUser, $startRow, $limit) : [];

        $this->ajaxReturn(['lists' => $lists]);

    }

    /**
     * 日、周、月、季积分盈利榜 hzl
     */
    public function earnPointList()
    {
        $pageNum    = 20;
        $expire     = 300;
        $page       = $this->param['page'] ?: 1;
        $dateType   = $this->param['dateType'] ?: 1;
        $tdGamble   = $this->param['todayGamble'] ?: 0;
        $userToken  = getUserToken($this->param['userToken']);
        $blockTime  = getBlockTime(1, true);//今日足球竞猜时间段
        $cacheKey   = MODULE_NAME . '_EarnPointList_' . $tdGamble . $dateType . $page . $pageNum;
        $myCacheKey = MODULE_NAME . '_myEarnPointRank_:' . $dateType . $userToken['userid'];

        switch (intval($dateType)) {
            case 1: //周
            case 2: //月
            case 3: //季
                $endDate    = getRankDate($dateType)[1];
                $topEndDate = getTopRankDate($dateType)[1];

                $where      = ['r.listDate' => ["EQ", $endDate], 'r.dateType' => $dateType];
                $topWhere   = ['r.listDate' => ["EQ", $topEndDate], 'r.dateType' => $dateType];
                break;

            case 4: //日
                $where      = ['r.listDate' => date('Ymd', strtotime("-1 day")), 'r.dateType' => $dateType];
                $topWhere   = ['r.listDate' => date('Ymd', strtotime("-2 day")), 'r.dateType' => $dateType];
                break;

            default:
                $this->ajaxReturn(['myRank' => [] , 'rankList' => []]);

        }

        if (!$rankLists = S($cacheKey)) {
            $count = M('earnPointList r')->where($where)->count();
            $field = ['r.user_id', 'f.nick_name', 'r.ranking', 'f.head' => 'face', 'r.gameCount', 'r.pointCount'];

            if (!$count)
                $where = $topWhere;

            if ($tdGamble) {
                //筛选今日竞猜
                $where['g.create_time'] = ['between', [$blockTime['beginTime'], $blockTime['endTime']]];
                $rankLists = M('earnPointList r')
                    ->field($field)
                    ->join('left join __GAMBLE__ g on g.user_id = r.user_id')
                    ->join('left join __FRONT_USER__ f on f.id = r.user_id')
                    ->where($where)
                    ->group('r.user_id')
                    ->order('r.ranking ASC')
                    ->page($page . ',' . $pageNum)
                    ->select();

            } else {
                $rankLists = M('earnPointList r')
                    ->join('left join __FRONT_USER__ f on f.id = r.user_id')
                    ->field($field)
                    ->where($where)
                    ->order("r.ranking ASC")
                    ->page($page . ',' . $pageNum)
                    ->select();
            }

            //获取用户信息
            foreach ($rankLists as $k => $v) {
                $rankLists[$k]['face']          = frontUserFace($v['face']);
                $rankLists[$k]['nick_name']     = $v['nick_name'] ?: '';
                $rankLists[$k]['today_gamble']  = M('Gamble')->where(['user_id' => $v['user_id'], 'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]]])->getField('id') ? '1' : '0';

                $isFollow = 0;
                if (is_array($userToken))
                    $isFollow = M('FollowUser')->where(['user_id' => $userToken['userid'], 'follow_id' => $v['user_id']])->find();

                $rankLists[$k]['isFollow'] = $isFollow ? '1' : '0';
            }

            if($rankLists)
                S($cacheKey, $rankLists, $expire);
        }

        //我的排名
        if (is_array($userToken)) {
            if (!$myRank = S($myCacheKey)) {
                $count = M('earnPointList r')->where($where)->count();

                if (!$count)
                    $where = $topWhere;

                $where['r.user_id'] = $userToken['userid'];
                $rankData = M('earnPointList r')->field(['r.user_id, r.pointCount, r.ranking'])->where($where)->select();

                if ($rankData) {
                    $myRank = $rankData[0];
                    $myRank['ranking'] .= '名';
                } else {
                    $myRank['pointCount'] = 0;
                    $myRank['ranking'] = '未上榜';
                }

                if ($myRank)
                    S($myCacheKey, $myRank, $expire);
            }
        }

        $this->ajaxReturn(['myRank' => $myRank ?: [], 'rankList' => $rankLists ?: []]);
    }

}

 ?>