 <?php
/**
 * ip黑名单控制器
 * @author dengwj <406516482@qq.com>
 * @since  2018-1-5
 */

class ShieldIpController extends CommonController {
    /**
    *构造函数
    *
    * @return  #
    */
    public function _initialize()
    {
        parent::_initialize();

    }

    public function add()
    {
        $this->display('edit');
    }

    /**
     * 保存/修改记录
     *
     * @return #
     */
    public function save(){
        $id = I('id', 'int');
        $model = D('ShieldIp');
        if (false === $model->create()) {
            $this->error($model->getError());
        }
        if (empty($id)) {
            //为新增
            $model->createtime = time();
            $rs = $model->add();
        }else{
            //为修改
            $rs = $model->save();
        }
        if (false !== $rs) {
            //成功提示
            $this->success('保存成功!',cookie('_currentUrl_'));
        } else {
            //错误提示
            $this->error('保存失败!');
        }
    }
}