<?php
/**
 * 回帖列表控制器
 *
 * @author dengweijun <406516482@qq.com>
 *
 * @since  2016-7-1
 */
class CommunityCommentController extends CommonController
{
    /**
     * index
     */
    public function index()
    {
        //过滤
        $map = $this->_search('CommunityCommentView');
        unset($map['base64_title']);
        unset($map['content']);

        //时间查询
        if(! empty($_REQUEST ['startTime']) || ! empty($_REQUEST ['endTime'])){
            if(! empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                $startTime = strtotime($_REQUEST ['startTime']);
                $endTime   = strtotime($_REQUEST ['endTime'])+86400;
                $map['create_time'] = array('BETWEEN',array($startTime,$endTime));
            } elseif (! empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']);
                $map['create_time'] = array('EGT',$strtotime);
            } elseif (! empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime'])+86400;
                $map['create_time'] = array('ELT',$endTime);
            }
        }
        $base64_title = I('base64_title');
        if(! empty($base64_title)){
            $map['_string'] = "from_base64(base64_title) like concat('%','".$base64_title."','%')";
        }
        $content = I('content');
        if(! empty($content)){
            $map['_string'] = "from_base64(c.content) like concat('%','".$content."','%')";
        }
        if($_REQUEST['report_num'] == 1){
            $map['report_num'] = ['gt',0];
        }
        if(! empty($_REQUEST['post_id'])){
            $map['post_id'] = $_REQUEST['post_id'];
        }
        $is_capture = I('is_capture');
        if($is_capture != ''){
            $map['is_capture'] = $is_capture == 1 ? ['gt',0] : 0;
        }
        //列表
        $list = $this->_list(CM('CommunityCommentView'),$map);

        include('./Public/Plugs/emoji/emoji.php');
        foreach ($list as $k => $v) //解码
        {
            if(! empty($content))
            {
                $str = emoji_unified_to_html(emoji_docomo_to_unified(base64_decode($v['content'])));
                $contentLater = "<font style='color:red;font-size:14px;'>$content</font>";
                $list[$k]['content'] = str_replace($content, $contentLater, $str);
            }
            else
            {
                $list[$k]['content'] = emoji_unified_to_html(emoji_docomo_to_unified(base64_decode($v['content'])));
            }
        }
        $this->assign('list',$list);
        $this->display();
    }
    /**
     * 查看评论详细内容
    */

    public function forbid(){
        $type = $_REQUEST['type'];
        $id = $_REQUEST['id'];
        $user_id = $_REQUEST['user_id'];
        if($type == 1){
            if($user_id){
                M('FrontUser')->where(['id' => $user_id])->save(['status' => 0]);
            }
        }else{
            $list = M('communityComment')->where(['id' => $id])->find();
            M('communityComment')->where(['user_id'=>$list['user_id']])->save(['status' => 0]);
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
            M('communityComment')->where(['id' => $id])->save(['status' => 1]);
        }

        $this->success('状态恢复成功！');
    }

    public function check() {
        $id = I("id");
        $vo = D('CommunityCommentView')->where(array('id'=>$id))->find();
        if(!$vo){
            $this->error("参数错误");
        }
        //解码
        include('./Public/Plugs/emoji/emoji.php');
        $vo['content'] = emoji_unified_to_html(emoji_docomo_to_unified(base64_decode($vo['content'])));
        $this->assign('vo',$vo);
        $this->display();
    }
    /**
     * 修改操作
     */
    public function save()
    {
        $id      = I('id','int');
        $user_id = I('user_id','int');
        if (!$id)
        {
            $this->error('参数错误');
        }

        //屏蔽用户
        $community_status = I('community_status');
        if(!in_array($community_status, [0,2]))
        {
            $community_status = time()+86400;
        }

        $rs  = M('FrontUser')->where(['id'=>$user_id])->save(['community_status'=>$community_status]);
        if (!is_bool($rs))  $rs  = true;

        //修改评论状态
        $rs2 = M('communityComment')->where(['id'=>$id])->save(['status'=>I('status')]);
        if (!is_bool($rs2)) $rs2 = true;

        if($community_status == 2){
            //禁用所有帖子和评论
            M('communityPosts')->where(['user_id'=>$user_id])->save(['status'=>0]);
            M('communityComment')->where(['user_id'=>$user_id])->save(['status'=>0]);
        }

        $is_reply = I('is_reply');
        if($is_reply == 1){
            //回复举报
            $report_user = I('report_user');
            if(empty($report_user)){
                $this->error("没有用户举报，不能回复哦！");
            }
            $user = explode(',', $report_user);
            $content = I('reply');
            if(empty($content)){
                $this->error("回复内容不能为空哦！");
            }
            //给所有举报的用户回复
            $rs3 = sendMsg($user,'您的举报回复',$content);
            if($rs3){
                //修复为已处理回复
                M('CommunityComment')->where(['id'=>$id])->save(['is_report'=>1]);
            }
        }else{
            $rs3 = true;
        }

        if ($rs && $rs2 && $rs3) {
            //成功提示
            $this->success('保存成功!',cookie('_currentUrl_'));
        } else {
            //错误提示
            $this->error('保存失败!');
        }
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
            $rs = M('CommunityComment')->where($condition)->save(['status'=>0]);
            if($rs !== false){
                $this->success('屏蔽成功','_currentUrl_');
            }else{
                $this->error('屏蔽失败');
            }
        } else {
            $this->error('非法操作');
        }
    }

    /**
    * 添加删除操作  (多个删除)
    * @access
    * @return string
    */
    public function delAll(){
        //删除指定记录
        $model = M("CommunityComment");
        if (!empty($model)) {
            $ids = isset($_POST['id']) ? $_POST['id'] : null;
            if ($ids) {
                $idsArr = explode(',', $ids);
                $condition = array ("id" => array ('in',$idsArr));
                $postidArr = $model->field('id,post_id')->where($condition)->select();
                if (false !== $model->where($condition)->delete()) 
                {
                    foreach ($postidArr as $k => $v) 
                    {
                        //帖子回帖数减一
                        M('CommunityPosts')->where(['id'=>$v['post_id']])->setDec('comment_num');
                    }
                    $this->success('删除成功！');
                } else {
                    $this->error('删除失败！');
                }
            } else {
                $this->error('非法操作');
            }
        }
    }
}

?>