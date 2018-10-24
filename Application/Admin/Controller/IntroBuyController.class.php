<?php

/**
 * 产品购买
 *
 * @author
 *
 * @since
 */
use Think\Controller;

class IntroBuyController extends CommonController
{

    public function index()
    {
        //生成查询条件
        $map = $this->_search('IntroBuy');
        $nick_name = I('nick_name');
        if($nick_name != ''){
            $map['nick_name'] = ['eq',$nick_name];
        }
        $username = I('username');
        if($username != ''){
            $map['username'] = ['eq',$username];
        }

        //是否销售权限列表
        $xs = I('xs');
        if($xs == 1){
            if(empty($nick_name) && empty($username)){
                $this->display();
                die;
            }
        }
        
        //时间查询
        if (preg_match('/[a-zA-Z]/',$_REQUEST ['startTime'])){
            $_REQUEST ['startTime'] = '';
        }
        if (preg_match('/[a-zA-Z]/',$_REQUEST ['endTime'])){
            $_REQUEST ['endTime'] = '';
        }

        if (!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])) {
            if (!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])) {
                if (strtotime($_REQUEST ['startTime']) < strtotime($_REQUEST ['endTime'])) {
                    $startTime = strtotime($_REQUEST ['startTime']);
                    $endTime = strtotime($_REQUEST ['endTime']);
                } else {
                    $endTime = strtotime($_REQUEST ['startTime']);
                    $startTime = strtotime($_REQUEST ['endTime']);
                }
                $endTime = $endTime + 60 * 60 * 24;
                $map['i.create_time'] = array('BETWEEN', array($startTime, $endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $startTime = strtotime($_REQUEST ['startTime']);
                $map['i.create_time'] = array('EGT', $startTime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime']);
                $endTime = $endTime + 60 * 60 * 24;
                $map['i.create_time'] = array('ELT', $endTime);
            }
        }
        $time = $_REQUEST['create_time'];
        if ($time) {
            $map['create_time'] = array('BETWEEN', array($time, $time + 24 * 60 * 60));
        }
        //不是admin只有属于自己的发布产品
        // $admin_id = $_SESSION['authId'];
        // if (!in_array($admin_id, C('RBAC_LOGIN_USER')))
        // {
        //     $map['l.admin_id'] = $admin_id;
        // }

        $list = $this->_list(D('IntroBuy'), $map, 'create_time');
        $list_id = [];
        foreach ($list as $k => $v) {
            if(!in_array($v['list_id'], $list_id)){
                $list_id[] = $v['list_id'];
            }
        }
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
                if($v['list_id'] == $vv['list_id']){
                    $list[$k]['gamble'][] = $vv;
                }
            }
        }
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 产品销售统计
     * @user liuweitao <liuwt@qc.com>
     */
    public function statistics()
    {
        if (!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])) {
            if (!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])) {
                if (strtotime($_REQUEST ['startTime']) < strtotime($_REQUEST ['endTime'])) {
                    $startTime = strtotime($_REQUEST ['startTime']);
                    $endTime = strtotime($_REQUEST ['endTime']);
                } else {
                    $endTime = strtotime($_REQUEST ['startTime']);
                    $startTime = strtotime($_REQUEST ['endTime']);
                }
                $endTime = $endTime + 60 * 60 * 24;
                $map['i.create_time'] = array('BETWEEN', array($startTime, $endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $startTime = strtotime($_REQUEST ['startTime']);
                $map['i.create_time'] = array('EGT', $startTime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime']);
                $endTime = $endTime + 60 * 60 * 24;
                $map['i.create_time'] = array('ELT', $endTime);
            }
        }
        //总数
        $totle = M('IntroBuy i')->field('id')->where($map)->group("UNIX_TIMESTAMP(FROM_UNIXTIME(i.create_time,'%Y-%m-%d'))")->select();
        $count = count($totle);
        if ($count > 0) {
            $pageNum = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
            $fieldName = 'group_concat(i.list_id) as lists,i.create_time,count(i.id) AS totleNum,sum(i.price) as priceSum,sum(ip.game_num) as gameNumSum,i.list_id';
            //排序
            $_order = I('_order');
            $_sort = I('_sort');
            $order = !empty($_order) && !empty($_sort) ? $_order . ' ' . $_sort : 'create_time desc';
            $list = M('IntroBuy i')
                ->field($fieldName)
                ->join('left join qc_intro_products ip on ip.id = i.product_id')
                ->where($map)
                ->limit($pageNum)
                ->group("UNIX_TIMESTAMP(FROM_UNIXTIME(i.create_time,'%Y-%m-%d'))")
                ->order($order)
                ->page(!empty($_REQUEST[C('VAR_PAGE')]) ? $_REQUEST[C('VAR_PAGE')] : 1)
                ->select();
            $list = $this->data_proces($list);//对搜索结果进行赛事结果获取
            //dump($list);
        }
        $IntroBuyCount = M('IntroBuy b')->join("LEFT JOIN qc_intro_lists l on l.id = b.list_id")->field("sum(b.price) as IntroBuyCount")->where("l.is_win <> 0")->group('l.is_win')->select();

        $this->assign('IntroBuyCount', $IntroBuyCount);
        $this->assign('numPerPage', $pageNum);
        $this->assign('totalCount', $count);
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 对查询结果进行赛事结果处理
     * @user liuweitao <liuwt@qc.com>
     * @param $arr 查询结果
     */
    public function data_proces($arr)
    {
        $list_arr = array();
        //对list_id进行处理
        foreach ($arr as &$val) {
            $list_arr[] = $val['lists'];
        }
        //对list_id合并去重生成查询条件
        $list_str = implode(',', $list_arr);
        $list_str = array_unique(explode(",", $list_str));
        $map['list_id'] = array('in', $list_str);
        $buy_res = M('IntroBuy b')->field('b.user_id,b.price,b.list_id,b.create_time,l.is_win')->join("LEFT JOIN qc_intro_lists l on l.id = b.list_id")->where($map)->select();
        //dump($buy_res);
        //获取查询结果中所有的赛事结果
        $gamble_res = M('IntroGamble')->where($map)->getField('id,list_id,result', true);
        //对每天的数据进行赛事结果的赋值
        foreach ($arr as &$val) {
            $lists = explode(',', $val['lists']);
            $result = $this->result_proces($lists, $val['create_time'], $gamble_res);
            $val['win'] = $result['win'];
            $val['draw'] = $result['draw'];
            $val['trans'] = $result['trans'];
            $val['day_time'] = $result['day_time'];
            
            $win_coin = $return_coin = 0;
            $day_time = date('Ymd',$val['create_time']);
            foreach ($buy_res as $v) {
                if(in_array($v['list_id'], $lists) && $day_time == date('Ymd',$v['create_time'])){
                    if($v['is_win'] == 1){
                        $win_coin += $v['price'];
                    }
                    if($v['is_win'] == 2){
                        $return_coin += $v['price'];
                    }
                }
            }
            $val['win_coin'] = $win_coin;
            $val['return_coin'] = $return_coin;
        }
        return $arr;
    }

    /**
     * 对赛事结果统计处理
     * @user liuweitao <liuwt@qc.com>
     * @param $list 当天所有推介ID
     * @param $time 数据日期
     * @param $gamble 赛事结果
     */
    public function result_proces($list, $time, $gamble)
    {
        $old_time = strtotime(date("Y-m-d", $time));
        $now_time = strtotime(date("Y-m-d", time()));
        //判断是否为当天数据,如果不是则读取缓存
        // if ($old_time < $now_time) {
        //     $res = S($old_time);
        //     if ($res) return $res;
        // }
        $result['day_time'] = $old_time;
        $result['win'] = 0;
        $result['draw'] = 0;
        $result['trans'] = 0;
        //对一天内所有赛事结果进行统计
        foreach ($list as $val) {
            foreach ($gamble as $v) {
                if ($val == $v['list_id'] && ($v['result'] == 1 || $v['result'] == 0.5)) {
                    $result['win']++;
                }
                if ($val == $v['list_id'] && $v['result'] == 2) {
                    $result['draw']++;
                }
                if ($val == $v['list_id'] && ($v['result'] == -1 || $v['result'] == -0.5)) {
                    $result['trans']++;
                }
            }

        }
        //S($old_time, $result, 10);//对非当天数据进行缓存处理,时间按需要进行调整
        return $result;
    }

    /**
     * 产品人员销售统计
     * @user dengwj
     */
    public function BuyCount()
    {
        if (!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])) {
            if (!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])) {
                if (strtotime($_REQUEST ['startTime']) < strtotime($_REQUEST ['endTime'])) {
                    $startTime = strtotime($_REQUEST ['startTime']);
                    $endTime = strtotime($_REQUEST ['endTime']);
                } else {
                    $endTime = strtotime($_REQUEST ['startTime']);
                    $startTime = strtotime($_REQUEST ['endTime']);
                }
                $endTime = $endTime + 60 * 60 * 24;
                $map['b.create_time'] = array('BETWEEN', array($startTime, $endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $startTime = strtotime($_REQUEST ['startTime']);
                $map['b.create_time'] = array('EGT', $startTime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime']);
                $endTime = $endTime + 60 * 60 * 24;
                $map['b.create_time'] = array('ELT', $endTime);
            }
        }
        else
        {
            $map['b.create_time'] = array('EGT',strtotime(date('Y-m-d',time())));
            $_REQUEST['startTime'] = date('Y-m-d',time());
        }

        $pageNum = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
        //排序
        $_order = I('_order');
        $_sort = I('_sort');
        $order = !empty($_order) && !empty($_sort) ? $_order . ' ' . $_sort : 'buy_num desc';
        
        //不是admin只有属于自己的发布产品
        // $admin_id = $_SESSION['authId'];
        // if (!in_array($admin_id, C('RBAC_LOGIN_USER')))
        // {
        //     $map['l.admin_id'] = $admin_id;
        // }
        $list = M('user u')
                ->field('u.id,l.admin_id,u.nickname,l.product_id,count(b.id) as buy_num,sum(b.price) as priceSum')
                ->join("LEFT JOIN qc_intro_lists l on l.admin_id = u.id")
                ->join("LEFT JOIN qc_intro_buy b on b.list_id = l.id")
                ->where($map)
                ->where(['l.is_win'=>['neq',0]])
                ->order($order)
                ->group("u.id")
                ->limit($pageNum)
                ->page(!empty($_REQUEST[C('VAR_PAGE')]) ? $_REQUEST[C('VAR_PAGE')] : 1)
                ->select();
        //dump($list);
        foreach ($list as $k => $v) {
            $adminArr[] = $v['id'];
        }
        $IntroBuy = M('IntroBuy b')
            ->join("LEFT JOIN qc_intro_lists l on l.id = b.list_id")
            ->field('b.price,b.product_id,l.admin_id,l.is_win')->where($map)->where(['l.admin_id'=>['in',$adminArr],'l.is_win'=>['neq',0]])->select();
        //dump($IntroBuy);
        foreach ($list as $k => $v) {
            $win_num = $lose_num = $win_coin = $return_coin = 0 ;
            foreach ($IntroBuy as $kk => $vv) {
                if($v['id'] == $vv['admin_id']){
                    if($vv['is_win'] == 1){
                        $win_num++;
                        $win_coin += $vv['price'];
                    }
                    if($vv['is_win'] == 2){
                        $lose_num++;
                        $return_coin += $vv['price'];
                    }
                }
            }
            $list[$k]['win_num']  = $win_num;
            $list[$k]['lose_num'] = $lose_num;
            $list[$k]['winrate']  = round($win_num / ($win_num+$lose_num) * 100);
            $list[$k]['win_coin'] = $win_coin;
            $list[$k]['return_coin'] = $return_coin;
            $totleNum += $v['priceSum'];
            $winNum += $win_coin;
            $returnNum += $return_coin;
        }
        $this->assign('totleNum', $totleNum);
        $this->assign('winNum', $winNum);
        $this->assign('returnNum', $returnNum);
        $this->assign('numPerPage', $pageNum);
        $this->assign('totalCount', count($list));
        $this->assign('list', $list);
        $this->display();
    }
}

?>