<?php
/**
 * 个人主页
 *
 * @author wangkaimao <527993759@qq.com>
 *
 * @since  2015-12-9
 */
use Think\Controller;
use Think\Tool\Tool;
class UserIndexController extends CommonController {
    //个人主页
    public function index()
    {
        C('HTTP_CACHE_CONTROL','no-cache,no-store');
        $id = I('user_id');
        if($id == ''){
            $this->_empty();
        }
        $game_type = I('game_type') ? : 1;
        $gamble_type = I('gamble_type') ? : 1;
        if(explode('?', $_SERVER['REQUEST_URI'])[1] == 'bet' && $game_type == 1){
            $gamble_type = 2;
            $_REQUEST['gamble_type'] = 2;
        }
        $user = M('FrontUser')->where("id=$id")->field('id,lv,lv_bk,lv_bet,nick_name,descript,head,is_expert,avder')->find();
        if($game_type==3 && $user['is_expert']!=1) $game_type = 1;
        $this->assign('game_type',$game_type);
        if(!$user){
            $this->_empty();
        }
        switch ($game_type)  //对应等级
        {
            case '1':
                $Lv = $gamble_type == 1 ? $user['lv'] : $user['lv_bet'];
                break;
            case '2':
                $Lv = $user['lv_bk'];
                break;
        }
        $this->assign('Lv', $Lv);
        $user['face'] = frontUserFace($user['head']);
        $user['number'] = M('FollowUser')->where("follow_id=$id")->count();//获取粉丝条数

        //查看粉丝信息
        $user_id = is_login();//获取会员登录id
        if($user_id)
        {
            $follow = M('FollowUser')->where(array('user_id'=>$user_id,'follow_id'=>$id))->find();
            $this->assign('follow',$follow);

            //已查看的推荐
            $gambleIdArr = array_map("array_shift", $history);
            $checkArr = M('quizLog')->master(true)->where(['user_id'=>$user_id,'gamble_id'=>['in',$gambleIdArr],'game_type'=>$game_type])->getField('gamble_id',true);
            $this->assign('checkArr',$checkArr);
        }


        if($game_type == 3)
        {
            //内容列表
            $where = array();
            $where['user_id'] = $id;
            $where['status'] = 1;
            $where['class_id'] = 10;
            $where['add_time'] = ['lt',time()];
            $list = $this->_list(M('PublishList'),$where,'5','add_time desc','id,title,remark,img,add_time,click_number','',"",2);
            foreach($list as $key=>$value)
            {
                if(!empty($value['img'])){
                    $list[$key]['img'] = Tool::imagesReplace($value['img']);
                }else{
                    //获取第一张图片
                    $list[$key]['img']  = Tool::getTextImgUrl(htmlspecialchars_decode($value['content']),false)[0] ?:SITE_URL.'www.'.DOMAIN.'/Public/Home/images/common/loading.png';
                }
                $list[$key]['add_time'] = date("Y-m-d H:i",$value['add_time']);
                $list[$key]['click_number'] = addClickConfig(1, $value['class_id'],$value['click_number'], $value['id']);
            }
            $this->assign("list",$list);
            $list_count = M("PublishList")->where(['user_id'=>$id,'class_id'=>10,'status'=>1])->count();
        }else{
            //足球/篮球推荐记录
            $gameModel = $game_type == 1 ? D('GambleView') : D('GamblebkView');
            //统计用户推荐的赢、平、输的场数(篮球或足球)
            $resultArr = $this->get_gamble_result($game_type,$id,$gamble_type);
            $this->assign('resultArr',$resultArr);
            //最近10场
            $tenArray = $this->getTenGamble($id,$game_type,$gamble_type);
            $this->assign('tenArray',$tenArray);
            //获取连胜
            $winning = D('GambleHall')->getWinning($id,$game_type,0,$gamble_type,0);
            $this->assign('winning',$winning);
            //周推荐记录
            $footWeek = $this->CountWinrate($id,$game_type,1,true,false,0,$gamble_type);
            $this->assign('footWeek',$footWeek);
            //月推荐记录
            $footMonth = $this->CountWinrate($id,$game_type,2,true,false,0,$gamble_type);
            $this->assign('footMonth',$footMonth);
            //季推荐记录
            $footSeason = $this->CountWinrate($id,$game_type,3,true,false,0,$gamble_type);
            $this->assign('footSeason',$footSeason);
            //推荐记录
            $map['user_id'] = $id;
            $map['play_type'] = $gamble_type == 1 ? ['in',[1,-1]] : ['in',[2,-2]];
            $history = $this->_list($gameModel,$map,15,'id desc','','',"",2);
            $history = HandleGamble($history);
        }
        $user['count'] = $list_count;
        $this->assign('user', $user);

        if($gamble_type == 2){
            //获取竞彩标记
            foreach ($history as $k => $v) {
                $gameIdArr[] = $v['game_id'];
            }
            $betCode = M('fbBetodds')->where(['game_id'=>['in',$gameIdArr]])->field('game_id,bet_code')->select();
            foreach ($history as $k => $v) {
                foreach ($betCode as $kk => $vv) {
                    if($v['game_id'] == $vv['game_id']){
                        $history[$k]['bet_code'] = $vv['bet_code'];
                    }
                }
            }
        }

        $this->assign('history',$history);
        if(!$rankingData = S('web_userindex_rank'))
        {
            //显示左侧足球排行榜
            $footRankWeek  = $this->getRankingData(1,1,null,true,10);
            $this->getBallRecord($footRankWeek);
            $footRankMonth  = $this->getRankingData(1,2,null,true,10);
            $this->getBallRecord($footRankMonth);
            $footRankSeason = $this->getRankingData(1,3,null,true,10);
            $this->getBallRecord($footRankSeason);
            //显示左侧足球竞彩排行榜
            $BettingWeek   = D('Common')->getRankBetting(1,1,null,10,true);
            $this->getBallRecord($BettingWeek,2);
            $BettingMonth  = D('Common')->getRankBetting(1,2,null,10,true);
            $this->getBallRecord($BettingMonth,2);
            $BettingSeason = D('Common')->getRankBetting(1,3,null,10,true);
            $this->getBallRecord($BettingSeason,2);
            $rankingData['footRankWeek']  = $footRankWeek;
            $rankingData['footRankMonth'] = $footRankMonth;
            $rankingData['footRankSeason']= $footRankSeason;
            $rankingData['BettingWeek']   = $BettingWeek;
            $rankingData['BettingMonth']  = $BettingMonth;
            $rankingData['BettingSeason'] = $BettingSeason;
            S('web_userindex_rank',json_encode($rankingData),86400);
        }

        $this->assign('footRankWeek',  $rankingData['footRankWeek']  );
        $this->assign('footRankMonth', $rankingData['footRankMonth'] );
        $this->assign('footRankSeason',$rankingData['footRankSeason']);
        $this->assign('BettingWeek',   $rankingData['BettingWeek']   );
        $this->assign('BettingMonth',  $rankingData['BettingMonth']  );
        $this->assign('BettingSeason', $rankingData['BettingSeason'] );

        //获取荣誉榜  只取每周一 每月一号 每季一号 春季（3.4.5）夏季（6.7.8）秋季（9.10.11）冬季（12.1.2）quarter
        $top_month = date('t', strtotime('-1 month'));//获取上月天数
        $where = "user_id = {$id} and ranking < 11 and ((FROM_UNIXTIME(UNIX_TIMESTAMP(end_date),'%d') = {$top_month} and dateType = 2) or (WEEKDAY(begin_date) = 0 and dateType = 1) or (right(begin_date,2) = 01 and dateType = 3))";
        $honor_roll = M('rankingList')->where($where)->field('dateType,gameType,begin_date,end_date,ranking')->order("id desc")->limit(5)->select();
        if($honor_roll){
            foreach ($honor_roll as $k => $v) {
                switch ($v['dateType']) {
                    case '1':
                        $honor_roll[$k]['explain'] = date('Y-m-d',strtotime($v['begin_date']))." 至 ".date('m-d',strtotime($v['end_date']))."周榜第<strong class='text-red'>".$v['ranking']."</strong>名";
                        break;
                    case '2':
                        $honor_roll[$k]['explain'] = date('Y年m月',strtotime($v['begin_date']))."月榜第<strong class='text-red'>".$v['ranking']."</strong>名";
                        break;
                    case '3':
                        $honor_roll[$k]['explain'] = date('Y年m月',strtotime($v['begin_date']))."-".date('Y年m月',strtotime($v['end_date']))."季榜第<strong class='text-red'>".$v['ranking']."</strong>名";
                        break;
                }
            }
            $this->assign('honor_roll',$honor_roll);
        }

        //添加浏览人数
        D('Common')->setFrontSeeNum($id,'web');
        $this->display();
    }
    
    //获取周/月/季记录
    public function getBallRecord(&$array,$gamble_type=1){
        foreach ($array as $k => $v) {
            //获取周记录
            $array[$k]['RankWeek'] = D('GambleHall')->CountWinrate($v['user_id'],$v['gameType'],1,true,false,0,$gamble_type);
            //获取月记录
            $array[$k]['RankMonth'] = D('GambleHall')->CountWinrate($v['user_id'],$v['gameType'],2,true,false,0,$gamble_type);
            //获取季记录
            $array[$k]['RankSeason'] = D('GambleHall')->CountWinrate($v['user_id'],$v['gameType'],3,true,false,0,$gamble_type);
        }
        return $array;
    }
}