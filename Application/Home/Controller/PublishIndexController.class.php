<?php
/**
 * 新闻资讯管理控制器
 *
 * @author dengweijun <406516482@qq.com>
 *
 * @since  2016-3-14
 */
use Think\Tool\Tool;
class PublishIndexController extends CommonController {

    /**
     * 亚盘 竞猜 北单 页面控制器
     */
    public function publishClass($class_id)
    {
        $class_id = $class_id?:I('class_id');
        $filter = I("filter");
        $request_p = I("p");
        if (!empty($request_p)) {
            if (((int) $request_p) === 0 || preg_match("/^\d+$/", $request_p) < 1) {
                $this->error("非法操作");
            }
        }

        $where['p.status']   = 1;
        $classArr  = getPublishClass(0);
        //分类
        $newsClass = $classArr[$class_id];
        //父级分类
        $parentClass = $newsClass['pid'] != 0 ? $classArr[$newsClass['pid']] : $newsClass;
        $parentClass['href'] = $parentClass['domain'] != '' ? U('@'.$parentClass['domain']) : newsClassUrl($class_id,$classArr);
        if($parentClass['domain']){
            $domain = explode('.', $_SERVER['HTTP_HOST'])[0];
            if($domain != $parentClass['domain']) parent::_empty();
        }
        //栏目链接
        if(strpos($_SERVER['PATH_INFO'],'tag/') !== false)
            $pageUrl = '//www.'.DOMAIN.'/'.$_SERVER['PATH_INFO'];
        else
            $pageUrl = newsClassUrl($class_id,$classArr);
        //旧资讯栏目链接301跳转
        if(stripos($_SERVER['REQUEST_URI'], 'list_n')){
            redirect301($pageUrl);
        }
        $this->assign('parentClass',$parentClass);
        //获取分类列表
        if(is_numeric($class_id))
        {
            $PublishClassIds = Tool::getAllSubCategoriesID( $classArr, $class_id );
            $className = $newsClass['name'];
            $where['class_id'] = array( 'in', $PublishClassIds );
            $class_flag = $newsClass['path'] ? : 'news';
        }
        else
        {
            //标签搜索
            if(!empty($class_id)){
                $regex = "/\(|\)|\{|\}|\<|\>|\?|\'|\=/";
                $boolean = preg_match($regex, $class_id );
                if ($boolean) {
                    $this->error("非法操作");
                }
                $class_id = strip_tags($class_id);
                $class_flag = $class_id;
                //将英文标签转换为中文
                if(!$keyword = S('url_keyword_key_val'))
                {
                    $keyword = M('HotKeyword')->getField('keyword,url_name',true);
                    S('url_keyword_key_val',$keyword,500);
                }
                if($keyword[$class_id]) $this->redirect('/tag/'.$keyword[$class_id]);
                $keyword = array_flip($keyword);
                $class_id = $keyword[$class_id]?$keyword[$class_id]:$class_id;
                $where['p.label'] = ['like','%'.urldecode($class_id).'%'];
                $className = '"'.urldecode($class_id)."\"相关资讯";
                $this->assign('isSearch', 1);
                
            }
        }
//
        $this->assign('className', $className);
        $this->assign("class_flag", $class_flag);

        if ($filter != null){
            if ($filter == 0 && $filter != "B1" && $filter != "B5") {
                $list = $this->_list(M('PublishList p'),$where,'20','p.update_time desc','','',$pageUrl."?p=%5BPAGE%5D");
            }else {
                $list = $this->expert_list(M('PublishList p'),$where,'20','p.update_time desc','','',$pageUrl."?p=%5BPAGE%5D", $filter);
            }
        } else {
            if ($where['p.label'] != null) {
                $list = $this->_list(M('PublishList p'),$where,'20','p.update_time desc','','',$pageUrl."?p=%5BPAGE%5D");
            } else {
                $list = $this->_list(M('PublishList p'),$where,'20','p.update_time desc','','',$pageUrl."?p=%5BPAGE%5D");
            }
        }
        $userIdArr = array_map(function ($element){return $element['user_id'];},$list);
        $userInfo = M('FrontUser')->where(['id'=>['IN',array_unique($userIdArr)]])->field('id,nick_name,head')->select();

        foreach ($list as $key => $value) {
            foreach ($userInfo as $k => $v)
            {
                if ($v['id'] == $value['user_id'])
                {
                    $list[$key]['nick_name']  = $v['nick_name'];
                    $list[$key]['head'] = $v['head'];
                }
            }
            $list[$key]['head']   = frontUserFace($list[$key]['head']);
            $list[$key]['img']    = newsImgReplace($value);
            $list[$key]['label']  = explode(',', $value['label']);
            $list[$key]['remark'] = $value['remark'] ? : msubstr(strip_tags(htmlspecialchars_decode($value['content'])), 0, 90);
            //增加资讯点击量的默认值
            $list[$key]['click_number'] = addClickConfig(1, $value['class_id'], $value['click_number'], $value['id']);
            if(empty($list[$key]['app_time'])) $list[$key]['app_time'] = $list[$key]['update_time'];

            $list[$key]['href'] = newsUrl($value['id'], $value['add_time'], $value['class_id'], $classArr);
        }
        $this->assign('list', $list);

        //获取独家解盘分类
        $quiz = M('publishList')->where(['class_id'=>10,'status'=>1,'is_original'=>1])->field("id,title,is_original,class_id,add_time")->order("add_time desc")->limit(10)->select();
        foreach ($quiz as &$v) {
            $v['href'] = newsUrl($v['id'], $v['add_time'], $v['class_id'], $classArr);
        }
        $this->assign('quiz',$quiz);

        //获取推荐指数
        $Marvellous = A('Index')->getMarvellous();
        $this->assign('Marvellous', $Marvellous);

        //获取视频推荐集锦
        $highlights = $this->getRecommendJJ();
        $this->assign('highlights',$highlights);

        if($class_flag == 'dujia'){
            $this->assign("union_name", $this->getOrderUnion());
        }

        //获取视频直播
        $this->assign("web_video", $this->getWebVideo());
        //设置seo
        $this->setSeo($newsClass);

        $this->display('PublishIndex/publishClass');
    }

    //新闻资讯主页
    public function index()
    {
        header('X-Frame-Options: deny');
        //导航栏1
        $topNav = D('Home')->getNavList(4, 'name, ui_type_value as url');
        $this->assign('topNav', $topNav);

        //导航栏2
        $topNav2 = D('Home')->getNavList(26, 'name, ui_type_value as url');
        $this->assign('topNav2', $topNav2);

        //导航栏3
        $topNav3 = D('Home')->getNavList(27, 'name, ui_type_value as url');
        $this->assign('topNav3', $topNav3);

        //导航栏4
        $topNav4 = D('Home')->getNavList(28, 'name, ui_type_value as url');
        $this->assign('topNav4', $topNav4);

        //导航栏5
        $topNav5 = D('Home')->getNavList(29, 'name, ui_type_value as url');
        $this->assign('topNav5', $topNav5);

        //第一屏和第二屏中间banner：Web首页广告1
        if(!$bigBanner = S('web_index_big_banner')) {
            $bigBanner = Tool::getAdList(45, 1);

            S('web_index_big_banner', json_encode($bigBanner), 60*5);
        }
        $this->assign('bigBanner', $bigBanner);

        //第二屏足球栏目配置
        if(!$fbNav = S('web_index_fb_nav')) {
            $fbNav = D('Home')->getNavList(5, 'name, ui_type_value as url');

            S('web_index_fb_nav', json_encode($fbNav), 60*20);
        }
        $this->assign('fbNav', $fbNav);

        //第二屏：国际足球，左边资讯
        if(!$newsTwo = S('web_index_new_two')) {
            $newsTwo = D('Home')->getIndexNewsTwo();

            S('web_index_new_two', json_encode($newsTwo), 60*5);
        }
        $this->assign('newsTwo', $newsTwo);

        //第二屏中间
        if(!$newsTwoMiddle = S('web_index_new_two_middle')) {
            $newsTwoMiddle = D('Home')->getIndexNewsTwoMiddle();

            S('web_index_new_two_middle', json_encode($newsTwoMiddle), 60*5);
        }
        $this->assign('newsTwoMiddle', $newsTwoMiddle);

        //第二屏右边，积分榜，射手榜，活动专题
        if(!$newsTwoRight = S('web_index_new_two_right')) {
            $newsTwoRight = D('Home')->getIndexNewsTwoRight(true);

            S('web_index_new_two_right', json_encode($newsTwoRight), 60*20);
        }
        $this->assign('newsTwoRight', $newsTwoRight);


        //第二屏：国内足球
        if(!$newsTwoDown = S('web_index_new_two_down')) {
            $newsTwoDown = D('Home')->getIndexNewsTwoDown();

            S('web_index_new_two_down', json_encode($newsTwoDown), 60*5);
        }
        $this->assign('newsTwoDown', $newsTwoDown);

        //第三屏：篮球专栏；
        if(!$newsThird = S('web_index_new_third')) {
            $newsThird = D('Home')->getIndexNewsThird();

            S('web_index_new_third', json_encode($newsThird), 60*5);
        }
        $this->assign('newsThird', $newsThird);

        //第四屏：综合体育
        if(!$newsFour = S('web_index_new_four')) {
            $newsFour = D('Home')->getIndexNewsFour();

            S('web_index_new_four', json_encode($newsFour), 60*5);
        }
        $this->assign('newsFour', $newsFour);

        //第四屏：电竞模块
        if(!$newsFourGame = S('web_index_new_four_game')) {
            $newsFourGame = D('Home')->getIndexNewsFourGame();

            S('web_index_new_four_game', json_encode($newsFourGame), 60*5);
        }

        $this->assign('newsFourGame', $newsFourGame);

        $this->display();
    }

    //新闻资讯内容页
    public function publishContent($id){
        if(checkUrlExt()){
            parent::_empty();
        }
        $this->assign("publishid", $id);
        $where['l.id'] = $id;
        $news = array();
        $list = M('PublishList l')
            ->join("LEFT JOIN qc_front_user f on f.id = l.user_id")
            ->field("l.odds,l.status,l.handcp,l.odds_other,l.id,l.title,l.is_original,l.class_id,l.user_id,l.game_id,l.gamebk_id,l.remark,l.content,l.source,l.label,l.click_number,l.add_time,l.update_time,l.app_time,l.seo_title,l.seo_keys,l.seo_desc,f.nick_name,f.lv,f.head as face,f.descript,l.play_type,l.chose_side,l.result")
            ->where($where)
            ->find();

        $domain = explode('.', $_SERVER['HTTP_HOST'])[0];
        $classArr  = getPublishCLass(0);
        // 新闻设置了二级域名，则要二级域名匹配才显示
        if($classArr[$list['class_id']]['domain'] != '' && $domain != $classArr[$list['class_id']]['domain']){
            parent::_empty();
        }

        //手机站链接适配
        $mobileUrl = mNewsUrl($list['id'],$list['class_id'],$classArr);
        $this->assign('mobileAgent', $mobileUrl);
        //手机端访问跳转
        if(isMobile()){
            redirect($mobileUrl);
        }
        $newsClass   = $classArr[$list['class_id']];
        $parentClass = $newsClass['pid'] != 0 ? $classArr[$newsClass['pid']] : $newsClass;

        //判斷當前二級域名
        $secondary = $parentClass['pid'] != 0 ? $classArr[$parentClass['pid']] : $parentClass;
        $domain = explode('.', $_SERVER['HTTP_HOST'])[0];
        if($domain != $secondary['domain'] && $secondary['domain'] != '') parent::_empty();
        $ex_path = explode('/', $_SERVER['PATH_INFO']);
        if(isset($ex_path[2])){
            $newsClass['path'] = $newsClass['path'] ? : 'news';
            $domain = explode('.', $_SERVER['HTTP_HOST'])[0];
            if($newsClass['path'] == 'notice' && $ex_path[0] == 'news'){
                //资讯广告旧链接301重定向到新链接
                $url = newsUrl($list['id'],$list['add_time'],$list['class_id'],$classArr);
                redirect301($url);
            }

            //判断是否为全英文
            if(preg_match("/^[a-zA-Z\s]+$/",$ex_path[1])){

                if($newsClass['path'] != $ex_path[1]) $this->_empty();
            }else{
                //二级目录，二级域名，文章日期判断
                if($newsClass['path'] != $ex_path[0] || $ex_path[1] != date('Ymd',$list['add_time']) || ($domain != $parentClass['domain'] && $domain != 'www')){
                    $this->_empty();
                }
            }
        }else if($list && !isset($ex_path[2])){
            //旧链接301重定向到新链接
            $url = newsUrl($list['id'],$list['add_time'],$list['class_id'],$classArr);
            redirect301($url);
        }

        if ($list['status'] != 1 && $list['user_id'] !== is_login() && !stripos($_SERVER['HTTP_REFERER'], 'qqty_admin')) {
            $this->_empty();
        }

        if($list['status'] == 1){
            //点击量加1
            M('PublishList')->where(array('id' => $id, 'status' => 1))->setInc('click_number');
        }

        $label = $list['label'];
        if(!$news = S('web_news_'.$id.'_'.get_client_ip())) //缓存5分钟
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
                $KeyRes = M('PublishKey')->where(['name'=>['in',$list['label']],'status'=>1])->getField('name,web_url',true);
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
            //增加上级面包屑资讯分类
            $list['className']  = str_replace('资讯', '', $newsClass['name']);
            $list['classHref']  = newsClassUrl($list['class_id'],$classArr);
            if($parentClass['domain'] != ''){
                $list['parentName'] = $parentClass['name'];
                $list['parentHref'] = U('@'.$parentClass['domain']);
            }
            $news['list'] = $list;
            
            //获取相关新闻
            $PublishClassIds = Tool::getAllSubCategoriesID( $classArr, $list['class_id'] );
            $beSimilar = M('PublishList l')
                ->field("l.id,l.title,l.remark,l.img,l.content,l.click_number,l.class_id,l.add_time,l.update_time,l.app_time")
                ->where(['l.status' => 1, 'l.class_id' => ['in',$PublishClassIds], 'l.id' => array("neq", $id)])
                ->order("l.add_time desc")
                ->limit(10)
                ->select();

            foreach($beSimilar as $k => &$v){
                $v['img']    = newsImgReplace($v);
                $v['href']   = newsUrl($v['id'],$v['add_time'],$v['class_id'],$classArr);
                $v['remark'] = $v['remark'] ? : msubstr(strip_tags(htmlspecialchars_decode($v['content'])), 0, 90);
                //增加资讯点击量的默认值
                $v['click_number'] = addClickConfig(1, $v['class_id'], $v['click_number'], $v['id']);
            }
            $news['beSimilar'] = $beSimilar;

            //专家文章
            $expertList = M('PublishList l')
                ->field("l.id,l.title,l.img,l.class_id,l.add_time,l.app_time,IF(l.remark, l.remark, '') as remark,l.content")
                ->where(['l.status' => 1, 'l.user_id' => $list['user_id'], 'l.id' => array("neq", $id)])
                ->order("l.add_time desc")
                ->limit(4)
                ->select();
            foreach ($expertList as $k => &$v) {
                $v['img']  = newsImgReplace($v);
                $v['href'] = newsUrl($v['id'],$v['add_time'],$v['class_id'],$classArr);
                unset($v['content']);
            }
            $news['expertList'] = $expertList;

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
                //mysql查询
//                if ($list['game_id']) {
//                    $M = M("GameFbinfo");
//                    $game_info['game_id'] = $game_id = $list['game_id'];
//                    $game_info['gtype'] = '1';
//                } else {
//                    $M = M("GameBkinfo");
//                    $game_info['gamebk_id'] = $game_id = $list['gamebk_id'];
//                    $game_info['gtype'] = '2';
//                }
                //mongodb查询
                $mongo = mongoService();
                if ($list['game_id']) {
                    $M = 'fb_game';
                    $game_info['game_id'] = $game_id = $list['game_id'];
                    $game_info['gtype'] = '1';
                    $game = $mongo->select($M,['game_id'=>(int)$game_id],['game_id','spottery_num','union_name','home_team_name','away_team_name','home_team_id','away_team_id','game_start_timestamp','game_state','score'])[0];
                    $game['gtime'] = $game['game_start_timestamp'];
                    $game['bet_code'] = $game['spottery_num'];
                } else {
                    $M = 'bk_game_schedule';
                    $game_info['gamebk_id'] = $game_id = $list['gamebk_id'];
                    $game_info['gtype'] = '2';
                    $game = $mongo->select($M,['game_id'=>(int)$game_id],['game_id','union_name','home_team_name','away_team_name','home_team_id','away_team_id','game_timestamp','game_status','game_info'])[0];
                    $game['game_state'] = $game['game_status'];
                    $game['score'] = $game['game_info'][3].'-'.$game['game_info'][4];
                    $game['gtime'] = $game['game_timestamp'];
                }
//                $game = $M->field('home_team_name,away_team_name,union_name,gtime,game_state,score,home_team_id,away_team_id,bet_code')->where(['game_id' => $game_id])->find();
                if ($game['game_state'] == '-1') $game_info['score'] = $game['score'];
                $game_info['game_state'] = $game['game_state'];
                $game_info['gtime'] = $game['gtime'];
                $game_info['home_team_id'] = $game['home_team_id'];
                $game_info['away_team_id'] = $game['away_team_id'];
                $game_info['home_team_name'] = $game['home_team_name'][0];
                $game_info['away_team_name'] = $game['away_team_name'][0];
                $game_info['union_name'] = $game['union_name'][0];
                $game_info['bet_code'] = $game['bet_code'];
                $_tmp[] = $game_info;
                setTeamLogo($_tmp, $game_info['gtype']);
                $against = $_tmp[0];
            }

            $news['against'] = $against;
            //文章数
            $articleNum = M('PublishList')->where(['status' => 1, 'user_id' => $list['user_id']])->count();
            $news['list']['articleNum'] = M('Highlights')->where(['status' => 1, 'user_id' => $list['user_id']])->count() + $articleNum;
            $news['list']['content'] = htmlspecialchars_decode($news['list']['content']);
            $news['list']['content'] = contKetToUrl($news['list']['content']);

            //粉丝数
            $news['list']['fansNum'] = M('FollowUser')->where(['follow_id'=>$news['list']['user_id']])->count();;
            S('web_news_'.$id.'_'.get_client_ip(),$news,C('newsCacheTime'));
        }

        //24小时热文
        if(!$hostNews24 = S('web_hostNews24')){
            $hostNews24 = D('Home')->getShouXie('news_shouye');
            //随机6条(缓存120分钟)
            shuffle($hostNews24);
            $hostNews24 = array_slice($hostNews24, 0,6);
            S('web_hostNews24',$hostNews24,120*60);
        }
        $this->assign("hostNews24", $hostNews24);

        //赋值模版
        $this->assign("list", $news['list']);
        $this->assign("game", $news['against']);
        $this->assign("beSimilar", $news['beSimilar']);
        $this->assign("expertList", $news['expertList']);
        $this->assign('user_id', is_login());

        //seo
        $this->setSeo([
            'seo_title' => $list['seo_title'] ?: $list['title'].'_'.$list['name'].'新闻专题频道'.'_全球体育网',
            'seo_keys'  => $list['seo_keys']  ?: $label,
            'seo_desc'  => $list['seo_desc']  ?: str_replace(' ', '', msubstr(strip_tags(htmlspecialchars_decode($list['content'])), 0, 150)),
        ]);
        $this->display('PublishIndex/publishContent');
    }

    //相关推荐
    public function getUserRank($game_id){
        $gambleArr = D("GambleView")->where(['game_id'=>$game_id])->select();
        if(!$gambleArr){
            $gambleArr = D("GambleView")->where(['result'=>'0','tradeCoin'=>['gt',0],'lv'=>['gt',2]])->order("rand()")->limit(5)->select();
        }
        if(!$gambleArr) return array();
        foreach ($gambleArr as $k => $v) {
            //周胜率和连胜
            $Winrate = D('GambleHall')->CountWinrate($v['user_id']);
            $Winning = D('GambleHall')->getWinning($v['user_id']);
            $gambleArr[$k]['curr_victs'] = $Winning['curr_victs'];
            $gambleArr[$k]['Winrate'] = $Winrate;
        }
        //分开付费和免费
        $freeGamble = array();
        $payGamble  = array();
        foreach ($gambleArr as $k => $v) {
            if($v['tradeCoin'] == 0){
                $freeGamble[] = $v; //免费
            }else{
                $payGamble[] = $v;  //付费
            }
        }
        //付费排序
        $payGamble  = $this->sortGamble($payGamble);
        //免费排序
        $freeGamble = $this->sortGamble($freeGamble);
        //付费与免费合并
        $gamble = array_merge_recursive($payGamble,$freeGamble);
        return $gamble;
    }

    //排序 "周胜率＞连胜＞等级＞发布时间"
    public function sortGamble($Gamble)
    {
        foreach ($Gamble as $k => $v)
        {
            $sort_Winrate[]    = $v['Winrate'];    //周胜率
            $sort_curr_victs[] = $v['curr_victs']; //连胜
            $sort_lv[]         = $v['lv'];         //等级
            $sort_time[]       = $v['create_time'];//发布时间
        }
        array_multisort($sort_Winrate,SORT_DESC,$sort_curr_victs,SORT_DESC,$sort_lv,SORT_DESC,$sort_time,SORT_DESC,$Gamble);
        return $Gamble;
    }

    /**
    *递归获取评论列表
    */
    public function getCommlist($pid = 0,&$comment = array(),$id,$sign='new',$limitpage=0,$num=10){
        $order = $sign == 'new' ? 'create_time desc' : 'like_num desc,create_time desc';
        $where['pid'] = $pid;
        $where['publish_id'] = $id;
        if($sign == 'host'){
            $where['like_num'] = ['gt',0];
        }
        $arr = M('Comment c')->join("LEFT JOIN qc_front_user f on f.id = c.user_id")
                            ->field("c.*,f.head,f.nick_name,f.is_gag")
                            ->where($where)
                            ->order($order)
                            ->limit($limitpage,$num)
                            ->select();
        if(empty($arr)){
            return array();
        }
        foreach ($arr as $cm) {
            $thisArr=&$comment[];
            $cm["children"] = M("comment")->table("qc_comment c")->join("left join qc_front_user f on c.user_id = f.id")->join("left join qc_front_user f2 on c.by_user = f2.id")->where(['top_id'=>$cm['id'],'publish_id'=>$id])->field("c.id,c.pid,c.publish_id,c.user_id,c.by_user,c.filter_content,c.like_num,c.like_user,c.status,c.create_time,f.nick_name,f.head,f2.nick_name by_username")->order($order)->select();
            $thisArr = $cm;
        }
        return $comment;
    }

    /**
    *添加评论
    */
    public function addComment(){
        $data=array();
        if((isset($_POST["comment"]))&&(!empty($_POST["comment"]))){
            $cm = json_decode($_POST["comment"],true);
            $user = M('frontUser')->where(['id'=>$cm['user_id']])->field('nick_name,is_gag,head,is_robot')->find();

            if($user['is_robot'] != 1) //机器人无需限制
            {
                $lastcomment = M('comment')->field('publish_id,create_time,content')->where(['user_id'=>$cm['user_id']])->order('create_time desc')->limit(1)->find();
                if($lastcomment && time() - $lastcomment['create_time'] < 30) //评论30秒限制
                {
                    $this->error("30秒内不能多次评论哦！");
                }
                if((trim($lastcomment['content']) == trim($cm['content'])) && ($lastcomment['publish_id'] == $cm['publish_id']))
                {
                    $this->error("不能重复评论！");
                }
            }

            //查看是否禁言
            if($user['is_gag'] == 1){
                $this->error("您已被管理员禁言，请联系客服！");
            }

            if ( $cm['pid'] != 0) //是否已达20评论数上限
            {
                $num = M('comment')->where(['pid'=>$cm['pid']])->count();
                if( $num >= 20){
                    $this->error("已达评论数上限，请另外发表评论");
                }
            }

            $cm['create_time'] = time();
            $cm['reg_ip'] = get_client_ip();
            //过滤内容
            $FilterWords = getWebConfig("FilterWords");
            foreach ($FilterWords as $key => $value) {
                $Words[] = '/'.$value.'/';
            }
            $cm['filter_content'] = preg_replace($Words, '***', $cm['content']);
            $rs = M('comment')->add($cm);
            $cm['id']           = $rs;
            $cm['face']         = frontUserFace($user['head']);
            $cm['create_time']  = format_date($cm['create_time']);
            $cm['nick_name']    = $user['nick_name'];
            $cm['commentCount'] = M('comment')->where(['publish_id'=>$cm['publish_id']])->count();
            $cm['info']         = "发布评论成功！";
            if($rs){
                $this->success($cm);
            }else{
                $this->error("发布评论失败,请稍后再试！");
            }
        }else{
            $this->error("发布评论失败,请稍后再试！");
        }
    }

    //添加举报
    public function addReport(){
        $id = I('id');
        $report_content = I('report_content');
        $user_id = session('user_auth')['id'];
        if( isset($id) && isset($user_id) && !empty($report_content) ){
            $comment = M('comment')->where(['id'=>$id])->field('user_id,report_user,report_content')->find();
            if($user_id == $comment['user_id']){
                $this->error("亲，不可举报自己哦！");
            }
            $is_report = explode(",", $comment['report_user']);
            //是否已举报
            if(in_array($user_id, $is_report)){
                $this->error("您已经举报过该评论了哦！");
                exit;
            }
            array_push($is_report,$user_id);
            $report_user = ltrim(implode(",", $is_report),',');
            //是否已存在的举报类型
            $report = explode(",", $comment['report_content']);
            if(!in_array($report_content, $report)){
                array_push($report,$report_content);
                $report_content = ltrim(implode(",", $report),',');
            }
            $rs = M('comment')->where(['id'=>$id])->save(['report_num'=>['exp','report_num+1'],'report_user'=>$report_user,'report_content'=>$report_content]);
            if($rs){
                $this->success("举报成功！");
            }else{
                $this->error("举报失败,请稍后再试！");
            }
        }else{
            $this->error("举报失败,请稍后再试！");
        }
    }

    /**
    *顶
    */
    public function addLikeNum(){
        if((isset($_POST["id"]))&&(!empty($_POST["user_id"]))){
            $id = I('id');
            $user_id = I('user_id');
            $is_like = M('comment')->where(['id'=>$id])->field('user_id,like_user')->find();
            if($user_id == $is_like['user_id']){
                $this->error("亲，不可点赞自己哦！");
            }
            $is_like = explode(",", $is_like['like_user']);
            if(in_array($user_id, $is_like)){
                $this->error("您已经顶过该评论了哦！");
                exit;
            }
            array_push($is_like,$user_id);
            $like_user = ltrim(implode(",", $is_like),',');
            $rs = M('comment')->where(['id'=>$id])->save(['like_num'=>['exp','like_num+1'],'like_user'=>$like_user]);
            if($rs){
                $this->success("操作成功！");
            }else{
                $this->error("操作失败！");
            }
        }else{
            $this->error("操作失败！");
        }
    }
    //获取更多评论
    public function send(){
        $p = isset($_POST['k'])?intval(trim($_POST['k'])):0;
        $publishid = I('publishid');
        $sign      = I('sign') ? I('sign') : 'new';

        $total = M('Comment')->where(['publish_id'=>$publishid])->count();//数据记录总数

        $num = 10;//每页记录数
        $totalpage = ceil($total/$num);//总计页数
        $limitpage = ($p-1)*$num;//每次查询取记录

        if($p>$totalpage){
            //超过最大页数，退出
            $this->error("没有了");
        }
        $comment = $this->getCommlist(0,$comment=array(),$publishid,$sign,$limitpage,$num);
        foreach ($comment as $k => $v) {
            $comment[$k]['face']    = frontUserFace($v['head']);
            $comment[$k]['is_like'] = !in_array(is_login(), explode(",", $v['like_user'])) || !is_login() ? "ds-post-likes" : "ds-post-likes-true";
            $comment[$k]['create_time'] = format_date($v['create_time']);
            $comment[$k]['cm_count'] = count($v['children']);
            if(!empty($v['children'])){
                foreach ($v['children'] as $k2 => $v2) {
                    $comment[$k]['children'][$k2]['face']    = frontUserFace($v2['head']);
                    $comment[$k]['children'][$k2]['is_like'] = !in_array(is_login(), explode(",", $v2['like_user'])) || !is_login() ? "ds-post-likes" : "ds-post-likes-true";
                    $comment[$k]['children'][$k2]['create_time'] = format_date($v2['create_time']);
                }
            }
        }
        if(count($comment)>0){
            // pr($comment);
            // die;
            $this->success($comment);
        }else{
            $this->error("没有了");
        }

    }
    //获取更多新闻
    public function sendNews()
    {
        $p = isset($_POST['k'])?intval(trim($_POST['k'])):0;
        $class_id = I('class_id');
        if(is_numeric($class_id) || in_array($class_id, ['football','NBA'])){
            //获取分类
            $PublishClass = M('PublishClass')->where("status=1")->field("id,pid,level")->select();
            //无限级分类中获取一个分类下的所有分类的ID,包括查找的父ID
            if($class_id == 'football')  //足球 包括国际足球与中国足球
            {
                $worldClassIds = Tool::getAllSubCategoriesID( $PublishClass, 1 );
                $chinaClassIds = Tool::getAllSubCategoriesID( $PublishClass, 2 );
                $PublishClassIds = array_merge($worldClassIds,$chinaClassIds);
            }
            else
            {
                if($class_id == 'NBA') $class_id = 4;
                $PublishClassIds = Tool::getAllSubCategoriesID( $PublishClass, $class_id );
            }
            $where['class_id'] = array( 'in', $PublishClassIds );
        }else{
            //标签搜索
            $where['label'] = ['like','%'.urldecode($class_id).'%'];
        }
        $where['status']   = 1;
        $where['is_original'] = 1;
        $total = M('PublishList')->where($where)->count();//数据记录总数

        $num = 15;//每页记录数
        $totalpage = ceil($total/$num);//总计页数
        $limitpage = ($p-1)*$num;//每次查询取记录

        if($p>$totalpage){
            //超过最大页数，退出
            $this->error("没有了");
        }
        $list = M('PublishList')->where($where)->field('id,user_id,title,content,click_number,remark,img,update_time')->order('add_time desc')->limit($limitpage,$num)->select();
        foreach ($list as $key => $value) {
            if(!empty($value['img'])){
                $list[$key]['img'] = Tool::imagesReplace($value['img']);
            }else{
                //获取第一张图片
                $list[$key]['img']  = Tool::getTextImgUrl(htmlspecialchars_decode($value['content']),false)[0] ?:staticDomain('/Public/Home/images/index/164x114.jpg');
            }
            $list[$key]['comment']  = M('comment')->where(['publish_id'=>$value['id']])->count();
            $list[$key]['update_time']  = date('Y-m-d H:i', $value['update_time']);
            $list[$key]['remark']   = mb_substr($value['remark'], 0,65,'utf-8');
            if($class_id == 10){
                $userInfo = M('frontUser')->where(['id'=>$value['user_id']])->field('nick_name,head')->find();
                $list[$key]['nick_name']  = $userInfo['nick_name'];
                $list[$key]['userFace'] = frontUserFace($userInfo['head']);
            }
        }
        if(count($list)>0){
            $this->success($list);
        }else{
            $this->error("没有了");
        }
    }

    /**
     * * @User liangzk <liangzk@qc.com>
     * @DataTime 2016-08-18
     * 获取连胜多（前50）的用户的推荐-----连胜统计更新一次-----显示推荐30分钟更新一次
     */
    public function getWinningGame()
    {

        $dateTime = S('userWinningArr:dateTime');
        $time = $dateTime - date('Ymd');
        $userWinningArr = S('userWinningArr');
        if (empty($userWinningArr) || $time < 0 )//每天更新一次
        {
            S('userWinningArr',null);
            S('userWinningArr:rand_user',null);
            S('userWinningArr:dateTime',null);
            $userWinningArr = $this->getUserWinning(50);
            S('userWinningArr',$userWinningArr);
            S('userWinningArr:dateTime',date('Ymd'));
        }
        $rand_user_arr = S('userWinningArr:rand_user');
        $blockTime = getBlockTime(1,true);
        if (empty($rand_user_arr) || date('i') == '30' || date('i') == '0')
        {
            if (empty($userWinningArr))
                $this->error('出错了');
            $userIdArr = array();
            foreach($userWinningArr as $key => $val)
            {
                $userIdArr[] = $val['user_id'];
            }
            //获取连胜多并今天有推荐（不免费）的用户id
            
            $gambleUserArr = M('Gamble')
                ->where(['user_id'=>['IN',$userIdArr],'result'=>'0','tradeCoin'=>['GT',0],
                         'create_time'=>['between',[$blockTime['beginTime'],$blockTime['endTime']]]])
                ->group('user_id')
                ->getField('user_id',true);
            //随机获取数组中不同的三个user_id
            $rand_user_arr = array();
            while (true)
            {
                if (count($rand_user_arr)>=3)
                    break;
                $rand_user_id = array_rand($gambleUserArr,1);
                if (in_array($gambleUserArr[$rand_user_id],$rand_user_arr))
                    continue;
                $rand_user_arr[] = $gambleUserArr[$rand_user_id];
            }
            S('userWinningArr:rand_user',$rand_user_arr);
        }


        //获取连胜前三名的用户推荐
        $gambleOne = D('GambleView')->where(['user_id'=>$rand_user_arr[0],
                                             'result'=>'0',
                                             'tradeCoin'=>['GT',0],
                                             'create_time'=>['between',[$blockTime['beginTime'],$blockTime['endTime']]]])
                                            ->order('create_time')
                                            ->limit(0,1)
                                            ->find();
        if (! empty($gambleOne)) $list_gamble[] = $gambleOne;

        $gambleSec = D('GambleView')
                ->where(['user_id'=>$rand_user_arr[1],
                         'result'=>'0',
                         'tradeCoin'=>['GT',0],
                        'create_time'=>['between',[$blockTime['beginTime'],$blockTime['endTime']]]])
                ->order('create_time')->limit(0,1)->find();
        if (! empty($gambleSec)) $list_gamble[] = $gambleSec;

        $gambleThree = D('GambleView')
            ->where(['user_id'=>$rand_user_arr[2],
                     'result'=>'0',
                     'tradeCoin'=>['GT',0],
                     'create_time'=>['between',[$blockTime['beginTime'],$blockTime['endTime']]]])
                    ->order('create_time')
                    ->limit(0,1)
                    ->find();
        if (! empty($gambleThree)) $list_gamble[] = $gambleThree;

        if (empty($list_gamble)) $this->success($list_gamble);//如果该次更新没有推荐就直接返回

        $list_gamble = HandleGamble($list_gamble);
        $user_id = is_login();
        foreach ($list_gamble as $key => $value)
        {
            $list_gamble[$key]['game_date'] = date('m/d',strtotime($value['game_date']));
            $list_gamble[$key]['head'] = frontUserFace($value['head']);
            //获取连胜数
            foreach ($userWinningArr as $k => $v)
            {
                if ($value['user_id'] == $v['user_id'])
                {
                    $list_gamble[$key]['winningNum'] = $v['num'];
                }
            }
            //判断推荐是否被查看或是否为用户自己的推荐
            if ($user_id)
            {
                if($value['user_id'] != $user_id )
                {
                    //是否已被查看
                    $list_gamble[$key]['is_check'] = M('quizLog')->where(['user_id'=>$user_id,'gamble_id'=>$value['id'],'game_type'=>1])->getField('id');
                }
                else
                {
                    $list_gamble[$key]['login_user'] = $user_id;
                }
            }

        }
        foreach ($list_gamble as $k => $v) {
            $winningNum[] = $v['winningNum'];
        }
        array_multisort($winningNum,SORT_DESC,$list_gamble);//根据连胜多的排序
        $this->success($list_gamble);
    }

    /**
     * 投稿页面跳转
     */
    public function submission()
    {
        $userId = $this->userId;
        if (!$userId) {
            $this->redirect("User/login");
        } else {
            $is_expert = M("FrontUser")->field('is_expert')->where(["id" => $userId])->select()[0]['is_expert'];
            if ($is_expert == '0') {
                redirect(U("UserInfo/ident"));
            }
            if ($is_expert == '1') {
                redirect(U("UserInfo/publish"));
            }
        }
    }

    /**
     * 获取最近一周发布文章关联赛事的联赛
     */
    public function getOrderUnion()
    {
        $fb_data_list= M("publishList")->alias('p')
            ->join("LEFT JOIN qc_game_fbinfo f on f.game_id = p.game_id")
            ->join("LEFT JOIN qc_union u on f.union_id = u.union_id")
            ->field("f.union_name, u.union_id")
            ->where(["p.add_time" => ["gt", strtotime("-7 day")]])
            ->where(["u.is_sub in (0, 1)"])
            ->select();
        $union_name = [];
        foreach ($fb_data_list as $val) {
            $union_name[$val['union_id']]  = explode(",", $val['union_name'],-1)[0];
        }
        $bk_data_list= M("publishList")->alias('p')
            ->join("LEFT JOIN qc_game_bkinfo b on b.game_id = p.gamebk_id")
            ->field("b.union_name, b.union_id")
            ->where(["p.add_time" => ["gt", strtotime("-7 day")]])
            ->where(["b.union_id in (1, 5)"])
            ->select();
        foreach ($bk_data_list as $val) {
            if ($val['union_id'] == 1) {
                $union_name["B".$val['union_id']]  =  "NBA";
            } elseif ($val['union_id'] == 5) {
                $union_name["B".$val['union_id']] = "CBA";
            }
        }
        $union_name = array_filter($union_name);
        return $union_name;
    }

    /**
     * 分页筛选专用 弥补_list方法
     */
    protected function expert_list($model, $map, $listRows, $order = '', $field="*",$style=false, $url, $filter, $pageType=1) {
        if (preg_match('/B/',  $filter)) {
            preg_match('/\d/', $filter, $result);
            $join_table = "LEFT JOIN qc_game_bkinfo b on b.game_id = p.gamebk_id";
            $map["b.union_id"] = $result[0];
        }else {
            $join_table = "LEFT JOIN qc_game_fbinfo f on f.game_id = p.game_id";
            $map["f.union_id"] = $filter;
        }
        //取得满足条件的记录数
        $count = $model->join($join_table)->where ($map)->count ();
        if ($count > 0) {
            //创建分页对象
            if (! empty ( $listRows )) {
                $listRows = $listRows;
            } else {
                $listRows = 15;
            }
            //实例化分页类
            $page = new \Think\Page ( $count, $listRows );
            //处理排序
            if (empty($order)) {
                $order = $_REQUEST['order'];
            }
            if (empty($order)) {
                $order = "id desc";
                $_REQUEST['order'] = $order;
            }
            //分页查询数据
            $voList = $model->join($join_table)->where($map)->group("p.id")->field('p.*')->order($order)->limit($page->firstRow . ',' . $page->listRows)->select();
            //分页跳转的时候保证查询条件
            foreach ( $map as $key => $val ) {
                if (! is_array ( $val )) {
                    $page->parameter .= "$key=" . urlencode ( $val ) . "&";
                }
            }
            //是否使用自定义样式
            if($style){
                $page->config  = array(
                    'header' => '<span class="rows">共 %TOTAL_ROW% 条记录</span>',
                    'prev'   => '<span aria-hidden="true">上一页</span>',
                    'next'   => '<span aria-hidden="true">下一页</span>',
                    'first'  => '首页',
                    'last'   => '...%TOTAL_PAGE%',
                    'theme'  => '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%',
                );
            }
            if (!empty($url)) {
                $page->url = $url;
            }
            //模板赋值显示
            if($pageType==1){
                if ($filter) {
                    // 修复TP自带分页不足 正则替换所需参数
                    $pattern =  '/\?p=(\d+)/';
                    $filterPage = preg_replace($pattern, '?p=$1&filter='.$filter ,$page->showJs());
                    $pattern2 = '/(class="end".*?p=")(\d+)(.*?)p=(\d+)/';
                     $filterPage2 = preg_replace($pattern2, '$1$2$3p=$2', $filterPage);
                    $this->assign("show", $filterPage2);
                } else {
                    $this->assign ( "show", $page->showJs());
                }
            }else{
                $this->assign ( "show", $page->showJump());
            }
            $this->assign('totalCount', $count );
            $this->assign('numPerPage', $page->listRows );
            //同时返回，以便对需要重新组装的数据进行操作
            return $voList;
        } else {
            return false;
        }
    }

    /**
     * 获取直播视频页面的视频
     * @return array
     */
    public function getWebVideo(){
        if(!$liveGame = S("sporttery_web_video")) //缓存5分钟
        {
            //足球
            $blockTime = getBlockTime(1, true);//获取竞猜分割日期的区间时间
            $fbGame = M("GameFbinfo g")-> field("g.game_id, g.union_name,  g.home_team_id, g.home_team_name, g.away_team_id, g.away_team_name, g.gtime, g.game_state, g.game_date, g.game_time, g.web_video, u.is_sub, g.is_video ")
                ->join('left join qc_union u on g.union_id = u.union_id')
                ->where(['u.is_sub' => ['exp', 'is not null'], 'g.game_state' => ['in', [1, 2, 3, 4, -1]], 'g.gtime' => ['between', [strtotime('-1 day', $blockTime['beginTime']), $blockTime['endTime']]], 'g.status'=>1 ])
                ->where("g.web_video is not null")
                ->order('g.game_state desc, u.is_sub asc, g.is_video desc, g.web_video desc,  g.gtime desc')
                ->limit(5)->select();
            $fbGame = $fbGame ? $fbGame : [];

            foreach ($fbGame as $fk => &$fv) {
                $fv['game_type'] = 1;//足球
            }
            //篮球
            $blockTime = getBlockTime(2, true);//获取竞猜分割日期的区间时间
            $bkGame = M('GameBkinfo g')->field("g.game_id, g.union_name,  g.home_team_id, g.home_team_name, g.away_team_id, g.away_team_name, g.gtime, g.game_state, g.game_date, g.game_time, g.web_video,  u.is_sub, g.is_video ")
                ->join('left join qc_union u on g.union_id = u.union_id')
                ->where(['u.is_sub' => ['exp', 'is not null'], 'g.game_state' => ['in', [1, 2, 3, 4, -1]], 'g.gtime' => ['between', [strtotime('-1 day', $blockTime['beginTime']), $blockTime['endTime']]], 'g.status'=>1 ])
                ->where("g.web_video is not null")
                ->order('g.game_state desc, u.is_sub asc, g.is_video desc, g.web_video desc,  g.gtime desc')
                ->limit(5)->select();

            $bkGame = $bkGame ? $fbGame : [];

            foreach ($bkGame as $bk => &$bv) {
                $bv['game_type'] = 2;//篮球
            }

            $liveGame = array_merge($fbGame, $bkGame);

            $gameState = $is_sub = $is_video = $gtime = [];
            foreach ($liveGame as $lk => &$v) {
                $gameState[] = $v['game_state'];
                $is_sub[] = $v['is_sub'];
                $is_video[] = ($v['is_video'] && !empty(json_decode($v['web_video'], true))) ? 1 : 0;
                $gtime[] = $v['gtime'];
                $v['gtime'] = date('m/d H:i', $v['gtime']);
                $v['unionName'] = explode(',', $v['unionName'])[0];
                $v['homeTeamName'] = explode(',', $v['homeTeamName'])[0];
                $v['awayTeamName'] = explode(',', $v['awayTeamName'])[0];
            }
            array_multisort($gameState, SORT_DESC, $is_sub, SORT_DESC, $is_video,  SORT_DESC, $gtime, SORT_DESC, $liveGame);
            unset($gameState, $is_sub, $is_video, $gtime);
            $liveGame = array_slice($liveGame, 0, 5);

            foreach ($liveGame as $k => &$v) {
                $v['web_video'] = json_decode($v['web_video'],true);
                //球队logo
                $v['homeTeamLogo'] = getLogoTeam($v['home_team_id'],1,$v['game_type']);
                $v['awayTeamLogo'] = getLogoTeam($v['away_team_id'],2,$v['game_type']);
            }
            S('sporttery_web_video', json_encode($liveGame), 60 * 10);
        }
//            if($liveGame){
//                foreach($liveGame as $k => &$v){
//                    if($v['game_type'] == 1){
//                        $one = M('GameFbinfo')->field('game_state, score')->where(['game_id' => $v['game_id']])->find();
//                    }else{
//                        $one = M('GameBkinfo')->field('game_state, score')->where(['game_id' => $bv['game_id']])->find();
//                    }
//                    $v['game_state'] = $one['game_state'];
//                }
//            }else{
//                $liveGame = [];
//            }
        return $liveGame;
    }
}