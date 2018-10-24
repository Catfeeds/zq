<?php

/**
 * 发起者列表
 *
 * @since
 */
use Think\Controller;

class CupquizSponsorController extends CommonController
{
    public function index()
    {
        if(!empty(I('startTime') &&!empty(I('endTime')))){
            $map['CS.add_time'] = ['BETWEEN',[strtotime(I('startTime')), strtotime(I('endTime'))]];
        }

        if(!empty(I('check_status'))){
            $map['CS.check_status'] = I('check_status') == 2 ? 0 : I('check_status') ;
        }


        if(!empty(I('result'))){
            $map['CS.result'] = I('result') == 2 ? 0 : I('result') ;
        }
       
        $name = trim(I('act_name'));
        if (!empty($name)) {
            $id = M('CupquizActivities')->where(['title' => ['Like', '%' . $name . '%']])->getField('id', true);
            $map['CS.act_id'] = ['IN', $id];
        }

        $nickname = trim(I('nickname'));
        if (!empty($nickname)) {
            $id = M('FrontUser')->where(['nick_name' => ['Like', '%' . $nickname . '%']])->getField('id', true);
            $map['CS.user_id'] = ['IN', $id];
        }

        $count = M('CupquizSponsor')->alias('CS')->where($map)->count();
        //获取每页显示的条数
        $pageNum = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
        //获取当前的页码
        $currentPage = !empty($_REQUEST[C('VAR_PAGE')]) ? $_REQUEST[C('VAR_PAGE')] : 1;
        $list = M('CupquizSponsor')
            ->alias('CS')
            ->field('CS.id,CS.user_id, CS.act_id, CS.help_num, CS.help_num, CS.limit_num, 
            CS.qcoin, CS.result, CS.is_send_coin, CS.send_coin_time, CS.send_coin, 
            CS.result_time, CS.settle_time, CS.is_settle as quiz_settle, CS.settle_type, CS.status, CS.add_time,CS.check_status,
            U.nick_name,U.username,CA.title')
            ->join('LEFT JOIN qc_front_user as U ON CS.user_id = U.id')
            ->join('LEFT JOIN qc_cupquiz_activities as CA ON CA.id = CS.act_id')
            ->where($map)
            ->order('CS.id DESC')
            ->limit($pageNum)
            ->page($_REQUEST[C('VAR_PAGE')])
            ->select();

        foreach($list as $k => $v){
            $list[$k]['bg'] = $v['act_id'] % 2 == 0 ? 'style="background-color: #eff9fd"' : '';
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
        $model = M('CupquizSponsor');
        $vo = $model->where(['id'=>$id])->find();
        if(!$vo){
            $this->error('参数错误!');
        }
        $this->assign('vo',$vo);
        $this->display();
    }

    public function update()
    {
        $model =  M('CupquizSponsor');
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
        $model =  M('CupquizSponsor');

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

    //全部金币结算
    public function coin_settle()
    {
        //筛选已经结算竞猜的、已经结算助力的、已经审核通过的 并且未发放金币的发起活动
        $w = ['is_send_coin' => 0, 'result' => ['IN', ['1', '-1']], 'is_settle' => 1];
        $sponsor = M("CupquizSponsor")->where($w)->select();

        if ($sponsor) {
            try {
                M()->startTrans();
                foreach ($sponsor as $k => $v) {
                    if ($v['status'] == 1 && $v['check_status'] == 1 && $v['result'] == 1) {
                        $qcoin = (int)$v['qcoin'];
                        if ($qcoin > 0) {
                            $userInfo = M('FrontUser')
                                ->field(['coin','unable_coin','(coin+unable_coin) as totalCion'])
                                ->where(['id' => $v['user_id'], 'status' => 1])
                                ->find();
                            if ($userInfo) {//判断用户状态，给用户增加金币
                                $res = M('FrontUser')->where(['id' => $v['user_id']])->setInc('coin', $qcoin);

                                $res2 = M('AccountLog')->add([
                                    'user_id'    =>  $v['user_id'],
                                    'log_time'   =>  NOW_TIME,
                                    'log_type'   =>  21,
                                    'log_status' =>  1,
                                    'change_num' =>  $qcoin,
                                    'total_coin' =>  $userInfo['totalCion'] + $qcoin,
                                    'desc'       =>  "世界杯好友助力",
                                    'platform'   =>  4,
                                    'operation_time' => NOW_TIME
                                ]);

                                if($res === false || $res2 === false){
                                    throw new Exception('结算错误，已回滚事务，请重试！！', -1);
                                }
                            }
                        }

                        //金币发放----发起活动金币结算状态更新
                        $sponsorUp = ['is_send_coin' => 1, 'send_coin' => $qcoin, 'send_coin_time' => time()];
                        $res3 = M("CupquizSponsor")->where(['id' => $v['id']])->save($sponsorUp);
                        if($res3 === false){
                            throw new Exception('结算错误，已回滚事务，请重试！！', -2);
                        }
                    }

                    //金币不发放----发起活动金币结算状态更新
                    if( $v['check_status'] == -1 || $v['status'] == 0 || $v['result'] == -1){
                        $sponsorUp = ['is_send_coin' => -1, 'send_coin' => 0, 'send_coin_time' => time()];
                        M("CupquizSponsor")->where(['id' => $v['id']])->save($sponsorUp);
                    }
                }
                M()->commit();
            } catch (Exception $e) {
                M()->rollback();
                $this->error($e->getMessage().':'.$e->getCode());
                exit;
            }
        }

        $this->success('发放成功！！');
    }

}