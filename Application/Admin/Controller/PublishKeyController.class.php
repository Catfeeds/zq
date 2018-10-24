<?php

/**
 * 文章关键词内链表
 *
 * @author liuweitao   <liuwt@qqty.com>
 *
 * @since  2018-04-17
 */
class PublishKeyController extends CommonController
{

    /**
     * 分类列表
     * @return string
     */
    public function index()
    {
        if (I('is_up') == 1) $this->keyForClass();
        //列表过滤器，生成查询Map对象
        $map = $this->_search("PublishKey");
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
        $list = $this->_list(CM('PublishKey'), $map);
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
            $list = M('PublishKey')->where(['id' => I('id')])->find();
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
        $data['web_url'] = I('web_url');
        $data['m_url'] = I('m_url');
        $data['sort'] = I('sort');
        $data['status'] = I('status');
        if (!empty($id)) {
            $rs = M('PublishKey')->where(['id' => $id])->save($data);
        } else {
            $data['add_time'] = time();
            //新增
            $rs = M('PublishKey')->add($data);
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
        $model = M("PublishKey");
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

    /*
     * 从分类列表获取带有二级域名的数据
     */
    public function keyForClass()
    {
        //定义空数组,储存修改数据跟新增数据
        $add = $edit = [];
        //获取所有包含二级域名的数据
        $where['status'] = 1;
        $where['domain'] = ['NEQ', ''];
        $domain = M('PublishClass')->field('name,domain')->where($where)->select();
        //获取已存在的关键字
        $contkey = M('PublishKey')->getField('id,name', true);
        foreach ($domain as $key => $val) {
            $url = '//' . $val['domain'] . '.' . DOMAIN;
            $id = array_search($val['name'], $contkey);
            if ($id) {
                $edit[] = ['id' => $id, 'web_url' => $url];
            } else {
                $add[] = ['name' => $val['name'], 'web_url' => $url, 'add_time' => time()];
            }
        }
        M('PublishKey')->addAll($add);
        $this->batch_update('qc_publish_key', $edit, 'id');
    }
}