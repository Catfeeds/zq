<?php
/**
 * @author : longs
 * @Date : 18-3-15
 */

use Home\Services\WebfbService;
use Think\Model;
use Think\Tool\Tool;

class NewBaseModel extends Model
{

    /**
     * @var array
     */
    public $unionArr = [
        'premierleague'   => 36, //英超
        'laliga'          => 31, //西甲
        'bundesliga'      => 8,  //德甲
        'seriea'          => 34, //意甲
        'championsleague' => 103,//欧冠
        'afccl'           => 192,//亚冠
        'csl'             => 60, //中超
        'nba'             => 1,  //nba
        'cba'             => 5,  //cba
        '2018worldcup'    => 75, //世界杯
    ];

    public $navArray = [
        "Mtop" => 34,    //M站首页导航栏
        "Mfb" => 36,      //M站足球专栏导航
        "Mbk" => 37,    //M站篮球专栏导航
        "Mzh" => 38,    //M站综合专栏导航
        "Mdj" => 39     //M站电竞专栏导航
    ];

    /**
     * 获取M站广告图
     */
    public function getMobileAdverting() {
        if (!$Adver = S('m_index_adver')) {
            //轮播
            $Adver['banner'] = Tool::getAdList(1,5,4);
            //横幅广告
            $Adver['platform'] = Tool::getAdList(105,1,4);
            S('m_index_adver', json_encode($Adver), 300);
        }
        return $Adver;
    }


    /**
     * M站直播 只要和web端保持一致
     * 获取首页第一屏右边，直播比赛列表
     * 1.取当天正在直播的十场比赛，加上完场；足球和篮球各10场一起排序；
     * 2.筛选条件：直播赛事（一级赛事优先 > 有视频直播 > 有动画直播 >按开赛的时间先后）
     * 3.l.is_link：1, l.md_id有值，才有动画直播
     */
    public function getLiveGame(){
        if(true || !$liveGame = S('m_index_live_game')) {
            //足球
            $blockTime = getBlockTime(1, true);//获取竞猜分割日期的区间时间
            $fbGame = M('GameFbinfo g')->field('g.game_state as gameState, g.union_name as unionName, g.gtime, g.home_team_name as homeTeamName, g.home_team_id, g.away_team_id, g.away_team_name as awayTeamName, g.game_id as gameId, g.score, g.is_video, g.app_video, u.is_sub, l.is_link, l.md_id')
                ->join('left join qc_union u on g.union_id = u.union_id')
                ->join('left join qc_fb_linkbet l on g.game_id = l.game_id')
                ->where(['u.is_sub' => ['exp', 'is not null'], 'g.game_state' => ['in', [1, 2, 3, 4, -1]], 'g.gtime' => ['between', [strtotime('-1 day', $blockTime['beginTime']), $blockTime['endTime']]], 'g.status'=>1])
                ->order('gameState desc, u.is_sub asc, g.is_video desc, g.app_video desc, concat(l.is_link,l.md_id) desc, g.gtime desc')
                ->limit(20)->select();
            setTeamLogo($fbGame);

            foreach ($fbGame as $fk => &$fv) {
                $fv['gameType'] = 1;//足球
            }

            //篮球
            $blockTime = getBlockTime(2, true);//获取竞猜分割日期的区间时间
            $bkGame = M('GameBkinfo g')->field('g.game_state as gameState, g.union_name as unionName, g.gtime, g.home_team_name as homeTeamName, g.home_team_id, g.away_team_id, g.away_team_name as awayTeamName, g.game_id as gameId, g.score, g.is_video, g.app_video, u.is_sub, l.is_link, l.md_id')
                ->join('left join qc_bk_union u on g.union_id = u.union_id')
                ->join('left join qc_bk_linkbet l on g.game_id = l.game_id')
                ->where(['u.is_sub' => ['exp', 'is not null'], 'g.union_id' => ['in', [1, 5]],'g.game_state' => ['in', [1, 2, 3, 4, 5, 6, -1]], 'g.gtime' => ['between', [strtotime('-1 day', $blockTime['beginTime']), $blockTime['endTime']]], 'g.status'=>1])
                ->order('gameState desc, u.is_sub asc, g.is_video desc, g.app_video desc, concat(l.is_link,l.md_id) desc, g.gtime desc')
                ->limit(20)->select();
            setTeamLogo($bkGame, 2);

            foreach ($bkGame as $bk => &$bv) {
                $bv['gameType'] = 2;//篮球
            }
            // 数据为null 时 置空
            if (empty($fbGame)) { $fbGame = []; }
	        if (empty($bkGame)) { $bkGame = []; }
            $liveGame = array_merge($fbGame, $bkGame);
            $gameState = $is_sub = $is_video = $app_video = $flash = $gtime = [];
            foreach ($liveGame as $k => &$v) {
                //是否有flash
                if ($v['is_link'] && $v['md_id']) {
                    $flash[] = $v['isFlash'] = 1;
                } else {
                    if(empty($v['app_video']) || empty(json_decode($v['app_video'], true))) {//没有视频源，没有动画
                        unset($liveGame[$k]);
                        continue;
                    }

                    $flash[]  = $v['isFlash'] = 0;
                }

                $gameState[] = $v['gameState'];
                $is_sub[]    = $v['is_sub'];
                $is_video[]  = ($v['isVideo'] && $v['app_video'] && !empty(json_decode($v['app_video'], true))) ? 1 : 0;
                $gtime[]     = $v['gtime'];

                $v['gtime'] = date('H:i', $v['gtime']);
                $v['unionName'] = explode(',', $v['unionName'])[0];
                $v['homeTeamName'] = explode(',', $v['homeTeamName'])[0];
                $v['awayTeamName'] = explode(',', $v['awayTeamName'])[0];
            }

            //排序
            array_multisort($gameState, SORT_DESC, $is_sub, SORT_DESC, $is_video, SORT_DESC, $flash, SORT_DESC, $gtime, SORT_DESC, $liveGame);
            unset($gameState, $is_sub, $is_video, $app_video, $flash, $gtime);
            $liveGame = array_slice($liveGame, 0, 6);

            S('m_index_live_game', json_encode($liveGame), 60 * 10);
        }

        //实时查询比赛比分，状态
        if($liveGame){
            foreach($liveGame as $k => &$v){
                if($v['gameType'] == 1){
                    $one = M('GameFbinfo f')->field('f.game_state as gameState, f.score')->where(['game_id' => $v['gameId']])->find();
                }else{
                    $one = M('GameBkinfo b')->field('b.game_state as gameState, b.score')->where(['game_id' => $v['gameId']])->find();
                }
                $v['gameState'] = $one['gameState'];
                $v['score'] = explode('-', $one['score']);
                unset($v['is_video'], $v['app_video'], $v['is_sub'], $v['is_link'], $v['md_id'], $v['isFlash']);
            }
        }else{
            $liveGame = [];
        }
        return $liveGame;
    }


    /**
     * 根据手写位返回数据
     * @param $site 位置 eg: shouye_config
     * @return 返回手写位资讯数据
     */
    public function getSiteNews($site) {
        $IndexConfig = M('config')->where(['sign' => $site])->getField('config');
        $IndexArray= json_decode($IndexConfig, true);
        $map = array_column($IndexArray, "id");
        $data = M("PublishList")->field("id, title, short_title, web_recommend, top_recommend, add_time, img,click_number")->where(["id" => ["in",$map]])->select();
        // 添加sign和title数据到总数据
        foreach ($IndexArray as $value) {
            foreach ($data as $key => $val) {
                if ($value['id'] == $val["id"]) {
                    $data[$key]['sign'] = $value['sign'];
                    $data[$key]['indexTitle'] = $value['title'];
                    $data[$key]['time'] = date("Y-m-d", $val['add_time']);
                    $data[$key]['img'] = newsImgReplace($val);
                    $data[$key]['hot'] = ($val['web_recommend'] == 1 && $val['top_recommend'] == 1) ? 1 : 0;
                }
            }
        }
        return $data;
    }


    /**
     * 获取M站导航配置
     * @param $type = 导航类型 M站 默认id为34
     * @param string $field
     * @param array $where
     * @return array
     */
    public function getNavList($type, $field='name, ui_type_value as url, icon, sort', $where=[]) {
        $type = $this->navArray[$type];
        $list = M('Nav')->field($field.',type')->where(['status' => 1])->where($where)->order('sort asc')->select();

        //返回对应导航
        $nav = array();
        foreach ($list as $k => $v) {
            if($v['type'] == $type) {
                $nav[] = $v;
            }
        }
        return $nav;
    }

    /**
     * 传入上级id 获取子集数据
     * @param $pid 上级id 仅限于次级上级id 三级以上不行
     * @param $classTable 分类表名称
     * @param $table 表名
     * @param $field select 字段
     * @param array $map sql where map对象
     * @param int $limit sql limit 对象
     * @param string $order sql  order 对象
     * @return mixed 返回指定上级id下所有子集id数据集
     */
    public function getData($pid, $classTable, $table, $field, $map=[], $limit=1, $order="") {
        $PictureClass = M($classTable)->where("status=1")->select();
        $Class = Tool::getTree($PictureClass, $pid, $col_id="id", $col_pid="pid", $col_cid="level");
        $ids = array_column($Class, "id");
        $data = M($table)->field($field)->where($map)->where(["class_id" => ["in", $ids]])->order($order)->limit($limit)->select();
        return $data;
    }


    /**
     * 传入数组对象 获取对象所在id分类下的数据
     * @param $arrayIds 数组对象
     * @param $table 表名
     * @param $field 字段
     * @param array $map $map sql where map对象
     * @param int $limit $limit sql limit 对象
     * @param string $order  $order sql  order 对象
     * @return mixed 返回所选id数据集
     */
    public function getInformation($arrayIds, $table, $field, $map=[], $limit=1, $order="") {
        $data = M($table)->field($field)->where($map)->where(["class_id" => ["in", $arrayIds]])->order($order)->limit($limit)->select();
        return $data;
    }


    /**
     * @param $type 联赛的名称
     * @param string $year 赛事年代
     * @return array 返回赛事信息数组
     */
    public function getLive($type, $year='2017-2018') {
        //联赛id
        switch ($type) {
            case 'premierleague':
                $union_id = $this->unionArr['premierleague'];
                break;
            case 'laliga':
                $union_id = $this->unionArr['laliga'];
                break;
            case 'bundesliga':
                $union_id = $this->unionArr['bundesliga'];
                break;
            case 'seriea':
                $union_id = $this->unionArr['seriea'];
                break;
            case 'csl':
                $union_id = $this->unionArr['csl'];
                $year = '2018';
                break;
        }
        $where['union_id'] = $union_id;
        if(!$unionData = S('special_live_union_'.$union_id)) {
            $unionData = mongo('fb_union')->field("statistics.".$year.".matchResult.round,statistics.".$year.".matchResult.jh")->where($where)->select();
            S('special_live_union_'.$union_id,json_encode($unionData),3600);
        }

        $round     = $unionData[0]['statistics'][$year]['matchResult']['round']; //轮次
        $gameIdArr = $unionData[0]['statistics'][$year]['matchResult']['jh'];    //赛事数据
        $round = explode('/', $round);
        $allNum = $round[1]; //总轮次
        $nowNum = $round[0]; //当前轮次

        if(true || !$live = S('special_live_live_'.$union_id)) {
            $idArr = call_user_func_array('array_merge',$gameIdArr);
            $live = mongo('fb_game')->field('game_id,game_starttime,game_state,home_team_id ,home_team_name,away_team_name,away_team_id ,score ,game_starttime ,round')->where(['game_id'=>['in',$idArr]])->select();
            setTeamLogo($live);
            foreach ($live as $k => $v) {
                $live[$k]['gtime'] = $v['game_starttime']->sec;
                unset($live[$k]['game_starttime']);
            }
            S('special_live_live_'.$union_id,json_encode($live),120);
        }

        if(!$live){
            return ['status' => 0];
        }
        //排序处理
        foreach ($live as $k => $v) {
            $gtime[]   = $v['gtime'];
            $game_id[] = $v['game_id'];
        }
        array_multisort($gtime,SORT_ASC,$game_id,SORT_ASC,$live);

        $data = [];
        foreach ($live as $k => $v) {
            $gtime = $v['gtime'];
            $v['gtime'] = date('m-d H:i',$gtime);
            $v['week'] = $this->week(date("N", $gtime));
            $v['home_team_name'] = $v['home_team_name'][0];
            $v['away_team_name'] = $v['away_team_name'][0];
            if(!isset($v['game_state'])){
                if($gtime < time()){
                    $v['game_state'] = -1;
                }else{
                    $v['game_state'] = 0;
                }
            }

            //比分状态判断
            if( in_array($v['game_state'], [0,-10,-11,-12,-13,14]) || $v['score'] == '' ){
                $v['score'] = 'VS';
            }

            //链接
            if(in_array($v['game_state'], [1,2,3,4])){
                $v['href'] = U('/live/'.$v['game_id'].'@bf');
            }else if($v['game_state'] == -1){
                $v['href'] = U('/news@bf',['game_id'=>$v['game_id']]);
            }else{
                $v['href'] = U('/dataFenxi@bf',['game_id'=>$v['game_id']]);
            }
            //分轮次
            $rno = explode('_', $v['round']);
            $v['rno'] = $rno[1];
            unset($v['_id'],$v['round']);
            for ($i=0; $i < $allNum; $i++) {
                if($rno[1] == ($i+1)){
                    $data[$i][] = $v;
                }
            }
        }

        return ['status' => 1, 'allNum' => $allNum, "nowNum" => $nowNum, "info"=>$data];
    }



    /**
     * 欧冠和亚冠
     * @param $type
     * @param string $year
     * @return array
     */
    public function getLive2($type){
        //定义赛事类型
        $unionName = C('fb_union_name');
        $unionTitleName = C('fb_union_titleName');
        $year = '2017-2018';
        //联赛id
        switch ($type) {
            case 'championsleague':
                $union_id = $this->unionArr['championsleague'];
                $nowNum = 5;
                break;
            case 'afccl':
                $union_id = $this->unionArr['afccl'];
                $year = '2018';
                $nowNum = 4;
                break;
            case '2018worldcup':
                $union_id = $this->unionArr['2018worldcup'];
                $year = '2018';
                $nowNum = 0;
                break;
        }
        $where['union_id'] = $union_id;
        $mService = mongoService();
        //获取当前比赛进度
        $arrCupKind = $mService->select('fb_union',$where,["statistics.".$year.".matchResult.arrCupKind"]);
        $arrCupKind = $arrCupKind[0]['statistics'][$year]['matchResult']['arrCupKind'];
        $unionDataName = end($arrCupKind)[4].'_matchs';//最后一个

        $data = $this->handleGame($unionDataName,$where,$union_id,$year);
        $xiaozu = $data['xiaozu'];
        if($data['taotai'])
        {
            $taoTmp[] = ['title'=>$unionTitleName[end($arrCupKind)[4]]?:'','data'=>$data['taotai']];
        }
        //查询所有淘汰赛
        array_pop($arrCupKind);
        $arrCupKind = array_reverse($arrCupKind);
        foreach ($arrCupKind as $val)
        {
            $unionDataName = $val[4].'_matchs';//最后一个
            $tmp = $this->handleGame($unionDataName,$where,$union_id,$year);
            //多余数据不处理
            if($unionName[count($tmp['taotai'])])
            {
                $taoTmp[] = ['title'=>$unionTitleName[$val[4]]?:'','data'=>$tmp['taotai']];
            }
        }

        $nowGroup = null;
        foreach ($xiaozu as $k => $v) {
            if ($v['game_state'] == 0) {
                $nowGroup = $v['game_id'];
                break;
            }
        }

        //小组赛当前状态
        $xiaozu = array_chunk($xiaozu, 16);
        if ($nowGroup != null){
            foreach ($xiaozu as $k => $v) {
                foreach ($v as $gkey =>  $game) {
                    if ($game['game_id'] == $nowGroup) {
                        $nowGroup = $k;
                    }
                }
            }
        } else {
            $nowGroup = key(array_slice($xiaozu,-1,1,true)) + 1;
        }
//        //淘汰赛分轮词
//        switch (count($taotai)) {
//            case '16':
//                $nowGroup = '1/16决赛';
//                break;
//            case '8':
//                $nowGroup = '1/8决赛';
//                break;
//            case '4':
//                $nowGroup = '半准决赛';
//                break;
//            case '2':
//                $nowGroup = '准决赛';
//                break;
//            case '1':
//                $nowGroup = "决赛";
//                break;
//        }
//        $taotai = array_chunk($taotai, 8);
        $isTaotai = !empty($taoTmp);
        return ['status' => 1, 'nowGroup' => '淘汰赛', 'isTaotai' =>$isTaotai ,'xiaozu'=>$xiaozu,'taotai'=>$taoTmp];
    }

    //处理赛事数据
    public function handleGame($unionDataName,$where,$union_id,$year)
    {
        $mService = mongoService();
        //获取联赛数据
        if(true || !$unionData = S('special_live_union_'.$union_id)){
            //获取比赛
            $unionData = $mService->select('fb_union',$where,["statistics.".$year.".matchResult.".$unionDataName,"statistics.".$year.".matchResult.Groups_matchs"]);
            S('special_live_union_'.$union_id,json_encode($unionData),3600);
        }

        $gameIdArr = $unionData[0]['statistics'][$year]['matchResult']['Groups_matchs'];    //赛事数据
        if($unionDataName != 'Groups_matchs'){
            $final16   = $unionData[0]['statistics'][$year]['matchResult'][$unionDataName];
        }

        //小组赛事game_id
        $xxIdArr = call_user_func_array('array_merge',$gameIdArr);
        //淘汰赛事game_id
        if(!empty($final16)){
            foreach ($final16 as $k => $v) {
                if (count($final16) == 1) {
                    $ttIdArr[]= $v;
                } else {
                    $ttIdArr[] = $v[4];
                    $ttIdArr[] = $v[5];
                }
            }
            $idArr = array_merge($xxIdArr,$ttIdArr);
        }else{
            $idArr = $xxIdArr;
        }

        //获取赛事数据
        if(true || !$live = S('special_live_live_'.$union_id) or 1){
            $live = mongo('fb_game')->field('game_id,game_starttime,game_state, home_team_id,home_team_name,away_team_id ,away_team_name,score,round')->where(['game_id'=>['in',$idArr]])->select();
            setTeamLogo($live);
            foreach ($live as $k => $v) {
                $live[$k]['gtime'] = $v['game_starttime']->sec;
                unset($live[$k]['game_starttime']);
            }
            S('special_live_live_'.$union_id,json_encode($live),120);
        }
        if(!$live){
            return ['status' => 0];
        }
        //排序
        foreach ($live as $k => $v) {
            $gtime[]   = $v['gtime'];
            $game_id[] = $v['game_id'];
        }
        array_multisort($gtime,SORT_ASC,$game_id,SORT_ASC,$live);

        $xiaozu = $taotai = [];
        foreach ($live as $k => $v) {
            $gtime = $v['gtime'];
            $v['gtime'] = date('m-d H:i',$gtime);
            $v['week'] = $this->week(date("N", $gtime));
            $v['home_team_name'] = $v['home_team_name'][0];
            $v['away_team_name'] = $v['away_team_name'][0];
            if(!isset($v['game_state'])){
                if($gtime < time()){
                    $v['game_state'] = -1;
                }else{
                    $v['game_state'] = 0;
                }
            }
            //比分状态判断
            if( in_array($v['game_state'], [0,-10,-11,-12,-13,14]) || $v['score'] == '' ){
                $v['score'] = 'VS';
            }
            //链接
            if(in_array($v['game_state'], [1,2,3,4])){
                $v['href'] = U('/live/'.$v['game_id'].'@bf');
            }else if($v['game_state'] == -1){
                $v['href'] = U('/news@bf',['game_id'=>$v['game_id']]);
            }else{
                $v['href'] = U('/dataFenxi@bf',['game_id'=>$v['game_id']]);
            }
            $v['round'] = substr($v['round'],-1);
            unset($v['_id']);
            if(in_array($v['game_id'], $xxIdArr)){
                $xiaozu[] = $v;
            }
            if(in_array($v['game_id'], $ttIdArr)){
                $taotai[] = $v;
            }
        }
        return ['xiaozu'=>$xiaozu,'taotai'=>$taotai];
    }


    /**
     * 世界杯赛程
     */
    public function schedule(){
        //所有世界杯赛程
        if(!$schedule = S('world_cup_matchResult')){
            $wc = mongo('fb_union')->field('statistics.2018.matchResult')->where(['union_id' => 75])->find();
            $schedule = $wc['statistics'][2018]['matchResult'];
            S('world_cup_matchResult', $schedule, 600);
        }

        $group_matchs = $schedule['Groups_matchs'];//小组赛
        $gameIdArr = [];

        foreach($group_matchs as $mk => $mv){
            $gameIdArr = array_merge($gameIdArr, $mv);
        }

        $gameIdArr = array_merge_recursive($gameIdArr,
            $schedule['Groups_matchs']['A'],
	        $schedule['Groups_matchs']['B'],
	        $schedule['Groups_matchs']['C'],
	        $schedule['Groups_matchs']['D'],
	        $schedule['Groups_matchs']['E'],
	        $schedule['Groups_matchs']['F'],
	        $schedule['Groups_matchs']['G'],
	        $schedule['Groups_matchs']['H'],
            $schedule['1/8 Final_matchs'],
            $schedule['Quarter Final_matchs'],
            $schedule['Semifinal_matchs'],
            $schedule['Third place_matchs'],
            $schedule['Final_matchs']
        );

        //比赛详情
        if(!$games = S('m_world_cup_schedule_games')){
            if($gameIdArr){
                $games = mongo('fb_game')->field(['game_id','gtime','home_team_name','away_team_name','score','worldcup_num','game_state','home_team_id','away_team_id'])->where(['game_id' => ['IN', $gameIdArr]])->select();
                S('m_world_cup_schedule_games', $games, 5);
            }
        }
        $games = $this->getCountryTeamLogo($games);

        $gamble = $group_schedule = $fmatchs = $knockout_matchs = $giant_matchs = [];


        //豪门赛程
        $giantTeamMaps = [
            '德国' => 650,
            '巴西' => 778,
            '葡萄牙' => 765,
            '阿根廷' => 766,
            '比利时' => 645,
            '西班牙' => 772,
            '法国' => 649,
            '英格兰' => 744
        ];
        $giantTeamSort = [
            '德国' => 1,
            '巴西' => 2,
            '葡萄牙' => 3,
            '阿根廷' => 4,
            '比利时' => 5,
            '西班牙' => 6,
            '法国' => 7,
            '英格兰' => 8
        ];
        $giant_name = array_keys($giantTeamMaps);

        foreach($games as $gk => $gv){
            unset($games[$gk]['_id']);
            $games[$gk]['round'] = $gv['worldcup_num'];
            $games[$gk]['score'] = $gv['score']?: "---";
            $games[$gk]['home_team_name'] = $gv['home_team_name'][0];
            $games[$gk]['away_team_name'] = $gv['away_team_name'][0];
            $games[$gk]['gamble'] = $gamble[$gv['game_id']];
            $games[$gk]['gtime'] = date("m-d H:i" ,strtotime($gv['gtime']));
            $games[$gk]['week'] = $this->week(date("N", strtotime($gv['gtime'])));

            //小组赛程
            foreach($group_matchs as $gmk => $gmv){
                if(in_array($gv['game_id'], $gmv)){
                    $group_schedule[$gmk][] = $games[$gk];
                }
            }

            //淘汰赛
            if(in_array($gv['game_id'], $schedule['1/8 Final_matchs'])){
                $knockout_matchs['1/8'][] = $fmatchs[] = $games[$gk];
                $games[$gk]['group'] = 8;
            }elseif(in_array($gv['game_id'], $schedule['Quarter Final_matchs'])){
                $knockout_matchs['1/4'][] = $fmatchs[] = $games[$gk];
                $games[$gk]['group'] = 4;
            }elseif(in_array($gv['game_id'], $schedule['Semifinal_matchs'])){
                $knockout_matchs['1/2'][] = $fmatchs[] = $games[$gk];
                $games[$gk]['group'] = 2;
            }elseif(in_array($gv['game_id'], $schedule['Third place_matchs'])){
                $knockout_matchs['三四名'][] = $fmatchs[] = $games[$gk];
                $games[$gk]['group'] = 3;
            }elseif(in_array($gv['game_id'], $schedule['Final_matchs'])){
                $knockout_matchs['冠军'][] = $fmatchs[] = $games[$gk];
                $games[$gk]['group'] = 1;
            }

            if(in_array($gv['home_team_name'][0], $giant_name)){
                $giant_matchs[$gv['home_team_name'][0]][] = $games[$gk];
            }
            if(in_array($gv['away_team_name'][0], $giant_name)){
                $giant_matchs[$gv['away_team_name'][0]][] = $games[$gk];
            }
        }

        //豪门赛程排序
        foreach($giant_matchs as $gkn2 => $gkv2){
            $teamSort[] = $giantTeamSort[$gkn2];
        }
        array_multisort($teamSort, SORT_ASC, $giant_matchs);

        //积分榜
        $WebfbService = new WebfbService();
        $point_rank['A'] = $WebfbService->getFbUnionRank(75,4,'A');
        $point_rank['B'] = $WebfbService->getFbUnionRank(75,4,'B');
        $point_rank['C'] = $WebfbService->getFbUnionRank(75,4,'C');
        $point_rank['D'] = $WebfbService->getFbUnionRank(75,4,'D');
        $point_rank['E'] = $WebfbService->getFbUnionRank(75,4,'E');
        $point_rank['F'] = $WebfbService->getFbUnionRank(75,4,'F');
        $point_rank['G'] = $WebfbService->getFbUnionRank(75,4,'G');
        $point_rank['H'] = $WebfbService->getFbUnionRank(75,4,'H');
        $ajaxData = [
            'status' => 1,
            'data' => [
                'day_schedule'=> $games,
                'group_schedule'=> $group_schedule,
                'knockout_matchs' => array_reverse($knockout_matchs),
                'fmatchs' => $fmatchs,
                'giant_matchs'=> $giant_matchs,
                'point_rank'=> $point_rank,
                'giantTeamImgs' => $giantTeamMaps
            ]
        ];
        return $ajaxData;
    }


    /**
     * 获取篮球赛程
     * @param $union_name
     * @param string $year
     * @return mixed
     */
    public function getBkUnionSchedule($union_name,$year = "2017-2018") {
        $type = $this->unionArr[$union_name];
        $mService = mongoService();
        $union_data = $mService->select("bk_union",["union_id" => $type], ["statistics.".$year.".schedule"])[0]['statistics'][$year]['schedule'];
        return $union_data;
    }


    /**
     * @param $unionId 联赛ID（英超-36/西甲-31/中超-60/德甲-8/意甲-34/欧冠-103/亚冠-192）
     * @param int $num 数据limit对象
     * @param string $group 分组
     * @return array
     */
    public function getFbUnionRank($unionId,$num=15,$group='A') {
        $fbService = new \Home\Services\WebfbService();
        $data =  $fbService->getFbUnionRank($unionId,$num,$group);
        return $data;
    }


    /**
     * @param $unionId 联赛ID（英超-36/西甲-31/中超-60/德甲-8/意甲-34/欧冠-103/亚冠-192）
     * @param int $num 数据limit条数
     * @return array
     */
    public function getFbUnionArcher($unionId,$num=15) {
        $fbService = new \Home\Services\WebfbService();
        $data =  $fbService->getFbUnionArcher($unionId,$num);
        return $data;
    }


    /**
     * @param $unionId 联赛id (NBA-1，CBA-5）
     * @param $type  类型：1、东部联赛积分；2、西部联赛积分；3，得分榜；4，助攻榜；5、篮板榜 PS：CBA没有东西部分开，联赛积分请求1
     * @param int $num 获取数据条数 默认 15
     * @return array
     */
    public function getBkUnionRank($unionId,$type,$num=15) {
        $fbService = new \Home\Services\WebfbService();
        $data =  $fbService->getBkUnionRank($unionId,$type,$num);
        return $data;
    }


    /**
     * 更新实时状态信息
     * @param $array
     * @return mixed
     */
    public function getBkGameStatus($array) {
        $gameArray = $this->filterDateData($array);
        foreach ($gameArray as $k => $v) {
            $mService = mongoService();
            $data = $mService->select("bk_game_schedule",["game_id" => $v['id']], ["game_status", "game_info"]);
            $array[$k]['game_status'] = $data[0]['game_status'];
            $array[$k]['home_team_score'] =  $data[0]['game_info'][0];
            $array[$k]['away_team_score'] =  $data[0]['game_info'][1];
        }
        return $array;
    }


    /**
     * 刷选数据
     * @param $array
     * @return array
     */
    public function filterDateData($array) {
        $gameArray = [];
        foreach ($array as $k => $v) {
            $yesTimeStamp = (strtotime($v['day']) - (60 * 60 * 48));
            $tomorrow = (strtotime($v['day']) + (60 * 60 * 48));
            if (($v['gtime'] < $tomorrow && $v['gtime'] > $yesTimeStamp)){
                $gameArray[$k] = $v;
            }
        }
        return $gameArray;
    }


    /**
     * @param $n N获取php格式化日期值
     * @return string 转换周几字符串
     */
    function week($n) {
        $week = "";
        switch($n) {
            case 1:$week="周一";break;
            case 2:$week="周二";break;
            case 3:$week="周三";break;
            case 4:$week="周四";break;
            case 5:$week="周五";break;
            case 6:$week="周六";break;
            case 7:$week="周日";break;
        }
        return $week;
    }


    /**
     * 获取mongo图片地址
     * @param $array
     * @return mixed
     */
    function getCountryTeamLogo($array) {
        $teamIdArr = [];
        foreach ($array as $k => $v) {
            $teamIdArr[] = $v['home_team_id'];
            $teamIdArr[] = $v['away_team_id'];
        }
        $team = M('gameTeam')->field('team_id,team_name,img_url')->where(['team_id' => ['in',$teamIdArr]])->select();
        foreach ($team as $k => $v) {
            $teamArr[$v['team_id']] = $v;
        }

        $sort = [];
        foreach ($array as $key => $value) {
            $sort[] = $value['gtime'];
            $home_logo = $teamArr[$value['home_team_id']]['img_url'];
            $away_logo = $teamArr[$value['away_team_id']]['img_url'];
            // $home_logo = mongo('fb_team')->field('images')->where(['team_id' => $value['home_team_id']])->select();
            // $away_logo = mongo('fb_team')->field('images')->where(['team_id' => $value['away_team_id']])->select();
            // $home_logo = $home_logo[0]['images'][0]['path'];
            // $away_logo = $away_logo[0]['images'][0]['path'];
            $array[$key]['homeTeamLogo']  = replaceTeamLogo($home_logo,2);
            $array[$key]['awayTeamLogo']  = replaceTeamLogo($away_logo,2);
        }
        array_multisort($sort,SORT_ASC,$array);
        return $array;
    }
}