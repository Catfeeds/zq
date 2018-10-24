<?php
ini_set('mongo.long_as_object', 1);

/**
 * 足球、篮球动画赛程管理控制器
 * @since  2018-05-07
 */
class SvgController extends CommonController
{
    protected $gameType;
    protected $mongoGTb;
    protected $mongoSTb;
    protected $svgUrl;

    /**
     *构造函数
     *
     * @return  #
     */
    public function _initialize()
    {
        $this->gameType = I('gameType') ?: 1;
        $this->mongoGTb = $this->gameType == 2 ? 'bk_game_365' : 'fb_game_365'.C('TableSuffix');
        $this->mongoSTb = $this->gameType == 2 ? 'bk_game_schedule' : 'fb_game';
        $this->svgUrl = $this->gameType == 2 ? U("/basketball_animate/basketball_animate@dh") : U("/svg/svg-f-animate@dh");
        parent::_initialize();
    }

    /**
     * Index页显示
     *
     */
    public function index()
    {
        $map = [];
        $page = !empty($_REQUEST[C('VAR_PAGE')]) ? $_REQUEST[C('VAR_PAGE')] : 1;
        $pageNum = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
        $curPage = !empty($_REQUEST[C('VAR_PAGE')]) ? $_REQUEST[C('VAR_PAGE')] : 1;
        $descPage = I('pageNum') ? I('pageNum') - 1 : 0;
        $mongo = mongoService();

        //筛选已关联上的赛事
        if (I('is_link') == 1) {
            $map = ['$or' => [
                ['jbh_id' => ['$exists' => true]],
                ['jb_id' => ['$exists' => true]],
            ]];
        }

        //筛选未关联上的赛事
        if (I('is_link') === '0') {
            $map = ['$and' => [
                ['jbh_id' => ['$exists' => false]],
                ['jb_id' => ['$exists' => false]],
            ]];
        }

        //赛事名
        if (I('union_name'))
            $map['union_name'] = new mongoRegex("/" . I('union_name') . ".*/");

        //主队名
        if (I('home_team_name'))
            $map['home_team_name'] = new mongoRegex("/" . I('home_team_name') . ".*/");

        //客队名
        if (I('away_team_name'))
            $map['away_team_name'] = new mongoRegex("/" . I('away_team_name') . ".*/");

        //赛程id
        if ($gid = (int)I('game_id')) {
            $map = ['$or' => [
                ['jbh_id' => $gid],
                ['jb_id' => $gid],
            ]];
        }

        //动画状态
        if (I('is_crawl') !== '')
            $map['is_crawl'] = (int)I('is_crawl');

        //賽程ID
        if (I('bet_game_id') !== '')
            $map['game_id'] = (string)I('bet_game_id');

        //获得时间
        if (!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])) {
            if (!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])) {
                $startTime = strtotime($_REQUEST ['startTime']) - 30;
                $endTime = strtotime($_REQUEST ['endTime']) + 30;
                $map['game_timestamp'] = [$mongo->cmd('>') => $startTime, $mongo->cmd('<') => $endTime];
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']) - 30;
                $map['game_timestamp'] = [$mongo->cmd('>') => $strtotime];
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime']) + 30;
                $map['game_timestamp'] = [$mongo->cmd('<') => $endTime];
            }
        }

        $countList = $mongo->count($this->mongoGTb, $map);

        //搜索数据
        $skip = ($page - 1) * $pageNum;
        $res = $mongo->select($this->mongoGTb, $map, [], ['game_timestamp' => -1], $pageNum, $skip);

        $list = [];
        //处理表格数据,没数据的为空数据
        foreach ($res as $val) {
            $tmp = [];
            $tmp['id'] = $val['_id'];
            $tmp['game_id_365'] = $val['game_id'];
            $tmp['game_id'] = $val['jbh_id'] ? $val['jbh_id'] : $val['jb_id'];
            if($tmp['game_id'])
            {
                $jb_time = $mongo->select($this->mongoSTb,['game_id'=>$tmp['game_id']],['game_start_timestamp'])[0];
                $tmp['jb_time'] = $jb_time['game_start_timestamp'] ? date('Y-m-d H:i:s', substr($jb_time['game_start_timestamp'], 0, 10)) : '';
            }
            $tmp['game_key'] = $val['game_key'];
            $tmp['union_name'] = $val['union_name'];
            $tmp['game_state'] = $val['status'];
            $tmp['is_crawl'] = $val['is_crawl'];
            $tmp['is_icon'] = $val['is_icon'];
            $tmp['home_team_name'] = $val['home_team_name'];
            $tmp['away_team_name'] = $val['away_team_name'];
            $tmp['game_date'] = $val['game_timestamp'] ? date('Y-m-d H:i:s', substr($val['game_timestamp'], 0, 10)) : '';
            $tmp['update_time'] = $val['update_time'] ? date('Y-m-d H:i:s', substr($val['update_time'], 0, 10)) : '';
//            $tmp['update_time'] = $val['update_time'] ? date('Y-m-d H:i:s', $val['update_time']) : '';
            $tmp['is_link'] = $tmp['game_id'] ? 1 : 0;
            $tmp['svg_url'] = $this->svgUrl . '?game_id=' . ($this->gameType == 2 ? $val['game_id'] : $tmp['game_id']);
            $list[] = $tmp;
        }

        //处理分页
        $this->setJumpUrl();
        $this->assign('totalCount', $countList);
        $this->assign('numPerPage', $pageNum); //每页显示多少条
        $this->assign('currentPage', $curPage);//当前页码
        $this->assign('desc_pag', $descPage);//用来页面序号的记录
        $this->assign('numPerPage', $pageNum);
        $this->assign('gameType', $this->gameType);
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 关联页面
     */
    public function doLink()
    {
        $game_id = I('id');
        //动画表数据
        $vo = mongo($this->mongoGTb)->where(['game_id' => (string)$game_id])->find();
        $bind_id = $vo['jbh_id'] ? $vo['jbh_id'] : $vo['jb_id'];
        $vo['bind_id'] = $bind_id;

        if (!$vo)
            $this->error("参数错误！");

        $game = mongo($this->mongoSTb)
            ->field(['game_id','red_card','union_name','home_team_name','away_team_name','game_start_timestamp','game_state', 'gtime'])
            ->where(['game_id' => $bind_id])
            ->find();
        $is_swap = mongo($this->mongoSTb)
            ->field(['is_swap'])
            ->where(['game_id' => $bind_id])
            ->find();
        $this->assign('is_swap',$is_swap['is_swap']);

        //获取相关数据
        $moreGame = $this->closeGame($vo);
        $this->assign('moreGame',$moreGame);

        $game['game_timestamp'] = $game['game_start_timestamp'] ? date('Y-m-d H:i:s', $game['game_start_timestamp']) : $game['gtime'];

        $this->assign('game', $game);
        $this->assign('vo', $vo);
        $this->assign('gameType', $this->gameType);
        $this->display();
    }

    //获取相近赛事数据
    public function closeGame($vo)
    {
        $mongo = mongoService();
        $startTime = $vo['game_timestamp'] - 120*60;
        $endTime = $vo['game_timestamp'] + 120*60;
        $map['game_start_timestamp'] = [$mongo->cmd('>') => $startTime, $mongo->cmd('<') => $endTime];
//        $map['home_team_name'] = ['Like',$vo['home_team_name'].'%'];
//        $map['away_team_name'] = ['Like',$vo['away_team_name'].'%'];
        $data = $mongo->select($this->mongoSTb, $map, ['game_id','union_name','home_team_name','away_team_name','game_start_timestamp','game_state'], ['game_start_timestamp' => 1]);
        return $data;
    }

    /**
     * 执行关联操作
     */
    public function save()
    {
        $bind_id = (int)I('bind_id');//需要绑定的赛事id
        $game_id = (string)I('game_id');//篮球动画绑定表
        $is_swap = (int)I('is_swap');//是否主客隊對調
        $game = mongo($this->mongoSTb)->field('game_id')->where(['game_id' => $bind_id])->find();
        if (!$game) {
            $this->error('查询不到关联赛程');
        }
        mongoService()->update($this->mongoGTb, ['jbh_id' => $bind_id,'is_icon' => 1], ['game_id' => $game_id]);
        mongoService()->update($this->mongoSTb, ['is_swap' => $is_swap], ['game_id' => $bind_id]);
        //修改球队关联表
        $this->updateTeam($bind_id,$game_id);
        $this->success("关联成功！");
    }

    /**
     * @param $jbid 需要绑定的赛事id(捷报)
     * @param $bet 365赛事id
     */
    public function updateTeam($jbid,$bet)
    {
        //获取捷报赛事数据
        $mongo = mongoService();
        $jiebao = $mongo->select($this->mongoSTb,['game_id'=>$jbid],['home_team_id','home_team_name','away_team_id','away_team_name'])[0];
        $betData = $mongo->select($this->mongoGTb,['game_id'=>$bet],['home_team_name','away_team_name'])[0];
        if($jiebao && $betData)
        {
            //主队
            $home['jb_team_name'] = $jiebao['home_team_name'];
            $home['jb_team_id'] = (int)$jiebao['home_team_id'];
            $home['bet_team_name'] = $betData['home_team_name'];
            $_id = $mongo->select('fb_365_jb_team_match', ['jb_team_id'=>$home['jb_team_id']],['_id']);
            if($_id) $home['_id'] = $_id[0]['_id'];
            $res = $mongo->save('fb_365_jb_team_match', $home,true);

            //客队
            $away['jb_team_name'] = $jiebao['away_team_name'];
            $away['jb_team_id'] = (int)$jiebao['away_team_id'];
            $away['bet_team_name'] = $betData['away_team_name'];
            $_id = $mongo->select('fb_365_jb_team_match', ['jb_team_id'=>$away['jb_team_id']],['_id']);
            if($_id) $away['_id'] = $_id[0]['_id'];
            $res = $mongo->save('fb_365_jb_team_match', $away,true);
        }
    }

}