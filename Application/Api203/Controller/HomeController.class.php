<?php
/**
 * 首页其他
 * @author huangjiezhen <418832673@qq.com> 2016.03.21
 */
use Think\Tool\Tool;
class HomeController extends PublicController
{
    //V2.2首页
    public function index()
    {
        $userToken = getUserToken($this->param['userToken']);
        $blockTime = getBlockTime(1, $gamble=true);

        //滚动的banner
        if(!$banner = S('HomeBanner'.$this->param['platform'].MODULE_NAME))
        {
            $banner = Tool::getAdList(1,5,$this->param['platform']) ?: '';
            $banner = D('Home')->getBannerShare($banner);
            S('HomeBanner'.$this->param['platform'].MODULE_NAME, json_encode($banner), 60*5);
        }

        //每日精选（红人榜）
        if(!$hotList = S('HomeHotList'.MODULE_NAME)){
            $hotList = D('Home')->getHotList();

            S('HomeHotList'.MODULE_NAME, json_encode($hotList), 60*2);
        }

        //热门竞猜
        if(!$match = S('HomeMatch'.MODULE_NAME.I('pkg'))){
            $match = D('Home')->getMatch();
            S('HomeMatch'.MODULE_NAME.I('pkg'), json_encode($match), 60*2);
        }

        //“筛选命中率高”、“连胜数多”的用户
        $lastCacheTime = S('lastCacheTime'.MODULE_NAME);

        //没有缓存时间或者缓存时间大于30分钟
        if(!$lastCacheTime || (time() - $lastCacheTime > 60*30)) {
            $indexUserInfo = D('GambleHall')->getIndexUser();

            //缓存一天
            S('lastUserArr' . MODULE_NAME, json_encode($indexUserInfo), 3600 * 24);
            S('lastCacheTime' . MODULE_NAME, NOW_TIME, 3600 * 24);
        }else{
            $indexUserInfo = S('lastUserArr'.MODULE_NAME);
        }

        //获取当天竞猜信息
        if($indexUserInfo){
            foreach ($indexUserInfo as $k => $v) {
                $one = M('Gamble')->where(['user_id' => $v['user_id'], 'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]]])->order('id desc')->find();

                if ($one) {//若当天竞猜存在
                    $indexUserInfo[$k]['todayHomeName'] = explode(',', $one['home_team_name']);//当天推荐主队名称
                    $indexUserInfo[$k]['todayAwayName'] = explode(',', $one['away_team_name']);//当天推荐客队名称
                } else {
                    $indexUserInfo[$k]['todayHomeName'] = '';//当天推荐主队名称
                    $indexUserInfo[$k]['todayAwayName'] = '';//当天推荐客队名称
                }
                unset($one);
            }
        }

        //高手竞猜
        if(!$masterGamble = S('indexMasterGamble' . MODULE_NAME)){
            $masterGamble = D('GambleHall')->masterGamble($userToken);
            S('indexMasterGamble' . MODULE_NAME, json_encode($masterGamble), 60 * 5);
        }else{
            //购买情况需要实时
            foreach($masterGamble as $k => $v){
                if ($userToken) {
                    $masterGamble[$k]['is_trade'] = M('QuizLog')->where(['game_type' => 1, 'gamble_id' => $v['gamble_id'], 'user_id' => $userToken['userid']])->getField('id') ? 1 : 0;//是否已查看购买过
                } else {//无登录则全部没有购买
                    $masterGamble[$k]['is_trade'] = 0;
                }
            }
        }

        //返回数据
        $this->ajaxReturn([
            'banner'    => $banner,
            'hotList'   => $hotList,
            'match'     => $match,
            'indexUser' => $indexUserInfo,
            'masterGamble' => $masterGamble
        ]);
    }

    //资讯频道列表
    public function channelArticle()
    {
        $page = $this->param['page'] ?: 1;
        $pageNum = 20;

        $articleList = D('Home')->getArticleList($page,$pageNum,$this->param['channel_id']?:10);

        if($articleList){
            foreach($articleList as $k => $v){
                if(in_array($v['class_id'], C('classId'))){
                    $img = $detail['content'] = http_to_https('http://www.qqty.com/Public/Home/images/common/loading.png');
                    $articleList[$k]['img']   = [$img];
                }
                unset($articleList[$k]['class_id']);
            }
        }

        $this->ajaxReturn(['articleList'=>$articleList]);
    }

    //资讯详情页
    public function articleDetail()
    {
        $id = I('aritcle_id');
        $cModel = M('Comment c');
        $blockTime = getBlockTime(1, $gamble = true);//获取竞猜分割日期的区间时间

        if(!$detail = S('articleDetail'.$id.get_client_ip())) {
            $detail = M('PublishList')->field(['id', 'title', 'add_time', 'source', 'content', 'label', 'user_id', 'class_id'])->where(['id' => $id])->find();
            M("PublishList")->where(['id'=>$id])->setInc('click_number'); //点击次数加1

            if(in_array($detail['class_id'], C('classId'))){
                $detail['content'] = htmlspecialchars_decode($detail['content']);
                $detail['content'] = preg_replace('/<img.*\>/isU', "", $detail['content']);

                if(in_array($detail['class_id'], array(2, 3))){
                    $detail['content'] = preg_replace('/<(figcaption style="text-align:center;".*?)>(.*?)<(\/p.*?)>/si', "", $detail['content']);
                }else{
                    $detail['content'] = preg_replace('/<(p style="text-align:center;.*?)>(.*?)<(\/p.*?)>/si', "", $detail['content']);
                }
            }

            S('articleDetail'.$id.get_client_ip(), json_encode($detail), C('newsCacheTime'));
        }

        $user_id = (int)$detail['user_id'];//名师id
        $userToken = getUserToken($this->param['userToken']);
        $comment = D('Home')->getCommentList($id, $userToken);

        //随机显示周胜率排名前10用户(专家除外)的竞猜内容
        if($user_id){//名师解盘
            $list = M('Gamble g')->field('g.id as gamble_id, g.union_name, g.game_date, g.game_time, g.home_team_name, g.away_team_name, g.play_type, g.tradeCoin, g.chose_side, g.handcp, g.odds, g.`desc`')
                    ->where(['g.user_id' => $user_id, 'g.result' => 0, 'g.create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]]])
                    ->order('RAND(), g.id desc')->limit(2)->select();

            foreach ($list as $k => $v) {
                $list[$k]['desc'] = (string)$v['desc'];
                $list[$k]['union_name'] = explode(',', $v['union_name']);
                $list[$k]['home_team_name'] = explode(',', $v['home_team_name']);
                $list[$k]['away_team_name'] = explode(',', $v['away_team_name']);
                $list[$k]['is_trade'] = M('QuizLog')->where(['game_type' => 1, 'user_id' => $userToken['userid'], 'gamble_id' => $v['gamble_id']])->count() ? 1 : 0;
            }

            $this->nick_name = M('FrontUser')->where(['id' => $user_id])->getField('nick_name');
        }else{//资讯:推荐频道
            if(in_array($detail['class_id'] ,[6,54,55])){
                //取值周榜连胜10名中取2个
                $paramType = 1;//取周榜
                $rankDate = getRankDate($paramType);//获取上周的日期

                $sql1 = " SELECT count(*) AS num from qc_ranking_list WHERE dateType = {$paramType} AND gameType = 1 AND begin_date >= {$rankDate[0]} AND end_date <= {$rankDate[1]} ";
                $count = M()->query($sql1);
                if (!$count[0]['num']){
                    $rankDate = getTopRankDate($paramType);//获取上上周的数据
                }

                //去掉专家，找周榜前10个
                $arr = M('RankingList r')->field('r.user_id')
                        ->join('LEFT JOIN qc_front_user AS u ON r.user_id = u.id')
                        ->where(['r.dateType' => $paramType, 'r.gameType' => 1, 'r.begin_date' => ['EGT', $rankDate[0]], 'r.end_date' => ['ELT', $rankDate[1]], 'r.id' => ['gt', 0], 'u.is_expert' => 0])
                        ->order('r.ranking ASC')->limit(10)->select();

                $idArr = array();
                foreach($arr as $k => $v){//当天有竞猜的用户
                    if(M('Gamble')->where(['user_id' => $v['user_id'], 'result' => 0, 'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]]])->count()){
                        $idArr[$v['user_id']] = $v['user_id'];
                    }else{
                        unset($arr[$k]);
                    }
                }

                //随机出2个
                $idArr = count($idArr) > 2 ? array_rand($idArr, 2) : array_values($idArr);
                foreach($idArr as $k => $v){
                    $one = M('Gamble g')->field('g.id as gamble_id, g.user_id, g.union_name, g.game_date, g.game_time, g.home_team_name, g.away_team_name, g.play_type, g.tradeCoin, g.chose_side, g.handcp, g.odds, g.desc, u.nick_name, u.head as face, u.lv')
                            ->join(' left join qc_front_user AS u ON g.user_id = u.id ')
                            ->where(['g.user_id' => $v, 'g.result' => 0, 'g.create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]]])->order('g.id desc')->find();
                    $one['face'] = frontUserFace($one['face']);
                    $one['union_name'] = explode(',', $one['union_name']);
                    $one['home_team_name'] = explode(',', $one['home_team_name']);
                    $one['away_team_name'] = explode(',', $one['away_team_name']);
                    $one['is_trade'] = M('QuizLog')->where(['game_type' => 1, 'user_id' => $userToken['userid'], 'gamble_id' => $one['gamble_id']])->count() ? 1 : 0;
                    $one['desc'] = (string)$v['desc'];
                    $list[$k] = $one;
                }
            }
        }

        $this->user_id = $user_id;
        $this->list = (array)$list;
        $this->detail = $detail;
        $this->comment = $comment;
        $this->number = M('Comment')->where(['publish_id'=>$id, 'pid'=>0])->count();//评论条数
        $this->display();
    }

    //资讯评论点赞
    public function commentClickLike()
    {
        $userInfo = $this->getInfo();

        $where = ['id'=>$this->param['comment_id']];
        $detail = M('Comment')->field(['like_num','like_user'])->where($where)->find();

        if (!$detail)
            $this->ajaxReturn(4008);

        if (in_array($userInfo['userid'],explode(',', $detail['like_user'])))
            $this->ajaxReturn(4005);

        $data['like_num'] = $detail['like_num'] + 1;
        $data['like_user'] = $detail['like_user'] ? $detail['like_user'].','.$userInfo['userid'] : $userInfo['userid'];

        if (M('Comment')->where($where)->save($data) === false)
            $this->ajaxReturn(4006);

        $this->ajaxReturn(['like_num'=>$detail['like_num'] + 1]);
    }

    //资讯评论列表
    public function commentList()
    {
        $page = $this->param['page'] ?: 1;
        $pageNum = 20;

        $comment = M('Comment c')->field(['c.id','c.user_id','u.nick_name','u.head','c.filter_content content ','c.like_num','c.like_user','c.create_time','c.status'])
                        ->where(['publish_id'=>$this->param['article_id'],'pid'=>0])
                        ->join('LEFT JOIN __FRONT_USER__ u ON u.id = c.user_id')
                        ->order('c.create_time desc')
                        ->page($page.','.$pageNum)
                        ->select();
        $userToken = getUserToken($this->param['userToken']);

        foreach ($comment as $k => $v)
        {
            $comment[$k]['face'] = frontUserFace($v['head']);
            unset($comment[$k]['head']);
            $comment[$k]['is_liked'] = $userToken && in_array($userToken['userid'],explode(',', $v['like_user'])) ? 1 : 0;
            // $comment[$k]['subComment'] = D('Home')->getSubComment($v['id'],$userToken);
            $subComment = M('Comment')->field(['id','user_id','by_user','filter_content content','status'])
                                         ->where(['publish_id'=>$this->param['article_id'],'top_id'=>$v['id']])
                                         ->order('create_time desc')
                                         ->select();

            foreach ($subComment as $kk => $vv)
            {
                $subComment[$kk]['fromUser'] = M('FrontUser')->where(['id'=>$vv['user_id']])->getField('nick_name');
                $subComment[$kk]['toUser']   = M('FrontUser')->where(['id'=>$vv['by_user']])->getField('nick_name');
            }

            $comment[$k]['subComment'] = $subComment;
            unset($comment[$k]['like_user']);
        }
        $this->ajaxReturn(['commentList'=>$comment]);
    }

    //资讯回复评论
    public function replyComment()
    {
        $userInfo = $this->getInfo();

        if (trim($this->param['content']) == '' || !$this->param['article_id'])
            $this->ajaxReturn(4001);

        //30秒限言
        if (S('newsReplyTime:'.$userInfo['userid']))
            $this->ajaxReturn(4017);

        //评论内容：同一个资讯限制重复内容
        $lastContent = M('Comment')->where(['user_id' => $userInfo['userid'], 'publish_id' => $this->param['article_id']])->order('id desc')->getField('content');
        if($lastContent != null && $lastContent == $this->param['content'])
            $this->ajaxReturn(4018);

        //规定时间内回复达到一定次数就暂时禁言
        $forbidTime = C('newsConfig')['forbidTime'];
        $forbidNum = C('newsConfig')['forbidNum'];
        $replyNum  = M('Comment')->where(['user_id' => $userInfo['userid'], 'create_time' => ['between', [time()-$forbidTime, time()]]])->count();
        if($replyNum >= $forbidNum)
            $this->ajaxReturn(4019);

        //查看是否禁言
        $user = M('frontUser')->where(['id'=>$userInfo['userid']])->field('is_gag')->find();

        if($user['is_gag'] == 1)
            $this->ajaxReturn(4012);

        if ($this->param['comment_id'])
        {
            $comment = M('Comment')->field(['pid','top_id','user_id'])->where(['id'=>$this->param['comment_id']])->find();

            //是否已达20评论数上限
            if ( $comment['pid'] != 0)
            {
                $num = M('comment')->where(['pid'=>$comment['pid']])->count();

                if( $num >= 20)
                    $this->ajaxReturn(4013);
            }

            if ($comment['pid'] == 0 && empty($comment['top_id']))
            {
                $pid   = $this->param['comment_id'];
                $topId = $this->param['comment_id'];
            }
            else
            {
                $pid     = $this->param['comment_id'];
                $topId   = $comment['top_id'];
            }

            $byUser = $comment['user_id'];
        }
        else
        {
            $pid    = 0;
            $topId  = null;
            $byUser = null;
        }

        //过滤内容
        $FilterWords = getWebConfig("FilterWords");
        foreach ($FilterWords as $key => $value) {
            $Words[] = '/'.$value.'/';
        }

        $filter_content = preg_replace($Words, '***', $this->param['content']);

        //增加评论
        $insertId = M('Comment')->add([
                'pid'            => $pid,
                'top_id'         => $topId,
                'by_user'        => $byUser,
                'publish_id'     => $this->param['article_id'],
                'user_id'        => $userInfo['userid'],
                'content'        => $this->param['content'],
                'filter_content' => $filter_content,
                'create_time'    => NOW_TIME,
                'platform'       => $userInfo['platform'],
                'reg_ip'         => get_client_ip()
            ]);

        //如果回复评论则返回评论参数
        if($this->param['comment_id']){
            $fromUser = M('FrontUser')->where(['id' => $userInfo['userid']])->getField('nick_name');
            $toUser = M('FrontUser')->where(['id' => $byUser])->getField('nick_name');
        }

        if (!$insertId)
            $this->ajaxReturn(4003);

        //设置评论时间token
        S('newsReplyTime:'.$userInfo['userid'],time(),C('replyTime'));

        if($this->param['comment_id']){
            $this->ajaxReturn(['result' => 1, 'fromUser' => $fromUser, 'toUser' => $toUser , 'top_id' => $topId, 'insertId' => $insertId, 'filter_content' => $filter_content]);
        }else{
            $number = M('Comment')->where(['publish_id'=>$this->param['article_id'], 'pid'=>0])->count();//一级评论条数
            $this->ajaxReturn(['result' => 1, 'number' => $number]);
        }

    }

    //资讯回复举报
    public function reportComment()
    {
        $userInfo = $this->getInfo();
        $comment = M('comment')->where(['id'=>$this->param['comment_id']])->field('user_id,report_user,report_content')->find();

        if($userInfo['userid'] == $comment['user_id'])  //不能举报自己
            $this->ajaxReturn(4009);

        $is_report = explode(",", $comment['report_user']);

        if(in_array($userInfo['userid'], $is_report))   //是否已举报
            $this->ajaxReturn(4010);

        array_push($is_report,$userInfo['userid']);
        $report_user = ltrim(implode(",", $is_report),',');

        $report_content = $this->param['report_content'];
        $report = explode(",", $comment['report_content']);

        if (!in_array($report_content, $report))        //是否已存在的举报类型
        {
            array_push($report,$report_content);
            $report_content = ltrim(implode(",", $report),',');
        }

        $rs = M('comment')->where(['id'=>$this->param['comment_id']])
                          ->save(['report_num'=>['exp','report_num+1'],'report_user'=>$report_user,'report_content'=>$report_content]);

        if($rs === false)
            $this->ajaxReturn(4011);

        $this->ajaxReturn(['result'=>1]);
    }

    //图库频道列表
    public function channelPic()
    {
        $page = $this->param['page'] ?: 1;
        $pageNum = 20;

        $is_in = iosCheck() ? "NOT IN" : "IN"; //是否ios审核
        $galleryClass = M('GalleryClass')->where(['status'=>1,'id'=>[$is_in,[5,17,18,21,30]]])->getField('id',true);

        $galleryList = M('Gallery')->field(['id','title','click_number','like_num','like_user','img_array'])
                       ->where(['status'=>1,'class_id'=>['IN',$galleryClass]])
                       ->order("add_time desc")
                       ->page($page.','.$pageNum)
                       ->select();

        $userToken = getUserToken($this->param['userToken']);

        foreach ($galleryList as $k => $v)
        {
            $img_array = json_decode($v['img_array'],true);
            $imgages = Tool::imagesReplace($img_array[1]);
            unset($galleryList[$k]['img_array']);

            if ($imgages)
            {
                if (strpos($imgages, 'http://') === false)
                {
                    $imgages = C('IMG_SERVER').$imgages;
                }

                $galleryList[$k]['images'] = $imgages;
                $size = explode('X', explode('&size=', $imgages)[1]);
                $galleryList[$k]['width']  = isset($size[0]) ? $size[0] : 0;
                $galleryList[$k]['height'] = isset($size[1]) ? $size[1] : 0;
            }
            else
            {
                $galleryList[$k]['images'] = '';
                $galleryList[$k]['width']  = 0;
                $galleryList[$k]['height'] = 0;
            }

            //是否点赞过
            $galleryList[$k]['is_liked'] = 0;

            if ($userToken && in_array($userToken['userid'],explode(',', $galleryList[$k]['like_user'])))
                $galleryList[$k]['is_liked'] = 1;

            unset($galleryList[$k]['like_user']);
        }
        $this->ajaxReturn(['picList'=>$galleryList]);
    }

    //图库详情
    public function picDetail()
    {
        $id = I('pic_id');
        $where = ['id'=>$id,'status'=>1];
        $detail = M('Gallery')->field(['id','title','like_num','like_user','img_array'])->where($where)->find();
        $img_array = json_decode($detail['img_array'],true);
        foreach ($img_array as $k => $v) {
            $imgArr[] = Tool::imagesReplace($v);
        }
        $detail['imgages'] = $imgArr;
        unset($detail['img_array']);

        $userToken = getUserToken($this->param['userToken']);

        if ($userToken && in_array($userToken['userid'],explode(',', $detail['like_user'])))
            $detail['is_liked'] = 1;
        else
            $detail['is_liked'] = 0;

        M("Gallery")->where($where)->setInc('click_number');
        $this->detail = $detail;
        $this->display();
    }

    //图库点赞
    public function picClikLike()
    {
        $userInfo = $this->getInfo();

        $id = I('pic_id');
        $where = ['id'=>$id,'status'=>1];
        $detail = M('Gallery')->field(['like_num','like_user'])->where($where)->find();

        if (!$detail)
            $this->ajaxReturn(4004);

        if (in_array($userInfo['userid'],explode(',', $detail['like_user'])))
            $this->ajaxReturn(4005);

        $data['like_num'] = $detail['like_num'] + 1;
        $data['like_user'] = $detail['like_user'] ? $detail['like_user'].','.$userInfo['userid'] : $userInfo['userid'];

        if (M('Gallery')->where($where)->save($data) === false)
            $this->ajaxReturn(4006);

        $this->ajaxReturn(['like_num'=>$detail['like_num'] + 1]);
    }

    //视频集锦频道列表
    public function channelVideo()
    {
        $page = $this->param['page'] ?: 1;
        $pageNum = 20;

        $videoList = M('Highlights')->field(['id','title','img','app_url','app_ischain','remark'])
                       ->where(['status'=>1,'app_url'=>['neq','']])
                       ->order("sort desc,add_time desc")
                       ->page($page.','.$pageNum)
                       ->select();

        foreach ($videoList as $k => $v)
        {
            $videoList[$k]['img'] = $v['img'] ? C('IMG_SERVER').$v['img'] : '';
        }

        $this->ajaxReturn(['videoList'=>$videoList]);
    }

    /**
     * 独家解盘/独家猛料
     */
    public function famousArticle()
    {
        $page       = $this->param['page'] ?: 1;
        $pageNum    = $this->param['page'] ? 10 : 30;
        $cacheKey   = MODULE_NAME . '_famousArticle:' . $page . $pageNum;

        if (!$famousArticle = S($cacheKey)) {
            $publishOrder = 'pl.is_channel_push DESC,pl.add_time DESC,pl.id DESC';
            $fields = ['pl.id', 'u.nick_name', 'u.head' => 'face', 'pl.user_id', 'pl.title', 'pl.remark', 'pl.add_time', 'pl.click_number', 'COUNT(c.publish_id)' => 'commentNum'];

            $famousArticle = M('PublishList pl')->field($fields)
                ->where(['pl.class_id' => 10, 'pl.status' => 1, 'pl.user_id' => ['GT', 0]])
                ->join('LEFT JOIN __FRONT_USER__ u ON u.id = pl.user_id')
                ->join('LEFT JOIN __COMMENT__ c ON c.publish_id = pl.id')
                ->group('pl.id')
                ->order($publishOrder)
                ->page($page . ',' . $pageNum)
                ->select();

            foreach ($famousArticle as $k => $v) {
                $famousArticle[$k]['face'] = frontUserFace($v['face']);
                $famousArticle[$k]['nick_name'] = $famousArticle[$k]['nick_name'] == '' ?'':$famousArticle[$k]['nick_name'];
            }

            if ($famousArticle)
                S($cacheKey, $famousArticle,10*60);
        }

        $this->ajaxReturn(['famousArticle' => $famousArticle ?: []]);
    }

    //专题
    public function topic()
    {
        $page = $this->param['page'] ?: 1;
        $pageNum = 20;

        $class_id = M('recommendClass')->where(['sign'=>'appZT'])->getField('id');
        $Recommend = (array)M("Recommend")
                    ->field(['id','title','remark','url','img'])
                    ->where(['class_id'=>$class_id,'status'=>1])
                    ->order("sort desc")
                    ->page($page.','.$pageNum)
                    ->select();

        foreach( $Recommend as $k => $v )
        {
            $Recommend[$k]['img']  = Tool::imagesReplace( $v['img'] );
        }
        $this->ajaxReturn(['topicList'=>$Recommend ?: null]);
    }

    /**
     * 专家资讯推荐列表
     */
    public function famousList(){
        $famous_id = $this->param['famous_id'] ?: 0;
        if(!$famous_id)
            $this->ajaxReturn(101);

        $update_time = $this->param['update_time'] ?: 0;//当前页面最小修改时间

        $where['status'] = 1;
        $where['class_id'] = 10;//名师解盘
        $where['user_id'] = $famous_id;
        $where['id'] = ['gt', 0];
        $fields = ['id', 'short_title as title', 'remark', 'update_time', 'result', 'img'];
        $order = 'update_time desc';

        if ($update_time) {//如果传最小修改时间，则向下取
            $where['update_time'] = ['lt', $update_time];
        }
        $list = M('PublishList')->field($fields)->where($where)->order($order)->limit(20)->select();

        foreach($list as $k => $v){
            $list[$k]['img'] = Tool::imagesReplace($v['img']);
        }

        $this->ajaxReturn(['list' => (array)$list]);
    }

    /**
     * 足球分析
     */
    public function fbAnalysis()
    {
        $pageNum    = $this->param['page'] ? 10 : 30;
        $page       = $this->param['page'] ?: 1;
        $cacheKey   = MODULE_NAME . '_fbAnalysis:' . $page . $pageNum;

        if (!$lists = S($cacheKey))
        {
            //获取资讯分类
            $PublishClass = M('PublishClass')->where("status = 1")->select();

            //板块id
            $classIds = ['6', '54', '55'];
            $searchClassIds = [];

            //根据当前分类id，获取下级分类id
            foreach ($classIds as $v) {
                $searchClassIds = array_merge($searchClassIds, \Think\Tool\Tool::getAllSubCategoriesID($PublishClass, $v));
            }

            $fields  = ['pl.id', 'pl.short_title as title', 'pl.remark', 'pl.img', 'pl.add_time', 'pl.click_number', 'COUNT(c.publish_id)' => 'commentNum'];

            $lists = M('PublishList pl')->field($fields)
                ->where(['pl.class_id' => ['IN', $searchClassIds], 'pl.status' => 1])
                ->join('LEFT JOIN __FRONT_USER__ u ON u.id = pl.user_id')
                ->join('LEFT JOIN __COMMENT__ c ON c.publish_id = pl.id')
                ->group('pl.id')
                ->order('pl.update_time DESC')
                ->page($page . ',' . $pageNum)
                ->select();

            foreach ($lists as $key => $value) {
                $lists[$key]['img'] = $value['img'] !='' ? [\Think\Tool\Tool::imagesReplace($value['img'])]:[];
                $lists[$key]['remark'] = $value['remark'] ?:'';

            }

            if ($lists)
                S($cacheKey, $lists, 60*10);
        }

        $this->ajaxReturn(['fbAnalysis' => $lists ?: []]);
    }

}

 ?>