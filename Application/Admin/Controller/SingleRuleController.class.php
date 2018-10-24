<?php
/**
 * 单场竞猜游戏规则管理
 *
 * @author liuweitao <cytusc@foxmail.com>
 * @since  2016-11-24
 */
    class SingleRuleController extends CommonController{
        /**
         * Index 首页
         *
         */
        public  function index()
        {
            if($_POST['multiple'] != 2 && isset($_POST['multiple']))
            {
                $where['single_multiple'] = I('multiple');
            }
            $where['status'] = 1;
            $single_id = M('SingleList')->where($where)->getField('single_title_id',true);
            $map['title_id'] = array('in',join(",",$single_id));
            if(I('id')) $map['id'] = ['eq',I('id')];
            $title = I('title');
            if(I('title')) $map['rule'] = ['like',"$title%"];
            $list=$this->_list(CM('SingleRule'),$map);
            foreach ($list as &$val)
            {
                $title = M('SingleTitle')->where("id = ".$val['title_id'])->getField('single_title');
                $val['multiple'] = M('SingleList')->where('single_title_id = '.$val['title_id'])->getField('single_multiple');
                $val['title'] = $title;
                $val['rule'] = str_replace("<br />","\n",$val['rule']);
            }
            $this->assign('list',$list);
            $this->display();
        }
        /**
         * 修改/添加操作
         */
        public function edit()
        {
            $_REQUEST['multiple'] = I('multiple');
            $id = I("id");
            if($id)
            {
                $vo = M('SingleRule')->find($id);
                $vo['rule'] = str_replace("<br />","\n",$vo['rule']);
                if (!$vo)
                {
                    $this->error('参数错误');
                }
            }
            $single = M('SingleList');
            if($_GET['multiple'] != 2 && isset($_GET['multiple']))
            {
                $where['single_multiple'] = I('multiple');
            }
            $where['status'] = 1;
            $res = $single->where($where)->getField('single_title_id',true);
            if($_GET['multiple'] != '0') $res = array_unique($res);
            $option = array();
            foreach($res as $key=>$val)
            {
                $option[$key]['title_id'] = $val;
                $option[$key]['single_title'] = M('SingleTitle')->where('id = '.$val)->getField('single_title');
            }
            $this->assign ('vo', $vo);
            $this->assign ('option', $option);
            $this->display("add");

        }
        /**
     * 添加/保存操作
     */
    public function save()
    {
        $id = M('SingleRule')->where('title_id = '.$_POST['title_id'])->getField('id');
        $rule = M('SingleRule');
        unset($_POST['multiple']);
        if($id)
        {
            unset($_POST['id']);
            $_POST['rule'] = str_replace("\n","<br />",$_POST['rule']);
            $rs = $rule->where('id ='.$id)->save($_POST);
        }else{
            unset($_POST['id']);
            $_POST['rule'] = str_replace("\n","<br />",$_POST['rule']);
            $_POST['add_time'] = time();
            $rs = $rule->add($_POST);
        }
        if(false!==$rs)
        {
            //成功提示
            $this->success('保存成功',cookie('_currentUrl_'));
        }
        else
        {
            //失败提示
            $this->error('保存失败');
        }
    }



        /**
         * ajax查询活动标题
         * @return string
         *
         */
        function ajaxget()
        {
            $single = M('SingleList');
            if($_GET['multiple'] != 2 && isset($_GET['multiple']))
            {
                $where['single_multiple'] = I('multiple');
            }
            $where['status'] = 1;
            $res = $single->where($where)->getField('single_title_id',true);
            $res = array_unique($res);
            $option = array();
            $num = 0;
            foreach($res as $key=>$val)
            {
                $option[$num]['title_id'] = $val;
                $option[$num]['single_title'] = M('SingleTitle')->where('id = '.$val)->getField('single_title');
                $num++;
            }
            echo json_encode($option);exit;

        }
}
?>