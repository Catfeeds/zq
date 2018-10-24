<?php
/**
 * 首页
 *
 * @author chenzj <443629770@qq.com>
 *
 * @since  2016-04-19
 */
use Think\Tool\Tool;
class SportteryController extends CommonController {
    
    /**
     * 首页
     * @User liangzk <liangzk@qc.com>
     * $dataTime 2016-11-2
     */
    public function index()
    {
        if(!isMobile()){
            redirect(U("/@sporttery"));
        }

        cookie('redirectUrl',__SELF__);
		
        //滚动的banner
		if (!$banner = S('IndexBanner_m'.MODULE_NAME))
		{
			$banner = Tool::getAdList(47, 5,4) ?: '';
			S('IndexBanner_m'.MODULE_NAME,$banner,1*60);
		}
		$this->assign('banner',$banner);//首页广告位
        A('Mobile/Nav')->navHead('Sporttery');
        $this->assign('titleHead','专家说彩');

        //每日精选
        $jxData = $this->getJxData();
        $this->assign('jxData',$jxData);

        //特邀专家
        $tyData = $this->getTyData();
        if(!$tyData) $tyData = '';
        $this->assign('tyData',$tyData);
        //获取文章列表
        $newList = $this->getNewList();
        $this->assign('newList',$newList);
        //美女直播列表
        $liveList = A('Home/LiveRoom')->offLinePage('@m');
        $this->assign('liveList',$liveList);
        $this->assign('showLogin',1);
        $this->display();
    }

    //每日精选
    public function getJxData(){
        if(!$jxData = S('m_jxData'.MODULE_NAME)){
            $yazhiHot = M('FrontUser')->field("id as user_id,nick_name,head,fb_ten_gamble as tenWin,1 as type")->where("lv > 0 and status = 1 and fb_ten_gamble >= 7")->order("fb_ten_gamble desc,lv desc")->limit("24")->select();
            $ouzhiHot = M('FrontUser')->field("id as user_id,nick_name,head,fb_ten_bet as tenWin,2 as type")->where("lv_bet > 0 and status = 1")->order("fb_ten_bet desc,lv_bet desc")->limit("24")->select();
            //判断是否有参加竞猜
            $yazhiUserArr  = array_column($yazhiHot,'user_id');
            $ouzhiUserArr  = array_column($ouzhiHot,'user_id');
            $selectUserArr = array_merge($yazhiUserArr,$ouzhiUserArr);
            $blockTime = getBlockTime(1,true);
            $userArr = M('Gamble')->master(true)
                ->where(['user_id'=>['IN',$selectUserArr],'result'=>0,'create_time'=>['between',[$blockTime['beginTime'],$blockTime['endTime']]]])
                ->group('user_id')
                ->getField('user_id',true);
            foreach ($yazhiHot as $k => $v) {
                $yazhiHot[$k]['head'] = frontUserFace($v['head']);
                //判断是否有推荐
                $yazhiHot[$k]['today_gamble'] = $today_gamble[] = in_array($v['user_id'],$userArr) ? 1 : 0;
            }
            foreach ($ouzhiHot as $k => $v) {
                $ouzhiHot[$k]['head'] = frontUserFace($v['head']);
                //判断是否有推荐
                $ouzhiHot[$k]['today_gamble'] = in_array($v['user_id'],$userArr) ? 1 : 0;
            }
            //将欧赔欧赔数组合并
            $data = array_merge($yazhiHot,$ouzhiHot);
            $sort1 = $sort2 = $sort3 = [];
            foreach ($data as $k => $v) {
                $sort1[] = $v['today_gamble'];
                $sort2[] = $v['tenWin'];
                $sort3[] = $v['is_robot'];
            }
            //排序：发布推荐>命中率>真实用户>机器人
            array_multisort($sort1,SORT_DESC ,$sort2, SORT_DESC, $sort3,SORT_ASC , $data);
            $data = array_slice($data,0,30);
            $jxData = array_chunk($data, 5);
            S('m_jxData'.MODULE_NAME,$jxData,300);
        }
        
        return $jxData;
    }
    /**
     * 高命中，连胜多：换一批
     */
    public function getIndexMore(){
        $game_type    = 1;
        $user1        = 0;
        $gamble_type1 = 0;

        $res = D('GambleHall')->getIndexUser($game_type, $user1, $gamble_type1);

        if($res) {
            $model = $game_type == 1 ? M('Gamble') : M('Gamblebk');
            foreach ($res as $k => $v) {
                //最新的未结算的推荐，不只是当天
                $play_type = $v['gamble_type'] == 1 ? ($game_type == 1 ? [-1, 1] : [1, 2, -1, -2]) : [-2, 2];
                $one = $model->where(['user_id' => $v['user_id'], 'play_type' => ['in', $play_type], 'result' => 0])->order('id desc')->find();

                if ($one) {//若当天推荐存在
                    $res[$k]['todayHomeName'] = explode(',', $one['home_team_name']);//当天推荐主队名称
                    $res[$k]['todayAwayName'] = explode(',', $one['away_team_name']);//当天推荐客队名称
                } else {
                    $res[$k]['todayHomeName'] = [];//当天推荐主队名称
                    $res[$k]['todayAwayName'] = [];//当天推荐客队名称
                }
                unset($one);
            }
        }

        return $res;
    }
    
    /**
     * 首页的大咖推荐列表--滑动加载
     */
    public function getHightList()
	{
		
		if (!$masterGamble = S('IndexMasterGamble'.MODULE_NAME))
		{
			$masterGamble = D('GambleHall')->masterGamble();
			S('IndexMasterGamble'.MODULE_NAME,$masterGamble,5*60);
		}
		
		//获取赛事id
		$gambleIdArr = $quizGameArr = array();
		foreach ($masterGamble as $k => $v)
		{
			$gambleIdArr[] = $v['gamble_id'];
			
			$masterGamble[$k]['union_name'] = $v['union_name'][0];
			$masterGamble[$k]['home_team_name'] = $v['home_team_name'][0];
			$masterGamble[$k]['away_team_name'] = $v['away_team_name'][0];
			$masterGamble[$k]['gDate'] = date('m/d',strtotime($v['game_date'])).' '.$v['game_time'];//比赛时间
		}
		if (!empty($masterGamble))  $masterGamble = HandleGamble($masterGamble,0,true);
		
		$userId = is_login();
		//获取用户查看的足球推荐
		if ($userId)
		{
			$quizGameArr = M('QuizLog')->where(['gamble_id'=>['IN',$gambleIdArr],'user_id'=>$userId,'game_type'=>1])->getField('gamble_id',true);
			if (empty($quizGameArr)) $quizGameArr = array();
		}
		
		foreach ($masterGamble as $key => $value)
		{
			//判断是否查看---1：查看或已结算或当前用户发布 0：还没查看
			if (in_array($value['gamble_id'],$quizGameArr) || $value['result'] != 0 || $value['user_id'] == $userId)
			{
				$masterGamble[$key]['is_trade'] = 1;
			}
			else
			{
				$masterGamble[$key]['chose_side'] = null;
				$masterGamble[$key]['handcp'] = null;
				$masterGamble[$key]['odds'] = null;
				$masterGamble[$key]['Answer'] = null;
				$masterGamble[$key]['desc'] = null;
				$masterGamble[$key]['is_trade'] = 0;
			}
		}

		return $masterGamble;
	}
	/**
	 * 超值高手
	 */
	public function superMaster()
	{
		if (!$totalArr = S('IndexSuperMaster'.MODULE_NAME))
		{
			$blockTime  = getBlockTime(1, $gamble = true);//获取赛程分割日期的区间时间
			$gambleData = D('GambleHall')->superMasterData(1, 15, $blockTime['beginTime'], $blockTime['endTime']);//亚盘
			$betData    = D('GambleHall')->superMasterData(2, 15, $blockTime['beginTime'], $blockTime['endTime']);//竞彩
			
			//不够就选前一天
			if(count($gambleData) < 15){
				$lastUser1 = [];
				foreach($gambleData as $uk => $uv){
					$lastUser1[] = $uv['user_id'];
				}
				
				$num = 15 - count($gambleData);
				$gambleData1 = D('GambleHall')->superMasterData(1, $num, strtotime('-1 day', $blockTime['beginTime']), strtotime('-1 day', $blockTime['endTime']), $lastUser1);//亚盘
				$gambleData  = array_merge($gambleData, $gambleData1);
			}
			
			if(count($betData) < 15){
				$lastUser2 = [];
				foreach($betData as $bk => $bv){
					$lastUser12[] = $bv['user_id'];
				}
				
				$num = 15 - count($betData);
				$betData1 = D('GambleHall')->superMasterData(2, $num, strtotime('-1 day', $blockTime['beginTime']), strtotime('-1 day', $blockTime['endTime']), $lastUser2);//亚盘
				$betData  = array_merge($betData, $betData1);
			}
			
			//重新排序
			$totalArr  = array_merge($gambleData, $betData);
			
			if ($totalArr) {
				$today  = $curr_victs1 = $tenGambleRate1 = $weekPercnet1 = $tradeCoin1 = $timeSort1 = array();
				$before = $curr_victs2 = $tenGambleRate2 = $weekPercnet2 = $tradeCoin2 = $timeSort2 = array();
				
				foreach ($totalArr as $k => $v) {
					//当天的分组，未结算
					if($v['result'] == 0){
						$curr_victs1[]    = $v['curr_victs'];
						$tenGambleRate1[] = $v['tenGambleRate'];
						$weekPercnet1[]   = $v['weekPercnet'];
						$tradeCoin1[]     = $v['tradeCoin'];
						$timeSort1[]      = $v['create_time'];
						$today[]          = $v;
					}else{
						$curr_victs2[]    = $v['curr_victs'];
						$tenGambleRate2[] = $v['tenGambleRate'];
						$weekPercnet2[]   = $v['weekPercnet'];
						$tradeCoin2[]     = $v['tradeCoin'];
						$timeSort2[]      = $v['create_time'];
						$before[]         = $v;
					}
					
					unset($totalArr[$k]['tenGambleRate']);
				}
				
				//排序，分组排序，当天时间优先，按连胜 > 10中几 > 周胜率 > 价格 > 发布时间
				array_multisort($curr_victs1, SORT_DESC, $tenGambleRate1, SORT_DESC, $weekPercnet1, SORT_DESC, $tradeCoin1, SORT_DESC, $timeSort1, SORT_DESC, $today);
				array_multisort($curr_victs2, SORT_DESC, $tenGambleRate2, SORT_DESC, $weekPercnet2, SORT_DESC, $tradeCoin2, SORT_DESC, $timeSort2, SORT_DESC, $before);
				
				//合并
				$totalArr = array_merge($today, $before);
				unset($curr_victs1, $tenGambleRate1, $weekPercnet1, $tradeCoin1, $timeSort1, $today);
				unset($curr_victs2, $tenGambleRate2, $weekPercnet2, $tradeCoin2, $timeSort2, $before);
			}
			
			S('IndexSuperMaster'.MODULE_NAME, $totalArr, 60*5);
		}
		
		$userId = is_login();
		if($totalArr)
		{
			//获取赛事id
			$gambleIdArr = $quizGameArr = array();
			foreach ($totalArr as $k => $v)
			{
				$gambleIdArr[] = $v['gamble_id'];
				$totalArr[$k]['home_team_name'] = $v['home_team_name'][0];
				$totalArr[$k]['away_team_name'] = $v['away_team_name'][0];
				$totalArr[$k]['union_name'] = $v['union_name'][0];
				
				$totalArr[$k]['gDate'] = date('m/d',strtotime($v['game_date'])).' '.$v['game_time'];//比赛时间
				
				$totalArr[$k]['score'] = $v['result'] != 0 ? str_replace('-','：',$v['score']) : ' VS ';
			}
			
			$totalArr = HandleGamble($totalArr,0,true);
			//获取用户查看的足球推荐
			if ($userId)
			{
				$quizGameArr = M('QuizLog')->where(['gamble_id'=>['IN',$gambleIdArr],'user_id'=>$userId,'game_type'=>1])->getField('gamble_id',true);
				if (empty($quizGameArr)) $quizGameArr = array();
			}
			
			foreach ($totalArr as $key => $value)
			{
				//判断是否查看---1：查看 2：还没查看
				if (in_array($value['gamble_id'],$quizGameArr) || $value['user_id'] == $userId || $value['result'] != 0)
				{
					$totalArr[$key]['is_trade'] = 1;
				}
				else
				{
					$totalArr[$key]['chose_side'] = null;
					$totalArr[$key]['handcp'] = null;
					$totalArr[$key]['odds'] = null;
					$totalArr[$key]['Answer'] = null;
					$totalArr[$key]['desc'] = null;
					$totalArr[$key]['is_trade'] = 0;
				}
			}
		}

		$this->ajaxReturn(['status'=>1,'list' => empty($totalArr) ? null : $totalArr]);
	}
    //
    public function getInfoToIndex()
    {
        $publishModel = M('PublishList');

        $list = $publishModel->alias('pl')->field('pl.id,pl.img,pl.title,pl.source,c.name')
        	->join('LEFT JOIN qc_publish_class c ON c.id = pl.class_id')
            ->where(['pl.class_id' => ['IN',[6,54,55]], 'pl.status' => 1])
            ->where(['pl.app_time' => ['lt', strtotime('+1 day', strtotime('7:15'))]])
            ->group('pl.id')
            ->order('pl.app_time DESC, pl.update_time desc')
            ->limit(30)
            ->select();
        
        foreach ($list as &$v)
        {
            $v['img'] = @Think\Tool\Tool::imagesReplace($v['img']);
        }

        $this->success($list);
    }
    
    //足球比分
    public function fbscore()
    {
        //获取即时页面数据
//        $data=$this->get_curl(C('API_URL')."/fb",'key=no',C('CURL_DOMAIN'));
//        //置顶
//        if($data['status']===1){
//            array_unique($data['data']);
//            $league=array();
//            $_Now=cookie('M_Now');
//            $_Now_arr=array();
//            if ($_Now != null){
//                $_Now_arr=explode(',', $_Now);
//            }
//            foreach ($data['data'] as $k=>&$v){
//                //获取联赛,不要第四级以下
//                if(!empty($v[2][0]) && $v[4]<=3){
//                    if($v[4]==0){
//                        $league[1][$v[1]]=$v[2][0];
//                    }else{
//                        $league[$v[4]][$v[1]]=$v[2][0];
//                    }
//                }
//                //移除没有选的联赛
//                if ($_Now == null){
//                    if($v[4]>2){
//                        unset($data['data'][$k]);
//                    }
//                }else{
//                    if(!in_array($v[1], $_Now_arr)){
//                        unset($data['data'][$k]);
//                    }
//                }
//            }
//            //级别筛选
//            ksort($league);
//            if($_Now==null){
//                $sche='';
//                foreach ($league as $k=>$v){
//                    if($k<3){
//                        $va= array_flip($v);
//                        $sche.= implode(',', $va);
//                        $sche.=',';
//                        }
//                }
//                $sche=  substr($sche, 0,-1);
//                cookie('M_Now',$sche);
//            }
//            $res = $data['data'];
//            $status = $time = [];
//            foreach($res as $key =>$val)
//            {
//                $status[] = (int)$val[5];
//                $tmp = str_replace(':','',$val[6].$val[7]);
//                $time[] = $tmp;
//                $res[$key][] =$tmp;
//            }
//            array_multisort($status, SORT_DESC, $time, SORT_ASC, $res);
//            $this->assign('league',$league);
//            $this->assign('list',$res);
//        }
        //广告图片
//        $adver = @Think\Tool\Tool::getAdList(17,5,2);
//        foreach ($adver as $k => $v)
//        {
//            unset($adver[$k]['id']);
//        }
//        $this->assign('adver_list',$adver);
        $this->assign('language',cookie('language'));
        $this->assign('chioce',cookie('M_Now'));
        cookie('userUrl', __SELF__);
		cookie('detailsUrl', __SELF__);
        $this->assign('title','足球比分');
        //mqtt配置
        $mqtt = C('Mqtt');
        $this->assign('mqttOpt', $mqtt);
        $this->assign('mqttUser', setMqttUser());
        if(I('f') == 'no'){
            $this->display('fbscore_f');
            die;
        }
        $this->display();
    }
    
    //篮球即时比分
    public function instantBk(){
        $this->assign('title','篮球比分');
        //获取即时页面数据
        $data=$this->get_curl(C('API_BKURL')."/bk",'key=no',C('CURL_DOMAIN'));
        if($data['status']===1){
            $league=[];
            $_Now=cookie('NowBk');
            if ($_Now != null){
                $_Now_arr=explode(',', $_Now);
            }
            array_unique($data['data']);
            //获取联赛
            foreach ($data['data'] as $k=>&$v){
                if(!empty($v[2][0] )){
                    $league[$v[1]]=$v[2][0];
                }
                //移除没有选的联赛
                if ($_Now != null){
                    if(!in_array($v[1], $_Now_arr)){
                        unset($data['data'][$k]);
                    }
                }
                
                $opHomeScore = explode('-',$v[24]);//主队加时比分
                $opAwayScore = explode('-',$v[25]);//客队加时比分
                if (count($opHomeScore) > count($opAwayScore))
                {
                    array_push($opAwayScore,0);
                }
                elseif (count($opHomeScore) < count($opAwayScore))
                {
                    array_push($opHomeScore,0);
                }
                elseif (empty($opHomeScore) && empty($opAwayScore) && $v[23] > 0)
                {
                    $opHomeScore = [0];
                    $opAwayScore = [0];
                }
                $v[24] = $opHomeScore;//主队加时比分
                $v[25] = $opAwayScore;//客队加时比分
          
            }
            if($_Now==null){
                $sche='';
                foreach ($league as $kk=>$vv){
                        $sche.= $kk.',';
                }
                $sche=  substr($sche, 0,-1);
                cookie('NowBk',$sche);
            }
            //级别筛选
            ksort($league);
            $this->assign('league',$league);
        }
 
        $this->assign('chioce',cookie('NowBk'));
        $this->assign('language',cookie('language'));
        $this->assign('list',$data['data']);
        cookie('userUrl', __SELF__);
        cookie('detailsUrl', __SELF__);
        $this->display();
    }
    
    public function ScoreInstant(){
        $data=$this->get_curl(C('API_URL')."/change",'key=no',C('CURL_DOMAIN'));
        
        if($data['status']==0 || empty($data['data'])){
            $this->error("没有数据！");
        }
        $this->success($data['data']);
    }
    public function goal(){
        $data=$this->get_curl(C('API_URL')."/goal",'id=3&key=no',C('CURL_DOMAIN'));
        if($data['status']==0 || empty($data['data'])){
            $this->error("没有数据！");
        }
        $this->success($data['data']);
    }
    
    public function BkScoreChange(){
        //获取即时页面数据
        $data=$this->get_curl(C('API_BKURL')."/bkchange",'key=no',C('CURL_DOMAIN'));
        if($data['status']==0 || empty($data['data'])){
            $this->error("没有数据！");
        }
        $this->success($data['data']);
    }
    /**
     * 篮球今日赔率变化数据
     * @User liangzk <liangzk@qc.com>
     * $DateTime 2016-10-281:56
     * @return json ----具体查看全球体育网接口文档_赛事数据相关_篮球
     */
    public function bkGoalChange()
    {
        $data=$this->get_curl(C('API_BKURL')."/bkodds",'companyId=3&key=no',C('CURL_DOMAIN'));//取皇冠的数据
        if($data['status']==0 || empty($data['data'])){
            $this->error("没有数据！");
        }
        foreach ($data['data'] as $k => $v)
        {
            $v[0] = number_format($v[0],2);
            $v[2] = number_format($v[2],2);
            $v[3] = number_format($v[5],2);
            $v[5] = number_format($v[5],2);
        }
        $this->success($data['data']);
    }

    //特邀专家
    public function getTyData()
    {
        if(!$expertList = S('expertList')) {
            $blockTime = getBlockTime(1, true);
            $where['p.user_id']  = ['gt', 0];
            $where['p.status']   = 1;
            $where['p.app_time'] = ['between', [strtotime('-6 day', $blockTime['endTime']), $blockTime['endTime']]];
            $where['u.status']   = 1;
            $where['u.is_expert'] = 1;
            $expertList = (array)M('PublishList p')->field('u.id as user_id, u.nick_name, u.head as face, u.descript')
                ->join('left join qc_front_user as u on p.user_id = u.id')
                ->where($where)->group('p.user_id')->order('max(p.app_time) desc')->limit(12)->select();

            if($expertList){
                foreach ($expertList as $k => &$v) {
                    $v['face'] = frontUserFace($v['face']);

                    if(iosCheck()){
                        $v['nick_name'] = str_replace(C('filterNickname'), C('replaceWord'), $v['nick_name']);
                        $v['descript']  = str_replace(C('filterNickname'), C('replaceWord'), $v['descript']);
                    }
                }

                S('expertList', $expertList, 60 * 30);
            }
        }

        //实时屏蔽过滤
        if(iosCheck()) {
            foreach ($expertList as $k => &$v) {
                $v['nick_name'] = str_replace(C('filterNickname'), C('replaceWord'), $v['nick_name']);
                $v['descript'] = str_replace(C('filterNickname'), C('replaceWord'), $v['descript']);
            }
        }
        $expertList = array_chunk($expertList, 4);
        return $expertList;
    }

    //获取文章列表
    public function getNewList(){
        //文章列表
        $classArr = getPublishClass(0);
        $where['status'] = 1;
        $where['class_id'] = ['in',[73,10,54,55,62]];
        $class_name = [73=>'说彩',10=>'独家',54=>'竞彩',55=>'北单',62=>'秘籍'];
        $list = M('PublishList')->field('id,title,click_number,img,add_time,class_id')->where($where)->order('add_time desc')->limit(15)->select();
        foreach($list as $key=>$val)
        {
            $list[$key]['img'] = newsImgReplace($val);
            $list[$key]['time'] = date('Y-m-d', $val['add_time']);
            $list[$key]['url'] = mNewsUrl($val['id'],$val['class_id'],$classArr);
            $list[$key]['click_number'] = addClickConfig(1, $val['class_id'],$val['click_number'], $val['id']);
            $list[$key]['class_name'] = $class_name[$val['class_id']];
        }
        $new = array_chunk($list, 5);
        //获取美女图集
        //获取美女图集下所有分类
        $class_id = M('GalleryClass')->where(['pid'=>42,'status'=>1])->getField('id',true);
        $class_id[] = 42;
        $map['status'] = 1;
        $map['class_id'] = ['in',$class_id];
        $photoList = M('Gallery')->field('id,class_id,title,img_array,click_number,like_num,class_id')->where($map)->order('add_time desc')->limit(6)->select();
        shuffle($photoList);
        foreach ($photoList as $key => $val) {
            $photoList[$key]['url'] = U('/photo/'.$val['id'].'@m');
            $photoList[$key]['imgTotal'] = count(json_decode($val['img_array'], true));
            $photoList[$key]['img'] = setImgThumb(json_decode($val['img_array'], true)[1], '240');
            $photoList[$key]['click_number'] = addClickConfig(1, $val['class_id'],$val['click_number'], $val['id']);
            unset($photoList[$key]['img_array']);
        }
        $photo = array_chunk($photoList, 3);
        $data = [
            $new[0],
            $photo[0],
            $new[1],
            $photo[1],
            $new[2],
        ];
        return $data;


    }


    //ajax获取赛事列表
    public function getGameList(){
        //获取即时页面数据
        $data=$this->get_curl(C('API_URL')."/fb",'key=no',C('CURL_DOMAIN'));
        //置顶
        if($data['status']===1){
            array_unique($data['data']);
            $league=array();
            $_Now=cookie('M_Now');
            $_Now_arr=array();
            if ($_Now != null){
                $_Now_arr=explode(',', $_Now);
            }
            foreach ($data['data'] as $k=>&$v){
                //获取联赛,不要第四级以下
                if(!empty($v[2][0]) && $v[4]<=3){
                    if($v[4]==0){
                        $league[1][$v[1]]=$v[2][0];
                    }else{
                        $league[$v[4]][$v[1]]=$v[2][0];
                    }
                }

                switch($v[5]){
                    case 0:
                    case -10:
                    case -11:
                    case -12:
                    case -13:
                    case -14:
                    case -1:
                    case 2:
                        $v['gameStateText'] = C('game_state')[$v[5]];
                        break;
                    case 1:
                    case 3:
                    case 4:
                        $v['gameStateText'] = '<time>'.MshowGameTime($v[8],$v[5]).'</time>\'';
                        break;
                }
            }
            //级别筛选
            ksort($league);
            if($_Now==null){
                $sche='';
                foreach ($league as $k=>$v){
                    if($k<3){
                        $va= array_flip($v);
                        $sche.= implode(',', $va);
                        $sche.=',';
                    }
                }
                $sche=  substr($sche, 0,-1);
                cookie('M_Now',$sche);
            }
            $res = $data['data'];
            $status = $time = [];
            foreach($res as $key =>$val)
            {
                $status[] = (int)$val[5];
                $tmp = str_replace(':','',$val[6].$val[7]);
                $time[] = $tmp;
                $res[$key][] =$tmp;
            }
            array_multisort($status, SORT_DESC, $time, SORT_ASC, $res);
            $data['union'] = $league;
            $data['data'] = $res;
        }
        $this->ajaxReturn($data);
    }
}