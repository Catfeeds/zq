<?php
    /**
     *ETC管理的公告说明
     *  @author liangzk <1343724998@qq.com>
     *
     *  @since  2016-6-6
     *
     */
    class EtcNoticeController extends CommonController{
        /**
         * Index 首页
         *
         */
        public  function indx()
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
                $vo = M('EtcNotice')->find($id);
                if (!$vo)
                {
                    $this->error('参数错误');
                }
            }
            $this->assign ('vo', $vo);
            $this->display("add");

        }
        /**
     * 添加/保存操作
     */
    public function save()
    {
        $id=I('id','int');
        $etcNoticeModel=D('EtcNotice');
        if(false===$etcNoticeModel->create())
        {
            $this->error($etcNoticeModel->getError());
        }
        if(!empty($id))
        {
            $resEtcNotice=$etcNoticeModel->save();
        }
        else
        {
            $resEtcNotice=$etcNoticeModel->add();
        }
        if(false!==$resEtcNotice)
        {
            //成功提示
            $this->success('保存成功',cookie('_currentUrl_'));
        }
        else
        {
            //失败提示
            $this->error('保存失败');
        }
    }

}
?>