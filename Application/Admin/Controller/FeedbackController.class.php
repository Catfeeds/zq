<?php

/**
 * 用户反馈
 *
 * @author dengwj <406516482@qq.com>
 *
 * @since  2015-12-25
 */

class FeedbackController extends CommonController{
    //获取反馈意见信息
    public function index()
    {
        $map  = $this->_search("FeedbackView");
        $list = $this->_list(D('FeedbackView'),$map);
        $this->assign('list',$list);
        $this->display();
    }

    //回复内容
    public function reply()
    {
        $id = I('id', 'int');
        $model = D('Feedback');
        if(IS_POST)
        {
            if (false === $model->create()) {
                $this->error($model->getError());
            }
            $user = $model->where(array('id'=>$id))->field('user_id')->find();
            if($user['user_id'])
            {
                $do_type = I('do_type');
                $remark  = I('remark');
                $rs = $model->where(['id'=>$id])->save(
                    array(
                        'reply'      => $_POST['reply'],
                        'admin_id'   => $_SESSION['authId'],
                        'do_type'    => $do_type,
                        'remark'     => $remark,
                        'reply_time' => time(),
                    )
                );
                if($rs)
                {
                    $is_msg = I('is_msg');
                    if(empty($is_msg))
                    {
                        $result = sendMsg($user['user_id'], '反馈信息', $_POST['reply']);
                    }
                    $this->success('回复成功！');
                }
                else
                {
                  $this->error('回复失败！');
                }
            }
            else
            {
              $this->error('该会员不存在无需回复！');
            }
        }
        else
        {
            $vo = $model->find($id);
            $this->assign('vo',$vo);
            $this->display();
        }
    }
}