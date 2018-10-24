<?php
    /**
     *ETC管理的粤卡通用户
     *  @author liangzk <1343724998@qq.com>
     *
     *  @since  2016-6-6
     *
     */
    class EtcUserController extends CommonController{
        /**
         * Index 首页
         *
         */
        public  function indx()
        {
            //列表过滤器，生成查询对象
            $map = $this->_search('EtcUser');

            $list=$this->_list(CM('EtcUser'),$map);
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
                $vo = M('EtcUser')->find($id);
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
            $etcUserModel=D('EtcUser');
            if(false===$etcUserModel->create())
            {
                $this->error($etcUserModel->getError());
            }
            if(!empty($id))
            {
                $resEtcUser=$etcUserModel->save();
            }
            else
            {
                $resEtcUser=$etcUserModel->add();
            }
            if (false !== $resEtcUser) {
                //成功提示
                $this->success('保存成功!',cookie('_currentUrl_'));
            } else {
                //错误提示
                $this->error('保存失败!');
            }

        }
}
?>