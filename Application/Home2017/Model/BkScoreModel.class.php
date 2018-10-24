<?php
ini_set('mongo.long_as_object', 1);
use Think\Model;

/**
 * 比分页面
 */
class BkScoreModel extends Model
{
    public $fenxiGameinfo = [];
    public $preMatchinfo = '';

    public function _initialize()
    {
        C('HTTP_CACHE_CONTROL', 'no-cache,no-store');
        parent::_initialize();
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
     * @ 对完场赛事进行缓存
     * @ author liuweitao 906742852@qq.com
     * @ $_time 查询时间
     */
    public function over_game($time, $type = 1, $is_now = 0)
    {
        $_time = date('Y-m-d', $time);
        S($_time . '_bk_' . $type, NULL);//按照日期做数据缓存,开发时打开更新数据
        if (S($_time . '_bk_' . $type)) {
            $arr = S($_time);
        } else {
            $res = $this->_over_game($time, $type, $is_now);
            $arr = $res;
            S($_time . '_bk_' . $type, $res, 60 * 5);
        }
        return $arr;
    }


    /**
     * @ 对完场赛事进行整理
     * @ author liuweitao 906742852@qq.com
     * @ $_time 查询时间
     */
    public function _over_game($date, $type = 1, $is_now)
    {

        if (!empty($date)) {
            $startTime = strtotime(date('Y-m-d', $date) . ' 14:35:00');
            $endTime = $startTime + 3600 * 24;
        } else {
            if (strtotime('14:35:00') < time()) {
                $startTime = strtotime('14:35:00');
                $endTime = strtotime('14:35:00') + 3600 * 24;
            } else {
                $startTime = strtotime('14:35:00') - 3600 * 24;
                $endTime = strtotime('14:35:00');
            }
        }
        $map['game_timestamp'] = array('between', array($startTime, $endTime));
        if ($type == 1 && $is_now === 0) $map['game_status'] = -1;
        $res = mongo('bk_game_schedule')->where($map)->order('game_timestamp')->select();
        if ($is_now == 1) {
            $startTime = strtotime(date('Y-m-d', $date) . ' 7:00:00');
            $endTime = strtotime(date('Y-m-d', $date) . ' 10:31:59');
            $_map['game_timestamp'] = array('between', array($startTime, $endTime));
            $_map['game_status'] = ['gt', 0];
            $_res = mongo('bk_game_schedule')->where($_map)->order('game_timestamp')->select();
            $res = array_merge($_res, $res);
        }
        if (empty($res)) return [];
        $data = $union_id = $union_data = $game_id = $rang_data = [];
        $ns = $now = $over = 0;
        foreach ($res as $key => $val) {
            if ($is_now == 1) {
                $up['quarter_time'] = $val['quarter_time'];
                if ($val['game_status'] > 0)
                    $ns = 1;
                elseif ($val['game_status'] == 0)
                    $now = 1;
                else
                    $over = 1;
            }
            $unionid = $val['union_id'];
            $union_id['num'][$unionid] = isset($union_id['num'][$unionid]) ? $union_id['num'][$unionid] + 1 : 1;
            $union_id['arr'][] = $unionid;
            $tmp = [];
            $game_info = $val['game_info'];
            $game_id[] = $val['game_id'];
            //上行开头
            $up['game_id'] = $val['game_id'];
            $up['union_name'] = $val['union_name'];
            $up['union_id'] = $val['union_id'];
            $up['union_color'] = $val['union_color'];
            $up['game_time'] = $val['game_timestamp'];
            $up['game_status'] = $val['game_status'];
            $up['is_sporttery'] = $val['is_sporttery'];
            //上行内容
            $up['team_name'] = $val['home_team_name'];
            $up['team_rank'] = $val['home_team_rank'];
            $up['all_court'] = $game_info[0];
            $up['half'] = ((int)$game_info[2] + (int)$game_info[4]) . '/' . ((int)$game_info[6] + (int)$game_info[8]);
            $up['bk_match'] = '半：' . ((int)$game_info[2] + (int)$game_info[4] - (int)$game_info[3] - (int)$game_info[5]);
            $up['all_match'] = '半：' . ((int)$game_info[2] + (int)$game_info[4] + (int)$game_info[3] + (int)$game_info[5]);
            $up_score = [$game_info[2], $game_info[4], $game_info[6], $game_info[8]];
            //下行内容
            $down['game_id'] = $val['game_id'];
            $down['team_name'] = $val['away_team_name'];
            $down['team_rank'] = $val['away_team_rank'];
            $down['all_court'] = $game_info[1];
            $down['half'] = ((int)$game_info[3] + (int)$game_info[5]) . '/' . ((int)$game_info[7] + (int)$game_info[9]);
            $down['bk_match'] = '全：' . ((int)$game_info[0] - (int)$game_info[1]);
            $down['all_match'] = '全：' . ((int)$game_info[0] + (int)$game_info[1]);

            //完场的比分颜色对比
            $up['res_col'] = '';
            $down['res_col'] = '';
            if ($val['game_status'] == -1) {
                if ($game_info[0] < $game_info[1]) {
                    $up['res_col'] = 'text-blue';
                    $down['res_col'] = 'text-red';
                } elseif ($game_info[0] > $game_info[1]) {
                    $up['res_col'] = 'text-red';
                    $down['res_col'] = 'text-blue';
                } else {
                    $up['res_col'] = 'text-red';
                    $down['res_col'] = 'text-red';
                }
            }

            if ($val['game_info'][24] != '') {
                $our = explode(',', $val['game_info'][24]);
                $up['our'] = sprintf("%01.2f", $our[0]);
                $down['our'] = sprintf("%01.2f", $our[1]);
            }

            $down_score = [$game_info[3], $game_info[5], $game_info[7], $game_info[9]];
            $head_back = ['1\'OT', '2\'OT', '3\'OT', '4\'OT'];
            if ($val['union_name'][0] == 'NCAA') {
                $head = ['上半场', '下半场'];
            } else {
                $head = ['一', '二', '三', '四'];
            }
            if ((int)$game_info[10] > 0) {
                $head_tmp = array_slice($head_back, 0, (int)$game_info[10]);
                $head = array_merge($head, $head_tmp);
                for ($i = 1; $i <= (int)$game_info[10]; $i++) {
                    $up_key = $i * 2 + 9;
                    $down_key = $i * 2 + 10;
                    $up_score[] = $game_info[$up_key];
                    $down_score[] = $game_info[$down_key];
                }
            }
            if ($val['union_name'][0] == 'NCAA') {
                $up_score = empty(array_filter($up_score)) ? ['', ''] : array_filter($up_score);
                $down_score = empty(array_filter($down_score)) ? ['', ''] : array_filter($down_score);
            }
            $up['score'] = $up_score;
            $down['score'] = $down_score;
            $tmp = ['head' => $head, 'info' => [$up, $down], 'text' => $game_info[19]];
            $data[] = $tmp;
            $tmp = [];
            $one = $two = '';
            if (strpos($val['game_odd'][0], '-') === false)
                $one = $val['game_odd'][0];
            else
                $two = ltrim($val['game_odd'][0], '-');
            if ($val['game_odd'][1]) $val['game_odd'][1] = sprintf("%.2f", substr(sprintf("%.3f", $val['game_odd'][1]), 0, -2));
            if ($val['game_odd'][2]) $val['game_odd'][2] = sprintf("%.2f", substr(sprintf("%.3f", $val['game_odd'][2]), 0, -2));
            if ($val['game_odd'][4]) $val['game_odd'][4] = sprintf("%.2f", substr(sprintf("%.3f", $val['game_odd'][4]), 0, -2));
            if ($val['game_odd'][5]) $val['game_odd'][5] = sprintf("%.2f", substr(sprintf("%.3f", $val['game_odd'][5]), 0, -2));
            $tmp = [
                1 => [$one, $val['game_odd'][1], $val['game_odd'][3], $val['game_odd'][4]],
                2 => [$two, $val['game_odd'][2], $val['game_odd'][3], $val['game_odd'][5]]
            ];
            $rang_data[$val['game_id']] = $tmp;
        }
        $union_arr = array_values(array_unique($union_id['arr']));
        $union_res = mongo('bk_union')->field('union_id,union_name,union_color,grade')->where(['union_id' => ['in', $union_arr]])->select();
        foreach ($union_res as $key => $val) {
            $union_data[$val['union_id']]['union_id'] = $val['union_id'];
            $union_data[$val['union_id']]['union_name'] = $val['union_name'];
            $union_data[$val['union_id']]['num'] = $union_id['num'][$val['union_id']];
            $union_data[$val['union_id']]['grade'] = $val['grade'];
            $union_data[$val['union_id']]['union_color'] = $val['union_color'];
        }
        $res = ['list' => $data, 'union' => $union_data, 'rang' => $rang_data];
        if ($is_now == 1) $res['status'] = ['ns' => $ns, 'now' => $now, 'over' => $over];
        return $res;
    }

    /**
     * @ 盘口数据处理
     * @author liuweitao 906742852@qq.com
     * $int 盘口数据
     */
    public function _sb($int, $type)
    {
        $_type = substr($int, 0, 1);
        if ($_type == '-' && strlen($int) == 1) return '';
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
        //自定义公司数组
        $unionInfo = ['澳门', '易胜博', 'SB', 'bet365', '韦德', '威廉希尔', '立博', '利记'];
        //定义默认显示公司
        $companyId = ['澳门', '易胜博', 'SB'];

        $data = time();
        $map['game_timestamp'] = array('between', array(time(), time() + 3600 * 24));
//        $map['game_id'] = 290288;
        $res = mongo('bk_game_schedule')->where($map)->order('game_timestamp')->select();
        if (empty($res)) return [];
        $data = $union_id = $union_data = $game_id = $rang_data = [];
        $ns = $now = $over = 0;
        foreach ($res as $key => $val) {
            $unionid = $val['union_id'];
            $union_id['num'][$unionid] = isset($union_id['num'][$unionid]) ? $union_id['num'][$unionid] + 1 : 1;
            $union_id['arr'][] = $unionid;
            $tmp = [];
            $game_id[] = $val['game_id'];
            //上行开头
            $tmp['game_id'] = $val['game_id'];
            $tmp['union_name'] = $val['union_name'];
            $tmp['union_id'] = $val['union_id'];
            $tmp['union_color'] = $val['union_color'];
            $tmp['game_time'] = $val['game_timestamp'];
            $tmp['home_name'] = $val['home_team_name'];
            $tmp['away_name'] = $val['away_team_name'];
            if (isset($val['compare_odds'])) {
                $odds_tmp = $tmp_odds = [];
                $tmp['compare_odds'] = $this->odds_color(array_slice($val['compare_odds'], 0, count($val['compare_odds']) - 2));
            } else {
                $tmp['compare_odds'] = [['澳门'], ['易胜博'], ['SB'], ['bet365'], ['韦德'], ['威廉希尔'], ['立博'], ['利记']];
            }
            $data[] = $tmp;
        }
        $union_arr = array_values(array_unique($union_id['arr']));
        $union_res = mongo('bk_union')->field('union_id,union_name,union_color,grade')->where(['union_id' => ['in', $union_arr]])->select();
        foreach ($union_res as $key => $val) {
            $union_data[$val['union_id']]['union_id'] = $val['union_id'];
            $union_data[$val['union_id']]['union_name'] = $val['union_name'];
            $union_data[$val['union_id']]['num'] = $union_id['num'][$val['union_id']];
            $union_data[$val['union_id']]['grade'] = $val['grade'];
            $union_data[$val['union_id']]['union_color'] = $val['union_color'];
        }
        $data['company'] = $unionInfo;
        $data['companyId'] = $companyId;
        $data['list'] =  $data;
        $data['union'] =  $union_data;
        return $data;
    }

    /*
     * 即时指数赔率公司数据对比设置相关颜色
     */
    public function odds_color($arr){
        foreach ($arr as $key=>$val)
        {
            //让球数据处理
            if(!empty($val[4])) $arr[$key]['chu_st'] = $this->odds_compare($val[1],$val[4]);
            if(!empty($val[5])) $arr[$key]['chu_nd'] = $this->odds_compare($val[2],$val[5]);
            if(!empty($val[6])) $arr[$key]['chu_rd'] = $this->odds_compare($val[3],$val[6]);

            //标准
            if(!empty($val[8])) $arr[$key]['biao_st'] = $this->odds_compare($val[7],$val[8]);
            if(!empty($val[10])) $arr[$key]['biao_nd'] = $this->odds_compare($val[9],$val[10]);

            //总分
            if(!empty($val[12])) $arr[$key]['zong_st'] = $this->odds_compare($val[11],$val[12]);
            if(!empty($val[14])) $arr[$key]['zong_nd'] = $this->odds_compare($val[13],$val[14]);
            if(!empty($val[16])) $arr[$key]['zong_rd'] = $this->odds_compare($val[15],$val[16]);
        }
        return $arr;
    }

    /*
     * 数据判断,返回颜色class
     */
    public function odds_compare($old,$new)
    {
        if($old > $new)
        {
            return 'text-red';
        }elseif($old < $new){
            return 'text-green';
        }
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
        $httpUrl = SITE_URL . $_SERVER['HTTP_HOST'];
        $mRes = $this->fenxiGameinfo = mongo("bk_game_schedule")->where(['game_id' => (int)$gameId])->find();
        $team_name = mongo("bk_team")->field('team_id,team_name,img_url')->where(['team_id' => ['in', [(int)$mRes['home_team_id'], (int)$mRes['away_team_id']]]])->select();
        $mRes['home_img_url'] = empty($team_name[0]['img_url']) ? $httpUrl . '/Public/Home/images/common/home_def.png' : $team_name[0]['img_url'];
        $mRes['away_img_url'] = empty($team_name[1]['img_url']) ? $httpUrl . '/Public/Home/images/common/away_def.png' : $team_name[1]['img_url'];
        $this->assign('gameInfo', $mRes);
    }

    /**
     * @author liuweitao <906742852@qq.com>
     * @DateTime 2017-1-19
     * 数据分析页面
     */
    public function dataFenxi()
    {
        $game_id = (int)I('game_id');
        $this->assign('gameId', $game_id);
        $this->oddsHeader($game_id);
        //比赛详情
        $gameInfo = $this->fenxiGameinfo;
        //数据分析页面
        $data = mongo('bk_analysis')->where(['game_id' => $game_id])->find();
        //对往战绩
        $duiwang = $this->duiwang($data['past_match']);
//        var_dump($duiwang);

        $this->assign('match_fight', $duiwang);

        $this->display();
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

    /*
     * @author liuweitao <liuwt@qc.com>
     * @DateTime 2017-12-25
     * 获取对往战绩数据
     */
    public function duiwang($data)
    {
        $union_arr = $tmp = [];
        foreach ($data as $key => $val) {
            $union_arr[] = $val[0];
            $data[$key] = $this->otherDataFenxi($val);
        }
        $tmp['list'] = $data;
//        var_dump($data);
        $tmp['union'] = array_unique($union_arr);
        return $tmp;
    }

    //对往战绩数据补充
    public function otherDataFenxi($data)
    {
        $start = strtotime($data[6]);
        $end = $start + 3600 * 24;
        $map['game_timestamp'] = array('between', array($start, $end));
        $map['home_team_id'] = (int)$data[1];
        $map['away_team_id'] = (int)$data[2];
        $res = mongo('bk_game_schedule')->field('union_color,game_info')->where($map)->find();
        if (!empty($res)) {
//            var_dump($res);
            $data['union_color'] = $res['union_color'];
        }
        return $data;
    }

    /*
     * @author liuweitao <liuwt@qc.com>
     * @DateTime 2017-12-25
     * 处理对往战绩让分总分,比分,颜色数据
     */
    public function duiwang_pack($home, $away)
    {
        $data = [];
        $data['poor'] = $home - $away;
        $data['total'] = $home + $away;
        return $data;
    }
}

?>