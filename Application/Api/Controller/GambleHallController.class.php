<?php
/**
 * 竞猜大厅
 * @author huangjiezhen <418832673@qq.com> 2015.12.02
 */

class GambleHallController extends PublicController
{
    //首页
    public function index()
    {
        $info = $this->param['info'] ?: 'football';

        if ($info == 'football')
        {
            list($game, $union) = D('GambleHall')->matchList();

            foreach ($game as $k => $v)
            {
                $game[$k]['homeTeamLogo'] = getLogoTeam($v['home_team_id'],1);
                $game[$k]['awayTeamLogo'] = getLogoTeam($v['away_team_id'],2);

                $gamble = (array)M('Gamble')->field(['play_type','chose_side'])->where(['game_id'=>$v['game_id']])->select();

                //计算竞猜玩法百分比
                $spreadNum  = 0; //让分
                $spreadHome = 0; //让分(主)
                $totalNum   = 0; //大小球
                $totalBig   = 0; //大小球(大)

                foreach ($gamble as $v)
                {
                    if ($v['play_type'] == 1)
                    {
                        $spreadNum++;

                        if ($v['chose_side'] == 1)
                            $spreadHome++;
                    }
                    else
                    {
                        $totalNum++;

                        if ($v['chose_side'] == 1)
                            $totalBig++;
                    }
                }

                $game[$k]['spreadNum']  = $spreadNum;
                $game[$k]['spreadHome'] = $spreadHome;
                $game[$k]['spreadAway'] = $spreadNum - $spreadHome;
                $game[$k]['totalNum']   = $totalNum;
                $game[$k]['totalBig']   = $totalBig;
                $game[$k]['totalSmall'] = $totalNum - $totalBig;
            }

            $this->ajaxReturn(['matchList'=>$game,'union'=>$union]);
        }
        else if ($info == 'basketball')
        {
            $this->ajaxReturn();
        }
        else
        {
            $this->ajaxReturn(2001);
        }
    }

    //赛事是否有竞猜
    public function hasGamble()
    {
        $show_date = time() >= strtotime('10:30') ? date('Ymd') : date("Ymd",strtotime("-1 day"));

        $sql = "
            select g.id,g.fsw_exp_home,g.fsw_exp,g.fsw_exp_away,g.fsw_ball_home,g.fsw_ball,g.fsw_ball_away
            from __PREFIX__game_fbinfo g,__PREFIX__union u
            where g.game_id = ".$this->param['game_id']."
            and g.fsw_exp_home != ''
            and g.fsw_exp != ''
            and g.fsw_exp_away != ''
            and g.fsw_ball_home != ''
            and g.fsw_ball != ''
            and g.fsw_ball_away != ''
            and g.union_id = u.union_id
            and ((u.is_sub < 3 and g.is_show = 1) or g.is_gamble = 1)
            and (
                g.show_date = ".$show_date."
                or (
                    g.show_date in (".date("Ymd",strtotime("-1 day")).",".date('Ymd').','.date("Ymd",strtotime("+1 day")).")
                    and g.game_state in (1,2,3,4,-1)
                )
            )
        ";

        $game = M()->query($sql);

        if ($game)
            $has = 1;
        else
            $has = 0;

        $data = [
            'has'           => $has,
            'fsw_exp_home'  => $game[0]['fsw_exp_home'],
            'fsw_exp'       => $game[0]['fsw_exp'],
            'fsw_exp_away'  => $game[0]['fsw_exp_away'],
            'fsw_ball_home' => $game[0]['fsw_ball_home'],
            'fsw_ball'      => $game[0]['fsw_ball'],
            'fsw_ball_away' => $game[0]['fsw_ball_away'],
        ];

        $this->ajaxReturn($data);
    }

    //竞猜统计
    public function gambleCount()
    {
        $page = $this->param['page'] ?: 1;
        $pageNum = 20;

        $where['game_id']   = $this->param['game_id'];
        $where['play_type'] = $this->param['play_type'];

        $gambleId = []; //本赛程查看过的竞猜记录
        $userToken = getUserToken($this->param['userToken']);

        if ($userToken && $userToken != -1)
        {
            $gambleId = (array)M('QuizLog')->where(['user_id'=>$userToken['userid'],'game_id'=>$this->param['game_id']])->getField('gamble_id',true);
            $where['user_id'] = ['NEQ',$userToken['userid']];
        }

        $list = (array)M('Gamble')->alias("g")->join("left join qc_front_user f on f.id = g.user_id")->field(['g.id gamble_id','g.user_id','g.play_type','g.chose_side','g.handcp','g.is_impt','g.result','f.head','f.nick_name'])->where($where)->select();

        foreach ($list as $k => $v)
        {
            //用户排序依据
            $list[$k]['weekPercnet']   = $weekSort[]   = D('GambleHall')->CountWinrate($v['user_id'],1,1);
            $list[$k]['monthPercnet']  = $monthSort[]  = D('GambleHall')->CountWinrate($v['user_id'],1,2);
            $list[$k]['seasonPercnet'] = $seasonSort[] = D('GambleHall')->CountWinrate($v['user_id'],1,3);
            $list[$k]['rank']          = $rankSort[]   = D('GambleHall')->getUserRank($gameType=1,$v['user_id']);
        }
        array_multisort($rankSort,SORT_ASC, $weekSort,SORT_DESC, $monthSort,SORT_DESC, $seasonSort,SORT_DESC, $list);
        $list = array_slice($list, ($page-1)*$pageNum, $pageNum);

        foreach ($list as $k => $v)
        {
            //用户信息
            $list[$k]['face']          = frontUserFace($v['head']);
            $list[$k]['tenGamble']     = D('GambleHall')->getTenGamble($v['user_id'],1);
            $list[$k]['is_trade']      = in_array($v['gamble_id'],$gambleId) ? 1 : 0;
            unset($list[$k]['head']);
        }

        $this->ajaxReturn(['gambleList'=>$list]);
    }

    //个人中心
    public function user()
    {
        if ($this->param['historyPage']) //只请求历史竞猜
        {
            $historyGamble = D('GambleHall')->getGambleList($this->param['user_id'],$dateType=2,$this->param['historyPage']); //历史竞猜
            $this->ajaxReturn(['historyGamble'=>$historyGamble]);
        }

        $userInfo = M('FrontUser')->field(['nick_name','descript','head'])->where(['id'=>$this->param['user_id']])->find();
        $winnig = D('GambleHall')->getWinning($this->param['user_id'],$gameType=1); //连胜记录
        $userInfo['curr_victs_ftball'] = $winnig['curr_victs'];
        $userInfo['max_victs_ftball']  = $winnig['max_victs'];
        $userInfo['fansNum']           = M('FollowUser')->where(['follow_id'=>$this->param['user_id']])->count();
        $userInfo['face']              = frontUserFace($userInfo['head']);
        $userInfo['weekPercnet']       = D('GambleHall')->CountWinrate($this->param['user_id'],1,1);
        $userInfo['monthPercnet']      = D('GambleHall')->CountWinrate($this->param['user_id'],1,2);
        $userInfo['seasonPercnet']     = D('GambleHall')->CountWinrate($this->param['user_id'],1,3);
        $userInfo['rank']              = D('GambleHall')->getUserRank($gameType=1,$this->param['user_id']);
        unset($userInfo['head']);

        $todayGamble = D('GambleHall')->getGambleList($this->param['user_id']); //今日竞猜
        $userToken = getUserToken($this->param['userToken']);

        foreach ($todayGamble as $k => $v)
        {
            if ($userToken) //如已经登陆
            {
                $isTrade = M('QuizLog')->where(['user_id'=>$userToken['userid'],'gamble_id'=>$v['gamble_id']])->getField('id');
                $todayGamble[$k]['is_trade'] = $isTrade ? 1 : 0;
            }
            else
            {
                $todayGamble[$k]['is_trade'] = 0;
            }
        }

        if ($userToken)
        {
            $isFollow = M('FollowUser')->where(['user_id'=>$userToken['userid'],'follow_id'=>$this->param['user_id']])->find(); //是否已经关注
            $userInfo['isFollow'] = $isFollow ? 1 : 0;
        }
        else
        {
            $userInfo['isFollow'] = 0;
        }

        $historyGamble = D('GambleHall')->getGambleList($this->param['user_id'],$dateType=2,$page=1); //历史竞猜

        $this->ajaxReturn(['userInfo'=>$userInfo,'todayGamble'=>$todayGamble,'historyGamble'=>$historyGamble]);
    }

    //排行榜
    public function rank()
    {
        $page = $this->param['page'] ?: 1;
        $pageNum = 20;

        if ($this->param['dateType'] == 4) //红人榜
        {
            $listDate = date('Ymd',strtotime("-1 day"));
            $exist = M('RedList')->where(['list_date'=>$listDate,'game_type'=>1])->field('id')->find();

            if (!$exist)
                $listDate = date('Ymd',strtotime("-2 day"));

            $field = ['user_id','ranking','gameCount','win','half','level','transport','donate','winrate','pointCount'];
            $rank = (array)M('RedList')->field($field)->where(['list_date'=>$listDate,'game_type'=>1])->page($page.','.$pageNum)->select();
        }
        else
        {
            //周、月、季
            $rank = (array)D('GambleHall')->getRankingData($gameType=1,$this->param['dateType'],$user_id=null,$more=false,$page,$pageNum);
        }

        $userToken = getUserToken($this->param['userToken']);
        $blockTime = getBlockTime(1,$gamble=true);

        foreach ($rank as $k => $v)
        {
            //用户信息
            $userInfo = M('FrontUser')->where(['id'=>$v['user_id']])->field('nick_name,head')->find();
            $rank[$k]['nick_name']    = $userInfo['nick_name'];
            $rank[$k]['face']         = frontUserFace($userInfo['head']);
            $rank[$k]['today_gamble'] = M('Gamble')->where(['user_id'=>$v['user_id'],'create_time'=>['between',[$blockTime['beginTime'],$blockTime['endTime']]]])
                                          ->getField('id') ? 1 : 0;

            //是否已经关注
            $isFollow = 0;

            if ($userToken)
                $isFollow = M('FollowUser')->where(['user_id'=>$userToken['userid'],'follow_id'=>$v['user_id']])->find();

            $rank[$k]['isFollow'] = $isFollow ? 1 : 0;
        }

        $this->ajaxReturn(['rankList'=>$rank]);
    }
}


 ?>