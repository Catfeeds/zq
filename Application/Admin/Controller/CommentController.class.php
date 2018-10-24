<?php
/**
 * 资讯分类控制器
 *
 * @author dengweijun <406516482@qq.com>
 *
 * @since  2016-4-5
 */
use Think\Tool\Tool;
class CommentController extends CommonController {
    /**
    *构造函数
    *
    * @return  #
    */
    public function _initialize()
    {
        parent::_initialize();
    }
    /**
     * 分类列表
     * @return string
    */
    public function index()
	{
		//列表过滤器，生成查询Map对象
		$map = $this->_search ("Comment");
		$title = I('title');
		if(! empty($title)){
			$map['title'] = ['like','%'.$title.'%'];
		}
		$content = I('content');
		if(! empty($content)){
			$map['content'] = ['like','%'.$content.'%'];
		}
		$reg_ip = I('reg_ip');
		if(! empty($reg_ip)){
			$map['reg_ip'] = ['like','%'.$reg_ip.'%'];
		}
		$nick_name = I('nick_name');
		if(! empty($nick_name)){
			$map['nick_name'] = ['like','%'.$nick_name.'%'];
		}
		//时间查询
		if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
		    if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
		        $startTime = strtotime($_REQUEST ['startTime']);
		        $endTime   = strtotime($_REQUEST ['endTime'])+86400;
		        $map['create_time'] = array('BETWEEN',array($startTime,$endTime));
		    } elseif (!empty($_REQUEST['startTime'])) {
		        $strtotime = strtotime($_REQUEST ['startTime']);
		        $map['create_time'] = array('EGT',$strtotime);
		    } elseif (!empty($_REQUEST['endTime'])) {
		        $endTime = strtotime($_REQUEST['endTime'])+86400;
		        $map['create_time'] = array('ELT',$endTime);
		    }
		}
		$is_report = I('is_report');
		if($is_report == 1){
			$map['report_num'] = ['gt',0];
		}elseif($is_report == 2){
			$map['report_num'] = ['eq',0];
		}
		unset($map['is_report']);
		//用户类型筛选
		$user_type = I('usertype');
		switch ($user_type)
		{
		    case '1':
		        $map['is_robot']  = ['neq',1];
		        $map['is_expert'] = ['neq',1];
		        break;
		    case '2': $map['is_expert'] = ['eq',1]; break;
		    case '3': $map['is_robot']  = ['eq',1]; break;
		    case '4': $map['user_type'] = ['eq',2]; break;
		}
		//获取列表
		$list = $this->_list ( D('CommentView'), $map);

        //高亮显示
        foreach ($list as $k => $v) {
            if(! empty($content))
            {
                $contentLater = "<font style='color:red';font-size:14px;'>$content</font>";
                $str = mb_substr($v['content'], 0, 40, 'utf-8');
                $list[$k]['content'] = str_replace($content, $contentLater, $str);
            }
        }
		$this->assign ('list', $list);
        $this->display();
    }

    //修改是否禁言
    public function saveIsGag(){
        $user_id = $_REQUEST ['user_id'];
        $is_gag  = $_REQUEST ['is_gag'];
        $rs = M('FrontUser')->where(array('id'=>$user_id))->data(array('is_gag'=>$is_gag))->save();
        if($is_gag == 1){
            //禁用所有资讯评论
            M('comment')->where(['user_id'=>$user_id])->save(['status'=>0]);
        }
        if($rs){
            $this->success('设置成功');
        }else{
            $this->error('设置失败');
        }
    }

    /**
	 * 查看评论详细内容
    */
	public function check() {
		$id = I("id");
		$vo = D('CommentView')->where(array('id'=>$id))->field("id,user_id,nick_name,content,status,is_gag,report_content,report_user,is_report")->find();
		$this->assign('vo',$vo);
		$this->display();
	}

	/**
     * 处理评论
     * @return #
    */
    public function save()
	{
		$id = I('id');
		$user_id = I('user_id');
		if(!isset($id) || !isset($user_id)){
			$this->error('参数错误!');
		}
		//修改评论状态
		$rs = M('Comment')->where(['id'=>$id])->save(['status'=>I('status')]);
		if(!is_bool($rs)){
			$rs = true;
		}
		//修改用户是否禁言
		$is_gag = I('is_gag');
		$rs2 = M('FrontUser')->where(['id'=>$user_id])->save(['is_gag'=>$is_gag]);
		if(!is_bool($rs2)) $rs2 = true;

		if($is_gag == 1){
		    //禁用所有资讯评论
		    M('comment')->where(['user_id'=>$user_id])->save(['status'=>0]);
		}
		
		$is_reply = I('is_reply');
		if($is_reply == 1){
			//回复举报
			$report_user = I('report_user');
			if(empty($report_user)){
				$this->error("没有用户举报，不能回复哦！");
			}
			$user = explode(',', $report_user);
			$content = I('reply');
			if(empty($content)){
				$this->error("回复内容不能为空哦！");
			}
			//给所有举报的用户回复
			$rs3 = sendMsg($user,'您的举报回复',$content);
			if($rs3){
				//修复为已处理回复
				M('Comment')->where(['id'=>$id])->save(['is_report'=>1]);
			}
		}else{
			$rs3 = true;
		}
		if($rs && $rs2 && $rs3){
			$this->success("提交成功！");
		}else{
			$this->error("提交失败！");
		}
	}

    public function forbidAll()
    {
        $model =  M('comment');

        // 更新数据
        $list = $model->where(['id' => ['IN', I('id')]])->save(['status' => 0]);
        if (false !== $list) {
            //成功提示
            $this->success('屏蔽成功!',cookie('_currentUrl_'));
        } else {
            //错误提示
            $this->error('屏蔽失败!');
        }
    }

}