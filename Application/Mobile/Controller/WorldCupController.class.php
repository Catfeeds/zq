<?php
/**
 * 世界杯专题页
 */
use Think\Tool\Tool;

class WorldCupController extends CommonController
{

    //球队分析页面
    public function TeamAanalysisList(){
        //获取球队直通车数据
        $navData = M('Nav')->field('id,name,sign,ui_type_value as url,icon')->where(['type' => 43, 'status' => 1])->order('sign,sort')->select();
        $navList = [];
        foreach ($navData as $val) {
            if (count($navList[$val['sign']]) < 5) {
                $val['icon'] = Tool::imagesReplace($val['icon']);
                $navList[$val['sign']][] = $val;
            }
        }
        $this->assign('navList', $navList);
        $this->assign('nav_id',2);
        $this->display();
    }

    //球队分析详情页面
    public function TeamAanalysisInfo(){
        $nav_id = I('id');
        //获取球队相关信息
        $team_info = M('nav')->where(['id'=>$nav_id])->find();
        $team_info['icon'] = Tool::imagesReplace($team_info['icon']);
        $this->assign('team',$team_info);

        //获取球队赛事
        $game = S('TeamAanalysisInfo_game_'.$team_info['filter_value']);
        if(!$game)
        {
            $game = $this->getWorldCupTeamList($team_info['filter_value']);
            S('TeamAanalysisInfo_game_'.$team_info['filter_value'],$game,60*5);
        }
        $this->assign('game',$game);

        //获取资讯文章
        $list = S('TeamAanalysisInfo_new_'.$team_info['id']);
        if(!$list)
        {
            $class_id = M('PublishClass')->field('id')->where(['remark'=>$team_info['id']])->find();
            $list = $this->getTeamInfoNew($class_id['id']);
            S('TeamAanalysisInfo_new_'.$team_info['id'],$list,60*5);
        }
        $this->assign('newList',$list);
        $this->assign('goback',1);
        $this->display();
    }

    //获取文章列表
    public function getTeamInfoNew($id)
    {
        $data = S('getTeamInfoNew'.$id);
        if(!$data)
        {
            //定义分组数据
            $up = ['阵容' => '', '成绩' => '', '深度' => ''];
            $down = ['人物' => '', '媒体预测' => '', '盘口数据' => ''];
            $list = M('PublishList')->field('id,title,remarks as type,class_id,img,add_time')->where(['class_id' => $id, 'status' => 1])->order('add_time')->select();
            if ($list) {
                $classArr = getPublishClass(0);
                foreach ($list as $key => $value) {
                    $class_id = $value['class_id'];
                    if($classArr[$class_id]['pid'] == 111) $class_id = 96;
                    $list[$key]['img'] = newsImgReplace($value);
                    $list[$key]['href'] = mNewsUrl($value['id'], $class_id, $classArr);
                    $up[$value['type']] = $list[$key];
                    $down[$value['type']] = $list[$key];
                }
            }
            $up = array_slice($up, 0, 3);
            $down = array_slice($down, 0, 3);
            $data = array_merge((array)$up, (array)$down);
            $data = array_filter($data);
            S('getTeamInfoNew'.$id,$data,60*5);
        }
        return $data;
    }

    //获取世界杯球队赛事
    public function getWorldCupTeamList($team_id)
    {
        $game = S('getWorldCupTeamList'.$team_id);
        if(!$game)
        {
            $over = $noover = $overTime = $nooverTime = [];
            $mongo = mongoService();
            //获取世界杯联赛信息
            $gameList = $this->getWorldCupList();
            //当球队为主场时数据
            $gameHome = $mongo->select('fb_game',['game_id'=>[$mongo->cmd('in')=>$gameList],'home_team_id'=>(int)$team_id],['game_id','gtime','home_team_id','away_team_id','home_team_name','away_team_name','game_state','score']);
            $gameAway = $mongo->select('fb_game',['game_id'=>[$mongo->cmd('in')=>$gameList],'away_team_id'=>(int)$team_id],['game_id','gtime','home_team_id','away_team_id','home_team_name','away_team_name','game_state','score']);
            $game = array_merge((array)$gameHome,(array)$gameAway);
            foreach($game as $key=>$val)
            {
                $game[$key]['home_team_name'] = $val['home_team_name'][0];
                $game[$key]['away_team_name'] = $val['away_team_name'][0];
                $game[$key]['home_logo'] = $this->getWorldCupTeamLogo($val['home_team_id']);
                $game[$key]['away_logo'] = $this->getWorldCupTeamLogo($val['away_team_id']);
                $game[$key]['game_time'] = strtotime($val['gtime']);
                $game[$key]['weekday'] = D('NewBase')->week(date("N", $game[$key]['game_time']));
                $game[$key]['day'] = date('m月d日',$game[$key]['game_time']);
                $game[$key]['time'] = date('H:i',$game[$key]['game_time']);
                $game[$key]['gstate'] = C('game_state')[$val['game_state']];
                if($val['game_state'] == -1) {
                    $over[] = $game[$key];
                    $overTime[] = $game[$key]['game_time'];
                }else {
                    $noover[] = $game[$key];
                    $nooverTime[] = $game[$key]['game_time'];
                }
            }
            array_multisort($overTime, SORT_ASC,$over);
            array_multisort($nooverTime, SORT_ASC,$noover);
            $game = array_merge((array)$noover,(array)$over);
            S('getWorldCupTeamList'.$team_id,$game,5*60);
        }
        return $game;
    }

    //获取参赛国家球队logo
    public function getWorldCupTeamLogo($id)
    {
        $logo = S('getWorldCupTeamLogo');
        if(!$logo)
        {
            $logo = M('Nav')->where(['type' => 43])->getField('filter_value,icon',true);
            foreach($logo as $key=>$val)
            {
                $logo[$key] = Tool::imagesReplace($val);
            }
            S('getWorldCupTeamLogo',$logo,3600*24);
        }
        return $logo[$id];

    }

    //获取世界杯所有赛事列表
    public function getWorldCupList()
    {
        $result = S('2018WorldCupGameList');
        if(!$result)
        {
            $mongo = mongoService();
            //获取世界杯联赛信息
            $game = $mongo->select('fb_union', ['union_id'=>75], ['statistics']);
            $game = $game[0]['statistics'][2018]['matchResult'];
            unset($game['arrCupKind'],$game['Groups']);
            $result = [];
            array_walk_recursive($game, function($value) use (&$result) {
                array_push($result, $value);
            });
            S('2018WorldCupGameList',$result,3600*12);
        }
        return $result;
    }

    //世界杯资讯页
    public function forecast()
    {
        $this->assign('nav_id',3);
        $this->display();
    }

    //ajax获取杯赛推荐数据
    public function recommend()
    {
        $page = I('page');
        $time = I('time');
        $data = $this->getRecommend($page, $time);
        if ($data)
            $tmp = ['code' => 200, 'data' => $data];
        else
            $tmp = ['code' => 201];
        $this->ajaxReturn($tmp);
    }

    //查询杯赛推荐数据
    public function getRecommend($p = 1, $time = NOW_TIME)
    {
        $limit = 10;
        $map['add_time'] = ['lt', $time];
        $map['status'] = 1;
        $map['is_cup'] = 1;
        $count = M('PublishList')->where($map)->count();
        $data = M('PublishList')->field("id,class_id,title,img,short_title,add_time")->where($map)->order('add_time desc')->page($p)->limit($limit)->select();
        if ($data) {
            $classArr = getPublishCLass(0); //资讯分类数组
            foreach ($data as $k => $v) {
                $class_id = $v['class_id'];
                if($classArr[$class_id]['pid'] == 111) $class_id = 96;
                $data[$k]['img'] = newsImgReplace($v);
                $data[$k]['href'] = mNewsUrl($v['id'], $class_id, $classArr);
                $data[$k]['vol'] = $count - (($p - 1) * $limit + $k);
            }
        } else {
            $data = [];
        }
        return $data;
    }

    //赛程列表
    public function competition()
    {
        if (!$data = S('M_worldCup_live')) {
            $data = D("NewBase")->schedule();
            $dataIcon = D("NewBase")->getNavList("Mfb" , 'name, ui_type_value as url, icon, sort', ["name" => '世界杯']);
            $data['iconUrl'] = Tool::imagesReplace($dataIcon[0]['icon']);
            S('M_worldCup_live', json_encode($data), 60 * 3);
        }
        $data = $data['data'];
        //处理日程赛
        $dayData = $groupData = $taotai = [];
        foreach($data['day_schedule'] as $val)
        {
            switch($val['group'])
            {
                case 8:
                    $title = '1/8决赛';
                    break;
                case 4:
                    $title = '1/4决赛';
                    break;
                case 2:
                    $title = '1/2决赛';
                    break;
                case 3:
                    $title = '三四名';
                    break;
                case 1:
                    $title = '冠军';
                    break;
                default:
                    $title = '小组赛';
            }
            $val = $this->gameCommon($val);
            $_key = $val['day'].' '.$val['week'].' '.$title;
            $dayData[$_key][] = $val;
        }

        //处理小组赛数据
        foreach ($data['group_schedule'] as $key=>$val)
        {
            foreach($val as $k=>$v)
            {
                $groupData[$key][$k] = $this->gameCommon($v);
            }
        }
        //处理淘汰赛数据
        foreach ($data['knockout_matchs'] as $key=>$val)
        {
            foreach($val as $k=>$v)
            {
                $taotai[$key][$k] = $this->gameCommon($v);
            }
        }
        $this->assign('dayData',$dayData);
        $this->assign('groupData',$groupData);
        $this->assign('taotai',$taotai);
        $this->assign('nav_id',1);
        $this->display();
    }

    //赛事数据处理公用方法
    public function gameCommon($val)
    {
        $val['homeLogo'] = $this->getWorldCupTeamLogo($val['home_team_id']);
        $val['awayLogo'] = $this->getWorldCupTeamLogo($val['away_team_id']);
        $val['day'] = date('m月d日',strtotime('2018-'.$val['gtime']));
        $time = explode(' ',$val['gtime']);
        $val['time'] = $time[1];
        $val['gstate'] = C('game_state')[$val['game_state']];
        $val['week'] = D('NewBase')->week(date("N", strtotime('2018-'.$val['gtime'])));
        return $val;
    }

    //世界杯积分榜
    public function crunchies()
    {
        //积分榜
        $group = $this->getFbUnionRank()['data'];
        foreach($group as $key=>$val)
        {
            foreach($val as $k=>$v)
            {
                $group[$key][$k]['nav_id'] = $this->getWorldCupTeamId($v['team_id']);
            }
        }
        //射手榜
        $rank = $this->getFbUnionArcher();
        $this->assign('group',$group);
        $this->assign('rank',$rank);
        $this->assign('nav_id',4);
        $this->display();
    }

    //获取参赛国家球队标识id
    public function getWorldCupTeamId($val)
    {
        $id = S('getWorldCupTeamId');
        if(!$id)
        {
            $id = M('Nav')->where(['type' => 43])->getField('filter_value,id',true);
            S('getWorldCupTeamId',$id,3600*24);
        }
        return $id[$val];

    }

    /**
     * 获取联赛积分排名
     */
    public function getFbUnionRank() {
        $id = 75;
        $type = '2018worldcup';
        $isChampion = true;
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
        return $data;
    }

    /**
     * 获取联赛射手排行
     */
    public function getFbUnionArcher() {
        $id = 75;
        if (!$data = S('M_'.$id.'_fbUnionArcher')) {
            $data = D("NewBase")->getFbUnionArcher($id);
            S('M_'.$id.'_fbUnionArcher', json_encode($data), 60 * 20);
        }
        return $data;
    }

}