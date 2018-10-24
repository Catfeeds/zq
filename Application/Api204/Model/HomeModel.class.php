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
                $banner[$k]['shareTitle'] = $publishInfo['title'];
                $banner[$k]['shareImg'] = $publishInfo['img'] ? C('IMG_SERVER') . $publishInfo['img'] : '';
            }

            if ($v['module'] == 2) {
                $galleryInfo = M('Gallery')->field(['title', 'img_array'])->where(['id' => $v['url']])->find();
                $banner[$k]['shareTitle'] = $galleryInfo['title'];

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
    public function getArticleList($page, $pageNum, $channel_id = null, $update_time = 0)
    {
        //要读取的资讯板块
        $channel_ids = explode(',',trim($channel_id,','));
        $searchClassIds = [];

        //获取资讯全部分类
        $PublishClass = M('PublishClass')->where("status = 1")->select();

        //根据当前分类id，获取下级分类id
        foreach ($channel_ids as $v) {
            $searchClassIds = array_merge($searchClassIds, \Think\Tool\Tool::getAllSubCategoriesID($PublishClass, $v));
        }

        $where['status'] = 1;
        $where['class_id'] = ['IN', $searchClassIds];

        if ($update_time) {//如果传最小id，则向下取
            $where['update_time'] = ['lt', (int)$update_time];
            $articleList = M('PublishList')->field(['id', 'class_id', 'short_title title', 'remark', 'img', 'content', 'update_time'])
                ->where($where)
                ->order('update_time desc')
                ->limit($pageNum)->select();
        } else {
            $articleList = M('PublishList')->field(['id', 'class_id', 'short_title title', 'remark', 'img', 'content', 'update_time'])
                ->where($where)
                ->order('update_time desc')
                ->page($page . ',' . $pageNum)->select();
        }

        return $this->getArticleImg($articleList);
    }

    //获取资讯里面的图片
    public function getArticleImg($articleList)
    {
        foreach ($articleList as $k => $v) {
            $imgs = Tool::getTextImgUrl(htmlspecialchars_decode($v['content']), 0);

            foreach ($imgs as $kkk => $vvv) {
                if (strtoupper(substr(strrchr($vvv, '.'), 1)) == 'GIF')
                    unset($imgs[$kkk]);
            }

            if (count($imgs) >= 3) {
                $imgs = array_slice($imgs, 0, 3);
                foreach ($imgs as $kk => $vv) {
                    if (strpos($vv, 'http://') === false)
                        $imgs[$kk] = C('IMG_SERVER') . $vv;
                }

                $articleList[$k]['img'] = $imgs;
            } else {
                if ($articleList[$k]['img']) {
                    $articleList[$k]['img'] = [C('IMG_SERVER') . $articleList[$k]['img']];
                } else {
                    if (count($imgs) >= 1) {
                        if (strpos($imgs[0], 'http://') === false)
                            $articleList[$k]['img'] = [C('IMG_SERVER') . $imgs[0]];
                        else
                            $articleList[$k]['img'] = [$imgs[0]];
                    } else {
                        $articleList[$k]['img'] = [];
                    }
                }

                // $articleList[$k]['img'] = $articleList[$k]['img'] ? ['http://'.C('IMG_SERVER').$articleList[$k]['img']] :
                //                             count($imgs) >= 1 ? $imgs[0] = ['http://'.C('IMG_SERVER').$imgs[0]] : [];
            }

            $articleList[$k]['commentNum'] = M('Comment')->where(['publish_id' => $v['id']])->count(); //获取评论数
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
            $match[$k]['home_team_name'] = explode(',', $v['home_team_name']);
            $match[$k]['away_team_name'] = explode(',', $v['away_team_name']);
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

    //获取首页每日精选（红人榜）
    public function getHotList()
    {
        $blockTime = getBlockTime(1, $gamble=true);
        //取现在大咖广场的命中选项，数据一样，50名取命中率高前24名；
        $paramType = 1;//取周榜
        $rankDate = getRankDate($paramType);//获取上周的日期

        $sql1 = " SELECT count(*) AS num from qc_ranking_list WHERE dateType = {$paramType} AND gameType = 1 AND begin_date >= {$rankDate[0]} AND end_date <= {$rankDate[1]} ";
        $count = M()->query($sql1);
        if (!$count[0]['num']) {
            $rankDate = getTopRankDate($paramType);//获取上上周的数据
        }

        $sql = " SELECT r.user_id, u.nick_name, u.head as face, r.winrate
                    FROM qc_ranking_list AS r
                    LEFT JOIN qc_front_user AS u ON r.user_id = u.id
                    WHERE r.dateType = {$paramType}
                    AND r.gameType = 1
                    AND r.begin_date >= {$rankDate[0]} AND r.end_date <= {$rankDate[1]} AND r.id > 0
                    ORDER BY  r.ranking ASC LIMIT 50 ";

        $arr = M()->query($sql);
        $tenGambleRateArr = $robotSort = $rateSort = $todayGambleSort = array();//排序数组

        foreach ($arr as $k => $v) {
            $arr[$k]['face']          = frontUserFace($v['face']);
            $arr[$k]['tenGamble']     = D('GambleHall')->getTenGamble($v['user_id'], 1);
            $arr[$k]['today_gamble']  = $todayGambleSort[] = M('Gamble')->where(['user_id' => $v['user_id'], 'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]]])->getField('id') ? 1 : 0;
            $arr[$k]['tenGambleRate'] = $tenGambleRateArr[] = countTenGambleRate($arr[$k]['tenGamble']);//近十场的胜率
            $robotSort[]              = M('FrontUser')->where(['id' => $v['user_id']])->getField('is_robot');
            $rateSort[]               = $v['winrate'];

            unset($arr[$k]['tenGambleRate']);
        }

        //当天发布竞猜>命中率>真实>周胜率
        array_multisort($todayGambleSort, SORT_DESC, $tenGambleRateArr, SORT_DESC, $robotSort, SORT_ASC, $rateSort, SORT_DESC, $arr);
        $hotList = array_slice($arr, 0, 24);//取前24

        unset($todayGambleSort, $tenGambleRateArr, $robotSort, $rateSort);

        return $hotList;
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
     *  高手竞猜模型
     */
    public function getMasterGamble($userToken='', $sortType, $pageSize, $pageNum){

        switch ($sortType)
        {
            case 'highHit':           $result = $this->rankGambleList($sortType, 1, 100, $userToken, $pageSize, $pageNum);      break;
            case 'winMore':           $result = $this->rankGambleList($sortType, 2, 200, $userToken, $pageSize, $pageNum);      break;
            case 'popularityHigh':    $result = $this->rankGambleList($sortType, 1, 100, $userToken, $pageSize, $pageNum);      break;
            case 'levelHigh':         $result = $this->getList($sortType, $userToken, 1, $pageSize, $pageNum);      break;
            case 'weekRate':          $result = $this->getList($sortType, $userToken, 1, $pageSize, $pageNum);      break;
            case 'priceHigh':         $result = $this->getList($sortType, $userToken, 1, $pageSize, $pageNum);      break;
            case 'priceLow':          $result = $this->getList($sortType, $userToken, 1, $pageSize, $pageNum);      break;
            default: $result = array();
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
    public function rankGambleList($sortType, $dateType, $num, $userToken, $pageSize, $pageNum){
        //把排好序的人做缓存，只要不结算就不会改变
        if(!$userArr = S('userArr'.$sortType.MODULE_NAME)) {
            $rankDate = getRankDate($dateType);//获取上个周期的日期
            $countNum = M('RankingList')->where(['dateType' => $dateType, 'gameType' => 1, 'begin_date' => ['between', [$rankDate[0], $rankDate[1]]]])->count();

            if (!$countNum) {
                $rankDate = getTopRankDate($dateType);//获取上个周期的数据
            }

            $arr = M('RankingList')->where(['dateType' => $dateType, 'gameType' => 1, 'begin_date' => ['between', [$rankDate[0], $rankDate[1]]]])->order('ranking ASC')->limit($num)->getField('user_id, winrate', true);
            $userArr = array_keys($arr);
            $rateArr = array();

            foreach ($userArr as $k => $v) {
                if ($sortType == 'highHit') {
                    $tenGamble = D('GambleHall')->getTenGamble($v, 1);
                    $tenGambleRate = countTenGambleRate($tenGamble);//近十场的胜率;
                    //要10中6的或以上
                    if ($tenGambleRate < 60) {
                        unset($userArr[$k], $arr[$v]);
                        continue;
                    }
                    $rateArr[$v] = $tenGambleRate;
                } else if ($sortType == 'winMore') {
                    $winnig = D('GambleHall')->getWinning($v, $gameType = 1); //连胜记录
                    //连胜2以上
                    if ($winnig['curr_victs'] < 2) {
                        unset($userArr[$k], $arr[$v]);
                        continue;
                    }
                    $rateArr[$v] = $winnig['curr_victs'];//连胜场数
                } else if ($sortType == 'popularityHigh') {
                    $rateNum = M('QuizLog')->where(['cover_id' => $v, 'game_type' => 1, 'coin' => ['gt', 0], 'log_time' => ['between', [strtotime(date('Y-m-d 00:00:00', strtotime('-3 day'))), strtotime(date('Y-m-d 23:59:59', strtotime('-1 day')))]]])->count();
                    //销量为0去掉
                    if($rateNum < 1){
                        unset($userArr[$k], $arr[$v]);
                        continue;
                    }
                    $rateArr[$v] = $rateNum;
                }
            }

            //等级
            $lvSort = M('FrontUser')->where(['id' => ['in', $userArr]])->getField('lv', true);

            //排序
            array_multisort(array_values($rateArr), SORT_ASC, array_values($arr), SORT_ASC, $lvSort, SORT_ASC, $userArr);
            S('userArr'.$sortType.MODULE_NAME, $userArr, 60*5);
            S('rateArr'.$sortType.MODULE_NAME, $rateArr, 60*5);
        }

        if(empty($userArr)){
            return array();
        }

        $rateArr = $rateArr ?: S('rateArr'.$sortType.MODULE_NAME);
        $blockTime = getBlockTime(1, $gamble = true);//获取竞猜分割日期的区间时间

        //竞猜赛程期间内，且未出结果的
        $where['g.user_id']     = ['in', $userArr];
        $where['g.create_time'] = ['between', [$blockTime['beginTime'], $blockTime['endTime']]];
        $where['g.result']      = 0;
        $where['g.play_type']   = ['in', [-1,1]];
        $where['gf.game_state'] = ['in', [0,1,2,3,4]];

        //人气旺不显示免费竞猜
        if($sortType == 'popularityHigh')
            $where['g.tradeCoin'] = ['gt', 0];

        //当天竞猜最新id
        $idList = M('Gamble g')->join(' LEFT JOIN qc_game_fbinfo AS gf ON g.game_id = gf.game_id ')
                  ->where($where)->group('g.user_id')->getField('max(g.id)', true);

        $where1['g.id'] = ['in', $idList];
        $order = ' field(g.user_id, '.implode(',', $userArr).') DESC, g.tradeCoin DESC, sortTime ASC ';
        $fields = ' CONCAT(g.game_date, g.game_time) as sortTime, g.id as gamble_id, g.user_id, g.union_name, g.game_date, g.game_time, g.home_team_name, g.away_team_name,
                  g.play_type, g.chose_side, g.tradeCoin, g.handcp, g.odds, g.result, g.`desc`, g.create_time, u.nick_name, u.head as face, u.lv, qu.union_color ';

        $res = M('Gamble g')->field($fields)
                ->join(' LEFT JOIN qc_front_user AS u ON g.user_id = u.id ')
                ->join(' LEFT JOIN qc_union AS qu ON g.union_id = qu.union_id ')
                ->where($where1)->group('gamble_id')->order($order)
                ->limit($pageSize, $pageNum)->select();

        if(!empty($res)) {
            foreach ($res as $k => $v) {
                if ($sortType == 'highHit') {
                    $res[$k]['tenGambleRate'] = $rateArr[$v['user_id']];
                    $winnig = D('GambleHall')->getWinning($v['user_id'], $gameType = 1); //连胜记录
                    $res[$k]['curr_victs'] = $winnig['curr_victs'];//连胜场数
                    unset($winnig);
                } else if ($sortType == 'winMore') {
                    if(!$rateArr[$v['user_id']]){
                        $winnig = D('GambleHall')->getWinning($v['user_id'], $gameType = 1); //连胜记录
                        $rateArr[$v['user_id']] = (string)$winnig['curr_victs'];//连胜场数
                    }
                    $res[$k]['curr_victs'] = $rateArr[$v['user_id']];
                    $tenGamble = D('GambleHall')->getTenGamble($v['user_id'], 1);
                    $res[$k]['tenGambleRate'] = countTenGambleRate($tenGamble);//近十场的胜率;
                    unset($tenGamble);
                } else {
                    $tenGamble = D('GambleHall')->getTenGamble($v['user_id'], 1);
                    $res[$k]['tenGambleRate'] = countTenGambleRate($tenGamble);//近十场的胜率;
                    $winnig = D('GambleHall')->getWinning($v['user_id'], $gameType = 1); //连胜记录
                    $res[$k]['curr_victs'] = $winnig['curr_victs'];//连胜场数
                    unset($tenGamble, $winnig);
                }

                $res[$k]['face'] = frontUserFace($v['face']);
                $res[$k]['weekPercnet'] = $arr[$v['user_id']] ?: (string)D('GambleHall')->CountWinrate($v['user_id'], 1, 1);//周榜

                $res[$k]['union_name'] = explode(',', $v['union_name']);
                $res[$k]['home_team_name'] = explode(',', $v['home_team_name']);
                $res[$k]['away_team_name'] = explode(',', $v['away_team_name']);
                $res[$k]['desc'] = (string)$v['desc'];

                //如已经登陆,判断当前用户是否有购买当前信息
                if ($userToken) {
                    $res[$k]['is_trade'] = M('QuizLog')->where(['game_type' => 1, 'gamble_id' => $v['gamble_id'], 'user_id' => $userToken['userid']])->getField('id') ? 1 : 0;//是否已查看购买过
                } else {//无登录则全部没有购买
                    $res[$k]['is_trade'] = 0;
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
    public function getList($sortType, $userToken, $dateType=1, $pageSize, $pageNum){
        $rankDate = getRankDate($dateType);//获取上个周期的日期
        $countNum = M('RankingList')->where(['dateType' => $dateType, 'gameType' => 1, 'begin_date' => ['between', [$rankDate[0], $rankDate[1]]]])->count();

        if (!$countNum){
            $rankDate = getTopRankDate($dateType);//获取上个周期的数据
        }

        $blockTime = getBlockTime(1, $gamble = true);//获取竞猜分割日期的区间时间

        $where['l.dateType']    = $dateType;
        $where['l.gameType']    = 1;
        $where['l.ranking']     = ['lt', 101];
        $where['l.begin_date']  = ['between', [$rankDate[0], $rankDate[1]]];
        $where['g.create_time'] = ['between', [$blockTime['beginTime'], $blockTime['endTime']]];
        $where['g.result']      = 0;
        $where['g.play_type']   = ['in', [-1,1]];
        $where['gf.game_state'] = ['in', [0,1,2,3,4]];

        //当天竞猜最新id
        $idList = M('Gamble g')->join(' LEFT JOIN qc_ranking_list AS l ON g.user_id = l.user_id ')
                  ->join(' LEFT JOIN qc_game_fbinfo AS gf ON g.game_id = gf.game_id ')
                  ->where($where)->group('g.user_id')->order('g.id DESC')->getField('max(g.id)', true);

        //竞猜赛程期间内，且未出结果的
        $where['g.id'] = ['in', $idList];
        $fields = ' CONCAT(g.game_date, g.game_time) as sortTime, g.id as gamble_id, g.user_id, g.union_name, g.game_date, g.game_time, g.home_team_name, g.away_team_name,
                  g.play_type, g.chose_side, g.tradeCoin, g.handcp, g.odds, g.result, g.`desc`, g.create_time, u.nick_name, u.head as face, u.lv, qu.union_color, l.winrate as weekPercnet ';

        switch ($sortType)
        {
            case 'levelHigh':   $order = ' u.lv DESC, l.winrate DESC, sortTime ASC ';  break;
            case 'weekRate':    $order = ' l.winrate DESC, sortTime ASC ';             break;
            case 'priceHigh':   $order = ' g.tradeCoin DESC, l.winrate DESC, u.lv DESC, sortTime ASC '; break;
            case 'priceLow':    $order = ' g.tradeCoin ASC, l.winrate DESC, u.lv DESC, sortTime ASC '; break;
            default:            $order = ' l.ranking ASC, sortTime ASC ';  break;
        }

        unset($where['gf.game_state']);
        $res = M('Gamble g')->field($fields)
                ->join(' LEFT JOIN qc_ranking_list AS l ON l.user_id = g.user_id ')
                ->join(' LEFT JOIN qc_front_user AS u ON g.user_id = u.id ')
                ->join(' LEFT JOIN qc_union AS qu ON g.union_id = qu.union_id ')
                ->where($where)->order($order)->limit($pageSize, $pageNum)->select();

        if(!empty($res)) {
            foreach ($res as $k => $v) {
                $tenGamble = D('GambleHall')->getTenGamble($v['user_id'], 1);
                $res[$k]['tenGambleRate'] = countTenGambleRate($tenGamble);//近十场的胜率;
                $res[$k]['face'] = frontUserFace($v['face']);
                $winnig = D('GambleHall')->getWinning($v['user_id'], $gameType = 1); //连胜记录
                $res[$k]['curr_victs'] = $winnig['curr_victs'];//连胜场数

                $res[$k]['union_name'] = explode(',', $v['union_name']);
                $res[$k]['home_team_name'] = explode(',', $v['home_team_name']);
                $res[$k]['away_team_name'] = explode(',', $v['away_team_name']);
                $res[$k]['desc'] = (string)$v['desc'];

                //如已经登陆,判断当前用户是否有购买当前信息
                if ($userToken) {
                    $res[$k]['is_trade'] = M('QuizLog')->where(['game_type' => 1, 'gamble_id' => $v['gamble_id'], 'user_id' => $userToken['userid']])->getField('id') ? 1 : 0;//是否已查看购买过
                } else {//无登录则全部没有购买
                    $res[$k]['is_trade'] = 0;
                }

                unset($tenGamble, $winnig, $res[$k]['sortTime'], $res[$k]['winrate']);
            }
        }

        return $res;
    }


}


?>