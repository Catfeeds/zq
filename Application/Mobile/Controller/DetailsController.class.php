<?php
/**
 * 推荐
 *
 * @author chenzj <443629770@qq.com>
 *
 * @since  2016-04-19
 */
class DetailsController extends CommonController {
    public $_GameTime;
    protected function _initialize() {
        parent::_initialize();
        $scheid=I('get.scheid',0,"intval");
        if($scheid<1){
            $this->error("参数有误!");
        }
        if(!cookie('redirectUrl')){
            cookie('redirectUrl',U('Index/index'));
        }
//        $against=M("GameFbinfo")->field('union_id,union_name,game_state,gtime,home_team_id,home_team_name,away_team_id,away_team_name,score,game_date,game_time')->where(array('status'=>1,'game_id'=>$scheid))->find();
        $mongo = mongoService();
        $against = $mongo->select('fb_game',['game_id'=>$scheid],['union_id','union_name','game_state','gtime','home_team_id','home_team_name','away_team_id','away_team_name','score','game_date','game_time'])[0];
        $against['gtime'] = strtotime($against['gtime']);
        $against['game_date'] = date('Ymd',$against['gtime']);
        $against['game_time'] = date('H:i',$against['gtime']);
        $against['home_team_name']=  implode(',', $against['home_team_name']);
        $against['away_team_name']=  implode(',', $against['away_team_name']);
        $against['union_name']=  implode(',', $against['union_name']);
        unset($against['_id']);
        $this->_GameTime=$against['gtime'];
        $against=getTeamLogo($against);
        $against['home_team_name']=  explode(',', $against['home_team_name']);
        $against['away_team_name']=  explode(',', $against['away_team_name']);
        $against['union_name']=  explode(',', $against['union_name']);
        $user=session('user_auth');
        if($user){
            $this->assign('user_auth',$user);
        }
        $this->assign('against',$against);
        $this->assign('scheid',$scheid);
    }
    
    public function index() {
        $this->redirect('data');
    }
    //数据页面
    public function data() {
        $scheid=I('get.scheid',0,"intval");
        if($scheid<1){
            $this->error("参数有误!");
        }
        $data=$this->get_curl(C('API_URL')."/analysis","gameId=$scheid&key=no",C('CURL_DOMAIN'));
        $list=array();
        if($data['status']==1){
            foreach ($data['data'] as $v){
                $list[$v['name']]=$v['content'];
            }
            foreach($list['match_integral'] as $v){
                $list['integral'][$v[0]][]=$v;
            }
            foreach($list['match_three'] as $v){
                $list['three'][$v[0]][]=$v;
            }
            foreach($list['match_panlu'] as $v){
                $list['panlu'][$v[0]][]=$v;
            }
            $this->assign('data',$list);
        }
        $this->display();
    }
    //赔率-亚赔
    public function odds_asia(){
        $scheid=I('get.scheid',0,"intval");
        if($scheid<1){
            $this->error("参数有误!");
        }
        $data=$this->get_curl(C('API_URL')."/asianOdds ","gameId=$scheid&key=no",C('CURL_DOMAIN'));
        if($data['status']==1 && !empty($data['data'])){
//            foreach ($data['data'] as &$v){
//                $v[1]=sprintf('%.2f',$v[1]);
//                $v[3]=sprintf('%.2f',$v[3]);
//                if(strpos($v[2],'/')===false){
//                    $v['res_star']=$v[2];
//                }else{
//                    $arr=explode('/', $v[2]);
//                    $v['res_star']=($arr[0]+$arr[1])/2;
//                }
//                if(empty($v[4])){
//                    $v[4]=$v[1];
//                }
//                if(empty($v[5])){
//                    $v[5]=$v[2];
//                }
//                if(empty($v[6])){
//                    $v[6]=$v[3];
//                }
//                $v[4]=sprintf('%.2f',$v[4]);
//                if(strpos($v[5],'/')===false){
//                    $v['res_now']=$v[5];
//                }else{
//                    $arr=explode('/', $v[5]);
//                    $v['res_now']=($arr[0]+$arr[1])/2;
//                }
//                $v[6]=sprintf('%.2f',$v[6]);
//            }
//            var_Dump($data['data']);
            $this->assign('data',$data['data']['detailOdds']);
        }
        $this->assign('nav','odds');
        $this->display();
    }
    //赔率-欧赔
    public function odds_euro() {
        $scheid=I('get.scheid',0,"intval");
        if($scheid<1){
            $this->error("参数有误!");
        }
        $mService = mongoService();
        $map = ['game_id'=>(int)$scheid];
        $gmRes = $mService->select('fb_euroodds',$map,['game_id','euroodds_apk']);
        $this->assign('data',$gmRes[0]['euroodds_apk']);
        $this->assign('nav','odds');
        $this->display();
    }
    //赔率-大小
    public function odds_bigs() {
        $scheid=I('get.scheid',0,"intval");
        if($scheid<1){
            $this->error("参数有误!");
        }
        $data=$this->get_curl(C('API_URL')."/ballOdds ","gameId=$scheid&key=no",C('CURL_DOMAIN'));
        if($data['status']==1 && !empty($data['data'])){
//            foreach ($data['data'] as &$v){
//                $v[1]=sprintf('%.2f',$v[1]);
//                $v[3]=sprintf('%.2f',$v[3]);
//                if(strpos($v[2],'/')===false){
//                    $v['res_star']=$v[2];
//                }else{
//                    $arr=explode('/', $v[2]);
//                    $v['res_star']=($arr[0]+$arr[1])/2;
//                }
//                if(empty($v[4])){
//                    $v[4]=$v[1];
//                }
//                if(empty($v[5])){
//                    $v[5]=$v[2];
//                }
//                if(empty($v[6])){
//                    $v[6]=$v[3];
//                }
//                $v[4]=sprintf('%.2f',$v[4]);
//                if(strpos($v[5],'/')===false){
//                    $v['res_now']=$v[5];
//                }else{
//                    $arr=explode('/', $v[5]);
//                    $v['res_now']=($arr[0]+$arr[1])/2;
//                }
//                $v[6]=sprintf('%.2f',$v[6]);
//            }
            $this->assign('data',$data['data']['detailOdds']);
        }
        $this->assign('nav','odds');
        $this->display();
    }
    //事件-赛况
    public function event_case() {
        $scheid=I('get.scheid',0,"intval");
        if($scheid<1){
            $this->error("参数有误!");
        }
        $data=$this->get_curl("api103/appdata/textliving ","gameId=$scheid&key=no",C('CURL_DOMAIN'))['data'];
		if (!empty($data))
		{
			krsort($data);
		}
//        $data=M('FbTextliving')->where('game_id='.$scheid)->getField('json_str');
//
//        if($data){
//            $data=  json_decode($data,true);
//            krsort($data);
//            $this->assign('data',$data);
//        }
		$this->assign('data',$data);
		
        $this->assign('nav','event');
        $this->display();
    }
    //事件-技术
    public function event_technology() {
        $scheid=I('get.scheid',0,"intval");
        if($scheid<1){
            $this->error("参数有误!");
        }
        $data=$this->get_curl(C('API_URL')."/skill ","gameId=$scheid&key=no",C('CURL_DOMAIN'));
        if($data['status']==1){
        $array=array("先开球", "第一个角球", "第一张黄牌", "射门", "射正", "犯规", "角球", "角球(加时)", "任意球", "越位", "乌龙球", "黄牌", "黄牌(加时)", "红牌", "控球", "头球", "救球", "守门员出击", "丟球", "成功抢断", "阻截", "长传", "短传", "助攻", "成功传中", "第一个换人", "最后换人", "第一个越位", "最后越位", "换人", "最后角球", "最后黄牌", "换人(加时)", "越位(加时)", "射门不中", "中柱", "头球成功", "射门被挡", "铲球", "过人", "界外球", "传球", "传球成功");
            foreach ($data['data'] as &$v){
                $v['homerate']=round($v[1]/($v[1]+$v[2])*100);
                $v['awayrate']=round($v[2]/($v[1]+$v[2])*100);
                $v['title']=$array[$v[0]];
            }
            $this->assign('data',$data['data']);
        }
        $action=$this->get_curl(C('API_URL')."/detail ","gameId=$scheid&key=no",C('CURL_DOMAIN'));
        if($action['status']==1){
            $this->assign('action',$action['data']);
        }
        $this->assign('nav','event');
        $this->display();
    }
    //事件-阵容
    public function event_squad() {
        $scheid=I('get.scheid',0,"intval");
        if($scheid<1){
            $this->error("参数有误!");
        }
        $data=$this->get_curl(C('API_URL')."/lineup","nosign=appdata&gameId=$scheid",C('CURL_DOMAIN'));
        if($data['status']==1 && !empty($data['data'][0])){
            $list=array();
            foreach ($data['data'] as $k=>$v){
                foreach ($v as $key=>$val){
                    if($val[3]=='1'){
                        $list[0][$key][$k]=$val;
                    }else if($val[3]=='0'){
                        $list[1][$key][$k]=$val;
                    }
                }
            }
            foreach($list as &$j){
                foreach($j as &$p){
                    if(count($p)==1){
                        $arr=array(
                            0=>'',
                            1=>'',
                        );
                        if(isset($p[0])){
                            $p[1]=$arr;
                        }else{
                             $p[0]=$arr;
                        }
                        sort($p);
                    }
                }
            }
            $this->assign('list',$list);
        }
        $this->assign('nav','event');
        $this->display();
    }
    public function company() {
        $this->display();
    }

    //推荐
    public function odd_guess(){
        $scheid=I('get.scheid',0,"intval");
        if($scheid<1)
        {
			$this->redirect('Guess/index');
        }
        $game = M('fb_goal')->field('game_id,exp_value')->where(['company_id'=>3,'game_id'=>$scheid])->find();
        //获取即时数据
            if($game['exp_value'])
            {
                $odds = explode('^', $game['exp_value']);

                $whole      = explode(',', $odds[0]);  //全场
                if($whole[6] !='' || $whole[7] !='' || $whole[8] !='')
                {
                    //全场滚球
                    if($whole[6] == 100 || $whole[7] == 100 || $whole[7] == 100)
                    {
                        $game['fsw_exp_home'] = '';
                        $game['fsw_exp']      = '封';
                        $game['fsw_exp_away'] = '';
                    }else{
                        $game['fsw_exp_home'] = $whole[6];
                        $game['fsw_exp']      = $whole[7];
                        $game['fsw_exp_away'] = $whole[8];
                    }
                }
                elseif ($whole[3] !='' || $whole[4] !='' || $whole[5]!='')
                {
                    //全场即时
                    if($whole[3] == 100 || $whole[4] == 100 || $whole[5] == 100)
                    {
                        $game['fsw_exp_home'] = '';
                        $game['fsw_exp']      = '封';
                        $game['fsw_exp_away'] = '';
                    }else{
                        $game['fsw_exp_home'] = $whole[3];
                        $game['fsw_exp']      = $whole[4];
                        $game['fsw_exp_away'] = $whole[5];
                    }
                }

                $size       = explode(',', $odds[2]);  //大小
                if($size[6] !='' || $size[7] !='' || $size[8] !='')
                {
                    //大小滚球
                    if($size[6] == 100 || $size[7] == 100 || $size[8] == 100)
                    {
                        $game['fsw_ball_home'] = '';
                        $game['fsw_ball']      = '封';
                        $game['fsw_ball_away'] = '';
                    }else{
                        $game['fsw_ball_home'] = $size[6];
                        $game['fsw_ball']      = $size[7];
                        $game['fsw_ball_away'] = $size[8];
                    }
                }
                elseif ($size[3] !='' || $size[4] !='' || $size[5] !='')
                {
                    //大小即时
                    if($size[3] == 100 || $size[4] == 100 || $size[5] == 100)
                    {
                        $game['fsw_ball_home'] = '';
                        $game['fsw_ball']      = '封';
                        $game['fsw_ball_away'] = '';
                    }else{
                        $game['fsw_ball_home'] = $size[3];
                        $game['fsw_ball']      = $size[4];
                        $game['fsw_ball_away'] = $size[5];
                    }
                }
                foreach ($game as $k=>$v){
                    if(($v=='' || $v==null) && ($v['fsw_exp']!='封' || $v['fsw_ball']!='封')){
                        $field[]=$k;
                    }
                }
                unset($field['game_id']);
                unset($field['exp_value']);
                if($field){
                    $initial = M('GameFbinfo')->field($field)->where(['status'=>1,'game_id'=>$scheid])->find();
                    foreach($initial as $key=>$val){
                        $game[$key]=$val;
                    }
                }
				$game['is_betting'] = M('GameFbinfo')->where(['status'=>1,'game_id'=>$scheid])->getField('is_betting');
				
        }else{
            $game = M('GameFbinfo')->field('game_id,fsw_exp_home,fsw_exp,fsw_exp_away,fsw_ball_home,fsw_ball,fsw_ball_away,is_betting')->where(['status'=>1,'game_id'=>$scheid])->find();
        }
        //转换亚盘盘口
		$this->assign('data_fsw_exp',changeExp($game['fsw_exp']));
		$game['fsw_ball'] = changeExp($game['fsw_ball']);
        if($game['fsw_exp']>0){
            $game['fsw_exph'] = '-'.changeExp($game['fsw_exp']);
            $game['fsw_exp'] = '+'.changeExp($game['fsw_exp']);
        }else if($game['fsw_exp']<0){
            $game['fsw_exph'] = '+'.changeExp(substr($game['fsw_exp'],1));
        }else{
            $game['fsw_exph'] = changeExp($game['fsw_exp']);
        }
	
        //查看该用户是否推荐这场赛事
		$user_id = is_login();
		if($user_id)
		{
			$mygamble=M('Gamble')->where(['game_id'=>$scheid,'user_id'=>$user_id])->getField('play_type,chose_side');
			$this->assign('mygamble',$mygamble);
			//获取该用户还推荐多少场---亚盘
			$lvArr = M('FrontUser')->where(['id'=>$user_id])->field('lv,lv_bet')->find();
			$this->lv = $lvArr['lv'];
			$this->assign('lv_bet',$lvArr['lv_bet']);
		}
		list($this->normLeftTimes,$this->imptLeftTimes,$this->gameConf) = D('GambleHall')->gambleLeftTimes($user_id,1);
	
	
		//竞彩盘口、赔率
		if ($game['is_betting'] != 0)
		{
			$jcGame = M('fbBetodds')
				->field('game_id,bet_code,home_odds,draw_odds,away_odds,let_exp,home_letodds,draw_letodds,away_letodds')
				->where(['game_id'=>$scheid])
				->find();
			list($jcNormLeftTimes,$jcImptLeftTimes,$jcGameConf) = D('GambleHall')->gambleLeftTimes($user_id,1,2);
			//检查赔率是否为空--为空则不能推荐
			if ($jcGame['home_odds'] == '' && $jcGame['draw_odds'] == '' && $jcGame['away_odds'] == ''
				&& $jcGame['home_letodds'] == '' && $jcGame['draw_letodds'] == '' && $jcGame['away_letodds'] == '')
			{
				$game['is_betting'] = 0;
			}
			$this->assign('jcNormLeftTimes',$jcNormLeftTimes);
			$this->assign('jcGameConf',$jcGameConf);
			$this->assign('jcGame',$jcGame);
		}
		
		
        //是否已开赛
        if($this->_GameTime)
        {
           if(($this->_GameTime-time())<0)
           {
                $this->assign('stop',1);
           }
        }
        
        
        
		$ypHas=1;
		//判断是否能推荐--亚盘
        if($game['fsw_exp_home']=='' || $game['fsw_exp']=='' || $game['fsw_exp_away']=='' || $game['fsw_ball_home']=='' || $game['fsw_ball']=='' || $game['fsw_ball_away']=='')
        {
			$ypHas=0;
        }
        
		//该场赛事推荐统计
	
		if(!$ypRecommend = S( MODULE_NAME.'oddGuess_gamblePage_gambleCount_'.$scheid.'1'.'1'))
		{
			$ypRecommend = $this->getTenMaster($scheid,1,1);
			if($ypRecommend) S(MODULE_NAME.'oddGuess_gamblePage_gambleCount_'.$scheid.'1'.'1', $ypRecommend, 60*60);//亚盘
		}
	
		if(!$jcRecommend = S( MODULE_NAME.'oddGuess_gamblePage_gambleCount_'.$scheid.'1'.'2'))
		{
			$jcRecommend = $this->getTenMaster($scheid,1,2);
			if($jcRecommend) S(MODULE_NAME.'oddGuess_gamblePage_gambleCount_'.$scheid.'1'.'2', $jcRecommend, 60*60);//竞彩
		}
	
		$this->assign('ypRecommend',$ypRecommend);//亚盘
		$this->assign('jcRecommend',$jcRecommend);//竞彩
		
        $config=getWebConfig('fbConfig');
        $this->assign('config',$config);
        $this->assign('game',$game);//
        $this->assign('ypHas',$ypHas);////判断是否能推荐--亚盘
        $this->assign('data',D('Base')->getGambleRatio($scheid));//赛事各玩法推荐比率

		cookie('userUrl', __SELF__);
		cookie('redirectUrl',__SELF__);
		cookie('pageUrl', __SELF__);
		$this->display();
    }
	
	
	/**
	 * APP3.2的接口
	 * 赛事推荐统计 (hzl)
	 * @param $gameId
	 * @param int $gameType
	 * @param int $gambleType
	 * @param int $playType
	 * @return array
	 */
	public function getTenMaster($gameId, $gameType = 1, $gambleType = 1, $playType = 0)
	{
		//根据亚盘、竞彩玩法组装条件
		$jWhere = $wh = $userWeekGamble = $userids = $pageList = $userGamble = [];
		$time   = $gameType == 1 ? (10 * 60 + 32) * 60 : (12 * 60) * 60;
		if($gameType == 1){
			if($gambleType == 1){
				$lvField            = 'f.lv lv';
				$wh['result']       = ['IN',['1', '0.5', '2', '-1', '-0.5']];
				$wh['play_type']    = ['IN', [-1, 1]];
				$jWhere['play_type']= ['IN', [-1, 1]];
			}else{
				$lvField            = 'f.lv_bet lv';
				$wh['result']       = ['IN', [1, -1]];
				$wh['play_type']    = ['IN', [2, -2]];
				$jWhere['play_type']= ['IN', [2, -2]];
			}
		}
		
		if($playType)
			$wh['play_type'] = (int)$playType;
		
		//获取参与该场赛事推荐的用户
		$fields = ['g.id gamble_id','g.user_id', 'g.play_type','g.chose_side','g.handcp','g.odds', 'g.is_impt',
			'g.result', 'g.tradeCoin','g.desc', 'g.create_time','f.head face', 'f.nick_name', $lvField];
		
		$list = M('Gamble')->alias("g")
			->join("left join qc_front_user f on f.id = g.user_id")
			->field($fields)
			->where(['game_id' => $gameId, 'play_type' => $wh['play_type']])
			->group('g.user_id')
			->order('lv desc')
			->limit(10)
			->select();
		
		if($list){
			list($wBegin,$wEnd) = getRankBlockDate($gameType,1);//周
			list($mBegin,$mEnd) = getRankBlockDate($gameType,2);//月
			list($jBegin,$jEnd) = getRankBlockDate($gameType,3);//季
			
			$wBeginTime = strtotime($wBegin) + $time;
			$wEndTime   = strtotime($wEnd) + 86400 + $time;
			
			$mBeginTime = strtotime($mBegin) + $time;
			$mEndTime   = strtotime($mEnd) + 86400 + $time;
			
			$jBeginTime = strtotime($jBegin) + $time;
			$jEndTime   = strtotime($jEnd) + 86400 + $time;
			
			foreach($list as $vv){
				$userids[] = $vv['user_id'];
			}
			
			$wWhere['user_id']      = ['IN',$userids];
			$wWhere['result']       = ['IN', ['1', '0.5', '-1', '-0.5']];
			$wWhere['play_type']    = $jWhere['play_type'];
			$wWhere['create_time']  = ["between", [$wBeginTime, $wEndTime]];
			
			$userGamble = M('Gamble')
				->field('user_id, GROUP_CONCAT(result) as result')
				->where($wWhere)
				->group('user_id')
				->select();
			
			//是否查看过本赛程
			$userId  = is_login();
			if ($userId){
				$gambleId = (array)M('QuizLog')->where(['user_id' => $userId, 'game_id' => $gameId])->getField('gamble_id',true);
			}
			
			//周推荐
			$userWeekGamble = array();
			foreach ($userGamble as $key => $value)
			{
				$userWeekGamble[$value['user_id']] = $value['result'];
			}
			
			$lv = $weekSort = $monthSort = $seasonSort = $tenGamble = $sortTime = [];
			
			//月推荐
			$jWhere['result']       = ["IN", ['1', '0.5', '-1', '-0.5']];
			$jWhere['create_time']  = ["between", [$jBeginTime, $jEndTime ]];
			
			foreach ($list as $k => $v)
			{
				//用户信息
				$list[$k]['face']       = frontUserFace($v['face']);
				$list[$k]['is_trade']   = in_array($v['gamble_id'], $gambleId) ? '1' : '0';
				$list[$k]['desc']       = (string)$pageList[$k]['desc'];
				
				//周胜率计算
				$wWin = $wHalf = $wTransport = $wDonate = 0;
				$resultArr = explode(',', $userWeekGamble[$v['user_id']]);
				
				foreach($resultArr as $resultV){
					if($resultV == '1')     $wWin++;
					if($resultV == '0.5')   $wHalf++;
					if($resultV == '-1')    $wTransport++;
					if($resultV == '-0.5')  $wDonate++;
				}
				$list[$k]['weekPercnet']    = (string)getGambleWinrate($wWin, $wHalf, $wTransport, $wDonate);
				
				
				//月、季胜率计算
				$jWhere['user_id'] = $v['user_id'];
				$jWin = $mWin = $jHalf = $mHalf = $jTransport = $mTransport = $jDonate = $mDonate = 0;
				$seasonGamble = M('gamble')->field(['result','earn_point','create_time'])->where($jWhere)->select();
				foreach($seasonGamble as $key => $val){
					switch($val['result']){
						case '1':
							$jWin ++;
							if($val['create_time'] >= $mBeginTime && $val['create_time'] <= $mEndTime) $mWin ++;
							break;
						
						case '0.5':
							$jHalf ++;
							if($val['create_time'] >= $mBeginTime && $val['create_time'] <= $mEndTime) $mHalf ++;
							break;
						
						case '-1':
							$jTransport ++;
							if($val['create_time'] >= $mBeginTime && $val['create_time'] <= $mEndTime) $mTransport++;
							break;
						
						case '-0.5':
							$jDonate++;
							if($val['create_time'] >= $mBeginTime && $val['create_time'] <= $mEndTime) $mDonate++;
							break;
					}
				}
				
				$list[$k]['monthPercnet']  = (string)getGambleWinrate($mWin, $mHalf, $mTransport, $mDonate);
				$list[$k]['seasonPercnet'] = (string)getGambleWinrate($jWin, $jHalf, $jTransport, $jDonate);
				
				//近十场胜负、胜平负
				$wh['user_id']    = $v['user_id'];
				$tenGamble  = M('gamble')->where($wh)->order("id desc")->limit(10)->getField('result',true);
				$list[$k]['tenGamble'] = $tenGamble;
				
				$_TenGambleSort = 0;
				foreach($tenGamble as $gamble_v){
					if($gamble_v == 1 || $gamble_v == 0.5){
						$_TenGambleSort++;
					}
				}
				
				unset($list[$k]['create_time']);
				
				//排序数组
				$lv[]           = $v['lv'];
				$tenGambleSort[]    = $_TenGambleSort;
				$weekSort[]     = $list[$k]['weekPercnet'];
				
				
				$monthSort[]    = $list[$k]['monthPercnet'];
				$seasonSort[]   = $list[$k]['seasonPercnet'];
				$sortTime[]     = $v['create_time'];
				unset($list[$k]['lv_bet']);
			}
			//排序：周胜率》月胜率》等级》发布时间
			array_multisort($tenGambleSort, SORT_DESC, $lv, SORT_DESC, $weekSort, SORT_DESC, $monthSort, SORT_DESC, $seasonSort,SORT_DESC,$list);
		}
        $tenCount = [];
        foreach($list as $k => $v){
            if($v['weekPercnet'] >= 65){//周胜率大于65才返回
                $tenCount[] = $v;
            }
        }
        return $tenCount;
	}
	
	
    //推荐统计
    public function guess_count()
	{
		cookie('pageUrl', __SELF__);
		$pageNum = 20;
		$page=I('param.page',1,'intval');
		$game_id=I('param.scheid',0,'intval');
		$type=I('param.type',1,'intval');
		$play_type = in_array($type,[2,-2]) ? ['IN',['2','-2']] : $type;
		//亚盘、竞彩的标识
		$gambleType = in_array($type,[2,-2]) ? 2 : 1;//1:亚盘，2：竞彩
		if($game_id<1 || !in_array($type,[1,-1,2,-2]))
		{
			$this->error('参数有误!');
		}
		
		//每当有新的推荐才更新列表，否则读取缓存
		$gambleCount = M('Gamble')->where(['game_id'=>$game_id,'play_type'=>$play_type])->count('id');
		$gCount = 0;
		if ($type === 1 || $type === -1)
		{
			$gCount = S('DetailsGuessCount'.$gambleType.$game_id.$type.MODULE_NAME);
			$list = S('DetailsGuessCountList'.$gambleType.$game_id.$type.MODULE_NAME);
		}
		elseif ($type === 2 || $type === -2)
		{
			$gCount = S('DetailsGuessCount'.$gambleType.$game_id.MODULE_NAME);
			$list = S('DetailsGuessCountList'.$gambleType.$game_id.MODULE_NAME);
		}
		
		if ($gambleCount > $gCount || empty($list))
		{
			$list = M('Gamble')->alias("g")
				->join("left join qc_front_user f on f.id = g.user_id")
				->field(['g.id gamble_id','g.user_id','g.play_type','g.chose_side','g.handcp','g.odds',
					'g.is_impt','g.result','g.tradeCoin','g.desc','g.create_time','f.head','f.nick_name','f.lv','f.lv_bet'])
				->where(['game_id'=>$game_id,'play_type'=>$play_type])
				->select();
			
			$lv = $weekSort = $tradeCoin = $tradeCount = $sortTime = []; //用户排序依据数组
			
			foreach ($list as $k => $v)
			{
				$userIdArr[] = $v['user_id'];//获取user_id列
				$gambleIdArr[] = $v['gamble_id'];////获取gamble_id列
			}
			
			$tradeList = D('QuizLog')->where(['gamble_id'=>['in',$gambleIdArr]])->field('count(id) as tradeCount , gamble_id')->select();
			$tradeCount = get_arr_column($tradeList,'tradeCount');//获取该赛程销售量
			
			//去重
			$userIdArr = array_unique($userIdArr);
			//获取周、月、季胜率--分亚盘、竞彩
//			$weekPercnetArr   = D('Base')->getWinrate($userIdArr,1,$gambleType,0,1);//周
	
			
			foreach ($list as $k => $v)
			{
				
				$lv[]                      = in_array($type,[2,-2]) ? $v['lv_bet'] : $v['lv'];
				$sortTime[]                = $v['create_time'];
				$tradeCoin[]               = $v['tradeCoin'];
				foreach ($tradeList AS $key => $value)
				{
					if ($v['gamble_id'] === $value['gamble_id'])
						$list[$k]['tradeCount'] = $value['tradeCount'];
				}
				
				//合并用户的周胜率
				
				if ($gambleType == 1)
				{
					$list[$k]['weekPercnet'] = $weekSort[] = D('GambleHall')->CountWinrate($v['user_id'],1,1);  //周
					$list[$k]['monthPercnet'] = D('GambleHall')->CountWinrate($v['user_id'],1,2);  //月
					$list[$k]['seasonPercnet'] = D('GambleHall')->CountWinrate($v['user_id'],1,3);  //季
				}
				else
				{
					$list[$k]['weekPercnet'] = $weekSort[] = D('GambleHall')->CountWinrate($v['user_id'],1,1,false,false,0,2);  //周
					$list[$k]['monthPercnet'] = $weekSort[] = D('GambleHall')->CountWinrate($v['user_id'],1,2,false,false,0,2);  //月
					$list[$k]['seasonPercnet'] = $weekSort[] = D('GambleHall')->CountWinrate($v['user_id'],1,3,false,false,0,2);  //季
				}
				
//				foreach ($weekPercnetArr as $key => $value)
//				{
//					if ($v['user_id'] == $value['user_id'])
//					{
//						$list[$k]['weekPercnet']  = $weekSort[] = $value['winrate'];
//					}
//				}
				
			}
	
			array_multisort($lv,SORT_DESC, $weekSort,SORT_DESC, $tradeCoin,SORT_DESC, $tradeCount,SORT_DESC, $sortTime,SORT_DESC, $list);
			
			if ($type === 1 || $type === -1)
			{
				S('DetailsGuessCount'.$gambleType.$game_id.$type.MODULE_NAME,count($list),30*60);
				S('DetailsGuessCountList'.$gambleType.$game_id.$type.MODULE_NAME,$list,30*60);
			}
			elseif ($type === 2 || $type === -2)
			{
				S('DetailsGuessCount'.$gambleType.$game_id.MODULE_NAME,count($list),30*60);
				S('DetailsGuessCountList'.$gambleType.$game_id.MODULE_NAME,$list,30*60);
			}
		}
		
		$list = array_slice($list, ($page-1)*$pageNum, $pageNum);
		
		
		$gambleId = []; //本赛程查看过的推荐记录
		$user_id=is_login();
		if ($user_id)
		{
			$gambleId = (array)M('QuizLog')->where(['user_id'=>$user_id,'game_id'=>$game_id])->getField('gamble_id',true);
		}

		$listUserId = get_arr_column($list,'user_id');//获取该赛程销售量
		//获取月、季胜率--分亚盘、竞彩
//		$monthPercnetArr  = D('Base')->getWinrate($listUserId,1,$gambleType,0,2);//月
//		$seasonPercnetArr = D('Base')->getWinrate($listUserId,1,$gambleType,0,3);//季
		//获取用户近十场推荐结果
		$gambleResult = D('Base')->getUserWinFew(1,$gambleType,$listUserId,true);
	
		foreach ($list as $ke => $va)
		{
//			//合并用户的月胜率
//			foreach ($monthPercnetArr as $key => $value)
//			{
//				if ($va['user_id'] == $value['user_id'])
//				{
//					$list[$ke]['monthPercnet'] = $value['winrate'];
//				}
//			}
//			//合并用户的季胜率
//			foreach ($seasonPercnetArr as $key => $value)
//			{
//				if ($va['user_id'] == $value['user_id'])
//				{
//					$list[$ke]['seasonPercnet'] = $value['winrate'];
//				}
//			}
			
			//用户信息
			$list[$ke]['face'] = frontUserFace($va['head']);
			
			//合并用户的季胜率
			foreach ($gambleResult as $key => $value)
			{
				if ($va['user_id'] == $value['user_id'])
				{
					$list[$ke]['tenGamble'] = $value['gambleResult'];
				}
			}
		
			$list[$ke]['is_trade'] = in_array($va['gamble_id'],$gambleId) ? 1 : 0;
			if ($va['user_id']==$user_id)
			{
				$list[$ke]['is_trade']=1;
			}
			if($va['play_type']==1)
			{
				$list[$ke]['handcp'] = $va['chose_side']*-1*$va['handcp'];
				if($list[$ke]['handcp']>0)
				{
					$list[$ke]['handcp']='+'.$list[$ke]['handcp'];
				}
			}
			
			unset($list[$ke]['create_time']);
		}
		if (! empty($list))
		{
			foreach ($list as $key => $value)
			{
				if (empty($value['weekPercnet']))
					$list[$key]['weekPercnet'] = 0;
				
				if (empty($value['monthPercnet']))
					$list[$key]['monthPercnet'] = 0;
				
				if (empty($value['seasonPercnet']))
					$list[$key]['seasonPercnet'] = 0;
				
			}
			$list = HandleGamble($list);
		}
			
		
        if(IS_AJAX){
            foreach($list as $k => $val){
                if($val['is_trade']!=1 && $val['result']==0){
                    unset($list[$k]['chose_side']);
                    unset($list[$k]['handcp']);
                    unset($list[$k]['odds']);
                    unset($list[$k]['desc']);
                    unset($list[$k]['Answer']);
                }
            }
            $this->ajaxReturn(['status'=>1,'list'=>empty($list) ? null : $list]);
            
        }else{
            $this->assign('type',$type);
            $this->assign('list',$list);
            $this->display();
        }
    }
    
    //必发页面
    public function odds_betfair(){
        $scheid=I('get.scheid',0,"intval");
        if($scheid<1){
            $this->error("参数有误!");
        }
        $Value=$this->get_curl(C('API_URL')."/BifaValue ","gameId=$scheid",C('CURL_DOMAIN'));
        if($Value['data'] && $Value['status']==1){
            $this->assign('data',$Value['data']);
        }
        $Trade=$this->get_curl(C('API_URL')."/BifaTrade ","gameId=$scheid&limit=30",C('CURL_DOMAIN'));
        if($Trade['data'] && $Trade['status']==1){
            $this->assign('trade',$Trade['data']);
        }
        $this->assign('nav','odds');
        $this->display();
    }
    public function odds_betfair_more(){
        $scheid=I('get.scheid',0,"intval");
        if($scheid<1){
            $this->error("参数有误!");
        }
        $Trade=$this->get_curl(C('API_URL')."/BifaTrade ","gameId=$scheid",C('CURL_DOMAIN'));
        if($Trade['data'] && $Trade['status']==1){
            $this->assign('trade',$Trade['data']);
        }
         $this->display();
    }
}