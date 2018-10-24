<?php
/**
 * 图库列表
 */
use Think\Tool\Tool;

class PhotosController extends CommonController
{
    protected function _initialize() {
        parent::_initialize();

        if(substr_count($_SERVER['PATH_INFO'],'/') > 1) $this->error("找不到相关页面！");
    }

    public function index(){
        A('Mobile/Nav')->navHead('photo');
        $this->assign('titleHead','图片专区');
        $seo = [
            'seo_title' => '图片专区|英超|西甲|德甲|美女足球宝贝|NBA-全球体育高清图集频道_全球体育网',
            'seo_keys'  => '英超,西甲,德甲,意甲,欧冠杯,亚冠,世界杯,清纯美女,清纯女神,美女,女神,足球宝贝,篮球宝贝,美女主播,NBA,CBA,中超,中超联赛,中超图片,性感女神,性感美女,写真',
            'seo_desc'  => '全球体育高清图集频道为您带来五大足球联赛、世界杯、足球宝贝、美女宝贝、中超、NBA、性感美女、模特写真等高清美图，欢迎关注！',
        ];
        $this->assign('seo',$seo);
        $this->display();
    }

    //获取图库列表
    public function getPhoto()
    {
        $page = I('p') ?: 1;
        $limit = 12;
        $where['G.status'] = 1;
        $where['G.add_time'] = ['lt',I('time',time(),'int')];
        //获取分类id
        $class_id = M('GalleryClass')->where(['pid'=>['in',['9','41','43']]])->getField('id',true);
        $where['G.class_id'] = ['in',$class_id];
        //获取图库
        $gallery = M('Gallery')
            ->alias('G')
            ->field('G.id,G.class_id,G.title,G.img_array,G.click_number,G.like_num,G.add_time,C.path')
            ->where($where)
            ->join('LEFT JOIN qc_gallery_class C ON  C.id = G.class_id')
            ->order('G.add_time DESC,G.like_num DESC')
            ->page($page . ',' . $limit)
            ->select();

        foreach ($gallery as $kk => $vv) {
            $gallery[$kk]['imgTotal'] = count(json_decode($vv['img_array'],true));
            $img = json_decode($vv['img_array'], true);
            $gallery[$kk]['cover_img1'] = $img[1]?setImgThumb($img[1],'240',1,$vv['id']):'';
            $gallery[$kk]['cover_img2'] = $img[2]?setImgThumb($img[2],'240',2,$vv['id']):'';
            $gallery[$kk]['cover_img3'] = $img[3]?setImgThumb($img[3],'240',3,$vv['id']):'';
            $gallery[$kk]['date_format'] = date('m', $vv['add_time']) . '月' . date('d', $vv['add_time']) . '日';
            $gallery[$kk]['like_num'] = intval($vv['like_num']);
            $gallery[$kk]['info_url'] = U('/' . $vv['path'] . '/' . date('Ymd', $vv['add_time']) . '/' . $vv['id'] . '@photo', '', 'html');
            $gallery[$kk]['img_height'] = getimagesize($gallery[$kk]['cover_img'])[1];
            $gallery[$kk]['click_number'] = addClickConfig(1, $vv['class_id'],$vv['click_number'], $vv['id']);
            //拼接url
            $gallery[$kk]['url'] = U('/photo/'.$vv['id'].'@m');
            unset($gallery[$kk]['img_array']);
        }
        if($gallery)
            $data = ['code'=>200,'data'=>$gallery];
        else
            $data = ['code'=>201,'msg'=>'暂无数据!!'];
        $this->ajaxReturn($data);
    }

    //图库详情页
    public function info()
    {
        $id = I('id');
        //获取内容
        $gallery = M("Gallery")
            ->where(array('id' => $id, 'status' => 1))
            ->field("id,class_id,title,remark,add_time,click_number,img_array,describe,seo_keys,seo_desc,seo_title")
            ->find();
        if ($gallery) {
            M("Gallery")->where(array('id' => $id, 'status' => 1))->setInc('click_number');
        } else {
            $this->error('页面不存在', '/');
        }
        //获取所属分类名
        $pclass = M('galleryClass')->where(array('id' => $gallery['class_id']))->find();
        $gallery['className'] = $pclass['name'];
        $gallery['path'] = $pclass['path'] ? '/' . $pclass['path'] . '.html' : '/';

        //获取图片
        $img_array = json_decode($gallery['img_array'], true);
        foreach ($img_array as $key => $value) {
            $ArrImg[] = Tool::imagesReplace($value);
        }

        //组装数组
        $describe = json_decode($gallery['describe'], true);
        $slide_data = array();

        foreach ($ArrImg as $k => $v) {
            if (empty($v)) {
                continue;
            }
            $imgArr[$k]['intro'] = $gallery['remark'];
            $imgArr[$k]['img_100_100'] = $v;
            $imgArr[$k]['image_url'] = $v;
            if ($describe) {
                foreach ($describe as $key => $value) {
                    //对应描述
                    if ($k + 1 == $key) {
                        $imgArr[$k]['title'] = $value ?: $gallery['title'];
                    }
                }
            } else {
                $imgArr[$k]['title'] = $gallery['title'];
            }
        }

        sort($imgArr);
        $this->assign('data',$imgArr);
        $seo = [
            'seo_title' => $gallery['seo_title'] ?: $gallery['title'].'_'.$gallery['className'].'图片专区频道'.'_全球体育手机网',
            'seo_keys'  => $gallery['seo_keys']  ?: $pclass['seo_keys'],
            'seo_desc'  => $gallery['seo_desc']  ?: $pclass['seo_desc'],
        ];
        $this->assign('seo',$seo);
        $this->display();
    }
}