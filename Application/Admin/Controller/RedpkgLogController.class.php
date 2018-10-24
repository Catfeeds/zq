 <?php
/**
 * 红包记录控制器
 * @author dengwj <406516482@qq.com>
 * @since  2018-08-09
 */
class RedpkgLogController extends CommonController {
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
        $model = M('RedpkgLog');
        $map = $this->_search('RedpkgLog');
        //排序字段 默认为主键名
        $order = $_REQUEST ['_order'] ? : 'id';
        //排序方式默认按照倒序排列
        $sort = $_REQUEST ['_sort'] == 'asc' ? 'asc' : 'desc';
        $title = I('title');
        if ($title != '')
        {
            $map['v.title'] = ['like',$title."%"];
        }
        $nick_name = I('nick_name');
        if ($nick_name != '')
        {
            $map['u.nick_name'] = ['like',$nick_name."%"];
        }
        //取得满足条件的记录数
        $count = $model->alias('l')
        ->join('LEFT JOIN qc_front_user u on u.id=l.user_id')
        ->join('LEFT JOIN qc_redpkg v on v.id=l.pid')
        ->where($map)->count();
        //echo $model->_sql();
        if ($count > 0)
        {
            $pageNum     = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
            $currentPage = !empty($_REQUEST[C('VAR_PAGE')]) ? $_REQUEST[C('VAR_PAGE')]:1;
            //分页查询数据
            $list = $model
                ->alias('l')
                ->join('LEFT JOIN qc_front_user u on u.id=l.user_id')
                ->join('LEFT JOIN qc_redpkg v on v.id=l.pid')
                ->field("l.*,v.title,u.username,u.nick_name")
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
}