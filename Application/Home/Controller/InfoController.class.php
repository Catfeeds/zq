<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/8
 * Time: 11:29
 */

use Think\Tool\Tool;
use Home\Services\WebfbService;

class InfoController extends CommonController
{
    private $domain = 'data';

    protected function _initialize(){
        $domain = explode('.', $_SERVER['HTTP_HOST'])[0];
        if($domain != $this->domain){
            parent::_empty();
        }
        parent::_initialize();
    }
    /**
     * 资料库-首页
     */
    public function index()
    {
        $mService = mongoService();

        $continent = $mService->select('fb_continent', [], ['continent_id', 'continent_name']);
        //顶级洲际赛事分类
        $topConIdArrs = $topCouIdArrs = $countryMap = $countryIds = $countryIds2 = [];

        $mService->index = ['s_name' => 1];
        foreach ($continent as $coKey => $coVal) {
            //洲际关联country_id
            $country1 = $mService->fetchRow('fb_country',
                ['s_name' => $coVal['continent_name'][0]],
                ['country_id', 'images']
            );

            $continent[$coKey]['name'] = $coVal['continent_name'][0];
            $continent[$coKey]['t_name'] = $coVal['continent_name'][1];
            $continent[$coKey]['country_id'] = (int)$country1['country_id'];
            $continent[$coKey]['logo'] = $this->getLogo($country1, 1);

            $countryNameMap[] = $continent[$coKey]['name'];
            $topConIdArrs[] = (string)$coVal['continent_id'];
            $topCouIdArrs[] = (int)$country1['country_id'];

            unset($continent[$coKey]['continent_name']);
        }

        //洲际赛事下所有国家
        $mService->index = ['continent_id' => 1];
        if (!$fb_country = S('home_data_fb_country')) {
            $fb_country = $mService->select(
                'fb_country',
                ['continent_id' => ['$in' => $topConIdArrs]],
                ['country_id', 's_name', 't_name', 'continent_id', 'images', 'image_urls', 'order']
            );
            S('home_data_fb_country', $fb_country, 3600 * 3);
        }

        $country = [];
        foreach ($fb_country as $couKey => $couVal) {
            if (is_int($couVal['country_id'])) {
                $country[] = $couVal;
            }
        }

        $country_order = array_column($country, 'order');
        array_multisort($country_order, SORT_ASC, $country);

        foreach ($country as $couKey => $couVal) {
            $country[$couKey]['logo'] = '/Public/Home/images/info/country_icon/' . $couVal['country_id'] . '.png';
            unset($country[$couKey]['_id'], $country[$couKey]['images'], $country[$couKey]['image_urls']);
            $countryMap[$couVal['continent_id']][] = $country[$couKey];

            if (!in_array($couVal['country_id'], $topCouIdArrs)) {
                $countryIds2[] = $couVal['country_id'];
            }
        }

        //洲际所有赛事
        $mService->index = ['country_id' => 1];

        if (!$continent_unions = S('home_data_continent_unions')) {
            $continent_unions = $mService->select(
                'fb_union',
                ['country_id' => ['$in' => $topCouIdArrs]],
                ['country_id', 'union_id', 'union_name', 'images', 'image_urls', 'union_or_cup', 'order', 'union_level']
            );
            S('home_data_continent_unions', $continent_unions, 3600 * 3);
        }


        foreach ($continent_unions as $ck => $cv) {
            $order = $cv['order'] === null || $cv['order'] === '' ? 3 : $cv['order'];
            $continent_unions_order[$cv['union_id']] = $unions_order[$cv['union_id']] = $order;
            $union_level[$cv['union_id']] = $cv['union_level'];
        }

        array_multisort($continent_unions_order, SORT_ASC, $continent_unions);

        foreach ($continent_unions as $uk => $uv) {
            if (!$uv['union_name']) {
                unset($continent_unions[$uk]);
            } else {
                $continent_unions[$uk]['union_name'] = $uv['union_name'][0];
                $continent_unions[$uk]['jump_url'] = U('/cupMatch/' . $uv['union_id'] . '@data');
                $continent_unions[$uk]['logo'] = $this->getLogo($uv, 1);
                unset($continent_unions[$uk]['_id'], $continent_unions[$uk]['image_urls'], $continent_unions[$uk]['images']);
            }
        }

        //国家所有赛事
        if (!$country_unions = S('home_data_country_unions') ) {
            $mService->index = ['country_id' => 1];
            $country_unions = $mService->select(
                'fb_union',
                ['country_id' => ['$in' => array_unique($countryIds2)]],
                ['country_id', 'union_id', 'union_name', 'images', 'image_urls',
                    'union_or_cup', 'order', 'union_level','is_league', 'union_or_cup'
                ]
            );
            S('home_data_country_unions', $country_unions, 3600 * 3);
        }

        foreach ($country_unions as $ck => $cv) {
            $order = $cv['order'] === null || $cv['order'] === '' ? 3 : $cv['order'];
            $unions_order[$cv['union_id']] = $country_unions_order[] = $order;
            $union_level[$cv['union_id']] = $cv['union_level'];
        }
        array_multisort($country_unions_order, SORT_ASC, $country_unions);

        foreach ($country_unions as $couKey2 => $couVal2) {

            if ($couVal2['union_name']) {
                $country_unions[$couKey2]['union_name'] = $couVal2['union_name'][0];
                $country_unions[$couKey2]['logo'] = $this->getLogo($couVal2, 1);

                $union_sort2[] = $couVal2['union_id'];
                $union_level_sort2[] = $couVal2['level'];

                $is_league = isset($couVal2['union_or_cup']) ? $couVal2['union_or_cup'] : $couVal2['is_league'];
                if ($is_league == 2) {
                    $country_unions[$couKey2]['jump_url'] = U('/cupMatch/' . $couVal2['union_id'] . '@data');
                } else {
                    $country_unions[$couKey2]['jump_url'] = U('/league/' . $couVal2['union_id'] . '@data');
                }

                unset($country_unions[$couKey2]['_id'], $country_unions[$couKey2]['image_urls'], $country_unions[$couKey2]['images']);
            } else {
                unset($country_unions[$couKey2]);
            }
        }

        //今日赛事
        if (strtotime('10:32:00') < time()) {
            $startTime = strtotime('8:00:00');
            $endTime = strtotime('10:32:00') + 3600 * 24;
        } else {
            $startTime = strtotime('8:00:00') - 3600 * 24;
            $endTime = strtotime('10:32:00');
        }

        $mService->index = ['game_start_timestamp' => 1];
        if (!$gameList = S('home_data_today_gamelist_' . $startTime . $endTime)) {
            $gameList = $mService->select(
                'fb_game',
                ['game_start_timestamp' => ['$lt' => $endTime, '$gt' => $startTime]],
                ['game_id', 'union_id', 'union_name', 'game_start_timestamp', 'home_team_name',
                    'away_team_name', 'game_state', 'home_team_id', 'away_team_id'
                ]
            );

            S('home_data_today_gamelist_' . $startTime . $endTime, $gameList, 3600 * 6);
        }

        foreach ($gameList as $gk => $gv) {
            $gameList[$gk]['home_team_name'] = $gv['home_team_name'][0];
            $gameList[$gk]['away_team_name'] = $gv['away_team_name'][0];
            $gameList[$gk]['union_name'] = $gv['union_name'][0];
            $gameList[$gk]['gtime'] = date('H:i', $gv['game_start_timestamp']);
            $gtime_sort[] = (int)$gv['game_start_timestamp'];
            $union_sort[] = $unions_order[$gv['union_id']];
            $union_id_sort[] = $gv['union_id'];
            $state_sort[] = $gv['game_state'];

            unset($gameList[$gk]['_id']);
//            if ($union_level[$gv['union_id']] > 0 && $union_level[$gv['union_id']] < 3) {
//                $gameList[$gk]['home_team_name'] = $gv['home_team_name'][0];
//                $gameList[$gk]['away_team_name'] = $gv['away_team_name'][0];
//                $gameList[$gk]['union_name'] = $gv['union_name'][0];
//                $gameList[$gk]['gtime'] = date('H:i', $gv['game_start_timestamp']);
//                $gtime_sort[] = (int)$gv['game_start_timestamp'];
//                $union_sort[] = $unions_order[$gv['union_id']];
//                $union_id_sort[] = $gv['union_id'];
//                $state_sort[] = $gv['game_state'];
//
//                unset($gameList[$gk]['_id']);
//            } else {
//                unset($gameList[$gk]);
//            }
        }

        array_multisort($union_sort, SORT_ASC, $union_id_sort, SORT_ASC,  $state_sort, SORT_DESC, $gtime_sort, SORT_ASC, $gameList);

        //重组今日赛事
        $k = 0;
        $today_games = [];
        foreach ($gameList as $ggk => $ggv) {
            $today_games[$ggv['union_id']]['list'][] = $ggv;
            $today_games[$ggv['union_id']]['union_name'] = $ggv['union_name'];
            $today_games[$ggv['union_id']]['level'] = $ggv['level'];
            $kk = $today_games[$ggv['union_id']]['key'];
            $today_games[$ggv['union_id']]['key'] = isset($today_games[$ggv['union_id']]['key']) ? $kk : $k++;
        }

        //seo设置
        $seo = [
            'seo_title' => '足球数据库_nba数据库_联赛资料库_全球体育',
            'seo_keys' => '足球资料,足球数据库,nba数据库,球队资料,球员资料,联赛数据库',
            'seo_desc' => '全球体育资料库频道为您提供最全的足球数据库和nba数据库，涵盖球队资料、赛程积分榜、联赛数据统计、足球大小盘路，还有更详细的篮球球员和足球球员的详细得分等数据。想了解球队的完整资料和信息就在全球体育资料库频道'
        ];
        $this->setSeo($seo);

        $nav = D('Home')->getNavList(40);
        $this->assign('hot_league', $this->hot_league());
        $this->assign('nav', $nav);
        $this->assign('continent', $continent);
        $this->assign('unions', $continent_unions);
        $this->assign('country_unions', $country_unions);
        $this->assign('countryMap', $countryMap);
        $this->assign('today_games', $today_games);
        $this->display();
    }

    /**
     * 联赛主页
     */
    public function league()
    {
        $nav = D('Home')->getNavList(40);

        //获取联赛信息
        if ($union_id = I('union_id')) {
            $mService = mongoService();

            $union = $mService->fetchRow(
                'fb_union',
                ['union_id' => (int)$union_id],
                ['country_id', 'union_id', 'union_name', 'images', 'image_urls',
                    'union_or_cup', 'union_level', 'is_league', 'level', 'season'
                ]
            );

            // 查询不到联赛404
            if(!$union){
                parent::_empty();
            }
            //当前赛季
            foreach($union['season'] as $sk => $sv){
                if(strstr($sv, '-')){
                    $_season[$sk] = $sv;
                }
            }
            $season = $_season ? $_season : $union['season'];
            arsort($season);
            $season = array_values($season);

            $curSeason = I('season') ? I('season') : $season[0];

            //赛季数据
            $season_data = $mService->fetchRow('fb_union',
                ['union_id' => (int)$union_id],
                [
                    "statistics.{$curSeason}.matchResult.arrSubLeague",
                    "statistics.{$curSeason}.matchResult.current",
                    "statistics.{$curSeason}.bestlineup",
                ]
            );

            $bestlineup = $season_data["statistics"][$curSeason]["bestlineup"];//最佳阵容
            $arrSubLeague = $season_data['statistics'][$curSeason]['matchResult']['arrSubLeague'];//二级联赛
            $curSubIndex = $season_data['statistics'][$curSeason]['matchResult']['current'] ?: 0;//当前二级联赛索引
            $gameIds = $roundGame = [];


            if ($arrSubLeague) {
                $curSubLeague = $arrSubLeague[$curSubIndex] ?: $arrSubLeague[0];
                $roundData = $mService->fetchRow('fb_union',
                    ['union_id' => (int)$union_id],
                    [
                        "statistics.{$curSeason}.matchResult.{$curSubLeague[0]}.jh",
                        "statistics.{$curSeason}.matchResult.{$curSubLeague[0]}.round",
                    ]
                );

                $seasonRound = $roundData['statistics'][$curSeason]['matchResult'][$curSubLeague[0]]['jh'];//轮次
                $curRound = $roundData['statistics'][$curSeason]['matchResult'][$curSubLeague[0]]['round'];//当前轮次
                $curRound = explode('/', $curRound);

                //轮次遍历

                for ($rd = 1; $rd <= $curRound[1]; $rd++) {
                    $rData = $seasonRound['R_' . $rd];
                    foreach ($rData as $rdk => $rdv) {
                        $gameIds = array_merge($gameIds, [$rdv[4], $rdv[5]]);
                    }
                    $roundGame[$rd] = $gameIds;//轮次赛程
                }

                $hasData = $roundData['statistics'][$curSeason]['matchResult'] ? 1 : 0;//是否有数据

            } else {
                $roundData = $mService->fetchRow('fb_union',
                    ['union_id' => (int)$union_id],
                    [
                        "statistics.{$curSeason}.matchResult.round",
                        "statistics.{$curSeason}.matchResult.jh",
                    ]
                );
                $hasData = $roundData['statistics'][$curSeason]['matchResult'] ? 1 : 0;//是否有数据
                $seasonRound = $roundData['statistics'][$curSeason]['matchResult']['jh'];//轮次
                $curRound = $roundData['statistics'][$curSeason]['matchResult']['round'];//当前轮次
                $curRound = explode('/', $curRound);

                //轮次遍历
                for ($rd = 1; $rd <= $curRound[1]; $rd++) {
                    $rData = $seasonRound['R_' . $rd];

                    $gameIds = array_merge($gameIds, $rData);
                    $roundGame[$rd] = $rData;//轮次赛程
                }
            }

            //联赛信息
            $union['logo'] = $this->getLogo($union, 1, $size = 1);

            $union['union_name'] = $union['union_name'][0];
            unset($union['_id'], $union['image_urls'], $union['images']);

            //联赛下的所有球队

            $all_teams = $mService->select(
                'fb_team',
                ['union_id' => $union_id],
                ["team_id", "team_name", "team_value", "images", "img_url", "image_urls", "team_id_tzuqiu_cc"]
            );
            $team_ids = array_column($all_teams, "team_id");

            //联赛的球队、球员信息
            foreach ($all_teams as $teamsk => $teamsv) {
                unset($teamsk['_id']);
                $teams[$teamsv['team_id']]['team_name'] = $teamsv['team_name'][0];
                $teams[$teamsv['team_id']]['img_url'] = $this->getLogo($teamsv, 2);
                $teams[$teamsv['team_id']]['value'] = $teamsv['team_value'];
                $teams[$teamsv['team_id']]['team_id'] = $teamsv['team_id'];

                if ($teamsv['team_id_tzuqiu_cc'])
                    $tzuqiu_team_ids[] = $teamsv['team_id_tzuqiu_cc'];

                //身价排序数组
                $teams_value_sort[] = $teamsv['team_value'];
                $top_value_teams = $teams;
            }

            //高价球队
            array_multisort($teams_value_sort, SORT_DESC, $top_value_teams);
            $hot_team = array_splice($top_value_teams, 0, 6);

            //球员信息
            $mService->index = ['now_team_id' => 1];
            $players = $mService->select(
                'fb_player',
                ['now_team_id' => ['$in' => $team_ids]],
                ["player_id", "player_name", 'images', 'image_urls', "value", 'now_team_id', "position",
                    'recent_status_rate', 'player_id_tzuqiu_cc'
                ],
                ['recent_status_rate' => -1]
            );

            //最佳球员
            $best_players = array_splice($players, 0, 6);

            //高价/热门球员
            $value_sort = array_column($players, 'value');
            array_multisort($value_sort, SORT_DESC, $players);
            $hot_players = array_splice($players, 0, 6);

            foreach ($hot_players as $pk => $pv) {
                $hot_players[$pk]['img_url'] = $this->getLogo($pv, 3);
                $hot_players[$pk]['player_name'] = $pv['player_name'][0];
                $hot_players[$pk]['team_name'] = $teams[$pv['now_team_id']]['team_name'];
                $hot_players[$pk]['team_img'] = $teams[$pv['now_team_id']]['img_url'];
                unset($hot_players[$pk]['image_urls']);
            }

            //5大联赛新闻
            $news = [];
            $classIdMap = [
                36 => 13,
                34 => 17,
                8 => 15,
                31 => 14,
                11 => 16
            ];
            if ($class_id = $classIdMap[$union_id]) {
                $where_str = 'status = 1 AND class_id = ' . $class_id;
                $news = M('PublishList')->where($where_str)->order('add_time desc')->limit(6)->select();
                $classArr = getPublishClass(0);
                foreach ($news as $k => $v) {
                    $news[$k]['href'] = newsUrl($v['id'], $v['add_time'], $v['class_id'], $classArr);
                }
            }

            //最佳阵容
            $blineup = [];
            foreach ($bestlineup as $lk => $bline) {
                foreach ($bline as $lk2 => $lv2) {
                    $d = [
                        'player_id' => $lv2['pID'],
                        'player_name' => $lv2['pName'][0],
                        'rating' => $lv2['rating'],
                        'player_logo' => __IMAGES__ . '/info/zone-player.png',
                        'team_id' => $lv2['tID'],
                    ];
                    $blineup[$lk][$lv2['sort'][0]][] = $d;
                }
            }

            $round = array_keys($roundGame);
            krsort($round);

        }

        //seo设置
        $un = $union['union_name'];

        $seo = [
            'seo_title' => "{$curSeason}赛季_{$un}_{$un}赛程_{$un}联赛_{$un}积分榜_全球体育",
            'seo_keys' => "{$un}赛程,{$un}联赛,{$un}积分榜,{$un}射手榜,{$un}助攻榜",
            'seo_desc' => "全球体育为您提供赛季{$un}赛程、{$un}积分榜、{$un}助攻榜、{$un}射手榜等{$un}联赛数据统计，同时还有更详细的{$un}级联赛赛果、让分盘、大小球盘、半全场胜负统计、标准亚盘对照等统计信息。"
        ];
        $this->setSeo($seo);

        $this->assign('hot_league', $this->hot_league());
        $this->assign('team_worth', $hot_team);
        $this->assign('player_worth', $hot_players);
        $this->assign('best_players', $best_players);
        $this->assign('nav', $nav);
        $this->assign('union', $union);
        $this->assign('arrSubLeague', $arrSubLeague);
        $this->assign('curSeason', $curSeason);
        $this->assign('season', $season);
        $this->assign('arrSubLeague', $arrSubLeague);
        $this->assign('curSubLeague', $curSubLeague);
        $this->assign('round', $round);
        $this->assign('curRound', $curRound);
        $this->assign('bestlineup', $blineup);
        $this->assign('teams', $teams);
        $this->assign('news', $news);
        $this->assign('hasData', $hasData);
        $this->display('league');
    }

    /**
     * 联赛交易流言、转会
     */
    public function rumorTran()
    {

        if ($union_id = I('union_id')) {
            $mService = mongoService();
            //转会流言、近期转会

            $union_teams = $mService->select(
                'fb_team',
                ['union_id' => (string)$union_id],
                ['team_id', 'team_name', 'team_id_tzuqiu_cc']
            );

            $tzuqiu_team_ids = array_column($union_teams, 'team_id_tzuqiu_cc');

            $statsaly_tzuqiu = $mService->select(
                'fb_statsaly_tzuqiu',
                ['stats_type' => ['$in' => ['tran', 'rumor']]]
            );

            //获取属于当前联赛的相关交易、传闻
            foreach ($statsaly_tzuqiu as $sk => $sv) {
                foreach ($sv['stats_list'] as $sk2 => $sv2) {
                    if ($sv['stats_type'] == 'rumor') {
                        if (in_array($sv2['origClubId'], $tzuqiu_team_ids) || in_array($sv2['destClubId'], $tzuqiu_team_ids)) {
                            $rumor[] = $sv2;
                        }
                    } else if ($sv['stats_type'] == 'tran') {
                        if (in_array($sv2['mfClubId'], $tzuqiu_team_ids) || in_array($sv2['miClubId'], $tzuqiu_team_ids)) {
                            $tran[] = $sv2;
                        }
                    }
                }
            }

            $tzuqiu_player_ids = array_merge(array_column($rumor, 'playerId'), array_column($tran, 'playerId'));
            foreach ($rumor as $k => $v) {
                if ($v['origClubId'])
                    $tzuqiu_team_ids2[] = $v['origClubId'];
                if ($v['destClubId'])
                    $tzuqiu_team_ids2[] = $v['destClubId'];
            }

            foreach ($tran as $k => $v) {
                if ($v['mfClubId'])
                    $tzuqiu_team_ids2[] = $v['mfClubId'];
                if ($v['miClubId'])
                    $tzuqiu_team_ids2[] = $v['miClubId'];
            }


            //转会留言球员、球队信息比配
            if ($tzuqiu_player_ids) {
                $mService->index = ['player_id_tzuqiu_cc' => 1];
                $tzuqiu_players = $mService->select(
                    'fb_player',
                    ['player_id_tzuqiu_cc' => ['$in' => $tzuqiu_player_ids]],
                    ["player_id", "player_name", 'images', 'image_urls', "value", 'now_team_id', "position",
                        'recent_status_rate', 'player_id_tzuqiu_cc'
                    ]
                );
            }

            if ($tzuqiu_team_ids2) {
                $tzuqiu_teams = $mService->select(
                    'fb_team',
                    ['team_id_tzuqiu_cc' => ['$in' => $tzuqiu_team_ids2]],
                    ["team_id", "img_url", 'images', "image_urls", "team_id_tzuqiu_cc"]
                );
            }

            foreach ($tzuqiu_players as $pk => $pv) {
                $rumorlayers[$pv['player_id_tzuqiu_cc']]['player_id'] = $pv['player_id'];
                $rumorlayers[$pv['player_id_tzuqiu_cc']]['player_logo'] = $this->getLogo($pv, 3);
            }

            foreach ($tzuqiu_teams as $tk => $tv) {
                $rumorTeam[$tv['team_id_tzuqiu_cc']]['team_id'] = $tv['team_id'];
                $rumorTeam[$tv['team_id_tzuqiu_cc']]['img_url'] = $this->getLogo($tv, 2);
            }
            foreach ($rumor as $rk => $rv) {
                $rumor[$rk]['dest'] = $rumorTeam[$rv['destClubId']] ?: ['img_url' => '/Public/Home/images/info/zone-team.png'];;//转入球队
                $rumor[$rk]['orig'] = $rumorTeam[$rv['origClubId']] ?: ['img_url' => '/Public/Home/images/info/zone-team.png'];;//转出球队
                $rumor[$rk]['player_id'] = $rumorlayers[$rv['playerId']]['player_id'];
                $rumor[$rk]['player_logo'] = $rumorlayers[$rv['playerId']]['player_logo'] ?: '/Public/Home/images/info/zone-player.png';
            }
            foreach ($rumor as $rk => $rv) {
                if ($rv['player_id']) {
                    $_rumor[] = $rv;
                }
            }
            $rumor = array_slice($_rumor, 0, 6);
            foreach ($tran as $tk => $tv) {
                $tran[$tk]['dest'] = $rumorTeam[$tv['miClubId']] ?: ['img_url' => '/Public/Home/images/info/zone-team.png'];//转入球队
                $tran[$tk]['orig'] = $rumorTeam[$tv['mfClubId']] ?: ['img_url' => '/Public/Home/images/info/zone-team.png'];//转出球队
                $tran[$tk]['player_id'] = $rumorlayers[$tv['playerId']]['player_id'];
                $tran[$tk]['player_logo'] = $rumorlayers[$tv['playerId']]['player_logo'] ?: '/Public/Home/images/info/zone-player.png';
            }
            foreach ($tran as $rk => $rv) {
                if ($rv['player_id']) {
                    $_tran[] = $rv;
                }
            }
            $tran = array_slice($_tran, 0, 6);
        }
        $this->ajaxReturn(['status' => 1, 'tran' => $tran, 'rumor' => $rumor]);
    }

    /**
     * 杯赛主页
     */
    public function cupMatch()
    {
        //获取联赛信息
        if ($union_id = I('union_id')) {
            $mService = mongoService();
            $mService->index = ['union_id' => 1];
            $union = $mService->fetchRow(
                'fb_union',
                ['union_id' => (int)$union_id],
                ['country_id', 'union_id', 'union_name', 'images', 'image_urls',
                    'union_or_cup', 'union_level', 'is_league', 'level', 'season'
                ]);

            $curSeason = I('season') ? I('season') : $union['season'][0];

            //联赛信息
            $union['logo'] = $this->getLogo($union, 1);
            $union['union_name'] = $union['union_name'][0];
            unset($union['_id'], $union['image_urls'], $union['images']);

            $temp = $mService->fetchRow('fb_union',
                ['union_id' => (int)$union_id],
                ["statistics.{$curSeason}.matchResult"]
            );

            $matchResult = $temp['statistics'][$curSeason]['matchResult'];
            $arrCupKind = $matchResult['arrCupKind'];

            //根据arrCupKind查询所有赛程
            $gStr = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q'];
            $gameIds = [];
            foreach ($arrCupKind as $k => $v) {
                $rn = str_replace('.', '_', $v[4]);

                if ($v[1] == 1) {//分组赛
                    for ($i = 0; $i < $v[5]; $i++) {
                        foreach ($matchResult[$rn . '_matchs'][$gStr[$i]] as $k1 => $v1) {
                            $gameIds[] = $v1;
                        }
                    }
                } elseif ($v[1] == 0) {
                    if ($v[7] != 1) {//淘汰赛
                        foreach ($matchResult[$rn . '_matchs'] as $tk => $tv) {
                            $gameIds[] = $tv;
                        }
                    } else {//主客场
                        foreach ($matchResult[$rn . '_matchs'] as $dk => $dv) {
                            $gameIds[] = $dv[4];
                            $gameIds[] = $dv[5];
                        }
                    }
                }
            }

            if ($gameIds) {
                $games = $mService->select(
                    'fb_game',
                    ['game_id' => ['$in' => $gameIds]],
                    ['game_id', 'gtime', 'home_team_id', 'away_team_id', 'home_team_name', 'away_team_name', 'game_state', 'score']
                );



                foreach ($games as $gtk => $gtv) {
                    $gtime_sort[] = strtotime($gtv['gtime']);
                }

                array_multisort($gtime_sort, SORT_ASC, $games);

                foreach ($games as $gk => $gv) {
                    //所有球队id
                    $team_ids[] = $gv['away_team_id'];
                    $team_ids[] = $gv['home_team_id'];

                    $gt = strtotime($gv['gtime']);
                    $games[$gk]['gdate'] = $this->gwk($gv['gtime']) . " " . date('m', $gt) . "月" . date('d', $gt) . "日";
                    $games[$gk]['gtime'] = date('H:i', $gt);
                    $games[$gk]['home_team_name'] = $gv['home_team_name'][0];
                    $games[$gk]['away_team_name'] = $gv['away_team_name'][0];
                    $games[$gk]['score'] = $gv['game_state'] != 0 ? (string)$gv['score'] : '';
                    unset($games[$gk]['_id']);
                    $statistics[$games[$gk]['gdate']]['games'][] = $games[$gk];
                    $statistics[$games[$gk]['gdate']]['week'] = $this->gwk($gv['gtime']);
                    $statistics[$games[$gk]['gdate']]['date'] = date('m', $gt) . "月" . date('d', $gt) . "日";
                }

                $gameIdArr = $gameIds;

            }

            $si = 1;
            foreach ($statistics as $sk => $sv) {
                $statistics[$sk]['key'] = $si;
                $si++;
            }

            if ($team_ids) {
                $u = $mService->select(
                    'fb_team',
                    ['team_id' => ['$in' => $team_ids]],
                    ["team_id", "team_name", 'img_url', 'images', "team_value", 'image_urls']
                );

                foreach ($u as $uk => $uv) {
                    unset($uv['_id']);
                    $teams[$uv['team_id']]['team_name'] = $uv['team_name'][0];
                    $teams[$uv['team_id']]['img_url'] = $this->getLogo($uv, 2);
                    $teams[$uv['team_id']]['value'] = $uv['team_value'];
                    $teams[$uv['team_id']]['team_id'] = $uv['team_id'];
                    $teams_value_sort[] = $uv['team_value'];
                    $t_teams = $teams;
                }
            }

            array_multisort($teams_value_sort, SORT_DESC, $t_teams);

            //赛程积分-小组、淘汰赛
            foreach ($arrCupKind as $k2 => $v2) {
                $rn = str_replace('.', '_', $v2[4]);
                if ($v2[1] != 1) {
                    if ($v2[7] != 1) {
                        foreach ($matchResult[$rn . '_matchs'] as $tk => $tv) {
                            $knockout_gameIds[$v2[4]][] = $kn_game_ids[] = $tv;
                        }
                    } else {
                        foreach ($matchResult[$rn . '_matchs'] as $dk => $dv) {
                            $knockout_gameIds[$rn][] = $kn_game_ids[] = $dv[4];
                            $knockout_gameIds[$rn][] = $kn_game_ids[] = $dv[5];
                        }
                    }
                } else {
                    $score_rank[$v2[4]] = $matchResult[$v2[4]];
                }

                $cup_score_types[] = [$v2[4], $v2[2], $v2[1], $v2[6]];
            }

            //淘汰赛半全场赔率
            if ($kn_game_ids) {
                $games2 = $mService->select(
                    'fb_game',
                    ['game_id' => ['$in' => $kn_game_ids]],
                    ['game_id', 'gtime', 'home_team_name', 'away_team_name','score', 'half_score', 'worldcup_num', 'asia_odds_sb_half', 'bigsmall_odds_sb_half', 'home_team_id', 'away_team_id', 'match_odds_m_asia', 'match_odds_m_bigsmall', 'game_start_timestamp', 'game_starttime'
                    ]
                );

                foreach ($games2 as $k => $v){
                    $gt_sort[] = $v['game_start_timestamp'] ? (int)$v['game_start_timestamp'] : (int)strtotime($v['game_starttime']);
                }

                $gameIdArr = array_merge($gameIdArr, $kn_game_ids);
            }

            //判断赛事是否有竞猜
            if($gameIdArr = array_unique($gameIdArr)){
                $ginfo = M('GameFbinfo')->alias('g')
                    ->field('game_id,is_gamble,is_sub,is_show,fsw_exp_away,fsw_exp,fsw_exp_home,fsw_ball,fsw_ball_home,fsw_ball_away')
                    ->join('LEFT JOIN qc_union u ON g.union_id=u.union_id')
                    ->where(['game_id' => ['IN', $gameIdArr]])
                    ->select();
                foreach ($ginfo as $ik => $iv) {
                    $isGamble[$iv['game_id']] = 1;

                    if ($iv['is_gamble'] != 1 || ($iv['is_sub'] > 2 && $iv['is_show'] != 1)) {
                        $isGamble[$iv['game_id']] = 0;
                    }

                    if ($iv['fsw_exp'] == '' ||
                        $iv['fsw_exp_home'] == '' ||
                        $iv['fsw_exp_away'] == '' ||
                        $iv['fsw_ball'] == '' ||
                        $iv['fsw_ball_home'] == '' ||
                        $iv['fsw_ball_away'] == ''
                    ) {
                        $isGamble[$iv['game_id']] = 0;
                    }

                }
            }

            $games2 = array_multisort($gt_sort, SORT_ASC, $games2);
            
            foreach ($games2 as $gk => $gv) {
                unset($gv['_id']);
                //全场赔率处理
                $match_odds_m_asia = $gv['match_odds_m_asia'][3];
                $match_odds_m_bigsmall = $gv['match_odds_m_bigsmall'][3];
                if ($match_odds_m_asia) {
                    $match_odds_m_asia[1] = handCpSpread($match_odds_m_asia[1]);
                    $gv['asia_odds_sb'] = $match_odds_m_asia;
                }

                $match_odds_m_asia[1] = handCpSpread($match_odds_m_asia[1]);
                $gv['home_team_name'] = [$gv['home_team_name'][0]];
                $gv['away_team_name'] = [$gv['away_team_name'][0]];

                if ($match_odds_m_bigsmall) {
                    $match_odds_m_bigsmall[1] = handCpTotal($match_odds_m_bigsmall[1]);
                    $gv['bigsmall_odds_sb'] = $match_odds_m_bigsmall;
                }

                if ($gv['asia_odds_sb_half'])
                    $gv['asia_odds_sb_half'] = explode('|', $gv['asia_odds_sb_half']);

                if ($gv['bigsmall_odds_sb_half'])
                    $gv['bigsmall_odds_sb_half'] = explode('|', $gv['bigsmall_odds_sb_half']);

                $gv['gdate'] = date('m-d', strtotime($gv['gtime']));
                $gv['gtime'] = date('H:i', strtotime($gv['gtime']));
                //亚、欧、析、猜
                $gv['y_url'] = U("/ypOdds@bf", ["game_id" => $gv['game_id'], "sign" => 1]);
                $gv['o_url'] = U('/eur_index@bf', ['game_id' => $gv['game_id']]);
                $gv['x_url'] = U('/dataFenxi@bf', ['game_id' => $gv['game_id']]);
                $gv['c_url'] = $isGamble[$gv['game_id']] ? U('/gambleDetails@bf', ['game_id' => $gv['game_id']]) :'';

                $gameble[$gv['game_id']] = $gv;
            }


            foreach ($knockout_gameIds as $kk => $kv) {
                for ($i = 0; $i < count($kv); $i++) {
                    if($gameble[$kv[$i]]){
                        $knockout_games[$kk][] = $gameble[$kv[$i]];
                    }
                }
            }
        }

        //热门球员
        $now_team_ids = array_keys($teams);
        if ($now_team_ids) {
            if (!$hot_players = S($curSeason . '_home_data_cup_hot_player')) {
                $players = $mService->select(
                    'fb_player',
                    ['now_team_id' => ['$in' => $now_team_ids]],
                    ["player_id", "player_name", 'images', 'image_urls', "value", 'now_team_id']
                );

                $value_sort = array_column($players, 'value');
                array_multisort($value_sort, SORT_DESC, $players);
                $hot_players = array_splice($players, 0, 9);
                S($curSeason . '_home_data_cup_hot_player', $hot_players, 600);
            }
        }

        foreach ($hot_players as $pk => $pv) {
            $hot_players[$pk]['image_urls'] = $this->getLogo($pv, 3);
            $hot_players[$pk]['player_name'] = $pv['player_name'][0];
            foreach ($teams as $tk => $tv) {
                if ($tv['team_id'] == $pv['now_team_id']) {
                    $hot_players[$pk]['team_name'] = $tv['team_name'];
                }
            }
        }

        foreach ($cup_score_types as $stk => $stv) {
            if ($stv[3] == 1) {
                $cur_cup_rank = $stv;
            }
        }

        //同级杯赛
        $mService->index = ['country_id' => 1];
        $cunions = $mService->select(
            'fb_union',
            ['country_id' => $union['country_id']],
            ['union_id', 'union_name']
        );

        //往届冠军
        foreach ($union['season'] as $seak => $seav) {
            $searchArr[] = "statistics.{$seav}.winner";
        }

        $his_winner = $mService->fetchRow('fb_union', ['union_id' => (int)$union_id], $searchArr);
        foreach ($his_winner['statistics'] as $wk => $wv) {
            if ($wv['winner']) {
                $winners[$wk]['team_id'] = $win_team_ids[] = $wv['winner'];
                $winners[$wk]['year'] = $wk;
            }
        }


        /*
         *  世界杯添加默认数据 
         */
        if (75 == $union_id) {
            $wordCupTeamData = array(
                array(
                    'team_id' => 778,
                    'year' => '2002',
                    'team_logo' => '',
                    'team_name' => '巴西'
                ),
                array(
                    'team_id' => 649,
                    'year' => '1998',
                    'team_logo' => '',
                    'team_name' => '法国'
                ),
                array(
                    'team_id' => 778,
                    'year' => '1994',
                    'team_logo' => '',
                    'team_name' => '巴西'
                ),
                array(
                    'team_id' => 650,
                    'year' => '1990',
                    'team_logo' => '',
                    'team_name' => '德国'
                ),
                array(
                    'team_id' => 766,
                    'year' => '1986',
                    'team_logo' => '',
                    'team_name' => '阿根廷'
                ),
                array(
                    'team_id' => 771,
                    'year' => '1982',
                    'team_logo' => '',
                    'team_name' => '意大利'
                )
            );
            $wordCupTeamId = array_column($wordCupTeamData, 'team_id');
            $win_team_ids = array_merge($wordCupTeamId, $win_team_ids);
        }

        if ($win_team_ids) {
            $win_teams = $mService->select(
                'fb_team',
                ['team_id' => ['$in' => $win_team_ids]],
                ["team_id", "team_name", 'img_url', 'images', "team_value", 'image_urls']
            );
        }

        $winTeamData = [];
        foreach ($winners as $wk => $wv) {
            foreach ($win_teams as $wk2 => $wv2) {
                if ($wv2['team_id'] == $wv['team_id']) {
                    $winners[$wk]['team_logo'] = $this->getLogo($wv2, 2);
                    $winners[$wk]['team_name'] = $wv2['team_name'][0];
                }

                $winTeamData[$wv2['team_id']] = $wv2;
            }
        }

        // 世界杯添加默认数据
        if (75 == $union_id) {
            foreach ($wordCupTeamData as $key => $cup) {
                $wordCupTeamData[$key]['team_logo'] = $this->getLogo($winTeamData[$cup['team_id']], 2);
            }
        }

        //世界杯新闻
        if ($union_id == 75) {
            $news = M('PublishList')
                ->field('id,title,content,source,add_time,class_id')
                ->where('status = 1 AND class_id = 96')
                ->order('add_time desc')
                ->limit(6)
                ->select();

            $classArr = getPublishClass(0);
            foreach ($news as $k => $v) {
                $news[$k]['href'] = newsUrl($v['id'], $v['add_time'], $v['class_id'], $classArr);
            }

            $winners = array_merge($winners, $wordCupTeamData);
        }

        $winners = array_slice($winners, 0, 9);


        $kn_gameid_sort = array_column($knockout_games, 'gtime');
        array_multisort($kn_gameid_sort, SORT_DESC, $knockout_games);

        //判断数据模块，没有数据的显示默认图
        $hasData = $matchResult ? 1 : 0;


        //seo设置
        $un = $union['union_name'];

        $seo = [
            'seo_title' => "{$curSeason}赛季_{$un}_{$un}赛程_{$un}联赛_{$un}积分榜_全球体育",
            'seo_keys' => "{$un}赛程,{$un}联赛,{$un}积分榜,{$un}射手榜,{$un}助攻榜",
            'seo_desc' => "全球体育为您提供赛季{$un}赛程、{$un}积分榜、{$un}助攻榜、{$un}射手榜等{$un}联赛数据统计，同时还有更详细的{$un}级联赛赛果、让分盘、大小球盘、半全场胜负统计、标准亚盘对照等统计信息。"
        ];
        $this->setSeo($seo);

        $nav = D('Home')->getNavList(40);
        $hot_league = $this->hot_league();
        $this->assign('nav', $nav);
        $this->assign('hot_league', $hot_league);
        $this->assign('hot_teams', array_slice($t_teams, 0, 9));
        $this->assign('hot_players', $hot_players);
        $this->assign('curSeason', $curSeason);
        $this->assign('union', $union);
        $this->assign('cunions', $cunions);
        $this->assign('statistics', $statistics);//赛程统计
        $this->assign('statistics_count', ceil(count($statistics) / 6));
        $this->assign('cup_score_types', $cup_score_types);//赛程积分筛选项
        $this->assign('cur_cup_rank', $cur_cup_rank);
        $this->assign('score_rank', $score_rank);//赛程积分-小组
        $this->assign('knockout_games', $knockout_games);//淘汰赛事
        $this->assign('teams', $teams);//球队信息
        $this->assign('winners', $winners);
        $this->assign('news', $news);
        $this->assign('hasData', $hasData);
        $this->display();
    }

    /**
     * 热门赛事
     * @return mixed
     */
    public function hot_league()
    {
        //热门赛事
        if (!$hot_league = S('home_data_hot_league')) {
            $hot_league = M('HotLeague')
                ->field('id,union_id,name,logo')
                ->where(['status' => 1])
                ->order('sort asc')
                ->select();

            if ($hot_league) {
                foreach ($hot_league as $hk => $hv) {
                    if ($hv['logo'] == '') {
                        unset($hot_league[$hk]);
                    } else {
                        $hot_league[$hk]['logo'] = Tool::imagesReplace($hv['logo']);
                    }
                    $union_ids[] = (int)$hv['union_id'];
                }

                $mService = mongoService();
                $mService->index = ['union_id' => 1];
                $unions = $mService->select(
                    'fb_union',
                    ['union_id' => ['$in' => $union_ids]],
                    ['union_id', 'union_or_cup', 'union_level', 'is_league', 'level']
                );

                //拼接地址（杯赛地址、一级联赛地址、二级联赛地址）
                foreach ($hot_league as $hk2 => $hv2) {
                    foreach ($unions as $uk => $uv) {
                        if ($hv2['union_id'] == $uv['union_id']) {
                            $isLeague = isset($uv['union_or_cup']) ? $uv['union_or_cup'] : $uv['is_league'];
                            if ($isLeague == 1) {//联赛
                                $hot_league[$hk2]['jump_url'] = U('/league/' . $uv['union_id'] . '@data', '', 'html');
                            } else {
                                $hot_league[$hk2]['jump_url'] = U('/cupMatch/' . $uv['union_id'] . '@data', '', 'html');
                            }
                        }
                    }
                }
            }

            S('home_data_hot_league', $hot_league, 600);
        }

        return $hot_league;
    }


    /**
     * 联赛-赛程
     */
    public function league_statistics()
    {
        $union_id = I('union_id');
        $season = I('season');
        $round = I('round', 1);
        $subLeagueId = (string)I('subLeagueId');
        $mService = mongoService();

        if (!$union_id)
            return;

        if ($subLeagueId) {
            $subLeague = $mService->fetchRow('fb_union',
                ['union_id' => (int)$union_id],
                [
                    "statistics.{$season}.matchResult.arrSubLeague",
                    "statistics.{$season}.matchResult.{$subLeagueId}.jh",
                ]
            );

            //是否是淘汰赛
            $arrSubLeague = $subLeague['statistics'][$season]['matchResult']['arrSubLeague'];
            foreach ($arrSubLeague as $sk => $sv) {
                if ($subLeagueId == $sv[0]) {
                    $isTaotai = $sv[7];
                }
            }

            $jh = $subLeague['statistics'][$season]['matchResult'][$subLeagueId]['jh'];

            if ($isTaotai == 1) {
                foreach ($jh['R_' . $round] as $dk => $dv) {
                    $gameIdArr[] = $dv[4];
                    $gameIdArr[] = $dv[5];
                }
            } else {
                $gameIdArr = $jh['R_' . $round];
            }

        } else {
            $mService->index = ['union_id' => 1];
            $tempjh = $mService->fetchRow('fb_union',
                ['union_id' => (int)$union_id],
                [
                    "statistics.{$season}.matchResult.jh",
                ]
            );
            $jh = $tempjh['statistics'][$season]['matchResult']['jh'];
            $gameIdArr = $jh['R_' . $round];

        }

        if ($gameIdArr) {
            $mService->index = ['game_id' => 1];
            $games = $mService->select(
                'fb_game',
                ['game_id' => ['$in' => $gameIdArr]],
                ['game_id', 'gtime', 'home_team_name', 'away_team_name', 'score',
                    'half_score', 'worldcup_num', 'bigsmall_odds_sb_half', 'asia_odds_sb_half', "home_team_id",
                    "away_team_id", 'match_odds_m_asia', 'match_odds_m_bigsmall','game_state'
                ],
                ['gtime' => 1]
            );
        }

        //是否有竞猜
        $ginfo = M('GameFbinfo')->alias('g')
            ->field('game_id,is_gamble,is_sub,is_show,fsw_exp_away,fsw_exp,fsw_exp_home,fsw_ball,fsw_ball_home,fsw_ball_away')
            ->join('LEFT JOIN qc_union u ON g.union_id=u.union_id')
            ->where(['game_id' => ['IN', $gameIdArr]])
            ->select();

        foreach ($ginfo as $ik => $iv) {
            $gamble[$iv['game_id']] = 1;
            if ($iv['is_gamble'] != 1 || ($iv['is_sub'] > 2 && $iv['is_show'] != 1)) {
                $gamble[$iv['game_id']] = 0;
            }

            if ($iv['fsw_exp'] == '' ||
                $iv['fsw_exp_home'] == '' ||
                $iv['fsw_exp_away'] == '' ||
                $iv['fsw_ball'] == '' ||
                $iv['fsw_ball_home'] == '' ||
                $iv['fsw_ball_away'] == ''
            ) {
                $gamble[$iv['game_id']] = 0;
            }
        }

        foreach ($games as $gk => $gv) {
            unset($games[$gk]['_id']);
//            if(intval($gv['game_state']) !== 0){
//                $games[$gk]['half_score'] = $gv['half_score'] ?: '-';
//                if (!$gv['score']) {
//                    $games[$gk]['score'] = $gv['half_score'] ? $gv['half_score'] : '-';
//                } else {
//                    $games[$gk]['score'] = $gv['score'] ?: '-';
//                }
//            }else{
//                $games[$gk]['score'] = '-';
//                $games[$gk]['half_score'] = '-';
//            }

//            if($gv['game_id'] == 1509884){
//                var_dump($gv);exit;
//            }

            $games[$gk]['half_score'] = intval($gv['game_state']) !== 0 ? $gv['half_score'] : '-';
            $games[$gk]['score'] = intval($gv['game_state']) !== 0 ? $gv['score'] : '-';

            $score = explode('-', $games[$gk]['score']);

            $games[$gk]['home_team_name'] = $gv['home_team_name'][0];
            $games[$gk]['away_team_name'] = $gv['away_team_name'][0];
            $games[$gk]['gamble'] = $gamble[$gv['game_id']];
            $gt = strtotime($gv['gtime']);
            $games[$gk]['gdate'] = date('m-d', $gt);
            $games[$gk]['gtime'] = date('H:i', $gt);
            $games[$gk]['hscore'] = (int)$score[0];
            $games[$gk]['ascore'] = (int)$score[1];
            //亚、欧、析、猜
            $games[$gk]['y_url'] = U("/ypOdds@bf", ["game_id" => $gv['game_id'], "sign" => 1]);
            $games[$gk]['o_url'] = U('/eur_index@bf', ['game_id' => $gv['game_id']]);
            $games[$gk]['x_url'] = U('/dataFenxi@bf', ['game_id' => $gv['game_id']]);
            $games[$gk]['c_url'] = U('/gambleDetails@bf', ['game_id' => $gv['game_id']]);
            //全场让球、大小赔率处理, 之前的'asia_odds_sb', 'bigsmall_odds_sb',换成match_odds_m
            $match_odds_m_asia = $gv['match_odds_m_asia'][3];
            $match_odds_m_bigsmall = $gv['match_odds_m_bigsmall'][3];
            if ($match_odds_m_asia) {
                $match_odds_m_asia[1] = handCpSpread($match_odds_m_asia[1]);
                $games[$gk]['asia_odds_sb'] = implode('|', $match_odds_m_asia);
            }

            if ($match_odds_m_bigsmall) {
                $match_odds_m_bigsmall[1] = handCpTotal($match_odds_m_bigsmall[1]);
                $games[$gk]['bigsmall_odds_sb'] = implode('|', $match_odds_m_bigsmall);
            }

            if ($gv['asia_odds_sb_half']){
                $asia_half = explode('|', $gv['asia_odds_sb_half']);
                preg_match('/./u', $asia_half[1], $match);



                if(strlen($match[0]) < 3){
                    $asia_half[1] = handCpSpread($asia_half[1]);
                    $games[$gk]['asia_odds_sb_half'] = implode('|', $asia_half);
                }
            }

//            if ($gv['bigsmall_odds_sb_half']){
//                $bigsmall_half = explode('|', $gv['bigsmall_odds_sb_half']);
//
//                preg_match('/./u', $bigsmall_half[1],$match);
//                if(strlen($match[0]) < 3){
//                    $bigsmall_half[1] = handCpSpread($bigsmall_half[1]);
//                    $games[$gk]['bigsmall_odds_sb_half'] = implode('|', $bigsmall_half);
//                }
//            }

            $teams[$gv['home_team_id']] = ['team_id' => $gv['home_team_id'], 'team_name' => $gv['home_team_name'][0]];
            $teams[$gv['away_team_id']] = ['team_id' => $gv['away_team_id'], 'team_name' => $gv['away_team_name'][0]];

        }

        $this->ajaxReturn(['status' => 1, 'list' => $games, 'teams' => $teams]);
    }

    /**
     * 联赛-积分榜
     */
    public function league_score_rank()
    {
        $union_id = I('union_id');
        $season = I('season');
        $rank_type = I('data_type') ?: 'total_score';
        $subId = I('subId');
        $mService = mongoService();

        $mService->index = ['union_id' => 1];

        //是否有二级联赛
        if (!$subId) {
            $tempjh = $mService->fetchRow('fb_union',
                ['union_id' => (int)$union_id],
                ["statistics.{$season}.matchResult." . $rank_type]
            );

            //近6轮胜负
            $total_score = $mService->fetchRow('fb_union',
                ['union_id' => (int)$union_id],
                [
                    "statistics.{$season}.matchResult.total_score",
                    "statistics.{$season}.matchResult.score_color",
                ]
            );
            $score_rank = $tempjh['statistics'][$season]['matchResult'][$rank_type];
            $tscore = $total_score['statistics'][$season]['matchResult']['total_score'];
            $scoreColor = $total_score['statistics'][$season]['matchResult']['score_color'];

        } else {//有二级联赛

            $tempjh = $mService->fetchRow('fb_union',
                ['union_id' => (int)$union_id],
                ["statistics.{$season}.matchResult.{$subId}.{$rank_type}"]
            );

            //近6轮胜负
            $total_score = $mService->fetchRow('fb_union',
                ['union_id' => (int)$union_id],
                [
                    "statistics.{$season}.matchResult.{$subId}.total_score",
                    "statistics.{$season}.matchResult.{$subId}.score_color",
                ]
            );

            $score_rank = $tempjh['statistics'][$season]['matchResult'][$subId][$rank_type];
            $tscore = $total_score['statistics'][$season]['matchResult'][$subId]['total_score'];
            $scoreColor = $total_score['statistics'][$season]['matchResult'][$subId]['score_color'];
        }

        if ($score_rank) {
            foreach ($score_rank as $tsK => $tsV) {
                $team_ids[] = $rank_type == 'total_score' ? $tsV[2] : $tsV[1];
            }

            $mService->index = ['team_id' => 1];
            $teamArrs = $mService->select(
                'fb_team',
                ['team_id' => ['$in' => $team_ids]],
                ['team_id', 'team_name', 'img_url', 'images']
            );

            $teams = [];
            foreach ($teamArrs as $tmK => $tmV) {
                unset($teamArrs[$tmK]['_id']);
                $teams[$tmV['team_id']]['team_id'] = $tmV['team_id'];
                $teams[$tmV['team_id']]['team_name'] = $tmV['team_name'][0];
                $teams[$tmV['team_id']]['img_url'] = $this->getLogo($tmV, 2);
                $teams[$tmV['team_id']]['url'] = U('/team/' . $tmV['team_id'] . '@data');
            }

            foreach ($tscore as $k => $v) {
                $gid = $rank_type == 'total_score' ? $v[2] : $v[1];
                $gamble[$gid] = [$v[19], $v[20], $v[21], $v[22], $v[23], $v[24]];
            }

            foreach ($score_rank as $tsK2 => $tsV2) {
                if ($rank_type == 'total_score') {
                    $score_rank[$tsK2][2] = $teams[$tsV2[2]];
                } else {
                    $score_rank[$tsK2][1] = $teams[$tsV2[1]];
                }
                $score_rank[$tsK2]['gamble'] = $gamble[$tsV2[2]];

            }
        }

        $this->ajaxReturn(['status' => 1, 'list' => $score_rank, 'scoreColor' => $scoreColor]);
    }

    /**
     * 数据统计：让球盘路、大小盘路、射手榜、积分榜、助攻榜、入球总数/单双、半全场胜负、上下半场入球
     */
    public function tech_statistics()
    {
        $union_id = I('union_id');
        $season = I('season');

        if (!$union_id || !$season)
            $this->ajaxReturn(['status' => 0, 'msg' => '参数错误']);

        if (I('mulSelect') == 1) {
            $data_type = explode(',', I('data_type'));

            $mService = mongoService();
            $mService->index = ['union_id' => 1];
            foreach ($data_type as $dk => $dv) {
                $data = $mService->fetchRow('fb_union', ['union_id' => (int)$union_id], ["statistics.{$season}.{$dv}"]);
                $list[$dv] = $data['statistics'][$season][$dv];

                if ($dv == 'firGetLose') {
                    foreach ($list[$dv]['arr_data'] as $k => $v) {
                        $teamIds[] = $v[0];
                    }

                    foreach ($list[$dv]['arr_result_data'] as $k => $v) {
                        $teamIds[] = $v[0];
                    }
                } elseif ($dv == 'nogetlose') {
                    foreach ($list[$dv] as $k => $v) {
                        $teamIds[] = $v[0];
                    }
                }
            }

        } else {
            $data_type = I('data_type');

            $mService = mongoService();
            $mService->index = ['union_id' => 1];
            $tempSinDou = $mService->fetchRow('fb_union', ['union_id' => (int)$union_id], ["statistics.{$season}.{$data_type}"]);

            $list = $tempSinDou['statistics'][$season][$data_type];

            if ($data_type == 'letGoal') {
                foreach ($list['total_pan_lou'] as $k => $v) {
                    $teamIds[] = $v[1];
                }
            }

            if ($data_type == 'bigSmall') {
                foreach ($list['TotalBs'] as $k => $v) {
                    $teamIds[] = $v[1];
                }
            }

        }

        //球队
        if ($teamIds) {
            $teamArrs = $mService->select(
                'fb_team',
                ['team_id' => ['$in' => $teamIds]],
                ['team_id', 'team_name', 'img_url', 'images']
            );
        }

        $teams = [];
        foreach ($teamArrs as $tmK => $tmV) {
            unset($teamArrs[$tmK]['_id']);
            $teams[$tmV['team_id']]['team_name'] = $tmV['team_name'][0];
            $teams[$tmV['team_id']]['img_url'] = $this->getLogo($tmV, 2);
            $teams[$tmV['team_id']]['url'] = U('/team/' . $tmV['team_id'] . '@data');
        }

        $this->ajaxReturn(['status' => 1, 'list' => $list, 'teams' => $teams]);
    }

    /**
     * 助攻榜,射手榜列表
     */
    public function goals_rank()
    {
        $union_id = I('union_id');
        $season = I('season');
        $page = I('p') < 1 ? 0 : (I('p') - 1);
        $page_type = (int)I('pt');

        if (!$union_id)
            $this->ajaxReturn(['status' => 0, 'msg' => '参数错误']);

        $mService = mongoService();

        if (!$season) {
            $union1 = $mService->fetchRow(
                'fb_union',
                ['union_id' => (int)$union_id],
                ['season']);

            $season = I('season') ? I('season') : $union1['season'][0];
        }


        $mService->index = ['union_id' => 1];
        $tem_archer = $mService->fetchRow('fb_union',
            ['union_id' => (int)$union_id],
            ["statistics.{$season}.Archer", "statistics.{$season}.player_tech"]
        );

        $player_tech = $tem_archer['statistics'][$season]['player_tech'];

        if ($tem_archer['statistics'][$season]['Archer'] && $tem_archer['statistics'][$season]['Archer']['team_data']) {
            $td = $tem_archer['statistics'][$season]['Archer']['total_data'];
            foreach ($td as $tk1 => $tv2) {
                $team_ids [] = $tv2[8];
            }

            if ($team_ids) {
                $team = $mService->select(
                    'fb_team',
                    ['team_id' => ['$in' => $team_ids]],
                    ["team_id", "team_name"]
                );

                foreach ($team as $uk => $uv) {
                    unset($uv['_id']);
                    $teams[$uv['team_id']]['team_name'] = $uv['team_name'][0];
                }

                foreach ($td as $tk => $tv) {
                    $goal_rank[$tk]['t1'] = $tv[1];//球员ID
                    $goal_rank[$tk]['t2'] = $tv[2];//名称
                    $goal_rank[$tk]['t3'] = $tv[5];//国家
                    $goal_rank[$tk]['t4'] = [$tv[8], $teams[$tv[8]]['team_name']];//球队ID
                    $goal_rank[$tk]['t5'] = $tv[9];//主
                    $goal_rank[$tk]['t6'] = $tv[10];//客
                    $goal_rank[$tk]['t7'] = $tv[11];//进球
                    $goal_rank[$tk]['t8'] = $tv[12];//点球
                    $goal_rank[$tk]['rank'] = $tk + 1;
                }

                $goal_rank_c = count($goal_rank);
                if ($page_type == 1) {
                    $goal_rank = array_slice($goal_rank, $page * 30, 30);
                } else {
                    $goal_rank = array_slice($goal_rank, 0, 30);
                }
            }

            $type = 0;
        } else {//从player_tech获取射手榜
            $type = 1;
            foreach ($player_tech['Pid'] as $pk => $pv) {
                $team_ids[] = $pv[1];
                $player_ids[] = $pk;
            }

            $tId = array_values(array_unique($team_ids));

            if ($tId) {
                $mService->index = ['team_id' => 1];
                $team = $mService->select(
                    'fb_team',
                    ['team_id' => ['$in' => $tId]],
                    ["team_id", "team_name", 'images', 'image_urls']
                );

                if ($player_ids) {
                    $mService->index = ['player_id' => 1];
                    $player = $mService->select(
                        'fb_player',
                        ['player_id' => ['$in' => $player_ids]],
                        ["player_id", "country", "player_name"]
                    );
                }

                foreach ($player as $pk => $pv) {
                    $players[$pv['player_id']] = $pv;
                }

                foreach ($team as $uk => $uv) {
                    unset($uv['_id']);
                    $teams[$uv['team_id']] = $uv;
                    $teams[$uv['team_id']]['team_name'] = $uv['team_name'][0];
                }

                //总进球
                foreach ($player_tech['Total']['value'] as $tk => $tv) {
                    $tid = $player_tech['Pid'][$tv[0]][1];
                    $goal_rank[$tv[0]]['t1'] = $tv[0];//球员ID
                    $goal_rank[$tv[0]]['t2'] = $player_tech['Pid'][$tv[0]][0][0];//名称
                    $goal_rank[$tv[0]]['t3'] = $players[$tv[0]]['country'][0];//国家
                    $goal_rank[$tv[0]]['t4'] = [$tid, $teams[$tid]['team_name']];//球队ID
                    $goal_rank[$tv[0]]['t7'] = $tv[4] + $tv[5];//总进球
                    $goal_rank[$tv[0]]['t8'] = $tv[5];//总点球
                    $goal_rank[$tv[0]]['t9'] = $tv[10];//评分
                    $goal_rank[$tv[0]]['t10'] = $tv[44];//进球转化率
                    $goal_rank[$tv[0]]['t11'] = $tv[15];//助攻
                    $goal_rank[$tv[0]]['t12'] = $tv[41];//传球成功率
                    $goal_rank[$tv[0]]['t13'] = $tv[1];//出场
                    $goal_rank[$tv[0]]['t14'] = $tv[2];//替补
                    $goal_rank[$tv[0]]['t17'] = $tv[6];//射门
                    $goal_rank[$tv[0]]['t18'] = $tv[7];//射正
                    $goal_rank[$tv[0]]['t19'] = $tv[12];//传球
                    $goal_rank[$tv[0]]['t20'] = $tv[14];//关键传球

                    $goal_sort[] = $tv[4] + $tv[5];
                    $pass_sort[] = $tv[15];
                    $score_sort[] = (int)$tv[10];
                    $score_sort2[] = (int)$tv[10];
                }

                foreach ($player_tech['Home']['value'] as $hk => $hv) {
                    $goal_rank[$hv[0]]['t5'] = $hv[4] + $hv[5];//主进球
                    $goal_rank[$hv[0]]['t15'] = $hv[15];//主助攻
                }

                foreach ($player_tech['guest']['value'] as $hk => $hv) {
                    $goal_rank[$hv[0]]['t6'] = $hv[4] + $hv[5]; //客进球
                    $goal_rank[$hv[0]]['t16'] = $hv[15];//客助攻
                }
                $_pass_rank = $goal_rank;

                //助攻榜
                array_multisort($pass_sort, SORT_DESC, $score_sort2, SORT_DESC, $_pass_rank);


                //射手榜排序
                array_multisort($goal_sort, SORT_DESC, $score_sort, SORT_DESC, $goal_rank);


                $i = 1;
                foreach ($goal_rank as $gkk => $gvv) {
                    $goal_rank[$gkk]['rank'] = $i++;
                }

                $goal_rank_c = count($goal_rank);
                if ($page_type == 1) {
                    $goal_rank = array_slice($goal_rank, $page * 30, 30);
                } else {
                    $goal_rank = array_slice($goal_rank, 0, 30);
                }


                $ii = 1;
                foreach ($_pass_rank as $skk => $svv) {
                    $_pass_rank[$skk]['rank'] = $ii++;
                }

                $pass_rank_c = count($_pass_rank);
                if ($page_type == 2) {
                    $pass_rank = array_slice($_pass_rank, $page * 30, 30);
                } else {
                    $pass_rank = array_slice($_pass_rank, 0, 30);
                }
            }


        }

        //球队页面的数据需要头像
        if (I('s') == 'team') {
            $team_player_ids[] = array_unique(array_merge(array_column($goal_rank, 't1'), array_column($pass_rank, 't1')));

            $mService->index = ['player_id' => 1];
            $fb_player = $mService->select(
                'fb_player',
                ['player_id' => ['$in' => $player_ids]],
                ['player_id', 'birthday', 'position', 'images', 'image_urls', 'now_team_id']);

            foreach ($fb_player as $fbk => $fbv) {
                $team_fb_player[$fbv['player_id']]['player_id'] = $fbv['player_id'];
                $team_fb_player[$fbv['player_id']]['age'] = date('Y') - date('Y', strtotime($fbv['birthday']));
                $team_fb_player[$fbv['player_id']]['position'] = $fbv['position'][0];
                $team_fb_player[$fbv['player_id']]['logo'] = $this->getLogo($fbv, 3);
                $team_fb_player[$fbv['player_id']]['team_logo'] = $this->getLogo($teams[$fbv['now_team_id']], 2);
                $team_fb_player[$fbv['player_id']]['team_name'] = $teams[$fbv['now_team_id']]['team_name'];
            }
        }

        $this->ajaxReturn([
            'status' => 1,
            'goal_rank' => $goal_rank,
            'pass_rank' => $pass_rank,
            'type' => $type,
            'pass_rank_c' => $pass_rank_c,
            'goal_rank_c' => $goal_rank_c,
            'team_fb_player' => $team_fb_player
        ]);
    }

    /**
     * 球队页
     */
    public function team()
    {
        $team_id = I('team_id');
        //球队信息
        $mService = mongoService();
        $mService->index = ['team_id' => 1];
        $Info = $mService->fetchRow(
            'fb_team',
            ['team_id' => (int)$team_id],
            ['team_id', 'team_name', 'team_intro',
                'images', 'image_urls', 'img_url', 'team_value',
                'coach', 'character', 'url', 'country',
                'formed', 'stadium_name', 'team_value',
                'formed', 'character', 'honor', 'lineup',
                'count_sum', 'team_count', 'union_id',
                'cup_data', 'player_data', 'team_id_tzuqiu_cc',
                'team_tran', 'schedule'
            ]);

        // 查询不到球队404
        if(!$Info){
            parent::_empty();
        }

        //球队特点
        foreach ($Info['character'] as $ck => $cv) {
            $cv[2] = explode('^', $cv[2])[0];
            $teamInfo['character'][$cv[0]][] = $cv;
        }
        $teamInfo['character'][1] = array_chunk($teamInfo['character'][1], 2);
        $teamInfo['character'][2] = array_chunk($teamInfo['character'][2], 2);
        $teamInfo['character'][3] = array_chunk($teamInfo['character'][3], 2);

        //球队荣耀
        foreach ($Info['honor'] as $hk => $hv) {
            $union_id[] = (int)$hv[2];
            $hn = explode('^', $hv[0])[0];
            $c = count(explode(',', $hv[1]));
            $teamInfo['honor'][] = [$hn, $hv[1], $hv[2], $c];
        }


        foreach ($Info['player_data'] as $pk => $pv) {
            $player_ids[] = (int)$pv[1];
        }

        foreach ($Info['team_tran'] as $ttk => $ttv) {
            foreach ($ttv as $tk2 => $tv2) {
                $player_ids[] = (int)$tv2[1];
                $team_ids[] = (int)$tv2[3];
            }
        }

        if ($player_ids) {
            $mService->index = ['player_id' => 1];
            $pls = $mService->select(
                'fb_player',
                ['player_id' => ['$in' => $player_ids]],
                ['player_id', 'player_name', 'country']);

            //球员统计
            foreach ($pls as $pk1 => $pv1) {
                $players[$pv1['player_id']] = $pv1;
            }
        }

        if ($team_ids) {
            $tes = $mService->select(
                'fb_team',
                ['team_id' => ['$in' => $team_ids]],
                ['team_id', 'team_name', 'country']);
            foreach ($tes as $tk1 => $tv1) {
                $teamData[$tv1['team_id']] = [(string)$tv1['team_id'], $tv1['team_name'][0]];
            }
        }
        foreach ($Info['player_data'] as $pk => $pv) {
            $Info['player_data'][$pk][1] = [$pv[1], $players[$pv[1]]['player_name'][0], $players[$pv[1]]['country'][0]];
        }

        //球员转会
        foreach ($Info['team_tran'] as $ttk1 => $ttv1) {
            foreach ($ttv1 as $tk => $tv) {
                $Info['team_tran'][$ttk1][$tk][1] = [$tv[1], $players[$tv[1]]['player_name'][0], $players[$tv[1]]['country'][0]];
                $Info['team_tran'][$ttk1][$tk][3] = $teamData[(int)$Info['team_tran'][$ttk1][$tk][3]];
            }
        }

        // 过滤查询不到球员
        foreach ($Info['team_tran'] as $ttk2 => $ttv2) {
            foreach ($ttv2 as $tk2 => $tv2) {
                if ($Info['team_tran'][$ttk2][$tk2][2] != '主教练') {
                    if ($Info['team_tran'][$ttk2][$tk2][1][1]) {
                        $_Info['team_tran'][$ttk2][] = $Info['team_tran'][$ttk2][$tk2];
                    }
                } else {
                    $_Info['team_tran'][$ttk2][] = $Info['team_tran'][$ttk2][$tk2];
                }
            }
        }
        $Info['team_tran'] = $_Info['team_tran'];
        //查询排名
        $union = $mService->fetchRow(
            'fb_union',
            ['union_id' => (int)$Info['union_id']],
            ['season']);

        $season = I('season') ? I('season') : $union['season'][0];
        $total_score = $mService->fetchRow('fb_union',
            ['union_id' => (int)$Info['union_id']],
            ["statistics.{$season}.matchResult.total_score"]
        );

        $slist = $alist = $total_score['statistics'][$season]['matchResult']['total_score'];
        foreach ($slist as $sk => $sv) {
            if ($sv[2] == $Info['team_id']) {
                $team_rank = $sv[1];
                $recently = [$sv[20], $sv[21], $sv[22], $sv[23], $sv[24]];
            }
            $index[] = $sk;
            $get_goal_sort[] = $sv[8];
            $lost_goal_sort[] = $sv[9];

        }

        array_multisort($lost_goal_sort, SORT_DESC, $slist);

        //球队能力值计算
        foreach ($Info['count_sum'] as $csk => $csv) {
            if ($csv[0] == 0) {
                $ability_value['score'] = ['评分', $csv[24] * 10, 'progBg-red'];//评分
                $ability_value['tech'] = ['技术', $csv[13] * 100, 'progBg-blue'];//技术
            }
            $sl = $csv[2] / ($csv[2] + $csv[3] + $csv[4]);
            $Info['count_sum'][$csk]['sl'] = round($sl, 3) * 100;
        }

        foreach ($slist as $sk => $sv) {
            if ($sv[2] == $Info['team_id']) {
                $ability_value['guard'] = ['防守', 100 - ($sk + 1) - ($sv[15] * 10), 'progBg-gray'];//防守值
            }
        }

        array_multisort($get_goal_sort, SORT_DESC, $index, SORT_ASC, $alist);

        foreach ($alist as $sk => $sv) {
            if ($sv[2] == $Info['team_id']) {
                $ability_value['attack'] = ['进攻', 60 - ($sk + 1) + ($sv[14] * 10), 'progBg-green'];//进攻值
            }
        }

        //胜：20分、平：10分、输：0分
        $astatus = 0;
        foreach ($recently as $rk => $rv) {//0赢，1走，2负
            if ($rv == 0)
                $astatus += 20;
            elseif ($rv == 1)
                $astatus += 10;
        }
        $ability_value['status'] = ['状态', $astatus, 'progBg-yellow'];//状态值

        //球队赛程
        foreach ($Info['schedule'] as $sck => $scv) {
            $schedule_taem_ids[] = $scv[4];
            $schedule_taem_ids[] = $scv[5];
        }

        if ($schedule_taem_ids = array_values(array_unique($schedule_taem_ids))) {
            $_schedule_taems = $mService->select(
                'fb_team',
                ['team_id' => ['$in' => $schedule_taem_ids]],
                ["team_id", "team_name", "image_urls", "images"]
            );
        }

        foreach ($_schedule_taems as $stk => $stv) {
            $schedule_taems[$stv['team_id']]['team_id'] = $stv['team_id'];
            $schedule_taems[$stv['team_id']]['logo'] = $this->getLogo($stv, 2);
            $schedule_taems[$stv['team_id']]['team_name'] = $stv['team_name'][0];
        }

        foreach ($Info['schedule'] as $sck => $scv) {
            $Info['schedule'][$sck][4] = $schedule_taems[$scv[4]];
            $Info['schedule'][$sck][5] = $schedule_taems[$scv[5]];
            //亚、欧、析、猜
            $Info['schedule'][$sck]['y_url'] = U("/ypOdds@bf", ["game_id" => $scv[0], "sign" => 1]);
            $Info['schedule'][$sck]['o_url'] = U('/eur_index@bf', ['game_id' => $scv[0]]);
            $Info['schedule'][$sck]['x_url'] = U('/dataFenxi@bf', ['game_id' => $scv[0]]);
            $Info['schedule'][$sck]['c_url'] = U('/gambleDetails@bf', ['game_id' => $scv[0]]);
            $Info['schedule'][$sck]['d_url'] = U('/ypOdds@bf', ['game_id' => $scv[0], 'sign' => 2]);
        }

        $tn = $Info['team_name'][0];
        $seo = [
            'seo_title' => "{$tn}赛程_{$tn}球队_{$tn}阵容_全球体育",
            'seo_keys' => "{$tn}赛程,{$tn}球队,{$tn}阵容,{$tn}球员,{$tn}数据统计,{$tn}介绍,{$tn}荣耀",
            'seo_desc' => "全球体育为您提供{$tn}足球最齐全的资料，这里有{$tn}赛程、{$tn}球队特点、{$tn}阵容、{$tn}球员名单、{$tn}球员转会、{$tn}数据统计等相关数据，全方位追踪{$tn}球队的信息资料，让您全面掌握{$tn}的最新动态。"
        ];
        $this->setSeo($seo);


        foreach ($Info['team_count'] as $k => $v) {
            $Info['team_count'][$k][19] = $v[17] > 0 ? round($v[18] / $v[17], 2) * 100 : 0;
        }


        $teamInfo['team_id'] = $Info['team_id'];
        $teamInfo['union_id'] = $Info['union_id'];
        $teamInfo['formed'] = $Info['formed'];
        $teamInfo['team_value'] = $Info['team_value'];
        $teamInfo['team_name'] = $Info['team_name'][0];
        $teamInfo['team_intro'] = $Info['team_intro'];
        $teamInfo['cup_data'] = $Info['cup_data'];
        $teamInfo['player_data'] = $Info['player_data'];
        $teamInfo['team_tran'] = $Info['team_tran'];
        $teamInfo['schedule'] = array_slice($Info['schedule'], 0, 100);
        $teamInfo['team_rank'] = $team_rank ?: '';
        $teamInfo['country'] = $Info['country'][0];
        $teamInfo['stadium_name'] = $Info['stadium_name'][0];
        $teamInfo['coach'] = $Info['coach'][0][2];
        $teamInfo['lineup'] = $Info['lineup'];//阵容
        $teamInfo['count_sum'] = $Info['count_sum'];//球队数据
        $teamInfo['team_count'] = $Info['team_count'];
        $teamInfo['ability_value'] = $ability_value;
        $teamInfo['logo'] = $this->getLogo($Info, 2);
        $teamInfo['url'] = $Info['url'];

        //当前联赛的所有球队
        if ($Info['union_id']) {
            $teamInfo['team_groups'] = $mService->select(
                'fb_team',
                ['union_id' => $Info['union_id']],
                ['team_id', 'team_name']);
        }

        $nav = D('Home')->getNavList(40);
        $hot_league = $this->hot_league();

        $hasData = 1;
        if(!$teamInfo['team_name'] || !$teamInfo['schedule']){
            $hasData = 0;
        }

        $this->assign('hasData', $hasData);
        $this->assign('hot_league', $hot_league);
        $this->assign('nav', $nav);
        $this->assign('teamInfo', $teamInfo);
        $this->display();
    }

    /**
     * 球员页
     */
    public function player()
    {
        $player_id = I('player_id');
        
        //球员信息
        $mService = mongoService();
        $Info = $mService->fetchRow(
            'fb_player',
            ['player_id' => (int)$player_id],
            ['player_id', 'player_name', 'player_intro',
                'images', 'image_urls', 'value', 'country', 'player_honor', 'now_team', 'country',
                'birthday', 'weight', 'height', 'position', 'now_team_id',
                'player_character', 'contract_due_date', 'league_performance', 'mv_data', 'hexagon'
            ]);


        // 查询不到球员404
        if(!$Info){
            parent::_empty();
        }
        $vcolor = ['progBg-red', 'progBg-gray', 'progBg-green', 'progBg-yellow', 'progBg-yellow'];
        $i = 0;
        foreach ($Info['hexagon'] as $hk => $hv) {
            $c = $vcolor[$i] ? $vcolor[$i] : $vcolor[2];
            $hexagon[] = [$hk, $hv, $c];
            $i++;
        }

        $playerInfo['player_id'] = $Info['player_id'];
        $playerInfo['player_name'] = $Info['player_name'];
        $playerInfo['player_intro'] = $Info['player_intro'];
        $playerInfo['contract_due_date'] = $Info['contract_due_date'];
        $playerInfo['league_performance'] = $Info['league_performance'];
        $playerInfo['mv_data'] = $Info['mv_data'];
        $playerInfo['now_team'] = $Info['now_team'][0][0];
        $playerInfo['player_number'] = $Info['now_team'][0][1];
        $playerInfo['country'] = $Info['country'][0];
        $playerInfo['value'] = $Info['value'];
        $playerInfo['birthday'] = $Info['birthday'];
        $playerInfo['weight'] = $Info['weight'];
        $playerInfo['height'] = $Info['height'];
        $playerInfo['position'] = $Info['position'][0];
        $playerInfo['player_honor'] = $Info['player_honor'];
        $playerInfo['hexagon'] = $hexagon;
        $playerInfo['logo'] = $this->getLogo($Info, 3);

        //球队荣耀
        foreach ($Info['player_honor'] as $hk => $hv) {
            $hn = explode('^', $hv[0]);
            $playerInfo['player_honor'][$hk] = [$hn[0], $hv[1]];
        }

        //球队阵容
        if($Info['now_team_id'] && $Info['now_team_id'] != 20245){
            $mService->index = ['team_id' => 1];
            $team1 = $mService->fetchRow(
                'fb_team',
                ['team_id' => $Info['now_team_id']],
                ['team_id', 'lineup', 'lineup_position', 'team_name', 'union_id', 'count_sum']);

            foreach ($team1['lineup_position'] as $tk => $tv) {
                foreach ($tv as $tk1 => $tv1) {
                    $teammate_id[] = (int)$tv1[0];
                }
            }

            $playerInfo['team_name'] = $team1['team_name'][0];
            $_teammate = $mService->select(
                'fb_player',
                ['player_id' => ['$in' => $teammate_id]],
                ['player_id', 'player_name', 'images', 'image_urls', 'position', 'img_url']);

            foreach ($team1['lineup_position'] as $tk => $tv) {
                // coach 教练 goalkeeper 守门员 rearguard 后卫 midfielder 中场 vanguard 前峰
                if ($tk == 'goalkeeper') {
                    foreach ($tv as $tk1 => $tv1) {
                        $data = array();
                        $data['player_id'] = $tv1[0];
                        $data['player_name'] = $tv1[2];
                        foreach ($_teammate as $ttk => $ttv) {
                            if ($tv1[0] == $ttv['player_id']) {
                                $data['logo'] = $this->getLogo($ttv, 3);
                            }
                        }
                        if (!isset($data['logo'])) $data['logo'] = $this->getLogo('', 3);
                        $teammate['守门员'][] = $data;
                    }
                }
                if ($tk == 'rearguard') {
                    foreach ($tv as $tk1 => $tv1) {
                        $data = array();
                        $data['player_id'] = $tv1[0];
                        $data['player_name'] = $tv1[2];
                        foreach ($_teammate as $ttk => $ttv) {
                            if ($tv1[0] == $ttv['player_id']) {
                                $data['logo'] = $this->getLogo($ttv, 3);
                            }
                        }
                        if (!isset($data['logo'])) $data['logo'] = $this->getLogo('', 3);
                        $teammate['后卫'][] = $data;
                    }
                }
                if ($tk == 'midfielder') {
                    foreach ($tv as $tk1 => $tv1) {
                        $data = array();
                        $data['player_id'] = $tv1[0];
                        $data['player_name'] = $tv1[2];
                        foreach ($_teammate as $ttk => $ttv) {
                            if ($tv1[0] == $ttv['player_id']) {
                                $data['logo'] = $this->getLogo($ttv, 3);
                            }
                        }
                        if (!isset($data['logo'])) $data['logo'] = $this->getLogo('', 3);
                        $teammate['中场'][] = $data;
                    }

                }
                if ($tk == 'vanguard') {
                    foreach ($tv as $tk1 => $tv1) {
                        $data = array();
                        $data['player_id'] = $tv1[0];
                        $data['player_name'] = $tv1[2];
                        foreach ($_teammate as $ttk => $ttv) {
                            if ($tv1[0] == $ttv['player_id']) {
                                $data['logo'] = $this->getLogo($ttv, 3);
                            }
                        }
                        if (!isset($data['logo'])) $data['logo'] = $this->getLogo('', 3);
                        $teammate['前峰'][] = $data;
                    }
                }
            }
        }


        //球员特点
        foreach ($Info['player_character'] as $ck => $cv) {
            $cv[2] = explode('^', $cv[2])[0];
            $playerInfo['character'][$cv[0]][] = $cv;
        }
        $playerInfo['character'][1] = array_chunk($playerInfo['character'][1], 2);
        $playerInfo['character'][2] = array_chunk($playerInfo['character'][2], 2);
        $playerInfo['character'][3] = array_chunk($playerInfo['character'][3], 2);

        //球员状态
        $_player_count = $mService->fetchRow('fb_player', ['player_id' => (int)$player_id], ['player_count']);
        $player_count = array_slice($_player_count['player_count'], 0, 10);
        $score = 0;

        foreach ($player_count as $ck => $cv) {
            $score += $cv['17'];
        }
        $score = round($score / 10, 2);

        if (0 < $score && $score <= 6)
            $playerInfo['recent_status'] = [$score, '较差'];
        elseif (6 < $score && $score <= 8)
            $playerInfo['recent_status'] = [$score, '一般'];
        elseif (8 < $score && $score <= 10)
            $playerInfo['recent_status'] = [$score, '火热'];

        //seo设置
        $pn = $playerInfo['player_name'][0];
        $seo = [
            'seo_title' => "【{$pn}】{$pn}简介_{$pn}数据_{$pn}个人荣誉_全球体育",
            'seo_keys' => "{$pn}球衣号码,{$pn}数据,{$pn}身价,{$pn}转会,{$pn}年薪,{$pn}战绩,{$pn}职业生涯",
            'seo_desc' => "全球体育为您提供{$pn}简介、{$pn}职业生涯、{$pn}个人荣誉、{$pn}近期状态等资料，并提供{$pn}战绩得分数据，主要包含{$pn}近期参加比赛时间、赛果、出场时间、进球、助攻、得牌等数据，让你全面了解{$pn}的资料信息"
        ];
        $this->setSeo($seo);

        $nav = D('Home')->getNavList(40);
        $hot_league = $this->hot_league();
        $this->assign('hot_league', $hot_league);
        $this->assign('nav', $nav);
        $this->assign('playerInfo', $playerInfo);
        $this->assign('teammate', $teammate);
        $this->display();
    }

    /**
     * 球员数据
     */
    public function player_data()
    {
        $player_id = I('player_id');
        //球员信息
        $mService = mongoService();
        $mService->index = ['player_id' => 1];
        $Info = $mService->fetchRow(
            'fb_player',
            ['player_id' => (int)$player_id],
            ['player_id', 'two_year', 'player_count']);

        //联盟信息
        foreach ($Info['two_year'] as $k => $v) {
            $two_year_menu[$v[1]] = $v[1];
            // 评分
            foreach ($Info['player_count'] as $pk => $pv) {
                if ($v[0] == $pv[0]) {
                    $Info['two_year'][$k][] = $pv[17];
                }
            }

        }
        $this->ajaxReturn([
            'status' => 1,
            'two_year' => $Info['two_year'],
            'two_year_menu' => $two_year_menu,
        ]);
    }

    /**
     * 获取球队信息数据
     *
     * @User Administrator
     * @DateTime 2018年6月5日
     *
     */
    private function _teamList()
    {
        $mService = mongoService();
        $teamId = I('team_id');
        $unionId = I('union_id');

        $teamWhere = [];
        if (!empty($unionId)) {
            $mService->index = ['union_id' => 1];
            $teamWhere['union_id'] = (string)$unionId;
        }
        if (!empty($teamId)) {
            $mService->index = ['team_id' => 1];
            $teamWhere['team_id'] = (int)$teamId;
        }

        $redis = connRedis();
        $redisKey = 'info_player_team_' . json_encode($teamWhere);
        if (empty($redis->get($redisKey))) {
            $teamArrs = $mService->select(
                'fb_team',
                $teamWhere,
                ['team_id', 'team_id_tzuqiu_cc', 'player_data', 'team_name', 'image_urls', 'images']
            );

            $redis->set($redisKey, json_encode($teamArrs));
        }

        if (!empty($redis->get($redisKey))) {
            $teamArrs = $redis->get($redisKey);
            $teamArrs = json_decode($teamArrs, TRUE);
        }

        $searchTeamId = array_filter(array_column($teamArrs, 'team_id'));

        $newTeamArrs = [];
        foreach ($teamArrs as $team) {
            $newTeamArrs[$team['team_id']] = $team;
        }

        if (empty($teamId)) { // 如果是联赛界面，需要获取当前联赛下的球队
            $teamId = $searchTeamId;
        }

        if (!is_array($teamId)) {
            $teamId = array((int)$teamId);
        }

        return [
            'newTeamArrs' => $newTeamArrs,
            'teamId' => $teamId
        ];
    }

    /**
     * 获取最快进步和状态火热
     *
     * @User mjf
     * @DateTime 2018年6月5日
     *
     */
    public function hot_and_mip()
    {

        $teamData = $this->_teamList();
        $teamId = $teamData['teamId'];
        $newTeamArrs = $teamData['newTeamArrs'];

        $t1 = microtime(true);
        $mService->index = [
            'now_team_id' => 1
        ];
        $ops = [
            [
                '$match' => [
                    'now_team_id' => [
                        '$in' => $teamId
                    ],
                    'player_count' => [
                        '$exists' => 1
                    ]
                ]
            ],
            [
                '$unwind' => '$player_count'
            ],
            [
                '$project' => [
                    'mark' => [
                        '$arrayElemAt' => [
                            '$player_count',
                            17
                        ]
                    ],
                    'time' => [
                        '$arrayElemAt' => [
                            '$player_count',
                            3
                        ]
                    ],
                    'now_team_id' => 1,
                    'player_id' => 1
                ]
            ],
            [
                '$match' => [
                    'mark' => [
                        '$nin' => [
                            '',
                            null,
                            0
                        ]
                    ]
                ]
            ],
            [
                '$sort' => [
                    'time' => -1
                ]
            ],
            [
                '$group' => [
                    '_id' => '$player_id',
                    'mark' => [
                        '$push' => '$mark'
                    ]
                ]
            ],
            [
                '$project' => [
                    'mark' => [
                        '$slice' => [
                            '$mark',
                            0,
                            10
                        ]
                    ]
                ]

            ]

        ];

        $mService = mongoService();
        $playerData = $mService->aggregate('fb_player', $ops);

        $hotArray = [];
        $mipArray = [];
        $newPlayData = [];

        foreach ($playerData as $player) {
            $count = count($player['mark']);

            if ($count > 1) {
                $diff = $this->_getMip($player['mark']);
                $mipArray['player_' . $player['_id']] = $diff;

                $newPlayData[$player['_id']]['diff'] = sprintf("%.2f", $diff);
                $newPlayData[$player['_id']]['newRate'] = sprintf("%.2f", current($player['mark']));
            }

            if (10 == $count) {

                $average = array_sum($player['mark']) / 10;
                $hotArray['player_' . $player['_id']] = $average;
                $newPlayData[$player['_id']]['average'] = sprintf("%.2f", $average);
            }
        }


        $mipArray = $this->_descSortArray($mipArray);
        $hotArray = $this->_descSortArray($hotArray);

        $playerIdArray = [];
        $newHotArray = [];
        $newMipArray = [];

        foreach ($mipArray as $key1 => $mip) {
            $playerId = (int)current(array_slice(explode('_', $key1), -1));
            $playerIdArray[] = $playerId;

        }
        foreach ($hotArray as $key2 => $hot) {
            $playerId = (int)current(array_slice(explode('_', $key2), -1));
            $playerIdArray[] = $playerId;

        }

        $retData = [
            'hot' => [],
            'mip' => [],
            'newStatsalyTzuqiu' => []
        ];

        if (!empty($playerIdArray)) {
            $statsalyTzuqiu = $mService->select('fb_player', [
                'player_id' => ['$in' => $playerIdArray]
            ], [
                'now_team_id',
                'player_id',
                'image_urls',
                'images',
                'value',
                'now_team',
                'birthday',
                'count_sum',
                'player_name'
            ]);

            $newStatsalyTzuqiu = [];
            foreach ($statsalyTzuqiu as $key => $st) {
                $newKey = $st['player_id'];
                // 处理url

                $st['logo'] = $this->getLogo($st, 3);
                $st['team_logo'] = $this->getLogo($newTeamArrs[$st['now_team_id']], 2);

                $st['teamName'] = isset($newTeamArrs[$st['now_team_id']]) ? $newTeamArrs[$st['now_team_id']]['team_name'][0] : '';
                $st['playerName'] = isset($st['player_name'][0]) ? $st['player_name'][0] : '';
                $st['age'] = date('Y') - date('Y', strtotime($st['birthday']));

                $st['playerMainPosition'] = isset($st['now_team'][0][2]) ? $st['now_team'][0][2] : '';
                $st['diff'] = $newPlayData[$newKey]['diff'];
                $st['newRate'] = $newPlayData[$newKey]['newRate'];
                $st['average'] = $newPlayData[$newKey]['average'];

                $newSumData = array_slice($st['count_sum'], -1, 1); // 最新赛季的信息
                $newSumData = current($newSumData);
                $st['sn'] = isset($newSumData[3]) ? ($newSumData[1] + $newSumData[3]) : '';

                $newStatsalyTzuqiu[$newKey] = $st;
            }

            $retData = [
                'hot' => $hotArray,
                'mip' => $mipArray,
                'newStatsalyTzuqiu' => $newStatsalyTzuqiu
            ];
        }

        $this->ajaxReturn([
            'status' => 1,
            'data' => $retData,
        ]);
    }

    /**
     * 右侧：球员 射手榜,助攻榜，最佳球员
     *
     * @User mjf
     * @DateTime 2018年5月29日
     *
     */
    public function statsaly_tzuqiu()
    {
        $teamData = $this->_teamList();
        $teamId = $teamData['teamId'];
        $newTeamArrs = $teamData['newTeamArrs'];

        $mService = mongoService();

        $mService->index = ['now_team_id' => 1];
        $statsalyTzuqiu = $mService->select('fb_player', [
            'now_team_id' => ['$in' => $teamId],
            'player_count' => ['$nin' => ['', null]]
        ], [
            'now_team_id',
            'player_id',
            'image_urls',
            'images',
            'value',
            'now_team',
            'birthday',
            'count_sum',
            'player_name'
        ]);

        $hotArray = [];
        $mipArray = [];
        $bestArray = [];
        $newStatsalyTzuqiu = [];

        $t1 = microtime(TRUE);
        foreach ($statsalyTzuqiu as $key => $st) {
            $newKey = $st['now_team_id'] . '_' . $st['player_id'];

            // 处理url
            $st['logo'] = $this->getLogo($st, 3);
            $st['team_logo'] = $this->getLogo($newTeamArrs[$st['now_team_id']], 2);

            $st['teamName'] = isset($newTeamArrs[$st['now_team_id']]) ? $newTeamArrs[$st['now_team_id']]['team_name'][0] : '';
            $st['playerName'] = isset($st['player_name'][0]) ? $st['player_name'][0] : '';
            $st['age'] = date('Y') - date('Y', strtotime($st['birthday']));

            $st['playerMainPosition'] = isset($st['now_team'][0][2]) ? $st['now_team'][0][2] : '';


            // player_count[0]是最新一场的比赛
            // count_sum[最后一个值] 是最新赛季的信息
            // count_sum[第一个值] 是全部平均赛季

            $averData = $st['count_sum'][0];

            // 最佳球员
            $averRate = isset($averData[31]) ? $averData[31] : '';//总评分
            $bestArray[$newKey] = $averRate; // 总评分

            $st['cc'] = $averData[0]; // 进场
            $st['tb'] = $averData[2]; // 替补
            $st['averRate'] = sprintf("%.2f", $averRate);

            $newSumData = array_slice($st['count_sum'], -1, 1); // 最新赛季的信息
            $newSumData = current($newSumData);

            $lateRate = isset($newSumData[31]) ? $newSumData[31] : '';//最近赛季评分
            $st['lateRate'] = $lateRate;


            if (!empty(I('team_id'))) {
                // 射手榜
                $st['sn'] = isset($newSumData[3]) ? ($newSumData[1] + $newSumData[3]) : '';
                $goalsRankData[$newKey] = $st['goal'] = $st['sn'];

                // 助攻榜
                $passRankData[$newKey] = $st['pass'] = isset($newSumData[10]) ? $newSumData[10] : 0;
            }

            $newStatsalyTzuqiu[$newKey] = $st;
        }

        $t2 = microtime(TRUE);

        \Think\log::write('t=' . ($t2 - $t1));

        $bestArray = $this->_descSortArray($bestArray, 6); // 最佳球员 当前联赛6名球员评分从高到低排序

        if (!empty(I('team_id'))) {
            // 射手榜
            $goalRankArray = $this->_descSortArray($goalsRankData, 6);

            // 助攻榜
            $passRankData = $this->_descSortArray($passRankData, 6);
        }

        $retData = [
            'goal' => $goalRankArray,
            'pass' => $passRankData,
            'best' => $bestArray,
            'newStatsalyTzuqiu' => $newStatsalyTzuqiu
        ];

        $this->ajaxReturn([
            'status' => 1,
            'data' => $retData,
        ]);
    }

    private function _playerCount($teamId, $playerId)
    {
        $mService = mongoService();

        $ops = [
            [
                '$match' => [
                    'player_id' => $playerId,
                    'now_team_id' => $teamId,
                    'player_count' => ['$nin' => ['', null]]
                ]
            ],
            [
                '$unwind' => '$player_count'
            ],
            [
                '$project' => [
                    'player_count' => [
                        '$slice' => ['$player_count', 17, 1]
                    ],
                    'player_id' => 1
                ]
            ]
        ];

        $playerData = $mService->aggregate('fb_player', $ops);
        $returnData = [];
        foreach ($playerData as $data) {
            if (!empty(current($data['player_count']))) {
                $returnData[] = current($data['player_count']);
            }
        }

//         echo '<pre/>';var_dump($returnData);die;
//         $t2 = microtime(true);
//         echo 't='.($t2-$t1);

        return $returnData;
    }

    /**
     * 对数组倒叙排序并截取需要的长度
     *
     * @User mjf
     * @DateTime 2018年5月30日
     *
     * @param array $data
     */
    private function _descSortArray($data, $cut = 6)
    {
        $mipArray = array_filter($data);
        arsort($data);

        $d = array_splice($data, $cut);

        return $data;
    }

    /**
     * 计算火热状态
     * @User mjf
     * @DateTime 2018年5月30日
     *
     * @param array $rage
     * @param number $num 计算的场数
     */
    private function _getHot($rage, $num = 10)
    {
        $total = 0;
        $count = count($rage);
        if ($count > $num) { // 只有满足10场才参与统计
            $total = array_slice($rage, 0, 10);
            $total = array_sum($total);
        }

        $average = $total / $num;

        return $average;
    }

    /**
     * 计算最快进步
     *
     * @User mjf
     * @DateTime 2018年5月30日
     *
     * @param array $rage
     */
    private function _getMip($rage)
    {
        $count = count($rage);
        $diff = 0;

        if (count($rage) > 1) { // 至少参加2场，才可以进行筛选

            $last = array_slice($rage, 1, 1);// 截取第二个元素

            $diff = $rage[0] - current($last);
        }

        return $diff;
    }

    /**
     * 球队-赛事统计
     */
    public function tournament_st()
    {
        $team_id = I('team_id', 24);
        $data_type = I('data_type');
        $cup_id = I('union_id');

        //球队信息
        $mService = mongoService();
        $mService->index = ['team_id' => 1];
        $Info = $mService->fetchRow(
            'fb_team',
            ['team_id' => (int)$team_id],
            ['team_id', 'union_id']);

        //联赛统计
        if ($data_type == 1) {
            //查询最新赛季
            $mService->index = ['union_id' => 1];
            $season = $mService->fetchRow('fb_union',
                ['union_id' => (int)$Info['union_id']],
                ['season']
            )['season'][0];

            $mService->index = ['union_id' => 1];
            $tdata = $mService->fetchRow('fb_union',
                ['union_id' => (int)$Info['union_id']],
                [
                    "statistics.{$season}.matchResult.total_score",
                    "statistics.{$season}.matchResult.home_score",
                    "statistics.{$season}.matchResult.guest_score",
                    "statistics.{$season}.matchResult.half_score",
                    "statistics.{$season}.matchResult.home_half_score",
                    "statistics.{$season}.matchResult.guest_half_score",
                    "statistics.{$season}.letGoal",
                    "statistics.{$season}.bigSmall",
                ]
            );
            $data = $tdata['statistics'][$season];


            foreach ($data as $lk => $lv) {
                foreach ($lv as $k1 => $v1) {
                    foreach ($v1 as $k2 => $v2) {
                        switch ($k1) {
                            case 'total_score':
                                if ($v2[2] == $Info['team_id'])
                                    $league_date[0][$k1] = $v2;
                                break;

                            case 'home_score':
                            case 'guest_score':
                                if ($v2[1] == $Info['team_id'])
                                    $league_date[0][$k1] = $v2;
                                break;

                            case 'half_score':
                            case 'home_half_score':
                            case 'guest_half_score':
                                if ($v2[1] == $Info['team_id'])
                                    $league_date[1][$k1] = $v2;
                                break;

                            case 'total_pan_lou':
                            case 'home_pan_lu':
                            case 'guest_pan_lu':
                                if ($v2[1] == $Info['team_id'])
                                    $league_date[2][$k1] = $v2;
                                break;

                            case 'total_half_pan_lu':
                            case 'home_half_pan_lu':
                            case 'guest_half_pan_lu':
                                if ($v2[1] == $Info['team_id'])
                                    $league_date[3][$k1] = $v2;
                                break;

                            case 'TotalBs':
                            case 'HomeBs':
                            case 'GuestBs':
                                if ($v2[1] == $Info['team_id'])
                                    $league_date[4][$k1] = $v2;
                                break;

                            case 'TotalBsHalf':
                            case 'HomeBsHalf':
                            case 'GuestBsHalf':
                                if ($v2[1] == $Info['team_id'])
                                    $league_date[5][$k1] = $v2;
                                break;

                        }
                    }
                }

            }
        } else if ($data_type == 2) {
            //查询最新赛季
            $mService->index = ['union_id' => 1];
            $season = $mService->fetchRow('fb_union',
                ['union_id' => (int)$cup_id],
                ['season']
            )['season'][0];

            $tdata = $mService->fetchRow('fb_union',
                ['union_id' => (int)$cup_id],
                [
                    "statistics.{$season}.letGoal",
                    "statistics.{$season}.bigSmall",
                ]
            );

            $data = $tdata['statistics'][$season];
            foreach ($data as $lk => $lv) {
                foreach ($lv as $k1 => $v1) {
                    foreach ($v1 as $k2 => $v2) {
                        switch ($k1) {
                            case 'total_pan_lou':
                            case 'home_pan_lu':
                            case 'guest_pan_lu':
                                if ($v2[1] == $Info['team_id'])
                                    $cup_data[0][$k1] = $v2;
                                break;

                            case 'total_half_pan_lu':
                            case 'home_half_pan_lu':
                            case 'guest_half_pan_lu':
                                if ($v2[1] == $Info['team_id'])
                                    $cup_data[1][$k1] = $v2;
                                break;

                            case 'TotalBs':
                            case 'HomeBs':
                            case 'GuestBs':
                                if ($v2[1] == $Info['team_id'])
                                    $cup_data[2][$k1] = $v2;
                                break;

                            case 'TotalBsHalf':
                            case 'HomeBsHalf':
                            case 'GuestBsHalf':
                                if ($v2[1] == $Info['team_id'])
                                    $cup_data[3][$k1] = $v2;
                                break;
                        }
                    }
                }
            }
        }

        ksort($league_date);
        ksort($cup_data);
        $this->ajaxReturn([
            'status' => 1,
            'league_data' => $league_date,
            'cup_data' => $cup_data,
            'season' => $season,
        ]);
    }

    /**
     * 球队-最佳球员
     */
    public function league_best_player()
    {
        $union_id = I('union_id');
        $season = I('season');

        $mService = mongoService();

        if (!$season) {
            $union = $mService->fetchRow(
                'fb_union',
                ['union_id' => (int)$union_id],
                ['season']);

            $season = I('season') ? I('season') : $union['season'][0];
        }

        $t_player_tech = $mService->fetchRow('fb_union',
            ['union_id' => (int)$union_id],
            ["statistics.{$season}.player_tech.Total", "statistics.{$season}.player_tech.Pid"]
        );

        $player_tech = $t_player_tech['statistics'][$season]['player_tech'];

        $best_rank = [];
        foreach ($player_tech['Total']['value'] as $tk => $tv) {
            $best_rank[$tv[0]]['player_id'] = $tv[0];//球员ID
            $best_rank[$tv[0]]['team_id'] = $player_tech['Pid'][$tv[0]][1];;//球队ID
            $best_rank[$tv[0]]['cc'] = $tv[1];//出场
            $best_rank[$tv[0]]['tp'] = $tv[2];//替补
            $best_rank[$tv[0]]['score'] = $tv[10];//总评分
            $best_rank[$tv[0]]['avg_score'] = round($tv[10] / $tv[1], 2);//赛季平均
            $score_sort[] = $best_rank[$tv[0]]['avg_score'];
        }

        array_multisort($score_sort, SORT_DESC, $best_rank);
        $best_rank = array_slice($best_rank, 0, 6);
        $player_ids = array_column($best_rank, 'player_id');

        if ($player_ids) {
            $fb_player = $mService->select(
                'fb_player',
                ['player_id' => ['$in' => $player_ids]],
                ['player_id', 'birthday', 'position', 'images', 'image_urls', 'player_name']);

        }
        $player_ids2 = array_column($fb_player, 'player_id');
        $players = array_combine($player_ids2, $fb_player);

        foreach ($best_rank as $bk => $bv) {
            $p = $players[$bv['player_id']];
            $best_rank[$bk]['player_logo'] = $this->getLogo($p, 3);
            $best_rank[$bk]['age'] = date('Y') - date('Y', strtotime($p['birthday']));
            $best_rank[$bk]['position'] = $p['position'][0];
            $best_rank[$bk]['player_name'] = $p['player_name'][0];
        }

        $this->ajaxReturn([
            'status' => 1,
            'list' => $best_rank,
        ]);

    }

    /**
     * 近期转会
     */
    public function nearly_tran()
    {
        $union_id = I('union_id', 11);
        $mService = mongoService();

        $union_teams = $mService->select(
            'fb_team',
            ['union_id' => (string)$union_id],
            ['team_id', 'team_name', 'team_id_tzuqiu_cc']
        );

        $tzuqiu_team_ids = array_column($union_teams, 'team_id_tzuqiu_cc');

        $statsaly_tzuqiu = $mService->select(
            'fb_statsaly_tzuqiu',
            ['stats_type' => ['$in' => ['tran', 'rumor']]]
        );

        //获取属于当前联赛的相关交易、传闻
        foreach ($statsaly_tzuqiu as $sk => $sv) {
            foreach ($sv['stats_list'] as $sk2 => $sv2) {
                if ($sv['stats_type'] == 'rumor') {
                    if (in_array($sv2['origClubId'], $tzuqiu_team_ids) || in_array($sv2['destClubId'], $tzuqiu_team_ids)) {
                        $rumor[] = $sv2;
                    }
                } else if ($sv['stats_type'] == 'tran') {
                    if (in_array($sv2['mfClubId'], $tzuqiu_team_ids) || in_array($sv2['miClubId'], $tzuqiu_team_ids)) {
                        $tran[] = $sv2;
                    }
                }

            }
        }

        $rumor = array_slice($rumor, 0, 6);
        $tran = array_slice($tran, 0, 6);

        $tzuqiu_player_ids = array_merge(array_column($rumor, 'playerId'), array_column($rumor, 'playerId'));
        foreach ($rumor as $k => $v) {
            if ($v['origClubId'])
                $tzuqiu_team_ids2[] = $v['origClubId'];
            if ($v['destClubId'])
                $tzuqiu_team_ids2[] = $v['destClubId'];
        }

        foreach ($tran as $k => $v) {
            if ($v['mfClubId'])
                $tzuqiu_team_ids2[] = $v['mfClubId'];
            if ($v['miClubId'])
                $tzuqiu_team_ids2[] = $v['miClubId'];
        }


        //转会留言球员、球队信息比配
        if ($tzuqiu_player_ids) {
            $mService->index = ['player_id_tzuqiu_cc' => 1];
            $tzuqiu_players = $mService->select(
                'fb_player',
                ['player_id_tzuqiu_cc' => ['$in' => $tzuqiu_player_ids]],
                ["player_id", "player_name", 'images', 'image_urls', "value", 'now_team_id', "position",
                    'recent_status_rate', 'player_id_tzuqiu_cc'
                ]
            );
        }

        if ($tzuqiu_team_ids2) {
            $tzuqiu_teams = $mService->select(
                'fb_team',
                ['team_id_tzuqiu_cc' => ['$in' => $tzuqiu_team_ids2]],
                ["team_id", "img_url", 'images', "image_urls", "team_id_tzuqiu_cc"]
            );
        }

        foreach ($tzuqiu_players as $pk => $pv) {
            $rumorlayers[$pv['player_id_tzuqiu_cc']]['player_id'] = $pv['player_id'];
            $rumorlayers[$pv['player_id_tzuqiu_cc']]['player_logo'] = $this->getLogo($pv, 3);
        }

        foreach ($tzuqiu_teams as $tk => $tv) {
            $rumorTeam[$tv['team_id_tzuqiu_cc']]['team_id'] = $tv['team_id'];
            $rumorTeam[$tv['team_id_tzuqiu_cc']]['img_url'] = $this->getLogo($tv, 2);
        }

        foreach ($rumor as $rk => $rv) {
            $rumor[$rk]['dest'] = $rumorTeam[$rv['destClubId']];//转入球队
            $rumor[$rk]['orig'] = $rumorTeam[$rv['origClubId']];//转出球队
            $rumor[$rk]['player_id'] = $rumorlayers[$rv['playerId']]['player_id'];
            $rumor[$rk]['player_logo'] = $rumorlayers[$rv['playerId']]['player_logo'] ?: '/Public/Home/images/info/zone-player.png';
        }
        foreach ($tran as $tk => $tv) {

            $tran[$tk]['dest'] = $rumorTeam[$tv['miClubId']];//转入球队
            $tran[$tk]['orig'] = $rumorTeam[$tv['mfClubId']];//转出球队
            $tran[$tk]['player_id'] = $rumorlayers[$tv['playerId']]['player_id'];
            $tran[$tk]['player_logo'] = $rumorlayers[$tv['playerId']]['player_logo'] ?: '/Public/Home/images/info/zone-player.png';
        }

        var_dump($tran);
        exit;
    }

    /**
     *
     * @User mjf
     * @DateTime 2018年5月30日
     *
     * @param array $data
     * @param number $type
     * @param number $size
     * @return Ambigous <string, mixed>
     */
    public function getLogo($data, $type, $size = 0)
    {
        $logo = '';
        //联盟图标
        if ($type == 1) {
            $default = $size == 0 ? '/Public/Home/images/info/zone-union.jpg' : '/Public/Home/images/info/player-default.png';
        }

        //球队图标
        if ($type == 2) {
            $default = '/Public/Home/images/info/zone-team.png';
        }

        //球员图标
        if ($type == 3) {
            $default = '/Public/Home/images/info/zone-player.png';

            // nopic.gif 如果是默认图则需要处理
//             Think\log::write(print_r($data, true));
            if (!empty($data['image_urls'][0]) && strpos($data['image_urls'][0], 'nopic.gif')) {
                $data['images'] = NULL;
            }
        }

        $logo = !empty($data['images'][0]) ? str_replace('full/', 'https://img3.qqty.com/', $data['images'][0]['path']) : $default;

        return $logo;
    }

    /**
     * 获取时间
     * @param $date1
     * @return mixed
     */
    public function gwk($date1)
    {
        $datearr = explode("-", $date1);
        $year = $datearr[0];
        $month = sprintf('%02d', $datearr[1]);
        $day = sprintf('%02d', $datearr[2]);
        $hour = $minute = $second = 0;
        $dayofweek = mktime($hour, $minute, $second, $month, $day, $year);
        $shuchu = date("w", $dayofweek);      //获取星期值
        $weekarray = array("星期日", "星期一", "星期二", "星期三", "星期四", "星期五", "星期六");
        return $weekarray[$shuchu];
    }

}