<?php
/**
 * 球王首页
 *
 * @author liuweitao <liuwt@qc.mail>
 *
 * @since  2017-05-02
 */
use Think\Controller;
use Think\Tool\Tool;

class IntroController extends CommonController
{


    //主页
    public function index()
    {
        $info_type = I('infotype') ? 1 : 0;
        $platform = 4;
        $page = I('page') ?: 1;
        $blockTime = getBlockTime(1, true);

        //分类
        $category = M('IntroClass')->field('id, name, desc, logo')->where(['status' => 1])->order('sort asc')->limit(4)->select();
        if ($category) {
            foreach ($category as $k3 => $v3) {
                $category[$k3]['logo'] = $v3['logo'] ? Tool::imagesReplace($v3['logo']) : '';
                unset($category[$k3]['desc']);

                $displayIds[] = $v3['id'];
            }
        }

        //今日最新推介
        $pubTimes = [];
        $lists = M('IntroLists')
            ->field('product_id, pub_time')
            ->where(['create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]], 'status' => '1'])
            ->select();

        //获取今日有最新推介并且已经发布的产品发布时间
        foreach ($lists as $k4 => $v4) {
            if ($v4['pub_time'] < NOW_TIME) {
                //$pubTimes[$v4['product_id']] = $v4['pub_time'];
                $publishedIds[$v4['product_id']] = 1;
            }
        }

        //所有产品
        $products = M('IntroProducts')->alias('P')
            ->field('P.id,P.class_id,P.name,P.`desc`,P.logo,P.sale,P.game_num,P.ten_num,P.total_rate,P.remark,C.background')
            ->join('LEFT JOIN qc_intro_class C ON c.id = P.class_id')
            ->where(['P.class_id' => ['IN', $displayIds], 'P.status' => 1])->select();

        $sort1 = $sort2 = $sort3 = [];
        foreach ($products as $k5 => $v5) {
            $published = '0';
            if ($publishedIds[$v5['id']]) {
                $published = '1';
            }
            $sort1[] = $published;
            $sort2[] = $v5['total_rate'];
            $sort3[] = $v5['ten_num'];
            $products[$k5]['published'] = $published;
        }

        //排序 已发布时间》累计回报率》近十中几
        array_multisort($sort1, SORT_DESC, $sort2, SORT_DESC, $sort3, SORT_DESC, $products);
        $_products = array_slice($products, ($page - 1) * 8, 8) ?: [];

        foreach ($_products as $k6 => $v6) {
            $_products[$k6]['logo'] = $v6['logo'] ? Tool::imagesReplace($v6['logo']) : '';
            $_products[$k6]['background'] = $v6['background'] ? Tool::imagesReplace($v6['background']) : '';
            unset($_products[$k6]['class_id']);
        }


        //首页广告位滚动的banner
        if(!$banner = S('IndexBanner_intro'.MODULE_NAME) or 1)
        {
            $banner = Tool::getAdList(28,5,4) ?: '';
            S('IndexBanner_intro'.MODULE_NAME, $banner, 1*60);
        }
        $this->assign('banner',$banner);
        //公告
        $notice = Tool::getAdList(31,5,4) ?: [];
        $ret_data = ['notice' => $notice, 'category' => $category ? $category : []];
        $ret_data['products'] = $_products;
        $this->assign('list', $ret_data);

        //判断是否为ajax查询
        if ($info_type) {
            $this->ajaxReturn($ret_data);
            exit;
        }
//        var_dump($ret_data);
        
        $this->assign('bannerCount',count($banner));
        $this->display();
    }

    //分类详情页
    public function intro_class()
    {
        $info_type = I('infotype') ? 1 : 0;
        $page = I('page') ?: 1;
        $start = ($page - 1) * 2;
        $classId = I('class_id');
        $sort1 = $sort2 = $sort3 = [];
        $blockTime = getBlockTime(1, true);
        if (!$classId)
            $this->ajaxReturn(101);

        //分类信息
        if ($page <= 1) {
            $classInfo = M('IntroClass')->field('id, name, desc, logo')->where(['id' => $classId])->order('sort asc')->find();
            if ($classInfo) {
                $classInfo['logo'] = $classInfo['logo'] ? Tool::imagesReplace($classInfo['logo']) : '';
                $ret_data['category'] = $classInfo;
            }
        }

        //今日最新推介
        $lists = M('IntroLists')
            ->field('product_id, pub_time, create_time')
            ->where(['create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]], 'status' => '1'])
            ->select();

        //获取今日有最新推介并且已经发布的时间
        foreach ($lists as $k4 => $v4) {
            if ($v4['pub_time'] < NOW_TIME) {
                $publishedIds[$v4['product_id']] = 1;
            }
        }

        //该分类下产品列表
        $products = M('IntroProducts')
            ->field('id,class_id,name, `desc`,logo,sale,game_num,ten_num,total_rate,remark')
            ->where(['status' => '1', 'class_id' => $classId])
            ->select();

        foreach ($products as $k2 => $v2) {
            $published = '0';
            if ($publishedIds[$v2['id']]) {
                $published = '1';
            }
            $sort1[] = $published;
            $sort2[] = $v2['total_rate'];
            $sort3[] = $v2['ten_num'];
            $products[$k2]['published'] = $published;
        }

        array_multisort($sort1, SORT_DESC, $sort2, SORT_DESC, $sort3, SORT_DESC, $products);
        $_products = array_slice($products, ($page - 1) * 8, 8) ?: [];

        //背景图片
        $background = M('IntroClass')->field('background')->where(['id' => $classId])->getField('background');
        foreach ($_products as $k3 => $v3) {
            $_products[$k3]['logo'] = $v3['logo'] ? Tool::imagesReplace($v3['logo']) : '';
            $_products[$k3]['background'] = $background ? Tool::imagesReplace($background) : '';
            unset($_products[$k3]['class_id']);
        }

        $ret_data['products'] = $_products;
        if ($info_type) {
            $this->ajaxReturn($ret_data);
            exit;
        }
        $this->assign('list', $ret_data);
        $this->display();
    }

    /*
     * 我的订购
     */
    public function order()
    {
        $user = session('user_auth');
        if (!$user) {
            redirect(U('User/login'));
        }
        $id = is_login();
        $page = I('page') ?: 1;
        $start = ($page - 1) * 30;

        $bug_log = M('IntroBuy')->alias('B')
            ->field('B.product_id,P.name,P.total_rate,P.ten_num,P.total_num,P.game_num,P.sale,P.logo,B.list_id,L.pub_time,L.remain_num')
            ->join('LEFT JOIN qc_intro_products P ON P.id = B.product_id')
            ->join('LEFT JOIN qc_intro_lists L ON L.id = B.list_id')
            ->where(['B.user_id' => $id])
            ->order('B.create_time DESC')
            ->limit($start, 30)
            ->select();

        foreach ($bug_log as $key => $val) {
            $res = [];
            $published = $val['pub_time'] && $val['pub_time'] < NOW_TIME && $val['list_id'] ? '1' : '0';
            $bug_log[$key]['published'] = $published;

            if ($published == '1') {
                $res = M('IntroGamble')->alias('G')
                    ->join('LEFT JOIN qc_union U ON U.union_id = G.union_id')
                    ->field('G.game_id,G.union_id,U.union_color,G.union_name,G.gtime,G.home_team_name,G.away_team_name,G.score,G.handcp,G.odds,G.chose_side,G.play_type,G.result')
                    ->where(['G.list_id' => $val['list_id']])->order('gtime ASC')->select();

                foreach ($res as $k => $v) {
                    $res[$k]['union_name'] = explode(',', $v['union_name']);
                    $res[$k]['home_team_name'] = explode(',', $v['home_team_name']);
                    $res[$k]['away_team_name'] = explode(',', $v['away_team_name']);
                    $res[$k]['score'] = (string)$v['score'];

                    unset($res[$k]['create_time']);
                    unset($res[$k]['pub_time']);
                    unset($res[$k]['union_id']);
                }
                $bug_log[$key]['buy_num'] = (string)($val['total_num'] - $val['remain_num']);
            } else {
                $blockTime = getBlockTime(1, true);
                $num = M('IntroBuy')
                    ->where(['product_id' => $val['product_id'], 'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]]])->count();

                $bug_log[$key]['buy_num'] = $num > $val['total_num'] ? $val['total_num'] : $num;
            }

            $bug_log[$key]['logo'] = $val['logo'] ? Think\Tool\Tool::imagesReplace($val['logo']) : '';

            $bug_log[$key]['gamble'] = $res ?: [];
            unset($bug_log[$key]['list_id']);
            unset($bug_log[$key]['remain_num']);
        }
        foreach ($bug_log as &$val) {
            if ($val['gamble']) {
                foreach ($val['gamble'] as &$v) {
                    if($v['play_type'] == 1) $v['handcp'] = $this->handcp($v['handcp'],$v['chose_side']);

                    if ($v['result'] == -1 || $v['result'] == -0.5) {
                        $v['photo'] = 'ic_dyseal_lose';
                    } elseif ($v['result'] == 2) {
                        $v['photo'] = 'ic_dyseal_split';
                    } elseif ($v['result'] == -11) {
                        $v['photo'] = 'ic_dyseal_pending';
                    } elseif ($v['result'] == -12) {
                        $v['photo'] = 'ic_dyseal_cut';
                    } elseif ($v['result'] == -13) {
                        $v['photo'] = 'ic_dyseal_interrupt';
                    } elseif ($v['result'] == -14) {
                        $v['photo'] = 'ic_dyseal_putoff';
                    } elseif ($v['result'] == -10) {
                        $v['photo'] = 'ic_dyseal_cancel';
                    } elseif ($v['result'] == 1 || $v['result'] == 0.5) {
                        $v['photo'] = 'ic_dyseal_win';
                    } else {
                        $v['photo'] = '';
                    }
                }
            }
        }
//        exit;
        $this->assign('list', $bug_log);
        $this->display();
    }

    /*
     * 盘口数据矫正
     */
    public function handcp($handcp,$chose)
    {
        if($chose == 1)
        {
            $res= - $handcp;
        }else{
            $res = $handcp;
        }
        return $res;
    }

    /*
     * 我的关注
     */
    public function putout()
    {
        $user = session('user_auth');
        if (!$user) {
            redirect(U('User/login'));
        }
        $id = is_login();
        $page = I('page') ?: 1;
        $start = ($page - 1) * 20;
        $blockTime = getBlockTime(1, true);

        //已关注的产品、当天有推介、比赛未完场
        $list = M('IntroFollow')->master(true)->alias('F')
            ->field('G.list_id,P.id product_id,P.name,P.total_rate,P.ten_num,P.total_num,P.game_num,P.sale,P.logo,G.create_time')
            ->join('LEFT JOIN qc_intro_gamble G ON F.product_id = G.product_id')
            ->join('LEFT JOIN qc_intro_products P ON P.id = F.product_id')
            ->where(['user_id' => $id,'G.result' => ['EQ','0']])
            ->group('G.list_id')
            ->having("G.create_time  between {$blockTime['beginTime']} and {$blockTime['endTime']}")
            ->order('F.id DESC')
            ->limit($start,20)->select();
        //比赛详情
        foreach($list as $key => $val){
            $list[$key]['logo'] =  $val['logo'] ? Think\Tool\Tool::imagesReplace($val['logo']) : '';
            //订购数量
            $intro = M('IntroLists')->where(['id' => $val['list_id']])->find();
            $list[$key]['buy_num'] = $val['total_num'] - $intro['remain_num'];
            unset($list[$key]['create_time']);
        }
        foreach ($list as &$val) {
            if ($val['buy_num'] < $val['total_num']) {
                $val['percent'] = round($val['buy_num'] / $val['total_num'] * 100, 2);
            } else {
                $val['percent'] = 100;
            }
        }
        $this->assign('list', $list);
        $this->display();
    }

    /*
     * 球王详情页
     */
    public function intro_info()
    {
        $id = I('id');
        $user_id = is_login();
        $list = $this->productDetail($id, $user_id);
        $class_id = M("IntroProducts")->where(['id' => $id])->getField('class_id');
        $class_name = M("IntroClass")->where(['id' => $class_id])->getField('name');
        $history_tab = array();
        $num = 1;
        foreach ($list['rateArrs'] as $key => $val) {
            $history_tab[$key][] = $num;
            $history_tab[$key][] = $val;
            $num++;
        }
        if ($list['baseInfo']['buy_num'] < $list['baseInfo']['total_num']) {
            $list['baseInfo']['percent'] = round($list['baseInfo']['buy_num'] / $list['baseInfo']['total_num'] * 100, 2);
        } else {
            $list['baseInfo']['percent'] = 100;
        }
        $new_status = 0;
        if($list['baseInfo']['pub_time'] > time())
        {
            $new_status = 3;
        }elseif ($list['baseInfo']['newIntro']) {
            //判断是否发布并且是否全部打完
            $new_status = 2;//假设全部打完
            foreach ($list['baseInfo']['newIntro'] as &$val2) {
                if ($val2['result'] == 0) $new_status = 1;//若有未打完这更改
                if(!$val2['score']) $val2['score'] = 'VS';
                if ($val2['play_type'] == -1) {
                    if ($val2['chose_side'] == 1) $val2['chose'] = '大球';
                    if ($val2['chose_side'] == -1) $val2['chose'] = '小球';
                } else {
                    if ($val2['chose_side'] == 1) $val2['chose'] = $val2['home_team_name'][0];
                    if ($val2['chose_side'] == -1) $val2['chose'] = $val2['away_team_name'][0];
                }
                if ($val2['result'] == 1) $val2['photo'] = 'ic_dyseal_win';
                if ($val2['result'] == 0.5) $val2['photo'] = 'ic_dyseal_win';
                if ($val2['result'] == 2) $val2['photo'] = 'ic_dyseal_split';
                if ($val2['result'] == -0.5) $val2['photo'] = 'ic_dyseal_lose';
                if ($val2['result'] == -1) $val2['photo'] = 'ic_dyseal_lose';
                if ($val2['result'] == -11) $val2['photo'] = 'ic_dyseal_pending';
                if ($val2['result'] == -12) $val2['photo'] = 'ic_dyseal_cut';
                if ($val2['result'] == -13) $val2['photo'] = 'ic_dyseal_interrupy';
                if ($val2['result'] == -14) $val2['photo'] = 'ic_dyseal_putoff';
                if ($val2['result'] == -10) $val2['photo'] = 'ic_dyseal_cancel';
            }
        }
        //购买选项按钮
        $buy_status = '';
        if ($list['is_order'] == 1) {
            if($new_status != 0 && $new_status != 3)
            {
                $new_status = 2;//已订购并且有推介
            }
            $buy_status = 2;//已订购
        }
        if ($list['baseInfo']['percent'] == 100) $buy_status = 1;//已抢光
        $list['historyIntro'] = $this->history($list['historyIntro']);
        $basetotal = M("IntroLists")->where(['product_id' => $id])->count();
        if ($list['baseInfo']['newIntro']) $basetotal = $basetotal - 1;
        $_count = count($list['rateArrs']);
        if($_count <= 5)
        {
            $_lenght = 0;
        }else{
            $_lenght = round(($_count - 5) / $_count * 100, 2);
        }
        $this->assign('basetotal', $basetotal);
        $this->assign('buy_status', $buy_status);
        $this->assign('new_status', $new_status);
        $this->assign('history_tab', json_encode($history_tab));
        $this->assign('list', $list);
        $this->assign('class_name', $class_name);
        $this->assign('lenght', $_lenght);
        $this->display();
    }

    /*
     * ajax获取推介详情数量
     */
    public function surplus_num()
    {
        $user_id = is_login();
        $id = I('id');
        $rs = M('IntroBuy')->where(['user_id'=>$user_id,'product_id'=>$id])->getField();
        if(!$rs) $this->AjaxReturn(['status'=>-1]);
        $list = $this->get_curl("/Api320/intro/productDetail", "nosign=app_qqty_ppa&productId=$id&platform=1", C('CURL_DOMAIN'))['data']['baseInfo'];
        if ($list['buy_num'] < $list['total_num']) {
            $res['percent'] = round($list['buy_num'] / $list['total_num'] * 100, 2);
        } else {
            $res['percent'] = 100;
        }
        $res['buy_num'] = $list['buy_num'];
        $res['total_num'] = $list['total_num'];
        $res['status'] = 1;
        if(empty($list['newIntro']))
        {
            $res['game_code'] = 1;
        }else{
            $res['game_code'] = 10;
            foreach ($list['newIntro'] as &$val2) {
                if(!$val2['score']) $val2['score'] = 'VS';
                $val2['time_day'] = date("m/d",$val2['gtime']);
                $val2['time_hour'] = date("H:i",$val2['gtime']);
                if ($val2['play_type'] == -1) {
                    if ($val2['chose_side'] == 1) $val2['chose'] = '大球';
                    if ($val2['chose_side'] == -1) $val2['chose'] = '小球';
                } else {
                    if ($val2['chose_side'] == 1) $val2['chose'] = $val2['home_team_name'][0];
                    if ($val2['chose_side'] == -1) $val2['chose'] = $val2['away_team_name'][0];
                }
                if ($val2['result'] == 1) $val2['photo'] = 'ic_dyseal_win';
                if ($val2['result'] == 0.5) $val2['photo'] = 'ic_dyseal_win';
                if ($val2['result'] == 2) $val2['photo'] = 'ic_dyseal_split';
                if ($val2['result'] == -0.5) $val2['photo'] = 'ic_dyseal_lose';
                if ($val2['result'] == -1) $val2['photo'] = 'ic_dyseal_lose';
                if ($val2['result'] == -11) $val2['photo'] = 'ic_dyseal_pending';
                if ($val2['result'] == -12) $val2['photo'] = 'ic_dyseal_cut';
                if ($val2['result'] == -13) $val2['photo'] = 'ic_dyseal_interrupy';
                if ($val2['result'] == -14) $val2['photo'] = 'ic_dyseal_putoff';
                if ($val2['result'] == -10) $val2['photo'] = 'ic_dyseal_cancel';
            }
            $res['info']['new'] = $list['newIntro'];
            $res['info']['pub_time'] = date("Y-m-d  H:i",$list['pub_time']);
            $res['info']['sale'] = $list['sale'].'金币/'.$list['game_num'].'场';
        }
        $this->AjaxReturn($res);
    }

    /*
     * 对数据进行处理
     */
    public function history($arr)
    {
        foreach ($arr as &$val) {
            $val['pub_time_format'] = date('Y-m-d H:i', $val['pub_time']);
            foreach ($val['gamble'] as &$v) {
                if($v['play_type'] == 1) $v['handcp'] = $this->handcp($v['handcp'],$v['chose_side']);
                $v['gtime_day'] = date('m/d', $v['gtime']);
                $v['gtime_hour'] = date('H:i', $v['gtime']);
                if ($v['play_type'] == -1) {
                    if ($v['chose_side'] == 1) $v['chose'] = '大球';
                    if ($v['chose_side'] == -1) $v['chose'] = '小球';
                } else {
                    if ($v['chose_side'] == 1) $v['chose'] = $v['home_team_name'][0];
                    if ($v['chose_side'] == -1) $v['chose'] = $v['away_team_name'][0];
                }
                if ($v['result'] == 1) $v['photo'] = 'ic_dyseal_win';
                if ($v['result'] == 0.5) $v['photo'] = 'ic_dyseal_win';
                if ($v['result'] == 2) $v['photo'] = 'ic_dyseal_split';
                if ($v['result'] == -0.5) $v['photo'] = 'ic_dyseal_lose';
                if ($v['result'] == -1) $v['photo'] = 'ic_dyseal_lose';
                if ($v['result'] == -11) $v['photo'] = 'ic_dyseal_pending';
                if ($v['result'] == -12) $v['photo'] = 'ic_dyseal_cut';
                if ($v['result'] == -13) $v['photo'] = 'ic_dyseal_interrupy';
                if ($v['result'] == -14) $v['photo'] = 'ic_dyseal_putoff';
                if ($v['result'] == -10) $v['photo'] = 'ic_dyseal_cancel';
            }
        }
        return $arr;
    }

    /*
     * ajax加载数据
     */
    public function ajax_info()
    {
        $pro_id = I('product_id');
        $page = I('page');
        $res = $this->productDetail($pro_id, '', $page);
        $list = $this->history($res['historyIntro']);
        $this->ajaxReturn($list);
    }


    /*
     * 判断是否购买跟关注
     */
    public function productDetail($productId, $user_id, $page)
    {
        $page = $page ? $page : 1;
        $published = $end_state = '0';
        $blockTime = getBlockTime(1, true);
        $user_id = $user_id ? $user_id : '';

        if (!$productId)
            $this->ajaxReturn(101);

        $products = M('introProducts')->field('id,name,desc,logo,logo,sale,total_num,pay_num,game_num,ten_num,total_rate,create_time')->where(['id' => $productId])->find();
//        if($page <= 1 ){
        if (!$products)
            $this->ajaxReturn(8011);

        //该产品是否发布推介、购买情况、当前用户是否购买或者订购
        $intro = M('IntroLists')->where(['status' => 1, 'product_id' => $productId, 'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]]])->find();

        if ($intro) {
            //是否发布
            if ($intro['pub_time'] < NOW_TIME) {
                $published = '1';
            }
            $pub_time = $intro['pub_time'];

            //今天的比赛推介
            $gamble = M('IntroGamble')->alias('G')
                ->field('G.union_name,U.union_color,G.game_id,G.home_team_name,G.away_team_name,G.play_type,G.chose_side,G.odds,G.score,G.handcp,G.gtime,FB.game_state,G.result')
                ->join('LEFT JOIN qc_union U ON U.union_id = G.union_id')
                ->join('LEFT JOIN qc_game_fbinfo FB ON FB.game_id = G.game_id')
                ->where(['list_id' => $intro['id']])
                ->order('G.gtime ASC')
                ->select();

            $gtime = $gamble[0]['gtime'];

            //是否【订购】
            $is_order = M('IntroBuy')
                ->where(['product_id' => $productId, 'user_id' => $user_id, 'list_id' => $intro['id']])->find() ? '1' : '0';
            $buy_num = $products['total_num'] - ($intro['remain_num'] <= 0 ? 0 : $intro['remain_num']);

            //完场情况
            foreach ($gamble as $k => $v) {
                $gamble[$k]['score'] = $v['score'] ? $v['score'] : '';
                if (in_array($v['game_state'], ['0', '1', '2', '3', '4'])) {
                    $end_state = '0';
                    break;
                } else {
                    $end_state = '1';
                }
            }

        } else {
            //后台没推介：统计**当天**产品订购数量
            $num = M('IntroBuy')
                ->where(['product_id' => $productId, 'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]]])->count();

            //实际预购数量+真实预购数量
            $order_num = $products['pay_num'] + $num;
            $buy_num = $order_num > $products['total_num'] ? $products['total_num'] : $order_num;

            //是否【订购】
            $buy_log = M('IntroBuy')->where(['product_id' => $productId, 'user_id' => $user_id])->order('id DESC')->find();

            if ($buy_log && !$buy_log['list_id']) {
                $is_order = '1';
            }
        }

        foreach ($gamble as $key1 => $value1) {
            $gamble[$key1]['union_name'] = explode(',', $value1['union_name']);
            $gamble[$key1]['home_team_name'] = explode(',', $value1['home_team_name']);
            $gamble[$key1]['away_team_name'] = explode(',', $value1['away_team_name']);
        }

        $products['buy_num'] = $buy_num ? (string)$buy_num : '0';
        $products['gtime'] = $gtime ? (string)$gtime : '';
        $products['end_state'] = $end_state;
        $products['pub_time'] = $pub_time ? (string)$pub_time : '';
        $products['published'] = $published ?: '0';
        $products['newIntro'] = $gamble ? $gamble : [];

        unset($products['create_time']);
        $products['logo'] = $products['logo'] ? Tool::imagesReplace($products['logo']) : '';

        //是否关注
        $subscribe = M('IntroFollow')->where(['product_id' => $productId, 'user_id' => $user_id])->find();

        //获取产品回报率
        $intros = M('IntroLists')->field('return_rate,total_rate,create_time')->where(['status' => 1, 'return_rate' => ['exp', 'is not null'], 'product_id' => $productId, 'create_time' => ['LT', $blockTime['beginTime']]])->order('create_time ASC')->select();
        $earlyIntroTime = $intros[0]['create_time'];

        //个推介时间距离最早推介时间的天数以及对应的回报率
        foreach ($intros as $kIntro => $vIntro) {
            $rateArrs[] = $vIntro['total_rate'] ?: '0';
            $nextArr = $intros[$kIntro + 1];
            if ($nextArr) {
                $curToMinDays = round(($vIntro['create_time'] - $earlyIntroTime) / 3600 / 24);
                $nextToMinDays = round(($nextArr['create_time'] - $earlyIntroTime) / 3600 / 24);
                $diffDays = $nextToMinDays - $curToMinDays;
                //如果某个天数没有推介，则以最近前一天推介回报率补齐
                if ($diffDays > 1) {
                    for ($i = 1; $i < $diffDays; $i++) {
                        $rateArrs[] = $vIntro['total_rate'] ?: '0';
                    }
                }
            }
        }

        $ret_data['subscribe'] = $subscribe ? '1' : '0';
        $ret_data['is_order'] = $is_order ? '1' : '0';

        $ret_data['baseInfo'] = $products ?: [];
        $ret_data['rateArrs'] = $rateArrs ?: [];

        //历史推介,按照天数分页，每天可能有3场或者1场比赛
        $start = ($page - 1) * 3 * $products['game_num'];
        $limit = 3 * $products['game_num'];
        $cacheKey = MODULE_NAME . '_historyIntro_list:' . $productId . $blockTime['endTime'];

//            if(!$historyIntro = S($cacheKey))
//            {
        $historyIntro = M('IntroLists')->alias('L')
            ->field('L.id,G.union_name,U.union_color,G.home_team_name,G.score,G.away_team_name,G.play_type,G.chose_side,G.odds,G.handcp,G.result,G.gtime,L.pub_time')
            ->join('LEFT JOIN qc_intro_gamble G ON L.id = G.list_id')
            ->join('LEFT JOIN qc_union U ON U.union_id = G.union_id')
            ->where(['L.status' => 1, 'L.product_id' => $productId, 'L.create_time' => ['LT', $blockTime['beginTime']]])
            ->order('L.create_time DESC, G.gtime ASC ')
            ->limit($start, $limit)
            ->select();

//                S($cacheKey, $historyIntro, 24*3600);
//            }
//        }


        //变换格式
        $_historyIntro = $historyIntroArrs = [];
        foreach ($historyIntro as $key => $value) {
            $value['union_name'] = explode(',', $value['union_name']);
            $value['home_team_name'] = explode(',', $value['home_team_name']);
            $value['away_team_name'] = explode(',', $value['away_team_name']);

            $_historyIntro[$value['id']]['pub_time'] = $value['pub_time'];
            $_historyIntro[$value['id']]['gamble'][] = $value;
        }

        foreach ($_historyIntro as $key2 => $value2) {
            $historyIntroArrs[] = $value2;
        }

        $ret_data['historyIntro'] = $historyIntroArrs;
        return $ret_data;
    }

    /**
     * 关注功能
     */
    public function subscribe()
    {
        $userId = is_login();
        $product_id = I('productId');
        $actionType = I('actionType');
        if (!$userId || !$product_id || !$actionType)
            $this->ajaxReturn(101);

        if (!isset($userId))
            $this->ajaxReturn(1001);

        $has = M('IntroFollow')->where(['product_id' => $product_id, 'user_id' => $userId])->find();

        //关注
        if ($actionType == 1) {
            if ($has) $this->ajaxReturn(8001);

            $res = M('IntroFollow')->add(['product_id' => $product_id, 'user_id' => $userId, 'create_time' => NOW_TIME]);

            if (!$res) $this->ajaxReturn(8002);
        } elseif ($actionType == 2) {
            if (!$has) $this->ajaxReturn(8003);

            $res = M('IntroFollow')->where(['product_id' => $product_id, 'user_id' => $userId])->delete();
            if (!$res)
                $this->ajaxReturn(8004);
        }

        $this->ajaxReturn(['result' => '1']);
    }

}