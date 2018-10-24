<?php
/**
 * @author : longs<lons@qc.mail>
 * @Date : 18-3-15
 */

use Think\Tool\Tool;
class IndexController extends CommonController
{

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

    public function index() {
        $this->assign("mobileAdvert", $this->getMobileAdverting());
        $this->assign("live", $this->getLiveGame());
        $seo = [
            'seo_title' => '全球体育手机网 - 深度足球新闻门户|足球预测分析网站',
            'seo_keys'  => '足球比赛,足球比分网,足球即时比分,足球推荐,足球比分直播网',
            'seo_desc'  => '全球体育手机网为您提供专业足球推荐,足球比赛新闻，是广大球迷们获取足球比分，足球即时比分资讯的足球比分直播网。',
        ];
        $this->assign('seo',$seo);
        
        //首页资讯数据
        $newsData = $this->getIndexData();
        $this->assign("newsData", $newsData);

        A('Mobile/Nav')->navHead('index');
        $this->display();
    }

    //首页资讯数据整理
    public function getIndexData(){
        $newsData  = $this->getNewsData();
        $indexNews = $newsData['indexNews'];
        shuffle($indexNews);
        $indexNews = array_slice($indexNews, 0,4);
        $AllData["indexNews"]   = $indexNews;

        $pictureData = $newsData['pictureData'];
        shuffle($pictureData);
        $pictureData = array_slice($pictureData, 0,2);
        $AllData["pictureData"] = $pictureData;

        $topicsData  = $newsData['topicsData'];
        foreach ($topicsData as $k => $v) {
            $Data = $v['Data'];
            shuffle($Data);
            $topicsData[$k]['Data'] = array_slice($Data, 0,4);
            $Video = $v['Video'];
            shuffle($Video);
            $topicsData[$k]['Video'] = array_slice($Video, 0,2);
        }
        $AllData["topicsData"]  = $topicsData;
        return $AllData;
    }

    //ajax更换资讯
    public function getData() {
        $AllData = $this->getNewsData();
        $this->ajaxReturn($AllData);
    }

    //获取资讯
    public function getNewsData(){
        if (!$AllData = S('m_index_getAllData')) {
            $AllData = [];
            $AllData["indexNews"]   = $this->getSiteNews();
            $AllData["pictureData"] = $this->getPictureData();
            $AllData["topicsData"]  = $this->getTopicsData();
            S('m_index_getAllData', $AllData, 60);
        }
        return $AllData;
    }

    /**
     * 获取首页广告信息
     * @return mixed
     */
    public function getMobileAdverting() {
        // 获取轮播图和平台广告
        $mobileAdvert = D("NewBase")->getMobileAdverting();
        return $mobileAdvert;
    }


    /**
     * 获取首页直播信息
     * @return mixed
     */
    public function getLiveGame() {
        // 获取直播信息
        $live = D("NewBase")->getLiveGame();
        return $live;
    }


    /**
     * 获取首页手写位数据
     * @return array
     */
    public function getSiteNews() {
        // 获取不同位置手写位的数据
        $indexNews = D("NewBase")->getSiteNews("news_shouye");
        $indexNews = $this->getClassId($indexNews);
        $classArr = getPublishClass(0);
        foreach ($indexNews as $key  => $val) {
            $indexNews[$key]['news_url'] = mNewsUrl($indexNews[$key]['id'], $indexNews[$key]['class_id'], $classArr);
            $indexNews[$key]['click_number'] = addClickConfig(1, $val['class_id'],$val['click_number'], $val['id']);
        }
        return $indexNews;
    }


    /**
     * 获取导航所在位置数据
     * @param $site 导航位置
     * @param string $field 字段
     * @return mixed 导航数据
     */
    public function getNavList($site, $field="name, ui_type_value as url, icon, sort") {
        // 获取导航条名称
        $navData = D("NewBase")->getNavList($site, $field);
        return $navData;
    }


    /**
     * 获取主页图片集合数据
     */
    public function getPictureData() {
        // 41 43 为 足球篮球分类
        $pictureData = array_merge(D("NewBase")->getData(41,"Gallery_class", "gallery","id, class_id, title, short_title, add_time, img_array, describe, remark" ,["status" => 1], 10, "add_time desc"),
            D("NewBase")->getData(43,"Gallery_class", "gallery","id, class_id, title, short_title, add_time, img_array, describe, remark", ["status" => 1], 10, "add_time desc"));

        // 获取图片地址
        foreach ($pictureData as &$value) {
            $value['cover'] = setImgThumb(json_decode($value['img_array'], true)[1],'240');
            //Url 未设置
            $value['href'] = galleryUrl($value['id'], $value['path'],$value['add_time']);
            $value["img_count"] =count(json_decode($value['img_array'], true));
        }
        // 时间排序
        $addTimeSort = [];
        foreach ($pictureData as $key => $val) {
            $addTimeSort[] = $val['add_time'];
        }
        // 获取排序后的足球和篮球图集数据
        array_multisort($addTimeSort, SORT_DESC, SORT_NUMERIC, $pictureData);
        $sliceData = array_slice($pictureData,0, 10);

        return $sliceData;
    }


    /**
     * @param $publishIds 指定ids
     * @return mixed
     */
    public function getPublishData($publishIds, $map = ["status" => 1],$order = true, $limit ="0,20") {
        if($order) $orderSql='web_recommend desc, ';
        $data = D("NewBase")->getInformation($publishIds, "PublishList","id, user_id, title, short_title, top_recommend, web_recommend, class_id, add_time, img,click_number", $map, $limit, $orderSql."add_time desc");
        $classIds = $this->getPublishClassArray();
        $classArr = getPublishClass(0);
        foreach ($data as $key => $val) {
            $data[$key]['sign'] = $classIds[$val['class_id']];
            $data[$key]['time'] = date("Y-m-d", $val['add_time']);
            $data[$key]['hot'] = ($val['web_recommend'] == 1 && $val['top_recommend'] == 1) ? 1 : 0;
            $data[$key]['img'] = newsImgReplace($val);
            $data[$key]['type'] = $this->getDomain($data[$key]['class_id']);
            $data[$key]['news_url'] = mNewsUrl($data[$key]['id'], $data[$key]['class_id'], $classArr);
            $data[$key]['click_number'] = addClickConfig(1, $val['class_id'],$val['click_number'], $val['id']);
        }
        return $data;
    }

    /**
     * @param $videoClass 获取执行id
     * @return mixed
     */
    public function getVideo($videoClass) {
        $where['m_url'] = ['NEQ',''];
        $where['status'] = 1;
        $fbVideoData = D("NewBase")
            ->getData($videoClass, "HighlightsClass", "Highlights", "id, class_id, title, remark, add_time, top_recommend, img, m_url, m_ischain, web_url, web_ischain, user_id, m_ischain",$where, 10, "top_recommend desc, add_time desc");
        foreach ($fbVideoData as &$val) {
            $val['img_url'] =  Tool::imagesReplace($val['img']);
            $val['m_url'] = $val['m_ischain'] == 1 ? $val['m_url'] : U('/video/'.$val['id'].'@m');
        }
        return $fbVideoData;
    }


    /**
     * 获取首页专栏所有数据
     * @return mixed
     */
    public function getTopicsData() {
        // 足球文章 class 分类
        $FbClassArray = [13, 14, 15, 16, 17, 18, 27, 28, 96];
        $AllData[0]['Data'] = $this->getPublishData($FbClassArray);;
        $AllData[0]['Video'] =$this->getVideo(52) ;
        $AllData[0]['Nav'] = $this->replaceIconUrl($this->getNavList("Mfb", 'name, ui_type_value as url, icon, sort'));

        // CBA 和 NBA分类
        $BkClassArray = [3, 4];
        $AllData[1]['Data'] = $this->getPublishData($BkClassArray);
        $AllData[1]['Video'] = $this->getVideo(53);
        $AllData[1]['Nav'] = $this->replaceIconUrl($this->getNavList("Mbk", 'name, ui_type_value as url, icon, sort'));

        // 综合体育分类
        $ZhClassArray = [64, 65, 66, 93, 95];
        $AllData[2]['Data'] = $this->getPublishData($ZhClassArray);
        $AllData[2]['Video'] = $this->getVideo(54);
        $AllData[2]['Nav'] = $this->replaceIconUrl($this->getNavList("Mzh", 'name, ui_type_value as url, icon, sort'));

        // 电竞分类
        $DjClassArray = [69, 70, 71, 72];
        $AllData[3]['Data'] = $this->getPublishData($DjClassArray);
        $AllData[3]['Video'] = $this->getVideo(55);
        $AllData[3]['Nav'] = $this->replaceIconUrl($this->getNavList("Mdj", 'name, ui_type_value as url, icon, sort'));

        return $AllData;
    }


    /**
     * 传递包含icon的数组
     * @param $array 携带参数的数组
     * @return mixed icon拼接的url
     */
    public function replaceIconUrl($array) {
        foreach ($array as $k => $v) {
            $array[$k]['iconUrl'] = Tool::imagesReplace($v['icon']);
        }
        return $array;
    }


    /**
     * 从数组中获取随机数量的数据
     * @param $array 数组
     * @param $count 随机数量
     * @return array
     */
    public function getRandomData($array, $count) {
        $indexArr =  array_rand($array, $count);
        $Arr = [];
        foreach ($indexArr as $v) {
            $Arr[] = $array[$v];
        }
        return $Arr;
    }


    /**
     * 获取文章分类数据
     * @return array
     */
    public function getPublishClassArray() {
        $data = M("PublishClass")->field("id, name")->select();
        $data =  array_column($data, "name", "id");
        return $data;
    }

    /**
     * 获取域名
     * @param $id
     * @return mixed
     */
    public function getDomain($id) {
        $special = A("Special")->special;
        foreach ($special as $key => $value) {
            if ($value['publishId'] == $id) {
                return $value['domain'];
            }
        }
    }

    /**
     * 获取class_id
     * @param $arr
     * @return mixed
     */
    public function getClassId($arr) {
        foreach ($arr as $key => $val) {
            $data = M("PublishList")->field('id, class_id')->where(['id' => $val['id']])->select();
            $arr[$key]['class_id'] = $data[0]['class_id'];
        }
        return $arr;
    }


    //删除所有缓存
    public function delCache(){
        S('m_index_getAllData',null);

        S('M_premierleague_live',null);
        S('M_seriea_live',null);
        S('M_bundesliga_live',null);
        S('M_laliga_live',null);
        S('M_csl_live',null);

        S('M_worldCup_live',null);

        S('M_championsleague_live2',null);
        S('M_afccl_live2',null);

        S('M_nba_bkSchedule',null);
        S('M_cba_bkSchedule',null);

        S('M_premierleague_fbUnionRank',null);
        S('M_seriea_fbUnionRank',null);
        S('M_bundesliga_fbUnionRank',null);
        S('M_laliga_fbUnionRank',null);
        S('M_csl_fbUnionRank',null);
        S('M_championsleague_fbUnionRank',null);
        S('M_afccl_fbUnionRank',null);
        S('M_bk_fbUnionRank',null);

        S('M_36_fbUnionArcher',null);
        S('M_34_fbUnionArcher',null);
        S('M_8_fbUnionArcher',null);
        S('M_31_fbUnionArcher',null);
        S('M_60_fbUnionArcher',null);
        S('M_2_fbUnionArcher',null);
        S('M_192_fbUnionArcher',null);
    }
}