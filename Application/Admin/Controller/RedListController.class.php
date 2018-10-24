<?php
set_time_limit(0);//0表示不限时
/**
 * 红人榜列表控制器
 *
 * @author dengweijun <406516482@qq.com>
 *
 * @since  2016-2-19
 */
class RedListController extends CommonController {
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
    public function index() {
        //列表过滤器，生成查询Map对象
        $map = $this->_search('RedListView');
        $map['game_type'] = I('gameType') == 1 ? 1 : 2;//1:足球；2：篮球
        //时间查询
        if(!empty($_REQUEST ['list_date'])){
            $map['list_date'] = $_REQUEST ['list_date'];
        }else{
            $list_date = M('RedList')->where(['game_type'=>$map['game_type']])->order('id desc')->limit(1)->getField('list_date');
            $map['list_date'] = $list_date;
            $_REQUEST['list_date'] = $list_date;
        }
        //排名筛选
        $rank = I('rank');
        if (! empty($rank))
        {
            if ($rank == 1)
                $map['_string'] = 'ranking < 10';
            elseif ($rank == 2)
                $map['_string'] = 'ranking < 50';
            elseif ($rank == 3)
                $map['_string'] = 'ranking < 100';
        }
        //手动获取列表
        $list = $this->_list(D("RedListView"), $map,'list_date desc,ranking asc',NULL);

        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 亚盘活动竞猜排行榜
     *
     */
    public function plate()
    {
        $game_id = I('game_id');
        //竞猜时间
        if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
                if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                    $startTime = strtotime($_REQUEST ['startTime'])+3600*10+35*60;
                    $endTime   = strtotime($_REQUEST ['endTime'])+86400+3600*10+35*60;
                    $map['create_time'] = array('BETWEEN',array($startTime,$endTime));
                    $time['create_time'] = array('BETWEEN',array($startTime,$endTime));
                } elseif (!empty($_REQUEST['startTime'])) {
                    $startTime = strtotime($_REQUEST ['startTime'])+3600*10+35*60;
                    $map['create_time'] = array('EGT',$startTime);
                    $time['create_time'] = array('EGT',$startTime);
                } elseif (!empty($_REQUEST['endTime'])) {
                    $endTime = strtotime($_REQUEST['endTime'])+86400+3600*10+35*60;
                    $map['create_time'] = array('ELT',$endTime);
                    $time['create_time'] = array('ELT',$endTime);
                }
            }
            else
            {
                $startTime = strtotime(date("Y-m-d",mktime(0,0,0,date("m"),date("d")-7,date("Y"))+3600*10+35*60));
                $map['create_time'] = array('EGT',$startTime);
                $time['create_time'] = array('EGT',$startTime);
                $_REQUEST['startTime'] = date("Y-m-d",$startTime);
            }
        $username = I('username');
        if(! empty($username))
            $map['username'] = ['like','%'.$username.'%'];
        $nick_name = I("nick_name");
        if(! empty($nick_name))
            $map['nick_name'] = ['like','%'.$nick_name.'%'];

        $condition = I('condition','',int);
        $is_robot = I('is_robot');
        if($is_robot != '')
            $map['is_robot'] = $is_robot;
        $map['play_type'] = ['in',[1,-1]];
        //列表 每个用户竞猜总数
        $list = M('Gamble g')
                ->field("f.id,f.nick_name,f.is_robot,count('f.id') as countNum,g.result,f.username,g.user_id")
                ->join('left join qc_front_user f on f.id = g.user_id')
                ->where($map)
                ->group('f.id')
                ->select();
        // 胜率
        foreach ($list as $key => $value)
        {
            $u_id[] = $value['id'];
            $countArr = $value['countNum'];
            //筛选满场
            if(! empty($condition))
            {
                if($condition > $value['countNum'])
                {
                    unset($list[$key]);
                }
            }
        }
        //统计每个用户亚盘赢的场数
        $countWin = M('Gamble')
            ->field("count('id') as win,user_id")
            ->where($time)
            ->where(['result'=>['in',[1,0.5]],'play_type'=>['in',[1,-1]],'user_id'=>['in',$u_id]])->group('user_id')
            ->select();
        foreach ($list as $key => $value)
        {
            foreach ($countWin as $k => $v)
            {
                if($value['id'] == $v['user_id'])
                {
                    $list[$key]['win'] = round($v['win']/$value['countNum']*100);
                }
            }
        }
        //排序
        array_multisort(get_arr_column($list,'win'),SORT_DESC,
                        get_arr_column($list,'countNum'),SORT_DESC,
                        $list);
        if(! empty($game_id))
        {
            $data = $map;
            $data['game_id'] = trim($game_id);
            $claim = I("claim");
            if(! empty($claim))
            {
                unset($map['play_type']);
                switch ($claim) 
                {
                    case '1':
                        $data['result'] = ['in',[1,0.5]];
                        $data['play_type'] = 1;
                    break;
                    case '2':
                        $data['result'] = ['in',[-1,-0.5]];
                        $data['play_type'] = 1; 
                    break;
                    case '3': 
                        $data['result'] = ['in',[1,0.5]];
                        $data['play_type'] = -1; 
                    break;
                    case '4': 
                        $data['result'] = ['in',[-1,-0.5]];
                        $data['play_type'] = -1; 
                    break;
                }
            }
            
            $resultList = M('Gamble g')
                ->field("g.id,f.nick_name,f.is_robot,g.game_id,g.result,f.username,g.user_id,g.play_type")
                ->join('left join qc_front_user f on f.id = g.user_id')
                ->where($data)
                ->select();

            foreach ($list as $key => $value) 
            {
                foreach ($resultList as $k => $v) 
                {
                    if($value['user_id'] == $v['user_id'])
                    {   
                        $list[$key]['game_id'] = $v['game_id'];
                        if(($v['result'] == 1 || $v['result'] == 0.5) && $v['play_type'] == 1)//让分赢
                        {
                            $list[$key]['letRe'] = $v['result'];
                        }
                        if(($v['result'] == -1 || $v['result'] == -0.5) && $v['play_type'] == 1)//让分输
                        {
                            $list[$key]['letRe'] = $v['result'];
                        }
                        if(($v['result'] == 1 || $v['result'] == 0.5) && $v['play_type'] == -1)//大小赢
                        {
                            $list[$key]['sizeRe'] = $v['result'];
                        }
                        if(($v['result'] == -1 || $v['result'] == -0.5) && $v['play_type'] == -1)//大小输
                        {
                            $list[$key]['sizeRe'] = $v['result'];
                        }
                    }
                }
            }
            //清除没有竞猜的用户
            foreach ($list as $key => $value) 
            {
                if($value['letRe'] == null && $value['sizeRe'] == null)
                {
                    unset($list[$key]);
                }
            }
        }
        $countList = count($list);
        $this->assign('totalCount',$countList);
        $this->assign('list',$list);
        $this->display();

    }

    //过滤针对这场赛事ID没有竞猜的用户
    // public function retainUser($game_id)
    // {
    //     if($game_id)
    //     {

    //     }
    //     else
    //     {
    //         return 
    //     }
    // }

    /**
     * 刷新昨天红人榜
     *
     */
    public function breakRanking() {
        $gameType = $_REQUEST['gameType'];
        //获取红人榜
        $this->getRedList($gameType);
        $this->success('刷新成功！');
    }

    /**
     * 获取红人榜
     *
     * @param int  $gameType     赛事类型(1:足球   2:篮球   默认为1)
     *
     * @return  array
    */
    public function getRedList($gameType=1){
        $blockTime  = getBlockTime($gameType,$gamble=true);

        $list_date  = date('Ymd',$blockTime['beginTime']-86400);
        $gameModel  = $gameType == 1 ? 'qc_gamble' : 'qc_gamblebk';

        $where['create_time'] = ['between',[$blockTime['beginTime']-86400,$blockTime['endTime']-86400]];
        if($gameType == 1) {
            $where['play_type']   = ['in', [-1,1]];
        }

        //$sign = $gameType == 1 ? 'fbConfig' : 'bkConfig';
        //$gameConf = getWebConfig($sign);
        if (in_array(date('N',$blockTime['beginTime']-86400),[1,2,3,4,5]))
        {
            //$gameCount = $gameConf['weekday_norm_times'] + $gameConf['weekday_impt_times'];
            $rankNumber = 3; //周1-5   3场
        }
        else
        {
            //$gameCount = $gameConf['weekend_norm_times'] + $gameConf['weekend_impt_times'];
            $rankNumber = 5; //周6-7   5场
        }

        $FrontUser = M("FrontUser f")
            ->join("LEFT JOIN {$gameModel} g on g.user_id = f.id")
            ->where(array('f.status'=>1))->where($where)
            ->field('f.id as user_id,f.username,f.nick_name,f.is_robot,count(g.id) as gameCount,group_concat(g.earn_point) as earn_point,group_concat(g.result) as result')
            ->group('f.id')->having("gameCount >= {$rankNumber}")->select();

        //检查是否有未完场的比赛
        foreach ($FrontUser as $k => $v) {
            $is_true = $this->checkResult($v['result']);
            if(!$is_true){
                unset($FrontUser[$k]);
            }
        }

        if(empty($FrontUser)){
            $this->error('没有用户满足红人榜条件');
        }

        //获取胜率和详细记录
        $userRanking = getGambleRate($FrontUser, $gameType);
        
        $winrate = $pointCount = $gameCount = $win = $half = $userid = array();
        //对数组进行排序,胜率>盈利积分>竞猜场次数>全赢场次数>赢半场次数＞后台生成的会员编号
        foreach ($userRanking as $k => $v) {
            $userRanking[$k]['list_date'] = $list_date;
            $userRanking[$k]['game_type'] = $v['gameType'];
            unset($userRanking[$k]['gameType']);
            $winrate   [] = $v['winrateTwo'];
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
                        $userRanking);
        foreach ($userRanking as $k => $v) {
            //名次
            $userRanking[$k]['ranking'] = $k+1;
        }

        //删除昨天红人榜，再添加。
        M('redList')->where(['list_date'=>$list_date,'game_type'=>$gameType])->delete();
        M('redList')->addAll($userRanking);
    }

    public function checkResult($array){
        $arr = explode(',', $array);
        foreach ($arr as $key => $value) {
            if($value == 0){
                return false;
            }
        }
        return true;
    }

    /**
     * 获取昨日胜率
     *
     * @param int  $array     竞猜记录
     * @param int  $gameType  赛事类型(1:足球   2:篮球   默认为1)
     *
     * @return  array
    */
    public function YestWinrate($array,$gameType=1,$user_id){
        if(empty($array)){
            $blockTime = getBlockTime(1, $gamble = true);
            $where['user_id']     = $user_id;
            $where['create_time'] = ['between',[$blockTime['beginTime']-86400, $blockTime['endTime']-86400]];
            if($gameType == 1) {
                $where['play_type']   = ['in', [-1,1]];
            }
            $where['result'] = ['NEQ', 0];
            $GambleModel = $gameType == 1 ? M('Gamble') : M('Gamblebk');
            $array = $GambleModel->where($where)->select();
        }
        //计算胜率
        $win        = 0;
        $half       = 0;
        $level      = 0;
        $transport  = 0;
        $donate     = 0;
        $pointCount = 0;
        foreach ($array as $k => $v) {
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
                'count'      =>  count($array),
                "winrate"    =>  $winrate,
                'win'        =>  $win,
                'half'       =>  $half,
                'level'      =>  $level,
                'transport'  =>  $transport,
                'donate'     =>  $donate,
                'pointCount' =>  $pointCount,
            );
    }
}