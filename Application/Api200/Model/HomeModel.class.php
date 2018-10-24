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

    //获取新闻资讯列表
    public function getArticleList($page, $pageNum, $channel_id = null, $update_time = 0)
    {
        if ($channel_id) {
            if($channel_id == 6 ) {
                $classIds = ['6', '54', '55'];
            }else{
                $classIds = [$channel_id];
            }

            //获取资讯分类
            $PublishClass = M('PublishClass')->where("status = 1")->select();

            $searchClassIds = [];

            //根据当前分类id，获取下级分类id
            foreach ($classIds as $v) {
                $searchClassIds = array_merge($searchClassIds, \Think\Tool\Tool::getAllSubCategoriesID($PublishClass, $v));
            }

            $class_id = ['IN', $searchClassIds];
        } else {
            $class_id = ['neq', 10];
        }

        $where['status'] = 1;
        $where['class_id'] = $class_id;

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
        //取现在大咖广场的命中选项，数据一样，50名取命中率高前24名；
        $paramType = 1;//取周榜
        $rankDate = getRankDate($paramType);//获取上周的日期

        $sql1 = " SELECT count(*) AS num from qc_ranking_list WHERE dateType = {$paramType} AND gameType = 1 AND begin_date >= {$rankDate[0]} AND end_date <= {$rankDate[1]} ";
        $count = M()->query($sql1);
        if (!$count[0]['num']) {
            $rankDate = getTopRankDate($paramType);//获取上上周的数据
        }

        $sql = " SELECT r.user_id, u.nick_name, u.head, r.winrate
                    FROM qc_ranking_list AS r
                    LEFT JOIN qc_front_user AS u ON r.user_id = u.id
                    WHERE r.dateType = {$paramType}
                    AND r.gameType = 1
                    AND r.begin_date >= {$rankDate[0]} AND r.end_date <= {$rankDate[1]} AND r.id > 0
                    ORDER BY  r.ranking ASC LIMIT 50 ";

        $arr = M()->query($sql);
        $tenGambleRateArr = $robotSort = array();//排序数组

        foreach ($arr as $k => $v) {
            $arr[$k]['tenGamble'] = D('GambleHall')->getTenGamble($v['user_id'], 1);
            $arr[$k]['tenGambleRate'] = $tenGambleRateArr[] = countTenGambleRate($arr[$k]['tenGamble']);//近十场的胜率
            $robotSort[] = M('FrontUser')->where(['id' => $v['user_id']])->getField('is_robot');
            unset($arr[$k]['tenGambleRate']);
        }

        //命中率高，真人
        array_multisort($tenGambleRateArr, SORT_DESC, $robotSort, SORT_ASC, $arr);
        $hotList = array_slice($arr, 0, 24);//取前24

        return $hotList;
    }

    public function getCommentList($article_id, $userToken, $showLevel = '')
    {
        $cModel = D('Comment c');
        $comment = $cModel->field(['c.id', 'c.user_id', 'u.nick_name', 'u.head face', 'c.filter_content content ', 'c.like_num', 'c.create_time', 'c.status'])
            ->where(['c.publish_id' => $article_id, 'c.pid' => 0])
            ->join('LEFT JOIN __FRONT_USER__ u ON u.id = c.user_id')
            ->order('c.like_num desc,c.create_time desc')
            ->limit(5)
            ->select();
        if ($showLevel == 1) return $comment;
        foreach ($comment as $k => $v) {
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
}


?>