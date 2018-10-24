<?php
set_time_limit(0);//0表示不限时
/**
 * 排行榜列表控制器
 *
 * @author dengweijun <406516482@qq.com>
 *
 * @since  2015-12-15
 */
class RankingListController extends CommonController {
    /**
    *构造函数
    *
    * @return  #     
    */
    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * Index页显示
     *
     */
    public function index()
    {
        //列表过滤器，生成查询Map对象
        $map = $this->_search('RankingListView');
        $map['gameType'] = I('gameType') == 1 ? 1 : 2;//区分足球还是篮球 1:足球；2：篮球

        //时间查询
        if(!empty($_REQUEST ['endTime'])){
            $map['end_date'] = $_REQUEST ['endTime'];
        }else{
            $map['end_date'] = date('Ymd',strtotime('-1 day'));
            if(M('RankingList')->where(['end_date'=>$map['end_date'],'gameType'=>$map['gameType']])->count() <= 0){
                $map['end_date'] = date('Ymd',strtotime('-2 day'));
            }
            $_REQUEST['endTime'] = $map['end_date'];
        }
        
        //排名筛选
        $rank = I('rank');
        if (! empty($rank)) $map['ranking'] = ['elt',$rank];
        //手动获取列表
        $list = $this->_list(D('RankingListView'), $map,'begin_date desc,end_date desc,ranking asc',NULL);
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * Index页显示
     *
     */
    public function RankBetting()
    {
        //列表过滤器，生成查询Map对象
        $map = $this->_search('RankBettingView');
        //时间查询
        if(!empty($_REQUEST ['listDate'])){
            $map['listDate'] = $_REQUEST ['listDate'];
        }else{
            $map['listDate'] = date('Ymd',strtotime('-1 day'));
            if(M('RankBetting')->where(['listDate'=>$map['listDate'],'gameType'=>1])->count() <= 0){
                $map['listDate'] = date('Ymd',strtotime('-2 day'));
            }
            $_REQUEST['listDate'] = $map['listDate'];
        }
        //排名筛选
        $rank = I('rank');
        if (! empty($rank)) $map['ranking'] = ['elt',$rank];
        //手动获取列表
        $list = $this->_list(D('RankBettingView'), $map,'listDate desc,dateType asc,ranking asc',NULL);
        $this->assign('list', $list);
        $this->display();
    }

    //竞彩排行结算
    public function breakRankBetting($gameType=1,$dateType=1,$export=false)
    {
        if($export != 1 && $dateType != 4){
            S('home_web_userindex_rank',NULL);
        }
        //日期筛选
        list($begin,$end) = getRankBlockDate($gameType,$dateType);

        $gameModel = $gameType == 1 ? 'qc_gamble' : 'qc_gamblebk';

        $where['g.result']     = array("IN",array('1','0.5','2','-1','-0.5'));

        //加上对应时间
        $time = $gameType == 1 ? (10*60+32)*60 : (12*60)*60;

        $where['g.create_time']  = array( "between",array( strtotime($begin) + $time, strtotime($end) + 86400 + $time ) );
        if($dateType == 4) //日榜时间条件
        {
            $blockTime  = getBlockTime($gameType,$gamble=true);
            $end        = date('Ymd', $blockTime['beginTime'] - 86400);
            $where['g.create_time'] = ['between',[$blockTime['beginTime']-86400,$blockTime['endTime']-86400]];
        }

        $where['g.play_type'] = ['in', [-2,2]];

        switch ($dateType) 
        {
            //周榜在7日内，有连续3天没有参与竞猜，或者参与竞猜数量不到15场，不上周排名榜
            case '1':
                $gameCount = 15;
                $checkDateNum = 3;
                break;
            //月榜在1个月内，有连续5天没有参与竞猜，或者参与竞猜数量不到50场，不上月排名榜
            case '2':
                $gameCount = 50;
                $checkDateNum = 5;
                break;
            //季榜在1个季内，有连续5天没有参与竞猜，或者参与竞猜数量不到150场，不上季排名榜
            case '3':
                $gameCount = 150;
                $checkDateNum = 5;
                break;
            case '4':
                if (in_array(date('N',$blockTime['beginTime']-86400),[1,2,3,4,5])) 
                {
                    $gameCount = 3; //周1-5   3场
                }
                else
                {
                    $gameCount = 5; //周6-7   6场
                }
                break;
        }

        //找出满足条件的用户
        $FrontUser = M("FrontUser f")
            ->join("LEFT JOIN {$gameModel} g on g.user_id = f.id")
            ->where(array('f.status'=>1))->where($where)
            ->field('f.id as user_id,f.username,f.nick_name,f.is_robot,count(g.id) as gameCount,group_concat(g.create_time) as create_time,group_concat(g.earn_point) as earn_point,group_concat(g.result) as result')
            ->group('f.id')->having("gameCount >= {$gameCount}")->select();

        if($dateType != 4){
            foreach ($FrontUser as $k => $v) 
            {
                $createTimeArr = explode(',', $v['create_time']);
                $timeArr = [];
                foreach ($createTimeArr as $kk => $vv) {
                    $sameday  = date('Ymd',$vv); //当天日期
                    $segmTime = $gameType == 1 ? strtotime($sameday.'10:32') : strtotime($sameday.'12:00');
                    //分割竞猜所属日期
                    $timeArr[] = $vv > $segmTime ? date('Ymd',$vv) : date('Ymd',$vv-86400);
                }
                unset($FrontUser[$k]['create_time']);
                $uniqueArray = array_unique($timeArr);
                $is_true = $this->checkDate($uniqueArray,$begin,$end,$checkDateNum);
                if(!$is_true){
                    unset($FrontUser[$k]);
                }
            }
        }

        if(empty($FrontUser)) $this->error('无用户上榜');

        //获取胜率和详细记录
        $userRanking = getGambleRate($FrontUser, $gameType);

        //对数组进行排序,胜率>盈利积分>竞猜场次数>全赢场次数>后台生成的会员编号
        $winrate = $pointCount = $gameCount = $win = $userid = array();
        foreach ($userRanking as $k => $v) {
            unset($userRanking[$k]['half'],$userRanking[$k]['level'],$userRanking[$k]['donate']);
            $userRanking[$k]['gameType'] = $gameType;
            $userRanking[$k]['dateType'] = $dateType;
            $userRanking[$k]['listDate'] = $end;
            $winrate   [] = $v['winrateTwo'];
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
                        $userRanking);
        
        foreach ($userRanking as $k => $v) 
        {
            //名次
            $userRanking[$k]['ranking'] = $k+1;
        }

        if($export == 1)  //排行预览
        {
            foreach ($userRanking as $k => $v) {
                foreach ($FrontUser as $kk => $vv) {
                    if($v['user_id'] == $vv['user_id']){
                        $userRanking[$k]['nick_name'] = $vv['nick_name'];
                    }
                }
            }
            //手动指定显示条数
            $_REQUEST ['numPerPage'] = 99999;
            $this->assign('list', $userRanking);
            $this->assign ( 'totalCount', count($userRanking) );
            $pageNum =empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
            $this->assign ( 'numPerPage', $pageNum ); //每页显示多少条
            $this->display("betpreview");
            die;
        }
        //删除排行榜，再添加。
        $rs = M('rankBetting')->where(array('listDate'=>$end,'gameType'=>$gameType,'dateType'=>$dateType))->delete();
        M('rankBetting')->addAll($userRanking);
        $this->success("刷新成功！");
    }

    public function rewardLog() {
        $map = $this->_search('rewardLog');
        //时间查询
        if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
            if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                $startTime = $_REQUEST ['startTime'];
                $endTime   = $_REQUEST ['endTime'];
                $map['begin_date'] = array('BETWEEN',array($startTime,$endTime));
                $map['end_date'] = array('BETWEEN',array($startTime,$endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = $_REQUEST ['startTime'];
                $map['begin_date'] = array('EGT',$strtotime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = $_REQUEST['endTime'];
                $map['end_date'] = array('ELT',$endTime);
            }
        }

        //用户名、昵称查询
        $this->addUserMap($map);

        $list = $this->_list(D('RewardLogView'), $map,'begin_date desc,end_date desc,game_type asc,ranking asc',NULL);

        //发放总金币
        $coin = M("rewardLog")->where($map)->sum('coin');
        $this->assign('coin', $coin);
        $this->assign('list', $list);
        $this->display();
    }
    /**
     * 刷新前7天/前30天/前90天排行榜
     *
     */
    public function breakRanking() {
        $gameType = $_REQUEST['gameType'];
        $dateType = $_REQUEST['dateType'];
        $export   = $_REQUEST['export'];
        //执行程序获取排行榜
        $this->getRankingList($gameType,$dateType,$export); 
        if($export != 1){
            S('home_web_userindex_rank',NULL);
        }
        $this->success("刷新成功！");
    }

    public function getRankingList($gameType=1,$dateType=1,$export=false)
    {
        //日期筛选
        list($begin,$end) = getRankBlockDate($gameType,$dateType);
        
        $gameModel = $gameType == 1 ? 'qc_gamble' : 'qc_gamblebk';

        $where['g.result']     = array("IN",array('1','0.5','2','-1','-0.5'));

        //加上对应时间
        $time = $gameType == 1 ? (10*60+32)*60 : (12*60)*60;

        $where['g.create_time']  = array( "between",array( strtotime($begin) + $time, strtotime($end) + 86400 + $time ) );

        $where['g.play_type'] = ['in', [-1,1]];

        switch ($dateType) 
        {
            //周榜在7日内，有连续3天没有参与竞猜，或者参与竞猜数量不到15场，不上周排名榜
            case '1':
                $gameCount = 15;
                $checkDateNum = 3;
                break;
            //月榜在1个月内，有连续5天没有参与竞猜，或者参与竞猜数量不到50场，不上月排名榜
            case '2':
                $gameCount = 50;
                $checkDateNum = 5;
                break;
            //季榜在1个季内，有连续5天没有参与竞猜，或者参与竞猜数量不到150场，不上季排名榜
            case '3':
                $gameCount = 150;
                $checkDateNum = 5;
                break;
        }

        //找出满足条件的用户
        $FrontUser = M("FrontUser f")
            ->join("LEFT JOIN {$gameModel} g on g.user_id = f.id")
            ->where(array('f.status'=>1))->where($where)
            ->field('f.id as user_id,f.username,f.nick_name,f.is_robot,count(g.id) as gameCount,group_concat(g.create_time) as create_time,group_concat(g.earn_point) as earn_point,group_concat(g.result) as result')
            ->group('f.id')->having("gameCount >= {$gameCount}")->select();
        
        foreach ($FrontUser as $k => $v) 
        {
            $createTimeArr = explode(',', $v['create_time']);
            $timeArr = [];
            foreach ($createTimeArr as $kk => $vv) {
                $sameday  = date('Ymd',$vv); //当天日期
                $segmTime = $gameType == 1 ? strtotime($sameday.'10:32') : strtotime($sameday.'12:00');
                //分割竞猜所属日期
                $timeArr[] = $vv > $segmTime ? date('Ymd',$vv) : date('Ymd',$vv-86400);
            }
            unset($FrontUser[$k]['create_time']);
            $uniqueArray = array_unique($timeArr);
            $is_true = $this->checkDate($uniqueArray,$begin,$end,$checkDateNum);
            if(!$is_true){
                unset($FrontUser[$k]);
            }
        }

        if(!empty($FrontUser))
        {
            $userRanking = getGambleRate($FrontUser, $gameType);
            //对数组进行排序,胜率>盈利积分>竞猜场次数>全赢场次数>赢半场次数＞后台生成的会员编号
            $winrate = $pointCount = $gameCount = $win = $half = $userid = array();
            foreach ($userRanking as $k => $v) {
                $userRanking[$k]['gameType'] = $gameType;
                $userRanking[$k]['dateType'] = $dateType;
                $userRanking[$k]['begin_date'] = $begin;
                $userRanking[$k]['end_date']   = $end;
                $winrate   [] = $v['winrateTwo'];
                $pointCount[] = $v['pointCount'];
                $gameCount [] = $v['gameCount'];
                $win       [] = $v['win'];
                $half      [] = $v['half'];
                $userid    [] = $v['user_id'];
            }
            //排序
            array_multisort($winrate, SORT_DESC,
                            $pointCount, SORT_DESC,
                            $gameCount, SORT_DESC,
                            $win, SORT_DESC,
                            $half, SORT_DESC,
                            $userid, SORT_ASC, 
                            $userRanking);
            foreach ($userRanking as $k => $v) 
            {
                //名次
                $userRanking[$k]['ranking'] = $k+1;
            }
            if($export == 1)  //排行预览
            {
                foreach ($userRanking as $k => $v) {
                    foreach ($FrontUser as $kk => $vv) {
                        if($v['user_id'] == $vv['user_id']){
                            $userRanking[$k]['nick_name'] = $vv['nick_name'];
                        }
                    }
                }
                //手动指定显示条数
                $_REQUEST ['numPerPage'] = 99999;
                $this->assign('list', $userRanking);
                $this->assign ( 'totalCount', count($userRanking) );
                $pageNum =empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
                $this->assign ( 'numPerPage', $pageNum ); //每页显示多少条
                $this->display("preview");
                die;
            }
            //删除排行榜，再添加。
            $rs = M('rankingList')->where(array('begin_date'=>$begin,'end_date'=>$end,'gameType'=>$gameType,'dateType'=>$dateType))->delete();
            M('rankingList')->addAll($userRanking);
        }else{
            $this->error('没有用户上榜！');
        }
    }

    //判断是否连续无竞猜
    public function checkDate($arr,$begin,$end,$date)
    {
        $arr = array_values($arr); //重置键值
        sort($arr);
        if(count($arr) < 3) return false;

        if(current($arr) != $begin)  //判断前几天是否有无连续竞猜
        {
            if ((strtotime(current($arr)) - strtotime($begin)) / 86400 >= $date)
                return false;
        }

        if(end($arr) != $end)  //判断后几天是否有无连续竞猜
        {
            if ((strtotime($end) - strtotime(end($arr))) / 86400 >= $date)
                return false;
        }

        $flag = true;
        for ($i=0; $i < count($arr)-1; $i++) {
            if ((strtotime($arr[$i+1]) - strtotime($arr[$i])) / 86400 > $date)
                $flag = false;
        }
        return $flag;
    }

    //判断榜类型
    public function doReward($gameType,$dateType)
    {
        switch ($dateType) {
            case '1':
                if(date('w') != 1)
                    $this->error("今天不是周一，不能发放");
                break;
            case '2':
            case '3':
                if(date('j') != 1)
                    $this->error("今天不是1号，不能发放");
                break;
        }
        //获取排行榜前十名
        list($begin,$end) = getRankBlockDate($gameType,$dateType);
        $where = ['begin_date'=>$begin,'end_date'=>$end,'gameType'=>$gameType,'dateType'=>$dateType,'ranking'=>['elt',10]];
        $rank = M('rankingList')->where($where)->field("user_id,ranking,winrate,begin_date,end_date,dateType,gameType")->order("ranking asc")->limit(10)->select();
        switch ($dateType) {
            case '1': $str = '周'; break;
            case '2': $str = '月'; break;
            case '3': $str = '季'; break;
        }
        //是否有用户上榜
        if(!$rank){
            $this->error("发放失败,上{$str}没有用户上榜！");
        }
        //是否已经发放过
        $RewardLog = M('RewardLog')->where(['begin_date'=>$begin,'end_date'=>$end,'game_type'=>$gameType,'date_type'=>$dateType])->select();
        if($RewardLog){
            $this->error("上{$str}奖励已经发放过了！");
        }
        switch ($dateType) {
            case '1':
                //周榜
                $rs = $this->doCoin($rank,1000,700,500,$dateType,$gameType);
                break;
            case '2':
                //月榜
                $rs = $this->doCoin($rank,1800,1300,500,$dateType,$gameType);
                break;
            case '3':
                //季榜
                $rs = $this->doCoin($rank,2000,1500,800,$dateType,$gameType);
                break;
        }
        if($rs){
            $this->success("发放奖励成功!");
        }else{
            $this->error("发放奖励失败！");
        }
    }

    //处理奖励数据
    public function doCoin($rank,$one,$two,$three,$dateType,$gameType)
    {
        $gameName = $gameType == '1' ? '足球' : '篮球';
        switch ($dateType) {
            case '1': $str = $gameName.'周榜'; break;
            case '2': $str = $gameName.'月榜'; break;
            case '3': $str = $gameName.'季榜'; break;
        }
        $num = count($rank);
        $rank_num = 0;
        //开始事务
        M()->startTrans();
        foreach ($rank as $k => $v) 
        {
            $userCoin = M('FrontUser')->where(['id'=>$v['user_id']])->field("coin,unable_coin")->find();
            if($v['ranking'] == 1)
            {
                //第一名奖励$one币
                $coin = $v['winrate'] >= 65 ? $one : floor($one/2); //小于65奖金减半
                $rs  = M('FrontUser')->where(['id'=>$v['user_id']])->setInc('coin',$coin);
                //添加交易记录
                $rs2 = $this->addAccountLog($v['user_id'],$coin,$userCoin,$str."第1名赠送");
                //发送系统通知
                $rs3 = $this->addMsg($v,$str,$coin);
                //添加奖励记录
                $rs4 = $this->addRewardLog($v,$coin);
            }
            elseif ($v['ranking'] >= 2 && $v['ranking'] <= 5)
            {
                //2-5名奖励$two币
                $coin = $v['winrate'] >= 65 ? $two : floor($two/2); //小于65奖金减半
                $rs  = M('FrontUser')->where(['id'=>$v['user_id']])->setInc('coin',$coin);
                //添加交易记录
                $rs2 = $this->addAccountLog($v['user_id'],$coin,$userCoin,$str."第2-5名赠送");
                //发送系统通知
                $rs3 = $this->addMsg($v,$str,$coin);
                //添加奖励记录
                $rs4 = $this->addRewardLog($v,$coin);
            }
            elseif ($v['ranking'] >= 6 && $v['ranking'] <= 10) 
            {
                //6-10名奖励$three币
                $coin = $v['winrate'] >= 65 ? $three : floor($three/2); //小于65奖金减半
                $rs  = M('FrontUser')->where(['id'=>$v['user_id']])->setInc('coin',$coin);
                //添加交易记录
                $rs2 = $this->addAccountLog($v['user_id'],$coin,$userCoin,$str."第6-10名赠送");
                //发送系统通知
                $rs3 = $this->addMsg($v,$str,$coin);
                //添加奖励记录
                $rs4 = $this->addRewardLog($v,$coin);
            }
            if($rs && $rs2 && $rs3 &&$rs4){
                $rank_num ++;
            }
        }
        if($num == $rank_num){
            M()->commit();
            return true;
        }else{
            M()->rollback();
            return false;
        }
    }

    //添加交易记录
    public function addAccountLog($user_id,$change_num,$userCoin,$desc)
    {
        $arr = [
            'user_id'    =>  $user_id,
            'log_time'   =>  time(),
            'log_type'   =>  5,
            'log_status' =>  1,
            'change_num' =>  $change_num,
            'total_coin' =>  $userCoin['coin']+$userCoin['unable_coin']+$change_num,
            'desc'       =>  $desc,
            'platform'   =>  1,
            'operation_time' => time()
        ];
        $rs = M('AccountLog')->add($arr);
        if($rs)
            return true;
        else
            return false;
    }
    //添加奖励记录
    public function addRewardLog($v,$coin)
    {   
        $arr = [
            'user_id'    =>  $v['user_id'],
            'ranking'    =>  $v['ranking'],
            'date_type'  =>  $v['dateType'],
            'game_type'  =>  $v['gameType'],
            'begin_date' =>  $v['begin_date'],
            'end_date'   =>  $v['end_date'],
            'coin'       =>  $coin,
        ];
        $rs = M('RewardLog')->add($arr);
        if($rs)
            return true;
        else
            return false;
    }

    //发送消息
    public function addMsg($v,$str,$coin)
    {
        $msg = "恭喜你在".
               date('Y-m-d',strtotime($v['begin_date'])).
               "至".
               date('Y-m-d',strtotime($v['end_date'])).
               "获得".$str."第".$v['ranking']."名，赠送金币".
               $coin."，详情请查看帐户明细。请再接再厉噢！";
        $rs = sendMsg($v['user_id'],'金币到账通知',$msg);
        if($rs)
            return true;
        else
            return false;
    }
}