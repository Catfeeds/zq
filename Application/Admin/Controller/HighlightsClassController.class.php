 <?php
/**
 * 集锦分类控制器
 * @author dengweijun <406516482@qq.com>
 * @since  2018-1-16
 */
use Think\Tool\Tool;
class HighlightsClassController extends CommonController {
    /**
    *构造函数
    *
    * @return  #
    */
    public function _initialize()
    {
        parent::_initialize();
        //手动指定显示条数
        $_REQUEST ['numPerPage'] = 1000;
    }
    public function index()
    {
        //列表过滤器，生成查询Map对象
        $map = $this->_search ("HighlightsClass");
        //获取列表
        $list = $this->_list ( CM('HighlightsClass'), $map);
        if($map['status']==NULL && $map['name']==NULL){
            $list = Tool::getTree($list, $pid = 0, $col_id = 'id', $col_pid = 'pid', $col_cid = 'level');
        }
        $this->assign ('list', $list);
        $this->display();
    }

    /**
     * 编辑指定记录
     *
     * @return string
     *
    */
    function edit() {
        $id = Tool::request("id");
        $vo = M('HighlightsClass')->find($id);
        if (!$vo){
            $this->error('参数错误');
        }
        //获取所有记录
        $list = M('HighlightsClass')->where(['id'=>['neq',$vo['id']]])->select();
        //引用Tree类
        $list = Tool::getTree($list, $pid = 0, $col_id = 'id', $col_pid = 'pid', $col_cid = 'level');
        $this->assign ('list', $list);
        $this->assign ('vo', $vo);
        $this->display('add');
    }
    /**
     * 添加记录
     *
     * @return string
     *
    */
    function add() {
        //获取所有记录
        $list = M('HighlightsClass')->select();
        //引用Tree类
        $list = Tool::getTree($list, $pid = 0, $col_id = 'id', $col_pid = 'pid', $col_cid = 'level');
        $this->assign ('list', $list);
        $this->assign ('add', $add);
        $this->display();
    }

    /**
     * 添加/编辑分类表数据
     * @return #
    */
    public function save()
    {
        //是否为修改标志
        $id = I('id');
        //检验数据
        $model = D('HighlightsClass');
        $validate = $model->create();
        if($validate['level'] > 3){
            $this->error('只能添加3层分类哦!');
        }
        //判断数据对象是否通过
        if( !$validate ){
            //返回错误提示
            $this->error($model->getError());
        }
        if (!empty($id)){
            //为修改
            $rs = $model->save();
            if (!is_bool($rs)){
                $rs = true;
            }
        } else {
            //新增
            $rs = $model->add();
        }
        if (false !== $rs) {
            S('cache_video_class',null);
            //成功提示
            $this->success('保存成功!');
        } else {
            //错误提示
            $this->error('保存失败!');
        }
    }

    /**
     * 删除指定记录
     *
     * @return string
     *
    */
    public function del() {
        //只允许单个删除
        $id = $_REQUEST['id'];
        if (isset ( $id )) {
            $rs = M('HighlightsClass')->where(['pid'=>$id])->find();
            if ($rs){
                $this->error ( '请先删除下级分类' );
            } else {
                if (M('HighlightsClass')->where(['id'=>$id])->delete()){
                    $this->success ( '删除成功' );
                } else {
                    $this->error ( '删除失败' );
                }
            }
        } else {
            $this->error ( '非法操作' );
        }
    }
}