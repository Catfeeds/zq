<?php
/**
 * 推荐
 *
 * @author chenzj <443629770@qq.com>
 *
 * @since  2016-04-19
 */
class DetailsBkController extends CommonController {
    public $_GameTime;
    protected function _initialize() {
        $scheid=I('get.scheid',0,"intval");
        if($scheid<1){
            $this->error("参数有误!");
        }
        if(!cookie('redirectUrl')){
            cookie('redirectUrl',U('Index/index'));
        }
        $against=M("GameBkinfo")->field('union_id,union_name,game_state,gtime,home_team_id,home_team_name,away_team_id,away_team_name,score,game_date,game_time')->where(array('status'=>1,'game_id'=>$scheid))->find();
        
        $this->_GameTime=$against['gtime'];
        $against=getTeamLogo($against,2);
        $against['home_team_name']=  explode(',', $against['home_team_name']);
        $against['away_team_name']=  explode(',', $against['away_team_name']);
        $against['union_name']=  explode(',', $against['union_name']);
        $user=session('user_auth');
        if($user){
            $this->assign('user_auth',$user);
        }
        $this->assign('against',$against);
        $this->assign('scheid',$scheid);
    }
    
    public function index() {
        $this->redirect('data');
    }
    
    //数据页面
    public function data() {
        $gameId=I('get.scheid',0,'intval');
        if($gameId<1){
            $this->error('参数有误!');
        }
        $data=$this->get_curl('Api203/AppBk'."/analysis","gameId=$gameId",C('CURL_DOMAIN'));
        $list=array();
        if($data['status']==1){
            foreach ($data['data'] as $v){
                $list[$v['name']]=$v['content'];
            }
            foreach($list['let_panlu'] as $k=>$v){
                $list['panlu'][$k]=$v;
            }
            foreach($list['total_panlu'] as $k=>$v){
                if(!empty($v)){
                    for($i=0;$i<4;$i++){
                        $rsl=array_merge($list['panlu'][$k][$i],$v[$i]);
                        $list['panlu'][$k][$i]=$rsl;
                    }
                }
            }
            $this->assign('data',$list);
        }
        $this->display();
    }
    
    //赛况
    public function event_case(){
        $gameId=I('get.scheid',0,'intval');
        if($gameId<1){
            $this->error('参数有误!');
        }
        $data=$this->get_curl(C('API_BKURL')."/bkSituation","gameId=$gameId",C('CURL_DOMAIN'));
        if($data['data']['live']){
            krsort($data['data']['live']);
        }
        $homeScroe = $awayScroe = 0;
        foreach ($data['data']['score'] as $k => $v)
        {
            $homeScroe += $v[0];
            $awayScroe += $v[1];
        }
      
        $this->assign('homeScroe',$homeScroe);
        $this->assign('awayScroe',$awayScroe);
        $this->assign('data',$data['data']);
        $this->assign('nav','event');
        $this->display();
    }
    
    //战报
    public function event_report(){
       $this->display();  
    }
    
    //技术
    public function event_technology(){
        $gameId=I('get.scheid',0,'intval');
        if($gameId<1){
            $this->error('参数有误!');
        }
       $data=$this->get_curl(C('API_BKURL')."/bkTech","gameId=$gameId",C('CURL_DOMAIN'));
        
       $this->assign('nav','event');
       $this->assign('data',$data['data']);
       $this->display();
    }
    
    //阵容
    public function event_squad(){
        $gameId=I('get.scheid',0,'intval');
        if($gameId<1){
            $this->error('参数有误!');
        }
       $data=$this->get_curl(C('API_BKURL')."/bkSquad","gameId=$gameId",C('CURL_DOMAIN'));
        $list=[];
       if($data['data']){
           foreach ($data['data'] as $k=>$v){
               foreach ($v as $ko=>$vo){
                   foreach ($vo as $key=>$val){
                      $list[$k][$key][$ko]=$val;
                   }
               }
           }
       }
       $this->assign('nav','event');
       $this->assign('data',$list);
        $this->display();
    }
    
    //赔率-亚赔
    public function odds_asia(){
        $gameId=I('get.scheid',0,'intval');
        if($gameId<1){
            $this->error('参数有误!');
        }
        $data=$this->get_curl(C('API_BKURL')."/bkMatchOdds","gameId=$gameId&type=1",C('CURL_DOMAIN'));
        $this->assign('data',$data['data']);
        $this->assign('nav','odds');
        $this->display();
    }
    
    //赔率-欧赔
    public function odds_euro(){
        $gameId=I('get.scheid',0,'intval');
        if($gameId<1){
            $this->error('参数有误!');
        }
        $data=$this->get_curl(C('API_BKURL')."/bkMatchOdds","gameId=$gameId&type=2",C('CURL_DOMAIN'));
        $this->assign('data',$data['data']);
        $this->assign('nav','odds');
        $this->display();
    }
    //赔率-大小
    public function odds_bigs(){
        $gameId=I('get.scheid',0,'intval');
        if($gameId<1){
            $this->error('参数有误!');
        }
        $data=$this->get_curl(C('API_BKURL')."/bkMatchOdds","gameId=$gameId&type=3",C('CURL_DOMAIN'));
        $this->assign('data',$data['data']);
        $this->assign('nav','odds');
        $this->display();
    }
    
    //推荐
    public function odd_guess(){
        $this->display();
    }
}