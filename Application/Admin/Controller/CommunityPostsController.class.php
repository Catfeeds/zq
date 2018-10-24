<?php
/**
 * 社区管理的帖子列表
 * @author liangzk <1343724998@qq.com>
 * @since v2.0 2016-06-29
 */
class CommunityPostsController extends CommonController
{
    /**
     * index
     */
    public function index()
    {
        //过滤
        $map = $this->_search('CommunityPostsView');
        unset($map['base64_content']);
        unset($map['base64_title']);
        $cid = I('cid');
        //获取分类
        $Community = M('Community')->where("status=1")->select();
        if(!empty($cid)){
            //无限级分类中获取一个分类下的所有分类的ID,包括查找的父ID
            $CommunityIds = Think\Tool\Tool::getAllSubCategoriesID( $Community, $cid );
            $map['cid'] = array( 'in', $CommunityIds );
        }
        $base64_title = trim(I('base64_title'));
        if(! empty($base64_title)){
            $map['_string'] = 'from_base64(base64_title) like concat("%","'.$base64_title.'","%")';
        }
        $base64_content = trim(I('base64_content'));
        if(! empty($base64_content))
        {
            $map['_string'] = 'from_base64(base64_content) like concat("%","'.$base64_content.'","%")';
        }
        if(! empty($base64_content) && ! empty($base64_title))
        {
            $map['_string'] = 'from_base64(base64_title) like concat("%","'.$base64_title.'","%") and from_base64(base64_content) like concat("%","'.$base64_content.'","%")';
        }
        //时间查询
        if(! empty($_REQUEST ['startTime']) || ! empty($_REQUEST ['endTime'])){
            if(! empty($_REQUEST ['startTime']) && ! empty($_REQUEST ['endTime'])){
                $startTime = strtotime($_REQUEST ['startTime']);
                $endTime   = strtotime($_REQUEST ['endTime'])+86400;
                $map['create_time'] = array('BETWEEN',array($startTime,$endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']);
                $map['create_time'] = array('EGT',$strtotime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime'])+86400;
                $map['create_time'] = array('ELT',$endTime);
            }
        }
        if($_REQUEST['report_num'] == 1){
            $map['report_num'] = ['gt',0];
        }
        $is_capture = I('is_capture');
        $map['is_capture'] = $is_capture == 1 ? ['gt',0] : 0;

        $list = $this->_list(CM('CommunityPostsView'),$map);
        include('./Public/Plugs/emoji/emoji.php');
        // foreach ($list as $k => $v) //解码
        // {

        // }

        $this->assign('list',$list);
        //引用Tree类
        $CommunityClass = Think\Tool\Tool::getTree($Community, $pid = 0, $col_id = 'id', $col_pid = 'pid', $col_cid = 'level');
        $this->assign('CommunityClass',$CommunityClass);
        //获取编辑
        $editor = M('user')->select();
        $this->assign('editor', $editor);
        //总评论数
        $comment_count = D('CommunityPostsView')->field("sum(comment_num) comment_count")->where($map)->find();
        $this->assign('comment_count', $comment_count);
        $this->display();
    }
    //发布帖子
    public function add()
    {
        //获取分类
        $Community = M('Community')->where("status=1")->select();
        //引用Tree类
        $CommunityClass = Think\Tool\Tool::getTree($Community, $pid = 0, $col_id = 'id', $col_pid = 'pid', $col_cid = 'level');
        $this->assign('CommunityClass',$CommunityClass);
        $this->display();
    }
    //编辑
    public function edit()
    {
        //获取分类
        $Community = M('Community')->where("status=1")->select();
        //引用Tree类
        $CommunityClass = Think\Tool\Tool::getTree($Community, $pid = 0, $col_id = 'id', $col_pid = 'pid', $col_cid = 'level');
        $this->assign('CommunityClass',$CommunityClass);

        $id = I("id");
        $vo = D('CommunityPostsView')->find($id);
        if (!$vo) $this->error('参数错误');
        //图片处理
        $imgArr = json_decode($vo['img'],true);
        unset($vo['img']);
        for($i=1;$i<10;$i++){
            if ($imgArr && !empty($imgArr[$i-1])){
                //存在
                $vo['img'][$i] = Think\Tool\Tool::imagesReplace($imgArr[$i-1]);
            } else {
                $vo['img'][$i] = "";
            }
        }
        $this->assign ('vo', $vo);
        $this->display("add");
    }

    /**
     * 查看操作
     */
    public function check()
    {
        $id = I("id");
        if(IS_POST){
            $user_id = I('user_id','int');
            if (!$id || !$user_id)
            {
                $this->error('参数错误');
            }
            $model = D('CommunityPosts');
            if (false === $model->create()) {
                $this->error($model->getError());
            }
            //屏蔽用户
            $community_status = I('community_status');
            if(!in_array($community_status, [0,2]))
            {
                $community_status = time()+86400;
            }

            $rs  = M('FrontUser')->where(['id'=>$user_id])->save(['community_status'=>$community_status]);
            if (!is_bool($rs))  $rs  = true;

            $rs2 = $model->save();
            if (!is_bool($rs2)) $rs2 = true;

            if($community_status == 2){
                //禁用所有帖子和评论
                M('communityPosts')->where(['user_id'=>$user_id])->save(['status'=>0]);
                M('communityComment')->where(['user_id'=>$user_id])->save(['status'=>0]);
            }

            if ($rs && $rs2) {
                //成功提示
                $this->success('保存成功!',cookie('_currentUrl_'));
            } else {
                //错误提示
                $this->error('保存失败!');
            }
            exit;
        }
        $communityPostsModel = D('CommunityPostsView');

        $vo = $communityPostsModel->find($id);
        if (!$vo) $this->error('参数错误');
        //图片处理
        foreach (json_decode($vo['img']) as $k => $v) {
            $img[] = Think\Tool\Tool::imagesReplace($v);
        }
        $this->assign ('img', $img);
        $this->assign ('vo', $vo);
        $this->display();

    }

    public function forbid(){
        $type = $_REQUEST['type'];
        $id = $_REQUEST['id'];
        $user_id = $_REQUEST['user_id'];
        if($type == 1){
            if($user_id){
                M('FrontUser')->where(['id' => $user_id])->save(['status' => 0]);
            }
        }else{
            $list = M('CommunityPosts')->where(['id' => $id])->find();
            M('CommunityPosts')->where(['user_id' => $list['user_id']])->save(['status' => 0]);
        }
        $this->success('状态禁用成功');
    }

    function resume() {
        $type = $_REQUEST['type'];
        $id = $_REQUEST['id'];
        $user_id = $_REQUEST['user_id'];
        if($type == 1){
            if($user_id){
                M('FrontUser')->where(['id' => $user_id])->save(['status' => 1]);
            }
        }else{
            M('CommunityPosts')->where(['id' => $id])->save(['status' => 1]);
        }

        $this->success('状态恢复成功！');
    }

    /**
     * 添加/修改操作
     */
    public function save()
    {
        $id = I('id');
        //数据数组
        $cid = I('cid');
        $pid = M('Community')->where(['id'=>$cid])->getField('pid');
        if($pid == 0) $this->error("一级分类不能选择");
        $arr['cid']            = $cid;
        $arr['user_id']        = I('user_id');
        $arr['base64_title']   = base64_encode(I('base64_title'));
        $arr['base64_content'] = base64_encode(I('base64_content'));
        $arr['home_recommend'] = I('home_recommend');
        $arr['top_recommend']  = I('top_recommend');
        $arr['status']         = I('status');
        $arr['editor_id']      = $_SESSION['authId'];
        if($id){
            //编辑
            $rs = M('CommunityPosts')->where(['id'=>$id])->save($arr);
            if(!is_bool($rs)) $rs = true;
            if($rs){
                S('api_postDetail'.$id, NULL);
            }
            //是否有上传
            for ($i=1; $i <10 ; $i++) {
                if (!empty($_FILES['fileInput_'.$i]['tmp_name'])) {
                    //先删除原来图片
                    $fileArr = array(
                        "/posts/{$id}/{$i}.jpg",
                        "/posts/{$id}/{$i}.gif",
                        "/posts/{$id}/{$i}.png",
                        "/posts/{$id}/{$i}.swf",
                        "/posts/{$id}/{$i}".C('thumbImgSize').".jpg",
                        "/posts/{$id}/{$i}".C('thumbImgSize').".gif",
                        "/posts/{$id}/{$i}".C('thumbImgSize').".png",
                        "/posts/{$id}/{$i}".C('thumbImgSize').".swf",
                    );
                    D('Uploads')->deleteFile($fileArr);
                    $return = D('Uploads')->uploadImg('fileInput_'.$i,"posts",$i,$id,"[[200,200," . $i . C('thumbImgSize') . "]]");
                    $pathArr[$i-1] = $return['url'];
                }
            }
            if(!empty($pathArr)){
                //修改图片地址
                $img_array = M('CommunityPosts')->where(['id'=>$id])->getField('img');
                $imgArr = json_decode($img_array,true);
                if(!empty($imgArr)){
                    foreach ($imgArr as $k => $v) {
                        foreach ($pathArr as $key => $value) {
                            if($k != $key){
                                $imgArr[$key] = $value;
                            }
                        }
                    }
                    ksort($imgArr);
                    M("CommunityPosts")->where(['id'=>$id])->save(['img'=>json_encode($imgArr)]);
                }else{
                    //为空直接修改
                    M("CommunityPosts")->where(['id'=>$id])->save(['img'=>json_encode($pathArr)]);
                }
            }
        }else{
            //新增
            $arr['create_time']    = time();
            $arr['lastreply_time'] = time();
            $rs = M('CommunityPosts')->add($arr);
            for ($i=1; $i < 10 ; $i++) {
                if (!empty($_FILES['fileInput_'.$i]['tmp_name'])) {
                    $return = D('Uploads')->uploadImg('fileInput_'.$i,"posts",$i,$rs,"[[200,200," . $i . C('thumbImgSize') . "]]");
                    $pathArr[] = $return['url'];
                }
            }
            if(!empty($pathArr)){
                //保存路径
                M("CommunityPosts")->where(['id'=>$rs])->save(['img'=>json_encode($pathArr)]);
            }
        }
        if ($rs) {
            $this->success('发帖成功!');
        } else {
            $this->error('发帖失败!');
        }
    }

    /**
     * 热点设置
     */
    public function host()
    {
        $sign = 'community';
        $data = getWebConfig($sign);
        if(IS_POST)
        {
            $config['sign'] = $sign;
            $config['config'] = json_encode(['hotPostNum'=>I('community')]);
            if($data){
                //修改
                $rs = M('config')->where(['sign'=>$sign])->save($config);
                if(!is_bool($rs))
                    $rs = true;
            }else{
                //新增
                $rs = M('config')->add($config);
            }
            if($rs){
                $this->success("设置成功！");
            }else{
                $this->error("设置失败！");
            }
        }
        $this->assign('data',$data);
        $this->display();
    }
    /**
     * 批量屏蔽
     * @author liangzk <1343724998@qq.com>
     * @since V1.0 2016-07-08
     */
    public function batchShield()
    {
        $ids = isset($_POST['id']) ? $_POST['id'] : null;
        if ($ids) {
            $idsArr = explode(',', $ids);
            $condition = array ("id" => array ('in',$idsArr));
            $rs = M('CommunityPosts')->where($condition)->save(['status'=>0]);
            if($rs !== false){
                $this->success('屏蔽成功','_currentUrl_');
            }else{
                $this->error('屏蔽失败');
            }
        } else {
            $this->error('非法操作');
        }
    }

    //异步删除图片
    public function delPic(){
        $id = I('id');
        $number = I('number');
        if(empty($id)){
            $this->error('参数错误!');
        }
        $fileArr = array(
            "/posts/{$id}/{$number}.jpg",
            "/posts/{$id}/{$number}.gif",
            "/posts/{$id}/{$number}.png",
            "/posts/{$id}/{$number}.swf",
            "/posts/{$id}/{$number}".C('thumbImgSize').".jpg",
            "/posts/{$id}/{$number}".C('thumbImgSize').".gif",
            "/posts/{$id}/{$number}".C('thumbImgSize').".png",
            "/posts/{$id}/{$number}".C('thumbImgSize').".swf",
        );
        //执行删除
        $return = D('Uploads')->deleteFile($fileArr);
        if($return['status'] == 1){
            $img_array = M('CommunityPosts')->where(['id'=>$id])->getField('img');
            $imgArr = json_decode($img_array,true);
            foreach ($imgArr as $k => $v) {
                if($k == $number-1){
                    unset($imgArr[$k]);
                }
            }
            //修改路径
            M("CommunityPosts")->where(['id'=>$id])->save(['img'=>json_encode($imgArr)]);
            $this->success('删除成功！');
        }else{
            $this->error('删除失败！');
        }
    }

    /**
    * 添加删除操作  (多个删除)
    * @access
    * @return string
    */
    public function delAll(){
        //删除指定记录
        $model = M("CommunityPosts");
        if (!empty($model)) {
            $ids = isset($_POST['id']) ? $_POST['id'] : null;
            if ($ids) {
                $idsArr = explode(',', $ids);
                $cidArr = $model->field('id,cid')->where(array ("id" => array ('in',$idsArr)))->select();
                
                if (false !== $model->where(array ("id" => array ('in',$idsArr)))->delete()) 
                {
                    foreach ($idsArr as $k => $v) 
                    {
                        //删除图片
                        $return = D('Uploads')->deleteFile(array("/posts/{$v}"));
                    }
                    foreach ($cidArr as $k => $v) {
                        //圈子帖子数量减一
                        M('community')->where(['id'=>$v['cid']])->setDec('post_num');
                    }
                    //删除评论
                    M('communityComment')->where(array ("post_id" => array ('in',$idsArr)))->delete();
                    $this->success('删除成功！');
                } else {
                    $this->error('删除失败！');
                }
            } else {
                $this->error('非法操作');
            }
        }
    }

    //刷评论
    public function addComment()
    {
        $id = I('id');
        if(IS_POST){
            $comment  = I('comment');
            $create_time = I('create_time');
            //获取机器人用户
            $robotUser = M('FrontUser')->where(['is_robot'=>1])->field('id')->select();
            shuffle($robotUser); //打乱数组
            foreach ($comment as $k => $v) {
                if(!empty($v['content'])){
                    $v['post_id']        = $id;
                    $v['filter_content'] = base64_encode($v['content']);
                    $v['content']        = base64_encode($v['content']);
                    $v['platform']       = rand(1,3);
                    $user_id             = $robotUser[$k]['id'];
                    $v['user_id']        = $user_id;
                    $v['create_time']    = rand($create_time,time());
                    $arr[]  =  $v;
                }
            }
            $rs = M('communityComment')->addAll($arr);
            if ($rs) {
                M('CommunityPosts')->where(['id'=>$id])->setInc('comment_num',count($arr));
                $this->success('发布成功!');
            } else {
                $this->error('发布失败!');
            }
            exit;
        }
        $vo = M("CommunityPosts")->field("id,base64_title,create_time")->find($id);
        if(!$vo) $this->error("参数错误!");
        $this->assign('vo', $vo);
        $this->display();
    }

}

?>