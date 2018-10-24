 <?php
/**
 * 主播列表控制器
 * @author dengwj <406516482@qq.com>
 * @since  2018-08-04
 */
 use Think\Tool\Tool;
class LiveUserController extends CommonController {
    public function _initialize()
    {
        parent::_initialize();
    }
    /**
     * Index页显示
     *
     */
    public function index()
    {
        $model = M('LiveUser');
        $map = $this->_search('LiveUser');
        //排序字段 默认为主键名
        $order = $_REQUEST ['_order'] ? : 'id';
        //排序方式默认按照倒序排列
        $sort = $_REQUEST ['_sort'] == 'asc' ? 'asc' : 'desc';

        $nick_name = I('nick_name');
        if ($nick_name != '')
        {
            $map['u.nick_name'] = ['like',$nick_name."%"];
        }
        $username = I('username');
        if ($username != '')
        {
            $map['u.username'] = ['like',$username."%"];
        }
        $status = I('status');
        if ($status != '')
        {
            unset($map['status']);
            $map['l.status'] = $status;
        }
        //dump($map);
        //取得满足条件的记录数
        $count = $model->alias('l')->join('LEFT JOIN qc_front_user u on u.id=l.user_id')->where($map)->count();
        //echo $model->_sql();
        if ($count > 0)
        {
            $pageNum     = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
            $currentPage = !empty($_REQUEST[C('VAR_PAGE')]) ? $_REQUEST[C('VAR_PAGE')]:1;
            //分页查询数据
            $list = $model
                ->alias('l')
                ->join('LEFT JOIN qc_front_user u on u.id=l.user_id')
                ->field("l.*,u.username,u.nick_name,u.head,descript")
                ->where($map)
                ->group('l.id')
                ->order( $order." ".$sort )
                ->page($currentPage,$pageNum)
                ->select();
            foreach ($list as $k => $v) {
                $list[$k]['head'] = frontUserFace($v['head']);
                $list[$k]['room_url'] = $v['live_status'] == 1?($v['room_num']) : '';
                $list[$k]['img'] = $v['img'] ? Tool::imagesReplace($v['img']) : ' ';
            }
            //模板赋值显示
            $this->assign('list', $list);
            $this->assign ( 'totalCount', $count );
            $this->assign ( 'numPerPage', $pageNum ); //每页显示多少条
            $this->assign ( 'currentPage', $currentPage);
            $this->setJumpUrl();
        }
        $this->display();
    }

    public function add() {
        $this->display();
    }

    public function edit() {
        $id = I('id');
        $vo = M("LiveUser")->alias('l')
                ->join('LEFT JOIN qc_front_user u on u.id=l.user_id')
                ->field("l.*,u.username,u.nick_name,u.head,descript")->where(['l.id'=>$id])->find();
        if (!$vo){
            $this->error('参数错误');
        }

        $vo['img']  = Tool::imagesReplace($vo['img']);

        $this->assign('vo', $vo);
        $this->display("add");
    }

    /**
     * 保存/修改记录
     *
     * @return #
    */
    public function save(){
        $id = I('id', 'int');

        $model = D('LiveUser');
        if (!$data = $model->create()) {
            $this->error($model->getError());
        }
        //用户id
        $user_id = I('FrontUser_id');
        if(empty($user_id)){
            $this->error('请选择用户!');
        }
        $data['user_id']  = $user_id;
        //房间号码
        $room_num = I('FrontUser_live_uniqueid');

        if(empty($room_num)){
            $this->error('请输入正确的房间号码!');
        }
        $data['unique_id'] = $room_num;
        if (empty($id)) {
            //为新增
            if(M('LiveUser')->where(['user_id'=>$data['user_id']])->find()){
                $this->error('该用户已是主播!');
            }
            if(M('LiveUser')->where(['unique_id'=>$data['unique_id']])->find()){
                $this->error('房间号码存在，请重新输入!');
            }
            $data['add_time'] = time();
            $rs = $model->add($data);
            //上传图标1
            if (! empty($_FILES['fileInput1']['tmp_name'])) {
                $return = D('Uploads')->uploadImg("fileInput1", "LiveUser", $rs);
                if($return['status'] == 1)
                    M("LiveUser")->where(['id' => $rs])->save(['img'=>$return['url']]);
            }

        }else{
            //为修改
            if(M('LiveUser')->where(['user_id'=>$data['user_id'],'id'=>['neq',$id]])->find()){
                $this->error('该用户已是主播!');
            }
            if(M('LiveUser')->where(['unique_id'=>$data['unique_id'],'id'=>['neq',$id]])->find()){
                $this->error('房间号码存在，请重新输入!');
            }
            $rs = $model->save($data);

            //是否有上传图标1
            if (!empty($_FILES['fileInput1']['tmp_name']))
            {
                //先删除原来图片
                $fileArr = array(
                    "/LiveUser/{$id}.jpg",
                    "/LiveUser/{$id}.gif",
                    "/LiveUser/{$id}.png",
                    "/LiveUser/{$id}.swf",
                );
                D('Uploads')->deleteFile($fileArr);
                //上传图片
                $return = D('Uploads')->uploadImg("fileInput1", "LiveUser", $id);
                //修改路径
                if($return['status'] == 1)
                    M("LiveUser")->where(['id'=>$id])->save(['img'=>$return['url']]);
            }
        }

        if (false !== $rs) {
            $descript = I('descript');
            if(!empty($descript)){
                M('FrontUser')->where(['id'=>$user_id])->save(['descript'=>$descript]);
            }
            //成功提示
            $this->success('保存成功!',cookie('_currentUrl_'));
        } else {
            //错误提示
            $this->error('保存失败!');
        }
    }

    //设置是否中断
    public function saveStop(){
        $is_stop = $_REQUEST['is_stop'];
        $id      = $_REQUEST['id'];
        if(empty($id)){
            $this->error('参数错误!');
        }
        $rs = M('LiveUser')->where(['id'=>$id])->save(['is_stop'=>$is_stop]);
        if($rs !== false){
            $this->success('设置成功!',cookie('_currentUrl_'));
        }else{
            $this->error('设置失败!');
        }
    }
}