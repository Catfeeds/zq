<?php
set_time_limit(0);//0表示不限时
/**
 * 竞猜记录列表控制器
 *
 * @author dengweijun <406516482@qq.com>
 *
 * @since  2015-12-16
 */
use Think\Tool\Tool;
class GambleListController extends CommonController {
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
        $gameType = I('gameType');
        $model = $gameType == 1 ? 'Gamble' : 'Gamblebk';
        $modelView = $gameType == 1 ? 'GambleView' : 'GamblebkView';
        //列表过滤器，生成查询Map对象
        $map = $this->_search($modelView);
        //时间查询
        if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
            if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                $startTime = strtotime($_REQUEST ['startTime']);
                $endTime   = strtotime($_REQUEST ['endTime'])+86400;
                $map['create_time'] = array('BETWEEN',array($startTime,$endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']);
                $map['create_time'] = array('EGT',$strtotime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime'])+86400;
                $map['create_time'] = array('ELT',$endTime);
            }
        }
        $user_id = I('user_id');
        //用户竞猜表
        if($user_id != '') $map['user_id'] = $user_id;

        $rank_gamble = I('rank_gamble');
        if($rank_gamble != ''){
            //亚盘榜单前50
            $y_weekRank = M('rankingList r')
            ->join('LEFT JOIN qc_front_user f on f.id=r.user_id')
            ->field("user_id,ranking,dateType,end_date,'1' as type")
            ->where("r.gameType = 1 and r.ranking <= 100 and (f.is_robot = 1 or f.user_type = 2)")
            ->order("end_date desc, ranking asc")->limit(300)->select();
            $y_date = $y_weekRank[0]['end_date'];
            foreach ($y_weekRank as $k => $v) {
                if($v['end_date'] != $y_date){
                    unset($y_weekRank[$k]);
                }
            }
            //亚盘日榜
            $red_list = M('RedList r')
            ->join('LEFT JOIN qc_front_user f on f.id=r.user_id')
            ->field("user_id,ranking,'4' as dateType,list_date as end_date,'1' as type")
            ->where("r.game_type = 1 and r.ranking <= 100 and (f.is_robot = 1 or f.user_type = 2)")
            ->order("list_date desc, ranking asc")->limit(100)->select();
            $r_date = $red_list[0]['end_date'];
            foreach ($red_list as $k => $v) {
                if($v['end_date'] != $r_date){
                    unset($red_list[$k]);
                }
            }
            $y_weekRank = array_merge($y_weekRank,$red_list);

            //获取唯一榜单 优先规则：日榜->周榜->月榜->季榜
            $y_weekRank = $this->getUniqueRank($y_weekRank);
            $userIdArr = [];
            foreach ($y_weekRank as $k => $v) {
                switch ($rank_gamble) {
                    case '1':
                        if($v['ranking'] <= 20){
                            $userIdArr[] = $v['user_id'];
                        }
                        break;
                    case '2':
                        if($v['ranking'] <= 50){
                            $userIdArr[] = $v['user_id'];
                        }
                        break;
                    case '3':
                        if($v['ranking'] <= 100){
                            $userIdArr[] = $v['user_id'];
                        }
                        break;
                    case '4':
                        if($v['ranking'] > 20 && $v['ranking'] <= 50){
                            $userIdArr[] = $v['user_id'];
                        }
                        break;
                    case '5':
                        if($v['ranking'] > 50 && $v['ranking'] <= 100){
                            $userIdArr[] = $v['user_id'];
                        }
                        break;
                }
            }
            $map['user_id'] = ['in',$userIdArr];
            $blockTime = getBlockTime(1);
            $map['create_time'] = ['between',[$blockTime['beginTime'],$blockTime['endTime']]];
        }
        //dump($map);
        $play_type = I('play_type');
        if($gameType == 1) $map['play_type'] = ['in',[1,-1]];
        if($play_type != '') $map['play_type'] = $play_type;

        $nick_name = I('nick_name');
        if($nick_name != ''){
            $map['nick_name'] = $nick_name;
        }
        //赛事百分比(竞猜赛程统计传过来的)
        $gambleCount = I('gambleCount');
        if(! empty($gambleCount))
        {
            if ($gameType == 1)
            {
                $where = $play_type == 1 || $play_type == -1
                    ?
                    ['game_id'=>I('game_id'),'result'=>['in',[0,1,0.5,2,-1,-0.5]],'play_type'=>$play_type]
                    :
                    ['game_id'=>I('game_id'),'result'=>['in',[0,1,0.5,2,-1,-0.5]]];

                $gamebleResult = M($model)
                            ->field('count(id) as resultCount,result')
                            ->where($where)
                            ->group('result')
                            ->select();
            }
            elseif ($gameType == 2)
            {
                $gamebleResult = M($model)
                            ->field('count(id) as resultCount,result')
                            ->where(['game_id'=>I('game_id'),'result'=>['in',[0,1,-1,2]]])
                            ->where($play_type == 1 || $play_type == -1 ? ['play_type'=>$play_type] : array())
                            ->group('result')
                            ->select();
            }
            $resultArr = array('winCount'=>0,'loseCount'=>0,'flatCount'=>0);
            foreach ($gamebleResult as $k => $v)
            {
                if($gameType == 1 ? $v['result'] == 1 || $v['result'] == 0.5 : $v['result'] == 1)
                    $resultArr['winCount'] += $v['resultCount'];//赢的条数
                if($gameType == 1 ? $v['result'] == -1 || $v['result'] == -0.5 : $v['result'] == -1)
                    $resultArr['loseCount'] += $v['resultCount'];//输的条数
                if($v['result'] == 2)
                    $resultArr['flatCount'] += $v['resultCount'];//平的条数
                if($v['result'] == 0)
                    $resultArr['notOutCount'] += $v['resultCount'];//未出的条数
            }
            $totleNum = $resultArr['winCount'] + $resultArr['loseCount'] + $resultArr['flatCount'];
            $resultArr['winpercentage'] = round($resultArr['winCount']/$totleNum*100)."%";
            $resultArr['losepercentage'] = round($resultArr['loseCount']/$totleNum*100)."%";
            $resultArr['flatpercentage'] = round($resultArr['flatCount']/$totleNum*100)."%";
            $this->assign('resultArr', $resultArr);
        }
        //查看该会员该榜竞猜场次明细(有足球、篮球排行榜传过来)
        $begin_date = I('begin_date');
        $end_date   = I('end_date');
        if(! empty($begin_date) && ! empty($end_date))
        {
            //日期筛选
            $time = $gameType == 1 ? (10*60+32)*60 : (12*60)*60; //加上对应时间
            $map['user_id'] = I('user_id');
            $map['result']  = ["IN",['1','0.5','2','-1','-0.5']];
            $map['create_time']  = ["between",[ strtotime($begin_date) + $time, strtotime($end_date) + 86400 + $time ]];
        }

        //排序字段 默认为主键名
        $order = $_REQUEST ['_order'] ? : 'id';
        if($order == 'quiz_number') $order = 'quiz_number+extra_number';
        //排序方式默认按照倒序排列
        $sort = $_REQUEST ['_sort'] == 'asc' ? 'asc' : 'desc';
        $order_by = $order." ".$sort;
        $gamble_id = I('id');
        if($gamble_id!=''){
            unset($map['id']);
            $map['g.id'] = ['eq',$gamble_id];
        }
        $username = I('username');
        $nick_name = I('nick_name');
        if($username != '' || $nick_name != ''){
            $totalCount = M($model.' g')->join("LEFT JOIN qc_front_user f on f.id = g.user_id")->where($map)->count('g.id');
        }else{
            $totalCount = M($model.' g')->where($map)->count('g.id');
        }
        //获取每页显示的条数
        $pageNum = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
        //获取当前的页码
        $currentPage = ! empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1;

        if ($totalCount > 0)
        {
            $list = D($modelView)
                  ->where($map)
                  ->order($order_by)
                  ->limit($pageNum*($currentPage-1),$pageNum)
                  ->select();
        }

        $this->assign ( 'totalCount', $totalCount );//当前条件下数据的总条数
        $this->assign ( 'numPerPage', $pageNum ); //每页显示多少条
        $this->assign ( 'currentPage', $currentPage);//当前页码

        $export = I('Export');
        if ($export == 1) //判断是否为导出操作
        {
            if (I('totalCount') > 20000)
                $this->error('导出数据量过大，请根据条件筛选后再导出');

            $list = D($modelView)->where($map)->order($order_by)->select();
        }
        
        //从mongo获取赛程信息
        $gameIdArr   = array_column($list,'game_id');
        $DataService = new \Common\Services\DataService();
        $mongoGame   = $DataService->getMongoGameData($gameIdArr,$gameType);

        $list = HandleGamble($list,0,false,$gameType);
        foreach ($list as $k => $v)
        {
            $mongoGameArr = $mongoGame[$v['game_id']];
            $list[$k]['score'] = $mongoGameArr['score'];
            $list[$k]['half_score'] = $mongoGameArr['half_score'];
            if(in_array($mongoGameArr['game_state'], [-1,4,5]))
            {
                if($gameType == 1)
                {
                    $result = getTheWin($mongoGameArr['score'],$v['play_type'],$v['handcp'],$v['chose_side']);
                }
                else
                {
                    $result = getTheWinbk($mongoGameArr['score'],$v['half_score'],$v['play_type'],$v['handcp'],$v['chose_side']);
                }
            }else{
                $result = '';
            }
            $list[$k]['show_result'] = $result;
        }
        //导出操作
        if ($export == 1 )
        {
            $this->excelExport($list,'',$gameType,$gambleCount == 1 ? $resultArr : 1);
        }
        $this->assign('list', $list);
        $this->setJumpUrl();
        $this->display();
    }

	//推荐分析列表
    public function gambleDesc()
    {
        $gameType = I('gameType');
        $model = $gameType == 1 ? 'Gamble' : 'Gamblebk';
        $modelView = $gameType == 1 ? 'GambleView' : 'GamblebkView';
        //列表过滤器，生成查询Map对象
        $map = $this->_search($modelView);
        //时间查询
        if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
            if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                $startTime = strtotime($_REQUEST ['startTime']);
                $endTime   = strtotime($_REQUEST ['endTime'])+86400;
                $map['create_time'] = array('BETWEEN',array($startTime,$endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']);
                $map['create_time'] = array('EGT',$strtotime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime'])+86400;
                $map['create_time'] = array('ELT',$endTime);
            }
        }

        //去掉分析为空的
        $map['_string'] = " (g.`desc` <> '') OR (g.voice <> '') ";

        $nick_name = I('nick_name');
        if($nick_name != ''){
            $map['nick_name'] = $nick_name;
        }

        if ($gameType == 1)//足球
        {
            $_order = I('_order');
            $order = empty($_order) ? 'id desc' : $_order.' '.I('_sort');
            $username = I('username');
            $nick_name = I('nick_name');
            if($username != '' || $nick_name != ''){
                $gambleDayCount = M('gamble g')->join("LEFT JOIN qc_front_user f on f.id = g.user_id")->where($map)->count('g.id');
            }else{
                $gambleDayCount = M('gamble g')->where($map)->count('g.id');
            }

            //获取每页显示的条数
            $pageNum = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
            //获取当前的页码
            $currentPage =! empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1;

            if ($gambleDayCount > 0)
            {
                $list = D($modelView)
                      ->where($map)
                      ->order($order)
                      ->limit($pageNum*($currentPage-1),$pageNum)
                      ->select();
            }

            $this->assign ( 'totalCount', $gambleDayCount );//当前条件下数据的总条数
            $pageNum =empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
            $this->assign ( 'numPerPage', $pageNum ); //每页显示多少条
            $this->assign ( 'currentPage', !empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1);//当前页码
        }
        elseif ($gameType == 2)//篮球
        {
            $list = $this->_list(D($modelView), $map );//获取列表
        }

        $list = HandleGamble($list,0,false,$gameType);

        foreach($list as $k => $v){
            $list[$k]['voice'] = $v['voice'] ? Tool::imagesReplace($v['voice']) : '';
        }

        $this->assign('list', $list);
        $this->setJumpUrl();
        $this->display();
    }

    //编辑推荐分析
    public function saveDesc()
    {
        $id = I('id');
        $gameType = I('gameType');
        $model = $gameType == 1 ? M('Gamble') : M('Gamblebk');

        if(IS_POST){
            $data['desc']     = I('desc');
            $data['is_voice'] = I('is_voice');
            $data['desc_check'] = I('desc_check');

            $rs = $model->where(['id'=>$id])->save($data);
            if($rs !== false){
                //屏蔽音频，发送消息给用户
                if($data['is_voice'] == 0){
                    $one = $model->field("user_id, home_team_name, away_team_name")->where(['id'=>$id])->find();
                    //发送消息
                    $title   = '音频审核通知';
                    $content = "您推荐的【".explode(',', $one['home_team_name'])[0]."VS".explode(',', $one['away_team_name'])[0]."】音频内容涉及敏感词汇，已被屏蔽！";
                    $data['user_id']   = $_SESSION['authId'];
                    $data['game_type'] = $gameType;
                    $data['gamble_id'] = $id;
                    sendMsg($one['user_id'],$title,$content,$data);
                }

                //屏蔽分析内容，发送消息给用户
                if($data['desc_check'] == 0){
                    $one = $model->field("user_id, home_team_name, away_team_name")->where(['id'=>$id])->find();
                    //发送消息
                    $title   = '推荐分析审核通知';
                    $content = "您推荐的【".explode(',', $one['home_team_name'])[0]."VS".explode(',', $one['away_team_name'])[0]."】 文字分析内容涉及敏感词汇，已被屏蔽！";
                    $data['user_id']   = $_SESSION['authId'];
                    $data['game_type'] = $gameType;
                    $data['gamble_id'] = $id;
                    sendMsg($one['user_id'],$title,$content,$data);
                }

                $this->success('编辑成功！');
            }else{
                $this->error('编辑失败！');
            }
            exit;
        }
        
        $vo = $model->field("id,desc,home_team_name,away_team_name,is_voice,desc_check")->where(['id'=>$id])->find();
        $this->assign('vo', $vo);
        $this->display();
    }

    /**
     * @author liangzk <1343724998@qq.com>
     * @version 2.0
     * @date 2016-07-13  @time 10:41
     * @param        $list  列表
     * @param string $filename  导出的文件名
     * @param int $gameType  1:足球；2：篮球
     * @param array $percentage  比率
     */
    public function excelExport($list,$filename="",$gameType = 1,$percentage )
    {
        $filename = empty($filename) ? date('Y-m-d') : $filename;
        $strTable ='<table width="500" border="1">';

        if (is_array($percentage))
        {
            $strTable .= '<tr>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">未出场数</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">赢的人数</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">胜率</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">输的人数</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">输率</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">平的人数</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">平率</th>';
            $strTable .= '</tr>';
            $strTable .= '<tr>';
            if (empty($percentage['notOutCount'])) $notOutCount = 0;
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$notOutCount.'</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$percentage['winCount'].'</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$percentage['winpercentage'].'</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$percentage['loseCount'].'</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$percentage['losepercentage'].'</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$percentage['flatCount'].'</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$percentage['flatpercentage'].'</th>';
            $strTable .= '</tr>';
            $strTable .= '<tr>';
            $strTable .= '</tr>';
        }

        $strTable .= '<tr>';
        $strTable .= '<th style="text-align:center;font-size:12px;width:"*">ID</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">赛程ID</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width=120px;>赛事名称</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">比赛时间</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">用户昵称(<span  style="color: red;">用户名</span>)</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">竞猜时间</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">竞猜玩法</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">主队</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">全场(<span  style="color: red;">半场</span>)</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">客队</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">竞猜球队</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">盘口(<span  style="color: red;">指数</span>)</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">竞猜积分</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">金币</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">结算结果</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">获得积分</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">购买人数</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">销售金币</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">渠道类型</th>';
        $strTable .= '</tr>';

        foreach($list as $k=>$val){
            $strTable .= '<tr>';
            $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['id'].'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['game_id'].' </td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['union_name'].'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['game_date']." ".$val['game_time'].'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['nick_name']."(<span style=\"color: red;\">".is_show_mobile($val['username'])."</span>)".'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.date('Y-m-d H:i',$val['create_time']).'</td>';
            $play_type = '';
            if ($val['play_type'] == 1) $play_type = '全场让分';
            if ($val['play_type'] == -1) $gameType == 1 ? $play_type = '竞猜大小' : '全场大小' ;
            if ($val['play_type'] == 2) $play_type = '半场让分';
            if ($val['play_type'] == -2) $play_type = '半场大小';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$play_type.'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['home_team_name'].'</td>';

            $score = $val['score'] ? substr_replace($val['score'],"--",stripos($val['score'],'-'),1) : '--';
            $half_score = $val['half_score'] ? substr_replace($val['half_score'],"--",stripos($val['half_score'],'-'),1) : '--';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$score."(<span style=\"color: red;\">".$half_score."</span>)".'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['away_team_name'].'</td>';
            $gambleTeam = (getUserPower()['is_show_answer'] == 1 || $val['result'] != 0) ? $val['Answer'] : '--'; 
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$gambleTeam.'</td>';
            $oddsStr = (getUserPower()['is_show_answer'] == 1 || $val['result'] != 0) ? $val['handcp']."(<span style=\"color: red;\">".$val['odds']."</span>)" : '--'; 
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$oddsStr.'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['vote_point'].'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['tradeCoin'].'</td>';
            switch ($val['result'])
            {
                case 1:$result = '赢';   $color = 'color:red;';  break;
                case 0.5:$result = '赢半'; $color = 'color:red;'; break;
                case 2:$result = '平';   $color = 'color:green;';   break;
                case -1:$result = '输';  $color = 'color:blue;';  break;
                case -0.5:$result = '输半';$color = 'color:blue;';break;
                case -10:$result = '取消'; $color = 'color:black;'; break;
                case -11:$result = '待定'; $color = 'color:black;'; break;
                case -12:$result = '腰斩'; $color = 'color:black;'; break;
                case -13:$result = '中断'; $color = 'color:black;'; break;
                case -14:$result = '推迟'; $color = 'color:black;'; break;
                default:$result = '--';$color = 'color:black;';
            }
            $strTable .= '<td style="text-align:left;font-size:12px;'.$color.'">'.$result.'</td>';
            $earn_point = !empty($val['earn_point']) ? $val['earn_point'] : "--";
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$earn_point.'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['quiz_number'].'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['tradeCoin']*$val['quiz_number'].'</td>';
            switch ($val['platform'])
            {
                case 1: $platform = 'web'; break;
                case 2: $platform = 'IOS'; break;
                case 3: $platform = 'ANDRIOD'; break;
                default: $platform = '未知';
            }
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$platform.'</td>';
            $strTable .= '</tr>';
        }
        $strTable .='</table>';
        unset($orderList);
        downloadExcel($strTable,$filename);
        exit();
    }

    /**
     *购买详情
     */
    public function buyDetails()
    {
        $gamble_id=I('gamble_id');
        $gameType = I('gameType') ? : 1;
        $ModelView = $gameType == 1 ? 'BuyDetails' : 'BuyDetailsBk';
        //列表过滤器，生成查询Map对象
        $map = [];
        $nick_name = I('nick_name');
        if($nick_name != ''){
            $map['f.nick_name'] = ['like','%'.$nick_name.'%'];
        }
        $nick_name_by = I('nick_name_by');
        if($nick_name_by != ''){
            $map['fu.nick_name'] = ['like','%'.$nick_name_by.'%'];
        }
        //是否使用体验券筛选
        $is_ticket = I('is_ticket');
        if($is_ticket != ''){
            $map['ticket_id'] = $is_ticket == 0 ? 0 : ['gt',0];
        }
        if($_REQUEST ['endTime'] == 'index.php') $_REQUEST ['endTime'] = '';
        //时间查询
        if($_REQUEST ['endTime'] == 'coin') $_REQUEST ['endTime'] = '';
        if($_REQUEST ['startTime'] == 'endTime') $_REQUEST ['startTime'] = '';
        if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
            if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                $startTime = strtotime($_REQUEST ['startTime']);
                $endTime   = strtotime($_REQUEST ['endTime'])+86400;
                $map['log_time'] = array('BETWEEN',array($startTime,$endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']);
                $map['log_time'] = array('EGT',$strtotime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime'])+86400;
                $map['log_time'] = array('ELT',$endTime);
            }
        }
        //用户销售统计传过来的---被购买人数
        $user_id = I('user_id');
        if($user_id != ''){
            $map['q.user_id'] = $user_id;
        }
        $cover_id = I('cover_id');
        if($cover_id != ''){
            $map['q.cover_id'] = $cover_id;
        }
        $coin = I('coin');
        switch ($coin) {
            case '1':
                $map['q.coin'] = ['gt',0];
                break;
            case '2':
                $map['q.coin'] = ['eq',0];
                break;
        }

        if($gamble_id != ''){
            $map['gamble_id'] = $gamble_id;
        }
        $play_type = I('play_type');
        //玩法筛选条件
        switch ($play_type)
        {
            case 1: $map['g.play_type'] = ['IN',['1','-1']]; break;
            case 2: $map['g.play_type'] = ['IN',['2','-2']]; break;
        }

        $map['q.game_type'] = $gameType;

        //导出Excel
        $Export=I('Export');
        if(!empty($Export))
        {
            $list = D($ModelView)->where($map)->order("id desc")->select();
        }else{
            //手动获取列表
            $list = $this->_list(D($ModelView), $map );
        }

        $list = HandleGamble($list,0,false,$gameType);
        foreach ($list as $k => $v) {
            //购买与被卖ip与设备号相同判断标红
            if( ($v['device_token'] == $v['device_token_by'] && !empty($v['device_token'])) || $v['last_ip'] == $v['last_ip_by']){
                $list[$k]['yichang'] = 1;
            }
            $lastArr[] = $v['last_ip'];
            $deviceArr[] = $v['device_token'];
        }
        
        if($gamble_id != ''){
            //异常ip查询
            $lastArr = array_count_values($lastArr);
            $yichang_ip = [];
            foreach ($lastArr as $k => $v) {
                if($v > 1 && !empty($k)){
                    $yichang_ip[] = $k;
                }
            }
            
            //异常设备号查询
            $deviceArr = array_count_values($deviceArr);
            $yichang_device = [];
            foreach ($deviceArr as $k => $v) {
                if($v > 1 && !empty($k)){
                    $yichang_device[] = $k;
                }
            }

            foreach ($list as $k => $v) {
                if(in_array($v['last_ip'], $yichang_ip) && !empty($yichang_ip)){
                    $list[$k]['yichang'] = 1;
                }
                if(in_array($v['device_token'], $yichang_device) && !empty($yichang_device)){
                    $list[$k]['yichang'] = 1;
                }
            }
        }
        
        //dump($yichang_ip);
        $this->assign('list', $list);

        $gambleModel = $gameType == 1 ? 'gamble' : 'gamblebk';
        $result = M("quizLog q")
            ->join("LEFT JOIN qc_".$gambleModel." g on g.id = q.gamble_id")
            ->join("LEFT JOIN qc_front_user f on f.id = q.user_id")
            ->field("result,count(1) resultNum")
            ->where($map)->group("result")->select();
        $win   = 0;
        $half  = 0;
        $level = 0;
        $lose  = 0;
        $lhalf = 0;
        foreach ($result as $k => $v) {
            switch ($v['result']) {
                case    '1': $win   += $v['resultNum']; break;
                case  '0.5': $half  += $v['resultNum']; break;
                case    '2': $level += $v['resultNum']; break;
                case   '-1': $lose  += $v['resultNum']; break;
                case '-0.5': $lhalf += $v['resultNum']; break;
            }
        }
        $this->assign('win',$win);
        $this->assign('half',$half);
        $this->assign('level',$level);
        $this->assign('lose',$lose);
        $this->assign('lhalf',$lhalf);
        if(!empty($Export))
        {
            $this->excelExportBuy($list,'',$gameType);
            exit;
        }
        $this->display();
    }

    /*
     * @param        $list  列表
     * @param string $filename  导出的文件名
     * @param int $gameType  1:足球；2：篮球
     */
    public function excelExportBuy($list,$filename="",$game_type = 1)
    {
        $filename = empty($filename) ? date('Y-m-d') : $filename;
        $strTable ='<table width="500" border="1">';
        $strTable .= '<tr>';
        $strTable .= '<th style="text-align:center;font-size:12px;width:"*">ID</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;width:"*">购买人的名称</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width=120px;>购买日期</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">购买渠道</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">被购买人的名称</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">比赛时间</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">竞猜玩法</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">主队</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">比分</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">客队</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">竞猜球队</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">盘口</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">金币</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">结果</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">使用体验券</th>';
        $strTable .= '</tr>';

        foreach($list as $k=>$val){
            $strTable .= '<tr>';
            $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['id'].'</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['nick_name'].'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.date('Y/m/d H:i:s',$val['log_time']).' </td>';
            $platform = '';
            if ($val['platform'] == 1 ) $platform = 'Web';
            if ($val['platform'] == 2 ) $platform = 'IOS';
            if ($val['platform'] == 3 ) $platform = 'Android';
            if ($val['platform'] == 4 ) $platform = 'M站';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$platform.'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['nick_name_by'].'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['game_date']." ".$val['game_time'].'</td>';
            $play_type = '';
            if ($val['play_type'] == 1) $play_type = '全场让分';
            if ($val['play_type'] == -1) $game_type == 1 ? $play_type = '竞猜大小' : '全场大小' ;
            if ($val['play_type'] == 2) $play_type = '半场让分';
            if ($val['play_type'] == -2) $play_type = '半场大小';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$play_type.'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['home_team_name'].'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.
                            substr_replace($val['score'],"--",stripos($val['score'],'-'),1)
                        .'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['away_team_name'].'</td>';
            if(getUserPower()['is_show_answer'] == 1 || $vo['result'] != '0'){
                $Answer = $val['Answer'];
            }else{
                $Answer = '-';
            }

            $strTable .= '<td style="text-align:left;font-size:12px;">'.$Answer.'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['handcp'].'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['tradeCoin'].'</td>';
            switch ($val['result'])
            {
                case 1:$result = '赢';   $color = 'color:red;';  break;
                case 0.5:$result = '赢半'; $color = 'color:red;'; break;
                case 2:$result = '平';   $color = 'color:green;';   break;
                case -1:$result = '输';  $color = 'color:blue;';  break;
                case -0.5:$result = '输半';$color = 'color:blue;';break;
                case -10:$result = '取消'; $color = 'color:black;'; break;
                case -11:$result = '待定'; $color = 'color:black;'; break;
                case -12:$result = '腰斩'; $color = 'color:black;'; break;
                case -13:$result = '中断'; $color = 'color:black;'; break;
                case -14:$result = '推迟'; $color = 'color:black;'; break;
                default:$result = '--';$color = 'color:black;';
            }
            $strTable .= '<td style="text-align:left;font-size:12px;'.$color.'">'.$result.'</td>';
            $ticket = $val['ticket_id'] == 1 ? '是' : '否';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$ticket.'</td>';
            $strTable .= '</tr>';
        }
        $strTable .='</table>';
        unset($orderList);
        downloadExcel($strTable,$filename);
        exit();
    }

    //删除单个
    public function delete() {
        //删除指定记录
        $model = M("Gamble");
        if (!empty($model)) {
            $id = $_REQUEST ['id'];
            if (isset($id)) {
                $condition = array('id' => $id);
                $quiz_number = $model->where($condition)->getField('quiz_number');
                if($quiz_number > 0) {
                    $this->error('该记录已被查看，不能删除');
                }
                if (false !== $model->where($condition)->delete()) {
                    $this->success('删除成功！');
                } else {
                    $this->error('删除失败！');
                }
            } else {
                $this->error('非法操作');
            }
        }
    }

    //榜单推荐虚拟购买人数自动增加程序
    public function setIncRankGamble()
    {
        $HTime = date(H);
        if(!in_array($HTime, [13,16,17,19,20,21,22])) exit('error:not_time');

        //亚盘榜单前50
        $y_end_date = M('rankingList')->where('gameType=1')->order('id desc')->limit(1)->getField('end_date');
        $y_weekRank = M('rankingList r')
        ->join('LEFT JOIN qc_front_user f on f.id=r.user_id')
        ->field("user_id,f.is_robot,f.user_type,ranking,dateType,end_date,'1' as type")
        ->where("r.end_date = {$y_end_date} and r.gameType = 1 and r.ranking <= 100")
        ->order("end_date desc, ranking asc")->limit(300)->select();

        $y_date = $y_weekRank[0]['end_date'];
        foreach ($y_weekRank as $k => $v) {
            if($v['end_date'] != $y_date){
                unset($y_weekRank[$k]);
            }
        }
        //亚盘日榜
        $y_list_date = M('RedList')->where('game_type=1')->order('id desc')->limit(1)->getField('list_date');
        $red_list = M('RedList r')
        ->join('LEFT JOIN qc_front_user f on f.id=r.user_id')
        ->field("user_id,f.is_robot,f.user_type,ranking,'4' as dateType,list_date as end_date,'1' as type")
        ->where("r.list_date = {$y_list_date} and r.game_type = 1 and r.ranking <= 100")
        ->order("list_date desc, ranking asc")->limit(100)->select();

        $r_date = $red_list[0]['end_date'];
        foreach ($red_list as $k => $v) {
            if($v['end_date'] != $r_date){
                unset($red_list[$k]);
            }
        }
        $y_weekRank = array_merge($y_weekRank,$red_list);
        $y_userId   = array_unique(array_map("array_shift",$y_weekRank));

        //竞彩榜前50
        $j_end_date = M('rankBetting')->where('gameType=1')->order('id desc')->limit(1)->getField('listDate');
        $j_weekRank = M('rankBetting r')
        ->join('LEFT JOIN qc_front_user f on f.id=r.user_id')
        ->field("r.user_id,f.is_robot,f.user_type,r.ranking,r.dateType,r.listDate as end_date,'2' as type")
        ->where("r.listDate = {$j_end_date} and r.gameType = 1 and r.ranking <= 100")
        ->order("r.listDate desc, r.ranking asc")->limit(400)->select();
        $j_date = $j_weekRank[0]['end_date'];
        foreach ($j_weekRank as $k => $v) {
            if($v['end_date'] != $j_date){
                unset($j_weekRank[$k]);
            }
        }

        $j_userId   = array_unique(array_map("array_shift",$j_weekRank));

        //获取id并取唯一
        $userIdArr = array_unique(array_merge($y_userId,$j_userId));

        //获取今天的推荐
        $blockTime = getBlockTime(1);
        $gambleArr = M('gamble')->field('id,user_id,play_type,tradeCoin,quiz_number')->where(['user_id'=>['in',$userIdArr],['create_time'=>['between',[$blockTime['beginTime'],$blockTime['endTime']]]],'result'=>0])->order('id desc')->select();
        //dump($gambleArr);
        if(!$gambleArr) exit('error:not_gamele');

        //获取唯一榜单 优先规则：日榜->周榜->月榜->季榜
        $y_weekRank = $this->getUniqueRank($y_weekRank);
        $j_weekRank = $this->getUniqueRank($j_weekRank);

        //合并
        $weekRank = array_merge($y_weekRank,$j_weekRank);
        //dump($weekRank);
        foreach ($weekRank as $k => $v) {
            foreach ($gambleArr as $kk => $vv) {
                //亚盘
                if( ($v['user_id'] == $vv['user_id']) && in_array($vv['play_type'], [1,-1]) && $v['type'] == 1 ){
                    $weekRank[$k]['gamble'][] = $vv;
                }
                //竞彩
                if( ($v['user_id'] == $vv['user_id']) && in_array($vv['play_type'], [2,-2]) && $v['type'] == 2 ){
                    $weekRank[$k]['gamble'][] = $vv;
                }
            }
        }
        //dump($weekRank);
        //组装sql
        $ids = [];
        $sql = "UPDATE qc_gamble SET extra_number = CASE id ";
        foreach ($weekRank as $k => $v) 
        {
            if(empty($v['gamble'])) continue;

            foreach ($v['gamble'] as $kk => $vv) 
            {
                if($v['is_robot'] == 1 || $v['user_type'] == 2){
                    if($v['ranking'] <= 20){
                        //1-20名0元（8-16），2元——8元（2-4）、16元——64元（3-6）、128元——512元（1-3）
                        switch ($vv['tradeCoin']) {
                            case '0': 
                                switch ($HTime) {
                                    case '13': $setIncNum = rand(5,7);break;
                                    case '16': $setIncNum = rand(5,10);break;
                                    case '17': $setIncNum = rand(5,10);break;
                                    case '19': $setIncNum = rand(4,8);break;
                                    case '20': $setIncNum = rand(3,6);break;
                                    case '21': $setIncNum = rand(3,4);break;
                                    case '22': $setIncNum = rand(1,3);break;
                                }
                                break;
                            case '2':
                            case '4':
                            case '8': 
                                switch ($HTime) {
                                    case '13': $setIncNum = rand(2,3);break;
                                    case '16': $setIncNum = rand(2,3);break;
                                    case '17': $setIncNum = rand(2,3);break;
                                    case '19': $setIncNum = rand(2,4);break;
                                    case '20': $setIncNum = rand(2,4);break;
                                    case '21': $setIncNum = rand(2,3);break;
                                    case '22': $setIncNum = rand(2,3);break;
                                }
                                break;
                            case '16':
                            case '32':
                            case '64': 
                                switch ($HTime) {
                                    case '13': $setIncNum = rand(2,3);break;
                                    case '16': $setIncNum = rand(2,3);break;
                                    case '17': $setIncNum = rand(2,3);break;
                                    case '19': $setIncNum = rand(2,4);break;
                                    case '20': $setIncNum = rand(2,4);break;
                                    case '21': $setIncNum = rand(2,3);break;
                                    case '22': $setIncNum = rand(2,3);break;
                                }
                                break;
                            case '128':
                            case '256':
                            case '512': 
                                switch ($HTime) {
                                    case '13': $setIncNum = rand(0,1);break;
                                    case '16': $setIncNum = rand(0,1);break;
                                    case '17': $setIncNum = rand(0,1);break;
                                    case '19': $setIncNum = rand(1,2);break;
                                    case '20': $setIncNum = rand(1,2);break;
                                    case '21': $setIncNum = rand(0,2);break;
                                    case '22': $setIncNum = rand(0,1);break;
                                }
                                break;
                        }
                    }else if($v['ranking'] > 20 && $v['ranking'] <= 50){
                        //21-50名0元（4-8），2元——8元（1-3）、16元——64元（2-4）、128元——512元（0-2）
                        switch ($vv['tradeCoin']) {
                            case '0': 
                                switch ($HTime) {
                                    case '13': $setIncNum = rand(3,6);break;
                                    case '16': $setIncNum = rand(4,8);break;
                                    case '17': $setIncNum = rand(4,8);break;
                                    case '19': $setIncNum = rand(3,6);break;
                                    case '20': $setIncNum = rand(2,4);break;
                                    case '21': $setIncNum = rand(2,3);break;
                                    case '22': $setIncNum = rand(1,2);break;
                                }
                                break;
                            case '2':
                            case '4':
                            case '8': 
                                switch ($HTime) {
                                    case '13': $setIncNum = rand(1,2);break;
                                    case '16': $setIncNum = rand(1,2);break;
                                    case '17': $setIncNum = rand(1,2);break;
                                    case '19': $setIncNum = rand(1,3);break;
                                    case '20': $setIncNum = rand(1,2);break;
                                    case '21': $setIncNum = rand(1,2);break;
                                    case '22': $setIncNum = rand(1,2);break;
                                }
                                break;
                            case '16':
                            case '32':
                            case '64': 
                                switch ($HTime) {
                                    case '13': $setIncNum = rand(1,2);break;
                                    case '16': $setIncNum = rand(1,2);break;
                                    case '17': $setIncNum = rand(1,2);break;
                                    case '19': $setIncNum = rand(1,2);break;
                                    case '20': $setIncNum = rand(1,2);break;
                                    case '21': $setIncNum = rand(0,1);break;
                                    case '22': $setIncNum = rand(0,1);break;
                                }
                                break;
                            case '128':
                            case '256':
                            case '512': 
                                switch ($HTime) {
                                    case '13': $setIncNum = rand(0,1);break;
                                    case '16': $setIncNum = rand(0,1);break;
                                    case '17': $setIncNum = rand(0,1);break;
                                    case '19': $setIncNum = rand(0,1);break;
                                    case '20': $setIncNum = rand(0,1);break;
                                    case '21': $setIncNum = rand(0,0);break;
                                    case '22': $setIncNum = rand(0,0);break;
                                }
                                break;
                        }
                    }else if($v['ranking'] > 50 && $v['ranking'] <= 100){
                        //51-100名0元（4-8），2元——8元（1-2）、16元——64元（2-3）、128元——512元（0-1） 
                        switch ($vv['tradeCoin']) {
                            case '0': 
                                switch ($HTime) {
                                    case '13': $setIncNum = rand(4,8);break;
                                    case '16': $setIncNum = rand(3,6);break;
                                    case '17': $setIncNum = rand(3,6);break;
                                    case '19': $setIncNum = rand(2,4);break;
                                    case '20': $setIncNum = rand(1,2);break;
                                    case '21': $setIncNum = rand(1,2);break;
                                    case '22': $setIncNum = rand(1,2);break;
                                }
                                break;
                            case '2':
                            case '4':
                            case '8': 
                                switch ($HTime) {
                                    case '13': $setIncNum = rand(1,2);break;
                                    case '16': $setIncNum = rand(1,2);break;
                                    case '17': $setIncNum = rand(1,2);break;
                                    case '19': $setIncNum = rand(1,2);break;
                                    case '20': $setIncNum = rand(0,1);break;
                                    case '21': $setIncNum = rand(1,2);break;
                                    case '22': $setIncNum = rand(1,2);break;
                                }
                                break;
                            case '16':
                            case '32':
                            case '64': 
                                switch ($HTime) {
                                    case '13': $setIncNum = rand(0,1);break;
                                    case '16': $setIncNum = rand(0,1);break;
                                    case '17': $setIncNum = rand(0,1);break;
                                    case '19': $setIncNum = rand(0,1);break;
                                    case '20': $setIncNum = rand(0,1);break;
                                    case '21': $setIncNum = rand(0,1);break;
                                    case '22': $setIncNum = rand(0,1);break;
                                }
                                break;
                            case '128':
                            case '256':
                            case '512': 
                                switch ($HTime) {
                                    case '13': $setIncNum = rand(0,1);break;
                                    case '16': $setIncNum = rand(0,0);break;
                                    case '17': $setIncNum = rand(0,0);break;
                                    case '19': $setIncNum = rand(0,0);break;
                                    case '20': $setIncNum = rand(0,0);break;
                                    case '21': $setIncNum = rand(0,0);break;
                                    case '22': $setIncNum = rand(0,0);break;
                                }
                                break;
                        }
                    }
                }else{
                    if($vv['tradeCoin'] == 0){
                        $setIncNum = rand(2,5);
                    }else{
                        $setIncNum = 0;
                    }
                }
                if($setIncNum == 0) continue;

                $ids[] = $vv['id'];
                $sql .= sprintf("WHEN %d THEN extra_number+$setIncNum ", $vv['id']);
            }
        }
        $sql .= "END WHERE id IN (".implode(',', $ids).")";
        // echo($sql);
        // die;
        $rs = M()->execute($sql);
        exit('success'.$rs);
        //echo "<br/>";
    }

    //获取唯一榜单 优先规则：日榜->周榜->月榜->季榜
    public function getUniqueRank($weekRank){
        $r_arr = $z_arr = $y_arr = $j_arr = [];
        foreach ($weekRank as $k => $v) {
            if($v['dateType'] == 4) $r_arr[] = $v;
            if($v['dateType'] == 1) $z_arr[] = $v;
            if($v['dateType'] == 2) $y_arr[] = $v;
            if($v['dateType'] == 3) $j_arr[] = $v;
        }
        $r_arr_id = array_map("array_shift", $r_arr);
        $z_arr_id = array_map("array_shift", $z_arr);
        $y_arr_id = array_map("array_shift", $y_arr);
        $j_arr_id = array_map("array_shift", $j_arr);

        foreach ($j_arr as $k => $v) {
            if(in_array($v['user_id'], array_merge($r_arr_id,$z_arr_id,$y_arr_id))){
                unset($j_arr[$k]);
            }
        }

        foreach ($z_arr as $k => $v) {
            if(in_array($v['user_id'], $r_arr_id)){
                unset($z_arr[$k]);
            }
        }

        foreach ($y_arr as $k => $v) {
            if(in_array($v['user_id'], array_merge($z_arr_id,$r_arr_id))){
                unset($y_arr[$k]);
            }
        }
        return array_merge($r_arr,$z_arr,$y_arr,$j_arr);
    }
}