<?php
/**
 * 首页其他
 * @author huangjiezhen <418832673@qq.com> 2016.03.21
 */
use Think\Tool\Tool;
class HomeController extends PublicController
{
    //V4.0首页
    public function index()
    {
        $game_type = $this->param['game_type'] ?: 1;//默认1足球，2篮球
        $userToken = getUserToken($this->param['userToken']);
        $blockTime = getBlockTime($game_type, $gamble = true);//获取赛程分割日期的区间时间

        //滚动的banner
        if(!$banner = S('HomeBanner'.$this->param['platform'].MODULE_NAME))
        {
            $banner = Tool::getAdList(1,5,$this->param['platform']) ?: [];

            if(!empty($banner)) {
                $banner = D('Home')->getBannerShare($banner);
                S('HomeBanner' . $this->param['platform'] . MODULE_NAME, json_encode($banner), 60 * 5);
            }
        }

        //每日精选（红人榜）
        if(!$hotList = S('HomeHotList'.MODULE_NAME.$game_type)){
            $hotList = D('Home')->getHotList($game_type);

            S('HomeHotList'.MODULE_NAME.$game_type, json_encode($hotList), 60*2);
        }

        //购买体验券入口
        $ticket = M('AdverList')->field('id, title, url, img, module')->where(['class_id' => 27, 'platform' => $this->param['platform'],'status' => 1, 'add_time' => ['elt', NOW_TIME],  'end_time' => ['egt', NOW_TIME]])->select();
        if($ticket){
            foreach($ticket as $k => $v){
                $ticket[$k]['img'] = (string)Tool::imagesReplace($v['img']);
                $ticket[$k]['value'] = $v['url'];
            }
        }

        //“筛选命中率高”、“连胜数多”的用户,缓存时间15分钟
        if(!S('lastUserArr'.MODULE_NAME.$game_type)){
            $indexUserInfo = D('GambleHall')->getIndexUser($game_type);
        }else{
            $indexUserInfo = S('lastUserArr'.MODULE_NAME.$game_type);
        }

        //获取当天推荐信息
        if($indexUserInfo){
            $model = $game_type == 1 ? M('Gamble') : M('Gamblebk');
            foreach ($indexUserInfo as $k => $v) {
                //最新的未结算的推荐，不只是当天
                $play_type = $v['gamble_type'] == 1 ? ($game_type == 1 ? [-1,1] : [1,2,-1,-2]) : [-2,2];
                $one = $model->where(['user_id' => $v['user_id'], 'play_type' => ['in', $play_type], 'result' => 0])->order('id desc')->find();

                if ($one) {//若当天推荐存在
                    $indexUserInfo[$k]['todayHomeName'] = explode(',', $one['home_team_name']);//当天推荐主队名称
                    $indexUserInfo[$k]['todayAwayName'] = explode(',', $one['away_team_name']);//当天推荐客队名称
                } else {
                    $indexUserInfo[$k]['todayHomeName'] = [];//当天推荐主队名称
                    $indexUserInfo[$k]['todayAwayName'] = [];//当天推荐客队名称
                }
                unset($one);
            }
        }

        //返回数据
        $this->ajaxReturn([
            'banner'    => $banner,
            'hotList'   => $hotList,
            'ticket'    => (array)$ticket,
            'indexUser' => $indexUserInfo,
        ]);
    }

    //资讯频道列表
    public function channelArticle()
    {
        $page = $this->param['page'] ?: 1;
        $pageNum = 20;

        $articleList = D('Home')->getArticleList($page,$pageNum,$this->param['channel_id']?:10);

        if($articleList){
            $publishClass = M('PublishClass')->where("status=1")->getField('id, name');
            foreach($articleList as $k => $v){
                if(in_array($v['class_id'], C('classId'))){
                    $img = $detail['content'] = http_to_https('http://www.qqty.com/Public/Home/images/common/loading.png');
                    $articleList[$k]['img']   = [$img];
                }
                $articleList[$k]['source'] = $v['source'].'/'.$publishClass[$v['class_id']];

                //返回game_type
                if(in_array($v['class_id'], C('gameTypeClass'))){
                    $articleList[$k]['game_type'] = '2';
                }else{
                    $articleList[$k]['game_type'] = '1';
                }

                unset($articleList[$k]['class_id']);
            }
        }

        $this->ajaxReturn(['articleList'=>$articleList?:[]]);
    }

    //资讯详情页
    public function articleDetail()
    {
        $id = I('aritcle_id');
        $game_type = $this->param['game_type'] ?: 1;//默认1足球，2篮球
        $blockTime = getBlockTime($game_type, $gamble = true);//获取推荐分割日期的区间时间

        if(!$detail = S('articleDetail'.$id.$game_type.get_client_ip())) {
            $detail = M('PublishList')->field(['id', 'title', 'add_time', 'app_time', 'source', 'content', 'label', 'user_id', 'class_id', 'game_id', 'gamebk_id'])->where(['id' => $id])->find();
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

            if($detail['class_id'] == 10)
                $detail['add_time'] = $detail['app_time'];

            S('articleDetail'.$id.$game_type.get_client_ip(), json_encode($detail), C('newsCacheTime'));
        }

        $user_id   = (int)$detail['user_id'];//名师id
        $userToken = getUserToken($this->param['userToken']);
        $comment   = D('Home')->getCommentList($id, $userToken);
        
        //名师解盘
        if($user_id){
            $list = D('Home')->getMatchGamble(0, $userToken['userid'], $game_type, $user_id);
            $this->nick_name = M('FrontUser')->where(['id' => $user_id])->getField('nick_name');
        }else{//资讯:推荐频道,显示关联比赛推荐，没有则随机显示
            $game_id = $detail['game_id'] ?: $detail['gamebk_id'];
            if($game_id){
                $list = D('Home')->getMatchGamble($game_id, $userToken['userid'], $game_type, $user_id);
            }else{
                $list = [];
            }
        }

        //https兼容
        $detail['content'] = http_to_https($detail['content'], 1);
        $this->user_id = $user_id;
        $this->my_id   = $userToken['userid'];
        $this->list = (array)$list;
        $this->detail = $detail;
        $this->comment = $comment;
        $this->number = M('Comment')->where(['publish_id'=>$id, 'pid'=>0])->count();//评论条数
        $this->game_type = $game_type;
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
        $filter_content = matchFilterWords('FilterWords', $this->param['content'], false, true);

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
        if(empty($this->param['comment_id']) || empty($this->param['report_content']))
            $this->ajaxReturn(101);

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
        $galleryClass = M('GalleryClass')->where(['status'=>1,'id'=>[$is_in,[4,5,17,18,21]]])->getField('id',true);

        $galleryList = M('Gallery')->field(['id','title','click_number','like_num','like_user','img_array'])
                       ->where(['status'=>1,'class_id'=>['IN',$galleryClass]])
                       ->order("field(class_id,17,21,18,5,4), add_time desc")
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
                if (strpos($imgages, SITE_URL) === false)
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

            //增加资讯点击量的默认值
            $galleryList[$k]['click_number'] = addClickConfig(2, $v['class_id'], $v['click_number'], $v['id'], 77);

            unset($galleryList[$k]['like_user']);
        }
        $this->ajaxReturn(['picList'=>$galleryList]);
    }

    //图库详情
    public function picDetail()
    {
        $id = I('pic_id');
        $where = ['id'=>$id, 'status'=>1];
        if(!$detail = S('picDetail'.$id)) {
            $detail = M('Gallery')->field(['id','title','img_array','capture_url'])->where($where)->find();
            $img_array = json_decode($detail['img_array'],true);
            foreach ($img_array as $k => $v) {
                $imgArr[] = Tool::imagesReplace($v);
            }
            $detail['imgages'] = $imgArr;
            unset($detail['img_array']);

            S('picDetail'.$id, json_encode($detail), 3600*3);
        }

        $detail['like_num'] = M('Gallery')->where($where)->getField('like_num');
        $detail['like_user'] = M('Gallery')->where($where)->getField('like_user');
        $userToken = getUserToken($this->param['userToken']);
        if ($userToken && in_array($userToken['userid'], explode(',', $detail['like_user'])))
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

        $videoList = M('Highlights')->field(['id','title','img','app_url','app_ischain','remark','app_isbrowser'])
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
                ->where(['app_time' => ['between', [strtotime('8:30'), strtotime('+1 day', strtotime('8:30'))]]])
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
                    ->field(['id','title','remark','url','img','app_isbrowser'])
                    ->where(['class_id'=>$class_id,'status'=>1])
                    ->order("sort desc")
                    ->page($page.','.$pageNum)
                    ->select();
        foreach( $Recommend as $k => $v )
        {
            $Recommend[$k]['img'] = Tool::imagesReplace( $v['img'] );
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
        $where['app_time'] = ['lt', strtotime('+1 day', strtotime('7:15'))];
        $fields = ['id', 'short_title as title', 'IFNULL(remark, "") as remark', 'app_time as update_time', 'result', 'img'];
        $order = 'app_time desc, update_time desc';

        if ($update_time) {//如果传最小修改时间，则向下取
            $where['app_time'] = ['lt', $update_time];
        }
        $list = M('PublishList')->field($fields)->where($where)->order($order)->limit(20)->select();

        foreach($list as $k => $v){
            $list[$k]['img'] = Tool::imagesReplace($v['img']);
        }

        $this->ajaxReturn(['list' => (array)$list]);
    }

    /**
     * 足球-篮球-分析
     */
    public function newAnalysis()
    {
        $pageNum    = $this->param['page'] ? 10 : 30;
        $page       = $this->param['page'] ?: 1;
        $game_type  = $this->param['game_type'] ?: 1;//默认1足球，2篮球
        $cacheKey   = MODULE_NAME . 'newAnalysis:' . $game_type.$page . $pageNum;

        if (!$lists = S($cacheKey)) {
            //获取资讯分类
            $PublishClass = M('PublishClass')->where("status = 1")->select();

            //板块id
            $classIds = $game_type == 1?['6', '54', '55']:['61'];

            $fields  = ['pl.id', 'pl.class_id', 'pl.source','pl.title', 'pl.remark', 'pl.img', 'pl.app_time as add_time', 'pl.click_number', 'COUNT(c.publish_id)' => 'commentNum'];

            $lists = M('PublishList pl')->field($fields)
                ->where(['pl.class_id' => ['IN', $classIds], 'pl.status' => 1])
                ->join('LEFT JOIN __FRONT_USER__ u ON u.id = pl.user_id')
                ->join('LEFT JOIN __COMMENT__ c ON c.publish_id = pl.id')
                ->where(['pl.app_time' => ['lt', strtotime('+1 day', strtotime('7:15'))]])
                ->group('pl.id')
                ->order('pl.app_time DESC, pl.update_time desc')
                ->page($page . ',' . $pageNum)
                ->select();

            $publishClass = M('PublishClass')->where("status=1")->getField('id, name');
            foreach ($lists as $key => $value) {
                $lists[$key]['img'] = $value['img'] !='' ? [\Think\Tool\Tool::imagesReplace($value['img'])]:[];
                $lists[$key]['remark'] = $value['remark'] ?:'';
                $lists[$key]['source'] = $value['source'].'/'.$publishClass[$value['class_id']];

                //增加资讯点击量的默认值
                $lists[$key]['click_number'] = addClickConfig(1, $value['class_id'], $value['click_number'], $value['id']);

                unset($lists[$key]['class_id']);
            }

            if ($lists)
                S($cacheKey, $lists, 60*10);
        }

        $this->ajaxReturn(['list' => $lists ?: []]);
    }

    /**
     * 超值高手
     */
    public function superMaster()
    {
        $userToken = getUserToken($this->param['userToken']);
        $game_type = $this->param['game_type'] ?: 1;//默认1足球，2篮球
        if (!$totalArr = S('SuperMaster' . $this->param['platform'] . MODULE_NAME.$game_type)) {
            $blockTime  = getBlockTime($game_type, $gamble = true);//获取赛程分割日期的区间时间

            if($game_type == 1){
                $gambleData = D('GambleHall')->superMasterData(1, 15, $blockTime['beginTime'], $blockTime['endTime'], '', $game_type);//亚盘
                $betData    = D('GambleHall')->superMasterData(2, 15, $blockTime['beginTime'], $blockTime['endTime']);//竞彩
            }else{
                $gambleData = D('GambleHall')->superMasterData(1, 30, $blockTime['beginTime'], $blockTime['endTime'], '', $game_type);//亚盘
            }

            //不够就选前一天
            if(count($gambleData) < 15 && in_array($game_type, [1, 2])){
                $lastUser1 = [];
                foreach($gambleData as $uk => $uv){
                    $lastUser1[] = $uv['user_id'];
                }

                $num = $game_type == 1 ? (15 - count($gambleData)) : (30 - count($gambleData));
                $gambleData1 = D('GambleHall')->superMasterData(1, $num, strtotime('-1 day', $blockTime['beginTime']), strtotime('-1 day', $blockTime['endTime']), $lastUser1, $game_type);//亚盘
                $gambleData  = array_merge($gambleData, $gambleData1);
            }

            if(count($betData) < 15 && $game_type == 1){
                $lastUser2 = [];
                foreach($betData as $bk => $bv){
                    $lastUser12[] = $bv['user_id'];
                }

                $num = 15 - count($betData);
                $betData1 = D('GambleHall')->superMasterData(2, $num, strtotime('-1 day', $blockTime['beginTime']), strtotime('-1 day', $blockTime['endTime']), $lastUser2);//亚盘
                $betData  = array_merge($betData, $betData1);
            }

            //重新排序
            $totalArr  = $game_type == 1 ? array_merge($gambleData, $betData) : $gambleData;

            if ($totalArr) {
                $today  = $curr_victs1 = $tenGambleRate1 = $weekPercnet1 = $tradeCoin1 = $timeSort1 = array();
                $before = $curr_victs2 = $tenGambleRate2 = $weekPercnet2 = $tradeCoin2 = $timeSort2 = array();

                foreach ($totalArr as $k => $v) {
                    //当天的分组，未结算
                    if($v['result'] == 0){
                        $curr_victs1[]    = $v['curr_victs'];
                        $tenGambleRate1[] = $v['tenGambleRate'];
                        $weekPercnet1[]   = $v['weekPercnet'];
                        $tradeCoin1[]     = $v['tradeCoin'];
                        $timeSort1[]      = $v['create_time'];
                        $today[]          = $v;
                    }else{
                        $curr_victs2[]    = $v['curr_victs'];
                        $tenGambleRate2[] = $v['tenGambleRate'];
                        $weekPercnet2[]   = $v['weekPercnet'];
                        $tradeCoin2[]     = $v['tradeCoin'];
                        $timeSort2[]      = $v['create_time'];
                        $before[]         = $v;
                    }

                    unset($totalArr[$k]['tenGambleRate']);
                }

                //排序，分组排序，当天时间优先，按连胜 > 10中几 > 周胜率 > 价格 > 发布时间
                array_multisort($curr_victs1, SORT_DESC, $tenGambleRate1, SORT_DESC, $weekPercnet1, SORT_DESC, $tradeCoin1, SORT_DESC, $timeSort1, SORT_DESC, $today);
                array_multisort($curr_victs2, SORT_DESC, $tenGambleRate2, SORT_DESC, $weekPercnet2, SORT_DESC, $tradeCoin2, SORT_DESC, $timeSort2, SORT_DESC, $before);

                //合并
                $totalArr = array_merge($today, $before);
                unset($curr_victs1, $tenGambleRate1, $weekPercnet1, $tradeCoin1, $timeSort1, $today);
                unset($curr_victs2, $tenGambleRate2, $weekPercnet2, $tradeCoin2, $timeSort2, $before);
            }

            S('SuperMaster' . $this->param['platform'] . MODULE_NAME.$game_type, $totalArr, 60*2);
        }

        if($totalArr) {
            foreach ($totalArr as $k => $v) {
                if ($userToken) {
                    $totalArr[$k]['is_trade'] = D('Common')->getTradeLog($v['gamble_id'], $userToken['userid'], $game_type);//是否已查看购买过
                } else {//无登录则全部没有购买
                    $totalArr[$k]['is_trade'] = 0;
                }
            }
        }

        $this->ajaxReturn(['list' => $totalArr ?: []]);
    }

    /**
     * 统计全部用户的5中4
     */
    public function countAllFiveGamble(){
        set_time_limit(0);
        $game_type = $this->param['game_type'] ?: 1;//默认1足球，2篮球

        if($this->param['begin'] != 'go')
            return false;

        $userArr = M('FrontUser')->master(true)->field('id, gamble_num, bet_num, bk_gamble_num')->select();
        $newArr  = array_chunk($userArr, 100);

        M()->startTrans();
        $num1 = $num2 = 0;
        foreach($newArr as $nk => $nv) {
            $gambleUser = $betUser = [];
            foreach ($nv as $uk => $uv) {
                $where['user_id'] = $uv['id'];
                //过滤掉未出结果的
                $where['result'] = $game_type == 1 ? ['in', [1, 0.5, 2, -1, -0.5]] : ['in', [-1,1]];

                if($game_type == 1) {
                    //亚盘近5场比赛结果
                    $where['play_type'] = ['in', [1, -1]];//亚盘
                    $gambleRes = D('Home')->fiveGameRate($where);

                    //竞彩近5场比赛结果
                    $where['play_type'] = ['in', [2, -2]];
                    $betRes = D('Home')->fiveGameRate($where);

                    //要满足5条竞猜，统计的结果不等于当前的结果
                    if ($gambleRes['num'] == 5 && $gambleRes['win'] != $uv['gamble_num']) {
                        $gambleUser[$uv['id']] = $gambleRes['win'];
                    }

                    if ($betRes['num'] == 5 && $betRes['win'] != $uv['bet_num']) {
                        $betUser[$uv['id']] = $betRes['win'];
                    }
                }else{
                    $where['play_type'] = ['in', [1, -1, -2, 2]];//亚盘
                    $gambleRes = D('Home')->fiveGameRate($where, $game_type);

                    //要满足5条竞猜，统计的结果不等于当前的结果
                    if ($gambleRes['num'] == 5 && $gambleRes['win'] != $uv['bk_gamble_num']) {
                        $gambleUser[$uv['id']] = $gambleRes['win'];
                    }
                }
            }

            if($game_type == 1) {
                $sql1 = D('Home')->assembleSql('qc_front_user', 'gamble_num', $gambleUser);
                $sql2 = D('Home')->assembleSql('qc_front_user', 'bet_num', $betUser);

                $res1 = M()->execute($sql1);
                $res2 = M()->execute($sql2);
            }else{
                $sql1 = D('Home')->assembleSql('qc_front_user', 'bk_gamble_num', $gambleUser);

                $res1 = M()->execute($sql1);
                $res2 = true;
            }

            //两种修改互不干扰
            if ($res1 === false && $res2 === false) {
                M()->rollback();
            } else {
                M()->commit();
                $num1 += $res1;
                $num2 += $res2;
            }
        }

        var_dump($num1, $num2);die;
    }


    /**
     * 高命中，连胜多：换一批
     */
    public function getIndexMore(){
        $game_type = $this->param['game_type'] ?: 1;//默认1足球，2篮球
        $user1     = $this->param['user1'] ?: 0;
        $gamble_type1 = $this->param['gamble_type1'] ?: 0;

        $res = D('GambleHall')->getIndexUser($game_type, $user1, $gamble_type1);

        if($res) {
            $model = $game_type == 1 ? M('Gamble') : M('Gamblebk');
            foreach ($res as $k => $v) {
                //最新的未结算的推荐，不只是当天
                $play_type = $v['gamble_type'] == 1 ? ($game_type == 1 ? [-1, 1] : [1, 2, -1, -2]) : [-2, 2];
                $one = $model->where(['user_id' => $v['user_id'], 'play_type' => ['in', $play_type], 'result' => 0])->order('id desc')->find();

                if ($one) {//若当天推荐存在
                    $res[$k]['todayHomeName'] = explode(',', $one['home_team_name']);//当天推荐主队名称
                    $res[$k]['todayAwayName'] = explode(',', $one['away_team_name']);//当天推荐客队名称
                } else {
                    $res[$k]['todayHomeName'] = [];//当天推荐主队名称
                    $res[$k]['todayAwayName'] = [];//当天推荐客队名称
                }
                unset($one);
            }
        }

        $this->ajaxReturn(['list' => $res ?: []]);
    }

    /**
     * 首页高手推荐
     */
    public function masterGamble(){
        $game_type = $this->param['game_type'] ?: 1;//默认1足球，2篮球
        $model = $game_type == 1 ? M('Gamble') : M('Gamblebk');
        $userToken = getUserToken($this->param['userToken']);

        //高手推荐
        if(!$masterGamble = S('indexMasterGamble' . MODULE_NAME.$game_type)){
            $masterGamble = D('GambleHall')->masterGamble($game_type);
            S('indexMasterGamble' . MODULE_NAME.$game_type, json_encode($masterGamble), 60 * 5);
        }

        //购买情况需要实时
        if($masterGamble){
            $gambleID = [];
            foreach($masterGamble as $k => $v){
                $gambleID[] = $v['gamble_id'];
            }
            //是否已查看购买过
            if($userToken){
                $tradeArr = M('QuizLog')->where(['game_type'=>$game_type,'user_id'=>$userToken['userid'],'gamble_id'=>['in',$gambleID]])->getField('gamble_id',true);
            }

            //一次获取全部的数据
            $quiz_number = M('Gamble')->where(['id' => ['in', $gambleID]])->field('(quiz_number + extra_number) as quiz_number')->order('field(id,'.implode(',', $gambleID).')')->select();

            foreach($masterGamble as $k => $v){
                $masterGamble[$k]['quiz_number'] = D('Common')->getQuizNumber($quiz_number[$k]['quiz_number']);
                $masterGamble[$k]['is_trade'] = in_array($v['gamble_id'], $tradeArr) ? 1 : 0;
            }
        }

        $this->ajaxReturn(['masterGamble' => (array)$masterGamble]);
    }

}

 ?>