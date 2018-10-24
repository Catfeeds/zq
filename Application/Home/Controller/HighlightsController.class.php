<?php
/**
 * 集锦控制器
 *
 * @author dengweijun <406516482@qq.com>
 *
 * @since  2016-3-30
 */
class HighlightsController extends CommonController {
    protected function _initialize(){
        parent::_initialize();
    }

    //获取集锦分类id数组
    public function getClassId($path,$classArr){
        $newArr = [];
        foreach ($classArr as $k => $v) {
            $newArr[$v['path']] = $v;
        }
        $jijing = $newArr[$path];
        $jijing_id[] = $jijing['id'];
        if($jijing['pid'] == 0){
            //找出子分类
            foreach ($classArr as $k => $v) {
                if($jijing['id'] == $v['pid']){
                    $jijing_id[] = $v['id'];
                }
            }
        }
        return $jijing_id;
    }

    //集锦主页
    public function index($path)
    {
        if(strpos($_SERVER['PATH_INFO'],'/')) parent::_empty();
        //手机站链接适配
        $mobileUrl = U('/video@m');
        $this->assign('mobileAgent', $mobileUrl);
        //手机端访问跳转
        if(isMobile()){
            redirect($mobileUrl);
        }
        $this->assign('path',$path);
        //获取视频分类配置
        $classArr = getVideoClass(0);
        foreach ($classArr as $k => $v) {
            if($path == $v['path']){
                //设置seo
                $this->setSeo($v);
                $navClass = $v;
            }
        }
        $parent_id = $navClass['id'];
        if($navClass['pid'] != 0){
            $parent_id = $navClass['pid'];
        }
        $this->assign('parent_id',$parent_id);//一级栏目id
        $this->assign('class_id',$navClass['id']);//二级栏目id
        $this->assign('className',$navClass['name']);
        //一级导航
        $treeArr  = array_to_tree($classArr);
        foreach ($treeArr as $k => $v) {
            $treeArr[$k]['href'] = U('/'.$v['path'].'@video');
        }
        //二级导航
        $childArr = $treeArr[$parent_id]['_child'];
        foreach ($childArr as $k => $v) {
            $childArr[$k]['href'] = U('/'.$v['path'].'@video');
        }
        $this->assign('treeArr',$treeArr);
        $this->assign('childArr',$childArr);
        
        $where["status"] = 1;
        //获取分类id包括子分类id
        $jijing_id = $this->getClassId($path,$classArr);
        $where['class_id'] = ['in',$jijing_id];

        $high = M('Highlights');

        //获取轮播推荐集锦
        if(!$carousel = S('web_highlights_carousel'.$path)){
            $carousel = $high->where($where)->where(['is_recommend'=>2])->order("add_time desc")->field("id,class_id,add_time,web_url,web_ischain,title,img")->limit(5)->select();
            foreach ($carousel as $k => $v) {
                $carousel[$k]['img']  = Think\Tool\Tool::imagesReplace($v['img']);
                $carousel[$k]['href'] = videoUrl($v,$classArr);
            }
            S('web_highlights_carousel'.$path,json_encode($carousel),300);
        }
        $this->assign('carousel',$carousel);

        //获取推荐集锦
        if(!$recommend = S('web_highlights_recommend'.$path)){
            $recommend = $high->where($where)->where(['is_recommend'=>1])->order("add_time desc")->field("id,class_id,add_time,web_url,web_ischain,title,img")->limit(6)->select();
            foreach ($recommend as $k => $v) {
                $recommend[$k]['img'] = setImgThumb($v['img'],'200');
                $recommend[$k]['href'] = videoUrl($v,$classArr);
            }
            S('web_highlights_recommend'.$path,json_encode($recommend),300);
        }
        $this->assign('recommend',$recommend);

        //获取最新视频
        if(!$new = S('web_highlights_new'.$path)){
            $new = $high->where($where)->order("add_time desc")->field("id,class_id,add_time,web_url,web_ischain,title,img,click_num")->limit(15)->select();
            foreach ($new as $k => $v) {
                $new[$k]['img']  = setImgThumb($v['img'],'200');
                $new[$k]['href'] = videoUrl($v,$classArr);
                $new[$k]['click_num'] = addClickConfig(1, $v['class_id'],$v['click_num'], $v['id']);
            }
            S('web_highlights_new'.$path,json_encode($new),300);
        }

        $this->assign('new',$new);

        $this->display('Highlights/index');
    }

    //ajax获取更多集锦
    public function sendMore(){
        $p = isset($_POST['k'])?intval(trim($_POST['k'])):0;
        $path = I('path');
        $classArr = getVideoClass(0);
        $jijing_id = $this->getClassId($path,$classArr);
        $where['status']     = 1;
        $where['class_id'] = ['in',$jijing_id];

        $total = M('Highlights')->where($where)->count();//数据记录总数
        $num = 15;//每页记录数
        $totalpage = ceil($total/$num);//总计页数
        $limitpage = ($p-1)*$num;//每次查询取记录

        if($p>$totalpage){
            //超过最大页数，退出
            $this->error("没有更多了");
        }
        $list = M('Highlights')->where($where)->field("id,class_id,add_time,web_url,web_ischain,title,img,click_num")->order('add_time desc')->limit($limitpage,$num)->select();
        foreach ($list as $key => $value) {
            $list[$key]['add_time'] = format_date($value['add_time']);
            $list[$key]['img']  = setImgThumb($value['img'],'200');
            $list[$key]['href'] = videoUrl($value,$classArr);
        }

        if(count($list)>0){
            $this->success($list);
        }else{
            $this->error("没有更多了");
        }
    }
}