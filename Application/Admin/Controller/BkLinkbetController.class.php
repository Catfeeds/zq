<?php
ini_set('mongo.long_as_object', 1);
/**
 * 动画赛程管理控制器
 *
 * @author <liuweitao@qqty.com> 2018-05-07
 *
 * @since  2018-05-07
 */
class BkLinkbetController extends CommonController
{
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
     * Index页显示
     *
     */
    public function index()
    {
        //排序字段 默认为主键名
        $order = $_REQUEST ['_order'] ? : 'id';
        //排序方式默认按照倒序排列
        $sort = $_REQUEST ['_sort'] == 'asc' ? 'asc' : 'desc';
        $map = [];
        //分页处理
        $page = !empty($_REQUEST[C('VAR_PAGE')]) ? $_REQUEST[C('VAR_PAGE')] : 1;
        $pageNum = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
        $mongo = mongoService();

        //生成搜索条件

        //筛选已关联上的赛事
        if (I('is_link') == 1) $map = ['$or' => [['jbh_id' => [$mongo->cmd('>') => 0]], ['jb_id' => [$mongo->cmd('>') => 0]]]];
        //筛选未关联上的赛事
        if (I('is_link') === '0') {
            $where = ['$or' => [['jbh_id' => [$mongo->cmd('>') => 0]], ['jb_id' => [$mongo->cmd('>') => 0]]]];
            $inArr = $mongo->select('bk_game_365', $where, ['game_id']);
            $map['game_id'] = [$mongo->cmd('nin') => array_column($inArr, 'game_id')];
        }
        //赛事名
        if (I('game_title')) $map['game_name'] = new mongoRegex("/" . I('game_title') . ".*/");
        //主队名
        if (I('home_team_name')) $map['home_team_name'] = new mongoRegex("/" . I('home_team_name') . ".*/");
        //客队名
        if (I('away_team_name')) $map['away_team_name'] = new mongoRegex("/" . I('away_team_name') . ".*/");
        //赛程id
        if (I('keyWord')) $map['game_id'] = (string)I('keyWord');
        //动画状态
        if (I('flash_status') !== '') $map['flash_status'] = (int)I('flash_status');

//        //获得时间
        if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
            if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime']))
            {
                $startTime = strtotime($_REQUEST ['startTime']);
                $endTime   = strtotime($_REQUEST ['endTime'])+86400;
                $map['game_timestamp'] = [$mongo->cmd('>')=>$startTime, $mongo->cmd('<')=>$endTime];
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']);
                $map['game_timestamp'] = [$mongo->cmd('>')=>$strtotime];
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime']);
                $map['game_timestamp'] = [$mongo->cmd('<')=>$endTime];
            }
        }

        $countList = $mongo->count('bk_game_365', $map);
        $this->assign('totalCount', $countList);

        //搜索数据
        $skip = ($page-1)*$pageNum;
        $res = $mongo->select('bk_game_365',$map,[],['update'=>-1],$pageNum,$skip);
        $list = [];
        //处理表格数据,没数据的为空数据
        foreach ($res as $val) {
            $tmp = [];
            $tmp['id'] = $val['_id'];
            $tmp['game_id'] = $val['game_id'];
            $tmp['game_id_new'] = $val['jbh_id']?:$val['jb_id']?:'';
            $tmp['from_id'] = null;
            $tmp['flash_id'] = null;
            $tmp['game_title'] = $val['game_name'];
            $tmp['game_state'] = null;
            $tmp['home_team_name'] = $val['home_team_name'];
            $tmp['away_team_name'] = $val['away_team_name'];
            $tmp['score'] = null;
            $tmp['gtime'] = $val['game_timestamp'];
            $tmp['game_time'] = null;
            $tmp['game_date'] = date('Ymd',$val['game_timestamp']);
            $tmp['status'] = '';
            $tmp['worker_id'] = '';
            $tmp['update_time'] = $val['update_time'];
            $tmp['is_link'] = $tmp['game_id_new']?1:0;
            $tmp['flash_status'] = $val['flash_status']?$val['flash_status']:'';
            $tmp['md_id'] = null;
            $list[] = $tmp;
        }

        //处理分页
        $pageNum =empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
        $this->assign ( 'numPerPage', $pageNum ); //每页显示多少条
        $this->assign ( 'currentPage', !empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1);//当前页码
        $this->setJumpUrl();
        $this->assign('desc_pag',I('pageNum') ? I('pageNum')-1 : 0 );//用来页面序号的记录
        $this->assign ( 'numPerPage', $pageNum );
        $this->assign('list', $list);
        $this->display();
    }

    public function doLink()
    {
        $game_id = I('id');
        //动画表数据
        $vo = mongo('bk_game_365')->where(['game_id'=>(string)$game_id])->find();
        $bind_id = $vo['jbh_id']?:$vo['jb_id']?:'';
        $vo['bind_id'] = $bind_id;
        if(!$vo){
            $this->error("参数错误！");
        }
        if($bind_id)
            $game = mongo('bk_game_schedule')->field('union_name,home_team_name,away_team_name,game_timestamp')->where(['game_id'=>$bind_id])->find();
        else
            $game = null;

        $this->assign('game',$game);

        $this->assign('vo',$vo);
        $this->display();
    }

    public function save()
    {
        $bind_id = (int)I('bind_id');//需要绑定的赛事id
        $id = (string)I('id');//篮球动画绑定表
        $game = mongo('bk_game_schedule')->field('game_id')->where(['game_id'=>$bind_id])->find();
        if(!$game){
            $this->error('请输入正确的赛程id');
        }
        mongoService()->update('bk_game_365',['jbh_id'=>$bind_id],['game_id'=>$id]);
        $this->success("关联成功！");

    }

}