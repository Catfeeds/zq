<?php
/**
 * 资讯列表控制器
 *
 * @author liuweitao     <906742852@qq.com>
 *
 * @since  2018-04-27
 */
use Think\Tool\Tool;

class AppHotseachController extends CommonController
{

    /**
     * 分类列表
     * @return string
     */
    public function index()
    {
        if (I('is_up') == 1) $this->keyForClass();
        //列表过滤器，生成查询Map对象
        $map = $this->_search("AppHotseach");
        if(!empty($_REQUEST['startTime']) || !empty($_REQUEST['endTime'])){
            if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                $startTime = strtotime($_REQUEST['startTime']);
                $endTime   = strtotime($_REQUEST['endTime'])+86400;
                $map['add_time'] = array('BETWEEN',array($startTime,$endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']);
                $map['add_time'] = array('EGT',$strtotime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime'])+86400;
                $map['add_time'] = array('ELT',$endTime);
            }
        }
        //获取列表
        $list = $this->_list(CM('AppHotseach'), $map);
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 编辑指定记录
     *
     * @return string
     *
     */
    public function edit()
    {
        if (I('id')) {
            //获取所有记录
            $list = M('AppHotseach')->where(['id' => I('id')])->find();
            $this->assign('vo', $list);
        }
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
        $data['name'] = I('name');
        $data['sort'] = I('sort');
        $data['status'] = I('status');
        if (!empty($id)) {
            $rs = M('AppHotseach')->where(['id' => $id])->save($data);
        } else {
            $data['add_time'] = time();
            //新增
            $rs = M('AppHotseach')->add($data);
        }
        if (false !== $rs) {
            S('cache_publish_class', null);
            //成功提示
            $this->success('保存成功!', cookie('_currentUrl_'));
        } else {
            //错误提示
            $this->error('保存失败!');
        }
    }

    //删除单个
    public function delete()
    {
        //删除指定记录
        $model = M("AppHotseach");
        if (!empty($model)) {
            $id = $_REQUEST ['id'];
            if (isset($id)) {
                $condition = array('id' => $id);
                if (false !== $model->where($condition)->delete()) {
                    $this->success('删除成功    ！');
                } else {
                    $this->error('删除失败！');
                }
            } else {
                $this->error('非法操作');
            }
        }
    }

}