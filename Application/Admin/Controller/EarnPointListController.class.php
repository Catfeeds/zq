<?php
set_time_limit(0);//0表示不限时
/**
 * 盈利榜榜列表控制器
 * @author dengweijun <406516482@qq.com>
 * @since  2016-8-30
 */
class EarnPointListController extends CommonController {
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
        $map = $this->_search('EarnPointListView');
        $map['gameType'] = I('gameType') == 1 ? 1 : 2;//区分足球还是篮球 1:足球；2：篮球
        //时间查询
        if(!empty($_REQUEST ['listDate'])){
            $map['listDate'] = $_REQUEST ['listDate'];
        }else{
            $map['listDate'] = date('Ymd',strtotime('-1 day'));
            if(M('EarnPointList')->where(['listDate'=>$map['listDate'],'gameType'=>$map['gameType']])->count() <= 0){
                $map['listDate'] = date('Ymd',strtotime('-2 day'));
            }
            $_REQUEST['listDate'] = $map['listDate'];
        }
        //排名筛选
        $rank = I('rank');
        if (! empty($rank)) $map['ranking'] = ['elt',$rank];
        //手动获取列表
        $list = $this->_list(D('EarnPointListView'), $map,'listDate desc,dateType asc,ranking asc',NULL);
        $this->assign('list', $list);
        $this->display();
    }

	//竞彩盈利榜
    public function RankBetprofit()
    {
        //列表过滤器，生成查询Map对象
        $map = $this->_search('RankBetprofitView');
        //时间查询
        if(!empty($_REQUEST ['listDate'])){
            $map['listDate'] = $_REQUEST ['listDate'];
        }else{
            $map['listDate'] = date('Ymd',strtotime('-1 day'));
            if(M('RankBetprofit')->where(['listDate'=>$map['listDate']])->count() <= 0){
                $map['listDate'] = date('Ymd',strtotime('-2 day'));
            }
            $_REQUEST['listDate'] = $map['listDate'];
        }
        //排名筛选
        $rank = I('rank');
        if (! empty($rank)) $map['ranking'] = ['elt',$rank];
        //手动获取列表
        $list = $this->_list(D('RankBetprofitView'), $map,'listDate desc,dateType asc,ranking asc',NULL);
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 竞彩积分盈利榜结算
     * @parmas：gameType、dateType
     */
    public function breakRankBetprofit($gameType=1,$dateType=1)
    {
        switch (intval($dateType)) {
            case 1:
            case 2:
            case 3:
                list($begin, $end) = getRankBlockDate($gameType, $dateType);
                $time       = $gameType == 1 ? (10 * 60 + 32) * 60 : (12 * 60) * 60;
                $listDate   = $end;
                $createTime = ["between", [strtotime($begin) + $time, strtotime($end) + 86400 + $time]];
                break;

            case 4:
                $blockTime  = getBlockTime($gameType, $gamble = true);
                $listDate   = date('Ymd', $blockTime['beginTime'] - 86400);
                $createTime = ['between', [$blockTime['beginTime'] - 86400, $blockTime['endTime'] - 86400]];
                break;

            default;
        }

        $where['F.status']      = 1;
        $where['G.create_time'] = $createTime;
        $where['G.result']      = ["IN", ['1', '-1']];
        $where['G.play_type']   = ["IN", ['2', '-2']];
        $gameModel              = $gameType == 1 ? '__GAMBLE__' : '__GAMBLEBK__';

        //用户的竞猜
        $userGambles = M("FrontUser")
            ->alias('F')
            ->field('F.id as user_id, F.nick_name, F.is_robot, GROUP_CONCAT(G.result) as result, GROUP_CONCAT(G.earn_point) as earn_point')
            ->join("LEFT JOIN $gameModel G ON F.id = G.user_id")
            ->where($where)
            ->order('G.user_id ASC')
            ->group('G.user_id')
            ->select();

        if(!$userGambles || empty($userGambles)) $this->error("无数据刷新！");
        
        //用户竞猜详情,排行数据
        $userRanking = getGambleRate($userGambles, $gameType);

        foreach ($userRanking as $k => $v) {
            $userRanking[$k]['gameType'] = $gameType;
            $userRanking[$k]['dateType'] = $dateType;
            $userRanking[$k]['listDate'] = $listDate;
            $pointCount[] = $v['pointCount'];
        }

        //排序
        array_multisort($pointCount, SORT_DESC, $userRanking);
        foreach ($userRanking as $kk => $vv) {
            $userRanking[$kk]['ranking'] = $kk + 1;
        }

        //删除旧数据并批量插入
        M('RankBetprofit')->where(['listDate' => $listDate, 'dateType' => $dateType, 'gameType' => $gameType])->delete();
        M('RankBetprofit')->addAll($userRanking);

        $this->success("刷新成功！");
    }

    /**
     * 亚盘积分盈利榜结算
     * @parmas：gameType、dateType、playType
     */
    public function breakPonitEearning()
    {
        //按赛事类型、排行榜类型组装条件
        $gameType = $_REQUEST['gameType'] ?: 1; //足球|篮球
        $dateType = $_REQUEST['dateType'] ?: 4; //日、周、月、季
        $playType = $_REQUEST['playType'] ?: 0; //玩法

        switch (intval($dateType)) {
            case 1:
            case 2:
            case 3:
                list($begin, $end) = getRankBlockDate($gameType, $dateType);
                $time       = $gameType == 1 ? (10 * 60 + 32) * 60 : (12 * 60) * 60;
                $listDate   = $end;
                $createTime = ["between", [strtotime($begin) + $time, strtotime($end) + 86400 + $time]];
                break;

            case 4:
                $blockTime  = getBlockTime($gameType, $gamble = true);
                $listDate   = date('Ymd', $blockTime['beginTime'] - 86400);
                $createTime = ['between', [$blockTime['beginTime'] - 86400, $blockTime['endTime'] - 86400]];
                break;

            default;
        }

        $where['F.status']      = 1;
        $where['G.create_time'] = $createTime;
        $where['G.result']      = ["IN", ['1', '0.5', '2', '-1', '-0.5']];
        $where['G.play_type']   = ["IN", ['1', '-1']];
        $gameModel              = $gameType == 1 ? '__GAMBLE__' : '__GAMBLEBK__';

        //筛选足球玩法竞猜
        if($playType && $_REQUEST['gameType'] == 1 )
            $where['play_type'] = $playType;

        //用户的竞猜
        $userGambles = M("FrontUser")
            ->alias('F')
            ->field('F.id as user_id, F.nick_name, F.is_robot, GROUP_CONCAT(G.result) as result, GROUP_CONCAT(G.earn_point) as earn_point')
            ->join("LEFT JOIN $gameModel G ON F.id = G.user_id")
            ->where($where)
            ->order('G.user_id ASC')
            ->group('G.user_id')
            ->select();

        if(!$userGambles || empty($userGambles)) $this->error("无数据刷新！");
        
        //用户竞猜详情,排行数据
        $userRanking = getGambleRate($userGambles, $gameType);

        foreach ($userRanking as $k => $v) {
            $userRanking[$k]['gameType'] = $gameType;
            $userRanking[$k]['dateType'] = $dateType;
            $userRanking[$k]['listDate'] = $listDate;
            $pointCount[] = $v['pointCount'];
        }

        //排序
        array_multisort($pointCount, SORT_DESC, $userRanking);
        foreach ($userRanking as $kk => $vv) {
            $userRanking[$kk]['ranking'] = $kk + 1;
        }

        //删除旧数据并批量插入
        M('earnPointList')->where(['listDate' => $listDate, 'dateType' => $dateType, 'gameType' => $gameType])->delete();
        M('earnPointList')->addAll($userRanking);

        $this->success("刷新成功！");
    }
}