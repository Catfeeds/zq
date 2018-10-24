<?php

/**
 * 首页
 *
 * @author chenzj <443629770@qq.com>
 *
 * @since  2016-04-19
 */
use Think\Tool\Tool;

class EuroController extends CommonController {
    /*
     * ---首页---
     */
    public function index() {
        $type = I('get.type', 5, 'intval');
        if ($type < 1 && $type > 5) {
            $this->error('参数有误!');
        }
        if ($type != 1) {
            $event = I('get.event', null);
        }
        //轮播图
        $recommend = $this->get_recommend('mOZB');
        $this->assign('recommend', $recommend);
        $where['g.status'] = 1;
        switch ($type) {
            case 1:
                $where['runno'] = 9852;
                $group = I('get.group', 'A');
                $group = strtoupper($group);
                $where['rno'] = $group;
                break;
            case 2:
                $where['runno'] = 12666;
                break;
            case 3:
                $where['runno'] = 12672;
                break;
            case 4:
                $where['runno'] = 12692;
                break;
            case 5:
                $where['id'] = 860887;
                break;
        }
        //小组赛球队
        $group_list = M('GameFbinfo')->alias('g')->field('g.game_id,g.gtime,g.game_state,g.score,home_team_id,away_team_id,home_team_name,away_team_name')
                        ->where($where)->order('game_state desc,gtime asc')->select();
        $new_list = array();
        if ($group_list) {
            $group_list = getTeamLogo($group_list);
            $game_id = array();
            foreach ($group_list as &$v) {
                $game_id[] = $v['game_id'];
                $v['home_team_name'] = explode(',', $v['home_team_name']);
                $v['away_team_name'] = explode(',', $v['away_team_name']);
            }
            $this->assign('group_list', $group_list);
            //新闻列表
            if ($event) {
                $game_id = $map['game_id'] = $event;
            } else {
                $map['game_id'] = array('IN', $game_id);
            }
            $map_or['pc.id'] = 29;
            $map_or['pc.pid'] = 29;
            $map_or['_logic'] = 'or';
            $map['_complex'] = $map_or;
            $map['pl.status'] = 1;
            $new_list = M('PublishList')->alias('pl')->field('pl.id,pl.remark,pl.img,pl.short_title')
                            ->join('__PUBLISH_CLASS__ pc ON pl.class_id=pc.id')
                            ->where($map)->limit(5)->order('add_time desc')->select();
            foreach ($new_list as &$v) {
                $v['img'] = @Think\Tool\Tool::imagesReplace($v['img']);
            }
            session('Euro_game_id', $game_id);
        }
        $this->assign('list', $new_list);
        $this->assign('title','index');
        $this->display();
    }

    public function hot_detail() {
        $id = I('get.id', 0, 'intval');
        if ($id < 1) {
            $this->error("找不到相关页面！");
        }
        $_M = M('PublishList');
        $data = $_M->field('title,class_id,label,add_time,source,content')->where(array('id' => $id, 'status' => 1))->find();
        if ($data) {
            //点击量加1
            M('PublishList')->where(array('id' => $id, 'status' => 1))->setInc('click_number');
        } else {
            $this->error("找不到相关内容！");
        }
        if (!empty($data['label'])) {
            $lable = explode(',', $data['label']);
            $this->assign('lable', $lable);
        }
        $this->assign('data', $data);
        $this->assign('title','index');
        $this->display();
    }

    //ajax 加载更多新闻
    public function hot_load() {
        $game_id = session('Euro_game_id');
        if (!$game_id) {
            $this->error('由于你停留了太久,请刷新重试!');
        }
        $p = isset($_POST['k']) ? intval(trim($_POST['k'])) : 0;

        is_array($game_id) ? $map['game_id'] = array('IN', $game_id) : $map['game_id'] = $game_id;
        $map_or['pc.id'] = 29;
        $map_or['pc.pid'] = 29;
        $map_or['_logic'] = 'or';
        $map['_complex'] = $map_or;
        $map['pl.status'] = 1;
        $_M = M('PublishList');
        $total = $_M->alias('pl')->join('__PUBLISH_CLASS__ pc ON pl.class_id=pc.id')->where($map)->count(); //数据记录总数
        $num = 5; //每页记录数
        $totalpage = ceil($total / $num); //总计页数
        $limitpage = ($p - 1) * $num; //每次查询取记录

        if ($p > $totalpage) {
            //超过最大页数，退出
            $this->error("没有更多了");
        }
        $list = $_M->alias('pl')->field('pl.id,pl.remark,pl.img,pl.short_title')
                        ->join('__PUBLISH_CLASS__ pc ON pl.class_id=pc.id')
                        ->where($map)->order('add_time desc')->limit($limitpage, $num)->select();
        if (!$list) {
            $this->error("没有更多了");
        }
        foreach ($list as &$v) {
            $v['img'] = @Think\Tool\Tool::imagesReplace($v['img']);
        }
        $this->success($list);
    }
    
    /*
     * ---赛程---
     */
    public function live() {
        $this->assign('current_nav', 'against');
        $this->assign('title','live');
        $this->display('live_against');
    }

    public function live_lists() {
        $group = I('get.group', '');
        $id = I('get.id', '');
        if (empty($group) && empty($id)) {
            $this->error('参数有误!');
        }
        if (!empty($group) && !empty($id)) {
            $this->error('参数有误!');
        }
        $where = array();
        $where['g.status']= 1;
        if (!empty($group)) {
            $group = strtoupper($group);
            $where['runno']=9852;
            $where['rno'] = $group;
            
            $group_list = M('GameFbinfo')->alias('g')->field('g.game_id,g.gtime,g.game_state,g.score,home_team_id,home_team_name,away_team_id,away_team_name')
                ->join("LEFT JOIN qc_run_fb r ON g.runno=r.id")
                ->where($where)->order('game_state desc,gtime asc')->select();
            if ($group_list) {
                $group_list = getTeamLogo($group_list);
                $game_id = array();
                foreach ($group_list as &$v) {
                    $game_id[] = $v['game_id'];
                    $v['home_team_name'] = explode(',', $v['home_team_name']);
                    $v['away_team_name'] = explode(',', $v['away_team_name']);
                }
                //新闻资讯
                $_M = M('PublishList');
                $map_or['pc.id']=29;
                $map_or['pc.pid']=29;
                $map_or['_logic'] = 'or';
                $map['user_id']=array(array('eq','0'),array('EXP','IS NULL'),'OR');
                $map['_complex'] = $map_or;
                $map['game_id']=$map_r['game_id'] = array('IN', $game_id);
                $map['pl.status']=$map_r['pl.status'] = 1;
                $new_list = $_M->alias('pl')->field('pl.id,pl.title')
                        ->join('__PUBLISH_CLASS__ pc ON pl.class_id=pc.id')
                                ->where($map)->limit(4)->order('add_time desc')->select();
                $this->assign('news_list', $new_list);

                //推荐
                $map_r['user_id'] = array('IN', array(305,322,323,324,325));
                $recom = $_M->alias('pl')->field('id,title')
                                ->where($map_r)->limit(4)->order('add_time desc')->select();
                $this->assign('recom_list', $recom);
            }
            
            $this->assign('group', $group);
        }
        if (!empty($id)) {
            $where['g.game_id'] = $id;
            $group_list = M('GameFbinfo')->alias('g')->field('r.run_name,g.game_id,g.gtime,g.game_state,g.score,home_team_name,away_team_name')
                ->join("LEFT JOIN qc_run_fb r ON g.runno=r.id")
                ->where($where)->order('game_state desc,gtime asc')->limit(1)->select();
            if ($group_list) {
                $group_list = getTeamLogo($group_list);
                $group_list[0]['home_team_name'] = explode(',', $group_list[0]['home_team_name']);
                $group_list[0]['away_team_name'] = explode(',', $group_list[0]['away_team_name']);
                $group_list[0]['run_name'] = explode(',', $group_list[0]['run_name']);
                
                //新闻资讯
                $_M = M('PublishList');
                $map_or['pc.id']=29;
                $map_or['pc.pid']=29;
                $map_or['_logic'] = 'or';
                $map['_complex'] = $map_or;
                $map['game_id']=$map_r['game_id'] = $group_list[0]['game_id'];
                $map['user_id']=array(null,'0','OR');
                $map['pl.status']=$map_r['pl.status'] = 1;
                $new_list = $_M->alias('pl')->field('id,title')
                                ->where($map)->limit(4)->order('add_time desc')->select();
                $this->assign('news_list', $new_list);
                //推荐
                $map_r['user_id'] = array('IN', array(305,322,323,324,325));
                $recom = $_M->alias('pl')->field('id,title')
                                ->where($map_r)->limit(4)->order('add_time desc')->select();
                $this->assign('recom_list', $recom);
            }
        }
        $this->assign('group_list', $group_list);
        $this->assign('current_nav', 'against');
        $this->assign('title','live');
        $this->display();
    }

    public function live_score() {
        $map['g.years']='2014-2016';
        $map['g.union_id']=67;
        $map['g.status']=1;
        $map['runno']=array('not in','9850,9851');
        $list = M('GameFbinfo')->alias('g')->field('r.run_name,g.rno,g.runno,g.game_id,g.gtime,g.game_half_time,g.game_state,g.score,home_team_name,away_team_name')
                ->join("LEFT JOIN qc_run_fb r ON g.runno=r.id")
                ->where($map)->order('game_state desc,gtime asc')->select();
        foreach ($list as &$v) {
            $v['run_name'] = explode(',', $v['run_name']);
            $v['home_team_name'] = explode(',', $v['home_team_name']);
            $v['away_team_name'] = explode(',', $v['away_team_name']);
        }
        $this->assign('list', $list);
        $this->assign('title','live');
        $this->display();
    }

    public function live_striker() {
        $list = M('EuroScorer')->order('score desc,penalty desc')->select();
        $this->assign('list', $list);
        $this->assign('title','live');
        $this->display();
    }

    public function live_integral() {
        $list = M('EuroIntegral')->order('group_letter,integral desc')->select();
        $data=[];
        foreach ($list as $v) {
            $data[$v['group_letter']][] = $v;
        }
        $this->assign('list', $data);
        $this->assign('title','live');
        $this->display();
    }
    /*
     * ---视频---
     */
    public function video() {
        $type=I('get.type',5,'intval');
        if($type<1 && $type>5){
            $this->error('参数有误!');
        }
        switch ($type) {
            case 1:
                $where['runno']=9852;
                $group = I('get.group', 'A');
                $group = strtoupper($group);
                $where['rno']=$group;
                break;
            case 2:
                $where['runno'] = 12666;
                break;
            case 3:
                $where['runno'] = 12672;
                break;
            case 4:
                $where['runno'] = 12692;
                break;
            case 5:
                $where['gf.id'] = 860887;
                break;
        }
        
        $_M=M('highlights');
        $where['h.status']=1;
        $where['h.m_url']=array(array('neq',''),array('EXP','IS NOT NULL'));
        $list=$_M->alias('h')->field('h.id,title,remark,img,m_url,m_ischain')
                ->join('qc_game_fbinfo gf ON h.game_id=gf.game_id')
                ->where($where)->order('h.add_time desc,h.id desc')->select();
        foreach ($list as &$v){
            $v['img'] = @Think\Tool\Tool::imagesReplace($v['img']);
        }
        $this->assign('list',$list);
        $this->display();
    }
    public function video_detail() {
        $id=I('get.id',0,'intval');
        if($id<1){
            $this->error("参数有误!");
        }
        $_M=M('highlights');
        $data=$_M->field('title,remark,m_url,img')->where(array('id'=>$id,'status'=>1))->find();
        if($data){
            //点击量加1
            $_M->where(array('id'=>$id,'status'=>1))->setInc('click_num');
        }else{
            $this->error("找不到相关内容！");
        }
        $data['img']=@Think\Tool\Tool::imagesReplace($data['img']);
        $this->assign('data',$data);
        $this->display();
    }
    /*
     * ---图片---     
     */
    public function photo() {
        $_M=M('Gallery');
        $map['gc.status']=1;
        $map['g.class_id']=28;
        $map['g.status']=1;
        $list = $_M->alias('g')->field('g.id,g.carousel_recommend,g.img_array,g.title')
                ->join('__GALLERY_CLASS__ gc ON g.class_id=gc.id')
                ->where($map)->order('g.add_time desc,g.id desc')->limit(6)->select();
        if($list){
            foreach ($list as  &$v) {
               $v['img_array']= json_decode($v['img_array'], true);
               $v['img_array'][1] = @Think\Tool\Tool::imagesReplace($v['img_array'][1]);
            }
            $this->assign('list',$list);
        }
        //轮播图
        $recommend=$this->get_recommend('appOZB');
        $this->assign('recommend',$recommend);
        $this->display();
    }
    public function photo_detail() {
        $id=I('get.id',0,'intval');
        if($id<1){
            $this->error('参数有误!');
        }
        $_M=M('gallery');
        $data=$_M->alias('g')->field('g.id,title,img_array,g.remark,describe')
                ->where(array('g.status'=>1,'g.id'=>$id))->find();
        if($data){
            //点击量加1
            $_M->where(array('id'=>$id,'status'=>1))->setInc('click_number');
        }else{
            $this->error("找不到相关内容！");
        }
        $data['img_array']= json_decode($data['img_array'], true);
        foreach ($data['img_array'] as  &$v) {
            $v = @Think\Tool\Tool::imagesReplace($v);
        }
        $data['describe']= json_decode($data['describe'], true);
        $this->assign('data',$data);
        $this->display();
    }
        //ajax 加载更多新闻
    public function photo_load() {
        $p = isset($_POST['k']) ? intval(trim($_POST['k'])) : 0;
        $map['gc.status']=1;
        $map['gc.id']=28;
        $map['g.status']=1;
        $_M=M('Gallery');
        $total = $_M->alias('g')
                ->join('__GALLERY_CLASS__ gc ON g.class_id=gc.id')
                ->where($map)->count(); //数据记录总数
        $num = 6; //每页记录数
        $totalpage = ceil($total / $num); //总计页数
        $limitpage = ($p - 1) * $num; //每次查询取记录

        if ($p > $totalpage) {
            //超过最大页数，退出
            $this->error("没有更多了");
        }
        $list=$_M->alias('g')->field('g.id,g.carousel_recommend,g.img_array,g.title')
                ->join('__GALLERY_CLASS__ gc ON g.class_id=gc.id')
                ->where($map)->order('g.add_time desc,g.id desc')->limit($limitpage, $num)->select();
        if(!$list){
            $this->error("没有更多了");
        }
        foreach ($list as  &$v) {
           $v['img_array']= json_decode($v['img_array'], true);
           $v['img_array'][1] = @Think\Tool\Tool::imagesReplace($v['img_array'][1]);
        }
        $this->success($list);
    }
    /*
     * ---推荐---
     */
    public function recommend() {
        $map['id']=array("IN",array(305,322,323,324,325));
        $expert=M('front_user')->field('id,nick_name,head')->where($map)->order('sort')->limit(5)->select();
        $id=I('get.expert',$expert[0]['id'],'intval');
        $list = M('PublishList')->alias('pl')->field('pl.id,user_id,short_title,img,remark')
                ->join('qc_front_user fu on pl.user_id=fu.id')
                            ->where(array('user_id'=>$id,'pl.status'=>1,'class_id'=>10))
                            ->limit(5)
                            ->order('pl.add_time desc')->select();
        foreach ($list as &$v) {
                $v['img'] = @Think\Tool\Tool::imagesReplace($v['img']);
            }
        $this->assign('user_id',$id);
        $this->assign('list',$list);
        $this->assign('expert',$expert);
        $this->assign('title','recommend');
        $this->display();
    }
    public function recommend_detail(){
        $id=I('get.id',0,'intval');
        if ($id < 1) {
            $this->error("找不到相关页面！");
        }
        $_M = M('PublishList');
        $data = $_M->field('title,class_id,label,add_time,source,content')->where(array('id' => $id, 'status' => 1))->find();
        if($data){
            //点击量加1
            M('PublishList')->where(array('id'=>$id,'status'=>1))->setInc('click_number');
        }else{
            $this->error("找不到相关内容！");
        }
        if(!empty($data['label'])){
            $lable = explode(',',$data['label']);
            $this->assign('lable', $lable);
        }
        $this->assign('data', $data);
        $this->assign('title','recommend');
        $this->display();
    }
    
    //ajax 加载更多新闻
    public function recomend_load() {
        $p = isset($_POST['k']) ? intval(trim($_POST['k'])) : 0;
        $user_id = I('user_id', 0, 'intval');
        if ($user_id < 1) {
            $this->error("参数有误！");
        }
        $map['pl.status']=1;
        $map['class_id']=10;
        $map['user_id']=$user_id;
        $_M=M('PublishList');
        $total = $_M->alias('pl')->where($map)->count(); //数据记录总数
        $num = 5; //每页记录数
        $totalpage = ceil($total / $num); //总计页数
        $limitpage = ($p - 1) * $num; //每次查询取记录

        if ($p > $totalpage) {
            //超过最大页数，退出
            $this->error("没有更多了");
        }
        $list=$_M->alias('pl')->field('pl.id,user_id,short_title,img,remark')
                ->join('qc_front_user fu on pl.user_id=fu.id')
                ->where($map)->order('pl.add_time desc')->limit($limitpage, $num)->select();
        if(!$list){
            $this->error("没有更多了");
        }
        foreach($list as &$v){
            $v['img'] = @Think\Tool\Tool::imagesReplace($v['img']);
        }
        $this->success($list);
    }
    public function live_against(){
        $this->display();
    }
}
