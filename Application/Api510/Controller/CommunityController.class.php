<?php

/**
 * 体育圈控制器
 * Created by PhpStorm.
 * User: zhangwen
 * Date: 2016/6/27
 * Time: 17:36
 */
use Think\Tool\Tool;

class CommunityController extends PublicController
{
    /**
     * 更新统计圈子的帖子数
     */
    /*先注释，待用
    public function test(){
        if($this->param['begin'] != 'go')
            $this->ajaxReturn(101);

        $data = M('CommunityPosts')->group('cid')->order('cid')->getField('cid, count(*) as num');

        $ids = implode(',', array_keys($data));
        $sql = " UPDATE qc_community SET post_num = CASE id ";

        foreach ($data as $id => $v) {
            $sql .= sprintf(" WHEN %d THEN %d ", $id, $v);
        }

        $sql .= " END WHERE id IN ($ids) ";

        M()->startTrans();
        $res = M()->execute($sql);

        if($res){
            M()->commit();
        }else{
            M()->rollback();
        }

        var_dump($sql, $res);die;
    }
*/

    /**
     * 首页——球迷圈
     */
    public function index()
    {
        //滚动的banner
        if(!$banner = S('HomeBanner'.$this->param['platform'].MODULE_NAME))
        {
            $banner = Tool::getAdList(44,5,$this->param['platform']) ?: [];

            if(!empty($banner)) {
                $banner = D('Home')->getBannerShare($banner);
                S('HomeBanner' . $this->param['platform'] . MODULE_NAME, json_encode($banner), 60 * 5);
            }
        }
        $userToken = getUserToken($this->param['userToken']);
        //我关注的,排除父类已被禁用
        $myAttention = M('CommunityMembers m')
            ->field('c.id, c.`name`, c.head_img, c.follower_num, c.post_num, c.sort')
            ->join('LEFT JOIN qc_community c on m.cid = c.id')
            ->join('LEFT JOIN qc_community qc on c.pid = qc.id')
            ->where(['m.user_id' => $userToken['userid'], 'c.status' => 1, 'qc.status' => 1])
            ->order('m.create_time desc')
            ->select();

        foreach($myAttention as $k => $v){
            $myAttention[$k]['head_img'] = Tool::imagesReplace($v['head_img']);
            if(iosCheck() && $v['sort'] < 50){
                unset($myAttention[$k]);
            }elseif (!iosCheck() && $v['sort'] > 50){
                unset($myAttention[$k]);
            }
        }

        //热门推荐,排除父类已被禁用
//        if(!$hotRecommend = S('hotRecommend'.MODULE_NAME)) {
        $hotRecommend = M('Community c')
            ->field('c.id, c.`name`, c.head_img, c.follower_num, c.post_num, c.sort')
            ->join('LEFT JOIN qc_community qc on c.pid = qc.id')
            ->where(['c.pid' => ['gt', 0], 'c.recommend' => 1, 'c.status' => 1, 'qc.status' => 1])
            ->order('c.sort asc')
            ->select();

        foreach ($hotRecommend as $k => $v) {
            $hotRecommend[$k]['head_img'] = Tool::imagesReplace($v['head_img']);
            if(iosCheck() && $v['sort'] < 50){
                unset($hotRecommend[$k]);
            }elseif (!iosCheck() && $v['sort'] > 50){
                unset($hotRecommend[$k]);
            }
        }
        // S('hotRecommend'.MODULE_NAME, json_encode($hotRecommend), 60*5);
//        }

        $hotRecommend = !is_array($hotRecommend) ||  !$hotRecommend ? [] : array_merge($hotRecommend);
        //一级分类所属的圈子列表
//        if(!$communityArr = S('communityArr'.MODULE_NAME)) {
        $pidArr = (array)M('Community')
            ->field('id, `name`, head_img')
            ->where(['pid' => 0, 'status' => 1])
            ->order('sort asc')
            ->select();

        $communityArr = array();
        foreach ($pidArr as $k => $v) {
            $arr = (array)M('Community')
                ->field('id, `name`, head_img, follower_num, post_num, sort')
                ->where(['pid' => $v['id'], 'status' => 1])
                ->order('sort asc')
                ->select();

            foreach ($arr as $k1 => $v1) {
                $arr[$k1]['head_img'] = Tool::imagesReplace($v1['head_img']);
                if(iosCheck() && $v1['sort'] < 50){
                    unset($arr[$k1]);
                }elseif (!iosCheck() && $v1['sort'] > 50){
                    unset($arr[$k1]);
                }
            }

            if($arr) {
                $communityArr[$k]['name'] = $v['name'];
                $communityArr[$k]['head_img'] = (string)Tool::imagesReplace($v['head_img']);
                $communityArr[$k]['arr'] = array_merge($arr);
            }
        }
        //S('communityArr'.MODULE_NAME, json_encode($communityArr), 60*5);
//        }

        $this->ajaxReturn(['myAttention' => (array)array_merge($myAttention), 'hotRecommend' => $hotRecommend, 'communityArr' => array_merge($communityArr), 'banner'=>$banner]);
    }

    /**
     * 首页——热门贴
     */
    public function hotPosts(){
        $page = $this->param['page'] ? (int)$this->param['page'] : 1;//页码大于1表示翻页
        $pageNum = 20;
        $pageSize = ($page-1)*$pageNum;

        //翻页不需要传推荐
        $homeRecommend = array();
        if($page == 1) {
            //后台推荐:回帖时间倒序，发帖时间倒序
            $homeRecommend = (array)M('CommunityPosts p')
                ->field('p.id, p.user_id, p.base64_title as title, p.base64_content as content, p.img, p.like_num, p.create_time, u.nick_name, u.head as face, c.`name`, p.comment_num as num, u.status')
                ->join(' LEFT JOIN qc_community c on p.cid= c.id ')
                ->join(' LEFT JOIN qc_front_user u on p.user_id = u.id ')
                ->where(['p.home_recommend' => 1, 'p.status' => 1, 'u.status' => 1])
                ->order('p.lastreply_time desc, p.create_time desc')
                ->group('p.id')
                ->select();
        }

        //超过热帖回帖数的热门贴:回帖时间倒序，发帖时间倒序
        $setHotNum = (int)getWebConfig('community')['hotPostNum'];
        $pageNum = $pageNum - count($homeRecommend);
        $hotPosts = (array)M('CommunityPosts p')
            ->field('p.id, p.user_id, p.base64_title as title, p.base64_content as content, p.img, p.like_num, p.create_time, u.nick_name, u.head as face, c.`name`, p.comment_num as num,u.status')
            ->join(' LEFT JOIN qc_community c on p.cid= c.id ')
            ->join(' LEFT JOIN qc_front_user u on p.user_id = u.id ')
            ->where(['p.home_recommend' => 0, 'p.status' => 1, 'u.status' => 1, 'p.comment_num' => ['gt', $setHotNum]])
            ->order('p.lastreply_time desc, p.create_time desc')
            ->group('p.id')
            ->limit($pageSize.' , '.$pageNum)
            ->select();

        $data = array_merge($homeRecommend, $hotPosts);
	    $comment = D('CommunityPosts')->getDataComment($data, 'id');
	    foreach ($data as $k => $v) {
            $data[$k]['title'] = $v['title'] ? (base64_decode($v['title']) ? (string)base64_decode($v['title']) : '') : '';
            $data[$k]['content'] = $v['content'] ? (base64_decode($v['content']) ? (string)base64_decode($v['content']) : '') : '';
            $data[$k]['face'] = frontUserFace($v['face']);
		
		    isset($comment[$v['id']]) ? $data[$k]['comment'] = array_slice($comment[$v['id']], 0, 2) : $data[$k]['comment'] = [];

            $imgArr = json_decode($v['img'], true);
            $newArr = array();
            foreach($imgArr as $kk => $vv){
                $newArr[$kk]['normal'] = Tool::imagesReplace($vv);
                $vArr = explode('.', $vv);
                $newArr[$kk]['thumb'] = Tool::imagesReplace($vArr[0].C('thumbImgSize').'.'.$vArr[1]);
            }
            $data[$k]['img'] = $newArr;
        }

        $this->ajaxReturn(['list' => $data]);
    }


    /**
     * 圈子首页
     */
    public function communityIndex(){
        $userToken = getUserToken($this->param['userToken']);
        $page = $this->param['page'] ? (int)$this->param['page'] : 1;//页码大于1表示翻页
        $pageNum = 20;
        $pageSize = ($page-1)*$pageNum;
        $sortType = $this->param['sortType'] ? (int)$this->param['sortType'] : 1;//1:最新回复;2:最新发表;3:查看热帖

        //圈子资料
        if(!$data = S('communityInfo'.$this->param['communityId'].MODULE_NAME)) {
            $data = (array)M('Community')
                ->field('id, `name`, description, head_img, background')
                ->where(['id' => $this->param['communityId']])->find();
            $data['head_img'] = (string)Tool::imagesReplace($data['head_img']);
            $data['background'] = (string)Tool::imagesReplace($data['background']);
            S('communityInfo'.$this->param['communityId'].MODULE_NAME, json_encode($data), 60*5);
        }
        $data['is_attention'] = M('CommunityMembers')->where(['user_id' => $userToken['userid'], 'cid' => $this->param['communityId']])->count() ? '1' : '0';
        $data['follower_num'] = M('Community')->where(['id' => $this->param['communityId']])->getField('follower_num');
        $data['post_num'] = M('Community')->where(['id' => $this->param['communityId']])->getField('post_num');
        //苹果审核用默认图片
        if(iosCheck())
            $data['background'] = SITE_URL.$_SERVER['HTTP_HOST'].'/Public/Api/images/bg_fb.png';

        $communityRecommend = array();
        if($page == 1) {
            //置顶帖子,只显示标题
            $communityRecommend = (array)M('CommunityPosts p')
                ->field('p.id, p.base64_title as title')
                ->join('LEFT JOIN qc_front_user U ON P.user_id = U.id')
                ->where(['p.cid' => $this->param['communityId'], 'p.top_recommend' => 1, 'p.status' => 1, 'U.status' => 1])
                ->group('p.id')->order('p.create_time desc')->limit(2)->select();

            foreach($communityRecommend as $k => $v){
                $communityRecommend[$k]['title'] = (string)base64_decode($v['title']);
            }
        }

        $pageNum = 20;
        $fields = 'p.id, p.user_id, p.base64_title as title, p.base64_content as content, p.img, p.like_num, p.create_time, u.nick_name, u.head as face, c.`name`, p.comment_num as num, u.status';
        if($sortType == 1){//最新回复:回帖时间倒序，发帖时间倒序(哪个新按哪个排序)
            $list = (array)M('CommunityPosts p')->field($fields)
                ->join(' LEFT JOIN qc_community c on p.cid= c.id ')
                ->join(' LEFT JOIN qc_front_user u on p.user_id = u.id ')
                ->where(['p.cid' => $this->param['communityId'], 'p.status' => 1, 'u.status' => 1])
                ->group('p.id')
                ->order('p.lastreply_time desc')->limit($pageSize.','.$pageNum)->select();
        }else if($sortType == 2){//最新发表:发帖时间倒序，id倒序
            $list = (array)M('CommunityPosts p')->field($fields)
                ->join(' LEFT JOIN qc_community c on p.cid= c.id ')
                ->join(' LEFT JOIN qc_front_user u on p.user_id = u.id ')
                ->where(['p.cid' => $this->param['communityId'], 'p.status' => 1, 'u.status' => 1])
                ->group('p.id')
                ->order('p.create_time desc, p.id desc')->limit($pageSize.','.$pageNum)->select();
        }else if($sortType == 3){//查看热帖：回帖数量倒序，发帖时间倒序
            $list = (array)M('CommunityPosts p')->field($fields)
                ->join(' LEFT JOIN qc_community c on p.cid= c.id ')
                ->join(' LEFT JOIN qc_front_user u on p.user_id = u.id ')
                ->where(['p.cid' => $this->param['communityId'], 'p.status' => 1, 'u.status' => 1])
                ->group('p.id')
                ->order('num desc, p.create_time desc')->limit($pageSize.','.$pageNum)->select();
        }
	
	    $comment = D('CommunityPosts')->getDataComment($list, 'id');

        foreach ($list as $k => $v) {
            $imgArr = json_decode($v['img'], true);
            $newArr = array();
            foreach($imgArr as $kk => $vv){
                $newArr[$kk]['normal'] = Tool::imagesReplace($vv);
                $vArr = explode('.', $vv);
                $newArr[$kk]['thumb'] = Tool::imagesReplace($vArr[0].C('thumbImgSize').'.'.$vArr[1]);
            }
            $list[$k]['img']     = $newArr;
            $list[$k]['face']    = frontUserFace($v['face']);
            $list[$k]['title']   = (string)base64_decode($v['title']);
            $list[$k]['content'] = $v['content'] ? sub_str(base64_decode($v['content'])) : '';

            $filterPatten = '/(彩|赌|博)/';
            $filterRes = preg_replace_callback($filterPatten, function ($m) {
                return isset($m[1]) ? '*' : $m[0];
            }, $list[$k]['content']);
	        isset($comment[$v['id']]) ? $list[$k]['comment'] = array_slice($comment[$v['id']], 0, 2) : $list[$k]['comment'] = [];

            $list[$k]['content'] = $filterRes ? $filterRes : $list[$k]['content'];
        }

        $this->ajaxReturn(['info' => $data, 'top' => $communityRecommend, 'list' => $list]);
    }

    /**
     * 发布圈资讯（IOS新包审核专用）
     */
    public function publish(){
        $userInfo = $this->getInfo($this->param['userToken']);

        if (!$userInfo || !is_array($userInfo))
            $this->ajaxReturn(1001);

        if (mb_strlen($this->param['title']) > 200 || strlen($this->param['title']) <= 0)
            $this->ajaxReturn(6008);

        if (mb_strlen($this->param['content']) > 10000)
            $this->ajaxReturn(6018);

        if (!$this->param['content'] && array_sum($_FILES['pics']['size']) <= 0)
            $this->ajaxReturn(6009);

        if(!iosCheck()){
            $res = matchFilterWords('FilterWords', $this->param['content'] . $this->param['title'], 1);
            if ($res !== true)
                $this->ajaxReturn(1063, '含有非法敏感词:' . $res);
        }

        $m = M('FrontUser');
        $cStatus = $m->field('community_status status')->where(array('id' => $userInfo['userid']))->find();
        if ($cStatus['status'] == 2 || $cStatus['status'] > time())
            $this->ajaxReturn(6014);

        $postModel = D('CommunityPosts');

        //生成帖子记录
        $addArrs = [
            'cid'           => 0,
            'user_id'       => $userInfo['userid'],
            'base64_title'  => base64_encode($this->param['title']),
            'base64_content'=> base64_encode($this->param['content']),
            'status'        => 1,
            'create_time'   => NOW_TIME,
            'lastreply_time'=> NOW_TIME,
        ];

        $post_id = $postModel->add($addArrs);

        if (!$post_id)
            $this->ajaxReturn(6011);

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
     * 我的发布
     */
    public function myPublish()
    {
        $userInfo   = getUserToken($this->param['userToken']);
        $postModel  = D('CommunityPosts');
        $data      = $postModel->getMyPosts($userInfo['userid'], $this->param['page'], 20, 1);

        $publish = [];
        foreach($data as $k => $v){
                $publish[$k]['id'] = $v['post_id'];
                $publish[$k]['title'] = $v['post_title'];
                if($v['post_img']){
                    foreach ($v['post_img'] as $imgk => $imgv){
                        $publish[$k]['img'][] = $imgv['thumb'];
                    }
                }else{
                    $publish[$k]['img'] = [];
                }
                $publish[$k]['remark'] = $v['post_content'];
                $publish[$k]['source'] = '全球体育';
                $publish[$k]['click_number'] = 0;
                $publish[$k]['game_type'] = 1;
                $publish[$k]['app_recommend'] = 0;
                $publish[$k]['commentNum'] = 0;
        }

        $this->ajaxReturn(['lists' => $publish]);
    }

    /**
     * 关注圈子
     */
    public function attention(){
        if (empty($this->param['userToken']) || empty($this->param['communityId']))
            $this->ajaxReturn(101);

        $type = isset($this->param['type']) ? $this->param['type'] : 0;//0:关注圈子；1：取消关注
        $userToken = getUserToken($this->param['userToken']);
        $communityId = $this->param['communityId'];

        if($type == 1){
            $where['cid'] = $communityId;
            $where['user_id'] = $userToken['userid'];

            $res = M('CommunityMembers')->where($where)->delete();
            if($res == false)
                $this->ajaxReturn(1019);
        }else{
            $data['cid'] = $communityId;
            $data['user_id'] = $userToken['userid'];
            $data['status'] = 1;
            $data['create_time'] = time();

            if(M('CommunityMembers')->where(['user_id' => $userToken['userid'], 'cid' => $communityId, 'status' => 1])->count())
                $this->ajaxReturn(6016);

            //圈子关注数量+1
            M('Community')->where(['id' => $communityId])->save(['follower_num' => ['exp', 'follower_num+1']]);

            $res = M('CommunityMembers')->add($data);
            if($res == false)
                $this->ajaxReturn(1018);
        }

        $this->ajaxReturn(['result' => 1]);
    }

    /**
     *  ta的主页——体育圈
     */
    public function homepage(){
        $create_time = (int)isset($this->param['create_time']) ? (int)$this->param['create_time'] : 0;//当前页面最小时间戳
        $userToken = getUserToken($this->param['userToken']);
        $user_id = isset($this->param['user_id']) ? $this->param['user_id'] : $userToken['userid'];

        //我关注的圈子
        $myAttention = (array)M('CommunityMembers m')->join('LEFT JOIN qc_community c on m.cid = c.id')
            ->field('c.id, c.`name`, c.head_img')
            ->where(['m.user_id' => $user_id, 'c.status' => 1])
            ->order('m.create_time desc')->limit(4)->select();

        foreach($myAttention as $k => $v){
            $myAttention[$k]['head_img'] = Tool::imagesReplace($v['head_img']);
        }

        $totalNum = (string)M('CommunityMembers m')->join('LEFT JOIN qc_community c on m.cid = c.id')
            ->where(['m.user_id' => $user_id, 'c.status' => 1])->count();

        //我的帖子
        if($create_time){//如果传最小id，则向下取
            $where['p.create_time'] = ['lt', (int)$create_time];
        }

        $where['p.user_id'] = $user_id;
        $where['p.home_recommend'] = 0;
        $where['p.top_recommend'] = 0;
        $where['p.status'] = 1;
        $where['u.status'] = 1;
        $list = (array)M('CommunityPosts p')
            ->field('p.id, p.base64_title as title, p.base64_content as content, p.img, p.like_num, p.create_time, c.`name`, p.comment_num as num,u.status as u_status')
            ->join(' LEFT JOIN qc_community c on p.cid= c.id ')
            ->join(' LEFT JOIN qc_front_user u on p.user_id = u.id ')
            ->where($where)
            ->group('p.id')
            ->order('p.id desc')
            ->limit(10)
            ->select();
        $comment = D('CommunityPosts')->getDataComment($list, 'id');

        foreach ($list as $k => $v) {
            $imgArr = json_decode($v['img'], true);
            $newArr = array();
            foreach($imgArr as $kk => $vv){
                $newArr[$kk]['normal'] = Tool::imagesReplace($vv);
                $vArr = explode('.', $vv);
                $newArr[$kk]['thumb'] = Tool::imagesReplace($vArr[0].C('thumbImgSize').'.'.$vArr[1]);
            }
            isset($comment[$v['id']]) ? $list[$k]['comment'] = array_slice($comment[$v['id']], 0, 2) : $list[$k]['comment'] = [];
            $list[$k]['img'] = (array)$newArr;
            $list[$k]['title'] = (string)base64_decode($v['title']);
            $list[$k]['content'] = $v['content'] ? (string)base64_decode($v['content']) : '';
        }

        $this->ajaxReturn(['info' => $myAttention, 'totalNum' => $totalNum, 'list' => $list ?:[]]);
    }

    /**
     * TA的圈子
     */
    public function taCommunity(){
        $userToken = getUserToken($this->param['userToken']);
        $user_id = isset($this->param['user_id']) ? $this->param['user_id'] : $userToken['userid'];

        //我关注的圈子
        $myAttention = (array)M('CommunityMembers m')->join('LEFT JOIN qc_community c on m.cid = c.id')
            ->field('c.id, c.`name`, c.head_img, c.follower_num, c.post_num')
            ->where(['m.user_id' => $user_id, 'c.status' => 1])
            ->order('m.create_time desc')->select();

        foreach($myAttention as $k => $v){
            $myAttention[$k]['head_img'] = Tool::imagesReplace($v['head_img']);
        }

        $this->ajaxReturn(['info' => $myAttention]);
    }


    /**
     * 体育圈资讯搜索
     **/
    public function newsSearch()
    {
        if(!empty($this->param['keyword']))
            $keyWord = $this->param['keyword'];
        else
            $this->ajaxReturn(101);

        $limit = 10;
        $page = $this->param['page'] ? (int)$this->param['page'] : 1;//页码大于1表示翻页
        $time = $this->param['time'] ? (int)$this->param['time'] : time();//时间界限


        $base64_title = trim($keyWord);//清除两边空格
        $where['_string'] = 'from_base64(base64_title) like concat("%","'.$base64_title.'","%")';//模糊搜索标题
        //时间查询
        $where['p.create_time'] = array('ELT',$time);

        $where['p.status']   = 1;
        $data = M('CommunityPosts p')
            ->join(' LEFT JOIN qc_front_user u on p.user_id = u.id ')
            ->field('p.id, p.user_id, p.base64_title as title, p.base64_content as content, p.img, p.like_num, p.create_time, u.nick_name, u.head as face, p.comment_num as num')
            ->where($where)
            ->order('p.create_time DESC')
            ->page($page . ',' . $limit)
            ->select();


        foreach ($data as $k => $v) {
            $data[$k]['title'] = $v['title'] ? (base64_decode($v['title']) ? (string)base64_decode($v['title']) : '') : '';
            $data[$k]['content'] = $v['content'] ? (base64_decode($v['content']) ? (string)base64_decode($v['content']) : '') : '';
            $data[$k]['face'] = frontUserFace($v['face']);
            //处理图片
            $imgArr = json_decode($v['img'], true);
            $newArr = array();
            foreach($imgArr as $kk => $vv){
                if(count($newArr) >= 3) break;//图片最多显示三张
                $newArr[$kk]['normal'] = Tool::imagesReplace($vv);
                $vArr = explode('.', $vv);
                $newArr[$kk]['thumb'] = Tool::imagesReplace($vArr[0].C('thumbImgSize').'.'.$vArr[1]);
            }
            if(count($newArr) == 2) $newArr = reset($newArr);//另一个情况为显示1张
            $data[$k]['img'] = $newArr;
        }
        $res['list'] =  $data ?: [];
        $res['listTime'] = $time;
        $this->ajaxReturn($res);

    }

    /*
     * 热门搜索关键字
     */
    public function hotSeach()
    {
        $data = M('AppHotseach')->where(['status'=>1])->order('sort desc,add_time desc')->limit(8)->getField('name',true);
        $this->ajaxReturn(['list' => $data ?: []]);
    }

}