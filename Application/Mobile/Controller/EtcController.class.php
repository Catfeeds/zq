<?php

/**
 *
 * @author chenzj <443629770@qq.com> 2016.06.01
 */
class EtcController extends CommonController {

    function _initialize() {
        parent::_initialize();
        $user_auth = session('etc_user');
        if ($user_auth) {
            $this->assign('user_auth', $user_auth);

        }
    }
    const max_coin = 5000;
    public function index() {
        //用户信息
        $_User = M('EtcUser');
            $token = I('param.token', '');
            if ($token) {
                $rsl = $this->etc_curl("http://act.etcchebao.com/open/user/get-user-info", array('token' => $token), 'GET');
                if ($rsl['code'] == 0 && $rsl) {
                    $data = $rsl['data'];
                    $exist = $_User->where(array('uid' => $data['user_id']))->find();
                    if ($exist) {
                        $_User->where(array('uid' => $data['user_id']))->save(array(
                            'nick_name' => $data['nickname'],
                            'head_url' => $data['avatar'],
                            'integral' => $data['integral'],
                            'token' => $token,
                            'last_time' => NOW_TIME
                        ));
                        $cook_arr = array(
                            'id' => $exist['id'],
                            'integral' => $exist['integral'],
                            'nick_name' => $exist['nick_name'],
                            'head_url' => $exist['head_url'],
                            'coin' => $exist['coin'],
                            'win_coin'=>$exist['win_coin'],
                            'token'=>$token,
                            'rank' => $exist['rank'],
                        );
                    } else {
                        $user_id = $_User->add(array(
                            'uid' => $data['user_id'],
                            'nick_name' => $data['nickname'],
                            'integral' => $data['integral'],
                            'head_url' => $data['avatar'],
                            'token' => $token,
                            'coin' => 3000,
                            'create_time' => NOW_TIME,
                            'last_time' => NOW_TIME,
                            'phone' => getPhoneNumber(),
                            'reg_ip' => getIP(),
                            'ua' => getUA(),
                        ));
                        if (!$user_id) {
                            $this->error('请重新登录!');
                        }
                        $this->assign('add_type', 'day');
                        M('EtcCoinlog')->add(array(
                            'user_id' => $user_id,
                            'log_type' => 10,
                            'change_num' => 3000,
                            'add_time' => NOW_TIME
                        ));
                        $cook_arr = array(
                            'id' => $user_id,
                            'integral' => $data['integral'],
                            'nick_name' => $data['nickname'],
                            'head_url' => $data['avatar'],
                            'coin' => 3000,
                            'win_coin'=>0,
                            'token'=>$token,
                            'rank' => 0,
                        );
                    }
                    session('etc_user', $cook_arr);
                    // if ($cook_arr['day_first'] == '1') {
                    if (($exist['last_time'] < strtotime(date('Ymd')) && $exist['last_time'] != null)) {
                        $user_id = $exist['uid'];
                        $map['uid'] = $user_id;
                        M()->startTrans(); //开启事务
                        $day_add = $_User->where($map)->setInc('coin', 1000);
                        $day_close = $_User->where($map)->setField('day_first', 0);
                        if ($day_add && $day_close !== false) {
                            M()->commit(); //提交事务
                            M('EtcCoinlog')->add(array(
                                'user_id' => $user_id,
                                'log_type' => 11,
                                'change_num' => 1000,
                                'add_time' => NOW_TIME
                            ));
                            $this->assign('add_type', 'day');
                        } else {
                            M()->rollback(); //回滚事务
                            M('EtcErrlog')->add(array(
                                'user_id' => $user_id,
                                'log_type' => 11,
                                'change_num' => 1000,
                                'add_time' => NOW_TIME
                            ));
                        }
                    }
                }
            }
        $this->assign('user_auth', session('etc_user'));
        //轮播图
        $recommend = $this->get_recommend('etc', 5);
        $this->assign('recommend', $recommend);

        //赛程
        $_Game = M('EtcGame');

        //更新第二天的赛程
        // $date = date('Ymd', time());
        $date = time() >= strtotime('06:00') ? date('Ymd') : date("Ymd",strtotime("-1 day"));
        //$blockTime = getBlockTime(1);
        //是否欧洲杯
        //$game_w['runno'] = 9852;
        $where['eg.status'] = 1;
        $where['gf.status'] = 1;
        $where['gf.show_date']=$date;
        //$where['gf.gtime'] = array('between',array($blockTime['beginTime'],$blockTime['endTime']));
        $game_list = $_Game->alias('eg')->field('eg.game_id,eg.odds_win,eg.odds_flat,eg.odds_lose,eg.home_let,eg.away_let,home_team_id,home_team_name,away_team_id,away_team_name,gtime')
                        ->join('qc_game_fbinfo gf ON eg.game_id=gf.game_id')
                        ->where($where)->order('gf.gtime')->select();
        if(!$game_list){
            $sdate = $_Game->alias('eg')
                        ->join('qc_game_fbinfo gf ON eg.game_id=gf.game_id')
                        ->where(array('eg.status'=>1,'gf.status'=>1,'show_date'=>array('gt',$date)))->order('gf.show_date')->limit(1)->getField('show_date');
            $game_list = $_Game->alias('eg')->field('eg.game_id,eg.odds_win,eg.odds_flat,eg.odds_lose,eg.home_let,eg.away_let,home_team_id,home_team_name,away_team_id,away_team_name,gtime')
                        ->join('qc_game_fbinfo gf ON eg.game_id=gf.game_id')
                        ->where(array('eg.status'=>1,'gf.status'=>1,'gf.show_date'=>$sdate))->order('gf.gtime')->select();
        }
        if ($game_list) {
            $game_list = getTeamLogo($game_list);
            foreach ($game_list as &$v) {
                $v['home_team_name'] = explode(',', $v['home_team_name']);
                $v['away_team_name'] = explode(',', $v['away_team_name']);
            }
        }
        $this->assign('game_list', $game_list);

        //热门资讯
        $_Publish = M('PublishList');
        $hot_list = $_Publish->alias('pl')->field('pl.id,pl.class_id,img,title,remark')
                        ->where(array('pl.class_id' => 29, 'pl.status' => 1))
                        ->order('add_time desc,pl.is_recommend desc')
                        ->limit(8)->select();
        if ($hot_list) {
            $classArr = getPublishClass(0);
            foreach ($hot_list as &$v) {
                $v['img']  = @Think\Tool\Tool::imagesReplace($v['img']);
                $v['href'] = mNewsUrl($v['id'],$v['class_id'],$classArr);
            }
            $this->assign('hot_list', $hot_list);
        }

        //推荐说明
        $notice = M('EtcNotice')->field('title')->where(array('status' => 1, 'type' => 1))->select();
        $this->assign('notice', $notice);
        cookie('redirectUrl', __SELF__);
        $this->display();
    }

    //玩法规则
    public function rule(){
        $this->display();
    }
    //推荐
    public function betting() {
        $user_auth = session('etc_user');
        if (!$user_auth) {
            $this->error('请先登录!');
        }
        $game_id = I('post.game_id', 0, 'intval');
        $type = I('post.type', 0, 'intval');
        $coin = I('post.coin', 0, 'intval');
        $max_coin = self::max_coin;
        if(!$max_coin){
                $max_coin=5000;
            }
        if ($game_id < 1 || $type < 1 || $coin < 100) {
            $this->error('参数有误,请重试!');
        }
        if ($coin % 100 !== 0) {
            $this->error('推荐币只能为100的整倍数');
        }
        if ($coin > $max_coin) {
            $this->error('推荐币不能大于' . $max_coin);
        }
        //查找用户
        $_User = M('EtcUser');
        $where['id'] = $user_auth['id'];
        $user_info = $_User->field('coin,status,day_first')->where($where)->find();
        if (!$user_info) {
            $this->error('由于您停留太久,请重试!');
        }
        if ($user_info['status'] == 0) {
            $this->error('您的账户已被禁用,请联系全球体育!');
        }
        if ($user_info['coin'] < $coin) {
            $this->error('您的推荐币不足!');
        }
        //判断比赛是否在30分钟后
        $gtime = M('GameFbinfo')->where('game_id='.$game_id)->getField('gtime');
        if (!$gtime) {
            $this->error('推荐失败!');
        }
        $bet_time = strtotime("-10 minutes", $gtime);
        if (time() > $bet_time) {
            $this->error('该场次已停止推荐!');
        }
        //查找推荐这场比赛是否已经大于1000
        $sum_coin = M("EtcQuiz")->where(array('game_id' => $game_id, 'user_id' => $user_auth['id']))->sum('bet_coin');
        if (($sum_coin + $coin) > $max_coin) {
            $this->error('每场推荐的推荐币不能大于' . $max_coin);
        }
        M()->startTrans(); //开启事务
        $rs1 = $_User->where($where)->setDec('coin', $coin);
        $rs2 = M('EtcQuiz')->add(array(
            'user_id' => $user_auth['id'],
            'game_id' => $game_id,
            'bet_coin' => $coin,
            'bet_type' => $type,
            'add_time' => NOW_TIME
        ));
        if ($rs1 && $rs2) {
            M()->commit(); //提交事务
            $user_auth['coin'] = $user_auth['coin'] - $coin;
            session('etc_user', $user_auth);
            M('EtcCoinlog')->add(array(
                'user_id' => $user_auth['id'],
                'log_type' => 1,
                'change_num' => $coin,
                'add_time' => NOW_TIME
            ));
            $this->success($user_auth['coin']);
        } else {
            M()->rollback(); //回滚事务
            M('EtcErrlog')->add(array(
                'user_id' => $user_auth['id'],
                'log_type' => 1,
                'change_num' => $coin,
                'add_time' => NOW_TIME
            ));
            $this->error('推荐失败,请重试!');
        }
    }

    //排行榜
    public function ranking() {
        $map['rank'] = array('between', '1,100');
        $map['win_coin'] = array('gt', 0);
        $list = M('EtcUser')->field('rank,win_coin,head_url,nick_name')->where($map)->order('rank')->select();
        $this->assign('list', $list);
        $this->display();
    }

    //推荐记录
    public function record() {
        $user_auth = session('etc_user');
        if ($user_auth) {
            $_Game = M('EtcGame');
            $where['eg.status'] = 1;
            $where['gf.status'] = 1;
            $where['eq.user_id'] = $user_auth['id'];
            $list = $_Game->alias('eg')->field('eq.add_time,gf.game_state,eq.bet_coin,eq.res_coin,eq.res,eg.odds_win,eg.odds_flat,eg.odds_lose,eg.home_let,eg.away_let,home_team_name,away_team_name,gtime,gf.score,eg.rsl,eq.bet_type')
                            ->join('qc_etc_quiz eq ON eg.game_id=eq.game_id')
                            ->join('qc_game_fbinfo gf ON eg.game_id=gf.game_id')
                            ->where($where)->order('eq.add_time desc')->select();
            foreach ($list as &$v) {
                $v['home_team_name'] = explode(',', $v['home_team_name']);
                $v['away_team_name'] = explode(',', $v['away_team_name']);
                if ($v['rsl'] != '0') {
                    $arr = explode('-', $v['score']);
                    $v['home_score'] = $arr[0];
                    $v['away_score'] = $arr[1];
                }
            }
            $this->assign('list', $list);
        }
        $this->display();
    }
    //奖品兑换
    public function prize() {
        //兑奖说明
        $notice = M('EtcNotice')->field('title')->where(array('status' => 1, 'type' => 2))->select();
        $this->assign('notice', $notice);
        //奖品
        if(S('prize')){
            $rsl=S('prize');
        }else{
            $rsl = $this->do_curl("http://act.etcchebao.com/uefa/goods/list", '', '', 'GET');
            $rsl = json_decode($rsl, true);
            if ($rsl['code'] == 0 && $rsl) {
                S('prize', $rsl ,60*5);
            }
        }
        if ($rsl['code'] == 0 && $rsl) {
            $user_auth = session('etc_user');
            $data = $rsl['data'];
            //判断是否可点击
            foreach ($data as &$v) {
                if(!$user_auth){
                    $v['allow'] = 0;
                    continue;
                }
                $v['allow'] = 1;
                if (($user_auth['coin'] < $v['price']) && $user_auth) {
                    $v['allow'] = 0;
                }
                if ($v['total'] <1) {
                    $v['allow'] = 0;
                }
                if((time()>$v['end_time']) && $v['end_time']!='0'){
                    $v['allow'] = 0;
                }
            }
        }
        $this->assign('list', $data);
        $this->display();
    }
    //奖品兑换记录
    public function prize_log(){
        if(S('prize')){
            $rsl=S('prize');
        }else{
            $rsl = $this->do_curl("http://act.etcchebao.com/uefa/goods/list", '', '', 'GET');
            $rsl = json_decode($rsl, true);
            if ($rsl['code'] == 0 && $rsl) {
                S('prize', $rsl ,60*60*2);
            }
        }
        $user_auth = session('etc_user');
        if ($rsl['code'] == 0 && $rsl && $user_auth) {
            $data=$rsl['data'];
            $list=M('EtcPrizelog')->where('user_id='.$user_auth['id'])->select();
            foreach($list as $k => &$v){
                foreach ($data as $key=>$val){
                    if($v['prize_id']==$val['id']){
                        $v['img']=$val['img'];
                        $v['title']=$val['title'];
                        $v['coin']=$val['price'];
                    }
                }
            }
            $this->assign('list',$list);
        }
        $this->display();
    }
    //执行兑换
    public function doprize() {
        $user_auth = session('etc_user');
        if (!$user_auth) {
            $this->error('请先登录!');
        }
        $id = I('post.id', 0, 'intval');
        if ($id < 1) {
            $this->error('参数有误,请重试!');
        }
        if(S('prize')){
            $rsl=S('prize');
        }else{
            $rsl = $this->do_curl("http://act.etcchebao.com/uefa/goods/list", '', '', 'GET');
            $rsl = json_decode($rsl, true);
            if ($rsl['code'] == 0 && $rsl) {
                S('prize', $rsl ,60*60*2);
            }
        }
        if ($rsl['code'] == 0 && $rsl) {
            $data = $rsl['data'];
            //是否存在此商品
            $flag=false;
            $coin=0;
            $type=1;
            foreach ($data as $v){
                if($v['id']==$id){
                    $flag=true;
                    $coin=$v['price'];
                    $type=$v['type'];
                    break;
                }
            }
            if(!$flag || $coin==0){
                $this->error('找不到此商品!');
            }
            $user_coin = M('EtcUser')->where(array('id' => $user_auth['id'], 'status' => 1))->getField('coin');
            if (!$user_coin) {
                $this->error('此账户已被冻结!');
            }
            if ($coin > $user_coin) {
                $this->error('推荐币不足!');
            }
            $params['token']=$user_auth['token'];
            $params['goods_id']=$id;
            $params['price']=$coin;
            $params['quantity']=1;
            $params['payment']=$coin;
            if($type==3){
                $params['receiver_name'] = I('post.re_name', '');
                $params['receiver_mobile'] = I('post.re_phone', '');
                $params['receiver_address'] = I('post.re_address', '');
                if(empty($params['receiver_name']) || empty($params['receiver_mobile']) || empty($params['receiver_address'])){
                    $this->error('请输入收件人信息!');
                }
            }
            $change = $this->etc_curl("http://act.etcchebao.com/uefa/order/save", $params);
            if ($change['code'] == 0 && $change) {
                $rs1 = M('EtcUser')->where(array('id' => $user_auth['id']))->setDec('coin', $coin);
                M('EtcPrizelog')->add(array(
                    'user_id' => $user_auth['id'],
                    'prize_id' => $id,
                    'add_time' => NOW_TIME,
                ));
                if($rs1){
                    M('EtcCoinlog')->add(array(
                        'user_id' => $user_auth['id'],
                        'log_type' => 3,
                        'change_num' => $coin,
                        'add_time' => NOW_TIME
                    ));
                } else {
                    M()->rollback(); //回滚事务
                    M('EtcErrlog')->add(array(
                        'user_id' => $user_auth['id'],
                        'log_type' => 3,
                        'change_num' => $coin,
                        'add_time' => NOW_TIME
                    ));
                }
                $return['msg'] = '兑换成功!';
                $return['coin'] = $user_auth['coin'] - $coin;
                $user_auth['coin'] = $return['coin'];
                session('etc_user', $user_auth);
                $this->success($return);
            }else{
                $this->error($change['msg']);
            }
        }else{
            $this->error('暂时无法兑换!');
        }
    }

    //兑换推荐币
    public function change() {
        $this->display();
    }
    //兑换记录
    public function change_log() {
        $user_auth = session('etc_user');
        if($user_auth){
            $list=M('EtcChange')->where('user_id='.$user_auth['id'])->select();
            $this->assign('list',$list);
        }
        $this->display();
    }

    public function dochange() {
        $user_auth = session('etc_user');
        if (!$user_auth) {
            $this->error('请先登录!');
        }
        $type = I('param.type', 0, 'intval');
        if ($type < 1 || $type > 6) {
            $this->error('参数有误,请重试!');
        }
        //获取兑换的积分
        $arr = array(
            1 => 100,
            2 => 300,
            3 => 500,
            4 => 1000,
            5 => 2000,
//            6 => 3000,
        );
        $num = $arr[$type];
        if ($user_auth['integral'] < $num) {
            $this->error('积分不足!');
        }
        $sumIntegral = M('EtcChange')->where(array('user_id' => $user_auth['id'],'add_date'=>date('Ymd', time())))->sum('change_integral');
        if (($sumIntegral + $num) > 2000) {
            $remain = 2000 - $sumIntegral;
            $this->error('您今天兑换还剩:' . $remain);
        }
        $where['id'] = $user_auth['id'];
        $token = $user_auth['token'];
        $rsl = $this->etc_curl("http://act.etcchebao.com/open/user/consume-integral", array('token' => $token, 'integral' => $num));
        if ($rsl['code'] == 0) {
            M()->startTrans(); //开启事务
            $rs_coin = M('EtcUser')->where($where)->save(array(
                'coin' => ['exp', 'coin+' . $num],
                'integral' => $rsl['data']['integral']
            ));
            $add_change = M('EtcChange')->add(array(
                'user_id' => $user_auth['id'],
                'change_integral' => $num,
                'add_date' => date('Ymd', time()),
                'add_time' => NOW_TIME
            ));
            if ($rs_coin && $add_change) {
                M()->commit();
                M('EtcCoinlog')->add(array(
                    'user_id' => $user_auth['id'],
                    'log_type' => 6,
                    'change_num' => $num,
                    'add_time' => NOW_TIME
                ));
                $data['integral'] = $user_auth['integral'] = $rsl['data']['integral'];
                $user_auth['coin'] = $user_auth['coin'] + $num;
                $data['coin'] = $user_auth['coin'];
                session('etc_user', $user_auth);
                $data['msg'] = '兑换成功!';
                $this->success($data);
            } else {
                M()->rollback();
                M('EtcErrlog')->add(array(
                    'user_id' => $user_auth['id'],
                    'log_type' => 6,
                    'change_num' => $num,
                    'add_time' => NOW_TIME
                ));
                $this->error('兑换失败,请重试!');
            }
        } else {
            $this->error('兑换失败!');
        }
    }

    //更新比赛结果
    public function gameover_rsl() {
        $_M = M('EtcGame');
        $map['eg.status'] = 1;
        $map['eg.rsl'] = 0;
        $map['gf.status'] = 1;
        $map['gf.game_state'] = '-1';
        $rsl = $_M->alias('eg')->field('gf.score,eg.game_id,eg.home_let,eg.away_let,odds_win,odds_flat,odds_lose')
                        ->join('qc_game_fbinfo gf ON eg.game_id=gf.game_id')
                        ->where($map)->select();
        if ($rsl) {
            foreach ($rsl as &$v) {
                $v['score'] = explode('-', $v['score']);
                $home = $v['score'][0] - $v['home_let'];
                $away = $v['score'][1] - $v['away_let'];
                $compare = $home - $away;
                if ($compare < 0) {
                    $odds=$v['odds_lose'];
                    $game_rsl = 3;
                } else if ($compare > 0) {
                    $odds=$v['odds_win'];
                    $game_rsl = 1;
                } else {
                    $odds=$v['odds_flat'];
                    $game_rsl = 2;
                }
                $_M->where(array('game_id' => $v['game_id']))->setField('rsl', $game_rsl);
            }
            // M('EtcUser')->where(['robot'=>1,'rank'=>['egt',46]])->delete(); //只保留排行榜43个机器人
            // $ids=M('EtcUser')->field('id,win_coin')->where(array('robot'=>1))->select();
            // $max_coin=self::max_coin;
            // if(!$max_coin){
            //     $max_coin=5000;
            // }
            // foreach ($ids as $v){
            //     M('EtcUser')->where(array('id'=>$v['id'],'robot'=>1))->save(['win_coin'=>['exp','win_coin+'.($max_coin*$odds-$max_coin)]]);
            // }

            // M('EtcUser')->where(array('robot'=>1,'rank'=>array('gt',20)))->setDec('win_coin',16000);
            $this->autosettlement();
            echo '执行结算成功!';
        }else{
            echo '该比赛还没结束或者已经更新了!';
        }
    }

    //结算
    public function autosettlement() {
        set_time_limit(0);
        $_EQuiz = M('EtcQuiz');
        $map['res'] = 0;
        $map['rsl'] = array('neq', 0);
        $rsl = $_EQuiz->alias('eq')->field('eq.id,eg.odds_win,eg.odds_flat,eg.odds_lose,eq.user_id,bet_coin,eg.rsl,eq.bet_type')
                        ->join('qc_etc_game eg ON eq.game_id=eg.game_id')
                        ->where($map)->select();
        foreach ($rsl as &$v) {
            if ($v['rsl'] == $v['bet_type']) {
                switch ($v['bet_type']) {
                    case 1:
                        $v['res_coin'] = $v['bet_coin'] * $v['odds_win'];
                        break;
                    case 2:
                        $v['res_coin'] = $v['bet_coin'] * $v['odds_flat'];
                        break;
                    case 3:
                        $v['res_coin'] = $v['bet_coin'] * $v['odds_lose'];
                        break;
                }
                $win_coin=$v['res_coin']-$v['bet_coin'];
                M()->startTrans(); //开启事务
                $rs2 = $_EQuiz->where(array('id' => $v['id']))->save(array(
                    'res' => 1,
                    'res_coin' => $v['res_coin']
                ));
                $rs3 = M('EtcUser')->where(array('id' => $v['user_id']))->save(['coin' => ['exp', 'coin+' . $v['res_coin']], 'win_coin' => ['exp', 'win_coin+' . $win_coin]]);
                if ($rs2 && $rs3)
                    M()->commit();
                else
                    M()->rollback();
            }else {
                M()->startTrans(); //开启事务
                $rs4 = $_EQuiz->where(array('id' => $v['id']))->save(array(
                    'res' => 2,
                    'res_coin' => $v['bet_coin']
                ));
                $rs5 = M('EtcUser')->where(array('id' => $v['user_id']))->setDec('win_coin', $v['bet_coin']);
                if ($rs4 && $rs5)
                    M()->commit();
                else
                    M()->rollback();
            }
        }

        /*
            机器人规则
            1、前三名都是真实用户；
            2、4-10名有三个人拿到球衣；
            3、11-50名有20个人拿到话费；
            4、50-100拿到20个抱枕；
         */
        $robot = M('EtcUser')->field('uid,nick_name,head_url,robot')->where(['robot'=>1])->order('rank')->select(); //查出所有机器人
        M('EtcUser')->where(['robot'=>1])->delete(); //删除所有机器人
        $realUser = M('EtcUser')->field('win_coin')->where(['robot'=>0])->order('win_coin desc')->limit(100)->select(); //取出真实用户的前100名

        foreach ($realUser as $k => $v)
        {
            $realRank = $k + 1;
            $robotWinCoin = ['win_coin'=>$v['win_coin']+mt_rand(10,20)]; //每个机器人比真实用户随机多10-20个盈利币

            if ($realRank >= 4 && $realRank <= 10)
            {
                M('EtcUser')->add(array_merge(array_shift($robot),$robotWinCoin));
            }

            if ($realRank >= 11 && $realRank <= 50)
            {
                M('EtcUser')->add(array_merge(array_shift($robot),$robotWinCoin));
            }

            if ($realRank >= 50 && $realRank <= 100)
            {
                if ($lastRobot = array_shift($robot))
                    M('EtcUser')->add(array_merge($lastRobot,$robotWinCoin));
            }
        }

        //更新排名
        $sql3 = "DROP TABLE IF EXISTS `qc_etc_temp`";
        M()->execute($sql3);
        $sql = "CREATE TABLE IF NOT EXISTS `qc_etc_temp` ( `id` int(11) NOT NULL AUTO_INCREMENT, `u_id` int(11) NOT NULL , PRIMARY KEY (`id`), UNIQUE INDEX `u_id` (`u_id`) )";
        $rsl1 = M()->execute($sql);
        if ($rsl1 !== false) {
            $sql2 = "INSERT INTO `qc_etc_temp` (u_id)SELECT id FROM `qc_etc_user` ORDER BY win_coin desc";
            $rsl2 = M()->execute($sql2);
            $rank_sql = "UPDATE `qc_etc_user` eu  INNER JOIN `qc_etc_temp` et ON eu.id=et.u_id SET eu.rank = et.id";
            $rsl_rank = M()->execute($rank_sql);
            $sql3 = "DROP TABLE IF EXISTS `qc_etc_temp`";
            $rsl3 = M()->execute($sql3);
        }
        //更新session
        $user_auth = session('etc_user');
        if ($user_auth) {
            $user_auth = M('EtcUser')->field('id,nick_name,head_url,rank,integral,coin,win_coin')->where("id=" . $user_auth['id'])->find();
            session('etc_user', $user_auth);
        }
        echo '执行成功排名!';
    }

    //生成兑换码页面
    public function exchange()
    {
        $user_auth = session('etc_user');
        if($user_auth){
            $code=M('EtcCode')->where(array('etc_id'=>$user_auth['id']))->order('id desc')->getField('code');
            $this->assign('code',$code);
        }
        $this->display();
    }

    //执行兑换
    public function getCode()
    {
        if(time() > 1468857600)
        {
            $this->error('更多活动<br/>敬请期待');
        }
        $user_auth = session('etc_user');
        if (!$user_auth) {
            $this->error('请先登录!');
        }
        $number = I('post.number');
        if(empty($number) || is_int($number)){
            $this->error('参数有误,请重试!');
        }
        if($user_auth['coin'] < 1000){
            $this->error('您的推荐币不足！');
        }
        //最多可兑换次数
        $sure = floor($user_auth['coin']/1000);
        if( $number > $sure ){
            $this->error('您最多可以兑换'.$sure.'次抽奖机会噢！');
        }

        M()->startTrans(); //开启事务
        //减去推荐币
        $rs = M('etc_user')->where(['id'=>$user_auth['id']])->setDec('coin',$number*1000);

        //添加推荐币记录
        $rs2 =M('EtcCoinlog')->add(array(
            'user_id' => $user_auth['id'],
            'log_type' => 12,
            'change_num' => $number*1000,
            'add_time' => NOW_TIME
        ));

        //更新session
        $new_coin = $user_auth['coin'] - $number*1000;
        $user_auth['coin'] = $new_coin;
        session('etc_user', $user_auth);

        //生成唯一兑换码
        $str = $this->randStr().$this->randStr(4,2).$this->randStr().$this->randStr(4,2);
        $rs3 = M('etc_code')->add(['etc_id'=>$user_auth['id'],'code'=>$str,'number'=>$number,'add_time'=>NOW_TIME]);

        if($rs && $rs2 && $rs3){
            M()->commit();
            $this->success(['code'=>$str,'coin'=>$new_coin,'number'=>floor($new_coin/1000)]);
        }else{
            M()->rollback();
            $this->error('兑换失败，请稍后重试！');
        }
    }
    //活动抽奖页面
    public function gacha()
    {
        //是否登录
        C('DATA_CACHE_PREFIX','api_');
        $token = I('userToken');
        $userInfo = S($token);
        if($userInfo){
            //查询剩余抽奖次数
            $etc_gacha = M('etc_gacha')->where(['user_id'=>$userInfo['userid']])->find();
            $this->assign('gacha_times',$etc_gacha['gacha_times']);
            $this->assign('userInfo',$userInfo);
        }
        //抽奖公告记录
        $gachaLog = M('etc_gachalog e')
                    ->join("LEFT JOIN qc_front_user f on f.id=e.user_id")
                    ->field("e.prize_id,f.username")
                    ->limit(30)
                    ->order('add_time desc')
                    ->select();
        $this->assign('gachaLog',$gachaLog);
        //奖品数组
        $prize_arr = $this->setPrize();
        ksort($prize_arr);
        $this->assign('prize_arr',$prize_arr);
        $this->display();
    }
    //执行抽奖
    public function getPrize()
    {
        if(time() > 1468857600)
        {
            $this->error('更多活动<br/>敬请期待');
        }
        //是否登录
        $userid = I('post.userid');
        if($userid == '')
        {
            $this->error('请先登录!');
        }
        //查询剩余抽奖次数
        $etc_gacha = M('etc_gacha')->where(['user_id'=>$userid])->find();
        if(!$etc_gacha || $etc_gacha['gacha_times'] <= 0)
        {
            $this->error('您没有抽奖机会，可使用兑换码兑换！');
        }
        //奖品数组
        $prize_arr = $this->setPrize();

        foreach ($prize_arr as $key => $val) {
            $arr[$val['id']] = $val['v'];
        }
        //根据概率获取奖项id
        $rid = $this->get_rand($arr);

        $prize = $prize_arr[$rid-1]; //中奖项
        if(!in_array($prize['id'], [3,5,6,7])){
            $this->error('系统繁忙，请稍后重试！');
        }

        M()->startTrans(); //开启事务
        //添加抽奖记录
        $log = ['user_id'=>$userid,'prize_id'=>$prize['id'],'add_time'=>NOW_TIME];
        if($prize['id'] == 5 || $prize['id'] == 7) $log['status'] = 1;
        $rs = M('etc_gachalog')->add($log);
        //抽奖次数减一
        $rs2 = M('etc_gacha')->where(['user_id'=>$userid])->setDec('gacha_times',1);
        if($prize['id'] == 5 || $prize['id'] == 7)
        {
            //积分直接给用户添加
            $old_point = M('FrontUser')->where(['id'=>$userid])->getField('point');
            $point = $prize['id'] == 7 ? '1000' : '2000';
            $rs3 = M('FrontUser')->where(['id'=>$userid])->setInc('point',$point);
            //添加积分记录
            $rs4 = M("pointLog")->add([
                'user_id'     => $userid,
                'log_time'    => NOW_TIME,
                'log_type'    => 13,
                'change_num'  => $point,
                'total_point' => $old_point + $point,
                'desc'        => '抽奖赠送',
            ]);
            if($rs && $rs2 && $rs3 && $rs4){
                M()->commit();
                $this->success(['rid'=>$rid,'gacha_id'=>$rs,'gacha_times'=>$etc_gacha['gacha_times']-1]);
            }else{
                M()->rollback();
                $this->error('抽奖失败，请稍后重试！');
            }
        }elseif ($prize['id'] == 3 || $prize['id'] == 6){
            //流量
            if($rs && $rs2){
                M()->commit();
                $this->success(['rid'=>$rid,'gacha_id'=>$rs,'gacha_times'=>$etc_gacha['gacha_times']-1]);
            }else{
                M()->rollback();
                $this->error('抽奖失败，请稍后重试！');
            }
        }elseif (in_array($prize['id'], [1,2,4,8])){
            //实物
            if($rs && $rs2){
                M()->commit();
                $this->success(['rid'=>$rid,'gacha_id'=>$rs,'gacha_times'=>$etc_gacha['gacha_times']-1]);
            }else{
                M()->rollback();
                $this->error('抽奖失败，请稍后重试！');
            }
        }else{
            //不存在该奖品
            M()->rollback();
            $this->error('抽奖失败，请稍后重试！');
        }
    }

    public function sendingFlow()
    {

        $gacha_id = I('post.gacha_id');
        $userid = I('post.userid');
        //是否有该中奖记录
        $gacha = M('etc_gachalog')->where(['id'=>$gacha_id,'user_id'=>$userid])->find();
        if(!$gacha){
            $this->error('领取失败，请联系工作人员');
        }
        if($gacha['status'] == 1){
            $this->error('领取失败，你已领取过了');
        }
        //判断手机运营商
        $mobile = I('post.mobile');
        if(preg_match('/^(134|135|136|137|138|139|150|151|157|158|159|187|188)[0-9]{8}$/', $mobile)){
            //移动
            $type = 1;
        }elseif (preg_match('/^(130|131|132|152|155|156|185|186)[0-9]{8}$/', $mobile)) {
            //联通
            $type = 2;
        }elseif (preg_match('/^(133|153|180|189)[0-9]{8}$/', $mobile)) {
            //电信
            $type =3;
        }
        if($gacha['prize_id'] == 3){
            //移动70M,联通电信100M
            $size = $type == 1 ? '70' : '100';
        }elseif ($gacha['prize_id'] == 6) {
            //联通50M, 移动电信30M
            $size = $type == 2 ? '50' : '30';
        }
        //执行充值
        $res = sendingFlow($mobile,$size);
        if($res['code'] == 0){
            //奖品改为已领取
            M('etc_gachalog')->where(['id'=>$gacha_id,'user_id'=>$userid])->save(['status'=>1]);
            $this->success('充值成功');
        }else{
            $this->error('充值失败,请重试！');
        }
    }

    //兑换码换抽奖次数
    public function doGacha()
    {
        $userid = I('post.userid');
        $code = I('post.code');
        if($userid == '' || $code == '')
        {
            $this->error('参数错误');
        }
        //查询兑换码
        $etc_code = M('etc_code')->where(['code'=>$code])->field('id,number,status')->find();
        if(!$etc_code){
            $this->error('该兑换码不存在！');
        }
        if($etc_code['status'] == 1){
            $this->error('该兑换码已被使用！');
        }
        //添加抽奖次数
        $etc_gacha = M('etc_gacha')->where(['user_id'=>$userid])->find();
        M()->startTrans(); //开启事务
        if(!$etc_gacha){
            //添加新记录
            $rs = M('etc_gacha')->add(['user_id'=>$userid,'gacha_times'=>$etc_code['number']]);
            $gacha_times = $etc_code['number'];
        }else{
            //添加抽奖次数
            $rs = M('etc_gacha')->where(['user_id'=>$userid])->setInc('gacha_times',$etc_code['number']);
            $gacha_times = $etc_code['number'] + $etc_gacha['gacha_times'];
        }
        //兑换码改为已使用
        $rs2 = M('etc_code')->where(['id'=>$etc_code['id']])->save(['status'=>1,'use_time'=>NOW_TIME,'user_id'=>$userid]);

        if($rs && $rs2){
            M()->commit();
            $this->success($gacha_times);
        }else{
            M()->rollback();
            $this->error('兑换失败，请稍后重试！');
        }
    }
    //设置奖品
    public function setPrize()
    {
        $prize_arr = array(
          '0' => array('id'=>'1','prize'=>'iphone6S(128G)','v'=>0 , 'img'=>staticDomain("/Public/Mobile/images/etc/jp01.png")          ),
          '1' => array('id'=>'2','prize'=>'恒大7月VIP门票','v'=>0 , 'img'=>staticDomain("/Public/Mobile/images/etc/img_cj_myq.png")    ),
          '2' => array('id'=>'3','prize'=>'4G流量卡',      'v'=>2,  'img'=>staticDomain("/Public/Mobile/images/etc/img_cj_myq_50.png") ),
          '3' => array('id'=>'4','prize'=>'行车记录仪',    'v'=>0,  'img'=>staticDomain("/Public/Mobile/images/etc/img_cj_xxcy.png")   ),
          '4' => array('id'=>'5','prize'=>'2000积分',      'v'=>15, 'img'=>staticDomain("/Public/Mobile/images/etc/img_cj_jinbi.png")  ),
          '5' => array('id'=>'6','prize'=>'4G流量卡',      'v'=>4,  'img'=>staticDomain("/Public/Mobile/images/etc/img_cj_myq_100.png")),
          '6' => array('id'=>'7','prize'=>'1000积分',      'v'=>79, 'img'=>staticDomain("/Public/Mobile/images/etc/img_cj_jinbi.png")  ),
          '7' => array('id'=>'8','prize'=>'欧洲杯官方T恤', 'v'=>0,  'img'=>staticDomain("/Public/Mobile/images/etc/img_cj_smdl.png")   ),
        );
        return $prize_arr;
    }

    //根据概率获取奖项id
    public function get_rand($proArr) {
        $result = '';
        //概率数组的总概率精度
        $proSum = array_sum($proArr);
        //概率数组循环
        foreach ($proArr as $key => $proCur) {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $proCur) {
                $result = $key;
                break;
            } else {
                $proSum -= $proCur;
            }
        }
        unset ($proArr);
        return $result;
    }

    /**
    * 随机生成指定长度字符串函数
    * @param int $length    #长度
    * @param int $type      #生成类型，1为字母，2为数字
    * @return string
    */
    public function randStr($length=4, $type=1)
    {
        //字母or数字
        $chars = $type == 1 ? 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' : '0123456789';
        $str = '';
        for ( $i = 0; $i < $length; $i++ )
        {
            // 第一种是使用substr 截取$chars中的任意一位字符；第二种是取字符数组$chars 的任意元素
            //$str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
            $str .= $chars[ mt_rand(0, strlen($chars) - 1) ];
        }
        return $str;
    }
}

?>