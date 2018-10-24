<?php
/**
 * 比分页面
 */
use Think\Controller;
use Think\Tool\Tool;

class ScoreController extends CommonController
{
    public $preMatchinfo = '';
    public function _initialize()
    {
        C('HTTP_CACHE_CONTROL', 'no-cache,no-store');
        parent::_initialize();
    }

    /**
     * @ 比分主页 dengwj
     * */
    public function index()
    {
        //获取赛事
        $unionId = !empty($_REQUEST['unionId']) ? $_REQUEST['unionId'] : null;
        $subId = !empty($_REQUEST['subId']) ? $_REQUEST['subId'] : null;
        $webfbService = new \Home\Services\WebfbService();
        $gameData = $webfbService->fbtodayList($unionId, $subId);
        $game = $gameData['info']; //赛事
        $union = $gameData['union']; //联盟

        //获取赔率
        $companyID = I('cid');
        $companyID = !empty($companyID) ? $companyID : 3;
        $oddsData = $webfbService->getOddsData($companyID);
        $do_game = $no_game = $over_game = [];
        foreach ($game as $k => $v) {
            foreach ($oddsData as $kk => $vv) {
                if ($v[0] == $kk) {
                    //全场
                    $fsw_odds = $this->do_odds($vv, 'fsw');
                    $game[$k]['fsw_exp_home'] = $fsw_odds['fsw_exp_home'];
                    $game[$k]['fsw_exp'] = $fsw_odds['fsw_exp'];
                    $game[$k]['fsw_exp_away'] = $fsw_odds['fsw_exp_away'];
                    $game[$k]['fsw_ball_home'] = $fsw_odds['fsw_ball_home'];
                    $game[$k]['fsw_ball'] = $fsw_odds['fsw_ball'];
                    $game[$k]['fsw_ball_away'] = $fsw_odds['fsw_ball_away'];
                    $game[$k]['fsw_europe_home'] = $fsw_odds['fsw_europe_home'];
                    $game[$k]['fsw_europe'] = $fsw_odds['fsw_europe'];
                    $game[$k]['fsw_europe_away'] = $fsw_odds['fsw_europe_away'];

                    //半场
                    $half_odds = $this->do_odds($vv, 'half');
                    $game[$k]['half_exp_home'] = $half_odds['half_exp_home'];
                    $game[$k]['half_exp'] = $half_odds['half_exp'];
                    $game[$k]['half_exp_away'] = $half_odds['half_exp_away'];
                    $game[$k]['half_ball_home'] = $half_odds['half_ball_home'];
                    $game[$k]['half_ball'] = $half_odds['half_ball'];
                    $game[$k]['half_ball_away'] = $half_odds['half_ball_away'];
                    $game[$k]['half_europe_home'] = $half_odds['half_europe_home'];
                    $game[$k]['half_europe'] = $half_odds['half_europe'];
                    $game[$k]['half_europe_away'] = $half_odds['half_europe_away'];
                }
            }
            //天气处理
            if ($v[45] != '') {
                $weather = explode('℃', $v[45]);
                $weatherStr = trim($weather[1]);
                //$weatherArr[] = $weatherStr;
                switch ($weatherStr) {
                    case '晴天':
                    case '天晴':
                    case '大致天晴':
                        $weather_class = 'weather-qt';
                        $weatherStr = '晴天';
                        break;
                    case '阴天':
                        $weather_class = 'weather-yt';
                        break;
                    case '多云':
                        $weather_class = 'weather-dy';
                        break;
                    case '少云':
                    case '间中有云':
                        $weather_class = 'weather-sy';
                        $weatherStr = '少云';
                        break;
                    case '阵雨':
                        $weather_class = 'weather-zy';
                        $weatherStr = '阵雨';
                        break;
                    case '烟雾':
                        $weather_class = 'weather-yw';
                        break;
                    case '霾'  :
                        $weather_class = 'weather-l';
                        break;
                    case '雾'  :
                    case '有雾':
                        $weather_class = 'weather-w';
                        $weatherStr = '雾';
                        break;
                    case '毛毛雨' :
                    case '微雨':
                        $weather_class = 'weather-mmy';
                        $weatherStr = '小雨';
                        break;
                    case '局部多云':
                        $weather_class = 'weather-jbdy';
                        break;
                    case '零散雷雨':
                    case '雷暴':
                    case '雷陣雨':
                        $weather_class = 'weather-lsly';
                        $weatherStr = '雷阵雨';
                        break;
                    case '大雪':
                        $weather_class = 'weather-dx';
                        break;
                    case '小雪':
                    case '雪':
                        $weather_class = 'weather-xx';
                        $weatherStr = '小雪';
                        break;
                }
                $game[$k]['weather_class'] = $weather_class;
                $game[$k][45] = $weather[0] . '℃<br/>' . $weatherStr;
            }
            //进行中
            if (in_array($v[7], [1, 2, 3, 4])) {
                $do_game[$k] = $game[$k];
            }
            //未开
            if ($v[7] == 0) {
                $no_game[$k] = $game[$k];
            }
            //结束
            if (in_array($v[7], [-1, -10, -11, -12, -13, -14])) {
                $over_game[$k] = $game[$k];
            }
        }
        //dump(array_unique($weatherArr));
        $this->assign('do_game', $do_game);
        $this->assign('no_game', $no_game);
        $this->assign('over_game', $over_game);
        $unionLevel = $unionSort = array();
        //统计联赛的赛事数
        foreach ($union as $key => $value) {
            $gameCount = 0;
            foreach ($game as $k => $v) {
                if ($value[0] == $v[1])//判断是否为同一联赛
                {
                    $gameCount++;
                }
            }
            $union[$key]['gameCount'] = $gameCount;
            //级别
            $unionLevel[] = $value[6];
            $unionSort[] = $value[9];
        }
        //级别排序--升序
        array_multisort($unionSort, SORT_ASC, $unionLevel, SORT_ASC, $union);

        $this->assign('union', $union);
        $this->score_nav();

        //mqtt 配置
        $mqtt = C('Mqtt');
        $this->assign('mqttOpt', $mqtt);
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
     * @ 今日赛程
     * */
    public function schtoday()
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
        $_time = I('score_t', time() + 60 * 60 * 24);
        $time_class = date('m-d', $_time);
        $this->assign('scroet', $_time);
        $this->assign('time_class', $time_class);
        $arr = $this->over_game($_time, 0);

        $this->assign('list', $arr);
        $this->score_nav();
        $this->display();
    }

    /**
     * @ 完场比分
     * */
    public function schedule()
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
        $_time = I('score_t', time() - 60 * 60 * 24);
        $time_class = date('m-d', $_time);
        $this->assign('scroet', $_time);
        $this->assign('time_class', $time_class);
        $arr = $this->over_game($_time);

        $this->assign('list', $arr);
        $this->score_nav();
        $this->display();
    }


    /**
     * @ 对完场赛事进行缓存
     * @ author liuweitao 906742852@qq.com
     * @ $_time 查询时间
     */
    public function over_game($time, $type = 1)
    {
        $_time = date('Y-m-d', $time);
        S($_time, NULL);//按照日期做数据缓存,开发时打开更新数据
        if (S($_time)) {
            $arr = S($_time);
        } else {
            $res = $this->_over_game($time, $type);
            $arr = $res;
            S($_time, $res, 60 * 5);
        }
        return $arr;
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
        if ($type == 1) {
            $time = date('Y-m-d', $_time);
//            $res = $this->get_curl("/Home/Webfb/fbOver", "date=$time", C('CURL_DOMAIN_QW'));
            $webfbService = new \Home\Services\WebfbService();
            $res = $webfbService->fbOverList($time);
        } else {
            $time = date('Ymd', $_time);
//            $res = $this->get_curl("/Home/Webfb/fbFixture","date=$time", C('CURL_DOMAIN_QW'));
            $webfbService = new \Home\Services\WebfbService();
            $res = $webfbService->fbFixtureList($time);
        }
        $res['data'] = $res;
        if (empty($res['data'])) {
            return 'null';
        }
        foreach ($res['data']['info'] as $key => &$val) {
            $val['total'] = $val[21] + $val[22];//主队得分加客队得分
            switch ($val[46]) {
                case 1;
                    $val['double_col'] = 'text-blue';
                    $val['double'] = '单';
                    break;
                case 2;
                    $val['double_col'] = 'text-red';
                    $val['double'] = '双';
                    break;
            }
            if ($val[37]) {
                if ($val[37] < $val['total']) {
                    $val['score'] = '大';
                    $val['score_col'] = 'text-blue';
                } elseif ($val[37] > $val['total']) {
                    $val['score'] = '小';
                    $val['score_col'] = 'text-red';
                } elseif ($val[37] = $val['total']) {
                    $val['score'] = '平';
                }
            } else {
                $val['score'] = '-';
            }
            $day = substr($val[8], 4);
            $val['day'] = substr_replace($day, '-', 2, 0);
            $val['draw'] = $bifen[$val[47]] . '/' . $bifen[$val[48]];
            if (is_numeric($val[44])) {
                switch ($val[44]) {
                    case 1;
                        $val['win'] = '赢';
                        $val['win_col'] = 'red';
                        break;
                    case 0;
                        $val['win'] = '走';
                        $val['win_col'] = 'blue';
                        break;
                    case -1;
                        $val['win'] = '输';
                        $val['win_col'] = '#A9A9A9';
                        break;
                }
            } else {
                $val['win'] = '-';
            }
            $_q = $this->_compare($val[21], $val[22]);
            $val['home_col'] = $_q[0];
            $val['away_col'] = $_q[1];
            $_b = $this->_compare($val[23], $val[24]);
            $val['home_col_b'] = $_b[0];
            $val['away_col_b'] = $_b[1];
            if ($type) {
                $_num = $val[34];
            } else {
                $_num = $val[22];
            }
            $val['sb'] = $this->_sb($_num, 1);
            $val['game_state'] = $game_state[$val[7]];
        }
        $list['info'] = $res['data']['info'];
        $list['match'] = array_values($res['data']['union']);
        $list = $this->total_match($list);
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
        //注意：把多个类型的值，存到同一个Cookie变量里  联赛ID---前缀‘!’，后缀‘@’ 公司ID---前缀‘#’，后缀‘$’

        //获取公司
        $company = C('DB_COMPANY_INFO');
        //获取公司ID
        $companyKey = array();
        foreach ($company as $k => $v) {
            $companyKey[] = $k;
        }

        //用户筛选的联赛ID和公司ID
        $indicesCookie = I('cookie.indicesCookie');
        $unionIdStr = explode('@', explode('!', $indicesCookie)[1])[0];//联赛ID
        $companyIdStr = explode('$', explode('#', $indicesCookie)[1])[0];//公司ID

        $unionIdArr = $unionIdStr ? explode(',', $unionIdStr) : [];//筛选ID 默认：“澳门”、“SB”、“BET365"
        $companyId = $companyIdStr ? explode(',', $companyIdStr) : [1, 3, 8];//筛选ID 默认：“澳门”、“SB”、“BET365"

        //指数
//		$instantUnion = $this->get_curl("/Home/Webfb/fbInstant", 'key=no', C('CURL_DOMAIN_QW'))['data'];
        $webfbService = new \Home\Services\WebfbService();
        $instantUnion = $webfbService->fbInstant();

        //今日赛事
//		$toDayEvent = $this->get_curl("/Home/Webfb/fb", 'key=no', C('CURL_DOMAIN_QW'))['data']['info'];
        $toDayEvent = $webfbService->fbtodayList()['info'];
        //赛事名称、级别
        $unionName = $instantUnion['union'];
        //赛事列表
        $unionInfo = $instantUnion['info'];
        unset($instantUnion);

        $unionLevelOne = $unionLevelTwo = $unionLevelThree = array();
        foreach ($unionName as $key => $value) {
            $gameCount = 0;//今日赛事数
            foreach ($toDayEvent as $k => $v) {
                if ($v[1] == $value[0])//判断是否为同一联赛
                {
                    $gameCount++;
                }
            }
            $unionName[$key]['gameCount'] = $gameCount;

            switch ($value[6]) {
                case 0 :
                    $unionLevelOne[] = $unionName[$key];//级别一
                case 1 :
                    $unionLevelOne[] = $unionName[$key];
                    break;//级别一
                case 2 :
                    $unionLevelTwo[] = $unionName[$key];
                    break;//级别二
                case 3 :
                    $unionLevelThree[] = $unionName[$key];
                    break;//级别三
            }

        }
        //指数


        foreach ($unionInfo as $key => $value) {
            //标识不显示的联赛
            $unionInfo[$key]['is_display'] = in_array($value[1], $unionIdArr) || empty($unionIdArr) ? 1 : 0;

            //比赛日期格式转换
            $unionInfo[$key][8] = date('Y-m-d', strtotime($value[8]));
            //比赛开始到现在的时间
            $unionInfo[$key]['gameTimeed'] = time() > strtotime($value[8] . $value[9]) ? time() - strtotime($value[8] . $value[9]) : 0;
            //指数
            $companyInstant = array();
            foreach ($companyKey as $comK => $comV) {
                //亚盘
                foreach ($value[21] as $k => $v) {
                    if ($comV == $k) {
                        $companyInstant[$comV][21] = $v;
                    }
                }
                //欧赔
                foreach ($value[22] as $k => $v) {
                    if ($comV == $k) {
                        $companyInstant[$comV][22] = $v;
                    }
                }
                //大小
                foreach ($value[23] as $k => $v) {
                    if ($comV == $k) {
                        $companyInstant[$comV][23] = $v;
                    }
                }
            }
            $unionInfo[$key]['companyInstant'] = $companyInstant;
            unset($unionInfo[$key][21], $unionInfo[$key][22], $unionInfo[$key][23]);

        }

        //语言判断 1:简体 2：繁体 3：EN
        $indicesLanguageSle = I('cookie.indicesLanguageSle');

        $this->assign('indicesLanguageSle', $indicesLanguageSle ? $indicesLanguageSle : 1);
        $this->assign('unionLevelOne', $unionLevelOne);//级别一
        $this->assign('unionLevelTwo', $unionLevelTwo);//级别二
        $this->assign('unionLevelThree', $unionLevelThree);//级别三
        $this->assign('unionInfo', $unionInfo);//赛事列表
        $this->assign('company', $company);//公司
        $this->assign('companyId', $companyId);//指数列表要显示的公司的公司ID

        $this->score_nav();
        if (I('var') == 'dump') var_dump($unionInfo);
        $this->display();
    }


    /**
     * Liangzk <Liangzk@qc.com>
     *
     */
    public function generate()
    {

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


        //赛事事件、技术
//        $detail = $this->get_curl("/Home/Webfb/detail", "gameId=".$gameId."", C('CURL_DOMAIN_QW'))['data'];
        $webfbService = new \Home\Services\WebfbService();
        $detail = $webfbService->getDetailWeb($gameId);   //数据库取值

        $eventRe_t = $detail['t'][$gameId];//赛事事件
        $eventRe_s = $detail['s'][$gameId];//赛事技术

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


        //比赛阵容
//        $lineup = $this->get_curl('/Home/Webfb/lineup',"gameId=".$gameId."",C('CURL_DOMAIN_QW'))['data'];
        $lineup = $webfbService->getLineup($gameId);

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
     * @User Liangzk <Liangzk@Qc.com>
     * @DateTime 2016-01-24
     *    亚盘赔率、大小比较、欧洲指数、数据分析、事情赛况、推荐详情的头部
     */
    public function oddsHeader($gameId)
    {
        $gameInfo = M('GameFbinfo g')
            ->join('INNER JOIN qc_union u ON  g.union_id = u.union_id')
            ->where(['g.game_id' => $gameId])
            ->field('g.id,g.union_id,g.home_team_name,g.away_team_name,g.union_name,g.home_team_id,g.away_team_id,g.gtime,g.score,g.half_score,g.home_team_rank,g.away_team_rank,g.fsw_exp,fsw_ball,g.fsw_exp_home,g.fsw_exp_away,g.fsw_ball_home,g.fsw_ball_away,g.is_gamble,g.is_video,g.game_state,g.is_show,u.is_sub,g.is_video,g.is_flash')
            ->select();

        //是否flash
//        $betRes = M('FbLinkbet')->field('game_id,is_link,flash_id,md_id')->where(['game_id' => $gameId])->find();
//
//        if (empty($betRes) || $gameInfo['game_state'] == -1) {
//            $gameInfo[0]['is_flash'] = '0';
//        } else {
//            if (in_array($gameInfo['game_state'], [1, 2, 3, 4])) {
//                if (!empty($betRes['md_id']))
//                    $gameInfo[0]['is_flash'] = '1';
//                else
//                    $gameInfo[0]['is_flash'] = '0';
//            } else {
//                $gameInfo[0]['is_flash'] = '1';
//            }
//        }

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
            $aoAdds[$key]['day'] = substr($value[8], 4, 2) . '-' . substr($value[8], 6, 2);
            $aoAdds[$key]['hour'] = substr($value[8], 8, 2) . ':' . substr($value[8], 10, 2);
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
//        $game_res = $this->get_curl("/Home/Webfb/europeOdds", "gameId=$game_id", C('CURL_DOMAIN_QW'));
        $webfbService = new \Home\Services\WebfbService();
        $data = $webfbService->getAllOdds($game_id, 2);
        $game_res = $this->eur_rank($data);
//        $game_res = $game_res['data'];
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
                    $val[$i]['time_day'] = substr($val[$i][3], 4, 2) . '-' . substr($val[$i][3], 6, 2);
                    $val[$i]['time_hour'] = substr($val[$i][3], 8, 2) . ':' . substr($val[$i][3], 10, 2);
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
            $game_res['oo'][$key]['time_day'] = substr($val[0][3], 4, 2) . '-' . substr($val[0][3], 6, 2);
            $game_res['oo'][$key]['time_hour'] = substr($val[0][3], 8, 2) . ':' . substr($val[0][3], 10, 2);
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
//        var_dump($game_res['oo']);
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
     * 数据分析页面
     */
    public function dataFenxi()
    {
        $game_id = I('game_id');
        $this->assign('gameId', $game_id);
        $webfbService = new \Home\Services\WebfbService();
        $res = $webfbService->getAnaForFile($game_id, 1);
        $this->assign('goals', $this->goals($game_id));
        $home = $res[0]['content'][1];
        //数据库查询数据
        //对往战绩
        $GameFbinfo = M('GameFbinfo');
        $baseRes = $GameFbinfo->field('*')->where('game_id = '.$game_id)->find();
        $home_name = explode('(',explode(',',$baseRes['home_team_name'])[0])[0];
        $away_name = explode('(',explode(',',$baseRes['away_team_name'])[0])[0];
        $fbService = new \Common\Services\FbdataService();
        $duiwang = $fbService->getMatchFight($baseRes['home_team_id'],$baseRes['away_team_id'],$baseRes['gtime'] ,1);
        foreach($duiwang as $k=>$v)
        {
            $chupan = M("GameFbinfo")->field('corner,fsw_exp_home,fsw_exp_away,home_team_name,fsw_eur_home as eur_home,fsw_eur_draw as eur_draw,fsw_eur_away as eur_away')->where(['game_id'=>$v[1]])->find();
//            $oupei = M("FbOdds")->where(['game_id'=>$v[1],'company_id'=>3])->getField('exp_value');
//            $ou = explode(',',explode('^',$oupei)[1]);
            unset($duiwang[$k][16]);
            $duiwang[$k][] = $chupan['eur_home'];
            $duiwang[$k][] = $chupan['eur_draw'];
            $duiwang[$k][] = $chupan['eur_away'];
            $duiwang[$k][] = $chupan['fsw_exp_home']?$chupan['fsw_exp_home']:'';
            $duiwang[$k][] = $chupan['fsw_exp_away']?$chupan['fsw_exp_away']:'';
            $corner = explode('-',$chupan['corner']);
            $duiwang[$k][] = $corner[0]?$corner[0]:'';
            $duiwang[$k][] = $corner[1]?$corner[1]:'';
            $duiwang[$k] = array_values($duiwang[$k]);
        }
        $this->assign('match_fight', $this->match_fight($duiwang, $home_name));//处理对阵历史

        //近期交战
        $jinqi_home = $fbService->getRecentFight($baseRes['home_team_id'] ,$baseRes['gtime'],1);
        $jinqi_home = $this->jinqi($jinqi_home,$home_name);
        $jinqi_away = $fbService->getRecentFight($baseRes['away_team_id'] ,$baseRes['gtime'],1);
        $jinqi_away = $this->jinqi($jinqi_away,$away_name);
        $jinqi_data = [
            0=>['name'=>'recent_fight1','content'=>$jinqi_home],
            1=>['name'=>'recent_fight2','content'=>$jinqi_away]
                        ];
        $this->assign('recent_fight', $this->recent_fight($jinqi_data));//处理近期赛事

        //处理接口返回数据
        foreach ($res as $val) {
            if ($val['name'] == 'match_three') {
                $this->assign('match_three', $this->match_three($val['content']));//未来3场数据赋值
            }
//            if ($val['name'] == 'recent_fight') {
//                $this->assign('recent_fight', $this->recent_fight($val['content']));//处理近期赛事
//            }
//            if ($val['name'] == 'match_fight') {
//                $this->assign('match_fight', $this->match_fight($val['content'], $home));//处理对阵历史
//            }
            if ($val['name'] == 'match_integral') {
                $this->assign('match_integral', $this->match_integral($val['content'], $home_name));//处理联赛积分
            }
            if ($val['name'] == 'match_panlu') {
                $this->assign('match_panlu', $this->match_panlu($val['content']));//处理联赛盘路
            }
            if ($val['name'] == 'cupmatch_integral') {
                $this->assign('cupmatch_integral', $this->cupmatch_integral($val['content']));//处理杯赛排名
            }
        }
        //新模块数据
        $appService = new \Home\Services\AppfbService();
        $new_res = $appService->getAnaForFile($game_id, 1);
        foreach ($new_res as $val) {
            if ($val['name'] == 'sameExp') {
                $this->assign('sameExp', $this->sameExp($val['content']));//处理历史亚盘
            }
            if ($val['name'] == 'Compare') {
                $this->assign('compare', $this->Compare($val['content']));//处理数据对比
            }
            if ($val['name'] == 'St') {
                $this->assign('st', $val['content']);//处理阵容情况
            }
        }
        $appService2 = new \Home\Services\AppdataService();
        $team_info = $appService2->getLineup($game_id);
        if (empty($team_info[0]) && empty($team_info[1])) $team_info = null;
        if ($team_info) {
            $h = array();
            foreach ($team_info as $key => $val) {
                foreach ($val as $k => $v) {
                    if ($v[3] == '0') {
                        $h[$key][] = $v;
                        unset($team_info[$key][$k]);
                    }
                }

            }
            $res['s'] = $team_info;
            $res['h'] = $h;
            $this->assign('team', $res);
        }
        $this->oddsHeader($game_id);

        //必发指数
//        $bifa = $this->bifa($game_id);
        $bifa = [];
        $this->assign('bifa', $bifa);

        $this->display();
    }

    //数据详情近期对战数据处理
    public function jinqi($data,$name)
    {
        foreach($data as $k=>$v)
        {
            $chupan = M("GameFbinfo")->field('corner,fsw_exp_home,fsw_exp_away,home_team_name')->where(['game_id'=>$v[1]])->find();
            $oupei = M("FbOdds")->where(['game_id'=>$v[1],'company_id'=>3])->getField('exp_value');
            $ou = explode(',',explode('^',$oupei)[1]);
            array_unshift($data[$k],$name);
            unset($data[$k][17]);
            $data[$k][] = $ou[0]?$ou[0]:'';
            $data[$k][] = $ou[0]?$ou[1]:'';
            $data[$k][] = $ou[0]?$ou[2]:'';
            $data[$k][] = $chupan['fsw_exp_home']?$chupan['fsw_exp_home']:'';
            $data[$k][] = $chupan['fsw_exp_home']?$chupan['fsw_exp_away']:'';
            $corner = explode('-',$chupan['corner']);
            $data[$k][] = $corner[0]?$corner[0]:'';
            $data[$k][] = $corner[1]?$corner[1]:'';
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

    /*
     * 数据分析即时赔率页面
     */
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
    public function match_fight($arr, $home)
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
            if ($v[5] == $home) {
                $v['home_c'] = 'text-red';
                $total_none++;
                $none = true;
                if ($v[14] == 1) $v['y_home_c'] = 'text-red';
                if ($v[14] == -1) $v['y_away_c'] = 'text-red';
            }
            if ($v[7] == $home) {
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
            if (trim($home) != trim($v[5])) $is_home = -1;
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
            $v[6] = trim($v[5]);
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
            foreach ($val['content'] as $v) {
                $name = mb_substr($v[0], 0, 3, 'utf-8');
                $total++;
                $none = "n";
                if (strpos($v[6], $name) !== false) {
                    $v['home_c'] = 'text-red';
                    if ($v[15] == 1) $v['y_home_c'] = 'text-red';
                    if ($v[15] == -1) $v['y_away_c'] = 'text-red';
                    if ($key === 0) {
                        $none = "y";
                        $total_none++;
                    }
                }
                if (strpos($v[8], $name) !== false) {
                    $v['away_c'] = 'text-red';
                    if ($v[15] == 1) $v['y_away_c'] = 'text-red';
                    if ($v[15] == -1) $v['y_home_c'] = 'text-red';
                    if ($key === 1) {
                        $none = "y";
                        $total_none++;
                    }
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
                $is_home = 1;
                if (strpos(trim($v[8]), trim($v[0])) !== false) $is_home = -1;
                if (strpos(trim($v[0]), trim($v[8])) !== false) $is_home = -1;
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
                $_b = $this->_compare($v[11], $v[12]);
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
            $arr[0] = $arr[2] = 'text-blue';
            $arr[1] = 'text-red';
            $arr[5] = 'bold';
            if ($is_home == 1) {
                $arr['game_res'] = '赢';
                $arr['game_res_col'] = 'text-red';
            } else {
                $arr['game_res'] = '输';
                $arr['game_res_col'] = 'text-green';
            }
        } elseif ($fir < $sen) {
            $arr[0] = 'text-red';
            $arr[1] = $arr[3] = 'text-blue';
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
            $arr[1] = 'text-blue';
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
        $page = I('page', 1, 'int');
        if ($gameId < 1) {
            $this->error();
        }
        //获取该赛事玩法的推荐列表
        if (!$QuizUser = S('web_statistics_' . $gameId . $play_type . $game_type)) {
            $QuizUser = $this->getUserRank($gameId, $play_type);
            S('web_statistics_' . $gameId . $play_type . $game_type, $QuizUser, 120);
        }
        $QuizUser = $this->getTenMaster(is_login(), $gameId, $game_type, $play_type);
        $newCount = count($QuizUser);
        if ($newCount > 0) {
            //登陆用户ID
            $userId = is_login();
            foreach ($QuizUser as $k => $v) {
                //判断语音推介是否通过
                if ($v['is_voice'] == 1) {
                    if ($v['voice']) {
                        $QuizUser[$k]['voice'] = Think\Tool\Tool::imagesReplace($v['voice']);
                    }
                } else {
                    $QuizUser[$k]['voice'] = '';
                }
                //计算前台显示总点击量
                $QuizUser[$k]['quiz_number'] = $v['quiz_number'] + $v['extra_number'];
            }
            //dump($QuizUser);
            $gambleIdArr = array_map("array_shift", $QuizUser);
            if (is_login()) {
                //是否已被查看
                $quizLog = M('quizLog')->master(true)->where(array('game_type' => $game_type, 'user_id' => is_login(), 'gamble_id' => ['in', $gambleIdArr]))->getField('gamble_id', true);
                foreach ($QuizUser as $k => $v) {
                    if (in_array($v['gamble_id'], $quizLog)) {
                        $QuizUser[$k]['is_check'] = 1;
                    }
                }
            }

            //获取用户ID，并获取用户的粉丝数
            $userIdArr = array();
            foreach ($QuizUser as $key => $value) {
                $userIdArr[] = $value['user_id'];
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
            $followIdArr = M("FollowUser")->where(array('user_id' => is_login()))->field("follow_id")->select();
            foreach ($followIdArr as $key => $value) {
                $followIds[] = $value['follow_id'];
            }
            $this->assign('followIds', $followIds);
            $this->assign('play_type', $play_type);
            $this->assign('userId', $userId);
            $_num = ceil(count($QuizUser)/2);
            $_tmp = array_chunk($QuizUser,$_num,true);
            $this->assign('QuizUser', $_tmp);
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
        switch ($game_type) {
            case '1':
                switch ($play_type) {
                    case '1':
                    case '-1':
                        $Lv = 'lv';
                        $gambleType = 1;
                        break;
                    case '2':
                    case '-2':
                        $Lv = 'lv_bet';
                        $gambleType = 2;
                        break;
                }
                break;
            case '2':
                $Lv = 'lv_bk';
                break;
        }
        //获取参与该赛程推荐的记录
        $gamble = $gameModel
            ->join("left join qc_front_user f on f.id=g.user_id")
            ->where(['g.game_id' => $game_id, 'g.play_type' => $play_type])
            ->field("g.id,g.game_id,g.user_id,g.is_impt,g.union_name,g.home_team_name,g.away_team_name,g.result,g.play_type,g.chose_side,g.handcp,g.odds,g.tradeCoin,g.desc,g.create_time,g.voice,g.is_voice,g.voice_time,g.quiz_number,g.extra_number,f.nick_name,f.{$Lv} lv,f.head")
            ->select();
        if (!$gamble) {
            return;
        }
        $gamble = HandleGamble($gamble, 0, true, $game_type);
        foreach ($gamble as $k => $v) {
            //获取比赛结果和推荐信息
            //周，月，季胜率
            $gamble[$k]['weekWin'] = D('GambleHall')->CountWinrate($v['user_id'], $game_type, 1, false, false, 0, $gambleType);
            //该场销量
            $gamble[$k]['check_number'] = M('quizLog')->where(array('gamble_id' => $v['id']))->count();
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
            $sort_lv[] = $v['lv'];        //等级
            $sort_weekWin[] = $v['weekWin'];  //周胜率
            $sort_check[] = $v['check_number']; //该场销量
            $sort_time[] = $v['create_time']; //发布时间
        }
        array_multisort($sort_lv, SORT_DESC, $sort_weekWin, SORT_DESC, $sort_check, SORT_DESC, $sort_time, SORT_DESC, $Gamble);
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
        $where['game_id'] = $game_id;
        //3:射门次数,4:射正次数,5:犯规次数,6:角球次数,7:角球次数(加时),8:任意球次数,9:越位次数,11:黄牌数,12:黄牌数(加时),13:红牌数,14:控球时间,44:危险进攻
        $where['s_type'] = ['in', [3, 4, 5, 6, 7, 8, 9, 11, 12, 13, 14, 44]];
        $game_info = M("StatisticsFb")->where($where)->getField('s_type as type,home_value as home,away_value as away');
        //角球
        $game['corner']['home'] = $game_info[6]['home'] + $game_info[7]['home'];
        $game['corner']['away'] = $game_info[6]['away'] + $game_info[7]['away'];
        //射门次数
        $game['shoot']['home'] = $game_info[3]['home'];
        $game['shoot']['away'] = $game_info[3]['away'];
        //射中次数
        $game['quiver']['home'] = $game_info[4]['home'];
        $game['quiver']['away'] = $game_info[4]['away'];
        //犯规次数
        $game['foul']['home'] = $game_info[5]['home'];
        $game['foul']['away'] = $game_info[5]['away'];
        //任意球次数
        $game['freekick']['home'] = $game_info[8]['home'];
        $game['freekick']['away'] = $game_info[8]['away'];
        //越位次数
        $game['offside']['home'] = $game_info[9]['home'];
        $game['offside']['away'] = $game_info[9]['away'];
        //危险进攻
        $game['dangerous']['home'] = $game_info[44]['home'];
        $game['dangerous']['away'] = $game_info[44]['away'];
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
        $game['y_card']['home'] = $game_info[11]['home'] + $game_info[12]['home'];
        $game['y_card']['away'] = $game_info[11]['away'] + $game_info[12]['away'];
        //红牌
        $game['r_card']['home'] = $game_info[13]['home'];
        $game['r_card']['away'] = $game_info[13]['away'];
        //控球率
        $game['hold']['home'] = $game_info[14]['home'];
        $game['hold']['away'] = $game_info[14]['away'];
        $game['hold']['home_num'] = $game['hold']['home']?rtrim($game['hold']['home'],'%')>15?rtrim($game['hold']['home'],'%'):15:0;
        $game['hold']['away_num'] = $game['hold']['away']?rtrim($game['hold']['away'],'%')>15?rtrim($game['hold']['away'],'%'):15:0;

        $team_info = M("GameFbinfo")->field('home_team_name,away_team_name,union_name,gtime,game_state,score,home_team_id,away_team_id,bet_code,web_video,weather,update_time')->where(['game_id' => $game_id])->find();
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
            if(strpos($v['weburl'],'sportstream365') !== false)
            {
                $videolist[$k]['url_type'] = 1;
                $id = explode('game=',$v['weburl'])[1];
                $videolist[$k]['weburl'] = 'http://sportstream365.com/viewer/frame/?header=1&autoplay=1&width=750&height=480&game='.$id;
            }else{
                $videolist[$k]['url_type'] = 0;
            }
        }
        array_merge($videolist);
        $this->assign('videolist',$videolist);

        //获取赛事直播源默认取第一个
        $game['video_url'] = reset($videolist)['weburl'];
        $game['url_type'] = reset($videolist)['url_type'];
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
        $has = M('FbLinkbet')->field('game_id,is_link,flash_id,md_id')->where(['game_id' => $game_id])->find();
        if ($has && $flashList) {
            if (intval($flashList['game_state']) == 0) {//未开赛，有关联的就显示
                $flashUrl = '';
            } elseif (intval($flashList['game_state']) != 0 && $has['md_id']) {//比赛开始，存在动画数据时才显示
                $flashUrl = 'http://dh.qqty.com/svg/svg-f-animate.html?game_id=' . $game_id;
            }
        }

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
        $this->assign('game', $game);
        $notice = Tool::getAdList(42, 5) ?: [];
        $this->assign('ad', $notice);
        $this->assign('game_id', $game_id);
        $this->assign('mqttOpt', $mqtt);
        $this->assign('userStatus', $userStatus);
        $this->assign('svg_url', $flashUrl);
        $this->assign('ip', get_client_ip());
        $this->assign('chatOpen', $status);
        $this->assign('userInfo', $uinfo ? json_encode($uinfo) : '');
        $this->assign('client_id', md5(get_client_ip() . $game_id . rand(0, 99999)));
        $this->assign('esrAddress', C('ESR_ADDRESS'));
        $this->display();
    }

    /**
     * 获取当前最新赔率
     */
    public function odds()
    {
        if (IS_POST) {
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
    public function getTenMaster($userId, $gameId, $gameType = 1, $gambleType = 1, $playType = 0, $getTotal = false)
    {
        //根据亚盘、竞彩玩法组装条件
        $jWhere = $wh = $userWeekGamble = $userids = $pageList = $userGamble = [];
        $time = $gameType == 1 ? (10 * 60 + 32) * 60 : (12 * 60) * 60;
        if ($gameType == 1) {
            $gambleModel = M('Gamble');
            if (abs($gambleType) == 1) {
                $lvField = 'f.lv lv';
                $wh['result'] = ['IN', ['1', '0.5', '2', '-1', '-0.5']];
                $wh['play_type'] = ['IN', [-1, 1]];
                $jWhere['play_type'] = ['IN', [-1, 1]];
            } else {
                $lvField = 'f.lv_bet lv';
                $wh['result'] = ['IN', [1, -1]];
                $wh['play_type'] = ['IN', [2, -2]];
                $jWhere['play_type'] = ['IN', [2, -2]];
            }

            if ($playType)
                $wh['play_type'] = (int)$playType;
        } else {
            $gambleModel = M('Gamblebk');
            $lvField = 'f.lv_bk lv';
            $wh['result'] = ['IN', ['1', '0.5', '2', '-1', '-0.5']];
            $wh['play_type'] = ['IN', [-1, 1]];
            $jWhere['play_type'] = ['IN', [-1, 1]];
        }
        //获取参与该场赛事竞猜的用户
        $fields = ['g.id gamble_id', 'g.game_id', 'g.user_id', 'g.play_type', 'g.chose_side', 'g.handcp', 'g.odds', 'g.is_impt', 'g.union_name', 'g.home_team_name', 'g.away_team_name', 'g.create_time', 'g.voice', 'g.is_voice', 'g.voice_time', 'g.quiz_number', 'g.extra_number',
            'g.result', 'g.tradeCoin', 'g.desc', 'g.create_time', 'f.head face', 'f.nick_name', $lvField, '(g.quiz_number + g.extra_number) as quiz_number'];

        if ($getTotal === true) {
            $list = $gambleModel
                ->field('DISTINCT user_id')
                ->where(['game_id' => $gameId, 'play_type' => $wh['play_type']])
                ->select();
            return (string)count($list);
        } else {
            $list = $gambleModel->alias("g")
                ->join("left join qc_front_user f on f.id = g.user_id")
                ->field($fields)
                ->where(['game_id' => $gameId, 'play_type' => $wh['play_type']])
                ->group('g.user_id')
                ->order('lv desc')
                ->limit(100)
                ->select();
        }

        if ($list) {
            list($wBegin, $wEnd) = getRankBlockDate($gameType, 1);//周
            list($mBegin, $mEnd) = getRankBlockDate($gameType, 2);//月
            list($jBegin, $jEnd) = getRankBlockDate($gameType, 3);//季

            $wBeginTime = strtotime($wBegin) + $time;
            $wEndTime = strtotime($wEnd) + 86400 + $time;

            $mBeginTime = strtotime($mBegin) + $time;
            $mEndTime = strtotime($mEnd) + 86400 + $time;

            $jBeginTime = strtotime($jBegin) + $time;
            $jEndTime = strtotime($jEnd) + 86400 + $time;

            foreach ($list as $vv) {
                $userids[] = $vv['user_id'];
            }

            $wWhere['user_id'] = ['IN', $userids];
            $wWhere['result'] = ['IN', ['1', '0.5', '-1', '-0.5']];
            $wWhere['play_type'] = $jWhere['play_type'];
            $wWhere['create_time'] = ["between", [$wBeginTime, $wEndTime]];

            $userGamble = $gambleModel
                ->field('user_id, GROUP_CONCAT(result) as result')
                ->where($wWhere)
                ->group('user_id')
                ->select();

            //是否查看过本赛程
            if (isset($userId)) {
                $gambleId = (array)M('QuizLog')->where(['user_id' => $userId, 'game_id' => $gameId, 'game_type' => $gameType])->getField('gamble_id', true);
            }

            //周竞猜
            $userWeekGamble = array_column($userGamble, 'result', 'user_id');
            $lv = $weekSort = $monthSort = $seasonSort = $tenGamble = $sortTime = [];

            //月竞猜
            $jWhere['result'] = ["IN", ['1', '0.5', '-1', '-0.5']];
            $jWhere['create_time'] = ["between", [$jBeginTime, $jEndTime]];

            foreach ($list as $k => $v) {
                //用户信息
                $list[$k]['face'] = frontUserFace($v['face']);
                $list[$k]['is_trade'] = in_array($v['gamble_id'], $gambleId) ? '1' : '0';
//                $list[$k]['desc']       = (string)$pageList[$k]['desc'];

                //周胜率计算
                $wWin = $wHalf = $wTransport = $wDonate = 0;
                $resultArr = explode(',', $userWeekGamble[$v['user_id']]);

                foreach ($resultArr as $resultV) {
                    if ($resultV == '1') $wWin++;
                    if ($resultV == '0.5') $wHalf++;
                    if ($resultV == '-1') $wTransport++;
                    if ($resultV == '-0.5') $wDonate++;
                }
                $list[$k]['weekPercnet'] = (string)getGambleWinrate($wWin, $wHalf, $wTransport, $wDonate);


                //月、季胜率计算
                $jWhere['user_id'] = $v['user_id'];
                $jWin = $mWin = $jHalf = $mHalf = $jTransport = $mTransport = $jDonate = $mDonate = 0;
                $seasonGamble = $gambleModel->field(['result', 'earn_point', 'create_time'])->where($jWhere)->select();
                foreach ($seasonGamble as $key => $val) {
                    switch ($val['result']) {
                        case '1':
                            $jWin++;
                            if ($val['create_time'] >= $mBeginTime && $val['create_time'] <= $mEndTime) $mWin++;
                            break;

                        case '0.5':
                            $jHalf++;
                            if ($val['create_time'] >= $mBeginTime && $val['create_time'] <= $mEndTime) $mHalf++;
                            break;

                        case '-1':
                            $jTransport++;
                            if ($val['create_time'] >= $mBeginTime && $val['create_time'] <= $mEndTime) $mTransport++;
                            break;

                        case '-0.5':
                            $jDonate++;
                            if ($val['create_time'] >= $mBeginTime && $val['create_time'] <= $mEndTime) $mDonate++;
                            break;
                    }
                }

                $list[$k]['monthPercnet'] = (string)getGambleWinrate($mWin, $mHalf, $mTransport, $mDonate);
                $list[$k]['seasonPercnet'] = (string)getGambleWinrate($jWin, $jHalf, $jTransport, $jDonate);

                //近十场胜负、胜平负
                $wh['user_id'] = $v['user_id'];
                $tenGamble = $gambleModel->where($wh)->order("id desc")->limit(10)->getField('result', true);
//                $list[$k]['tenGamble'] = $tenGamble;
                $list[$k]['tenGambleRate'] = countTenGambleRate($tenGamble);;

                $_TenGambleSort = 0;
                foreach ($tenGamble as $gamble_v) {
                    if ($gamble_v == 1 || $gamble_v == 0.5) {
                        $_TenGambleSort++;
                    }
                }

                //过滤近十中5一下
                if ($_TenGambleSort < 5) {
                    unset($list[$k]);
                    continue;
                } else {
                    $list[$k]['ten_rate'] = $_TenGambleSort;
                }


                //排序数组
                $lv[] = $v['lv'];
                $tenGambleSort[] = $_TenGambleSort;
                $weekSort[] = $list[$k]['weekPercnet'];

                $monthSort[] = $list[$k]['monthPercnet'];
                $seasonSort[] = $list[$k]['seasonPercnet'];
                $sortTime[] = $v['create_time'];
                unset($list[$k]['lv_bet']);
            }
            //排序：近十中几》周胜》等级》月》季》发布时间
            array_values($list);
            array_multisort($tenGambleSort, SORT_DESC, $weekSort, SORT_DESC, $lv, SORT_DESC, $monthSort, SORT_DESC, $seasonSort, SORT_DESC, $list);
        }
        return array_slice($list, 0, 10) ?: [];
    }


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
        $redis = connRedis();

        $temp_log = $redis->lRange('qqty_chat_' . $game_type . '_' . $game_id, 0, 100);
        $members = $redis->sMembers('qqty_chat_forbid_userids');
        $chat_log = [];
        foreach ($temp_log as $k => $v) {
            $lg = json_decode($v, true);
            if (in_array($lg['user_id'], $members)) {
                unset($temp_log[$k]);
            } else {
                if ($game_type == 1) {
                    $lv = $lg['lv'] > $lg['lv_bet'] ? $lg['lv'] : $lg['lv_bet'];
                } else {
                    $lv = $lg['lv_bk'];
                }
                unset($lg['lv'], $lg['lv_bet'], $lg['lv_bk']);
                $lg['lv'] = $lv >= 4 ? $lv : '';
                $chat_log[] = $lg;
            }
        }

        //发送欢迎语
        $userStatus = 1;
        if ($userid) {
            $uinfo = M('FrontUser')->field('id as user_id,nick_name,head,lv,lv_bet,lv_bk,coin,unable_coin,status')->where(['id' => $userid])->find();

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
                    $data = array_merge($uinfo, ['content' => "Hi,大家好,我是 {$uinfo['nick_name']},很高兴和大家一起来聊球。", 'msg_id' => $msg_id, 'chat_time' => time()]);
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
        $this->ajaxReturn(['isAdmin' => $isAdmin, 'chatLog' => array_reverse($chat_log)]);
    }

    /**
     * 屏蔽用户聊天
     */
    public function forbid()
    {
        $type = I('type');
        $game_id = I('game_id');
        $game_type = I('game_type');
        $content = I('content');
        $forbid_id = I('user_id');
        $msg_id = I('msg_id');
        $chat_time = I('chat_time');
        $userid = is_login();

        try {
            if (!$type || !$game_id || !$game_type || !$content || !$forbid_id || !$msg_id)
                throw new Exception('参数错误', 101);

            if (!$userid)
                throw new Exception('请先登录', 1011);

            if ($userid == $forbid_id)
                throw new Exception('不能举报、屏蔽自己', 3020);

            $room_id = $game_type . '_' . $game_id;
            $forbid = [
                'user_id' => $forbid_id,
                'type' => $type,
                'content' => json_encode($content),
                'from_id' => $userid,
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

        if (!$responseList = S($cacheKey)) {
            $akey = $game_type == 1 ? 'game_id' : 'gamebk_id';
            $articleList = (array)M('PublishList')->field(['id', 'app_time as time', 'class_id', 'source', 'title', 'remark', 'click_number', 'img', 'content', 'add_time', 'game_id', 'short_title'])
                ->where([$akey => $game_id, 'status' => 1])
                ->order('is_recommend desc, is_channel_push desc, add_time desc')
                ->limit(20)
                ->select();


            $videoList = (array)M('Highlights')->field(['id', 'title', 'remark', 'click_num as click_number', 'img', 'app_url', 'app_ischain', 'is_prospect', 'add_time as time', 'app_isbrowser'])
                ->where(['game_id' => $game_id, 'game_type' => $game_type, 'app_url' => ['neq', ''], 'status' => 1])
                ->order('is_recommend desc, add_time asc')
                ->limit(20)
                ->select();
            $list = array_merge($articleList, $videoList);
            $publishClass = M('PublishClass')->where("status=1")->getField('id, name');
            foreach ($list as $k => $v) {
                if ($v['class_id']) {
                    $list[$k]['type'] = 1;
                } else {
                    $list[$k]['type'] = 2;
                }
                $addTimeSort[] = $v['add_time'];
                if (isset($v['class_id'])) {

                    $list[$k]['source'] = $v['source'] . '/' . $publishClass[$v['class_id']];
                } else {
                    $list[$k]['source'] = '';
                }
                if ($v['game_id']) {
                    $gameinfo = $this->gameinfos($v['game_id'], $game_type);
                    $list[$k] = array_merge($list[$k], $gameinfo);
                }

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
                $img = http_to_https('http://www.qqty.com/Public/Home/images/common/loading.png');
                $articleList[$k]['img'] = [$img];
            }

            //来源
            $articleList[$k]['source'] = $v['source'] . '/' . $publishClass[$v['class_id']];

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
        $this->oddsHeader($game_id);
        $webfbService = new \Home\Services\WebfbService();
        $data = $webfbService->getOddsInfoM($game_id, $comp_id, $half, $oddtype);
        $res = array_reverse($data['data']);
        $home_tmp = $away_tmp = $per_tmp = null;
        foreach ($res as $key => $val) {
            if (count($val) == 6) continue;
            $res[$key]['home_c'] = $this->oddshis_color($val[2], $home_tmp);
            $home_tmp = $val[2];
            if($sign == 2)
            {
                $res[$key]['per_c'] = $this->oddshis_color($val[3], $per_tmp);
                $per_tmp = $val[3];
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
        if ($b == null) return '';
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

}

?>