<?php
/**
 * 视频直播
 * @author huangjiezhen <418832673@qq.com> 2016.03.29
 */
class VideoController extends PublicController
{
    //直播首页列表
    public function index()
    {
        $where = [
            'f.is_video' => 1,
            'f.game_state' => ['IN',[0,1,2,3,4,-1]],
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
        $field = ['f.game_id','f.game_date','f.game_time','f.union_name','f.home_team_id','f.home_team_name','f.away_team_id','f.away_team_name','f.game_state','f.score','f.app_video','qu.union_color'];
        $list = M('GameFbinfo')->alias('f')->join("LEFT JOIN qc_union AS qu ON f.union_id = qu.union_id")->field($field)->where($where)->order('f.game_date asc,f.game_time asc')->select();

        //获取球队logo
        setTeamLogo($list);

        $liveList = [];

        foreach ($list as $k => $v)
        {
            $list[$k]['union_name']     = explode(',', $v['union_name']);
            $list[$k]['home_team_name'] = explode(',', $v['home_team_name']);
            $list[$k]['away_team_name'] = explode(',', $v['away_team_name']);
//            $list[$k]['homeTeamLogo']   = getLogoTeam($v['home_team_id'],1);
//            $list[$k]['awayTeamLogo']   = getLogoTeam($v['away_team_id'],2);
            $list[$k]['hasVideo']       = M('Highlights')->where(['game_id'=>$v['game_id'],'app_url'=>['neq','']])->find() ? 1 : 0;

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

        $this->ajaxReturn(['liveList'=>$liveList]);
    }

    //是否有直播
    public function isLive()
    {
        $liveInfo = M('GameFbinfo')->where(['game_id'=>$this->param['game_id'],'is_video'=>1,'game_state'=>['IN',[0,1,2,3,4]]])->getField('app_video');
        $liveList = [];

        if ($liveInfo)
        {
            $liveInfo = json_decode($liveInfo,true);

            foreach ($liveInfo as $k => $v)
            {
                if ($v['appname'] && $v['appurl'])
                {
                    $liveList[] = [
                        'appname'     => $v['appname'],
                        'appurl'      => htmlspecialchars_decode(stripslashes($v['appurl'])),
                        'app_ischain' => $v['app_ischain']
                    ];
                }
            }
        }

        $this->ajaxReturn(['liveList'=>$liveList]);
    }

    //战报集锦
    public function videoList()
    {
        if(!$videoList = S('Video:videoList'.MODULE_NAME.$this->param['game_id']))
        {
            $videoList = M('Highlights')->field(['title','remark','img','app_url','app_ischain','is_prospect'])
                        ->where(['game_id'=>$this->param['game_id'],'game_type'=>1,'app_url'=>['neq',''],'status'=>1])
                        ->order('add_time asc')
                        ->select();

            if ($videoList)
            {
                S('Video:videoList'.MODULE_NAME.$this->param['game_id'], json_encode($videoList), 60*10);
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
        if(!$articleList = S('Video:articleList'.MODULE_NAME.$this->param['game_id']))
        {
            $where['status'] = 1;
            $where['game_id'] = $this->param['game_id'];

            $articleList = M('PublishList')->field(['id','title','remark','img','content'])
                           ->where($where)
                           ->order('is_recommend desc,is_channel_push desc,add_time desc')
                           ->limit(10)
                           ->select();

            if ($articleList)
            {
                S('Video:articleList'.MODULE_NAME.$this->param['game_id'], json_encode($articleList), 1800);
            }
        }

        $articleList = D('Home')->getArticleImg($articleList);
        $this->ajaxReturn(['articleList'=>$articleList]);
    }

}