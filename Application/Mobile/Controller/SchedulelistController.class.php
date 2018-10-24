<?php

/**
 * @author : longs<lons@qc.mail>
 * @Date : 18-3-15
 */

use Think\Tool\Tool;

class SchedulelistController extends CommonController
{

    //定义专题页参数
    public $special = [];
    protected function _initialize() {
        parent::_initialize();
        $this->special = [
            'premierleague' => ['level' => 1, 'id' => 36, 'publishId' => 13], //英超
            'seriea'            => ['level' => 1, 'id' => 34, 'publishId' => 17],//意甲
            'bundesliga'        => ['level' => 1, 'id' => 8, 'publishId' => 15],//德甲
            'laliga'            => ['level' => 1, 'id' => 31, 'publishId' => 14],//西甲
            'csl'               => ['level' => 1, 'id' => 60, 'publishId' => 18],//中超
            'championsleague'   => ['level' => 2, 'id' => 103, 'publishId' => 27],//欧冠
            'afccl'             => ['level' => 2, 'id' => 192, 'publishId' => 28],//亚冠
            'nba'               => ['level' => 3, 'id' => 1, 'publishId' => 4],//NBA
            'cba'              => ['level' => 3, 'id' => 5, 'publishId' => 3],//CBA
            '2018worldcup' => ['level' => 4, 'id' => 75,'publishId' => 96] //世界杯
        ];
    }

    public $unionArr = [
        'premierleague'   => '英超',
        'laliga'          => '西甲',
        'bundesliga'      => '德甲',
        'seriea'          => '意甲',
        'championsleague' => '欧冠',
        'afccl'           => '亚冠',
        'csl'             => '中超',
        'nba'             => 'NBA',
        'cba'             => 'CBA',
        '2018worldcup'    => '世界杯',
    ];


    /**
     * 赛程页面
     */
    public function index() {
        $type = explode('/',$_SERVER['REQUEST_URI'])[1];
        $unionLevel = $this->special[$type]['level'];
        $unionName = $this->unionArr[$type];
        $this->assign("unionName", $unionName);
        $this->assign("level", $unionLevel);
        $this->assign("name", $type);
        $this->assign("schedule", "schedule");

        if($unionLevel === 1) {
            $this->display("leagueschedule");
        } elseif ($unionLevel === 2) {
            $this->display("championschedule");
        } elseif ($unionLevel === 3) {
            $this->display("bkleagueschedule");
        } elseif ($unionLevel === 4) {
            $this->display("worldcupschedule");
        }
    }


    /**
     * 获取赛程
     */
    public function schedule() {
        $type = I("type") ? I("type") : "laliga";
        $level = $this->special[$type]['level'];
        if ($level === 1) {
            $this->getLive($type);
        } else if ($level === 2) {
            $this->getLive2($type);
        } else if ($level === 3) {
            $this->getBkUnionSchedule($type);
        } else if ($level === 4) {
            $this->getWorldCupSchedule($type);
        }
    }


    /**
     * rank页面
     */
    public function rank() {
        $type = explode('/',$_SERVER['REQUEST_URI'])[1];
        $level = $this->special[$type]['level'];
        $unionLevel = $this->special[$type]['level'];
        $unionName = $this->unionArr[$type];
        $this->assign("unionName", $unionName);
        $this->assign("level", $unionLevel);
        $this->assign("name", $type);
        $this->assign("schedule", "schedule");
        if ($level == 1) {
            $this->display("leaguerank");
        } elseif ($level == 2) {
            $this->display("championrank");
        } elseif ($level == 3) {
            $this->display("championrank");
        } elseif ($level == 4) {
            $this->display("championrank");
        }
    }

    /**
     * 获取排行榜信息
     */
    public function getRankData() {
        $type = I("type") ? I("type") : "laliga";
        $level = $this->special[$type]['level'];
        $id = $this->special[$type]['id'];
        if ($level === 1) {
            $this->getFbUnionRank($id, $type);
        } else if ($level === 2) {
            $this->getFbUnionRank($id, $type,true);
        } else if ($level === 3) {
            $this->getBkUnionRank();
        } else if ($level === 4) {
            $this->getFbUnionRank($id, $type,true);
        }
    }


    /**
     * 获取射手榜信息
     */
    public function getShooter() {
        $type = I("type") ? I("type") : "laliga";
        $level = $this->special[$type]['level'];
        $id = $this->special[$type]['id'];
        if ($level === 1) {
            $this->getFbUnionArcher($id);
        } else if ($level === 2) {
            $this->getFbUnionArcher($id);
        } else if ($level === 4) {
            $this->getFbUnionArcher($id);
        }
    }


    /**
     * 获取资讯列表
     */
    public function info() {
        $type = explode('/',$_SERVER['REQUEST_URI'])[1];
        $unionLevel = $this->special[$type]['level'];
        $unionName = $this->unionArr[$type];
        $this->assign("unionName", $unionName);
        $this->assign("level", $unionLevel);
        $this->assign("name", $type);
        $this->display("leagueinfo");
    }


    /**
     * 获取
     * @return bool|mixed|string|void
     */
    public function getInfo() {
        $type = I("type") ? I("type") : "laliga";
        $time_stamp = I("time_stamp") ? I("time_stamp") : time();
        $page_num = I("page_num") ? I("page_num") : 1;
        $limit = (($page_num -1) * 20). ", 20";
        $pid = $this->special[$type]['publishId'];
        if ($type == "nba" || $type == "cba") {
            $dataIcon = D("NewBase")->getNavList("Mbk" , 'name, ui_type_value as url, icon, sort', ["name" => $this->unionArr[$type]]);
        }else {
            $dataIcon = D("NewBase")->getNavList("Mfb" , 'name, ui_type_value as url, icon, sort', ["name" => $this->unionArr[$type]]);
        }
        if (!$data = S('M_'.$type.'_info'.$time_stamp.":".$page_num)) {
            $publishId[] = $pid;
            $data['data'] = A("Index")->getPublishData($publishId, ["status" => 1, "add_time < ".$time_stamp], false, $limit);
            $data['iconUrl'] = Tool::imagesReplace($dataIcon[0]['icon']);
            S('M_'.$type.'_info'.$time_stamp.":".$page_num, json_encode($data), 30);
        }
        $this->ajaxReturn($data);
    }


    /**
     * 获取直播条live地址 西甲 英超 法甲 中超等 俱乐部联赛
     * @param $type
     */
    public function getLive($type) {
        if (!$data = S('M_'.$type.'_live')) {
            $data = D("NewBase")->getLive($type);
            $dataIcon = D("NewBase")->getNavList("Mfb" , 'name, ui_type_value as url, icon, sort', ["name" => $this->unionArr[$type]]);
            $data['iconUrl'] = Tool::imagesReplace($dataIcon[0]['icon']);
            S('M_'.$type.'_live', json_encode($data), 60 * 20);
        }

        $this->ajaxReturn($data);
    }


    /**
     * 获取世界杯赛程信息
     */
    public function getWorldCupSchedule($type) {
        if (!$data = S('M_worldCup_live')) {
            $data = D("NewBase")->schedule();
            $dataIcon = D("NewBase")->getNavList("Mfb" , 'name, ui_type_value as url, icon, sort', ["name" => $this->unionArr[$type]]);
            $data['iconUrl'] = Tool::imagesReplace($dataIcon[0]['icon']);
            S('M_worldCup_live', json_encode($data), 60 * 3);
        }
        $this->ajaxReturn($data);
    }

    /**
     * 亚冠 欧冠 赛程信息
     */
    public function getLive2($type) {
        if (!$data = S('M_'.$type.'_live2')) {
            $data = D("NewBase")->getLive2($type);
            $dataIcon = D("NewBase")->getNavList("Mfb" , 'name, ui_type_value as url, icon, sort', ["name" => $this->unionArr[$type]]);
            $data['iconUrl'] = Tool::imagesReplace($dataIcon[0]['icon']);
            S('M_'.$type.'_live2', json_encode($data), 60 * 3);
        }

        $this->ajaxReturn($data);
    }


    /**
     * 获取篮球赛程信息
     * @param $typeName
     * @param string $year
     */
    public function getBkUnionSchedule($typeName, $year = "2017-2018") {
        $type = I("type") ? I("type") : "nba";
        if (!$data = S('M_'.$type.'_bkSchedule')) {
            $data = D("NewBase")->getBkUnionSchedule($typeName, $year);
            $allGame = $newGame = $timeSort =  [];
            foreach ($data as $key => $value) {
                if ($key === "perseason") {
                    foreach ($value as $perkey => $pervalue) {
                        $allGame[] = $pervalue;
                    }
                }
                if ($key === "regular") {
                    foreach ($value as $rekey => $revalue) {
                        foreach ($revalue as $rekey2 => $reval2) {
                            $allGame[] = $reval2;
                        }
                    }
                }
                if ($key === "playoff") {
                    foreach ($value as $playkey =>$playvalue) {
                        foreach ($playvalue as $plkey => $plval) {
                            if ($plkey === "game_list") {
                                foreach ($plval as $plk => $plv) {
                                    foreach ($plv as $lisk => $liv) {
                                        if ($lisk  == "4") {
                                            foreach ($liv as $k => $v) {
                                                $allGame[] = $v;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            foreach ($allGame as $key => $value) {
                $newGame[$key]['id'] = $value[0];
                //排序字段
                $newGame[$key]['gtime'] = $timeSort[$key] =  strtotime($value[2]);
                $newGame[$key]['day'] = date("Y-m-d",strtotime($value[2]));
                $newGame[$key]['game_month'] = date("m",strtotime($value[2]));
                $newGame[$key]['game_day'] = date("d",strtotime($value[2]));
                $newGame[$key]['game_time'] = date("H:i", strtotime($value[2]));
                $newGame[$key]['home_team_id'] = $value[3];
                $newGame[$key]['away_team_id'] = $value[4];
                $newGame[$key]['home_team_score'] = $value[5];
                $newGame[$key]['away_team_score'] = $value[6];
                $newGame[$key]['game_status'] = $value[9];
                $newGame[$key]['week'] = D("NewBase")->week(date("N",strtotime($value[2])));

                /*
                 暂不需字段
                $newGame[]['once'] = $value[1];
                $newGame[]['home_team_half_score'] = $value[7];
                $newGame[]['away_team_half_score'] = $value[8];
                */
            }
            array_multisort($timeSort, SORT_DESC, $newGame);
            $newGame = D("NewBase")->getBkGameStatus($newGame);
            $Ids = array_column($newGame, "id");
            $newGame = $this->getTeamName($Ids, $newGame);
            setTeamLogo($newGame, 2);
            unset($allGame, $timeSort, $data);
            $data['data'] = $newGame;
            $dataIcon = D("NewBase")->getNavList("Mbk" , 'name, ui_type_value as url, icon, sort', ["name" => $this->unionArr[$type]]);
            $data['iconUrl'] = Tool::imagesReplace($dataIcon[0]['icon']);
            S('M_'.$type.'_bkSchedule', json_encode($data), 10);
        }

        $this->ajaxReturn($data);
    }


    /**
     * 获取联赛积分排名
     */
    public function getFbUnionRank($id, $type, $isChampion = false) {
        if (!$data = S('M_'.$type.'_fbUnionRank')) {
            if ($isChampion) {
                $data = [];
                $data['data']["A"] = D("NewBase")->getFbUnionRank($id, 15, "A");
                $data['data']["B"] = D("NewBase")->getFbUnionRank($id, 15, "B");
                $data['data']["C"] = D("NewBase")->getFbUnionRank($id, 15, "C");
                $data['data']["D"] = D("NewBase")->getFbUnionRank($id, 15, "D");
                $data['data']["E"] = D("NewBase")->getFbUnionRank($id, 15, "E");
                $data['data']["F"] = D("NewBase")->getFbUnionRank($id, 15, "F");
                $data['data']["G"] = D("NewBase")->getFbUnionRank($id, 15, "G");
                $data['data']["H"] = D("NewBase")->getFbUnionRank($id, 15, "H");
                $dataIcon = D("NewBase")->getNavList("Mfb" , 'name, ui_type_value as url, icon, sort', ["name" => $this->unionArr[$type]]);
                $data['iconUrl'] = Tool::imagesReplace($dataIcon[0]['icon']);
            } else {
                $data['data'] = D("NewBase")->getFbUnionRank($id);
                $dataIcon = D("NewBase")->getNavList("Mfb" , 'name, ui_type_value as url, icon, sort', ["name" => $this->unionArr[$type]]);
                $data['iconUrl'] = Tool::imagesReplace($dataIcon[0]['icon']);
            }
            S('M_'.$type.'_fbUnionRank', json_encode($data), 60 * 20);
        }
        $this->ajaxReturn($data);
    }


    /**
     * 获取联赛射手排行
     */
    public function getFbUnionArcher($id) {
        if (!$data = S('M_'.$id.'_fbUnionArcher')) {
            $data = D("NewBase")->getFbUnionArcher($id);
            S('M_'.$id.'_fbUnionArcher', json_encode($data), 60 * 20);
        }
        $this->ajaxReturn($data);
    }


    /**
     * 获取篮球联赛联赛数据
     */
    public function getBkUnionRank() {
        if (!$data = S('M_bk_fbUnionRank')) {
            $data = [];
            $dataNbaIcon = D("NewBase")->getNavList("Mbk" , 'name, ui_type_value as url, icon, sort', ["name" => "nba", "trim(icon) != ''" ]);
            $dataCbaIcon = D("NewBase")->getNavList("Mbk" , 'name, ui_type_value as url, icon, sort', ["name" => "cba", "trim(icon) != ''"]);
            $data['nba']['iconUrl'] = Tool::imagesReplace($dataNbaIcon[0]['icon']);
            $data['cba']['iconUrl'] = Tool::imagesReplace($dataCbaIcon[0]['icon']);
            $data['nba']['east'] = D("NewBase")->getBkUnionRank(1,1);
            $data['nba']['west'] = D("NewBase")->getBkUnionRank(1,2);
            $data['nba']['points'] = D("NewBase")->getBkUnionRank(1,3);
            $data['nba']['assists'] = D("NewBase")->getBkUnionRank(1,4);
            $data['nba']['rebound'] = D("NewBase")->getBkUnionRank(1,5);

            $data['cba']['integral'] = D("NewBase")->getBkUnionRank(5,1);
            $data['cba']['points'] = D("NewBase")->getBkUnionRank(5,3);
            $data['cba']['assists'] = D("NewBase")->getBkUnionRank(5,4);
            $data['cba']['rebound'] = D("NewBase")->getBkUnionRank(5,5);
            S('M_bk_fbUnionRank', json_encode($data), 60 * 20);
        }

        $this->ajaxReturn($data);
    }


    /**
     * 赛程表中无对阵信息球队名 获取球队名
     * @param $ids
     * @param $newGame
     * @return mixed
     */
    public function getTeamName($ids, $newGame) {
        $data = mongo("bk_game_schedule")->field("game_id, union_id, home_team_name, away_team_name, game_time, game_status, home_team_id, away_team_id")->where(['game_id' => ['in', $ids]])->select();
        foreach ($newGame as $key => $game) {
            foreach ($data as $val) {
                if ($game['id'] === $val['game_id']) {
                    $newGame[$key]['home_team_name'] = $val['home_team_name'];
                    $newGame[$key]['away_team_name'] = $val['away_team_name'];
                }
            }
        }
        return $newGame;
    }

}