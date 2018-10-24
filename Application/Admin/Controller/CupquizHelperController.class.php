<?php

/**
 * 助力列表
 * @since
 */
use Think\Controller;

class CupquizHelperController extends CommonController
{
    public function index()
    {
        if(!empty(I('startTime') && !empty(I('endTime')))){
            $map['CH.add_time'] = ['BETWEEN',[strtotime(I('startTime')), strtotime(I('endTime'))]];
        }

        //按时间区间查询
        $name = trim(I('act_name'));
        if (!empty($name)) {
            $id = M('CupquizActivities')->where(['title' => ['Like', '%' . $name . '%']])->getField('id', true);
            $csid1 = M('CupquizSponsor')->alias('CS')->where(['act_id' => ['IN', $id]])->getField('id', true);
            $map['CH.launch_id'] = ['IN', $csid1];
        }

        //按发起者时间查询
        $sponsor_name = trim(I('sponsor_name'));
        if (!empty($sponsor_name)) {
            $id = M('FrontUser')->where(['nick_name' => ['Like', '%' . $sponsor_name . '%']])->getField('id', true);
            $csid2 = M('CupquizSponsor')->alias('CS')->where(['user_id' => ['IN', $id]])->getField('id', true);
            $map['CH.launch_id'] = ['IN',  $csid1 ? array_intersect($csid1,$csid2) : $csid2];
        }

        //按助力者时间查询
        $nickname = trim(I('nickname'));
        if (!empty($nickname)) {
            $id = M('FrontUser')->where(['nick_name' => $nickname])->getField('id');
            $map['CH.user_id'] = $id;
        }

        //按活动ID查询
        $act_id = trim(I('act_id'));
        if (!empty($act_id)) {
            $map['CH.launch_id'] = $act_id;
        }

        $count = M('CupquizHelper')->alias('CH')->where($map)->count();

        //获取每页显示的条数
        $pageNum = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
        //获取当前的页码
        $currentPage = !empty($_REQUEST[C('VAR_PAGE')]) ? $_REQUEST[C('VAR_PAGE')] : 1;
        $list = M('CupquizHelper')
            ->alias('CH')
            ->field('CH.id, CH.launch_id, CH.user_id, CH.qcoin, CH.add_time, CH.`status`, U.nick_name as sponsor_name, U.username, CS.act_id, CA.title')
            ->join('LEFT JOIN qc_cupquiz_sponsor as CS ON CS.id = CH.launch_id')
            ->join('LEFT JOIN qc_front_user as U ON U.id = CS.user_id')
            ->join('LEFT JOIN qc_cupquiz_activities as CA ON CA.id = CS.act_id')
            ->where($map)
            ->order('CH.add_time DESC')
            ->limit($pageNum)
            ->page($_REQUEST[C('VAR_PAGE')])
            ->select();

        $help_userids = array_column($list, 'user_id');

        $frontUser = M('FrontUser')
            ->field('id,username,nick_name')
            ->where(['id' => ['IN', $help_userids]])
            ->select();

        foreach($list as $k => $v){

            foreach ($frontUser as $uk => $uv){
                if($v['user_id'] == $uv['id']){
                    $list[$k]['help_name'] = $uv['nick_name'];
                }
            }
        }

        $this->assign('list', $list);
        $this->assign('totalCount', $count);//当前条件下数据的总条数
        $this->assign('numPerPage', $pageNum); //每页显示多少条
        $this->assign('currentPage', $currentPage);//当前页码
        $this->setJumpUrl();
        $this->display();
    }

    //编辑
    public function edit()
    {
        $id = I('id');
        $model = M('CupquizHelper');
        $vo = $model->where(['id'=>$id])->find();
        if(!$vo){
            $this->error('参数错误!');
        }
        $this->assign('vo',$vo);
        $this->display();
    }

    public function update()
    {
        $model =  M('CupquizHelper');
        if (false === $model->create()) {
            $this->error($model->getError());
        }

        // 更新数据
        $list = $model->save();
        if (false !== $list) {
            //成功提示
            $this->success('编辑成功!',cookie('_currentUrl_'));
        } else {
            //错误提示
            $this->error('编辑失败!');
        }
    }

    public function forbidAll()
    {
        $model =  M('CupquizHelper');

        // 更新数据
        $list = $model->where(['id' => ['IN', I('id')]])->save(['status' => 0]);
        if (false !== $list) {
            //成功提示
            $this->success('编辑成功!',cookie('_currentUrl_'));
        } else {
            //错误提示
            $this->error('编辑失败!');
        }
    }


}