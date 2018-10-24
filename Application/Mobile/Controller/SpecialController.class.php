<?php
/**
 * 图库列表
 */
use Think\Tool\Tool;

class SpecialController extends CommonController
{
    //定义专题页参数
    public $special = [];
    public $publishClass = [];
    protected function _initialize() {
        parent::_initialize();
        $this->publishClass = getPublishClass();
        $this->special = [
            'seriea'            => ['publishId' => 17, 'videoId' => 59, 'photoId' => 33, 'haveNav' => 1,'name'=>'意甲','sign'=>'seriea','domain'=>'seriea'],//意甲
            'bundesliga'        => ['publishId' => 15, 'videoId' => 58, 'photoId' => 32, 'haveNav' => 1,'name'=>'德甲','sign'=>'bundesliga','domain'=>'bundesliga'],//德甲
            'laliga'            => ['publishId' => 14, 'videoId' => 57, 'photoId' => 34, 'haveNav' => 1,'name'=>'西甲','sign'=>'laliga','domain'=>'laliga'],//西甲
            'csl'               => ['publishId' => 18, 'videoId' => 63, 'photoId' => 35, 'haveNav' => 1,'name'=>'中超','sign'=>'csl','domain'=>'csl'],//中超
            'championsleague'   => ['publishId' => 27, 'videoId' => 60, 'photoId' => 36, 'haveNav' => 1,'name'=>'欧冠','sign'=>'championsleague','domain'=>'championsleague'],//欧冠
            'afccl'             => ['publishId' => 28, 'videoId' => 61, 'photoId' => 37, 'haveNav' => 1,'name'=>'亚冠','sign'=>'afccl','domain'=>'afccl'],//亚冠
            'nba'               => ['publishId' => 4, 'videoId' => 64, 'photoId' => 39, 'haveNav' => 1,'name'=>'NBA','sign'=>'nba','domain'=>'nba'],//NBA
            'cba'               => ['publishId' => 3, 'videoId' => 65, 'photoId' => 40, 'haveNav' => 1,'name'=>'CBA','sign'=>'cba','domain'=>'cba'],//CBA
            'tennis'            => ['publishId' => 64, 'videoId' => 66, 'photoId' => 44, 'haveNav' => 0,'name'=>'网球','sign'=>'tennis','domain'=>'tennis'],//网球
            'snooker'           => ['publishId' => 66, 'videoId' => 68, 'photoId' => 46, 'haveNav' => 0,'name'=>'斯诺克','sign'=>'snooker','domain'=>'snooker'],//斯诺克
            'nfl'               => ['publishId' => 67, 'videoId' => 69, 'photoId' => 47, 'haveNav' => 0,'name'=>'橄榄球','sign'=>'nfl','domain'=>'nfl'],//橄榄球
            'pingpong'          => ['publishId' => 93, 'videoId' => 74, 'photoId' => 52, 'haveNav' => 0,'name'=>'乒乓球','sign'=>'pingpong','domain'=>'pingpong'],//乒乓球
            'vollyball'         => ['publishId' => 95, 'videoId' => 75, 'photoId' => 0, 'haveNav' => 0,'name'=>'排球','sign'=>'vollyball','domain'=>'vollyball'],//排球
            'lol'               => ['publishId' => 69, 'videoId' => 70, 'photoId' => 48, 'haveNav' => 0,'name'=>'英雄联盟','sign'=>'lol','domain'=>'lol'],//英雄联盟
            'pvp'               => ['publishId' => 72, 'videoId' => 73, 'photoId' => 51, 'haveNav' => 0,'name'=>'王者荣耀','sign'=>'pvp','domain'=>'pvp'],//王者荣耀
            'pubg'              => ['publishId' => 71, 'videoId' => 72, 'photoId' => 50, 'haveNav' => 0,'name'=>'绝地求生','sign'=>'pubg','domain'=>'pubg'],//绝地求生
            'dota2'             => ['publishId' => 70, 'videoId' => 71, 'photoId' => 49, 'haveNav' => 0,'name'=>'DOTA2','sign'=>'dota2','domain'=>'dota2'],//DOTA2
            'premierleague'     => ['publishId' => 13, 'videoId' => 56, 'photoId' => 31, 'haveNav' => 1,'name'=>'英超','sign'=>'premierleague','domain'=>'premierleague'],//英超
            '2018worldcup'      => ['publishId' => 96, 'videoId' => 77, 'photoId' => 38, 'haveNav' => 1,'name'=>'世界杯','sign'=>'2018worldcup','domain'=>'2018worldcup'],//世界杯
            'wuzhou'            => ['publishId' => 107, 'videoId' => 78, 'photoId' => 53, 'haveNav' => 0,'name'=>'五洲','sign'=>'wuzhou','domain'=>'wuzhou'],//五洲
            'ligue1'            => ['publishId' => 16, 'videoId' => 76, 'photoId' => 54, 'haveNav' => 0,'name'=>'法甲','sign'=>'ligue1','domain'=>'ligue1'],//五洲
        ];
    }

    public function index(){
        $type = explode('.',explode('/',$_SERVER['REQUEST_URI'])[1])[0];
        $specialConfig = $this->special[$type];
        $specialConfig['navUrl'] = $type;
        $this->setSeo($this->publishClass[$specialConfig['publishId']]);
        $this->assign('sConfig',$specialConfig);
        A('Mobile/Nav')->navHead($specialConfig['sign']);
        $this->assign('titleHead',$specialConfig['name']);
        $this->display();
    }

    //ajax获取专题页数据
    public function SpecialList()
    {
        //西甲
        $pcid = I('pubId',14,'int');
        $hcid = I('videoId',57,'int');
        $icid = I('imgId',34,'int');
        if(I('noData') == 1)
            $res = $this->getNoHeadList($pcid);
        else
            $res = $this->getList($pcid,$hcid,$icid);
        $data = ['code'=>200,'data'=>$res];
        $this->ajaxReturn($data);
    }

    //获取页面嵌入app时的数据
    public function getNoHeadList($id)
    {
        $classArr = getPublishClass(0);
        //获取手写位推荐
        $recData = M('Config')->field('config')->where(['sign'=>'news_2018worldcup'])->find();
        $recData = json_decode($recData['config'],true);
        $idData = $titleData = [];
        foreach($recData as $val)
        {
            $idData[] = $val['id'];
            $titleData[$val['id']] = $val['title'];
        }
        $hotList = M('PublishList')->field('id,title,top_recommend as is_top,click_number,img,add_time as time,content,class_id,web_recommend')->where(['id'=>['in',$idData]])->order('add_time desc,top_recommend desc')->limit(20)->select();
        //获取资讯列表
        //获取该分类下所有分类id
        $class_id = M('PublishClass')->where(['pid'=>$id])->getField('id',true);
        $class_id[] = $id;
        $where['class_id'] = ['in',$class_id];
        $where['add_time'] = ['lt', I('time', time(), 'int')];
        $where['id'] = ['not in', $idData];
        $page = I('page', 0, 'int');
        $where['status'] = 1;
        $twoList = M('PublishList')->field('id,title,top_recommend as is_top,img,click_number,add_time as time,content,class_id,web_recommend')->where($where)->order('add_time desc')->page($page . ',16')->select();
        $data = array_merge((array)$hotList,(array)$twoList);
        foreach ($data as $key => $val) {
            $data[$key]['title'] = $titleData[$val['id']]?:$val['title'];
            $data[$key]['img'] = newsImgReplace($val);
            $data[$key]['time'] = date('Y-m-d', $val['time']);
            $data[$key]['url'] = mNewsUrl($val['id'],$val['class_id'],$classArr);
            $data[$key]['click_number'] = addClickConfig(1, $val['class_id'],$val['click_number'], $val['id']);
            if ($val['is_top'] == 1 && $val['web_recommend'] > 0)
                $data[$key]['isHot'] = 1;
            else
                $data[$key]['isHot'] = 0;
            unset($data[$key]['content'], $data[$key]['is_top'], $data[$key]['web_recommend']);
        }
        $tmp = ['hot' => array_slice($data, 0, 3)?:0, 'video' => 0, 'listNd' => array_slice($data, 3, 6)?:0, 'photo' => 0, 'listRd' => array_slice($data, 9)?:0];
        return $tmp;
    }

    //获取专题页列表
    public function getList($pcid, $hcid, $icid)
    {
        //生成条件
        $where['status'] = 1;
        $classArr = getPublishClass(0);
        //获取该分类下所有分类id
        $class_id = M('PublishClass')->where(['pid'=>$pcid])->getField('id',true);
        $class_id[] = $pcid;
        $where['class_id'] = ['in',$class_id];
        //记录第一模块已使用的id
        $hotId = [];
        //第一模块图文资讯
        if (!$hotList = S('M_PublishList_' . $pcid)) {
            $hotList = M('PublishList')->field('id,title,top_recommend as is_top,click_number,img,add_time as time,content,class_id,web_recommend')->where($where)->order('top_recommend desc,add_time desc')->limit(20)->select();
            S('M_PublishList_' . $pcid, $hotList, 500);
        }
        shuffle($hotList);
        $hotList = array_slice($hotList, 0, 4);
        foreach ($hotList as $key => $val) {
            $hotId[] = $val['id'];
            $hotList[$key]['img'] = newsImgReplace($val);
            $hotList[$key]['time'] = date('Y-m-d', $val['time']);
            $hotList[$key]['url'] = mNewsUrl($val['id'],$val['class_id'],$classArr);
            $hotList[$key]['click_number'] = addClickConfig(1, $val['class_id'],$val['click_number'], $val['id']);
            if ($val['is_top'] == 1 && $val['web_recommend'] > 0)
                $hotList[$key]['isHot'] = 1;
            else
                $hotList[$key]['isHot'] = 0;
            unset($hotList[$key]['content'], $hotList[$key]['is_top'], $hotList[$key]['web_recommend']);
        }
        //获取第二模块图文
        $where['add_time'] = ['lt', I('time', time(), 'int')];
        $where['id'] = ['not in', $hotId];
        $page = I('page', 0, 'int');
        $twoList = M('PublishList')->field('id,title,top_recommend as is_top,img,click_number,add_time as time,content,class_id,web_recommend')->where($where)->order('add_time desc')->page($page . ',16')->select();
        foreach ($twoList as $key => $val) {
            $twoList[$key]['img'] = newsImgReplace($val);
            $twoList[$key]['time'] = date('Y-m-d', $val['time']);
            $twoList[$key]['url'] = mNewsUrl($val['id'],$val['class_id'],$classArr);
            $twoList[$key]['click_number'] = addClickConfig(1, $val['class_id'],$val['click_number'], $val['id']);
            if ($val['is_top'] == 1 && $val['web_recommend'] > 0)
                $twoList[$key]['isHot'] = 1;
            else
                $twoList[$key]['isHot'] = 0;
            unset($twoList[$key]['content'], $twoList[$key]['is_top'], $twoList[$key]['web_recommend']);
        }

        //获取视频列表
        $where = [];
        $where['status'] = 1;
        $where['class_id'] = $hcid;
        $where['m_url'] = ['NEQ',''];
        $where['user_id'] = ['NEQ','NULL'];
        if (!$videoList = S('M_VideoList_' . $hcid)) {
            $videoList = M('Highlights')->field('id,img,title,click_num as click_number,m_ischain,m_url,class_id')->where($where)->order('add_time desc')->limit(10)->select();
            S('M_VideoList_' . $hcid, $videoList, 500);
        }
        shuffle($videoList);
        $videoList = array_slice($videoList, 0, 2);
        foreach ($videoList as $key => $val) {
            //拼接url
            $videoList[$key]['url'] = $val['m_ischain'] == 1?$val['m_url']:U('/video/'.$val['id'].'@m');
            $videoList[$key]['click_number'] = addClickConfig(1, $val['class_id'],$val['click_number'], $val['id']);
            if (!empty($val['img'])) {
                $videoList[$key]['img'] = Think\Tool\Tool::imagesReplace($val['img']);
            } else {
                $videoList[$key]['img'] = staticDomain('/Public/Images/defalut/newsimg.jpg');
            }
        }

        //获取图库内容
        if($icid > 0)
        {
            $where = [];
            $where['status'] = 1;
            $where['class_id'] = $icid;
            if (!$photoList = S('M_ImgList' . $icid)) {
                $photoList = M('Gallery')->field('id,class_id,title,img_array,click_number,like_num,class_id')->where($where)->order('add_time desc')->limit(10)->select();
                S('M_ImgList' . $icid, $photoList, 500);
            }
            shuffle($photoList);
            $photoList = array_slice($photoList, 0, 2);
            foreach ($photoList as $key => $val) {
                $photoList[$key]['url'] = U('/photo/'.$val['id'].'@m');
                $photoList[$key]['imgTotal'] = count(json_decode($val['img_array'], true));
                $photoList[$key]['img'] = setImgThumb(json_decode($val['img_array'], true)[1], '240');
                $photoList[$key]['click_number'] = addClickConfig(1, $val['class_id'],$val['click_number'], $val['id']);
                unset($photoList[$key]['img_array']);
            }
        }
        //排列数据
        $data = ['hot' => $hotList?:0, 'video' => $videoList?:0, 'listNd' => array_slice($twoList, 0, 6)?:0, 'photo' => $photoList?:0, 'listRd' => array_slice($twoList, 6)?:0];
        return $data;
    }

    //资讯详情页
    public function news()
    {
        $id = I('id');
        C('HTTP_CACHE_CONTROL','no-cache,no-store');
        $this->assign("publishid", $id);
        $where['l.id'] = $id;

        $classArr = getPublishClass(0);
        $news = array();
        $list = M('PublishList l')
            ->join("LEFT JOIN qc_front_user f on f.id = l.user_id")
            ->field("l.odds,l.seo_title,l.seo_desc,l.seo_keys,l.status,l.handcp,l.odds_other,l.id,l.title,l.is_original,l.class_id,l.user_id,l.game_id,l.gamebk_id,l.remark,l.content,l.source,l.label,l.click_number,l.add_time,l.update_time,l.app_time,l.seo_title,l.seo_keys,l.seo_desc,f.nick_name,f.lv,f.head as face,f.descript,l.play_type,l.chose_side,l.result,f.is_expert")
            ->where($where)
            ->find();
        //获取顶级分类
        $c_id = $list['class_id'];
        $type = explode('/',$_SERVER['REQUEST_URI'])[1];
        $specialConfig = $this->special[$type];
		//判断点击更多跳转的地址
        if($specialConfig['haveNav'] === 1)
        {
            $this->assign('moreUrl',U('/'.$specialConfig['domain'].'/info'));
        }elseif($specialConfig['haveNav'] === 0){
            $this->assign('moreUrl',U('/'.$specialConfig['domain']));
        }
        //获取分类信息
        $newsClass = $classArr[$list['class_id']];
        $parentClass = $newsClass['pid'] != 0 ? $classArr[$newsClass['pid']] : $newsClass;
        //为空时默认general
        $domain = $parentClass['domain'] ? : 'general';
        if($classArr[$c_id]['pid'] == 111) $domain = '2018worldcup';
        //判断访问地址是否被修改
        if($domain != $type) 
            $this->_empty();

        if ($list['status'] != 1 && $list['user_id'] !== is_login()) {
            $this->_empty();
        }
        if($list['status'] == 1){
            //点击量加1
            M('PublishList')->where(array('id' => $id, 'status' => 1))->setInc('click_number');
        }
        
        $label = $list['label'];
        $is_noheader = I('header');
        $header = I('header');
        if(!$news = S($is_noheader.'mobile_news_'.$id.'_'.get_client_ip().$header)) //缓存5分钟
        {
            $list['face'] = (string)frontUserFace($list['face']);

            $list['label'] = $list['label'] ? explode(',', $list['label']) : '';
            //处理标签英文跳转连接
            if($list['label'] != '')
            {

                if(!$keyword = S('url_keyword_key_val'))
                {
                    $keyword = M('HotKeyword')->getField('keyword,url_name',true);
                    S('url_keyword_key_val',$keyword,500);
                }
                //查询关键字表已存在的数据
                $KeyRes = M('PublishKey')->where(['name'=>['in',$list['label']],'status'=>1])->getField('name,m_url',true);
                $label = [];
                foreach ($list['label'] as $kk=>$vv)
                {
                    $tmp     = [];
                    $tmp['name']   = $vv;
                    $url  = $KeyRes[$vv];
                    if($keyword[$vv]) $url = U('/tag/'.$keyword[$vv]);
                    if(!$url) $url = U('/tag/'.getPy($vv));
                    $tmp['url']   = $url;
                    $label[] = $tmp;
                }
                $list['label'] = $label;
            }

            //获取文章分类
            $list['specialName'] = $specialConfig['name'];

            $news['list'] = $list;


            if (($list['gamebk_id'] || $list['game_id']) && $list['class_id'] == "10") {
                $game_info = array();
                if (abs((int)$list['play_type']) == 1) {
                    $game_info['fsw_exp_home'] = $list['odds'];
                    $game_info['fsw_exp'] = $list['handcp'];
                    $game_info['fsw_exp_away'] = $list['odds_other'];
                } elseif (abs((int)$list['play_type']) == 2) {
                    $odds = json_decode($list['odds_other'], true);
                    $game_info['handcp'] = $list['handcp'];
                    $game_info = array_merge($game_info, $odds);
                }
                if ($list['game_id']) {
                    $M = M("GameFbinfo");
                    $game_info['game_id'] = $game_id = $list['game_id'];
                    $game_info['gtype'] = '1';
                } else {
                    $M = M("GameBkinfo");
                    $game_info['gamebk_id'] = $game_id = $list['gamebk_id'];
                    $game_info['gtype'] = '2';
                }
                $game = $M->field('home_team_name,away_team_name,union_name,gtime,game_state,score,home_team_id,away_team_id,bet_code')->where(['game_id' => $game_id])->find();

                if ($game['game_state'] == '-1') $game_info['score'] = $game['score'];
                $game_info['game_state'] = $game['game_state'];
                $game_info['gtime'] = $game['gtime'];
                $game_info['home_team_id'] = $game['home_team_id'];
                $game_info['away_team_id'] = $game['away_team_id'];
                $game_info['home_team_name'] = $game['home_team_name'];
                $game_info['away_team_name'] = $game['away_team_name'];
                $game_info['union_name'] = $game['union_name'];
                $game_info['bet_code'] = $game['bet_code'];
                $_tmp[] = $game_info;
                setTeamLogo($_tmp, $game_info['gtype']);
                $against = $_tmp[0];
            }

            $news['against'] = $against;
            $news['list']['content'] = htmlspecialchars_decode($news['list']['content']);
            if(I('header') != 'no') $news['list']['content'] = contKetToUrl($news['list']['content'],0);
            S($is_noheader.'mobile_news_'.$id.'_'.get_client_ip().$header,$news,C('newsCacheTime'));
        }
        if($type == 'general') $type = '';
        $this->assign('list',$news['list']);
        $this->assign('game',$news['against']);
        $this->assign('titleHead',$specialConfig['name']?:'新闻');
        $this->assign('user',['name'=>'正文']);

        $type = explode('.',explode('/',$_SERVER['REQUEST_URI'])[1])[0];
        if($type == 'sporttery') $type = 'sporttery/dujia';
        $this->assign('urlHead','//m.'.DOMAIN.'/'.$type.'.html');
        $this->assign('sConfig',['url'=>$type]);

        //seo
        $this->setSeo([
            'seo_title' => $list['seo_title'] ?: $list['title'].'_'.$list['name'].'新闻专题频道'.'_全球体育手机网',
            'seo_keys'  => $list['seo_keys']  ?: $label,
            'seo_desc'  => $list['seo_desc']  ?: str_replace(' ', '', msubstr(strip_tags(htmlspecialchars_decode($list['content'])), 0, 150)),
        ]);
        $this->display();
    }

    //资讯标签列表
    public function newsList(){
        $class_id = I('key');
        //获取分类列表
        //将英文标签转换为中文
        if(!$keyword = S('url_keyword_key_val'))
        {
            $keyword = M('HotKeyword')->getField('keyword,url_name',true);
            S('url_keyword_key_val',$keyword,500);
        }
        if($keyword[$class_id]) $this->redirect('/video/'.$keyword[$class_id]);
        $keyword = array_flip($keyword);
        $class_id = $keyword[$class_id]?$keyword[$class_id]:$class_id;
        $this->assign('key',$class_id);
        $this->assign('titleHead','"'.$class_id.'"相关资讯');
        $this->display();
    }

    //ajax异步请求标签列表
    public function  getNewsList()
    {
        $page = I('page') ?: 1;
        $limit = 12;
        $key = I('keyWord');
        $where['p.add_time'] = ['lt',I('time',time(),'int')];
        //标签搜索
        $where['p.label'] = ['like','%'.urldecode($key).'%'];
        $where['p.status']   = 1;
        $list = M('PublishList p')
            ->join("LEFT JOIN qc_publish_class c on c.id = p.class_id")
            ->field('p.id,p.class_id,p.title,p.add_time,p.click_number,p.img,c.domain,c.pid')
            ->where($where)
            ->order('p.add_time DESC,p.click_number DESC')
            ->page($page . ',' . $limit)
            ->select();
        $classArr  = getPublishClass(0);
        foreach($list as $key=>$val)
        {
            $list[$key]['img'] = newsImgReplace($val);
            $list[$key]['add_time'] = date('Y-m-d', $val['add_time']);
            $list[$key]['click_number'] = addClickConfig(1, $val['class_id'],$val['click_number'], $val['id']);
            $list[$key]['href'] = mNewsUrl($val['id'],$val['class_id'],$classArr);
        }
        if($list)
            $data = ['code'=>200,'data'=>$list];
        else
            $data = ['code'=>201,'msg'=>'暂无数据!!'];
        $this->ajaxReturn($data);
    }

    public function getMoreList()
    {
        $pid = I('pid');
        $inId = I('inId');
        $notIn = array_filter(explode('+',$inId));
        $where['id'] = ['not in',$notIn];
//        $where['stauts'] = 1;
        $class_id = M('PublishClass')->where(['pid'=>$pid])->getField('id',true);
        $class_id[] = $pid;
        $where['class_id'] = ['in',$class_id];

        $page = I('page') ?: 1;
        $limit = 10;
        $where['add_time'] = ['lt',I('time',time(),'int')];
        $list = M('PublishList')
            ->field('id,title,top_recommend as is_top,click_number,img,add_time as time,content,class_id,web_recommend')
            ->where($where)
            ->where(['status'=>1])
            ->order('add_time DESC')
            ->page($page . ',' . $limit)
            ->select();
        if($list)
        {
            $classArr = getPublishClass(0);
            foreach ($list as $key => $val) {
                $list[$key]['img'] = newsImgReplace($val);
                $list[$key]['time'] = date('Y-m-d', $val['time']);
                $list[$key]['click_number'] = addClickConfig(1, $val['class_id'],$val['click_number'], $val['id']);
                $list[$key]['url'] = mNewsUrl($val['id'],$val['class_id'],$classArr);
                if ($val['is_top'] == 1 && $val['web_recommend'] > 0)
                    $list[$key]['isHot'] = 1;
                else
                    $list[$key]['isHot'] = 0;
                unset($list[$key]['content'], $list[$key]['is_top'], $list[$key]['web_recommend'],$list[$key]['class_id']);
            }
            $data = ['code'=>200,'data'=>$list];
        }else{
            $data = ['code'=>201,'msg'=>'暂无数据!!'];
        }
        $this->ajaxReturn($data);
    }

    //获取资讯底部资讯列表
    public function getButtomList(){
        $pcid = I('class_id',0,'int');
        if($pcid < 1) $this->ajaxReturn(['code'=>202,'msg'=>'参数错误!!']);
        $page = I('page',0,'int');
        $limit = 10;
        //获取相关内容
        $class_id = M('PublishClass')->where(['pid'=>$pcid])->getField('id',true);
        $class_id[] = $pcid;
        $where['class_id'] = ['in',$class_id];
        $where['status'] = 1;
        $where['add_time'] = ['lt',I('time',time(),'int')];
        $list = M('PublishList')
            ->field('id,title,top_recommend as is_top,click_number,img,add_time as time,content,class_id,web_recommend')
            ->where($where)
            ->order('add_time DESC')
            ->page($page . ',' . $limit)
            ->select();
        //获取专题地址标识
        $classArr = getPublishClass(0);
        if($list)
        {
            foreach ($list as $key => $val) {
                if($classArr[$val['class_id']]['pid'] == 111)
                    $class_id = 96;
                else
                    $class_id = $val['class_id'];
                $list[$key]['img'] = newsImgReplace($val);
                $list[$key]['time'] = date('Y-m-d', $val['time']);
                $list[$key]['url'] = mNewsUrl($val['id'],$class_id,$classArr);
                $list[$key]['click_number'] = addClickConfig(1, $val['class_id'],$val['click_number'], $val['id']);
                if ($val['is_top'] == 1 && $val['web_recommend'] > 0)
                    $list[$key]['isHot'] = 1;
                else
                    $list[$key]['isHot'] = 0;
                unset($list[$key]['content'], $list[$key]['is_top'], $list[$key]['web_recommend'],$list[$key]['class_id']);
            }
            $data = ['code'=>200,'data'=>$list];
        }else{
            $data = ['code'=>201,'msg'=>'暂无数据!!'];
        }
        $this->ajaxReturn($data);
    }

    public function articleList() {
        $class_id = I('get.class_id');
        $classArr = getPublishClass(0);
        $limit = 20;
        $where['pl.status']   = 1;
        $where['pl.class_id'] = $class_id;
        //分类
        $list = M('PublishList')->alias('pl')
                ->field('pl.id,pl.class_id,img,title,is_original,label,source,remark,content')
                ->where($where)
                ->order($this->_order)
                ->limit($limit)->select();
        foreach ($list as &$v) {
            $v['url'] = mNewsUrl($v['id'],$v['class_id'],$classArr);
            $v['img'] = newsImgReplace($v);
            if(empty($v['remark'])){
                $v['remark'] = str_replace(',', ' ', $v['label']);
            }
        }
        //资讯标题
        $newsClass = $classArr[$class_id];
        $seo_title = $newsClass['name'].'资讯';
        $this->assign('seo_title', $seo_title);
        //上级链接
        $parentClass = $classArr[$newsClass['pid']];
        $parentUrl = U('/'.$parentClass['domain'].'@m');
        $this->assign('parentUrl', $parentUrl);
        $this->assign('class_id', $class_id);
        $this->assign('list', $list);
        $this->display();
            
    }

    public function loadMore() {
        $classArr = getPublishClass(0);
        $p = isset($_POST['k']) ? intval(trim($_POST['k'])) : 0;
        $class_id = I('class_id') ? : 10;

        $where['pl.status'] = 1;
        $where['class_id'] = $class_id;
        $total = M('PublishList')->alias('pl')->where($where)->count(); //数据记录总数
        $num = 20; //每页记录数
        $totalpage = ceil($total / $num); //总计页数
        $limitpage = ($p - 1) * $num; //每次查询取记录

        if ($p > $totalpage) {
            //超过最大页数，退出
            $this->error("没有更多了");
        }
        $_M = M('PublishList');
        if($class_id==10){
            $list = $_M->alias('pl')->field('pl.id,pl.class_id,fu.head img,user_id,nick_name,source,title,is_original,remark')->join('left join qc_front_user fu on pl.user_id=fu.id')
                            ->where($where)
                            ->order($this->_order)
                            ->limit($limitpage, $num)->select();
            foreach ($list as  &$value) {
                $value['img']=frontUserFace($value['img']);
                $value['url'] = mNewsUrl($value['id'],$value['class_id'],$classArr);
            }
            $this->success($list);
        }else{
            $list = $_M->alias('pl')->field('pl.id,pl.class_id,img,title,is_original,source,label,remark,content')
                            ->where($where)
                            ->order($this->_order)
                            ->limit($limitpage, $num)->select();
        }
        if ($list) {
            foreach ($list as &$v) {
                $v['url'] = mNewsUrl($v['id'],$v['class_id'],$classArr);
                if(!empty($v['img'])){
                    $v['img'] = Tool::imagesReplace($v['img']);
                }else{
                    //获取第一张图片
                    $v['img']  = Tool::getTextImgUrl(htmlspecialchars_decode($v['content']),false)[0]?:staticDomain('/Public/Images/defalut/newsimg.jpg');
                }
                $v['title'] = msubstr($v['title'],0,26);
                if(empty($v['remark'])){
                    $v['remark'] = str_replace(',', ' ', $v['label']);
                }
            }
            $this->success($list);
        } else {
            $this->error("没有更多了");
        }
    }
}