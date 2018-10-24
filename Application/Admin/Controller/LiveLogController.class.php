 <?php
/**
 * 主播直播记录控制器
 * @author dengwj <406516482@qq.com>
 * @since  2018-08-06
 */
class LiveLogController extends CommonController {
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
        $model = M('LiveLog');
        $map = $this->_search('LiveLog');
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
        $unique_id = I('room_id');
        if ($unique_id != '')
        {
            $map['l.room_id'] = $unique_id;
        }
        $user_id = I('user_id');
        if ($user_id != '')
        {
            unset($map['user_id']);
            $map['l.user_id'] = $user_id;
        }
        //取得满足条件的记录数
        $count = $model->alias('l')
        ->join('LEFT JOIN qc_front_user u on u.id=l.user_id')
        ->join('LEFT JOIN qc_live_user v on v.user_id=l.user_id')
        ->where($map)
            ->count();

        if ($count > 0)
        {
            $pageNum     = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
            $currentPage = !empty($_REQUEST[C('VAR_PAGE')]) ? $_REQUEST[C('VAR_PAGE')]:1;
            //分页查询数据
            $list = $model
                ->alias('l')
                ->join('LEFT JOIN qc_front_user u on u.id=l.user_id')
                ->join('LEFT JOIN qc_live_user v on v.user_id=l.user_id')
                ->field("l.*,v.unique_id,u.username,u.nick_name,u.head,descript")
                ->where($map)
                ->group('l.id')
                ->order( $order." ".$sort )
                ->page($currentPage,$pageNum)
                ->select();

            foreach ($list as $k => $v) {
                $list[$k]['img'] = imagesReplace($v['img']);
                if($v['game_id'] == '' || $v['live_status'] == 0){
                    $list[$k]['room_url'] = U('/liveRoom/'.$v['room_id']);
                }else{
                    $list[$k]['room_url'] = U('/live/'.$v['game_id'].'@bf') . "?is_live=1";
                }
                $list[$k]['link_game'] = $v['game_id'] == '' ? '0' : '1';
                $noticeLogId[] = $v['id'];
            }

			//是否有文字广播
            $LiveNotice = M('LiveNotice')->where(['log_id'=>['in',$noticeLogId]])->group('log_id')->getField('log_id',true);
            $this->assign('LiveNotice', $LiveNotice);

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
        $vo = M("LiveLog")->alias('l')
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
        $model = D('LiveLog');
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
                M("LiveLog")->where(['id'=>$id])->save(['img'=>$return['url']]);
        }

        if (false !== $rs) {
            //成功提示
            $this->success('保存成功!',cookie('_currentUrl_'));
        } else {
            //错误提示
            $this->error('保存失败!');
        }
    }
    
    //文字广播
    public function LiveNotice()
    {
        $map = $this->_search('LiveNotice');
        $content = I('content');
        if ($content != '')
        {
            $map['content'] = ['like',$content."%"];
        }
        $list = $this->_list(CM('LiveNotice'),$map);
        $this->assign('list',$list);
        $this->display();
    }
}