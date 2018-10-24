<?php
/**
 * 球王页面
 */
use Think\Controller;
use Think\Tool\Tool;

class IntroController extends CommonController
{
    public $today = '';

    public function _initialize()
    {
        $this->today = mktime(10, 32, 0, date('m'), date('d'), date('Y'));//更新时间点,每天10点30
        parent::_initialize();
    }

    /**
     * @ 球王主页
     * */
    public function index()
    {
        //获取首页图片轮播
        if (!$AdCarousel = S('web_intro_AdCarousel')) {
            $AdCarousel = Tool::getAdList(28,5,1);
            S('web_intro_AdCarousel', json_encode($AdCarousel), 60);
        }
        $this->assign('AdCarousel', $AdCarousel);

        //获取首页广告图
        if (!$ad = S('intro_AdCarousel')) {
            $ad = Tool::getAdList(35, 1);
            S('intro_AdCarousel', json_encode($ad), 60);
        }
        $this->assign('ad', $ad[0]);
        //获取分类
        $intro_class = S('introclass');
        if (!$intro_class) {
            $list = M('IntroClass')->order('sort')->limit(4)->select();
            foreach ($list as &$val) {
                $val['logo'] = Tool::imagesReplace($val['logo']);
            }
            S('introclass', $list, 300);
            $intro_class = $list;
        }
        $intro_list = $this->introlist();//获取产品列表
        $introgamble = $this->introgamble();//获取全部赛事列表
        $this->assign('intro_class', $intro_class);
        $this->assign('intro_list', $intro_list);
        $this->assign('introgamble', $introgamble);
        //获取周,月回报率
        $rank = $this->remain_rank();
        $this->assign('rank', $rank);
        $this->display();
    }

    //获取我关注的产品
    public function getIntroFollow()
    {
        //球王---我的关注
        $user_id = is_login();
        if ($user_id > 0)
        {
            //获取登录用户关注的产品
            $user_pro = M("IntroFollow")->where(['user_id' => $user_id])->getField('product_id', true);
            $list = M('IntroProducts')->where(['id' => ['IN', $user_pro]])->select();
            if ($list) {
                $list = $this->pub_sort($user_pro, $list);//获取发布时间
                foreach($list as &$val)
                {
                    $val['logo'] = Tool::imagesReplace($val['logo']);
                    $val['num']= M("IntroProducts")->where($val['id'])->order('total_pay desc')->getField('total_pay as num');//销量
                }
            $this->ajaxReturn(['code'=>1,'data'=>$list]);
            }
            else
            {
                $this->ajaxReturn(['code'=>3]);
            }
        }
        else
        {
            $this->ajaxReturn(['code'=>2]);
        }
    }

    /*
     * 获取当天命中率跟回报率排行
     */
    public function remain_rank()
    {
        $res = S('remain_rank');
        if (!empty($res['week_remain']) && !empty($res['mon_remain']) && !empty($res['week_hit']) && !empty($res['mon_hit'])) return $res;
        $time = M("IntroRank")->order('create_time desc')->getField('create_time');
        $list = M("IntroRank")->where(['create_time' => $time])->order('total_num desc')->select();
        $res = array();
        $introlist = S('IntroProducts');
        foreach ($list as &$val) {
            foreach ($introlist as $v) {
                if ($val['product_id'] == $v['id']) {
                    $val['name'] = $v['name'];
                    $val['logo'] = $v['logo'];
                    $val['ten_num'] = $v['ten_num'];
                    $val['p_id'] = $v['id'];
                    $val['total_rate'] = $v['total_rate'];
                }
            }
            if($val['name'])
            {
                if ($val['type'] == 1) {
                    if ($val['time_type'] == 1) {
                        $res['week_remain'][] = $val;
                    } elseif ($val['time_type'] == 2) {
                        $res['mon_remain'][] = $val;
                    }
                } else {
                    if ($val['time_type'] == 1) {
                        $res['week_hit'][] = $val;
                    } elseif ($val['time_type'] == 2) {
                        $res['mon_hit'][] = $val;
                    }
                }
            }
        }
        $res['week_remain'] = array_slice($this->sortArrByManyField($res['week_remain'], 'total_num', SORT_DESC, 'ten_num', SORT_DESC, 'p_id', SORT_DESC), 0, 10);
        $res['mon_remain'] = array_slice($this->sortArrByManyField($res['mon_remain'], 'total_num', SORT_DESC, 'ten_num', SORT_DESC, 'p_id', SORT_DESC), 0, 10);
        $res['week_hit'] = array_slice($this->sortArrByManyField($res['week_hit'], 'total_num', SORT_DESC, 'total_rate', SORT_DESC, 'p_id', SORT_DESC), 0, 10);
        $res['mon_hit'] = array_slice($this->sortArrByManyField($res['mon_hit'], 'total_num', SORT_DESC, 'total_rate', SORT_DESC, 'p_id', SORT_DESC), 0, 10);
        S('remain_rank', $res, 60 * 4);
        return $res;
    }

    /*
     * 跟新当天周,月回报率跟命中率
     * 供后台跟新按钮使用
     */
    public function remain_num()
    {
        $today = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $res = M('IntroRank')->where(['create_time' => $today])->getField('id',true);
        M('IntroRank')->where(['id' => ['in',$res]])->delete();
//        if ($res) return true;
        $week = mktime(0, 0, 0, date('m'), date('d') - 7, date('Y'));
        $month = mktime(0, 0, 0, date('m'), date('d') - 30, date('Y'));
        $week_map['create_time'] = array('BETWEEN', array($week, $today));
        $month_map['create_time'] = array('BETWEEN', array($month, $today));
        $week_remain = M("IntroLists")->field("product_id,sum(return_rate) AS count_num")->where($week_map)->group('product_id')->select();
        $mon_remain = M("IntroLists")->field("product_id,sum(return_rate) AS count_num")->where($month_map)->group('product_id')->select();
        $field = "`product_id`,sum(	CASE WHEN is_win = 1 THEN 1 ELSE 0 END ) + sum( CASE WHEN is_win = 2 THEN 1 ELSE 0 END ) AS count, sum( CASE WHEN is_win = 1 THEN 1 ELSE 0 END ) AS win";
        $week_hit = M("IntroLists")->field($field)->where($week_map)->group('product_id')->select();
        $mon_hit = M("IntroLists")->field($field)->where($month_map)->group('product_id')->select();
        //回报率
        $remain = array();
        foreach ($week_remain as $val) {
            $data['product_id'] = $val['product_id'];
            $data['total_num'] = $val['count_num'];
            $data['create_time'] = $today;
            $data['type'] = 1;
            $data['time_type'] = 1;
            $remain[] = $data;
        }
        foreach ($mon_remain as $val) {
            $data['product_id'] = $val['product_id'];
            $data['total_num'] = $val['count_num'];
            $data['create_time'] = $today;
            $data['type'] = 1;
            $data['time_type'] = 2;
            $remain[] = $data;
        }
        //命中率
        foreach ($week_hit as $val) {
            $data['product_id'] = $val['product_id'];
            $data['total_num'] = round($val['win'] / $val['count'] * 100, 0);
            $data['create_time'] = $today;
            $data['type'] = 2;
            $data['time_type'] = 1;
            $remain[] = $data;
        }
        foreach ($mon_hit as $val) {
            $data['product_id'] = $val['product_id'];
            $data['total_num'] = round($val['win'] / $val['count'] * 100, 0);
            $data['create_time'] = $today;
            $data['type'] = 2;
            $data['time_type'] = 2;
            $remain[] = $data;
        }
        $status = M("IntroProducts")->where(['status'=>0])->getField('id',true);

        foreach($remain as $key=>$val)
        {
            foreach ($status as $v)
            {
                if($val['product_id'] == $v) unset($remain[$key]);
            }
        }
        $res = M("IntroRank")->addAll($remain);
        if(I('key') == 'var')
        {
            var_dump($remain,$week_remain,$mon_remain,$week_hit,$mon_hit,$res);
            var_dump(M("IntroRank")->getLastSql());
        }

        return true;
    }


    /*
     * 获取全部赛事列表
     */
    public function introgamble()
    {
        if (!$arr = S('introgamble')) {
            $arr = array();
            $pro_list = M('IntroProducts')->where(['status' => 1])->select();
            $productsArr = array();
            foreach ($pro_list as $val) {
                $productsArr[] = $val['id'];
            }
            $map['product_id'] = array('IN', $productsArr);
            $list_id = M('IntroLists')->field("LEFT(GROUP_CONCAT(id ORDER BY pub_time desc),22) as id")->where($map)->GROUP('product_id')->select();
            $list = array();
            foreach ($list_id as $val) {
                if ($val['id']) {
                    $list_arr = array();
                    $list_arr = explode(',', $val['id']);
                    $game = M('IntroGamble')->where(['list_id' => $list_arr[0]])->select();
                    $status = false;
                    if ($game) {
                        foreach ($game as $val) {
                            if ($val['result'] == 0) $status = 1;
                        }
                        if ($status) {
                            $_res = M('IntroGamble')->where(['list_id' => $list_arr[1]])->select();
                        } else {
                            $_res = $game;
                        }
                    } else {
                        $_res = M('IntroGamble')->where(['list_id' => $list_arr[1]])->select();
                    }
                }
                if ($_res) $list = array_merge($list, $_res);

            }
            foreach ($productsArr as $val) {
                foreach ($list as $v) {
                    $v['union_name'] = explode(',', $v['union_name'])[0];
                    $v['home_team_name'] = explode(',', $v['home_team_name'])[0];
                    $v['away_team_name'] = explode(',', $v['away_team_name'])[0];
                    $v['game_time'] = date("m/d H:s ", $v['gtime']);
                    if ($v['result'] == -1 || $v['result'] == -0.5) {
                        $v['photo'] = 'lose';
                    } elseif ($v['result'] == 2) {
                        $v['photo'] = 'zou';
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
                        $v['photo'] = 'win';
                    } else {
                        $v['photo'] = '';
                    }
                    if ($v['product_id'] == $val) {
                        $arr[$val][] = $v;
                    }
                }
            }
            S('introgamble', $arr, 5);
        }
        return $arr;
    }

    /*
     * 获取产品列表
     * 返回数组中release用来判断是否发布,反正不等于2都是未发布
     */
    public function introlist()
    {
        $class = S('introclass');
        if (!S('IntroProducts' . $this->key) || !S('productIdArr' . $this->key)) {
            $list = M('IntroProducts')->where(['status' => 1])->select();
            foreach ($list as &$val1) {
                $val1['logo'] = Tool::imagesReplace($val1['logo']);
                $productIdArr[] = $val1['id'];
            }
            $pro_arr = $productIdArr;
            $map['product_id'] = array('IN', $pro_arr);
            $list_id = M('IntroLists')->field("LEFT(GROUP_CONCAT(id ORDER BY pub_time desc),11) as id")->where($map)->GROUP('product_id')->select();
            $list_arr = array();
            foreach ($list_id as $value) {
                if ($value['id']) $list_arr[] = explode(',', $value['id'])[0];
            }
            $where['id'] = array('IN', $list_arr);
            $res = M('IntroLists')->where($where)->select();
            $time = $this->today;//更新时间点,每天10点30
            foreach ($res as $val) {
                foreach ($list as &$v) {
                    if ($val['product_id'] == $v['id']) {
                        $v['pub_time'] = $val['pub_time'];
                        $v['release'] = $this->is_pub($v['pub_time']);
                    }
                }
            }
            S('IntroProducts', $list, 60);
            S('productIdArr', $productIdArr, 600);
        }
        foreach ($class as $val) {
            $arr[$val['id']] = $this->intro_list_module($val['id']);
        }
        return $arr;
    }

    /*
     * 根据分类获取数据
     * @return array
     * 返回数组,一个大分类下4种产品排序数组
     */
    public function intro_list_module($id)
    {
        $products_arr = S('IntroProducts');
        $arr = array();
        $product_id = array();
        foreach ($products_arr as $key => $val) {
            if ($val['class_id'] == $id) {
                $val['num']= M("IntroProducts")->where($val['id'])->order('total_pay desc')->getField('total_pay as num');//销量
                $arr[$val['id']] = $val;
                $product_id[] = $val['id'];
            }
        }
        $res = $this->pub_sort($product_id, $arr);//获取发布时间
        $res = $this->sortArrByManyField($res, 'total_rate', SORT_DESC, 'ten_num', SORT_DESC);//按回报率排序
        return $res;
    }

    /*
     * 按发布时间排序
     */
    public function pub_sort($productArr, $list)
    {
        $blockTime = getBlockTime(1, true);
        //今日是否已发布产品
        $is_push = M('IntroLists')
            ->field('pub_time,product_id,count(id) as is_push')
            ->where(['product_id' => ['in', $productArr]])
            ->group('product_id')
            ->order('pub_time desc')
            ->select();
        foreach ($list as &$v) {
            foreach ($is_push as $val) {
                if ($val['product_id'] == $v['id']) {
                    $v['is_push'] = $val['is_push'];
                    $v['pub_time'] = $val['pub_time'];
                }
            }
            if (!$v['pub_time']) {
                $v['pub_time'] = 0;
            }
        }
        $res = $this->sortArrByManyField($list, 'pub_time', SORT_DESC, 'total_rate', SORT_DESC);
        return $res;
    }

    /*
     * 按销量排序(实时查询)
     */
    public function sales_volume($id, $arr, $type = 1)
    {
        if ($type) {
            $map['id'] = array('in', $id);
            $list = M("IntroProducts")->field('id,total_pay as num')->where($map)->order('total_pay desc')->select();
            if (!$type) return $list;
            $res = array();
            foreach ($list as $val) {

                foreach($arr as $v)
                {
                    if($val['id'] == $v['id']) $res[$val['id']] = $v;
                }
                $res[$val['id']]['num'] = $val['num'];
            }
        } else {
            $limit = 5;
            $list = M("IntroProducts")->field('id,total_pay as num')->order('total_pay desc')->limit($limit)->select();
            if (!$type) return $list;
            $res = array();
            foreach ($list as $val) {
                $res[$val['id']] = $arr[$val['id']];
                $res[$val['id']]['num'] = $val['num'];
            }
        }


        $res = $this->sortArrByManyField($res, 'num', SORT_DESC, 'total_rate', SORT_DESC);
        return $res;
    }

    /*
     * 回报率跟价格排序
     */
    public function rate_sale($products_arr)
    {
        $arr['sale'] = $this->sortArrByManyField($products_arr, 'sale', SORT_DESC, 'total_rate', SORT_DESC);//按价格排序
        $arr['total_rate'] = $this->sortArrByManyField($products_arr, 'total_rate', SORT_DESC, 'ten_num', SORT_DESC);//按回报率排序
        return $arr;
    }

    /*
     * 对二维数组进行排序
     * 第一个参数为排序数组,第二个参数为排序字段,第三个为排序倒序,字段跟排序可传多个,逗号拼接,
     */
    public function sortArrByManyField()
    {
        $args = func_get_args();
        if (empty($args)) {
            return null;
        }
        $arr = array_shift($args);
        if (!is_array($arr)) {
            return [];
        }
        foreach ($args as $key => $field) {
            if (is_string($field)) {
                $temp = array();
                foreach ($arr as $index => $val) {
                    $temp[$index] = $val[$field];
                }
                $args[$key] = $temp;
            }
        }
        $args[] = &$arr;//引用值
        call_user_func_array('array_multisort', $args);
        return array_pop($args);
    }

    /*
     * 球王详情页
     */
    public function intro_info()
    {
        C('HTTP_CACHE_CONTROL','no-cache,no-store');
        $products_id = I('id');
        $is_pro = M("IntroProducts")->where(['id' => $products_id])->getField('id');
        if (!$is_pro) {
            $this->error('参数错误!', U('Index/index'));
            return true;
        }
        //获取每日精选与热门推介
        $left_list['choice'] = $this->_choice();
        $left_list['hot'] = $this->_hot();
        $this->assign('left_list', $left_list);

        //最新推介
        $new_list = $this->new_list($products_id);
        $this->assign('new_list', $new_list);

        //获取详情页广告图
        if (!$info_ad = S('intro_info_AdCarousel')) {
            $info_ad = Tool::getAdList(37, 1);
            S('intro_info_AdCarousel', json_encode($info_ad), 60);
        }
        $this->assign('info_ad', $info_ad[0]);
        //获取详情页左侧广告图
        if (!$left_ad = S('intro_left_AdCarousel')) {
            $left_ad = Tool::getAdList(38, 1);
            S('intro_left_AdCarousel', json_encode($left_ad), 60);
        }
        $this->assign('left_ad', $left_ad[0]);

        //产品信息
        $time = $this->today;//更新时间点,每天10点30
        $products_info = M('IntroProducts')->where(['id' => $products_id])->find();
        $products_info['logo'] = Tool::imagesReplace($products_info['logo']);
        switch ($new_list['status']) {
            case 1:
                $list_id = 0;
                break;
            case 2:
                $list_id = $new_list['list_id'];
                break;
            case 5:
                $list_id = 0;
                break;
        }
        if ($list_id) {
            $count_pay = M("IntroLists")->where(['id' => $list_id])->getField('remain_num');
            if ($count_pay == 0) {
                $products_info['count_pay'] = $products_info['total_num'];
                $products_info['percent'] = 100;
            } else {
                $products_info['count_pay'] = $products_info['total_num'] - $count_pay;
                $products_info['percent'] = round($products_info['count_pay'] / $products_info['total_num'] * 100, 2);
            }
        } else {
            $new_map['product_id'] = $products_id;
            $new_map['create_time'] = array('gt', $time);
            $new_map['list_id'] = $list_id;
            $count_pay = M("IntroLists")->where($new_map)->count();
            $count_pay = intval($products_info['pay_num']) + intval($count_pay);
            if ($products_info['total_num'] <= $count_pay) {
                $products_info['count_pay'] = $products_info['total_num'];
                $products_info['percent'] = 100;
            } else {
                $products_info['count_pay'] = $count_pay;
                $products_info['percent'] = round($products_info['count_pay'] / $products_info['total_num'] * 100, 2);
            }
        }
        $products_info['key'] = D('FrontUser')->encrypt($products_info['id']);
        $this->assign('products_info', $products_info);

        //判断用户是否订阅
        $user_id = is_login();
        if ($user_id) {
            $follow = M("IntroFollow")->where(['user_id' => $user_id, 'product_id' => $products_id])->getField('id');
            if ($follow) {
                $follow_status = 1;
            } else {
                $follow_status = 2;
            }
        } else {
            $follow_status = 0;
        }

        $this->assign('follow', $follow_status);

        //历史推介
        $time = $this->today;//更新时间点,每天10点30
//        $map['pub_time'] = array('lt',$time);
        $pro_info = M("IntroProducts")->where(['id' => $products_id])->find();
        $map['product_id'] = $products_id;
        $map['status'] = 1;
        $count = M('IntroLists')
            ->field('id')
            ->where($map)
            ->count('id');

        //获取当前的页码
        $currentPage = I('p', 1, 'int');
        //获取每页显示的条数
        $list = M('IntroLists')->where(['product_id' => $products_id, 'status' => 1])->order('pub_time desc')->limit(1)->find();
        if ($pro_info['game_num'] == 1) {
            $pageNum = 8;
        } elseif ($pro_info['game_num'] == 3) {
            $pageNum = 4;
        }
        $pageNum = $pageNum ? $pageNum : 3;
        //判断是否过了时间点,用来预留一条数据用来最新推介
        if ($time > $list['pub_time']) {
            if ($time < time()) {
                $page_num = $pageNum * ($currentPage - 1);
            } else {
                $page_num = $pageNum * ($currentPage - 1) + 1;
                $count = $count - 1;
            }
        } else {
            $page_num = $pageNum * ($currentPage - 1) + 1;
            $count = $count - 1;
        }
        if ($count > 0) {
            $list_arr = M('IntroLists')
                ->field('id,pub_time')
                ->where($map)
                ->order('pub_time desc')
                ->limit($page_num, $pageNum)
                ->select();
            foreach ($list_arr as $val) {
                $list_ids[] = $val['id'];
            }
            $where['list_id'] = array('IN', $list_ids);
            $list = M('IntroGamble')->where($where)->order('gtime desc')->select();
            foreach ($list_arr as $val) {
                foreach ($list as $v) {
                    $v['union_name'] = explode(',', $v['union_name'])[0];
                    $v['home_team_name'] = explode(',', $v['home_team_name'])[0];
                    $v['away_team_name'] = explode(',', $v['away_team_name'])[0];
                    $v['game_time'] = date("m/d H:s ", $v['gtime']);
                    if ($v['result'] == -1 || $v['result'] == -0.5) {
                        $v['photo'] = 'lose';
                    } elseif ($v['result'] == 2) {
                        $v['photo'] = 'zou';
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
                        $v['photo'] = 'win';
                    } else {
                        $v['photo'] = '';
                    }
                    if ($v['list_id'] == $val['id']) {
                        $arr[$val['id']]['list'][] = $v;
                        $arr[$val['id']]['time'] = $val['pub_time'];
                    }
                }
            }
            $history['arr'] = $arr;
        }
        //实例化分页类
        $page = new \Think\Page ($count, $pageNum);

        //自定义分页样式
        $page->config = array(
            'header' => '<span class="rows">共 %TOTAL_ROW% 条记录</span>',
            'prev' => '<span aria-hidden="true">上一页</span>',
            'next' => '<span aria-hidden="true">下一页</span>',
            'first' => '首页',
            'last' => '...%TOTAL_PAGE%',
            'theme' => '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%',
        );
        //设置分页路由链
        $page->url = "/Intro/intro_info/id/" . $products_id . "/p/%5BPAGE%5D.html";

        //模板赋值显示
        $this->assign("show", $page->showJs());
        //页数
        $this->assign("pageCount", $count / $page->listRows);


        $this->assign('history', $history);
        $this->display();
    }

    /*
     * ajax执行用户订阅取消操作
     */
    public function user_follow()
    {
        $data['user_id'] = is_login();
        $data['product_id'] = I(productId);
        $res = M("IntroFollow")->where($data)->find();
        if ($res) {
            M("IntroFollow")->where(['id' => $res['id']])->delete();
            $rs = 2;
        } else {
            $data['create_time'] = time();
            M('IntroFollow')->add($data);
            $rs = 1;
        }
        $this->ajaxReturn($rs);
    }

    /*
     * 最新推介
     */
    public function new_list($id)
    {
        $time = $this->today;//更新时间点,每天10点30
        $today = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $user = is_login();
        $list = M('IntroLists')->where(['product_id' => $id, 'status' => 1])->order('pub_time desc')->limit(1)->find();
        $products_info = M('IntroProducts')->field('total_num,pay_num')->where(['id' => $id])->find();
        if ($time > $list['pub_time'] && $time < time() || !$list) {
            $count_buy = M('IntroBuy')->where(['product_id' => $id, 'list_id' => 0])->count();
            $total = $products_info['pay_num'] + $count_buy;
            if ($total <= $products_info['total_num']) {
                $res['total'] = $total;
            } else {
                $res['total'] = $products_info['total_num'];
            }
            $res['percent'] = round($res['total'] / $products_info['total_num'] * 100, 2);
            if ($user) {
                $buy = M("IntroBuy")->where(['user_id' => $user, 'list_id' => 0, 'product_id' => $id])->select();
                if ($buy) {
                    $res['status'] = 5;
                    if($res['percent'] == 100)
                    {
                        $res['type'] = '已抢光';
                    }else{
                        $res['type'] = '已预购';
                    }
                    return $res;//过了10点32还没有新的推介.但是用户预购了
                }
            }
            $res['status'] = 1;
            return $res;//过了10点32还没有新的推介
        } else {
            $res['total'] = $products_info['total_num'] - $list['remain_num'];
            $res['percent'] = round($res['total'] / $products_info['total_num'] * 100, 2);
            $game = M('IntroGamble')->where(['list_id' => $list['id']])->select();
            if ($game) {
                foreach ($game as $val) {
                    if ($val['result'] == 0) $status = 1;
                }
                if ($user) $buy = M("IntroBuy")->where(['user_id' => $user, 'list_id' => $list['id']])->select();
                if ($status && !$buy) {
                    $res['list_id'] = $list['id'];
                    $res['gtime'] = M('IntroGamble')->where(['list_id' => $list['id']])->order('gtime')->getField('gtime');
                    $res['status'] = 2;//发布了推荐但是用户没登入或没购买
                    return $res;
                } else {
                    if ($list['pub_time'] > time()) {
                        $res['status'] = 5;
                        if($res['percent'] == 100)
                        {
                            $res['type'] = '已抢光';
                        }else{
                            $res['type'] = '已订购';
                        }
                        return $res;
                    }
                    $res['game_list'] = $this->new_list_arr($game);
                    $res['status'] = 3;//发布了推荐用户购买了
                    return $res;
                }
                if (!$status) {
                    $res['game_list'] = $this->new_list_arr($game);
                    $res['status'] = 4;//推荐已出全部打完已出结果
                    return $res;
                }
            }
        }
    }

    /*
     * ajax获取历史曲线数据
     */
    public function history_tab()
    {
        $today = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $key = I('key');//产品ID
        $id = D('FrontUser')->decrypt($key);
        $list = M("IntroLists")->field("total_rate,pub_time")->where(['product_id' => $id, 'status' => 1,'is_win'=>['gt',0]])->order('pub_time')->select();
        $res = array();
        foreach ($list as $key => $val) {
//            if ($val['pub_time'] < $today) {
                $res[$key][] = date("m-d", $val['pub_time']);
                $res[$key][] = $val['total_rate'];
//            }
        }
        if(end($res)[1] == 0)
        {
            array_splice($res,-1,1);
        }
        $this->ajaxReturn(json_encode($res));
    }

    /*
     * 数据处理
     */
    public function new_list_arr($game)
    {
        foreach ($game as $v) {
            $v['union_name'] = explode(',', $v['union_name'])[0];
            $v['home_team_name'] = explode(',', $v['home_team_name'])[0];
            $v['away_team_name'] = explode(',', $v['away_team_name'])[0];
            $v['game_time'] = date("m/d H:s ", $v['gtime']);
            if ($v['result'] == -1 || $v['result'] == -0.5) {
                $v['photo'] = 'lose';
            } elseif ($v['result'] == 2) {
                $v['photo'] = 'zou';
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
                $v['photo'] = 'win';
            } else {
                $v['photo'] = '';
            }
            $arr[] = $v;
        }
        return $arr;
    }

    /*
     * 热门产品
     */
    public function _hot()
    {
        $_res = S('intro_hot');
        if ($_res) return $_res;
        $today = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $res = $this->sales_volume('', '', false);
        $map['id'] = array('in', array($res[0]['id'], $res[1]['id'], $res[2]['id'], $res[3]['id'], $res[5]['id']));
        $map['status'] = 1;
        $list = M("IntroProducts")->where($map)->select();
        foreach ($list as &$val) {
            $val['logo'] = Tool::imagesReplace($val['logo']);
            $num = M("IntroLists")->field('remain_num,pub_time')->where(['product_id' => $val['id'], 'status' => 1])->order('pub_time desc')->limit(1)->find();
            if ($num) {
                $is_pub = $this->is_pub($num['pub_time']);
            } else {
                $is_pub = 1;
            }
            if ($is_pub == 1) {
                $count_buy = M('IntroBuy')->where(['product_id' => $val['id'], 'list_id' => 0])->count();
                $val['remain_num'] = $val['pay_num'] + $count_buy;
                $val['confirm'] = 1;
            } elseif ($is_pub == 2) {
                $val['remain_num'] = $val['total_num'] - $num['remain_num'];
                $val['confirm'] = 2;
            } elseif ($is_pub == 3) {
                $val['remain_num'] = $val['total_num'] - $num['remain_num'];
                $val['confirm'] = 1;
            }
            $val['pub_time'] = date("Y-m-d H:i", $num['pub_time']);
            foreach ($res as $v) {
                if ($val['id'] == $v['id']) {
                    $val['num'] = $v['num'];
                }
            }
        }
        $_res = S('IntroProducts');
        $list = $this->sortArrByManyField($list, 'num', SORT_DESC, 'total_rate', SORT_DESC);
        $list = array_slice($list, 0, 3);
        S('intro_hot', $list, 60);
        return $list;
    }

    /*
     * 每日精选
     */
    public function _choice()
    {
        $res = S('intro_choice');
        if ($res) return $res;
        $time = $this->today;//更新时间点,每天10点30
        $today = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $choice = M("IntroProducts")->where(['status' => 1])->order('total_rate desc')->limit(3)->select();
        $choice = $this->sortArrByManyField($choice, 'total_rate', SORT_DESC, 'ten_num', SORT_DESC);//按回报率排序
        $res = S('IntroProducts');
        if (!$res) {
            $this->introlist();
            $res = S('IntroProducts');
        }
        foreach ($choice as &$val) {
            $val['logo'] = Tool::imagesReplace($val['logo']);
            $num = M("IntroLists")->field('remain_num,pub_time')->where(['product_id' => $val['id'], 'status' => 1])->order('pub_time desc')->limit(1)->find();
            if ($num) {
                $is_pub = $this->is_pub($num['pub_time']);
            } else {
                $is_pub = 1;
            }
            if ($is_pub == 1) {
                $count_buy = M('IntroBuy')->where(['product_id' => $val['id'], 'list_id' => 0])->count();
                $val['remain_num'] = $val['pay_num'] + $count_buy;
                $val['confirm'] = 1;
            } elseif ($is_pub == 2) {
                $val['remain_num'] = $val['total_num'] - $num['remain_num'];
                $val['confirm'] = 2;
            } elseif ($is_pub == 3) {
                $val['remain_num'] = $val['total_num'] - $num['remain_num'];
                $val['confirm'] = 1;
            }
            $val['pub_time'] = date("Y-m-d H:i", $num['pub_time']);
        }
        S('intro_choice', $choice, 60);
        return $choice;
    }

    /*
     * 传入发布时间判断是否发布标识
     * 1为未发布,2为已发布
     */
    public function is_pub($t)
    {
        if ($t > $this->today) {
            if ($t > time()) {
                return 3;
            } else {
                return 2;
            }
        } else {
            if ($this->today > time()) {
                return 2;
            } else {
                return 1;
            }
        }
    }

    /*
     * 清理本控制器所有S缓存
     * 后台更改数据前台没有及时显示可运行该方法
     */
    public function s_null()
    {
        S('remain_rank', NULL);
        S('IntroProducts', NULL);
        S('introclass', NULL);
        S('introgamble', NULL);
        S('choice', NULL);
        S('productIdArr', NULL);
        S('web_intro_AdCarousel', NULL);
        S('intro_AdCarousel', NULL);
        S('intro_info_AdCarousel', NULL);
        S('intro_left_AdCarousel', NULL);
        S('intro_choice', NULL);
        S('intro_hot', NULL);
    }

    public function res_return()
    {
        $key = I('key');
        $res = S($key);
        $this->ajaxReturn($res);
    }

    /*
     * 统计总销量,作数据补充使用
     */
    public function total_pay()
    {
        $res = M('IntroBuy')->field("product_id as id,count(id) as total_pay")->group('product_id')->select();
        $rs = $this->batch_update('qc_intro_products', $res, 'id');
    }
}

?>