<?php
/**
 * 单场竞猜游戏公告管理
 *
 * @author liuweitao <cytusc@foxmail.com>
 * @since  2016-11-24
 */

class SingleBbsController extends CommonController{
    /**
     * 竞猜列表
     * @return string
     */
    public function index()
    {
        //列表过滤器，生成查询Map对象
        $map = $this->_search ("PublishList");
        $map['is_bbs'] = 1;
        if(I('id')) $map['id'] = ['eq',I('id')];
        $title = I('title');
        if(I('title')) $map['title'] = ['like',"%$title%"];
        //获取列表
        $list = $this->_list(CM("PublishList"), $map);
        $this->assign('list',$list);
        $this->display();
    }


    /**
     *
     * 编辑指定记录
     *
     * @return string
     *
     */
    function edit() {
        $id = I('id');
        //获取课程内容信息
        $vo = M('SingleList')->find($id);
//        if (!$vo){
//            $this->error('参数错误');
//        }
        //查出所有选项
        $this->display ('add');
    }
    /**
     *
     * 添加公告
     *
     * @return string
     *
     */
    function add() {
        $id = I('id');
        $rs = M('PublishList')->where("id = ".$id)->setField('is_bbs',1);
        if($rs){
            $this->success('添加成功');
        }else{
            $this->error('添加失败');
        }
    }

    /**
     * 删除指定记录
     *
     * @return string
     *
     */
    public function remove() {
        $id = $_REQUEST['id'];
        if (isset ( $id )) {
            if (false !== M('PublishList')->where ( "id = ".$id )->setField('is_bbs','0')) {
                $this->success ( '删除成功' );
            } else {
                $this->error ( '删除失败' );
            }
        } else {
            $this->error ( '非法操作' );
        }
    }


    /**
     * ajax查询文章标题
     * @return string
     *
     */
    function gettitle()
    {
        $pid = I('pid');
        $pub = M("PublishList");
        $res = $pub->where("id = ".$pid)->getField('title');
        if($res)
        {
            echo $res;exit;
        }else{
            echo "没有相关数据";exit;
        }

    }

    /**
    +----------------------------------------------------------
     * 添加删除操作  (多个删除)
    +----------------------------------------------------------
     * @access public
    +----------------------------------------------------------
     * @return string
    +----------------------------------------------------------
     * @throws ThinkExecption
    +----------------------------------------------------------
     */

    public function delAll(){
        //删除指定记录
        $model = M("PublishList");
        if (!empty($model)) {
            $ids = isset($_POST['id']) ? I('id') : null;
            if ($ids) {
                $idsArr = explode(',', $ids);
                $condition = array ("id" => array ('in',$idsArr));
                if (false !== $model->where($condition)->setField('is_bbs',0)) {
                    $this->success('批量删除成功！');
                } else {
                    $this->error('批量删除失败！');
                }
            } else {
                $this->error('非法操作');
            }
        }
    }
}
?>