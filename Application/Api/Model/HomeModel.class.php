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
        foreach ($banner as $k => $v)
        {
            $banner[$k]['shareTitle'] = '';
            $banner[$k]['shareImg'] = '';

            if ($v['module'] == 1)
            {
                $publishInfo = M('PublishList')->field(['title','img'])->where(['id'=>$v['url']])->find();
                $banner[$k]['shareTitle'] = $publishInfo['title'];
                $banner[$k]['shareImg'] = $publishInfo['img'] ? C('IMG_SERVER').$publishInfo['img'] : '';
            }

            if ($v['module'] == 2)
            {
                $galleryInfo = M('Gallery')->field(['title','img_array'])->where(['id'=>$v['url']])->find();
                $banner[$k]['shareTitle'] = $galleryInfo['title'];

                if ($galleryInfo['img_array'])
                {
                    $imgArr = json_decode($galleryInfo['img_array'],true);

                    foreach ($imgArr as $v)
                    {
                        if ($v)
                        {
                            $banner[$k]['shareImg'] = C('IMG_SERVER').$v;
                            break;
                        }

                    }
                }
            }
        }

        return $banner;
    }

    //获取新闻资讯列表
    public function getArticleList($page,$pageNum,$channel_id=null)
    {
        if ($channel_id)
        {
            $childClassId = M('PublishClass')->where(['pid'=>$channel_id])->getField('id',true);       //是否有子分类
            $class_id = $childClassId ? ['IN',array_merge($childClassId,[$channel_id])] : $channel_id;
        }
        else
        {
            $class_id = ['neq',10];
        }

        $where['status'] = 1;
        $where['class_id'] = $class_id;

        $articleList = M('PublishList')->field(['id','short_title title','remark','img','content'])
                       ->where($where)
                       ->order('update_time desc')
                       ->page($page.','.$pageNum)->select();

        return $this->getArticleImg($articleList);
    }

    //获取资讯里面的图片
    public function getArticleImg($articleList)
    {
        foreach ($articleList as $k => $v)
        {
            $imgs = Tool::getTextImgUrl(htmlspecialchars_decode($v['content']),0);

            foreach ($imgs as $kkk => $vvv)
            {
                if (strtoupper(substr(strrchr($vvv, '.'), 1)) == 'GIF')
                    unset($imgs[$kkk]);
            }

            if (count($imgs) >= 3)
            {
                $imgs = array_slice($imgs, 0,3);
                foreach ($imgs as $kk => $vv)
                {
                    if (strpos($vv, 'http://') === false)
                        $imgs[$kk] = C('IMG_SERVER').$vv;
                }

                $articleList[$k]['img'] = $imgs;
            }
            else
            {
                if ($articleList[$k]['img'])
                {
                    $articleList[$k]['img'] = [C('IMG_SERVER').$articleList[$k]['img']];
                }
                else
                {
                    if (count($imgs) >= 1)
                    {
                        if (strpos($imgs[0], 'http://') === false)
                            $articleList[$k]['img'] = [C('IMG_SERVER').$imgs[0]];
                        else
                            $articleList[$k]['img'] = [$imgs[0]];
                    }
                    else
                    {
                        $articleList[$k]['img'] = [];
                    }
                }

                // $articleList[$k]['img'] = $articleList[$k]['img'] ? ['http://'.C('IMG_SERVER').$articleList[$k]['img']] :
                //                             count($imgs) >= 1 ? $imgs[0] = ['http://'.C('IMG_SERVER').$imgs[0]] : [];
            }

            $articleList[$k]['commentNum'] = M('Comment')->where(['publish_id'=>$v['id']])->count(); //获取评论数
            unset($articleList[$k]['content']);
        }
        return $articleList;
    }

    // 最新竞猜
    public function getMatch()
    {
        $matchField = [
            'gf.union_name',
            'u.union_color',
            'gf.game_id',
            'gf.game_date',
            'gf.game_time',
            'gf.game_state',
            'gf.home_team_name',
            'gf.home_team_id',
            'gf.score',
            'gf.away_team_name',
            'gf.away_team_id'
        ];
        $matchWhere = [
            'gf.show_date'     => $this->show_date,
            'gf.fsw_exp'       => ['neq',''],
            'gf.fsw_ball'      => ['neq',''],
            'gf.fsw_exp_home'  => ['neq',''],
            'gf.fsw_exp_away'  => ['neq',''],
            'gf.fsw_ball_home' => ['neq',''],
            'gf.fsw_ball_away' => ['neq',''],
            'gf.game_state'    => ['in',[0,1,2,3,4,-1]],
            '_string'          => '(u.is_sub < 3 AND gf.is_show = 1) or gf.is_gamble = 1'
        ];

        $match = M('GameFbinfo gf')->field($matchField)
                                   ->where($matchWhere)
                                   ->join('LEFT JOIN __UNION__ u ON u.union_id = gf.union_id')
                                   ->order("gf.game_date desc,gf.game_time desc")
                                   ->limit(6)
                                   ->select();

        $match = $match[array_rand($match)]; //6条中随机取一条

        $match['union_name']     = explode(',', $match['union_name']);
        $match['home_team_name'] = explode(',', $match['home_team_name']);
        $match['away_team_name'] = explode(',', $match['away_team_name']);
        $match['homeTeamLogo']   = getLogoTeam($match['home_team_id'],1);
        $match['awayTeamLogo']   = getLogoTeam($match['away_team_id'],2);

        return $match;
    }

    //直播和集锦
    public function getVideo()
    {
        $field = ['game_id','game_date','game_time','game_state','union_name','home_team_id','home_team_name','away_team_id','away_team_name','score'];
        $video = M('GameFbinfo')->field($field)
                ->where(['game_state'=>['in',[0,1,2,3,4]],'show_date'=>$this->show_date,'is_video'=>1,'is_recommend'=>1])
                ->order('rand()')->limit(2)->select();

        $videoNum = 2 - count($video);

        if ($videoNum > 0)
        {
            $gameId = M('Highlights')->where(['game_type'=>1,'is_recommend'=>['neq',0],'status'=>1])->order('add_time desc')
                    ->limit($videoNum)->group('game_id')->getField('game_id',true);
            $needVideo = M('GameFbinfo')->field($field)->where(['game_id'=>['in',$gameId]])->select();
            $video = array_merge((array)$video,$needVideo);
        }

        foreach ($video as $k => $v)
        {
            $video[$k]['union_name']     = explode(',', $video[$k]['union_name']);
            $video[$k]['home_team_name'] = explode(',', $video[$k]['home_team_name']);
            $video[$k]['away_team_name'] = explode(',', $video[$k]['away_team_name']);
            $video[$k]['homeTeamLogo']   = getLogoTeam($v['home_team_id'],1);
            $video[$k]['awayTeamLogo']   = getLogoTeam($v['away_team_id'],2);
        }

        return $video;
    }

    //获取回复的评论
    public function getSubComment($pid=0,$userToken='')
    {
        $comment = M('Comment c')->field(['c.id','c.pid','c.user_id','u.nick_name','u.head','c.content','c.like_num','c.like_user','c.create_time'])
                        ->where(['pid'=>$pid])
                        ->join('LEFT JOIN __FRONT_USER__ u ON u.id = c.user_id')
                        ->order('c.create_time desc')
                        ->select();

        if (!$comment)
            return;

        foreach ($comment as $k => $v)
        {
            $comment[$k]['face'] = frontUserFace($v['head']);
            $comment[$k]['is_liked'] = $userToken && in_array($userToken['userid'],explode(',', $v['like_user'])) ? 1 : 0;
            $comment[$k]['subComment'] = $this->getSubComment($v['id'],$userToken);

            unset($comment[$k]['like_user']);
        }

        return $comment;
    }

    //获取首页红人榜
    public function getHotList($date)
    {
        $hotList = M('RedList r')->field(['r.user_id','r.winrate','u.nick_name','u.head'])
                    ->where(['game_type'=>1,'list_date'=>date('Ymd',strtotime("$date day")),'ranking'=>['elt',30]])
                    ->join('LEFT JOIN __FRONT_USER__ u ON r.user_id = u.id')
                    ->order('rand()')->limit(18)->select();

        if (!$hotList)
        {
            $hotList = $this->getHotList($date-1);
        }

        return $hotList;
    }
}


 ?>