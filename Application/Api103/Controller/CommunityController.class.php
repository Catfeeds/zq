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
     * 首页——球迷圈
     */
    public function index()
    {
        $userToken = getUserToken($this->param['userToken']);
        //我关注的,排除父类已被禁用
        $myAttention = M('CommunityMembers m')->field('c.id, c.`name`, c.head_img, c.follower_num, c.post_num')
                        ->join('LEFT JOIN qc_community c on m.cid = c.id')
                        ->join('LEFT JOIN qc_community qc on c.pid = qc.id')
                        ->where(['m.user_id' => $userToken['userid'], 'c.status' => 1, 'qc.status' => 1])
                        ->order('m.create_time desc')->select();
        foreach($myAttention as $k => $v){
            $myAttention[$k]['head_img'] = Tool::imagesReplace($v['head_img']);
        }

        //热门推荐,排除父类已被禁用
        // if(!$hotRecommend = S('hotRecommend'.MODULE_NAME)) {
            $hotRecommend = M('Community c')->field('c.id, c.`name`, c.head_img, c.follower_num, c.post_num')
                ->join('LEFT JOIN qc_community qc on c.pid = qc.id')
                ->where(['c.pid' => ['gt', 0], 'c.recommend' => 1, 'c.status' => 1, 'qc.status' => 1])
                ->order('c.sort asc')->select();

            foreach ($hotRecommend as $k => $v) {
                $hotRecommend[$k]['head_img'] = Tool::imagesReplace($v['head_img']);
            }
            // S('hotRecommend'.MODULE_NAME, json_encode($hotRecommend), 60*5);
        // }
        $hotRecommend = ($hotRecommend == null) ? [] : $hotRecommend;

        //一级分类所属的圈子列表
        // if(!$communityArr = S('communityArr'.MODULE_NAME)) {
            $pidArr = (array)M('Community')->field('id, `name`, head_img')->where(['pid' => 0, 'recommend' => 0, 'status' => 1])->order('sort asc')->select();
            $communityArr = array();
            foreach ($pidArr as $k => $v) {
                $arr = (array)M('Community')->field('id, `name`, head_img, follower_num, post_num')->where(['pid' => $v['id'], 'recommend' => 0, 'status' => 1])->order('sort asc')->select();
                foreach ($arr as $k1 => $v1) {
                    $arr[$k1]['head_img'] = Tool::imagesReplace($v1['head_img']);
                }
                $communityArr[$k]['name'] = $v['name'];
                $communityArr[$k]['head_img'] = (string)Tool::imagesReplace($v['head_img']);
                $communityArr[$k]['arr'] = $arr;
            }
            // S('communityArr'.MODULE_NAME, json_encode($communityArr), 60*5);
        // }

        $this->ajaxReturn(['myAttention' => (array)$myAttention, 'hotRecommend' => $hotRecommend, 'communityArr' => (array)$communityArr]);
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
            $homeRecommend = (array)M('CommunityPosts p')->field('p.id, p.user_id, p.base64_title as title, p.base64_content as content, p.img, p.like_num, p.create_time, u.nick_name, u.head as face, c.`name`, count(cc.post_id) as num')
                ->join(' LEFT JOIN qc_community c on p.cid= c.id ')
                ->join(' LEFT JOIN qc_front_user u on p.user_id = u.id ')
                ->join(' LEFT JOIN qc_community_comment cc on p.id=cc.post_id ')
                ->where(['p.home_recommend' => 1, 'p.status' => 1])
                ->group('p.id')->order('p.lastreply_time desc, p.create_time desc')->select();
        }

        //超过热帖回帖数的热门贴:回帖时间倒序，发帖时间倒序
        $setHotNum = (int)getWebConfig('community')['hotPostNum'];
        $pageNum = $pageNum - count($homeRecommend);
        $hotPosts = (array)M('CommunityPosts p')->field('p.id, p.user_id, p.base64_title as title, p.base64_content as content, p.img, p.like_num, p.create_time, u.nick_name, u.head as face, c.`name`, count(cc.post_id) as num')
                    ->join(' LEFT JOIN qc_community c on p.cid= c.id ')
                    ->join(' LEFT JOIN qc_front_user u on p.user_id = u.id ')
                    ->join(' LEFT JOIN qc_community_comment cc on p.id=cc.post_id ')
                    ->where(['p.home_recommend' => 0, 'p.status' => 1])
                    ->group('p.id')->having('count(cc.post_id)>'.$setHotNum)
                    ->order('p.lastreply_time desc, p.create_time desc')
                    ->limit($pageSize.' , '.$pageNum)->select();

        $data = array_merge($homeRecommend, $hotPosts);
        foreach ($data as $k => $v) {
            $data[$k]['title'] = $v['title'] ? (base64_decode($v['title']) ? (string)base64_decode($v['title']) : '') : '';
            $data[$k]['content'] = $v['content'] ? (base64_decode($v['content']) ? (string)base64_decode($v['content']) : '') : '';
            $data[$k]['face'] = frontUserFace($v['face']);

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
            $data = (array)M('Community')->field('id, `name`, description, head_img, background, follower_num, post_num')
                ->where(['id' => $this->param['communityId']])->find();
            $data['head_img'] = (string)Tool::imagesReplace($data['head_img']);
            $data['background'] = (string)Tool::imagesReplace($data['background']);
            S('communityInfo'.$this->param['communityId'].MODULE_NAME, json_encode($data), 60*5);
        }
        $data['is_attention'] = M('CommunityMembers')->where(['user_id' => $userToken['userid'], 'cid' => $this->param['communityId']])->count() ? '1' : '0';

        $communityRecommend = array();
        if($page == 1) {
            //置顶帖子,只显示标题
            $communityRecommend = (array)M('CommunityPosts p')->field('p.id, p.base64_title as title')
                ->where(['p.cid' => $this->param['communityId'], 'p.top_recommend' => 1, 'p.status' => 1])
                ->group('p.id')->order('p.create_time desc')->limit(2)->select();

            foreach($communityRecommend as $k => $v){
                $communityRecommend[$k]['title'] = (string)base64_decode($v['title']);
            }
        }

        $pageNum = 20;
        $fields = 'p.id, p.user_id, p.base64_title as title, p.base64_content as content, p.img, p.like_num, p.create_time, u.nick_name, u.head as face, c.`name`, count(cc.post_id) as num';
        if($sortType == 1){//最新回复:回帖时间倒序，发帖时间倒序
            $list = (array)M('CommunityPosts p')->field($fields)
                    ->join(' LEFT JOIN qc_community c on p.cid= c.id ')
                    ->join(' LEFT JOIN qc_front_user u on p.user_id = u.id ')
                    ->join(' LEFT JOIN qc_community_comment cc on p.id=cc.post_id ')
                    ->where(['p.cid' => $this->param['communityId'], 'p.status' => 1])
                    ->group('p.id')->order('p.lastreply_time desc, p.create_time desc')->limit($pageSize.','.$pageNum)->select();
        }else if($sortType == 2){//最新发表:发帖时间倒序，id倒序
            $list = (array)M('CommunityPosts p')->field($fields)
                    ->join(' LEFT JOIN qc_community c on p.cid= c.id ')
                    ->join(' LEFT JOIN qc_front_user u on p.user_id = u.id ')
                    ->join(' LEFT JOIN qc_community_comment cc on p.id=cc.post_id ')
                    ->where(['p.cid' => $this->param['communityId'], 'p.status' => 1])
                    ->group('p.id')->order('p.create_time desc, p.id desc')->limit($pageSize.','.$pageNum)->select();
        }else if($sortType == 3){//查看热帖：回帖数量倒序，发帖时间倒序
            $list = (array)M('CommunityPosts p')->field($fields)
                    ->join(' LEFT JOIN qc_community c on p.cid= c.id ')
                    ->join(' LEFT JOIN qc_front_user u on p.user_id = u.id ')
                    ->join(' LEFT JOIN qc_community_comment cc on p.id=cc.post_id ')
                    ->where(['p.cid' => $this->param['communityId'], 'p.status' => 1])
                    ->group('p.id')
                    ->order('num desc, p.create_time desc')->limit($pageSize.','.$pageNum)->select();
        }

        foreach ($list as $k => $v) {
            $imgArr = json_decode($v['img'], true);
            $newArr = array();
            foreach($imgArr as $kk => $vv){
                $newArr[$kk]['normal'] = Tool::imagesReplace($vv);
                $vArr = explode('.', $vv);
                $newArr[$kk]['thumb'] = Tool::imagesReplace($vArr[0].C('thumbImgSize').'.'.$vArr[1]);
            }
            $list[$k]['img'] = $newArr;
            $list[$k]['face'] = frontUserFace($v['face']);
            $list[$k]['title'] = (string)base64_decode($v['title']);
            $list[$k]['content'] = $v['content'] ? sub_str(base64_decode($v['content'])) : '';
        }

        $this->ajaxReturn(['info' => $data, 'top' => $communityRecommend, 'list' => $list]);
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
        $list = (array)M('CommunityPosts p')->field('p.id, p.base64_title as title, p.base64_content as content, p.img, p.like_num, p.create_time, c.`name`, count(cc.post_id) as num')
                ->join(' LEFT JOIN qc_community c on p.cid= c.id ')
                ->join(' LEFT JOIN qc_front_user u on p.user_id = u.id ')
                ->join(' LEFT JOIN qc_community_comment cc on p.id=cc.post_id ')
                ->where($where)->group('p.id')->order('p.id desc')
                ->limit(10)->select();

        foreach ($list as $k => $v) {
            $imgArr = json_decode($v['img'], true);
            $newArr = array();
            foreach($imgArr as $kk => $vv){
                $newArr[$kk]['normal'] = Tool::imagesReplace($vv);
                $vArr = explode('.', $vv);
                $newArr[$kk]['thumb'] = Tool::imagesReplace($vArr[0].C('thumbImgSize').'.'.$vArr[1]);
            }
            $list[$k]['img'] = (array)$newArr;
            $list[$k]['title'] = (string)base64_decode($v['title']);
            $list[$k]['content'] = $v['content'] ? (string)base64_decode($v['content']) : '';
        }

        $this->ajaxReturn(['info' => $myAttention, 'totalNum' => $totalNum, 'list' => $list]);
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

}