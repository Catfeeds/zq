<?php
/**
 *ETC管理的粤卡通竞猜记录
 *  @author liangzk <1343724998@qq.com>
 *
 *  @since  2016-6-6
 *
 */
class EtcQuizController extends CommonController{

    /**
     * Index 首页
     */
    public function index()
    {

        //列表过滤器，生成查询对象
        $map=$this->_search('EtcQuizView');
        $user_id=I('user_id');
        //对个人的粤卡通竞猜记录的查询
        if(!empty($user_id))
        {
            $map['user_id']=$user_id;
        }
        //时间查询
        if(!empty($_REQUEST['startTime']) || !empty($_REQUEST['endTime'])){
            if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST['endTime'])){
                $startTime = strtotime($_REQUEST['startTime']);
                $endTime   = strtotime($_REQUEST['endTime'])+86400;
                $map['add_time'] = array('BETWEEN',array($startTime,$endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST['startTime']);
                $map['add_time'] = array('EGT',$strtotime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime'])+86400;
                $map['add_time'] = array('ELT',$endTime);
            }
        }
        if($_REQUEST['id'])
        {
            $map['id']=$_REQUEST['id'];
        }
        $list=$this->_list(D('EtcQuizView'),$map);
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
            $vo = M('EtcQuiz')->find($id);
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
        $data=array(
            'user_id'=>I('user_id'),
            'game_id'=>I('game_id'),
            'bet_coin'=>I('bet_coin'),
            'res_coin'=>I('res_coin'),
            'bet_type'=>I('bet_type'),
            'res'=>I('res'),
            );
        $ectQuizSel=M('EtcQuiz')->where(['id'=>$id])->find();
        if($ectQuizSel)
        {

            $resEtcQuiz=M('EtcQuiz')->where(['id'=>$id])->save($data);
            if (false !== $resEtcQuiz) {
                //成功提示
                $this->success('保存成功!',cookie('_currentUrl_'));
            } else {
                //错误提示
                $this->error('保存失败!');
            }
        }
        else
        {
            $data['add_time']=strtotime(I('add_time'));
            $resEtcQuiz=M('EtcQuiz')->add($data);
            if (false !== $resEtcQuiz) {
                //成功提示
                $this->success('添加成功!',cookie('_currentUrl_'));
            } else {
                //错误提示
                $this->error('添加失败!');
            }
        }


    }
}

?>