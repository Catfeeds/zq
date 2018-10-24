<?php

/**
 * 竞彩赛事对阵列表控制器
 *
 */
class FbBetScheduleController extends CommonController
{
    public $gameType;

    /**
     *构造函数
     *
     * @return  #
     */
    public function _initialize()
    {
        parent::_initialize();
        $gameType = $_REQUEST['gameType'];
        $this->gameType = $gameType;
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

        $map['is_sporttery'] = 1;
        if (I('game_id'))
            $map['game_id'] = (int)I('game_id');

        //赛事名
        if (I('union_name'))
            $map['union_name'] = new mongoRegex("/" . I('union_name') . ".*/");

        //主队名
        if (I('home_team_name'))
            $map['home_team_name'] = new mongoRegex("/" . I('home_team_name') . ".*/");

        //客队名
        if (I('away_team_name'))
            $map['away_team_name'] = new mongoRegex("/" . I('away_team_name') . ".*/");

        //获得时间
        if (!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])) {
            if (!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])) {
                $startTime = strtotime($_REQUEST ['startTime']) - 30;
                $endTime = strtotime($_REQUEST ['endTime']) + 30;
                $map['game_start_timestamp'] = [$mongo->cmd('>') => $startTime, $mongo->cmd('<') => $endTime];
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']) - 30;
                $map['game_start_timestamp'] = [$mongo->cmd('>') => $strtotime];
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime']) + 30;
                $map['game_start_timestamp'] = [$mongo->cmd('<') => $endTime];
            }
        }

        //默认只查询一周
        if (!$map['game_start_timestamp']) {
            $map['game_start_timestamp'] = ['$gt' => time() - 3600 * 24 * 8];
        }

        $countList = $mongo->count('fb_game', $map);


        //比赛列表中是竞彩的
        $skip = ($page - 1) * $pageNum;

        $games = $mongo->select('fb_game', $map,
            ['game_id', 'union_name', 'spottery_num', 'is_sporttery', 'game_starttime',
                'game_start_timestamp', 'game_state', 'home_team_name', 'away_team_name', 'score'
            ], [], $pageNum, $skip);

        //竞彩表获取盘口赔率
        $game_ids = array_column($games, 'game_id');
        if ($game_ids) {
            $_sporttery = $mongo->select('fb_sporttery',
                ['game_id' => ['$in' => array_unique($game_ids)]],
                ['game_id', 'had', 'hhad']);
            foreach ($_sporttery as $sk => $sv) {

                $sporttery[$sv['game_id']] = [
                    'brq' => [(int)$sv['had']['fixedodds'], $sv['had']['a'], $sv['had']['d'], $sv['had']['h']],
                    'rq' => [(int)$sv['hhad']['fixedodds'], $sv['hhad']['a'], $sv['hhad']['d'], $sv['hhad']['h']]
                ];
            }
        }

        $list = [];
        foreach ($games as $k => $v) {
            $list[$k]['game_id'] = $v['game_id'];
            $list[$k]['game_state'] = $v['game_state'];
            $list[$k]['union_name'] = $v['union_name'][0];
            $list[$k]['home_team_name'] = $v['home_team_name'][0];
            $list[$k]['away_team_name'] = $v['away_team_name'][0];
            $list[$k]['score'] = $v['game_state'] !== 0 ? $v['score'] : '-';
            $list[$k]['spottery_num'] = $v['spottery_num'];
            $list[$k]['oddsArr'] = $sporttery[$v['game_id']] ?: [];
            $list[$k]['game_time'] = $v['game_start_timestamp'] ? date('Y-m-d H:i:s', $v['game_start_timestamp']) : $v['game_starttime'];

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

    public function _index()
    {
        $map = $this->_search('GameFbinfo');
        //时间查询
        if (!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])) {
            if (!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])) {
                $startTime = strtotime($_REQUEST ['startTime']);
                $endTime = strtotime($_REQUEST ['endTime']) + 86400;
                $map['gtime'] = array('BETWEEN', array($startTime, $endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']);
                $map['gtime'] = array('EGT', $strtotime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime']) + 86400;
                $map['gtime'] = array('ELT', $endTime);
            }
        }
        //竞彩
        $map['is_betting'] = 1;

        $code = I('code');
        if (!empty($code)) {
            switch ($code) {
                case 1:
                    $map['bet_code'] = ['like', '%周一%'];
                    break;
                case 2:
                    $map['bet_code'] = ['like', '%周二%'];
                    break;
                case 3:
                    $map['bet_code'] = ['like', '%周三%'];
                    break;
                case 4:
                    $map['bet_code'] = ['like', '%周四%'];
                    break;
                case 5:
                    $map['bet_code'] = ['like', '%周五%'];
                    break;
                case 6:
                    $map['bet_code'] = ['like', '%周六%'];
                    break;
                case 7:
                    $map['bet_code'] = ['like', '%周日%'];
                    break;
            }

        }
        $list = $this->_list(D('BetoddsAgainstView'), $map, 'let_exp');
        $list = HandleGamble($list);
        $this->assign('list', $list);
        $this->display();
    }

}