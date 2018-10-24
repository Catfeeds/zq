<?php
/**
 * @杯赛预测
 * @author liuweitao 20180521
 */

use Think\Tool\Tool;

class WorldCupController extends CommonController
{

    //文章首页
    public function index()
    {
        //获取导航
        $nav = D('Home')->getNavList(42);
        $this->assign('nav', $nav);

        //获取球队直通车数据
        $navData = M('Nav')->field('id,name,sign,ui_type_value as url,icon')->where(['type' => 43, 'status' => 1])->order('sign,sort')->select();
        $navList = [];
        foreach ($navData as $val) {
            if (count($navList[$val['sign']]) < 5) {
                $val['icon'] = Tool::imagesReplace($val['icon']);
                $navList[$val['sign']][] = $val;
            }
        }
        $this->assign('navList', $navList);

        //获取杯赛预测轮播数据
        $CarouselData = $this->CarouselData();
        $this->assign('carousel', $CarouselData);

        //获取首页横幅广告图
        $ad = S('worldCup_AdCarousel');
        if (!$ad) {
            $ad = Tool::getAdList(117, 1);
            S('worldCup_AdCarousel', json_encode($ad), 60);
        }
        $this->assign('ad', $ad[0]);

        //获取杯赛推荐文章列表
        $time = time();
        $recList = $this->getRecommend(1, $time);
        $this->assign('recList', $recList);
        $this->assign('listTime', $time);

        //大咖推荐
        $daka = $this->getDakaList();
        $this->assign('daka', $daka);

        //大咖文章
        $dakaNew = $this->getDakaNew();
        $this->assign('dakaNew', $dakaNew);
        $redNav = ['319','324','327','331','335','339','342','350'];
        $this->assign('redNav', $redNav);

        $this->assign('linkArr', $linkArr);

        //设置seo
        $publshClass = M('PublishClass')->where(['id' => 111])->find();
        $seo = [
            'seo_title' => $publshClass['seo_title'],
            'seo_keys' => $publshClass['seo_keys'],
            'seo_desc' => $publshClass['seo_desc'],
        ];
        $this->setSeo($seo);
        $this->display('WorldCup/index');
    }

    //大咖推荐数据
    public function getDakaList()
    {
        //竞猜列表
        $yapan = $this->Daka_rank();
        //竞猜列表
        $jincai = $this->Daka_rank_bet();
        return ['ya' => $yapan, 'jin' => $jincai];
    }

    //大咖推荐亚盘列表
    public function Daka_rank()
    {
        $map['game_type'] = $where['game_type'] = 1;
        $map['list_date'] = $where['list_date'] = date('Ymd', strtotime("-1 day"));
        //获取昨日红人榜
        $is_has = M('redList')->where($where)->count();

        if (!$is_has) $map['list_date'] = date('Ymd', strtotime("-2 day"));
        $map['gamble_num'] = 5;
        $Ranking = M('red_list r')
            ->field("r.user_id,r.id,r.ranking,r.win,r.half,r.level,r.transport,r.donate,r.winrate,r.pointCount,f.head,f.lv,f.lv_bk,f.nick_name")
            ->join('left join qc_front_user f on f.id = r.user_id')
            ->where($map)
            ->order('ranking asc')
            ->limit(8)
            ->select();

        foreach ($Ranking as $k => $v) //处理数据
        {
            $where['user_id'] = $v['user_id'];
            $where['play_type'] = ['in', [1, -1]];
            $gamble = M('Gamble')->field('home_team_name,away_team_name,play_type')->where($where)->order('id desc')->find();
            $Ranking[$k]['home_team_name'] = switchName(0, $gamble['home_team_name']);
            $Ranking[$k]['away_team_name'] = switchName(0, $gamble['away_team_name']);
            //获取粉丝数量
            $Ranking[$k]['follow'] = M('followUser')->where(array('follow_id' => $v['user_id']))->count();
            //获取头像
            $Ranking[$k]['face'] = frontUserFace($v['head']);
        }
        return $Ranking;

    }

    //大咖推荐竞猜列表
    public function Daka_rank_bet()
    {
        $map['gameType'] = $where['gameType'] = 1;
        $map['dateType'] = $where['dateType'] = 4;
        $map['listDate'] = $where['listDate'] = date('Ymd', strtotime("-1 day"));
        //查看是否有上周/月/季的数据
        $is_has = M('rankBetting')->where($where)->count();
        if (!$is_has) $map['listDate'] = date('Ymd', strtotime("-2 day"));
        $map['gamble_num'] = 5;
        $Ranking = M('rankBetting r')
            ->field("r.user_id,r.id,r.ranking,r.gameCount,r.win,r.transport,r.winrate,r.pointCount,f.head,f.lv_bet,f.nick_name")
            ->join('left join qc_front_user f on f.id = r.user_id')
            ->where($map)
            ->group('r.user_id')
            ->order('r.ranking')
            ->limit(8)
            ->select();

        foreach ($Ranking as $k => $v) //处理数据
        {
            $where['user_id'] = $v['user_id'];
            $where['play_type'] = ['in', [2, -2]];
            $gamble = M('Gamble')->field('home_team_name,away_team_name,play_type')->where($where)->order('id desc')->find();
            $Ranking[$k]['home_team_name'] = switchName(0, $gamble['home_team_name']);
            $Ranking[$k]['away_team_name'] = switchName(0, $gamble['away_team_name']);
            //获取粉丝数量
            $Ranking[$k]['follow'] = M('followUser')->where(array('follow_id' => $v['user_id']))->count();
            //获取头像
            $Ranking[$k]['face'] = frontUserFace($v['head']);
        }
        return $Ranking;
    }

    //获取轮播数据
    public function CarouselData()
    {
        $AdCarousel = S('web_worldCup_AdCarousel');
        if (!$AdCarousel) {
            $AdCarousel = Tool::getAdList(116, 5, 1);
            $tmp = [];
            foreach ($AdCarousel as $val) {
                $tmp[$val['sort']] = $val;
            }
            //定义需要展示的数据规则
            $worldPhoto = [1 => 1, 2 => 2, 3 => 3];
            $womanPhoto = [4 => 4, 5 => 5];
            for ($i = 1; $i <= 5; $i++) {
                if (!$tmp[$i]) {
                    if (in_array($i, $worldPhoto)) {
                        $limit = 4 - count($worldPhoto);
                        $worldTmp = M('Gallery')
                            ->alias('G')
                            ->field('G.id,G.class_id,G.title,G.img_array,G.click_number,G.like_num,G.add_time,C.path')
                            ->where(['G.status' => 1, 'G.class_id' => 38])
                            ->join('LEFT JOIN qc_gallery_class C ON  C.id = G.class_id')
                            ->order('G.add_time DESC,G.like_num DESC')
                            ->page($limit . ',1')
                            ->find();
                        $worldTmp['img'] = Tool::imagesReplace(json_decode($worldTmp['img_array'], true)[1]);
                        $worldTmp['url'] = U('/' . $worldTmp['path'] . '/' . date('Ymd', $worldTmp['add_time']) . '/' . $worldTmp['id'] . '@photo', '', 'html');
                        $worldTmp['sort'] = $i;
                        $tmp[$i] = $worldTmp;
                        unset($worldPhoto[$i]);
                    } else {
                        $limit = 3 - count($womanPhoto);
                        $map['G.status'] = 1;
                        $map['G.class_id'] = ['in', '42,21,18,17,5,4'];
                        $womanTmp = M('Gallery')
                            ->alias('G')
                            ->field('G.id,G.class_id,G.title,G.img_array,G.click_number,G.like_num,G.add_time,C.path')
                            ->where($map)
                            ->join('LEFT JOIN qc_gallery_class C ON  C.id = G.class_id')
                            ->order('G.add_time DESC,G.like_num DESC')
                            ->page($limit . ',1')
                            ->find();
                        $womanTmp['img'] = Tool::imagesReplace(json_decode($womanTmp['img_array'], true)[1]);
                        $womanTmp['url'] = U('/' . $womanTmp['path'] . '/' . date('Ymd', $womanTmp['add_time']) . '/' . $womanTmp['id'] . '@photo', '', 'html');
                        $womanTmp['sort'] = $i;
                        $tmp[$i] = $womanTmp;
                        unset($womanPhoto[$i]);
                    }
                }
            }
            $Arr = get_arr_column($tmp, 'sort');
            array_multisort($Arr, SORT_ASC, $tmp);
            $AdCarousel = $tmp;
            $AdCarousel = array_values($AdCarousel);
            S('web_worldCup_AdCarousel', json_encode($AdCarousel), 60);
        }
        return $AdCarousel;
    }

    //ajax获取杯赛推荐数据
    public function recommend()
    {
        $page = I('page');
        $time = I('time');
        $data = $this->getRecommend($page, $time);
        if ($data)
            $tmp = ['code' => 200, 'data' => $data];
        else
            $tmp = ['code' => 201];
        $this->ajaxReturn($tmp);
    }

    //查询杯赛推荐数据
    public function getRecommend($p = 1, $time = NOW_TIME)
    {
        $limit = 10;
        $map['add_time'] = ['lt', $time];
        $map['status'] = 1;
        $map['is_cup'] = 1;
        $count = M('PublishList')->where($map)->count();
        $data = M('PublishList')->field("id,class_id,title,img,short_title,add_time")->where($map)->order('add_time desc')->page($p)->limit($limit)->select();
        if ($data) {
            $classArr = getPublishCLass(0); //资讯分类数组
            foreach ($data as $k => $v) {
                $data[$k]['img'] = newsImgReplace($v);
                $data[$k]['href'] = newsUrl($v['id'], $v['add_time'], $v['class_id'], $classArr);
                $data[$k]['vol'] = $count - (($p - 1) * $limit + $k);
            }
        } else {
            $data = [];
        }
        return $data;
    }

    //查询大咖文章
    public function getDakaNew()
    {
        $limit = 8;
        $dakaArrId = [];
        $map['status'] = 1;
        $map['is_cup'] = 0;
        $map['class_id'] = 111;
        $data = M('PublishList')->field("id,class_id,title,img,add_time")->where($map)->order('add_time desc')->limit($limit)->select();
        $classArr = getPublishCLass(0); //资讯分类数组
        foreach ($data as $k => $v) {
            $data[$k]['img'] = newsImgReplace($v);
            $data[$k]['href'] = newsUrl($v['id'], $v['add_time'], $v['class_id'], $classArr);
        }
        $fbNewNum = $limit - count($data);
        if ($fbNewNum > 0) {
            $map['class_id'] = 10;
            $map['game_id'] = array('exp', 'is not null');
            $fbNew = M('PublishList')->field("id,class_id,title,img,add_time")->where($map)->order('add_time desc')->limit($fbNewNum)->select();
            foreach ($fbNew as $k => $v) {
                $fbNew[$k]['img'] = newsImgReplace($v);
                $fbNew[$k]['href'] = newsUrl($v['id'], $v['add_time'], $v['class_id'], $classArr);
            }
        }
        $data = array_merge((array)$data, (array)$fbNew);
        return $data;
    }

    //球队直通车
    public function teamInfo($path)
    {
        $nav_id = M('PublishClass')->field('remark,id,seo_title,seo_keys,seo_desc')->where(['path' => $path, 'pid' => 111])->find();
        if(!$nav_id) parent::_empty();
        //获取导航
        $nav = D('Home')->getNavList(42);
        $this->assign('nav', $nav);

        //获取球队直通车数据
        $navData = M('Nav')->field('id,name,sign,ui_type_value as url,icon,icon2 as bg')->where(['type' => 43, 'status' => 1])->order('sign,sort')->select();
        $navList = $navGoupArr = [];
        foreach ($navData as $val) {
            if ($val['id'] == $nav_id['remark']) {
                $nav_goup = $val['sign'];
                $bg = Tool::imagesReplace($val['bg']);
            }
            $navGoupArr[$val['sign']] = $val['sign'];
            if (count($navList[$val['sign']]) < 5) {
                $val['icon'] = Tool::imagesReplace($val['icon']);
                $val['bg'] = Tool::imagesReplace($val['bg']);
                $navList[$val['sign']][] = $val;
            }
        }
        $this->assign('nav_goup', $nav_goup);
        $this->assign('nav_id', $nav_id['remark']);
        $this->assign('navGoupArr', $navGoupArr);
        $this->assign('bg', $bg);
        $this->assign('navList', $navList);

        //获取文章列表
        $newList = $this->getTeamInfoNew($nav_id['id']);
        $this->assign('newList', $newList);

        //判断当前页面球队
        $ex_path = explode('/', $_SERVER['PATH_INFO'])[1];
        $this->assign('bgphoto', $ex_path);

        //设置seo
        $seo = [
            'seo_title' => $nav_id['seo_title'],
            'seo_keys' => $nav_id['seo_keys'],
            'seo_desc' => $nav_id['seo_desc'],
        ];
        $this->setSeo($seo);
        $this->display('WorldCup/teamInfo');
    }

    //获取文章列表
    public function getTeamInfoNew($id)
    {
        //定义分组数据
        $up = ['阵容' => '', '成绩' => '', '深度' => ''];
        $down = ['人物' => '', '媒体预测' => '', '盘口数据' => ''];
        $list = M('PublishList')->field('id,title,remarks as type,class_id,img,add_time')->where(['class_id' => $id, 'status' => 1])->order('add_time')->select();
        if ($list) {
            $classArr = getPublishClass(0);
            foreach ($list as $key => $value) {
                $list[$key]['img'] = newsImgReplace($value);
                $list[$key]['href'] = newsUrl($value['id'], $value['add_time'], $value['class_id'], $classArr);
                $up[$value['type']] = $list[$key];
                $down[$value['type']] = $list[$key];
            }
        }
        $up = array_slice($up, 0, 3);
        $down = array_slice($down, 0, 3);
        $data = array_merge((array)$up, (array)$down);
        return $data;
    }

    //投票页
    public function schedule()
    {
        //友情链接
        if (!$linkArr = S('web_index_link')) {
            $linkArr = M('link')->field('name, url')->where(['status' => 1, 'position' => 1])->order('sort asc')->select();
            S('web_index_link', json_encode($linkArr), 600);
        }

        $this->assign('linkArr', $linkArr);
        //设置seo
        $seo = [
            'seo_title' => '世界杯小组赛_2018世界杯小组赛预测_全球体育',
            'seo_keys' => '世界杯小组赛,世界杯小组赛预测,世界杯小组赛分组,俄罗斯世界杯小组赛',
            'seo_desc' => '全球体育全局预测频道主要是为您提供2018俄罗斯世界杯小组对阵淘汰等相关预测，欢迎浏览！',
        ];
        $this->setSeo($seo);
        $this->display('WorldCup/schedule');
    }

    //获取票数信息
    public function getVote()
    {
        $id = I('id', 1, 'int');
        if ($id > -1) {
            //获取投票信息
            $data = M('Config')->where(['sign' => 'fifaNum'])->getField('config');
            $data = json_decode($data);
            $data[$id] = $data[$id] + 6;
            M('Config')->where(['sign' => 'fifaNum'])->save(['config' => json_encode($data)]);
        }
        $data = M('Config')->where(['sign' => 'fifaNum'])->getField('config');
        $data = $tmp = json_decode($data);
        sort($tmp);
        $max = end($tmp);
        $res = [];
        foreach ($data as $val) {
            $tmp = [];
            $tmp[] = $val;
            $tmp[] = round(($val / $max), 2) * 100;
            $res[] = $tmp;
        }
        $this->ajaxReturn($res);
    }
}

?>