<?php

/**
 * 世界杯活动
 *
 * @author liuweitao <cytusc@foxmaig.com>
 *
 * @since
 */
use Think\Controller;

class CupquizActivitiesController extends CommonController
{
    // 世界杯活动列表
    public function index()
    {
        //列表过滤器，生成查询Map对象
        $map = $this->_search('CupquizActivities');

        //手动获取列表
        $list = $this->_list(CM("CupquizActivities"), $map, 'id', false);

        //玩法
        $_playType = M('CupquizPlaytype')->where(['status' => 1])->select();
        foreach ($_playType as $pk => $pv) {
            $pv['options'] = json_decode($pv['options']);
            $playType[$pv['id']] = $pv;
        }


        foreach ($list as $lk => $lv) {
            $list[$lk]['end_status'] = 1;
            if ($lv['start_time'] > time()) {
                $list[$lk]['end_status'] = 0;
            }elseif (time() > $lv['end_time']){
                $list[$lk]['end_status'] = -1;
            }

            //赛事、问题选项、赛果
            //活动赛事
            $game_options = json_decode($lv['game_options']);
            $game_ids = array_column($game_options, 0);
            $games = M('GameFbinfo')
                ->alias('g')
                ->field('g.game_id, g.union_id, u.union_name,u.is_sub,g.gtime,g.game_state,g.home_team_name, g.away_team_name')
                ->join("LEFT JOIN qc_union as u  on g.union_id = u.union_id")
                ->where(['g.game_id' => ['IN', $game_ids]])
                ->select();

            foreach ($games as $gk => $gv) {
                $hn = explode(',', $gv['home_team_name']);
                $an = explode(',', $gv['away_team_name']);
                $un = explode(',', $gv['union_name']);
                $games[$gk]['home_team_name'] = $hn[0];
                $games[$gk]['away_team_name'] = $an[0];
                $games[$gk]['union_name'] = $un[0];
            }

            $keyArr = array_column($games, 'game_id');
            $valArr = array_values($games);
            $games = array_combine($keyArr, $valArr);

            //赛事玩法
            $activity_games = [];
            foreach ($game_options as $k => $v) {
                $activity_games[$k]['game_id'] = $v[0];
                $activity_games[$k]['union_name'] = $games[$v[0]]['union_name'];
                $activity_games[$k]['home_team_name'] = $games[$v[0]]['home_team_name'];
                $activity_games[$k]['away_team_name'] = $games[$v[0]]['away_team_name'];

                $activity_games[$k]['play_type_name'] = $playType[$v[1]]['name'];
                $activity_games[$k]['play_type'] = $playType[$v[1]]['id'];

                if ($v[2] != '')
                    $activity_games[$k]['answer'] = $v[2];
            }

            $list[$lk]['activity_games'] = $activity_games;
        }

        $this->assign('list', $list);
        $this->assign('play_types', $playType);
        $this->display();
    }

    //编辑
    public function edit()
    {
        // 当前活动
        $id = I('id');
        $activity = M('CupquizActivities')->where(['id' => $id])->find();
        if (!$activity)
            $this->error('参数错误!');

        //玩法
        $_playType = M('CupquizPlaytype')->where(['status' => 1])->select();
        if (!$_playType)
            $this->error('请先去玩法管理设置玩法');

        foreach ($_playType as $pk => $pv) {
            $pv['options'] = json_decode($pv['options']);
            $playType[$pv['id']] = $pv;
        }

        //活动赛事
        $game_options = json_decode($activity['game_options']);
        $game_ids = array_column($game_options, 0);
        $games = M('GameFbinfo')
            ->alias('g')
            ->field('g.game_id, g.union_id, u.union_name,u.is_sub,g.gtime,g.game_state,g.home_team_name, g.away_team_name')
            ->join("LEFT JOIN qc_union as u  on g.union_id = u.union_id")
            ->where(['g.game_id' => ['IN', $game_ids]])
            ->select();

        foreach ($games as $gk => $gv) {
            $hn = explode(',', $gv['home_team_name']);
            $an = explode(',', $gv['away_team_name']);
            $un = explode(',', $gv['union_name']);
            $games[$gk]['home_team_name'] = $hn[0];
            $games[$gk]['away_team_name'] = $an[0];
            $games[$gk]['union_name'] = $un[0];
        }

        $keyArr = array_column($games, 'game_id');
        $valArr = array_values($games);
        $games = array_combine($keyArr, $valArr);

        //赛事玩法
        $activity_games = [];
        foreach ($game_options as $k => $v) {
            $activity_games[$k]['game_id'] = $v[0];
            $activity_games[$k]['union_name'] = $games[$v[0]]['union_name'];
            $activity_games[$k]['home_team_name'] = $games[$v[0]]['home_team_name'];
            $activity_games[$k]['away_team_name'] = $games[$v[0]]['away_team_name'];

            $activity_games[$k]['play_type_name'] = $playType[$v[1]]['name'];
            $activity_games[$k]['play_type'] = $playType[$v[1]]['id'];

            if ($v[2] != '')
                $activity_games[$k]['answer'] = $v[2];
        }

        //活动已经开始或者结束，不能改动玩法
        $begin = 0;
        if($activity['start_time'] <= time() ||  time() >= $activity['end_time'] ){
           $begin = 1;
        }

        $this->assign('play_types', $playType);
        $this->assign('vo', $activity);
        $this->assign('today_games', $this->getCupMatch());
        $this->assign('activity_games', $activity_games);
        $this->assign('begin', $begin);
        $this->display();
    }

    //获取比赛列表
    public function getCupMatch()
    {
        //世界杯比赛
        $blockTime = getBlockTime(1, true);
        $games = M('GameFbinfo')
            ->alias('g')
            ->field('g.game_id, g.union_id, u.union_name,u.is_sub,g.gtime,g.game_state,g.home_team_name, g.away_team_name')
            ->join("LEFT JOIN qc_union as u  on g.union_id = u.union_id")
            ->where(['g.gtime' => ['BETWEEN', [$blockTime['beginTime'], $blockTime['endTime']]], 'g.union_id' => 75])
            ->select();

        foreach ($games as $gk => $gv) {
            $hn = explode(',', $gv['home_team_name']);
            $an = explode(',', $gv['away_team_name']);
            $games[$gk]['home_team_name'] = $hn[0];
            $games[$gk]['away_team_name'] = $an[0];
        }

        $keyArr = array_column($games, 'game_id');
        $valArr = array_values($games);
        $games = array_combine($keyArr, $valArr);

        return $games;
    }

    public function add()
    {
        //玩法
        $_playType = M('CupquizPlaytype')->where(['status' => 1])->select();
        if (!$_playType)
            $this->error('请先去玩法管理设置玩法');

        foreach ($_playType as $pk => $pv) {
            $pv['options'] = json_decode($pv['options']);
            $playType[$pv['id']] = $pv;
        }

        $this->assign('play_types', $playType);
        $this->assign('today_games', $this->getCupMatch());
        $this->display();
    }

    //更新
    public function update()
    {
        $id = I('id');
        $model = M('CupquizActivities');
        if ($id) {
            $data['title'] = I('title');
            $data['desc'] = I('desc');
            $data['auto_settle'] = I('auto_settle');
            $data['settle_type'] = I('settle_type');
            $data['sponsor'] = I('sponsor');
            $data['limit_num'] = I('limit_num');
            $data['start_time'] = strtotime(I('start_time'));
            $data['end_time'] = strtotime(I('end_time'));
            $data['status'] = I('status');

            //赛事对应的玩法、答案
            if (!I('game_options'))
                $this->error('请添加赛事玩法');

            $game_options = I('game_options');
            foreach ($game_options as $k => $v) {
                $op = explode(',', $v);

                if ($op[0] != 'null' || $op[0] != 'null') {
                    if ($answer = I('answer' . $op[0] . $op[1])) {
                        $op[2] = $answer;
                    }
                    $data['game_options'][] = $op;
                } else {
                    unset($game_options[$k]);
                }
            }

            //活动赛事
            $game_ids = array_column($data['game_options'], 0);

            $games = M('GameFbinfo')
                ->alias('g')
                ->field('g.game_id, g.union_id, u.union_name,u.is_sub,g.gtime,g.game_state,g.home_team_name, g.away_team_name')
                ->join("LEFT JOIN qc_union as u  on g.union_id = u.union_id")
                ->where(['g.game_id' => ['IN', $game_ids]])
                ->select();

            foreach ($games as $gk => $gv) {
                if($data['start_time'] > $gv['gtime'] || $data['end_time'] > $gv['gtime']){
                    $this->error('开始结束时间设置有误，请根据比赛时间重新填写！');
                }
            }

            $data['game_options'] = json_encode($data['game_options']);
            $result = $model->where('id=' . $id)->save($data);
            if ($result !== false) {
                $this->success('修改活动成功！', cookie('_currentUrl_'));
            } else {
                $this->error('修改活动失败！');
            }

        } else {//新增活动
            $data['title'] = I('title');
            $data['desc'] = I('desc');
            $data['auto_settle'] = I('auto_settle');
            $data['settle_type'] = I('settle_type');
            $data['sponsor'] = I('sponsor');
            $data['limit_num'] = I('limit_num');
            $data['start_time'] = strtotime(I('start_time'));
            $data['end_time'] = strtotime(I('end_time'));
            $data['add_time'] = time();
            $data['status'] = I('status');

            //赛事对应的玩法、答案
            if (!I('game_options'))
                $this->error('请添加赛事玩法');

            $game_options = I('game_options');
            foreach ($game_options as $k => $v) {
                $op = explode(',', $v);

                if ($op[0] != 'null' || $op[0] != 'null') {
                    if ($answer = I('answer' . $op[0] . $op[1])) {
                        $op[2] = $answer;
                    }
                    $data['game_options'][] = $op;
                } else {
                    unset($game_options[$k]);
                }
            }

            //活动赛事
            $game_ids = array_column($data['game_options'], 0);

            $games = M('GameFbinfo')
                ->alias('g')
                ->field('g.game_id, g.union_id, u.union_name,u.is_sub,g.gtime,g.game_state,g.home_team_name, g.away_team_name')
                ->join("LEFT JOIN qc_union as u  on g.union_id = u.union_id")
                ->where(['g.game_id' => ['IN', $game_ids]])
                ->select();

            foreach ($games as $gk => $gv) {
                if($data['start_time'] > $gv['gtime'] || $data['end_time'] > $gv['gtime']){
                    $this->error('开始结束时间设置有误，请根据比赛时间重新填写！');
                }
            }

            $data['game_options'] = json_encode($data['game_options']);
            $result = $model->add($data);
            if ($result !== false) {
                $this->success('添加活动成功！', cookie('_currentUrl_'));
            } else {
                $this->error('添加活动失败！');
            }
        }

    }

    /**
     * 竞猜结算、助力结算
     * @return int
     */
    public function quiz_settle()
    {

        //1 查询所有结束,并且还没结算竞猜的活动
        $acts = M("CupquizActivities")
            ->where(['status' => 1, 'end_time' => ['LT', time()], 'quize_settle' => ['NEQ', 1]])->select();

        if (!$acts)
            $this->error('当前没有可以的结算的活动！！');

        //2 判断活动下的比赛是否都完场、并且设置了答案
        $actAnswer = [];
        foreach ($acts as $k => $v) {
            //活动赛事选项
            $game_options = json_decode($v['game_options']);
            $game_ids = array_column($game_options, 0);

            //查询当前活动比赛是否都完场
            $game = M('GameFbinfo')
                ->field('game_id, game_state, home_team_name, away_team_name')
                ->where(['game_id' => ['IN', $game_ids]])
                ->select();

            foreach ($game as $gk => $gv) {
                if ($gv['game_state'] != '-1') {
                    $this->error('比赛还没全部完场！！');
                    exit;
                }
            }

            //拼接活动-赛事-玩法-答案
            foreach ($game_options as $gk => $gv) {
                if ($gv[2] === '' || !isset($gv[2])) {
                    $this->error('请设置活动答案再结算！！');
                    exit;
                } else {
                    $actAnswer[$v['id']][$gv[0]][$gv[1]] = $gv[2];
                }
            }
        }

        //3 查询活动下的所有发起
        $actIds = array_column($acts, 'id');
        $sponsor = M("CupquizSponsor")->where(['act_id' => ['IN', $actIds], 'status' => 1])->select();

        //4 查询发起活动的赛事竞猜
        $sponsorQuizResult = $gambleQuizResult = [];
        foreach ($sponsor as $sk => $sv) {
            $gambles = M("CupquizGamble")->where(['launch_id' => $sv['id']])->select();
            //全部赛事猜对、则获胜
            $sponsorQuizResult[$sv['id']] = '1';

            //玩法结算
            foreach ($gambles as $gbk => $gbv) {
                $answer = $actAnswer[$gbv['act_id']][$gbv['game_id']][$gbv['play_type']];
                //判断赛事是否猜中
                if ($answer && $gbv['chose_side'] && $answer == $gbv['chose_side']) {
                    $gambleQuizResult[$gbv['id']] = '1';
                } else {
                    $gambleQuizResult[$gbv['id']] = '-1';
                    $sponsorQuizResult[$sv['id']] = '-1';
                }
            }
        }

        //5 准备赛事竞猜结果数据
        $wGambleQuizResultIds = $nwGambleQuizResultIds = [];
        foreach ($gambleQuizResult as $grk => $grv) {
            if ($grv == '1') {
                $wGambleQuizResultIds[] = $grk;
            } else if ($grv == '-1') {
                $nwGambleQuizResultIds[] = $grk;
            }
        }

        //准备活动竞猜结果数据
        $wSponsorQuizResultIds = $nwSponsorQuizResultIds = [];
        foreach ($sponsorQuizResult as $srk => $srv) {
            if ($srv == '1') {
                $wSponsorQuizResultIds[] = $srk;
            } else if ($srv == '-1') {
                $nwSponsorQuizResultIds[] = $srk;
            }
        }

        //------------------开启事务，执行数据更新-----------------
        try {
            M()->startTrans();
            $t = time();

            //赛事表更新
            if ($wGambleQuizResultIds) {
                $data = ['result' => 1, 'result_time' => $t];
                $gambleRes1 = M("CupquizGamble")->where(['id' => ['IN', $wGambleQuizResultIds]])->save($data);
            }
            if ($nwGambleQuizResultIds) {
                $data = ['result' => -1, 'result_time' => $t];
                $gambleRes2 = M("CupquizGamble")->where(['id' => ['IN', $nwGambleQuizResultIds]])->save($data);
            }

            //发起表更新
            if ($wSponsorQuizResultIds) {
                $data = ['result' => 1, 'result_time' => $t];
                $sponsorRes1 = M("CupquizSponsor")->where(['id' => ['IN', $wSponsorQuizResultIds]])->save($data);
            }
            if ($nwSponsorQuizResultIds) {
                $data = ['result' => -1, 'result_time' => $t];
                $sponsorRes2 = M("CupquizSponsor")->where(['id' => ['IN', $nwSponsorQuizResultIds]])->save($data);
            }

            //活动表更新竞猜结算状态
            $activitiesRes2 = M("CupquizActivities")->where(['id' => ['IN', $actIds]])->save(['quize_settle' => 1]);

            if ($gambleRes1 === false || $gambleRes2 === false || $sponsorRes1 === false || $sponsorRes2 === false || $activitiesRes2 === false){
                throw new Exception('结算错误，已回滚事务，请重试！！', -1);
            }

            M()->commit();

            // 6 结算助力，筛选已经结束、已经结算竞猜的发起活动
            $acts2 = M("CupquizSponsor")->where(['is_settle' => 0])->select();
            foreach ($acts2 as $k => $v) {
                $qcoins = M("CupquizHelper")->where(['launch_id' => $v['id'], 'status' => 1])->sum('qcoin');
                M("CupquizSponsor")->where(['id' => $v['id']])->setInc('qcoin', (int)$qcoins);
                M("CupquizSponsor")->where(['id' => $v['id']])->save(['is_settle' => 1, 'settle_time' => time(), 'settle_type' => 1]);
                M("CupquizSponsor")->where(['id' => $v['id']])->setDec('qcoin', (int)$qcoins);
            }

        } catch (Exception $e) {
            M()->rollback();
            $this->error($e->getMessage());
            exit;
        }

        $this->success('结算成功！！');
    }

}