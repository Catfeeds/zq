<?php
/**
 * 图库页面控制器
 *
 * @author dengweijun <406516482@qq.com>
 *
 * @since  2015-12-21
 */
use Think\Tool\Tool;
class GalleryIndexController extends CommonController {
    //图库主页
    public function index()
    {
        //获取所有分类
        $allClass = M('galleryClass')->where("status=1")->order("sort asc")->select();
        foreach ($allClass as $key => $value) {
            $allClass_id[] = $value['id'];
        }
        $this->assign('allClass',$allClass);

        //获取所有图库
        $class_id = I('get.class_id', 17,'int');
        $_GET['class_id'] = $class_id;

        $where['class_id'] = $class_id;
        $where['status'] = 1;
        $allGallery = $this->_list(M('Gallery'), $where, '40', 'add_time desc','','',"/list_p/{$class_id}/%5BPAGE%5D.html");

        foreach ($allGallery as $k => $v) {
            $img_array = json_decode($v['img_array'],true);
            $allGallery[$k]['images'] = Tool::imagesReplace($img_array[1]);
            $allGallery[$k]['imagesCount'] = count($img_array);
            unset($allGallery[$k]['img_array']);
        }

        $this->assign('allGallery',$allGallery);
        $this->display();
    }

    //获取更多评论
    public function sendPhotos(){
        $p = isset($_POST['k'])?intval(trim($_POST['k'])):0;
        $where['status'] = 1;
        $class_id = I('class_id');
        if(!empty($class_id)){
            $where['class_id'] = $class_id;
        }
        $total = M('Gallery')->where($where)->count();//数据记录总数

        $num = 40;//每页记录数
        $totalpage = ceil($total/$num);//总计页数
        $limitpage = ($p-1)*$num;//每次查询取记录

        if($p>$totalpage){
            //超过最大页数，退出
            $this->error("没有了");
        }
        $gallery = M('gallery')->where($where)->field('id,short_title,img_array')->order('add_time desc')->limit($limitpage,$num)->select();
        foreach ($gallery as $k => $v) {
            $img_array = json_decode($v['img_array'],true);
            $gallery[$k]['images'] = Tool::imagesReplace($img_array[1]);
            $gallery[$k]['imagesCount'] = count($img_array);
            unset($gallery[$k]['img_array']);
        }
        if(count($gallery)>0){
            // pr($comment);
            // die;
            $this->success($gallery);
        }else{
            $this->error("没有了");
        }

    }
    //图库展示
    public function picture_list()
    {
        $id = I('get.id');
        //获取内容
        $gallery = M("Gallery")->where(array('id'=>$id,'status'=>1))->field("id,class_id,title,remark,add_time,click_number,img_array,describe")->find();
        if($gallery){
            M("Gallery")->where(array('id'=>$id,'status'=>1))->setInc('click_number');
        }else{
            $this->_empty();
        }
        //获取所属分类名
        $gallery['className'] = M('galleryClass')->where(array('id'=>$gallery['class_id']))->getField('name');
        $this->assign('gallery',$gallery);
        //获取图片
        $img_array = json_decode($gallery['img_array'],true);
        foreach ($img_array as $key => $value) {
            $ArrImg[] = Tool::imagesReplace($value); 
        }
        //组装数组
        $describe = json_decode($gallery['describe'],true);
        $slide_data = array();
        foreach ($ArrImg as $k => $v) {
            if(empty($v)){
                continue;
            }
            $imgArr[$k]['intro']       = $gallery['remark'];
            $imgArr[$k]['img_100_100'] = $v;
            $imgArr[$k]['image_url']   = $v;
            if($describe){
                foreach ($describe as $key => $value) {
                    //对应描述
                    if ($k + 1 == $key) {
                        $imgArr[$k]['title'] = $value ?: '';
                    }
                }
            }else{
                $imgArr[$k]['title'] = '';
            }
        }
        sort($imgArr);
        //上一图库
        $prev_album = M('Gallery')->where(['class_id'=>$gallery['class_id'],'status'=>1,'id'=>['lt',$gallery['id']]])->order("id desc")->limit(1)->field('id,class_id,title,img_array')->find();
        if(empty($prev_album)){
            $prev_album = M('Gallery')->where(['class_id'=>$gallery['class_id'],'status'=>1])->order("id desc")->limit(1)->field('id,class_id,title,img_array')->find();
        }
        $prev_album_img = Tool::imagesReplace(json_decode($prev_album['img_array'],true)[1]);

        //下一图库
        $next_album = M('Gallery')->where(['class_id'=>$gallery['class_id'],'status'=>1,'id'=>['gt',$gallery['id']]])->order("id asc")->limit(1)->field('id,class_id,title,img_array')->find();
        if(empty($next_album)){
            $next_album = M('Gallery')->where(['class_id'=>$gallery['class_id'],'status'=>1])->order("id asc")->limit(1)->field('id,class_id,title,img_array')->find();
        }
        $next_album_img = Tool::imagesReplace(json_decode($next_album['img_array'],true)[1]);

        $slide_data['slide']  = ['createtime'=>date("Y-m-d H:i:s",$gallery['add_time'])];
        $slide_data['images'] = $imgArr;
        $slide_data['next_album'] = ['title'=>$next_album['title'],'url'=>"//www.".DOMAIN."/info_p/{$next_album['id']}.html",'img_url'=>$next_album_img];
        $slide_data['prev_album'] = ['title'=>$prev_album['title'],'url'=>"//www.".DOMAIN."/info_p/{$prev_album['id']}.html",'img_url'=>$prev_album_img];

        $this->assign('slide_data',json_encode($slide_data));
        //获取同分类相关图库
        $withImg = M("Gallery")->where(array('class_id'=>$gallery['class_id'],'status'=>1,'id'=>array('neq',$id)))->order("add_time desc")->limit(5)->field("id,short_title,img_array")->select();
        foreach ($withImg as $k => $v) {
            $img_array = json_decode($v['img_array'],true);
            $withImg[$k]['img'] = Tool::imagesReplace($img_array[1]);
            $withImg[$k]['imgCount'] = count($img_array);
        }
        $this->assign('withImg',$withImg);
        //获取广告
        $adImg = Tool::getAdList(6,1);
        $this->assign('adImg',$adImg);
        $this->display();
    }
}