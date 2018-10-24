<?php
/**
 * 红包发布列表
 *
 * @author dengwj <406516482@qq.com>
 *
 * @since  2018-8-7
 */
class RedpkgController extends CommonController {
    public function index()
    {
        $model = CM('Redpkg');
        $map = $this->_search('Redpkg');
        //排序字段 默认为主键名
        $order = $_REQUEST ['_order'] ? : 'id';
        //排序方式默认按照倒序排列
        $sort = $_REQUEST ['_sort'] == 'asc' ? 'asc' : 'desc';

        $nick_name = I('nick_name');
        if ($nick_name != '')
        {
            $map['u.nick_name'] = ['like',$nick_name."%"];
        }

        $status = I('status_select');
        if ($status != '')
        {
            $map['l.status'] = $status;
        }

        //取得满足条件的记录数
        $count = $model->alias('l')
        ->join('LEFT JOIN qc_front_user u on u.id=l.user_id')
        ->where($map)->count();
        if ($count > 0)
        {
            $pageNum     = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
            $currentPage = !empty($_REQUEST[C('VAR_PAGE')]) ? $_REQUEST[C('VAR_PAGE')]:1;
            //分页查询数据
            $list = $model
                ->alias('l')
                ->join('LEFT JOIN qc_front_user u on u.id=l.user_id')
                ->field("l.*,u.nick_name")
                ->where($map)
                ->group('l.id')
                ->order( $order." ".$sort )
                ->page($currentPage,$pageNum)
                ->select();

            //红包获取记录
            $pids = array_column($list, 'id');
            $logs = M('RedpkgLog')
                ->field('pid, value')
                ->where(['pid' => ['IN', $pids], 'get_status' => 1])
                ->select();

            foreach($logs as $k => $v){
                $get_logs[$v['pid']][] = $v['value'];
            }

            //历史红包金额统计
            $total_value = M('RedpkgLog')->sum('value');
            $total_get_value = M('RedpkgLog')->where(['get_status' => 1])->sum('value');
            //历史红包个数统计
            $total_count = M('RedpkgLog')->count();
            $total_get_count = M('RedpkgLog')->where(['get_status' => 1])->count();

            foreach($list as $k => $v){
                $list[$k]['get_num']  = isset($get_logs[$v['id']]) ? count($get_logs[$v['id']]) : 0;
                $list[$k]['get_coin'] = isset($get_logs[$v['id']]) ? array_sum($get_logs[$v['id']]) : 0;
                $list[$k]['send_status'] = time() > $v['start_time'] ? '1' : '0';
            }

            //模板赋值显示
            $this->assign('list', $list);
            $this->assign('total_value', $total_value);
            $this->assign('total_get_value', $total_get_value);
            $this->assign('total_count', $total_count);
            $this->assign('total_get_count', $total_get_count);
            $this->assign ( 'totalCount', $count );
            $this->assign ( 'numPerPage', $pageNum ); //每页显示多少条
            $this->assign ( 'currentPage', $currentPage);
            $this->setJumpUrl();
        }
        $this->display();
    }

    public function add(){
        $this->display();
    }

    public function edit() {
        $id = I('id');
        $vo = M("Redpkg")->find($id);
        if (!$vo){
            $this->error('参数错误');
        }
        if($vo['start_time'] <= time()){
            $this->error('活动已开始，不能编辑!');
        }
        $this->assign('vo', $vo);
        $this->display("add");
    }

    /**
     * 保存/修改记录
     *
     * @return #
    */
    public function save(){
        $id = I('id', 'int');
        $model = D('Redpkg');
        if (!$data = $model->create()) {
            $this->error($model->getError());
        }
        if($data['count'] > $data['value']){
            $this->error('红包个数不能大于数量总金额!');
        }
        $user_id = I('room_user_id');
        if(empty($user_id)){
            $this->error('请选择主播!');
        }
        $data['user_id']   = $user_id;
        $data['start_time'] = strtotime($data['start_time']);
        if($data['start_time'] <= time()){
            $this->error('活动开始时间有误!');
        }
        if (empty($id)) {
            //为新增
            $data['add_time'] = time();
            $rs = $model->add($data);
            if($rs){
                //添加红包记录
                $data['pid'] = $rs;
                $this->addRedpkgLog($data);
            }
        }else{
            //为修改
            $rs = $model->save($data);
            if($rs){
                //删除红包重新添加
                $redis = connRedis();
                $oldData = M('redpkgLog')->where(['pid'=>$data['id']])->find();
                foreach ($oldData as $k => $v){
                    $key = 'RedPacketRepertory:' . $v['unique_id'];
                    $redis->del($key);//redis 删除
                }
                
                M('redpkgLog')->where(['pid'=>$data['id']])->delete();

                $data['pid'] = $data['id'];
                $this->addRedpkgLog($data);
            }
        }
        if (false !== $rs) {
            //成功提示
            $this->success('保存成功!',cookie('_currentUrl_'));
        } else {
            //错误提示
            $this->error('保存失败!');
        }
    }

    //添加红包记录
    public function addRedpkgLog($data){
        $rankArr = getRandomDivInt($data['value'],$data['count']);
        $redpkgLog = [];
        foreach ($rankArr as $k => $v) {
            $arr = [];
            $arr['pid']      = $data['pid'];
            $arr['unique_id']= md5($data['pid'].microtime(true).rand(0,999).$k.$v);
            $arr['value']    = $v;
            $redpkgLog[]     = $arr;
        }
        M('redpkgLog')->addAll($redpkgLog);

        //红包存储到redis
        $redis = connRedis();
        foreach($redpkgLog as $k => $v){
            if($v['unique_id'] && $v['value']){
                $key = 'RedPacketRepertory:' . $v['unique_id'];
                $redis->rPush($key , $v['value']);//入列
                $redis->expire($key, 3600 * 24);
            }
        }

        //保存红包事件
        if(isset($data['status']) && $data['status'] == 1){
            $redis->zAdd('redPackEvent', $data['start_time'], $data['pid'] . '@' . $data['user_id']);
        }else{
            $redis->zDelete('redPackEvent', $data['pid'] . '@' . $data['user_id']);
        }
    }

    /**
     * 弹窗查找（房间号）
     * return #
     */
    public function findRoom(){
        $model = M('LiveUser');
        $map = $this->_search('LiveUser');
        //排序字段 默认为主键名
        $order = $_REQUEST ['_order'] ? : 'id';
        //排序方式默认按照倒序排列
        $sort = $_REQUEST ['_sort'] == 'asc' ? 'asc' : 'desc';

        $nick_name = I('nick_name');
        if ($nick_name != '')
        {
            $map['u.nick_name'] = ['like',$nick_name."%"];
        }
        $unique_id = I('unique_id');
        if ($unique_id != '')
        {
            $map['unique_id'] = ['like',$unique_id."%"];
        }

        $count = $model->alias('l')->join('LEFT JOIN qc_front_user u on u.id=l.user_id')->where($map)->count();
        $pageNum     = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
        $currentPage = !empty($_REQUEST[C('VAR_PAGE')]) ? $_REQUEST[C('VAR_PAGE')]:1;
        //分页查询数据
        $list = $model
            ->alias('l')
            ->join('LEFT JOIN qc_front_user u on u.id=l.user_id')
            ->field("l.*,u.username,u.nick_name")
            ->where($map)
            ->group('l.id')
            ->order( $order." ".$sort )
            ->page($currentPage,$pageNum)
            ->select();

        //模板赋值显示
        $this->assign('list', $list);
        $this->assign ( 'totalCount', $count );
        $this->assign ( 'numPerPage', $pageNum ); //每页显示多少条
        $this->assign ( 'currentPage', $currentPage);
        
        $tp = "Public:findLiveUserDialog";
        $this->display($tp);
    }

}