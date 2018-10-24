<?php
/**
 * 推荐管理
 * @author huangjiezhen <418832673@qq.com> 2015.11.27
 */
use Think\Tool\Tool;
class UserGambleController extends HomeController
{
    public function index()
    {
        echo '这里是推荐管理';
    }

    //关注的比赛
    public function followGame()
    {
        //获取我关注的人
        $followUser = M('followUser')->where(['user_id'=>is_login()])->select();
        foreach ($followUser as $key => $value) {
            $followId[] = $value['follow_id'];
        }
        //获取我关注的人今天推荐的比赛
        $where['user_id'] = ['in',$followId];
        $blockTime   = getBlockTime(1,$gamble=true);
        $where['create_time'] = ['between',[$blockTime['beginTime'],$blockTime['endTime']]];
        $gamble = $this->_list(D('GambleView'),$where,12,"id desc",'','',"/UserGamble/followGame/p/%5BPAGE%5D.html");
        $gamble = HandleGamble($gamble);
        $user_id = is_login();
        foreach ($gamble as $k => $v) {
            if($v['user_id'] != $user_id && $v['result'] == 0){
                //是否已被查看
                $gamble[$k]['is_check'] = M('quizLog')->where(['user_id'=>$user_id,'gamble_id'=>$v['id'],'game_type'=>1])->getField('id');
            }
        }
        $this->assign('gamble',$gamble);
        $this->position = '个人中心';
        
        $this->display();
    }

    //我的关注
    public function followUser()
    {
        $map            = $this->_search('followUser');
        $map['user_id'] = is_login();
        $list           = $this->_list(D('followUser'),$map,12,"follow_time desc",'','',"/UserGamble/followUser/p/%5BPAGE%5D.html");
        foreach ($list as $k => $v) {
            //获取今天推荐数量
            $blockTime = getBlockTime(1, $gamble = true);
            $list[$k]['gambleCount']   = M('gamble')->where(['user_id'=>$v['follow_id'],'create_time'=>['between',[$blockTime['beginTime'], $blockTime['endTime']]]])->count();
            //获取被关注人足球胜率
            $list[$k]['footballWin']   = $this->CountWinrate($v['follow_id']);
            //获取被关注人篮球胜率
            $list[$k]['basketballWin'] = $this->CountWinrate($v['follow_id'],2);
            $userInfo = M('FrontUser')->where(array('id'=>$v['follow_id']))->field('nick_name,head')->find();
            //获取被关注人昵称
            $list[$k]['nickname']      = $userInfo['nick_name'];
            //获取被关主人头像
            $list[$k]['face'] = FrontUserFace($userInfo['head']);
        }
        $this->assign('list',$list);
        $this->position = '个人中心';
        
        $this->display();
    }

    //我的粉丝
    public function myFans()
    {
        $map              = $this->_search('followUser');
        $map['follow_id'] = is_login();
        $list             = $this->_list(D('followUser'),$map,12,"follow_time desc",'','',"/UserGamble/myFans/p/%5BPAGE%5D.html");
        foreach ($list as $k => $v) {
            //获取今天推荐数量
            $blockTime = getBlockTime(1,$gamble=true);
            $where = ['user_id'=>$v['user_id'],'create_time'=>['between',[$blockTime['beginTime'],$blockTime['endTime']]]];
            $list[$k]['gambleCount']   = M('gamble')->where($where)->count();
            //获取关注人足球胜率
            $list[$k]['footballWin']   = $this->CountWinrate($v['user_id']);
            //获取关注人篮球胜率
            $list[$k]['basketballWin'] = $this->CountWinrate($v['user_id'],2);
            $userInfo = M('FrontUser')->where(array('id'=>$v['user_id']))->field('nick_name,head')->find();
            //获取关注人昵称
            $list[$k]['nickname']      = $userInfo['nick_name'];
            //获取关主人头像
            $list[$k]['face'] = FrontUserFace($userInfo['head']);
        }
        //我关注的人
        $followIdArr = M("FollowUser")->where(array('user_id'=>is_login()))->field("follow_id")->select();
        foreach ($followIdArr as $key => $value) {
            $followIds[] = $value['follow_id'];
        }
        $this->assign('followIds',$followIds);
        $this->assign('list',$list);
        $this->position = '个人中心';
        
        $this->display();
    }

    //足球推荐记录
    public function gambleFtball()
    {
        $id = is_login();
        $gamble_type = I('gamble_type') ? : 1;
        //获取用户等级
        $userLv = M('FrontUser')->field("lv,lv_bet")->where(['id'=>$id])->find();
        $lv = $gamble_type == 1 ? $userLv['lv'] : $userLv['lv_bet'];
        $this->assign('lv',$lv);
        //生成查询条件
        $map  = $this->_search("gamble");
        $map['user_id'] = $id;
        $map['play_type'] = $gamble_type == 1 ? ['in',[1,-1]] : ['in',[2,-2]];
        //获取列表
        if($gamble_type == 1){
            $url = "/UserGamble/gambleFtball/p/%5BPAGE%5D.html";
        }else{
            $url = "/UserGamble/gambleFtball/gamble_type/{$gamble_type}/p/%5BPAGE%5D.html";
        }
        $list = $this->_list(D("gambleView"),$map,10,"id desc",'','',$url);
        $list = HandleGamble($list);
        foreach ($list as $k => $v) {
            if($gamble_type == 2){
                $list[$k]['bet_code'] = M('fbBetodds')->where(array('game_id'=>$v['game_id']))->getField('bet_code');
            }
        }
        //统计用户推荐足球的赢、平、输的场数
        $resultArr = $this->get_gamble_result(1,'',$gamble_type);
        $this->assign('resultArr',$resultArr);
        //连胜记录
        $winning = D('GambleHall')->getWinning($id,1,0,$gamble_type,0);
        $this->assign('winning',$winning);
        //近十场足球推荐结果
        $TenGamble = $this->getTenGamble($id,1,$gamble_type);
        $this->assign('TenGamble',$TenGamble);
        //周推荐记录
        $footWeek = $this->CountWinrate($id,1,1,true,false,0,$gamble_type);
        $this->assign('footWeek',$footWeek);
        //月推荐记录
        $footMonth = $this->CountWinrate($id,1,2,true,false,0,$gamble_type);
        $this->assign('footMonth',$footMonth);
        //季推荐记录
        $footSeason = $this->CountWinrate($id,1,3,true,false,0,$gamble_type);
        $this->assign('footSeason',$footSeason);
        $this->assign('list',$list);
        $this->position = '个人中心';
        
        $this->display();
    }

    //篮球推荐记录
    public function gambleBktball()
    {
    	$id = is_login();
        //获取用户等级
        $lv = M('FrontUser')->where(['id'=>$id])->getField('lv_bk');
        $this->assign('lv',$lv);
        //生成查询条件
        $map  = $this->_search("gamblebk");
        $map['user_id'] = $id;
        //获取列表
        $list = $this->_list(D("gamblebkView"),$map,10,"id desc",'','',"/UserGamble/gambleBktball/p/%5BPAGE%5D.html");
        $list = HandleGamble($list);
        //统计用户推荐篮球的赢、平、输的场数
		$this->assign('resultArr',$this->get_gamble_result(2));
        //连胜记录
        $winning = D('GambleHall')->getWinning($id,2,0,1,0);
        $this->assign('winning',$winning);
        //近十场蓝球推荐结果
        $TenGamble = $this->getTenGamble($id,2);
        $this->assign('TenGamble',$TenGamble);
        //周推荐记录
        $footWeek = $this->CountWinrate($id,2,1,true);
        $this->assign('footWeek',$footWeek);
        //月推荐记录
        $footMonth = $this->CountWinrate($id,2,2,true);
        $this->assign('footMonth',$footMonth);
        //季推荐记录
        $footSeason = $this->CountWinrate($id,2,3,true);
        $this->assign('footSeason',$footSeason);
        $this->assign('list',$list);
        $this->position = '个人中心';
        
        $this->display();
    }

    //查看推荐记录
    public function adviseTrade()
    {
        $id               = is_login();
        $gameType         = I('gameType') ? I('gameType') : 1; 
        //生成查询条件
        $map              = $this->_search("quizLog");
        $map['user_id']   = $id;
        $map['game_type'] = $gameType;
        //获取列表
        $Model = $gameType == 1 ? D('quizLog') : D('quizLogBk'); 
        $list = $this->_list($Model,$map,15,"log_time desc",'','',"/UserGamble/adviseTrade/gameType/{$gameType}/p/%5BPAGE%5D.html");
        if($gameType == 1) //足球时获取新旧表一起的数据
        {
            foreach ($list as $k => $v)  
            {
                $gamble = D('GambleHall')->getGambleInfo($v['gamble_id']);
                $list[$k]['play_type']   = $gamble['play_type'];
                $list[$k]['chose_side']  = $gamble['chose_side'];
                $list[$k]['handcp']      = $gamble['handcp'];
                $list[$k]['odds']        = $gamble['odds'];
                $list[$k]['result']      = $gamble['result'];
                $list[$k]['tradeCoin']   = $gamble['tradeCoin'];
                $list[$k]['analysis']    = $gamble['desc'];
                $list[$k]['union_color'] = $gamble['union_color'];
            }
        }
        //处理数据
        $list = HandleGamble($list,0,false,$gameType);
        $this->assign('list',$list);
        $this->position = '个人中心';
        
        $this->display();
    }

}

 ?>