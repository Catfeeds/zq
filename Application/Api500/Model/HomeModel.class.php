<?php
/**
 * 资讯首页模型类
 * @author huangjiezhen <418832673@qq.com> 2016.03.26
 */
use Think\Tool\Tool;

class HomeModel extends \Think\Model
{
    protected $show_date; //比赛日

    public function __construct()
    {
        $this->show_date = getShowDate();
    }

    //获取banner的分享信息
    public function getBannerShare($banner)
    {
        foreach ($banner as $k => $v) {
            $banner[$k]['shareTitle'] = '';
            $banner[$k]['shareImg'] = '';

            if ($v['module'] == 1) {
                $publishInfo = M('PublishList')->field(['title', 'img'])->where(['id' => $v['url']])->find();
                $banner[$k]['shareTitle'] = (string)$publishInfo['title'];
                $banner[$k]['shareImg']   = $publishInfo['img'] ? C('IMG_SERVER') . $publishInfo['img'] : '';
            }

            if ($v['module'] == 2) {
                $galleryInfo = M('Gallery')->field(['title', 'img_array'])->where(['id' => $v['url']])->find();
                $banner[$k]['shareTitle'] = (string)$galleryInfo['title'];

                if ($galleryInfo['img_array']) {
                    $imgArr = json_decode($galleryInfo['img_array'], true);

                    foreach ($imgArr as $v) {
                        if ($v) {
                            $banner[$k]['shareImg'] = C('IMG_SERVER') . $v;
                            break;
                        }

                    }
                }
            }

            //http =》https
//            $banner[$k]['url'] = http_to_https($v['url']);
        }

        return $banner;
    }

    /**获取新闻资讯列表
     * @param $page
     * @param $pageNum
     * @param null $channel_id
     * @param int $update_time
     * @return mixed
     */
    public function getArticleList($page, $pageNum, $channel_id = null, $update_time = 0, $user_id=0)
    {
        //要读取的资讯板块
        $channel_ids = explode(',',trim($channel_id,','));

        $field = ['id', 'class_id', 'title', 'remark', 'label', 'img', 'content', 'source', 'click_number', 'app_recommend'];
        if($user_id)
            $where['user_id'] = $user_id;

        $where['status'] = 1;
        $where['class_id'] = ['IN', $channel_ids];
        if(empty($user_id))
            $where['app_recommend'] = 0;//不查APP置顶

        //独家解盘增加APP发布时间，10:32，和足球赛程时间一样
        if($channel_id == 10){
            $blockTime = getBlockTime(1, true);
            $where[] = ['app_time' => ['lt', $blockTime['endTime']]];
            $field[] = 'app_time as update_time';
            $order = 'app_time desc, update_time desc';
        }else{
            $field[] = 'update_time';
            $order = 'update_time desc';
        }

        if ($update_time) {//如果传最小id，则向下取
            $where['update_time'] = ['lt', (int)$update_time];
            $articleList = M('PublishList')->field($field)
                ->where($where)
                ->order($order)
                ->limit($pageNum)->select();
        } else {
            $articleList = M('PublishList')->field($field)
                ->where($where)
                ->order($order)
                ->page($page . ',' . $pageNum)->select();
        }

        //头条，独家增加置顶；用户id为0
        if(empty($user_id) && $page == 1 && (in_array(10, $channel_ids) || in_array(13, $channel_ids))){
            $topList = M('PublishList')->field($field)
                        ->where(['class_id' => ['IN', $channel_ids], 'status' => 1, 'app_recommend' => 1])
                        ->order('update_time desc')
                        ->limit(3)->select();

            if($topList)
                $articleList = array_merge($topList, $articleList);
        }

        return $this->getArticleImg($articleList ?:[]);
    }

    //获取资讯赛事列表情报
    public function getGameArticleList($game_id,$game_type=1,$from=1){
        $cacheKey = 'Video:articleList' . MODULE_NAME . $game_id . $game_type;
        if(!$responseList = S($cacheKey))
        {
            $akey = $game_type == 1 ? 'game_id' : 'gamebk_id';
            $articleList = (array)M('PublishList')->field(['id', 'class_id', 'source','title', 'remark', 'click_number', 'img', 'content', 'add_time'])
                ->where([$akey => $game_id, 'status' => 1])
                ->order('is_recommend desc, is_channel_push desc, add_time desc')
                ->limit(20)
                ->select();

            $videoList = (array)M('Highlights')->field(['id', 'title', 'remark', 'click_num as click_number', 'img', 'app_url', 'app_ischain', 'is_prospect', 'add_time','app_isbrowser'])
                ->where(['game_id' => $game_id, 'game_type' => $game_type, 'app_url' => ['neq', ''], 'status' => 1])
                ->order('is_recommend desc, add_time asc')
                ->limit(20)
                ->select();

            $list = array_merge($articleList, $videoList);

            foreach($list as $k=> $v){
                $addTimeSort[] = $v['add_time'];
                unset($list[$k]['add_time']);
            }

            //排序
            array_multisort($addTimeSort, SORT_DESC, $list);
            $responseList = array_slice($list, 0 ,10);

           // if ($responseList)
           //     S($cacheKey, $responseList, 60 * 2);
        }

        $lists = D('Home')->getArticleImg($responseList, false);
        //获取赛前情报
        if($game_type == 1){
            $appService = new \Home\Services\AppfbService();
            $preMatchinfo = $appService->getPreMatchinfo($game_id,$from);
        }
        return ['articleList' => $lists ?:[],'preInfo' => $preMatchinfo?:''];
    }

    //获取资讯里面的图片
    public function getArticleImg($articleList,  $comment = true)
    {
        $publishClass = M('PublishClass')->where("status=1")->getField('id, name');
        foreach ($articleList as $k => $v) {
            //处理remark
            $articleList[$k]['remark'] = $v['remark'] ?: str_replace(',', ' ', $v['label']);
            unset($articleList[$k]['label']);

            $imgs = Tool::getTextImgUrl(htmlspecialchars_decode($v['content']), 0);

            foreach ($imgs as $kkk => $vvv) {
                if (strtoupper(substr(strrchr($vvv, '.'), 1)) == 'GIF')
                    unset($imgs[$kkk]);
            }

            if (count($imgs) >= 3) {
                $imgs = array_slice($imgs, 0, 3);
                foreach ($imgs as $kk => $vv) {
                    if (strpos($vv, SITE_URL) === false)
                        $imgs[$kk] =  http_to_https($vv);
                }

                $articleList[$k]['img'] = $imgs;
            } else {
                if ($articleList[$k]['img']) {
                    $articleList[$k]['img'] = [ C('IMG_SERVER') . $articleList[$k]['img']];
                } else {
                    if (count($imgs) >= 1) {
                        if (strpos($imgs[0], SITE_URL) === false)
                            $articleList[$k]['img'] = [http_to_https($imgs[0])];
                        else
                            $articleList[$k]['img'] = [$imgs[0]];
                    } else {
                        $articleList[$k]['img'] = [];
                    }
                }

                // $articleList[$k]['img'] = $articleList[$k]['img'] ? [SITE_URL.C('IMG_SERVER').$articleList[$k]['img']] :
                //                             count($imgs) >= 1 ? $imgs[0] = [SITE_URL.C('IMG_SERVER').$imgs[0]] : [];
            }

            //按需获取评论数
            if($comment)
                $articleList[$k]['commentNum'] = M('Comment')->where(['publish_id' => $v['id']])->count();

            //增加资讯点击量的默认值
            $articleList[$k]['click_number'] = addClickConfig(1, $v['class_id'], $v['click_number'], $v['id']);

            //返回game_type
            if(in_array($v['class_id'], C('gameTypeClass'))){
                $articleList[$k]['game_type'] = '2';
            }else{
                $articleList[$k]['game_type'] = '1';
            }

            //过滤图片
            if(in_array($v['class_id'], C('classId'))){
                $img = http_to_https('http://www.qqty.com/Public/Home/images/common/loading.png');
                $articleList[$k]['img'] = [$img];
            }

            //来源
            $articleList[$k]['source'] = $v['source'].'/'.$publishClass[$v['class_id']];

            unset($articleList[$k]['content']);
        }
        return $articleList;
    }

    // 热门竞猜
    public function getMatch()
    {
        $blockTime = getBlockTime(1, $gamble = true); //足球竞猜竞猜时间区间
        $num = 6; //获取的条数

        $sql = "
            SELECT
                gf.union_name,
                u.union_color,
                gf.game_id,
                gf.game_state,
                gf.home_team_name,
                gf.home_team_id,
                gf.score,
                gf.away_team_name,
                gf.away_team_id,
                gf.gtime,
                count(*) gambleCount
            FROM
                qc_gamble gm
            LEFT JOIN qc_game_fbinfo gf ON gf.game_id = gm.game_id
            LEFT JOIN qc_union u ON u.union_id = gf.union_id
            WHERE
                gm.create_time BETWEEN {$blockTime['beginTime']} AND {$blockTime['endTime']}
            GROUP BY
                gm.game_id
            ORDER BY
                gambleCount DESC
            LIMIT {$num}
        ";

        $match = M()->query($sql);
        $sort = [];

        foreach ($match as $k => $v) {
            $match[$k]['union_name'] = explode(',', $v['union_name']);
            $match[$k]['home_team_name'] = explode(',', $v['home_team_name']) ?: [];
            $match[$k]['away_team_name'] = explode(',', $v['away_team_name']) ?: [];
            $match[$k]['homeTeamLogo'] = getLogoTeam($v['home_team_id'], 1);
            $match[$k]['awayTeamLogo'] = getLogoTeam($v['away_team_id'], 2);

            //分解时间
            $match[$k]['game_date'] = date('Ymd', $v['gtime']);
            $match[$k]['game_time'] = date('H:i', $v['gtime']);

            $sort[] = $v['gtime'];

            unset($match[$k]['home_team_id']);
            unset($match[$k]['away_team_id']);
            unset($match[$k]['gtime']);
        }

        //获取球队logo
//        setTeamLogo($match);

        array_multisort($sort, SORT_DESC, $match);
        return $match;
    }

    //直播和集锦
    public function getVideo()
    {
        $blockTime = getBlockTime(1); //足球赛程日期时间区间

        $field = ['f.game_id', 'f.game_date', 'f.game_time', 'f.game_state', 'f.union_name', 'f.home_team_id', 'f.home_team_name', 'f.away_team_id', 'f.away_team_name', 'f.score', 'qu.union_color'];

        $video = M('GameFbinfo f')
            ->join("LEFT JOIN __UNION__ AS qu ON f.union_id = qu.union_id")
            ->field($field)
            ->where(['f.game_state' => ['in', [0, 1, 2, 3, 4]], 'f.gtime' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]], 'f.is_video' => 1, 'f.is_recommend' => 1])
            ->order('rand()')->limit(2)->select();

        $videoNum = 2 - count($video);

        if ($videoNum > 0) {
            $gameId = M('Highlights h')
                ->where(['h.game_type' => 1, 'h.is_recommend' => ['neq', 0], 'h.status' => 1, 'g.game_state' => ['in', [0, 1, 2, 3, 4, -1]]])
                ->field('h.game_id')
                ->order('h.add_time desc')
                ->join('LEFT JOIN __GAME_FBINFO__ g ON g.game_id = h.game_id')
                ->limit($videoNum)->group('h.game_id')
                ->select();

            $arrId = [];
            foreach ($gameId as $k => $v) {
                if ($v['game_id'])
                    $arrId[] = $v['game_id'];
            }

            $needVideo = [];
            if ($arrId) {
                $needVideo = M('GameFbinfo f')->join("LEFT JOIN qc_union AS qu ON f.union_id = qu.union_id")->field($field)->where(['f.game_id' => ['in', $arrId]])->select();
            }

            $video = array_merge((array)$video, $needVideo);
        }

        foreach ($video as $k => $v) {
            $video[$k]['union_name'] = explode(',', $video[$k]['union_name']);
            $video[$k]['home_team_name'] = explode(',', $video[$k]['home_team_name']);
            $video[$k]['away_team_name'] = explode(',', $video[$k]['away_team_name']);
//            $video[$k]['homeTeamLogo'] = getLogoTeam($v['home_team_id'], 1);
//            $video[$k]['awayTeamLogo'] = getLogoTeam($v['away_team_id'], 2);

            if ($video[$k]['score'] == '')
                $video[$k]['score'] = '0-0';
        }

        //获取球队logo
        setTeamLogo($video);

        return $video;
    }

    //获取回复的评论
    public function getSubComment($pid = 0, $userToken = '')
    {
        $comment = M('Comment c')->field(['c.id', 'c.pid', 'c.user_id', 'u.nick_name', 'u.head', 'c.content', 'c.like_num', 'c.like_user', 'c.create_time'])
            ->where(['pid' => $pid])
            ->join('LEFT JOIN __FRONT_USER__ u ON u.id = c.user_id')
            ->order('c.create_time desc')
            ->select();

        if (!$comment)
            return;

        foreach ($comment as $k => $v) {
            $comment[$k]['face'] = frontUserFace($v['head']);
            $comment[$k]['is_liked'] = $userToken && in_array($userToken['userid'], explode(',', $v['like_user'])) ? 1 : 0;
            $comment[$k]['subComment'] = $this->getSubComment($v['id'], $userToken);

            unset($comment[$k]['like_user']);
        }

        return $comment;
    }

    //获取首页每日精选（红人榜），足球分别取亚盘和竞彩；篮球只有亚盘
    public function getHotList($game_type)
    {
        //周榜前500名取命中率高前24名；篮球取48名；
        $paramType = 1;//取周榜
        $rankDate = 0;
        if($game_type == 1){//足球
            //亚盘
            $hotList1 = $this->getPlayTypeData(1, $paramType, $rankDate, $game_type);

            //竞彩
            $hotList2 = $this->getPlayTypeData(2, $paramType, $rankDate, $game_type);

            //转成三个小数组
            $hot = $a = $b = array();

            //分成三个数组
            $a = array_chunk($hotList1, 4);
            $b = array_chunk($hotList2, 4);

            for($i=0; $i< 6; $i++){
                if(empty($a[$i])) $a[$i] = array();
                if(empty($b[$i])) $b[$i] = array();
                $hot[$i] = array_merge($a[$i], $b[$i]);
            }
        }else{//篮球
            //亚盘
            $hotList1 = $this->getPlayTypeData(1, $paramType, $rankDate, $game_type);
            $hot = array_chunk($hotList1, 8);//6组8个
        }

        //过滤内容是空的数组
        foreach($hot as $k => $v){
            if(empty($v))
                unset($hot[$k]);
        }

        return $hot;
    }

    public function getCommentList($article_id, $userToken, $showLevel = '')
    {
        $cModel = M('Comment c');
        $comment = $cModel->field(['c.id', 'c.user_id', 'u.nick_name', 'u.head face', 'c.filter_content content ', 'c.like_num', 'c.create_time', 'c.status'])
            ->where(['c.publish_id' => $article_id, 'c.pid' => 0])
            ->join('LEFT JOIN __FRONT_USER__ u ON u.id = c.user_id')
            ->order('c.like_num desc,c.create_time desc')
            ->limit(5)
            ->select();

        if ($showLevel == 1)
            return $comment;

        foreach ($comment as $k => $v)
        {
            $comment[$k]['face'] = frontUserFace($v['face']);
            $comment[$k]['is_liked'] = $userToken && in_array($userToken['userid'], explode(',', $v['like_user'])) ? 1 : 0;

            $subComment = $cModel->field(['id', 'user_id', 'by_user', 'filter_content content', 'status'])
                ->where(['publish_id' => $article_id, 'top_id' => $v['id']])
                ->order('create_time desc')
                ->select();

            foreach ($subComment as $kk => $vv) {
                $subComment[$kk]['fromUser'] = M('FrontUser')->where(['id' => $vv['user_id']])->getField('nick_name');
                $subComment[$kk]['toUser'] = M('FrontUser')->where(['id' => $vv['by_user']])->getField('nick_name');
            }

            $comment[$k]['subComment'] = $subComment;
            unset($comment[$k]['like_user']);
        }

        return $comment;
    }

    /**
     *  大咖广场模型
     */
    public function getMasterGamble($userToken, $playType, $sortType, $lvType, $priceType, $unionType, $pageSize, $pageNum, $timestamp, $game_type=1, $platform=0){
        if(iosCheck()){
            $result = $this->getList($playType, 0, $lvType, $priceType, $unionType, $userToken, 1, $pageSize, $pageNum, $game_type, $platform);
        }else{
            switch ($sortType)
            {
                case 1: /*周胜率*/   $result = $this->getList($playType, $sortType, $lvType, $priceType, $unionType, $userToken, 1, $pageSize, $pageNum, $game_type, $platform);      break;
                case 2: /*高命中*/   $result = $this->rankGambleList($playType, $sortType, $lvType, $priceType, $unionType, 1, 100, $userToken, $pageSize, $pageNum, $game_type, $platform);      break;
                case 3: /*连胜多*/   $result = $this->rankGambleList($playType, $sortType, $lvType, $priceType, $unionType, 2, 200, $userToken, $pageSize, $pageNum, $game_type, $platform);      break;
                case 4: /*人气旺*/   $result = $this->rankGambleList($playType, $sortType, $lvType, $priceType, $unionType, 1, 100, $userToken, $pageSize, $pageNum, $game_type, $platform);      break;
                case 5: /*我关注的*/ $result = $this->myFollowGamble($playType, $lvType, $priceType, $unionType, $userToken, $timestamp, $pageSize, $pageNum, $game_type, $platform);      break;
                default:/*综合*/     $result = $this->getList($playType, 0, $lvType, $priceType, $unionType, $userToken, 1, $pageSize, $pageNum, $game_type, $platform);
            }
        }

        return $result;
    }

    /**
     * 获取排行榜的数据
     * @param $sortType string 排序类型
     * @param $dateType int 榜类型 1：周， 2：月， 3：季
     * @param $num int 查询数量
     * @param $userToken string 用户口令
     * @param $pageSize int
     * @param $pageNum int
     * @return array
     */
    public function rankGambleList($playType, $sortType, $lvType, $priceType, $unionType, $dateType, $num, $userToken, $pageSize, $pageNum, $game_type=1, $platform=0){
        //把排好序的人做缓存，只要不结算就不会改变
        if(!$userArr = S('userArr'.$game_type.$playType.$sortType.MODULE_NAME)) {
            if($playType == 1){//亚盘
                $table = M('RankingList');
            }else if($playType == 2){//竞彩
                $table = M('RankBetting');
            }

            $rankDate = getRankDate($dateType);//获取上个周期的日期
            $countNum = $table->where(['dateType' => $dateType, 'gameType' => $game_type, 'end_date' => $rankDate[1]])->count();

            if (!$countNum) {
                $rankDate = getTopRankDate($dateType);//获取上个周期的数据
            }

            if($playType == 1){
                $where['end_date'] = $rankDate[1];
                $where1['g.play_type'] = ['in', [-1,1]];
            }else if($playType == 2){
                $where['listDate'] = $rankDate[1];
                $where1['g.play_type'] = ['in', [-2,2]];
            }
            $where['dateType'] = $dateType;
            $where['gameType'] = $game_type;
            $where['f.status'] = 1;
            $arr     = $table->alias('r')->join("LEFT JOIN qc_front_user f on f.id = r.user_id")->where($where)->order('ranking ASC')->limit($num)->getField('user_id, winrate', true);
            $userArr = array_keys($arr);
            $rateArr = array();

            foreach ($userArr as $k => $v) {
                if ($sortType == 2) {//高命中
                    $tenGamble     = D('GambleHall')->getTenGamble($v, $game_type, $playType);
                    $tenGambleRate = countTenGambleRate($tenGamble, $playType);//近十场的胜率;
                    //要10中6的或以上
                    if ($tenGambleRate < 60) {
                        unset($userArr[$k], $arr[$v]);
                        continue;
                    }
                    $rateArr[$v] = $tenGambleRate;
                } else if ($sortType == 3) {//连胜多
                    $winnig = D('GambleHall')->getWinning($v, $game_type, 0, $playType, 30); //连胜记录

                    //连胜2以上
                    if ($winnig['curr_victs'] < 2) {
                        unset($userArr[$k], $arr[$v]);
                        continue;
                    }
                    $rateArr[$v] = $winnig['curr_victs'];//连胜场数
                } else if ($sortType == 4) {//人气旺
                    $where1['q.cover_id']  = $v;
                    $where1['q.game_type'] = $game_type;
                    $where1['q.coin']      = ['gt', 0];
                    $where1['q.log_time']  = ['between', [strtotime(date('Y-m-d 00:00:00', strtotime('-3 day'))), strtotime(date('Y-m-d 23:59:59', strtotime('-1 day')))]];

                    $game_table = $game_type == 1 ? 'qc_gamble' : 'qc_gamblebk';
                    $rateNum = M('QuizLog q')->join(' LEFT JOIN '.$game_table.' AS g ON q.gamble_id = g.id')->where($where1)->count();

                    //销量为0去掉
                    if($rateNum < 1){
                        unset($userArr[$k], $arr[$v]);
                        continue;
                    }
                    $rateArr[$v] = $rateNum;
                }
            }

            //等级
            $lv = $game_type == 2 ? 'lv_bk as lv' : ($playType == 2 ? 'lv_bet as lv' : 'lv');
            $lvSort = M('FrontUser')->where(['id' => ['in', $userArr]])->getField($lv, true);

            //排序
            array_multisort(array_values($rateArr), SORT_ASC, array_values($arr), SORT_ASC, $lvSort, SORT_ASC, $userArr);
            S('userArr'.$game_type.$playType.$sortType.MODULE_NAME, $userArr, 60*5);
            S('rateArr'.$game_type.$playType.$sortType.MODULE_NAME, $rateArr, 60*5);
        }

        if(empty($userArr)){
            return array();
        }

        $rateArr = $rateArr ?: S('rateArr'.$game_type.$playType.$sortType.MODULE_NAME);
        $blockTime = getBlockTime($game_type, $gamble = true);//获取竞猜分割日期的区间时间

        //竞猜赛程期间内，且未出结果的
        if($playType == 1){//亚盘
            $where2['g.play_type'] = ['in', ($game_type == 1) ? [1,-1] : [1,2,-1,-2]];//篮球再细分
        }else{//足球竞彩
            $where2['g.play_type'] = ['in',[2,-2]];
        }
        $where2['g.create_time'] = ['between', [$blockTime['beginTime'], $blockTime['endTime']]];
        $where2['g.result']      = ['in', ['0', '1', '0.5', '2' ,'-0.5' ,'-1']];//0:未出结果，1：赢，0.5:赢半，2：平，-1：输，-0.5：输半）
        $where2['g.user_id']     = ['in', $userArr];
        $where2['gf.game_state'] = $game_type == 1 ? ['in', [-1,0,1,2,3,4]] : ['in', [-1,0,1,2,3,4,5,6,7]];

        if(is_numeric($lvType) && in_array($lvType, array(1,2,3,4,5,6,7,8,9))){
            if($playType == 1){
                if($game_type == 1){
                    $where2['u.lv'] = $lvType;
                }else{
                    $where2['u.lv_bk'] = $lvType;
                }
            }else{
                $where2['u.lv_bet'] = $lvType;
            }
        }

        if($unionType){
            $where2['g.union_id'] = ['in', explode(',', $unionType)];
        }

        //当天竞猜最新id
        $model = $game_type == 1 ? M('Gamble g'): M('Gamblebk g');
        $info  = $game_type == 1 ? 'qc_game_fbinfo' : 'qc_game_bkinfo';
        $idList = $model->join(' LEFT JOIN '.$info.' AS gf ON g.game_id = gf.game_id ')
                  ->join(' LEFT JOIN qc_front_user AS u ON g.user_id = u.id ')
                  ->where($where2)->group('g.user_id')->getField('max(g.id)', true);

        if(empty($idList)){
            return array();
        }

        $where3['g.id'] = ['in', $idList];

        $order = '';
        if($priceType){
            $sort   = ($priceType == 1) ? 'DESC' : 'ASC';
            $order .= ' g.tradeCoin '.$sort.', ';
        }

        $order .= ' field(g.user_id, '.implode(',', $userArr).') DESC, qu.is_sub ASC, sortTime ASC ';

        $fields = " CONCAT(g.game_date, g.game_time) as sortTime, g.id as gamble_id, g.user_id, g.union_name, g.game_date, g.game_time, g.home_team_name, g.away_team_name,
                  g.play_type, g.chose_side, g.tradeCoin, g.handcp, g.odds, g.result, g.`desc`, (g.quiz_number + g.extra_number) as quiz_number, g.create_time, IF(is_voice=1, voice, '') as voice, g.voice_time, u.nick_name, u.head as face ";

        if($playType == 1){
            $lv_string = $game_type == 2 ? 'u.lv_bk as lv' : 'u.lv';
            $union     = $game_type == 1 ? 'qc_union' : 'qc_bk_union';
            $fields   .= ' ,'.$lv_string.', qu.union_color ';
            $res = $model->field($fields)
                    ->join(' LEFT JOIN qc_front_user AS u ON g.user_id = u.id ')
                    ->join(' LEFT JOIN '.$union.' AS qu ON g.union_id = qu.union_id ')
                    ->where($where3)->group('gamble_id')->order($order)
                    ->limit($pageSize, $pageNum)->select();
        }else{
            $fields .= ' ,u.lv_bet as lv, qu.union_color, b.bet_code ';
            $res = M('Gamble g')->field($fields)
                ->join(' LEFT JOIN qc_fb_betodds AS b ON g.game_id = b.game_id ')
                ->join(' LEFT JOIN qc_front_user AS u ON g.user_id = u.id ')
                ->join(' LEFT JOIN qc_union AS qu ON g.union_id = qu.union_id ')
                ->where($where3)->group('gamble_id')->order($order)
                ->limit($pageSize, $pageNum)->select();
        }

        if(!empty($res)) {
            foreach ($res as $k => $v) {
                if ($sortType == 2) {//高命中
                    $res[$k]['tenGambleRate'] = $rateArr[$v['user_id']]/10;
                    $winnig = D('GambleHall')->getWinning($v['user_id'], $game_type, 0, $playType, 30); //连胜记录
                    $res[$k]['curr_victs'] = $winnig['curr_victs'];//连胜场数
                    unset($winnig);
                } else if ($sortType == 3) {//连胜多
                    if(!$rateArr[$v['user_id']]){
                        $winnig = D('GambleHall')->getWinning($v['user_id'], $game_type, 0, $playType, 30); //连胜记录
                        $rateArr[$v['user_id']] = (string)$winnig['curr_victs'];//连胜场数
                    }
                    $res[$k]['curr_victs'] = $rateArr[$v['user_id']];
                    $tenGamble = D('GambleHall')->getTenGamble($v['user_id'], $game_type, $playType);
                    $res[$k]['tenGambleRate'] = countTenGambleRate($tenGamble, $playType)/10;//近十场的胜率;
                    unset($tenGamble);
                } else {
                    $winnig = D('GambleHall')->getWinning($v['user_id'], $game_type, 0, $playType, 30); //连胜记录
                    $res[$k]['curr_victs'] = $winnig['curr_victs'];//连胜场数
                    $res[$k]['tenGambleRate'] = $winnig['tenGambleRate'];//近十场的胜率;
                    unset($winnig);
                }

                $res[$k]['face'] = frontUserFace($v['face']);
                $res[$k]['weekPercnet'] = $arr[$v['user_id']] ?: (string)D('GambleHall')->CountWinrate($v['user_id'], $game_type, 1, false, false, 0, $playType);//周榜

                $res[$k]['union_name']     = explode(',', $v['union_name']);
                $res[$k]['home_team_name'] = explode(',', $v['home_team_name']);
                $res[$k]['away_team_name'] = explode(',', $v['away_team_name']);
                $res[$k]['desc']           = (string)$v['desc'];
                $res[$k]['voice']          = $v['voice'] ? Tool::imagesReplace($v['voice']) : '';
                $res[$k]['game_type']      = $game_type;
                $res[$k]['quiz_number']    = D('Common')->getQuizNumber($v['quiz_number']);

                //如已经登陆,判断当前用户是否有购买当前信息
                if ($userToken) {
                    $res[$k]['is_trade'] = D('Common')->getTradeLog($v['gamble_id'], $userToken['userid'], $game_type);//是否已查看购买过
                } else {//无登录则全部没有购买
                    $res[$k]['is_trade'] = '0';
                }
                unset($res[$k]['sortTime']);
            }
        }

        return $res;
    }

    /**
     * 数据列表：等级，周胜率，价格
     * @param $sortType string 排序类型
     * @param $userToken string 用户口令
     * @param $dateType int 榜类型 1：周， 2：月， 3：季
     * @param $pageSize
     * @param $pageNum
     * @return array
     */
    public function getList($playType, $sortType, $lvType, $priceType, $unionType, $userToken, $dateType=1, $pageSize, $pageNum, $game_type=1, $platform=0){
        $iosCheck = 0;

        $blockTime = getBlockTime($game_type, $gamble = true);//获取竞猜分割日期的区间时间
        if ($playType == 1) {
            //亚盘
            $rankTable = M('rankingList');
            $sort_field = 'end_date';
            $where['g.play_type'] = ['in', ($game_type == 1) ? [1,-1] : [1,2,-1,-2]];//篮球再细分
        } else if ($playType == 2) {
            //竞彩
            $rankTable = M('rankBetting');
            $sort_field = 'listDate';
            $where['g.play_type'] = ['in', [-2, 2]];
        }

        if ($unionType) {
            $where['g.union_id'] = ['in', explode(',', $unionType)];
        }

        //竞猜赛程期间内，且未出结果的
        if($iosCheck) $where['g.tradeCoin'] = 0;
        $where['g.create_time'] = ['between', [$blockTime['beginTime'], $blockTime['endTime']]];
        $where['g.result']      = ['in', ['0', '1', '0.5', '2' ,'-0.5' ,'-1']];//0:未出结果，1：赢，0.5:赢半，2：平，-1：输，-0.5：输半）

        if (is_numeric($lvType) && in_array($lvType, array(1, 2, 3, 4, 5, 6, 7, 8, 9))) {
            if ($playType == 1) {
                if($game_type == 1){
                    $where['u.lv'] = $lvType;
                }else{
                    $where['u.lv_bk'] = $lvType;
                }
            } else {
                $where['u.lv_bet'] = $lvType;
            }
        }

        $model = $game_type == 1 ? M('Gamble g'): M('Gamblebk g');
        $table = $playType  == 1 ? 'qc_ranking_list': 'qc_rank_betting';

        if(!$rankUser = S('rankUser:'.$dateType.$playType.$game_type)){
            //排行榜前100名
            $rankWhere['dateType']    = $dateType;
            $rankWhere['gameType']    = $game_type;
            $rankDate = $rankTable->where($rankWhere)->order("$sort_field desc")->limit(1)->getField($sort_field);
            $rankWhere[$sort_field]   = $rankDate;
            $rankWhere['f.status']    = 1;
            $limit    = ($iosCheck) ? 300 : 100;
            $rankUser = $rankTable->alias('r')->join("LEFT JOIN qc_front_user f on f.id = r.user_id")->where($rankWhere)->order("ranking asc")->limit($limit)->getField('ranking,user_id',true);
            S('rankUser:'.$dateType.$playType.$game_type,$rankUser,600);
        }

        $where['g.user_id'] = ['in',array_values($rankUser)];
        //当天竞猜最新id
        $idList = $model
            ->join(' LEFT JOIN qc_front_user AS u ON g.user_id = u.id ')
            ->where($where)->group('g.user_id')->order('g.id DESC')->getField('max(g.id)', true);

        if (empty($idList))
            return array();

        $order = '';
        if($priceType){
            $sort = ($priceType == 1) ? 'DESC' : 'ASC';
            $order .= ' g.tradeCoin '.$sort.', ';
        }

        $lv = $game_type == 2 ? 'u.lv_bk' : ($playType == 2 ? 'u.lv_bet' : 'u.lv');
        switch ($sortType)
        {
            case 1:   $order .= ' l.winrate DESC, g.game_date ASC, g.game_time ASC';  break;
            default:  $order .= ' g.create_time DESC ';
        }

        $order .= ', qu.is_sub ASC ';
        
        $union = $game_type == 1 ? 'qc_union' : 'qc_bk_union';
        $fields = "g.id as gamble_id, g.user_id, g.union_name, g.game_date, g.game_time, g.home_team_name, g.away_team_name, g.play_type, g.chose_side, g.tradeCoin, g.handcp, g.odds, g.result, g.`desc`, (g.quiz_number + g.extra_number) as quiz_number, g.create_time, IF(is_voice=1, voice, '') as voice, g.voice_time, u.nick_name, u.head as face, u.fb_ten_gamble, u.fb_ten_bet, u.fb_gamble_win,u.fb_bet_win,u.bk_ten_gamble ,u.bk_gamble_win";
        if($playType == 1){
            $fields .= ' , '.$lv.' as lv, qu.union_color';
            $res = $model->field($fields)
                    ->join(' LEFT JOIN '.$table.' AS l ON l.user_id = g.user_id ')
                    ->join(' LEFT JOIN qc_front_user AS u ON g.user_id = u.id ')
                    ->join(' LEFT JOIN '.$union.' AS qu ON g.union_id = qu.union_id ')
                    ->where(['g.id'=>['in',$idList]])->order($order)->group('gamble_id')
                    ->limit($pageSize, $pageNum)->select();
        }else{//竞彩需要赛事序号
            $fields .= ' ,u.lv_bet as lv, qu.union_color, b.bet_code ';
            $res = M('Gamble g')->field($fields)
                    ->join(' LEFT JOIN '.$table.' AS l ON l.user_id = g.user_id ')
                    ->join(' LEFT JOIN qc_fb_betodds AS b ON g.game_id = b.game_id ')
                    ->join(' LEFT JOIN qc_front_user AS u ON g.user_id = u.id ')
                    ->join(' LEFT JOIN qc_union AS qu ON g.union_id = qu.union_id ')
                    ->where(['g.id'=>['in',$idList]])->order($order)->group('gamble_id')
                    ->limit($pageSize, $pageNum)->select();
        }
        if(!empty($res)) {
            //对应排名
            foreach ($res as $k => $v) {
                foreach ($rankUser as $kk => $vv) {
                    if($v['user_id'] == $vv){
                        $res[$k]['ranking'] = $kk;
                    }
                }
            } 

            foreach ($res as $k => $v) {
                $res[$k]['face'] = frontUserFace($v['face']);
                $res[$k]['weekPercnet'] = (string)D('GambleHall')->CountWinrate($v['user_id'], $game_type, 1, false, false, 0, $playType);//周胜率
                //近十场的胜率;
                if($game_type == 1){
                    $res[$k]['tenGambleRate'] = $playType == 1 ? $v['fb_ten_gamble'] : $v['fb_ten_bet'];
                    $res[$k]['curr_victs']    = $playType == 1 ? $v['fb_gamble_win'] : $v['fb_bet_win'];
                }else{
                    $res[$k]['tenGambleRate'] = $v['bk_ten_gamble'];
                    $res[$k]['curr_victs']    = $v['bk_gamble_win'];
                }

                $res[$k]['union_name']     = explode(',', $v['union_name']);
                $res[$k]['home_team_name'] = explode(',', $v['home_team_name']);
                $res[$k]['away_team_name'] = explode(',', $v['away_team_name']);
                $res[$k]['desc']           = (string)$v['desc'];
                $res[$k]['voice']          = $v['voice'] ? Tool::imagesReplace($v['voice']) : '';
                $res[$k]['game_type']      = $game_type;
                $res[$k]['quiz_number']    = D('Common')->getQuizNumber($v['quiz_number']);

                if($iosCheck) $res[$k]['nick_name'] = str_replace(C('filterNickname'), C('replaceWord'), $res[$k]['nick_name']);

                //如已经登陆,判断当前用户是否有购买当前信息
                if ($userToken) {
                    $res[$k]['is_trade'] = D('Common')->getTradeLog($v['gamble_id'], $userToken['userid'], $game_type);//是否已查看购买过
                } else {//无登录则全部没有购买
                    $res[$k]['is_trade'] = '0';
                }
                unset($res[$k]['winrate'],$res[$k]['fb_gamble_win'],$res[$k]['fb_bet_win'],$res[$k]['fb_ten_gamble'],$res[$k]['fb_ten_bet'],$res[$k]['bk_ten_gamble'],$res[$k]['bk_gamble_win']);
            }
        }
        return $res?:[];
    }

    /**
     * 大咖广场——我的关注
     * @return array
     */
    public function myFollowGamble($playType, $lvType, $priceType, $unionType, $userToken, $timestamp=0, $pageSize, $pageNum, $game_type=1, $platform=0){
        if(empty($userToken))
            return array();

        $userid   = $userToken['userid'];
        $followId = M('FollowUser')->where(['user_id'=>$userid])->getField('follow_id', true); //关注用户的id数组
        $model = $game_type == 1 ? M('Gamble g'): M('Gamblebk g');

        $where['g.user_id'] = is_array($followId) ? ['IN', $followId] : $followId;

        if($playType)
            $where['g.play_type'] = ($playType == 1) ? ($game_type == 1 ? ['in', [1,-1]] : ['in', [1,2,-1,-2]]) : ['in', [-2,2]];

        //只显示昨天和今天，找到最小id
        $blockTime = getBlockTime($game_type, true);
        $where['g.create_time'] = ['between', [strtotime('-1 day', $blockTime['beginTime']), $blockTime['endTime']]];
        $minId = $model->where($where)->getField('min(id)');
        unset($where['g.create_time']);

        //这两天没有推荐
        if(empty($minId))
            return array();

        $where['g.id'] = ['gt', (int)$minId];

        if($timestamp){
            $where['g.create_time'] = ['lt', (int)$timestamp];
        }

        if($unionType){
            $where['g.union_id'] = ['in', explode(',', $unionType)];
        }

        $where['g.result']      = ['in', ['0', '1', '0.5', '2' ,'-0.5' ,'-1']];//0:未出结果，1：赢，0.5:赢半，2：平，-1：输，-0.5：输半）
        $where['gf.game_state'] = $game_type == 1 ? ['in', [-1,0,1,2,3,4]] : ['in', [-1,0,1,2,3,4,5,6,7]];

        if(is_numeric($lvType) && in_array($lvType, array(1,2,3,4,5,6,7,8,9))){
            if($playType == 1){
                if($game_type == 1){
                    $where['u.lv'] = $lvType;
                }else{
                    $where['u.lv_bk'] = $lvType;
                }
            }else{
                $where['u.lv_bet'] = $lvType;
            }
        }

        $order = '';
        if($priceType){
            $sort = ($priceType == 1) ? 'DESC' : 'ASC';
            $order .= ' g.tradeCoin '.$sort.',';
        }
        $order .= ' g.id desc, qu.is_sub ASC ';

        $fields = " g.id as gamble_id, g.user_id, g.union_name, g.game_date, g.game_time, g.home_team_name, g.away_team_name,
                  g.play_type, g.chose_side, g.tradeCoin, g.handcp, g.odds, g.result, g.`desc`, (g.quiz_number + g.extra_number) as quiz_number, g.create_time, IF(is_voice=1, voice, '') as voice, g.voice_time, u.nick_name, u.head as face ";

        $info  = $game_type == 1 ? 'qc_game_fbinfo' : 'qc_game_bkinfo';
        $lv    = $game_type == 2 ? 'u.lv_bk' : ($playType == 2 ? 'u.lv_bet' : 'u.lv');
        if($playType == 1){
            $fields .= ' , '.$lv.' as lv, qu.union_color ';
            $list = $model->field($fields)
                ->join(' LEFT JOIN qc_union AS qu ON g.union_id = qu.union_id ')
                ->join(' LEFT JOIN '.$info.' AS gf ON g.game_id = gf.game_id ')
                ->join(' LEFT JOIN qc_front_user AS u ON g.user_id = u.id ')
                ->where($where)->limit($pageSize, $pageNum)->order($order)
                ->group('g.id')->select();
        }else{
            $fields .= ' , u.lv_bet as lv, qu.union_color, b.bet_code ';
            $list = M('Gamble g')->field($fields)
                ->join(' LEFT JOIN qc_union AS qu ON g.union_id = qu.union_id ')
                ->join(' LEFT JOIN qc_game_fbinfo AS gf ON g.game_id = gf.game_id ')
                ->join(' LEFT JOIN qc_fb_betodds AS b ON b.game_id = g.game_id ')
                ->join(' LEFT JOIN qc_front_user AS u ON g.user_id = u.id ')
                ->where($where)->limit($pageSize, $pageNum)->order($order)
                ->group('g.id')->select();
        }

        if(empty($list))
            return array();

        foreach ($list as $k => $v)
        {
            $list[$k]['face']        = frontUserFace($v['face']);
            $list[$k]['weekPercnet'] = D('GambleHall')->CountWinrate($v['user_id'], $game_type, 1, false, false, 0,$playType);//周胜率

            $winnig = D('GambleHall')->getWinning($v['user_id'], $game_type, 0, $playType, 30); //连胜记录
            $list[$k]['curr_victs'] = $winnig['curr_victs'];//连胜场数
            $list[$k]['tenGambleRate'] = $winnig['tenGambleRate'];//近十场的胜率;
            $list[$k]['union_name']     = explode(',', $v['union_name']);
            $list[$k]['home_team_name'] = explode(',', $v['home_team_name']);
            $list[$k]['away_team_name'] = explode(',', $v['away_team_name']);
            $list[$k]['desc']           = (string)$v['desc'];
            $list[$k]['voice']          = $v['voice'] ? Tool::imagesReplace($v['voice']) : '';
            $list[$k]['game_type']      = $game_type;
            $list[$k]['quiz_number']    = D('Common')->getQuizNumber($v['quiz_number']);

            //如已经登陆,判断当前用户是否有购买当前信息
            if ($userToken) {
                $list[$k]['is_trade'] = D('Common')->getTradeLog($v['gamble_id'], $userToken['userid'], $game_type);//是否已查看购买过
            } else {//无登录则全部没有购买
                $list[$k]['is_trade'] = '0';
            }

            unset($winnig);
        }

        return $list;
    }

    /**
     * 获取亚盘或者竞彩的数据
     * @param $playType  int  玩法，1：亚盘；2：竞彩
     * @param $paramType int 排行榜类型
     * @param $rankDate  array 日期
     * @param $game_type int  默认1：足球；2：篮球
     * @return array
     */
    public function getPlayTypeData($playType, $paramType, $rankDate=0, $game_type=1){
        $blockTime = getBlockTime($game_type, $gamble = true);//获取竞猜分割日期的区间时间
        $where['r.dateType']  = $paramType;
        $where['r.gameType']  = $game_type;
        if($playType == 1){
            $table = M('RankingList r');
            $sort_field = 'end_date';
            $tenStr = $game_type == 1 ? 'fb_ten_gamble' : 'bk_ten_gamble';
        }else if($playType == 2){
            $table = M('RankBetting r');
            $sort_field = 'listDate';
            $tenStr = 'fb_ten_bet';
        }
        $where['r.id'] = ['gt', 0];
        $rankDate = $table->where($where)->order('id desc')->limit(1)->getField($sort_field);
        $where['r.'.$sort_field] = $rankDate;
        $where['u.status'] = 1;
        //周榜前200名取命中率高前24名；篮球取48名；
        $arr = $table->field('r.user_id, u.nick_name, u.head as face, u.is_robot, r.winrate,u.fb_ten_gamble,u.fb_ten_bet,u.bk_ten_gamble')
                ->join('left join qc_front_user AS u ON r.user_id = u.id ')
                ->where($where)->group('r.user_id')->order('r.ranking ASC')->limit(200)->select();

        if($arr) {
            $tenGambleRateArr = $robotSort = $rateSort = $todayGambleSort = $userIdArr = array();//排序数组

            $model = $game_type == 1 ? M('Gamble') : M('Gamblebk');
            $playTypeString = $playType== 1 ? ($game_type == 1 ? [-1,1] : [-1,-2,1,2]) : [-2,2];
            $iosCheck = iosCheck();
            foreach ($arr as $k => $v) {
                $arr[$k]['face']          = frontUserFace($v['face']);
                $tenNum = $v[$tenStr]; //近十中几数量
                $tenGamble = [];
                //创造近十中几数组
                for ($i=0; $i < 10; $i++) { 
                    if($i < $tenNum){
                        $tenGamble[] = '1';
                    }else{
                        $tenGamble[] = '2';
                    }
                }
                $arr[$k]['tenGamble']     = $tenGamble;
                $arr[$k]['gameType']      = $playType;
                if($iosCheck) $arr[$k]['nick_name'] = str_replace(C('filterNickname'), C('replaceWord'), $arr[$k]['nick_name']);
                $userIdArr[]              = $v['user_id'];
                $robotSort[]              = $v['is_robot'];
                $rateSort[]               = $v['winrate'];
                $tenGambleRateArr[]       = $tenNum;//近十中几数量
                unset($arr[$k]['fb_ten_gamble'],$arr[$k]['fb_ten_bet'],$arr[$k]['bk_ten_gamble']);
            }
            $gambleArr = $model->where(['user_id' => ['in',$userIdArr],  'play_type' => ['in', $playTypeString], 'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]]])->group('user_id')->getField('user_id',true);
            foreach ($arr as $k => $v) {
                $arr[$k]['today_gamble'] = $todayGambleSort[] = in_array($v['user_id'], $gambleArr) ? 1 : 0;
            }
            //当天发布竞猜>命中率>真实>周胜率
            array_multisort($todayGambleSort, SORT_DESC, $tenGambleRateArr, SORT_DESC, $robotSort, SORT_ASC, $rateSort, SORT_DESC, $arr);
            $listNum = $game_type == 1 ? 24 : 48;
            $hotList = array_slice($arr, 0, $listNum);//取前24,6页

            unset($todayGambleSort, $tenGambleRateArr, $robotSort, $rateSort);
        }

        return $arr ? $hotList: array();
    }

    /**
     * 比赛关联相关竞猜
     * @param $game_id
     * @param $game_type 默认1：足球；2：篮球
     * @return array
     */
    public function getMatchGamble($game_id=0, $userid=0, $game_type=1, $famous_id=0){
        //名师
        if($famous_id){
            $where['g.user_id'] = $famous_id ;
        }else if($game_id){
            $where['g.game_id'] = $game_id;
        }

        $blockTime = getBlockTime($game_type, $gamble = true);//获取竞猜分割日期的区间时间

        $where['g.result'] = 0;
        $where['g.create_time'] = ['between', [$blockTime['beginTime'], $blockTime['endTime']]];

        $field = 'g.id as gamble_id, g.user_id, g.union_name, g.game_date, g.game_time, g.home_team_name, g.away_team_name, g.play_type, g.tradeCoin, g.chose_side, g.handcp, g.odds, g.`desc`, (g.quiz_number + g.extra_number) as quiz_number, u.nick_name, u.head as face, u.lv';

        if($game_type == 1){
            $field .= ', b.bet_code';
            $gambleArr = M('Gamble g')->field($field)
                ->join(' LEFT JOIN qc_front_user as u on u.id = g.user_id')
                ->join(' LEFT JOIN qc_fb_betodds AS b ON g.game_id = b.game_id ')
                ->where($where)
                ->order("quiz_number desc")->limit(2)->select();
        }else{
            $gambleArr = M('Gamblebk g')->field($field)
                ->join(' LEFT JOIN qc_front_user as u on u.id = g.user_id')
                ->where($where)
                ->order("quiz_number desc")->limit(2)->select();
        }

        if(!$gambleArr) return array();

        foreach ($gambleArr as $k => $v) {
            $gambleArr[$k]['face']           = frontUserFace($v['face']);
            $gambleArr[$k]['union_name']     = explode(',', $v['union_name']);
            $gambleArr[$k]['home_team_name'] = explode(',', $v['home_team_name']);
            $gambleArr[$k]['away_team_name'] = explode(',', $v['away_team_name']);
            //自己发布的相当于购买
            if($v['user_id'] == $userid){
                $gambleArr[$k]['is_trade']  = 1;
            }else{
                $gambleArr[$k]['is_trade']   = M('QuizLog')->where(['game_type' => $game_type, 'user_id' => $userid, 'gamble_id' => $v['gamble_id']])->count() ? 1 : 0;
            }
            $gambleArr[$k]['desc']           = (string)$v['desc'];
            $gambleArr[$k]['quiz_number']    = D('Common')->getQuizNumber($v['quiz_number']);
        }

        return $gambleArr;
    }

    /**
     * 近5场比赛结果
     */
    public function fiveGameRate($where, $game_type=1){
        //默认足球
        $model = $game_type == 1 ? M('Gamble'): M('Gamblebk');
        $fiveArray = $model->master(true)->where($where)->order("id desc")->limit(5)->field('result')->select();
        $num       = 0;
        $count     = count($fiveArray);
        if ($count == 5) {
            foreach ($fiveArray as $k => $v) {
                if ($v['result'] == '1' || $v['result'] == '0.5') {
                    $num++;
                }
            }
        }

        return ['num'=>$count, 'win'=>$num];
    }

    /**
     * 拼装sql
     */
    public function assembleSql($table, $field, $arr){
        $ids = implode(',', array_keys($arr));
        $sql = " UPDATE {$table} SET {$field} = CASE id ";

        foreach ($arr as $id => $v) {
            $sql .= sprintf(" WHEN %d THEN %d ", $id, $v);
        }

        $sql .= " END WHERE id IN ($ids) ";

        return $sql;
    }

    /**
     * 资讯里的名师推荐内容
     */
    public function getNewsGamble($game_type, $detail){
        //足球才有推荐，名师推荐
        if($game_type == 1 && $detail['game_id']){//足球
            $gameType = in_array($detail['play_type'], [1,-1]) ? 1 : 2;
            $gameInfo = M('GameFbinfo f')->field('f.union_name, f.gtime, f.home_team_name, f.away_team_name, f.score, t1.img_url as home_img, t2.img_url as away_img')
                ->join('left join qc_game_team t1 on f.home_team_id = t1.team_id')
                ->join('left join qc_game_team t2 on f.away_team_id = t2.team_id')
                ->where(['f.game_id' => $detail['game_id']])
                ->find();

            if($gameInfo) {
                $gameInfo['play_type']  = $detail['play_type'];
                $gameInfo['chose_side'] = $detail['chose_side'];
                $gameInfo['result'] = $detail['result'];
                $gameInfo['odds'] = $detail['odds'];
                $gameInfo['handcp'] = $gameType == 1 ? changeExp($detail['handcp']) : $detail['handcp'];
                $gameInfo['odds_other'] = $gameType == 1 ? $detail['odds_other'] : json_decode($detail['odds_other'], true);
                $gameInfo['union_name'] = explode(',', $gameInfo['union_name'])[0];
                $gameInfo['home_team_name'] = explode(',', $gameInfo['home_team_name'])[0];
                $gameInfo['away_team_name'] = explode(',', $gameInfo['away_team_name'])[0];
                $gameInfo['home_img'] = (string)Tool::imagesReplace($gameInfo['home_img']) ?: 'https://www.qqty.com/Public/Home/images/common/home_def.png';
                $gameInfo['away_img'] = (string)Tool::imagesReplace($gameInfo['away_img']) ?: 'https://www.qqty.com/Public/Home/images/common/away_def.png';
                $gameInfo['game_date'] = date('m/d H:i', $gameInfo['gtime']);
                if ($gameType == 2)
                    $gameInfo['bet_code'] = (string)M('FbBetodds')->where(['game_id' => $detail['game_id']])->getField('bet_code');
            }
        } else if($game_type == 2 && $detail['gamebk_id']){//篮球
            $gameInfo = M('GameBkinfo f')->field('f.union_name, f.gtime, f.home_team_name, f.away_team_name, f.score, t1.img_url as home_img, t2.img_url as away_img')
                ->join('left join qc_game_teambk t1 on f.home_team_id = t1.team_id')
                ->join('left join qc_game_teambk t2 on f.away_team_id = t2.team_id')
                ->where(['f.game_id' => $detail['gamebk_id']])
                ->find();

            if($gameInfo) {
                $gameInfo['play_type']  = $detail['play_type'];
                $gameInfo['chose_side'] = $detail['chose_side'];
                $gameInfo['result'] = $detail['result'];
                $gameInfo['odds'] = $detail['odds'];
                $gameInfo['handcp'] = $detail['handcp'];
                $gameInfo['odds_other'] = $detail['odds_other'];
                $gameInfo['union_name'] = explode(',', $gameInfo['union_name'])[0];
                $gameInfo['home_team_name'] = explode(',', $gameInfo['home_team_name'])[0];
                $gameInfo['away_team_name'] = explode(',', $gameInfo['away_team_name'])[0];
                $gameInfo['home_img'] = (string)Tool::imagesReplace($gameInfo['home_img']) ?: 'https://www.qqty.com/Public/Home/images/common/home_def.png';
                $gameInfo['away_img'] = (string)Tool::imagesReplace($gameInfo['away_img']) ?: 'https://www.qqty.com/Public/Home/images/common/away_def.png';
                $gameInfo['game_date'] = date('m/d H:i', $gameInfo['gtime']);
            }
        } else {
            $gameInfo = '';
        }

        return $gameInfo ?: '';
    }

}


?>