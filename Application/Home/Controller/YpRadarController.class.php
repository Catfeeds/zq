<?php
/**
 * 首页
 * @author longs <2502737229@qq.com>
 * @since  2018-1-24
 */

use Think\Tool\Tool;

class YpRadarController extends CommonController
{
    public function _empty($path)
    {
        $ex_path = explode('/', $_SERVER['PATH_INFO']);
        if(!isset($ex_path[0])){
            parent::_empty();
        }
        //球王
        switch ($ex_path[0]) {
            case 'qiuwang':
                if ($ex_path[1] == 'getIntroFollow') {
                    //我的关注
                    A('Home/Intro')->getIntroFollow();
                    exit;
                }
                if ($ex_path[1] == 'history_tab') {
                    //历史曲线
                    $key = I('key');
                    A('Home/Intro')->history_tab($key);
                    exit;
                }
                if ($ex_path[1] == 'user_follow') {
                    //关注产品
                    $productId = I('productId');
                    A('Home/Intro')->user_follow($productId);
                    exit;
                }
                if (isset($ex_path[0]) && isset($ex_path[1])) {
                    $path = $ex_path[1];
                    //资讯内容页
                    A('Home/Intro')->intro_info($path);
                    exit;
                }
                //球王页面
                A('Home/Intro')->index();
                exit;
                break;
            case 'radar':
                //盈球雷达
                A('Home/WinDiskRadar')->index();
                exit;
                break;
            case 'mrjx':
                //每日极限
                A('Home/WinDiskRadar')->getDailyMax();
                exit;
                break;
            case 'lrjy':
                //冷热交易
                A('Home/WinDiskRadar')->getHotColdTrade();
                exit;
                break;
            case 'ypdk':
                //赢盘对抗
                A('Home/WinDiskRadar')->getCompete();
                exit;
                break;
            case 'jccy':
                //竞彩差异
                A('Home/WinDiskRadar')->getBettingDifference();
                exit;
                break;
            case 'download':
                //数据下载
                A('Home/WinDiskRadar')->download();
                exit;
                break;
            case 'fifa':
                if(empty($ex_path[1])) {
                    //杯赛直通车
                    A('Home/WorldCup')->index();
                }else if($ex_path[1] == 'schedule'){
                    A('Home/WorldCup')->schedule();
                }else if(count($ex_path) == 3){
                    $id = $ex_path[2];
                    //资讯内容页
                    A('Home/PublishIndex')->publishContent($id);
                }else{
                    A('Home/WorldCup')->teamInfo($ex_path[1]);
                }
                 exit;
                break;
            case 'recommend':
                A('Home/WorldCup')->recommend();
                exit;
                break;
            case 'getVote':
                A('Home/WorldCup')->getVote();
                exit;
                break;
        }

        //资讯
        if(isset($ex_path[0]) && isset($ex_path[2])){
            $id = $ex_path[2];
            //资讯内容页
            A('Home/PublishIndex')->publishContent($id);
        }else{
            //资讯栏目页
            switch ($ex_path[0]) {
                case 'dujia':   $class_id = 10; break;//独家
                case 'jingcai': $class_id = 54; break;//竞彩
                case 'beidan':  $class_id = 55; break;//北单
                case 'djmj':    $class_id = 62; break;//独家秘笈
                case 'news':    $class_id = 73; break;//专家说彩
                default:
                    parent::_empty();
                break;
            }
            A('Home/PublishIndex')->publishClass($class_id);
        }
    }

    public function index()
    {
        $from = I('from');
        if(isMobile() && $from != 'm'){
            redirect(U("/Sporttery@m").'?ad=1');
        }

        header('X-Frame-Options: deny');

        //手写位
        $this->assign('HomeNews', $this->getHomeNews());

        //广告位
        $this->assign("Adver", $this->getAdver());

        //赢盘雷达
        $this->assign("Radar", $this->getRadar());

        //热门赛事
        $this->assign("PopularEvents", $this->getPopularEvents());

        //情报资讯
        $this->assign("Intelligences", $this->getIntelligences());

        //大咖推荐
        $this->assign("DaKaList", $this->getDaKaList());

        //排行榜
        $this->assign('superList', $this->getCjUserList());

        //美女图片
        $this->assign("getGirlPic", $this->getGirlPic());

        //特约专家
        $this->assign('specialist', $this->specialist());
        //设置seo
        //查询直播列表
        $liveList = A('Home/LiveRoom')->offLinePage();
        $this->assign('liveList',$liveList);
        $classArr = getPublishClass(0);
        $this->setSeo($classArr[73]);

        //mqtt 配置
        $mqtt = C('Mqtt');
        $this->assign('mqttOpt', $mqtt);
        $this->assign('mqttUser', setMqttUser());

        $this->display();
    }


    /**
     * 获取手写位主页信息
     * @return bool|mixed|string
     */
    public function getHomeNews()
    {
        if (!$HomeNews = S('web_YpRadar_HomeNews')) {
            $list = D('Home')->getShouXie('news_zhuanjia');
            $HomeNews = array_chunk($list, 5);
            unset($list);
            S('web_YpRadar_HomeNews', json_encode($HomeNews), 60*60);
        }
        return $HomeNews;
    }


    /**
     * 获取后台专家说彩配置的广告位
     * @return bool|mixed|string
     */
    public function getAdver()
    {
        if (!$Adver = S('web_YpRadar_adver')) {
            $Adver['Carousel'] = Tool::getAdList(47, 5);
            $Adver['WideAdver'] = Tool::getAdList(66, 1);
            $Adver['RightAdver'] = Tool::getAdList(67, 1);
            S('web_YpRadar_adver', json_encode($Adver), 60);
        }
        return $Adver;
    }


    /**
     * 赢盘雷达
     */
    public function getRadar()
    {
        if ($BigData = S('bigDataIndex')) {
            $BigData = $this->dateFilter($BigData);
            S('web_YpRadar_Radar', json_encode($BigData), 30);
            return $BigData;
        }

        if (!$BigData = S('web_YpRadar_Radar')) {
            $nosign = C('nosignStr');
            $index = $this->InterWarning("https://www.qqty.com/Api510/BigData/index?nosign=".$nosign, null);
            $data = json_decode($index['data'], true);
            $BigData = $data['data']['bigData'];
            $BigData = $this->dateFilter($BigData);
            if (!$BigData['jiXian1']['unionName']){
                $BigData['jiXian1'] = $BigData['jiXian2'];
            }
            S('web_YpRadar_Radar', json_encode($BigData), 30);
        }
        return $BigData;
    }


    /**
     * 数据拼接
     * @param $BigData
     * @return mixed
     */
    function dateFilter(&$BigData) {
        foreach ($BigData as $k => &$v) {
            $v['unionName'] = explode(",", $v['unionName'], -1)[0];
            $v['homeTeamName'] = explode(",", $v['homeTeamName'], -1)[0];
            $v['awayTeamName'] = explode(",", $v['awayTeamName'], -1)[0];
        }
        return $BigData;
    }


    /**
     * 热门赛事
     * @return 0|array
     */
    public function getPopularEvents()
    {
        return array_slice(D("Home")->getLiveGame(), 0, 8);
    }

    /**
     * 情报资讯
     * @return mixed
     */
    public function getIntelligences()
    {
        if (!$Intelligences = S('web_YpRadar_Intelligences')) {
            //独家解盘
            $Intelligences['infoAnalyze'] = M('PublishList')
                ->where(['class_id' => 10, 'status' => 1, 'is_original' => 1])
                ->field("id,title,content,is_original, img, add_time, class_id")
                ->order("add_time desc")
                ->limit(12)->select();

            //北单推荐
            $Intelligences['europeInfo'] = M('PublishList')
                ->where(['class_id' => 55, 'status' => 1, 'is_original' => 1])
                ->field("id,title,content,is_original, img, add_time, class_id")
                ->order("add_time desc")
                ->limit(6)->select();

            //竞彩前瞻
            $Intelligences['asiaInfo'] = M('PublishList')
                ->where(['class_id' => 54, 'status' => 1, 'is_original' => 1])
                ->field("id,title,is_original, add_time, class_id, content")
                ->order("add_time desc")
                ->limit(8)->select();

            foreach ($Intelligences as $k => &$val) {
                if ($k != "europeInfo") {
                    foreach ($val as &$vo) {
                        $vo['content'] = trimall(msubstr(trim(preg_replace("/<.*?>|&nbsp;/is", "", htmlspecialchars_decode($vo['content']))), 0, 200, 'utf-8'));
                        $vo['img'] = newsImgReplace($vo);
                    }
                }
            }

            $classArr = getPublishClass(0);
            foreach ($Intelligences as &$val) {
                foreach ($val as &$v) {
                    $v['href'] = newsUrl($v['id'], $v['add_time'], $v['class_id'], $classArr);
                }
            }
            S('web_YpRadar_Intelligences', $Intelligences, 10 * 60);
        }
        return $Intelligences;
    }

    /**
     * 大咖用户
     * @return array|bool|mixed|string
     */
    public function getDaKaList()
    {
        if (!$killer = S('web_YpRadar_getDaKaList')) {
            //亚盘
            $ypKiller = M('rankingList r')
                ->join("LEFT JOIN qc_front_user f on f.id=r.user_id")
                ->field("r.*,f.nick_name,f.lv,f.lv_bk,f.head,f.is_robot,f.gamble_num as five_num")
                ->where("r.gameType=1 and r.dateType=1 and r.ranking <= 100")
                ->order('r.end_date desc')
                ->limit(100)
                ->select();

            //竞彩
            $jcKiller = M('rankBetting r')
                ->field("r.id,r.user_id,r.ranking,r.gameType,r.gameCount,r.win,r.transport,r.winrate,r.pointCount,f.head,f.lv,f.lv_bet,f.nick_name,f.bet_num as five_num")
                ->join('left join qc_front_user f on f.id = r.user_id')
                ->where("r.gameType=1 and r.dateType=1 and r.ranking <= 100")
                ->order('r.listDate desc')
                ->limit(100)
                ->select();

            foreach ($ypKiller as $k => $v) {
                foreach ($jcKiller as $kk => $vv) {
                    if ($v['user_id'] == $vv['user_id']) {
                        //两个榜都上了，取近5中几高的
                        if ($v['five_num'] > $vv['five_num']) {
                            unset($jcKiller[$kk]);
                        } else {
                            unset($ypKiller[$k]);
                        }
                    }
                    //近5中3以下不要
                    if ($vv['five_num'] < 3) {
                        unset($jcKiller[$kk]);
                    }
                }
                //近5中3以下不要
                if ($v['five_num'] < 3) {
                    unset($ypKiller[$k]);
                }
            }
            $killer = array_merge($ypKiller, $jcKiller);
            shuffle($killer);
            $killer = array_slice($killer, 0, 10);

            foreach ($killer as $k => $v) {
                //获取最新一条推荐
                $gamble = M('Gamble')->field('home_team_name,away_team_name,play_type')->where(['user_id' => $v['user_id']])->order('id desc')->find();
                $killer[$k]['home_team_name'] = switchName(0, $gamble['home_team_name']);
                $killer[$k]['away_team_name'] = switchName(0, $gamble['away_team_name']);
                $killer[$k]['play_type'] = $gamble['play_type'];
                $killer[$k]['face'] = frontUserFace($v['head']);
            }
            S('web_YpRadar_getDaKaList', $killer, 15 * 60);
        }
        return $killer;
    }

    /**
     * 排行榜
     * @return array|bool|mixed|string
     */
    public function getCjUserList()
    {
        if (!$killer = S('web_YpRadar_getCjUserList')) {
            $where['g.create_time'] = ['gt', strtotime('-1 day')];
            $ypKiller = M('FrontUser f')->join("RIGHT JOIN qc_gamble g on g.user_id = f.id")->field('f.id as user_id,f.head,f.nick_name,f.gamble_num as five_num')->where($where)->order('f.gamble_num desc')->limit(100)->group('f.id')->select();
            $jcKiller = M('FrontUser f')->join("RIGHT JOIN qc_gamble g on g.user_id = f.id")->field('f.id as user_id,f.head,f.nick_name,f.bet_num as five_num')->where($where)->order('bet_num desc')->limit(100)->group('f.id')->select();
            foreach ($ypKiller as $k => $v) {
                foreach ($jcKiller as $kk => $vv) {
                    if ($v['user_id'] == $vv['user_id']) {
                        //两个都有，取近5中几高的
                        if ($v['five_num'] > $vv['five_num']) {
                            unset($jcKiller[$kk]);
                        } else {
                            unset($ypKiller[$k]);
                        }
                    }
                    //近5中3以下不要
                    if ($vv['five_num'] < 3) {
                        unset($jcKiller[$kk]);
                    }
                }
                //近5中3以下不要
                if ($v['five_num'] < 3) {
                    unset($ypKiller[$k]);
                }
            }
            $killer = array_merge($ypKiller, $jcKiller);
            shuffle($killer);
            $killer = array_slice($killer, 0, 10);

            foreach ($killer as $k => $v) {
                $killer[$k]['face'] = frontUserFace($v['head']);
                //获取最新一条推荐
                $gamble = M('Gamble')->field('home_team_name,away_team_name,play_type')->where(['user_id' => $v['user_id']])->order('id desc')->find();
                $killer[$k]['home_team_name'] = switchName(0, $gamble['home_team_name']);
                $killer[$k]['away_team_name'] = switchName(0, $gamble['away_team_name']);
                $killer[$k]['play_type'] = $gamble['play_type'];
            }
            S('web_YpRadar_getCjUserList', $killer, 15 * 60);
        }
        return $killer;
    }

    /**
     *  特邀专家
     *  通过后台获取是否推荐
     */
    public function specialist()
    {
        //特邀专家，独家解盘才显示，优先展示当天有发布资讯的专家，一周以内的数据，发布时间倒序，每次12个，10:32，和足球赛程时间一样，半小时再统计一次
        if (!$expertList = S('web_YpRadar_expertList')) {
            $blockTime = getBlockTime(1, true);
            $where['p.user_id'] = ['gt', 0];
            $where['p.status'] = 1;
            $where['u.status'] = 1;
            $where['u.is_expert'] = 1;
            $expertList = (array)M('PublishList p')->field('u.id as user_id, u.nick_name, u.head as face, u.descript')
                ->join('left join qc_front_user as u on p.user_id = u.id')
                ->where($where)->group('p.user_id')->order('max(p.app_time) desc')->limit(8)->select();
            if ($expertList) {
                foreach ($expertList as $k => &$v) {
                    $v['face'] = frontUserFace($v['face']);
                    if (iosCheck()) {
                        $v['nick_name'] = str_replace(C('filterNickname'), C('replaceWord'), $v['nick_name']);
                        $v['descript'] = str_replace(C('filterNickname'), C('replaceWord'), $v['descript']);
                    }
                }
                S('web_YpRadar_expertList', $expertList, 30 * 60);
            }
        }
        return $expertList;
    }


    /**
     * 获取足球图片
     * @return bool|mixed|string
     */
    public function getGirlPic()
    {
        if (!$GirlPic = S('web_YpRadar_GirlPic')) {
            $girl_list = M("GalleryClass")->where(["pid" => 41])->select();
            foreach ($girl_list as $key => $value) {
                $ids[] = $value['id'];
            }
            $GirlPic = M('Gallery as g')
                ->field("g.id, g.img_array, g.add_time ,g.class_id, g.title, c.path")
                ->join("LEFT JOIN qc_gallery_class as c on c.id = g.class_id")
                ->where(['class_id' => ['in', $ids],'g.status'=>1])
                ->where("img_array is not null")
                ->order("add_time desc")
                ->limit(10)
                ->select();
            foreach ($GirlPic as &$value) {
                $value['cover'] = setImgThumb(json_decode($value['img_array'], true)[1],'240');
                $value['href'] = galleryUrl($value['id'], $value['path'],$value['add_time']);
                unset($value['img_array']);
            }
            S('web_YpRadar_GirlPic', $GirlPic, 10 * 60);
        }
        return $GirlPic;
    }


    /**
     * 忽略超时请求
     * @param $request 请求地址
     * @param $param 参数
     * @return array 数据
     */
    public function InterWarning($request, $param)
    {
        if ($param != null) {
            $data = httpPost($request, $param);
            if (!$data['data']) {
                return $this->InterWarning($request, $param);
            }
            return $data;
        } else {
            $data = httpPost($request);
            if (!$data['data']) {
                return $this->InterWarning($request, null);
            }
            return $data;
        }
    }

    //刷新清除首页redis缓存
    public function delCache()
    {
        S('web_YpRadar_adver', null);
        S('web_index_live_game', null);
        S('web_YpRadar_Radar', null);
        S('web_YpRadar_Intelligences', null);
        S('web_YpRadar_getDaKaList', null);
        S('web_YpRadar_getCjUserList', null);
        S('web_YpRadar_expertList', null);
        S('web_YpRadar_GirlPic', null);
        dump(S('web_YpRadar_adver'));
        dump(S('web_index_live_game'));
        dump(S('web_YpRadar_Radar'));
        dump(S('web_YpRadar_Intelligences'));
        dump(S('web_YpRadar_getDaKaList'));
        dump(S('web_YpRadar_getCjUserList'));
        dump(S('web_YpRadar_expertList'));
        dump(S('web_YpRadar_GirlPic'));
    }
}