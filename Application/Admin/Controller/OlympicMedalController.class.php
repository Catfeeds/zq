 <?php
/**
 * 奥运奖牌榜控制器
 *
 * @author dengweijun <406516482@qq.com>
 *
 * @since  2016-7-7
 */

class OlympicMedalController extends CommonController 
{
    public function edit()
    {
    	$id=I('id');
    	$vo = M('OlympicMedal')->where()->find($id);
    	if(!$vo){
    		$this->error('参数错误');
    	}
    	$this->assign('vo',$vo);
    	$this->display('add');
    }
    /**
     * 保存/修改记录
     *
     * @return #
    */
    public function save()
    {
        $id = I('id', 'int');
        $model = D('OlympicMedal');
        if (false === $model->create()) 
        {
            $this->error($model->getError());
        }
        $model->get_time = strtotime(I('get_time'));
        if (empty($id)) {
            //为新增
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