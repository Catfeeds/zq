 <?php
/**
 * APP下载点击统计控制器
 *
 * @author dengweijun <406516482@qq.com>
 *
 * @since  2016-08-26
 */

class AppLogController extends CommonController {
    /**
     * 分类列表
     * @return string     
    */
    public function index()
	{
		//列表过滤器，生成查询Map对象
		$map = $this->_search ("AppLog");
        //时间查询
        if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
            if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                $startTime = strtotime($_REQUEST ['startTime']);
                $endTime   = strtotime($_REQUEST ['endTime'])+86400;
                $map['add_time'] = array('BETWEEN',array($startTime,$endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']);
                $map['add_time'] = array('EGT',$strtotime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime'])+86400;
                $map['add_time'] = array('ELT',$endTime);
            }
        }
        $device = I('device');
        if(!empty($device)){
            $iPhone_wiki = C('iPhone_wiki');
            //机型搜索
            foreach ($iPhone_wiki as $k => $v) {
                if(strtolower($v) == strtolower($device)){
                    $deviceArr[] = $k;
                }
            }
            $map['device'] = $deviceArr ? ['in',$deviceArr] : $device;
        }

        $nickname = trim(I('nick_name'));
        if (!empty($nickname)) {
            $id = M('FrontUser')->where(['nick_name' => ['Like', '%' . $nickname . '%']])->getField('id', true);
            $map['user_id'] = ['IN', $id];
        }

		//获取列表
		$list = $this->_list ( CM('AppLog'), $map);
        $userArr = [];
        foreach ($list as $k => $v) {
            if(!empty($v['user_id'])){
                $userArr[] = $v['user_id'];
            }
        }
        $userIdArr = array_unique($userArr);
        $user = M('FrontUser')->field('id,nick_name')->where(['id'=>['in',$userIdArr]])->select();
        foreach ($list as $k => $v) {
            foreach ($user as $kk => $vv) {
                if($v['user_id'] == $vv['id']){
                    $list[$k]['nick_name'] = $vv['nick_name'];
                }
            }
        }
		$this->assign ('list', $list);
        $this->display();
    }

    //编辑
    public function edit()
    {
        $id = I('id');
        $model = M('AppLog');
        $vo = $model->where(['id'=>$id])->find();
        if(!$vo){
            $this->error('参数错误!');
        }
        $this->assign('vo',$vo);
        $this->display();
    }

    public function update()
    {
        $model =  M('AppLog');
        if (false === $model->create()) {
            $this->error($model->getError());
        }

        // 更新数据
        $list = $model->save();
        if (false !== $list) {
            //更新推送表
            $vo = $model->where(['id'=>I('id')])->find();
            if($vo['device_id']){
                M('ApnsUsers')->where(['device_id' => $vo['device_id']])->save(['status' => I('push_status')]);
            }else{
                M('ApnsUsers')->where(['user_id' => $vo['user_id']])->save(['status' => I('push_status')]);
            }

            //成功提示
            $this->success('编辑成功!',cookie('_currentUrl_'));
        } else {
            //错误提示
            $this->error('编辑失败!');
        }
    }
}