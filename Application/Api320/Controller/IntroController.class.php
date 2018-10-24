<?php
/**
 *推介理财控制器
 */
use Think\Tool\Tool;
class IntroController extends PublicController
{
    /**
     * 球王首页
     */
    public function index()
    {
        $platform   = $this->param['platform'];
        $page       = $this->param['page'] ?: 1;
        $blockTime  = getBlockTime(1, true);

        //分类
        $category = M('IntroClass')->field('id, name, desc, logo')->where(['status' => 1])->order('sort asc')->limit(4)->select();
        if ($category) {
            foreach ($category as $k3 => $v3) {
                $category[$k3]['logo'] = $v3['logo'] ? Tool::imagesReplace($v3['logo']) : '';
                unset($category[$k3]['desc']);

                $displayIds[] = $v3['id'];
            }
        }

        if ($page <= 1) {
            //轮播图
            $banner = Tool::getAdList(28, 20, $platform) ?: [];
            foreach ($banner as $k => $v) {
                unset($banner[$k]['id']);
            }
            //公告
            $where = [
                'class_id'    => 31,
                'status'      => 1,
                'online_time' => array("elt",time()),
                'end_time'    => array("egt",time())
            ];
            $nt = M("AdverList")->where($where)->field(['id','title','remark'])->order("sort asc")->select();
            $notice = [];
            foreach ($nt as $k2 => $v2) {
                $notice[] = ['id' => $v2['id'], 'content' => $v2['remark']];
            }
            $ret_data = ['banner' => $banner, 'notice' => $notice, 'category' => $category ? $category : []];
        }

        //今日最新推介
        $pubTimes = [];
        $lists = M('IntroLists')
            ->field('product_id, pub_time')
            ->where([ 'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]], 'status' => '1'])
            ->select();

        //获取今日有最新推介并且已经发布的产品发布时间
        foreach($lists as $k4 => $v4){
            if($v4['pub_time'] < NOW_TIME){
                //$pubTimes[$v4['product_id']] = $v4['pub_time'];
                $publishedIds[$v4['product_id']] = 1;
            }
        }

        //所有产品
        $products = M('IntroProducts')->alias('P')
            ->field('P.id,P.class_id,P.name,P.`desc`,P.logo,P.sale,P.game_num,P.ten_num,P.total_rate,P.remark,C.background')
            ->join('LEFT JOIN qc_intro_class C ON c.id = P.class_id')
            ->where(['P.class_id' => ['IN', $displayIds],'P.status' => 1])->select();

        $sort1 = $sort2 = $sort3 = [];
        foreach($products as $k5 => $v5){
            $published = '0';
            if($publishedIds[$v5['id']]){
                $published = '1';
            }
            $sort1[] = $published;
            $sort2[] = $v5['total_rate'];
            $sort3[] = $v5['ten_num'];
            $products[$k5]['published'] = $published;
        }

        //排序 已发布时间》累计回报率》近十中几
        array_multisort($sort1, SORT_DESC, $sort2, SORT_DESC, $sort3, SORT_DESC, $products);
        $_products = array_slice($products, ($page - 1) * 20, 20)?:[];

        foreach($_products as $k6 => $v6){
            $_products[$k6]['logo'] = $v6['logo'] ? Tool::imagesReplace($v6['logo']) :'';
            $_products[$k6]['background'] = $v6['background']?Tool::imagesReplace($v6['background']):'';
            unset($_products[$k6]['class_id']);
        }

        $ret_data['products'] = $_products;
        $this->ajaxReturn($ret_data);
    }

    /**
     * 分类详情
     */
    public function categoryDetail(){
        $page       = $this->param['page']?:1;
        $start      = ($page - 1) * 20;
        $classId    = $this->param['classId'];
        $sort1      = $sort2 = $sort3 = [];
        $blockTime  = getBlockTime(1, true);
        if(!$classId)
            $this->ajaxReturn(101);

        //分类信息
        if($page <= 1 ){
            $classInfo = M('IntroClass')->field('id, name, desc, logo')->where(['id' => $classId])->order('sort asc')->find();
            if($classInfo){
                $classInfo['logo'] = $classInfo['logo'] ? Tool::imagesReplace($classInfo['logo']):'';
                $ret_data['category'] = $classInfo;
            }
        }

        //今日最新推介
        $lists = M('IntroLists')
            ->field('product_id, pub_time, create_time')
            ->where([ 'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]], 'status' => '1'])
            ->select();

        //获取今日有最新推介并且已经发布的时间
        foreach($lists as $k4 => $v4){
            if($v4['pub_time'] < NOW_TIME){
                $publishedIds[$v4['product_id']] = 1;
            }
        }

        //该分类下产品列表
        $products = M('IntroProducts')
            ->field('id,class_id,name, `desc`,logo,sale,game_num,ten_num,total_rate,remark')
            ->where(['status' => '1','class_id' => $classId])
            ->select();

        foreach($products as $k2 => $v2){
            $published = '0';
            if($publishedIds[$v2['id']]){
                $published = '1';
            }
            $sort1[] = $published;
            $sort2[] = $v2['total_rate'];
            $sort3[] = $v2['ten_num'];
            $products[$k2]['published'] = $published;
        }

        array_multisort($sort1, SORT_DESC, $sort2, SORT_DESC, $sort3, SORT_DESC, $products);
        $_products = array_slice($products, $start, 20);

        //背景图片
        $background = M('IntroClass')->field('background')->where(['id' => $classId])->getField('background');
        foreach($_products as $k3 => $v3){
            $_products[$k3]['logo'] = $v3['logo'] ? Tool::imagesReplace($v3['logo']) :'';
            $_products[$k3]['background'] = $background?Tool::imagesReplace($background):'';
            unset($_products[$k3]['class_id']);
        }

        $ret_data['products'] = $_products;

        $this->ajaxReturn($ret_data);
    }

    /**
     * 产品详情
     */
    public function productDetail(){
        $page       = $this->param['page']?:1;
        $productId  = $this->param['productId'];
        $userToken  = $this->param['userToken'];
        $userInfo   = getUserToken($userToken);
        $published  = $end_state = '0';
        $blockTime = getBlockTime(1, true);

        if(!$productId)
            $this->ajaxReturn(101);

        $products = M('introProducts')->field('id,name,desc,logo,logo,sale,total_num,pay_num,game_num,ten_num,total_rate,create_time')->where(['id' => $productId])->find();
        if($page <= 1 ){
            if(!$products)
                $this->ajaxReturn(8011);

            //该产品是否发布推介、购买情况、当前用户是否购买或者订购
            $intro = M('IntroLists')->where(['status' => 1, 'product_id' => $productId, 'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]]])->find();

            if ($intro) {
                //是否发布
                if($intro['pub_time'] < NOW_TIME){
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
                    ->where(['product_id' => $productId, 'user_id' => $userInfo['userid'], 'list_id' => $intro['id']])->find()?'1':'0';
                $buy_num = $products['total_num'] - ($intro['remain_num'] <= 0 ? 0 : $intro['remain_num']);

                //完场情况
                foreach($gamble as $k=>$v){
                    $gamble[$k]['score'] = $v['score']? $v['score']:'';
                    if(in_array($v['game_state'], ['0','1','2','3','4'])){
                        $end_state = '0';break;
                    }else{
                        $end_state = '1';
                    }
                }

            }else{
                //后台没推介：统计**当天**产品订购数量
                $num = M('IntroBuy')
                    ->where(['product_id' => $productId, 'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]]])->count();

                //实际预购数量+真实预购数量
                $order_num = $products['pay_num'] + $num;
                $buy_num =  $order_num > $products['total_num'] ? $products['total_num'] : $order_num;

                //是否【订购】
                $buy_log = M('IntroBuy')->where(['product_id' => $productId, 'user_id' => $userInfo['userid']])->order('id DESC')->find();

                if($buy_log && !$buy_log['list_id']){
                    $is_order = '1';
                }
            }

            foreach($gamble as $key1=>$value1){
                $gamble[$key1]['union_name'] = explode(',',$value1['union_name']);
                $gamble[$key1]['home_team_name'] = explode(',',$value1['home_team_name']);
                $gamble[$key1]['away_team_name'] = explode(',',$value1['away_team_name']);
            }

            $products['buy_num']    = $buy_num  ?(string)$buy_num:'0';
            $products['gtime']      = $gtime    ?(string)$gtime:'';
            $products['end_state']  = $end_state;
            $products['pub_time']   = $pub_time ?(string)$pub_time:'';
            $products['published']  = $published?:'0';
            $products['newIntro']   = $gamble   ?$gamble:[];

            unset($products['create_time']);
            $products['logo'] = $products['logo'] ? Tool::imagesReplace($products['logo']) : '';

            //是否关注
            $subscribe = M('IntroFollow')->where(['product_id' => $productId, 'user_id' => $userInfo['userid']])->find();

            //获取产品回报率
            $intros  = M('IntroLists')->field('return_rate,total_rate,create_time')->where(['status' => 1, 'return_rate' => ['exp','is not null'] ,'product_id' => $productId, 'create_time' => ['LT', $blockTime['beginTime']]])->order('create_time ASC')->select();
            $earlyIntroTime = $intros[0]['create_time'];

            //个推介时间距离最早推介时间的天数以及对应的回报率
            foreach($intros as $kIntro => $vIntro){
                $rateArrs[] = $vIntro['total_rate'] ?:'0';
                $nextArr = $intros[$kIntro+1];
                if($nextArr){
                    $curToMinDays   = round(($vIntro['create_time'] - $earlyIntroTime)/3600/24);
                    $nextToMinDays  = round(($nextArr['create_time'] - $earlyIntroTime)/3600/24);
                    $diffDays = $nextToMinDays - $curToMinDays;
                    //如果某个天数没有推介，则以最近前一天推介回报率补齐
                    if($diffDays > 1){
                        for($i = 1; $i < $diffDays; $i++){
                            $rateArrs[] = $vIntro['total_rate']?:'0';
                        }
                    }
                }
            }

            $ret_data['subscribe'] = $subscribe?'1':'0';
            $ret_data['is_order'] = $is_order?'1':'0';

            $ret_data['baseInfo'] = $products?:[];
            $ret_data['rateArrs'] = $rateArrs?:[];

            //历史推介,按照天数分页，每天可能有3场或者1场比赛
            //$start  = ($page - 1) * 20 * $products['game_num'];
            //$limit  = 20 * $products['game_num'];
            $cacheKey = MODULE_NAME . '_historyIntro_list:' . $productId . $blockTime['endTime'];

            if(!$historyIntro = S($cacheKey))
            {
                $historyIntro = M('IntroLists')->alias('L')
                ->field('L.id,G.union_name,U.union_color,G.home_team_name,G.score,G.away_team_name,G.play_type,G.chose_side,G.odds,G.handcp,G.result,G.gtime,L.pub_time')
                ->join('LEFT JOIN qc_intro_gamble G ON L.id = G.list_id')
                ->join('LEFT JOIN qc_union U ON U.union_id = G.union_id')
                ->where(['L.status' => 1, 'L.product_id' => $productId, 'L.create_time' => ['LT', $blockTime['beginTime']]])
                ->order('L.create_time DESC, G.gtime ASC ')
                ->select();
                //->limit($start, $limit)

                S($cacheKey, $historyIntro, 30*60);
            }
        }



        //变换格式
        $_historyIntro = $historyIntroArrs=[];
        foreach($historyIntro as $key=>$value){
            $value['union_name'] = explode(',',$value['union_name']);
            $value['home_team_name'] = explode(',',$value['home_team_name']);
            $value['away_team_name'] = explode(',',$value['away_team_name']);

            $_historyIntro[$value['id']]['pub_time']= $value['pub_time'];
            $_historyIntro[$value['id']]['gamble'][]= $value;
        }

        foreach($_historyIntro as $key2=>$value2){
            $historyIntroArrs[]= $value2;
        }

        $ret_data['historyIntro'] = $historyIntroArrs;
        $this->ajaxReturn($ret_data);
    }


    /**
     * 关注功能
     */
    public function subscribe()
    {
        $userToken = $this->param['userToken'];
        $product_id = $this->param['productId'];
        $actionType = $this->param['actionType'];
        if(!$userToken || !$product_id || !$actionType)
            $this->ajaxReturn(101);

        $userInfo = getUserToken($userToken);
        if(!isset($userInfo['userid']))
            $this->ajaxReturn(1001);

        $has = M('IntroFollow')->where(['product_id' => $product_id, 'user_id' => $userInfo['userid']])->find();

        //关注
        if($actionType == 1){
            if($has) $this->ajaxReturn(8001);

            $res = M('IntroFollow')->add(['product_id' => $product_id, 'user_id' => $userInfo['userid'], 'create_time' => NOW_TIME]);

            if(!$res) $this->ajaxReturn(8002);
        }elseif($actionType == 2){
            if(!$has) $this->ajaxReturn(8003);

            $res = M('IntroFollow')->where(['product_id' => $product_id, 'user_id' => $userInfo['userid']])->delete();
            if(!$res)
                $this->ajaxReturn(8004);
        }

        $this->ajaxReturn(['result' => '1']);
    }

    /**
     * 订购、抢购
     * 注意：高并发下可能会出现超卖的情况，需优化（基于redis乐观锁）
     */
    public function order()
    {
        $userToken  = $this->param['userToken'];
        $productId = $this->param['productId'];
        $platform = $this->param['platform'];
        $userInfo   = getUserToken($userToken);
        $blockTime = getBlockTime(1, true);

        if (!$userToken || !$productId || !$platform)
            $this->ajaxReturn(101);

        if (!isset($userInfo['userid']))
            $this->ajaxReturn(1001);

        $products = M('introProducts')->master(true)->field('id,name,total_num,sale,create_time')->where(['id' => $productId])->find();
        if(!$products)
            $this->ajaxReturn(101);

        //个人金币
        $frontUser = M('FrontUser')->master(true)->field('coin, username, unable_coin')->where(['id' => $userInfo['userid']])->find();

        //金币是否足够,先使用不可提金币
        $total_coin = $frontUser['coin'] + $frontUser['unable_coin'];
        if ($total_coin <= 0 || $total_coin < $products['sale'])
            $this->ajaxReturn(8009);

        if ($frontUser['unable_coin'] < $products['sale']) {
            $save_coin = $frontUser['coin'] - ($products['sale'] - $frontUser['unable_coin']);
            $save_unable_coin = 0;
        } else {
            $save_coin = $frontUser['coin'];
            $save_unable_coin = $frontUser['unable_coin'] - $products['sale'];
        }

        //是否已经发了推介
        $intro = M('IntroLists')->master(true)->where(['status' => 1, 'product_id' => $productId, 'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]]])->find();

        if ($intro) {
            //是否达到订购上限
            if($intro['remain_num'] <=0)
                $this->ajaxReturn(8005);

            //是否已购买
            $is_order = M('IntroBuy')->master(true)->where(['product_id' => $productId, 'user_id' => $userInfo['userid'], 'list_id' => $intro['id']])->find();

            if($is_order)
                $this->ajaxReturn(8006);

        }else{
            //是否已购买
            $buy_log = M('IntroBuy')->master(true)->where(['product_id' => $productId, 'user_id' => $userInfo['userid']])->order('id DESC')->find();

            if($buy_log && !$buy_log['list_id']){
                $this->ajaxReturn(8007);
            }

            //是否达到订购上限
            $num = M('IntroBuy')->master(true)->where(['product_id' => $productId, 'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]]])->count();
            if($num >= $products['total_num'])
                $this->ajaxReturn(8005);
        }

        //事务开始
        M()->startTrans();

        //金币更新
        $update1 = M('FrontUser')->where(['id'=>$userInfo['userid']])->save(['unable_coin'=> $save_unable_coin,'coin'=> $save_coin]);
        //生成购买记录
        $insertId1 = M('IntroBuy')->add([
            'user_id'       =>  $userInfo['userid'],
            'product_id'    =>  $products['id'],
            'list_id'       =>  $intro['id']?:'',
            'price'         =>  $products['sale'],
            'platform'      =>  $platform,
            'create_time'   => NOW_TIME
        ]);
        //账户明细
        $insertId2 = M('AccountLog')->add([
            'user_id'    =>  $userInfo['userid'],
            'intro_buy_id'    =>  $insertId1,
            'log_time'   =>  NOW_TIME,
            'log_type'   =>  '17',
            'log_status' =>  '1',
            'change_num' =>  $products['sale'],
            'total_coin' =>  $save_coin + $save_unable_coin,
            'desc'       =>  "您已成功购买【{$products['name']}】的服务",
            'platform'   =>  $platform,
            'operation_time' => NOW_TIME
        ]);
        //限购数减一
        if($intro['id']){
            $update2 = M('IntroLists')->where(['id' => $intro['id']])->setDec('remain_num', 1);//没有发推介时，是没有intro id的
        }

        if($insertId1 === false || $update1 === false || $insertId2 === false || $update2 === false){
            M()->rollback();
            $this->ajaxReturn(8010);
        }else{
            M()->commit();
        }

        //推送消息
        if($intro['id'] && $intro['pub_time'] > NOW_TIME){
            $gamble = M('IntroGamble')->master(true)->where(['list_id' => $intro['id']])->select();
            $msg = $products['name'];
            foreach($gamble as $gkey => $gval) {
                $union_name     = explode(',', $gval['union_name'])[0];
                $home_team_name = explode(',', $gval['home_team_name'])[0];
                $away_team_name = explode(',', $gval['away_team_name'])[0];

                if ($gamble['play_type'] == 1) {
                    $select = $gamble['play_type'] == 1 ? $home_team_name : $away_team_name;
                } else {
                    $select = $gamble['play_type'] == 1 ? '大球' : '小球';
                }

                $gdata = date('d-m H:i', $gval['gtime']);
                $str = '';
                switch($gkey){
                    case 0: $str = "推介一：";break;
                    case 1: $str = "推介二：";break;
                    case 2: $str = "推介三：";break;
                }
                $msg .= $str. "{$gdata} {$union_name}【{$home_team_name} VS {$away_team_name}】{$select} {$gval['handcp']}(".$gval['odds'].");";
            }

            //插入消息表
            M('MobileMsg')->add([
                'user_id'       => $userInfo['userid'],
                'list_id'       => $intro['id'],
                'content'       => $msg,
                'send_type'     => '2',
                'module'        => '16',
                'module_value'  => $products['id'],
                'state'         => 0,
                'send_time'     => $intro['pub_time']
            ]);
        }

        $this->ajaxReturn(['result' => 1, 'alert' => '注意：请留意接收短信和App推送通知，如没收到信息，请及时联系在线客服。']);
    }
}