<?php
/**
 * 前台用户中心公共控制器
 * 为防止多分组Controller名称冲突，公共Controller名称统一使用分组名称
 * @author dengweijun <406516482@qq.com>
 * @since  2015-11-27
 */
use Think\Controller;
use Think\Tool\Tool;
class HomeController extends CommonController {

    protected function _initialize(){
    	parent::_initialize();
    	C('HTTP_CACHE_CONTROL','no-cache,no-store');
    	if(!is_login()){
        	$this->error('请先登录!',U('User/login'));
        }
        //获取未读的消息
        $noRead = M("Msg")->where(array('front_user_id'=>is_login(),'is_read'=>0))->count();
        $this->assign('noRead',$noRead);
        $this->assign('liveChatUrl',getLivezillaUrl());
        //判断是否为专家
        $is_expert = M("FrontUser")->field('is_expert,expert_status')->where(['id'=>is_login()])->find();
        $this->assign('is_expert',$is_expert);
        //判断是否为主播
        $is_live = M("LiveUser")->field('id,status')->where(['user_id'=>is_login()])->find();
        if(!$is_live) $is_live['status'] = -1;
        $this->assign('is_live',$is_live);
    }
}