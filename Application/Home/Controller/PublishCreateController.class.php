<?php
/**
 * Created by PhpStorm.
 * User: zhangsh
 * Date: 2018/4/25
 * Time: 12:25
 */

use Think\Controller;
use Think\Tool\Tool;
class PublishCreateController extends CommonController{

    public $user_id = 53546;   // 指定的专家ID
    public $user_vip_id = 53570; // Vip小编
    public $resultTip = array(
        // 非0 0 平局
        'pj' => array('平分秋色', '握手言和', '难分高下'),
        // 0 0 平局
        'pj0' => array('互交白卷', '不分伯仲'),
        // 赢1到2球
        'win' => array('击败', '捍卫主场 拿下'),
        // 赢3 or 以上
        'win3' => array('大胜', '横扫'),
        // 输1到2球
        'lose' => array('遗憾不敌', '吞下失利苦果'),
        // 输3 or 以上
        'lose3' => array('惨败', '主场颜面尽失 X球失利')
    );
    /**
     * 生成资讯前瞻
     */
    public function prospect(){

        //获取赛事
        $unionId = !empty($_REQUEST['unionId']) ? $_REQUEST['unionId'] : null;
        $subId = !empty($_REQUEST['subId']) ? $_REQUEST['subId'] : null;
        $webfbService = new \Home\Services\WebfbService();
        $gameData = $webfbService->fbtodayList($unionId, $subId);
        $game = $gameData['info']; //赛事
        //var_dump(count($game));exit;

        $from = $this->param['from'] ?: 1;//情报来源
        $appService = new \Home\Services\AppfbService();

        // 竞彩
        foreach ($game as $g){
            // 42 1是0非  7 -1完赛 0未开
            if($g[42] == 1 && $g[7] == 0){
//                $jingcai[0] = $g;
//                $g_id[] = $g[0];
                $game_id = $g[0];
                if ($game_id < 0) {
                    continue;
                }
                $class_id = 108;// 前瞻 ID
                $publish = M('PublishList');
                // find
                $where_str = 'game_id = '.$game_id.' AND class_id = '.$class_id;
                $old = $publish->where($where_str)->select();
                if($old){
                    continue;
                }

                $preMatchinfo = $appService->getPreMatchinfo($game_id,$from);
                if($preMatchinfo){
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

                    $scroe = A('Score');
                    $match_fight = $scroe->match_fight($duiwang, $home_name);

                    //近期交战
                    $jinqi_home = $fbService->getRecentFight($baseRes['home_team_id'] ,$baseRes['gtime'],1);
                    $jinqi_home = $scroe->jinqi($jinqi_home,$home_name);
                    $jinqi_away = $fbService->getRecentFight($baseRes['away_team_id'] ,$baseRes['gtime'],1);
                    $jinqi_away = $scroe->jinqi($jinqi_away,$away_name);
                    $jinqi_data = [
                        0=>['name'=>'recent_fight1','content'=>$jinqi_home],
                        1=>['name'=>'recent_fight2','content'=>$jinqi_away]
                    ];
                    $recent_fight = $scroe->recent_fight($jinqi_data);
                    //var_dump($recent_fight);
                    //var_dump($recent_fight[0]['home']);
                    //$this->assign('recent_fight', $this->recent_fight($jinqi_data));//处理近期赛事

                    // 即时赔率
                    //$goals = $scroe->goals($game_id);
                    //var_dump($goals);

                    $res = $webfbService->getAnaForFile($game_id, 1);
                    //处理接口返回数据
                    foreach ($res as $val) {
                        if ($val['name'] == 'match_three') {
                            $match_three = $scroe->match_three($val['content']);//未来3场数据赋值
                        }
            //            if ($val['name'] == 'recent_fight') {
            //                $this->assign('recent_fight', $this->recent_fight($val['content']));//处理近期赛事
            //            }
            //            if ($val['name'] == 'match_fight') {
            //                $this->assign('match_fight', $this->match_fight($val['content'], $home));//处理对阵历史
            //            }
                        if ($val['name'] == 'match_integral') {
                            $match_integral = $scroe->match_integral($val['content'], $home_name);//处理联赛积分
                        }
                        if ($val['name'] == 'match_panlu') {
                            $match_panlu = $scroe->match_panlu($val['content']);//处理联赛盘路
                        }
                        if ($val['name'] == 'cupmatch_integral') {
                            $cupmatch_integral = $scroe->cupmatch_integral($val['content']);//处理杯赛排名
                        }
                    }


                    // 亚赔 倾向值
                    $qinxiangYpUrl='https://www.qqty.com/Api510/Appdata/asianOdds?gameId='.$game_id.'&nosign=api_qqty_ipa';
                    $returnData = $this->_curl_get_https($qinxiangYpUrl);
                    $returnJsonData = json_decode($returnData);
                    if($returnJsonData->status == 1){
                        if($returnJsonData->data->aobTrend->h > $returnJsonData->data->aobTrend->a){
                            $qinxiangYp = $g[13];
                        }else{
                            $qinxiangYp = $g[16];
                        }
                    }
                    // 大小 倾向值
                    $qinxiangDxUrl = 'https://www.qqty.com/Api510/Appdata/ballOdds?gameId='.$game_id.'&nosign=api_qqty_ipa';
                    $returnData = $this->_curl_get_https($qinxiangDxUrl);
                    $returnJsonData = json_decode($returnData);
                    if($returnJsonData->status == 1){
                        if($returnJsonData->data->aobTrend->h > $returnJsonData->data->aobTrend->a){
                            $qinxiangDx = '大';
                        }else{
                            $qinxiangDx = '小';
                        }
                    }


                        // 标题
                    $ymd = substr($g[8], 4, 2).'/'.substr($g[8], 6, 2);
                    $union = $g[2];
                    $home = $g[13];
                    $away = $g[16];
                    $time = $g[9];
                    $shi = explode(':', $time)[0];
                    $shi_str = '';
                    if($shi >= 6 && $shi < 8){
                        $shi_str = '早上';
                    }elseif($shi >= 8 && $shi < 11){
                        $shi_str = '上午';
                    }elseif ($shi >= 11 && $shi < 14){
                        $shi_str = '中午';
                    }elseif ($shi >= 14 && $shi < 18){
                        $shi_str = '下午';
                    }elseif ($shi >= 18 && $shi < 20){
                        $shi_str = '傍晚';
                    }elseif ($shi >= 20 && $shi < 24){
                        $shi_str = '晚上';
                    }elseif ($shi > 0 && $shi < 6){
                        $shi_str = '凌晨';
                    }

                    // 标题
                    $title = $ymd.' '.$union.' '.$home.' VS '.$away.' 赛事前瞻情报';
                    // 摘要
                    $description = $union.'最新战报:北京时间'.substr($g[8], 4, 2).'月'.substr($g[8], 6, 2).'日'.$shi_str.$time.'，'.$union.'，'.$home.'VS'.$away.'，两队历史交战近'.$match_fight['data']['total'].'场，'.$home.'取得'.$match_fight['data']['win'].'胜'.$match_fight['data']['draw'].'平'.$match_fight['data']['fail'].'负';
                    // 基本面
                    $tem = '<h2>'.$home.' VS '.$away.'基本面分析：</h2>';
                    if(isset($match_integral)){
                        $tem_str1 = '<p>目前'.$home.'共赛'.$match_integral[0][0][4].'场，胜'.$match_integral[0][0][6].'场、平'.$match_integral[0][0][8].'场、负'.$match_integral[0][0][10].'场得'.$match_integral[0][0][16].'分联赛排名第'.$match_integral[0][0][18].'位。'.$away.'共赛'.$match_integral[1][0][4].'场，胜'.$match_integral[1][0][6].'场，平'.$match_integral[1][0][8].'场、负'.$match_integral[1][0][10].'场得'.$match_integral[1][0][16].'分联赛排名第'.$match_integral[1][0][18].'位。</p>';
                    }else{
                        $tem_str1 = '';
                    }
                    $tem_str2 = '<p>两队历史交战近'.$match_fight['data']['total'].'场，'.$home.'取得'.$match_fight['data']['win'].'胜'.$match_fight['data']['draw'].'平'.$match_fight['data']['fail'].'负，胜率'.$match_fight['data']['win_per'].'%，赢盘率：'.$match_fight['data']['win_pan_per'].'%。</p>';
                    $tem_str3 = '<p>两队近期交战近'.$recent_fight[0]['data']['total'].'场，'.$recent_fight[0]['home'].$recent_fight[0]['data']['win'].'胜'.$recent_fight[0]['data']['draw'].'平'.$recent_fight[0]['data']['fail'].'负，胜率达到'.$recent_fight[0]['data']['win_per'].'%，赢盘率达到'.$recent_fight[0]['data']['win_pan_per'].'%。';
                    $tem_str4 = $recent_fight[1]['home'].$recent_fight[1]['data']['win'].'胜'.$recent_fight[1]['data']['draw'].'平'.$recent_fight[1]['data']['fail'].'负，胜率达到'.$recent_fight[1]['data']['win_per'].'%，赢盘率达到'.$recent_fight[1]['data']['win_pan_per'].'%。</p>';
                    $jiben = $tem.$tem_str1.$tem_str2.$tem_str3.$tem_str4.'</p>';
                    // 盘口
                    $tem = '<h2>'.$home.' VS '.$away.'盘口分析：</h2>';
                    $tem_str1 = '<p>根据全球体育比分提供数据，本场比赛亚盘初盘开出'.$g[34].'盘，主队'.$g[33].'客队'.$g[35].'，即时盘机构倾向'.$qinxiangYp.'，机构对'.$qinxiangYp.'支持力度较强。大小球初盘开出'.$g[37].'球盘，即时盘机构倾向'.$qinxiangDx.'球，机构对打出'.$qinxiangDx.'球支持力度较强。</p>';
                    $pankou = $tem.$tem_str1;
                    // 情报
                    $mpInfoStart = '<h2>'.$home.'VS'.$away.'情报收集：</h2>';
                    $HpmInfo = '<h3>'.$home.'：</h3>';
                    foreach ($preMatchinfo['HpmInfo'] as $item){
                        $HpmInfo .= '<p>'.$item.'</p>';
                    }
                    $ApmInfo = '<h3>'.$away.'：</h3>';;
                    foreach ($preMatchinfo['ApmInfo'] as $item){
                        $ApmInfo .= '<p>'.$item.'</p>';
                    }

                    // 是否Wifi推送
                    $gameTeam = M('GameTeam');
                    $where_str = 'team_id in ('.$g[11].', '.$g[12].') AND wifi_push = 1';
                    $gameTeamWifi = $gameTeam->where($where_str)->select();
                    if($gameTeamWifi){
                        $wifi = 1;
                    }else{
                        $wifi = 0;
                    }
                    $t = time();
                    // save
                    $data['title'] = $title;
                    $data['short_title'] = '';
                    $data['remark'] = $description;
                    $data['content'] = $jiben.$pankou.$mpInfoStart.$HpmInfo.$ApmInfo;
                    $data['en_content'] = $jiben.$pankou.$mpInfoStart.$HpmInfo.$ApmInfo;
                    $data['class_id'] = $class_id;
                    $data['game_id'] = $game_id;
                    $data['union_id'] = $g[1];
                    $data['user_id'] = $this->user_id;
                    $data['add_time'] = $t;
                    $data['update_time'] = $t;
                    $data['hs_recommend'] = $wifi;
                    $data['is_original'] = 1;

                    //入库
                    $result = $publish->data($data)->add();

                    //缩略图
                    $_FILES['fileInput'] = D("Cover")->cover($result, $game_id, 0);
                    $up_result = D('Uploads')->uploadImg("fileInput", "publish", $result,'',"[[400,400,{$result}]]");
                    if($up_result['status'] == 1){
                        $up_data['img'] = $up_result['url'];
                        $publish->where('id='.$result)->save($up_data);
                    }

                    // vip小编
                    $VipTitle = $ymd.' '.$union.'密报 '.$home.' VS '.$away;
                    $class_id = 110; // vip小编ID
                    $VipData['title'] = $VipTitle;
                    $VipData['short_title'] = '';
                    $VipData['remark'] = $description;
                    $VipData['content'] = $mpInfoStart.$HpmInfo.$ApmInfo;
                    $VipData['en_content'] = $mpInfoStart.$HpmInfo.$ApmInfo;
                    $VipData['class_id'] = $class_id;
                    $VipData['game_id'] = $game_id;
                    $VipData['union_id'] = $g[1];
                    $VipData['user_id'] = $this->user_vip_id;
                    $VipData['add_time'] = $t;
                    $VipData['update_time'] = $t;
                    $VipData['is_original'] = 1;
                    $VipData['img'] = C('STATIC_SERVER').'/Public/Home/images/expert/vip.png';
                    //vip小编入库
                    $result = $publish->data($VipData)->add();

                    // 生成baidu推送href
                    $classArr  = getPublishClass(0);
                    $baiduHref[] = newsUrl($result, $t, $class_id, $classArr);
                    //有,进行推送
                    if($baiduHref){
                        $result = baiduPushNews($baiduHref);
                    }
                    break; // 只生成一篇
                }
            }

        }
    }

    /**
     * 生成资讯战报
     */
    public function report(){

        //获取赛事
        $unionId = !empty($_REQUEST['unionId']) ? $_REQUEST['unionId'] : null;
        $subId = !empty($_REQUEST['subId']) ? $_REQUEST['subId'] : null;
        $webfbService = new \Home\Services\WebfbService();
        $gameData = $webfbService->fbtodayList($unionId, $subId);
        $game = $gameData['info']; //赛事

        // 1 2 级赛事
        foreach ($game as $g){
            // 6 [0 1 2 3]级赛事
            if(($g[6] == 0 || $g[6] == 1 || $g[6] == 2) && $g[7] == -1 ){
                $one_two_game[] = $g;
                $g_id[] = $g[0];
            }
        }

        $class_id = 109;// 战报 ID
        $publish = M('PublishList');
        $baiduHref = [];
        foreach ($one_two_game as $g){
            $gameId = $g[0];
            // find
            $where_str = 'game_id = '.$gameId.' AND class_id = '.$class_id;
            $old = $publish->where($where_str)->select();
            if($old){
                continue;
            }

            $ymd = substr($g[8], 0, 4).'年'.substr($g[8], 4, 2).'月'.substr($g[8], 6, 2).'日';
            $union = $g[2];
            $home = $g[13];
            $homeId = $g[11];
            $awayId = $g[12];
            $away = $g[16];
            $month = substr($g[8], 4, 2);
            $day = substr($g[8], 6, 2);
            $time = $g[9];
            $shi = explode(':', $time)[0];
            if($shi >= 6 && $shi < 8){
                $shi_str = '早上';
            }elseif($shi >= 8 && $shi < 11){
                $shi_str = '上午';
            }elseif ($shi >= 11 && $shi < 14){
                $shi_str = '中午';
            }elseif ($shi >= 14 && $shi < 18){
                $shi_str = '下午';
            }elseif ($shi >= 18 && $shi < 20){
                $shi_str = '傍晚';
            }elseif ($shi >= 20 && $shi < 24){
                $shi_str = '晚上';
            }elseif ($shi > 0 && $shi < 6){
                $shi_str = '凌晨';
            }
            // 总角球
//            $jiaoqiu = $g[29]+$g[30];
//            if(!$jiaoqiu){
//                $jiaoqiu = 0;
//            }
            // 总黄牌
            $yesllow = $g[27]+$g[28];
            if(!$yesllow){
                $yesllow = 0;
            }
            // 总红牌
            $red = $g[25]+$g[26];
            if(!$red){
                $red = 0;
            }
            // 总进球
            $goals = $g[21]+$g[22];

            //赛事事件、技术
            $scroe = A('Score');
            $detail = $webfbService->getDetailWeb($gameId);   //数据库取值

            // 接口中的赛事事件

            $url='https://www.qqty.com/Api510/Appdata/textSkill?gameId='.$gameId.'&nosign=api_qqty_ipa';
            //var_dump($url);
            $returnData = $this->_curl_get_https($url);
            $returnJsonData = json_decode($returnData);
            //var_dump($returnJsonData);

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
            $eventRe_s = $scroe->multi_array_sort($eventRe_s, '1');

            //比赛阵容
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

            // 组装数据 start
            // 首发
            $homeStart = $awayStart = '';
            foreach ($lineupStart as $lineup){
                $temStr = $lineup['home'][2].'-'.$lineup['home'][1].'  ';
                $homeStart = $homeStart.$temStr;

                $temStr = $lineup['away'][2].'-'.$lineup['away'][1].'  ';
                $awayStart = $awayStart.$temStr;
            }
            //替补
            $homeSub = $awaySub = '';
            foreach ($lineupSub as $lineup){
                $temStr = $lineup['home'][2].'-'.$lineup['home'][1].'  ';
                $homeSub = $homeSub.$temStr;

                $temStr = $lineup['away'][2].'-'.$lineup['away'][1].'  ';
                $awaySub = $awaySub.$temStr;
            }
            $first = '';
            if($homeStart && $awayStart && $homeSub && $awaySub){
                $first = '<h2>'.$home.'首发阵容：</h2><p>'.$homeStart.'</p><h2>替补球员：</h2><p>'.$homeSub.'</p><h2>'.$away.'首发阵容：</h2><p>'.$awayStart.'</p><h2>替补球员：</h2><p>'.$awaySub.'</p>';
            }

            // 战报
            $report = '';
            // 统计总点球
            $penaltyHome = $penaltyAway = $allPenalty = 0;
            $temStr = '<h2>'.$home.'VS'.$away.'本场赛事赛况：</h2>';
            $report = $temStr;
            foreach ($returnJsonData->data->det as $eRe){
                if($eRe[2] == 3){
                    // 黄牌事件
                    $eTip = '<p>第'.$eRe[3].'分钟，主裁判判罚'.$eRe[6].'犯规吃到一张黄牌。</p>';
                    $report = $report.$eTip;
                }elseif ($eRe[2] == 9){
                    // 红牌事件
                    $eTip = '<p>第'.$eRe[3].'分钟，主裁判判罚'.$eRe[6].'红牌被罚下场。</p>';
                    $report = $report.$eTip;
                }elseif ($eRe[2] == 11){
                    // 换人事件
                    // 主队
                    if($eRe[1] == 1){
                        $eHome = $home;
                    // 客队
                    }elseif($eRe[1] == 0){
                        $eHome = $away;
                    }
                    $arrName = explode('↑', $eRe[6]);
                    $eTip = '<p>第'.$eRe[3].'分钟，'.$eHome.'-'.$arrName[0].'换下'.$arrName[1].'。</p>';
                    $report = $report.$eTip;
                }elseif($eRe[2] == 7){
                    // 点球事件 进
                    $allPenalty = ++$allPenalty;
                    // 主队
                    if($eRe[1] == 1){
                        $eHome = $home;
                        $penaltyHome = ++$penaltyHome;
                        // 客队
                    }elseif($eRe[1] == 0){
                        $eHome = $away;
                        $penaltyAway = ++$penaltyAway;
                    }
                    $eTip = '<p>第'.$eRe[3].'分钟，'.$eHome.'-'.$eRe[6].'主罚点球入网。</p>';
                    $report = $report.$eTip;
                }elseif ($eRe[2] == 13){
                    // 射失点球事件
                    $allPenalty = ++$allPenalty;
                }elseif($eRe[2] == 8){
                    // 乌龙事件
                    if($eRe[1] == 1){
                        $eHome = $home;
                        // 客队
                    }elseif($eRe[1] == 0){
                        $eHome = $away;
                    }
                    $eTip = '<p>第'.$eRe[3].'分钟，'.$eHome.'-'.$eRe[6].'失误打进了一粒乌龙球。</p>';
                    $report = $report.$eTip;
                }elseif($eRe[2] == 1){
                    //进球事件
                    // 主队
                    if($eRe[1] == 1){
                        $eHome = $home;
                        // 客队
                    }elseif($eRe[1] == 0){
                        $eHome = $away;
                    }
                    $eTip = '<p>第'.$eRe[3].'分钟，'.$eHome.'-'.$eRe[6].'攻入进球。</p>';
                    $report = $report.$eTip;
                }
            }

                /*
                 *   <div class="icon"><img src="/images/bf_img/1.png" />入球</div>
    <div class="icon"><img src="/images/bf_img/7.png" />点球</div>
    <div class="icon"><img src="/images/bf_img/8.png" />乌龙</div>
    <div class="icon"><img src="/images/bf_img/12.png" />助攻</div>
    <div class="icon"><img src="/images/bf_img/3.png" />黄牌</div>
    <div class="icon"><img src="/images/bf_img/2.png" />红牌</div>
    <div class="icon"><img src="/images/bf_img/9.png" />两黄变红</div>
    <div class="icon"><img src="/images/bf_img/55.png" />标注</div>
    <div class="icon"><img src="/images/bf_img/11.png" />换人</div>
    <div class="icon"><img src="/images/bf_img/4.png" />换入</div>
    <div class="icon"><img src="/images/bf_img/5.png" />换出</div>
    <div class="icon"><img src="/images/bf_img/13.png" />射失点球</div>
    <div class="icon"><img src="/images/bf_img/14.png" />扑出点球</div>
    <div class="icon"><img src="/images/bf_img/15.png" />射中门柱</div>
    <div class="icon"><img src="/images/bf_img/16.png" />最佳球员</div>
    <div class="icon"><img src="/images/bf_img/20.png" />犯规造成点球</div>
    <div class="icon"><img src="/images/bf_img/17.png" />失误导致失球</div>
    <div class="icon"><img src="/images/bf_img/19.png" />门线救险</div>
    <div class="icon"><img src="/images/bf_img/18.png" />最后防守球员</div>
    <div class="icon"><img src="/images/bf_img/21.png" />最后运球</div>
                 */
           // }
            $report .= '</p>';

            // 汇总
            $all = '';
            $corner = $yellowCard = $redCard = 0;
            $goalKickHome = $shootInsideHome = $offsideHome = $ballKeepHome = $freeKickHome = $foulHome = $offsideHome = 0;
            $goalKickAway = $shootInsideAway = $offsideAway = $ballKeepAway = $freeKickAway = $foulAway = $offsideAway = 0;

            // logo
            $homeLogo = getLogoTeam($homeId, 1, 1);
            $awayLogo = getLogoTeam($awayId, 2, 1);
            $tStart = '<ul class="tableHead">
                      <li class="tableLeft left">
                        <img src="'.$homeLogo.'"><p>
                        '.$home.'</p>
                      </li>
                      <li class="tableMiddle middle"><p>
                        '.$g[21].':'.$g[22].'</p>
                      </li>
                      <li class="tableRight right"><p>
                        '.$away.'</p>
                        <img src="'.$awayLogo.'">
                      </li>
                    </ul>';
            $tableStart = '<div class="tableProgress">';
            foreach ($eventRe_s as $eRe_s){
                if($eRe_s[1] == 3){
                    // 射门
                    $goalKickHome = $eRe_s[2];
                    $goalKickAway = $eRe_s[3];
                    $goalKick = $eRe_s[2] + $eRe_s[3];
                    if($goalKick){
                        $tableStart .= $this->_str($goalKickHome, $goalKickAway, '射门');
                    }
                }elseif($eRe_s[1] == 4){
                    // 射中
                    $shootInsideHome = $eRe_s[2];
                    $shootInsideAway = $eRe_s[3];
                    $shootInside = $eRe_s[2] + $eRe_s[3];
                    if($shootInside){
                        $tableStart .= $this->_str($shootInsideHome, $shootInsideAway, '射中');
                    }
                }elseif($eRe_s[1] == 5){
                    // 犯规
                    $foulHome = $eRe_s[2];
                    $foulAway = $eRe_s[3];
                    $foul = $eRe_s[2] + $eRe_s[3];
                    if($foul){
                        $tableStart .= $this->_str($foulHome, $foulAway, '犯规');
                    }
                }elseif ($eRe_s[1] == 6){
                    // 角球
                    $cornerHome = $eRe_s[2];
                    $cornerAway = $eRe_s[3];
                    $corner = $eRe_s[2] + $eRe_s[3];
                    if($corner){
                        $tableStart .= $this->_str($cornerHome, $cornerAway, '角球');
                    }
                }elseif($eRe_s[1] == 8){
                    // 任意球
                    $freeKickHome = $eRe_s[2];
                    $freeKickAway = $eRe_s[3];
                    $freeKick = $eRe_s[2] + $eRe_s[3];
                    if($freeKick){
                        $tableStart .= $this->_str($freeKickHome, $freeKickAway, '任意球');
                    }
                }elseif($eRe_s[1] == 9){
                    // 越位
                    $offsideHome = $eRe_s[2];
                    $offsideAway = $eRe_s[3];
                    $offside = $eRe_s[2] + $eRe_s[3];
                    if($offside){
                        $tableStart .= $this->_str($offsideHome, $offsideAway, '越位');
                    }
                }elseif ($eRe_s[1] == 11){
                    // 黄牌
                    $yellowCardHome = $eRe_s[2];
                    $yellowCardAway = $eRe_s[3];
                    $yellowCard = $eRe_s[2] + $eRe_s[3];
                    if($yellowCard){
                        $tableStart .= $this->_str($yellowCardHome, $yellowCardAway, '黄牌');
                    }
                }elseif ($eRe_s[1] == 13){
                    // 红牌
                    $redCardHome = $eRe_s[2];
                    $redCardAway = $eRe_s[3];
                    $redCard = $eRe_s[2] + $eRe_s[3];
                    if($redCard){
                        $tableStart .= $this->_str($redCardHome, $redCardAway, '红牌');
                    }
                }elseif ($eRe_s[1] == 14){
                    // 控球率
                    $ballKeepHome = $eRe_s[2];
                    $ballKeepAway = $eRe_s[3];
                    $ballKeep = $eRe_s[2] + $eRe_s[3];
                    if($ballKeep){
                        $tableStart .= $this->_str($ballKeepHome, $ballKeepAway, '控球率');
                    }
                }elseif ($eRe_s[1] == 29){
                    // 换人
                    $subsitutionHome = $eRe_s[2];
                    $subsitutionAway = $eRe_s[3];
                    $subsitution = $eRe_s[2] + $eRe_s[3];
                    if($subsitution){
                        $tableStart .= $this->_str($subsitutionHome, $subsitutionAway, '换人');
                    }
                }elseif ($eRe_s[1] == 34){
                    // 射偏
                    $shootOutsideHome = $eRe_s[2];
                    $shootOutsideAway = $eRe_s[3];
                    $shootOutside = $eRe_s[2] + $eRe_s[3];
                    if($shootOutside){
                        $tableStart .= $this->_str($shootOutsideHome, $shootOutsideAway, '射偏');
                    }
                }elseif ($eRe_s[1] == 40){
                    // 界外球
                    $throwInHome = $eRe_s[2];
                    $throwInAway = $eRe_s[3];
                    $throwIn = $eRe_s[2] + $eRe_s[3];
                    if($throwIn){
                        $tableStart .= $this->_str($throwInHome, $throwInAway, '界外球');
                    }
                }elseif ($eRe_s[1] == 43){
                    // 进攻
                    $attackHome = $eRe_s[2];
                    $attackAway = $eRe_s[3];
                    $attack = $eRe_s[2] + $eRe_s[3];
                    if($attack){
                        $tableStart .= $this->_str($attackHome, $attackAway, '进攻');
                    }
                }elseif ($eRe_s[1] == 44){
                    // 危险进攻
                    $dangerAttackHome = $eRe_s[2];
                    $dangerAttackAway = $eRe_s[3];
                    $dangerAttack = $eRe_s[2] + $eRe_s[3];
                    if($dangerAttack){
                        $tableStart .= $this->_str($dangerAttackHome, $dangerAttackAway, '危险进攻');
                    }
                }
            }
            $temAllStr0 = '<h2>'.$home.'VS'.$away.'本场赛事数据汇总：</h2>';
            $temAllStr1 = '<p class="headline">本场比赛一共产生'.$corner.'个角球，'.$yellowCard.'张黄牌，'.$redCard.'张红牌，'.$allPenalty.'个点球，'.$goals.'个进球。</p>';
            $temAllStr2 = '<p>'.$home.'射门'.$goalKickHome.'次，射正'.$shootInsideHome.'次，点球'.$penaltyHome.'个，任意球'.$freeKickHome.'次，犯规'.$foulHome.'次，越位'.$offsideHome.'次。</p>';
            $temAllStr3 = '<p>'.$away.'射门'.$goalKickAway.'次，射正'.$shootInsideAway.'次，点球'.$penaltyAway.'个，任意球'.$freeKickAway.'次，犯规'.$foulAway.'次，越位'.$offsideAway.'次。</p>';
            $all = $temAllStr0.$temAllStr1.$temAllStr2.$temAllStr3;

            $tableEnd = '</div>';
            $table = $tStart.$tableStart.$tableEnd;
            $all_s = $all.$first.$table;
            // 标题
            $tip = '';
            $lose = $g[21] - $g[22];
            if((0 == $g[21]) && ($g[21] == $g[22])){
                $tip = $this->resultTip['pj0'][array_rand($this->resultTip['pj0'])];
            }elseif ((0 != $g[21]) && ($g[21] == $g[22])){
                $tip = $this->resultTip['pj'][array_rand($this->resultTip['pj'])];
            }elseif ((0 < $lose) &&  ($lose < 3)){
                $tip = $this->resultTip['win'][array_rand($this->resultTip['win'])].$away;
            }elseif ($lose >= 3){
                $tip = $this->resultTip['win3'][array_rand($this->resultTip['win3'])].$away;
            }elseif (($lose > -3) && ($lose < 0)){
                $tem = $this->resultTip['lose'][array_rand($this->resultTip['lose'])];
                if(strpos($tem, '不敌')){
                    $tip = $tem.$away;
                }elseif(strpos($tem, '失利')){
                    $tip = $home.$tem;
                }
            }elseif($lose <= -3){
                $tem = $this->resultTip['lose3'][array_rand($this->resultTip['lose3'])];
                if(strpos($tem, 'X')){
                    $tip = str_replace('X', abs($lose), $tem);
                }else{
                    $tip = $tem.$away;
                }
            }
            $title = $g[2].'：'.$g[13].' VS '.$g[16].' '.$tip.' 比分 '.$g[21].'-'.$g[22];

            // 摘要
            $description = $union.'最新战报:北京时间'.$month.'月'.$day.'日'.$shi_str.$time.'，'.$home.'VS'.$away.'，全场比分'.$g[21].':'.$g[22].' '.$tip.' .本场比赛一共产生'.$corner.'个角球，'.$yesllow.'张黄牌，'.$red.'张红牌，'.$allPenalty.'个点球，'.$goals.'个进球。本场足场裁判吹响比赛终场哨声，'.$home.$g[21].':'.$g[22].'最终'.$tip;

            // 组装数据 end

            // 是否Wifi推送
            $gameTeam = M('GameTeam');
            $where_str = 'team_id in ('.$g[11].', '.$g[12].') AND wifi_push = 1';
            $gameTeamWifi = $gameTeam->where($where_str)->select();
            if($gameTeamWifi){
                $wifi = 1;
            }else{
                $wifi = 0;
            }

            $t = time();
            // save
            $data['title'] = $title;
            $data['short_title'] = '';
            $data['remark'] = $description;
            $data['content'] = $report.$all_s.'';
            $data['en_content'] = $report.$all_s.'';
            $data['class_id'] = $class_id; // 战报 ID
            $data['game_id'] = $gameId;
            $data['union_id'] = $g[1];
            $data['user_id'] = $this->user_id;
            $data['add_time'] = $t;
            $data['update_time'] = $t;
            $data['hs_recommend'] = $wifi;
            $data['is_original'] = 1;

            $result = $publish->data($data)->add();
            //缩略图
            $_FILES['fileInput'] = D("Cover")->cover($result, $gameId, 0);
            $up_result = D('Uploads')->uploadImg("fileInput", "publish", $result,'',"[[400,400,{$result}]]");
            if($up_result['status'] == 1){
                $up_data['img'] = $up_result['url'];
                $publish->where('id='.$result)->save($up_data);
            }
            // 生成baidu推送href
            $classArr  = getPublishClass(0);
            $baiduHref[] = newsUrl($result, $t, $class_id, $classArr);

        }
        //有,进行推送
        if($baiduHref){
            $result = baiduPushNews($baiduHref);
        }
    }

    private function _curl_get_https($url){
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, true);  // 从证书中检查SSL加密算法是否存在
        $tmpInfo = curl_exec($curl);     //返回api的json对象
        //关闭URL请求
        curl_close($curl);
        return $tmpInfo;    //返回json对象
    }

    private function _str($hData, $aData, $tip, $toDo=true){
        if($toDo){
            $hPercent = round($hData/($hData+$aData)*100,2).'%';
            $aPercent = round($aData/($hData+$aData)*100,2).'%';
        }else{
            $hPercent = round($hData/($hData+$aData)*100,2);
            $aPercent = round($aData/($hData+$aData)*100,2);
        }
        $temStr = '<div class="unit clearfix">
                        <div class="unitLeft">'.$hData.'</div>
                        <div class="progressLeft"><span style="width: '.$hPercent.'"></span></div>
                        <div class="unitWords">'.$tip.'</div>
                        <div class="progressRight"><span style="width: '.$aPercent.'"></span></div>
                        <div class="unitRight">'.$aData.'</div>
                      </div>';

        return $temStr;
    }
}