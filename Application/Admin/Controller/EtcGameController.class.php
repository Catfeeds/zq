<?php
/**
 * 粤卡通竞猜
 *  @author liangzk <1343724998@qq.com>
 *
 * @since  2016-6-2
 */
use Think\Controller;
use Think\Tool\Tool;
class EtcGameController extends CommonController{

    public function index()
    {
        //列表过滤器，生成查询对象
        $map = $this->_search('EtcGame');

        $list=$this->_list(CM('EtcGame'),$map);
        $this->assign('list',$list);
        $this->display();

    }
    /**
     * 修改/添加操作
     */
    public function edit()
    {
        $id = I("id");
        if($id)
        {
            $vo = M('EtcGame')->find($id);
            if (!$vo)
            {
                $this->error('参数错误');
            }
        }
        $this->assign ('vo', $vo);
        $this->display("add");

    }
    /**
     * 添加/修改操作
     */
    public function save()
    {
        $id=I('id','int');
        $model = D('EtcGame');
        if (false === $model->create()) {
            $this->error($model->getError());
        }
        if(!empty($id))
        {
            $resEtcGame=$model->save();
        }
        else
        {
            $resEtcGame=$model->add();
        }
        if (false !== $resEtcGame) {
            //成功提示
            $this->success('保存成功!',cookie('_currentUrl_'));
        } else {
            //错误提示
            $this->error('保存失败!');
        }
    }
}
?>