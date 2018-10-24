<?php
class NodeController extends CommonController {
    public function index()
    {
        //分组
        $group = M('group')->field('id,group_menu,name,title')->where(['status'=>1])->order('sort asc')->select();
        $this->assign('groupArr',$group);

        //分钟筛选
        $group_id = I('group_id');
        if($group_id != ''){
            foreach ($group as $k => $v) {
                if($group_id != $v['id']){
                    unset($group[$k]);
                }
            }
        }
        
        //节点
        $groupArr = array_map("array_shift", $group);

        //节点筛选
        $node_name = I('node_name');
        $node_where = ['group_id'=>['in',$groupArr],'pid'=>1];
        if($node_name != ''){
            $node_where['title'] = ['like','%'.$node_name.'%'];
        }
        
        $node = M('node')->field('id,name,title,sort,status,remark,group_id')->where($node_where)->order('sort asc')->select();

        //匹配对应节点
        foreach ($group as $k => $v) {
            foreach ($node as $kk => $vv) {
                if($v['id'] == $vv['group_id']){
                    $group[$k]['node'][] = $vv;
                }
            }
        }
        
        $this->assign('list',$group);
        $this->assign('groupNum',count($group));
        $this->assign('nodeNum',count($node));
        $this->setJumpUrl();
        $this->display();
    }

	public function _filter(&$map)
	{
        if(!empty($_GET['group_id'])) {
            $map['group_id'] =  $_GET['group_id'];
            $this->assign('nodeName','分组');
        }elseif(empty($_POST['search']) && !isset($map['pid']) ) {
			$map['pid']	=	0;
		}
		if($_GET['pid']!=''){
			$map['pid']=$_GET['pid'];
		}
		$_SESSION['currentNodeId']	=	$map['pid'];
		//获取上级节点
		$node  = M("Node");
        if(isset($map['pid'])) {
            if($node->getById($map['pid'])) {
                $this->assign('level',$node->level+1);
                $this->assign('nodeName',$node->name);
            }else {
                $this->assign('level',1);
            }
        }
        $GroupArr = M('Group')->where(['status'=>1])->field('id,title')->select();
        $this->assign('GroupArr',$GroupArr);
	}



	public function _before_index() {
		$model	=	M("Group");
		$list	=	$model->where('status=1')->getField('id,title');
		$this->assign('groupList',$list);
	}

	// 获取配置类型
	public function _before_add() {
		$model	=	M("Group");
		$list	=	$model->where('status=1')->select();
		$this->assign('list',$list);
		$node	=	M("Node");
		$node->getById($_SESSION['currentNodeId']);
        $this->assign('pid',$node->id);
		$this->assign('level',$node->level+1);
	}

    public function _before_patch() {
		$model	=	M("Group");
		$list	=	$model->where('status=1')->select();
		$this->assign('list',$list);
		$node	=	M("Node");
		$node->getById($_SESSION['currentNodeId']);
        $this->assign('pid',$node->id);
		$this->assign('level',$node->level+1);
    }
	public function _before_edit() {
		$model	=	M("Group");
		$list	=	$model->where('status=1')->select();
		$this->assign('list',$list);
	}

    /**
     +----------------------------------------------------------
     * 默认排序操作
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    public function sort()
    {
		$node = M('Node');
        if(!empty($_GET['sortId'])) {
            $map = array();
            $map['status'] = 1;
            $map['id']   = array('in',$_GET['sortId']);
            $sortList   =   $node->where($map)->order('sort asc')->select();
        }else{
            if(!empty($_GET['pid'])) {
                $pid  = $_GET['pid'];
            }else {
                $pid  = $_SESSION['currentNodeId'];
            }
            if($node->getById($pid)) {
                $level   =  $node->level+1;
            }else {
                $level   =  1;
            }
            $this->assign('level',$level);
            $sortList   =   $node->where('status=1 and pid='.$pid.' and level='.$level)->order('sort asc')->select();
        }
        $this->assign("sortList",$sortList);
        $this->display();
        return ;
    }
}
?>