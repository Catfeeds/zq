<?php
/**
 * 专题管理控制器
 * @since  2018-1-25
 */
use Think\Tool\Tool;
use Home\Services\WebfbService;
class SpecialController extends CommonController {
    public $publishClass = [];
    public $unionArr = [
        'premierleague'   => 36, //英超
        'laliga'          => 31, //西甲
        'bundesliga'      => 8,  //德甲
        'seriea'          => 34, //意甲
        'championsleague' => 103,//欧冠
        'afccl'           => 192,//亚冠
        'csl'             => 60, //中超
        'nba'             => 1,  //nba
        'cba'             => 5,  //cba     
        '2018worldcup'    => 75, //世界杯          
    ];
    public $dataArr = [
        'premierleague'   => ['link'=>6],//英超
        'laliga'          => ['link'=>7],//西甲
        'bundesliga'      => ['link'=>8],//德甲
        'seriea'          => ['link'=>9],//意甲
        'csl'             => ['link'=>10],//中超
        'championsleague' => ['link'=>11],//欧冠
        'afccl'           => ['link'=>12],//亚冠
        'nba'             => ['link'=>13],//nba
        'cba'             => ['link'=>14],//cba     
        '2018worldcup'    => ['link'=>15],//世界杯          
    ];
    protected function _initialize(){
        $this->publishClass = getPublishClass();
        parent::_initialize();
        //二级域名host
        /*
        127.0.0.1 premierleague.qt.com
        127.0.0.1 laliga.qt.com
        127.0.0.1 bundesliga.qt.com
        127.0.0.1 seriea.qt.com
        127.0.0.1 championsleague.qt.com
        127.0.0.1 afccl.qt.com
        127.0.0.1 csl.qt.com
        127.0.0.1 nba.qt.com
        127.0.0.1 cba.qt.com
        127.0.0.1 tennis.qt.com
        127.0.0.1 baseball.qt.com
        127.0.0.1 snooker.qt.com
        127.0.0.1 nfl.qt.com
        127.0.0.1 esports.qt.com
        127.0.0.1 lol.qt.com
        127.0.0.1 dota2.qt.com
        127.0.0.1 pubg.qt.com
        127.0.0.1 pvp.qt.com
        127.0.0.1 sporttery.qt.com
        127.0.0.1 video.qt.com
        127.0.0.1 pingpong.qt.com
        127.0.0.1 vollyball.qt.com
        127.0.0.1 ligue1.qt.com
        127.0.0.1 2018worldcup.qt.com
        127.0.0.1 wuzhou.qt.com
        */
        //二级域名
        //premierleague.qt.com laliga.qt.com bundesliga.qt.com seriea.qt.com championsleague.qt.com afccl.qt.com csl.qt.com nba.qt.com cba.qt.com tennis.qt.com baseball.qt.com snooker.qt.com nfl.qt.com esports.qt.com lol.qt.com dota2.qt.com pubg.qt.com pvp.qt.com sporttery.qt.com video.qt.com pingpong.qt.com vollyball.qt.com ligue1.qt.com 2018worldcup.qt.com
    }

    //专题页（通过二级域名判断专题类型）
    public function index()
    {
        if(strlen($_SERVER['PATH_INFO']) != 0) parent::_empty();
        $domain = explode('.', $_SERVER['HTTP_HOST'])[0];
        if(in_array($domain, ['premierleague','laliga','bundesliga','seriea','csl','championsleague','afccl','nba','cba','2018worldcup'])){
            //友情链接
            if(!$linkArr = S('special_link_'.$domain)) {
                $position_id = $this->dataArr[$domain]['link'];
                $linkArr = M('link')->field('name, url')->where(['status' => 1, 'position' => $position_id])->order('sort asc')->select();
                S('special_link_'.$domain, $linkArr, 600);
            }
            $this->assign('linkArr', $linkArr);
        }
        //手机站链接适配
        $mobileUrl = U('/'.$domain.'@m');
        if(isMobile()){
            redirect($mobileUrl);
        }
        $this->assign('mobileAgent', $mobileUrl);
        switch ($domain) 
        {
            case 'premierleague':   $this->premierleague('premierleague');     break;//英超
            case 'laliga':          $this->laliga('laliga');                   break;//西甲
            case 'bundesliga':      $this->bundesliga('bundesliga');           break;//德甲
            case 'seriea':          $this->seriea('seriea');                   break;//意甲
            case 'championsleague': $this->championsleague('championsleague'); break;//欧冠
            case 'afccl':           $this->afccl('afccl');                     break;//亚冠
            case 'csl':             $this->csl('csl');                         break;//中超
            case 'nba':             $this->nba('nba');                         break;//nba
            case 'cba':             $this->cba('cba');                         break;//cba
            case 'tennis':          $this->tennis('tennis');                   break;//网球
            case 'baseball':        $this->baseball('baseball');               break;//棒球
            case 'snooker':         $this->snooker('snooker');                 break;//斯诺克
            case 'nfl':             $this->nfl('nfl');                         break;//美式足球
            case 'esports':         $this->esports('esports');                 break;//电竞 (暂时没出页面)
            case 'lol':             $this->lol('lol');                         break;//英雄联盟
            case 'dota2':           $this->dota2('dota2');                     break;//DOTA2
            case 'pubg':            $this->pubg('pubg');                       break;//绝地求生
            case 'pvp':             $this->pvp('pvp');                         break;//王者荣耀
            case 'pingpong':        $this->pingpong('pingpong');               break;//乒乓球
            case 'vollyball':       $this->vollyball('vollyball');             break;//排球
            case 'ligue1':          $this->ligue1('ligue1');                   break;//法甲
            case '2018worldcup':    $this->worldcup('2018worldcup');           break;//世界杯专题
            case 'wuzhou':          $this->wuzhou('wuzhou');                   break;//五洲专题
        }
    }

    //空方法链接跳转
    public function _empty($path)
    {
        $ex_path = explode('/', $_SERVER['PATH_INFO']);
        
        if(isset($ex_path[0]) && isset($ex_path[2]) && !isset($ex_path[3])){
            //资讯内容页
            $id = (int)$ex_path[2];
            A('Home/PublishIndex')->publishContent($id);
        }else if(isset($ex_path[0]) && !isset($ex_path[1])){
            //资讯栏目页
            $path = $ex_path[0];
            if($path == 'news'){
                $path = explode('.', $_SERVER['HTTP_HOST'])[0];
            }
            $classArr = getPublishClass(0);
            foreach ($classArr as $k => $v) {
                if($v['domain'] == $path || $v['path'] == $path){
                    $class_id = $v['id'];break;
                }
            }
            if(!$class_id){
                parent::_empty();
            }
            A('Home/PublishIndex')->publishClass($class_id);
        }else{
            parent::_empty();
        }
    }

    //英超专题页
    public function premierleague($viewName)
    {
        $class_id      = 13; //英超分类id
        $this->setSeo($this->publishClass[$class_id]);
        $this->assign('class_id',$class_id);
        $nav_id        = 9;  //导航配置id
        $shouxie_sign  = 'news_yingchao'; //手写位配置标识
        $lunbo_id      = 48; //轮播图广告id
        $adver_id1     = 57; //英超横幅广告id
        $adver_id2     = 65; //射手榜下面广告图id
        $highlights_id = 56; //热点视频分类id
        $photo_id      = 31; //图片分类id
        $union_id      = $this->unionArr['premierleague']; //英超的联盟id

        //导航
        $nav = D('Home')->getNavList($nav_id);
        $this->assign('nav',$nav);

        //资讯手写位
        if(!$shouxie = S('special_news_'.$viewName))
        {
            $shouxie = D('Home')->getShouXie($shouxie_sign,$class_id,18,6);

            S('special_news_'.$viewName,($shouxie),5*60);
        }
        $this->assign('shouxie',$shouxie);

        //轮播图
        if(!$banner = S('special_banner_'.$viewName)) 
        {
            $banner = Tool::getAdList($lunbo_id, 5);
            S('special_banner_'.$viewName,($banner), 5*60);
        }
        $this->assign('banner', $banner);

        //英超横幅广告
        if(!$adver1 = S('special_adver1_'.$viewName))
        {
            $adver1 = Tool::getAdList($adver_id1,1)[0];
            S('special_adver1_'.$viewName,($adver1),5*60);
        }
        $this->assign('adver1', $adver1);

        //射手榜下面广告图
        if(!$adver2 = S('special_adver2_'.$viewName)) 
        {
            $adver2 = Tool::getAdList($adver_id2,1)[0];
            S('special_adver2_'.$viewName,($adver2), 5*60);
        }
        $this->assign('adver2', $adver2);

        //热点视频
        if(!$highlights = S('special_highlight_'.$viewName)) 
        {
            $highlights = $this->getSpecialHighlights($highlights_id,3);
            S('special_highlight_'.$viewName,($highlights), 60*5);
        }
        $this->assign('highlights', $highlights);

        //积分榜
        $WebfbService = new WebfbService();
        $pointRank = $WebfbService->getFbUnionRank($union_id,12);
        $this->assign('pointRank',$pointRank);

        //射手榜
        $archerRank = $WebfbService->getFbUnionArcher($union_id,12);
        $this->assign('archerRank',$archerRank);

        //英超分类
        $publishClass = $this->publishClass[$class_id];
        $this->assign('publishClass', $publishClass);
        
        //英超资讯(包括下级分类)
        if(!$news_all = S('special_newsall_'.$viewName))
        {
            $news_all = $this->getSpecialNews($class_id);
            S('special_newsall_'.$viewName,($news_all),5*60);
        }
        $this->assign('news_all', $news_all);

        //英超3张最新图片
        if(!$photo = S('special_photo_'.$viewName))
        {
            $photo = $this->getSpecialPhoto($photo_id);
            S('special_photo_'.$viewName,($photo),5*60);
        }
        $this->assign('photo', $photo);

        //获取竞彩分析(独家接盘-英超)
        if(!$dujia = S('special_dujia_'.$viewName))
        {
            $dujia = $this->getSpecialDujia($union_id);
            S('special_dujia_'.$viewName,($dujia),5*60);
        }
        $this->assign('dujia', $dujia);

        //资讯前瞻 战报
        $prospect = $this->getProspect($union_id);
        $report = $this->getReport($union_id);
        //$tttt = array_merge($prospect, $report);var_dump($tttt);exit;
        if($prospect || $report){
            $prospect_report = True;
        }else{
            $prospect_report = False;
        }
        $this->assign('prospect_report', $prospect_report);
        $this->assign('prospect', $prospect);
        $this->assign('report', $report);

        $this->display($viewName);
    }

    //西甲专题页
    public function laliga($viewName){
        $class_id      = 14; //西甲分类id
        $this->setSeo($this->publishClass[$class_id]);
        $this->assign('class_id',$class_id);
        $nav_id        = 10;  //导航配置id
        $shouxie_sign  = 'news_xijia'; //手写位配置标识
        $lunbo_id      = 70; //轮播图广告id
        $adver_id1     = 71; //西甲横幅广告id
        $adver_id2     = 65; //射手榜下面广告图id
        $highlights_id = 57; //热点视频分类id
        $photo_id      = 34; //图片分类id
        $union_id      = $this->unionArr['laliga']; //西甲的联盟id

        //导航
        $nav = D('Home')->getNavList($nav_id);
        $this->assign('nav',$nav);

        //资讯手写位
        if(!$shouxie = S('special_news_'.$viewName))
        {
            $shouxie = D('Home')->getShouXie($shouxie_sign,$class_id,18,6);

            S('special_news_'.$viewName,($shouxie),5*60);
        }
        $this->assign('shouxie',$shouxie);

        //轮播图
        if(!$banner = S('special_banner_'.$viewName))
        {
            $banner = Tool::getAdList($lunbo_id, 5);
            S('special_banner_'.$viewName,($banner), 5*60);
        }
        $this->assign('banner', $banner);

        //英超横幅广告
        if(!$adver1 = S('special_adver1_'.$viewName))
        {
            $adver1 = Tool::getAdList($adver_id1,1)[0];
            S('special_adver1_'.$viewName,($adver1),5*60);
        }
        $this->assign('adver1', $adver1);

        //射手榜下面广告图
        if(!$adver2 = S('special_adver2_'.$viewName))
        {
            $adver2 = Tool::getAdList($adver_id2,1)[0];
            S('special_adver2_'.$viewName,($adver2), 5*60);
        }
        $this->assign('adver2', $adver2);

        //热点视频
        if(!$highlights = S('special_highlight_'.$viewName))
        {
            $highlights = $this->getSpecialHighlights($highlights_id,3);
            S('special_highlight_'.$viewName,($highlights), 60*5);
        }
        $this->assign('highlights', $highlights);

        //积分榜
        $WebfbService = new WebfbService();
        $pointRank = $WebfbService->getFbUnionRank($union_id,12);
        $this->assign('pointRank',$pointRank);

        //射手榜
        $archerRank = $WebfbService->getFbUnionArcher($union_id,12);
        $this->assign('archerRank',$archerRank);

        //英超分类
        $publishClass = $this->publishClass[$class_id];
        $this->assign('publishClass', $publishClass);

        //英超资讯(包括下级分类)
        if(!$news_all = S('special_newsall_'.$viewName))
        {
            $news_all = $this->getSpecialNews($class_id);
            S('special_newsall_'.$viewName,($news_all),5*60);
        }
        $this->assign('news_all', $news_all);

        //英超3张最新图片
        if(!$photo = S('special_photo_'.$viewName))
        {
            $photo = $this->getSpecialPhoto($photo_id);
            S('special_photo_'.$viewName,($photo),5*60);
        }
        $this->assign('photo', $photo);

        //获取竞彩分析(独家接盘-英超)
        if(!$dujia = S('special_dujia_'.$viewName))
        {
            $dujia = $this->getSpecialDujia($union_id);
            S('special_dujia_'.$viewName,($dujia),5*60);
        }
        $this->assign('dujia', $dujia);

        //资讯前瞻 战报
        $prospect = $this->getProspect($union_id);
        $report = $this->getReport($union_id);
        //$tttt = array_merge($prospect, $report);var_dump($tttt);exit;
        if($prospect || $report){
            $prospect_report = True;
        }else{
            $prospect_report = False;
        }
        $this->assign('prospect_report', $prospect_report);
        $this->assign('prospect', $prospect);
        $this->assign('report', $report);

        $this->display($viewName);
    }
    //德甲专题页
    public function bundesliga($viewName){
        $class_id      = 15; //德甲分类id
        $this->setSeo($this->publishClass[$class_id]);
        $this->assign('class_id',$class_id);
        $nav_id        = 11;  //导航配置id
        $shouxie_sign  = 'news_dejia'; //手写位配置标识
        $lunbo_id      = 74; //轮播图广告id
        $adver_id1     = 75; //德甲横幅广告id
        $adver_id2     = 65; //射手榜下面广告图id
        $highlights_id = 58; //热点视频分类id
        $photo_id      = 32; //图片分类id
        $union_id      = $this->unionArr['bundesliga']; //德甲的联盟id

        //导航
        $nav = D('Home')->getNavList($nav_id);
        $this->assign('nav',$nav);

        //资讯手写位
        if(!$shouxie = S('special_news_'.$viewName))
        {
            $shouxie = D('Home')->getShouXie($shouxie_sign,$class_id,18,6);

            S('special_news_'.$viewName,($shouxie),5*60);
        }
        $this->assign('shouxie',$shouxie);

        //轮播图
        if(!$banner = S('special_banner_'.$viewName))
        {
            $banner = Tool::getAdList($lunbo_id, 5);
            S('special_banner_'.$viewName,($banner), 5*60);
        }
        $this->assign('banner', $banner);

        //英超横幅广告
        if(!$adver1 = S('special_adver1_'.$viewName))
        {
            $adver1 = Tool::getAdList($adver_id1,1)[0];
            S('special_adver1_'.$viewName,($adver1),5*60);
        }
        $this->assign('adver1', $adver1);

        //射手榜下面广告图
        if(!$adver2 = S('special_adver2_'.$viewName))
        {
            $adver2 = Tool::getAdList($adver_id2,1)[0];
            S('special_adver2_'.$viewName,($adver2), 5*60);
        }
        $this->assign('adver2', $adver2);

        //热点视频
        if(!$highlights = S('special_highlight_'.$viewName))
        {
            $highlights = $this->getSpecialHighlights($highlights_id,3);
            S('special_highlight_'.$viewName,($highlights), 60*5);
        }
        $this->assign('highlights', $highlights);

        //积分榜
        $WebfbService = new WebfbService();
        $pointRank = $WebfbService->getFbUnionRank($union_id,12);
        $this->assign('pointRank',$pointRank);

        //射手榜
        $archerRank = $WebfbService->getFbUnionArcher($union_id,12);
        $this->assign('archerRank',$archerRank);

        //英超分类
        $publishClass = $this->publishClass[$class_id];
        $this->assign('publishClass', $publishClass);

        //英超资讯(包括下级分类)
        if(!$news_all = S('special_newsall_'.$viewName))
        {
            $news_all = $this->getSpecialNews($class_id);
            S('special_newsall_'.$viewName,($news_all),5*60);
        }
        $this->assign('news_all', $news_all);

        //英超3张最新图片
        if(!$photo = S('special_photo_'.$viewName))
        {
            $photo = $this->getSpecialPhoto($photo_id);
            S('special_photo_'.$viewName,($photo),5*60);
        }
        $this->assign('photo', $photo);

        //获取竞彩分析(独家接盘-英超)
        if(!$dujia = S('special_dujia_'.$viewName))
        {
            $dujia = $this->getSpecialDujia($union_id);
            S('special_dujia_'.$viewName,($dujia),5*60);
        }
        $this->assign('dujia', $dujia);

        //资讯前瞻 战报
        $prospect = $this->getProspect($union_id);
        $report = $this->getReport($union_id);
        //$tttt = array_merge($prospect, $report);var_dump($tttt);exit;
        if($prospect || $report){
            $prospect_report = True;
        }else{
            $prospect_report = False;
        }
        $this->assign('prospect_report', $prospect_report);
        $this->assign('prospect', $prospect);
        $this->assign('report', $report);

        $this->display($viewName);
    }
    //意甲专题页
    public function seriea($viewName){
        $class_id      = 17; //分类id
        $this->setSeo($this->publishClass[$class_id]);
        $this->assign('class_id',$class_id);
        $nav_id        = 12;  //导航配置id
        $shouxie_sign  = 'news_yijia'; //手写位配置标识
        $lunbo_id      = 68; //轮播图广告id
        $adver_id1     = 69; //横幅广告id
        $adver_id2     = 65; //射手榜下面广告图id
        $highlights_id = 59; //热点视频分类id
        $photo_id      = 33; //图片分类id
        $union_id      = $this->unionArr['seriea']; //联盟id

        //导航
        $nav = D('Home')->getNavList($nav_id);
        $this->assign('nav',$nav);

        //资讯手写位
        if(!$shouxie = S('special_news_'.$viewName))
        {
            $shouxie = D('Home')->getShouXie($shouxie_sign,$class_id,18,6);

            S('special_news_'.$viewName,($shouxie),5*60);
        }
        $this->assign('shouxie',$shouxie);

        //轮播图
        if(!$banner = S('special_banner_'.$viewName))
        {
            $banner = Tool::getAdList($lunbo_id, 5);
            S('special_banner_'.$viewName,($banner), 5*60);
        }
        $this->assign('banner', $banner);

        //横幅广告
        if(!$adver1 = S('special_adver1_'.$viewName))
        {
            $adver1 = Tool::getAdList($adver_id1,1)[0];
            S('special_adver1_'.$viewName,($adver1),5*60);
        }
        $this->assign('adver1', $adver1);

        //射手榜下面广告图
        if(!$adver2 = S('special_adver2_'.$viewName))
        {
            $adver2 = Tool::getAdList($adver_id2,1)[0];
            S('special_adver2_'.$viewName,($adver2), 5*60);
        }
        $this->assign('adver2', $adver2);

        //热点视频
        if(!$highlights = S('special_highlight_'.$viewName))
        {
            $highlights = $this->getSpecialHighlights($highlights_id,3);
            S('special_highlight_'.$viewName,($highlights), 60*5);
        }
        $this->assign('highlights', $highlights);

        //积分榜
        $WebfbService = new WebfbService();
        $pointRank = $WebfbService->getFbUnionRank($union_id,12);
        $this->assign('pointRank',$pointRank);

        //射手榜
        $archerRank = $WebfbService->getFbUnionArcher($union_id,12);
        $this->assign('archerRank',$archerRank);

        //分类
        $publishClass = $this->publishClass[$class_id];
        $this->assign('publishClass', $publishClass);

        //资讯(包括下级分类)
        if(!$news_all = S('special_newsall_'.$viewName))
        {
            $news_all = $this->getSpecialNews($class_id);
            S('special_newsall_'.$viewName,($news_all),5*60);
        }
        $this->assign('news_all', $news_all);

        //3张最新图片
        if(!$photo = S('special_photo_'.$viewName))
        {
            $photo = $this->getSpecialPhoto($photo_id);
            S('special_photo_'.$viewName,($photo),5*60);
        }
        $this->assign('photo', $photo);

        //获取竞彩分析(独家接盘)
        if(!$dujia = S('special_dujia_'.$viewName))
        {
            $dujia = $this->getSpecialDujia($union_id);
            S('special_dujia_'.$viewName,($dujia),5*60);
        }
        $this->assign('dujia', $dujia);

        //资讯前瞻 战报
        $prospect = $this->getProspect($union_id);
        $report = $this->getReport($union_id);
        //$tttt = array_merge($prospect, $report);var_dump($tttt);exit;
        if($prospect || $report){
            $prospect_report = True;
        }else{
            $prospect_report = False;
        }
        $this->assign('prospect_report', $prospect_report);
        $this->assign('prospect', $prospect);
        $this->assign('report', $report);

        $this->display($viewName);
    }
    //欧冠专题
    public function championsleague($viewName){
        $class_id      = 27; //分类id
        $this->setSeo($this->publishClass[$class_id]);
        $this->assign('class_id', $class_id);
        $nav_id        = 13; //导航配置id
        $shouxie_sign  = 'news_ouguan'; //手写位配置标识
        $lunbo_id      = 72; //轮播图广告id
        $adver_id1     = 73; //横幅广告id
        $adver_id2     = 65; //射手榜下面广告图id
        $highlights_id = 60; //热点视频分类id
        $photo_id      = 36; //图片分类id
        $union_id      = $this->unionArr['championsleague']; //联盟id
        $this->assign('unionId', $union_id);

        //导航
        $nav = D('Home')->getNavList($nav_id);
        $this->assign('nav',$nav);

        //资讯手写位
        if(!$shouxie = S('special_news_'.$viewName))
        {
            $shouxie = D('Home')->getShouXie($shouxie_sign,$class_id,18,6);

            S('special_news_'.$viewName,($shouxie),5*60);
        }
        $this->assign('shouxie',$shouxie);

        //轮播图
        if(!$banner = S('special_banner_'.$viewName))
        {
            $banner = Tool::getAdList($lunbo_id, 5);
            S('special_banner_'.$viewName,($banner), 5*60);
        }
        $this->assign('banner', $banner);

        //横幅广告
        if(!$adver1 = S('special_adver1_'.$viewName))
        {
            $adver1 = Tool::getAdList($adver_id1,1)[0];
            S('special_adver1_'.$viewName,($adver1),5*60);
        }
        $this->assign('adver1', $adver1);

        //射手榜下面广告图
        if(!$adver2 = S('special_adver2_'.$viewName))
        {
            $adver2 = Tool::getAdList($adver_id2,1)[0];
            S('special_adver2_'.$viewName,($adver2), 5*60);
        }
        $this->assign('adver2', $adver2);

        //热点视频
        if(!$highlights = S('special_highlight_'.$viewName))
        {
            $highlights = $this->getSpecialHighlights($highlights_id,3);
            S('special_highlight_'.$viewName,($highlights), 60*5);
        }
        $this->assign('highlights', $highlights);

        $WebfbService = new WebfbService();
        //射手榜
        $archerRank = $WebfbService->getFbUnionArcher($union_id,12);
        $this->assign('archerRank',$archerRank);

        //分类
        $publishClass = $this->publishClass[$class_id];
        $this->assign('publishClass', $publishClass);

        //资讯(包括下级分类)
        if(!$news_all = S('special_newsall_'.$viewName))
        {
            $news_all = $this->getSpecialNews($class_id);
            S('special_newsall_'.$viewName,($news_all),5*60);
        }
        $this->assign('news_all', $news_all);

        //3张最新图片
        if(!$photo = S('special_photo_'.$viewName))
        {
            $photo = $this->getSpecialPhoto($photo_id);
            S('special_photo_'.$viewName,($photo),5*60);
        }
        $this->assign('photo', $photo);

        //获取竞彩分析(独家接盘)
        if(!$dujia = S('special_dujia_'.$viewName))
        {
            $dujia = $this->getSpecialDujia($union_id);
            S('special_dujia_'.$viewName,($dujia),5*60);
        }
        $this->assign('dujia', $dujia);

        //资讯前瞻 战报
        $prospect = $this->getProspect($union_id);
        $report = $this->getReport($union_id);
        //$tttt = array_merge($prospect, $report);var_dump($tttt);exit;
        if($prospect || $report){
            $prospect_report = True;
        }else{
            $prospect_report = False;
        }
        $this->assign('prospect_report', $prospect_report);
        $this->assign('prospect', $prospect);
        $this->assign('report', $report);

        $this->display($viewName);
    }

    //亚冠专题
    public function afccl($viewName){
        $class_id      = 28; //亚冠分类id
        $this->setSeo($this->publishClass[$class_id]);
        $this->assign('class_id',$class_id);
        $nav_id        = 14;  //导航配置id
        $shouxie_sign  = 'news_yaguan'; //手写位配置标识
        $lunbo_id      = 76; //亚冠轮播图广告id
        $adver_id1     = 77; //亚冠横幅广告id
        $adver_id2     = 65; //射手榜下面广告图id
        $highlights_id = 61; //亚冠热点视频分类id
        $photo_id      = 37; //图片分类id
        $union_id      = $this->unionArr['afccl']; //亚冠的联盟id
        $this->assign('unionId', $union_id);

        //导航
        $nav = D('Home')->getNavList($nav_id);
        $this->assign('nav',$nav);

        //资讯手写位
        if(!$shouxie = S('special_news_'.$viewName))
        {
            $shouxie = D('Home')->getShouXie($shouxie_sign,$class_id,18,6);

            S('special_news_'.$viewName,($shouxie),5*60);
        }
        $this->assign('shouxie',$shouxie);

        //轮播图
        if(!$banner = S('special_banner_'.$viewName))
        {
            $banner = Tool::getAdList($lunbo_id, 5);
            S('special_banner_'.$viewName,($banner), 5*60);
        }
        $this->assign('banner', $banner);

        //英超横幅广告
        if(!$adver1 = S('special_adver1_'.$viewName))
        {
            $adver1 = Tool::getAdList($adver_id1,1)[0];
            S('special_adver1_'.$viewName,($adver1),5*60);
        }
        $this->assign('adver1', $adver1);

        //射手榜下面广告图
        if(!$adver2 = S('special_adver2_'.$viewName))
        {
            $adver2 = Tool::getAdList($adver_id2,1)[0];
            S('special_adver2_'.$viewName,($adver2), 5*60);
        }
        $this->assign('adver2', $adver2);

        //热点视频
        if(!$highlights = S('special_highlight_'.$viewName))
        {
            $highlights = $this->getSpecialHighlights($highlights_id,3);
            S('special_highlight_'.$viewName,($highlights), 60*5);
        }
        $this->assign('highlights', $highlights);

        //积分榜
        $WebfbService = new WebfbService();

        //射手榜
        $archerRank = $WebfbService->getFbUnionArcher($union_id,12);
        $this->assign('archerRank',$archerRank);

        //分类
        $publishClass = $this->publishClass[$class_id];
        $this->assign('publishClass', $publishClass);

        //资讯(包括下级分类)
        if(!$news_all = S('special_newsall_'.$viewName))
        {
            $news_all = $this->getSpecialNews($class_id);
            S('special_newsall_'.$viewName,($news_all),5*60);
        }
        $this->assign('news_all', $news_all);

        //3张最新图片
        if(!$photo = S('special_photo_'.$viewName))
        {
            $photo = $this->getSpecialPhoto($photo_id);
            S('special_photo_'.$viewName,($photo),5*60);
        }
        $this->assign('photo', $photo);

        //获取竞彩分析(独家接盘-英超)
        if(!$dujia = S('special_dujia_'.$viewName))
        {
            $dujia = $this->getSpecialDujia($union_id);
            S('special_dujia_'.$viewName,($dujia),5*60);
        }
        $this->assign('dujia', $dujia);

        //资讯前瞻 战报
        $prospect = $this->getProspect($union_id);
        $report = $this->getReport($union_id);
        //$tttt = array_merge($prospect, $report);var_dump($tttt);exit;
        if($prospect || $report){
            $prospect_report = True;
        }else{
            $prospect_report = False;
        }
        $this->assign('prospect_report', $prospect_report);
        $this->assign('prospect', $prospect);
        $this->assign('report', $report);

        $this->display($viewName);
    }

    //中超专题
    public function csl($viewName){
        $class_id      = 18; //中超分类id
        $this->setSeo($this->publishClass[$class_id]);
        $this->assign('class_id',$class_id);
        $nav_id        = 15;  //导航配置id
        $shouxie_sign  = 'news_zhongchao'; //手写位配置标识
        $lunbo_id      = 54; //中超轮播图广告id
        $adver_id1     = 62; //中超横幅广告id
        $adver_id2     = 65; //射手榜下面广告图id
        $highlights_id = 63; //热点视频分类id
        $photo_id      = 35; //图片分类id
        $union_id      = $this->unionArr['csl']; //中超的联盟id

        //导航
        $nav = D('Home')->getNavList($nav_id);
        $this->assign('nav',$nav);

        //资讯手写位
        if(!$shouxie = S('special_news_'.$viewName))
        {
            $shouxie = D('Home')->getShouXie($shouxie_sign,$class_id,21,7);

            S('special_news_'.$viewName,($shouxie),5*60);
        }
        $this->assign('shouxie',$shouxie);
        //轮播图
        if(!$banner = S('special_banner_'.$viewName))
        {
            $banner = Tool::getAdList($lunbo_id, 5);
            S('special_banner_'.$viewName,($banner), 5*60);
        }
        $this->assign('banner', $banner);

        //英超横幅广告
        if(!$adver1 = S('special_adver1_'.$viewName))
        {
            $adver1 = Tool::getAdList($adver_id1,1)[0];
            S('special_adver1_'.$viewName,($adver1),5*60);
        }
        $this->assign('adver1', $adver1);

        //射手榜下面广告图
        if(!$adver2 = S('special_adver2_'.$viewName))
        {
            $adver2 = Tool::getAdList($adver_id2,1)[0];
            S('special_adver2_'.$viewName,($adver2), 5*60);
        }
        $this->assign('adver2', $adver2);

        //热点视频
        if(!$highlights = S('special_highlight_'.$viewName))
        {
            $highlights = $this->getSpecialHighlights($highlights_id,5);
            S('special_highlight_'.$viewName,($highlights), 60*5);
        }
        $this->assign('highlights', $highlights);

        //积分榜
        $WebfbService = new WebfbService();
        $pointRank = $WebfbService->getFbUnionRank($union_id,12);
        $this->assign('pointRank',$pointRank);

        //射手榜
        $archerRank = $WebfbService->getFbUnionArcher($union_id,12);
        $this->assign('archerRank',$archerRank);

        //英超分类
        $publishClass = $this->publishClass[$class_id];
        $this->assign('publishClass', $publishClass);

        //英超资讯(包括下级分类)
        if(!$news_all = S('special_newsall_'.$viewName))
        {
            $news_all = $this->getSpecialNews($class_id);
            S('special_newsall_'.$viewName,($news_all),5*60);
        }
        $this->assign('news_all', $news_all);

        //英超3张最新图片
        if(!$photo = S('special_photo_'.$viewName))
        {
            $photo = $this->getSpecialPhoto($photo_id);
            S('special_photo_'.$viewName,($photo),5*60);
        }
        $this->assign('photo', $photo);

        //获取竞彩分析(独家接盘-英超)
        if(!$dujia = S('special_dujia_'.$viewName))
        {
            $dujia = $this->getSpecialDujia($union_id);
            S('special_dujia_'.$viewName,($dujia),5*60);
        }
        $this->assign('dujia', $dujia);

        //资讯前瞻 战报
        $prospect = $this->getProspect($union_id);
        $report = $this->getReport($union_id);
        //$tttt = array_merge($prospect, $report);var_dump($tttt);exit;
        if($prospect || $report){
            $prospect_report = True;
        }else{
            $prospect_report = False;
        }
        $this->assign('prospect_report', $prospect_report);
        $this->assign('prospect', $prospect);
        $this->assign('report', $report);


        $this->display($viewName);
    }
    //nba专题
    public function nba($viewName){
        $class_id      = 4; //分类id
        $this->setSeo($this->publishClass[$class_id]);
        $this->assign('class_id',$class_id);
        $nav_id        = 16;  //导航配置id
        $shouxie_sign  = 'news_nba'; //手写位配置标识
        $lunbo_id      = 55; //轮播图广告id
        $adver_id1     = 63; //横幅广告id
        $highlights_id = 64; //热点视频分类id
        $photo_id      = 39; //图片分类id
        $union_id      = $this->unionArr['nba']; //联盟id

        //导航
        $nav = D('Home')->getNavList($nav_id);
        $this->assign('nav',$nav);

        //资讯手写位
        if(!$shouxie = S('special_news_'.$viewName))
        {
            $shouxie = D('Home')->getShouXie($shouxie_sign,$class_id,18,6);

            S('special_news_'.$viewName,($shouxie),5*60);
        }
        $this->assign('shouxie',$shouxie);

        //轮播图
        if(!$banner = S('special_banner_'.$viewName)) 
        {
            $banner = Tool::getAdList($lunbo_id, 5);
            S('special_banner_'.$viewName,($banner), 5*60);
        }
        $this->assign('banner', $banner);

        //英超横幅广告
        if(!$adver1 = S('special_adver1_'.$viewName))
        {
            $adver1 = Tool::getAdList($adver_id1,1)[0];
            S('special_adver1_'.$viewName,($adver1),5*60);
        }
        $this->assign('adver1', $adver1);

        //nba球星
        if(!$qiuxing = S('special_qiuxing_'.$viewName))
        {
            $qiuxing = Tool::getAdList(96,8);
            S('special_qiuxing_'.$viewName,($qiuxing),5*60);
        }
        $this->assign('qiuxing', $qiuxing);

        //热点视频
        if(!$highlights = S('special_highlight_'.$viewName)) 
        {
            $highlights = $this->getSpecialHighlights($highlights_id,3);
            S('special_highlight_'.$viewName,($highlights), 60*5);
        }
        $this->assign('highlights', $highlights);

        //nba积分榜(东部)
        $WebfbService = new WebfbService();
        $pointRank = $WebfbService->getBkUnionRank($union_id,1,12);
        $this->assign('pointRank',$pointRank);

        //nba得分榜
        $archerRank = $WebfbService->getBkUnionRank($union_id,3,12);
        $this->assign('archerRank',$archerRank);

        //分类
        $publishClass = $this->publishClass[$class_id];
        $this->assign('publishClass', $publishClass);
        
        //nba资讯(包括下级分类)
        if(!$news_all = S('special_newsall_'.$viewName))
        {
            $news_all = $this->getSpecialNews($class_id);
            S('special_newsall_'.$viewName,($news_all),5*60);
        }
        $this->assign('news_all', $news_all);

        //nba3张最新图片
        if(!$photo = S('special_photo_'.$viewName))
        {
            $photo = $this->getSpecialPhoto($photo_id);
            S('special_photo_'.$viewName,($photo),5*60);
        }
        $this->assign('photo', $photo);

        //获取竞彩分析(独家接盘-nba)
        if(!$dujia = S('special_dujia_'.$viewName))
        {
            $dujia = $this->getSpecialDujia($union_id,2);
            S('special_dujia_'.$viewName,($dujia),5*60);
        }
        $this->assign('dujia', $dujia);

        $this->display($viewName);
    }

    //获取nba,cna积分榜
    public function getPointRank(){
        $unionId = I('unionId');
        $type    = I('type');
        $WebfbService = new WebfbService();
        $pointRank = $WebfbService->getBkUnionRank($unionId,$type,12);
        $this->success($pointRank);
    }

    //cba专题
    public function cba($viewName){
        $class_id      = 3; //分类id
        $this->setSeo($this->publishClass[$class_id]);
        $this->assign('class_id',$class_id);
        $nav_id        = 17;  //导航配置id
        $shouxie_sign  = 'news_cba'; //手写位配置标识
        $lunbo_id      = 56; //轮播图广告id
        $adver_id1     = 64; //横幅广告id
        $highlights_id = 65; //热点视频分类id
        $photo_id      = 40; //图片分类id
        $union_id      = $this->unionArr['cba'];; //联盟id

        //导航
        $nav = D('Home')->getNavList($nav_id);
        $this->assign('nav',$nav);

        //资讯手写位
        if(!$shouxie = S('special_news_'.$viewName))
        {
            $shouxie = D('Home')->getShouXie($shouxie_sign,$class_id,18,6);

            S('special_news_'.$viewName,($shouxie),5*60);
        }
        $this->assign('shouxie',$shouxie);

        //轮播图
        if(!$banner = S('special_banner_'.$viewName)) 
        {
            $banner = Tool::getAdList($lunbo_id, 5);
            S('special_banner_'.$viewName,($banner), 5*60);
        }
        $this->assign('banner', $banner);

        //英超横幅广告
        if(!$adver1 = S('special_adver1_'.$viewName))
        {
            $adver1 = Tool::getAdList($adver_id1,1)[0];
            S('special_adver1_'.$viewName,($adver1),5*60);
        }
        $this->assign('adver1', $adver1);

        //cba球星
        if(!$qiuxing = S('special_qiuxing_'.$viewName))
        {
            $qiuxing = Tool::getAdList(97,8);
            S('special_qiuxing_'.$viewName,($qiuxing),5*60);
        }
        $this->assign('qiuxing', $qiuxing);

        //热点视频
        if(!$highlights = S('special_highlight_'.$viewName)) 
        {
            $highlights = $this->getSpecialHighlights($highlights_id,3);
            S('special_highlight_'.$viewName,($highlights), 60*5);
        }
        $this->assign('highlights', $highlights);

        //cba积分榜
        $WebfbService = new WebfbService();
        $pointRank = $WebfbService->getBkUnionRank($union_id,1,12);
        $this->assign('pointRank',$pointRank);

        //cba得分榜
        $archerRank = $WebfbService->getBkUnionRank($union_id,3,12);
        $this->assign('archerRank',$archerRank);

        //分类
        $publishClass = $this->publishClass[$class_id];
        $this->assign('publishClass', $publishClass);
        
        //nba资讯(包括下级分类)
        if(!$news_all = S('special_newsall_'.$viewName))
        {
            $news_all = $this->getSpecialNews($class_id);
            S('special_newsall_'.$viewName,($news_all),5*60);
        }
        $this->assign('news_all', $news_all);

        //nba3张最新图片
        if(!$photo = S('special_photo_'.$viewName))
        {
            $photo = $this->getSpecialPhoto($photo_id);
            S('special_photo_'.$viewName,($photo),5*60);
        }
        $this->assign('photo', $photo);

        //获取竞彩分析(独家接盘-nba)
        if(!$dujia = S('special_dujia_'.$viewName))
        {
            $dujia = $this->getSpecialDujia($union_id,2);
            S('special_dujia_'.$viewName,($dujia),5*60);
        }
        $this->assign('dujia', $dujia);
        $this->display($viewName);
    }
    //网球专题页
    public function tennis($viewName){
        $class_id      = 64; //分类id
        $this->setSeo($this->publishClass[$class_id]);
        $this->assign('class_id', $class_id);
        $nav_id        = 24; //导航配置id
        $shouxie_sign  = 'news_tennis'; //手写位配置标识
        $lunbo_id      = 88; //轮播图广告id
        $adver_id1     = 89; //横幅广告id
        $highlights_id = 66; //热点视频分类id

        //导航
        $nav = D('Home')->getNavList($nav_id);
        $this->assign('nav',$nav);

        //资讯手写位
        if(!$shouxie = S('special_news_'.$viewName))
        {
            $shouxie = D('Home')->getShouXie($shouxie_sign,$class_id,8,4);

            S('special_news_'.$viewName,($shouxie),5*60);
        }
        $this->assign('shouxie',$shouxie);

        //轮播图
        if(!$banner = S('special_banner_'.$viewName))
        {
            $banner = Tool::getAdList($lunbo_id, 5);
            S('special_banner_'.$viewName,($banner), 5*60);
        }
        $this->assign('banner', $banner);

        //横幅广告
        // if(!$adver1 = S('special_adver1_'.$viewName))
        // {
        //     $adver1 = Tool::getAdList($adver_id1,1)[0];
        //     S('special_adver1_'.$viewName,($adver1),5*60);
        // }
        // $this->assign('adver1', $adver1);

        //热点视频
        if(!$highlights = S('special_highlight_'.$viewName))
        {
            $highlights = $this->getSpecialHighlights($highlights_id,2);
            S('special_highlight_'.$viewName,($highlights), 60*5);
        }
        $this->assign('highlights', $highlights);

        //分类
        $publishClass = $this->publishClass[$class_id];
        $this->assign('publishClass', $publishClass);

        //资讯(包括下级分类)
        if(!$news_all = S('special_newsall_'.$viewName))
        {
            $news_all = $this->getSpecialNews($class_id);
            S('special_newsall_'.$viewName,($news_all),5*60);
        }
        $this->assign('news_all', $news_all);

        //已有视频id
        $highlightId = [];
        foreach($highlights as $k => &$v){
            $highlightId[$k] = $v['id'];
        }

        //精彩视频
        if(!$highlights2 = S('special_highlight2_'.$viewName))
        {
            $highlights2 = $this->getSpecialHighlights($highlights_id,5,0,$highlightId);
            S('special_highlight2_'.$viewName,($highlights2), 60*5);
        }
        $this->assign('highlights2', $highlights2);

        $this->display($viewName);
    }

    //棒球专题页
    public function baseball($viewName){
        $class_id      = 65; //分类id
        $this->setSeo($this->publishClass[$class_id]);
        $this->assign('class_id', $class_id);
        $nav_id        = 23; //导航配置id
        $shouxie_sign  = 'news_baseball'; //手写位配置标识
        $lunbo_id      = 94; //轮播图广告id
        $adver_id1     = 95; //横幅广告id
        $highlights_id = 67; //热点视频分类id

        //导航
        $nav = D('Home')->getNavList($nav_id);
        $this->assign('nav',$nav);

        //资讯手写位
        if(!$shouxie = S('special_news_'.$viewName))
        {
            $shouxie = D('Home')->getShouXie($shouxie_sign,$class_id,8,4);

            S('special_news_'.$viewName,($shouxie),5*60);
        }
        $this->assign('shouxie',$shouxie);


        //轮播图
        if(!$banner = S('special_banner_'.$viewName))
        {
            $banner = Tool::getAdList($lunbo_id, 5);
            S('special_banner_'.$viewName,($banner), 5*60);
        }
        $this->assign('banner', $banner);

        //横幅广告
        // if(!$adver1 = S('special_adver1_'.$viewName))
        // {
        //     $adver1 = Tool::getAdList($adver_id1,1)[0];
        //     S('special_adver1_'.$viewName,($adver1),5*60);
        // }
        // $this->assign('adver1', $adver1);

        //热点视频
        if(!$highlights = S('special_highlight_'.$viewName))
        {
            $highlights = $this->getSpecialHighlights($highlights_id,2);
            S('special_highlight_'.$viewName,($highlights), 60*5);
        }
        $this->assign('highlights', $highlights);

        //分类
        $publishClass = $this->publishClass[$class_id];
        $this->assign('publishClass', $publishClass);

        //资讯(包括下级分类)
        if(!$news_all = S('special_newsall_'.$viewName))
        {
            $news_all = $this->getSpecialNews($class_id);
            S('special_newsall_'.$viewName,($news_all),5*60);
        }
        $this->assign('news_all', $news_all);

        //已有视频id
        $highlightId = [];
        foreach($highlights as $k => &$v){
            $highlightId[$k] = $v['id'];
        }

        //精彩视频
        if(!$highlights2 = S('special_highlight2_'.$viewName))
        {
            $highlights2 = $this->getSpecialHighlights($highlights_id,5,0,$highlightId);
            S('special_highlight2_'.$viewName,($highlights2), 60*5);
        }
        $this->assign('highlights2', $highlights2);

        $this->display($viewName);
    }
    //斯诺克专题页
    public function snooker($viewName){
        $class_id      = 66; //分类id
        $this->setSeo($this->publishClass[$class_id]);
        $this->assign('class_id', $class_id);
        $nav_id        = 18; //导航配置id
        $shouxie_sign  = 'news_snooker'; //手写位配置标识
        $lunbo_id      = 78; //轮播图广告id
        $adver_id1     = 79; //横幅广告id
        $highlights_id = 68; //热点视频分类id

        //导航
        $nav = D('Home')->getNavList($nav_id);
        $this->assign('nav',$nav);

        //资讯手写位
        if(!$shouxie = S('special_news_'.$viewName))
        {
            $shouxie = D('Home')->getShouXie($shouxie_sign,$class_id,8,4);

            S('special_news_'.$viewName,($shouxie),5*60);
        }
        $this->assign('shouxie',$shouxie);

        //轮播图
        if(!$banner = S('special_banner_'.$viewName))
        {
            $banner = Tool::getAdList($lunbo_id, 5);
            S('special_banner_'.$viewName,($banner), 5*60);
        }
        $this->assign('banner', $banner);

        //横幅广告
        // if(!$adver1 = S('special_adver1_'.$viewName))
        // {
        //     $adver1 = Tool::getAdList($adver_id1,1)[0];
        //     S('special_adver1_'.$viewName,($adver1),5*60);
        // }
        // $this->assign('adver1', $adver1);

        //热点视频
        if(!$highlights = S('special_highlight_'.$viewName))
        {
            $highlights = $this->getSpecialHighlights($highlights_id,2);
            S('special_highlight_'.$viewName,($highlights), 60*5);
        }
        $this->assign('highlights', $highlights);

        //分类
        $publishClass = $this->publishClass[$class_id];
        $this->assign('publishClass', $publishClass);

        //资讯(包括下级分类)
        if(!$news_all = S('special_newsall_'.$viewName))
        {
            $news_all = $this->getSpecialNews($class_id);
            S('special_newsall_'.$viewName,($news_all),5*60);
        }
        $this->assign('news_all', $news_all);

        //已有视频id
        $highlightId = [];
        foreach($highlights as $k => &$v){
            $highlightId[$k] = $v['id'];
        }

        //精彩视频
        if(!$highlights2 = S('special_highlight2_'.$viewName))
        {
            $highlights2 = $this->getSpecialHighlights($highlights_id,5,0,$highlightId);
            S('special_highlight2_'.$viewName,($highlights2), 60*5);
        }
        $this->assign('highlights2', $highlights2);

        $this->display($viewName);
    }
    //美式足球专题页
    public function nfl($viewName){
        $class_id      = 67; //分类id
        $this->setSeo($this->publishClass[$class_id]);
        $this->assign('class_id', $class_id);
        $nav_id        = 25; //导航配置id
        $shouxie_sign  = 'news_nfl'; //手写位配置标识
        $lunbo_id      = 80; //轮播图广告id
        $adver_id1     = 81; //横幅广告id
        $highlights_id = 69; //热点视频分类id

        //导航
        $nav = D('Home')->getNavList($nav_id);
        $this->assign('nav',$nav);

        //资讯手写位
        if(!$shouxie = S('special_news_'.$viewName))
        {
            $shouxie = D('Home')->getShouXie($shouxie_sign,$class_id,8,4);

            S('special_news_'.$viewName,($shouxie),5*60);
        }
        $this->assign('shouxie',$shouxie);

        //轮播图
        if(!$banner = S('special_banner_'.$viewName))
        {
            $banner = Tool::getAdList($lunbo_id, 5);
            S('special_banner_'.$viewName,($banner), 5*60);
        }
        $this->assign('banner', $banner);

        //横幅广告
        // if(!$adver1 = S('special_adver1_'.$viewName))
        // {
        //     $adver1 = Tool::getAdList($adver_id1,1)[0];
        //     S('special_adver1_'.$viewName,($adver1),5*60);
        // }
        // $this->assign('adver1', $adver1);

        //热点视频
        if(!$highlights = S('special_highlight_'.$viewName))
        {
            $highlights = $this->getSpecialHighlights($highlights_id,2);
            S('special_highlight_'.$viewName,($highlights), 60*5);
        }
        $this->assign('highlights', $highlights);

        //分类
        $publishClass = $this->publishClass[$class_id];
        $this->assign('publishClass', $publishClass);

        //资讯(包括下级分类)
        if(!$news_all = S('special_newsall_'.$viewName))
        {
            $news_all = $this->getSpecialNews($class_id);
            S('special_newsall_'.$viewName,($news_all),5*60);
        }
        $this->assign('news_all', $news_all);

        //已有视频id
        $highlightId = [];
        foreach($highlights as $k => &$v){
            $highlightId[$k] = $v['id'];
        }

        //精彩视频
        if(!$highlights2 = S('special_highlight2_'.$viewName))
        {
            $highlights2 = $this->getSpecialHighlights($highlights_id,5,0,$highlightId);
            S('special_highlight2_'.$viewName,($highlights2), 60*5);
        }
        $this->assign('highlights2', $highlights2);

        $this->display($viewName);
    }
    //电竞专题页
    public function esports($viewName){
        $this->display($viewName);
    }
    //英雄联盟专题页
    public function lol($viewName){
        $class_id      = 69; //分类id
        $this->setSeo($this->publishClass[$class_id]);
        $this->assign('class_id', $class_id);
        $nav_id        = 19; //导航配置id
        $shouxie_sign  = 'news_lol'; //手写位配置标识
        $lunbo_id      = 82; //轮播图广告id
        $adver_id1     = 83; //横幅广告id
        $highlights_id = 70; //热点视频分类id

        //导航
        $nav = D('Home')->getNavList($nav_id);
        $this->assign('nav',$nav);

        //资讯手写位
        if(!$shouxie = S('special_news_'.$viewName))
        {
            $shouxie = D('Home')->getShouXie($shouxie_sign,$class_id,8,4);

            S('special_news_'.$viewName,($shouxie),5*60);
        }
        $this->assign('shouxie',$shouxie);

        //轮播图
        if(!$banner = S('special_banner_'.$viewName))
        {
            $banner = Tool::getAdList($lunbo_id, 5);
            S('special_banner_'.$viewName,($banner), 5*60);
        }
        $this->assign('banner', $banner);

        //横幅广告
        // if(!$adver1 = S('special_adver1_'.$viewName))
        // {
        //     $adver1 = Tool::getAdList($adver_id1,1)[0];
        //     S('special_adver1_'.$viewName,($adver1),5*60);
        // }
        // $this->assign('adver1', $adver1);

        //热点视频
        if(!$highlights = S('special_highlight_'.$viewName))
        {
            $highlights = $this->getSpecialHighlights($highlights_id,2);
            S('special_highlight_'.$viewName,($highlights), 60*5);
        }
        $this->assign('highlights', $highlights);

        //分类
        $publishClass = $this->publishClass[$class_id];
        $this->assign('publishClass', $publishClass);

        //资讯(包括下级分类)
        if(!$news_all = S('special_newsall_'.$viewName))
        {
            $news_all = $this->getSpecialNews($class_id);
            S('special_newsall_'.$viewName,($news_all),5*60);
        }
        $this->assign('news_all', $news_all);

        //已有视频id
        $highlightId = [];
        foreach($highlights as $k => &$v){
            $highlightId[$k] = $v['id'];
        }

        //精彩视频
        if(!$highlights2 = S('special_highlight2_'.$viewName))
        {
            $highlights2 = $this->getSpecialHighlights($highlights_id,5,0,$highlightId);
            S('special_highlight2_'.$viewName,($highlights2), 60*5);
        }
        $this->assign('highlights2', $highlights2);
        $this->display($viewName);
    }
    //DOTA2专题页
    public function dota2($viewName){
        $class_id      = 70; //分类id
        $this->setSeo($this->publishClass[$class_id]);
        $this->assign('class_id', $class_id);
        $nav_id        = 20; //导航配置id
        $shouxie_sign  = 'news_dota2'; //手写位配置标识
        $lunbo_id      = 85; //轮播图广告id
        $adver_id1     = 84; //横幅广告id
        $highlights_id = 71; //热点视频分类id

        //导航
        $nav = D('Home')->getNavList($nav_id);
        $this->assign('nav',$nav);

        //资讯手写位
        if(!$shouxie = S('special_news_'.$viewName))
        {
            $shouxie = D('Home')->getShouXie($shouxie_sign,$class_id,8,4);

            S('special_news_'.$viewName,($shouxie),5*60);
        }
        $this->assign('shouxie',$shouxie);

        //轮播图
        if(!$banner = S('special_banner_'.$viewName))
        {
            $banner = Tool::getAdList($lunbo_id, 5);
            S('special_banner_'.$viewName,($banner), 5*60);
        }
        $this->assign('banner', $banner);

        //横幅广告
        // if(!$adver1 = S('special_adver1_'.$viewName))
        // {
        //     $adver1 = Tool::getAdList($adver_id1,1)[0];
        //     S('special_adver1_'.$viewName,($adver1),5*60);
        // }
        // $this->assign('adver1', $adver1);

        //热点视频
        if(!$highlights = S('special_highlight_'.$viewName))
        {
            $highlights = $this->getSpecialHighlights($highlights_id,2);
            S('special_highlight_'.$viewName,($highlights), 60*5);
        }
        $this->assign('highlights', $highlights);

        //分类
        $publishClass = $this->publishClass[$class_id];
        $this->assign('publishClass', $publishClass);

        //资讯(包括下级分类)
        if(!$news_all = S('special_newsall_'.$viewName))
        {
            $news_all = $this->getSpecialNews($class_id);
            S('special_newsall_'.$viewName,($news_all),5*60);
        }
        $this->assign('news_all', $news_all);

        //已有视频id
        $highlightId = [];
        foreach($highlights as $k => &$v){
            $highlightId[$k] = $v['id'];
        }

        //精彩视频
        if(!$highlights2 = S('special_highlight2_'.$viewName))
        {
            $highlights2 = $this->getSpecialHighlights($highlights_id,5,0,$highlightId);
            S('special_highlight2_'.$viewName,($highlights2), 60*5);
        }
        $this->assign('highlights2', $highlights2);
        $this->display($viewName);
    }
    //绝地求生专题页
    public function pubg($viewName){
        $class_id      = 71; //分类id
        $this->setSeo($this->publishClass[$class_id]);
        $this->assign('class_id', $class_id);
        $nav_id        = 22; //导航配置id
        $shouxie_sign  = 'news_pubg'; //手写位配置标识
        $lunbo_id      = 86; //轮播图广告id
        $adver_id1     = 87; //横幅广告id
        $highlights_id = 72; //热点视频分类id

        //导航
        $nav = D('Home')->getNavList($nav_id);
        $this->assign('nav',$nav);

        //资讯手写位
        if(!$shouxie = S('special_news_'.$viewName))
        {
            $shouxie = D('Home')->getShouXie($shouxie_sign,$class_id,8,4);

            S('special_news_'.$viewName,($shouxie),5*60);
        }
        $this->assign('shouxie',$shouxie);

        //轮播图
        if(!$banner = S('special_banner_'.$viewName))
        {
            $banner = Tool::getAdList($lunbo_id, 5);
            S('special_banner_'.$viewName,($banner), 5*60);
        }
        $this->assign('banner', $banner);

        //横幅广告
        // if(!$adver1 = S('special_adver1_'.$viewName))
        // {
        //     $adver1 = Tool::getAdList($adver_id1,1)[0];
        //     S('special_adver1_'.$viewName,($adver1),5*60);
        // }
        // $this->assign('adver1', $adver1);

        //热点视频
        if(!$highlights = S('special_highlight_'.$viewName))
        {
            $highlights = $this->getSpecialHighlights($highlights_id,2);
            S('special_highlight_'.$viewName,($highlights), 60*5);
        }
        $this->assign('highlights', $highlights);

        //分类
        $publishClass = $this->publishClass[$class_id];
        $this->assign('publishClass', $publishClass);

        //资讯(包括下级分类)
        if(!$news_all = S('special_newsall_'.$viewName))
        {
            $news_all = $this->getSpecialNews($class_id);
            S('special_newsall_'.$viewName,($news_all),5*60);
        }
        $this->assign('news_all', $news_all);

        //已有视频id
        $highlightId = [];
        foreach($highlights as $k => &$v){
            $highlightId[$k] = $v['id'];
        }

        //精彩视频
        if(!$highlights2 = S('special_highlight2_'.$viewName))
        {
            $highlights2 = $this->getSpecialHighlights($highlights_id,5,0,$highlightId);
            S('special_highlight2_'.$viewName,($highlights2), 60*5);
        }
        $this->assign('highlights2', $highlights2);
        $this->display($viewName);
    }
    //王者荣耀专题页
    public function pvp($viewName){
        $class_id      = 72; //分类id
        $this->setSeo($this->publishClass[$class_id]);
        $this->assign('class_id', $class_id);
        $nav_id        = 21; //导航配置id
        $shouxie_sign  = 'news_pvp'; //手写位配置标识
        $lunbo_id      = 90; //轮播图广告id
        $adver_id1     = 91; //横幅广告id
        $highlights_id = 73; //热点视频分类id

        //导航
        $nav = D('Home')->getNavList($nav_id);
        $this->assign('nav',$nav);

        //资讯手写位
        if(!$shouxie = S('special_news_'.$viewName))
        {
            $shouxie = D('Home')->getShouXie($shouxie_sign,$class_id,8,4);

            S('special_news_'.$viewName,($shouxie),5*60);
        }

        $this->assign('shouxie',$shouxie);

        //轮播图
        if(!$banner = S('special_banner_'.$viewName))
        {
            $banner = Tool::getAdList($lunbo_id, 5);
            S('special_banner_'.$viewName,($banner), 5*60);
        }
        $this->assign('banner', $banner);


       //横幅广告
       // if(!$adver1 = S('special_adver1_'.$viewName))
       // {
       //     $adver1 = Tool::getAdList($adver_id1,1)[0];
       //     S('special_adver1_'.$viewName,($adver1),5*60);
       // }
       // $this->assign('adver1', $adver1);

        //热点视频
        if(!$highlights = S('special_highlight_'.$viewName))
        {
            $highlights = $this->getSpecialHighlights($highlights_id,2);
            S('special_highlight_'.$viewName,($highlights), 60*5);
        }
        $this->assign('highlights', $highlights);

        //分类
        $publishClass = $this->publishClass[$class_id];
        $this->assign('publishClass', $publishClass);

        //资讯(包括下级分类)
        if(!$news_all = S('special_newsall_'.$viewName))
        {
            $news_all = $this->getSpecialNews($class_id);
            S('special_newsall_'.$viewName,($news_all),5*60);
        }
        $this->assign('news_all', $news_all);

        //已有视频id
        $highlightId = [];
        foreach($highlights as $k => &$v){
            $highlightId[$k] = $v['id'];
        }

        //精彩视频
        if(!$highlights2 = S('special_highlight2_'.$viewName))
        {
            $highlights2 = $this->getSpecialHighlights($highlights_id,5,0,$highlightId);
            S('special_highlight2_'.$viewName,($highlights2), 60*5);
        }
        $this->assign('highlights2', $highlights2);
        $this->display($viewName);
    }

    /*
     * 获取3个最新图库
     * $class_id  分类id
    */
    public function getSpecialPhoto($class_id){
        $photo = M('gallery g')->join("LEFT JOIN qc_gallery_class c on g.class_id=c.id")->field('g.id,g.title,g.add_time,g.img_array,c.path')->where(['g.status'=>1,'g.class_id'=>$class_id])->order("g.add_time desc")->limit(3)->select();
        if(!$photo) return [];
        foreach ($photo as $k => $v) {
            $img_array = json_decode($v['img_array'],true);
            $photo[$k]['images'] = setImgThumb($img_array[1],'240');
            $photo[$k]['href']   = galleryUrl($v['id'],$v['path'],$v['add_time']);
            unset($photo[$k]['img_array']);
        }
        return $photo;
    }

    /*
     * 根据union_id 获取独家解盘专题资讯
     * $class_id  分类id
    */
    public function getSpecialDujia($union_id,$gameType=1){
        if($gameType == 1){
            $dujia = M('publishList')->alias('p')->join('LEFT JOIN qc_game_fbinfo f on f.game_id = p.game_id')->field("p.id,p.class_id,p.title,p.img,p.content,p.add_time,f.union_id")->where(['p.class_id'=>10,'p.status'=>1,'f.union_id'=>$union_id,'p.title'=>['like','%预测%']])->order('p.add_time desc')->limit(16)->group('p.id')->select();
        }else{
            $dujia = M('publishList')->alias('p')->join('LEFT JOIN qc_game_bkinfo f on f.game_id = p.gamebk_id')->field("p.id,p.class_id,p.title,p.img,p.content,p.add_time,f.union_id")->where(['p.class_id'=>10,'p.title'=>['like','%预测%'],'p.status'=>1,'f.union_id'=>['in',[1,5]]])->order('p.add_time desc')->limit(16)->group('p.id')->select();
        }
        if(!$dujia) return [];
        $classArr = getPublishCLass(0); //资讯分类数组
        foreach ($dujia as $k => $v) {
            $dujia[$k]['img']  = newsImgReplace($v);
            $dujia[$k]['href'] = newsUrl($v['id'],$v['add_time'],$v['class_id'],$classArr);
        }
        return $dujia;
    }

    /**
     * *获取视频集锦
     * @param $class_id 分类id
     * @param $limit 数量
     * @param int $recommend 是否推荐
     * @return mixed
     */
    public function getSpecialHighlights($class_id,$limit,$recommend=1,$highlightId=[]){
        if($recommend){
            $order = "h.is_recommend desc, h.add_time desc";
            $where['h.is_recommend'] = 1;//获取推荐
        }else{
            $order = " h.add_time desc";
            $where['h.is_recommend'] = ['in', [0,1]];//不要轮播推荐
        }

        $where['h.class_id'] = $class_id;
        $where['h.status'] = 1;
        if(!empty($highlightId)) $where['h.id'] = ['not in', $highlightId];

        $highlights = M('highlights')->alias('h')
            ->join("LEFT JOIN qc_front_user f on h.user_id = f.id")
            ->field("h.id, h.user_id,h.class_id,h.add_time,h.title,h.img,h.web_url,h.web_ischain,h.click_num,f.nick_name,f.head ")
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->group("h.id")
            ->select();

        $classArr = getVideoClass(0);
        foreach ($highlights as $k => $v) {
            $highlights[$k]['img']  = Tool::imagesReplace($v['img']);
            $highlights[$k]['head'] = frontUserFace($v['head']);
            $highlights[$k]['href'] = videoUrl($v,$classArr);
            $highlights[$k]['click_num'] = addClickConfig(1, $v['class_id'],$v['click_num'], $v['id']);
        }
        return $highlights;
    }

    /**
     * 获取专题资讯
     * @param int $class_id
     * @param int $page
     * @return mixed
     */
    public function getSpecialNews($class_id=0,$top=0)
    {
        $page = I('page',1);//页数 默认1
        $limit = 30;//每页资讯数量

        $where['p.status'] = 1;
        $publishClass = $this->publishClass[$class_id];
        //判断是否有子分类
        if(isset($publishClass['_child'])){
            //合并子分类
            $childClass = array_merge([$class_id],array_keys($publishClass['_child']));
            $where['p.class_id'] = ['in',$childClass];
        }else{
            $where['p.class_id'] = $class_id;
        }

        //是否头条
        if($top == 1){
            $where['p.top_recommend'] = 1;
        }
        //获取资讯
        $news = M('publishList')->alias('p')
            ->join("LEFT JOIN qc_front_user f on p.user_id = f.id")
            ->field("p.id, p.user_id, p.class_id,p.title,p.remark,p.img,p.content,p.add_time,p.click_number,f.head,f.nick_name")
            ->where($where)
            ->page($page,$limit)->order("p.add_time desc")->select();

        $classArr = getPublishCLass(0); //资讯分类数组
        foreach ($news as $k => $v) {
            $news[$k]['img']    = newsImgReplace($v);
            $news[$k]['head']   = frontUserFace($v['head']);
            $news[$k]['remark'] = $v['remark'] ? : msubstr(strip_tags(htmlspecialchars_decode($v['content'])), 0, 90);
            $news[$k]['href']   = newsUrl($v['id'],$v['add_time'],$v['class_id'],$classArr);
            $news[$k]['expert'] = U('/expuser/'.$v['user_id']);
            $news[$k]['add_time'] = date('Y-m-d H:i',$v['add_time']);
            $news[$k]['click_number'] = addClickConfig(1, $v['class_id'],$v['click_number'], $v['id']);
        }
        //资讯栏目链接
        $classUrl = newsClassUrl($class_id,$classArr);
        return ['news'=>$news,'classUrl'=>$classUrl];
    }

    /**
     * 获取分类资讯
     * @param int $class_id 分类id
     * @param int $top      是否头条  默认否
     * @return josn
     */
    public function ajaxGetNews($classid,$top=0)
    {
        $news = $this->getSpecialNews($classid,$top);
        $this->success($news);
    }

    /**
     * 获取欧冠和亚冠的分组积分信息
     */
    public function getGroupData(){
        $unionId = $_POST['unionId'] ?: 0;
        $groupId = $_POST['groupId'] ?: 'A';

        $data = D('Home')->getGroupData($unionId, $groupId);

        if($data){
            $this->ajaxReturn(['status' => 1, 'info' => $data]);
        }else{
            $this->ajaxReturn(['status' => 0, 'info' => '没有更多了']);
        }
    }

    //乒乓球球专题页
    public function pingpong($viewName){
        $class_id      = 93; //分类id
        $this->setSeo($this->publishClass[$class_id]);
        $this->assign('class_id', $class_id);
        $nav_id        = 31; //导航配置id
        $shouxie_sign  = 'news_pingpong'; //手写位配置标识
        $lunbo_id      = 102; //轮播图广告id
        $adver_id1     = 89; //横幅广告id
        $highlights_id = 74; //热点视频分类id

        //导航
        $nav = D('Home')->getNavList($nav_id);
        $this->assign('nav',$nav);

        //资讯手写位
        if(!$shouxie = S('special_news_'.$viewName))
        {
            $shouxie = D('Home')->getShouXie($shouxie_sign,$class_id,8,4);

            S('special_news_'.$viewName,($shouxie),5*60);
        }
        $this->assign('shouxie',$shouxie);

        //轮播图
        if(!$banner = S('special_banner_'.$viewName))
        {
            $banner = Tool::getAdList($lunbo_id, 5);
            S('special_banner_'.$viewName,($banner), 5*60);
        }
        $this->assign('banner', $banner);

        //横幅广告
        // if(!$adver1 = S('special_adver1_'.$viewName))
        // {
        //     $adver1 = Tool::getAdList($adver_id1,1)[0];
        //     S('special_adver1_'.$viewName,($adver1),5*60);
        // }
        // $this->assign('adver1', $adver1);

        //热点视频
        if(!$highlights = S('special_highlight_'.$viewName))
        {
            $highlights = $this->getSpecialHighlights($highlights_id,2);
            S('special_highlight_'.$viewName,($highlights), 60*5);
        }
        $this->assign('highlights', $highlights);

        //分类
        $publishClass = $this->publishClass[$class_id];
        $this->assign('publishClass', $publishClass);

        //资讯(包括下级分类)
        if(!$news_all = S('special_newsall_'.$viewName))
        {
            $news_all = $this->getSpecialNews($class_id);
            S('special_newsall_'.$viewName,($news_all),5*60);
        }
        $this->assign('news_all', $news_all);

        //已有视频id
        $highlightId = [];
        foreach($highlights as $k => &$v){
            $highlightId[$k] = $v['id'];
        }

        //精彩视频
        if(!$highlights2 = S('special_highlight2_'.$viewName))
        {
            $highlights2 = $this->getSpecialHighlights($highlights_id,5,0,$highlightId);
            S('special_highlight2_'.$viewName,($highlights2), 60*5);
        }
        $this->assign('highlights2', $highlights2);

        $this->display($viewName);
    }

    //排球专题页
    public function vollyball($viewName){
        $class_id      = 95; //分类id
        $this->setSeo($this->publishClass[$class_id]);
        $this->assign('class_id', $class_id);
        $nav_id        = 32; //导航配置id
        $shouxie_sign  = 'news_vollyball'; //手写位配置标识
        $lunbo_id      = 103; //轮播图广告id
        $adver_id1     = 89; //横幅广告id
        $highlights_id = 75; //热点视频分类id

        //导航
        $nav = D('Home')->getNavList($nav_id);
        $this->assign('nav',$nav);

        //资讯手写位
        if(!$shouxie = S('special_news_'.$viewName))
        {
            $shouxie = D('Home')->getShouXie($shouxie_sign,$class_id,8,4);

            S('special_news_'.$viewName,($shouxie),5*60);
        }
        $this->assign('shouxie',$shouxie);

        //轮播图
        if(!$banner = S('special_banner_'.$viewName))
        {
            $banner = Tool::getAdList($lunbo_id, 5);
            S('special_banner_'.$viewName,($banner), 5*60);
        }
        $this->assign('banner', $banner);

        //横幅广告
        // if(!$adver1 = S('special_adver1_'.$viewName))
        // {
        //     $adver1 = Tool::getAdList($adver_id1,1)[0];
        //     S('special_adver1_'.$viewName,($adver1),5*60);
        // }
        // $this->assign('adver1', $adver1);

        //热点视频
        if(!$highlights = S('special_highlight_'.$viewName))
        {
            $highlights = $this->getSpecialHighlights($highlights_id,2);
            S('special_highlight_'.$viewName,($highlights), 60*5);
        }
        $this->assign('highlights', $highlights);

        //分类
        $publishClass = $this->publishClass[$class_id];
        $this->assign('publishClass', $publishClass);

        //资讯(包括下级分类)
        if(!$news_all = S('special_newsall_'.$viewName))
        {
            $news_all = $this->getSpecialNews($class_id);
            S('special_newsall_'.$viewName,($news_all),5*60);
        }
        $this->assign('news_all', $news_all);

        //已有视频id
        $highlightId = [];
        foreach($highlights as $k => &$v){
            $highlightId[$k] = $v['id'];
        }

        //精彩视频
        if(!$highlights2 = S('special_highlight2_'.$viewName))
        {
            $highlights2 = $this->getSpecialHighlights($highlights_id,5,0,$highlightId);
            S('special_highlight2_'.$viewName,($highlights2), 60*5);
        }
        $this->assign('highlights2', $highlights2);

        $this->display($viewName);
    }

    //法甲专题页
    public function ligue1($viewName){
        $class_id      = 16; //分类id
        $this->setSeo($this->publishClass[$class_id]);
        $this->assign('class_id', $class_id);
        $nav_id        = 33; //导航配置id
        $shouxie_sign  = 'news_ligue1'; //手写位配置标识
        $lunbo_id      = 106; //轮播图广告id
        $adver_id1     = 89; //横幅广告id
        $highlights_id = 76; //热点视频分类id

        //导航
        $nav = D('Home')->getNavList($nav_id);
        $this->assign('nav',$nav);

        //资讯手写位
        if(!$shouxie = S('special_news_'.$viewName))
        {
            $shouxie = D('Home')->getShouXie($shouxie_sign,$class_id,8,4);

            S('special_news_'.$viewName,($shouxie),5*60);
        }
        $this->assign('shouxie',$shouxie);

        //轮播图
        if(!$banner = S('special_banner_'.$viewName))
        {
            $banner = Tool::getAdList($lunbo_id, 5);
            S('special_banner_'.$viewName,($banner), 5*60);
        }
        $this->assign('banner', $banner);

        //横幅广告
        // if(!$adver1 = S('special_adver1_'.$viewName))
        // {
        //     $adver1 = Tool::getAdList($adver_id1,1)[0];
        //     S('special_adver1_'.$viewName,($adver1),5*60);
        // }
        // $this->assign('adver1', $adver1);

        //热点视频
        if(!$highlights = S('special_highlight_'.$viewName))
        {
            $highlights = $this->getSpecialHighlights($highlights_id,2);
            S('special_highlight_'.$viewName,($highlights), 60*5);
        }
        $this->assign('highlights', $highlights);

        //分类
        $publishClass = $this->publishClass[$class_id];
        $this->assign('publishClass', $publishClass);

        //资讯(包括下级分类)
        if(!$news_all = S('special_newsall_'.$viewName))
        {
            $news_all = $this->getSpecialNews($class_id);
            S('special_newsall_'.$viewName,($news_all),5*60);
        }
        $this->assign('news_all', $news_all);

        //已有视频id
        $highlightId = [];
        foreach($highlights as $k => &$v){
            $highlightId[$k] = $v['id'];
        }

        //精彩视频
        if(!$highlights2 = S('special_highlight2_'.$viewName))
        {
            $highlights2 = $this->getSpecialHighlights($highlights_id,5,0,$highlightId);
            S('special_highlight2_'.$viewName,($highlights2), 60*5);
        }
        $this->assign('highlights2', $highlights2);

        $this->display($viewName);
    }

    //世界杯专题
    public function worldcup($viewName){
        $class_id      = 96; //分类id
        $this->setSeo($this->publishClass[$class_id]);
        $this->assign('class_id', $class_id);
        $nav_id        = 35; //导航配置id
        $shouxie_sign  = 'news_2018worldcup'; //手写位配置标识
        $lunbo_id      = 107; //轮播图广告id
        $adver_id1     = 108; //横幅广告id
        $highlights_id = 77;  //热点视频分类id
        $union_id      = $this->unionArr['2018worldcup']; //联盟id
        $classArr = getPublishCLass(0);
        //导航
        $nav = D('Home')->getNavList($nav_id);

        $this->assign('nav',$nav);

        //资讯手写位
        if(!$shouxie = S('special_news_'.$viewName))
        {
            $shouxie = D('Home')->getShouXie($shouxie_sign,$class_id,12,4);
            S('special_news_'.$viewName,($shouxie),5*60);
        }
        $this->assign('shouxie',$shouxie);

        //轮播图
        if(!$banner = S('special_banner_'.$viewName))

        {
            $banner = Tool::getAdList($lunbo_id, 5);
            S('special_banner_'.$viewName,($banner), 5*60);
        }
        $this->assign('banner', $banner);

        //横幅广告
        if(!$adver1 = S('special_adver1_'.$viewName))
        {
            $adver1 = Tool::getAdList($adver_id1,1)[0];
            S('special_adver1_'.$viewName,($adver1),5*60);
        }
        $this->assign('adver1', $adver1);

        //推荐活动广告
        if(!$adversing = S('special_adversing_'.$viewName))
        {
            $adversing = Tool::getAdList(110,5);
            S('special_adversing_'.$viewName,($adversing),5*60);
        }
        $this->assign('adversing', $adversing);

        //明星资料广告
        if(!$advermx = S('special_advermx_'.$viewName))
        {
            $advermx = Tool::getAdList(111,8);
            S('special_advermx_'.$viewName,($advermx),5*60);
        }
        $this->assign('advermx', $advermx);

        //世界杯活动广告
        if(!$adverhd = S('special_adverhd_'.$viewName))
        {
            $adverhd = Tool::getAdList(112,8);
            S('special_adverhd_'.$viewName,($adverhd),5*60);
        }
        $this->assign('adverhd', $adverhd);

        //世界杯预测分析广告
        if(!$adverfx = S('special_adverfx_'.$viewName))
        {
            $adverfx = Tool::getAdList(113,8);
            S('special_adverfx_'.$viewName,($adverfx),5*60);
        }
        $this->assign('adverfx', $adverfx[0]);

        //热点视频
        if(!$highlights = S('special_highlight_'.$viewName))
        {
            $highlights = $this->getSpecialHighlights($highlights_id,6);
            S('special_highlight_'.$viewName,($highlights), 60*5);
        }
        $this->assign('highlights', $highlights);

        //分类
        $publishClass = $this->publishClass[$class_id];
        $this->assign('publishClass', $publishClass);

        //资讯(包括下级分类)
        if(!$news_all = S('special_newsall_'.$viewName))
        {
            $news_all = $this->getSpecialNews($class_id);
            S('special_newsall_'.$viewName,($news_all),5*60);
        }
        $this->assign('news_all', $news_all);

        //积分榜
        $WebfbService = new WebfbService();
        $pointRank = $WebfbService->getFbUnionRank($union_id,4);
        $this->assign('pointRank',$pointRank);

        //射手榜
        $archerRank = $WebfbService->getFbUnionArcher($union_id,8);
        $this->assign('archerRank',$archerRank);

        //获取联盟数据信息
        if(!$worldcup_gameArr = S('special_worldcup_gameArr')){
            //获取世界杯淘汰赛
            $mService  = mongoService();
            $unionData = $mService->select('fb_union',['union_id'=>$union_id],["statistics.2018.matchResult"]);
            //获取32强国家球队 
            $matchs      = $unionData[0]['statistics']['2018']['matchResult'];
            $arrCupKind  = $matchs['arrCupKind'];
            $matchs16    = $matchs[$arrCupKind[1][4].'_matchs']? :[];//16强
            $matchs8     = $matchs[$arrCupKind[2][4].'_matchs']? :[];//8强
            $matchs4     = $matchs[$arrCupKind[3][4].'_matchs']? :[];//4强
            $matchs2     = $matchs[$arrCupKind[4][4].'_matchs']? :[];//2强
            $matchs1     = $matchs[$arrCupKind[5][4].'_matchs']? :[];//决赛
            $matchsIdArr = array_merge($matchs16,$matchs8,$matchs4,$matchs2,$matchs1);
            $gameArr = $mService->select('fb_game',['game_id'=>[$mService->cmd('in')=>$matchsIdArr]],["game_id","home_team_name","away_team_name","gtime","game_start_timestamp","worldcup_num"],array('gtime'=>1));
            foreach ($gameArr as $k => $v) {
                if(!isset($v['gtime'])){
                    $gameArr[$k]['gtime'] = date('Y-m-d H:i',$v['game_start_timestamp']);
                }
            }

            //世界杯赛事game_id
            $Groups_matchs = $matchs['Groups_matchs'];
            $Groups_matchs_id = [];
            foreach ($Groups_matchs as $k => $v) {
                foreach ($v as $kk => $vv) {
                    $Groups_matchs_id[] = $vv;
                }
            }
            //获取世界杯预测分析资讯
            $yuche_news = M('publishList')->field("id,title,add_time,class_id,content,img")->where(['status'=>1,'game_id'=>['in',$Groups_matchs_id],'title'=>['like','%足球分析预测%']])->order('add_time desc')->limit(4)->select();
            if(!$yuche_news){
                $yuche_news = M('publishList p')->join("LEFT JOIN qc_game_fbinfo g on p.game_id = g.game_id")->field("p.id,p.title,p.add_time,p.class_id,p.content,p.img")->where(['p.status'=>1,'p.class_id'=>10,'g.union_id'=>['in',[36,31,8,34,60,103,192]]])->order('p.add_time desc')->limit(4)->select();
            }
            foreach ($yuche_news as $k => $v) {
                $yuche_news[$k]['img']  = newsImgReplace($v);
                $yuche_news[$k]['href'] = newsUrl($v['id'],$v['add_time'],$v['class_id'],$classArr);
            }

            //获取世界杯赛事推荐
            $blockTime = getBlockTime(1,true);
            $sql = "SELECT g.id,g.game_state,g.game_id,g.union_id,g.gtime,g.score,g.union_name,g.home_team_name,g.away_team_name,g.home_team_id,g.away_team_id,(gn.let_home_num+gn.let_away_num+gn.size_big_num+gn.size_small_num) as quizNum FROM qc_game_fbinfo g 
                    LEFT JOIN qc_gamble_number gn ON gn.game_id = g.game_id 
                    WHERE g.fsw_exp <> ''
                    AND g.fsw_ball <> ''
                    AND g.fsw_exp_home <> ''
                    AND g.fsw_exp_away <> ''
                    AND g.fsw_ball_home <> ''
                    AND g.fsw_ball_away <> ''
                    AND (g.gtime BETWEEN {$blockTime['beginTime']} AND {$blockTime['endTime']} )
                    AND g.is_gamble = 1 
                    AND g.status = 1 ORDER BY quizNum desc";
            $gameHost = M()->query($sql);
            $sort = $quizNum = [];
            foreach ($gameHost as $k => $v) {
                if($v['union_id'] == $union_id){
                    $sort[] = 1;
                }else{
                    $sort[] = 0;
                }
                $quizNum[] = $v['quizNum'];
            }
            //排序，世界杯>推荐数量
            array_multisort($sort,SORT_DESC,$quizNum,SORT_DESC,$gameHost);
            $gameHost = array_slice($gameHost, 0,4);
            //获取实力对抗值
            $fbService = new Api510\Services\AppfbService;
            foreach ($gameHost as $k => $v) {
                $mon = $fbService->getStrengthMon($v['game_id']);
                $gameHost[$k]['home_mon'] = $mon['home'] * 100;
                $gameHost[$k]['away_mon'] = $mon['away'] * 100;
                if($v['game_state'] == 0){
                    $gameHost[$k]['score'] = 'VS';
                }
            }
            setTeamLogo($gameHost);
            $worldcup_gameArr['gameArr'] = $gameArr;
            $worldcup_gameArr['yuche_news'] = $yuche_news;
            $worldcup_gameArr['gameHost'] = $gameHost;
            S('special_worldcup_gameArr',$worldcup_gameArr,300);
        }

        $this->assign('gameArr',$worldcup_gameArr['gameArr']);
        $this->assign('yuche_news',$worldcup_gameArr['yuche_news']);
        $this->assign('gameHost',$worldcup_gameArr['gameHost']);

        //获取特约专家
        if(!$expuser = S('special_worldcup_expuser')){
            $expwhere['p.status'] = 1;
            $expwhere['p.web_recommend'] = ['gt',0];
            if(isset($publishClass['_child'])){
                //合并子分类
                $childClass = array_merge([$class_id],array_keys($publishClass['_child']));
                $expwhere['p.class_id'] = ['in',$childClass];
            }else{
                $expwhere['p.class_id'] = $class_id;
            }
            $expuser = M('publishList p')->join('qc_front_user f on f.id=p.user_id')->field("p.class_id,f.nick_name,f.head,f.id,f.descript,MAX(p.add_time) as add_time")->where($expwhere)->order("add_time desc")->group("p.user_id")->limit(5)->select();
            S('special_worldcup_expuser',$expuser,300);
        }
        $this->assign('expuser',$expuser);

        //获取世界杯图片
        if(!$worldcupImg = S('special_worldcup_img')){
            //9张世界杯
            $worldcupImg = M('gallery g')->join("LEFT JOIN qc_gallery_class c on g.class_id=c.id")->field("g.id,g.add_time,g.title,g.img_array,c.path")->where(['g.status'=>1,'g.class_id'=>38])->order("g.add_time desc")->limit(9)->select()?:[];

            foreach ($worldcupImg as $k => $v) {
                $img_array = json_decode($v['img_array'],true);
                $worldcupImg[$k]['images'] = setImgThumb($img_array[1],'240');
                $worldcupImg[$k]['href']   = galleryUrl($v['id'],$v['path'],$v['add_time']);
                unset($worldcupImg[$k]['img_array'],$worldcupImg[$k]['path'],$worldcupImg[$k]['id'],$worldcupImg[$k]['add_time']);
            }
            S('special_worldcup_img',$worldcupImg,300);
        }
        $this->assign('worldcupImg',$worldcupImg);

        //获取世界杯话题资讯
        if(!$worldcupNews = S('special_worldcup_news')){
            $worldcupNews = M('publishList')->field('id,class_id,title,add_time,img,content')->where(['status'=>1])->order("worldcup_recommend desc,add_time desc")->limit(12)->select();
            foreach ($worldcupNews as $k => $v) {
                $worldcupNews[$k]['img']  = newsImgReplace($v);
                $worldcupNews[$k]['href'] = newsUrl($v['id'],$v['add_time'],$v['class_id'],$classArr);
            }
            S('special_worldcup_news',$worldcupNews,300);
        }
        $this->assign('worldcupNews',$worldcupNews);
        $this->display($viewName);
    }

    //获取世界杯积分榜
    public function getWorldCupRank(){
        $unionId = I('unionId');
        $sign    = I('sign');
        $WebfbService = new WebfbService();
        $pointRank = $WebfbService->getFbUnionRank($unionId,4,$sign);
        $this->success($pointRank);
    }

    /**
     * 推荐游戏推荐提交
     */
    public function requestGamble()
    {
        $titleid  = I('titleid','','int');
        $strData  = I('strData','','string');

        $user_id = is_login();

        if (empty($user_id))
            $this->ajaxReturn(['status'=>2,'info'=>'还没登录喔，亲！']);

        $singleTitle = M('singleTitle')->where(['status'=>1,'id'=>$titleid])->find();

        if ($singleTitle['end_time'] <= time())
            $this->ajaxReturn(['status'=>-1,'info'=>'推荐已经结束，请留意下一期！']);

        if ($strData === '' || !$singleTitle)
            $this->ajaxReturn(['status'=>0,'info'=>'参数出错！']);

        //获取传过来的数据，并进行验证数据的合法性
        $paramArr = explode(',',$strData);
        $time = time();
        $single_id = [];
        foreach ($paramArr as $k => $v) {
            $data = explode(':',$v);
            $Log['title_id']  = $singleTitle['id'];
            $Log['single_id'] = $data[0];
            $Log['quiz_id']   = $data[1];
            $Log['answer']    = $data[2];
            $Log['add_time']  = $time;
            $Log['user_id']   = $user_id;
            $singleLog[] = $Log;
            $single_id[]      = $data[0];
        }

        //获取活动信息,检查这些问题是否已经出了结果
        $SingleQuiz = M('SingleQuiz')->master(true)->where(['single_id'=>['in',$single_id]])->select();
        foreach ($SingleQuiz as $k => $v) {
            if($v['re_answer'] >= 0){
                $this->ajaxReturn(['status'=>-1,'info'=>'推荐结果已经公布！']);
            }
        }   
        
        $singleLogId = M('SingleLog')->master(true)
            ->where(['single_id'=>['in'=>$single_id],'user_id'=>$user_id])
            ->getField('id');

        if (! empty($singleLogId))
            $this->ajaxReturn(['status'=>-1,'info'=>'已经推荐选过了喔，亲！']);

        $res = M('SingleLog')->addAll($singleLog);

        if ($res === false)
            $this->ajaxReturn(['status'=>0,'info'=>'提交失败，请重新提交！']);

        //记录选择的答案的次数+加上原来的基数
        foreach ($SingleQuiz as $key => $value)
        {
            //获取选择，并转换格式
            $activityLog[$key]['option'] = json_decode($value['option'],true);
            $signLog = false;
            foreach ($singleLog as $k => $v)
            {
                if ($value['single_id'] == $v['single_id'])
                {
                    foreach ($activityLog[$key]['option'] as $item => $itemV)
                    {
                        if ($itemV['aid'] == $v['answer'])
                        {
                            $activityLog[$key]['option'][$item]['num'] += 1;

                            $signLog = true;
                        }
                    }
                }
                if ($signLog) break;
            }
            //转换格式
            $activityLog[$key]['option'] = json_encode($activityLog[$key]['option']);
            M()->query('UPDATE qc_single_quiz set option = '.json_encode($activityLog[$key]['option']).' where id = '.$value['id']);
        }
        $this->ajaxReturn(['status'=>1,'info'=>'提交成功']);
    }

    //获取直播条
    public function getLive(){
        $type = I('type');
        //联赛id
        switch ($type) {
            case 'premierleague':
                $union_id = $this->unionArr['premierleague'];
                break;
            case 'laliga':
                $union_id = $this->unionArr['laliga'];
                break;
            case 'bundesliga':
                $union_id = $this->unionArr['bundesliga'];
                break;
            case 'seriea':
                $union_id = $this->unionArr['seriea'];
                break;
            case 'csl':
                $union_id = $this->unionArr['csl'];
                break;
        }
        $where['union_id'] = $union_id;
        $mongo = mongoService();
        $season = $mongo->fetchRow('fb_union',$where,['season']);
        $year = $season['season'][0];
        $unionData = $mongo->select('fb_union',$where,["statistics.".$year.".matchResult.round","statistics.".$year.".matchResult.jh"]);
        $round     = $unionData[0]['statistics'][$year]['matchResult']['round']; //轮次
        $gameIdArr = $unionData[0]['statistics'][$year]['matchResult']['jh'];    //赛事数据
        $round = explode('/', $round);
        $allNum = $round[1]; //总轮次
        $nowNum = $round[0]; //当前轮次

        if(!$live = S('special_live_live_'.$union_id)){
            $idArr = call_user_func_array('array_merge',$gameIdArr); 
            $live = mongo('fb_game')->field('game_id,game_starttime,game_state,home_team_name,away_team_name,score,round')->where(['game_id'=>['in',$idArr]])->select();
            foreach ($live as $k => $v) {
                $live[$k]['gtime'] = $v['game_starttime']->sec;
                unset($live[$k]['game_starttime']);
            }
            S('special_live_live_'.$union_id,$live,120);
        }

        if(!$live){
            $this->ajaxReturn(['status'=>0]);
        }
        //排序处理
        foreach ($live as $k => $v) {
            $gtime[]   = $v['gtime'];
            $game_id[] = $v['game_id'];
        }
        array_multisort($gtime,SORT_ASC,$game_id,SORT_ASC,$live);

        $data = [];
        foreach ($live as $k => $v) {
            $gtime = $v['gtime'];
            $v['gtime'] = date('m-d H:i',$gtime);
            $v['home_team_name'] = $v['home_team_name'][0];
            $v['away_team_name'] = $v['away_team_name'][0];
            if(!isset($v['game_state'])){
                if($gtime < time()){
                    $v['game_state'] = -1;
                }else{
                    $v['game_state'] = 0;
                }
            }

            //比分状态判断
            if( in_array($v['game_state'], [0,-10,-11,-12,-13,14]) || $v['score'] == '' ){
                $v['score'] = 'VS';
            }
        
            //链接
            if(in_array($v['game_state'], [1,2,3,4])){
                $v['href'] = U('/live/'.$v['game_id'].'@bf');
            }else if($v['game_state'] == -1){
                $v['href'] = U('/news@bf',['game_id'=>$v['game_id']]);
            }else{
                $v['href'] = U('/dataFenxi@bf',['game_id'=>$v['game_id']]);
            }
            //分轮次
            $rno = explode('_', $v['round']);
            $v['rno'] = $rno[1];
            unset($v['_id'],$v['round']);
            for ($i=0; $i < $allNum; $i++) { 
                if($rno[1] == ($i+1)){
                    $data[$i][] = $v;
                }
            }
        }

        $this->ajaxReturn(['status'=>1,'allNum'=>$allNum,'nowNum'=>$nowNum,'info'=>$data]);
    }

    //欧冠和亚冠获取直播条
    public function getLive2(){
        $type = I('type');

        if(!$res = S('getLive2'.$type));
        {
            //定义赛事类型
            $unionName = C('fb_union_name');
            //联赛id
            switch ($type) {
                case 'championsleague':
                    $union_id = $this->unionArr['championsleague'];
                    $nowNum = 5;
                    break;
                case 'afccl':
                    $union_id = $this->unionArr['afccl'];
                    $nowNum = 4;
                    break;
                case '2018worldcup':
                    $union_id = $this->unionArr['2018worldcup'];
                    $nowNum = 0;
                    break;
            }
            $where['union_id'] = $union_id;
            $mService = mongoService();

            $season = $mService->fetchRow('fb_union',$where,['season']);
            $year = $season['season'][0];
            if($type == 'championsleague'){
                $year = '2017-2018';
            }
            //获取当前比赛进度
            $arrCupKind = $mService->select('fb_union',$where,["statistics.".$year.".matchResult.arrCupKind"]);
            $arrCupKind = $arrCupKind[0]['statistics'][$year]['matchResult']['arrCupKind'];
            $unionDataName = end($arrCupKind)[4].'_matchs';//最后一个
            $data = $this->handleGame($unionDataName,$where,$union_id,$year);
            $xiaozu = $data['xiaozu'];
            $taotai = $data['taotai'];
            if($taotai)
            {
                $nameKey = count($taotai);
                foreach($taotai as $key=>$val)
                {
                    $taotai[$key]['unionName'] = $unionName[$nameKey];
                }
            }

            //查询所有淘汰赛
            array_pop($arrCupKind);
            $arrCupKind = array_reverse($arrCupKind);
            foreach ($arrCupKind as $val)
            {
                $unionDataName = $val[4].'_matchs';//最后一个
                $tmp = $this->handleGame($unionDataName,$where,$union_id,$year);
                $gameTmp = $tmp['taotai'];
                $nameKey = count($gameTmp);
                //多余数据不处理
                if(!$unionName[$nameKey]) continue;
                foreach($gameTmp as $key=>$val)
                {
                    $gameTmp[$key]['unionName'] = $unionName[$nameKey];
                }
                $taotai = array_merge($taotai,$gameTmp);
            }
 
            //小组赛分轮次
            $xiaozu = array_chunk($xiaozu, 8);
            $taotai = array_chunk($taotai, 8);
            $res = ['status'=>1,'nowNum'=>$nowNum,'nowGroup'=>null,'xiaozu'=>$xiaozu,'taotai'=>$taotai];
            S('getLive2'.$type,$res,3000);//缓存
        }
        $this->ajaxReturn($res);
    }

    //处理赛事数据
    public function handleGame($unionDataName,$where,$union_id,$year)
    {
        $mService = mongoService();
        //获取联赛数据
        if(!$unionData = S('special_live_union_'.$union_id.$unionDataName)){
            //获取比赛
            $unionData = $mService->select('fb_union',$where,["statistics.".$year.".matchResult.".$unionDataName,"statistics.".$year.".matchResult.Groups_matchs"]);
            S('special_live_union_'.$union_id.$unionDataName,$unionData,3600);
        }

        $gameIdArr = $unionData[0]['statistics'][$year]['matchResult']['Groups_matchs'];    //赛事数据
        if($unionDataName != 'Groups_matchs'){
            $final16   = $unionData[0]['statistics'][$year]['matchResult'][$unionDataName];
        }

        //小组赛事game_id
        $xxIdArr = !empty($gameIdArr) ? call_user_func_array('array_merge',$gameIdArr) : [];
        //淘汰赛事game_id
        if(!empty($final16)){
            $ttIdArr = [];
            if($union_id == 75){
                $ttIdArr = $final16;
            }else{
                foreach ($final16 as $k => $v) {
                    if(isset($v[4])){
                        $ttIdArr[] = $v[4];
                    }
                    if(isset($v[5])){
                        $ttIdArr[] = $v[5];
                    }
                }
            }
            $idArr = array_merge($xxIdArr,$ttIdArr);
        }else{
            $idArr = $xxIdArr;
        }

        //获取赛事数据
        if(!$live = S('special_live_live_'.$union_id.$unionDataName)){
            $live = mongo('fb_game')->field('game_id,game_starttime,game_state,home_team_name,away_team_name,score,round')->where(['game_id' => ['IN', $idArr]])->select();
            foreach ($live as $k => $v) {
                $live[$k]['gtime'] = $v['game_starttime']->sec;
                unset($live[$k]['game_starttime']);
            }
            S('special_live_live_'.$union_id.$unionDataName,$live,120);
        }
        if(!$live){
            return [];
        }
        //排序
        foreach ($live as $k => $v) {
            $gtime[]   = $v['gtime'];
            $game_id[] = $v['game_id'];
        }
        array_multisort($gtime,SORT_ASC,$game_id,SORT_ASC,$live);

        $xiaozu = $taotai = [];
        foreach ($live as $k => $v) {
            $gtime = $v['gtime'];
            $v['gtime'] = date('m-d H:i',$gtime);
            $v['home_team_name'] = $v['home_team_name'][0];
            $v['away_team_name'] = $v['away_team_name'][0];
            if(!isset($v['game_state'])){
                if($gtime < time()){
                    $v['game_state'] = -1;
                }else{
                    $v['game_state'] = 0;
                }
            }
            //比分状态判断
            if( in_array($v['game_state'], [0,-10,-11,-12,-13,14]) || $v['score'] == '' ){
                $v['score'] = 'VS';
            }
            //链接
            if(in_array($v['game_state'], [1,2,3,4])){
                $v['href'] = U('/live/'.$v['game_id'].'@bf');
            }else if($v['game_state'] == -1){
                $v['href'] = U('/news@bf',['game_id'=>$v['game_id']]);
            }else{
                $v['href'] = U('/dataFenxi@bf',['game_id'=>$v['game_id']]);
            }
            $v['round'] = substr($v['round'],-1);
            unset($v['_id']);
            if(in_array($v['game_id'], $xxIdArr)){
                $xiaozu[] = $v;
            }
            if(in_array($v['game_id'], $ttIdArr)){
                $taotai[] = $v;
            }
        }
        return ['xiaozu'=>$xiaozu,'taotai'=>$taotai];
    }

    //五洲专题页
    public function wuzhou($viewName){
        $class_id      = 107; //分类id
        $this->setSeo($this->publishClass[$class_id]);
        $this->assign('class_id', $class_id);
        $nav_id        = 41; //导航配置id
        $shouxie_sign  = 'news_wuzhou'; //手写位配置标识
        $lunbo_id      = 109; //轮播图广告id
        $adver_id1     = 89; //横幅广告id
        $highlights_id = 78; //热点视频分类id

        //导航
        $nav = D('Home')->getNavList($nav_id);
        $this->assign('nav',$nav);

        //资讯手写位
        if(!$shouxie = S('special_news_'.$viewName))
        {
            $shouxie = D('Home')->getShouXie($shouxie_sign,$class_id,8,4);

            S('special_news_'.$viewName,($shouxie),5*60);
        }
        $this->assign('shouxie',$shouxie);

        //轮播图
        if(!$banner = S('special_banner_'.$viewName))
        {
            $banner = Tool::getAdList($lunbo_id, 5);
            S('special_banner_'.$viewName,($banner), 5*60);
        }
        $this->assign('banner', $banner);

        //横幅广告
        // if(!$adver1 = S('special_adver1_'.$viewName))
        // {
        //     $adver1 = Tool::getAdList($adver_id1,1)[0];
        //     S('special_adver1_'.$viewName,($adver1),5*60);
        // }
        // $this->assign('adver1', $adver1);

        //热点视频
        if(!$highlights = S('special_highlight_'.$viewName))
        {
            $highlights = $this->getSpecialHighlights($highlights_id,2);
            S('special_highlight_'.$viewName,($highlights), 60*5);
        }
        $this->assign('highlights', $highlights);

           //分类
        $publishClass = $this->publishClass[$class_id];
        $this->assign('publishClass', $publishClass);

        //资讯(包括下级分类)
        if(!$news_all = S('special_newsall_'.$viewName))
        {
            $news_all = $this->getSpecialNews($class_id);
            S('special_newsall_'.$viewName,($news_all),5*60);
        }
        $this->assign('news_all', $news_all);

        //已有视频id
        $highlightId = [];
        foreach($highlights as $k => &$v){
            $highlightId[$k] = $v['id'];
        }

        //精彩视频
        if(!$highlights2 = S('special_highlight2_'.$viewName))
        {
            $highlights2 = $this->getSpecialHighlights($highlights_id,5,0,$highlightId);
            S('special_highlight2_'.$viewName,($highlights2), 60*5);
        }
        $this->assign('highlights2', $highlights2);

        //资讯前瞻 战报
        $prospect = $this->getProspect(0);
        $report = $this->getReport(0);
        //$tttt = array_merge($prospect, $report);var_dump($tttt);exit;
        if($prospect || $report){
            $prospect_report = True;
        }else{
            $prospect_report = False;
        }
        $this->assign('prospect_report', $prospect_report);
        $this->assign('prospect', $prospect);
        $this->assign('report', $report);

        $this->display($viewName);
    }


    /**
     * 赛程
     */
    public function schedule(){
        $mService = mongoService();

        //所有世界杯赛程
        if(!$schedule = S('world_cup_matchResult')){
            $mService->index = ['union_id' => 1];
            $wc = $mService->fetchRow('fb_union', ['union_id' => 75]);
            $schedule = $wc['statistics'][2018]['matchResult'];
            S('world_cup_matchResult', $schedule, 600);
        }
        $group_matchs = $schedule['Groups_matchs'];//小组赛

        $arrCupKind = $schedule['arrCupKind'];
        //根据轮次获取赛事ID
        $gStr = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q'];
        $gameIdArr = $gameIdMap = [];
        foreach ($arrCupKind as $k => $v) {
            $rn = str_replace('.', '_', $v[4]);

            if ($v[1] == 1) {//分组赛
                for ($i = 0; $i < $v[5]; $i++) {
                    foreach ($schedule[$rn . '_matchs'][$gStr[$i]] as $k1 => $v1) {
                        $gameIdArr[] = $v1;
                    }
                }
            } elseif ($v[1] == 0) {
                if ($v[7] != 1) {//淘汰赛
                    foreach ($schedule[$rn . '_matchs'] as $tk => $tv) {
                        $gameIdArr[] = $gameIdMap[$rn . '_matchs'][] = $tv;
                    }

                } else {//主客场
                    foreach ($schedule[$rn . '_matchs'] as $dk => $dv) {
                        $gameIdArr[] = $gameIdMap[$rn . '_matchs'][] = $dv[4];
                        $gameIdArr[] = $gameIdMap[$rn . '_matchs'][] = $dv[5];
                    }
                }
            }
        }

        //比赛详情
        if(!$games = S('web_world_cup_schedule_games')){
            if($gameIdArr){
                // $games = $mService->select(
                //     'fb_game',
                //     ['game_id' => ['$in' => $gameIdArr]],
                //     ['game_id','gtime','home_team_name','away_team_name','score','worldcup_num','game_state'],
                //     ['gtime' => 1]
                // );
                $games = mongo('fb_game')
                    ->field(['game_id','gtime','home_team_name','away_team_name','score','worldcup_num','game_state', 'game_start_timestamp','home_team_id','away_team_id'])
                    ->where(['game_id' => ['IN', $gameIdArr]])
                    ->select();
                S('web_world_cup_schedule_games', $games, 5);
            }
        }

        //是否有竞猜
        $ginfo = M('GameFbinfo')->alias('g')
            ->field('game_id,is_gamble,is_sub,is_show,fsw_exp_away,fsw_exp,fsw_exp_home,fsw_ball,fsw_ball_home,fsw_ball_away')
            ->join('LEFT JOIN qc_union u ON g.union_id=u.union_id')
            ->where(['game_id' => ['IN', $gameIdArr]])
            ->select();

        $gamble = $group_schedule = $fmatchs = $knockout_matchs = $giant_matchs = [];
        foreach($ginfo as $ik => $iv){
            $gamble[$iv['game_id']] = 1;
            if ($iv['is_gamble'] != 1 || ($iv['is_sub'] > 2 && $iv['is_show'] != 1)) {
                $gamble[$iv['game_id']] = 0;
            }

            if ($iv['fsw_exp'] == '' ||
                $iv['fsw_exp_home'] == '' ||
                $iv['fsw_exp_away'] == '' ||
                $iv['fsw_ball'] == '' ||
                $iv['fsw_ball_home'] == '' ||
                $iv['fsw_ball_away'] == '') {
                $gamble[$iv['game_id']] = 0;
            }
        }

        //豪门赛程
        $giantTeamMaps = [
            '德国' => 650,
            '巴西' => 778,
            '葡萄牙' => 765,
            '阿根廷' => 766,
            '比利时' => 645,
            '西班牙' => 772,
            '法国' => 649,
            '英格兰' => 744
        ];
        $giantTeamSort = [
            '德国' => 1,
            '巴西' => 2,
            '葡萄牙' => 3,
            '阿根廷' => 4,
            '比利时' => 5,
            '西班牙' => 6,
            '法国' => 7,
            '英格兰' => 8
        ];
        $giant_name = array_keys($giantTeamMaps);

        $knockoutRound = ['1/8','1/4','1/2','34名','决赛'];
        $sort = [];
        foreach($games as $gk1 => $gv1){
            $sort[] = $gv1['gtime'] ? strtotime($gv1['gtime']) : $gv1['game_start_timestamp'];
        }
        array_multisort($sort, SORT_ASC, $games);
        
        foreach($games as $gk => $gv){
            unset($games[$gk]['_id']);
            if(!isset($gv['gtime'])){
                $games[$gk]['gtime'] = date('Y-m-d H:i',$gv['game_start_timestamp']);
            }
            $games[$gk]['round'] = $gv['worldcup_num'];
            $games[$gk]['score'] = $gv['game_state'] == 0 ? '-' : $gv['score'];
            $games[$gk]['home_team_name'] = $gv['home_team_name'][0];
            $games[$gk]['away_team_name'] = $gv['away_team_name'][0];
            $games[$gk]['gamble'] = $gamble[$gv['game_id']];
            $games[$gk]['gtime'] = $gv['gtime'] ?$gv['gtime'] : date('Y-m-d H:i', $gv['game_start_timestamp']);

            if(!isset($v['game_state'])){
                if($gv['gtime'] && strtotime($gv['gtime']) < time() || $gv['game_start_timestamp'] && $gv['game_start_timestamp'] < time()){
                    $games[$gk]['game_state'] = -1;
                }else{
                    $games[$gk]['game_state'] = 0;
                }
            }
            
            //小组赛程
            foreach($group_matchs as $gmk => $gmv){
                if(in_array($gv['game_id'], $gmv)){
                    $group_schedule[$gmk][] = $games[$gk];
                }
            }

            //淘汰赛
            $gameIdMap = array_combine($knockoutRound, array_values($gameIdMap));

            foreach($gameIdMap as $ggk => $ggv){
                if(in_array($gv['game_id'], $ggv)){
                    $knockout_matchs[$ggk][] = $fmatchs[] = $games[$gk];
                    foreach($knockoutRound as $kk1 => $vv1){
                        if($vv1 == $ggk){
                            $knockout_matchs_sort[$ggk] = $kk1;
                        }
                    }
                }
            }

            if(in_array($gv['home_team_name'][0], $giant_name)){
                $giant_matchs[$gv['home_team_name'][0]][] = $games[$gk];
            }
            if(in_array($gv['away_team_name'][0], $giant_name)){
                $giant_matchs[$gv['away_team_name'][0]][] = $games[$gk];
            }
        }


        //豪门赛程排序
        foreach($giant_matchs as $gkn2 => $gkv2){
            $teamSort[] = $giantTeamSort[$gkn2];
        }
        array_multisort($teamSort, SORT_ASC, $giant_matchs);


        if($callback = I('callback')){
            //赛程积分-小组积分榜
            $temp = $mService->fetchRow('fb_union',
                ['union_id' => 75],
                ["statistics.2018.matchResult"]
            );

            $matchResult = $temp['statistics']['2018']['matchResult'];
            $arrCupKind = $matchResult['arrCupKind'];

            foreach ($arrCupKind as $k2 => $v2) {
                if ($v2[1] == 1) {
                    $score_rank = $matchResult[$v2[4]];
                }
            }

            //球队信息
            foreach ($score_rank as $sk => $sv){
                foreach($sv as $k => $v){
                    $team_ids[] = $v[1];
                }
            }

            //球队信息
            if ($team_ids) {
                $u = $mService->select(
                    'fb_team',
                    ['team_id' => ['$in' => $team_ids]],
                    ["team_id", "team_name", 'img_url', 'images', "team_value", 'image_urls']
                );

                $logo = '/Public/Home/images/info/zone-team.png';
                foreach ($u as $uk => $uv) {
                    if($uv['img_url']){
                        $logo =  C('IMG_SERVER') . $uv['img_url'];
                    }else{
                        $logo = !empty($uv['images'][0]) ? str_replace('full/','https://img3.qqty.com/', $uv['images'][0]['path']) : '';
                    }
                    unset($uv['_id']);
                    $teams[$uv['team_id']]['team_name'] = $uv['team_name'][0];
                    $teams[$uv['team_id']]['img_url'] = $logo;
                    $teams[$uv['team_id']]['value'] = $uv['team_value'];
                    $teams_value_sort[] = $uv['team_value'];
                    $teams[$uv['team_id']]['team_id'] = $uv['team_id'];
                }
            }

            //积分榜数据处理
            foreach ($score_rank as $sk1 => $sv1){
                foreach($sv1 as $k1 => $v1){
                    $_r['rank'] = $v1[0];
                    $_r['team_id'] = $v1[1];
                    $_r['team_name'] = $teams[$v1[1]]['team_name'];
                    $_r['team_logo'] = $teams[$v1[1]]['img_url'];
                    $_r['count'] = $v1[2];//总场数
                    $_r['win'] = $v1[3];//胜场数
                    $_r['draw'] = $v1[4];//平场数
                    $_r['lose'] = $v1[5];//输场数
                    $_r['int'] = $v1[9];//积分
                    $point_rank[$sk1][$k1] = $_r;
                }
            }

            $ajaxData = [
                'status' => 1,
                'data' => [
                    'day_schedule'=> $games,
                    'group_schedule'=> $group_schedule,
                    'giant_matchs'=> $giant_matchs,
                    'point_rank'=> $point_rank
                ]
            ];

            if($callback = I('callback')){
                echo htmlspecialchars($callback) . "(".json_encode($ajaxData).")";
                return;
            }
        }else{
            $seo = [
                'seo_title' => '2018世界杯完整赛程|世界杯赛程_2018俄罗斯世界杯赛程_全球体育网',
                'seo_keys'  => '2018年世界杯,世界杯赛程,2018年俄罗斯世界杯,世界杯赛事',
                'seo_desc'  => '全球体育网2018俄罗斯世界杯赛程频道实时报道最新世界杯赛程对阵情况以及晋级球队淘汰对阵赛程，让您第一时间获悉世界杯赛程动态。',
                ];
            $this->setSeo($seo);
            $nav = D('Home')->getNavList(35);
            $this->assign('nav', $nav);
        }
        //小组赛排序
        $groups_keys = array_keys($group_schedule);
        array_multisort($groups_keys, SORT_ASC, $group_schedule);
        //淘汰赛排序
        array_multisort(array_values($knockout_matchs_sort), SORT_ASC, $knockout_matchs);

        $this->assign('day_schedule', $games);//日程赛程
        $this->assign('group_schedule', $group_schedule);//小组赛程
        $this->assign('knockout_matchs', $knockout_matchs);//淘汰赛
        $this->assign('fmatchs', $fmatchs);
        $this->assign('giant_matchs', $giant_matchs);
        $this->assign('giantTeamImgs', $giantTeamMaps);
        $this->display();
    }

    //用户反馈
    public function tiyubf(){
        if(IS_AJAX && IS_POST){
            if(!check_form_token()){
                $this->error('发送失败，请稍后重试！');
            }
            $userInfo = session('user_auth');

            $feedback_sign = get_client_ip().'feedback_sign';
            if(S($feedback_sign)){
                $this->error('请等待60秒后重新发送');
            }
            $content = I('content');
            $feedback['content']     = $content;
            $feedback['phone']       = I('phone');
            $feedback['create_time'] = time();
            $result = M('feedback')->add($feedback);
            if($result){
                //发送短信通知运营
                $feedbackConfig = C('feedbackConfig');
                if($feedbackConfig['mobile'] != ''){
                    sendingSMS($feedbackConfig['mobile'],"反馈内容：{$content}");
                }
                S($feedback_sign,1,$feedbackConfig['sendTime']);
                $this->success('反馈成功');
            }else{
                $this->error('反馈失败');
            }
        }
        $this->display('Copyright/feed');
    }

    /*
     *  资讯前瞻
     */
    public function getProspect($union_id=0){
        $prospect_id = 108;
        $publishProspect = M('PublishList');
        $today = strtotime(date("Y-m-d"),time());
        $end = $today+60*60*24;
        if($union_id){
            if($union_id == 'all'){
                $where_str = 'class_id = '.$prospect_id.' AND add_time BETWEEN '.$today.' AND '.$end;
            }else{
                $where_str = 'union_id = '.$union_id.' AND class_id = '.$prospect_id.' AND add_time BETWEEN '.$today.' AND '.$end;
            }
        }else{
            $where_str = 'union_id NOT in (36, 31, 8, 34, 103, 192, 60, 75) AND class_id = '.$prospect_id.' AND add_time BETWEEN '.$today.' AND '.$end;
        }
        $prospect = $publishProspect->where($where_str)->order('add_time desc')->limit(10)->select();
        $classArr  = getPublishClass(0);
        foreach ($prospect as $k => $v) {
            $prospect[$k]['href'] = newsUrl($v['id'], $v['add_time'], $v['class_id'], $classArr);
        }
        return $prospect;
    }

    /*
     *  资讯战报
     */
    public function getReport($union_id=0){
        $prospect_id = 109;
        $publishProspect = M('PublishList');
        $today = strtotime(date("Y-m-d"),time());
        $start = $today-60*60*24;
        $end = $today+60*60*24;
        if($union_id){
            if($union_id == 'all'){
                $where_str = 'class_id = '.$prospect_id.' AND add_time BETWEEN '.$start.' AND '.$end;
            }else{
                $where_str = 'union_id = '.$union_id.' AND class_id = '.$prospect_id.' AND add_time BETWEEN '.$start.' AND '.$end;
            }
        }else{
            $where_str = 'union_id NOT IN (36, 31, 8, 34, 103, 192, 60, 75) AND class_id = '.$prospect_id.' AND add_time BETWEEN '.$start.' AND '.$end;
        }
        $report = $publishProspect->where($where_str)->order('add_time desc')->limit(10)->select();
        $classArr  = getPublishClass(0);
        foreach ($report as $k => $v) {
            $report[$k]['href'] = newsUrl($v['id'], $v['add_time'], $v['class_id'], $classArr);
        }
        return $report;
    }

}