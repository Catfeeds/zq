<?php

/**
 * 产品推荐列表
 *
 * @author liuweitao <cytusc@foxmaig.com>
 *
 * @since
 */
use Think\Controller;

class IntroGambleController extends CommonController
{
    public function index()
    {
        //排序字段 默认为主键名
        $order = $_REQUEST ['_order'] ? : 'id';
        //排序方式默认按照倒序排列
        $sort = $_REQUEST ['_sort'] == 'asc' ? 'asc' : 'desc';
        //名字查询
        $name = trim(I('name'));
        if (!empty($name)) {
            $map['ip.name'] = ['Like', $name . '%'];
        }
        //赛事名称
        $union_name = trim(I('union_name'));
        if (!empty($union_name)) {
            $map['ig.union_name'] = ['Like', '%'.$union_name . '%'];
        }
        //主队名
        $home_team_name = trim(I('home_team_name'));
        if (!empty($home_team_name)) {
            $map['ig.home_team_name'] = ['Like', '%'.$home_team_name . '%'];
        }
        //客队名
        $away_team_name = trim(I('away_team_name'));
        if (!empty($away_team_name)) {
            $map['ig.away_team_name'] = ['Like', '%'.$away_team_name . '%'];
        }
        //本场推荐结果
        $result = trim(I('result'));
        if ($result != '') {
            if($result == 3) $result = '0.5'; 
            if($result == 4) $result = '-0.5';
            $map['ig.result'] = $result;
        }
        //玩法
        $play_type = trim(I('play_type'));
        if (!empty($play_type)) {
            $map['ig.play_type'] = $play_type;
        }
        //所属推介
        $list_id = trim(I('list_id'));
        if (!empty($list_id)) {
            $map['ig.list_id'] = $list_id;
        }
        //不是admin只有属于自己的发布推荐
        // $admin_id = $_SESSION['authId'];
        // if (!in_array($admin_id, C('RBAC_LOGIN_USER')))
        // {
        //     $map['ip.admin_id'] = $admin_id;
        // }

        $count = M('IntroGamble ig')->join('LEFT JOIN qc_intro_products ip on ip.id = ig.product_id')->where($map)->count('ig.id');
        //获取每页显示的条数
        $pageNum = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
        //获取当前的页码
        $currentPage = !empty($_REQUEST[C('VAR_PAGE')]) ? $_REQUEST[C('VAR_PAGE')] : 1;
        if ($count > 0) {
            $list = M('IntroGamble ig')
                ->Field('ig.id,ig.union_name,ig.game_id,ig.gtime,ig.home_team_name,gf.score,gf.half_score,ig.away_team_name,ig.play_type,ig.chose_side,ig.result,ig.create_time,ig.handcp,ig.odds,ig.is_change,ip.name,gf.game_state')
                ->join('LEFT JOIN qc_intro_products ip on ip.id = ig.product_id')
                ->join('LEFT JOIN qc_game_fbinfo gf on gf.game_id = ig.game_id')
                ->where($map)
                ->order($order." ".$sort)
                ->limit($pageNum * ($currentPage - 1), $pageNum)
                ->select();
            foreach ($list as $k => $v)
            {
                if($v['game_state'] == -1)
                {
                    $result = getTheWin($v['score'],$v['play_type'],$v['handcp'],$v['chose_side']);
                }
                else
                {
                    $result = $v['game_state'];
                }
                $list[$k]['show_result'] = $result;
            }
        }
        $this->assign('list', $list);
        $this->assign('totalCount', $count);//当前条件下数据的总条数
        $this->assign('numPerPage', $pageNum); //每页显示多少条
        $this->assign('currentPage', $currentPage);//当前页码
        $this->setJumpUrl();
        $this->display();
    }

    //结算推介
    public function runIntroGamble()
    {
        $reTime = strtotime("10:32");
        if(time() > $reTime && (time() < $reTime + 360))
        {
            //重置预购人数与每天随机剩余配置
            $sql = "UPDATE `qc_intro_products` SET `pay_num`=0";
            S('admin_runIntroPayNum',null);
            M()->execute($sql);
        }
        $inTime = S('admin_runIntroProducts');
        //每小时只执行一次
        if(date('H') != $inTime)
        {
            $blockTime  = getBlockTime(1, true);
            $IntroProducts = M('IntroProducts')->field('id,total_num,pay_num')->where(['status'=>1,'is_auto_pay'=>1])->order('id desc')->select();

            $product_id = array_map("array_shift", $IntroProducts);
            //今天的购买
            $IntroBuy = M('IntroBuy')->field('product_id,count(product_id) as buy_num')->where(['product_id'=>['in',$product_id],'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]]])->group('product_id')->select();
            //今天的发布
            $IntroLists = M('IntroLists')->field('id,product_id,remain_num')->where(['product_id'=>['in',$product_id],'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]]])->select();

            foreach ($IntroProducts as $k => $v) {
                //是否发布
                foreach ($IntroLists as $kk => $vv) {
                    if($v['id'] == $vv['product_id']){
                        $IntroProducts[$k]['list'] = $vv;
                    }
                }
                //是否购买
                foreach ($IntroBuy as $b => $bb) {
                    if($v['id'] == $bb['product_id']){
                        $IntroProducts[$k]['buy_num'] = $bb['buy_num'];
                    }
                }
                if($v['total_num'] > 10){
                    //随机剩余7-12
                    $randConf[$v['id']]['rand'] = rand(7,12);
                }else{
                    //随机剩余2-5
                    $randConf[$v['id']]['rand'] = rand(2,5);
                }
            }

            if(!$randLastNum = S('admin_runIntroPayNum')){
                //每天配置
                $randLastNum = $randConf;
                S('admin_runIntroPayNum',$randLastNum);
            }

            //对应每天配置
            foreach ($IntroProducts as $k => $v) {
                foreach ($randLastNum as $kk => $vv) {
                    if($v['id'] == $kk){
                        $IntroProducts[$k]['rand'] = $vv['rand'];
                    }
                }
            }

            foreach ($IntroProducts as $k => $v) {
                //随机减去0到2
                $randNum = rand(0,2);
                if(empty($v['list'])){
                    //未发布
                    $s_num = $v['total_num'] - $v['buy_num'] - $v['pay_num'];
                    if($s_num <= $v['rand']){
                        continue;
                    }
                    //达到剩余数量或10点时取剩下的一次性减去
                    if( ($s_num - $randNum) < $v['rand'] || date('H') == 22 ){
                        $randNum = $s_num - $v['rand'];
                    }
                    M('IntroProducts')->where(['id'=>$v['id']])->save(['pay_num'=>['exp','pay_num+'.$randNum]]);
                }else{
                    //已发布
                    $s_num = $v['list']['remain_num'] - $v['buy_num'];
                    if($s_num <= $v['rand']){
                        continue;
                    }
                    //达到剩余数量或10点时取剩下的一次性减去
                    if(($s_num - $randNum) < $v['rand'] || date('H') == 22 ){
                        $randNum = $s_num - $v['rand'];
                    }
                    M('IntroLists')->where(['id'=>$v['list']['id']])->save(['remain_num'=>['exp','remain_num-'.$randNum]]);
                }
            }
            S('admin_runIntroProducts',date('H'));
        }

        $create_time = NOW_TIME - 86400*3;
        $gtime       = NOW_TIME - 3600*5;
        //比赛开始后超过5小时还没结算的改为取消
        $qx_gamble = M('IntroGamble g')
                ->join("LEFT JOIN qc_game_fbinfo gf on gf.game_id = g.game_id")
                ->field("g.id,gf.gtime,gf.game_state")
                ->where("g.result not in(1,-1,2,0.5,-0.5,-10) AND g.create_time > {$create_time} AND g.gtime < {$gtime} AND gf.game_state != -1")
                ->group("g.id")
                ->order("g.id asc")
                ->select();
        //         echo M('IntroGamble g')->_sql();
        // dump($qx_gamble);
        // die;
        foreach ($qx_gamble as $v)
        {
            //更新竞猜记录表结果为取消
            M('IntroGamble')->where(['id'=>$v['id']])->save(['result'=> '-10']);
        }

        $list = M('IntroGamble g')
                ->master(true)
                ->field("g.id,g.result,g.list_id,g.gtime,g.play_type,g.handcp,g.chose_side,g.odds,g.odds_other,gf.game_state,gf.score,gf.half_score,l.product_id")
                ->join('LEFT JOIN qc_game_fbinfo gf on gf.game_id = g.game_id')
                ->join('LEFT JOIN qc_intro_lists l on l.id = g.list_id')
                ->where("(g.result in(0,-10,-11,-12,-13,-14) AND g.create_time > {$create_time} AND g.result <> gf.game_state AND gf.game_state < 0) OR (g.score <> gf.score AND g.create_time > {$create_time})")
                ->group("g.id")
                ->order("g.id asc")
                ->select();
        // echo M('IntroGamble g')->_sql();
        // die;
        $num = $Tnum = $Dnum = 0;
        if($list)
        {
            foreach ($list as $v)
            {
                if ($v['game_state'] != -1)
                {
                    if($v['result'] != $v['game_state'])
                    {
                        M('IntroGamble')->where(['id'=>$v['id']])->save(['result'=>$v['game_state']]);
                        $num++;
                    }
                    continue;
                }

                $result = getTheWin($v['score'],$v['play_type'],$v['handcp'],$v['chose_side']);
                $saveArray = [
                    'score'      => $v['score'],
                    'half_score' => $v['half_score'],
                    'result'     => $result,
                ];
                
                // dump($saveArray);
                // die;
                //更新竞猜记录表的比分、状态
                $updateGamble = M('IntroGamble')->where(['id'=>$v['id']])->save($saveArray);
                $num++;
            }
        }  
        //找出需要推送的消息
        $mobileMsg = M('mobileMsg m')->field('m.*,f.username')
        ->join("LEFT JOIN qc_front_user f on f.id = m.user_id")
        ->where(['m.state'=>0,'m.is_send'=>0,'m.send_time'=>['elt',time()]])->select();

        if($mobileMsg)
        {
            if(M('mobileMsg')->where(['state'=>0,'is_send'=>0,'send_time'=>['elt',time()]])->save(['state'=>1,'is_send'=>1]))
            {
                foreach ($mobileMsg as $k => $v) {
                    switch ($v['send_type']) {
                        case 0:
                            //app推送
                            if(addMessageToQueue($v['user_id'], $v['content'], $v['platform'], $v['module'], $v['module_value'])) 
                                $Tnum++;

                            if($v['username'] != ''){
                                //短信发送
                                if(sendInfobipSMS('86'.$v['username'],'【全球体育】'.$v['content'])) 
                                    $Dnum++;
                            }
                            break;
                        case 1:
                            //短信发送
                            if(sendingSMS($v['username'],$v['content'])) 
                                $Dnum++;
                            break;
                        case 2:
                            //app推送
                            if(addMessageToQueue($v['user_id'], $v['content'], $v['platform'], $v['module'], $v['module_value'])) 
                                $Tnum++;
                            break;
                    }
                }
            }
        }
        
        echo '更新了 <b style="color:red;font-size:15px;">'.$num.'</b> 条推荐竞猜数据<br/>';
        echo '推送了 <b style="color:red;font-size:15px;">'.$Tnum.'</b> 条推送通知<br/>';
        echo '发送了 <b style="color:red;font-size:15px;">'.$Dnum.'</b> 条短信通知<br/>';
    }

    //编辑
    public function edit()
    {
        $id = I('id');
        $model = M('IntroGamble');
        $vo = $model->where(['id'=>$id])->find();
        if(!$vo){
            $this->error('参数错误!');
        }
        $all_odds = explode('^', $vo['all_odds']);
        $this->assign('vo',$vo);
        $this->assign('all_odds',$all_odds);
        $this->display();
    }

    public function save() {
        $id = I('id');
        $list_id = I('list_id');
        $pub_time = M('IntroLists')->where(['id'=>$list_id])->getField('pub_time');
        if(time() >= $pub_time){
            $this->error("该赛事推介已经发布，不能更改！");
        }
        $game_num = I('game_num');
        //处理推介赛事
        $game_id    = I('game_game_id');
        $odds       = I('game_odds');
        $handcp     = I('game_handcp');
        $all_odds   = I('game_all_odds');
        $play_type  = I('game_play_type');
        $chose_side = I('game_chose_side');
        $union_name = I('game_union_name');
        $gtime      = I('game_gtime');
        $home_team_name = I('game_home_team_name');
        $away_team_name = I('game_away_team_name');

        if($odds == '') $this->error("请选择赛事玩法");

        if($game_id != '')
        {
            $game = [
                'game_id'    => $game_id,
                'odds'       => $odds,
                'handcp'     => $handcp,
                'all_odds'   => $all_odds,
                'play_type'  => $play_type,
                'chose_side' => $chose_side, 
                'union_name' => $union_name, 
                'gtime'      => $gtime, 
                'home_team_name' => $home_team_name, 
                'away_team_name' => $away_team_name, 
            ];
        }
        $rs = M('IntroGamble')->where(['id'=>$id])->save($game);

        if( $rs !== false )
        {
            $this->success('修改推荐成功！');
        }
        else
        {
            $this->success('修改推荐失败！');
        }

    }
}