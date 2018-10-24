<?php

/**
 * 产品推荐列表
 *
 * @author liuweitao <cytusc@foxmail.com>
 *
 * @since
 */
use Think\Controller;

class IntroListsController extends CommonController
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
        $nickname = trim(I('nickname'));
        if (!empty($nickname)) {
            $map['u.nickname'] = ['Like', '%'.$nickname . '%'];
        }

        //不是admin只有属于自己的发布产品
        // $admin_id = $_SESSION['authId'];
        // if (!in_array($admin_id, C('RBAC_LOGIN_USER')))
        // {
        //     $map['il.admin_id'] = $admin_id;
        // }

        if(!empty($name) || !empty($nickname)){
            $count = M('IntroLists il')
            ->join('LEFT JOIN qc_intro_products ip on ip.id = il.product_id')
            ->join('LEFT JOIN qc_user u on u.id = il.admin_id')->where($map)->count('il.id');
        }else{
            $count = M('IntroLists il')->where($map)->count('il.id');
        }

        //获取每页显示的条数
        $pageNum = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
        //获取当前的页码
        $currentPage = !empty($_REQUEST[C('VAR_PAGE')]) ? $_REQUEST[C('VAR_PAGE')] : 1;

        if ($count > 0) {
            $list = M('IntroLists il')
                ->Field('il.id,il.remain_num,il.status,il.return_rate,il.pub_time,il.create_time,il.is_win,ip.name,ip.sale,ip.game_num,ip.total_num,count(ib.id) as buy_num,u.nickname')
                ->join('LEFT JOIN qc_intro_products ip on ip.id = il.product_id')
                ->join('LEFT JOIN qc_intro_buy ib on ib.list_id = il.id')
                ->join('LEFT JOIN qc_user u on u.id = il.admin_id')
                ->where($map)
                ->order($order." ".$sort)
                ->group('il.id')
                ->limit($pageNum * ($currentPage - 1), $pageNum)
                ->select();
        }
        $list_id = array_map("array_shift", $list);
        $gamble = M('IntroGamble ig')
            ->Field('ig.id,ig.list_id,ig.union_name,ig.game_id,ig.gtime,ig.home_team_name,gf.score,gf.half_score,ig.away_team_name,ig.play_type,ig.chose_side,ig.result,ig.create_time,ig.handcp,ig.odds,ig.is_change,gf.game_state')
            ->join('LEFT JOIN qc_game_fbinfo gf on gf.game_id = ig.game_id')
            ->where(['ig.list_id'=>['in',$list_id]])
            ->select();
        foreach ($gamble as $k => $v)
        {
            if($v['game_state'] == -1)
            {
                $result = getTheWin($v['score'],$v['play_type'],$v['handcp'],$v['chose_side']);
            }
            else
            {
                $result = $v['game_state'];
            }
            $gamble[$k]['show_result'] = $result;
        }
        foreach ($list as $k => $v) {
            foreach ($gamble as $kk => $vv) {
                if($v['id'] == $vv['list_id']){
                    $list[$k]['gamble'][] = $vv;
                }
            }
        }
        $this->assign('list', $list);
        $this->assign('totalCount', $count);//当前条件下数据的总条数
        $this->assign('numPerPage', $pageNum); //每页显示多少条
        $this->assign('currentPage', $currentPage);//当前页码
        $this->setJumpUrl();
        $this->display();
    }

    //编辑
    public function edit()
    {
        $id = I('id');
        $model = M('IntroLists');
        $vo = $model->where(['id'=>$id])->find();
        if(!$vo){
            $this->error('参数错误!');
        }
        $this->assign('vo',$vo);
        $this->display();
    }

    public function update()
    {
        $dwz_db_name=$dwz_db_name ? $dwz_db_name : $this->getActionName();
        $model = CM($dwz_db_name);
        if (false === $model->create()) {
            $this->error($model->getError());
        }
        if($model->pub_time != ''){
            $model->pub_time = strtotime($model->pub_time);
        }
        // 更新数据
        $list = $model->save();
        if (false !== $list) {
            //成功提示
            $this->success('编辑成功!',cookie('_currentUrl_'));
        } else {
            //错误提示
            $this->error('编辑失败!');
        }
    }

    //判断是否需要退钱
    public function checkReturn($game)
    {   
        //推荐数据数量
        $game_num = count($game);
        //初始化 回报率 赢  平  输  特 数量
        $return_rate = $win = $level = $lose = $ab = 0;
        foreach ($game as $kk => $vv) 
        {
            switch ($vv['result']) {
                case '1':
                    $win++;
                    $return_rate += ($vv['odds'] * 100); //该场比赛赢的回报率：+100%X赔率
                    break;
                case '0.5':
                    $win++;
                    $return_rate += ($vv['odds'] * 50); //该场比赛赢半的回报率：+50%X赔率
                    break;
                case '2':
                    $level++;
                    break;
                case '-1':
                    $lose++ ;
                    $return_rate -= 100;  //该场比赛输的回报率:-100%
                    break;
                case '-0.5':
                    $lose++ ;
                    $return_rate -= 50; //该场比赛输半的回报率:-50%
                    break;
                default:
                    $ab++;
                    break;
            };
        }

        switch ($game_num) 
        {
            //一场时 除了 赢  其他情况全退
            case '1':
                if($win == 1){
                    $is_back = 0;
                }else{
                    $is_back = 1;
                }
                break;
            //三场时 2赢  1赢2平  1赢1平1特  1赢2特  其他情况退
            case '3':
                if($win >= 2 || ($win == 1 && $level == 2) || ($win == 1 && $level == 1 && $ab == 1) || ($win == 1 && $ab == 2)){
                    $is_back = 0;
                }else{
                    $is_back = 1;
                }
                break;
        }
        return ['return_rate'=>$return_rate,'is_back'=>$is_back];
    }

    //结算推介（每天下午1点）
    public function runIntroLists()
    {
        $ticDate = S('admin_runIntroLists');
        //一天只发一次
        if(date('Ymd') != $ticDate)
        {
            $startTime = strtotime(date('Ymd',strtotime('+3 day')));
            $endTime   = strtotime(date('Ymd',strtotime('+4 day')));
            //优惠券到期提醒
            $ticket_user = M('ticketLog')->where("is_use = 0 and over_time >= {$startTime} and over_time <= {$endTime}")->group('user_id')->getField('user_id',true);
            if(!empty($ticket_user)){
                sendMsg($ticket_user,'优惠券到期提醒','您有优惠卷或体验卷将在3天后过期，请尽快使用！');
            }
            
            //预测模型会员到期提醒
            $model_user = M('FrontUser')->where("predictive_model_vip > 0 and predictive_model_vip >= {$startTime} and predictive_model_vip <= {$endTime}")->group('id')->getField('id',true);
            if(!empty($model_user)){
                sendMsg($model_user,'大数据服务到期提醒','您好，您购买的大数据预测服务将在三天后到期，如需继续使用，请前往续费。');
            }

            S('admin_runIntroLists',date('Ymd'));
        }

        $list = M('IntroLists l')
                ->field("l.id,l.product_id,p.game_num,p.name")
                ->master(true)
                ->join('LEFT JOIN qc_intro_products p on p.id = l.product_id')
                ->where("l.is_win = 0")
                ->order('l.id asc')
                ->group('l.id')
                ->select();

        $num = 0;
        foreach ($list as $k => $v) 
        {
            $game = M('IntroGamble')->master(true)->field('result,odds')->where(['list_id'=>$v['id'],'result'=>['neq',0]])->select();
            if(count($game) == $v['game_num'])
            {
                //检查数据
                $return = $this->checkReturn($game);
                //获取本次推介回报率与是否需要退钱
                $return_rate = $return['return_rate'];
                $saveIntroLists['is_win'] = 1;
                if($return['is_back'] == 1)
                {
                    //退钱
                    $introBuy = M('introBuy')->master(true)->field('user_id,price,list_id,platform')->where(['list_id'=>$v['id']])->select();
                    foreach ($introBuy as $ii => $i) 
                    {
                        //这里读取主数据库
                        $userInfo  = M('FrontUser')->master(true)->field(['coin','unable_coin'])->where(['id'=>$i['user_id']])->find();
                        //添加退款
                        if(M('FrontUser')->where(['id'=>$i['user_id']])->setInc('unable_coin',$i['price']))
                        {
                            //添加退款记录
                            M('accountLog')->add([
                                'user_id'    => $i['user_id'],
                                'log_type'   => 18,
                                'log_status' => 1,
                                'log_time'   => time(),
                                'change_num' => $i['price'],
                                'total_coin' => $userInfo['coin']+$userInfo['unable_coin']+$i['price'],
                                'desc'       => '您订购的【'.$v['name'].'】未完成服务，退还查看的金币。',
                                'platform'   => $i['platform'],
                                'list_id'    => $i['list_id'],
                                'operation_time' => time()
                            ]);

                            //发送消息通知
                            $content = '您好，您订购的【'.$v['name'].'】未完成服务，退还查看的'.$i['price'].'金币。';
                            sendMsg($i['user_id'],C('accountType')[18].'通知',$content);
                        }
                    }
                    $saveIntroLists['is_win'] = 2;
                }
                //查询历史推介回报率
                $rate = M('IntroLists')->master(true)->where("product_id = {$v['product_id']}")->sum('return_rate');

                //累计回报率
                $total_rate = $return_rate + $rate; 
                $saveIntroLists['return_rate'] = $return_rate;
                $saveIntroLists['total_rate']  = $total_rate;
                if(M('IntroLists')->where(['id'=>$v['id']])->save($saveIntroLists))
                {
                    $ProductArr = [];
                    $ProductArr['total_rate'] = $total_rate;

                    //找出该分类下最新10条推荐竞猜
                    //$gamble = M('IntroGamble')->where(['product_id'=>$v['product_id'],'result'=>['in',[1,0.5,2,-1,-0.5]]])->order("id desc")->limit(10)->getField('result',true);

                    //找出最新10条产品推荐完成服务条数
                    $listWin = M('IntroLists')->master(true)->where("product_id = {$v['product_id']} and is_win <> 0")->order('id desc')->limit(10)->getField('is_win',true);

                    $rateArr = array_slice($listWin, 0,C('introRateNum'));

                    $ProductArr['ten_num']  = array_count_values($listWin)[1] ? : 0;
                    $ProductArr['rate_num'] = array_count_values($rateArr)[1] ? : 0;
                        
                    $rs = M('IntroProducts')->where(['id'=>$v['product_id']])->save($ProductArr);
                    $num++;
                }
            }
            else
            {
                unset($list[$k]);
            } 
        }

        echo "结算成功{$num}条推介！<br/>";
        echo "发送了".count($ticket_user)."条优惠券到期提醒！".count($model_user)."条大数据服务到期提醒！";
    }
}