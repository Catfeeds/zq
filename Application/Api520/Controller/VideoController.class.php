<?php
use Common\Mongo\GambleHallMongo;
/**
 * 视频直播
 * @author huangjiezhen <418832673@qq.com> 2016.03.29
 */
class VideoController extends PublicController
{
    //直播首页列表
    public function index()
    {
        $game_type = $this->param['game_type'] ?: 1;//默认足球
        $gameModel = $game_type == 1 ? M('GameFbinfo') : M('GameBkinfo');
        $stateArr  = $game_type == 1 ? [0,1,2,3,4,-1] : [0,1,2,3,4,5,6,50,-1];
        $joinTable = $game_type == 1 ? 'qc_union' : 'qc_bk_union';

        $where = [
            'f.is_video' => 1,
            'f.game_state' => ['IN', $stateArr],
            'f.game_date'=>['IN',[
                    date('Ymd', strtotime('-3 day')),
                    date('Ymd', strtotime('-2 day')),
                    date('Ymd', strtotime('-1 day')),
                    date('Ymd'),
                    date('Ymd', strtotime('+1 day')),
                    date('Ymd', strtotime('+2 day')),
                    date('Ymd', strtotime('+3 day')),
                ]
            ]
        ];
        $field = ['f.game_id','f.game_date','f.game_time','f.union_name','f.home_team_id','f.home_team_name','f.away_team_id','f.away_team_name','f.game_state','f.score','f.app_video','f.is_flash','qu.union_color'];
        $list = $gameModel
            ->alias('f')
            ->join(' LEFT JOIN '.$joinTable.' AS qu ON f.union_id = qu.union_id ')
            ->field($field)
            ->where($where)
            ->order('f.game_date asc,f.game_time asc')
            ->select();

        //获取球队logo
        setTeamLogo($list, $game_type);

        $liveList = [];

        foreach ($list as $k => $v)
        {
            $list[$k]['union_name']     = explode(',', $v['union_name']);
            $list[$k]['home_team_name'] = explode(',', $v['home_team_name']);
            $list[$k]['away_team_name'] = explode(',', $v['away_team_name']);
//            $list[$k]['homeTeamLogo']   = getLogoTeam($v['home_team_id'],1,$game_type);
//            $list[$k]['awayTeamLogo']   = getLogoTeam($v['away_team_id'],2,$game_type);
            $list[$k]['hasVideo'] = M('Highlights')
                ->where(['game_id'=>$v['game_id'],'app_url'=>['neq',''],'game_type'=>$game_type])->find() ? 1 : 0;

            $list[$k]['isLive'] = 0; //是否有直播

            if ($v['app_video'])
            {
                $liveInfo = json_decode($v['app_video'],true);

                foreach ($liveInfo as $vv)
                {
                    if ($vv['appname'] && $vv['appurl'])
                    {
                        $list[$k]['isLive'] = 1;
                        break;
                    }
                }
            }

            unset($list[$k]['app_video']);

            $liveList[$v['game_date']][] = $list[$k];
        }

        $this->ajaxReturn(['liveList'=>$liveList ? (array)$liveList : (object)$liveList]);
    }

    //获取视频、动画直播
    public function getLive()
    {
        $game_type = $this->param['game_type'] ?: 1;//默认足球
        $game_id = $this->param['game_id'];
        if($game_type == 2){
            $gameModel = M('GameBkinfo');
            $linkModel = M('BkLinkbet');
            $stateArr  = [0,1,2,3,4,5,6,50];
            $flashUrl  = 'https://dh.qqty.com/basketball_animate/basketball_animate.html?game_id='.$game_id;
        }else{
            $gameModel = M('GameFbinfo');
            $linkModel = M('FbLinkbet');
            $stateArr  = [0,1,2,3,4,5];
            $flashUrl  = C('dh_host') . '/svg-f-animate.html?game_id='.$game_id;
        }

        //是否有视频直播
        $videoList  = $gameModel->field(['gtime','app_video','gtime','game_state','is_video'])->where(['game_id'=>$game_id])->find();
        if ($videoList['app_video'] && $videoList['is_video'] == 1){
            $videoInfo = json_decode($videoList['app_video'],true);
            foreach ($videoInfo as $k => $v)
            {
                if ($v['appname'] && $v['appurl'])
                {
                    $appurl[] = [
                        'appname'       => $v['appname'],
                        'appurl'        => htmlspecialchars_decode(stripslashes($v['appurl'])),
                        'app_ischain'   => $v['app_ischain'],
                        'app_isbrowser' => $v['app_isbrowser'],
                    ];
                }
            }
            if(in_array($videoList['game_state'], $stateArr) && !empty($appurl) && !iosCheck()){
                //比赛中才显示有视频直播
                $video = $appurl;
            }
        }

        //是否有动画直播
//         $has = D('GambleHall')->getFbLinkbet($game_id);
        $has = (new GambleHallMongo())->getFbLinkbet($game_id);
        
        if($has){
            if(in_array($videoList['game_state'],[0,1,2,3,4]))
            {
               $flash = $flashUrl;
            }
            if($videoList['game_state'] != 0)
            {
               $is_flash = '1';
            }
        }

        //获取正在直播的主播列表
        $lives = M('liveLog')
            ->alias('Lg')
            ->field('Lg.user_id, Lg.id as live_id, Lu.unique_id, Lg.title, Lg.room_id, Lg.start_time, U.nick_name')
            ->join('LEFT JOIN qc_live_user LU ON LU.user_id = Lg.user_id')
            ->join('LEFT JOIN qc_front_user U ON U.id = Lg.user_id')
            ->where(['Lg.status' => 1, 'LU.status' => 1, 'Lg.live_status' => 1,'Lg.game_id' => $game_id])
            ->order('Lg.start_time DESC')
            ->limit(10)
            ->select();


        foreach($lives as $k => $v){
            $lives[$k]['live_url'] = D('Live')->getLiveUrl($v['room_id'], $v['start_time']);
            $lives[$k]['mqtt_room_topic'] = 'qqty/live_' . $v['room_id'] . '/chat';//mqtt room topic
        }

        $this->ajaxReturn([
            'video'   => $video ?: [],
            'is_video'=> $appurl ? '1' : '0',
            'flash'   => $flash ?:'',
            'is_flash'=> $is_flash ? : '0',
            'live_list'=> $lives ? : [],
            'gtime'   => (string)$videoList['gtime']?:''
        ]);
    }

    //战报集锦
    public function videoList()
    {
        $game_type = $this->param['game_type'] ?: 1;//默认足球
        if(!$videoList = S('Video:videoList'.MODULE_NAME.$this->param['game_id'].$game_type))
        {
            $videoList = M('Highlights')->field(['title','remark','img','app_url','app_ischain','is_prospect','app_isbrowser'])
                        ->where(['game_id'=>$this->param['game_id'],'game_type'=>$game_type,'app_url'=>['neq',''],'status'=>1])
                        ->order('add_time asc')
                        ->select();

            if ($videoList)
            {
                S('Video:videoList'.MODULE_NAME.$this->param['game_id'].$game_type, json_encode($videoList), 60*10);
            }
        }

        $preVideo = [];
        $finishVideo = [];

        foreach ($videoList as $k => $v)
        {
            $v['img'] = C('IMG_SERVER').$v['img'];

            if ($v['is_prospect'] == 1)
            {
                unset($v['is_prospect']);
                $preVideo[] = $v;
            }
            else
            {
                unset($v['is_prospect']);
                $finishVideo[] = $v;
            }
        }

        $this->ajaxReturn(['videoList'=>['preVideo'=>$preVideo,'finishVideo'=>$finishVideo]]);
    }

    //相关资讯
    public function articleList()
    {
        $game_id    = $this->param['game_id'];
        $game_type  = $this->param['game_type'] ?: 1;//默认足球
        $from       = $this->param['from']?:1;  //情报来源
        $platform   = $this->param['platform']; //平台
        $pkg        = $this->param['pkg'];  //包名
        if($game_id == '')
            $this->ajaxReturn(101);

        $articleList = D('Home')->getGameArticleList($game_id,$game_type,$from,$platform,$pkg);

        $this->ajaxReturn($articleList);
    }

    //用户添加视频源接口
    public function addVideoShareurl(){
        $userInfo = $this->getInfo();               //用户信息
        $url      = $this->param['url'];            //视频源链接
        $gameType = $this->param['gameType'] ? : 1; //1足球 2篮球 默认1足球
        $gameId   = $this->param['gameId'];         //赛程id 必填

        if(empty($gameId))
            $this->ajaxReturn(101);

        //验证url链接
        if(empty($url) || !preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%
        =~_|]/i",$url))
            $this->ajaxReturn(2020);
        
        $rs = M('videoShareurl')->add([
            'user_id'   => $userInfo['userid'],
            'game_id'   => $gameId,
            'game_type' => $gameType,
            'url'       => $url,
            'add_time'  => time(),
        ]);
        if(!$rs) 
            $this->ajaxReturn(4002);

        $this->ajaxReturn(['result'=>1],2021);
    }

}