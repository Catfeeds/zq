<?php
/**
 * @直播，视频
 * @author junguo 2016.3.15
 */

use Think\Controller;
use Think\Tool\Tool;

class VideoController extends CommonController
{
    public  $live_arr = array( 1,2,50,3,4,5,6 );//比赛中
    public  $end_arr = array( -12,-13,-1,-10 );//已完场
    public  $wait_arr = array( 0,11,-14 );//未开赛
    public  $IMG_SERVER;
    public  $lang;
    public function _initialize(){
        parent::_initialize();
        $this->lang = $_COOKIE['lang']?$_COOKIE['lang']:0;
        $this->IMG_SERVER = C('IMG_SERVER');
    }
    /**
     * @ 视频主页
     *
     * */
    public function index()
    {
        $type        = I('type');
        $game_date   = I('game_date')?urldecode(I('game_date')):date('m月d日',time());

        $list1 = array();
        $list2 = array();
        //type 1 足球  2 篮球 0  全部
        $str  = date('Y').$game_date;
        $str  = str_replace('月','',$str);
        $str  = str_replace('日','',$str);
        //dump($str);
        if(date('m',strtotime($str)) == '01'){
            $str = '2017'.$game_date;
        }
        $str  = str_replace('月','',$str);
        $str  = str_replace('日','',$str);
        //dump($str);
        if(!$type){
            //全部
            $list = array_merge_recursive(self::getGameFb($str),self::getGameBk($str));
        }elseif ($type == 1) {
            //足球
            $list = self::getGameFb($str);
        }elseif ($type == 2) {
            //篮球
            $list = self::getGameBk($str);
        }
        foreach($list as $k=>$v){
            if( $v['score']=='-' || $v['score']==''||in_array($v['game_state'],$this->wait_arr) )
            {
                $v['score'] ='VS';
            }
            if($v['game_state']==0){
                //前瞻
                $v['prospect'] = M('highlights')->where(['type'=>$v['type'],'game_id'=>$v['game_id'],'is_prospect'=>1])->count();
            }elseif ($v['game_state']=='-1'){
                //集锦
                $v['jijin_num']  = M('highlights')->where(array('game_id'=>$v['game_id'],'type'=>$v['type'],'is_prospect'=>0))->count();
            }
            $v['web_video']  =  $v['web_video'] ? json_decode( $v['web_video'],true) : array();
            if( in_array($v['game_state'],$this->live_arr))
            {
                $v['is_live'] =1;
            }else{
                $v['is_live'] =0;
            }
            foreach ($v['web_video'] as $cc=>$uu){
                if(!$uu['weburl']){
                    unset($v['web_video'][$cc]);
                }else if( in_array($v['game_state'],$this->wait_arr)){
                   $v['is_live'] =3;
                }
            }
            $v['web_video_num'] = count($v['web_video']);
            if( in_array($v['game_state'],$this->live_arr))  //比赛中
            {
                $v['play_state'] =1;
            }else if($v['game_state']==0||in_array($v['game_state'],$this->wait_arr)){ //未开赛
                $v['play_state'] =2;
            }else if(in_array($v['game_state'],$this->end_arr)){ //已完场
                $v['play_state'] =3;
            }
            $this->getlogo($v,$v['type']);
            $list[$k]  = $v;
        }
        //对数组进行排序
        foreach ($list as $v) {
            $game_state[] = $v['game_state'];
            $game_time[]  = $v['game_time'];
        }
        array_multisort($game_state, SORT_DESC,$game_time, SORT_ASC, $list);

        //dump($list);
        $week  = array();
        $date  = time()-3*86400;
        for( $i=0; $i<7; $i++ )
        {
           $stime = $date + $i*86400;
           $day   = date('m月d日', $stime);
           $w     = array(
                     'day'   => $day,
                     'week'  => getWeek( date("w",$stime) ),
                    );
           if( $game_date == $day )
           {
               $w['is_on'] =1;
           }
           else{
               $w['is_on'] =0;
           }
           $week[]  =  $w;
        }

        $today =array(  'day'=>date('m月d日', time()),
                        'week'=>getWeek( date("w",time()) )
        );
        $this->assign('today',$today);
        $this->assign('lang',$this->lang);
        $this->assign('week',$week);
        $this->assign('play',$list);

        if(IS_AJAX){
            $lis='';
            foreach($list as $k=>$v){
                if($this->lang == 0){
                    $union_name = '<span>'.switchName(0,$v['union_name']).'</span><span class="hidden">'.switchName(1,$v['union_name']).'</span>';
                    $home_team_name = '<span><a href="#">'.switchName(0,$v['home_team_name']).'</a></span><span class="hidden"><a href="#">'.switchName(1,$v['home_team_name']).'</a></span>';
                    $away_team_name = '<span><a href="#">'.switchName(0,$v['away_team_name']).'</a></span><span class="hidden"><a href="#">'.switchName(1,$v['away_team_name']).'</a></span>';
                }else{
                    $union_name = '<span class="hidden">'.switchName(0,$v['union_name']).'</span><span>'.switchName(1,$v['union_name']).'</span>';
                    $home_team_name = '<span class="hidden"><a href="#">'.switchName(0,$v['home_team_name']).'</a></span><span><a href="#">'.switchName(1,$v['home_team_name']).'</a></span>';
                    $away_team_name = '<span class="hidden"><a href="#">'.switchName(0,$v['away_team_name']).'</a></span><span><a href="#">'.switchName(1,$v['away_team_name']).'</a></span>';
                }
                $li='<li data-id="'.$v['id'].'"><div class="live-con clearfix"><div class="lc-left"><span class="s1">'.$v['game_time'].'</span><span class="s2"><a href="#"><img src="'.$v['team_logo'].'"/></a></span>
                    <span class="s3 sefew">'.$union_name.'</span> </div> <div class="lc-center">
                    <span class="s2">'.$home_team_name.'</span><span class="s1"><a href="#"><img src="'.$v['home_logo'].'" /></a></span> <span class="s3 ';
                if($v['play_state']==3) $li .=' ing';
                if($v['play_state']==1) $li .=' over';
                $li  .=' " >'.$v['score'].'</span> <span class="s5"><a href="#"><img src="'.$v['away_logo'].'" /></a></span> <span class="s4">'.$away_team_name.'</span></div><div class="lc-right">';

                if( $v['is_live']==1|| $v['is_live']==3)
                {
                    //直播 或者有地址
                    if( $v['is_live']==3){
                        $li   .='  <a href="javascript:;" class="zb-common zbz">播放源 <span></span></a> <div class="zb-list clearfix"><ul>';
                    }else{
                        if( $v['web_video_num']>=1 ){
                            $li   .='  <a href="javascript:;" class="zb-common zbz">直播中<span></span></a> <div class="zb-list clearfix"><ul>';
                        }
                        else{
                            $li   .='  <a href="javascript:;" class="zb-common zbz cdefault">进行中<span></span></a> <div class="zb-list clearfix"><ul>';
                        }
                    }
                    foreach($v['web_video'] as $kk=>$vv ){
                        if($vv['webname']) {
                            if( $vv['web_ischain']!=1 ){
                                $li.='  <li><a href="'.U('/video/live/?webid='.$vv['webid'].'&type='.$v['type'].'&id='.$v['id']).'" target="_blank"  >'.$vv['webname'].'</a></li>';
                            }else{
                                $li.='  <li><a href="'.$vv['weburl'].'" target="_blank" >'.$vv['webname'].'</a></li>';
                            }
                        }
                    }
                    $li.=' </ul></div>';
                }
                else
                {  
                    // 非直播
                    if( $v['game_state'] == 0 ) {  //未开始
                        if($v['prospect'] > 0){ //有前瞻
                            $li .= ' <a  target="_blank"  href="' . U('/video/lives/?type=' . $v['type'] . '&id=' . $v['id']) . '" class="zb-common play">前瞻</a>';
                        }else{
                            $li .=' <a href="javascript:;" class="zb-common" style="color: #999999; cursor: default;">未开赛</a>';
                        }
                    }else if($v['game_state'] == -1)  {//已完场
                        if($v['jijin_num']>0) {  //有集锦
                            $li .= ' <a  target="_blank"  href="' . U('/video/lives/?type=' . $v['type'] . '&id=' . $v['id']) . '" class="zb-common play">集锦</a>';
                        }else{
                            $li  .='<a href="javascript:;" class="zb-common gameover cdefault">已完场</a>';
                        }
                    }else{
                        switch ($v['game_state']) {
                            case '-2':
                            case '-11': $desc = '待定'; break;
                            case '-10': $desc = '取消'; break;
                            case '-12': $desc = '腰斩'; break;
                            case '-13': $desc = '中断'; break;
                            case '-14': $desc = '推迟'; break;
                        }
                        $li  .='<a href="javascript:;" class="zb-common gameover cdefault">'.$desc.'</a>';
                    }
                }
                $li   .=' </div></div></li> ';
                $lis  .=$li;
            }

           $this->ajaxReturn($lis);
        }else{
            $this->display();
        }

    }

    //获取足球赛事
    public function getGameFb($str){
        $img = $this->IMG_SERVER;
        $sql ="SELECT
            f.id,f.game_time,f.union_name,f.game_id,f.score,f.web_video,f.game_state,f.home_team_name,f.away_team_name, 
            concat('$img',g.img_url) home_logo,
            concat('$img',g2.img_url) away_logo,
            u.img team_logo
        FROM qc_game_fbinfo f
        LEFT JOIN qc_game_team g ON g.team_id = f.home_team_id
        LEFT JOIN qc_game_team g2 ON g2.team_id = f.away_team_id
        LEFT JOIN qc_union u ON u.union_id = f.union_id
        WHERE (f.status = 1)
        AND (f.is_video = 1)
        AND (f.game_date ='$str')";

        $list= M()->query($sql);
        if(!$list){
            return array();
        }
        foreach ($list as $key => $value) {
            $list[$key]['type'] = 1;
        }
        return $list;
    }

    //获取篮球赛事
    public function getGameBk($str){
        $img = $this->IMG_SERVER;
        $sql ="SELECT
            f.id,f.game_time,f.union_name,f.game_id,f.score,f.web_video,f.game_state,f.home_team_name,f.away_team_name, 
            concat('$img',g.img_url) home_logo,
            concat('$img',g2.img_url) away_logo,
            u.img team_logo
        FROM qc_game_bkinfo f
        LEFT JOIN qc_game_teambk g ON g.team_id = f.home_team_id
        LEFT JOIN qc_game_teambk g2 ON g2.team_id = f.away_team_id
        LEFT JOIN qc_bk_union u ON u.union_id = f.union_id
        WHERE (f.status = 1)
        AND (f.is_video = 1)
        AND (f.game_date ='$str')";
        $list= M()->query($sql);
        if(!$list){
            return array();
        }
        foreach ($list as $key => $value) {
            $list[$key]['type'] = 2;
        }
        return $list;
    }
    
    /**
     * @desc: logo
     * */
    function  getlogo(&$v,$type=1){
        if(!$v['id'])return ;
        $img = $this->IMG_SERVER;
        if(!is_file('.'.$v['team_logo'])||!$v['team_logo'])
        {
            if($v['type']==1){
                $v['team_logo'] ='/Public/Home/images/common/zu.png';
            }else{
                $v['team_logo'] ='/Public/Home/images/common/lan.png';
            }

        }
        if($v['home_logo']==$img ||!$v['home_logo'])
        {
            $v['home_logo'] ='/Public/Home/images/common/home_def.png';
        }

        if($v['away_logo']==$img ||!$v['away_logo'])
        {
            $v['away_logo'] ='/Public/Home/images/common/away_def.png';
        }
    }
    function articleList($game_id)
    {
        $where['status'] = 1;
        $where['game_id'] = $game_id;

        return  $articleList = M('PublishList')->field(['id','title','FROM_UNIXTIME( add_time, \'%m/%d\') day','FROM_UNIXTIME( add_time, \'%H:%i\') hour'])
            ->where($where)
            ->order('is_recommend desc,is_channel_push desc,add_time desc')
            ->limit(10)
            ->select();
    }
    /**
    * @直播间
    *
    */
    public function live()
    {
        $info = $this->getdata();

        $_type            ="[\"先开球\", \"第一个角球\", \"第一张黄牌\", \"射门次数\", \"射正次数\", \"犯规次数\", \"角球次数\", \"角球次数(加时)\", \"任意球次数\", \"越位次数\", \"乌龙球数\", \"黄牌数\", \"黄牌数(加时)\", \"红牌数\", \"控球时间\", \"头球\", \"救球\", \"守门员出击\", \"丟球\", \"成功抢断\", \"阻截\", \"长传\", \"短传\", \"助攻\", \"成功传中\", \"第一个换人\", \"最后换人\", \"第一个越位\", \"最后越位\", \"换人数\", \"最后角球\", \"最后黄牌\", \"换人数(加时)\", \"越位次数(加时)\", \"射门不中\", \"中柱\", \"头球成功次数\", \"射门被挡\", \"铲球\", \"过人次数\", \"界外球\", \"传球次数\", \"传球成功次数\"]";
        $type            = json_decode($_type);
       //$type1          = array(1=>'入球','红牌','黄牌','点球','乌龙','两黄变红','换人');
        $type2          = array(1=>'goal','red-card','yellow-card','penalty','oolong','两黄变红','up down');
        //赛事
        $appService           = new \Home\Services\AppdataService();
        $res                  = $appService->getDetailApp($info['game_id']);
       //$res                  ="{\"status\":1,\"data\":[[\"1225499\",\"1\",\"1\",\"38\",\"Nikas\",\"\",\"Nikas\"],[\"1225499\",\"1\",\"1\",\"53\",\"Payne\",\"\",\"Payne\"],[\"1225499\",\"1\",\"7\",\"58\",\"Payne\",\"\",\"Payne\"],[\"1225499\",\"1\",\"1\",\"83\",\"Taneski\",\"\",\"Taneski\"]]}";
        $d                    = json_decode($res,true);

        foreach ($d['data'] as $k=>$v){
            $v[2]           = $type2[$v[2]];
            $d['data'][$k] = $v;
        }
        $info['game_list']  = $d['data'];

        //阵容
        $res            = $appService->getSkillApp($info['game_id']);
      // $res            = "{\"status\":1,\"data\":[[\"6\",\"10\",\"10\"],[\"11\",\"2\",\"3\"],[\"3\",\"20\",\"10\"],[\"4\",\"9\",\"7\"],[\"34\",\"11\",\"3\"],[\"14\",\"58%\",\"42%\"]]}";
        $d              = json_decode($res,true);
        foreach ($d['data'] as $k=>$v){
            $v[0]            = $type[$v[0]];
            $v[2]            = str_replace('%','',$v[2]);
            $v[1]            = str_replace('%','',$v[1]);
            $total           = $v[1] + $v[2];
            $v[3]            = round($v[1]/$total*100);
            $v[4]            = round($v[2]/$total*100);
            $d['data'][$k] = $v;
        }
        $info['skill_list']  = $d['data'];

        $game_id= $info['game_id'];
       // $game_id = 1213862;
        $news_list=  $this->articleList($game_id);
        $info['news_list']  = $news_list;
        $this->assign('info',$info);
        $this->display();
    }
    /**
     *@集锦
     *
     * */
    public function lives()
    {
        $info  = $this->getdata();
        $jj_id = I('jj_id');
        $where['status']   = 1;
        if($info['game_id'] > 0){
            $where['game_id']   = $info['game_id'];
        }
        $prefix = C('IMG_SERVER');

        $jijin   = M('highlights')->where($where)->field("*,concat('$prefix',img) img")->order('click_num desc,add_time desc')->select();
        $url     = '';

        foreach ($jijin as $k=>$v){
            $jijin[$k]['logo']   = $v['img'];
            if($jj_id== $v['id']){
                $url = $v['web_url'];
                $info['title'] =$v['title'];
                $info['remark'] =$v['remark'];
            }
        }

        //浏览次数
        if($jj_id){
            $wh  = array('id'=>$jj_id);
        }else{
            $wh  = array('id'=>$jijin[0]['id']);
            $url = $jijin[0]['web_url'];
        }

        M('highlights')->where($wh)->setInc('click_num');
        
        //相关比赛
        $this->assign('union_list',$this->union_list($info['union_id'],$_GET['type']));

        $this->assign('url',$url);
		
        $this->assign('urlType',stripos($url,'.html') !== false ? 'neiqian' : 1);//用来判断是源地址还是内嵌页面
        if($jijin[0]['is_best'] == 1){
            $info['union_id'] = 'caiJin';
            $info['union_name'] = '全球彩经';
        }
        $this->assign('info',$info);

        $this->assign('jijin',$jijin);//remark
        $this->display();
    }
    /*
     *相关比赛
     * */
    private function union_list($union_id=0,$type=1){
        $img         = $this->IMG_SERVER;
        //type 1 足球  2 篮球 0  全部
        //足球
        $where  = array(
            'f.union_id'=>$union_id,
            'f.status'=>1,
        );
        if($type==1 ){
            $list    = M("game_fbinfo f")->
            field("f.id,f.game_time,f.union_name,f.game_id,f.score,f.web_video,f.game_state,f.home_team_name,f.away_team_name,
                  concat('$img',g.img_url) home_logo,
                  concat('$img',g2.img_url) away_logo,
                  u.img team_logo")
            ->join('left join qc_game_team g on g.team_id=f.home_team_id')
            ->join('left join qc_game_team g2 on g2.team_id=f.away_team_id')
            ->join('left join qc_union u on u.union_id=f.union_id')
            ->where($where)
            ->order('f.game_date desc,f.game_time desc')
            ->limit('8')
            ->select();
        }
        //篮球
        if( $type==2 ){
            $list    = M("game_bkinfo f")->
            field("f.id,f.game_time,f.union_name,f.game_id,f.score,f.web_video,f.game_state,f.home_team_name,f.away_team_name,
                  concat('$img',g.img_url) home_logo,
                  concat('$img',g2.img_url) away_logo,
                  u.img team_logo")
            ->join('left join qc_game_teambk g on g.team_id=f.home_team_id')
            ->join('left join qc_game_teambk g2 on g2.team_id=f.away_team_id')
            ->join('left join qc_bk_union u on u.union_id=f.union_id')
            ->where($where)
            ->order('f.game_date desc,f.game_time desc')
            ->limit('8')
            ->select();
        }
        
        foreach($list as $k=>$v){
            if( in_array($v['game_state'],$this->live_arr))
            {
                $v['is_live'] =1;
            }else{
                $v['is_live'] =0;
            }
            if( $v['score']=='-'||$v['score']==''||$v['score']=='0-0' )
            {
                $v['score'] ='VS';
            }
            $v['web_video']  =  $v['web_video'] ? json_decode( $v['web_video'],true) : array();
            $v['type'] = $type;
            //联赛名称
            $uname     = explode(',',$v['union_name']);
            if(!$uname['0']){
                unset($list[$k] );
                continue;}
            $v['union_name']   = $uname['0'] ;
            //球队名称 home_team_name
            $v['home_team_name']   = explode(',',$v['home_team_name'])['0'] ;
            $v['away_team_name']   = explode(',',$v['away_team_name'])['0'] ;
            $this->getlogo($v,$type);
            $list[$k] =$v;
        }
        return $list;
    }

    private  function getdata(){
        $type           = I('type');
        $id             = I('id'); // game_bkinfo 主键
        $game_id        = I('game_id');
        $webid          = I('webid');
        $img            = $this->IMG_SERVER;
        if($type == ''){
            $this->_empty();
        }
        if($id){
            $where  =  array('f.id'=>$id);
        }else if($game_id){
            $where  =  array('f.game_id'=>$game_id);
        }else{
            return;
        }
        if($type == 1){
            $info  =  M("game_fbinfo f")
                ->field("f.id,f.game_time,f.union_name,f.game_id,f.score,f.web_video,f.union_id,f.home_up,f.away_up,f.game_state,f.home_team_name,f.away_team_name,
                        concat('$img',t1.img_url) home_logo,
                        concat('$img',t2.img_url) away_logo,
                        u.img team_logo")
                ->join('left join qc_game_team t1 on t1.team_id=f.home_team_id')
                ->join('left join qc_game_team t2 on t2.team_id=f.away_team_id')
                ->join('left join qc_union u on u.union_id=f.union_id')
                ->where($where)
                ->find();
        }else if($type == 2){
            $info  = M("game_bkinfo f")
                ->field("f.id,f.game_time,f.union_name,f.game_id,f.score,f.web_video,f.union_id,f.home_up,f.away_up,f.game_state,f.home_team_name,f.away_team_name,
                        concat('$img',t1.img_url) home_logo,
                        concat('$img',t2.img_url) away_logo,
                        u.img team_logo")
                ->join('left join qc_game_teambk t1 on t1.team_id=f.home_team_id')
                ->join('left join qc_game_teambk t2 on t2.team_id=f.away_team_id')
                ->join('left join qc_bk_union u on u.union_id=f.union_id')
                ->where($where)
                ->find();
        }
        //比分
        $info['scores']       = explode('-',$info['score']);
        //百分比
        $total                 =  $info['away_up'] + $info['home_up'];
        $info['away_score']  =  round($info['away_up']/$total*100);
        $info['home_score']  =  round($info['home_up']/$total*100);
        //获取直播源
        $web_video            = json_decode($info['web_video'],true);
        //直播中 状态判断
        if( in_array($info['game_state'],$this->live_arr))
        {
            $info['is_live'] =1;
        }else{
            $info['is_live'] =0;
        }
        $info['union_name']       = explode(',',$info['union_name'])['0'];
        $info['home_team_name']   = explode(',',$info['home_team_name'])['0'];
        $info['away_team_name']   = explode(',',$info['away_team_name'])['0'];
        foreach( $web_video as $k => $v ){
            if( $v['webid'] == $webid ){
                $v['weburl']    ='//v.qqw.cn/hls/app'.$type.'-'.$info['game_id'].'-'.($k+1).'/index.m3u8';
                $info['video']  = $v['weburl'];
            }
        }
        $this->getlogo($info,$type);
        return $info;
    }

    //异步增加播放次数
    public function ajax_click() 
    {
        $id = I('id');
        M('highlights')->where(['id'=>$id])->setInc('click_num');
    }

    //获取生产环境jrs视频源
    public function getJrs()
    {
        $res = $this->curlCapture('http://www.jrssports.com/Home/Capture/getgame?updata=getgameandurl');
        $arr = json_decode($res,true);
        foreach($arr as $key=>$val)
        {
            if($val['web_video'] == '' && $val['app_video'] == '') continue;
            $res = [];
            if($val['game_class'] == 1) $table = M("GameFbinfo");
            if($val['game_class'] == 2) $table = M("GameFbinfo");
            $res = $table->field('web_video,app_video,home_team_name,away_team_name')->where(['game_id'=>$val['game_id']])->find();
            if(empty($val)) continue;
            $web_video  = json_decode($res['web_video'],true);
            $app_video  = json_decode($res['app_video'],true);
            $data = $web_data = $app_data = array();
            //处理web端视频源
            $web = json_decode($val['web_video'],true);
            //原有数据去除空数据
            foreach ($web_video as $kkk=>$vvv)
            {
                if(empty($vvv['weburl']) || empty($vvv['web_name']))
                    unset($web_video[$kkk]);
                else
                    continue;
            }
            $web_tmp = array_values(array_merge((array)$web_video,(array)$web));
            //格式化合并后数据格式
            $web_in = [];
            foreach ($web_tmp as $k=>$v)
            {
                if(in_array($v['weburl'],$web_in)) continue;
                $tmp = [];
                $tmp['webid'] = $k;
                $tmp['webname'] = $v['web_name']?$v['web_name']:$v['webname'];
                $tmp['weburl'] = $v['weburl'];
                $tmp['webformat'] = 'rtmp';
                $tmp['web_ischain'] = '1';
                $tmp['webstart'] = false;
                $web_data[] = $tmp;
                $web_in[] = $v['weburl'];
            }
            $data['web_video'] = json_encode($web_data);

            //处理app端视频源
            $app = json_decode($val['app_video'],true);
            //原有数据去除空数据
            foreach ($app_video as $k2=>$v2)
            {
                if(empty($v2['appurl']) || empty($v2['appname']))
                    unset($app_video[$k2]);
                else
                    continue;
            }
            $app_tmp = array_values(array_merge((array)$app_video,(array)$app));
            //格式化合并后数据格式
            $app_in = [];
            foreach ($app_tmp as $kk=>$vv)
            {
                if(in_array($vv['appurl'],$app_in)) continue;
                $tmp = [];
                $tmp['appid'] = $kk;
                $tmp['appname'] = $vv['appname'];
                $tmp['appurl'] = $vv['appurl'];
                $tmp['app_isbrowser'] = '0';
                $tmp['appstart'] = false;
                $tmp['app_ischain'] = '2';
                if(strpos($vv['appurl'],'m3u8') || strpos($vv['appurl'],'rtmp') || strpos($vv['appurl'],'flv') || strpos($vv['appurl'],'mp4')) $tmp['app_ischain'] = '0';
                $app_data[] = $tmp;
                $app_in[] = $vv['appurl'];
            }
            $data['app_video'] = json_encode($app_data);

            $rs = M("GameFbinfo")->where(['game_id'=>$val['game_id']])->save($data);
            if($rs) echo "gameId为".$val['game_id']."的视频源同步成功<br/>";
        }
    }
    public function curlCapture($requestUrl,$headers,$resFormat = false)
    {
        // 初始化 CURL
        $ch = curl_init();

//			$headers = array(
//				'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
//				'Upgrade-Insecure-Requests:1',
//				'User-Agent:Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36',
//				'Referer:'.$referer,
//				'Accept-Encoding:gzip, deflate, sdch',
//				'Accept-Language:zh-CN,zh;q=0.8',
//			);

        // 设置 URL
        curl_setopt($ch, CURLOPT_URL,$requestUrl);
        // 让 curl_exec() 获取的信息以数据流的形式返回，而不是直接输出。
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        // 在发起连接前等待的时间，如果设置为0，则不等待
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 0);
        if(substr($requestUrl,0,5) == "https")
        {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        }
        // 设置 CURL 最长执行的秒数
        curl_setopt ($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
        if (!empty($headers))
        {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        // 尝试取得文件内容
        $store = curl_exec ($ch);


        // 检查文件是否正确取得
        if (curl_errno($ch)){
            //"无法取得 URL 数据";
            return null;
            exit;
        }

        // 关闭 CURL
        curl_close($ch);

        return $resFormat ? json_decode($store,true) : $store;
    }

    /**
     * 字符串截取，支持中文和其他编码
     * static
     * access public
     * @param string $str 需要转换的字符串
     * @param string $start 开始位置
     * @param string $length 截取长度
     * @param string $charset 编码格式
     * @param string $suffix 截断显示字符
     * return string
     */
    function msubstrs($str) {
        $arr = preg_split("/([a-zA-Z0-9]+)/", $str, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $title = '';
        if(is_numeric($arr[1]))
        {
            $title = $arr[0].substr($arr[1],-2);
        }else{
            $title = $arr[0].$arr[1].substr($arr[2],-2);
        }
        return $title;
    }
}
 ?>