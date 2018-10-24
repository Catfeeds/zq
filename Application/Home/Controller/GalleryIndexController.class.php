<?php
/**
 * 图库页面控制器
 *
 * @author Allen Wong <496631832@qq.com>
 *
 * @since  2018-1-21
 */
use Think\Tool\Tool;

class GalleryIndexController extends CommonController
{
    private $path = null;

    //图库主页
    public function index()
    {
        if(strpos($_SERVER['PATH_INFO'],'/')) parent::_empty();
        //手机站链接适配
        $mobileUrl = U('/photo@m');
        $this->assign('mobileAgent', $mobileUrl);
        //手机端访问跳转
        if(isMobile()){
            redirect($mobileUrl);
        }
        $page = I('p') ?: 1;
        $limit = 40;
        $where['G.status'] = 1;
        $class_id = I('class_id');
        $pid = I('pid');

        //获取所有分类
        $all_class = M('galleryClass')->where(['status' => 1])->order("sort asc")->select();
        $top_class = $child_class = [];

        foreach ($all_class as $key1 => $val1) {
            $all_class[$key1]['url'] = $val1['path'] ? '/' . $val1['path'] . '.html' : '/';

            if ($val1['pid'] == 0) {
                $top_class[] = $all_class[$key1];//父级分类
            }else{
                $child_class[$val1['id']] = $all_class[$key1];//二级分类
            }

            //根据url的二级目录 设置查询的父类id或者二级分类id
            if($this->path == $val1['path']){
                if( $val1['pid'] == 0){
                    $pid = $val1['id'];
                }else{
                    $class_id = $val1['id'];
                    $pid = $val1['pid'];
                }
                $seo_title = $val1['seo_title'];
                $seo_keys  = $val1['seo_keys'];
                $seo_desc  = $val1['seo_desc'];
            }
        }
        //seo
        $this->setSeo([
            'seo_title' => $seo_title ?: '图片专区|英超|西甲|德甲|美女足球宝贝|NBA-全球体育高清图集频道_全球体育网', 
            'seo_keys'  => $seo_keys ?: '英超,西甲,德甲,意甲,欧冠杯,亚冠,世界杯,清纯美女,清纯女神,美女,女神,足球宝贝,篮球宝贝,美女主播,NBA,CBA,中超,中超联赛,中超图片,性感女神,性感美女,写真', 
            'seo_desc'  => $seo_desc ?: '全球体育高清图集频道为您带来五大足球联赛、世界杯、足球宝贝、美女宝贝、中超、NBA、性感美女、模特写真等高清美图，欢迎关注！'
        ]);

        //生成查询条件
        if($pid){
            foreach ($child_class as $key2 => $val2) {
                if ($val2['pid'] == $pid) {
                    $child_class_ids[] = $val2['id'];
                }else{
                    unset($child_class[$key2]);
                }
            }

            $where['G.class_id'] = ['IN', $child_class_ids];
            foreach ($top_class as $key3 => $val3) {
                if ($val3['id'] == $pid) {
                    $top_path = $val3['path'] ? '/' . $val3['path'] . '.html' : '/';
                }
            }

        }

        if($class_id){
            $where['G.class_id'] = $class_id;
        }

        //图集tab不显示二级分类
        if(!$class_id && !$pid){
            $child_class = [];
        }

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
            $gallery[$kk]['cover_img'] = setImgThumb(json_decode($vv['img_array'], true)[1],'240');
            $gallery[$kk]['date_format'] = date('m', $vv['add_time']) . '月' . date('d', $vv['add_time']) . '日';
            $gallery[$kk]['like_num'] = intval($vv['like_num']);
            $gallery[$kk]['info_url'] = U('/' . $vv['path'] . '/' . date('Ymd', $vv['add_time']) . '/' . $vv['id'] . '@photo', '', 'html');
            unset($gallery[$kk]['img_array']);
        }

        //分页
        $totalCount = M('Gallery')->alias('G')->where($where)->count();
        $page = $this->getPage($totalCount, $page, $limit);

        if (IS_POST) {
            $this->ajaxReturn(['list' => $gallery ?: [], 'page' => $page]);
        }

        $this->assign('top_class', $top_class);
        $this->assign('top_path', $top_path);
        $this->assign('page', $page);
        $this->assign('pid', $pid);
        $this->assign('class_id', $class_id);
        $this->assign('child_class', $child_class);
        $this->assign('gallery', $gallery);
        $this->display('index');
    }

    //分页
    public function getPage($totalCount, $curPage, $limit,$_end = 10)
    {
        $pageStr    = '';
        $initStr    = <<<EOF
            <li><li %s><a href="javascript:void(0)" aria-lable="Next" class="next"> <span aria-hidden="true"> < </span></a></li></li> %s
            <li %s><a class="next" href="javascript:void(0)" aria-lable="Next"> <span aria-hidden="true"> > </span></a></li>
            <li><a herf="javascript:void(0)" style="border: none; margin: 0px; background-color: #fff; margin: 0px -5px;">跳到</a></li>
            <li><a style="border: none; margin: 0px -5px;"><input class="isTxtBig" type="text" value="" name="p"></a> </li>
            <li><a herf="#" style="border: none; margin: 0px; background-color: #fff; margin: 0px -5px;">页</a></li>
            <li id="GO" page="%s"><a href="javascript:void(0)">GO</a> </li>
EOF;
        $tempLi     = '<li %s page="%s"><a  href="javascript:void(0)">%s</a> </li>';

        $ceil       = ceil($totalCount / $limit);//总页码
        $curPage    = $curPage < 1 ? 1 : ($curPage > $ceil ? $ceil : $curPage);//当前页码
        $loopLi     = $leftNext = $rightNext = '';

        $start      = 1;//显示开始页码
        $end        = $_end;//显示结束页码

        if ($ceil <= 1)
            return $pageStr;

        if($ceil < $_end){
            $end    = $ceil;
        }else{
            //以当前页码为基点，左右补足10页页码
            if ($curPage > ($_end/2)) {
                if ($curPage + ($_end/2-1) < $ceil) {
                    $start  = $curPage - ($_end/2-1);
                    $end    = $curPage + ($_end/2-1);
                } else {
                    $end    = $ceil;
                    $start  = $ceil - ($_end-1);
                }
            }
        }

        //拼接页码
        for ($i = $start; $i <= $end; $i++) {
            $active = $i == $curPage ? 'class = "active"' : '';
            $loopLi .= sprintf($tempLi, $active, $i, $i);
        }

        //显示上一页/下一页
        $leftNext   = $curPage <= 1 ? "page='1' style='display:none'" : "page='" . ($curPage - 1) . "'";
        $rightNext  = $curPage >= $ceil ? "page='" . $curPage . "' style='display:none'" : "page='" . ($curPage + 1) . "'";

        return sprintf($initStr, $leftNext, $loopLi, $rightNext, $curPage + 1);
    }

    //获取更多评论
    public function sendPhotos()
    {
        $p = isset($_POST['k']) ? intval(trim($_POST['k'])) : 0;
        $where['status'] = 1;
        $class_id = I('class_id');
        if (!empty($class_id)) {
            $where['class_id'] = $class_id;
        }
        $total = M('Gallery')->where($where)->count();//数据记录总数

        $num = 40;//每页记录数
        $totalpage = ceil($total / $num);//总计页数
        $limitpage = ($p - 1) * $num;//每次查询取记录

        if ($p > $totalpage) {
            //超过最大页数，退出
            $this->error("没有了");
        }
        $gallery = M('gallery')
            ->where($where)
            ->field('id,short_title,img_array')
            ->order('add_time desc')
            ->limit($limitpage, $num)
            ->select();

        foreach ($gallery as $k => $v) {
            $img_array = json_decode($v['img_array'], true);
            $gallery[$k]['images'] = setImgThumb($img_array[1],'240');
            $gallery[$k]['imagesCount'] = count($img_array);
            unset($gallery[$k]['img_array']);
        }

        if (count($gallery) > 0) {
            $this->success($gallery);
        } else {
            $this->error("没有了");
        }
    }

    //图库展示
    public function picture_list($info_p = '')
    {
        if(checkUrlExt()){
            parent::_empty();
        }
        $id = I('get.id', $info_p);
        //手机站链接适配
        $mobileUrl = U('/photo/'.$id.'@m');
        $this->assign('mobileAgent', $mobileUrl);
        //手机端访问跳转
        if(isMobile()){
            redirect($mobileUrl);
        }
        //获取内容
        $gallery = M("Gallery g")
            ->join("LEFT JOIN qc_gallery_class c on g.class_id = c.id")
            ->where(array('g.id' => $id, 'g.status' => 1))
            ->field("g.id,g.class_id,g.title,g.remark,g.add_time,g.click_number,g.img_array,g.describe,g.seo_keys,g.seo_desc,g.seo_title,c.path,c.name as className,c.seo_keys as seokeys,c.seo_desc as seodesc")
            ->find();

        $ex_path = explode('/', $_SERVER['PATH_INFO']);
        //二级目录,日期判断
        if (!$gallery || $ex_path[1] != date('Ymd',$gallery['add_time']) || $ex_path[0] != $gallery['path']) {
            parent::_empty();
        }

        M("Gallery")->where(array('id' => $id, 'status' => 1))->setInc('click_number');

        //seo
        $this->setSeo([
            'seo_title' => $gallery['seo_title'] ?: $gallery['title'].'_'.$gallery['className'].'图片专区频道'.'_全球体育网',
            'seo_keys'  => $gallery['seo_keys']  ?: $gallery['seokeys'],
            'seo_desc'  => $gallery['seo_desc']  ?: $gallery['seodesc'],
        ]);

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

        //上一图库
        $prev_album = M('Gallery')
            ->where(['class_id' => $gallery['class_id'], 'status' => 1, 'id' => ['lt', $gallery['id']]])
            ->order("id desc")
            ->limit(1)
            ->field('id,class_id,title,img_array,add_time')
            ->find();

        if (empty($prev_album)) {
            $prev_album = M('Gallery')
                ->where(['class_id' => $gallery['class_id'], 'status' => 1])
                ->order("id desc")
                ->limit(1)
                ->field('id,class_id,title,img_array,add_time')
                ->find();
        }

        $prev_album_img = setImgThumb(json_decode($prev_album['img_array'], true)[1],'240');

        //下一图库
        $next_album = M('Gallery')
            ->where(['class_id' => $gallery['class_id'], 'status' => 1, 'id' => ['gt', $gallery['id']]])
            ->order("id asc")
            ->limit(1)
            ->field('id,class_id,title,img_array,add_time')
            ->find();

        if (empty($next_album)) {
            $next_album = M('Gallery')
                ->where(['class_id' => $gallery['class_id'], 'status' => 1])
                ->order("id asc")
                ->limit(1)
                ->field('id,class_id,title,img_array,add_time')
                ->find();
        }

        $next_album_img = setImgThumb(json_decode($next_album['img_array'], true)[1],'240');
        $slide_data['slide'] = ['createtime' => date("Y-m-d H:i:s", $gallery['add_time'])];
        $slide_data['images'] = $imgArr;

        $url1 = galleryUrl($prev_album['id'],$gallery['path'],$prev_album['add_time']);
        $url2 = galleryUrl($next_album['id'],$gallery['path'],$next_album['add_time']);

        $slide_data['prev_album'] = ['title' => $prev_album['title'], 'url' => $url1, 'img_url' => $prev_album_img];
        $slide_data['next_album'] = ['title' => $next_album['title'], 'url' => $url2, 'img_url' => $next_album_img];

        //获取同分类相关图库
        $withImg = M("Gallery")
            ->where(array('class_id' => $gallery['class_id'], 'status' => 1, 'id' => array('neq', $id)))
            ->field("id,title,short_title,img_array,add_time")
            ->order("add_time desc")
            ->limit(5)
            ->select();

        foreach ($withImg as $k => $v) {
            $img_array = json_decode($v['img_array'], true);
            $withImg[$k]['img'] = setImgThumb($img_array[1],'240');
            $withImg[$k]['imgCount'] = count($img_array);
            $withImg[$k]['info_url'] = galleryUrl($v['id'],$gallery['path'],$v['add_time']);
        }

        $this->assign('gallery', $gallery);
        $this->assign('slide_data', json_encode($slide_data));
        $this->assign('withImg', $withImg);
        $this->display('picture_list');
    }

    public function _empty($path)
    {
        //图库详情
        $ex_path = explode('/', $_SERVER['PATH_INFO']);
        if ($ex_path[2]) {
            $this->picture_list((int)$ex_path[2]);
        } else {//图库页
            $this->path = $path;
            $this->index();
        }
    }
}