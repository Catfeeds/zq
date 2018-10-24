<?php

// 后台用户模块
class UserController extends CommonController {
    function _filter(&$map){
        $map['id'] = array('egt',2);
        if(!empty($_POST['account'])) {
            $map['account'] = array('like',"%".$_POST['account']."%");
        }
    }


    // 检查帐号
    public function checkAccount() {
        if(!preg_match('/^[a-z]\w{4,}$/i',$_POST['account'])) {
            $this->error( '用户名必须是字母，且5位以上！');
        }
        $User = M("User");
        // 检测用户名是否冲突
        $name  =  $_REQUEST['account'];
        $result  =  $User->getByAccount($name);
        if($result) {
            $this->error('该用户名已经存在！');
        }else {
            $this->success('该用户名可以使用！');
        }
    }

    // 插入数据
    public function insert() {
        // 创建数据对象
        $User	 =	 D("User");
        if(!$User->create()) {
            $this->error($User->getError());
        }else{
            list($hash,$pwdHash) = pwdHash($User->password);
            $User->hash     = $hash;
            $User->password = $pwdHash;
            // 写入帐号数据
            if($result	 =	 $User->add()) {
                $this->addRole($result);
                $this->success('用户添加成功！');
            }else{
                $this->error('用户添加失败！');
            }
        }
    }

    function update($dwz_db_name = '') {
        $dwz_db_name=$dwz_db_name ? $dwz_db_name : $this->getActionName();
        $model = CM($dwz_db_name);
        if (!$data = $model->create()) {
            $this->error($model->getError());
        }
        $data['is_search_user'] = I('is_search_user','0');
        $data['is_show_user']   = I('is_show_user','0');
        $data['is_save_user']   = I('is_save_user','0');
        $data['is_show_pay']    = I('is_show_pay','0');
        $data['is_show_mobile'] = I('is_show_mobile','0');
        $data['is_show_answer'] = I('is_show_answer','0');
        $data['is_show_count']  = I('is_show_count','0');
        $data['is_show_index']  = I('is_show_index','0');

        // 更新数据
        $list = $model->save($data);
        if (false !== $list) {
            //成功提示
            $this->success('编辑成功!',cookie('_currentUrl_'));
        } else {
            //错误提示
            $this->error('编辑失败!');
        }
    }

    protected function addRole($userId) {
        //新增用户自动加入相应权限组
        $RoleUser = M("RoleUser");
        $RoleUser->user_id	=	$userId;
        // 默认加入网站编辑组
        $RoleUser->role_id	=	3;
        $RoleUser->add();
    }

    //重置密码
    public function resetPwd() {
        $id          = I('id');
        $password    = I('password');
        if(''== trim($password)) {
            $this->error('密码不能为空！');
        }
        $User = M('User');
        list($hash,$pwdHash) = pwdHash($password);
        $User->hash     = $hash;
        $User->password	= $pwdHash;
        $User->id	    = $id;
        $result	=	$User->save();
        if(false !== $result) {
            $this->success("密码修改为$password");
        }else {
            $this->error('重置密码失败！');
        }
    }


}