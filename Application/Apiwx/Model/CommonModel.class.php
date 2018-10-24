<?php
/**
 * 全站公用方法模型类
 * @author dengwj <406516482@qq.com> 2016.6.23
 */

use Think\Model;
use Think\Tool\Tool;
class CommonModel extends Model
{
	/**
     * 获取近十场竞猜结果
     *
     * @param int  $id        会员id
     * @param int  $gameType  赛事类型(1:足球   2:篮球   默认为1)
     * @param int  $playType  玩法(1:亚盘   2:竞彩   默认为1)
     *
     * @return  array
    */
    public function getTenGamble($id,$gameType=1,$playType=1){
        //条件会员id
        $where['user_id']    = $id;
        //赛事类型
        $Model = $gameType == 1 ? M('gamble') : M('gamblebk');
        //过滤掉未出结果的
        $where['result']     = array('in',[1,0.5,2,-1,-0.5]);
        if($gameType == 1){
            $where['play_type'] = $playType == 1 ? ['in',[1,-1]] : ['in',[2,-2]];
        }
        //近10场比赛结果
        $tenArray = $Model->where($where)->order("id desc")->limit(10)->field('result')->select();
        return $tenArray;
    }

    //近10中几
    public function getWinNum($user_id)
    {
        //近十场
        $TenGamble = $this->getTenGamble($user_id);
        $num = 0;
        foreach ($TenGamble as $k => $v) {
            if($v['result'] == '1' || $v['result'] == '0.5'){
                $num++;
            }
        }
        return ['num'=>count($TenGamble),'win'=>$num];
    }

    /**
     * 获取连胜记录
     *
     * @param int  $id        会员id
     * @param int  $gameType  赛事类型(1:足球   2:篮球   默认为1)
     * @param int  $gambleType  玩法(1:亚盘   2:竞彩   默认为1)
     *
     * @return  array
    */
    public function getWinning($id,$gameType=1,$gambleType=1)
    {
        $where['user_id'] = $id;
        $where['result']  = ['neq',0];
        //竞彩足球
        if($gameType == 1){
            $where['play_type'] = ($gambleType == 1) ? ['in', [-1,1]] : ['in', [-2,2]];
        }
        $GambleModel = $gameType == 1 ? M('Gamble') : M('Gamblebk');
        $gamble = $GambleModel->where($where)->order("id desc")->getField('result',true);

        $curr_victs = 0; //当前连胜
        foreach ($gamble as $k => $v) {
            if($v == '1' || $v == '0.5'){
                $curr_victs++;
            }
            if($v == '-1' || $v == '-0.5'){
                break;
            }
        }

        $temp = $max_victs = 0;
        foreach ($gamble as $v)
        {
            if ($v == 1 || $v == 0.5)
            {
                $temp++;
                if ($temp > $max_victs)
                    $max_victs = $temp;
            }
            else if ($v == -1 || $v == -0.5)
                $temp = 0;
            else
                continue;  //需考虑推迟、取消的赛程结果值为-14,-13等
        }
        return ['curr_victs'=>$curr_victs,'max_victs'=>$max_victs];
    }

    /**
     * 获取排行榜(读取数据表)
     *
     * @param int  $gameType     赛事类型(1:足球   2:篮球   默认为1)
     * @param int  $dateType     时间类型(1:周胜率 2:月胜率 3:季胜率 默认为1)
     * @param int  $user_id      是否查找指定用户,默认为否
     * @param int  $number       提取前多少名(默认false全部)
     * @param int  $winrate      提取胜率条件
     *
     * @return  array
    */
    public function getRankingData($gameType=1,$dateType=1,$user_id=null,$number=false,$winrate=null){
        list($begin,$end) = getRankDate($dateType);
        $where['r.gameType']   = $gameType;
        $where['r.dateType']   = $dateType;
        $where['r.begin_date'] = array("between",array($begin,$end));
        $where['r.end_date']   = array("between",array($begin,$end));
        //查看是否有上周/月/季的数据
        $count = M('rankingList r')->where($where)->count();
        if (!$count)
        {
            list($begin,$end) = getTopRankDate($dateType);  //获取上上周的数据
            $where['r.begin_date'] = array("between",array($begin,$end));
            $where['r.end_date']   = array("between",array($begin,$end));
        }
        if($user_id !== null) $where['r.user_id']   = $user_id;

        if($number) $where['r.ranking']   = array('elt',$number);

        if($winrate !== null) $where['r.winrate']   = array('egt',$winrate);

        $Ranking = M('rankingList r')
                 ->join("LEFT JOIN qc_front_user f on f.id=r.user_id")
                 ->field("r.*,f.nick_name,f.lv,f.lv_bk,f.head,f.is_robot")
                 ->where($where)
                 ->order('r.ranking asc')
                 ->select();

        foreach ($Ranking as $k => $v) {
            $Ranking[$k]['face'] = frontUserFace($v['head']);
        }
        return $Ranking;
    }

    /**
     * 获取红人榜(读取数据表)
     *
     * @param int  $gameType     赛事类型(1:足球   2:篮球   默认为1)
     * @param int  $number       提取前多少名(默认false全部)
     * @param int  $user_id      是否查找指定用户,默认为否
     *
     * @return  array
    */
    public function getRedList($gameType=1,$number=false,$user_id=null){
        $where['game_type']  = $gameType;
        $where['list_date']  = date('Ymd',strtotime("-1 day"));
        $count = M('redList')->where($where)->count();
        if(!$count){
            $where['list_date']  = date('Ymd',strtotime("-2 day"));
        }
        if($number){
            $where['ranking']   = array('elt',$number);
        }
        if($user_id !== null) $where['r.user_id']  = $user_id;

        //从数据表获取昨日红人榜
        $Ranking = M('redList r')
               ->join("LEFT JOIN qc_front_user f on f.id=r.user_id")
               ->field("r.*,f.nick_name,f.head,f.lv,f.lv_bk")
               ->where($where)->order('ranking asc')->select();
               
        foreach ($Ranking as $k => $v) {
            $Ranking[$k]['face'] = frontUserFace($v['head']);
        }
        return $Ranking;
    }

    /**
     * 获取盈利榜(读取数据表)
     *
     * @param int  $gameType     赛事类型(1:足球   2:篮球     默认为1)
     * @param int  $dateType     时间类型(1:周 2:月 3:季 4:日 默认为1)
     * @param int  $user_id      是否查找指定用户,默认为否
     * @param int  $number       提取前多少名(默认false全部)
     *
     * @return  array
    */
    public function getProfitData($gameType=1,$dateType=1,$user_id=null,$number=false){
        $map['gameType']   = $gameType;
        $map['dateType']   = $dateType;
        if($dateType == 4){
            $map['listDate']   = date('Ymd', strtotime("-1 day"));
        }else{
            list($begin,$end)  = getRankDate($dateType);
            $map['listDate']   = $end;
        }
        //查看是否有上周/月/季的数据
        $count = M('earnPointList')->where($map)->count();
        if(!$count){
            if($dateType == 4){
                $map['listDate']   = date('Ymd', strtotime("-2 day"));
            }else{
                list($begin,$end)  = getTopRankDate($dateType);
                $map['listDate']   = $end;
            }
        }
        if($user_id !== null) $map['r.user_id']  = $user_id;

        if($number) $map['ranking']   = array('elt',$number);
        
        $Ranking = (array)M('earnPointList r')
            ->field("r.id,r.user_id,r.ranking,r.gameCount,r.winrate,r.pointCount,r.win,r.level,r.transport,r.donate,r.half,f.head,f.lv,f.lv_bk,f.nick_name")
            ->join('left join qc_front_user f on f.id = r.user_id')
            ->where($map)
            ->group('r.user_id')
            ->order('r.ranking')
            ->select();
        foreach ($Ranking as $k => $v) {
            $Ranking[$k]['face'] = frontUserFace($v['head']);
        }
        return $Ranking;
    }

    /**
     * 获取竞彩排行榜(读取数据表)
     *
     * @param int  $gameType     赛事类型(1:足球   2:篮球     默认为1)
     * @param int  $dateType     时间类型(1:周 2:月 3:季 4:日 默认为1)
     * @param int  $user_id      是否查找指定用户,默认为否
     * @param int  $number       提取前多少名(默认false全部)
     *
     * @return  array
    */
    public function getRankBetting($gameType=1,$dateType=1,$user_id=null,$number=false,$more=false){
        $map['gameType']   = $gameType;
        $map['dateType']   = $dateType;
        if($dateType == 4){
            $map['listDate']   = date('Ymd', strtotime("-1 day"));
        }else{
            list($begin,$end)  = getRankDate($dateType);
            $map['listDate']   = $end;
        }
        //查看是否有上周/月/季的数据
        $count = M('rankBetting')->where($map)->count();
        if(!$count){
            if($dateType == 4){
                $map['listDate']   = date('Ymd', strtotime("-2 day"));
            }else{
                list($begin,$end)  = getTopRankDate($dateType);
                $map['listDate']   = $end;
            }
        }
        if($user_id !== null) $map['r.user_id']  = $user_id;

        if($number) $map['ranking']   = array('elt',$number);
        
        $Ranking = (array)M('rankBetting r')
            ->field("r.id,r.user_id,r.ranking,r.gameType,r.gameCount,r.win,r.transport,r.winrate,r.pointCount,f.head,f.lv,f.lv_bet,f.nick_name")
            ->join('left join qc_front_user f on f.id = r.user_id')
            ->where($map)
            ->group('r.user_id')
            ->order('r.ranking')
            ->select();
		
        foreach ($Ranking as $k => $v) {
            $Ranking[$k]['face'] = frontUserFace($v['head']);
            if($more){
                //近10场记录
                $Ranking[$k]['tenArray'] = $this->getTenGamble($v['user_id'],$gameType,2);
                //当前连胜与最大连胜
                $Ranking[$k]['Winning'] = $this->getWinning($v['user_id'],$gameType,2);
            }
        }
        return $Ranking;
    }

    /**
     * 获取竞彩盈利榜(读取数据表)
     *
     * @param int  $gameType     赛事类型(1:足球   2:篮球     默认为1)
     * @param int  $dateType     时间类型(1:周 2:月 3:季 4:日 默认为1)
     * @param int  $user_id      是否查找指定用户,默认为否
     * @param int  $number       提取前多少名(默认false全部)
     *
     * @return  array
    */
    public function getRankBetprofit($gameType=1,$dateType=1,$user_id=null,$number=false){
        $map['gameType']   = $gameType;
        $map['dateType']   = $dateType;
        if($dateType == 4){
            $map['listDate']   = date('Ymd', strtotime("-1 day"));
        }else{
            list($begin,$end)  = getRankDate($dateType);
            $map['listDate']   = $end;
        }
        //查看是否有上周/月/季的数据
        $count = M('RankBetprofit')->where($map)->count();
        if(!$count){
            if($dateType == 4){
                $map['listDate']   = date('Ymd', strtotime("-2 day"));
            }else{
                list($begin,$end)  = getTopRankDate($dateType);
                $map['listDate']   = $end;
            }
        }
        if($user_id !== null) $map['r.user_id']  = $user_id;

        if($number) $map['ranking']   = array('elt',$number);
        
        $Ranking = (array)M('RankBetprofit r')
            ->field("r.id,r.user_id,r.ranking,r.gameCount,r.pointCount,f.head,f.lv_bet,f.nick_name")
            ->join('left join qc_front_user f on f.id = r.user_id')
            ->where($map)
            ->group('r.user_id')
            ->order('r.ranking')
            ->select();
        foreach ($Ranking as $k => $v) {
            $Ranking[$k]['face'] = frontUserFace($v['head']);
        }
        return $Ranking;
    }

    /**
     * 获取昨日胜率
     * @param int  $user_id    用户id
     * @param int  $gameType   赛事类型(1:足球   2:篮球   默认为1)
     * @param int  $playType   玩法
     * @param int  $gambleType 是否竞彩  1亚盘  2竞彩  默认1
     * @return  array
    */
    public function YestWinrate($user_id,$gameType=1,$playType=0,$gambleType=1){
        $blockTime = getBlockTime(1, $gamble = true);
        $where['user_id']     = $user_id;
        $where['create_time'] = ['between',[$blockTime['beginTime']-86400, $blockTime['endTime']-86400]];
        $where['result'] = ['NEQ', 0];
        if($gameType == 1){
            $where['play_type'] = $gambleType == 1 ? ['in',[1,-1]] : ['in',[2,-2]];
        }
        if($playType){
            $where['play_type'] = (int)$playType;
        }
        $GambleModel = $gameType == 1 ? M('Gamble') : M('Gamblebk');
        $gameArray = $GambleModel->where($where)->select();
        //计算胜率
        $win        = 0;
        $half       = 0;
        $level      = 0;
        $transport  = 0;
        $donate     = 0;
        $pointCount = 0;
        foreach ($gameArray as $k => $v) {
            if($v['result'] == '1'){
                $win++;
            }
            if($v['result'] == '0.5'){
                $half++;
            }
            if($v['result'] == '2'){
                $level++;
            }
            if($v['result'] == '-1'){
                $transport++;
            }
            if($v['result'] == '-0.5'){
                $donate++;
            }
            if($v['earn_point'] > 0){
                $pointCount += $v['earn_point'];
            }
        }
        $winrate = getGambleWinrate($win,$half,$transport,$donate);
        return array(
                "winrate"    =>  $winrate,
                'win'        =>  $win,
                'half'       =>  $half,
                'level'      =>  $level,
                'transport'  =>  $transport,
                'donate'     =>  $donate,
                'pointCount' =>  $pointCount,
                'user_id'    =>  $user_id,
                'gameCount'  =>  count($gameArray),
            );
    }

    /**
     * 邀请好友充值判断:
     * 只要受邀请的好友在（30天内）已经充值了，
     * 就不受“动态活跃考核规则”的限制了，
     * （自己注册的金币和对上级的金币两种）
     * 即相当于已经通过了邀请考核
     */
    public function checkPay($userid, $trade_no){
        $info = M('FrontUser')->field('login_time, login_count, reg_time')->where(['id' => $userid])->find();

        //20161101之前注册不需要考核
        if($info['reg_time'] < strtotime('2016-11-01 00:00:00'))
            return false;

        //没有邀请人不需要考核
        $recommend_id = M('FrontUser')->where(['id' => $userid])->getField('recommend_id');
        if(!$recommend_id)
            return false;

        //是不是30天之内
        $inviteConfig = getWebConfig('invite');
        if((NOW_TIME - $info['reg_time']) > $inviteConfig['login_days']*3600*24)
            return false;

        //检查登录情况是否已有记录
        $res = M('InviteLoginInfo')->where(['user_id' => $userid])->count();
        if($res)//没有入库的才入库
            return false;

        //判断邀请注册时获得金币是否已给，有邀请金币且没有给的才进入方法
        $getInfo = M('InviteInfo')->field('register_coin, is_get')->where(['user_id' => $userid])->find();

        //已给或者没有邀请金币的过滤
        if($getInfo['is_get'] == 1 || $getInfo['register_coin'] == 0)
            return false;

        try{
            M()->startTrans();

            $data['type']          = 1;//有效
            $data['user_id']       = $userid;
            $data['register_time'] = $info['reg_time'];
            $data['login_time']    = $info['login_time'];
            $data['login_num']     = $info['login_count'];
            $data['create_time']   = NOW_TIME;
            $data['pay_no']        = $trade_no;

            $res0 = M('InviteLoginInfo')->add($data);

            $getTotalCion = M('FrontUser')->where(['id' => $userid])->getField('(coin+unable_coin) as total');
            $res1 = M('FrontUser')->where(['id' => $userid])->save(['coin' => ['exp', 'coin+' . $getInfo['register_coin']]]);

            if($res0 && $res1) {
                $res2 = M('AccountLog')->add([
                    'user_id'    => $userid,
                    'log_time'   => NOW_TIME,
                    'log_type'   => 13,
                    'log_status' => 1,
                    'change_num' => $getInfo['register_coin'],
                    'total_coin' => $getTotalCion + $getInfo['register_coin'],
                    'desc'       => "邀请好友",
                    'platform'   => 1,
                    'pay_no'     => $trade_no,
                ]);

                $getData['valid_coin'] = ['exp', 'valid_coin+' . $getInfo['register_coin']];
                $getData['await_coin'] = ['exp', 'await_coin-' . $getInfo['register_coin']];
                $getData['is_get']     = 1;
                $res3 = M('InviteInfo')->where(['user_id' => $userid])->save($getData);

                if($res2 === false || $res3 === false){
                    throw new Exception();
                }
            }else{
                throw new Exception();
            }

            M()->commit();
            return true;
        }catch(Exception $e) {
            M()->rollback();
            return false;
        }
    }

    /**
     * APP充值赠送金币
     * @param $pay_fee  int 充值金额
     * @param $user_id  int 用户id
     * @param $platform int 平台
     * @param $order_id int 订单号
     * @return boolean
     */
    public function giveCoin($pay_fee, $user_id, $platform, $order_id){
        $config = getWebConfig('recharge');//充值配置

        $chang_num = 0;
        foreach($config as $k => $v){
            if($v['account'] == $pay_fee){
                $chang_num = $v['number'];
                break;
            }
        }

        if($chang_num == 0)
            return false;

        $user = M('FrontUser')->field('coin, unable_coin')->where(['id' => $user_id])->find();
        $rs   = M('FrontUser')->where(['id'=>$user_id])->save(['coin'=>['exp', "coin+{$chang_num}"]]);
        if($rs){
            $array = array(
                'user_id'   => $user_id,
                'log_type'  => 5,
                'log_status'=> 1,
                'log_time'  => time(),
                'change_num'=> $chang_num,
                'total_coin'=> $user['coin'] + $user['unable_coin'] + $chang_num,
                'desc'      => '充值赠送',
                'platform'  => $platform,
                'order_id'  => $order_id,
                'operation_time' => time(),
            );

            //添加记录
            $rs1 = M('AccountLog')->add($array);
            if($rs1 === false){
                logRecord("金币添加 充值赠送记录 ：".M()->getLastsql().'====>'.$rs1,'logWx.txt');
                return false;
            }
        }else{
            logRecord("金币添加 FrontUser赠送记录 ：".M()->getLastsql().'====>'.$rs,'logWx.txt');
            return false;
        }

        return true;
    }

    /**
     * 判断用户一天重置多少，不能大于3000
     */
    public function checkRechargeNum($pay_fee, $user_id){
        $todayNum = M('AccountLog')->where(['user_id' => $user_id, 'log_type' => ['in', [7, 8]], 'log_status' => 1,
                    'log_time' => ['between', [strtotime('00:00:00'), strtotime('23:59:59')]]])->sum('change_num');

        $config = getWebConfig('common')['rechargeLimit'];

        if($config > 0 && ($pay_fee > $config || ($pay_fee + $todayNum) > $config)){
            return true;
        }

        return false;
    }

    /**
     * 首页导航列表
     */
    public function getNavList(){
        $ct   = "CONCAT('" . C('IMG_SERVER') . "'" . ",icon)";
        $icon = "http://" . DOMAIN . "/Public/Api/Home/default_icon.png";
        $sql  = "SELECT `id`, `name`, `ui_type` as type, `ui_type_value` as value, ( CASE WHEN `icon` != '' THEN {$ct} ELSE '{$icon}' END ) AS icon FROM `qc_nav` WHERE (`status` = 1) AND (`type` = 1) ORDER BY sort ASC";
        return M()->query($sql);
    }

    /**
     * API300,大咖广场的赛事信息
     * @return array
     */
    public function getMatchList(){
        list($game, $masterGamble1) = D('GambleHall')->matchList(1);//亚盘
        foreach($masterGamble1 as $k => $v){
            unset($masterGamble1[$k]['union_num'], $masterGamble1[$k]['union_color']);
        }

        list($game, $masterGamble2) = D('GambleHall')->matchList(2);//竞彩
        unset($game);
        foreach($masterGamble2 as $k => $v){
            unset($masterGamble2[$k]['union_num'], $masterGamble2[$k]['union_color']);
        }

        //1是亚盘，2是竞彩
        return [1 => $masterGamble1, 2 => $masterGamble2];
    }

    /**
     * 用户查看竞猜
     * @param  int $userid    用户id
     * @param  int $gambleId  竞猜记录id
     * @param  int $platform  平台 1：web  2：IOS  3：ANDRIOD
     * @param  int $game_type 赛事类型 1：足球  2：篮球
     * @param  int $isTicket  默认不使用体验券0，1为使用
     * @return array  是否交易成功
     */
    public function trade($userid,$gambleId,$platform=1,$game_type=1,$isTicket=0)
    {
        if(empty($gambleId)) return ['code'=>101,'data'=>''];

        $GambleModel = $game_type == 1 ? M('Gamble') : M('Gamblebk');
        //竞猜记录信息
        $gambleInfo = $GambleModel->field("id,user_id,game_id,home_team_name,away_team_name,game_date,game_time,play_type,chose_side,handcp,odds,result,tradeCoin,quiz_number,income,desc")->where(['id'=>$gambleId])->find();

        $ticket_id = 0;
        //查出时间最近的体验券
        if($isTicket){
            $where['user_id']   = $userid;
            $where['price']     = $gambleInfo['tradeCoin'];
            $where['is_use']    = 0;
            $where['status']    = 1;
            $where['type']      = 1;
            $where['over_time'] = ['gt', NOW_TIME];

            $ticket = M('TicketLog')->where($where)->order(' over_time ASC ')->limit(1)->find();

            //判断是否已经使用
            if(empty($ticket))
                return ['code'=>7005, 'data'=>''];

            //判断该体验券原始金额是否等于查看竞猜
            if($ticket['price'] != $gambleInfo['tradeCoin'])
                return ['code'=>7006, 'data'=>''];

            $ticket_id = $ticket['id'];
        }

        //处理竞猜记录
        $gambleInfo = HandleGamble($gambleInfo,0,true,$game_type);

        //是否已结算
        if ($gambleInfo['result'] != 0) return ['code'=>2013,'data'=>$gambleInfo];

        //是否自己的竞猜
        if ($gambleInfo['user_id'] == $userid) return ['code'=>2014,'data'=>$gambleInfo];

        $isTrade = M('QuizLog')->master(true)->where(['user_id'=>$userid,'gamble_id'=>$gambleId,'game_type'=>$game_type])->getField('id');

        //是否已查看过
        if ($isTrade) return ['code'=>2010,'data'=>$gambleInfo];

        $FrontUser = M('FrontUser');
        $coin      = $gambleInfo['tradeCoin']; //查看需要购买的金币
        $coverid   = $gambleInfo['user_id'];   //被查看者id
        $tradeCoin = 0;

        M()->startTrans(); //开启事务

        if ($coin > 0 && $gambleInfo['result'] == 0)
        {
            //一并找出查看者与被查看者信息
            $userInfo = $FrontUser->master(true)->field("id,lv,lv_bk,lv_bet,coin,unable_coin")->where(['id'=>['in',[$userid,$coverid]]])->select();

            foreach ($userInfo as $k => $v) 
            {
                if($userid  == $v['id']) $userCoin  = $v; //查看者
                if($coverid == $v['id']) $coverCoin = $v; //被查看者
            }

            //使用体验券，金币不需要改变
            if($isTicket) {
                $leftCoin   = $userCoin['coin'];
                $unableCoin = $userCoin['unable_coin'];
            }else{
                if ($userCoin['unable_coin'] >= $coin) //不可提足够扣除时
                {
                    $leftCoin   = $userCoin['coin'];
                    $unableCoin = $userCoin['unable_coin'] - $coin;
                } else {
                    $leftCoin   = $userCoin['coin'] - ($coin - $userCoin['unable_coin']);
                    $unableCoin = 0;
                    if ($leftCoin < 0) return ['code' => 2008, 'data' => ''];
                }
            }

            //获取竞猜配置
            $sign = $game_type == 1 ? 'fbConfig' : 'bkConfig';
            $gameConf = getWebConfig($sign);

            //获取竞猜玩法
            if($game_type == 1){
                switch ($gambleInfo['play_type']) {
                    case  '1':
                    case '-1': $gameName = '亚盘-'; $userLv = 'lv';    break;
                    case  '2':
                    case '-2': $gameName = '竞彩-'; $userLv = 'lv_bet';break;
                }
                $playName = C('fb_play_type')[$gambleInfo['play_type']];
            }else{
                $userLv = 'lv_bk';
                $gameName = '篮球-';
                $playName = C('bk_play_type')[$gambleInfo['play_type']];
            }

            //对应销售分成百分比
            $split = $gameConf['userLv'][$coverCoin[$userLv]]['split'];
            $tradeCoin = ceil($coin*($split/100));            //销售分成

            //查看者减少球币，使用体验券查看者不需要修改金币，不需要添加金币记录
            if($ticket_id == 0){
                $rs1 = $FrontUser->where(array('id'=>$userid))->save(['unable_coin'=>$unableCoin,'coin'=>$leftCoin]);

                //查看者添加球币交易记录
                $rs2 = M('AccountLog')->add([
                    'user_id'    =>  $userid,
                    'log_time'   =>  time(),
                    'log_type'   =>  3,
                    'game_type'  =>  $game_type,
                    'log_status' =>  1,
                    'change_num' =>  $ticket_id ? 0 : $coin,
                    'total_coin' =>  $leftCoin + $unableCoin,
                    'gamble_id'  =>  $gambleId,
                    'desc'       =>  '查看推荐记录',
                    'platform'   =>  $platform,
                    'operation_time' => time(),
                    'ticket_id'  => $ticket_id
                ]);
            }else{
                $rs1 = $rs2 = true;
            }

            //消息数据
            $VSteam = $gambleInfo['home_team_name'] . ' VS ' . $gambleInfo['away_team_name'];
            $quiz_number = $gambleInfo['quiz_number']+1;
            $income      = $gambleInfo['income']+$tradeCoin;
            $msg = "恭喜您！您推荐的【".$gameName.$playName."：".$VSteam."】被".$quiz_number."人查看，共获得 ".$income." 金币收入，金币进入待结算状态，比赛结束后进入正常状态方可使用，详情请查看账户明细。";
            //是否已被查看过
            $is_msg = M('msg')->where(['game_type'=>$game_type,'gamble_id'=>$gambleId])->getField('id');

            if(!$is_msg) //发送被查看通知
            {
                $rs3 = M('msg')->add([
                    'title'        => '销售收入通知',
                    'content'      => $msg,
                    'send_time'    => time(),
                    'game_type'    => $game_type,
                    'gamble_id'    => $gambleId,
                    'front_user_id'=> $coverid
                ]);
            }
            else //更新消息内容并改为未读
            {
                $rs3 = M('msg')->where(['game_type'=>$game_type,'gamble_id'=>$gambleId])->save([
                    'content'      => $msg,
                    'send_time'    => time(),
                    'is_read'      => 0
                ]);
            }
        }

        //添加查看记录
        $rs4 = M('QuizLog')->add([
            'game_type' => $game_type,
            'user_id'   => $userid,
            'cover_id'  => $coverid,
            'game_id'   => $gambleInfo['game_id'],
            'gamble_id' => $gambleId,
            'log_time'  => time(),
            'platform'  => $platform,
            'coin'      => $coin,
            'cover_coin'=> $tradeCoin,
            'ticket_id' => $ticket_id
        ]);

        //添加查看数量与销售总收入
        $rs5 = $GambleModel->where(['id'=>$gambleId])->save([
                'quiz_number' => ['exp','quiz_number+1'],
                'income'      => ['exp','income+'.$tradeCoin],
        ]);

        if ($coin > 0 && $gambleInfo['result'] == 0)
        {
            if (!$rs1 || !$rs2 || !$rs3 || !$rs4 || !$rs5)
            {
                M()->rollback();
                return ['code'=>2009,'data'=>''];
            }
        }
        else
        {
            if (!$rs4 || !$rs5)
            {
                M()->rollback();
                return ['code'=>2009,'data'=>''];
            }  
        }
        M()->commit();

        return ['code'=>'success','data'=>$gambleInfo,'userCoin'=>['coin'=>$leftCoin,'unableCoin'=>$unableCoin], 'ticket_id' => $ticket_id];
    }

    /**
     * 使用充值优惠券
     */
    public function useTicket($user_id, $trade_no){
        $order = M('tradeRecord')->master(true)->where(['trade_no'=>$trade_no])->field("pay_fee, trade_state, platform, give_coin")->find();

        if(empty($user_id) || empty($trade_no) || !in_array($order['trade_state'], [1, 2]))
            return false;

        $coin      = (int)$order['pay_fee'];
        $give_coin = $order['give_coin'];

        //时间最近的充值优惠券
        $where['user_id']   = $user_id;
        $where['price']     = $coin;
        $where['give_coin'] = $give_coin;
        $where['is_use']    = 0;
        $where['status']    = 1;
        $where['type']      = 2;
        $where['over_time'] = ['gt', NOW_TIME];

        $ticket = M('TicketLog')->where($where)->order(' over_time ASC ')->limit(1)->find();

        //判断是否已经使用
        if(empty($ticket))
            return 7005;

        //判断该优惠券金额是否等于或小于充值金额
        if((int)$ticket['price'] > (int)$order['pay_fee'])
            return 7006;

        //只能赠送一次
        if(M('AccountLog')->where(['user_id' => $user_id, 'order_id' => $trade_no, 'log_type' => 16, 'desc' => '优惠券充值赠送'])->count()){
            return false;
        }

        $chang_num = $ticket['give_coin'];
        $user = M('FrontUser')->master(true)->field('coin, unable_coin')->where(['id' => $user_id])->find();
        $rs   = M('FrontUser')->master(true)->where(['id'=>$user_id])->save(['coin'=>['exp', "coin+{$chang_num}"]]);
        if($rs){
            $array = array(
                'user_id'   => $user_id,
                'log_type'  => 16,
                'log_status'=> 1,
                'log_time'  => NOW_TIME,
                'change_num'=> $chang_num,
                'total_coin'=> $user['coin'] + $user['unable_coin'] + $chang_num,
                'desc'      => '优惠券充值赠送-'.$ticket['name'],
                'platform'  => $order['platform'],
                'order_id'  => $trade_no,
                'operation_time' => NOW_TIME,
                'ticket_id' => $ticket['id']
            );

            //添加记录
            $rs1 = M('AccountLog')->add($array);
            if($rs1 === false){
                logRecord("金币添加 优惠券赠送记录 ：".M()->getLastsql().'====>'.$rs1,'logTicket.txt');
                return false;
            }
        }else{
            logRecord("金币添加 FrontUser优惠券赠送记录 ：".M()->getLastsql().'====>'.$rs,'logTicket.txt');
            return false;
        }

        //修改体验券的状态
        M('TicketLog')->where(['id' => $ticket['id']])->save(['is_use' => 1, 'use_time' => NOW_TIME]);

        return true;
    }

    /**
     * 使用充值优惠券——测试用
     */
    public function useTicketTest($user_id, $trade_no){
        $order = M('tradeRecord')->master(true)->where(['trade_no'=>$trade_no])->field("pay_fee, trade_state, platform, give_coin, total_fee")->find();
        $order['trade_state'] = 2;
        if(empty($user_id) || empty($trade_no) || !in_array($order['trade_state'], [1, 2]))
            return false;

        //test
        $give_coin = $order['give_coin'];
        $order['pay_fee'] = $order['total_fee'];
        $coin      = (int)$order['total_fee'];

        //时间最近的充值优惠券
        $where['user_id']   = $user_id;
        $where['price']     = $coin;
        $where['give_coin'] = $give_coin;
        $where['is_use']    = 0;
        $where['status']    = 1;
        $where['type']      = 2;
        $where['over_time'] = ['gt', NOW_TIME];

        $ticket = M('TicketLog')->where($where)->order(' over_time ASC ')->limit(1)->find();

        //判断是否已经使用
        if(empty($ticket))
            return 7005;

        //判断该优惠券金额是否等于或小于充值金额
        if((int)$ticket['price'] > (int)$order['pay_fee'])
            return 7006;

        //只能赠送一次
        if(M('AccountLog')->where(['user_id' => $user_id, 'order_id' => $trade_no, 'log_type' => 16, 'desc' => '优惠券充值赠送'])->count()){
            return false;
        }

        $chang_num = $ticket['give_coin'];
        $user = M('FrontUser')->master(true)->field('coin, unable_coin')->where(['id' => $user_id])->find();
        $rs   = M('FrontUser')->master(true)->where(['id'=>$user_id])->save(['coin'=>['exp', "coin+{$chang_num}"]]);
        if($rs){
            $array = array(
                'user_id'   => $user_id,
                'log_type'  => 16,
                'log_status'=> 1,
                'log_time'  => NOW_TIME,
                'change_num'=> $chang_num,
                'total_coin'=> $user['coin'] + $user['unable_coin'] + $chang_num,
                'desc'      => '优惠券充值赠送-'.$ticket['name'],
                'platform'  => $order['platform'],
                'order_id'  => $trade_no,
                'operation_time' => NOW_TIME,
                'ticket_id' => $ticket['id']
            );

            //添加记录
            $rs1 = M('AccountLog')->add($array);
            if($rs1 === false){
                logRecord("金币添加 优惠券赠送记录 ：".M()->getLastsql().'====>'.$rs1,'logTicket.txt');
                return false;
            }
        }else{
            logRecord("金币添加 FrontUser优惠券赠送记录 ：".M()->getLastsql().'====>'.$rs,'logTicket.txt');
            return false;
        }

        //修改体验券的状态
        M('TicketLog')->where(['id' => $ticket['id']])->save(['is_use' => 1, 'use_time' => NOW_TIME]);

        return true;
    }

}