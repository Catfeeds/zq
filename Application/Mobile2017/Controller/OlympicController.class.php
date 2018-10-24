<?php

/**
 * 首页
 *
 * @author chenzj <443629770@qq.com>
 *
 * @since  2016-07-08
 */
class OlympicController extends CommonController {

    protected function _initialize() {
        C('DATA_CACHE_PREFIX','api_');
        $token=S(I('userToken'));
        if($token){
                D('FrontUser')->autoLogin($token['userid']);
        }
        $medal_ch = M('OlympicAllmedal')->where(array('country' => '中国'))->find();
        $this->assign('medal_ch', $medal_ch);
    }

    /*
     * ---首页---
     */

    public function index() {
        //今日头条
        $_M = M('PublishClass');
        $top_news = M('Config')->where(['sign' => 'OlympicNews'])->getField('config');
        //中国金牌项目
        $class = $_M->where(array('is_recommend' => 1, 'status' => 1, 'pid' => 24))
                        ->order('sort')->limit(10)->getField('id,name,sort');
        $ids = array_keys($class);
        $list = M('PublishList')->field('id,class_id,title')->where(array('status' => 1, 'is_channel_push' => 1, 'class_id' => array('IN', $ids)))->order('add_time desc,id desc')->select();
        if (!empty($class) && !empty($list)) {
            foreach ($list as $v) {
                if ($class[$v['class_id']]) {
                    $class[$v['class_id']]['_news'][] = $v;
                }
            }
        }
        $obj_news = $class;
        //综合资讯
        $all_news = $_M->alias('pc')->field('pl.id,pl.title')->join('__PUBLISH_LIST__ pl ON pc.id=pl.class_id')
                        ->where(array('pc.id' => array('between', array(37, 39)), 'pl.status' => 1))
                        ->limit(6)
                        ->order('is_channel_push desc,add_time desc,pl.id desc')->select();

        //banner
        $banner = $this->get_recommend('Olympic');
        $this->assign('banner', $banner);
        //金牌资讯
        $adver = @Think\Tool\Tool::getAdList(21, 20, 4) ? : [];
        $this->assign('adver', $adver);
        $this->assign('top_news', $top_news);
        $this->assign('obj_news', $obj_news);
        $this->assign('all_news', $all_news);
        $this->display();
    }

    //赛程
    public function schedule() {
        $GetDate = I("param.date", date('Ymd'), "intval");
        if ($GetDate < 20160804) {
            $GetDate = 20160804;
        }
        $is_china = cookie('m_china');
        $map['status'] = 1;
        $map['game_date'] = $GetDate;
        if ($is_china) {
            $map['is_china'] = $is_china;
        }
        $list = M('Olympics')->field('game_time,game_type,game_name,run_name')->where($map)->select();
        foreach ($list as &$v) {
            $v['game_type'] = str_replace("2016年里约奥运会", "", $v['game_type']);
            $v['game_type'] = str_replace("赛程", "", $v['game_type']);
        }
        if (IS_AJAX) {
            $this->success($list);
        }
        $date_list = M('Olympics')->field('game_date')->where(array('status' => 1))->group('game_date')->select();
        foreach ($date_list as &$v) {
            $v['md'] = date('m-d', strtotime($v['game_date']));
            $v['week'] = getWeek(date('w', strtotime($v['game_date'])));
        }
        $this->assign('list', $list);
        $this->assign('get_date', $GetDate);
        $this->assign('date_list', $date_list);
        //金牌资讯
        $adver = @Think\Tool\Tool::getAdList(21, 20, 4) ? : [];
        $this->assign('adver', $adver);
        $this->display();
    }

    //规则
    public function guess_rule() {
        $this->display();
    }

    //推荐
    public function guess() {
        //金牌资讯
        $adver = @Think\Tool\Tool::getAdList(21, 20, 4) ? : [];
        $this->assign('adver', $adver);
        //获取推荐
        $quiz = M('OlympicQuiz')->where(['pid' => 0, 'status' => 1])->order("game_time asc")->select();
        //获取对应选项
        foreach ($quiz as $k => $v) {
            $quiz[$k]['question'] = M("OlympicQuiz")->where(['pid' => $v['id']])->select();
            $quizId[] = $v['id'];
        }
        foreach ($quiz as $key => $vo) {
            if (NOW_TIME > $vo['game_time']) {
                $quiz[] = $vo;
                unset($quiz[$key]);
            }
        }
        $user_auth = session('user_auth');
        if ($user_auth) {
            //已推荐的选项
            $gameRecord = M("OlympicRecord")->where(['user_id' => $user_auth['id'], 'quiz_id' => ['in', $quizId]])->select();
            $this->assign("gameRecord", $gameRecord);
            //头像
            $head = M('FrontUser')->where(['id' => $user_auth['id']])->getField('head');
            $this->head = $head;
            //获得奥运总积分
            $pointCount = M("OlympicRecord")->where(['user_id' => $user_auth['id']])->sum('earn_point');
            $this->assign("pointCount", $pointCount);
            //排名
            $ranking = M("OlympicRank")->where(['user_id' => $user_auth['id']])->getField('ranking');
            $this->assign("ranking", $ranking);
            $this->assign("user_auth", $user_auth);
        }
        //banner
        $banner = $this->get_recommend('OlyGuess');
        cookie('redirectUrl', __SELF__);
        $this->assign('banner', $banner);
        $this->assign('quiz', $quiz);
        $this->display();
    }

    //执行推荐
    public function doGame() {
        $user_auth = session('user_auth');
        if (!$user_auth) {
            $this->error("请先登录！");
        }
        $id = I('game_id');
        $answer_id = I('answer_id');
        if (empty($id) || empty($answer_id)) {
            $this->error("参数错误！");
        }
        $quiz = M('OlympicQuiz')->where(['id' => $id])->find();
        //比赛是否已开始
        if (time() > $quiz['game_time']) {
            $this->error("比赛已经开始，不能推荐！");
        }
        //是否已出答案
        if ($quiz['status'] == 0 || $quiz['answer'] > 0) {
            $this->error("比赛已经结束，不能推荐！");
        }
        //是否重复推荐
        $is_record = M("OlympicRecord")->where(['user_id' => $user_auth['id'], 'quiz_id' => $id])->find();
        if ($is_record) {
            $this->error("你已推荐过，不能再推荐了！");
        }
        //查出推荐赔率
        $odds = M('OlympicQuiz')->where(['id' => $answer_id])->getField('odds');
        //添加推荐记录
        $rs = M("OlympicRecord")->add([
            'user_id' => $user_auth['id'],
            'quiz_id' => $id,
            'answer_id' => $answer_id,
            'create_time' => NOW_TIME,
            'odds' => $odds,
            'vote_point' => $quiz['point'],
        ]);
        if ($rs) {
            $this->success("推荐成功，请等候推荐结果公布！");
        } else {
            $this->error("推荐失败！");
        }
    }

    //推荐记录
    public function guess_record() {
        $user_auth = session('user_auth');
        if ($user_auth) {
            $num = 10; //每页记录数
            if (IS_AJAX) {
                $p = isset($_POST['k']) ? intval(trim($_POST['k'])) : 0;
                $total = M('OlympicRecord')->where(['user_id' => $user_auth['id']])->count(); //数据记录总数
                $totalpage = ceil($total / $num); //总计页数
                $limitpage = ($p - 1) * $num; //每次查询取记录
                if ($p > $totalpage) {
                    //超过最大页数，退出
                    $this->error("已经全部加载完毕");
                }
            } else {
                $limitpage = 0;
            }
            $record = M("OlympicRecord r")
                            ->join("LEFT JOIN qc_olympic_quiz q on r.quiz_id = q.id")
                            ->join("LEFT JOIN qc_olympic_quiz o on r.answer_id = o.id")
                            ->field("q.union_name,q.title,q.answer,q.game_time,r.create_time,r.earn_point,r.result,o.title as question,o.odds")
                            ->where(['user_id' => $user_auth['id']])->order("create_time desc")->limit($limitpage, $num)->select();
            foreach ($record as $k => $v) {
                if ($v['result'] != 0 && $v['answer'] > 0) {
                    //推荐答案
                    $record[$k]['answer_name'] = M("OlympicQuiz")->where(['id' => $v['answer']])->getField("title");
                }
            }
            if (IS_AJAX) {
                //组装html
                $lis = '';
                foreach ($record as $k => $v) {
                    $answer_name = $v['answer_name'] ? $v['answer_name'] : "未结算";
                    $li = "<li class=\"list\">";
                    if ($v['result'] == '1') {
                        $li .= "<div class=\"win\"></div>";
                    } elseif ($v['result'] == '-1') {
                        $li .= "<div class=\"lose\"></div>";
                    }
                    $li .= "<div class=\"n_top\">" .
                            "<aside><img src=\"/Public/Mobile/images/Olympic/gr_title.png\"><span>" . $v['union_name'] . "</span> <time>" . date('m-d H:i', $v['game_time']) . "</time></aside>" .
                            "</div>" .
                            "<p class=\"p_1\">" . $v['title'] . "</p>" .
                            "<p class=\"p_2\">推荐情况：<em>" . $v['question'] . "<span>（" . $v['odds'] . "）</span></em></p>" .
                            "<p class=\"p_3\">推荐结果：" . $answer_name . "</p>";
                    if ($v['result'] != 0) {
                        $earn_point = "";
                        if ($v['earn_point'] != 0) {
                            $earn_point = '+' . $v['earn_point'];
                        } else {
                            $earn_point = $v['earn_point'];
                        }
                        $li.= "<p class=\"p_5\">盈利结果：" . $earn_point . "</p>";
                    }
                    $li.= "<p class=\"p_4\">推荐时间：" . date('Y-m-d H:i', $v['create_time']) . "</p>" .
                            "</li>";
                    $lis .= $li;
                }
                $this->success($lis);
                exit;
            }
            $this->assign('record', $record);
        }
        $this->display();
    }

    public function photo() {
        $page = I('param.page', 1, 'intval');
        $pageNum = 10;
        $_M = M('Gallery');
        $map['gc.status'] = 1;
        $map['g.class_id'] = 29;
        $map['g.status'] = 1;
        $list = $_M->alias('g')->field('g.id,g.img_array,g.title,g.like_num,g.click_number,like_user')
                        ->join('__GALLERY_CLASS__ gc ON g.class_id=gc.id')
                        ->where($map)->page($page . ',' . $pageNum)
                        ->order('g.add_time desc,g.id desc')->select();
        foreach ($list as &$v) {
            $v['img_array'] = json_decode($v['img_array'], true);
            $v['img_array'][1] = @Think\Tool\Tool::imagesReplace($v['img_array'][1]);
            $v['is_like'] = 0;
            if (is_login() && $v['like_user']) {
                $like_arr = explode(',', $v['like_user']);
                if (in_array(is_login(), $like_arr)) {
                    $v['is_like'] = 1;
                }
            }
        }
        if (IS_AJAX) {
            $this->success($list);
        } else {
            //金牌资讯
            $adver = @Think\Tool\Tool::getAdList(21, 20, 4) ? : [];
            $this->assign('adver', $adver);
            $this->assign('list', $list);
            $this->display();
        }
    }

    public function photo_detail() {
        $id = I('get.id', 0, 'intval');
        if ($id < 1) {
            $this->error('参数有误!');
        }
        $_M = M('gallery');
        $data = $_M->alias('g')->field('g.id,title,img_array,g.remark,describe')
                        ->where(array('g.status' => 1, 'g.id' => $id))->find();
        if ($data) {
            //点击量加1
            $_M->where(array('id' => $id, 'status' => 1))->setInc('click_number');
        } else {
            $this->error("找不到相关内容！");
        }
        $data['img_array'] = json_decode($data['img_array'], true);
        foreach ($data['img_array'] as &$v) {
            $v = @Think\Tool\Tool::imagesReplace($v);
        }
        $data['describe'] = json_decode($data['describe'], true);
        $this->assign('data', $data);
        $this->display();
    }

    public function video() {
        $page = I('param.page', 1, 'intval');
        $pageNum = 6;
        //金牌资讯
        $adver = @Think\Tool\Tool::getAdList(21, 20, 4) ? : [];
        $this->assign('adver', $adver);
        $_M = M('OlympicVideo');
        $where['h.status'] = 1;
        $where['h.m_url'] = array(array('neq', ''));
        $list = $_M->alias('h')->field('h.id,title,remark,img,m_url,m_ischain,click_num')
                        ->where($where)->page($page . ',' . $pageNum)->order('h.add_time desc,h.id desc')->select();
        foreach ($list as &$v) {
            $v['img'] = @Think\Tool\Tool::imagesReplace($v['img']);
        }
        if (IS_AJAX) {
            $this->success($list);
        } else {
            $this->assign('list', $list);
            $this->assign('title', 'video');
            $this->display();
        }
    }

    public function toVideo() {
        $id = I('post.id', 0, 'intval');
        if ($id < 1) {
            $this->error('参数有误!');
        }
        $_M = M('OlympicVideo');
        $data = $_M->alias('h')->field('m_url,m_ischain')->where(array('id' => $id))->find();
        if ($data) {
            $_M->where(array('id' => $id, 'status' => 1))->setInc('click_num');
            if ($data['m_ischain'] == 1) {
                $this->success($data['m_ischain'], $data['m_url']);
            } else {
                $this->success($data['m_ischain'], U('Olympic/video_detail', ['id' => $id]));
            }
        } else {
            $this->error('找不到相关内容!');
        }
    }

    public function video_detail() {
        $id = I('get.id', 0, 'intval');
        if ($id < 1) {
            $this->error("参数有误!");
        }
        $_M = M('OlympicVideo');
        $data = $_M->field('title,remark,m_url,img')->where(array('id' => $id, 'status' => 1))->find();
        if (!$data) {
            $this->error("找不到相关内容！");
        }
        $data['img'] = @Think\Tool\Tool::imagesReplace($data['img']);
        $this->assign('data', $data);
        $this->display();
    }

    //新闻列表
    public function news_info() {
        $class_id = I('get.class_id');
        $_M = M('PublishClass');
        if (is_numeric($class_id)) {
            $map['pc.id'] = $class_id;
        } else {
            $class_id = urldecode($class_id);
            switch ($class_id) {
                case '金牌项目':
                    $map['pc.is_recommend'] = 1;
                    break;
                case '综合资讯':
                    $map['pc.id'] = array('between', array(38, 39));
                    break;
            }
        }
        $map['pc.pid'] = 24;
        $map['pc.status'] = 1;
        $map['pl.status'] = 1;
        $list = $_M->alias('pc')->field('pc.name,pl.id,pl.title,pl.remark,pl.img')->join('__PUBLISH_LIST__ pl ON pc.id=pl.class_id')
                        ->where($map)
                        ->order('add_time desc,pl.id desc')->select();
        foreach ($list as &$v) {
            $v['img'] = @Think\Tool\Tool::imagesReplace($v['img']);
        }
        if (is_numeric($class_id)) {
            $this->assign('title', $list[0]['name']);
        } else {
            $this->assign('title', $class_id);
        }
        $this->assign('list', $list);
        $this->display();
    }

    //新闻详情
    public function news_detail() {
        $id = I('get.id', 0, 'intval');
        if ($id < 1) {
            $this->error("找不到相关页面！");
        }
        $user_id = is_login();
        $page = I('param.page', 1, 'intval');
        $num = 10;
        $limit = ($page - 1) * $num;
        $commlist = A('Home/PublishIndex')->getCommlist(0, $comment = array(), $id, 'hot', $limit, $num);
        foreach ($commlist as &$v) {
            $v['create_time'] = format_date($v['create_time']);
            $v['head'] = frontUserFace($v['head']);
            $v['is_like'] = 0;
            if ($v['like_user'] && $user_id) {
                $like = explode(',', $v['like_user']);
                if (in_array($user_id, $like)) {
                    $v['is_like'] = 1;
                }
            }
        }
        if (IS_AJAX) {
            $this->success($commlist);
        }
        $_M = M('PublishList');
        $data = $_M->alias('pl')->field('pc.name,pl.title,pl.class_id,label,pl.add_time,source,content')
                        ->join('__PUBLISH_CLASS__ pc ON pl.class_id=pc.id')->where(array('pl.id' => $id, 'pl.status' => 1, 'pc.status' => 1))->find();
        if ($data) {
            //点击量加1
            M('PublishList')->where(array('id' => $id, 'status' => 1))->setInc('click_number');
        } else {
            $this->error("找不到相关内容！");
        }
        if (!empty($data['label'])) {
            $lable = explode(',', $data['label']);
            $this->assign('lable', $lable);
        }
        $this->assign('commlist', $commlist);
        $this->assign('user_id', is_login());
        $this->assign('publish_id', $id);
        $this->assign('data', $data);
        $this->display();
    }

    //奖牌榜
    public function medal() {
        $list = M('OlympicAllmedal oa')->where(['ranking'=>['gt',0]])->order('ranking,id')->select();
        foreach ($list as &$v) {
            $ranking[]=$v['ranking'];
            $count[] = $v['count'] = $v['gold'] + $v['silver'] + $v['copper'];
            $gold[] = $v['gold'];
            $silver[] = $v['silver'];
            $copper[] = $v['copper'];
        }
        array_multisort($ranking,SORT_ASC,$count, SORT_DESC, $gold, SORT_DESC, $silver, SORT_DESC, $copper, SORT_DESC, $list);
        $this->assign('list', $list);
        $this->display();
    }

    public function medal_ch() {
        $id = I('get.id', 1, 'intval');
        if ($id < 1 || $id > 3) {
            $id = 1;
        }
        $list = M('OlympicMedal')->field('name,player_name,sport_name,get_time,medal_type,url')->where(array('status' => 1))->order('medal_type desc,get_time desc')->select();
        $data = array();
        foreach ($list as $v) {
            $data[$v['medal_type']][] = $v;
        }
        $this->assign('id', $id);
        $this->assign('list', $data);
        $this->display();
    }

    //排行榜
    public function rank_list() {
        $rank = M("OlympicRank r")
                        ->join("LEFT JOIN qc_front_user f on f.id = r.user_id")
                        ->field("r.ranking,r.pointCount,f.head,f.nick_name")
                        ->where(['r.year_date' => date('Y')])
                        ->order("ranking asc")->select();
        foreach ($rank as $k => $v) {
            //头像
            $rank[$k]['head'] = @Think\Tool\Tool::imagesReplace($v['head']);
            if($rank[$k]['head']==NULL){
                $rank[$k]['head']='/Public/Mobile/images/default_head.png';
            }
        }
        $this->assign("rank", $rank);
        $user_auth = session('user_auth');
        if ($user_auth) {
            //获得奥运总积分
            $pointCount = M("OlympicRecord")->where(['user_id' => $user_auth['id']])->sum('earn_point');
            $this->assign("pointCount", $pointCount);
            //排名
            $ranking = M("OlympicRank")->where(['user_id' => $user_auth['id']])->getField('ranking');
            $this->assign("ranking", $ranking);
        }
        $this->display();
    }

    //点赞
    public function dolike() {
        $id = I('post.id', 0, 'intval');
        $type = I('post.type', 0, 'intval');
        $url = I('post.url', '');
        if ($id < 1 || $type < 1 || $type > 2 || empty($url)) {
            $this->error('参数有误!');
        }
        switch ($type) {
            case 1:
                $_M = M('Gallery');
                break;
            case 2:
                $_M = M('Comment');
                break;
        }
        $user_id = is_login();
        if (!$user_id) {
            cookie('redirectUrl', $url);
            $this->ajaxReturn(['status' => -1, 'info' => '请先登录!', 'url' => U('User/login')]);
        }
        $like_user = $_M->where(array('id' => $id))->getField('like_user');
        if ($like_user) {
            $like_arr = explode(',', $like_user);
            if (in_array($user_id, $like_arr)) {
                $this->error('您已经赞过了!');
            }
            $like_arr[] = $user_id;
            $data = implode(',', $like_arr);
        } else {
            $data = is_login();
        }

        $rsl = $_M->where(array('id' => $id))->save(array(
            'like_num' => ['exp', 'like_num+1'],
            'like_user' => $data
        ));
        if ($rsl) {
            $this->success('操作成功!');
        } else {
            $this->error('操作失败!');
        }
    }

    //举报
    public function toReport() {
        $id = I('post.id', 0, 'intval');
        $choice = I('post.choice', 0, 'intval');
        $user_id = is_login();
        if (!$user_id) {
            $this->error('请先登录!');
        }
        if ($choice < 1 || $choice > 6 || $id < 1) {
            $this->error('参数有误!');
        }
        $arr = array(
            1 => '反动言论',
            2 => '淫秽色情',
            3 => '虚假中奖',
            4 => '广告营销',
            5 => '人身攻击',
            6 => '其他'
        );
        $choice = $arr[$choice];
        $_M = M('Comment');
        $data = $_M->field('report_num,report_content,report_user')->where(array('id' => $id))->find();
        if ($data['report_user']) {
            $report = explode(',', $data['report_user']);
            if (in_array($user_id, $report)) {
                $this->error('您已经举报过了!');
            }
            $report[] = $user_id;
            $report = implode(',', $report);
        } else {
            $report = is_login();
        }
        if ($data['report_content']) {
            $content = explode(',', $data['report_content']);
            if (!in_array($choice, $content)) {
                $content[] = $choice;
            }
            $content = implode(',', $content);
        } else {
            $content = $choice;
        }
        $rsl = $_M->where(array('id' => $id))->save(array(
            'report_num' => $data['report_num'] + 1,
            'report_user' => $report,
            'report_content' => $content,
        ));
        if ($rsl) {
            $this->success('举报成功!');
        } else {
            $this->error('举报失败,请重试!');
        }
    }

    /**
     * 添加评论
     */
    public function addComment() {
        $contnet = I('post.contnet', '');
        $pid = I('post.pid', 0, 'intval');
        $publish_id = I('publish_id', 0, 'intval');
        $top_id = I('post.top_id', 0, 'intval');
        $user_id = is_login();
        if (!$user_id) {
            cookie('redirectUrl', 'http://m.' . DOMAIN . '/Olympic/news_detail/id/' . $publish_id);
            $this->ajaxReturn(['status' => -1, 'info' => '请先登录!', 'url' => U('User/login')]);
        }
        if ($publish_id < 1) {
            $this->error('参数有误,请刷新重试!');
        }
        if ($contnet == '') {
            $this->error('请输入评论内容!');
        }
        if (mb_strlen($contnet, 'utf-8') > 255) {
            $this->error('最多只能输入255个字符');
        }
        $lastcomment = cookie('lastcomment');
        if (isset($lastcomment) && time() - $lastcomment < 30) {
            //评论30秒限制
            $this->error("30秒内不能多次评论哦！");
        }
        //查看是否禁言
        $user = M('frontUser')->where(['id' => $user_id])->field('nick_name,is_gag,head')->find();
        if ($user['is_gag'] == 1) {
            $this->error("您已被管理员禁言，请联系客服！");
        }
        //是否已达20评论数上限
        if ($pid != 0) {
            $by_user = M('comment c')->field('c.user_id,fu.nick_name')->where(array('c.id' => $pid))->join("qc_front_user fu ON c.user_id=fu.id")->find();
            $num = M('comment')->where(['pid' => $pid])->count();
            if ($num >= 20) {
                $this->error("已达评论数上限，请另外发表评论");
            }
        }

        //过滤内容
        $FilterWords = getWebConfig("FilterWords");
        foreach ($FilterWords as $key => $value) {
            $Words[] = '/' . $value . '/';
        }
        $data['filter_content'] = preg_replace($Words, '***', $contnet);
        $rs = M('comment')->add(array(
            'by_user' => $by_user['user_id'],
            'content' => $contnet,
            'filter_content' => $data['filter_content'],
            'top_id' => $top_id,
            'pid' => $pid,
            'publish_id' => $publish_id,
            'user_id' => $user_id,
            'platform' => 4,
            'create_time' => NOW_TIME,
            'reg_ip' => get_client_ip()
        ));
        if ($top_id) {
            $data['top_id'] = $top_id;
        } else {
            $data['top_id'] = $pid;
        }
        $data['by_name'] = $by_user['nick_name'];
        $data['pid'] = $pid;
        $data['id'] = $rs;
        $data['face'] = frontUserFace($user['head']);
        $data['create_time'] = format_date(NOW_TIME);
        $data['nick_name'] = $user['nick_name'];
        if ($rs) {
            //cookie('lastcomment', time(), 30);
            $this->success($data);
        } else {
            $this->error("发布评论失败,请稍后再试！");
        }
    }

    public function live() {
        //金牌资讯
        $adver = @Think\Tool\Tool::getAdList(21, 20, 4) ? : [];
        $this->assign('adver', $adver);
        $date = date('Ymd');
        $map['status'] = 1;
        $map['game_date'] = $date;
        $map['is_video'] = 1;
        $list = M('Olympics')->field('game_time,game_type,game_name,run_name')->where($map)->select();
        foreach ($list as &$v) {
            $v['game_type'] = str_replace("2016年里约奥运会", "", $v['game_type']);
            $v['game_type'] = str_replace("赛程", "", $v['game_type']);
        }
        //直播列表
        $type=I('get.type',2,'intval');
        $tv_arr=array(
            1=>['CCTV5','http://58.135.196.138:8090/live/6b9e3889ec6e2ab1a8c7bd0845e5368a/index.m3u8'],
            2=>['广东体育','http://125.88.92.166:30001/PLTV/88888956/224/3221227703/1.m3u8'],
            3=>['欧洲足球','http://aiseet.lszb.atianqi.com/app_4/ozzq.m3u8?bitrate=2000'],
        );
        $this->assign('tv', $tv_arr[$type]);
        $this->assign('list', $list);
        $this->assign('title', 'video');
        $this->display();
    }
    public function logout(){
        session('user_auth', null);
        redirect(U('User/login'));
    }

    public function catData() {
        $curlobj = curl_init();
        curl_setopt($curlobj, CURLOPT_URL, 'http://sports.weibo.com/olympics2016/medaltable/view/list');
        curl_setopt($curlobj, CURLOPT_HEADER, 0);
        curl_setopt($curlobj, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlobj, CURLOPT_ENCODING, "");
        $rtn = curl_exec($curlobj);
        if (curl_errno($curlobj) != 0) {
            echo 'false';
        }
        curl_close($curlobj);
        $contents = mb_convert_encoding($rtn, 'UTF-8', "auto");
        $listNumberStr = '/<tr>(.*?)<\/tr>/s';
        preg_match_all($listNumberStr, $contents, $list);
        $labelstr = '/<em.*?>(.*?)<\/em>/is';
        $pregname = '/<span.*?>(.*?)<\/span>/is';
        $data = array();
        foreach ($list[1] as $k => &$v) {
            $str = preg_replace('/<td.*?>/i', '', $v);
            $str = preg_replace('/<\/td>/i', '', $str);
            preg_match_all($labelstr, $str, $data[$k]);
            preg_match_all($pregname, $str, $name);
            $data[$k] = $data[$k][1];
            $data[$k]['name'] = $name[1];
            $data[$k]['img'] = Think\Tool\Tool::getTextImgUrl($v, false);
        }
        unset($data[0]);
        $_M = M('OlympicAllmedal');
        foreach ($data as $val) {
            $has = $_M->where(array('country' => $val['name'][0]))->find();
            if ($has) {
                $_M->where(array('country' => $val['name'][0]))->save(array(
                    'img' => $val['img'][0],
                    'ranking' => $val[0],
                    'gold' => $val[1],
                    'silver' => $val[2],
                    'copper' => $val[3],
                ));
            } else {
                $_M->add(array(
                    'img' => $val['img'][0],
                    'country' => $val['name'][0],
                    'ranking' => $val[0],
                    'gold' => $val[1],
                    'silver' => $val[2],
                    'copper' => $val[3],
                ));
            }
        }
    }

}
