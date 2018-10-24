<?php
/**
 * 消息列表管理
 *
 * @author dengweijun <406516482@qq.com>
 *
 * @since  2015-12-4
 */
class MsgController extends CommonController{
	function _filter(&$map){

	$username_nickname = trim(I('username_nickname'));
	if(!empty($username_nickname))
	{
		$userWhere['username']  = ['like','%'.$username_nickname.'%'];
		$userWhere['nick_name'] = ['like','%'.$username_nickname.'%'];
		$userWhere['_logic'] = 'or';
		$userIdRes = M('FrontUser')->where($userWhere)->getField('id',true);
		! empty($userIdRes) ? $map['front_user_id'] = ['IN',$userIdRes] : $map['front_user_id'] = '';
		unset($userIdRes);
	}
    }
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
     * 添加消息列表记录
	 *
     * @return #
    */
    public function add(){
		if(IS_POST){
			//获取发送消息数据
			$msgData['user_id'] = I('user_id');
			$msgData['title']    = I('title');
			$msgData['content']  = I('content');
			$msgData['send_time']  = time();
			$model = D('Msg');
			//获取每一个接收用户的id
			$FrontUserId = explode(',',I("FrontUser_id"));
			//开始事务
			$model->startTrans();
			foreach ($FrontUserId as $key => $value) {
				$msgData['front_user_id'] = $value;
				$rs = $model->add($msgData);
				if($rs){
					$opt['clientid'] = $value . rand(0, 1000).time();
					$opt['topic'] = 'qqty/api500/' . $value . '/system_notify';
					$opt['payload'] = ['status' => 1, 'data' => ['newMsg' => 1], 'randKey' => $key.rand(0, 1000)];
					$opt['qos'] = 1;
					Mqtt($opt);
				}
			}
			if($rs){
				$model->commit();
				$this->success("发送成功");
			}else{
				$model->rollback();
				$this->error("发送失败");
			}
		}else{
			$this->display();
		}
	}
	/**
     * 内容详情
	 *
     * @return string
	 *
    */
	public function contentDetail() {
		$id = I("id");
		$vo = M('Msg')->where(array('id'=>$id))->field("title,content")->find();
		$this->assign('vo',$vo);
		$this->display();
	}

    /**
     *新屏蔽词预览
     */
	public function inputPreView(){
	    if(I('newInput')){
            $this->ajaxReturn(['newInput' => preg_quote(I('newInput'))]);
        }

        $this->ajaxReturn([]);
    }

    /**
     *屏蔽词测试
     */
    public function inputTest(){
        $keyArrs = getWebConfig('FilterWords');
        if(!trim(I('inputTest')))
            $this->success('请输入内容');

        if(preg_match('/(' . implode('|', array_filter($keyArrs)) . ')/',I('inputTest'), $match)){
            $this->error('检测到包含有敏感词：'.$match[1]);
        }

        $this->success('没有检测到敏感词');

    }

    /**
     * 获取设置屏蔽词
     */
	public function FilterWords(){
	    $filterArrs = array_unique(array_filter(getWebConfig('FilterWords')));
	    $words = implode("|", $filterArrs);
	    $this->assign('words',$words);
	    $this->display();
	}

	public function saveFilterWords(){
		if (IS_POST)
		{
			$words = trim(I('words'), '|');
		    $FilterWords = json_encode(array_unique(explode("|", $words)));
		   	$is_add = M('config')->where(['sign'=>'FilterWords'])->getField('id');
		   	if($is_add){
		   		//修改
		   		$rs = M('config')->where(['sign'=>'FilterWords'])->save(['config'=>$FilterWords]);
		   		if(!is_bool($rs)){
		   			$rs = true;
		   		}
		   	}else{
		   		//新增
		   		$config['sign'] = 'FilterWords';
		   		$config['config'] = $FilterWords;
		   		$rs = M('config')->add($config);
		   	}

		    if ($rs)
		        $this->success('保存成功');

		    $this->error('保存失败!');
		}
	}

	//获取设置昵称屏蔽词
	public function nickFilter(){
		$words=implode("|", getWebConfig('nickFilter'));
		$this->assign('words',$words);
		$this->display();
	}

	public function saveNickFilter(){
		if (IS_POST)
		{
			$words = trim(I('words'), '|');

			$FilterWords = json_encode(array_unique(explode("|", $words)));

			$is_add = M('config')->where(['sign'=>'nickFilter'])->getField('id');

			if($is_add){
				//修改
				$rs = M('config')->where(['sign'=>'nickFilter'])->save(['config'=>$FilterWords]);
				if(!is_bool($rs)){
					$rs = true;
				}
			}else{
				//新增
				$config['sign'] = 'nickFilter';
				$config['config'] = $FilterWords;
				$rs = M('config')->add($config);
			}

			if ($rs)
				$this->success('保存成功');

			$this->error('保存失败!');
		}
	}
}
?>