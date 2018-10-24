<?php

/**
 * 优惠卷
 *
 * @author
 *
 * @since
 */
use Think\Controller;
class TicketConfController extends CommonController {
    //主页列表
    public function index()
    {
        $map = $this->_search('TicketConf');
        $list = $this->_list(CM('TicketConf'),$map);
        $this->assign('list',$list);
        $this->display();
    }

    //体验劵/优惠券记录列表
    public function ticketLog()
    {
        //排序字段 默认为主键名
        $order = $_REQUEST ['_order'] ? : 'id';
        //排序方式默认按照倒序排列
        $sort = $_REQUEST ['_sort'] == 'asc' ? 'asc' : 'desc';
        $map = $this->_search('ticketLog');
        unset($map['user_id']);
        //获得时间
        if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
            if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime']))
            {
                $startTime = strtotime($_REQUEST ['startTime']);
                $endTime   = strtotime($_REQUEST ['endTime'])+86400;
                $map['get_time'] = array('BETWEEN',array($startTime,$endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']);
                $map['get_time'] = array('EGT',$strtotime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime']);
                $map['get_time'] = array('ELT',$endTime);
            }
        }
        $nick_name = I('nick_name');
        if(! empty($nick_name))
            $map['nick_name'] = ['like','%'.$nick_name.'%'];

        $user_id = I('user_id');
        if($user_id != '')
            $map['t.user_id'] = $user_id;

        $page = !empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1;
        $pageNum = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];

        $countList = M('TicketLog t')->join('left join qc_front_user f on t.user_id = f.id')->where($map)->count();
        $this->assign('totalCount',$countList);

        $list = M('TicketLog t')
            ->join('left join qc_front_user f on t.user_id = f.id')
            ->join('left join qc_quiz_log l on t.id = l.ticket_id')
            ->field('t.*,l.gamble_id,f.nick_name')
            ->where($map)
            ->page($page)
            ->limit($pageNum)
            ->group('t.id')
            ->order($order." ".$sort)
            ->select();
        $this->setJumpUrl();
        $this->assign ( 'numPerPage', $pageNum );
        $this->assign('list',$list);
        $this->assign ( 'currentPage', !empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1);
        $this->display();
    }

    //新增
    public function add()
    {
        $this->display("edit");
    }

    //编辑
    public function edit()
    {
        $id = I('id');
        $model = M('TicketConf');
        $vo = $model->where(['id'=>$id])->find();
        if(!$vo){
            $this->error('参数错误!');
        }
        $this->assign('vo',$vo);
        $this->display();
    }

    //新增编辑体验卷设置
    public function save(){
        $id = I('id', 'int');
        $model = D('TicketConf');
        if (!$data = $model->create()) {
            $this->error($model->getError());
        }
        $model->start_time = strtotime($model->start_time);
        $model->end_time   = strtotime($model->end_time);
        $model->over_time  = strtotime($model->over_time) + 86399;
        if($model->start_time > $model->end_time){
            $this->error('发行开始时间必须小于结束时间!');
        }
        if($model->over_time <= $model->end_time){
            $this->error('券有效期必须大于发行结束时间!');
        }
        if($model->over_num > $model->totle_num){
            $this->error('券剩余数量不能大于总数量!');
        }
        if (empty($id)) {
            //为新增
            $rs = $model->add();
        }else{
            //为修改
            $rs = $model->save();
        }
        if ($rs !== false) {
            //成功提示
            $this->success('保存成功!',cookie('_currentUrl_'));
        } else {
            //错误提示
            $this->error('保存失败!');
        }
    }


    //批量设置禁用启用
    public function onOff()
    {
        $sign = I('get.sign');
        $id = I('post.id');
        if(isset($id))
        {
            $status = $sign == 'open' ? 1 : 0;
            $re =M('TicketConf')->where(['id'=>['in',$id]])->save(['status'=>$status]);
            if($re !== false)
            {
                $this->success('批量设置成功');
            }
            else
            {
                $this->error('批量设置失败');
            }
        }
        else
        {
            $this->error("非法操作");
        }
    }

    //修改体验卷状态
    public function saveStatus(){
        $where['id'] = $_REQUEST['id'];
        unset($_REQUEST['id']);
        $rs = M('ticketLog')->where($where)->save($_REQUEST);
        if($rs !== false){
            $this->success('保存成功');
        }else{
            $this->error('保存失败');
        }
    }
}