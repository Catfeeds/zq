<?php
/**
 * 比分页面
 */
use Think\Controller;
use Think\Tool\Tool;
use Common\Mongo\GambleHallMongo;

class ScoreController extends CommonController
{
    public $preMatchinfo = '';
    public function _initialize()
    {
        C('HTTP_CACHE_CONTROL', 'no-cache,no-store');
        parent::_initialize();
        $url = explode('.',$_SERVER['REQUEST_URI'])[0];
        if(strpos($url,'header') && strpos($url,'no')) $this->assign('noHead',1);
    }

    public function fbGame(){
        //获取赛事
        $webfbService = new \Home\Services\WebfbService();
        $gameData = $webfbService->fbtodayList();
        // dump($gameData);
        // die;
        $game  = $gameData['info']; //赛事
        $union = $gameData['union']; //联盟

        $this->ajaxReturn(['status'=>1,'data'=>['game'=>$game,'union'=>$union,'state'=>C('game_state')]]);
    }
    /**
     * @ 比分主页 dengwj
     * */
    public function index()
    {
        $this->score_nav();
        //mqtt 配置
        $mqtt = C('Mqtt');
        $this->assign('mqttOpt', $mqtt);
        $this->assign('mqttUser', setMqttUser());
        $for = I('f');
        if($for == 'no'){
            $this->display("indexFor");
            die;
        }
        $this->display();
    }

    /**
     * 获取比赛各项数据 api hzl
     */
    public function gameInfo()
    {
        //获取赔率
        $map['game_id'] = I('game_id');
        $map['company_id'] = I('company_id') ?: 3;
        $scConfig = C('score');
        $scConfig2 = C('score_sprit');
        $res = M('FbOdds')->field('game_id,exp_value')->where($map)->find();

        $odds = oddsChArr($res['exp_value']);
        $retArrs = [];
        //全场
        $fsw_odds = $this->do_odds($odds, 'fsw');
        $retArrs['odds']['fsw_exp_home'] = $fsw_odds['fsw_exp_home'];
        $temp_exp = trim($fsw_odds['fsw_exp'], '-');
        $retArrs['odds']['fsw_exp'] = strpos($fsw_odds['fsw_exp'], '-') !== false ? '受' . $scConfig[$temp_exp] : $scConfig[$temp_exp];
        $retArrs['odds']['fsw_exp_away'] = $fsw_odds['fsw_exp_away'];
        $retArrs['odds']['fsw_ball_home'] = $fsw_odds['fsw_ball_home'];
        $retArrs['odds']['fsw_ball'] = $scConfig2[sprintf("%01.2f", $fsw_odds['fsw_ball'])];
        $retArrs['odds']['fsw_ball_away'] = $fsw_odds['fsw_ball_away'];
        $retArrs['odds']['fsw_europe_home'] = $fsw_odds['fsw_europe_home'];
        $retArrs['odds']['fsw_europe'] = $fsw_odds['fsw_europe'];
        $retArrs['odds']['fsw_europe_away'] = $fsw_odds['fsw_europe_away'];

        //半场
        $half_odds = $this->do_odds($odds, 'half');
        $retArrs['odds']['half_exp_home'] = $half_odds['half_exp_home'];
        $temp_exp2 = trim($half_odds['half_exp'], '-');
        $retArrs['odds']['half_exp'] = strpos($half_odds['half_exp'], '-') !== false ? '受' . $scConfig[$temp_exp2] : $scConfig[$temp_exp2];
        $retArrs['odds']['half_exp_away'] = $half_odds['half_exp_away'];
        $retArrs['odds']['half_ball_home'] = $half_odds['half_ball_home'];
        $retArrs['odds']['half_ball'] = $scConfig2[sprintf("%01.2f", $half_odds['half_ball'])];
        $retArrs['odds']['half_ball_away'] = $half_odds['half_ball_away'];
        $retArrs['odds']['half_europe_home'] = $half_odds['half_europe_home'];
        $retArrs['odds']['half_europe'] = $half_odds['half_europe'];
        $retArrs['odds']['half_europe_away'] = $half_odds['half_europe_away'];

        //对阵信息
        $appService = new \Home\Services\AppdataService();
        $gameInfo = $appService->getTeamLogo($_REQUEST['game_id']);
        $retArrs['gameInfo'] = $gameInfo;

        $this->ajaxReturn($retArrs);
    }


    /**
     *Liangzk <Liangzk@qc.com>
     * 足球比分的头隐藏
     *
     */
    public function score_nav()
    {
        $header = I('header', '', 'string');
        $this->assign('header', empty($header) ? '' : 'no');
    }

    /**
     * @ 获取赔率  滚球->即时->初盘
     * @param $vv array 赔率数据
     * @param $type string  类型  fsw 全场   half 半场
     * @return array
     * */
    public function do_odds($vv, $type)
    {
        $whole = $type == 'fsw' ? $vv[0] : $vv[3];  //全场
        if ($whole[6] != '' || $whole[7] != '' || $whole[8] != '') {
            //全场滚球
            if ($whole[6] == 100 || $whole[7] == 100 || $whole[7] == 100) {
                $odds[$type . '_exp_home'] = '';
                $odds[$type . '_exp'] = '封';
                $odds[$type . '_exp_away'] = '';
            } else {
                $odds[$type . '_exp_home'] = $whole[6];
                $odds[$type . '_exp'] = $whole[7];
                $odds[$type . '_exp_away'] = $whole[8];
            }
        } elseif ($whole[3] != '' || $whole[4] != '' || $whole[5] != '') {
            //全场即时
            if ($whole[3] == 100 || $whole[4] == 100 || $whole[5] == 100) {
                $odds[$type . '_exp_home'] = '';
                $odds[$type . '_exp'] = '封';
                $odds[$type . '_exp_away'] = '';
            } else {
                $odds[$type . '_exp_home'] = $whole[3];
                $odds[$type . '_exp'] = $whole[4];
                $odds[$type . '_exp_away'] = $whole[5];
            }
        } elseif ($whole[0] != '' || $whole[1] != '' || $whole[2] != '') {
            //初盘
            if ($whole[0] == 100 || $whole[1] == 100 || $whole[2] == 100) {
                $odds[$type . '_exp_home'] = '';
                $odds[$type . '_exp'] = '封';
                $odds[$type . '_exp_away'] = '';
            } else {
                $odds[$type . '_exp_home'] = $whole[0];
                $odds[$type . '_exp'] = $whole[1];
                $odds[$type . '_exp_away'] = $whole[2];
            }
        }

        $size = $type == 'fsw' ? $vv[2] : $vv[5];  //大小
        if ($size[6] != '' || $size[7] != '' || $size[8] != '') {
            //大小滚球
            if ($size[6] == 100 || $size[7] == 100 || $size[8] == 100) {
                $odds[$type . '_ball_home'] = '';
                $odds[$type . '_ball'] = '封';
                $odds[$type . '_ball_away'] = '';
            } else {
                $odds[$type . '_ball_home'] = $size[6];
                $odds[$type . '_ball'] = $size[7];
                $odds[$type . '_ball_away'] = $size[8];
            }
        } elseif ($size[3] != '' || $size[4] != '' || $size[5] != '') {
            //大小即时
            if ($size[3] == 100 || $size[4] == 100 || $size[5] == 100) {
                $odds[$type . '_ball_home'] = '';
                $odds[$type . '_ball'] = '封';
                $odds[$type . '_ball_away'] = '';
            } else {
                $odds[$type . '_ball_home'] = $size[3];
                $odds[$type . '_ball'] = $size[4];
                $odds[$type . '_ball_away'] = $size[5];
            }
        } elseif ($size[0] != '' || $size[1] != '' || $size[2] != '') {
            //大小初盘
            if ($size[0] == 100 || $size[1] == 100 || $size[2] == 100) {
                $odds[$type . '_ball_home'] = '';
                $odds[$type . '_ball'] = '封';
                $odds[$type . '_ball_away'] = '';
            } else {
                $odds[$type . '_ball_home'] = $size[0];
                $odds[$type . '_ball'] = $size[1];
                $odds[$type . '_ball_away'] = $size[2];
            }
        }

        $europe = $type == 'fsw' ? $vv[1] : $vv[4];  //欧赔
        if ($europe[6] != '' || $europe[7] != '' || $europe[8] != '') {
            //欧赔滚球
            if ($europe[6] == 100 || $europe[7] == 100 || $europe[8] == 100) {
                $odds[$type . '_europe_home'] = '';
                $odds[$type . '_europe'] = '封';
                $odds[$type . '_europe_away'] = '';
            } else {
                $odds[$type . '_europe_home'] = $europe[6];
                $odds[$type . '_europe'] = $europe[7];
                $odds[$type . '_europe_away'] = $europe[8];
            }
        } elseif ($europe[3] != '' || $europe[4] != '' || $europe[5] != '') {
            //欧赔即时
            if ($europe[3] == 100 || $europe[4] == 100 || $europe[5] == 100) {
                $odds[$type . '_europe_home'] = '';
                $odds[$type . '_europe'] = '封';
                $odds[$type . '_europe_away'] = '';
            } else {
                $odds[$type . '_europe_home'] = $europe[3];
                $odds[$type . '_europe'] = $europe[4];
                $odds[$type . '_europe_away'] = $europe[5];
            }
        } elseif ($europe[0] != '' || $europe[1] != '' || $europe[2] != '') {
            //欧赔初盘
            if ($europe[0] == 100 || $europe[1] == 100 || $europe[2] == 100) {
                $odds[$type . '_europe_home'] = '';
                $odds[$type . '_europe'] = '封';
                $odds[$type . '_europe_away'] = '';
            } else {
                $odds[$type . '_europe_home'] = $europe[0];
                $odds[$type . '_europe'] = $europe[1];
                $odds[$type . '_europe_away'] = $europe[2];
            }
        }
        return $odds;
    }

    /**
     * @ 未来赛事
     * */
    public function schtoday()
    {
        if($_SERVER['PATH_INFO'] != 'schtoday' && strpos($_SERVER['PATH_INFO'],'schtoday/score_t/') === false) parent::_empty();
        //页面日期S
        $date = array();
        for ($i = 1; $i < 8; $i++) {
            $time = time() + $i * 60 * 60 * 24;
            $date[$i]['day'] = date('m-d', $time);
            $date[$i]['week'] = $this->getTimeWeek($time);
            $date[$i]['time'] = $time;
        }
        $this->assign('week', $date);
        //页面日期E
        $_time = I('score_t', time() + 60 * 60 * 24);
        $time_class = date('m-d', $_time);
        $this->assign('scroet', $_time);
        $this->assign('time_class', $time_class);
//        $arr = $this->over_game($_time, 0);
//
//        $this->assign('list', $arr);
        $this->score_nav();
        $for = I('f');
        if($for == 'no'){
            $this->display("schtodayFor");
            die;
        }
        $this->display();
    }

    /**
     * @ 完场比分
     * */
    public function schedule()
    {
        if($_SERVER['PATH_INFO'] != 'schedule'  && strpos($_SERVER['PATH_INFO'],'schedule/score_t/') === false) parent::_empty();
        //页面日期S
        $date = array();
        for ($i = 1; $i < 8; $i++) {
            $time = time() - $i * 60 * 60 * 24;
            $date[$i]['day'] = date('m-d', $time);
            $date[$i]['week'] = $this->getTimeWeek($time);
            $date[$i]['time'] = $time;
        }
        $this->assign('week', $date);
        //页面日期E
        $_time = I('score_t', time() - 60 * 60 * 24);
        $time_class = date('m-d', $_time);
        $this->assign('scroet', $_time);
        $this->assign('time_class', $time_class);
//        $arr = $this->over_game($_time);
//        $this->assign('list', $arr);
        $this->score_nav();
        $for = I('f');
        if($for == 'no'){
            $this->display("scheduleFor");
            die;
        }
        $this->display();
    }

    //未來賽事完場賽事數據接口
    public function getOverGame()
    {
        $time = I('time',strtotime('-1 day'),int);
        if(I('type') == 1)
            $arr = $this->over_game($time);
        else
            $arr = $this->future_game($time);
        $data = ['status'=>1,'data'=>$arr];
        $this->ajaxReturn($data);
    }
    /**
     * @ 对未來赛事进行缓存
     * @ author liuweitao 906742852@qq.com
     * @ $_time 查询时间
     */
    public function future_game($time){
        $_time = date('Y-m-d', $time);
        if (S('future_game_'.$_time)) {
            $arr = S('future_game_'.$_time);
        } else {
            $res = $this->_future_game($time);
            $arr = $res;
            S('future_game_'.$_time, $res, 60 * 5);
        }
        return $arr;
    }

    //查詢未來賽事
    public function _future_game($_time)
    {
        $time = date('Ymd', $_time);
        $appfbService = new \Home\Services\AppfbService();
        $res = $appfbService->fbFixtureList($time);
        $data = $this->over_and_future($res);
        $gameArr = $data['gameArr'];
        $unionArr = $data['unionArr'];
        $newsArr = $data['newsArr'];
        $nullArr = ["total"=>'--',"double_col"=>'',"double"=>'--',"score"=>'--',"score_col"=>'', "day"=>'', "draw"=>'--', "win"=>'--', "win_col"=>'', "home_col"=>'', "away_col"=>'', "home_col_b"=>'', "away_col_b"=>'', "game_state"=>'未开'];
        foreach($res as $key=>$val)
        {
            $sb = '';
            $is_league = $val[38];
            if($val[24] != '') $sb = $this->_sb($val[24], 1);
            $home_id = $val[22];
            $away_id = $val[23];
            array_splice($val, 8, 0, '');
            $val = array_slice($val,0,11);
            $val = array_pad($val,37,'');
            $mysqlGame = $gameArr[$val[0]];
            //联盟表数据
            $unionData = $unionArr[$val[1]];
            $unionLevel = isset($unionData['level']) ? $unionData['level'] : 3;
            $webfbService = new \Home\Services\WebfbService();

            $val['tuijian'] = (string)$webfbService->checkGamble([
                'game_id'       => $val[0],
                'is_gamble'     => $mysqlGame['is_gamble'],
                'is_show'       => $mysqlGame['is_show'],
                'is_sub'        => $unionLevel,
                'fsw_exp'       => $val[23],
                'fsw_exp_home'  => $val[24],
                'fsw_exp_away'  => $val[25],
                'fsw_ball'      => $val[26],
                'fsw_ball_home' => $val[27],
                'fsw_ball_away' => $val[28],
            ]);
            $val['sb'] = $sb;
            $val['news'] = in_array($val[0], $newsArr) ? 1 : 0;
            $val['union_level'] = $unionData['level'];
            $unionArr[$val[1]]['total'] = (int)$unionArr[$val[1]]['total'] + 1;
            $val[35] = $home_id;
            $val[36] = $away_id;
            $val['is_go'] = $mysqlGame['is_go']?:0;//滾球
            $val['is_betting'] = $mysqlGame['is_betting']?:0;//競猜
            $val = array_merge($val,$nullArr);
            $val[38] = $is_league;
            $res[$key] = $val;
        }
        $list['info'] = array_values($res);
        $list['match'] = array_values($unionArr);
        return $list;
    }

    /**
     * @ 对完场赛事进行缓存
     * @ author liuweitao 906742852@qq.com
     * @ $_time 查询时间
     */
    public function over_game($time, $type = 1)
    {
        $_time = date('Y-m-d', $time);
        if (S('over_game_'.$_time)) {
            $arr = S('over_game_'.$_time);
        } else {
            $res = $this->_over_game($time, $type);
            $arr = $res;
            S('over_game_'.$_time, $res, 60 * 5);
        }
        return $arr;
    }


    //完場未來公用數據查詢
    public function over_and_future($res)
    {

        $gids = array_column($res,0);
        $unionIdArr = array_column($res,1);
        $mongo = mongoService();
        $GameFbinfo = M('GameFbinfo')->field("game_id,is_gamble,is_show,status,web_video,is_video")->where(['game_id'=>['in',$gids]])->select();
        $newsArr = M('PublishList')->where(['game_id'=>['in',$gids]])->group('game_id')->getField('game_id',true);
        $gamewhere = [];
        foreach ($GameFbinfo as $k => $v) {
            $gameArr[$v['game_id']] = $v;
            $gamewhere[] = (int)$v['game_id'];
        }
        //獲取滾球競猜數據
        $gameM = $mongo->select('fb_game',['game_id'=>['$in'=>$gamewhere]],['game_id','is_go','is_sporttery']);
        foreach ($gameM as $k => $v) {
            $gameArr[$v['game_id']]['is_go'] = $v['is_go']?:0;
            $gameArr[$v['game_id']]['is_betting'] = $v['is_sporttery']?:0;
        }
        //获取联盟数据
        $unionIdArr = array_values(array_unique($unionIdArr));
        foreach($unionIdArr as $key=>$val)
        {
            $unionIdArr[$key] = (int)$val;
        }
        $union = $mongo->select('fb_union',['union_id'=>['$in'=>$unionIdArr]],['union_id','union_name','country_id','level','union_or_cup','union_name_today','union_color']);
        foreach ($union as $k => $v) {
            $v['union_name'] = $v['union_name']?:$v['union_name_today'];
            unset($v['_id'],$v['union_name_today']);
            $unionArr[$v['union_id']] = $v;
        }
        return ['gameArr'=>$gameArr,'unionArr'=>$unionArr,'newsArr'=>$newsArr];
    }

    /**
     * @ 对完场赛事进行整理
     * @ author liuweitao 906742852@qq.com
     * @ $_time 查询时间
     */
    public function _over_game($_time, $type = 1)
    {
        $bifen = array(1 => '主', 0 => '平', -1 => '客');
        $game_state = C('game_state');
        $unionId = $gameArr = $unionArr = [];
        $time = date('Ymd', $_time);
//            $res = $this->get_curl("/Home/Webfb/fbOver", "date=$time", C('CURL_DOMAIN_QW'));
        $appfbService = new \Home\Services\AppfbService();
        $res = $appfbService->fbOverList($time);
        $data = $this->over_and_future($res);
        $gameArr = $data['gameArr'];
        $unionArr = $data['unionArr'];
        $newsArr = $data['newsArr'];
        foreach ($res as $key => &$val) {
            $val['total'] = $val[13] + $val[14];//主队得分加客队得分
            $score_double = $val['total']%2 == 1?'1':'2';
            switch ($score_double) {
                case 1;
                    $val['double_col'] = 'text-blue';
                    $val['double'] = '单';
                    break;
                case 2;
                    $val['double_col'] = 'text-red';
                    $val['double'] = '双';
                    break;
            }
            if ($val[27]) {
                if(strpos($val[27],'/'))
                {
                    $tmp_27 = explode('/',$val[27]);
                    $tmp27 = ($tmp_27[0]+$tmp_27[1])/2;
                }else{
                    $tmp27 = $val[27];
                }
                if ($tmp27 < $val['total']) {
                    $val['score'] = '大';
                    $val['score_col'] = 'text-blue';
                } elseif ($tmp27 > $val['total']) {
                    $val['score'] = '小';
                    $val['score_col'] = 'text-red';
                } elseif ($tmp27 = $val['total']) {
                    $val['score'] = '平';
                }
            } else {
                $val['score'] = '-';
            }
            $day = substr($val[6], 4);
            $val['day'] = substr_replace($day, '-', 2, 0);
            $half_score = $val[15] .'-'. $val[16];
            $score = $val[13] .'-'. $val[14];
            $tmp47 = getScoreWinFb($half_score) !== false?getScoreWinFb($half_score):'';
            $tmp48 = getScoreWinFb($score) !== false?getScoreWinFb($score):'';
            $val['draw'] = $bifen[$tmp47] . '/' . $bifen[$tmp48];
            $tmp44 = getHandcpWin($score,$val[24]);
//            $tmp45 = getBallWinFb($score,$val[27]);
            switch ($tmp44) {
                case '赢';
                    $val['win'] = '赢';
                    $val['win_col'] = 'red';
                    break;
                case '走';
                    $val['win'] = '走';
                    $val['win_col'] = 'blue';
                    break;
                case '输';
                    $val['win'] = '输';
                    $val['win_col'] = '#A9A9A9';
                    break;
                default:
                    $val['win'] = '-';
            }
            $_q = $this->_compare($val[13], $val[14]);
            $val['home_col'] = $_q[0];
            $val['away_col'] = $_q[1];
            $_b = $this->_compare($val[15], $val[16]);
            $val['home_col_b'] = $_b[0];
            $val['away_col_b'] = $_b[1];
            if ($type) {
                $_num = $val[24];
            } else {
                $_num = $val[22];
            }
            $val['sb'] = $this->_sb($_num, 1);
            $val['game_state'] = $game_state[$val[5]];
            $unionId[] = $val[1];
            $mysqlGame = $gameArr[$val[0]];
            $val['is_go'] = $mysqlGame['is_go'];//滾球
            $val['is_betting'] = $mysqlGame['is_betting'];//競猜
            //联盟表数据
            $unionData = $unionArr[$val[1]];
            $unionLevel = isset($unionData['level']) ? $unionData['level'] : 3;
            $webfbService = new \Home\Services\WebfbService();

            $val['tuijian'] = (string)$webfbService->checkGamble([
                'game_id'       => $val[0],
                'is_gamble'     => $mysqlGame['is_gamble'],
                'is_show'       => $mysqlGame['is_show'],
                'is_sub'        => $unionLevel,
                'fsw_exp'       => $val[23],
                'fsw_exp_home'  => $val[24],
                'fsw_exp_away'  => $val[25],
                'fsw_ball'      => $val[26],
                'fsw_ball_home' => $val[27],
                'fsw_ball_away' => $val[28],
            ]);
            $val['news'] = in_array($val[0], $newsArr) ? 1 : 0;
            $val['union_level'] = $unionData['level'];
            $unionArr[$val[1]]['total'] = (int)$unionArr[$val[1]]['total'] + 1;
        }
        $list['info'] = array_values($res);
        $list['match'] = array_values($unionArr);
        return $list;
    }

    /**
     * @ 盘口数据处理
     * @author liuweitao 906742852@qq.com
     * $int 盘口数据
     */
    public function _sb($int, $type)
    {
        $_type = substr($int, 0, 1);
        if($_type == '-' && strlen($int) == 1) return '';
        $score = C('score');
        $str = '';
        if ($_type == '-') {
            $int = substr($int, 1);
            if ($type == 1) {
                $str = "<span class='text-red'>*</span>";
            } else {
                $str = '受';
            }
        }
        $num = explode('/', $int);
        if (count($num) <= 1) {
            return $str . $score[$num[0]];
        } else {
            $n = strval(($num[0] + $num[1]) / 2);
            return $str . $score[$n];
        }
    }

    /**
     * @ 统计赛事下赛程数量
     * @author liuweitao 906742852@qq.com
     * $arr 完场赛事数据
     */
    public function total_match($arr)
    {
        $tmp = array();
        $_tmp = array();
        foreach ($arr['info'] as $val) {
            $tmp[$val[1]][] = $val;
        }
        foreach ($tmp as $k => $v) {
            $_tmp[$k] = count($v);
        }
        foreach ($arr['match'] as &$item) {
            $item['total'] = $_tmp[$item[0]];
        }
        return $arr;
    }

    /**
     * @ 获取时间对应星期
     * @author liuweitao 906742852@qq.com
     * $time 需要获取的时间戳
     */
    function getTimeWeek($time)
    {
        $weekarray = array("日", "一", "二", "三", "四", "五", "六");
        return $weekarray[date("w", $time)];
    }

    /**
     * Liangzk <Liangzk@qc.com>
     * @ 即时指数
     * */
    public function indices()
    {
//        //注意：把多个类型的值，存到同一个Cookie变量里  联赛ID---前缀‘!’，后缀‘@’ 公司ID---前缀‘#’，后缀‘$’
//
//        //获取公司
//        $company = C('DB_COMPANY_INFO');
//        //获取公司ID
//        $companyKey = array();
//        foreach ($company as $k => $v) {
//            $companyKey[] = $k;
//        }
//
//        //用户筛选的联赛ID和公司ID
//        $indicesCookie = I('cookie.indicesCookie');
//        $unionIdStr = explode('@', explode('!', $indicesCookie)[1])[0];//联赛ID
//        $companyIdStr = explode('$', explode('#', $indicesCookie)[1])[0];//公司ID
//
//        $unionIdArr = $unionIdStr ? explode(',', $unionIdStr) : [];//筛选ID 默认：“澳门”、“SB”、“BET365"
//        $companyId = $companyIdStr ? explode(',', $companyIdStr) : [1, 3, 8];//筛选ID 默认：“澳门”、“SB”、“BET365"
//
//        //指数
////		$instantUnion = $this->get_curl("/Home/Webfb/fbInstant", 'key=no', C('CURL_DOMAIN_QW'))['data'];
//        $webfbService = new \Home\Services\WebfbService();
//        $instantUnion = $webfbService->fbInstant();
//
//        //今日赛事
////		$toDayEvent = $this->get_curl("/Home/Webfb/fb", 'key=no', C('CURL_DOMAIN_QW'))['data']['info'];
//        $toDayEvent = $webfbService->fbtodayList()['info'];
//        //赛事名称、级别
//        $unionName = $instantUnion['union'];
//        //赛事列表
//        $unionInfo = $instantUnion['info'];
//        unset($instantUnion);
//
//        $unionLevelOne = $unionLevelTwo = $unionLevelThree = array();
//        foreach ($unionName as $key => $value) {
//            $gameCount = 0;//今日赛事数
//            foreach ($toDayEvent as $k => $v) {
//                if ($v[1] == $value[0])//判断是否为同一联赛
//                {
//                    $gameCount++;
//                }
//            }
//            $unionName[$key]['gameCount'] = $gameCount;
//
//            switch ($value[6]) {
//                case 0 :
//                    $unionLevelOne[] = $unionName[$key];//级别一
//                case 1 :
//                    $unionLevelOne[] = $unionName[$key];
//                    break;//级别一
//                case 2 :
//                    $unionLevelTwo[] = $unionName[$key];
//                    break;//级别二
//                case 3 :
//                    $unionLevelThree[] = $unionName[$key];
//                    break;//级别三
//            }
//
//        }
//        //指数
//
//
//        foreach ($unionInfo as $key => $value) {
//            //标识不显示的联赛
//            $unionInfo[$key]['is_display'] = in_array($value[1], $unionIdArr) || empty($unionIdArr) ? 1 : 0;
//
//            //比赛日期格式转换
//            $unionInfo[$key][8] = date('Y-m-d', strtotime($value[8]));
//            //比赛开始到现在的时间
//            $unionInfo[$key]['gameTimeed'] = time() > strtotime($value[8] . $value[9]) ? time() - strtotime($value[8] . $value[9]) : 0;
//            //指数
//            $companyInstant = array();
//            foreach ($companyKey as $comK => $comV) {
//                //亚盘
//                foreach ($value[21] as $k => $v) {
//                    if ($comV == $k) {
//                        $companyInstant[$comV][21] = $v;
//                    }
//                }
//                //欧赔
//                foreach ($value[22] as $k => $v) {
//                    if ($comV == $k) {
//                        $companyInstant[$comV][22] = $v;
//                    }
//                }
//                //大小
//                foreach ($value[23] as $k => $v) {
//                    if ($comV == $k) {
//                        $companyInstant[$comV][23] = $v;
//                    }
//                }
//            }
//            $unionInfo[$key]['companyInstant'] = $companyInstant;
//            unset($unionInfo[$key][21], $unionInfo[$key][22], $unionInfo[$key][23]);
//
//        }
//
//        $this->assign('unionLevelOne', $unionLevelOne);//级别一
//        $this->assign('unionLevelTwo', $unionLevelTwo);//级别二
//        $this->assign('unionLevelThree', $unionLevelThree);//级别三
//        $this->assign('unionInfo', $unionInfo);//赛事列表
//        $this->assign('company', $company);//公司
//        $this->assign('companyId', $companyId);//指数列表要显示的公司的公司ID
        //mqtt 配置
        $mqtt = C('Mqtt');
        $this->assign('mqttOpt', $mqtt);
        $this->assign('mqttUser', setMqttUser());
        if($_SERVER['PATH_INFO'] != 'indices') parent::_empty();
        $this->score_nav();
        if (I('var') == 'dump') var_dump($unionInfo);
        $for = I('f');
        if($for == 'no'){
            $this->display("indicesFor");
            die;
        }
        $this->display();
    }


    /**
     * Liangzk <Liangzk@qc.com>
     *
     */
    public function generate()
    {
        if($_SERVER['PATH_INFO'] != 'generate') parent::_empty();
        $this->display();
    }


    /*
   * @ 事件赛事
   * $gameId 赛事ID
   */
    public function event_technology()
    {
        $gameId = I('game_id', 0, 'int');//赛事id
        if ($gameId === 0) {
            $this->error();
        }

        $this->oddsHeader($gameId);
	
	    $webfbService = new \Home\Services\WebfbService();
	    $temp = $webfbService->getWebGameDetailTc($gameId);

        $eventRe_t = $temp['detail'][$gameId];//赛事事件
        $eventRe_s = $temp['tc'][$gameId];//赛事技术

        foreach ($eventRe_t as $k => $v) {
            //判断是否有换人
            if ($v[2] == 11) {
//                $str = mb_substr($v[6],0,-1,'utf-8');
                $arrName = explode('↑', $v[6]);
//                list($upName,$endName) = $arrName;
                $eventRe_t[$k]['upName'] = $arrName[0];
                $eventRe_t[$k]['endName'] = $arrName[1];
            }
        }
        foreach ($eventRe_s as $k => $v) {
            $eventRe_s[$k]['homerate'] = round($v[2] / ($v[2] + $v[3]) * 100);
            $eventRe_s[$k]['awayrate'] = round($v[3] / ($v[2] + $v[3]) * 100);
        }
        $eventRe_s = $this->multi_array_sort($eventRe_s, '1');
        $this->assign('eventRe_t', $eventRe_t);
        $this->assign('eventRe_s', $eventRe_s);

        $lineup = $webfbService->getWebLineUp($gameId);

        //首发、替补球员
        $lineupStart = $lineupSub = array();

        //主队
        foreach ($lineup['home'] as $key => $value) {
            if ($value[3] == 1) {
                $lineupStart[$key]['home'] = $value;
            } elseif ($value[3] == 0) {
                $lineupSub[$key]['home'] = $value;
            }
        }

        //客队
        foreach ($lineup['away'] as $key => $value) {
            if ($value[3] == 1) {
                $lineupStart[$key]['away'] = $value;
            } elseif ($value[3] == 0) {
                $lineupSub[$key]['away'] = $value;
            }

        }

        //首发
        $this->assign('lineupStart', $lineupStart);
        //替补
        $this->assign('lineupSub', $lineupSub);

        $this->assign('lineupSubCount', count($lineupSub));

        $this->assign('gameId', $gameId);
        $this->display();
    }

    /**
     * @User liuwt <liuwt@Qc.com>
     * @DateTime 2017-02-17
     *对二维数组的某个值排序
     */
    public function multi_array_sort($multi_array, $sort_key, $sort = SORT_ASC)
    {
        if (is_array($multi_array)) {
            foreach ($multi_array as $row_array) {
                if (is_array($row_array)) {
                    $key_array[] = $row_array[$sort_key];
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }
        array_multisort($key_array, $sort, $multi_array);
        return $multi_array;
    }

    /**
     * @User dengwj (mongo数据)
     * @DateTime 2018-07-04
     * 亚盘赔率、大小比较、欧洲指数、数据分析、事情赛况、推荐详情的头部
     */
    public function oddsHeader($gameId)
    {
        if(empty($gameId)){
            $this->_empty();
        }
        $mongodb = mongoService();

        $gameInfo = $mongodb->select('fb_game',['game_id'=>(int)$gameId],['game_id','union_id','home_team_name','away_team_name','union_name','home_team_id','away_team_id','game_starttime','game_start_timestamp','start_time','score','half_score','home_team_rank','away_team_rank','game_state','match_odds.3']);

        if(empty($gameInfo)){
            $this->_empty();
        }

        //获取联盟数据
        $union = $mongodb->select('fb_union',['union_id'=>$gameInfo[0]['union_id']],['union_id','union_name','level','union_color','is_league']);

        //获取mysql业务数据
        $GameFbinfo = M('GameFbinfo')->field("game_id,is_gamble,is_show,status,web_video,is_video")->where(['game_id'=>$gameId])->find();

        //主客队名字转换
        foreach ($gameInfo as $key => $value) {
            $gameInfo[$key]['union_color']    = $union[0]['union_color'];
            $gameInfo[$key]['home_team_name'] = $value['home_team_name'][0];
            $gameInfo[$key]['away_team_name'] = $value['away_team_name'][0];
            $gameInfo[$key]['union_name'] = $value['union_name'][0];
            $game_start_timestamp = $value['game_start_timestamp'] ? : $value['game_starttime']->sec;
            $gameInfo[$key]['game_time']  = $game_start_timestamp;
            $gameInfo[$key]['gtime'] = date('Y-m-d H:i', $game_start_timestamp);
            $gameInfo[$key]['is_gamble']  = $GameFbinfo['is_gamble'];
            $gameInfo[$key]['is_show']    = $GameFbinfo['is_show'];
            $gameInfo[$key]['is_sub']     = $union[0]['level'];
            $gameInfo[$key]['is_league']     = $union[0]['is_league'];

            //是否有视频直播
            $web_video = $GameFbinfo['web_video'];
            $videoNum = 0;
            if($web_video){
                $web_video = json_decode($web_video,true);
                //判断是否有直播链接
                foreach ($web_video as $web => $url) {
                    if($url['weburl'] != ''){
                        $videoNum++;
                    }
                }
            }

            $gameInfo[$key]['is_video'] = (in_array($value['game_state'], [0,1,2,3,4]) && $GameFbinfo['is_video'] == 1 && $videoNum > 0) ? 1 : 0;
            //是否有动画直播
            if(in_array($value['game_state'], [0,1,2,3,4])){
//                 $gameInfo[$key]['is_flash'] = D('GambleHall')->getFbLinkbet($value['game_id']);
                $gameInfo[$key]['is_flash'] = (new GambleHallMongo())->getFbLinkbet($value['game_id']);
            }else{
                $gameInfo[$key]['is_flash'] = 0;
            }
            
            #初盘赔率
            $odds    = $value['match_odds'][3];
            $gameInfo[$key]['fsw_exp_home']  = str_replace(' ', '', $odds[0]);    //主队亚盘初盘赔率
            $gameInfo[$key]['fsw_exp']       = str_replace(' ', '', $odds[1]);    //亚盘初盘盘口
            $gameInfo[$key]['fsw_exp_away']  = str_replace(' ', '', $odds[2]);    //客队亚盘初盘赔率
            $gameInfo[$key]['fsw_ball_home'] = str_replace(' ', '', $odds[12]);   //主队大小初盘赔率
            $gameInfo[$key]['fsw_ball']      = str_replace(' ', '', $odds[13]);   //大小初盘盘口
            $gameInfo[$key]['fsw_ball_away'] = str_replace(' ', '', $odds[14]);   //客队大小初盘赔率
        }
        //获取球队logo
        setTeamLogo($gameInfo);

        $webfbService = new \Home\Services\WebfbService();
        $is_gamble = $webfbService->checkGamble($gameInfo[0]);

        $data = $gameInfo[0];
        $from = $this->param['from'] ?: 1;
        //情报来源
        $appService = new \Home\Services\AppfbService();
        $preMatchinfo = $appService->getPreMatchinfo($gameId,$from);
        $this->preMatchinfo = $preMatchinfo;

        $articleList = (array)M('PublishList')->field('id')
            ->where(['game_id' => $gameId, 'status' => 1])
            ->select();
        if(empty($preMatchinfo) && empty($articleList))
        {
            $data['is_news'] = 0;
        }else{
            $data['is_news'] = 1;
        }
        $data['game_state_zn'] = C('game_state')[$data['game_state']];
        $this->assign('gameInfo', $data);
        $this->assign('gameId', $gameId);
        $this->assign('is_gamble', $is_gamble);
        return $data;
    }
    
    public function oddsHeader1($gameId)
    {
        $gameInfo = M('GameFbinfo g')
            ->join('INNER JOIN qc_union u ON  g.union_id = u.union_id')
            ->where(['g.game_id' => $gameId])
            ->field('g.id,g.union_id,g.home_team_name,g.away_team_name,g.union_name,g.home_team_id,g.away_team_id,g.gtime,g.score,g.half_score,g.home_team_rank,g.away_team_rank,g.fsw_exp,fsw_ball,g.fsw_exp_home,g.fsw_exp_away,g.fsw_ball_home,g.fsw_ball_away,g.is_gamble,g.is_video,g.game_state,g.is_show,u.is_sub,g.is_video,g.is_flash')
            ->select();

        $union_color = M('Union')->where(['union_id' => $gameInfo[0]['union_id']])->getField('union_color');

        //获取球队logo
        setTeamLogo($gameInfo);
        $webfbService = new \Home\Services\WebfbService();
        $is_gamble = $webfbService->checkGamble($gameInfo[0]);
        //主客队名字转换
        foreach ($gameInfo as $key => $value) {
            $gameInfo[$key]['home_team_name'] = explode(',', $value['home_team_name'])[0];
            $gameInfo[$key]['away_team_name'] = explode(',', $value['away_team_name'])[0];
            $gameInfo[$key]['union_name'] = explode(',', $value['union_name'])[0];
            $gameInfo[$key]['game_time'] = $value['gtime'];
            $gameInfo[$key]['gtime'] = date('Y-m-d H:i', $value['gtime']);
            $gameInfo[$key]['gameStatus'] = $value['gtime'] < time() ? 1 : 0;
            $gameInfo[$key]['let_exp'] = M('FbBetodds')->where("game_id_new=" . $value['id'])->getField('let_exp');

        }
        $data = $gameInfo[0];
        $from = $this->param['from'] ?: 1;//情报来源
        $appService = new \Home\Services\AppfbService();
        $preMatchinfo = $appService->getPreMatchinfo($gameId,$from);
        $this->preMatchinfo = $preMatchinfo;
        $articleList = (array)M('PublishList')->field('id')
            ->where(['game_id' => $gameId, 'status' => 1])
            ->select();
        if(empty($preMatchinfo) && empty($articleList))
        {
            $data['is_news'] = 0;
        }else{
            $data['is_news'] = 1;
        }
        $data['game_state_zn'] = C('game_state')[$data['game_state']];
        $this->assign('gameInfo', $data);
        $this->assign('union_color', $union_color);
        $this->assign('gameId', $gameId);
        $this->assign('is_gamble', $is_gamble);
    }

    /**
     * @User Liangzk <Liangzk@Qc.com>
     * @DateTime 2016-01-18
     * 亚盘赔率、大小比较
     */
    public function ypOdds()
    {
        $sign = I('sign', 1, 'int');//1：亚盘赔率 2：大小比较
        $gameId = I('game_id', 0, 'int');

        if ($gameId < 0) {
            $this->error();
        }

        $this->oddsHeader($gameId);

        $webfbService = new \Home\Services\WebfbService();
		$aoAdds = $webfbService->getNewAllOddsAndHistoryOdds($gameId, $sign);
        foreach ($aoAdds as $key => $value) {
            $homeTemp = $awayTemp = '';
            end($value['aohis']);
            while (!is_null($k = key($value['aohis']))) {
                $v = current($value['aohis']);
                //主队
                if ($homeTemp === '') {
                    $homeTemp = $v[0];
                    $aoAdds[$key]['homeColor'] = $aoAdds[$key]['aohis'][$k]['homeColor'] = 0;//0:黑色 1：红色 2：绿色

                } else {
                    if ($homeTemp == $v[0]) {
                        $aoAdds[$key]['homeColor'] = $aoAdds[$key]['aohis'][$k]['homeColor'] = 0;//0:黑色 1：红色 2：绿色
                    } else {
                        $aoAdds[$key]['homeColor'] = $aoAdds[$key]['aohis'][$k]['homeColor'] = $homeTemp > $v[0] ? 2 : 1;//0:黑色 1：红色 2：绿色
                    }
                    $homeTemp = $v[0];
                }
                //客队
                if ($awayTemp === '') {
                    $awayTemp = $v[2];
                    $aoAdds[$key]['awayColor'] = $aoAdds[$key]['aohis'][$k]['awayColor'] = 0;//0:黑色 1：红色 2：绿色
                } else {
                    if ($awayTemp == $v[2]) {
                        $aoAdds[$key]['awayColor'] = $aoAdds[$key]['aohis'][$k]['awayColor'] = 0;//0:黑色 1：红色 2：绿色
                    } else {
                        $aoAdds[$key]['awayColor'] = $aoAdds[$key]['aohis'][$k]['awayColor'] = $awayTemp > $v[2] ? 2 : 1;//0:黑色 1：红色 2：绿色
                    }

                    $awayTemp = $v[2];
                }

                prev($value['aohis']);
            }

        }
        $this->assign('aoAdds', $aoAdds);
        $this->assign('sign', $sign);
        $this->display();
    }

    /**
     * @author liuweitao <906742852@qq.com>
     * @DateTime 2017-1-18
     * 欧洲指数
     */
    public function eur_index()
    {
        $game_id = I('game_id');
        $this->oddsHeader($game_id);
        $this->assign('gameId', $game_id);
        $list = $this->eur($game_id);
        $this->assign('list', $list);
        $this->display();
    }


    /**
     * @author liuweitao <906742852@qq.com>
     * @DateTime 2017-1-18
     * 赛事ID处理欧赔指数
     */
    public function eur($game_id)
    {
        $webfbService = new \Home\Services\WebfbService();
        $data = $webfbService->getNewEurOdds($game_id);
        $game_res = $this->eur_rank($data);
        foreach ($game_res['oohis'] as $key => &$val) {
            $val = array_slice($val, 0, 10);
            foreach ($val as &$v) {
                $v = explode('^', $v);
                $v[0] = sprintf("%01.2f",$v[0]);
                $v[1] = sprintf("%01.2f",$v[1]);
                $v[2] = sprintf("%01.2f",$v[2]);
            }
            $data = $val[0];
            if (count($data) == 7) {
                $k1 = $data[4];
                $k2 = $data[5];
                $k3 = $data[6];
            } else {
                $k1 = $data[5];
                $k2 = $data[6];
                $k3 = $data[7];
            }
            $_rate = ($data[0] * $data[1] * $data[2]) / ($data[0] * $data[1] + $data[1] * $data[2] + $data[2] * $data[0]);
            $_rate = number_format($_rate, 2, '.', '');
            $num = count($val);
            if (count($val) > 1) {
                for ($i = 0; $i < $num; $i++) {
                    $home_col = $this->color_cont($val[$i][0], $val[$i + 1][0]);
                    $draw_col = $this->color_cont($val[$i][1], $val[$i + 1][1]);
                    $away_col = $this->color_cont($val[$i][2], $val[$i + 1][2]);
                    $val[$i]['time_day'] = explode(' ', $val[$i][3])[0];
                    $val[$i]['time_hour'] = explode(' ', $val[$i][3])[1];
                    $val[$i]['home_col'] = $home_col;
                    $val[$i]['draw_col'] = $draw_col;
                    $val[$i]['away_col'] = $away_col;
                    if ($i == 0) {
                        $game_res['oo'][$key]['home_col'] = $home_col;
                        $game_res['oo'][$key]['draw_col'] = $draw_col;
                        $game_res['oo'][$key]['away_col'] = $away_col;
                    }
                }
            }
            $game_res['oo'][$key]['rate'] = $_rate * 100;
            $game_res['oo'][$key]['kelly_1'] = $k1;
            $game_res['oo'][$key]['kelly_2'] = $k2;
            $game_res['oo'][$key]['kelly_3'] = $k3;
            $game_res['oo'][$key]['time_day'] = explode(' ', $val[0][3])[0];
            $game_res['oo'][$key]['time_hour'] = explode(' ', $val[0][3])[1];
            $game_res['oo'][$key]['home_rate'] = number_format($_rate / $val[0][0], 2, '.', '') * 100;
            $game_res['oo'][$key]['pin_rate'] = number_format($_rate / $val[0][1], 2, '.', '') * 100;
            $game_res['oo'][$key]['away_rate'] = number_format($_rate / $val[0][2], 2, '.', '') * 100;
            $game_res['oo'][$key][2] = sprintf("%01.2f",$game_res['oo'][$key][2]);
            $game_res['oo'][$key][3] = sprintf("%01.2f",$game_res['oo'][$key][3]);
            $game_res['oo'][$key][4] = sprintf("%01.2f",$game_res['oo'][$key][4]);
            $game_res['oo'][$key][5] = sprintf("%01.2f",$game_res['oo'][$key][5]);
            $game_res['oo'][$key][6] = sprintf("%01.2f",$game_res['oo'][$key][6]);
            $game_res['oo'][$key][7] = sprintf("%01.2f",$game_res['oo'][$key][7]);

        }
        return $game_res;
    }

    /*
     * 欧赔按照公司排序
     */
    public function eur_rank($data)
    {
        $eurComp = [
            545 => 'SB',
            281 => 'bet 365',
            80 => '澳门',
            82 => '立博',
            115 => '威廉希尔',
            81 => '伟德',
            90 => '易胜博',
            517 => '明陞',
            474 => '利记sbobet',
            499 => '金宝博',
            422 => '博天堂',
            659 => '盈禾',
            450 => 'TOTO',
            60 => 'STS',
            110 => 'SNAI',
            177 => 'Pinnacle',
            16 => '10BET',
            18 => '12BET',
            1183 => 'ManbetX',
            976 => '18Bet',
            173 => 'bet-at-home',
            255 => 'Bwin',
            88 => 'Coral',
            71 => 'Eurobet',
            70 => 'Expekt',
            104 => 'Interwetten',
            97 => 'Nike',
            4 => 'Nordicbet',
            649 => 'IBCBET',
            158 => 'Gamebookers',
        ];
        $tmp = array();
        foreach ($eurComp as $key => $val) {
            if ($data['oo'][$key]) {
                $tmp['oohis'][$key] = $data['oohis'][$key];
                $tmp['oo'][$key] = $data['oo'][$key];
                $tmp['oo'][$key]['img'] = '1';
            }
            unset($data['oohis'][$key], $data['oo'][$key]);
        }
        $tmp['oohis'] = array_filter($tmp['oohis'] + $data['oohis']);
        $tmp['oo'] = array_filter($tmp['oo'] + $data['oo']);
        return $tmp;
    }

    /**
     * @author liuweitao <906742852@qq.com>
     * @DateTime 2017-1-18
     * 对数据对比返回颜色
     */
    public function color_cont($fir, $last)
    {
        if (empty($last)) return '';
        if ($fir > $last) {
            return 'text-red';
        } elseif ($fir < $last) {
            return 'text-green';
        } else {
            return '';
        }
    }

    /**
     * @author liuweitao <906742852@qq.com>
     * @DateTime 2017-1-19
     * @ChangeTime 2018-06-25
     * 数据分析页面
     */
    public function dataFenxi()
    {
        $game_id = I('game_id');
	    $webfbService = new \Home\Services\WebfbService();
	    $mongodb = mongoService();
	    $otherData = $mongodb->select('fb_game', ['game_id'=> (int) $game_id],
		    ['home_team_name', 'away_team_name',  'home_team_id', 'away_team_id','game_analysis_web_qt', 'union_color','game_analysis_mobi'])[0];
	    $home_name = $otherData['home_team_name'][0];
	    $away_name = $otherData['away_team_name'][0];
	    $web_analysis = $otherData['game_analysis_web_qt'];
	    $union_color = $otherData['union_color'];
	    $home_id = $otherData['home_team_id'];
	    $away_id = $otherData['away_team_id'];
	
	    $this->assign('gameId', $game_id);
	    $this->assign('union_color', $union_color);
	    $this->assign("home_team_name", $home_name);
	    $this->assign("away_team_name", $away_name);
	    
		//即时赔率
	    $analysisGoals = S('dataFenxi_goals'.$game_id);
	    if(!$analysisGoals)
	    {
		    $analysisGoals = $this->analysisGoals($game_id);
		    S('dataFenxi_goals'.$game_id,$analysisGoals,300);
	    }
	    $this->assign('goals', $analysisGoals);
	    
	    //对往战绩
	    $pastMatchData = $webfbService->getPastMatchData($game_id, 1, TRUE);
		$match_fight = $this->match_fight($pastMatchData, $home_id);
        $this->assign('match_fight', $match_fight);//处理对阵历史
	    
        //近期交战
		$home_data = $webfbService->getRecentMatchData($game_id, 'home_team');
	    $jinqi_home = $this->jinqi($home_data, $home_id);
		$guest_data = $webfbService->getRecentMatchData($game_id, 'guest_team');
	    $jinqi_away = $this->jinqi($guest_data, $away_id);
		$jinqi_data = [
			0=>['name'=>'recent_fight1','content'=>$jinqi_home],
			1=>['name'=>'recent_fight2','content'=>$jinqi_away]
		];
		$recent_fight = $this->recent_fight($jinqi_data);
        $this->assign('recent_fight', $recent_fight);//处理近期赛事
	    
	    //未来三场数据
//	    $home_future_three = $this->future_three($web_analysis['future_five']['home_team'], $union_color);
//	    $away_future_three = $this->future_three($web_analysis['future_five']['guest_team'], $union_color);
	    $this->assign('home_future_three', $otherData['game_analysis_mobi']['future_three']['home_team']);
	    $this->assign('away_future_three', $otherData['game_analysis_mobi']['future_three']['away_team']);
	    $this->assign("home_team_name", $home_name);
	    $this->assign("away_team_name", $away_name);
	    
	    // 联赛积分
	    $this->assign('home_league_rank', $web_analysis['league_rank']['home_team']);
	    $this->assign('away_league_rank', $web_analysis['league_rank']['guest_team']);
	    
	    // 联赛盘路走势
		$this->assign('panlu', $web_analysis['panlu_trend']);
		
		// 入球数/上下半场入球分布
	    $this->assign('goal_distribution', $web_analysis['goal_distribution']);
	    
	    // 大小/单双
	    $this->assign('bigSmall_singleDouble', $web_analysis['bigSmall_singleDouble']);
		
	    // 半全场
	    $this->assign('half_full', $web_analysis['half_full']);
	    
	    // 进球时间
	    $this->assign('goal_time', $web_analysis['goal_time']);
	    
	    // 数据对比
	    $this->assign('compare', $web_analysis['data_compare']);
		
	    // 处理相同历史亚盘
        $same_odd['home_team'] = $this->_same_odd($otherData['game_analysis_mobi']['same_odd']['home_team']);
        $same_odd['guest_team'] = $this->_same_odd($otherData['game_analysis_mobi']['same_odd']['away_team']);
	    if($same_odd['home_team'] || $same_odd['guest_team']) $this->assign('same_odd', $same_odd);
	    
        //新模块数据
        $appService = new \Home\Services\AppfbService();
        $new_res = $appService->getAnaForFile($game_id, 1);
        foreach ($new_res as $val) {
            if ($val['name'] == 'StHaveId' && ($val['content']['Home_S'] || $val['content']['Away_S'])) {
                $this->assign('st', $val['content']);//处理阵容情况
            }
        }
        $appService2 = new \Home\Services\AppdataService();
        $team_info[] = $otherData['game_analysis_web_qt']['game_layoff']['home_team']['last_launch'];
        $team_info[] = $otherData['game_analysis_web_qt']['game_layoff']['away_team']['last_launch'];
        $h[] = $otherData['game_analysis_web_qt']['game_layoff']['home_team']['last_backup'];
        $h[] = $otherData['game_analysis_web_qt']['game_layoff']['away_team']['last_backup'];
        if($team_info[0] || $team_info[1]) $res['s'] = $team_info;
        if($h[0] || $h[1]) $res['h'] = $h;
        if($res['s'] || $res['h']) $this->assign('team', $res);
        $this->oddsHeader($game_id);

        //必发指数
//        $bifa = $this->bifa($game_id);
        $bifa = [];
        $this->assign('bifa', $bifa);

        $this->display();
    }

    //处理相同历史亚盘数据
    public function _same_odd($data){
        $res = [];
        foreach($data as $val)
        {
            $tmp = [];
            $tmp[] = $val['league_name'];
            $tmp[] = substr($val['game_time'],0,4)."-".substr($val['game_time'],4,2)."-".substr($val['game_time'],6,2);
            $tmp[] = $val['home_team_name'];
            $tmp[] = $this->_sb($val['first_odd'],1);
            $tmp[] = $val['away_team'];
            $tmp[] = $val['score'];
            $tmp[] = $val['panlu'];
            $tmp[] = $val['home_team_id'];
            $tmp[] = $val['away_team_id'];
            $res[] = $tmp;
        }
        return $res;
    }

    //数据详情近期对战数据处理
    public function jinqi($data,$home_id)
    {
        foreach($data as $k=>$v)
        {
        	$home_temp_conner = $v[sizeof($v)-2];
            $away_temp_conner = $v[sizeof($v)-1];
	        unset($data[$k][sizeof($v) - 2], $data[$k][sizeof($v) - 1]);
	        $chupan2 =  $ou = [];
	        $mongodb = mongoService();
	        $otherData = $mongodb->select('fb_game', ['game_id'=> (int) $v[1]],
		        ['match_odds_m_asia', 'match_odds', 'match_odds_m_bigsmall','corner',  'home_team_id', 'union_color'])[0];
	        $chupan2[] = (string) $otherData['corner'];
	        $chupan2[] = (string) $otherData['match_odds_m_asia'][3][0];
	        $chupan2[] = (string) $otherData['match_odds_m_asia'][3][2];
	        $chupan2[] = (string) $otherData['home_team_id'];
            $ou[] =  (string) $otherData['match_odds'][3][6];
	        $ou[] =  (string) $otherData['match_odds'][3][7];
	        $ou[] =  (string) $otherData['match_odds'][3][8];
            array_unshift($data[$k], (string) $home_id);
            unset($data[$k][17]);
            $data[$k][] = $ou[0]?$ou[0]:'';
            $data[$k][] = $ou[0]?$ou[1]:'';
            $data[$k][] = $ou[0]?$ou[2]:'';
            $data[$k][] = $chupan2[1] ?$chupan2[1]:'';
            $data[$k][] = $chupan2[2]?$chupan2[2]:'';
	        $data[$k][] = $home_temp_conner == null ?'': $home_temp_conner;
	        $data[$k][] = $away_temp_conner == null ?'': $away_temp_conner;
            $data[$k] = array_values($data[$k]);
        }
        return $data;
    }

    //必发指数
    public function bifa($id)
    {
        $WebService = new \Home\Services\WebfbService();
        $res = $WebService->getFenxiBifa($id);
//        var_dump($res);
//        $data = M("FbBingfa")->field('bf_value_win')->where(['game_id' => $id])->find();
//        $bf_val = json_decode($data['bf_value_win'], true);
//        $bf_dx[] = $bf_val[3];
//        $bf_dx[] = $bf_val[4];
        return $res;
    }


    /*
     * 处理数据对比
     */
    public function Compare($res)
    {
        $data = array();
        $data[0][0] = array_merge($res[0][0], $res[0][3]);
        $data[0][1] = array_merge($res[0][1], $res[0][4]);
        $data[0][2] = array_merge($res[0][2], $res[0][5]);
        $data[1][0] = array_merge($res[1][0], $res[1][3]);
        $data[1][1] = array_merge($res[1][1], $res[1][4]);
        $data[1][2] = array_merge($res[1][2], $res[1][5]);
        return $data;
    }

    /*
     * 处理相同历史亚盘
     */
    public function sameExp($res)
    {
        $num = count($res[0]) > count($res[1]) ? count($res[0]) : count($res[1]);
        $arr = array();
        for ($i = 0; $i < $num; $i++) {
            $arr[] = array_merge($res[0][$i] ? $res[0][$i] : array(), $res[1][$i] ? $res[1][$i] : array());
        }
        foreach ($arr as $k => $v) {
            $arr[$k]['t'] = substr($v[1], 0, 4) . '-' . substr($v[1], 4, 2) . '-' . substr($v[1], 6, 2);
            $arr[$k]['t2'] = substr($v[11], 0, 4) . '-' . substr($v[11], 4, 2) . '-' . substr($v[11], 6, 2);
        }
        return $arr;
    }

    
    // 数据分析即时赔率
    public function analysisGoals($game_id)
    {
	    $webfbService = new \Home\Services\WebfbService();
	    // 所需公司名称 3 cown (皇冠 SB) 8 bet 365 1 澳彩 12易胜博
	    $compareOdds = $goals = [];
	    $compareIds = [3, 8, 1, 12];
	    foreach ($compareIds as $k => $v) {
	    	$temp = $webfbService->getNewAllOdds($game_id, $v);
	    	if (!($webfbService->nullString($temp['asia']) || $webfbService->nullString($temp['bigsmall']) || $webfbService->nullString($temp['europ']))) {
			    $compareOdds[$v] = $temp;
		    }
	    }
	    $compareNames = [ 3 =>'ＳＢ', 8 => 'bet 365', 1 => '澳彩', 12 => '易胜博'];
	    // ych 亚赔主队初盘 ycp 亚赔盘口  yca  亚赔客队赔率
	    // yjh 亚赔即时盘口 yjp 亚赔即时盘口 yja 亚赔客队赔率
	    // ycz 亚赔初盘水位  yjz 亚赔即时水位
	    // 以此类推 d 为大小  o 为欧赔
	    foreach ($compareOdds as $key => $value) {
	    	$goals[] = ['on' => $compareNames[$key],
			    'ych' => $compareOdds[$key]['asia'][0], 'ycp' => $compareOdds[$key]['asia'][1], 'yca' =>$compareOdds[$key]['asia'][2],
			    'yjh' => $compareOdds[$key]['asia'][3], 'yjp' => $compareOdds[$key]['asia'][4], 'yja' =>$compareOdds[$key]['asia'][5],
			    'dch' => $compareOdds[$key]['bigsmall'][0], 'dcp' => $compareOdds[$key]['bigsmall'][1], 'dca' =>$compareOdds[$key]['bigsmall'][2],
			    'djh' => $compareOdds[$key]['bigsmall'][3], 'djp' => $compareOdds[$key]['bigsmall'][4], 'dja' =>$compareOdds[$key]['bigsmall'][5],
			    'och' => $compareOdds[$key]['europ'][0], 'ocp' => $compareOdds[$key]['europ'][1], 'oca' =>$compareOdds[$key]['europ'][2],
			    'ojh' => $compareOdds[$key]['europ'][3], 'ojp' => $compareOdds[$key]['europ'][4], 'oja' =>$compareOdds[$key]['europ'][5],
			    'ycz' => (string) ($compareOdds[$key]['asia'][0] + $compareOdds[$key]['asia'][2]), 'yjz' => (string) ($compareOdds[$key]['bigsmall'][3] + $compareOdds[$key]['bigsmall'][5]),
			    'dcz' => (string) ($compareOdds[$key]['bigsmall'][0] + $compareOdds[$key]['bigsmall'][2]), 'djz' => (string) ($compareOdds[$key]['bigsmall'][3] + $compareOdds[$key]['bigsmall'][5]),
			    'ocz' => (string) ($compareOdds[$key]['europ'][0] + $compareOdds[$key]['europ'][2]), 'ojz' => (string) ($compareOdds[$key]['europ'][3] + $compareOdds[$key]['europ'][5]),
			    'yhc' => $this->goals_c($compareOdds[$key]['asia'][0], $compareOdds[$key]['asia'][3]),
			    'yac' =>$this->goals_c($compareOdds[$key]['asia'][2], $compareOdds[$key]['asia'][5]),
			    'ypc' => $this->goals_c($compareOdds[$key]['asia'][1], $compareOdds[$key]['asia'][4]),
			    'yzc' =>$this->goals_c($compareOdds[$key]['asia'][0] + $compareOdds[$key]['asia'][2], $compareOdds[$key]['asia'][3] + $compareOdds[$key]['asia'][5]),
			    'dhc' => $this->goals_c($compareOdds[$key]['bigsmall'][0], $compareOdds[$key]['bigsmall'][3]),
			    'dac' =>$this->goals_c($compareOdds[$key]['bigsmall'][2], $compareOdds[$key]['bigsmall'][5]),
			    'dpc' => $this->goals_c($compareOdds[$key]['bigsmall'][1], $compareOdds[$key]['bigsmall'][4]),
			    'dzc' =>$this->goals_c($compareOdds[$key]['bigsmall'][0] + $compareOdds[$key]['bigsmall'][2], $compareOdds[$key]['bigsmall'][3] + $compareOdds[$key]['bigsmall'][5]),
			    'ohc' => $this->goals_c($compareOdds[$key]['europ'][0], $compareOdds[$key]['europ'][3]),
			    'oac' =>$this->goals_c($compareOdds[$key]['europ'][2], $compareOdds[$key]['europ'][5]),
			    'opc' => $this->goals_c($compareOdds[$key]['europ'][1], $compareOdds[$key]['europ'][4]),
			    'ozc' =>$this->goals_c($compareOdds[$key]['europ'][0] + $compareOdds[$key]['europ'][2], $compareOdds[$key]['europ'][3] + $compareOdds[$key]['europ'][5]),
		    ];
	    }
	    return $goals;
    }
    
    
    
    /*
     * 数据分析即时赔率页面
    public function goals($id)
    {
        $ou = $this->eur($id);
        $_ou = array();
        $arr = ['macauslot', 'ＳＢ', 'bet365', 'easybets','澳彩','易胜博','sb','澳门'];
        $o_arr = ['macauslot', 'ＳＢ', 'bet365', 'easybets','澳彩','易胜博','sb','澳门'];
        foreach ($ou['oo'] as $key => $val) {
            if (!in_array($this->trimall($val[0]), $o_arr)) {
                unset($ou['oo'][$key]);
                continue;
            }
            if ($this->trimall($val[0]) == 'macauslot' || $this->trimall($val[0]) == '澳彩' || $this->trimall($val[0]) == '澳门') {
                $_ou[$key]['on'] = '澳彩';
            } elseif ($this->trimall($val[0]) == 'easybets' || $this->trimall($val[0]) == '易胜博') {
                $_ou[$key]['on'] = '易胜博';
            } elseif ($this->trimall($val[0]) == 'sb' || $this->trimall($val[0]) == 'ＳＢ') {
                $_ou[$key]['on'] = 'ＳＢ';
            }else {
                $_ou[$key]['on'] = $val[0];
            }

            $_ou[$key]['och'] = $val[2];
            $_ou[$key]['ocp'] = $val[3];
            $_ou[$key]['oca'] = $val[4];
            $_ou[$key]['ojh'] = $ou['oohis'][$key][0][0];
            $_ou[$key]['ojp'] = $ou['oohis'][$key][0][1];
            $_ou[$key]['oja'] = $ou['oohis'][$key][0][2];
        }
        $ya = $this->odds_goals($id, 1);
        $da = $this->odds_goals($id, 2);
        foreach ($_ou as $k => $v) {
            foreach ($ya as $kk => $vv) {
                unset($ya[$kk]['aohis']);
                if (!in_array($this->trimall($vv[0]), $arr)) {
                    unset($ya[$kk]);
                }
                if ($this->trimall($v['on']) == $this->trimall($vv[0])) {
                    $_ou[$k]['ych'] = $vv[1];
                    $_ou[$k]['ycp'] = $vv[2];
                    $_ou[$k]['yca'] = $vv[3];
                    $_ou[$k]['yjh'] = $vv[4];
                    $_ou[$k]['yjp'] = $vv[5];
                    $_ou[$k]['yja'] = $vv[6];
                    unset($ya[$kk]);
                }
            }
        }
        if ($ya) {
            foreach ($ya as $vv) {
                $num = count($_ou) + 1;
                $_ou[$num]['on'] = $vv[0];
                $_ou[$num]['ych'] = $vv[1];
                $_ou[$num]['ycp'] = $vv[2];
                $_ou[$num]['yca'] = $vv[3];
                $_ou[$num]['yjh'] = $vv[4];
                $_ou[$num]['yjp'] = $vv[5];
                $_ou[$num]['yja'] = $vv[6];
            }
        }
        foreach ($_ou as $k => $v) {
            foreach ($da as $kk => $vv) {
                unset($da[$kk]['aohis']);
                if (!in_array($this->trimall($vv[0]), $arr)) {
                    unset($da[$kk]);
                }
                if ($this->trimall($v['on']) == $this->trimall($vv[0])) {
                    $_ou[$k]['dch'] = $vv[1];
                    $_ou[$k]['dcp'] = $vv[2];
                    $_ou[$k]['dca'] = $vv[3];
                    $_ou[$k]['djh'] = $vv[4];
                    $_ou[$k]['djp'] = $vv[5];
                    $_ou[$k]['dja'] = $vv[6];
                    unset($da[$kk]);
                }
            }
        }
        if ($da) {
            foreach ($da as $vv) {
                $num = count($_ou) + 1;
                $_ou[$num]['on'] = $vv[0];
                $_ou[$num]['dch'] = $vv[1];
                $_ou[$num]['dcp'] = $vv[2];
                $_ou[$num]['dca'] = $vv[3];
                $_ou[$num]['djh'] = $vv[4];
                $_ou[$num]['djp'] = $vv[5];
                $_ou[$num]['dja'] = $vv[6];
            }
        }
        $res = $data = array();
        foreach ($_ou as $k => $v) {
            //欧赔数据处理
            $_ou[$k]['ohc'] = $this->goals_c($v['och'], $v['ojh']);
            $_ou[$k]['opc'] = $this->goals_c($v['ocp'], $v['ojp']);
            $_ou[$k]['oac'] = $this->goals_c($v['oca'], $v['oja']);
            //亚盘数据处理
            $_ou[$k]['yhc'] = $this->goals_c($v['ych'], $v['yjh']);
            $_ou[$k]['yac'] = $this->goals_c($v['yca'], $v['yja']);
            $_ou[$k]['ycz'] = (string)($v['ych'] + $v['yca']);
            $_ou[$k]['yjz'] = (string)($v['yjh'] + $v['yja']);
            $_ou[$k]['yzc'] = $this->goals_c($_ou[$k]['ycz'], $_ou[$k]['yjz']);
            $ycp = explode('/', ltrim($v['ycp'], '-'));
            $yjp = explode('/', ltrim($v['yjp'], '-'));
            $_ycp = $ycp[1] ? (string)(($ycp[0] + $ycp[1]) / 2) : (string)$ycp[0];
            $_yjp = $yjp[1] ? (string)(($yjp[0] + $yjp[1]) / 2) : $yjp[0];
            $_ou[$k]['ypc'] = $this->goals_c($_ycp, $_yjp);
            //大小数据处理
            $_ou[$k]['dhc'] = $this->goals_c($v['dch'], $v['djh']);
            $_ou[$k]['dac'] = $this->goals_c($v['dca'], $v['dja']);
            $dcp = explode('/', ltrim($v['dcp'], '-'));
            $djp = explode('/', ltrim($v['djp'], '-'));
            $_dcp = $dcp[1] ? (string)(($dcp[0] + $dcp[1]) / 2) : $dcp[0];
            $_djp = $djp[1] ? (string)(($djp[0] + $djp[1]) / 2) : $djp[0];
            $_ou[$k]['dpc'] = $this->goals_c($_dcp, $_djp);
            $res[$v['on']] = $_ou[$k];
        }
        if ($res['ＳＢ'] || $res['SB']) $res['ＳＢ']['cid'] = 3;
        if ($res['Bet 365']){
            $res['Bet 365']['cid'] = 8;
        }elseif ($res['bet 365']){
            $res['bet 365']['cid'] = 8;
        }
        if ($res['澳彩']) $res['澳彩']['cid'] = 1;
        if ($res['易胜博']) $res['易胜博']['cid'] = 12;
        $data = [
            0 => $res['ＳＢ'],
            1 => $res['Bet 365']?$res['Bet 365']:$res['bet 365'],
            2 => $res['澳彩'],
            3 => $res['易胜博']
        ];
        $data = array_filter($data);
        return $data;
    }

     */
    
    
    /*
     * 数据分析判断初盘即时变化,返回颜色class
     */
    public function goals_c($c, $j)
    {
        if ($c < $j) return 'text-red';
        if ($c > $j) return 'text-green';
        return null;
    }

    /*
     * 删除字符串中所有空格并全部转换小写
     */
    public function trimall($str)//删除空格
    {
        $qian = array(" ", "　", "\t", "\n", "\r");
        $hou = array("", "", "", "", "");
        return strtolower(str_replace($qian, $hou, $str));
    }

    /*
     * 即时赔率获取亚盘大小数据
     */
    public function odds_goals($gameId, $sign = 1)
    {

        $webfbService = new \Home\Services\WebfbService();
        if ($sign == 1) {
//			$changeChodds = $this->get_curl("/Home/Webfb/asianOdds", 'key=no&gameId='.$gameId, C('CURL_DOMAIN_QW'))['data'];
            $changeChodds = $webfbService->getAllOdds($gameId, 1);
            $aoAdds = $changeChodds['ao'];
            $aohisAdds = $changeChodds['aohis'];
        } else {
//			$changeChodds = $this->get_curl("/Home/Webfb/ballOdds", 'key=no&gameId='.$gameId, C('CURL_DOMAIN_QW'))['data'];
            $changeChodds = $webfbService->getAllOdds($gameId, 3);
            $aoAdds = $changeChodds['bo'];
            $aohisAdds = $changeChodds['bohis'];
        }


        $aohisArr = $changeTime = array();
        foreach ($aoAdds as $key => $value) {
            foreach ($aohisAdds as $k => $v) {
                //公司ID匹配
                if ($k == $key) {
                    foreach ($v as $hisK => $hisV) {
                        $hisData = explode('^', $hisV);
                        $hisData[3] = date('m-d H:i', strtotime($hisData[3]));
                        $aoAdds[$key]['aohis'][$hisK] = $hisData;
                    }
                }
            }
        }


        foreach ($aoAdds as $key => $value) {
            $homeTemp = $awayTemp = '';
            end($value['aohis']);
            while (!is_null($k = key($value['aohis']))) {
                $v = current($value['aohis']);
                //主队
                if ($homeTemp === '') {
                    $homeTemp = $v[0];
                    $aoAdds[$key]['homeColor'] = $aoAdds[$key]['aohis'][$k]['homeColor'] = 0;//0:黑色 1：红色 2：绿色

                } else {
                    if ($homeTemp == $v[0]) {
                        $aoAdds[$key]['homeColor'] = $aoAdds[$key]['aohis'][$k]['homeColor'] = 0;//0:黑色 1：红色 2：绿色
                    } else {
                        $aoAdds[$key]['homeColor'] = $aoAdds[$key]['aohis'][$k]['homeColor'] = $homeTemp > $v[0] ? 2 : 1;//0:黑色 1：红色 2：绿色
                    }
                    $homeTemp = $v[0];
                }
                //客队
                if ($awayTemp === '') {
                    $awayTemp = $v[2];
                    $aoAdds[$key]['awayColor'] = $aoAdds[$key]['aohis'][$k]['awayColor'] = 0;//0:黑色 1：红色 2：绿色
                } else {
                    if ($awayTemp == $v[2]) {
                        $aoAdds[$key]['awayColor'] = $aoAdds[$key]['aohis'][$k]['awayColor'] = 0;//0:黑色 1：红色 2：绿色
                    } else {
                        $aoAdds[$key]['awayColor'] = $aoAdds[$key]['aohis'][$k]['awayColor'] = $awayTemp > $v[2] ? 2 : 1;//0:黑色 1：红色 2：绿色
                    }

                    $awayTemp = $v[2];
                }

                prev($value['aohis']);
            }

        }
        return $aoAdds;
    }

    /**
     * @author liuweitao <906742852@qq.com>
     * @DateTime 2017-1-19
     * 处理杯赛积分排行
     */
    public function cupmatch_integral($arr)
    {
        foreach ($arr as $key => &$val) {
            if (!is_numeric($val[0])) {
                unset($arr[$key]);
            }
            $val[] = strval($val[6] - $val[7]);

        }
        return $arr;
    }

    /**
     * @author liuweitao <906742852@qq.com>
     * @DateTime 2017-1-19
     * 处理联赛盘路
     */
    public function match_panlu($arr)
    {
        if (!$arr) return NULL;
        $_type = array('总', '主场', '客场', '近6场', '总', '主场', '客场', '近6场');
        $_num = 0;
        $_arr = array();
        for ($a = 0; $a < 8; $a++) {
            if ($_type[$a] == $arr[$_num][1]) {
                $_arr[] = $arr[$_num];
                $_num++;
            } else {
                $_arr[] = '';
            }
        }
        $tmp = $this->_array($_arr);
        array_pop($tmp);
        $res = $this->_match_col($_arr);
        $list['list'] = $tmp;
        $list['res'] = $res;
        return $list;
    }

    /*
     * 联赛盘路特殊合并
     */
    public function match_panlu_arr($arr)
    {
        $_type = array('总', '主场', '客场', '近6场', '总', '主场', '客场', '近6场');
        $_num = 0;
        $_arr = array();
        for ($a = 0; $a < 8; $a++) {
            if ($_type[$a] == $arr[$_num][1]) {
                $_arr[] = $arr[$_num];
                $_num++;
            } else {
                $_arr[] = '';
            }
        }
        $count = ceil(count($_arr) / 2);
        $tmp = array();
        for ($i = 0; $i < $count; $i++) {
            for ($j = 0; $j < count($arr[0]); $j++) {
                $tmp[$i][] = $_arr[$i * 1][$j];
                $tmp[$i][] = $_arr[$i * 1 + $count][$j];
            }
        }
        return $tmp;
    }

    /**
     * @author liuweitao <906742852@qq.com>
     * @DateTime 2017-1-19
     * 处理联赛盘路颜色
     */
    public function _match_col($arr)
    {
        $res['home'] = $arr[3];
        $res['away'] = $arr[7];
        foreach ($res as $key => &$val) {
            preg_match_all('/[\x{4e00}-\x{9fa5}]/u', $val[3], $win);
            foreach ($win[0] as $k => $v) {
                if ($v == '赢') {
                    $res[$key]['win'][$k][] = '赢';
                    $res[$key]['win'][$k][] = 'text-red';
                } elseif ($v == '输') {
                    $res[$key]['win'][$k][] = '输';
                    $res[$key]['win'][$k][] = 'text-green';
                } elseif ($v == '走') {
                    $res[$key]['win'][$k][] = '走';
                    $res[$key]['win'][$k][] = 'text-blue';
                }
            }
            preg_match_all('/[\x{4e00}-\x{9fa5}]/u', $val[6], $ball);
            foreach ($ball[0] as $k => $v) {
                if ($v == '大') {
                    $res[$key]['ball'][$k][] = '大';
                    $res[$key]['ball'][$k][] = 'text-red';
                } elseif ($v == '小') {
                    $res[$key]['ball'][$k][] = '小';
                    $res[$key]['ball'][$k][] = 'text-green';
                } elseif ($v == '走') {
                    $res[$key]['ball'][$k][] = '走';
                    $res[$key]['ball'][$k][] = 'text-blue';
                }
            }
        }
        return $res;
    }

    /**
     * @author liuweitao <906742852@qq.com>
     * @DateTime 2017-1-19
     * 处理联赛积分
     */
    public function match_integral($arr)
    {
        foreach ($arr as &$val) {
            $val[10] = number_format($val[3] / $val[2], 3, '.', '') * 100;

        }
        $quan = array_slice($arr, 0, 8);
        $ban = array_slice($arr, 8, 8);
        $_q = $this->_array($quan);
        foreach ($_q as $k => $v) {
            $_q[$k][] = strval($v[12]) - strval($v[14]);
            $_q[$k][] = strval($v[13]) - strval($v[15]);
        }
        $_b = $this->_array($ban);
        foreach ($_b as $k => $v) {
            $_b[$k][] = strval($v[12]) - strval($v[14]);
            $_b[$k][] = strval($v[13]) - strval($v[15]);
        }
        $tmp = [0 => $_q, 1 => $_b];
        return $tmp;
    }

    /**
     * @author liuweitao <906742852@qq.com>
     * @DateTime 2017-1-19
     * 数组处理
     */
    public function _array($arr)
    {
        $count = ceil(count($arr) / 2);
        $tmp = array();
        for ($i = 0; $i < $count; $i++) {
            for ($j = 0; $j < count($arr[0]); $j++) {
                $tmp[$i][] = $arr[$i * 1][$j];
                $tmp[$i][] = $arr[$i * 1 + $count][$j];
            }
        }
        return $tmp;
    }

    /**
     * @author liuweitao <906742852@qq.com>
     * @DateTime 2017-1-19
     * 处理过往对阵
     */
    public function match_fight($arr, $home_id)
    {
        $total = 0;
        $win = 0;
        $fail = 0;
        $draw = 0;
        $win_pan = 0;
        $total_none = 0;
        $win_none = 0;
        $fail_none = 0;
        $draw_none = 0;
        $win_pan_none = 0;
        $union = array();
        foreach ($arr as $v) {
            $none = false;
            $total++;
            if ($v[4] == $home_id) {
                $v['home_c'] = 'text-red';
                $total_none++;
                $none = true;
                if ($v[14] == 1) $v['y_home_c'] = 'text-red';
                if ($v[14] == -1) $v['y_away_c'] = 'text-red';
            }
            if ($v[6] == $home_id) {
                $v['away_c'] = 'text-red';
                if ($v[14] == 1) $v['y_away_c'] = 'text-red';
                if ($v[14] == -1) $v['y_home_c'] = 'text-red';
            }
            $v['sb'] = $this->_sb($v[12], 1);
            if ($v[14] == 1) {
                $win_pan++;
                if ($none) $win_pan_none++;
            }
            $pan_ball = array();
//            if ($v[16] != '' && $v[17] && $v[18] && $v[19] && $v[20]) $pan_ball = $this->_ball($v[15], $v[14]);
            $pan_ball = $this->_ball($v[15], $v[14]);
            $v['pan'] = $pan_ball[2];
            $v['pan_col'] = $pan_ball[3];
            $v['ball'] = $pan_ball[0];
            $v['ball_col'] = $pan_ball[1];
            if ($v[13] == 1) {
                $win++;
                if ($none) $win_none++;
            } elseif ($v[13] == -1) {
                $fail++;
                if ($none) $fail_none++;
            } else {
                $draw++;
                if ($none) $draw_none++;
            }
            $is_home = 1;
            if (trim($home_id) != trim($v[4])) $is_home = -1;
            $_q = $this->_compare($v[8], $v[9], $is_home);
            $v['h_col'] = $_q[0];
            $v['a_col'] = $_q[1];
            $v['h_col_s'] = $_q[2];
            $v['a_col_s'] = $_q[3];
            $v['p_col_s'] = $_q[4];
            $v['h_col_f'] = $_q[5];
            $v['a_col_f'] = $_q[6];
            $v['p_col_f'] = $_q[7];
            $v['game_res'] = $_q['game_res'];
            $v['game_res_col'] = $_q['game_res_col'];
            $_b = $this->_compare($v[10], $v[11]);
            $v['h_col_b'] = $_b[0];
            $v['a_col_b'] = $_b[1];
            $v[0] = trim($v[0]);
            $v[6] = trim($v[6]);
            $v[7] = trim($v[7]);
            if (!empty($v[16])) $v[16] = number_format($v[16], 2);
            if (!empty($v[17])) $v[17] = number_format($v[17], 2);
            if (!empty($v[18])) $v[18] = number_format($v[18], 2);
            if($v[19] == '' || $v[20] =='') $v['sb'] = '';
            Array_unshift($v, $v[5]);
            $tmp['list'][] = $v;
            $union[] = $v[3];
        }
        $tmp['union'] = array_unique($union);
        $tmp['data']['total'] = $total;//总赛事数量
        $tmp['data']['win'] = $win;//赢场数量
        $tmp['data']['fail'] = $fail;//输场数量
        $tmp['data']['draw'] = $draw;//平局数量
        $tmp['data']['win_pan'] = $win_pan;//赢盘数量
        $tmp['data']['win_pan_per'] = intval(round($win_pan / $total, 3) * 100);//赢盘胜率
        $tmp['data']['win_per'] = intval(round($win / $total, 3) * 100);//胜场胜率
        $tmp['data']['draw_per'] = intval(round($draw / $total, 3) * 100);//平局胜率
        $tmp['data']['fail_per'] = 100 - $tmp['data']['win_per'] - $tmp['data']['draw_per'];//输场胜率
        $tmp['data']['total_none'] = $total_none;//总赛事数量-隐藏
        $tmp['data']['win_none'] = $win_none;//赢场数量-隐藏
        $tmp['data']['fail_none'] = $fail_none;//输场数量-隐藏
        $tmp['data']['draw_none'] = $draw_none;//平局数量-隐藏
        $tmp['data']['win_pan_none'] = $win_pan_none;//赢盘数量-隐藏
        $tmp['data']['win_pan_per_none'] = intval(round($win_pan_none / $total_none, 3) * 100);//赢盘胜率-隐藏
        $tmp['data']['win_per_none'] = intval(round($win_none / $total_none, 3) * 100);//胜场胜率-隐藏
        $tmp = $this->total_list($tmp);
        if(I('var') == 'dump')
        {
            echo '<pre>';
            print_r($arr);
            echo '</pre>';

            echo '<pre>';
            print_r($tmp);
            echo '</pre>';
        }
//        var_dump($tmp);
        return $tmp;
    }

    /*
     * 统计数组大小
     */
    public function total_list($data)
    {
        $size = count($data['list']);
        if ($size <= 5) {
            $data['data']['mix'] = $size;
            $data['data']['mix_arr'] = [0 => $size];
        } elseif (5 < $size && $size <= 10) {
            $data['data']['mix'] = $size;
            $data['data']['mix_arr'] = [0 => 5, 1 => $size];
        } elseif (10 < $size) {
            $data['data']['mix'] = $size;
            $data['data']['mix_arr'] = [0 => 5, 1 => 10, 2 => $size];
        }
        return $data;
    }
    
    public function future_three($array, $union_color)
    {
    	$data = [];
        foreach ($array as $key => $value) {
	        $temp = [];
	        $temp['union_name'] = $value[1];
	        $temp['date'] = $value[0];
	        $temp['home_team'] = trim(explode('-', $value[2])[0]);
	        $temp['away_team'] = trim(explode('-', $value[2])[1]);
	        $temp['days'] = $value[5];
	        $temp['union_color'] = $union_color;
	        $data[] = $temp;
        }
        return $data;
    }

    /**
     * @author liuweitao <906742852@qq.com>
     * @DateTime 2017-1-19
     * 整理未来三场赛事
     */
    public function match_three($arr)
    {
        foreach ($arr as &$val) {
            switch ($val[3]) {
                case '主';
                    $val[6] = $val[0];
                    $val[7] = $val[4];
                    break;
                case '客';
                    $val[7] = $val[0];
                    $val[6] = $val[4];
                    break;
            }
            $val[8] = $this->union_color($val[1]);
        }
        $arr = $this->_array($arr);
        return $arr;
    }

    //通过联赛名获取联赛背景颜色
    public function union_color($name)
    {
        $map['union_name'] = array('like',"$name%");
        $data = M('Union')->field('union_name,union_color')->where($map)->select();
        $color = '';
        foreach($data as $val)
        {
            $union_name = explode(',',$val['union_name'])[0];
            if($union_name == $name)
            {
                $color = $val['union_color'];
                break;
            }
        }
        return $color;
    }

    /**
     * @author liuweitao <906742852@qq.com>
     * @DateTime 2017-1-19
     * 处理近期赛事
     */
    public function recent_fight($arr)
    {
        $tmp = array();
        foreach ($arr as $key => $val) {
            $total = 0;
            $win = 0;
            $fail = 0;
            $draw = 0;
            $win_pan = 0;
            $total_none = 0;
            $win_none = 0;
            $fail_none = 0;
            $draw_none = 0;
            $win_pan_none = 0;
            $union = array();
            foreach ($val['content'] as $k=>$v) {
				//去除主队或者客队没有数据的赛事
                if(empty($v[6]) || empty($v[8]))
                {
                    unset($val['content'][$k]);
                    continue;
                }
//                $name = mb_substr($v[0], 0, 3, 'utf-8');
	            $home_id = $v[0];
                $total++;
                $none = "n";
                if ($v[5] == $home_id) {
                    $v['home_c'] = 'text-red';
                    if ($v[16] == 1) $v['y_home_c'] = 'text-red';
                    if ($v[16] == -1) $v['y_away_c'] = 'text-red';
                    if ($key === 0) {
                        $none = "y";
                        $total_none++;
                    }
                }
                if ($v[7] == $home_id) {
                    $v['away_c'] = 'text-red';
                    if ($v[16] == 1) $v['y_away_c'] = 'text-red';
                    if ($v[16] == -1) $v['y_home_c'] = 'text-red';
                    if ($key === 1) {
                        $none = "y";
                        $total_none++;
                    }
                    $is_home = 0;
                }else{
                    $is_home = 1;
                }
                if($v[13] == '')
                {
                    $v[20] = $v[21] = '';
                }else{
                    $v['sb'] = $this->_sb($v[13], 1);
                }
                if ($v[15] == 1) {
                    $win_pan++;
                    if ($none == "y") $win_pan_none++;
                }
                $pan_ball = $this->_ball($v[16], $v[15]);
                $v['pan'] = $pan_ball[2];
                $v['pan_col'] = $pan_ball[3];
                $v['ball'] = $pan_ball[0];
                $v['ball_col'] = $pan_ball[1];
                if ($v[14] == 1) {
                    $win++;
                    if ($none == "y") $win_none++;
                } elseif ($v[14] == -1) {
                    $fail++;
                    if ($none == "y") $fail_none++;
                } else {
                    $draw++;
                    if ($none == "y") $draw_none++;
                }
                if ($v[7] == $v[0]) $is_home = -1;
                $_q = $this->_compare($v[9], $v[10], $is_home);
                $v['h_col'] = $_q[0];
                $v['a_col'] = $_q[1];
                $v['h_col_s'] = $_q[2];
                $v['a_col_s'] = $_q[3];
                $v['p_col_s'] = $_q[4];
                $v['h_col_f'] = $_q[5];
                $v['a_col_f'] = $_q[6];
                $v['p_col_f'] = $_q[7];
                $v['game_res'] = $_q['game_res'];
                $v['game_res_col'] = $_q['game_res_col'];
                if (!empty($v[19])) $v[19] = number_format($v[19], 2);
                if (!empty($v[17])) $v[17] = number_format($v[17], 2);
                if (!empty($v[18])) $v[18] = number_format($v[18], 2);
                $_b = $this->_compare($v[11], $v[12],$is_home);
                $v['h_col_b'] = $_b[0];
                $v['a_col_b'] = $_b[1];
                $v[0] = trim($v[0]);
                $v[6] = trim($v[6]);
                $v[8] = trim($v[8]);
                $union[] = $v[3];
                if($v[20] == '' || $v[21] == '') $v['sb'] = '';
                $tmp[$key]['list'][] = $v;
            }
            $tmp[$key]['union'] = array_unique($union);
            $tmp[$key]['home'] = $val['content'][0][0];
            $tmp[$key]['data']['total'] = $total;//总赛事数量
            $tmp[$key]['data']['win'] = $win;//赢场数量
            $tmp[$key]['data']['fail'] = $fail;//输场数量
            $tmp[$key]['data']['draw'] = $draw;//平局数量
            $tmp[$key]['data']['win_pan'] = $win_pan;//赢盘数量
            $tmp[$key]['data']['win_pan_per'] = intval(round($win_pan / $total, 3) * 100);//赢盘胜率
            $tmp[$key]['data']['win_per'] = intval(round($win / $total, 3) * 100);//胜场胜率
            $tmp[$key]['data']['total_none'] = $total_none;//总赛事数量-隐藏
            $tmp[$key]['data']['win_none'] = $win_none;//赢场数量-隐藏
            $tmp[$key]['data']['fail_none'] = $fail_none;//输场数量-隐藏
            $tmp[$key]['data']['draw_none'] = $draw_none;//平局数量-隐藏
            $tmp[$key]['data']['win_pan_none'] = $win_pan_none;//赢盘数量-隐藏
            $tmp[$key]['data']['win_pan_per_none'] = intval(round($win_pan_none / $total_none, 3) * 100);//赢盘胜率-隐藏
            $tmp[$key]['data']['win_per_none'] = intval(round($win_none / $total_none, 3) * 100);//胜场胜率-隐藏
            $tmp[$key] = $this->total_list($tmp[$key]);
        }
        return $tmp;
    }

    /**
     * @author liuweitao <906742852@qq.com>
     * @DateTime 2017-1-19
     * 往期赛事盘路与大小对比
     */
    public function _ball($ball, $pan)
    {
        $arr = array();
        if ($ball == 1) {
            $arr[0] = '大';
            $arr[1] = 'text-red';
        } elseif ($ball == -1) {
            $arr[0] = '小';
            $arr[1] = 'text-green';
        }
        if ($pan == 1) {
            $arr[2] = '赢';
            $arr[3] = 'text-red';
        } elseif ($pan == -1) {
            $arr[2] = '输';
            $arr[3] = 'text-green';
        } elseif ($pan == 0) {
            $arr[2] = '走';
            $arr[3] = 'text-blue';
        }
        return $arr;
    }

    /**
     * @author liuweitao <906742852@qq.com>
     * @DateTime 2017-1-19
     * 数据对比赋值颜色
     */
    public function _compare($fir, $sen, $is_home = 1)
    {
        $arr = array();
        if ($fir > $sen) {
            $arr[0] = $arr[2] = 'text-red';
            $arr[1] = 'text-blue';
            $arr[5] = 'bold';
            if ($is_home == 1) {
                $arr['game_res'] = '赢';
                $arr['game_res_col'] = 'text-red';
            } else {
                $arr['game_res'] = '输';
                $arr['game_res_col'] = 'text-green';
            }
        } elseif ($fir < $sen) {
            $arr[0] = 'text-blue';
            $arr[1] = $arr[3] = 'text-red';
            $arr[6] = 'bold';
            if ($is_home == 1) {
                $arr['game_res'] = '输';
                $arr['game_res_col'] = 'text-green';
            } else {
                $arr['game_res'] = '赢';
                $arr['game_res_col'] = 'text-red';
            }
        } else {
            $arr[0] = 'text-blue';
            $arr[1] = 'text-blue ';
            $arr[4] = 'text-red';
            $arr[7] = 'bold';
            $arr['game_res'] = '平';
            $arr['game_res_col'] = 'text-blue';
        }
        return $arr;
    }

    /**
     *推荐统计页面
     */
    public function gambleDetails()
    {
        $game_type = 1;
        $gameId = I('game_id', 0, 'int');
        $play_type = I('play_type', 1, 'int');
        $p = I('p', 1, 'int');
        if ($gameId < 1) {
            $this->error();
        }
        //获取该赛事玩法的推荐列表
        $QuizUser = $this->getUserRank($gameId, $play_type,$game_type);
        $newCount = count($QuizUser);
        $limit = 20;
        if ($newCount > 0) {
            //登陆用户ID
            $userId = is_login();
            //分页
            $QuizUser = array_slice($QuizUser, ($p - 1) * $limit, $limit);

            foreach ($QuizUser as $k => $v) {
                //判断语音推介是否通过
                if ($v['is_voice'] == 1) {
                    if ($v['voice']) {
                        $QuizUser[$k]['voice'] = Think\Tool\Tool::imagesReplace($v['voice']);
                    }
                } else {
                    $QuizUser[$k]['voice'] = '';
                }

                //判断推介分析是否通过
                if ($v['desc_check'] != 1) {
                    $QuizUser[$k]['desc'] = '';
                }
            }
            $gambleIdArr = array_map("array_shift", $QuizUser);
            if ($userId) {
                //是否已被查看
                $quizLog = M('quizLog')->master(true)->where(array('game_type' => $game_type, 'user_id' => $userId, 'gamble_id' => ['in', $gambleIdArr]))->getField('gamble_id', true);
                foreach ($QuizUser as $k => $v) {
                    if (in_array($v['id'], $quizLog)) {
                        $QuizUser[$k]['is_check'] = 1;
                    }
                }
            }

            //获取用户ID，并获取用户的粉丝数
            $userIdArr = array();
            foreach ($QuizUser as $key => $value) {
                $userIdArr[] = $value['user_id'];
                //周，月，季胜率
                $QuizUser[$key]['weekWin'] = D('GambleHall')->CountWinrate($value['user_id'], $game_type, 1, false, false, 0, $play_type);
            }
            $followUser = M('FollowUser')->where(['follow_id' => ['in', $userIdArr]])->field('follow_id,count(id) as FollowNumber')->group('follow_id')->select();
            if (!empty($followUser)) {
                foreach ($QuizUser as $key => $value) {
                    foreach ($followUser as $k => $v) {
                        if ($value['user_id'] == $v['follow_id']) {
                            $QuizUser[$key]['FollowNumber'] = $v['FollowNumber'];
                        }
                    }
                }
            }
            //获取我关注的人
            $followIdArr = M("FollowUser")->where(array('user_id' => $userId))->field("follow_id")->select();
            foreach ($followIdArr as $key => $value) {
                $followIds[] = $value['follow_id'];
            }
            $this->assign('followIds', $followIds);
            $this->assign('play_type', $play_type);
            $this->assign('userId', $userId);
            //分页
            $page = new \Think\Page ( $newCount, $limit );
            $page->config  = array(
                'header' => '<span class="rows">共 %TOTAL_ROW% 条记录</span>',
                'prev'   => '<span aria-hidden="true">上一页</span>',
                'next'   => '<span aria-hidden="true">下一页</span>',
                'first'  => '首页',
                'last'   => '...%TOTAL_PAGE%',
                'theme'  => '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%',
            );
            $page->url = "/gambleDetails/game_id/{$gameId}/play_type/{$play_type}.html?p=%5BPAGE%5D";
            $this->assign ( "newCount", $newCount);
            $this->assign ( "limit", $limit);
            $this->assign ( "show", $page->showJs());

            foreach($QuizUser as $k => $v){
                if($v['weekWin'] < 65){
                    unset($QuizUser[$k]);
                }
            }

            $this->assign('QuizUser', $QuizUser);
        }
        //获取头部
        $this->oddsHeader($gameId);

        $this->display();
    }

    /**
     * 获取参与赛程推荐的用户记录
     *
     * @param int $game_id 赛程id
     * @param int $play_type 玩法(1:让分 -1:大小) 为篮球时(1:全场让分 -1:全场大小 2:半场让分 -2:半场大小)
     * @param int $game_type 赛事类型 1:足球  2:篮球
     *
     * @return  array
     */
    public function getUserRank($game_id, $play_type, $game_type = 1)
    {
        $gameModel = $game_type == 1 ? M('gamble g') : M('gamblebk g');
        $gambleWhere['g.game_id'] = $game_id; 
        switch ($game_type) {
            case '1':
                switch ($play_type) {
                    case '1':
                    case '-1':
                        $Lv = 'lv';
                        $gambleType = 1;
                        $gambleWhere['g.play_type'] = ['in',[1,-1]];
                        $tenStr = 'fb_ten_gamble';
                        break;
                    case '2':
                    case '-2':
                        $Lv = 'lv_bet';
                        $gambleType = 2;
                        $gambleWhere['g.play_type'] = ['in',[2,-2]];
                        $tenStr = 'fb_ten_bet';
                        break;
                }
                break;
            case '2':
                $Lv = 'lv_bk';
                $gambleWhere['g.play_type'] = $play_type;
                $tenStr = 'bk_ten_gamble';
                break;
        }   

        //获取参与该赛程推荐的记录
        $gamble = $gameModel
            ->join("left join qc_front_user f on f.id=g.user_id")
            ->where($gambleWhere)
            ->field("g.id,g.game_id,g.user_id,g.is_impt,g.union_name,g.home_team_name,g.away_team_name,g.result,g.play_type,g.chose_side,g.handcp,g.odds,g.tradeCoin,g.desc,g.desc_check,g.create_time,g.voice,g.is_voice,g.voice_time,(g.quiz_number + g.extra_number) as quiz_number,f.nick_name,f.{$Lv} lv,f.head,f.fb_ten_gamble,f.fb_ten_bet,f.bk_ten_gamble")
            ->select();

        if (!$gamble) {
            return;
        }
        $gamble = HandleGamble($gamble, 0, true, $game_type);
        foreach ($gamble as $k => $v) {
            $gamble[$k]['ten_rate'] = $v[$tenStr];
        }
        //分开付费和免费
        $freeGamble = array();
        $payGamble = array();
        foreach ($gamble as $k => $v) {
            if ($v['tradeCoin'] == 0) {
                $freeGamble[] = $v; //免费
            } else {
                $payGamble[] = $v;  //付费
            }
        }

        //付费排序
        $payGamble = $this->sortGamble($payGamble);
        //免费排序
        $freeGamble = $this->sortGamble($freeGamble);
        //付费与免费合并
        $rankArr = array_merge_recursive($payGamble, $freeGamble);

        return $rankArr;
    }

    //排序 "等级＞周胜率＞该场销量＞发布时间" 排序
    public function sortGamble($Gamble)
    {
        foreach ($Gamble as $k => $v) {
            $sort_lv[]     = $v['lv'];        //等级
            $sort_rate[]   = $v['ten_rate'];  //周胜率
            $quiz_number[] = $v['quiz_number']; //该场销量
            $sort_time[]   = $v['create_time']; //发布时间
        }
        array_multisort($sort_lv, SORT_DESC, $sort_rate, SORT_DESC, $quiz_number, SORT_DESC, $sort_time, SORT_DESC, $Gamble);
        return $Gamble;
    }

    //充值页面
    public function recharge()
    {
        $userId = is_login();
        if ($userId) {
            $coin = M('FrontUser')->field("sum(coin+unable_coin) as coin")->where(['id' => $userId])->find();
            $this->assign('coin', $coin['coin']);
        }
        $this->display();
    }

    public function live()
    {
        $game_id = I('game_id', 0, 'int');//赛事id
        if ($game_id < 0) {
            $this->error();
        }
	    $mongodb  = mongoService();
	    $textSkill = $mongodb->select("fb_game", ['game_id' => $game_id], ['game_starttime','home_team_id', 'home_team_name','away_team_id', 'away_team_name', 'union_name', 'game_start_timestamp', 'start_time','game_state', 'score','tc', 'corner_sb', 'detail'])[0];
	    $tc = $textSkill['tc'];
     
//        $where['game_id'] = $game_id;
        //3:射门次数,4:射正次数,5:犯规次数,6:角球次数,7:角球次数(加时),8:任意球次数,9:越位次数,11:黄牌数,12:黄牌数(加时),13:红牌数,14:控球时间,44:危险进攻
//        $where['s_type'] = ['in', [3, 4, 5, 6, 7, 8, 9, 11, 12, 13, 14, 44]];
//        $game_info = M("StatisticsFb")->where($where)->getField('s_type as type,home_value as home,away_value as away');
        //角球
        $game['corner']['home'] = $tc[6][0];
        $game['corner']['away'] = $tc[6][1];
	    //射门次数
        $game['shoot']['home'] = $tc[3][0];
        $game['shoot']['away'] = $tc[3][1];
        //射中次数
        $game['quiver']['home'] = $tc[4][0];
        $game['quiver']['away'] = $tc[4][1];
        //犯规次数
        $game['foul']['home'] = $tc[5][0];
        $game['foul']['away'] = $tc[5][1];
        //任意球次数
        $game['freekick']['home'] = $tc[8][0];
        $game['freekick']['away'] = $tc[8][1];
        //越位次数
        $game['offside']['home'] = $tc[9][0];
        $game['offside']['away'] = $tc[9][1];
        //危险进攻
        $game['dangerous']['home'] = $tc[44][0];
        $game['dangerous']['away'] = $tc[44][1];
        foreach ($game as $key => $val) {
            if ($val['home']) {
                $game[$key]['percent'] = intval((int)$val['home'] * 100 / ((int)$val['home'] + (int)$val['away']));
                $game[$key]['percent_other'] = 100 - $game[$key]['percent'];
                $game[$key]['color_other'] = 1;
            } else {
                $game[$key]['home'] = 0;
                $game[$key]['away'] = 0;
                $game[$key]['percent'] = 50;
                $game[$key]['home_p'] = 0;
                $game[$key]['away_p'] = 0;
            }
        }
        //黄牌
        $game['y_card']['home'] = $tc[11][0];
        $game['y_card']['away'] = $tc[11][1];
        //红牌
        $game['r_card']['home'] = $tc[13][0];
        $game['r_card']['away'] = $tc[13][1];
        //控球率
        $game['hold']['home'] = $tc[14][0];
        $game['hold']['away'] = $tc[14][1];
        $game['hold']['home_num'] = $game['hold']['home']?rtrim($game['hold']['home'],'%')>15?rtrim($game['hold']['home'],'%'):15:0;
        $game['hold']['away_num'] = $game['hold']['away']?rtrim($game['hold']['away'],'%')>15?rtrim($game['hold']['away'],'%'):15:0;

        $team_info = M("GameFbinfo")->field('bet_code,web_video,weather,update_time')->where(['game_id' => $game_id])->find();
	    $team_info['home_team_id'] = $textSkill['home_team_id'];
	    $team_info['away_team_id'] = $textSkill['away_team_id'];
	    $team_info['home_team_name'] = $textSkill['home_team_name'][0];
	    $team_info['away_team_name'] = $textSkill['away_team_name'][0];
	    $team_info['union_name'] = $textSkill['union_name'][0];
	    $team_info['game_state'] = $textSkill['game_state'];
	    $start_time = $textSkill['start_time'];
	    $detail = null;
	    if (!empty($textSkill['detail']) || !empty($textSkill['corner_sb'][3][1])) {
			$detail = $textSkill['detail'];
			$server = new \Home\Services\AppfbService();
		    $det = $server->getDetail($textSkill['corner_sb'][3], $detail, $game_id, $textSkill['home_team_id'], $textSkill['away_team_id']);
		    $sort = [];
		    foreach ($det as $k => $v) {
		    	$sort[$k] = $v[3];
		    }
		    array_multisort($sort, SORT_DESC, $det);
	    }
	    if (!empty($start_time)) {
	    	$date = date('Y-m-d', $textSkill['game_start_timestamp']);
	    	$time = $start_time;
	    	$team_info['gtime'] = strtotime($date.$time);
	    } else {
		    $team_info['gtime'] = $textSkill['game_start_timestamp'];
	    }
	    $team_info['score'] = $textSkill['score'];
        
        $team_info = getTeamLogo($team_info);
        $game = array_merge($game, $team_info);
        //视频列表
        $videolist = json_decode($team_info['web_video'], true);
        foreach($videolist as $k=>$v)
        {
            if($v['weburl'] == '')
            {
                unset($videolist[$k]);
                continue;
            }
            if(strpos($v['weburl'],'365.com') !== false)
            {
                $videolist[$k]['url_type'] = 1;
                $url = htmlspecialchars_decode($v['weburl']);
                $url = str_replace('width=1280','width=750',$url);
                $url = str_replace('height=700','height=480',$url);
                $videolist[$k]['weburl'] = $url;
            }else{
                $videolist[$k]['url_type'] = 0;
            }
        }
        array_merge($videolist);
        $this->assign('videolist',$videolist);
        //获取赛事直播源默认取第一个
        $is_player = 0;
        $game['video_url'] = reset($videolist)['weburl'];
        if($game['video_url']) $is_player = 1;//外链播放地址
        if(strpos($game['video_url'],'.m3u8') || strpos($game['video_url'],'.flv') || strpos($game['video_url'],'.mp4') || strpos($game['video_url'],'rtmp')) $is_player = 2; //内嵌播放地址

        $this->assign('is_player',$is_player);
//        $game['url_type'] = reset($videolist)['url_type'];
        //获取赛事列表
        $game_list = M("GameFbinfo")->where(['is_video' => 1, 'game_state' => ['gt', 0], 'status' => 1])->order('gtime desc')->limit(10)->getField('game_id,home_team_name,away_team_name', true);
        foreach ($game_list as $k => $v) {
            if (
                ($v['gtime'] + 60 < time() && $v['game_state'] == 0)  //过了开场时间未开始
                || ($v['game_state'] == -14 || $v['game_state'] == -11)  //屏蔽待定和推迟
                || ($v['gtime'] + 8400 < time() && array_search($v['game_state'], [1, 2, 3, 4]) !== false) //140分钟还没结束
            ) {
                unset($game[$k]);
            }
        }

        //是否有动画直播
        $flashList = M("GameFbinfo")->where(['game_id' => $game_id, 'game_state' => ['IN', [0, 1, 2, 3, 4]]])->order('update_time desc')->find();

        $linkRes = (new GambleHallMongo())->getFbLinkbet($game_id);

        $flashUrl = '';
//        if(intval($flashList['game_state']) != 0 && $linkRes){
            $flashUrl = C('dh_host') . '/svg-f-animate.html?game_id=' . $game_id;
//        }

        //用户信息
        $uinfo = M('FrontUser')->field('id as user_id,nick_name,head,lv,lv_bet,lv_bk,coin,unable_coin,status')->where(['id' => is_login()])->find();
        if ($uinfo) {
            $uinfo['head'] = frontUserFace($uinfo['head']);

            //状态是否被禁用
            if ($uinfo['status'] != 1)
                $userStatus = -1;//您的账号被管理员屏蔽了

            //判断是否被屏蔽、踢出
            $forbid = M('ChatForbid')->where(['user_id' => $uinfo['user_id'], 'status' => ['IN', [1, 3]]])->order('id DESC')->find();
            if ($forbid) {
                if ($forbid['type'] == 1) {
                    $userStatus = -2;//您被管理员屏蔽了聊天功能
                } else if ($forbid['type'] == 3) {
                    if (NOW_TIME < $forbid['operate_time'] + 600) {
                        $userStatus = -3;//您已被管理员限时禁言
                    }
                } else if ($forbid['type'] == 2) {
                    if ($forbid['status'] == 1) {
                        $userStatus = -2;
                    } else {
                        if (NOW_TIME < $forbid['operate_time'] + 600) {
                            $userStatus = -3;//您已被管理员限时禁言
                        }
                    }
                }
            }
        }

        //聊天室开启时间
        $status = 1;
        if ($team_info['gtime'] - 3600 * 6 >= time()) {
            $status = '-1';
        } elseif ($team_info['game_state'] == '-1' && $team_info['update_time'] + 3600 * 3 <= time()) {
            $status = '-2';
        }

        //mqtt 配置
        $mqtt = C('Mqtt');
        $game['game_list'] = $game_list;
        if((int)$game['gtime'] >= time())
        {
            $this->assign('CountDown',1);
            $timeDiff = ((int)$game['gtime']- time() + 10)*1000;
            $this->assign('timeDiff',$timeDiff);
        }
        //查询当前赛事是否含有美女直播
        $liveList = M('LiveLog lg')->field('lg.id,lg.room_id,lg.img,lg.start_time,lg.live_status,fu.id,fu.nick_name')->join('LEFT JOIN qc_front_user fu ON fu.id = lg.user_id')->where(['lg.game_id'=>$game_id,'lg.live_status'=>['gt',0],'lg.status'=>1])->select();
        $is_live = $now_live = 0;
        if($liveList){
            $sort = [];
            foreach($liveList as $key=>$val){
                $liveList[$key]['live_url'] = D('Live')->getLiveUrl($val['room_id'], $val['start_time']);
                $liveList[$key]['mqtt_room_topic'] = 'qqty/live_' . $val['room_id'] . '/#';//mqtt room topic
                $liveList[$key]['img'] = (string)Tool::imagesReplace($val['img']);
                $sort[] = $val['live_status'];
            }
            array_multisort($sort,SORT_ASC,$liveList);
            $this->assign('liveList',$liveList);
            //判断是不是进入就切换美女直播
            $is_live = 1;
            if(I('is_live') ==1) $now_live = 1;
        }
        if($is_player > 0 && $liveList) {
            $is_live = 3;
        }elseif($is_player === 0 &&$liveList){
            $is_live = 2;
        }
        $this->assign('is_live',$is_live);
        $this->assign('now_live',$now_live);
//        $this->assign('is_live',0);
//        var_Dump($liveList);
//        exit;


        $this->assign('game', $game);
        $this->assign('detail', $det);
        $notice = Tool::getAdList(42, 5) ?: [];
        $this->assign('ad', $notice);
        $this->assign('game_id', $game_id);
        $this->assign('game_state', $textSkill['game_state']);
        $this->assign('mqttOpt', $mqtt);
        $this->assign('userStatus', $userStatus);
        $this->assign('svg_url', $flashUrl);
        $this->assign('ip', get_client_ip());
        $this->assign('chatOpen', $status);
        $this->assign('userInfo', $uinfo ? json_encode($uinfo) : '');
        $this->assign('client_id', md5(get_client_ip() . $game_id . rand(0, 99999)));
        $this->assign('esrAddress', C('ESR_ADDRESS'));
        $this->assign('mqttUser', setMqttUser());
        $this->display();
    }

    /**
     * 获取当前最新赔率
     */
    public function odds()
    {
        if (IS_POST) {
        	$game_id = (int) I('gameId');
        	$game_state = (int) I('game_state');
	        $server = new \Home\Services\AppfbService();
	        $odds = $server->fbOdds([$game_id]);
	        $goals = D("GambleHall")->getGambleGoal($game_id);
	        $all_odds= D("GambleHall")->doFswOdds($odds[$game_id], $game_state, $goals);
	        $data['fsw_exp_home'] = $all_odds[0];
	        $data['fsw_exp'] = handCpSpread($all_odds[1]);
	        $data['fsw_exp_away'] = $all_odds[2];
	        $data['fsw_europe_home'] = $all_odds[3];
	        $data['fsw_europe'] = $all_odds[4];
	        $data['fsw_europe_away'] = $all_odds[5];
	        $data['fsw_ball_home'] = $all_odds[6];
	        $data['fsw_ball'] = changeExp($all_odds[7]);
	        $data['fsw_ball_away'] = $all_odds[8];
	        $data['half_exp_home'] = $all_odds[9];
	        $data['half_exp'] = handCpSpread($all_odds[10]);
	        $data['half_exp_away'] = $all_odds[11];
	        $data['half_europe_home'] = $all_odds[12];
	        $data['half_europe'] = $all_odds[13];
	        $data['half_europe_away'] = $all_odds[14];
	        $data['half_ball_home'] = $all_odds[15];
	        $data['half_ball'] = changeExp($all_odds[16]);
	        $data['half_ball_away'] = $all_odds[17];
	        $this->ajaxReturn($data);
	        
	        /*
            $map['game_id'] = I('gameId');
            $map['company_id'] = I('company_id') ?: 3;
            $scConfig = C('score');
            $scConfig2 = C('score_sprit');
            $res = M('FbOdds')->field('game_id,exp_value')->where($map)->find();

            $odds = oddsChArr($res['exp_value']);
            $retArrs = [];
            $fsw_odds = $this->do_odds($odds, 'fsw');
            //全场亚盘
            $retArrs['fsw_exp_home'] = $fsw_odds['fsw_exp_home'];

            if ($fsw_odds['fsw_exp_home'] == '' || $fsw_odds['fsw_exp_away'] == '') {
                $retArrs['fsw_exp'] = '';
            } else {
                $temp_exp = trim($fsw_odds['fsw_exp'], '-');
                $retArrs['fsw_exp'] = strpos($fsw_odds['fsw_exp'], '-') !== false ? '受' . $scConfig[$temp_exp] : $scConfig[$temp_exp];
            }
            $retArrs['fsw_exp_away'] = $fsw_odds['fsw_exp_away'];

            //全场大小
            $retArrs['fsw_ball_home'] = $fsw_odds['fsw_ball_home'];
            if ($fsw_odds['fsw_ball_home'] == '' || $fsw_odds['fsw_ball_away'] == '') {
                $retArrs['fsw_ball'] = '';
            } else {
                $retArrs['fsw_ball'] = $scConfig2[sprintf("%01.2f", $fsw_odds['fsw_ball'])];
            }
            $retArrs['fsw_ball_away'] = $fsw_odds['fsw_ball_away'];

            $retArrs['fsw_europe_home'] = $fsw_odds['fsw_europe_home'];
            $retArrs['fsw_europe'] = $fsw_odds['fsw_europe'];
            $retArrs['fsw_europe_away'] = $fsw_odds['fsw_europe_away'];

            //半场
            $half_odds = $this->do_odds($odds, 'half');
            $retArrs['half_exp_home'] = $half_odds['half_exp_home'];
            if ($half_odds['half_exp_home'] == '' || $half_odds['half_exp_away'] == '') {
                $retArrs['half_exp'] = '';
            } else {
                $temp_exp2 = trim($half_odds['half_exp'], '-');
                $retArrs['half_exp'] = strpos($half_odds['half_exp'], '-') !== false ? '受' . $scConfig[$temp_exp2] : $scConfig[$temp_exp2];
            }
            $retArrs['half_exp_away'] = $half_odds['half_exp_away'];

            if ($half_odds['half_ball_home'] == '' || $half_odds['half_ball_away'] == '') {
                $retArrs['half_ball'] = '';
            } else {
                $retArrs['half_ball'] = $scConfig2[sprintf("%01.2f", $half_odds['half_ball'])];
            }

            $retArrs['half_ball_home'] = $half_odds['half_ball_home'];
            $retArrs['half_ball_away'] = $half_odds['half_ball_away'];

            $retArrs['half_europe_home'] = $half_odds['half_europe_home'];
            $retArrs['half_europe'] = $half_odds['half_europe'];
            $retArrs['half_europe_away'] = $half_odds['half_europe_away'];

            foreach ($retArrs as $k => $v) {
                $retArrs[$k] = (string)$v;
            }
            $this->ajaxReturn($retArrs);
	        */
        }

    }

    /**
     * 聊天室发言
     */
    public function say()
    {
        if (IS_POST) {
            try {
                if (I('content') == '')
                    throw new Exception('请填输入聊天内容', 101);

                if (I('gameId') == '')
                    throw new Exception('缺少参数', 101);

                $game_type = I('game_type') ? I('game_type') : 1;

                if (!$userid = is_login())
                    throw new Exception('登录一起参与聊球吧', 1011);

                $user = M('FrontUser')->master(true)->field(['status'])->find($userid);
                //状态是否被禁用
                if ($user['status'] != 1)
                    throw new Exception('您的账号被管理员屏蔽了', 1005);

                //判断是否被屏蔽、踢出
                $forbid = M('ChatForbid')->where(['user_id' => $userid, 'status' => ['IN', [1, 3]]])->order('id DESC')->find();
                if ($forbid) {
                    if ($forbid['type'] == 1) {
                        $errCode = 3018;
                        $errMsg = '您被管理员屏蔽了聊天功能';
                        throw new Exception($errMsg, $errCode);
                    } else {
                        if (NOW_TIME < $forbid['operate_time'] + 600) {
                            $errCode = 3019;
                            $errMsg = '您已被管理员限时禁言';
                            throw new Exception($errMsg, $errCode);
                        }
                    }
                }

                //如果没有被屏蔽，则从屏蔽旧的记录集合里删除用户
                $redis = connRedis();
                $redis->sRem('qqty_chat_forbid_userids', $userid);

                import('Vendor.Emoji.Emoji');
                $content = htmlspecialchars(emoji_html_to_unified2(trim($_REQUEST['content'])));

                $userInfo = M('FrontUser')->master(true)->field('nick_name,head,lv,lv_bet,lv_bk,is_expert')->where(['id' => $userid])->find();
                $say['data'] = [
                    'userId' => $userid,
                    'nickName' => $userInfo['nick_name'],
                    'head' => frontUserFace($userInfo['head']),
                    'content' => $content,
                    'contentType' => 1,
                    'time' => NOW_TIME,
                    'gift' => '',
                    'desc' => ''
                ];

                //显示等级
                $lv = $game_type == 1 ? max($userInfo['lv'], $userInfo['lv_bet']) : $userInfo['bk'];
                $say['data']['lv'] = $lv >= 4 ? $lv : '';
                $say['data']['is_expert'] = (string)$userInfo['is_expert'];

                //敏感词检测
                if (!matchFilterWords('FilterWords', I('content')))
                    throw new Exception('您的输入含有非法敏感词', 1061);

                //发言
                $redis = connRedis();
                $channel = 'esr_chat_' . $game_type . ':' . I('gameId');
                $msgid = $redis->incr('chat_esr_msg_id');
                $say['type'] = 2002;
                $say['status'] = 1;
                $say['data']['msg_id'] = $msgid;

                //mqtt
                $opt = [
                    'topic' => 'qqty/' . $game_type . '_' . I('gameId') . '/chat/say',
                    'payload' => $say,
                    'clientid' => md5(time() . $userid),
                ];
                Mqtt($opt);

                $redis->lPush($channel, json_encode($say['data']));
                $redis->expire($channel, 86400);
                $say['data']['code'] = 200;

            } catch (Exception $m) {
                $this->ajaxReturn(['code' => $m->getCode(), 'msg' => $m->getMessage()]);
            }

            $this->ajaxReturn($say['data']);
        }
    }

    /**
     * 文字直播
     */
    public function textliving()
    {
        try {
            if (I('gameId') == '')
                throw new Exception('参数缺失', 111);

            $web = !empty($_REQUEST['web']) ? $_REQUEST['web'] : 2;
            $appService = new \Home\Services\AppfbService();
            $res = $appService->getTextliving(I('gameId'), 1);

            if ($res === false)
                throw new Exception('查询错误', 222);

        } catch (Exception $m) {
            $this->ajaxReturn(['code' => $m->getCode(), 'msg' => $m->getMessage()]);
        }

        $this->ajaxReturn(['code' => 200, 'data' => $res]);
    }

    /**
     * 赛事推荐统计 (hzl)
     * @param $gameId
     * @param int $gameType
     * @param int $gambleType
     * @param int $playType
     * @param bool $getTotal
     * @return array
     */
//     public function getTenMaster($userId, $gameId, $gameType = 1, $gambleType = 1, $playType = 0, $getTotal = false)
//     {
//         //根据亚盘、竞彩玩法组装条件
//         $jWhere = $wh = $userWeekGamble = $userids = $pageList = $userGamble = [];
//         $time = $gameType == 1 ? (10 * 60 + 32) * 60 : (12 * 60) * 60;
//         if ($gameType == 1) {
//             $gambleModel = M('Gamble');
//             if (abs($gambleType) == 1) {
//                 $lvField = 'f.lv lv';
//                 $wh['result'] = ['IN', ['1', '0.5', '2', '-1', '-0.5']];
//                 $wh['play_type'] = ['IN', [-1, 1]];
//                 $jWhere['play_type'] = ['IN', [-1, 1]];
//             } else {
//                 $lvField = 'f.lv_bet lv';
//                 $wh['result'] = ['IN', [1, -1]];
//                 $wh['play_type'] = ['IN', [2, -2]];
//                 $jWhere['play_type'] = ['IN', [2, -2]];
//             }

//             if ($playType)
//                 $wh['play_type'] = (int)$playType;
//         } else {
//             $gambleModel = M('Gamblebk');
//             $lvField = 'f.lv_bk lv';
//             $wh['result'] = ['IN', ['1', '0.5', '2', '-1', '-0.5']];
//             $wh['play_type'] = ['IN', [-1, 1]];
//             $jWhere['play_type'] = ['IN', [-1, 1]];
//         }
//         //获取参与该场赛事竞猜的用户
//         $fields = ['g.id gamble_id', 'g.game_id', 'g.user_id', 'g.play_type', 'g.chose_side', 'g.handcp', 'g.odds', 'g.is_impt', 'g.union_name', 'g.home_team_name', 'g.away_team_name', 'g.create_time', 'g.voice', 'g.is_voice', 'g.voice_time', 'g.quiz_number', 'g.extra_number',
//             'g.result', 'g.tradeCoin', 'g.desc', 'g.create_time', 'f.head face', 'f.nick_name', $lvField, '(g.quiz_number + g.extra_number) as quiz_number'];

//         if ($getTotal === true) {
//             $list = $gambleModel
//                 ->field('DISTINCT user_id')
//                 ->where(['game_id' => $gameId, 'play_type' => $wh['play_type']])
//                 ->select();
//             return (string)count($list);
//         } else {
//             $list = $gambleModel->alias("g")
//                 ->join("left join qc_front_user f on f.id = g.user_id")
//                 ->field($fields)
//                 ->where(['game_id' => $gameId, 'play_type' => $wh['play_type']])
//                 ->group('g.user_id')
//                 ->order('lv desc')
//                 ->limit(100)
//                 ->select();
//         }

//         if ($list) {
//             list($wBegin, $wEnd) = getRankBlockDate($gameType, 1);//周
//             list($mBegin, $mEnd) = getRankBlockDate($gameType, 2);//月
//             list($jBegin, $jEnd) = getRankBlockDate($gameType, 3);//季

//             $wBeginTime = strtotime($wBegin) + $time;
//             $wEndTime = strtotime($wEnd) + 86400 + $time;

//             $mBeginTime = strtotime($mBegin) + $time;
//             $mEndTime = strtotime($mEnd) + 86400 + $time;

//             $jBeginTime = strtotime($jBegin) + $time;
//             $jEndTime = strtotime($jEnd) + 86400 + $time;

//             foreach ($list as $vv) {
//                 $userids[] = $vv['user_id'];
//             }

//             $wWhere['user_id'] = ['IN', $userids];
//             $wWhere['result'] = ['IN', ['1', '0.5', '-1', '-0.5']];
//             $wWhere['play_type'] = $jWhere['play_type'];
//             $wWhere['create_time'] = ["between", [$wBeginTime, $wEndTime]];

//             $userGamble = $gambleModel
//                 ->field('user_id, GROUP_CONCAT(result) as result')
//                 ->where($wWhere)
//                 ->group('user_id')
//                 ->select();

//             //是否查看过本赛程
//             if (isset($userId)) {
//                 $gambleId = (array)M('QuizLog')->where(['user_id' => $userId, 'game_id' => $gameId, 'game_type' => $gameType])->getField('gamble_id', true);
//             }

//             //周竞猜
//             $userWeekGamble = array_column($userGamble, 'result', 'user_id');
//             $lv = $weekSort = $monthSort = $seasonSort = $tenGamble = $sortTime = [];

//             //月竞猜
//             $jWhere['result'] = ["IN", ['1', '0.5', '-1', '-0.5']];
//             $jWhere['create_time'] = ["between", [$jBeginTime, $jEndTime]];

//             foreach ($list as $k => $v) {
//                 //用户信息
//                 $list[$k]['face'] = frontUserFace($v['face']);
//                 $list[$k]['is_trade'] = in_array($v['gamble_id'], $gambleId) ? '1' : '0';
// //                $list[$k]['desc']       = (string)$pageList[$k]['desc'];

//                 //周胜率计算
//                 $wWin = $wHalf = $wTransport = $wDonate = 0;
//                 $resultArr = explode(',', $userWeekGamble[$v['user_id']]);

//                 foreach ($resultArr as $resultV) {
//                     if ($resultV == '1') $wWin++;
//                     if ($resultV == '0.5') $wHalf++;
//                     if ($resultV == '-1') $wTransport++;
//                     if ($resultV == '-0.5') $wDonate++;
//                 }
//                 $list[$k]['weekPercnet'] = (string)getGambleWinrate($wWin, $wHalf, $wTransport, $wDonate);


//                 //月、季胜率计算
//                 $jWhere['user_id'] = $v['user_id'];
//                 $jWin = $mWin = $jHalf = $mHalf = $jTransport = $mTransport = $jDonate = $mDonate = 0;
//                 $seasonGamble = $gambleModel->field(['result', 'earn_point', 'create_time'])->where($jWhere)->select();
//                 foreach ($seasonGamble as $key => $val) {
//                     switch ($val['result']) {
//                         case '1':
//                             $jWin++;
//                             if ($val['create_time'] >= $mBeginTime && $val['create_time'] <= $mEndTime) $mWin++;
//                             break;

//                         case '0.5':
//                             $jHalf++;
//                             if ($val['create_time'] >= $mBeginTime && $val['create_time'] <= $mEndTime) $mHalf++;
//                             break;

//                         case '-1':
//                             $jTransport++;
//                             if ($val['create_time'] >= $mBeginTime && $val['create_time'] <= $mEndTime) $mTransport++;
//                             break;

//                         case '-0.5':
//                             $jDonate++;
//                             if ($val['create_time'] >= $mBeginTime && $val['create_time'] <= $mEndTime) $mDonate++;
//                             break;
//                     }
//                 }

//                 $list[$k]['monthPercnet'] = (string)getGambleWinrate($mWin, $mHalf, $mTransport, $mDonate);
//                 $list[$k]['seasonPercnet'] = (string)getGambleWinrate($jWin, $jHalf, $jTransport, $jDonate);

//                 //近十场胜负、胜平负
//                 $wh['user_id'] = $v['user_id'];
//                 $tenGamble = $gambleModel->where($wh)->order("id desc")->limit(10)->getField('result', true);
// //                $list[$k]['tenGamble'] = $tenGamble;
//                 $list[$k]['tenGambleRate'] = countTenGambleRate($tenGamble);;

//                 $_TenGambleSort = 0;
//                 foreach ($tenGamble as $gamble_v) {
//                     if ($gamble_v == 1 || $gamble_v == 0.5) {
//                         $_TenGambleSort++;
//                     }
//                 }

//                 //过滤近十中5一下
//                 if ($_TenGambleSort < 5) {
//                     unset($list[$k]);
//                     continue;
//                 } else {
//                     $list[$k]['ten_rate'] = $_TenGambleSort;
//                 }


//                 //排序数组
//                 $lv[] = $v['lv'];
//                 $tenGambleSort[] = $_TenGambleSort;
//                 $weekSort[] = $list[$k]['weekPercnet'];

//                 $monthSort[] = $list[$k]['monthPercnet'];
//                 $seasonSort[] = $list[$k]['seasonPercnet'];
//                 $sortTime[] = $v['create_time'];
//                 unset($list[$k]['lv_bet']);
//             }
//             //排序：近十中几》周胜》等级》月》季》发布时间
//             array_values($list);
//             array_multisort($tenGambleSort, SORT_DESC, $weekSort, SORT_DESC, $lv, SORT_DESC, $monthSort, SORT_DESC, $seasonSort, SORT_DESC, $list);
//         }
//         return array_slice($list, 0, 10) ?: [];
//     }


    /**
     * 加入聊天室
     */
    public function joinRoom()
    {
        $game_type = I('game_type') ?: 1;
        $game_id = I('game_id');
        if ($game_type == '' || $game_id == '')
            $this->ajaxReturn(101);

        //判断是否是管理员
        $userid = is_login();
        $ad = M('ChatAdmin')->where(['user_id' => $userid])->find();
        $isAdmin = $ad ? '1' : '0';

        //获取聊天记录
        $dataService = new \Common\Services\DataService();
        $chat_log = $dataService->chatRecord($game_type . '_' . $game_id);

        //发送欢迎语
        $userStatus = 1;
        if ($userid) {
            $uinfo = M('FrontUser')
                ->field('id as user_id,nick_name,head,lv,lv_bet,lv_bk,coin,unable_coin,status')
                ->where(['id' => $userid])
                ->find();

            if ($uinfo) {
                $uinfo['head'] = frontUserFace($uinfo['head']);

                //状态是否被禁用
                if ($uinfo['status'] != 1)
                    $userStatus = -1;//您的账号被管理员屏蔽了

                //判断是否被屏蔽、踢出
                $forbid = M('ChatForbid')->where(['user_id' => $uinfo['user_id'], 'status' => ['IN', [1, 3]]])->order('id DESC')->find();
                if ($forbid) {
                    if ($forbid['type'] == 1) {
                        $userStatus = -2;
                    } else if ($forbid['type'] == 3) {
                        if (NOW_TIME < $forbid['operate_time'] + 600) {
                            $userStatus = -3;
                        }
                    } else if ($forbid['type'] == 2) {
                        if ($forbid['status'] == 1) {
                            $userStatus = -2;
                        } else {
                            if (NOW_TIME < $forbid['operate_time'] + 600) {
                                $userStatus = -3;
                            }
                        }
                    }
                }
                C('DATA_CACHE_PREFIX', 'api_');
                $key = 'qqty_chat_send_hello:' . $userid . '_' . $game_type . '_' . $game_id;
                if ($userStatus == 1 && !S($key)) {
                    $msg_id = md5(time() . $userid . $game_type . $game_id . rand(0, 9999));
                    $data = array_merge($uinfo, ['content' => "Hi,很高兴和大家一起来聊球。", 'msg_id' => $msg_id, 'chat_time' => time()]);
                    $payload = [
                        'action' => 'sayHello',
                        'data' => $data,
                        'dataType' => 'text',
                        'platform' => 1,
                        'status' => '1'
                    ];

                    $opt = [
                        'topic' => 'qqty/' . $game_type . '_' . $game_id . '/chat',
                        'payload' => $payload,
                        'clientid' => md5(time() . $userid),
                    ];
                    mqttPub($opt);

                    S($key, time(), 3600*24);
                }
            }

            //在线人数
            D('Robot')->onOffLine($userid, $game_type, $game_id, 1);
        }
        $identity = $userid ? 'normal' : 'robot';
        D('Robot')->onOffLine($userid, $game_type, $game_id, 1, $identity);
        $this->ajaxReturn(['isAdmin' => $isAdmin, 'chatLog' => $chat_log]);
    }

    /**
     * 屏蔽用户聊天
     */
    public function forbid()
    {
        $type = I('type');
        $game_id = I('game_id');
        $room_id = I('room_id');
        $game_type = I('game_type');
        $content = I('content');
        $forbid_id = I('user_id');
        $msg_id = I('msg_id');
        $chat_time = I('chat_time');
        $userid = is_login();

        try {
            if (!$type || !$content || !$forbid_id || !$msg_id)
                throw new Exception('参数错误', 101);
            if((!$game_id || !$game_type) && !$room_id) throw new Exception('参数错误', 101);

            if (!$userid)
                throw new Exception('请先登录', 1011);

            if ($userid == $forbid_id)
                throw new Exception('不能举报、屏蔽自己', 3020);

            $room_id = $game_type . '_' . $game_id;
            $forbid = [
                'user_id' => $forbid_id,
                'type' => $type,
                'content' => json_encode($content),
                'room_id' => $room_id,
                'msg_id' => $msg_id,
                'chat_time' => $chat_time,
                'create_time' => NOW_TIME
            ];

            if ($type == 1 || $type == 3) {//屏蔽、踢出
                $ad = M('ChatAdmin')->where(['user' => $userid])->find();
                if (!$ad) {
                    throw new Exception('请先登录', 1011);
                }

                $forbid['status'] = $type;
                $forbid['operate_time'] = NOW_TIME;
                $forbid['operator'] =  $userid;
                $forbid['operate_type'] = 3;

                $add = M('ChatForbid')->add($forbid);
                if (!$add)
                    throw new Exception('举报失败', 3017);

                if ($type == 1) {
                    $action = 'forbid';
                    $notice_str = '您的聊天内容已经严重违反了全球体育平台规则，您将被永久屏蔽帐号';
                } else {
                    $action = 'kickout';
                    $notice_str = '您的聊天内容影响到其他用户，你将被禁言十分钟';
                }
                $redis = connRedis();
                $redis->sAdd('qqty_chat_forbid_userids', $forbid_id);
                //过滤被屏蔽的用户消息
                $chat_log = $redis->lRange($room_id, 0, -1);
                $members = $redis->sMembers('qqty_chat_forbid_userids');
                foreach ($chat_log as $k => $v) {
                    $log = json_decode($v, true);
                    if (in_array($log['user_id'], $members)) {
                        $redis->lRem($room_id, $v, 1);
                    }
                }

                //mqtt
                $pubData = [
                    'action' => $action,
                    'dataType' => 'text',
                    'data' => ['user_id' => $forbid_id, 'notice_str' => $notice_str, 'msg_id' => $msg_id],
                    'status' => 1
                ];

                $opt = [
                    'topic' => 'qqty/' . $room_id . '/chat',
                    'payload' => $pubData,
                    'clientid' => md5(time() . $userid),
                ];
                mqttPub($opt);

            } elseif ($type == 2) {//举报
                $forbid['from_id'] =  $userid;
                $add = M('ChatForbid')->add($forbid);
                if (!$add)
                    throw new Exception('举报失败', 3017);
            }

        } catch (Exception $m) {
            $this->ajaxReturn(['msg' => $m->getMessage(), 'code' => $m->getCode()]);
        }
        $this->ajaxReturn(['result' => '1', 'code' => 200]);
    }

    /**
     * emoji转html
     */
    public function emojiToHtml()
    {
        import('Vendor.Emoji.Emoji');
        $this->ajaxReturn(['content' => emoji_unified_to_html($_REQUEST['content']), 'code' => 200]);
    }

    /*
     * 情报详情
     */
    public function news()
    {
        $game_id = I('game_id', 0, 'int');

        if ($game_id < 0) {
            $this->error();
        }
        $game_type = $this->param['game_type'] ?: 1;//默认足球
        $from = $this->param['from'] ?: 1;//情报来源

        $cacheKey = 'Video:articleList' . MODULE_NAME . $game_id . $game_type;
        $informationIdArr = C('informationIdArr');
        if (!$responseList = S($cacheKey)) {
            $akey = $game_type == 1 ? 'game_id' : 'gamebk_id';
            $articleList = (array)M('PublishList p')->field(['p.id','p.class_id','1 as hrefType', 'p.app_time as time', 'class_id', 'p.source', 'p.title', 'p.remark', 'p.click_number', 'p.img', 'p.content', 'p.add_time', 'p.game_id', 'p.short_title','fu.nick_name','fu.head','p.user_id'])
                ->join('left join qc_front_user fu on fu.id=p.user_id')
                ->where([$akey => $game_id, 'p.status' => 1,'p.class_id'=>['IN',$informationIdArr]])
                ->order('web_recommend desc, is_channel_push desc, p.add_time desc')
                ->limit(20)
                ->select();

            $videoList = (array)M('Highlights h')->field(['h.id','h.class_id','2 as hrefType', 'h.title', 'h.remark', 'click_num as click_number', 'h.img', 'h.app_url', 'h.app_ischain', 'h.is_prospect', 'h.add_time as time', 'h.app_isbrowser','fu.nick_name','fu.head','h.user_id'])
                ->join('left join qc_front_user fu on fu.id=h.user_id')
                ->where(['game_id' => $game_id, 'game_type' => $game_type, 'app_url' => ['neq', ''], 'h.status' => 1])
                ->order('h.is_recommend desc, h.add_time asc')
                ->limit(20)
                ->select();

            $list = array_merge($articleList, $videoList);
            $newsClass = getPublishClass(0);
            $videoClass = getVideoClass(0);
            foreach ($list as $k => $v) {
                if ($v['class_id']) {
                    $list[$k]['type'] = 1;
                } else {
                    $list[$k]['type'] = 2;
                }
                $addTimeSort[] = $v['add_time'];
                if ($v['game_id']) {
                    $gameinfo = $this->gameinfos($v['game_id'], $game_type);
                    $list[$k] = array_merge($list[$k], $gameinfo);
                }
                if($v['hrefType'] == 1)
                {
                    $href = newsUrl($v['id'],$v['add_time'],$v['class_id'],$newsClass);
                }else{
                    $href = videoUrl($v,$videoClass);
                }
                $list[$k]['href'] = $href;
                $list[$k]['head'] = frontUserFace($v['head']);
//                unset($list[$k]['add_time']);
                unset($list[$k]['class_id']);
            }

            //排序
            array_multisort($addTimeSort, SORT_DESC, $list);
            $responseList = array_slice($list, 0, 10);

//            if ($responseList)
//                S($cacheKey, $responseList, 60 * 1);
        }

        $lists = $this->getArticleImg($responseList, false);
        //获取赛前情报
        $this->oddsHeader($game_id);
        $preMatchinfo = $this->preMatchinfo;
	    if(empty($lists) && empty($preMatchinfo))
	    {
		    $this->redirect('/dataFenxi@bf',['game_id'=>$game_id]);
	    }
        $this->assign('list', $lists);
        $this->assign('matchinfo', $preMatchinfo);
        $this->display();
    }

    //获取资讯里面的图片
    public function getArticleImg($articleList, $comment = true)
    {
        $publishClass = M('PublishClass')->where("status=1")->getField('id, name');
        foreach ($articleList as $k => $v) {
            //处理remark
            $articleList[$k]['remark'] = $v['remark'] ?: str_replace(',', ' ', $v['label']);
            unset($articleList[$k]['label']);

            $imgs = Tool::getTextImgUrl(htmlspecialchars_decode($v['content']), 0);

            foreach ($imgs as $kkk => $vvv) {
                if (strtoupper(substr(strrchr($vvv, '.'), 1)) == 'GIF')
                    unset($imgs[$kkk]);
            }

            if (count($imgs) >= 3) {
                $imgs = array_slice($imgs, 0, 3);
                foreach ($imgs as $kk => $vv) {
                    if (strpos($vv, SITE_URL) === false)
                        $imgs[$kk] = http_to_https($vv);
                }

                $articleList[$k]['img'] = $imgs;
            } else {
                if ($articleList[$k]['img']) {
                    $articleList[$k]['img'] = [C('IMG_SERVER') . $articleList[$k]['img']];
                } else {
                    if (count($imgs) >= 1) {
                        if (strpos($imgs[0], SITE_URL) === false)
                            $articleList[$k]['img'] = [http_to_https($imgs[0])];
                        else
                            $articleList[$k]['img'] = [$imgs[0]];
                    } else {
                        $articleList[$k]['img'] = [];
                    }
                }

                // $articleList[$k]['img'] = $articleList[$k]['img'] ? [SITE_URL.C('IMG_SERVER').$articleList[$k]['img']] :
                //                             count($imgs) >= 1 ? $imgs[0] = [SITE_URL.C('IMG_SERVER').$imgs[0]] : [];
            }

            //按需获取评论数
            if ($comment)
                $articleList[$k]['commentNum'] = M('Comment')->where(['publish_id' => $v['id']])->count();

            //增加资讯点击量的默认值
            $articleList[$k]['click_number'] = addClickConfig(1, $v['class_id'], $v['click_number'], $v['id']);

            //返回game_type
            if (in_array($v['class_id'], C('gameTypeClass'))) {
                $articleList[$k]['game_type'] = '2';
            } else {
                $articleList[$k]['game_type'] = '1';
            }

            //过滤图片
            if (in_array($v['class_id'], C('classId'))) {
                $img = http_to_https(staticDomain('/Public/Home/images/common/loading.png'));
                $articleList[$k]['img'] = [$img];
            }

            unset($articleList[$k]['content']);
        }
        return $articleList;
    }

    /**
     * 查询赛事信息
     * @return array
     *
     */
    public function gameinfos($gameid = "", $table)
    {
        $gid = $gameid;
        switch ($table) {
            case 1:
                $type = 'fbinfo';
                break;
            case 2:
                $type = 'bkinfo';
                break;
        }
        $info = M("game_" . $type);
        $res = $info->field('union_name,home_team_name as home,away_team_name as away')->where("game_id = " . $gameid)->find();
        if (empty($res)) {
            return false;
        }
        return $res;

    }

    /*
     * 赔率详情
     */
    public function oddsinfo()
    {
        $game_id = I('game_id', 0, 'int');
        $comp_id = I('compid', 0, 'int');
        $sign = I('sign', 0, 'int');

        if ($game_id < 0 || $comp_id < 0 || $sign < 0) {
            $this->error();
        }
        $company = C('BF_COMPANY_ODDS');
        if(!array_key_exists($comp_id,$company)) $comp_id=3;
        $this->assign('company',$company);
        $this->assign('comp_id',$comp_id);
        $list = $table = $tab_h = $tab_a = $tab_p = array();
        $half = $oddtype = 0;
        switch ($sign) {
            case 1:
            case 2:
            case 3:
            case 4:
                $half = 1;
                break;
            case 5:
            case 6:
            case 7:
                $half = 0;
                break;
        }
        switch ($sign) {
            case 1:
            case 5:
                $oddtype = '亚';
                $list['name_h'] = '主队';
                $list['name_a'] = '客队';
                break;
            case 2:
            case 6:
                $oddtype = '欧';
                $list['name_h'] = '胜';
                $list['name_p'] = '平';
                $list['name_a'] = '负';
                break;
            case 3:
            case 7:
                $oddtype = '大';
                $list['name_h'] = '大球';
                $list['name_a'] = '小球';
                break;
        }
        switch ($sign) {
            case 1:
            case 5:
                $type = 0;
                break;
            case 2:
            case 6:
                $type = 2;
                break;
            case 3:
            case 7:
                $type = 1;
                break;
            default:
                $type = 3;
        }
        $gameInfo = $this->oddsHeader($game_id);
        $webfbService = new \Home\Services\WebfbService();
        $data = $webfbService->getOddsInfo($game_id, $comp_id, $half, $oddtype,$type,$gameInfo);
        $res = array_reverse($data['data']);
        $home_tmp = $away_tmp = $per_tmp = null;
        if($sign == 4)
        {
            foreach($res as $kk=>$vv)
            {
                $vv = array_reverse($vv);
                $home_tmp = $per_tmp = $away_tmp = '';
                foreach ($vv as $key => $val) {
                    if (count($val) == 6) continue;
                    $val['home_c'] = $this->oddshis_color($val[2], $home_tmp);
                    $home_tmp = $val[2];
                    if($kk == 0)
                    {
                        if($val[3] == '封') $per_tmp = '';
                        $val['per_c'] = $this->oddshis_color($val[3], $per_tmp);
                        $per_tmp = $val[3];
                        if($val[3] == '封') $per_tmp = '';
                    }
                    $val['away_c'] = $this->oddshis_color($val[4], $away_tmp);
                    $away_tmp = $val[4];
                    $vv[$key] = $val;
                }
                $vv = array_reverse($vv);
                $res[$kk] = $vv;
            }
            $this->assign('list', $res);
            $this->display('oddsinfo2');
        }else{
            foreach ($res as $key => $val) {
                if (count($val) == 6) continue;
                $res[$key]['home_c'] = $this->oddshis_color($val[2], $home_tmp);
                $home_tmp = $val[2];
                if($sign == 2 || $sign == 6)
                {
                    if($val[3] == '封') $per_tmp = '';
                    $res[$key]['per_c'] = $this->oddshis_color($val[3], $per_tmp);
                    $per_tmp = $val[3];
                    if($val[3] == '封') $per_tmp = '';
                }
                $res[$key]['away_c'] = $this->oddshis_color($val[4], $away_tmp);
                $away_tmp = $val[4];
                $res[$key]['home_per'] = number_format($val[2] / $data['home_max'], 2, '.', '') * 100;
                $res[$key]['away_per'] = number_format($val[4] / $data['away_max'], 2, '.', '') * 100;
                if($oddtype == '欧') $res[$key]['pin_per'] = number_format($val[3] / $data['pin_max'], 2, '.', '') * 100;
                if ($val[7] != '滚') {
                    $tab_h[] = $val[2];
                    $tab_p[] = $val[3];
                    $tab_a[] = $val[4];
                    $table[] = '\'' . $val[5] . ' ' . $val[6] . '\'';
                }
            }
            $list['data']['tab_h'] = array_reverse($res);
            if ($sign == 1 || $sign == 5 || $sign == 3 || $sign == 7) {
                $tabinfo = array_merge($tab_h, $tab_a);
                $list['tab_time'] = implode(',', $table);
                $min = array_search(min($tabinfo), $tabinfo);
                $list['min'] = number_format($tabinfo[$min] - 0.1, 1, '.', '');
                $max = array_search(max($tabinfo), $tabinfo);
                $list['max'] = number_format($tabinfo[$max] + 0.1, 1, '.', '');
            } elseif ($sign == 2 || $sign == 6) {
                $tabinfo = array_merge($tab_h, $tab_a, $tab_p);
                $list['tab_time'] = implode(',', $table);
                $min = array_search(min($tabinfo), $tabinfo);
                $list['min'] = number_format($tabinfo[$min] - 1);
                $max = array_search(max($tabinfo), $tabinfo);
                $list['max'] = number_format($tabinfo[$max] + 1);
                $list['tab_p'] = json_encode($tab_p);
            }
            $list['tab_h'] = json_encode($tab_h);
            $list['tab_a'] = json_encode($tab_a);
            $this->assign('list', $list);
            $this->display();
        }
    }

    /*
     * 大额交易
     */
    public function trades()
    {
        $game_id = I('game_id', 0, 'int');
        $sign = I("sign", 0, 'int');
        $p = I('p', 0, 'int');
        $p = $p == 0 ? $p : $p - 1;

        if ($game_id < 0) {
            $this->error();
        }
        $this->oddsHeader($game_id);

        $res = M("FbBingfa")->field("bf_trade_win")->where(['game_id' => $game_id])->find();
        $data = json_decode($res['bf_trade_win'], true);
        if ($sign != 0) $data = $this->trade_arr($data, $sign);
        $list = array_values($data)[$p];
        $total = 0;
        foreach ($data as $k => $v) {
            $total = $total + count($v);
        }
        $this->assign("list", $list);

        //处理分页数据
        $count = $total;
        if ($count > 0) {
            //创建分页对象
            if (!empty ($listRows)) {
                $listRows = $listRows;
            } else {
                $listRows = 30;
            }
            //实例化分页类
            $page = new \Think\Page ($count, $listRows);
            $page->url = "/trades/game_id/{$game_id}/sign/{$sign}/p/%5BPAGE%5D.html";
            $arr['game_id'] = $game_id;
            $arr['sign'] = $sign;
            foreach ($arr as $key => $val) {
                $page->parameter .= "$key=" . urlencode($val) . "&";
            }
            $this->assign("show", $page->showJs());
            $this->assign('totalCount', $count);
            $this->assign('numPerPage', $page->listRows);
        }

        $this->assign('sign', $sign);
        $this->display();
    }

    //处理大额交易数据
    public function trade_arr($data, $type)
    {
        switch ($type) {
            case 1:
                $name = '主';
                break;
            case 2:
                $name = '平';
                break;
            case 3:
                $name = '客';
                break;
        }
        $res = array();
        $i = 0;
        $num = 0;
        foreach ($data as $val) {
            foreach ($val as $v) {
                if ($v[0] == $name) {
                    $res[$i][] = $v;
                    $num++;
                    if ($num > 29) {
                        $i++;
                        $num = 0;
                    }
                }
            }
        }
        return $res;
    }

    /*
     * 赔率详情数据对比显示颜色
     */
    public function oddshis_color($a, $b)
    {
        if ($b == '') return '';
        $tmp = explode('/',$a);
        $a = $tmp[0]+$tmp[1];
        if ($a > $b) {
            return 'text-red';
        } elseif ($a < $b) {
            return 'text-green';
        } else {
            return '';
        }
    }

    /*
     * 数据分析必发指数交易明细
     */
    public function detTrade(){

        $game_id = I('game_id', 0, 'int');
        $p = I('p', 0, 'int');
        $p = $p == 0 ? $p : $p - 1;

        if ($game_id < 0) {
            $this->error();
        }
        $this->oddsHeader($game_id);

        //处理类型值,防止传入错误数值
        $sign = (int)I('sign');
        if($sign < 1){
            $sign = 1;
        }elseif($sign > 6)
        {
            $sign = 6;
        }
        $field = [2=>'hostdetail','drawdetail','awaydetail','bigdetail','smalldetail'];
//        var_dump($field);
        $WebService = new \Home\Services\WebfbService();
        $data = [];
        $home = $WebService->getDetTrade($game_id,'hostdetail',30,$p);
        $draw = $WebService->getDetTrade($game_id,'drawdetail',30,$p);
        $away = $WebService->getDetTrade($game_id,'awaydetail',30,$p);
        foreach($home['data'] as $val)
        {
            if(count($data[$val['gtime']]) >=5) continue;
            $data[$val['gtime']][] = $val[0];
            $data[$val['gtime']][] = $val[1];
            $data[$val['gtime']][] = $val[2];
            $data[$val['gtime']][] = $val[3];
            $data[$val['gtime']][] = $val[4];
        }
        foreach($draw['data'] as $val)
        {
            if(count($data[$val['gtime']]) >=9) continue;
            $data[$val['gtime']][] = $val[1];
            $data[$val['gtime']][] = $val[2];
            $data[$val['gtime']][] = $val[3];
            $data[$val['gtime']][] = $val[4];
        }
        foreach($away['data'] as $val)
        {
            if(count($data[$val['gtime']]) >=13) continue;
            $data[$val['gtime']][] = $val[1];
            $data[$val['gtime']][] = $val[2];
            $data[$val['gtime']][] = $val[3];
            $data[$val['gtime']][] = $val[4];
        }
        switch ($sign)
        {
            case 1:
                $res = ['count'=>$home['count'],'data'=>$data];
                break;
            case 2:
                $res = $home;
                break;
            case 3:
                $res = $draw;
                break;
            case 4:
                $res = $away;
                break;
            default:
                $res = $WebService->getDetTrade($game_id,$field[$sign],30,$p);
        }

        //处理买卖单汇总
        $buy_sell = [
            ['买单汇总',$home['buy'],$home['buy_num'],$draw['buy'],$draw['buy_num'],$away['buy'],$away['buy_num']],
            ['卖单汇总',$home['sell'],$home['sell_num'],$draw['sell'],$draw['sell_num'],$away['sell'],$away['sell_num']]
        ];
        $this->assign('buy_sell',$buy_sell);

        if($res['count'] > 0)
        {
            //创建分页对象
            if (!empty ($listRows)) {
                $listRows = $listRows;
            } else {
                $listRows = 30;
            }
            //实例化分页类
            $page = new \Think\Page ($res['count'], $listRows);
            $page->url = "/detTrade/game_id/{$game_id}/sign/{$sign}/p/%5BPAGE%5D.html";
            $arr['game_id'] = $game_id;
            $arr['sign'] = $sign;
            foreach ($arr as $key => $val) {
                $page->parameter .= "$key=" . urlencode($val) . "&";
            }
            $this->assign('data',$res['data']);
            $this->assign("show", $page->showJs());
            $this->assign('totalCount', $res['count']);
            $this->assign('numPerPage', $page->listRows);

        }
        $this->assign('sign',$sign);
        $this->display();
    }

    /****************************************篮球比分*************************************************************/
    /****************************************篮球比分*************************************************************/
    /****************************************篮球比分*************************************************************/
    //篮球主入口,对各模块进行跳转
    public function lanqiu()
    {
        $url = explode('.',$_SERVER['REQUEST_URI'])[0];
        $url_info = array_filter(explode('/',$url));
        switch ($url_info[2])
        {
            case 'schedule':
                $this->bk_score_nav($url);
                $this->assign('actionName', 'schedule');
                $this->bk_schedule($url);
                break;
            case 'schtoday':
                $this->bk_score_nav($url);
                $this->assign('actionName', 'schtoday');
                $this->bk_schtoday($url);
                break;
            case 'indices':
                $this->bk_score_nav($url);
                $this->assign('actionName', 'indices');
                $this->bk_indices();
                break;
            case 'generate':
                $this->assign('actionName', 'generate');
                $this->display('BkScore/generate');
                break;
            case 'dataFenxi':
                $this->bk_dataFenxi();
                break;
            default:
                $this->bk_score_nav($url);
                $this->assign('actionName', 'index');
                $this->bk_index();
                break;
        }
    }

    //篮球即时比分页面
    public function bk_index()
    {
        //页面日期E
        $time_class = date('m-d', time());
        $this->assign('scroet', time());
        $this->assign('time_class', $time_class);
        $arr = D('BkScore')->over_game(null, 0, 1);
        $this->assign('union', $arr['union']);
        $this->assign('list', $arr['list']);
        $this->assign('rang', $arr['rang']);
        $this->assign('status', $arr['status']);
        //mqtt 配置
        $mqtt = C('Mqtt');
        $this->assign('mqttOpt', $mqtt);
        $this->assign('client_id', md5(get_client_ip() . $game_id . rand(0, 99999)));
        $this->assign('mqttUser', setMqttUser());
        $this->display('BkScore/index');
    }

    /**
     * @ 完场比分
     * */
    public function bk_schedule($url)
    {
        //页面日期S
        $date = array();
        for ($i = 1; $i < 8; $i++) {
            $time = time() - $i * 60 * 60 * 24;
            $date[$i]['day'] = date('m-d', $time);
            $date[$i]['week'] = $this->getTimeWeek($time);
            $date[$i]['time'] = $time;
        }
        $this->assign('week', $date);
        //页面日期E
        $_time = explode('score_t/',$url)[1];
        $_time = $_time?$_time:time() - 60 * 60 * 24;
        $time_class = date('m-d', $_time);
        $this->assign('scroet', $_time);
        $this->assign('time_class', $time_class);
        $arr = D('BkScore')->over_game($_time);
        $this->assign('union', $arr['union']);
        $this->assign('list', $arr['list']);
        $this->assign('rang', $arr['rang']);
        $this->display('BkScore/schedule');
    }

    /**
     * @ 今日赛程
     * */
    public function bk_schtoday($url)
    {
        //页面日期S
        $date = array();
        for ($i = 1; $i < 8; $i++) {
            $time = time() + $i * 60 * 60 * 24;
            $date[$i]['day'] = date('m-d', $time);
            $date[$i]['week'] = $this->getTimeWeek($time);
            $date[$i]['time'] = $time;
        }
        $this->assign('week', $date);
        //页面日期E
        $_time = explode('score_t/',$url)[1];
        $_time = $_time?$_time:time() + 60 * 60 * 24;
        $time_class = date('m-d', $_time);
        $this->assign('scroet', $_time);
        $this->assign('time_class', $time_class);
        $arr = D('BkScore')->over_game($_time, 0);
        $this->assign('union', $arr['union']);
        $this->assign('list', $arr['list']);
        $this->assign('rang', $arr['rang']);
        $this->display('BkScore/schtoday');
    }

    /*
     * 即时指数
     */
    public function bk_indices()
    {
        $data = D('BkScore')->indices();
        $this->assign('company', $data['company']);
        $this->assign('companyId', $data['companyId']);
        $this->assign('list', $data['list']);
        $this->assign('union', $data['union']);
        $this->display('BkScore/indices');
    }

    //篮球去除顶部导航
    public function bk_score_nav($url)
    {
        if(strpos($url,'no')) $header = 'no';
        $this->assign('header', empty($header) ? '' : 'no');
    }

    /**
     * 获取动画基础信息
     */
    public function svg_base()
    {
//        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
//        $allow_origins = ['http://dh.qqty.com','http://www.qqty.com'];
//        if(in_array($origin,$allow_origins)){
//            header("Access-Control-Allow-Origin:  {$origin}");
//        }
//        header("Access-Control-Allow-Credentials:true");
//        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
//        header('Access-Control-Allow-Methods: GET, POST, PUT,DELETE,OPTIONS,PATCH');

        $ret = [];
        $status = 1;
        if ($gameId = (int)I('game_id')) {
            if ((int)I('type') == 2) {
                $mGame = mongo('bk_game_schedule');
                $mGame365 = mongo('bk_game_365');
                $mLinkBet = M('BkLinkbet');
                $st = 'game_status';
            } else {
                $mGame = mongo('fb_game');
                $mGame365 = mongo('fb_game_365');
                $mLinkBet = M('FbLinkbet');
                $st = 'game_state';
            }

            $schedule = $mGame
                ->field($st . ',union_name,home_team_name,away_team_name,bet_game_id,away_team_id,home_team_id,game_start_timestamp,game_start_datetime,score,is_swap')
                ->where(['game_id' => $gameId])
                ->find();

//            if (!$bet_game_id = $schedule['bet_game_id']) {
//                $bet_game_id = $mLinkBet->where(['game_id' => $gameId])->order('id DESC')->getField('from_id');
//            }

            $game_365 = mongo('fb_game_365')
                ->field('game_type,game_name,game_score,events,tech,statistics,home_team_color,away_team_color,game_length,game_time,game_status,status,flash')
                ->where(['jbh_id' => $gameId])
                ->find();

            if(!$game_365){
                $game_365 = mongo('fb_game_365')
                    ->field('game_type,game_name,game_score,events,tech,statistics,home_team_color,away_team_color,game_length,game_time,game_status,status,flash')
                    ->where(['jb_id' => (int)$gameId])
                    ->find();
            }

            if (I('type') == 2) {
                $home_team_img = mongo('bk_team')->field('img_url')->where(['team_id' => $schedule['home_team_id']])->find();
                $away_team_img = mongo('bk_team')->field('img_url')->where(['team_id' => $schedule['away_team_id']])->find();

                //返回数据
                $ret['_id'] = (string)$game_365['_id'];
                $ret['status'] = $schedule['game_status'];
                $ret['gameType'] = I('type');
                $ret['gameLeague'] = $schedule['union_name'][0];
                $ret['gameName'] = $game_365['game_name'];
                $ret['gameScore'] = $game_365['game_score'];
                $ret['h_team'] = $schedule['home_team_name'][0];
                $ret['a_team'] = $schedule['away_team_name'][0];
                $ret['h_team_img'] = 'https://img1.qqty.com/' . ($home_team_img['img_url'] ?: '/img/home_team_logo.png');
                $ret['a_team_img'] = 'https://img1.qqty.com/' . ($away_team_img['img_url'] ?: '/img/away_team_logo.png');
                $ret['event'] = $game_365['events'];
                $ret['last_flash'] = $game_365['flash']?:'';
            } else {
                $game_status = [];
                foreach ($game_365['game_status'] as $sk => $sv) {
                    unset($sv['realtime']);
                    $game_status[] = $sv;
                }

                $ret['info']["homeTeamColor"] = (string)$game_365['home_team_color'];
                $ret['info']["awayTeamColor"] = (string)$game_365['away_team_color'];
                $ret['info']["homeTeamName"] = (string)$schedule['home_team_name'][0];
                $ret['info']["awayTeamName"] = (string)$schedule['away_team_name'][0];
                if($schedule['is_swap']  == 1)
                {
                    $ret['info']["homeTeamName"] = (string)($ret['info']["homeTeamName"].'(客)');
                    $ret['info']["awayTeamName"] = (string)('(主)'.$ret['info']["awayTeamName"]);
                }
                $ret['info']["gameLength"] = (string)$game_365['game_length'];
                $ret['info']['gameLeague'] = (string)$schedule['union_name'][0];
                $ret['info']["gameName"] = (string)$game_365['game_name'];
                $ret['info']["gameScore"] = $game_365['game_score']?:'0-0';
                $ret['info']["gameTime"] = (string)$game_365['game_time'];
                $ret['info']["status"] = (string)$game_365['status'];
                $ret['info']["gameTime"] = $schedule['game_start_timestamp'];
                $ret['info']["gameTimeStr"] = (string)$schedule['game_start_datetime'];
                $ret['info']["type"] = 0;
                $ret['game_status']['type'] = 1;
                $ret['game_status']['event'] = $game_status;
                $ret['break']['game_statistics'] = $game_365['statistics']?:'';
                $ret['break']['game_tech'] = $game_365['tech']?:'';
                $ret['last_flash'] = $game_365['flash']?:'';
            }

            if($callback = I('svgCallback')){
                echo htmlspecialchars($callback) . "(".json_encode(['status' => $status, 'data' => $ret]).")";
                return;
            }

            $this->ajaxReturn(['status' => $status, 'data' => $ret]);
        }
    }

    /**
     * 获取动画关联
     */
    public function getLinkbet(){
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
        $allow_origins = ['http://dh.qqty.com','http://www.qqty.com'];
        if(in_array($origin,$allow_origins)){
            header("Access-Control-Allow-Origin:  {$origin}");
        }
        header("Access-Control-Allow-Credentials:true");
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
        header('Access-Control-Allow-Methods: GET, POST, PUT,DELETE,OPTIONS,PATCH');
        $fromId = (int)I('from_id');
        $gameType = (int)I('game_type',1,'int');
        if($gameType == 1)
        {
            $model = M('FbLinkbet');

            $count  = $model->where(['from_id' => $fromId])->count();
            if($count > 1){
                $linkBet = $model->where(['from_id' => $fromId, 'is_link' => 1])->find();
            }else{
                $linkBet = $model->where(['from_id' => $fromId])->find();
            }
        }else{
            //篮球数据处理
            $data = mongo('bk_game_365')->where(['game_id'=>(string)$fromId])->find();
            if($data['jb_id']) $game_id = $data['jb_id'];//py匹配
            if($data['jbh_id']) $game_id = $data['jbh_id'];//手动编辑
            if($game_id)
            {
                $linkBet = [
                    'id' => $data['_id'],
                    'game_id' => $game_id,
                    'from_id' => $data['game_id'],
                    'game_title' => $data['game_name'],
                    'home_team_name' => $data['home_team_name'],
                    'away_team_name' => $data['away_team_name'],
                    'gtime' => $data['game_timestamp'],
                ];
            }
        }

        $this->ajaxReturn(['data' => $linkBet ?:'']);
    }


    /**
     * 定时检测动画是否关联
     */
    public function checkBetLink()
    {
        $date = date('Ymd');
        $betRes = M('FbLinkbet')->where(['game_date' => $date, 'notice_status' => 0, 'is_link' => 0])->find();

        if($betRes){
            $gt = date('Y-m-d H:i:s', $betRes['gtime']);
            $post_data = [
                'platform' => 'MailQQTY',
                'token' => 'huangzl@qqty.com,huanghn@qqty.com,hejb@qqty.com,chenff@qqty.com,chent@qqty.com,meijf@qqty.com',
                'title' => '足球动画未关联提示',
                'payload' => "<style class=\"fox_global_style\"> <!-- body,div,dl,dt,dd,h1,h2,h3,h4,h5,h6,pre,form,fieldset,input,textarea,p,blockquote,th,td{padding:0; margin:0; } fieldset,img{border:0; } table{border-collapse:collapse; border-spacing:0; } ol,ul{} address,caption,cite,code,dfn,em,strong,th,var{font-weight:normal; font-style:normal; } caption,th{text-align:left; } h1,h2,h3,h4,h5,h6{font-weight:bold; font-size:100%; } q:before,q:after{content:''; } abbr,acronym{border:0; } a:link,a:visited{} a:hover{} .Bdy{font-size:14px; font-family:verdana,Arial,Helvetica,sans-serif; padding:20px;} h1{font-size:24px; color:#cd0021; padding-bottom:30px;} p{} .Tb_mWp{border:1px solid #ddd; border-right:none; border-bottom:none; table-layout:fixed;} .Tb_mWp th,.Tb_mWp td{border-right:1px solid #ddd; border-bottom:1px solid #ddd; padding:8px 4px;} .Tb_mWp th{font-size:14px; text-align:right; width:130px; font-weight:bold; background:#f6f6f6; color:#666;} .Tb_mWp td{font-size:14px; padding-left:10px; word-break:break-all;} .Tb_miWp{ margin-top:-2px; margin-left:-1px; float:left; table-layout:fixed;} .Tb_miWp th,.Tb_miWp td{border-left:1px solid #eee; border-top:1px solid #eee; border-right:none; border-bottom:none; font-size:12px;line-height:18px} .Tb_miWp th{width:68px; background:#f8f8f8;line-height:18px} .tr_Mi{} .tr_Mi th{} .tr_Mi td{} .tr_Rz{} .tr_Rz th{} .tr_Rz td{ background:#fff4f6;} .tr_Rz .infoTt{ color:#cd0021; font-weight:bold; line-height:18px;} .tr_Rz .infoDcr{ padding-top:4px; color:#999; line-height:18px;} .tr_Sr{} .tr_Sr th{} .tr_Sr td{background:#f4fff4;} .ul_lstWp{margin-left:-20px;} .ul_lst{padding-top:0px; padding-bottom:0px; margin-top:6px; margin-bottom:6px;} .ul_lst li{padding-top:3px; padding-bottom:3px;} --> </style><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"><!-- saved from url=(0022)http://internet.e-mail --> <meta http-equiv=\"Content-Type\" content=\"text/html; charset=gb2312\"> <meta name=\"Keywords\" content=\"\"> <meta name=\"Description\" content=\"\"> <title></title> <style type=\"text/css\">body, div, dl, dt, dd, h1, h2, h3, h4, h5, h6, pre, form, fieldset, input, textarea, p, blockquote, th, td { padding: 0px; margin: 0px; }table { border-collapse: collapse; border-spacing: 0px; }h1, h2, h3, h4, h5, h6 { font-weight: bold; font-size: 100%; }</style> <h1 style=\"font-size: 19px;\">足球动画赛事未关联！！！请收到邮件的相关人员及时处理。</h1><br><table border=\"1\" bordercolor=\"#000000\" cellpadding=\"2\" cellspacing=\"0\" style=\"font-size: 10pt; border: none;\" width=\"50%\"> <tbody><tr> <td width=\"50%\" style=\"border: solid 1 #000000\" nowrap=\"\"><font size=\"2\" face=\"Verdana\"><div>&nbsp;比赛</div></font></td> <td width=\"50%\" style=\"border: solid 1 #000000\" nowrap=\"\"><font size=\"2\" face=\"Verdana\"><div>{$betRes['game_title']}，&nbsp;&nbsp;{$betRes['home_team_name']} VS {$betRes['away_team_name']}</div></font></td> </tr> <tr> <td width=\"50%\" style=\"border: solid 1 #000000\" nowrap=\"\"><font size=\"2\" face=\"Verdana\"><div>&nbsp;<span style=\"line-height: 1.5; background-color: window;\">比赛日期</span></div></font></td> <td width=\"50%\" style=\"border: solid 1 #000000\" nowrap=\"\"><font size=\"2\" face=\"Verdana\"><div>&nbsp;<span style=\"line-height: 1.5; background-color: window;\">{$gt}</span></div></font></td> </tr> <tr> <td width=\"50%\" style=\"border: solid 1 #000000\" nowrap=\"\"><font size=\"2\" face=\"Verdana\"><div>&nbsp;处理方法</div></font></td> <td width=\"50%\" style=\"border: solid 1 #000000\" nowrap=\"\"><font size=\"2\" face=\"Verdana\"><div>&nbsp;请前往后台-》网站管理-》足球赛事-》动画赛事页面手动关联</div></font></td> </tr> <tr> <td width=\"50%\" style=\"border: solid 1 #000000\" nowrap=\"\"><font size=\"2\" face=\"Verdana\"><div>&nbsp;处理<span style=\"line-height: 1.5; background-color: window;\">状态</span></div></font></td> <td width=\"50%\" style=\"border: solid 1 #000000\" nowrap=\"\"><font size=\"2\" face=\"Verdana\"><div>&nbsp;<span style=\"line-height: 19px; background-color: window;\">未处理</span></div></font></td> </tr></tbody></table>",
            ];

            M('FbLinkbet')->where(['id' => $betRes['id']])->save(['notice_status' => 1]);

            $res = httpPost(C('push_adress'), $post_data);
            echo $post_data['payload'];
            var_dump($res);

        }
    }

    //比分赛事数量接口（给兄弟公司用）
    public function gameNum(){
        if(!$data = S('socreApi_gameNum')){
            $webfbService = new \Home\Services\WebfbService();
            //即时
            $res = $webfbService->fbtodayList();
            $data['nowNum']    = count($res['info']);
            //完场
            $res = $webfbService->fbOverList(date('Ymd',strtotime('-1 day')));
            $data['overNum']   = count($res['info']);
            //未来
            $res = $webfbService->fbFixtureList(date('Ymd',strtotime('+1 day')));
            $data['futureNum'] = count($res['info']);
            //指数
            $res = $webfbService->fbInstant();
            $data['oddsNum']   = count($res['info']);
            S('socreApi_gameNum',$data,10);
        }
        
        $this->jsonpReturn(1,$data,'gameNum');
    }

    //獲取即時指數數據
    public function getIndices()
    {
        $data = S('fbIndices');
        if(!$data)
        {
            $data = $this->fbIndices();
            S('fbIndices',$data,5*60);
        }
        $res = ['status'=>1,'data'=>$data];
        $this->ajaxReturn($res);
    }

    public function fbIndices()
    {
        $appfbService = new \Home\Services\AppfbService();
        $res = $appfbService->fbInstant();
        $data = $this->over_and_future($res);
        $gameArr = $data['gameArr'];
        $unionArr = $data['unionArr'];
        $union = $unionNum = $comp = [];
        $unionTmp = [];
        foreach($res as $key=>$val){
            if(count($val[8]) == 0 && count($val[9]) == 0 && count($val[10]) == 0)
            {
                unset($res[$key]);
                continue;
            }
            if(!$unionTmp[$val[1]]) $unionTmp[$val[1]] = $unionArr[$val[1]];
            $pei = [];
            //合併亞盤大小歐賠數組
            foreach ($val[8] as $k=>$v){
                $pei[$v[1]][0] = $v[0];
                $pei[$v[1]] = array_pad($pei[$v[1]],7,'--');
                $pei[$v[1]][1] = $v[2];
                $pei[$v[1]][2] = $v[3];
                $pei[$v[1]][3] = $v[4];
                $pei[$v[1]][4] = $v[5];
                $pei[$v[1]][5] = $v[6];
                $pei[$v[1]][6] = $v[7];
                $pei[$v[1]] = array_pad($pei[$v[1]],19,'--');
            }
            foreach ($val[9] as $k=>$v){
                $pei[$v[1]][0] = $v[0];
                $pei[$v[1]] = array_pad($pei[$v[1]],13,'--');
                $pei[$v[1]][7] = $v[2];
                $pei[$v[1]][8] = $v[3];
                $pei[$v[1]][9] = $v[4];
                $pei[$v[1]][10] = $v[5];
                $pei[$v[1]][11] = $v[6];
                $pei[$v[1]][12] = $v[7];
                $pei[$v[1]] = array_pad($pei[$v[1]],19,'--');
            }
            foreach ($val[10] as $k=>$v){
                $pei[$v[1]][0] = $v[0];
                $pei[$v[1]] = array_pad($pei[$v[1]],19,'--');
                $pei[$v[1]][13] = $v[2];
                $pei[$v[1]][14] = $v[3];
                $pei[$v[1]][15] = $v[4];
                $pei[$v[1]][16] = $v[5];
                $pei[$v[1]][17] = $v[6];
                $pei[$v[1]][18] = $v[7];
            }
            //對比數據大小,計算顯示顏色
            foreach($pei as $k=>$v){
                $pei[$k]['y_h_c'] = $v[1] != '--'?$this->contrast($v[1],$v[4]):'';
                $pei[$k]['y_p_c'] = $v[2] != '--'?$this->contrast($v[2],$v[5]):'';
                $pei[$k]['y_a_c'] = $v[3] != '--'?$this->contrast($v[3],$v[6]):'';
                $pei[$k]['d_h_c'] = $v[7] != '--'?$this->contrast($v[7],$v[10]):'';
                $pei[$k]['d_p_c'] = $v[8] != '--'?$this->contrast($v[8],$v[11]):'';
                $pei[$k]['d_a_c'] = $v[9] != '--'?$this->contrast($v[9],$v[12]):'';
                $pei[$k]['o_h_c'] = $v[13] != '--'?$this->contrast($v[13],$v[16]):'';
                $pei[$k]['o_p_c'] = $v[14] != '--'?$this->contrast($v[14],$v[17]):'';
                $pei[$k]['o_a_c'] = $v[15] != '--'?$this->contrast($v[15],$v[18]):'';
                $pei[$k]['name'] = $k;
                $comp[$v[0]] = $k;
            }
            $unionNum[$val[1]] = (int)$unionNum[$val[1]]+1;
            $union[$val[1]] = (int)$val[1];
//            $pei = array_pad($pei,3,'');
            unset($res[$key][8],$res[$key][9],$res[$key][10]);
            $res[$key]['info'] = array_values($pei);
            $res[$key][6] = mb_substr($val[6],0,4).'-'.mb_substr($val[6],4,2).'-'.mb_substr($val[6],6,2).' '.mb_substr($val[6],8,2).':'.mb_substr($val[6],10,2);


            $mysqlGame = $gameArr[$val[0]];
            $res[$key]['is_go'] = $mysqlGame['is_go']?:0;//滾球
            $res[$key]['is_betting'] = $mysqlGame['is_betting']?:0;//競猜
            //联盟表数据
            $unionData = $unionArr[$val[1]];
            $unionLevel = isset($unionData['level']) ? $unionData['level'] : 3;
            $webfbService = new \Home\Services\WebfbService();

            $res[$key]['tuijian'] = (string)$webfbService->checkGamble([
                'game_id'       => $val[0],
                'is_gamble'     => $mysqlGame['is_gamble'],
                'is_show'       => $mysqlGame['is_show'],
                'is_sub'        => $unionLevel,
                'fsw_exp'       => $val[23],
                'fsw_exp_home'  => $val[24],
                'fsw_exp_away'  => $val[25],
                'fsw_ball'      => $val[26],
                'fsw_ball_home' => $val[27],
                'fsw_ball_away' => $val[28],
            ]);
            $res[$key][3] = $unionArr[$val[1]]['union_color'];
            $res[$key]['gtime'] = strtotime($val[6]);
            $unionTmp[$val[1]]['total'] = (int)$unionTmp[$val[1]]['total'] + 1;

        }
        $addTimeArr = get_arr_column($res,'gtime');
        array_multisort($addTimeArr,SORT_ASC,$res);
        $arr['info'] = $res;
        $arr['union'] = array_values($unionTmp);
        $arr['company'] = $comp;
        return $arr;
    }

    //根據數據對比顏色,返回相關class
    public function contrast($home,$away){
        if(strpos($home,'/') || strpos($away,'/'))
        {
            $home_tmp= explode('/',$home);
            $away_tmp = explode('/',$away);
            $home_tmp[1] = $home_tmp?:0;
            $away_tmp[1] = $away_tmp?:0;
            $home = ((int)$home_tmp[0]+(int)$home_tmp[1])/2;
            $away = ((int)$away_tmp[0]+(int)$away_tmp[1])/2;
        }
        if($home > $away)
        {
            $class = 'text-green';
        }elseif($home<$away){
            $class = 'text-red';
        }else{
            $class = '';
        }
        return $class;
    }

    /**
     * 籃球比分賽事詳情模塊
     * @author liuweitao
     */
    public function bk_dataFenxi(){
        $game_id = $this->bkHeaderInfo();
        $this->display('BkScore/dataFenxi');
    }

    /**
     * 獲取籃球賽事id,並且對模板賦值賽事信息
     * @param int $type 是否直接返回賽事id
     */
    public function bkHeaderInfo($type = false)
    {
        $game_id = (int)explode('/',$_SERVER['PATH_INFO'])[3];
        if(!$type)
        {
            D('BkScore')->bkHeaderInfo($game_id);
        }
    }

    //ajax获取美女聊天室历史消息
    public function getLiveHistoryChat(){
        $roomTopic = I('room_id');
        if(is_numeric($roomTopic))
            $room_id = $roomTopic;
        else
            $room_id = explode('_',explode('/',$roomTopic)[1])[1];
        $data = [];
        if($room_id){
            $dataService = new \Common\Services\DataService();
            $chatRecord = $dataService->chatRecord('live_'.$room_id);
            if($chatRecord){
                $tmp = [];
                foreach($chatRecord as $v){
                    $tmp[] = json_encode([
                        'action'=>'say',
                        'dataType'=>'text',
                        'status'=>1,
                        'data'=>$v
                    ]);
                }
                $data = ['code'=>200,'data'=>$tmp];
            }else{
                $data = ['code'=>404];
            }
        }else{
            $data = ['code'=>404];
        }

        $this->ajaxReturn($data);
    }
}

?>