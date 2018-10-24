<?php
/**
 * 大数据模型
 * Date: 2011/11/30
 */

use Think\Model;

class BigDataModel extends Model
{

    /**
     * 竞彩差异数据
     * @param $chooseSide int 选择胜平负：主胜：1，平局：2，客胜：3
     * @param $playType int 玩法：不让球1，让球2
     * @param $date string 日期：2017-12-01
     * @return array
     */
    public function getJingcaiDiff($chooseSide, $playType, $date){
        if(!in_array($playType, [1,2]) || !in_array($chooseSide, [1,2,3]) || $date == '') return [];

        $time = $date == date('Y-m-d') ? 60 * 5 : 60 * 60 * 24;//当天的30分钟；往期的保存一天
        $newArr = S('bettingDifference_'.$date.$chooseSide.$playType);
        $iosCheck = iosCheck();
        $mongodb = mongoService();
        if(!$newArr || $iosCheck) {
            if(!$mRes = S('bettingDifference_games_'.$date)){
                $beginTime = strtotime($date);
                $endTime = $beginTime + 129600;
                //今天0点到明天12点匹配
                $where['game_start_timestamp'] = ['$gt'=>$beginTime,'$lt'=>$endTime];
                //竞彩字符周几匹配
                $today = "周" . mb_substr( "日一二三四五六", date("w", strtotime($date)), 1, "utf-8" );
                $where['num'] = new mongoRegex("/^" . $today . "/");
                $mRes = $mongodb->select('fb_sporttery',$where,['game_id', 'num', 'had', 'had_p', 'hhad', 'hhad_p', 'game_start_datetime']);
                S('bettingDifference_games_'.$date, json_encode($mRes), $time);//赔率会变
            }

            $newArr = $diff = [];
            if ($mRes) {
                if($iosCheck) $config = getWebConfig('common')['ios_character'];

                foreach ($mRes as $k => &$v) {
                    if ($playType == 1) {//不让球
                        if(empty($v['had_p'])) continue;

                        $pre_win = $v['had_p']['pre_win'];
                        $pre_lose = $v['had_p']['pre_lose'];
                        $pre_draw = $v['had_p']['pre_draw'];

                        //概率
                        $rate = calculateRate($v['had']['h'], $v['had']['d'], $v['had']['a']);
                        $hrate = $rate[0];//胜
                        $drate = $rate[1];//平
                        $arate = $rate[2];//负

                        //差异
                        $hdif = round(abs(intval($pre_win) - $hrate));
                        $ddif = round(abs(intval($pre_draw) - $drate));
                        $adif = round(abs(intval($pre_lose) - $arate));

                        $newArr[$k]['handcp'] = $v['had']['fixedodds'];
                    } else if ($playType == 2) {
                        if(empty($v['hhad_p'])) continue;

                        //让球
                        $pre_win = $v['hhad_p']['pre_win'];
                        $pre_lose = $v['hhad_p']['pre_lose'];
                        $pre_draw = $v['hhad_p']['pre_draw'];

                        //概率
                        $rate = calculateRate($v['hhad']['h'], $v['hhad']['d'], $v['hhad']['a']);
                        $hrate = $rate[0];//胜
                        $drate = $rate[1];//平
                        $arate = $rate[2];//负

                        //差异
                        $hdif = round(abs(intval($pre_win) - $hrate));
                        $ddif = round(abs(intval($pre_draw) - $drate));
                        $adif = round(abs(intval($pre_lose) - $arate));

                        $newArr[$k]['handcp'] = ($iosCheck) ? $config['jingcai'].':'.$v['hhad']['fixedodds'] : '竞彩:'.$v['hhad']['fixedodds'];
                    }

                    if ($chooseSide == 1) {
                        $newArr[$k]['dealRatio'] = $pre_win;
                        $newArr[$k]['probability'] = !empty($hrate) ? $hrate . '%' : '0%';
                        $newArr[$k]['differenceRatio'] = !empty($hdif) ? $hdif . '%' : '0%';
                        $diff[] = $hdif;
                    } else if ($chooseSide == 2) {
                        $newArr[$k]['dealRatio'] = $pre_draw;
                        $newArr[$k]['probability'] = !empty($drate) ? $drate . '%' : '0%';
                        $newArr[$k]['differenceRatio'] = !empty($ddif) ? $ddif . '%' : '0%';
                        $diff[] = $ddif;
                    } else if ($chooseSide == 3) {
                        $newArr[$k]['dealRatio'] = $pre_lose;
                        $newArr[$k]['probability'] = !empty($arate) ? $arate . '%' : '0%';
                        $newArr[$k]['differenceRatio'] = !empty($adif) ? $adif . '%' : '0%';
                        $diff[] = $adif;
                    }

                    $newArr[$k]['betCode'] = $v['num'];
                    $newArr[$k]['gameId']  = $v['game_id'];
                    $newArr[$k]['gtime']   = date('H:i', strtotime($v['game_start_datetime']));
                }

                //最大差异值倒序，前十
                array_multisort($diff, SORT_DESC, $newArr);
                $newArr = array_slice($newArr, 0, 10);

                $chooseResult = [1=>'主胜', 2=>'平局', 3=>'客胜'];

                $gameIdArr = [];
                foreach ($newArr as $k => &$v) {
                    $gameIdArr[] = $v['gameId'];
                }

                //获取game数据
                $one = $mongodb->select('fb_game',['game_id'=>['$in'=>$gameIdArr]],['game_id', 'home_team_name', 'away_team_name', 'union_name', 'game_state', 'score']);

                foreach ($newArr as $nk => &$nv) {
                    foreach($one as $ok => &$ov) {
                        if($nv['gameId'] == $ov['game_id']) {
                            //用game表的球队名,联赛名
                            $nv['homeTeamName'] = implode(',', $ov['home_team_name']);
                            $nv['awayTeamName'] = implode(',', $ov['away_team_name']);
                            $nv['unionName'] = implode(',', $ov['union_name']);

                            //完场才显示比分
                            $nv['score'] = $ov['game_state'] == '-1' ? (string)$ov['score'] : '';
                            $nv['chooseResult'] = $chooseResult[$chooseSide];

                            $nv['gameId'] = (string)$nv['gameId'];
                        }
                    }
                }

                unset($gameIdArr, $one);
                S('bettingDifference_' . $date . $chooseSide . $playType, json_encode($newArr), $time);
            }
        }

        return $newArr;
    }

    /**
     * 冷热交易数据
     * @param $chooseSide int 类型 1:必发交易 2:庄家盈利 3:庄家亏损  默认1
     * @param $date string 年月日：yyyy-mm-dd 默认今天
     * @return array
     */
    public function getHotColdTrade($type=1, $date=null)
    {
        if(!$type) $type = 1;
        if(!$date) $date = date('Y-m-d');

        if(!$data = S('hotColdTrade_'.$date))
        {
            $mongo = mongoService();
            //获取赛事
            $startTime = strtotime($date." ".C('fb_bigdata_time'));
            $endTime   = $startTime + 86400;
            //10:35到10:35
            $fb_game = $mongo->select('fb_game',['game_start_timestamp'=>['$gt'=>$startTime,'$lt'=>$endTime]],['game_id','score','game_state','spottery_num','union_name','home_team_name','away_team_name','game_start_timestamp']);

            if(!$fb_game) return [];

            //赛事信息处理
            $data = $gameIdArr = [];
            foreach ($fb_game as $k => $v) {
                $gameIdArr[] = $v['game_id'];
            }

            //获取必发数据
            $fb_bifaindex310win = $mongo->select('fb_bifaindex310win',['game_id'=>['$in'=>$gameIdArr]],['game_id','bifadatastandard']);

            if(!$fb_bifaindex310win) return [];

            foreach ($fb_bifaindex310win as $k => $v) {
                $bifaData[$v['game_id']] = $v['bifadatastandard'];
            }
            foreach ($fb_game as $k => $v) {
                //必发数据处理
                $bifa = $bifaData[$v['game_id']];

                if(isset($bifa))
                {
                    $arr['gameId']       = (string)$v['game_id'];
                    $arr['betCode']      = isset($v['spottery_num']) ? $v['spottery_num'] : '';
                    $arr['gtime']        = date('H:i',$v['game_start_timestamp']);
                    $arr['unionName']    = isset($v['union_name']) ? is_array($v['union_name']) ? implode(',', $v['union_name']) : $v['union_name'] : '';
                    $arr['homeTeamName'] = implode(',', $v['home_team_name']);
                    $arr['awayTeamName'] = implode(',', $v['away_team_name']);
                    //完场才显示比分
                    $arr['score']        = $v['game_state'] == '-1' ? $v['score'] : '';

                    $homeBiFa = $bifa[0]; //主队数据
                    $drawBiFa = $bifa[1]; //平局数据
                    $awayBiFa = $bifa[2]; //客队数据
                    //成交价为0去掉
                    if( $homeBiFa[0] <= 0 || $drawBiFa[0] <= 0 || $awayBiFa[0] <= 0 ){
                        continue;
                    }
                    //去掉逗号
                    $homeDealNum = str_replace(',', '', $homeBiFa[3]);
                    $drawDealNum = str_replace(',', '', $drawBiFa[2]);
                    $awayDealNum = str_replace(',', '', $awayBiFa[2]);
                    //总成交量
                    $allDealNum = $homeDealNum + $drawDealNum + $awayDealNum;
                    $arr['allDealNum']    = $this->FormatMoney($allDealNum);

                    $arr['homeDealPrice'] = $homeBiFa[0];//主队成交价
                    $arr['drawDealPrice'] = $drawBiFa[0];//平局成交价
                    $arr['awayDealPrice'] = $awayBiFa[0];//客队成交价

                    $arr['homeDealNum']   = $this->FormatMoney($homeDealNum);//主队成交量
                    $arr['drawDealNum']   = $this->FormatMoney($drawDealNum);//平局成交量
                    $arr['awayDealNum']   = $this->FormatMoney($awayDealNum);//客队成交量

                    $arr['homeDealBi']    = $this->changeDealRatio($homeBiFa[4]);//主队成交比例
                    $arr['drawDealBi']    = $this->changeDealRatio($drawBiFa[3]);//平局成交比例
                    $arr['awayDealBi']    = $this->changeDealRatio($awayBiFa[3]);//客队成交比例

                    $arr['homeDealYk']    = $homeBiFa[6];//主队盈亏
                    $arr['drawDealYk']    = $drawBiFa[5];//平局盈亏
                    $arr['awayDealYk']    = $awayBiFa[5];//客队盈亏
                    $data[] = $arr;
                }
            }
            S('hotColdTrade_'.$date, json_encode($data), 300);
        }

        foreach ($data as $k => $v) {
            //根据数据类型排序
            switch ($type) {
                case '1':
                    //必发排序，取三个中比例最高的
                    $dealArr = [1=>$v['homeDealBi'], 2=>$v['drawDealBi'], 3=>$v['awayDealBi']];
                    $key                       = array_search(max($dealArr),$dealArr); 
                    $sort_DealRatio[$k]        = $dealArr[$key];
                    $data[$k]['homeDealRatio'] = $v['homeDealBi'].'%';
                    $data[$k]['drawDealRatio'] = $v['drawDealBi'].'%';
                    $data[$k]['awayDealRatio'] = $v['awayDealBi'].'%';
                    $data[$k]['maxSign']       = $key;
                    $sort                      = SORT_DESC;
                    break;
                case '2':
                    //盈利排序，取三个中盈利最高的
                    $dealArr = [1=>$v['homeDealYk'], 2=>$v['drawDealYk'], 3=>$v['awayDealYk']];
                    $key                       = array_search(max($dealArr),$dealArr); 
                    $sort_DealRatio[$k]        = $dealArr[$key];
                    $data[$k]['homeDealRatio'] = $this->FormatMoney($v['homeDealYk']);
                    $data[$k]['drawDealRatio'] = $this->FormatMoney($v['drawDealYk']);
                    $data[$k]['awayDealRatio'] = $this->FormatMoney($v['awayDealYk']);
                    $data[$k]['maxSign']       = $key;
                    $sort                      = SORT_DESC;
                    break;
                case '3':
                    //亏损排序，取三个中亏损最高的
                    $dealArr = [1=>$v['homeDealYk'], 2=>$v['drawDealYk'], 3=>$v['awayDealYk']];
                    $key                       = array_search(min($dealArr),$dealArr); 
                    $sort_DealRatio[$k]        = $dealArr[$key];
                    $data[$k]['homeDealRatio'] = $this->FormatMoney($v['homeDealYk']);
                    $data[$k]['drawDealRatio'] = $this->FormatMoney($v['drawDealYk']);
                    $data[$k]['awayDealRatio'] = $this->FormatMoney($v['awayDealYk']);
                    $data[$k]['maxSign']       = $key;
                    $sort                      = SORT_ASC;
                    break;
            }
            unset($data[$k]['homeDealBi'],$data[$k]['drawDealBi'],$data[$k]['awayDealBi'],$data[$k]['homeDealYk'],$data[$k]['drawDealYk'],$data[$k]['awayDealYk']);
        }
        //排序
        array_multisort($sort_DealRatio, $sort ,$data);
        return $data;
    }

    /**
     * 转格式 例如22.4%变成22.40% 保留小数点后两位
     */
    public function changeDealRatio($DealRatio){
        $number = str_replace('%', '', $DealRatio);
        return sprintf('%01.2f',$number);
    }

    /**
     * 转格式 成交量达到万以上则使用中文'万'代替，保留两位小数。譬如：2684540简写成268.45万
     */
    function FormatMoney($money){
        if(abs($money) >= 10000){
            return sprintf("%.2f", $money/10000).'万';
        }else{
            return $money;
        }
    }

    /**
     * 每日极限数据
     * @param $playType int 玩法：1胜平负，2亚盘，3大小球
     * @param $winType  int 类型：1连胜/连大 ，2连平 ， 3连负/连小
     * @param $date    string 年月日：yyyy-mm-dd 默认今天
     * @return array
     */
    public function getDailyMax($playType=1, $winType=1, $date=''){
        if(!$date) $date = date('Y-m-d');

        if(!$data = S('getDailyMax_'.$date.$playType.$winType))
        {
            $mongodb = mongoService();
            if(!$fb_game = S('DailyMaxFbGame_'.$date))
            {
                //获取赛事
                $startTime = strtotime($date." ".C('fb_bigdata_time'));
                $endTime   = $startTime + 86400;
                //10:35到10:35
                $fb_game = $mongodb->select('fb_game',['game_start_timestamp'=>['$gt'=>$startTime,'$lt'=>$endTime]],['game_id','score','game_state','spottery_num','union_name','home_team_name','away_team_name','home_team_extreme','away_team_extreme','game_start_timestamp']);
                S('DailyMaxFbGame_'.$date, json_encode($fb_game), 300);
            }

            $data = $sortArr = [];
            $winNum = 3; //连胜条件数量
            $iosCheck = iosCheck();
            foreach ($fb_game as $k => $v) {
                $home_team_extreme = $v['home_team_extreme'];
                $away_team_extreme = $v['away_team_extreme'];
                if(!$home_team_extreme || !$away_team_extreme){
                    continue;
                }
                //玩法
                switch ($playType) {
                    case '1':
                        //胜平负
                        $homeWin   = $home_team_extreme['wincurrent'];   //主当前连胜
                        $homeDraw  = $home_team_extreme['drawcurrent'];  //主当前连平
                        $homeLose  = $home_team_extreme['losecurrent'];  //主当前连负
                        $homeWinH  = $home_team_extreme['winhis'];       //主胜历史最高
                        $homeDrawH = $home_team_extreme['drawhis'];      //主平历史最高
                        $homeLoseH = $home_team_extreme['losehis'];      //主负历史最高

                        $awayWin   = $away_team_extreme['wincurrent'];   //客当前连胜
                        $awayDraw  = $away_team_extreme['drawcurrent'];  //客当前连平
                        $awayLose  = $away_team_extreme['losecurrent'];  //客当前连负
                        $awayWinH  = $away_team_extreme['winhis'];       //客胜历史最高
                        $awayDrawH = $away_team_extreme['drawhis'];      //客平历史最高
                        $awayLoseH = $away_team_extreme['losehis'];      //客负历史最高
                        break;
                    case '2':
                        //亚盘
                        $homeWin   = $home_team_extreme['awincurrent'];  //主当前连胜
                        $homeLose  = $home_team_extreme['alosecurrent']; //主当前连负
                        $homeWinH  = $home_team_extreme['awinhis'];      //主胜历史最高
                        $homeLoseH = $home_team_extreme['alosehis'];     //主负历史最高

                        $awayWin   = $away_team_extreme['awincurrent'];  //客当前连胜
                        $awayLose  = $away_team_extreme['alosecurrent']; //客当前连负
                        $awayWinH  = $away_team_extreme['awinhis'];      //客胜历史最高
                        $awayLoseH = $away_team_extreme['alosehis'];     //客负历史最高
                        break;
                    case '3':
                        //大小球
                        $homeWin   = $home_team_extreme['bigcurrent'];   //主当前连胜
                        $homeLose  = $home_team_extreme['smallcurrent']; //主当前连负
                        $homeWinH  = $home_team_extreme['bighis'];       //主胜历史最高
                        $homeLoseH = $home_team_extreme['smallhis'];     //客负历史最高

                        $awayWin   = $away_team_extreme['bigcurrent'];   //客当前连胜
                        $awayLose  = $away_team_extreme['smallcurrent']; //客当前连负
                        $awayWinH  = $away_team_extreme['bighis'];       //主胜历史最高
                        $awayLoseH = $away_team_extreme['smallhis'];     //客负历史最高
                        break;
                }

                //类型
                switch ($winType) {
                    case '1':
                        //连胜/连大： 主或客大于等于3
                        if($homeWin >= $winNum || $awayWin >= $winNum){
                            $gameIdArr[] = $v['game_id'];
                            $DailyData = $this->handleDailyData($v,$homeWin,$awayWin,$homeWinH,$awayWinH);
                            $yapanStr = $iosCheck ? '红' : '赢';
                            $DailyData['winType'] = $playType == 3 ? '大' : ($playType == 1 ? '胜' : $yapanStr);
                            $data[] = $DailyData;
                        }
                        break;
                    case '2':
                        //连平： 主或客大于等于3
                        if($homeDraw >= $winNum || $awayDraw >= $winNum){
                            $gameIdArr[] = $v['game_id'];
                            $DailyData = $this->handleDailyData($v,$homeDraw,$awayDraw,$homeDrawH,$awayDrawH);
                            $DailyData['winType'] = '平';
                            $data[] = $DailyData;
                        }
                        break;
                    case '3':
                        //连负/连小： 主或客大于等于3
                        if($homeLose >= $winNum || $awayLose >= $winNum){
                            $gameIdArr[] = $v['game_id'];
                            $DailyData = $this->handleDailyData($v,$homeLose,$awayLose,$homeLoseH,$awayLoseH);
                            $yapanStr = $iosCheck ? '黑' : '输';
                            $DailyData['winType'] = $playType == 3 ? '小' : ($playType == 1 ? '负' : $yapanStr);
                            $data[] = $DailyData;
                        }
                        break;
                }
            }

            if(!$data || !$gameIdArr) return [];

            //胜平负获取（欧赔初盘赔率），亚盘和大小球获取（初盘盘口）
            $fb_odds = $mongodb->select('fb_odds',['game_id'=>['$in'=>$gameIdArr],'company_id'=>3,'is_half'=>0],['game_id','odds']);

            foreach ($fb_odds as $k => $v) {
                $odds[$v['game_id']] = $v['odds'];
            }
            //处理赔率或盘口
            foreach ($data as $k => $v) {
                $dataOdds = $odds[$v['gameId']];
                if($playType == 1){
                    //欧赔赔率，先拿即时，没有就初盘
                    switch ($winType) {
                        case '1':
                            $j_odds = isset($dataOdds[12]) ? $dataOdds[12] : $dataOdds[9];
                            $handcp = !empty($j_odds) ? '胜赔:'.$j_odds : '';
                            break;
                        case '2':
                            $j_odds = isset($dataOdds[13]) ? $dataOdds[13] : $dataOdds[10];
                            $handcp = !empty($j_odds) ? '平赔:'.$j_odds : '';
                            break;
                        case '3':
                            $j_odds = isset($dataOdds[14]) ? $dataOdds[14] : $dataOdds[11];
                            $handcp = !empty($j_odds) ? '负赔:'.$j_odds : '';
                            break;
                    }
                }else if($playType == 2){
                    //亚盘先拿即时盘口，没有就初盘
                    $y_odds = isset($dataOdds[4]) ? $dataOdds[4] : $dataOdds[1];
                    $handcp = !empty($y_odds) ? '亚盘:'.changeExp($y_odds) : '';
                }else if($playType == 3){
                    //大小先拿即时盘口，没有就初盘
                    $d_odds = isset($dataOdds[22]) ? $dataOdds[22] : $dataOdds[19];
                    $handcp = !empty($d_odds) ? '大小:'.changeExp($d_odds) : '';
                }
                $data[$k]['handcp'] = $handcp;
                $sortArr[$k] = $v['curMaxScore'];
            }
            array_multisort($sortArr,SORT_DESC,$data);
            S('getDailyMax_'.$date.$playType.$winType, json_encode($data), 300);
        }
        return $data;
    }

    /**
     * 处理每日极限数据
     * @param $v array 数据
     * @param $homeWin  int 主队当前连胜
     * @param $awayWin  int 客队当前连胜
     * @param $homeWinH  int 主队历史最高
     * @param $awayWinH  int 客队历史最高
     * @return array
     */
    public function handleDailyData($v,$homeWin,$awayWin,$homeWinH,$awayWinH){
        $homeTeamName = implode(',', $v['home_team_name']);
        $awayTeamName = implode(',', $v['away_team_name']);
        $arr['gameId']       = (string)$v['game_id'];
        $arr['betCode']      = isset($v['spottery_num']) ? $v['spottery_num'] : '';
        $arr['gtime']        = date('H:i',$v['game_start_timestamp']);
        $arr['unionName']    = isset($v['union_name']) ? is_array($v['union_name']) ? implode(',', $v['union_name']) : $v['union_name'] : '';
        $arr['homeTeamName'] = $homeTeamName;
        $arr['awayTeamName'] = $awayTeamName;
        $arr['score']        = $v['game_state'] == '-1' ? $v['score'] : '';
        $winTeam = $awayWin > $homeWin ? 2 : 1; //显示主队还是客队 相同时主队
        $arr['winTeam']      = $winTeam; //当前球队
        $arr['curMaxScore']  = $winTeam == 1 ? $homeWin : $awayWin;   //当前球队连胜
        $arr['maxScore']     = $winTeam == 1 ? $homeWinH : $awayWinH; //当前球队历史最高
        return $arr;
    }

    /**
     * 历史同赔数据
     * @param $type int 类型 1:欧指 2:亚指 3:大小
     * @param $date string 日期 
     * @return array
     */
    public function getAlikeHistory($type,$date)
    {
        //获取赛事
        $startTime = strtotime($date." ".C('fb_bigdata_time'));
        $endTime   = $startTime + 86400;

        if(!$data = S('AlikeHistory_'.$type.$date)){
            $mongodb = mongoService();
            //赔率公司id
            $alikeHistoryCompany = C('alikeHistoryCompany');
            $companyId   = $alikeHistoryCompany['companyId'];
            $companyName = $alikeHistoryCompany['companyName'];
            //10:35到10:35
            $map['game_start_timestamp'] = [$mongodb->cmd('<')=>$endTime,$mongodb->cmd('>')=>$startTime];
            switch ($type) {
                case '1':
                    $same_name  = 'same_euro';
                    $match_odds = 'match_odds';
                    break;
                case '2':
                    $same_name  = 'same_asia';
                    $match_odds = 'match_odds_m_asia';
                    break;
                case '3':
                    $same_name  = 'same_bigsmall';
                    $match_odds = 'match_odds_m_bigsmall';
                    break;
            }
            $map['same_odds.'.$same_name.'.all'] = ['$ne'=>null];
            //获取赛事数据
            $fb_game = $mongodb->select('fb_game',$map,['game_id','union_name','home_team_name','away_team_name','score','game_state','spottery_num','game_start_timestamp','same_odds.'.$same_name.'.all',$match_odds.'.'.$companyId]);

            if(!$fb_game) return [];

            $data = $sort1 = $sort2 = [];
            foreach ($fb_game as $k => $v) {
                $arr = [];
                $arr['gameId']       = (string)$v['game_id'];
                $arr['unionName']    = implode(',', $v['union_name']);
                $arr['homeTeamName'] = implode(',', $v['home_team_name']);
                $arr['awayTeamName'] = implode(',', $v['away_team_name']);
                $arr['gtime']        = date('m-d H:i',$v['game_start_timestamp']);
                $arr['betCode']      = $v['spottery_num'] ? : '';
                //完场才显示比分
                $arr['score']        = $v['game_state'] == '-1' ? $v['score'] : '';

                //即时赔率
                $odds = $v[$match_odds][$companyId];
                //使用初盘赔率
                if($type == 1){
                    $odds[6] = ($odds[6] != ' ') ? $odds[6] : '';
                    $odds[7] = ($odds[7] != ' ') ? $odds[7] : '';
                    $odds[8] = ($odds[8] != ' ') ? $odds[8] : '';
                    $arr['odds'] = $odds[6].'^'.$odds[7].'^'.$odds[8];
                }else{
                    $odds[0] = ($odds[0] != ' ') ? $odds[0] : '';
                    $odds[1] = ($odds[1] != ' ') ? $odds[1] : '';
                    $odds[2] = ($odds[2] != ' ') ? $odds[2] : '';
                    $arr['odds'] = $odds[0].'^'.changeExpP($odds[1]).'^'.$odds[2];
                }

                //同赔数据
                $nearArr = $v['same_odds'][$same_name]['all'];
                $homeWin = ($nearArr[0] != '') ? $nearArr[0] : '';
                $falt    = ($nearArr[1] != '') ? $nearArr[1] : '';
                $awayWin = ($nearArr[2] != '') ? $nearArr[2] : '';
                $arr['ratio']        = $homeWin.'^'.$falt.'^'.$awayWin;

                $nearNum = count($nearArr[3]);
                
                //小于20剔除，过滤赔率为空数据
                if($nearNum < 20 || $arr['odds'] == '^^') continue;

                $arr['nearNum'] = $nearNum.'场'.$companyName;
                $data[]  = $arr;
                $sort1[] = $nearArr[0];
                $sort2[] = $v['game_start_timestamp'];
            }
            array_multisort($sort1,SORT_DESC,$sort2,SORT_ASC,$data);
            //缓存
            S('AlikeHistory_'.$type.$date,$data,600);
        }
        return $data;
    }

    /**
     * 历史同赔详情页数据
     * @param $gameId int 赛程id 
     * @param $type int 类型 1:欧指 2:亚指 3:大小
     * @return array
     */
    public function getAlikeHistoryDetail($gameId,$type){
        if(empty($gameId))
            return [];

        $mongodb = mongoService();
        //赔率公司id
        $companyId = C('alikeHistoryCompany')['companyId'];
        //获取赛事数据
        $fb_game = $mongodb->select('fb_game',['game_id'=>$gameId],['home_team_name','away_team_name','same_odds']);
        if(!$fb_game) return [];

        $same_odds = $fb_game[0]['same_odds'];

        switch ($type) {
            case '1':
                $sameOdds = $same_odds['same_euro'];//1:欧指 
                break;
            case '2':
                $sameOdds = $same_odds['same_asia'];//2:亚指 
                break;
            case '3':
                $sameOdds = $same_odds['same_bigsmall'];//3:大小
                break;
        }
        
        $all       = $sameOdds['all'];          //全部赛事
        $level_one = $sameOdds['level_one'];    //一级赛事
        $sporttery = $sameOdds['sporttery'];    //竞彩赛事

        //合并game_id
        $allRatio       = $all[3] ? : [];
        $oneRatio       = $level_one[3] ? : [];
        $colorRatio     = $sporttery[3] ? : [];
        $gameIdArr = array_values(array_unique(array_merge($allRatio,$oneRatio,$colorRatio)));

        //一并找出赛事
        $gameArr = $mongodb->select('fb_game',['game_id'=>[$mongodb->cmd('in')=>$gameIdArr]],['game_id','home_team_name','away_team_name','game_state','score','union_name','match_odds.'.$companyId,'match_odds_m_asia.'.$companyId,'match_odds_m_bigsmall.'.$companyId]);

        //处理赛事
        $data['homeTeamName'] = implode(',', $fb_game[0]['home_team_name']);
        $data['awayTeamName'] = implode(',', $fb_game[0]['away_team_name']);
        //全部赛事
        $data['allRatio']   = $this->setHistoryDetail($all,$gameArr,$type);
        //一级赛事
        $data['oneRatio']   = $this->setHistoryDetail($level_one,$gameArr,$type);
        //竞彩赛事
        $data['colorRatio'] = $this->setHistoryDetail($sporttery,$gameArr,$type);
        return $data;
    }

    /**
     * 处理历史同赔详情页数据
     * @param $same int 赛事分组 
     * @param $gameArr array 赛事数组
     * @param $type int 类型 1:欧指 2:亚指 3:大小
     * @param $companyId int 赔率公司id
     * @return array
     */
    public function setHistoryDetail($same,$gameArr,$type){
        $homeWin = ($same[0] != '') ? $same[0] : '';
        $falt    = ($same[1] != '') ? $same[1] : '';
        $awayWin = ($same[2] != '') ? $same[2] : '';
        $ratio   = $homeWin.'^'.$falt.'^'.$awayWin; //胜平负

        $gameIdArr = $same[3] ? : [];
        $nearNum = count($gameIdArr); //近多少场

        if($nearNum > 0){
            //赔率公司id
            $alikeHistoryCompany = C('alikeHistoryCompany');
            $companyId   = $alikeHistoryCompany['companyId'];
            $companyName = $alikeHistoryCompany['companyName'];

            $gameList = [];
            foreach ($gameArr as $k => $v) {
                if(in_array($v['game_id'], $gameIdArr)){
                    $arr['gameId']       = $v['game_id'];//暂时返回，方便检查
                    $arr['unionName']    = implode(',', $v['union_name']);
                    $arr['homeTeamName'] = implode(',', $v['home_team_name']);
                    $arr['awayTeamName'] = implode(',', $v['away_team_name']);
                    //完场才显示比分
                    $arr['score']        = $v['game_state'] == '-1' ? $v['score'] : '';

                    //赔率
                    switch ($type) {
                        case '1':
                            //1:欧指 
                            $odds = $v['match_odds'][$companyId];
                            $odds[6]  = ($odds[6]  != ' ') ? $odds[6]  : '';
                            $odds[7]  = ($odds[7]  != ' ') ? $odds[7]  : '';
                            $odds[8]  = ($odds[8]  != ' ') ? $odds[8]  : '';
                            $odds[9]  = ($odds[9]  != ' ') ? $odds[9]  : '';
                            $odds[10] = ($odds[10] != ' ') ? $odds[10] : '';
                            $odds[11] = ($odds[11] != ' ') ? $odds[11] : '';
                            $arr['firstOdds'] = $odds[6].'^'.$odds[7].'^'.$odds[8];
                            $arr['nowOdds']   = $odds[9].'^'.$odds[10].'^'.$odds[11];
                            //计算胜平负（以主队为比较，主>客=赢，主<客=输，主=客=平）
                            if($arr['score'] != ''){
                                $score = explode('-', $v['score']);
                                $arr['winType'] = $score[0] > $score[1] ? '胜' : ($score[0] < $score[1] ? '负' : '平');
                            }else{
                                $arr['winType'] = '';
                            }
                            break;
                        case '2':
                            //2:亚指 
                            $odds = $v['match_odds_m_asia'][$companyId];
                            $odds[0] = ($odds[0] != ' ') ? $odds[0] : '';
                            $odds[1] = ($odds[1] != ' ') ? $odds[1] : '';
                            $odds[2] = ($odds[2] != ' ') ? $odds[2] : '';
                            $odds[3] = ($odds[3] != ' ') ? $odds[3] : '';
                            $odds[4] = ($odds[4] != ' ') ? $odds[4] : '';
                            $odds[5] = ($odds[5] != ' ') ? $odds[5] : '';
                            $arr['firstOdds'] = $odds[0].'^'.changeExpP($odds[1]).'^'.$odds[2];
                            $arr['nowOdds']   = $odds[3].'^'.changeExpP($odds[4]).'^'.$odds[5];
                            //计算赢走输（以初盘盘口做判断 主-客>盘口=赢; 主-客<盘口=输; 主-客=盘口=走）
                            $arr['winType'] = getHandcpWin($arr['score'],$odds[1],1);
                            break;
                        case '3':
                            //3:大小
                            $odds = $v['match_odds_m_bigsmall'][$companyId];
                            $odds[0] = ($odds[0] != ' ') ? $odds[0] : '';
                            $odds[1] = ($odds[1] != ' ') ? $odds[1] : '';
                            $odds[2] = ($odds[2] != ' ') ? $odds[2] : '';
                            $odds[3] = ($odds[3] != ' ') ? $odds[3] : '';
                            $odds[4] = ($odds[4] != ' ') ? $odds[4] : '';
                            $odds[5] = ($odds[5] != ' ') ? $odds[5] : '';
                            $arr['firstOdds'] = $odds[0].'^'.changeExpP($odds[1]).'^'.$odds[2];
                            $arr['nowOdds']   = $odds[3].'^'.changeExpP($odds[4]).'^'.$odds[5];
                            //计算大走小（以初盘盘口做判断 主+客>盘口=大; 主+客<盘口=小; 主+客=盘口=走）
                            $arr['winType'] = getHandcpWin($arr['score'],$odds[1],2);
                            break;
                    }
                    //过滤错误赛事
                    if(empty($arr['unionName']) || empty($arr['homeTeamName']) || empty($arr['awayTeamName'])){
                        continue;
                    }
                    $gameList[] = $arr;
                }
            }
            return (object)['ratio'=>$ratio,'nearNum'=>$nearNum.'场'.$companyName,'gameList'=>$gameList];
        }
        return (object)[];
    }
}