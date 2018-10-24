<?php
use Think\Model;
use Think\Tool\Tool;
use Home\Services\AppdataService;
use Home\Services\WebfbService;

/**
 * 综合模型
 */
class HomeModel extends Model{

    /**
     * 获取首页第一屏的左边资讯：随机4个
     * @return array
     */
    public function getNewsList(){
        $list = $this->getShouXie('news_shouye');
        $newsOne = array_chunk($list, 4);
        return $newsOne;
    }

    /**
     * 获取首页第一屏的精彩视频
     */
    public function getHighlights(){
        $highlights = M("Highlights h")->where(['h.status' => 1])
            ->join('left join qc_highlights_class c on h.class_id = c.id')
            ->field("h.id,h.class_id,h.game_id,h.game_type,h.title,h.img,h.web_url,h.web_ischain,h.add_time,c.name")
            ->order("h.top_recommend desc, h.add_time desc")
            ->limit(9)->select();

        $classArr = getVideoClass(0);
        foreach ($highlights as $k => &$v) {
            $v['href'] = videoUrl($v,$classArr);
            $v['img'] = (string)setImgThumb($v['img'], '200');
        }

        return $highlights;
    }

    /**
     * 获取首页第一屏右边，直播比赛列表
     * 1.取当天正在直播的十场比赛，加上完场；足球和篮球各10场一起排序；
     * 2.筛选条件：直播赛事（一级赛事优先 > 有视频直播 > 有动画直播 >按开赛的时间先后）
     * 3.l.is_link：1, l.md_id有值，才有动画直播
     */
    public function getLiveGame(){
        //足球
        // $blockTime = getBlockTime(1, true);//获取竞猜分割日期的区间时间
        // $fbGame = M('GameFbinfo g')->field('g.game_state as gameState, g.union_name as unionName, g.gtime, g.home_team_name as homeTeamName, g.away_team_name as awayTeamName, g.game_id as gameId, g.score, g.is_video, g.app_video, u.is_sub, l.is_link, l.md_id')
        //     ->join('left join qc_union u on g.union_id = u.union_id')
        //     ->join('left join qc_fb_linkbet l on g.game_id = l.game_id')
        //     ->where(['u.is_sub' => ['exp', 'is not null'], 'g.game_state' => ['in', [1, 2, 3, 4, -1]], 'g.gtime' => ['between', [strtotime('-1 day', $blockTime['beginTime']), $blockTime['endTime']]], 'g.status'=>1])
        //     ->order('gameState desc, u.is_sub asc, g.is_video desc, g.app_video desc, concat(l.is_link,l.md_id) desc, g.gtime desc')
        //     ->limit(20)->select();
        $webfbService = new \Home\Services\WebfbService();
        $gameData = $webfbService->fbtodayList($unionId, $subId);
        $fbGame = $gameData['info']; //赛事
        //dump($fbGame);

        foreach ($fbGame as $fk => &$fv) {
            $fv['gameType'] = 1;//足球
        }

        //篮球
        /*
        $blockTime = getBlockTime(2, true);//获取竞猜分割日期的区间时间
        $bkGame = M('GameBkinfo g')->field('g.game_state as gameState, g.union_name as unionName, g.gtime, g.home_team_name as homeTeamName, g.away_team_name as awayTeamName, g.game_id as gameId, g.score, g.is_video, g.app_video, u.is_sub, l.is_link, l.md_id')
            ->join('left join qc_bk_union u on g.union_id = u.union_id')
            ->join('left join qc_bk_linkbet l on g.game_id = l.game_id')
            ->where(['u.is_sub' => ['exp', 'is not null'], 'g.game_state' => ['in', [1, 2, 3, 4, 5, 6, -1]], 'g.gtime' => ['between', [strtotime('-1 day', $blockTime['beginTime']), $blockTime['endTime']]], 'g.status'=>1])
            ->order('gameState desc, u.is_sub asc, g.is_video desc, g.app_video desc, concat(l.is_link,l.md_id) desc, g.gtime desc')
            ->limit(10)->select();

        foreach ($bkGame as $bk => &$bv) {
            $bv['gameType'] = 2;//篮球
        }
*/
//            $liveGame = array_merge($fbGame, $bkGame);
        $gameState = $is_sub = $gtime = $shipin = $donghua = $is_bet = $liveGame = [];
        foreach ($fbGame as $k => &$v) {
            $is_bet[]              = $v[42];//是否竞彩
            $shipin[]              = $v[40];//视频
            $donghua[]             = $v[41];//动画
            $gameState[]           = $v[7]; //比赛状态
            $is_sub[]              = $v[6]; //联盟级别
            $game_time             = strtotime($v[8].$v[9]);
            $gtime[]               = $game_time; //比赛时间
            $video['gameId']       = $v[0];
            $video['gameType']     = 1;
            $video['gtime']        = date('m/d H:i', $game_time);
            $video['score']        = [$v[21],$v[22]];
            $video['unionName']    = $v[2];
            $video['homeTeamName'] = $v[13];
            $video['awayTeamName'] = $v[16];
            $video['gameState']    = $v[7];
            $video['is_bet']       = $v[42];
            $liveGame[] = $video;
        }

        //排序 比赛状态》是否竞彩》视频》动画》联盟级别》比赛时间
        array_multisort($gameState, SORT_DESC, $is_bet ,SORT_DESC,$shipin, SORT_DESC, $donghua, SORT_DESC,$is_sub, SORT_ASC, $gtime, SORT_ASC, $liveGame);
        $liveGame = array_slice($liveGame, 0, 10);

        return $liveGame;
    }


    /**
     * 获取首页第二屏左边资讯
     * 英超资讯、西甲资讯、德甲资讯、意甲资讯、其它资讯：各5条
     * 13,14,15,17，其他27,96,18
     */
    public function getIndexNewsTwo(){
//        $newsClass = [13 => '英超', 14 => '西甲', 15 => '德甲', 17 => '意甲', 27 => '欧冠', 28 => '亚冠', 18 => '中超'];
        $newsClass = [13 => '英超足球推荐', 14 => '西甲足球推荐', 15 => '德甲足球推荐', 17 => '意甲足球推荐'];
        $classList = [13,14,15,17,[107]];

        $PublishClass = getPublishClass();
        $classArr = getPublishClass(0);

        $list = [];
        foreach($classList as $k => &$v){
            if(is_array($v)){
                $ids = ['in', $v];
            }else{
                $idArr = array_keys($PublishClass[$v]['_child']);
                $idArr[] = $v;
                $ids = ['in', $idArr];
            }
            $subClass = $PublishClass[$v]['_child'];
            foreach ($subClass as $s => $ss) {
                $subClass[$s]['href'] = newsClassUrl($ss['id'],$classArr);
            }
            $list[$k]['subClass'] = is_array($v) ? '' : $subClass;
            $list[$k]['className'] = $newsClass[$v] ?: '其它足球推荐';
            $news = M('PublishList')->field('id,title,add_time,class_id')->where(['class_id' => $ids, 'status' => 1, 'web_recommend' => ['in', [0,1]]])->order('web_recommend desc, update_time desc')->limit(5)->select();
            foreach ($news as $kk => $vv) {
                $news[$kk]['href'] = newsUrl($vv['id'],$vv['add_time'],$vv['class_id'],$classArr);
            }
            $list[$k]['list'] = $news;
        }

        return $list;
    }

    /**
     * 获取首页第二屏中间
     */
    public function getIndexNewsTwoMiddle(){
        $classArr = getPublishClass(0);
        //资讯：国际足球的分类：英超、西甲、德甲、意甲、欧冠、亚冠、世界杯
        $data['list'] = M('PublishList')->field('id, class_id, title, img, remark, content, add_time')->where(['class_id' => ['in', [13,14,15,17,27,96]], 'status' => 1])
            ->order('web_recommend desc, update_time desc')->limit(4)->select();

        foreach($data['list'] as $k => &$v){
            $v['img'] = newsImgReplace($v);
            $v['href'] = newsUrl($v['id'],$v['add_time'],$v['class_id'],$classArr);
            $v['remark'] = $v['remark'] ? msubstr($v['remark'], 0, 24) : msubstr(strip_tags(htmlspecialchars_decode($v['content'])), 0, 46);
            unset($v['content'], $v['add_time'], $v['class_id']);
        }

        //视频精选：昨日点播量最高的三个，每天早上10:30更新
        //分类:英超、西甲、德甲、意甲、欧冠、法甲、世界杯
        $blockTime = getBlockTime(1, true);//获取竞猜分割日期的区间时间
        $data['highlights'] = M("Highlights h")->where(['h.status' => 1])
            ->field("h.id,h.class_id,h.game_id,h.game_type,h.title,h.img,h.web_url,h.web_ischain,h.add_time")
            ->where(['h.class_id' => ['in', [56,57,58,59,60,76,77]],'h.add_time' => ['lt', strtotime('-1 day', $blockTime['endTime'])]])
            ->order("h.add_time desc,h.click_num desc")
            ->limit(3)->select();

        $videoClass = getVideoClass(0);
        foreach ($data['highlights'] as $k => &$v) {
            $v['href'] = videoUrl($v,$videoClass);
            $v['img'] = (string)setImgThumb($v['img'], '200');
        }

        //国际足球精彩图片：筛选最新足球图库类目最新上传的4张
        $galleryClass = array_keys(getGalleryClass()[41]['_child']);
        $data['gallery'] = M("Gallery g")->join('left join qc_gallery_class c on g.class_id=c.id')
            ->where(['g.class_id'=>['in', $galleryClass],'g.status'=>1])
            ->field("g.id,g.class_id,g.title,g.img_array,g.add_time,c.path")->order("g.add_time desc")->limit(4)->select();
        foreach ($data['gallery'] as $k => &$v) {
            $v['href'] = galleryUrl($v['id'], $v['path'], $v['add_time']);
            $img_array = json_decode($v['img_array'], true);
            $v['img'] = setImgThumb($img_array[1], '240');
            unset($v['img_array']);
        }

        return $data;
    }

    /**
     * 第二屏右边，积分榜，射手榜，活动专题
     */
    public function getIndexNewsTwoRight($type = false){
        //世界杯积分榜，射手榜
        $appService = new WebfbService();
        $legueRank  = $appService->getFbUnionRank(75);

        $data['legueRank']  = $legueRank; 

        //活动专题
        $class_id = M('recommendClass')->where(['sign'=>'appZT'])->getField('id');
        $data['topic'] = (array)M("Recommend")->field(['id','title','url','img'])
            ->where(['class_id'=>$class_id, 'status'=>1])
            ->order("sort desc")->limit(5)->select();

        foreach($data['topic'] as $k => &$v){
            $v['img'] = (string)Tool::imagesReplace($v['img']);
            if($type) $v['url'] = str_replace('://m','://www',$v['url']);
        }

        return $data;
    }

    /**
     * 获取首页第二屏：国内足球
     */
    public function getIndexNewsTwoDown(){
        $data['fbNav'] = $this->getNavList(30, 'name, ui_type_value as url');
        $classArr = getPublishClass(0);

        //左侧资讯模块：重点推荐文,4条
        $list1 = M('PublishList')->field('id, class_id, title, img, remark, content, add_time')->where(['class_id' => ['in',[18,28]], 'status' => 1])
            ->order('web_recommend desc, update_time desc')->limit(4)->select();

        foreach($list1 as $k => &$v){
            $v['img'] = newsImgReplace($v);
            $v['href'] = newsUrl($v['id'],$v['add_time'],$v['class_id'],$classArr);
            $v['remark'] = $v['remark'] ? msubstr($v['remark'], 0, 24) : msubstr(strip_tags(htmlspecialchars_decode($v['content'])), 0, 46);
            unset($v['content'], $v['add_time'], $v['class_id']);
        }

        $data['list'] = $list1;

        //中间图文模块：7条
        $list2 = M('PublishList')->field('id, class_id, title, img, remark, content, add_time')->where(['class_id' => ['in',[18,28]], 'status' => 1])
            ->order('web_recommend desc, update_time desc')->limit(7)->select();

        foreach($list2 as $k => &$v){
            $v['img'] = newsImgReplace($v);
            $v['href'] = newsUrl($v['id'],$v['add_time'],$v['class_id'],$classArr);
//            $v['remark'] = $v['remark'] ? msubstr($v['remark'], 0, 24) : '';
            unset($v['content'], $v['add_time'], $v['class_id']);
        }

        $data['list2'] = $list2;

        //精选视频：选取三条最新的国内足球视频文章
        $data['highlights'] = M("Highlights h")->where(['h.status' => 1])
            ->field("h.id,h.class_id,h.game_id,h.game_type,h.title,h.img,h.web_url,h.web_ischain,h.add_time")
            ->where(['h.class_id' => ['in',[61,63]]])
            ->order("h.add_time desc")
            ->limit(3)->select();
        $videoClass = getVideoClass(0);
        foreach ($data['highlights'] as $k => &$v) {
            $v['href'] = videoUrl($v,$videoClass);
            $v['img'] = (string)setImgThumb($v['img'], '200');
        }

        //右侧体坛动态模块：（待完善）

        return $data;
    }

    /**
     * 第三屏：篮球专栏；
     */
    public function getIndexNewsThird(){
        //专栏配置
        $data['bkNav'] = $this->getNavList(6, 'name, ui_type_value as url');

        //NBA，10条
        $classArr = getPublishClass(0);
        $nbalist1 = M('PublishList')->field('id, class_id, title, img, if(remark is null, "", remark) as remark, content, add_time')->where(['class_id' => 4, 'status' => 1])
            ->order('web_recommend desc, update_time desc')->limit(2)->select();

        foreach($nbalist1 as $k => &$v){
            $v['img'] = newsImgReplace($v);
            $v['href'] = newsUrl($v['id'],$v['add_time'],$v['class_id'],$classArr);
            $v['remark'] = $v['remark'] ? msubstr($v['remark'], 0, 24) : msubstr(strip_tags(htmlspecialchars_decode($v['content'])), 0, 46);
            unset($v['content'], $v['add_time'], $v['class_id']);
        }

        $data['nba1'] = $nbalist1;

        //重点推荐2条
        $nbalist2 = M('PublishList')->field('id, class_id, title, img, if(remark is null, "", remark) as remark, content, add_time')->where(['class_id' => 4, 'status' => 1])
            ->order('web_recommend desc, update_time desc')->limit(10)->select();

        foreach($nbalist2 as $k => &$v){
            $v['img'] = newsImgReplace($v);
            $v['href'] = newsUrl($v['id'],$v['add_time'],$v['class_id'],$classArr);
//            $v['remark'] = $v['remark'] ? msubstr($v['remark'], 0, 24) : '';
            unset($v['content'], $v['add_time'], $v['class_id']);
        }

        $data['nba2'] = $nbalist2;

        //精选视频：选取三条最新的nba视频文章
        $data['nba3'] = M("Highlights h")->where(['h.status' => 1])
            ->field("h.id,h.class_id,h.game_id,h.game_type,h.title,h.img,h.web_url,h.web_ischain,h.add_time")
            ->where(['h.class_id' => 64])
            ->order("h.add_time desc")
            ->limit(3)->select();
        $videoClass = getVideoClass(0);
        foreach ($data['nba3'] as $k => &$v) {
            $v['href'] = videoUrl($v,$videoClass);
            $v['img'] = (string)setImgThumb($v['img'], '200');
        }

        //CBA，重点4条
        $cbalist1 = M('PublishList')->field('id, class_id, title, img, if(remark is null, "", remark) as remark, content, add_time')->where(['class_id' => 3, 'status' => 1])
            ->order('web_recommend desc, update_time desc')->limit(4)->select();

        foreach($cbalist1 as $k => &$v){
            $v['img'] = newsImgReplace($v);
            $v['href'] = newsUrl($v['id'],$v['add_time'],$v['class_id'],$classArr);
            $v['remark'] = $v['remark'] ? msubstr($v['remark'], 0, 24) : msubstr(strip_tags(htmlspecialchars_decode($v['content'])), 0, 46);
            unset($v['content'], $v['add_time'], $v['class_id']);
        }

        $data['cba1'] = $cbalist1;
        //推荐7条
        $cbalist2 = M('PublishList')->field('id, class_id, title, img, if(remark is null, "", remark) as remark, content, add_time')->where(['class_id' => 3, 'status' => 1])
            ->order('web_recommend desc, update_time desc')->limit(7)->select();

        foreach($cbalist2 as $k => &$v){
            $v['img'] = newsImgReplace($v);
            $v['href'] = newsUrl($v['id'],$v['add_time'],$v['class_id'],$classArr);
//            $v['remark'] = $v['remark'] ? msubstr($v['remark'], 0, 24) : '';

            unset($v['content'], $v['add_time'], $v['class_id']);
        }

        $data['cba2'] = $cbalist2;

        //精选视频：选取三条最新的nba视频文章
        $data['cba3'] = M("Highlights h")->where(['h.status' => 1])
            ->field("h.id,h.class_id,h.game_id,h.game_type,h.title,h.img,h.web_url,h.web_ischain,h.add_time")
            ->where(['h.class_id' => 65])
            ->order("h.add_time desc")
            ->limit(3)->select();
        $videoClass = getVideoClass(0);
        foreach ($data['cba3'] as $k => &$v) {
            $v['href'] = videoUrl($v,$videoClass);
            $v['img'] = (string)setImgThumb($v['img'], '200');
        }

        //官网直通车
        $data['webList'] = M('Link')->field('name, url')->where(['position' => 5, 'status' => 1])->order('sort asc')->select();

        return $data;
    }

    /**
     * 获取首页第四屏左边资讯
     * 网球、棒球、斯诺克、美式足球：各6条
     * 64,65,66,67
     */
    public function getIndexNewsFour(){
        //专栏配置
        $data['sportNav'] = $this->getNavList(7, 'name, ui_type_value as url');

        $newsClass = [64 => '网球资讯', 93 => '乒乓球资讯', 66 => '斯诺克资讯', 95 => '排球资讯'];
        $classList = [64,93,66,95];

        $data['list1'] = [];
        $classArr = getPublishClass(0);
        foreach($classList as $k => &$v){
            $data['list1'][$k]['className'] = $newsClass[$v];
            $num = $k<2 ? 4 : 3;
            $news = M('PublishList')->field('id, class_id, title, add_time')->where(['class_id' => $v, 'status' => 1])
                ->order('web_recommend desc, update_time desc')->limit($num)->select();
            foreach ($news as $kk => $vv) {
                $news[$kk]['href'] = newsUrl($vv['id'],$vv['add_time'],$vv['class_id'],$classArr);
                unset($vv['add_time'], $vv['class_id']);
            }
            $data['list1'][$k]['list'] = $news;
        }

        //资讯：网球、棒球、斯诺克、美式足球
        /*
        $data['list2'] = M('PublishList')->field('id, class_id, title, img, if(remark is null, "", remark) as remark, content, add_time')->where(['class_id' => ['in', $classList], 'status' => 1, 'web_recommend' => ['in', [0,1]]])
            ->order('web_recommend desc, update_time desc')->limit(3)->select();

        foreach($data['list2'] as $k => &$v){
            $v['img'] = newsImgReplace($v);
            $v['href'] = newsUrl($v['id'],$v['add_time'],$v['class_id'],$classArr);
            $v['remark'] = $v['remark'] ? msubstr($v['remark'], 0, 24) : '';

            unset($v['content'], $v['add_time'], $v['class_id']);
        }
*/
        //视频精选：当天最新的
        $data['highlights'] = M("Highlights h")->where(['h.status' => 1])
            ->field("h.id,h.class_id,h.game_id,h.game_type,h.title,h.img,h.web_url,h.web_ischain,h.add_time")
            ->where(['h.class_id' => ['in', [66,67,68,69]]])
            ->order("h.add_time desc")
            ->limit(3)->select();
        $videoClass = getVideoClass(0);
        foreach ($data['highlights'] as $k => &$v) {
            $v['href'] = videoUrl($v,$videoClass);
            $v['img'] = (string)setImgThumb($v['img'], '200');
        }

        //精彩图片：筛选最新图库类目最新上传的4张
        $galleryClass = array_keys(getGalleryClass()[9]['_child']);
        $data['gallery'] = M("Gallery g")->join('left join qc_gallery_class c on g.class_id=c.id')
            ->where(['g.class_id'=>['in', $galleryClass],'g.status'=>1])
            ->field("g.id,g.class_id,g.title,g.img_array,g.add_time,c.path")
            ->order("g.add_time desc")->limit(4)->select();
        foreach ($data['gallery'] as $k => &$v) {
            $v['href'] = galleryUrl($v['id'], $v['path'], $v['add_time']);
            $img_array = json_decode($v['img_array'], true);
            $v['img'] = (string)setImgThumb($img_array[1], '240');
            unset($v['img_array']);
        }

        return $data;
    }

    /**
     * 第四屏：电竞
     * 英雄联盟/DOTA2/绝地求生/王者荣耀，各2条
     * 69,70,71,72
     */
    public function getIndexNewsFourGame(){
        //专栏配置
        $data['gameNav'] = $this->getNavList(8, 'name, ui_type_value as url');

        $classList = [69,70,71,72];
        $classArr = getPublishClass(0);
        $data['list'] = [];
        foreach($classList as $k => &$v){
            $list = M('PublishList p')->field('p.id, p.class_id, p.title, p.img, if(p.remark is null, "", p.remark) as remark, p.content, p.add_time, c.name as className')
                    ->join('left join qc_Publish_class c on p.class_id = c.id')
                    ->where(['p.class_id' => $v, 'p.status' => 1])
                    ->order('p.web_recommend desc, p.update_time desc')->limit(2)->select();

            if($list) $data['list'] = array_merge($data['list'], $list);
        }

        foreach($data['list'] as $k => &$v){
            if($k == 0){//第一张要图片
                $v['remark'] = $v['remark'] ? msubstr($v['remark'], 0, 24) : msubstr(strip_tags(htmlspecialchars_decode($v['content'])), 0, 46);
                $v['img'] = (string)newsImgReplace($v);
            }else{
                unset($v['img'], $v['remark']);
            }

            $v['href'] = newsUrl($v['id'],$v['add_time'],$v['class_id'],$classArr);
            unset($v['content'], $v['add_time'], $v['class_id']);
        }

        //视频精选：当天最新的
        $data['highlights'] = M("Highlights h")->where(['h.status' => 1])
            ->join('left join qc_highlights_class c on h.class_id = c.id')
            ->field("h.id,h.class_id,h.game_id,h.game_type,h.title,h.img,h.web_url,h.web_ischain,h.add_time,c.name as className")
            ->where(['h.class_id' => ['in', [70,71,72,73]]])
            ->order("h.add_time desc")
            ->limit(6)->select();
        $videoClass = getVideoClass(0);
        foreach ($data['highlights'] as $k => &$v) {
            $v['href'] = videoUrl($v,$videoClass);
            $v['img'] = (string)setImgThumb($v['img'], '200');
        }

        return $data;
    }

    /**
     * 获取配置栏
     * @param $type
     * @param $field
     * @return array
     */
    public function getNavList($type, $field='id,name, ui_type_value as url'){
        if(!$list = S('qqty_nav_list')){
            $list = M('Nav')->field($field.',type')->where(['status' => 1])->order('sort asc')->select();
            S('qqty_nav_list',json_encode($list),5*60);
        }

        //返回对应导航
        $nav = array();
        foreach ($list as $k => $v) {
            if($v['type'] == $type){
                $nav[] = $v;
            }
        }
        return $nav;
    }

    /**
     * 积分榜
     * @param $unionId
     * @return array
     */
    public function getLeagueRank($unionId){
        if(empty($unionId)) return [];
        $appService = new AppdataService();
        $legueRank = $appService->getLeagueIntegral($unionId);//英超
        $legueRank = array_slice($legueRank, 0, 12);

        return $legueRank;
    }

    /**
     * 资料库联赛射手榜
     * @param $unionId
     * @return array
     */
    public function archer($unionId){
        $appService = new AppdataService();
        $res = $appService->getArcher($unionId);
        $res = array_slice($res, 0, 10);

        return $res;
    }

    /**
     * 获取WEB首页直播比赛的html主体
     */
    public function getIndexLiveGames(){
        $liveGame = $this->getLiveGame();

        if($liveGame){
            $html = '<ul class="clearfix als-wrapper">';
            foreach($liveGame as $k => &$v){
                $html .= '<li class="als-item">';
                if($v['gameType'] == 1 && $v['gameState'] == -1){
                    $html .= '<a href="'.U('/event_technology/game_id/'.$v['gameId'].'@bf').'" target="_blank">';
                }else if($v['gameType'] == 1 && $v['gameState'] != -1){
                    $html .= '<a href="'.U('live/'.$v['gameId'].'@bf').'" target="_blank">';
                }else{
                    $html .= '<a href="javascript:;">';
                }
                $html .= '<div class="item-tt clearfix"><strong class="pull-left matchName">'.$v['unionName'].'</strong> <time class="pull-right">'.$v['gtime'].'</time></div>
							<div class="clearfix teamHome"><span class="pull-left teamName">'.$v['homeTeamName'].'</span> <strong class="pull-right teamScore">'.$v['score'][0].'</strong></div>
						<div class="clearfix teamAway"><span class="pull-left teamName">'.$v['awayTeamName'].'</span> <strong class="pull-right teamScore">'.$v['score'][1].'</strong></div>
						<div class="liveTypeCon">';
                if(in_array($v['gameState'], [1,2,3,4])){
                    $html .= '<span class="liveType">直播中</span>';
                }else if($v['gameState'] == -1){
                    $html .= '<span class="endType">已完赛</span>';
                }else{
                    $html .= '<span class="endType">未开</span>';
                }
                $html .= '</div></a></li>';
            }

            $html .= '</ul>';

            return $html;
        }else{
            return '';
        }
    }


    /**
     * 获取首页篮球联赛信息
     */
    public function getIndexBkData($unionId, $type)
    {
        if (empty($unionId) || empty($type)) return false;

        $Webfb = new WebfbService;
        $data = $Webfb->getBkUnionRank($unionId, $type);

        if ($data) {
            if (in_array($type, [1, 2])) {
                $html = '<thead>
								<tr>
									<th width="60">排名</th>
									<th width="100">球队</th>
									<th width="80">胜/负</th>
									<th width="50">胜率</th>
								</tr>
							</thead><tbody>';
                foreach ($data as $k => &$v) {
                    switch ($k) {
                        case 0:
                            $class = 'class="noRank noOne"';
                            break;
                        case 1:
                            $class = 'class="noRank noTwo"';
                            break;
                        case 2:
                            $class = 'class="noRank noThree"';
                            break;
                        case 3:
                            $class = 'class="noRank noFour"';
                            break;
                        default:
                            $class = '';
                    }
                    if($k == 0) {
                        $bg = 'bgcolor="#fafafa"';
                    }else{
                        $bg = '';
                    }
                    $html .= '<tr '.$bg.'>
									<td><strong ' . $class . '>' . $v['rank'] . '</strong></td>
									<td>';
                    if ($k == 0) {
                        $html .= '<div class="matchLogo"><img src="' . $v['team_logo'] . '" width="48" height="48"></div>';
                    }
                    $html .= '<p class="teamName" title="'. $v['team_name'] . '">' . $v['team_name'] . '</p></td>
									<td>' . $v['win'] . '/' . $v['lose'] . '</td>
									<td>' . $v['win_ratio'] . '%</td>';
                }

                $html .= '</tbody>';
            } else {
                $html = '<thead><tr>
									<th width="46">排名</th>
									<th width="100">球员</th>
									<th width="100">球队</th>
									<th width="50">场均</th>
								</tr>
								</thead><tbody>';
                foreach ($data as $k => &$v) {
                    switch ($k) {
                        case 0:
                            $class = 'class="noRank noOne"';
                            break;
                        case 1:
                            $class = 'class="noRank noTwo"';
                            break;
                        case 2:
                            $class = 'class="noRank noThree"';
                            break;
                        case 3:
                            $class = 'class="noRank noFour"';
                            break;
                        default:
                            $class = '';
                    }

                    if($k == 0) {
                        $bg = 'bgcolor="#fafafa"';
                    }else{
                        $bg = '';
                    }
                    $html .= '<tr '.$bg.'>
									<td><strong ' . $class . '>' . $v['rank'] . '</strong></td>';
                    if ($k == 0) {
                        $html .= '<td><div class="playerLogo"><img src="' . $v['player_logo'] . '" width="48" height="48"></div><p class="playerName text-hidden">' . $v['player_name'] . '</p></td>
									<td><div class="matchLogo"><img src="' . $v['team_logo'] . '" width="48" height="48"></div><p class="teamName">' . $v['team_name'] . '</p></td>';
                    }
//                    else if (in_array($k, [1, 2])) {
//                        $html .= '<td><p class="playerName text-hidden"><img src="' . $v['player_logo'] . '" width="24" height="24" class="img-circle" alt="' . $v['player_name'] . '">' . $v['player_name'] . '</p></td>
//									<td><p class="teamName text-hidden">' . $v['team_name'] . '</p></td>';
//                    }
                    else {
                        $html .= '<td><p class="playerName text-hidden" title="'. $v['player_name'] .'">' . $v['player_name'] . '</p></td>
									<td><p class="teamName text-hidden" title="'. $v['team_name'] .'">' . $v['team_name'] . '</p></td>';
                    }
                    $html .= '<td><strong> ' . $v['val'] . '</strong></td>
								</tr>';
                }

                $html .= '</tbody>';
            }

            return $html;
        }else{
            return '';
        }
    }

    /**
     * 获取首页足球联赛信息
     * @param $unionId
     * @param string $group
     * @return string
     */
    public function getIndexFbData($unionId, $group='A'){
        if(empty($unionId)) return '';

        $appService = new WebfbService();
        $legueRank = $appService->getFbUnionRank($unionId, 10, $group);
        $archerList = $appService->getFbUnionArcher($unionId);

        if($legueRank || $archerList){
            if($legueRank){
                $html1 = '';
                foreach($legueRank as $k => $v) {
                    switch($k){
                        case 0: $class = 'class="noRank noOne"'; break;
                        case 1: $class = 'class="noRank noTwo"'; break;
                        case 2: $class = 'class="noRank noThree"'; break;
                        case 3: $class = 'class="noRank noFour"'; break;
                        default: $class = '';
                    }

                    if($k == 0) {
                        $bg = 'bgcolor="#fafafa"';
                    }else{
                        $bg = '';
                    }
                    $html1 .= ' <tr '.$bg.'>
                                        <td><strong '.$class.'>'.$v['rank'].'</strong></td>
                                        <td>';
                    if($k == 0) {
                        $html1 .= '<div class="matchLogo"><img src="'.$v['team_logo'].'" width="48" height="48"></div>';
                    }
                    $html1 .=  '<p class="teamName text-hidden" title="'.$v['team_name'].'">'.$v['team_name'].'</p>
                                        </td>
                                        <td>'.$v['win'].'/'.$v['draw'].'/'.$v['lose'].' </td>
                                        <td><strong> '.$v['int'].'</strong></td>
                                    </tr>';
                }
            }else{
                $html1 = '<tr><td colspan="4">暂时没有数据哦！</td></tr>';
            }

            if($archerList){
                $html2 = '';
                foreach($archerList as $k => $v) {
                    switch($k){
                        case 0: $class = 'class="noRank noOne"'; break;
                        case 1: $class = 'class="noRank noTwo"'; break;
                        case 2: $class = 'class="noRank noThree"'; break;
                        case 3: $class = 'class="noRank noFour"'; break;
                        default: $class = '';
                    }

                    if($k == 0) {
                        $bg = 'bgcolor="#fafafa"';
                    }else{
                        $bg = '';
                    }
                    $html2 .= '<tr '.$bg.'>
									<td><strong ' . $class . '>' . $v['rank'] . '</strong></td>';
                    if ($k == 0) {
                        $html2 .= '<td><div class="playerLogo"><img src="' . $v['player_logo'] . '" width="48" height="48"></div><p class="playerName text-hidden">' . $v['player_name'] . '</p></td>
									<td><div class="matchLogo"><img src="' . $v['team_logo'] . '" width="48" height="48"></div><p class="teamName">' . $v['team_name'] . '</p></td>';
                    }
//                    else if (in_array($k, [1, 2])) {
//                        $html2 .= '<td><p class="playerName text-hidden"><img src="' . $v['player_logo'] . '" width="24" height="24" class="img-circle" alt="' . $v['player_name'] . '">' . $v['player_name'] . '</p></td>
//									<td><p class="teamName text-hidden">' . $v['team_name'] . '</p></td>';
//                    }
                    else {
                        $html2 .= '<td><p class="playerName text-hidden" title="'.$v['player_name'].'">' . $v['player_name'] . '</p></td>
									<td><p class="teamName text-hidden" title="'.$v['team_name'].'">' . $v['team_name'] . '</p></td>';
                    }
                    $html2 .= '<td><strong> ' . $v['val'] . '</strong></td>
								</tr>';
                }
            }else{
                $html2 = '<tr><td colspan="4">暂时没有数据哦！</td></tr>';
            }

            $data['list1'] = $html1;
            $data['list2'] = $html2;
            return $data;
        }else{
            return '';
        }
    }

    /**
     * 获取手写位，并组装url链接
     * @param $shouxie_sign
     * @return array
     */
    public function getShouXie($shouxie_sign,$class_id,$num,$type){
        $shouxie_config = getWebConfig($shouxie_sign);
        foreach ($shouxie_config as $k => $v) {
            $newsIdArr[] = $v['id'];
        }

        $news = M('publishList')->field('id,add_time,class_id,img,content')->where(['id'=>['in',$newsIdArr]])->select();
        $classArr = getPublishClass(0); //资讯分类数组
        if(!empty($news)){
            foreach ($shouxie_config as $k => $v) {
                foreach ($news as $kk => $vv) {
                    if($v['id'] == $vv['id']){
                        //组装url链接
                        $shouxie_config[$k]['href']     = newsUrl($v['id'],$vv['add_time'],$vv['class_id'],$classArr);
                        $shouxie_config[$k]['img']      = newsImgReplace($vv);
                        $shouxie_config[$k]['add_time'] = $vv['add_time'];
                    }
                }
            }
        }

        if(!$num && !$type) return $shouxie_config;
        //是否有子分类
        $classIdArr = [$class_id];
        if($classArr[$class_id]['pid'] == 0){
            $pid = $classArr[$class_id]['id'];
            foreach ($classArr as $k => $v) {
                if($v['pid'] == $pid){
                    $classIdArr[] = intval($v['id']);
                }
            }
        }
        $newList = M('PublishList')->field('id,add_time,class_id,title')->where(['class_id'=>['in',$classIdArr],'status'=>1])->order('top_recommend desc,add_time desc')->limit($num)->select();
        foreach ($newList as $k => $v) {
            $newList[$k]['href'] = newsUrl($v['id'],$v['add_time'],$v['class_id'],$classArr);
        }
        //根据后台手写位配置划分
        $shouxie_config = array_chunk($shouxie_config, 1);
        $newList = array_chunk($newList, $type);
        foreach ($shouxie_config as $k => $v) {
            $shouxie_config[$k]['newList'] = $newList[$k];
        }
        return $shouxie_config;
    }

    /**
     * 获取欧冠和亚冠的分组积分信息
     * @param $unionId
     * @param $groupId
     * @return array
     */
    public function getGroupData($unionId, $groupId){
        if(empty($unionId) || empty($groupId)) return '';

        $WebfbService = new WebfbService();
        $pointRank = $WebfbService->getFbUnionRank($unionId, 4, $groupId);

        if($pointRank){
            $html = '';
            foreach($pointRank as $k => &$v) {
                switch($k){
                    case 0: $class = 'class="noRank ogredRank"'; break;
                    case 1: $class = 'class="noRank ogblueRank"'; break;
                    case 2: $class = 'class="noRank grayRank"'; break;
                    case 3: $class = 'class="noRank grayRank"'; break;
                    default: $class = '';
                }

                if($k == 0) {
                    $bg = 'bgcolor="#fafafa"';
                }else{
                    $bg = '';
                }
                $html .= '<tr '.$bg.'> <td><span '.$class.'>'.$v['rank'].'</span></td><td>';
                if($k == 0) {
                    $html .= '<div class="matchLogo"><img src="'.$v['team_logo'].'" width="48" height="48"></div>';
                }
                $html .= '<p class="playerName text-hidden"><a target="_blank" title="'.$v['team_name'].'" href="'.U('/team/' . $v['team_id'] . '@data', '', 'html').'">'.$v['team_name'].'</a></p></td>
                                <td>'.$v['win'].'/'.$v['draw'].'/'.$v['lose'].'</td>
                                <td class="strong">'.$v['int'].'</td>
                            </tr>';
            }

            return $html;
        }else{
            return '<tr><td colspan="4">暂时没有数据哦！</td></tr>';;
        }
    }

}