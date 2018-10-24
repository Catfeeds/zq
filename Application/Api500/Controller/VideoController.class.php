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
        $list = $gameModel->alias('f')->join(' LEFT JOIN '.$joinTable.' AS qu ON f.union_id = qu.union_id ')->field($field)->where($where)->order('f.game_date asc,f.game_time asc')->select();

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
            $list[$k]['hasVideo']       = M('Highlights')->where(['game_id'=>$v['game_id'],'app_url'=>['neq',''],'game_type'=>$game_type])->find() ? 1 : 0;

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
            $stateArr  = [0,1,2,3,4];
            $flashUrl  = 'http://dh.qqty.com/svg/svg-f-animate.html?game_id='.$game_id;
        }

        //是否有视频直播
        $videoList  = $gameModel->field(['app_video','gtime'])->where(['game_id'=>$game_id, 'is_video'=>1, 'game_state'=>['IN', $stateArr]])->find();

        if ($videoList['app_video']){
            $videoInfo = json_decode($videoList['app_video'],true);
            foreach ($videoInfo as $k => $v)
            {
                if ($v['appname'] && $v['appurl'])
                {
                    $video[] = [
                        'appname'       => $v['appname'],
                        'appurl'        => htmlspecialchars_decode(stripslashes($v['appurl'])),
                        'app_ischain'   => $v['app_ischain'],
                        'app_isbrowser' => $v['app_isbrowser'],
                    ];
                }
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
        }

        $this->ajaxReturn(['video'=>$video?:[],'flash'=>$flash?:'','gtime'   => (string)$videoList['gtime']?:'']);
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
        $game_type  = $this->param['game_type'] ?: 1;//默认足球
        $game_id    = $this->param['game_id'];
        $from       = $this->param['from']?:1;//情报来源

        if($game_id == '')
            $this->ajaxReturn(101);

        $cacheKey = 'Video:articleList' . MODULE_NAME . $this->param['game_id'] . $game_type;

        if(!$responseList = S($cacheKey))
        {
            $akey = $game_type == 1 ? 'game_id' : 'gamebk_id';
            $articleList = (array)M('PublishList')->field(['id', 'class_id', 'source','title', 'remark', 'click_number', 'img', 'content', 'add_time'])
                ->where([$akey => $game_id, 'status' => 1])
                ->order('is_recommend desc, is_channel_push desc, add_time desc')
                ->limit(20)
                ->select();


            $videoList = (array)M('Highlights')->field(['id', 'title', 'remark', 'click_num as click_number', 'img', 'app_url', 'app_ischain', 'is_prospect', 'add_time','app_isbrowser'])
                ->where(['game_id' => $game_id, 'game_type' => $game_type, 'app_url' => ['neq', ''], 'status' => 1])
                ->order('is_recommend desc, add_time asc')
                ->limit(20)
                ->select();

            $list = array_merge($articleList, $videoList);
            $publishClass = M('PublishClass')->where("status=1")->getField('id, name');

            foreach($list as $k=> $v){
                $addTimeSort[] = $v['add_time'];
                if(isset($v['class_id'])){

                    $list[$k]['source'] = $v['source'].'/'.$publishClass[$v['class_id']];
                }else{
                    $list[$k]['source'] = '';
                }

                unset($list[$k]['add_time']);
                unset($list[$k]['class_id']);
            }

            //排序
            array_multisort($addTimeSort, SORT_DESC, $list);
            $responseList = array_slice($list, 0 ,10);

//            if ($responseList)
//                S($cacheKey, $responseList, 60 * 1);
        }

        $lists = D('Home')->getArticleImg($responseList, false);

        //获取赛前情报
        if($game_type == 1){
            $appService = new \Home\Services\AppfbService();
            $preMatchinfo = $appService->getPreMatchinfo($game_id,$from);
        }

        $this->ajaxReturn(['articleList' => $lists ?:[],'preInfo' => $preMatchinfo?:'']);
    }

}