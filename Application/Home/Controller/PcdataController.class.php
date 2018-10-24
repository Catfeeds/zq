<?php
/**
 +------------------------------------------------------------------------------
 * PC推荐接口控制器
 +------------------------------------------------------------------------------
 * Copyright (c) 2015 http://www.qqty.com All rights reserved.
 +------------------------------------------------------------------------------
 * @author Hmg <39383198@qq.com>
 +------------------------------------------------------------------------------
*/
use Think\Controller;
use Think\Tool\Tool;

class PcdataController extends Controller
{
    /**
     * 当天最新赔率数据
     * @return json 当日赛事数据
     */
    public function odds()
    {
        $pcData = new \Home\Services\PcdataService();
        $aRes = $pcData->oddsDataDiv();
        $this->ajaxReturn($res);
    }

    /**
     * web端 赛事实时数据change文件内容
     * @return json
     */
    public function change()
    {
        $pcData = new \Home\Services\PcdataService();
        $sRes = $pcData->getChange();
        echo $sRes;
        exit;
    }

    /**
     * web端 赛事实时数据数据库内容
     * @return json
     */
    public function changeTwo()
    {
        $pcData = new \Home\Services\PcdataService();

        $res = $pcData->getChangeB();
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 101;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        echo htmlspecialchars($_GET['jsoncallback']) . "(".json_encode($data).")";
    }

    /**
     * web端 赛事实时指数变化数据goal文件内容
     * @return json
     */
    public function goal()
    {
		$companyID = !empty($_REQUEST['id'])?$_REQUEST['id']:null;
        if(empty($companyID)) $this->error('参数错误',3);

        $pcData = new \Home\Services\PcdataService();
        $sRes = $pcData->getGoal($companyID);
        echo $sRes;
        exit;
    }

    /**
     * web端 赛事实时赔率指数变化数据库内容
     * @return json
     */
    public function goalTwo()
    {
        $companyID = !empty($_REQUEST['id'])?$_REQUEST['id']:null;
        if(empty($companyID)) $this->error('参数错误',3);

        $pcData = new \Home\Services\PcdataService();
        $res = $pcData->getGoalB($companyID);

        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 101;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        echo htmlspecialchars($_GET['jsoncallback']) . "(".json_encode($data).")";
    }

    /**
     * web端 赛事实时指数变化数据goal文件内容
     * @return json
     */
    public function goalById()
    {
        $companyID = !empty($_REQUEST['id'])?$_REQUEST['id']:3;
        $gameId = !empty($_REQUEST['gameId'])?$_REQUEST['gameId']:null;
        if(empty($companyID) || empty($gameId)) $this->error('参数错误',3);

        $pcData = new \Home\Services\PcdataService();
        $res = $pcData->getGoalById($gameId, $companyID);

        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 101;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

    /**
     * web端 赛事实时指数变化数据goal文件内容
     * @return json
     */
    public function oddsById()
    {
        $gameId = !empty($_REQUEST['gameId'])?$_REQUEST['gameId']:null;
        $type = !empty($_REQUEST['type'])? $_REQUEST['type'] : '2';
        if(empty($gameId)) $this->error('参数错误',3);

        $pcData = new \Home\Services\PcdataService();
        $res = $pcData->getOddsById($gameId,$type);

        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 101;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }

     /**
     * web端 足球竞彩赛事赔率变化数据
     * @return json
     */
    public function fbbetodds()
    {
        $blockTime = getBlockTime(1);
        $sql = "SELECT DISTINCT
                g.game_id,bet.home_odds,bet.draw_odds,bet.away_odds,bet.let_exp,bet.home_letodds,bet.draw_letodds,bet.away_letodds
            FROM __PREFIX__game_fbinfo g
            LEFT JOIN __PREFIX__union u ON g.union_id = u.union_id
            LEFT JOIN __PREFIX__fb_betodds bet ON bet.game_id = g.game_id
            WHERE
                g.status = 1
            AND gtime between {$blockTime['beginTime']} AND {$blockTime['endTime']}
            AND (u.is_sub < 3 or g.is_show =1)
            AND g.is_color = 1
            AND g.is_betting = 1";
        $game  = M()->query($sql);

        if($game === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 101;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $game;
        }
        echo htmlspecialchars($_GET['jsoncallback']) . "(".json_encode($data).")";
    }

    /**
     +------------------------------------------------------------------------------
     * 以下开始为app篮球接口
     +------------------------------------------------------------------------------
    */

    /**
     * web端 篮球赛事实时数据change
     * @return json
     */
    public function bkchange()
    {
        $pcData = new \Home\Services\PcdataService();
        //$res = $pcData->getNbachange();
        $res = $pcData->getBkchange();
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = null;
            $data['ErrorCode'] = 101;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        echo htmlspecialchars($_GET['jsoncallback']) . "(".json_encode($data).")";
    }

     /**
     * web端 篮球赛事赔率变化数据
     * @return json
     */
    public function bkodds()
    {
        $companyID = !empty($_REQUEST['cid'])?$_REQUEST['cid']:2;

        $pcData = new \Home\Services\PcdataService();
        $res = $pcData->getBkodds($companyID);
        if($res === false)
        {
            $data['status']  = 0;
            $data['data'] = [];
            $data['ErrorCode'] = 101;
        }
        else
        {
            $data['status']  = 1;
            $data['data'] = $res;
        }
        echo htmlspecialchars($_GET['jsoncallback']) . "(".json_encode($data).")";
    }


    /**
     +------------------------------------------------------------------------------
     * 其他接口
     +------------------------------------------------------------------------------
    */

    /**
     * 足球赛事直播匹配
     * @return json
     */
    public function liveGamePreg()
    {
        $url = "www.jrssports.com/Home/Capture/getGameList";
        $contents = Tool::url_get_contents($url);

        $arr = json_decode($contents,true);

        $GameFbinfo = M('GameFbinfo');
        $GameBkinfo = M('GameBkinfo');

        foreach($arr as $k=>$v)
        {
            $date = date('Y-m-d',$v['gameDate']);

            foreach($v['gamelist'] as $k2=>$v2)
            {
                if(strlen($v2['gtime']) < 9)
                {
                    $dateTime = $date .' '.$v2['gtime'];
                    $gtime = strtotime($dateTime);
                }else{
                    $gtime = $v2['gtime'];
                }

                if($v2['title'] == '足球')
                {
                    $map = [];
                    $map['gtime'] = array(array('gt',$gtime-600),array('lt',$gtime+600));
                    $sql = "select id,game_id from qc_game_fbinfo where gtime >=".($gtime-600)." and gtime <= ".($gtime+600)." and (home_team_name like '%".$v2['homeName']."%' or away_team_name like '%".$v2['awayName']."%' or away_team_name like '%".$v2['homeName']."%' or home_team_name like '%".$v2['awayName']."%')";
                    $res = M()->query($sql);

                    if(!empty($res))
                    {
                        $data['is_video'] = 1;
                        $a = $GameFbinfo->where('id='.$res[0]['id'])->save($data);
                    }else{
                        echo "足球查询失败:".$gtime."-".date('Y-m-d H:i',$gtime)."-".$v2['union']."-".$v2['homeName']."-".$v2['awayName']."<br/>";
                    }
                }
                else if($v2['title'] == '篮球')
                {
                    $map = [];
                    $map['gtime'] = array(array('gt',$gtime-600),array('lt',$gtime+600));
                    $sql = "select id,game_id from qc_game_bkinfo where gtime >=".($gtime-600)." and gtime <= ".($gtime+600)." and (home_team_name like '%".$v2['homeName']."%' or away_team_name like '%".$v2['awayName']."%' or away_team_name like '%".$v2['homeName']."%' or home_team_name like '%".$v2['awayName']."%')";
                    $res = M()->query($sql);

                    if(!empty($res))
                    {
                        $data['is_video'] = 1;
                        $a = $GameBkinfo->where('id='.$res[0]['id'])->save($data);
                    }else{
                        echo "篮球查询失败:".$gtime."-".date('Y-m-d H:i',$gtime)."-".$v2['union']."-".$v2['homeName']."-".$v2['awayName']."<br/>";
                    }
                }
            }
        }
    }
}