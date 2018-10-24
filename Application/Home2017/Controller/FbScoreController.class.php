<?php

/**
 * 比分
 *
 * @author chenzj <443629770@qq.com>
 *
 * @since  2016-05-23
 */
use Think\Controller;
use Think\Tool\Tool;

class FbScoreController extends CommonController {

    protected function _initialize() {
        $scheid=I('get.scheid',0,"intval");
        if($scheid>0){
            $against=M("game_fbinfo")->field('union_name,game_state,gtime,home_team_id,home_team_name,away_team_id,away_team_name,score,game_date,game_time')->where(array('status'=>1,'game_id'=>$scheid))->find();
            $against=getTeamLogo($against);
            $against['home_team_name']=  explode(',', $against['home_team_name']);
            $against['away_team_name']=  explode(',', $against['away_team_name']);
            $against['union_name']=  explode(',', $against['union_name']);
            $against['time']=strtotime($against['game_date'].$against['game_time']);
            $this->assign('against',$against);
        }
    }

    public function index() {
        //获取即时页面数据
        $data = $this->get_curl("Home/Appdata/fb", 'key=no', C('CURL_DOMAIN'));
        if ($data['status'] === 1) {
            array_unique($data['data']);
            $this->assign('list', $data['data']);
        }
        //获取友情链接
        $Link = M("Link")->where(array('status'=>1,'position'=>3))->order("sort asc")->select();
        $this->assign('Link', $Link);
        
        //广告图片
//        $adver = @Think\Tool\Tool::getAdList(17,5,2);
//        foreach ($adver as $k => $v)
//        {
//            unset($adver[$k]['id']);
//        }
//        $this->assign('adver_list',$adver);
        $this->display();
    }

    public function ScoreInstant() {
        $data = $this->get_curl("Home/Appdata/change", 'key=no', C('CURL_DOMAIN'));

        if ($data['status'] == 0 || empty($data['data'])) {
            $this->error("没有数据！");
        }
        $this->success($data['data']);
    }

    public function goal() {
        $data = $this->get_curl("Home/Appdata/goal", 'id=3&key=no', C('CURL_DOMAIN'));
        if ($data['status'] == 0 || empty($data['data'])) {
            $this->error("没有数据！");
        }
        $this->success($data['data']);
    }

    //内页
    //数据页面
    public function data() {
        $scheid=I('get.scheid',0,"intval");
        if($scheid<1){
            $this->error("参数有误!");
        }
        $data=$this->get_curl("Home/Appdata/analysis","gameId=$scheid&key=no",C('CURL_DOMAIN'));
        $list=array();
        if($data['status']==1){
            foreach ($data['data'] as $v){
                $list[$v['name']]=$v['content'];
            }
            foreach($list['match_integral'] as $v){
                $list['integral'][$v[0]][]=$v;
            }
            foreach($list['match_three'] as $v){
                $list['three'][$v[0]][]=$v;
            }
            foreach($list['match_panlu'] as $v){
                $list['panlu'][$v[0]][]=$v;
            }
            $this->assign('data',$list);
        }
        $this->display();
    }
    //赔率-亚赔
    public function odds_asia(){
        $scheid=I('get.scheid',0,"intval");
        if($scheid<1){
            $this->error("参数有误!");
        }
        $data=$this->get_curl("Home/Appdata/asianOdds ","gameId=$scheid&key=no",C('CURL_DOMAIN'));
        if($data['status']==1 && !empty($data['data'])){
            foreach ($data['data'] as &$v){
                $v[1]=sprintf('%.2f',$v[1]);
                $v[3]=sprintf('%.2f',$v[3]);
                if(strpos($v[2],'/')===false){
                    $v['res_star']=$v[2];
                }else{
                    $arr=explode('/', $v[2]);
                    $v['res_star']=($arr[0]+$arr[1])/2;
                }
                if(empty($v[4])){
                    $v[4]=$v[1];
                }
                if(empty($v[5])){
                    $v[5]=$v[2];
                }
                if(empty($v[6])){
                    $v[6]=$v[3];
                }
                $v[4]=sprintf('%.2f',$v[4]);
                if(strpos($v[5],'/')===false){
                    $v['res_now']=$v[5];
                }else{
                    $arr=explode('/', $v[5]);
                    $v['res_now']=($arr[0]+$arr[1])/2;
                }
                $v[6]=sprintf('%.2f',$v[6]);
            }
            $this->assign('data',$data['data']);
        }
        $this->assign('nav','odds');
        $this->display();
    }
    //赔率-欧赔
    public function odds_euro() {
        $scheid=I('get.scheid',0,"intval");
        if($scheid<1){
            $this->error("参数有误!");
        }
        $data=$this->get_curl("Home/Appdata/europeOdds ","gameId=$scheid&key=no",C('CURL_DOMAIN'));
        if($data['status']==1 && !empty($data['data'])){
            foreach ($data['data'] as &$v){
                $v[2]=sprintf('%.2f',$v[2]);
                $v[3]=sprintf('%.2f',$v[3]);
                $v[4]=sprintf('%.2f',$v[4]);
                if(empty($v[5])){
                    $v[5]=$v[2];
                }
                if(empty($v[6])){
                    $v[6]=$v[3];
                }
                if(empty($v[7])){
                    $v[7]=$v[4];
                }
                $v[5]=sprintf('%.2f',$v[5]);
                $v[6]=sprintf('%.2f',$v[6]);
                $v[7]=sprintf('%.2f',$v[7]);
            }
            $this->assign('data',$data['data']);
        }
        $this->assign('nav','odds');
        $this->display();
    }
    //赔率-大小
    public function odds_bigs() {
        $scheid=I('get.scheid',0,"intval");
        if($scheid<1){
            $this->error("参数有误!");
        }
        $data=$this->get_curl("Home/Appdata/ballOdds ","gameId=$scheid&key=no",C('CURL_DOMAIN'));
        if($data['status']==1 && !empty($data['data'])){
            foreach ($data['data'] as &$v){
                $v[1]=sprintf('%.2f',$v[1]);
                $v[3]=sprintf('%.2f',$v[3]);
                if(strpos($v[2],'/')===false){
                    $v['res_star']=$v[2];
                }else{
                    $arr=explode('/', $v[2]);
                    $v['res_star']=($arr[0]+$arr[1])/2;
                }
                if(empty($v[4])){
                    $v[4]=$v[1];
                }
                if(empty($v[5])){
                    $v[5]=$v[2];
                }
                if(empty($v[6])){
                    $v[6]=$v[3];
                }
                $v[4]=sprintf('%.2f',$v[4]);
                if(strpos($v[5],'/')===false){
                    $v['res_now']=$v[5];
                }else{
                    $arr=explode('/', $v[5]);
                    $v['res_now']=($arr[0]+$arr[1])/2;
                }
                $v[6]=sprintf('%.2f',$v[6]);
            }
            $this->assign('data',$data['data']);
        }
        $this->assign('nav','odds');
        $this->display();
    }
    //事件-赛况
    public function event_case() {
        $scheid=I('get.scheid',0,"intval");
        if($scheid<1){
            $this->error("参数有误!");
        }
        $data=$this->get_curl("Home/Appdata/detail ","gameId=$scheid&key=no",C('CURL_DOMAIN'));
        if($data['status']==1){
            $this->assign('data',$data['data']);
        }
        $this->assign('nav','event');
        $this->display();
    }
    //事件-技术
    public function event_technology() {
        $scheid=I('get.scheid',0,"intval");
        if($scheid<1){
            $this->error("参数有误!");
        }
        $data=$this->get_curl("Home/Appdata/skill ","gameId=$scheid&key=no",C('CURL_DOMAIN'));
        if($data['status']==1){
        $array=array("先开球", "第一个角球", "第一张黄牌", "射门次数", "射正次数", "犯规次数", "角球次数", "角球次数(加时)", "任意球次数", "越位次数", "乌龙球数", "黄牌数", "黄牌数(加时)", "红牌数", "控球时间", "头球", "救球", "守门员出击", "丟球", "成功抢断", "阻截", "长传", "短传", "助攻", "成功传中", "第一个换人", "最后换人", "第一个越位", "最后越位", "换人数", "最后角球", "最后黄牌", "换人数(加时)", "越位次数(加时)", "射门不中", "中柱", "头球成功次数", "射门被挡", "铲球", "过人次数", "界外球", "传球次数", "传球成功次数");
            foreach ($data['data'] as &$v){
                $v['homerate']=round($v[1]/($v[1]+$v[2])*100);
                $v['awayrate']=round($v[2]/($v[1]+$v[2])*100);
                $v['title']=$array[$v[0]];
            }
            $this->assign('data',$data['data']);
        }
        $this->assign('nav','event');
        $this->display();
    }
    //事件-阵容
    public function event_squad() {
        $scheid=I('get.scheid',0,"intval");
        if($scheid<1){
            $this->error("参数有误!");
        }
        $data=$this->get_curl("Home/Appdata/lineup","nosign=appdata&gameId=$scheid",C('CURL_DOMAIN'));
        if($data['status']==1 && !empty($data['data'][0])){
            $list=array();
            foreach ($data['data'] as $k=>$v){
                foreach ($v as $key=>$val){
                    if($val[3]=='1'){
                        $list[0][$key][$k]=$val;
                    }else if($val[3]=='0'){
                        $list[1][$key][$k]=$val;
                    }
                }
            }
            foreach($list as &$j){
                foreach($j as &$p){
                    if(count($p)==1){
                        $arr=array(
                            0=>'',
                            1=>'',
                        );
                        if(isset($p[0])){
                            $p[1]=$arr;
                        }else{
                             $p[0]=$arr;
                        }
                        sort($p);
                    }
                }
            }
            $this->assign('list',$list);
        }
        $this->assign('nav','event');
        $this->display();
    }
    public function company() {
        $this->display();
    }

    //推荐
    public function odd_guess(){
        $this->display();
    }
    
}
