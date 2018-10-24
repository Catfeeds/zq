<?php
/**
 * 首页其他
 * @author huangjiezhen <418832673@qq.com> 2016.03.21
 */
use Think\Tool\Tool;
class HomeController extends PublicController
{
    //资讯首页
    public function index()
    {
        //只返回资讯列表
        if ($this->param['page'] > 1)
        {
            $articleList = D('Home')->getArticleList((int)$this->param['page'],10);
            $this->ajaxReturn(['articleList'=>$articleList]);
        }

        //滚动的banner
        $banner = Tool::getAdList(1,3,$this->param['platform']) ?: null;
        $banner = D('Home')->getBannerShare($banner);

        //直播和集锦
        $video = D('Home')->getVideo();

        //红人榜
        $hotList = D('Home')->getHotList(-1);

        $blockTime = getBlockTime(1,$gamble=true);

        foreach ($hotList as $k => $v)
        {
            $hotList[$k]['face'] = frontUserFace($v['head']);
            unset($hotList[$k]['head']);
            $hotList[$k]['today_gamble'] = M('Gamble')->where(['user_id'=>$v['user_id'],'create_time'=>['between',[$blockTime['beginTime'],$blockTime['endTime']]]])
                                          ->getField('id') ? 1 : 0;
        }
        //最新竞猜
        $match = D('Home')->getMatch();

        //名师解盘
        $famous = M('FrontUser')->field(['id','nick_name','avder','head'])->where(['is_expert'=>1,'status'=>1])->order('is_recommend desc,sort asc')->limit(3)->select();
        $publishOrder = 'is_channel_push desc,add_time desc,id desc'; //资讯排序

        foreach ($famous as $k => $v)
        {
            $famous[$k]['face'] = frontUserFace($v['head']);
            unset($famous[$k]['head']);
            $article = M('PublishList')->field(['id','title','remark'])->where(['user_id'=>$v['id'],'status'=>1])->order($publishOrder)->find();
            $famous[$k]['article'] = $article;
        }
        //名师解盘轮播
        $famousArticle = M('PublishList')->field(['id','title'])->where(['class_id'=>10,'status'=>1])->order($publishOrder)->limit(10)->select();

        //资讯快报轮播
        $newsArticle = M('PublishList')->field(['id','title']) ->where(['class_id'=>['neq',10],'status'=>1])->order($publishOrder)->limit(10)->select();

        //资讯快报列表
        $articleList = D('Home')->getArticleList(1,10);

        //返回数据
        $this->ajaxReturn([
                'banner'  => $banner,
                'video'   => $video,
                'hotList' => $hotList,
                'match'   => $match,
                'famous'  => $famous,
                'famousArticle' => $famousArticle,
                'newsArticle'   => $newsArticle,
                'articleList'   => $articleList,
            ]);
    }

    //资讯频道列表
    public function channelArticle()
    {
        $page = $this->param['page'] ?: 1;
        $pageNum = 20;

        $articleList = D('Home')->getArticleList($page,$pageNum,$this->param['channel_id']);
        $this->ajaxReturn(['articleList'=>$articleList]);
    }

    //资讯详情页
    public function articleDetail()
    {
        $id = I('aritcle_id');
        $detail = M('PublishList')->field(['id','title','add_time','source','content','label'])->where(['id'=>$id])->find();
        $comment = M('Comment c')->field(['c.id','c.user_id','u.nick_name','u.head','c.filter_content content ','c.like_num','c.create_time','c.status'])
                        ->where(['c.publish_id'=>$id,'c.pid'=>0])
                        ->join('LEFT JOIN __FRONT_USER__ u ON u.id = c.user_id')
                        ->order('c.like_num desc,c.create_time desc')
                        ->limit(5)
                        ->select();

        foreach ($comment as $k => $v)
        {
            $comment[$k]['face'] = frontUserFace($v['head']);
            unset($comment[$k]['head']);
        }

        $this->detail = $detail;
        $this->comment = $comment;
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

        if (!$insertId)
            $this->ajaxReturn(4003);

        $this->ajaxReturn(['result'=>1]);
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

        $galleryClass = M('GalleryClass')->where(['status'=>1,'id'=>['IN',[5,17,18,21,30]]])->getField('id',true);
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

    //名师解盘资讯列表
    public function famousArticle()
    {
        $page = $this->param['page'] ?: 1;
        $pageNum = 20;

        $publishOrder = 'pl.is_channel_push desc,pl.add_time desc,pl.id desc'; //资讯排序
        $famousArticle = M('PublishList pl')->field(['pl.id','u.nick_name','u.head','pl.user_id','pl.title','pl.remark','pl.add_time','pl.click_number'])
                       ->where(['pl.class_id'=>10,'pl.status'=>1])
                       ->join('LEFT JOIN __FRONT_USER__ u ON u.id = pl.user_id')
                       ->order($publishOrder)
                       ->page($page.','.$pageNum)
                       ->select();

        foreach ($famousArticle as $k => $v)
        {
            $famousArticle[$k]['face'] = frontUserFace($v['head']);
            unset($famousArticle[$k]['head']);
            $famousArticle[$k]['commentNum'] = M('Comment')->where(['publish_id'=>$v['id']])->count(); //获取评论数
        }

        $this->ajaxReturn(['famousArticle'=>$famousArticle]);
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

}

 ?>