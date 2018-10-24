<?php

/**
 * 新闻
 * @author chenzj <443629770@qq.com>
 * @since  2016-04-19
 */
use Think\Tool\Tool;
class NewsController extends CommonController {

    protected function _initialize() {
        if (!cookie('redirectUrl')) {
            cookie('redirectUrl', U('Index/index'));
        }
    }

    private $_order = 'pl.add_time desc,pl.update_time desc,pl.is_recommend desc';

    public function index() {
        $this->assign('user_auth',session('user_auth'));
        cookie('userUrl',__SELF__);//用于点击头像时作为返回本页面的链接
        $class_id = I('get.class_id')?:10;
        $_M = M('PublishList');
        $limit = 20;
        $this->assign('title', '新闻');
        $where['pl.status']   = 1;
        $where['pl.class_id'] = $class_id;
        if ($class_id == 10) {
            //热盘
            $list = $_M->alias('pl')->field('pl.id,fu.head,user_id,nick_name,title,is_original,source,remark')->join('left join qc_front_user fu on pl.user_id=fu.id')
                            ->where($where)
                            ->order($this->_order)
                            ->limit($limit)->select();
            $this->assign('seo_title', '独家解盘');
            $this->assign('class_id', $class_id);
            $this->assign('list', $list);
            $this->display('hot');
        }else if($class_id == 'girl') {
            $galleryClass = M('GalleryClass')->where(['status' => 1, 'id' => ['IN', [19, 17, 15, 11,7]]])->getField('id', true);
            $galleryList = M('Gallery')->field(['id', 'title', 'click_number', 'like_num', 'like_user', 'img_array'])
                    ->where(['status' => 1, 'class_id' => ['IN', $galleryClass]])
                    ->order("add_time desc")
                    ->limit($limit)
                    ->select();

            foreach ($galleryList as $k => $v) {
                $img_array = json_decode($v['img_array'], true);
                $imgages = Tool::imagesReplace($img_array[1]);
                unset($galleryList[$k]['img_array']);
                if ($imgages) {
                    $galleryList[$k]['images'] = $imgages;
                }
                //增加资讯点击量的默认值
                $galleryList[$k]['click_number'] = addClickConfig(2, $v['class_id'], $v['click_number'], $v['id'], 77);
            }
            $this->assign('seo_title', '美女');
            $this->assign('class_id', $class_id);
            $this->assign('list', $galleryList);
            $this->display('girl');
        }else {
            //分类
            $list = $_M->alias('pl')->field('pl.id,img,title,is_original,label,source,remark,content')
                            ->where($where)
                            ->order($this->_order)
                            ->limit($limit)->select();
            foreach ($list as &$v) {
                if(!empty($v['img'])){
                    $v['img'] = Tool::imagesReplace($v['img']);
                }else{
                    //获取第一张图片
                    $v['img']  = Tool::getTextImgUrl(htmlspecialchars_decode($v['content']),false)[0]?:SITE_URL.'www.'.DOMAIN.'/Public/Home/images/common/loading.png';;
                }
                if(empty($v['remark'])){
                    $v['remark'] = str_replace(',', ' ', $v['label']);
                }
            }
            switch ($class_id) {
                case 54 :
                    $seo_title = "竞彩前瞻";
                    break;
                case 55 :
                    $seo_title = "北单推荐";
                    break;
                case 62 :
                    $seo_title = "独家秘笈";
                    break;
                default :
                    $seo_title = '手机全球体育网';
                    break;
            }
            $this->assign('seo_title', $seo_title);
            $this->assign('class_id', $class_id);
            $this->assign('list', $list);
            $this->display('news');
            
        }
    }

    public function loadMore() {
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
            $list = $_M->alias('pl')->field('pl.id,fu.head img,user_id,nick_name,source,title,is_original,remark')->join('left join qc_front_user fu on pl.user_id=fu.id')
                            ->where($where)
                            ->order($this->_order)
                            ->limit($limitpage, $num)->select();
            foreach ($list as  &$value) {
                $value['img']=frontUserFace($value['img']);
            }
            $this->success($list);
        }else{
            $list = $_M->alias('pl')->field('pl.id,img,title,is_original,source,label,remark,content')
                            ->where($where)
                            ->order($this->_order)
                            ->limit($limitpage, $num)->select();
        }
        if ($list) {
            foreach ($list as &$v) {
                if(!empty($v['img'])){
                    $v['img'] = Tool::imagesReplace($v['img']);
                }else{
                    //获取第一张图片
                    $v['img']  = Tool::getTextImgUrl(htmlspecialchars_decode($v['content']),false)[0]?:SITE_URL.'www.'.DOMAIN.'/Public/Home/images/common/loading.png';;
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
    public function loadMore_g() {
        $p = isset($_POST['k']) ? intval(trim($_POST['k'])) : 0;
        $galleryClass = M('GalleryClass')->where(['status' => 1, 'id' => ['IN', [19, 17, 15, 11,7]]])->getField('id', true);
        $where=array('status'=>1,'class_id'=>['IN', $galleryClass]);
        $total = M('Gallery')->where($where)->count();//数据记录总数
        $num = 20; //每页记录数
        $totalpage = ceil($total / $num); //总计页数
        $limitpage = ($p - 1) * $num; //每次查询取记录

        if ($p > $totalpage) {
            //超过最大页数，退出
            $this->error("没有更多了");
        }
        $galleryList = M('Gallery')->field(['id', 'title', 'click_number', 'like_num', 'like_user', 'img_array'])
                    ->where($where)
                    ->order("add_time desc")
                    ->limit($limitpage, $num)
                    ->select();
        foreach ($galleryList as $k => $v) {
            $img_array = json_decode($v['img_array'], true);
            $imgages = Tool::imagesReplace($img_array[1]);
            unset($galleryList[$k]['img_array']);
            if ($imgages) {
                $galleryList[$k]['images'] = $imgages;
            }
            //增加资讯点击量的默认值
            $galleryList[$k]['click_number'] = addClickConfig(2, $v['class_id'], $v['click_number'], $v['id'], 77);
        }
        if($galleryList){
            $this->success($galleryList);
        }else{
            $this->error('没有更多了');
        }
    }

    public function detail() {
        $id = I('get.id', 0, 'intval');
        if ($id < 1) {
            $this->error("找不到相关页面！");
        }
        $_M = M('PublishList');
        $data = $_M->field('title,class_id,label,add_time,source,content')->where(array('id' => $id, 'status' => 1))->find();
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
        $this->assign('data', $data);
        $this->display();
    }
    public function detail_g() {
        $id = I('get.id', 0, 'intval');
        if ($id < 1) {
            $this->error("找不到相关页面！");
        }
        $where = ['id'=>$id,'status'=>1];
        $detail = M('Gallery')->field(['id','title','like_num','like_user','img_array','add_time'])->where($where)->find();
        $img_array = json_decode($detail['img_array'],true);
        foreach ($img_array as $k => $v) {
            $imgArr[] = Tool::imagesReplace($v);
        }
        $detail['imgages'] = $imgArr;
        unset($detail['img_array']);
        M("Gallery")->where($where)->setInc('click_number');
        $this->assign('data', $detail);
        $this->display();
    }

}
