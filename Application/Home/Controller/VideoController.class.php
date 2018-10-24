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

    public function _empty($path)
    {
        if($path == 'sendMore'){
            //加载更多
            A('Home/Highlights')->sendMore();
            die;
        }
        $ex_path = explode('/', $_SERVER['PATH_INFO']);
        $classArr = getVideoClass(0);
        foreach ($classArr as $k => $v) {
            $arr[] = $v['path'];
        }
        if(!in_array($ex_path[0], $arr)){
            parent::_empty();
        }
        if ($ex_path[2]) {
            $this->lives($ex_path[2]);
        } else {
            //集锦页
            A('Home/Highlights')->index($path);
        }
    }

    /**
     * @ 视频主页
     *
     * */
    public function index()
    {
        //手机站链接适配
        $mobileUrl = U('/video@m');
        $this->assign('mobileAgent', $mobileUrl);
        //手机端访问跳转
        if(isMobile()){
            redirect($mobileUrl);
        }
        $type  = I('type');
        $str   = I('game_date')?date('Ymd',I('game_date')):date('Ymd',time());

        $list1 = array();
        $list2 = array();
        //type 1 足球  2 篮球 0  全部
        if(!$type){
            //全部
            $list = array_merge_recursive(self::getGameFb($str),self::getGameBk($str));
            $this->assign('className','全部');
        }elseif ($type == 1) {
            //足球
            $list = self::getGameFb($str);
            $this->assign('className','足球');
        }elseif ($type == 2) {
            //篮球
            $list = self::getGameBk($str);
            $this->assign('className','篮球');
        }
        $classArr = getVideoClass(0);
        foreach($list as $k => $v){
            if( $v['score']=='-' || $v['score']==''||in_array($v['game_state'],$this->wait_arr) )
            {
                $v['score'] ='VS';
            }
            if($v['game_state']==0){
                //查询前瞻
                $prospect = M('highlights')->where(['game_type'=>$v['type'],'game_id'=>$v['game_id'],'is_prospect'=>1])->order("id desc")->find();
                $v['prospect_url'] = $prospect ? videoUrl($prospect,$classArr) : ''; 
            }elseif ($v['game_state']=='-1'){
                //查询集锦
                $jijin = M('highlights')->where(array('game_id'=>$v['game_id'],'game_type'=>$v['type'],'is_prospect'=>0))->order("id desc")->find();
                $v['jijin_url'] = $jijin ? videoUrl($jijin,$classArr) : ''; 
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
                'time'  => $stime,
            );
            if( date('m月d日', time()) == $day )
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
        //获取导航
        $classArr = getVideoClass(1);
        foreach ($classArr as $k => $v) {
            $arr['name'] = $v['name'];
            $arr['href'] = U('/'.$v['path'].'@video');
            $navArr[] = $arr;
        }
        $this->assign('navArr',$navArr);
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
                        $li   .='  <a href="javascript:;" class="zb-common cdefault">播放源 <span></span></a> <div class="zb-list clearfix"><ul>';
                    }else{
                        if( $v['web_video_num']>=1 ){
                            $li   .='  <a href="javascript:;" class="zb-common zbz">直播中<span></span></a> <div class="zb-list clearfix"><ul>';
                        }
                        else{
                            $li   .='  <a href="javascript:;" class="zb-common cdefault">进行中<span></span></a> <div class="zb-list clearfix"><ul>';
                        }
                    }
                    foreach($v['web_video'] as $kk=>$vv ){
                        if($vv['weburl'] != '' && $v['type'] == 1) {
                            $li.='  <li><a href="'.U('/live/'.$v['game_id'].'@bf').'" target="_blank" >'.$vv['webname'].'</a></li>';
                        }
                    }
                    $li.=' </ul></div>';
                }
                else
                {
                    // 非直播
                    if( $v['game_state'] == 0 ) {  //未开始
                        if($v['prospect_url'] != ''){ //有前瞻
                            $li .= ' <a  target="_blank"  href="' . $v['prospect_url'] . '" class="zb-common play">前瞻</a>';
                        }else{
                            $li .=' <a href="javascript:;" class="zb-common" style="color: #999999; cursor: default;">未开赛</a>';
                        }
                    }else if($v['game_state'] == -1)  {//已完场
                        if($v['jijin_url'] != '') {  //有集锦
                            $li .= ' <a  target="_blank"  href="' .$v['jijin_url'] . '" class="zb-common play">集锦</a>';
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
                $v['team_logo'] =staticDomain('/Public/Home/images/common/zu.png');
            }else{
                $v['team_logo'] =staticDomain('/Public/Home/images/common/lan.png');
            }

        }
        if($v['home_logo']==$img ||!$v['home_logo'])
        {
            $v['home_logo'] =staticDomain('/Public/Home/images/common/home_def.png');
        }

        if($v['away_logo']==$img ||!$v['away_logo'])
        {
            $v['away_logo'] =staticDomain('/Public/Home/images/common/away_def.png');
        }
    }
    function articleList($game_id)
    {
        $where['status'] = 1;
        $where['game_id'] = $game_id;

        return  $articleList = M('PublishList')->field(['id','title','FROM_UNIXTIME( add_time, \'%m/%d\') day','FROM_UNIXTIME( add_time, \'%H:%i\') hour'])
            ->where($where)
            ->order('web_recommend desc,is_channel_push desc,add_time desc')
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
    public function lives($jj_id)
    {
        if(checkUrlExt()){
            parent::_empty();
        }
        //手机站链接适配
        $mobileUrl = U('/video/'.$jj_id.'@m');
        $this->assign('mobileAgent', $mobileUrl);
        //手机端访问跳转
        if(isMobile()){
            redirect($mobileUrl);
        }
        
        $prefix = C('IMG_SERVER');
        $where['id'] = $jj_id;
        $where['status'] = 1;

        $jijin = M('highlights')->where(['id'=>$jj_id])->field("*,concat('$prefix',img) img")->order('click_num desc,add_time desc')->find();
        //视频分类数组
        $classArr = getVideoClass(0); 

        $ex_path = explode('/', $_SERVER['PATH_INFO']);
        $videoClass = $classArr[$jijin['class_id']];
        
        //二级目录,日期判断
        if(!$jijin || $ex_path[1] != date('Ymd',$jijin['add_time']) || $videoClass['path'] != $ex_path[0]){
            parent::_empty();
        }

        $info  = $this->getdata($jijin['game_id'],$jijin['game_type']);
        $url     = '';
        $jijin['logo']   = $jijin['img'];
        if($jj_id== $jijin['id']){
            $url = $jijin['web_url'];
            $info['title'] =$jijin['title']?$jijin['title']:$info['title'];
            $info['remark'] =$jijin['remark'];
        }

        //浏览次数
        if($jj_id){
            $wh  = array('id'=>$jj_id);
        }else{
            $wh  = array('id'=>$jijin['id']);
            $url = $jijin['web_url'];
        }
        //点击量加1
        M('highlights')->where($wh)->setInc('click_num');

        //相关视频
        $more_video = M('Highlights')->field('id,title,remark,add_time,img,class_id')->where(['class_id'=>$jijin['class_id'],'status'=>1])->order('add_time desc')->limit(8)->select();
        if(empty($more_video)) $more_video = M('Highlights')->field('id,title,remark,add_time,img,class_id')->where(['status'=>1])->order('add_time desc')->limit(8)->select();
        
        foreach($more_video as $key=>$val)
        {
            $more_video[$key]['href'] = videoUrl($val,$classArr);
            $more_video[$key]['img'] = Tool::imagesReplace($val['img']);
        }
        $this->assign('more_video',$more_video);

        $this->assign('url',$url);
        $neiqian = 'neiqian';
        if(stripos($url,'.swf') !== false) $neiqian = 1;
        if(stripos($url,'.flv') !== false || stripos($url,'.mp4') !== false) $neiqian = 2;
        $this->assign('urlType',$neiqian);//用来判断是源地址还是内嵌页面
        if($jijin['is_best'] == 1){
            $info['union_id'] = 'caiJin';
            $info['union_name'] = '全球彩经';
        }
        $this->assign('info',$info);

        //获取关联专家号信息
        $this->assign('author',$this->author_info($jijin['user_id']));
        if(empty($jijin['label']))
        {
            $label_arr = M('Highlights')->where(['class_id'=>$jijin['class_id'],'label'=>['exp','is not null']])->limit(5)->getField('label');
            if(empty($label_arr))
            {
                $label = [];
            }else{
                $tmp = [];
                $tmp = explode(',',implode(',',$label_arr));
                $label = array_slice($tmp,0,5);
            }
        }else{
            $label = explode(',',$jijin['label']);
        }
        $jijin['label']  = array_filter($label);
        //处理标签英文跳转连接
        if(!$keyword = S('url_keyword_key_val'))
        {
            $keyword = M('HotKeyword')->getField('keyword,url_name',true);
            S('url_keyword_key_val',$keyword,500);
        }
        $label = [];
        foreach ($jijin['label'] as $kk=>$vv)
        {
            $tmp = [];
            $tmp[] = $vv;
            $tmp[] = $keyword[$vv]?:getPy($vv)?:$vv;
            $label[] = $tmp;
        }
        $jijin['label'] = $label;
        if(is_login() > 0)
        {
            $user_arr = explode(',',$jijin['like_user']);
            if(in_array(is_login(),$user_arr)) $jijin['zan'] = 1;
        }
        $this->assign('jijin',$jijin);//remark
        $user_head = session('user_auth')['head'] ? session('user_auth')['head'] : staticDomain("/Public/Home/images/common/face.png");
        $this->assign('user_head',$user_head);
        $this->assign('user_id',is_login());
        //seo
        $classArr = getVideoClass(0)[$jijin['class_id']];
        $this->setSeo([
            'seo_title' => $jijin['seo_title'] ?: $jijin['title'].'_'.$classArr['name'].'视频集锦专区频道'.'_全球体育网',
            'seo_keys'  => $jijin['seo_keys']  ?: $classArr['seo_keys'],
            'seo_desc'  => $jijin['seo_desc']  ?: $classArr['seo_desc'],
        ]);
        $this->display('Video/lives');
    }

    //发布者信息
    public function author_info($id){
        $info = M("FrontUser")->field('id,lv,lv_bet,nick_name as name,head,descript,is_expert')->where(['id'=>$id])->find();
        $video_total = M()->query('SELECT count(id) as id_total,SUM(click_num) AS click_total FROM qc_highlights where `status` = 1 and `user_id` = '.$id);
        $publish_total = M()->query('SELECT count(id) as id_total,SUM(click_number) AS click_total FROM qc_publish_list where `status` = 1 and `user_id` = '.$id);
        $info['total'] = (int)$video_total[0]['id_total'] + (int)$publish_total[0]['id_total'];
        $info['click'] = (int)$video_total[0]['click_total'] + (int)$publish_total[0]['click_total'];
        $info['followNum'] = M('FollowUser')->where(['follow_id'=>$id])->count();
        $info['head'] = frontUserFace($info['head']);
        return $info;
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
            field("f.id,f.game_time,f.union_name,f.game_id,f.score,f.web_video,f.game_state,f.home_team_name,f.away_team_name,f.is_video,
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
            field("f.id,f.game_time,f.union_name,f.game_id,f.score,f.web_video,f.game_state,f.home_team_name,f.away_team_name,f.is_video,
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
            if($v['game_state']==0){
                //前瞻
                $tmp = M('highlights')->where(['game_type'=>$v['type'],'game_id'=>$v['game_id'],'is_prospect'=>1])->select();
                $list[$k]['prospect'] = count($tmp);
                if(count($tmp) > 0) $list[$k]['prospect_id'] = $tmp[0]['id'];
            }elseif ($v['game_state']=='-1'){
                //集锦
                $tmp = M('highlights')->where(array('game_id'=>$v['game_id'],'game_type'=>$v['type'],'is_prospect'=>0))->select();
                $list[$k]['jijin_num']  = count($tmp);
                if(count($tmp) > 0) $list[$k]['jijin_id'] = $tmp[0]['id'];
            }
        }
        return $list;
    }

    private  function getdata($game_id,$type){
        $id             = I('id'); // game_bkinfo 主键
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
        $info['title'] = $info['home_team_name'].'VS'.$info['away_team_name'];
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
            if($val['game_class'] == 2) $table = M("GameBkinfo");
            $res = $table->field('web_video,app_video,home_team_name,away_team_name,union_name')->where(['game_id'=>$val['game_id']])->find();
            if(empty($res)) continue;
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
                if(strpos($tmp['weburl'],'m3u8') || strpos($tmp['weburl'],'rtmp') || strpos($tmp['weburl'],'flv') || strpos($tmp['weburl'],'mp4')){
                    $tmp['webformat'] = 'm3u8';
                    $tmp['web_ischain'] = '12';
                }else{
                    $tmp['webformat'] = 'rtmp';
                    $tmp['web_ischain'] = '1';
                }
                $tmp['webstart'] = false;
                $web_data[$tmp['webname']] = $tmp;
                $web_in[] = $v['weburl'];
            }
            $data['web_video'] = json_encode(array_values($web_data));

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
                $app_data[$tmp['appname']] = $tmp;
                $app_in[] = $vv['appurl'];
            }
            $data['app_video'] = json_encode(array_values($app_data));
            $data['is_video'] = 1;

            $rs = $table->where(['game_id'=>$val['game_id']])->save($data);
            $type = $val['game_class'] ==1?'足球':'篮球';
            if($rs) echo "gameId为".$val['game_id']."的".$type."视频源同步成功<br/>";
        }
    }

    //ajax请求评论内容
    public function getComment()
    {
        $p = I('p',1,'int');
        $id = I('id',0,'int');
        $time = I('time',0,'int');
        if($id > 0 && $time > 0)
        {
            $login = is_login();
            $num = 5;//每页记录数
            $limitpage = ($p-1)*$num;//每次查询取记录
            $where['vc.h_id'] = $id;
            $where['vc.status'] = 1;
            $where['create_time'] = ['elt',$time];
            $comment = M('video_comment vc')->join('LEFT JOIN  qc_front_user fu ON fu.id = vc.user_id')->where($where)->field('vc.id,vc.filter_content,vc.create_time,vc.user_id,fu.username,fu.head,vc.like_num,vc.like_user')->order('create_time desc')->limit($limitpage,$num)->select();
            foreach($comment as $key=>$val)
            {
                if($login > 0)
                {
                    $user_arr = explode(',',$val['like_user']);
                    if(in_array($login,$user_arr)) $comment[$key]['zan'] = 1;
                }
                unset($comment[$key]['user_id'],$comment[$key]['like_user']);
                $comment[$key]['head'] = frontUserFace($val['head']);
                $comment[$key]['create_time'] = Tool::processTime($val['create_time']);
            }
            $data = ['status'=>200,'data'=>$comment];
        }else{
            $data = ['status'=>201];
        }
        $this->ajaxReturn($data);

    }

    //ajax保存评论内容
    public function saveComment()
    {
        $content = I('data');
        $id = I('id');
        //过滤内容
        $FilterWords = getWebConfig("FilterWords");
        foreach ($FilterWords as $key => $value) {
            $Words[] = '/'.$value.'/';
        }

        $filter_content = preg_replace($Words, '***', $content);
        //增加评论
        $insertId = M('VideoComment')->add([
            'h_id'           => $id,
            'user_id'        => is_login(),
            'content'        => $content,
            'filter_content' => $filter_content,
            'create_time'    => NOW_TIME,
            'reg_ip'         => get_client_ip()
        ]);
        if (!$insertId)
            $this->ajaxReturn(['status'=>200,'data']);

        $user = session('user_auth');
        $data = [
            'id'                =>  $insertId,
            'filter_content'    =>  $filter_content,
            'create_time'       =>  '刚刚',
            'head'              =>  $user['head'],
            'username'          =>  $user['nick_name'],
        ];
        $this->ajaxReturn(['status'=>200,'data'=>$data]);
    }

    //视频详情点赞功能
    public function zanComment()
    {
        $id = I('id');
        $user_id = is_login();
        $code = 201;
        $msg = '点赞失败!!';
        if($id > 0 && $user_id >0)
        {
            $like_user  = M("VideoComment")->field('like_user')->where(['id'=>$id])->find();
            $user_arr = explode(',',$like_user['like_user']);
            if(in_array($user_id,$user_arr)) $is_like = true;
            if($is_like)
            {
                $msg = '你已经赞过了!!';
            }else{
                array_push($user_arr,$user_id);
                $like_user = ltrim(implode(",", $user_arr),',');
                $rs = M('VideoComment')->where(['id'=>$id])->save(['like_num'=>['exp','like_num+1'],'like_user'=>$like_user]);
                if($rs){
                    $msg = '点赞成功!!';
                    $code = 200;
                }
            }
        }
        $this->ajaxReturn(['status'=>$code,'msg'=>$msg]);
    }

    //视频详情点赞功能
    public function zanVideo()
    {
        $id = I('id');
        $user_id = is_login();
        $code = 201;
        $msg = '点赞失败!!';
        if($id > 0 && $user_id >0)
        {
            $like_user  = M("Highlights")->field('like_user')->where(['id'=>$id])->find();
            $user_arr = explode(',',$like_user['like_user']);
            if(in_array($user_id,$user_arr)) $is_like = true;
            if($is_like)
            {
                $msg = '你已经赞过了!!';
            }else{
                array_push($user_arr,$user_id);
                $like_user = ltrim(implode(",", $user_arr),',');
                $rs = M('Highlights')->where(['id'=>$id])->save(['like_num'=>['exp','like_num+1'],'like_user'=>$like_user]);
                if($rs){
                    $msg = '点赞成功!!';
                    $code = 200;
                }
            }
        }
        $this->ajaxReturn(['status'=>$code,'msg'=>$msg]);
    }

    /**
     * Created by PhpStorm.
     * User: liuwt
     * Date: 2016/8/26
     * Time: 16:15
     */



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

    public function videoClass()
    {
        if(!I('class_id')) $this->_empty();
        if(strpos($_SERVER['HTTP_HOST'],'www') !== false)
        {
            A('PublishIndex')->publishClass();
            exit();
        }
        $class_id = I('class_id');
        //获取分类列表
        //将英文标签转换为中文
        if(!$keyword = S('url_keyword_key_val'))
        {
            $keyword = M('HotKeyword')->getField('keyword,url_name',true);
            S('url_keyword_key_val',$keyword,500);
        }
        if($keyword[$class_id]) $this->redirect('/tag/'.$keyword[$class_id]);
        $keyword = array_flip($keyword);
        $class_id = $keyword[$class_id]?$keyword[$class_id]:$class_id;
        //标签搜索
        $where['label'] = ['like','%'.urldecode($class_id).'%'];
        $className = '"'.urldecode($class_id)."\"相关视频";
        $where['status']   = 1;
        $this->assign('className', $className);
        $classArr = getVideoClass(0);
        $list = $this->_list(M('Highlights'),$where,'15','add_time desc','','',"/tag/$class_id.html?p=%5BPAGE%5D");
        //用户id数组
        $userIdArr = $userInfo = [];
        foreach ($list as $key => $value) {
            $list[$key]['href'] = videoUrl($value,$classArr);
            //$list[$key]['comment']  = M('comment')->where(['publish_id'=>$value['id']])->count();
            $list[$key]['img'] = Tool::imagesReplace($value['img']);
            $list[$key]['label'] = explode(',', $value['label']);
            //增加资讯点击量的默认值
            $list[$key]['click_number'] = addClickConfig(1, $value['class_id'], $value['click_num'], $value['id']);
            if(empty($list[$key]['app_time'])) $list[$key]['app_time'] = $list[$key]['update_time'];
            $userIdArr[] = $value['user_id'];
        }
        $this->assign('list', $list);
        $userIdArr = array_filter ($userIdArr);
        $userInfoArr = M('FrontUser')->field('id,head,nick_name')->where(['id'=>['in',$userIdArr]])->select();
        foreach($userInfoArr as $val)
        {
            $tmp = [];
            $tmp['head'] = Tool::imagesReplace($val['head']);
            $tmp['name'] = $val['nick_name'];
            $tmp['id'] = $val['id'];
            $userInfo[$val['id']] = $tmp;
        }
        //专家信息赋值
        $this->assign('userInfoArr',$userInfo);

        $classArr = getPublishClass(0);
        //获取独家解盘分类
        $quiz = M('publishList')->where(['class_id'=>10,'status'=>1,'is_original'=>1])->field("id,title,is_original,class_id,add_time")->order("update_time desc")->limit(10)->select();
        foreach ($quiz as &$v) {
            $v['href'] = newsUrl($v['id'], $v['add_time'], $v['class_id'], $classArr);
        }
        $this->assign('quiz',$quiz);
        //获取推荐指数
        $Marvellous = A('Index')->getMarvellous();
        $this->assign('Marvellous', $Marvellous);
        //获取视频推荐集锦
        $highlights = $this->getRecommendJJ();
//        var_dump($highlights);
        $this->assign('highlights',$highlights);
        $this->assign("union_name", A('Home/PublishIndex')->getOrderUnion());
        //获取视频直播
        $live = A('Home/PublishIndex')->getWebVideo();
        $this->assign('live',$live);
        $this->display('Video/videoClass');
    }

    /*
     * 将数据库内视频列表中QQ视频的PC端地址生成一条移动端地址
     * 会强制修改为外链
     */
    public function PcToMobileForQQ()
    {
        $where['status'] = 1;
        $where['web_url'] = ['like', '%/imgcache.qq.com/%'];
        $where['user_id'] = ['NEQ','NULL'];
        $where['m_url'] = ['EQ',''];
        $videoList = M('Highlights')->field('id,web_url')->where($where)->select();
        foreach ($videoList as $key=>$val)
        {
            $url = html_entity_decode($val['web_url']);
            $QQkey = explode('&',explode('vid=',$url)[1])[0];
            if($QQkey)
                $m_url = 'https://xw.qq.com/a/video/'.$QQkey;
            else
                continue;
            $videoList[$key]['m_url'] = $m_url;
            $videoList[$key]['m_ischain'] = 1;
            unset($videoList[$key]['web_url']);
        }
        $editRes = $this->batch_update('qc_Highlights', $videoList, 'id');

    }
}
?>