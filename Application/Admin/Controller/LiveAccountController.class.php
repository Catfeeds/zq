 <?php
/**
 * 主播礼物打赏记录控制器
 * @author dengwj <406516482@qq.com>
 * @since  2018-08-10
 */
class LiveAccountController extends CommonController {
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
        $model = M('LiveAccount');
        $map = $this->_search('LiveAccount');
        //排序字段 默认为主键名
        $order = $_REQUEST ['_order'] ? : 'id';
        //排序方式默认按照倒序排列
        $sort = $_REQUEST ['_sort'] == 'asc' ? 'asc' : 'desc';
        $title = I('title');
        if ($title != '')
        {
            $map['log.title'] = ['like',$title."%"];
        }

        $cover_name = I('cover_name');
        if ($cover_name != '')
        {
            $map['cover.nick_name'] = ['like',$cover_name."%"];
        }
        $user_name = I('user_name');
        if ($user_name != '')
        {
            $map['user.nick_name'] = ['like',$user_name."%"];
        }

        //取得满足条件的记录数
        $count = $model->alias('l')
        ->join('LEFT JOIN qc_live_log log on log.id=l.log_id')
        ->join('LEFT JOIN qc_front_user user on user.id=l.user_id')
        ->join('LEFT JOIN qc_front_user cover on cover.id=l.cover_id')
        ->where($map)->count();
        //echo $model->_sql();
        if ($count > 0)
        {
            $pageNum     = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
            $currentPage = !empty($_REQUEST[C('VAR_PAGE')]) ? $_REQUEST[C('VAR_PAGE')]:1;
            //分页查询数据
            $list = $model
                ->alias('l')
                ->join('LEFT JOIN qc_live_log log on log.id=l.log_id')
                ->join('LEFT JOIN qc_front_user user on user.id=l.user_id')
                ->join('LEFT JOIN qc_front_user cover on cover.id=l.cover_id')
                ->field("l.*,log.title,user.nick_name as user_name,cover.nick_name as cover_name")
                ->where($map)
                ->group('l.id')
                ->order( $order." ".$sort )
                ->page($currentPage,$pageNum)
                ->select();
            //dump($list);
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
        $vo = M("LiveAccount")->alias('l')
                ->join('LEFT JOIN qc_front_user u on u.id=l.user_id')
                ->field("l.*,u.username,u.nick_name,u.head,descript")->where(['l.id'=>$id])->find();
        $vo['img'] = imagesReplace($vo['img']);
        if (!$vo){
            $this->error('参数错误');
        }

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
        if(empty($id)){
            $this->error('参数错误!');
        }
        $model = D('LiveAccount');
        if (!$data = $model->create()) {
            $this->error($model->getError());
        }

        $rs = $model->save($data);

        //是否有上传
        if (!empty($_FILES['fileInput']['tmp_name'])) {
            //先删除原来图片
            $fileArr = array(
                "/liveimg/{$id}.jpg",
                "/liveimg/{$id}.gif",
                "/liveimg/{$id}.png",
                "/liveimg/{$id}.swf",
            );
            D('Uploads')->deleteFile($fileArr);
            //上传图片
            $return = D('Uploads')->uploadImg("fileInput", "liveimg", $id,'',"[[400,400,{$id}]]");
            //修改路径
            if($return['status'] == 1)
                M("LiveAccount")->where(['id'=>$id])->save(['img'=>$return['url']]);
        }

        if (false !== $rs) {
            //成功提示
            $this->success('保存成功!',cookie('_currentUrl_'));
        } else {
            //错误提示
            $this->error('保存失败!');
        }
    }
}