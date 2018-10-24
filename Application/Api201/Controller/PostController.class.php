<?php

/**
 * 帖子控制器
 * Created by PhpStorm.
 * User: zhangwen
 * Date: 2016/6/27
 * Time: 16:17
 */
use Think\Tool\Tool;

class PostController extends PublicController
{

    /**
     * 帖子列表
     */
    public function index()
    {

    }

    /**
     * 帖子或资讯收藏
     */
    public function postCollection()
    {
        if (empty($this->param['userToken']) || empty($this->param['collection_id']) || empty($this->param['collection_type']))
            $this->ajaxReturn(101);

        $userToken = getUserToken($this->param['userToken']);
        $type = isset($this->param['type']) ? $this->param['type'] : 0;//0:收藏；1：取消收藏
        $collection_id = $this->param['collection_id'];
        $collection_type = $this->param['collection_type'];

        if ($type == 1) {
            //直接根据收藏记录id删除会更好
            $where['collection_id'] = $collection_id;
            $where['user_id'] = $userToken['userid'];
            $where['collection_type'] = $collection_type;
            M('CommunityCollection')->where($where)->delete();

        } else {
            $data['user_id'] = $userToken['userid'];
            $data['collection_id'] = $collection_id;
            $data['collection_type'] = $collection_type;
            $data['create_time'] = time();

            if(M('CommunityCollection')->where(['user_id' => $userToken['userid'], 'collection_id' => $collection_id, 'collection_type' => $collection_type])->count())
                $this->ajaxReturn(6015);

            $res = M('CommunityCollection')->add($data);
            if ($res === false)
                $this->ajaxReturn(6007);
        }

        $this->ajaxReturn(['result' => 1]);
    }

    /**
     * 取消收藏
     */
    public function delCollectionById()
    {
        $userToken = getUserToken($this->param['userToken']);

        $map['id'] = array('in', $this->param['collection_id']);
        $map['user_id'] = array('eq', $userToken['userid']);//多加一层条件

        $m = M('CommunityCollection');
        $res = $m->where($map)->delete();
        if ($res === false)
            $this->ajaxReturn(6012);

        $this->ajaxReturn(['result' => 1]);
    }

    /**
     * 显示我收藏的帖子和咨询列表
     */
    public function collectionList()
    {

        if (!isset($this->param['actionType']))
            $this->ajaxReturn(101);

        $cType = intval($this->param['actionType']);

        $collectionModel = M('communityCollection');
        $userInfo = getUserToken($this->param['userToken']);
        $collections = array();
        $lists = array();

        $map['C.user_id'] = $userInfo['userid'];
        $map['C.collection_type'] = $cType;
        $map['C.status'] = 1;
        $limit = 20;

        $page = !isset($this->param['page']) || $this->param['page'] == 0 ? $page = 1 : $this->param['page'];
        $startRow = ($page - 1) * $limit;

        switch ($cType) {

            case 1://资讯

                $searchFields = array(
                    'C.id' => 'collection_id',
                    'P.id' => 'news_id',
                    'P.short_title' => 'news_title',
                    'P.remark' => 'news_content',
                    'P.content' => 'content',
                    'P.update_time' => 'news_update_time',
                    'C.create_time' => 'collection_time'
                );

                $map['P.id'] = array('NEQ', '');
                $results = $collectionModel
                    ->alias('C')
                    ->field($searchFields)
                    ->join('LEFT JOIN qc_publish_list P ON C.collection_id = P.id')
                    ->order('C.create_time DESC')
                    ->where($map)
                    ->limit($startRow, $limit)->select();


                foreach ($results as $key => $row) {
                    $results[$key]['news_title'] = (string)$row['news_title'];
                    $results[$key]['news_content'] = (string)$row['news_content'];
                    $results[$key]['news_id'] = (string)$row['news_id'];
                    $results[$key]['collection_time'] = (string)$row['collection_time'];
                    $results[$key]['news_update_time'] = (string)$row['news_update_time'];
                }

                if (!empty($results)) {
                    $lists = D('communityPosts')->getNewsLists($results);
                }

                $collections['nLists'] = $lists;
                break;

            case 2://帖子

                $searchFields = array(
                    'P.user_id' => 'user_id',
                    'U.nick_name' => 'user_nick_name',
                    'U.head' => 'user_head_img',
                    'Q.name' => 'quan_name',
                    'Q.head_img' => 'quan_head_img',
                    'C.id' => 'collection_id',
                    'P.id' => 'post_id',
                    'P.base64_title' => 'post_title',
                    'P.base64_content' => 'post_content',
                    'P.img' => 'post_img',
                    'count(CC.post_id)' => 'post_comment_num',
                    'P.like_num' => 'post_like_num',
                    'P.create_time' => 'post_create_time',
                    'C.create_time' => 'collection_time'
                );

                $map['P.id'] = array('NEQ', '');
                $results = $collectionModel
                    ->alias('C')
                    ->field($searchFields)
                    ->join('LEFT JOIN qc_community_posts P ON P.id = C.collection_id')
                    ->join('LEFT JOIN qc_community Q ON P.cid = Q.id')
                    ->join('LEFT JOIN qc_front_user U ON P.user_id = U.id')
                    ->join('LEFT JOIN qc_community_comment CC ON P.id = CC.post_id')
                    ->where($map)
                    ->group('P.id')
                    ->order('C.create_time DESC')
                    ->limit($startRow, $limit)->select();

                if (empty($results))
                    $results = array();

                foreach ($results as $key => $row) {
                    $results[$key]['user_head_img'] = frontUserFace($row['user_head_img']);
                    $results[$key]['user_nick_name'] = (string)$row['user_nick_name'];
                    $results[$key]['quan_name'] = (string)$row['quan_name'];
                    $results[$key]['quan_head_img'] = (string)\Think\Tool\Tool::imagesReplace($row['quan_head_img']);
                    $results[$key]['post_title'] = $row['post_title'] == '' ? '' : base64_decode($row['post_title']);
                    $results[$key]['post_content'] = $row['post_content'] == '' ? '' : base64_decode($row['post_content']);
                    $post_imgs = empty($row['post_img']) ? array() : json_decode($row['post_img'], 1);

                    foreach ($post_imgs as $k => $sourceImg) {
                        $thumbImg = implode(C('thumbImgSize') . '.', explode('.', $sourceImg));
                        $post_imgs[$k] = [
                            'normal' => C('IMG_SERVER') . $sourceImg,
                            'thumb' => C('IMG_SERVER') . $thumbImg
                        ];
                    }

                    $results[$key]['post_img'] = $post_imgs;
                }

                $collections['pLists'] = $results;
                break;
            default;
        }

        $collections['imgServer'] = C('IMG_SERVER');
        $this->ajaxReturn($collections);
    }

    /**
     * 取消收藏
     */
    public function cancelCollection()
    {
        if (!isset($this->param['collectionId'])) $this->ajaxReturn(101);

        $collectionModel = M('communityCollection');
        $userInfo = getUserToken($this->param['userToken']);
        $map['user_id'] = $userInfo['userid'];
        $map['id'] = $this->param['collectionId'];

        $res = $collectionModel->where($map)->delete();
        if (!$res) {
            $this->ajaxReturn(6012);
        } else {
            $this->ajaxReturn(array('result' => 1));
        }
    }

    /**
     * 帖子或评论举报
     */
    public function reportPost()
    {
        $userToken = getUserToken($this->param['userToken']);
        $type = isset($this->param['type']) ? (int)$this->param['type'] : 1;//1:贴子；2：评论
        $tableName = ($type == 1) ? 'CommunityPosts' : 'CommunityComment';

        $comment = M($tableName)->where(['id' => $this->param['post_id']])->field('user_id,report_user,report_content')->find();

        if ($userToken['userid'] == $comment['user_id'] && $type == 2)  //不能举报自己
            $this->ajaxReturn(4009);

        $is_report = explode(",", $comment['report_user']);

        if (in_array($userToken['userid'], $is_report))   //是否已举报
            $this->ajaxReturn(4015);

        array_push($is_report, $userToken['userid']);
        $report_user = ltrim(implode(",", $is_report), ',');

        $report_content = $this->param['report_content'];
        $report = explode(",", $comment['report_content']);

        if (!in_array($report_content, $report))        //是否已存在的举报类型
        {
            array_push($report, $report_content);
            $report_content = ltrim(implode(",", $report), ',');
        }

        $rs = M($tableName)->where(['id' => $this->param['post_id']])
            ->save(['report_num' => ['exp', 'report_num+1'], 'report_user' => $report_user, 'report_content' => $report_content]);

        if ($rs === false)
            $this->ajaxReturn(4011);

        $this->ajaxReturn(['result' => 1]);
    }

    /**
     * 帖子或评论点赞
     */
    public function postClickLike()
    {
        $userInfo = getUserToken($this->param['userToken']);
        $type = isset($this->param['type']) ? (int)$this->param['type'] : 1;//1:贴子；2：评论
        $tableName = ($type == 1) ? 'CommunityPosts' : 'CommunityComment';

        $where = ['id' => $this->param['post_id']];
        $detail = M($tableName)->field(['like_num', 'like_user'])->where($where)->find();

        if (!$detail) {
            $code = ($type == 1) ? 4016 : 4008;
            $this->ajaxReturn($code);
        }

        if (in_array($userInfo['userid'], explode(',', $detail['like_user'])))
            $this->ajaxReturn(4005);

        $data['like_num'] = $detail['like_num'] + 1;
        $data['like_user'] = $detail['like_user'] ? $detail['like_user'] . ',' . $userInfo['userid'] : $userInfo['userid'];

        if (M($tableName)->where($where)->save($data) === false)
            $this->ajaxReturn(4006);

        $this->ajaxReturn(['like_num' => $detail['like_num'] + 1]);
    }

    /**
     * 发表帖子
     */
    public function sendPost()
    {
        $userInfo = $this->getInfo($this->param['userToken']);

        if (!is_array($userInfo))
            $this->ajaxReturn(1001);

        if (mb_strlen($this->param['title']) > 20 || strlen($this->param['title']) <= 0)
            $this->ajaxReturn(6008);

        if (mb_strlen($this->param['content']) > 10000)
            $this->ajaxReturn(6018);

        if (!$this->param['content'] && array_sum($_FILES['pics']['size']) <= 0)
            $this->ajaxReturn(6009);

        if (!$this->param['cid'] || !$userInfo)
            $this->ajaxReturn(101);

        $res = matchFilterWords('FilterWords', $this->param['content'] . $this->param['title'],1);
        if ($res !== true)
            $this->ajaxReturn(1063, '含有非法敏感词:' . $res);
        
        $m = M('FrontUser');
        $cStatus = $m->field('community_status status')->where(array('id' => $userInfo['userid']))->find();
        if ($cStatus['status'] == 2 || $cStatus['status'] > time())
            $this->ajaxReturn(6014);

        //规定时间内发帖达到一定次数就暂时禁言
        $forbidTime = C('postConfig')['forbidTime'];
        $forbidNum = C('postConfig')['forbidNum'];
        $postNum  = M('CommunityPosts')->where(['user_id' => $userInfo['userid'], 'create_time' => ['between', [time()-$forbidTime, time()]]])->count();
        if($postNum >= $forbidNum)
            $this->ajaxReturn(4019);

        $postModel = D('CommunityPosts');
        $addArrs = array();

        //生成帖子记录
        $addArrs['cid'] = $this->param['cid'];
        $addArrs['user_id'] = $userInfo['userid'];
        $addArrs['base64_title'] = base64_encode($this->param['title']);
        $addArrs['base64_content'] = base64_encode($this->param['content']);
        $addArrs['status'] = 1;
        $addArrs['create_time'] = NOW_TIME;
        $addArrs['lastreply_time'] = NOW_TIME;

        $post_id = $postModel->add($addArrs);

        if (!$post_id)
            $this->ajaxReturn(6011);

        M('Community')->where('id=' . $this->param['cid'])->setInc('post_num', 1); // 圈子帖子数

        //图片上传
        if (array_sum($_FILES['pics']['size']) > 0) {
            $re = $postModel->uploadPostImg($post_id);

            if (!$re['status'])
                $this->ajaxReturn(6010);

            $imgData['img'] = str_replace('\\', '', json_encode($re['data']));
            $postModel->where('id=' . $post_id)->save($imgData);
        }

        $this->ajaxReturn(['result' => 1]);

    }

    /**
     * 我的发帖
     */
    public function MyPosts()
    {
        $userInfo = getUserToken($this->param['userToken']);
        $postModel = D('CommunityPosts');
        $lists = $postModel->getMyPosts($userInfo['userid'], $this->param['page']);
        $posts['lists'] = $lists ? $lists : array();

        $this->ajaxReturn($posts);
    }

    /**
     * 好友帖子动态列表
     */
    public function myFollowPosts()
    {
        $userInfo = getUserToken($this->param['userToken']);
        $followId = M('FollowUser')->where(['user_id' => $userInfo['userid']])->getField('follow_id', true);
        
        if (empty($followId))
            $this->ajaxReturn(['lists'=>[]]);

        $postModel = D('CommunityPosts');
        $lists = $postModel->getMyFllowPosts($followId, $this->param['page']);

        $posts['lists'] = $lists ? $lists : array();

        $this->ajaxReturn($posts);
    }

    /**
     * 帖子详情
     */
    public function detail()
    {
        $look_id = I('look_id', 0, 'intval');//查看回复id
        $userToken = getUserToken($this->param['userToken']);

        if(!$detail = S('postDetail'.$this->param['post_id'].MODULE_NAME)) {
            $where = ['p.id' => $this->param['post_id']];
            $detail = (array)M('CommunityPosts p')->field('p.id, p.user_id, p.base64_title as title, p.base64_content as content, p.img, p.create_time, u.nick_name, u.head as face')
                    ->join(' LEFT JOIN qc_community c on p.cid= c.id ')
                    ->join(' LEFT JOIN qc_front_user u on p.user_id = u.id ')
                    ->where($where)
                    ->group('p.id')->find();

            $detail['title'] = (string)base64_decode($detail['title']);
            $detail['content'] = (string)base64_decode($detail['content']);
            $detail['face'] = frontUserFace($detail['face']);
            $detail['img'] = json_decode($detail['img'], true);

            foreach ($detail['img'] as $k => $v) {
                $detail['img'][$k] = Tool::imagesReplace($v);
            }
            S('postDetail'.$this->param['post_id'].MODULE_NAME, json_encode($detail), 60*60);
        }
        $detail['like_num'] = M('CommunityPosts p')->where(['p.id' => $this->param['post_id']])->getField('p.like_num');
        $detail['num'] = M('CommunityComment cc')->where(['cc.post_id' => $this->param['post_id'], 'cc.pid' => 0])->count();
        $detail['like_user'] = M('CommunityPosts p')->where(['p.id' => $this->param['post_id']])->getField('p.like_user');
        $detail['is_liked'] = $userToken && in_array($userToken['userid'], explode(',', $detail['like_user'])) ? 1 : 0;

        $page = $this->param['page'] ?: 1;
        $pageNum = 20;
        $fields = ['c.id', 'c.user_id', 'u.nick_name', 'u.head as face', 'c.filter_content content ', 'c.like_num', 'c.like_user', 'c.create_time', 'c.status'];
        if ($look_id) {
            //评论的顶级id
            $top_id = M('CommunityComment')->where(['id' => $look_id])->getField('top_id');
            $look_id = ($top_id == 0) ? $look_id : $top_id;
            $comment = M('CommunityComment c')->field($fields)
                        ->where(['c.post_id' => $this->param['post_id'], 'c.pid' => 0, 'c.id' => ['gt', $look_id - 5]])
                        ->join('LEFT JOIN __FRONT_USER__ u ON u.id = c.user_id')
                        ->order('c.create_time desc')
                        ->select();
            $startNum = M('CommunityComment c')->where(['c.post_id' => $this->param['post_id'], 'c.pid' => 0, 'c.id' => ['gt', $look_id - 5]])
                        ->join('LEFT JOIN __FRONT_USER__ u ON u.id = c.user_id')->count();
        } else {
            $comment = M('CommunityComment c')->field($fields)
                        ->where(['c.post_id' => $this->param['post_id'], 'c.pid' => 0])
                        ->join('LEFT JOIN __FRONT_USER__ u ON u.id = c.user_id')
                        ->order('c.create_time desc')
                        ->page($page . ',' . $pageNum)
                        ->select();
            $startNum = M('CommunityComment c')->where(['c.post_id' => $this->param['post_id'], 'c.pid' => 0])
                        ->join('LEFT JOIN __FRONT_USER__ u ON u.id = c.user_id')->count();
        }

        $floor_num = $startNum - $pageNum;//下一页楼数开始
        foreach ($comment as $k => $v) {
            $comment[$k]['floor_num'] = $startNum;
            $startNum--;
            $comment[$k]['content'] = ($v['status'] == 0) ? '该回贴已被管理员屏蔽' : (string)base64_decode($v['content']);
            $comment[$k]['face'] = frontUserFace($v['face']);
            $comment[$k]['is_liked'] = $userToken && in_array($userToken['userid'], explode(',', $v['like_user'])) ? 1 : 0;
            $subComment = M('CommunityComment')->field(['id', 'user_id', 'by_user', 'like_num', 'filter_content content', 'status'])
                        ->where(['post_id' => $this->param['post_id'], 'top_id' => $v['id']])
                        ->order('create_time desc')
                        ->select();

            foreach ($subComment as $kk => $vv) {
                $subComment[$kk]['content'] = ($vv['status'] == 0) ? '该回贴已被管理员屏蔽' : (string)base64_decode($vv['content']);
                $subComment[$kk]['fromUser'] = M('FrontUser')->where(['id' => $vv['user_id']])->getField('nick_name');
                $subComment[$kk]['toUser'] = M('FrontUser')->where(['id' => $vv['by_user']])->getField('nick_name');
            }

            $comment[$k]['subComment'] = $subComment;
            unset($comment[$k]['like_user']);
        }

        $this->floor_num = $floor_num;
        $this->post_id = $this->param['post_id'];
        $this->userToken = $this->param['userToken'];
        $this->info = $detail;
        $this->commentList = $comment;
        $this->look_id = $look_id;
        $this->display();
    }

    /**
     * 贴子回复
     */
    public function reply()
    {
        $userInfo = $this->getInfo();

        if (trim($this->param['content']) == '' || !$this->param['post_id'])
            $this->ajaxReturn(4001);

        //30秒限言
        if (S('postReplyTime:' . $userInfo['userid']))
            $this->ajaxReturn(4017);

        //评论内容：同一个贴子限制重复内容
        $lastContent = M('CommunityComment')->where(['user_id' => $userInfo['userid'], 'post_id' => $this->param['post_id']])->order('id desc')->getField('content');
        if($lastContent != null && base64_decode($lastContent) == $this->param['content'])
            $this->ajaxReturn(4018);

        //规定时间内回复达到一定次数就暂时禁言
        $forbidTime = C('replyConfig')['forbidTime'];
        $forbidNum = C('replyConfig')['forbidNum'];
        $replyNum  = M('CommunityComment')->where(['user_id' => $userInfo['userid'], 'create_time' => ['between', [time()-$forbidTime, time()]]])->count();
        if($replyNum >= $forbidNum)
            $this->ajaxReturn(4019);

        //查看是否禁言，2为永久禁言，或看时间戳是否大于现在时间
        $user = M('frontUser')->where(['id' => $userInfo['userid']])->field('community_status')->find();
        if ($user['community_status'] == 2 || $user['community_status'] > time())
            $this->ajaxReturn(4012);

        //如果有评论id，则是回复评论
        if ($this->param['comment_id']) {
            $comment = M('CommunityComment')->field(['pid', 'top_id', 'user_id'])->where(['id' => $this->param['comment_id']])->find();
            //如果上级id和顶级id为0，则是第一条回复
            if ($comment['pid'] == 0 && $comment['top_id'] == null) {
                $pid = $this->param['comment_id'];
                $topId = $this->param['comment_id'];
            } else {
                $pid = $this->param['comment_id'];
                $topId = $comment['top_id'];
            }

            $byUser = $comment['user_id'];
        } else {
            $pid = 0;
            $topId = null;
            $byUser = null;
        }

        //过滤内容
        $FilterWords = getWebConfig("FilterWords");
        foreach ($FilterWords as $key => $value) {
            $Words[] = '/' . $value . '/';
        }

        $filter_content = preg_replace($Words, '***', $this->param['content']);

        //增加评论
        $insertId = M('CommunityComment')->add([
            'pid' => $pid,
            'top_id' => $topId,
            'by_user' => $byUser,
            'post_id' => $this->param['post_id'],
            'user_id' => $userInfo['userid'],
            'content' => base64_encode($this->param['content']),
            'filter_content' => base64_encode($filter_content),
            'create_time' => NOW_TIME,
            'platform' => $this->userInfo['platform'],
            'reg_ip' => get_client_ip()
        ]);

        //修改帖子回复时间和帖子评论数加1
        M('CommunityPosts')->where(['id' => $this->param['post_id']])->save(['lastreply_time' => NOW_TIME, 'comment_num' => ['exp', 'comment_num+1']]);

        //如果回复评论则返回评论参数
        if ($this->param['comment_id']) {
            $fromUser = M('FrontUser')->where(['id' => $userInfo['userid']])->getField('nick_name');
            $toUser = M('FrontUser')->where(['id' => $byUser])->getField('nick_name');
        }

        if (!$insertId)
            $this->ajaxReturn(4003);

        //设置评论时间token
        S('postReplyTime:' . $userInfo['userid'], time(), C('replyTime'));

        if ($this->param['comment_id']) {
            $this->ajaxReturn(['result' => 1, 'fromUser' => $fromUser, 'toUser' => $toUser, 'top_id' => $topId, 'insertId' => $insertId, 'filter_content' => $filter_content]);
        } else {
            $this->ajaxReturn(['result' => 1]);
        }
    }

    /**
     * 我的回复
     */
    public function myReply()
    {
        $id = $this->param['comment_id'] ? (int)$this->param['comment_id'] : 0;
        $pageNum = 20;

        $userToken = getUserToken($this->param['userToken']);

        if ($id)
            $where['cc.id'] = ['lt', $id];

        $where['cc.status'] = 1;
        $where['p.status'] = 1;
        $where['c.status'] = 1;
        $where['_string'] = "(cc.user_id = {$userToken['userid']}) or (cc.by_user = {$userToken['userid']}) or (p.user_id = {$userToken['userid']})";
        $data = M('CommunityComment cc')->field('u.id, u.nick_name as name, u.head as face, fu.id as to_id, fu.nick_name as to_name, c.`name` as community_name, cc.id as comment_id, cc.pid, cc.post_id, cc.create_time, cc.filter_content as content, p.base64_title as post_title, p.base64_content as post_content')
            ->join(' LEFT JOIN qc_front_user u on cc.user_id = u.id ')
            ->join(' LEFT JOIN qc_front_user fu on cc.by_user = fu.id ')
            ->join(' LEFT JOIN qc_community_posts p on cc.post_id = p.id ')
            ->join(' LEFT JOIN qc_community c on p.cid= c.id ')
            ->where($where)->group('cc.id')->order('cc.id DESC')
            ->limit($pageNum)
            ->select();

        if ($data) {
            foreach ($data as $k => $v) {
                $data[$k]['face'] = frontUserFace($v['face']);
                $data[$k]['content'] = $v['content'] ? (string)base64_decode($v['content']) : '';
                $data[$k]['post_title'] = $v['post_title'] ? (string)base64_decode($v['post_title']) : '';
                $data[$k]['post_content'] = $v['post_content'] ? (string)base64_decode($v['post_content']) : '';


                //如pid存在则是回复评论，没有就算回复帖子
                if ($v['pid']) {
                    $data[$k]['last_content'] = (string)base64_decode(M('CommunityComment')->where(['id' => $v['pid']])->getField('filter_content'));
                    $data[$k]['to_name'] = ($v['to_id'] == $userToken['userid']) ? '我的回贴' : $v['to_name'] . '的回贴';
                } else {
                    $info = M('CommunityPosts p')->field('p.user_id, p.base64_title as title, u.nick_name')
                        ->join(' LEFT JOIN qc_front_user u on p.user_id = u.id ')
                        ->where(['p.id' => $v['post_id']])->find();
                    $data[$k]['last_content'] = $info['title'] ? (string)base64_decode($info['title']) : '';
                    $data[$k]['to_name'] = ($info['user_id'] == $userToken['userid']) ? '我的贴子' : $info['nick_name'] . '的贴子';
                }
                $data[$k]['to_id'] = (string)$v['to_id'];
                unset($data[$k]['pid']);
            }
        } else {
            $data = array();
        }

        $this->ajaxReturn(['list' => $data]);
    }

    /**
     *  加载更多帖子评论
     */
    public function loadMore()
    {
        $p = isset($_POST['k']) ? intval(trim($_POST['k'])) : 0;
        $post_id = I('post_id');
        $userToken = getUserToken(I('userToken'));
        $floor_num = I('floor_num');

        $where['post_id'] = $post_id;
        $where['pid'] = 0;
        $total = M('CommunityComment')->where($where)->count(); //数据记录总数
        $num = 20; //每页记录数
        $totalpage = ceil($total / $num); //总计页数
        $limitpage = ($p - 1) * $num; //每次查询取记录

        if ($p > $totalpage) {
            //超过最大页数，退出
            echo json_encode(['status' => 0, 'info' => '没有更多了']);
            die;
        }

        $comment = M('CommunityComment c')->field(['c.id', 'c.user_id', 'u.nick_name', 'u.head as face', 'c.filter_content content ', 'c.like_num', 'c.like_user', 'c.create_time', 'c.status'])
                ->where($where)
                ->join('LEFT JOIN __FRONT_USER__ u ON u.id = c.user_id')
                ->order('c.create_time desc')
                ->limit($limitpage, $num)
                ->select();

        $nextNum = $floor_num - $num;//下一页楼数开始
        foreach ($comment as $k => $v) {
            $comment[$k]['floor_num'] = $floor_num;
            $floor_num--;
            $comment[$k]['content'] = ($v['status'] == 0) ? '该回贴已被管理员屏蔽' : (string)base64_decode($v['content']);
            $comment[$k]['face'] = frontUserFace($v['face']);
            $comment[$k]['is_liked'] = $userToken && in_array($userToken['userid'], explode(',', $v['like_user'])) ? 1 : 0;
            $subComment = M('CommunityComment')->field(['id', 'user_id', 'by_user', 'like_num', 'filter_content content', 'status'])
                        ->where(['post_id' => $post_id, 'top_id' => $v['id']])
                        ->order('create_time desc')
                        ->select();

            foreach ($subComment as $kk => $vv) {
                $subComment[$kk]['content'] = ($vv['status'] == 0) ? '该回贴已被管理员屏蔽' : (string)base64_decode($vv['content']);
                $subComment[$kk]['fromUser'] = M('FrontUser')->where(['id' => $vv['user_id']])->getField('nick_name');
                $subComment[$kk]['toUser'] = M('FrontUser')->where(['id' => $vv['by_user']])->getField('nick_name');
            }

            $comment[$k]['subComment'] = $subComment;
            unset($comment[$k]['like_user']);
        }

        if ($comment) {
            $list = '';
            foreach ($comment as $k => $v) {
                $list .= '<div class="list comment_box clearfix" userid="' . $v['user_id'] . '" id="top_' . $v['id'] . '">
                             <div class="head">
                                <img src="' . $v['face'] . '" alt="head">
                             </div>
                             <div class="comment_wrap comment_wrap_' . $v['id'] . '">
                                <p><span class="user">' . $v['nick_name'] . '<em>' . $v['floor_num'] . '楼</em></span><a href="javascript:void(0);" class="on like_comment_' . $v['id'] . '" comment_id="' . $v['id'] . '">(' . $v['like_num'] . ')</a></p>
                                <time>' . date('Y-m-d H:i:s', $v['create_time']) . '</time>
                                <article class="comment" comment_name="' . $v['nick_name'] . '" comment_id="' . $v['id'] . '" id="' . $v['id'] . '">' . $v['content'] . '</article>';
                foreach ($v['subComment'] as $k1 => $v1) {
                    $list .= ' 	<article class="reply comment" comment_name="' . $v1['fromUser'] . '" comment_id="' . $v1['id'] . '"><em>' . $v1['fromUser'] . ' 回复 ' . $v1['toUser'] . '：</em>' . $v1['content'] . '</article>';
                }
                $list .= '</div></div>';
            }
            echo json_encode(['status' => 1, 'info' => $list, 'nextNum' => $nextNum]);
            die;
        } else {
            echo json_encode(['status' => 0, 'info' => '没有更多了']);
            die;
        }
    }

    /**
     * 帖子或资讯详情的是否收藏
     */
    public function isCollection()
    {
        if (empty($this->param['userToken']) || empty($this->param['collection_id']) || empty($this->param['collection_type']))
            $this->ajaxReturn(101);

        $userToken = getUserToken($this->param['userToken']);
        $collection_id = $this->param['collection_id'];
        $collection_type = $this->param['collection_type'];

        $where['collection_id'] = $collection_id;
        $where['user_id'] = $userToken['userid'];
        $where['collection_type'] = $collection_type;

        $this->ajaxReturn(['result' => M('CommunityCollection')->where($where)->count() ? 1 : 0]);
    }

}