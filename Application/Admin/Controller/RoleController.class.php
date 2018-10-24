<?php
// 角色模块
class RoleController extends CommonController {
    public function index()
    {
        $map = $this->_search('Role');
        $list = $this->_list(D('Role'));
        $role_id = array_map("array_shift", $list);
        $role_user = M('role_user r')->field('r.role_id,r.user_id,u.account,u.nickname')->join("LEFT JOIN qc_user u on u.id = r.user_id")->where(['role_id'=>['in',$role_id]])->select();
        foreach ($list as $k => $v) {
            foreach ($role_user as $kk => $vv) {
                if($v['id'] == $vv['role_id']){
                    $list[$k]['user'][] = $vv;
                }
            }
        }
        //dump($list);
        $this->assign('list',$list);
        $this->display();
    }

	function _filter(&$map){
		$map['name'] = array('like',"%".$_POST['name']."%");
	}
     /**
     +----------------------------------------------------------
     * 增加组操作权限
     *
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     * @throws FcsException
     +----------------------------------------------------------
     */
    public function setApp()
    {
        $id     = $_POST['groupAppId'];
		$groupId	=	$_POST['groupId'];
		$group    =   D('Role');
		$group->delGroupApp($groupId);
		$result = $group->setGroupApps($groupId,$id);

		if($result===false) {
			$this->error('项目授权失败！');
		}else {
			$this->success('项目授权成功！');
		}
    }


    /**
     +----------------------------------------------------------
     * 组操作权限列表
     *
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     * @throws FcsException
     +----------------------------------------------------------
     */
    public function app()
    {
        //读取系统的项目列表
        $node    =  D("Node");
        $list	=	$node->where('level=1')->field('id,title')->select();
		foreach ($list as $vo){
			$appList[$vo['id']]	=	$vo['title'];
		}

        //读取系统组列表
		$group   =  D('Role');
        $list       =  $group->field('id,name')->select();
		foreach ($list as $vo){
			$groupList[$vo['id']]	=	$vo['name'];
		}
		$this->assign("groupList",$groupList);

        //获取当前用户组项目权限信息
        $groupId =  isset($_GET['groupId'])?$_GET['groupId']:'';
		$groupAppList = array();
		if(!empty($groupId)) {
			$this->assign("selectGroupId",$groupId);
			//获取当前组的操作权限列表
            $list	=	$group->getGroupAppList($groupId);
			foreach ($list as $vo){
				$groupAppList[$vo['id']]	=	$vo['id'];
			}
		}
		$this->assign('groupAppList',$groupAppList);
        $this->assign('appList',$appList);
        $this->display();

        return;
    }

     /**
     +----------------------------------------------------------
     * 增加组操作权限
     *
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     * @throws FcsException
     +----------------------------------------------------------
     */
    public function setModule()
    {
        $id     = $_POST['groupModuleId'];
		$groupId	=	$_POST['groupId'];
        $appId	=	$_POST['appId'];
		$group    =   D("Role");
		$group->delGroupModule($groupId,$appId);
		$result = $group->setGroupModules($groupId,$id);

		if($result===false) {
			$this->error('模块授权失败！');
		}else {
			$this->success('模块授权成功！');
		}
    }


    /**
     +----------------------------------------------------------
     * 组操作权限列表
     *
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     * @throws FcsException
     +----------------------------------------------------------
     */
    public function module()
    {
        $groupId =  $_GET['groupId'];
        $appId  = $_GET['appId'];

		$group   =  D("Role");
        //读取系统组列表
        $list=$group->field('id,name')->select();
		foreach ($list as $vo){
			$groupList[$vo['id']]	=	$vo['name'];
		}
		$this->assign("groupList",$groupList);

        if(!empty($groupId)) {
			$this->assign("selectGroupId",$groupId);
            //读取系统组的授权项目列表
            $list	=	$group->getGroupAppList($groupId);
			foreach ($list as $vo){
				$appList[$vo['id']]	=	$vo['title'];
			}
            $this->assign("appList",$appList);
        }
        $node    =  D("Node");
        if(!empty($appId)) {
            $this->assign("selectAppId",$appId);
        	//读取当前项目的模块列表
			$where['level']=2;
			$where['pid']=$appId;
            $nodelist=$node->field('id,title')->where($where)->select();
			foreach ($nodelist as $vo){
				$moduleList[$vo['id']]	=	$vo['title'];
			}
        }

        //获取当前项目的授权模块信息
		$groupModuleList = array();
		if(!empty($groupId) && !empty($appId)) {
            $grouplist	=	$group->getGroupModuleList($groupId,$appId);
			foreach ($grouplist as $vo){
				$groupModuleList[$vo['id']]	=	$vo['id'];
			}
		}

		$this->assign('groupModuleList',$groupModuleList);
        $this->assign('moduleList',$moduleList);

        $this->display();

        return;
    }

     /**
     +----------------------------------------------------------
     * 增加组操作权限
     *
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     * @throws FcsException
     +----------------------------------------------------------
     */
    public function setAction()
    {
        $id     = $_POST['groupActionId'];
		$groupId	=	$_POST['groupId'];
        $moduleId	=	$_POST['moduleId'];
		$group    =   D("Role");
		$group->delGroupAction($groupId,$moduleId);
		$result = $group->setGroupActions($groupId,$id);

		if($result===false) {
			$this->error('操作授权失败！');
		}else {
			$this->success('操作授权成功！');
		}
    }


    /**
     +----------------------------------------------------------
     * 组操作权限列表
     *
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     * @throws FcsException
     +----------------------------------------------------------
     */
    public function action()
    {
        $groupId =  $_GET['groupId'];
        $appId  = $_GET['appId'];
        $moduleId  = $_GET['moduleId'];

		$group   =  D("Role");
        //读取系统组列表
        $grouplist=$group->field('id,name')->select();
		foreach ($grouplist as $vo){
			$groupList[$vo['id']]	=	$vo['name'];
		}
		$this->assign("groupList",$groupList);

        if(!empty($groupId)) {
			$this->assign("selectGroupId",$groupId);
            //读取系统组的授权项目列表
            $list	=	$group->getGroupAppList($groupId);
			foreach ($list as $vo){
				$appList[$vo['id']]	=	$vo['title'];
			}
            $this->assign("appList",$appList);
        }
        if(!empty($appId)) {
            $this->assign("selectAppId",$appId);
        	//读取当前项目的授权模块列表
            $list	=	$group->getGroupModuleList($groupId,$appId);
			foreach ($list as $vo){
				$moduleList[$vo['id']]	=	$vo['title'];
			}
            $this->assign("moduleList",$moduleList);
        }
        $node    =  D("Node");

        if(!empty($moduleId)) {
            $this->assign("selectModuleId",$moduleId);
        	//读取当前项目的操作列表
			$map['level']=3;
			$map['pid']=$moduleId;
            $list	=	$node->where($map)->field('id,title')->select();
			if($list) {
				foreach ($list as $vo){
					$actionList[$vo['id']]	=	$vo['title'];
				}
			}
        }


        //获取当前用户组操作权限信息
		$groupActionList = array();
		if(!empty($groupId) && !empty($moduleId)) {
			//获取当前组的操作权限列表
            $list	=	$group->getGroupActionList($groupId,$moduleId);
			if($list) {
			foreach ($list as $vo){
				$groupActionList[$vo['id']]	=	$vo['id'];
			}
			}

		}

		$this->assign('groupActionList',$groupActionList);
		//$actionList = array_diff_key($actionList,$groupActionList);
        $this->assign('actionList',$actionList);

        $this->display();

        return;
    }

    /**
     +----------------------------------------------------------
     * 增加组操作权限
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     * @throws FcsException
     +----------------------------------------------------------
     */
    public function setUser()
    {
        $id     = $_POST['groupUserId'];
		$groupId	=	$_POST['groupId'];
		$group    =   D("Role");
		$group->delGroupUser($groupId,$id);
		$result = $group->setGroupUsers($groupId,$id);
		if($result===false) {
			$this->error('授权失败！');
		}else {
			$this->success('授权成功！');
		}
    }

    /**
     +----------------------------------------------------------
     * 组操作权限列表
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     * @throws FcsException
     +----------------------------------------------------------
     */
    public function user()
    {
        //读取系统的用户列表
        $user    =   D("User");
		$list2=$user->field('id,account,nickname')->select();
		//echo $user->getlastsql();
		//dump(	$user);
		foreach ($list2 as $vo){
			$userList[$vo['id']]	=	$vo['account'].' '.$vo['nickname'];
		}

		$group    =   D("Role");
        $list=$group->field('id,name')->select();
		foreach ($list as $vo){
			$groupList[$vo['id']]	=	$vo['name'];
		}
		$this->assign("groupList",$groupList);

        //获取当前用户组信息
        $groupId =  isset($_GET['id'])?$_GET['id']:'';
		$groupUserList = array();
		if(!empty($groupId)) {
			$this->assign("selectGroupId",$groupId);
			//获取当前组的用户列表
            $list	=	$group->getGroupUserList($groupId);
			foreach ($list as $vo){
				$groupUserList[$vo['id']]	=	$vo['id'];
			}

		}
		$this->assign('groupUserList',$groupUserList);
        $this->assign('userList',$userList);
        $this->display();

        return;
    }
	public function _before_edit(){
	   $Group = D('Role');
        //查找满足条件的列表数据
        $list     = $Group->field('id,name')->select();
        $this->assign('list',$list);

	}
	public function _before_add(){
	   $Group = D('Role');
        //查找满足条件的列表数据
        $list     = $Group->field('id,name')->select();
        $this->assign('list',$list);

	}
    public function select()
    {
        $map = $this->_search();
        //创建数据对象
        $Group = D('Role');
        //查找满足条件的列表数据
        $list     = $Group->field('id,name')->select();
        $this->assign('list',$list);
        $this->display();
        return;
    }

    //获取节点列表
    public function addAccess(){
        //导航
        $group_class = M('group_class')->field('menu,name')->where('status=1')->order('sort asc')->select();
        $classArr = array_map("array_shift", $group_class);
        //分组
        $group = M('group')->field('id,group_menu,name,title')->where(['group_menu'=>['in',$classArr],'status'=>1])->order('sort asc')->select();
        $groupArr = array_map("array_shift", $group);
        //节点
        $node = M('node')->field('id,name,title,group_id')->where(['group_id'=>['in',$groupArr],'status'=>1,'pid'=>1])->order('sort asc')->select();
        //匹配对应节点
        foreach ($group as $k => $v) {
            foreach ($node as $kk => $vv) {
                if($v['id'] == $vv['group_id']){
                    $group[$k]['node'][] = $vv;
                }
            }
        }
        //匹配对应分组
        foreach ($group_class as $k => $v) {
            foreach ($group as $kk => $vv) {
                if($v['menu'] == $vv['group_menu']){
                    $group_class[$k]['group'][] = $vv;
                }
            }
        }
        //dump($group_class);
        $this->assign('group_class',$group_class);
        // $node=M("Node")->where("pid=1")->select();
        // $nodeData=list_to_tree($node);
        // $this->node=$nodeData;
        //已有节点权限
        $groupId=$_GET['groupId'];
        $selectdNode=M("Access")->where(array('role_id'=>"{$groupId}",'level'=>2))->getField('node_id',true);
        // $array=array();
        // foreach($Access as $val){
        //     $array[$val['level']][]=$val['node_id'];
        // }
        // dump($Access);
        //dump($selectdNode);
        $this->assign('selectdNode',$selectdNode);
        $this->display();
    }


    public function insertAccess(){
        $role_id = I('role_id');
        $node_name = I('node_name');
        if(empty($node_name)){
            $this->error('授权失败,请选择至少一个权限。');
        }
        $data=$dataNode=array();
        M("Access")->where(array('role_id'=>"$role_id"))->delete();
        foreach($node_name as $v){
            $dataNode['role_id']=$role_id;
            $dataNode['node_id']=$v;
            $dataNode['level']  =2;
            $dataNode['pid']    =1;
            $data[]=$dataNode;
        }
        $data[] = ['role_id'=>$role_id,'node_id'=>40,'level'=>2,'pid'=>1];
        $data[] = ['role_id'=>$role_id,'node_id'=>1,'level'=>1,'pid'=>0];
        $accNum=M("Access")->addAll($data);
        if($accNum){
            $this->success('授权成功。');
        }else{
            $this->error('授权失败。');
        }
    }
}
?>