<?php

/**
 * 首页
 *
 * @author chenzj <443629770@qq.com>
 *
 * @since  2016-04-19
 */
use Think\Tool\Tool;
class ActivityController extends CommonController {

    public function index() {
        #code...
    }

    /**
     * 滚球界面赔率接口————全场、半场亚盘、欧盘、大小的即时、滚球、初盘赔率
     * @return json
     */
    public function euroTickets() {
        $this->display();
    }

    /**
     * 欧洲杯PS门票获取
     * @param  string   $name  门票上显示名字
     * @return json
     */
    public function getEtickets() {
        $uname = !empty($_REQUEST['name']) ? $_REQUEST['name'] : null;

        if (empty($uname)) {
            echo 0;
        }
        $sourcePath = './Public/Mobile/images/activity/demo.jpg';

        $destPath = './Public/Mobile/images/activity/demo' . rand(0, 9999) . '.jpg';
        $flag = imageMosaics($sourcePath, $destPath, $uname);

        if ($flag) {
            $file = $destPath;
            if ($fp = fopen($file, "rb", 0)) {
                $gambar = fread($fp, filesize($file));
                fclose($fp);
                $base64 = chunk_split(base64_encode($gambar));
                $encode = '<img src="data:image/jpg/png/gif;base64,' . $base64 . '" >';
                unlink($destPath);
                echo $encode;
            }
        }
    }

    //国足助威
    public function cheer() {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            $wxpay_config = C('wxpay.wxpay_config');
            if (!isset($_GET['code'])) {
                redirect("https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . $wxpay_config['appid'] . "&redirect_uri=http://m.qqty.com/Activity/cheer.html&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect");
            } else {
                //获取code码，以获取openid
                $code = $_GET['code'];
                //获取token
                $tokenInfo = $this->do_curl('https://api.weixin.qq.com/sns/oauth2/access_token', 'appid=' . $wxpay_config['appid'] . '&secret=' . $wxpay_config['appsecret'] . '&code=' . $code . '&grant_type=authorization_code');
                $tokenInfo = json_decode($tokenInfo, true);
                session('wx_openid', $tokenInfo['openid']);
            }
        }
        //微信分享內容控制
        $this->wxShar();
        M('ActivityCheer')->where('id=1')->setInc('num', 1);
        $num = M('ActivityCheer')->where('id=1')->getField('num');
        session('cheer_num', $num);
        $this->assign('num', $num);
        $this->display();
    }

    //国足助威图片生成
    public function docheer() {
        $truename = I('post.truename', '');
        $airpore = I('post.airpore', '');
        $people = session('cheer_num');
        $half_home=I('post.half_home',0,'intval');
        $half_away=I('post.half_away',0,'intval');
        $home=I('post.home',0,'intval');
        $away=I('post.away',0,'intval');
        $day=I('post.day',3,'intval');
        $hour=I('post.hour',15,'intval');
        $minutes=I('post.minutes',30,'intval');
        $month=10;
        $month=date("F",  strtotime('2016-10-1'));
        $date=$day.' '.$month;
        if($hour<10){
            $hour='0'.$hour;
        }
        if($minutes<10){
            $minutes='0'.$minutes;
        }
        $time=$hour.$minutes;
        if ($truename == '' || strlen($truename)>15) {
            $this->error('名字不能超过5个字哦！');
        }
        if ($airpore=='' || mb_strlen($airpore,'UTF8')>10) {
            $this->error('请填写机场!');
        }
        if( !is_int($half_home) || !is_int($half_away)  || !is_int($home)  || !is_int($away)){
            $this->error('请填写比分!');
        }
        $people=str_pad($people,5,'0',STR_PAD_LEFT);
        $sourcePath = './Public/Mobile/images/activity/demo01.jpg';

        $destPath = './Public/Mobile/images/activity/demo01' . rand(0, 9999) . '.jpg';
        $half_score=$half_home.' - '.$half_away;
        $score=$home.' - '.$away;
        $flag = imageText($sourcePath, $destPath,$truename,$airpore,$half_score,$score,$people,$date,$time);
        M('ActivityCheer')->where('id=1')->setInc('photo',1);
        if ($flag) {
            $file = $destPath;
            if ($fp = fopen($file, "rb", 0)) {
                $gambar = fread($fp, filesize($file));
                fclose($fp);
                $base64 = chunk_split(base64_encode($gambar));
                $return = D('Uploads')->uploadFileBase64($base64, "cheer", "", "cheer".$people.date('His'));
                $img = Think\Tool\Tool::imagesReplace($return['url']);
                //$encode = '<img src="data:image/jpg/png/gif;base64,' . $base64 . '" >';
                unlink($destPath);
                $openid=session('wx_openid');
                if(!$openid){
                    $openid='';
                }
                $rsl=M('ActivityCheer')->add([
                    'username'=>$truename,
                    'airpore'=>$airpore,
                    'rank'=>$people,
                    'half_score'=>$half_score,
                    'score'=>$score,
                    'wechat_openid'=>$openid,
                    'create_time'=>NOW_TIME,
                ]);
                if(!$rsl){
                    $this->error('啊~！啊~！你的能量不足，重新试试吧！');
                }
                $this->success($img);
            }
        }else{
            $this->error('啊~！啊~！你的能量不足，重新试试吧！');
        }
    }

    //宣传页
    public function referral1(){
        //微信分享內容控制
        $this->wxShar();
        $this->display();
    }
    public function test(){
        echo strtotime('2016-10-11');
        echo date("F",  strtotime('2016-10-11'));
    }

	/**
	 * 推荐游戏首页
	 * @User Liangzk
	 * @DateTime 2016-11-30
	 *
	 */
    public function gamble()
	{
		
//		$activityId = I('activityId',0,'int');
//
//		if ($activityId < 1)
//		{
//			$this->error('参数错误');
//		}

		
		$titleRes = M('SingleTitle')->field('id,single_title,game_type,trophy,explain_title,explain_cont,end_time')->order('id desc')->limit(1)->find();
		$activityId = $titleRes['id'];
	
//		if (empty($titleRes['id']))
//			$this->error('参数错误');

		$single_multiple = M('SingleList')->where(['single_title_id'=>$activityId])->getField('single_multiple');
        $statusfont = '提交答案';

		//滚动的banner
		if ($single_multiple > 0)
		{
			//多场推荐游戏广告
			$banner = Tool::getAdList(26,4,4) ?: '';
		}
		else
		{
			//单场推荐游戏广告
			$banner = Tool::getAdList(25,4,4) ?: '';
		}

        $this->assign('bannerCount',count($banner));
        $this->assign('banner',$banner);
        $this->assign('multiple',$single_multiple);//用来区分单场还是多场
		
		//获取app登录
		$userToken = I('userToken');
		$userInfo = getUserToken($userToken,true);
		if ($userInfo['userid'] > 0)
		{
			$user_id = $userInfo['userid'];
			D('FrontUser')->autoLogin($user_id);//登录m站
		}
		else
		{
			$user_id = is_login();
		}
		if ($user_id > 0)
		{
			$userInfo = M('FrontUser')->where(['id'=>$user_id])->field('id as user_id,head,nick_name')->find();
			$userInfo['head'] = frontUserFace($userInfo['head']);
		}
		$this->assign('userInfo',$userInfo);

		if ($titleRes['end_time'] > time())
		{
			$list = M('SingleTitle st')
					->join('INNER JOIN qc_single_list sl ON st.id = sl.single_title_id')
					->where(['st.id'=>$activityId,'sl.status'=>1])
					->field('sl.id,st.single_title,sl.single_title_id,sl.game_id,sl.home_team_name,sl.away_team_name,sl.game_time,sl.single_multiple')
					->order('sl.game_time asc')
					->select();
		}


		if (! empty($list) && $titleRes['end_time'] > time())
		{
			//活动截止时间
			$this->assign('end_time',date('m月d日 H:i',$titleRes['end_time']));
			//活动奖品
			$this->assign('trophy',$titleRes['trophy']);
			$this->assign('explain_title',$titleRes['explain_title']);
			$this->assign('explain_cont',$titleRes['explain_cont']);

			//获取活动id和活动相关的赛事ID
			$singleListId = $gameIdArr = array();
			foreach ($list as $k => $v)
			{
				$singleListId[$k] = $v['id'];
				$gameIdArr[$k] = $v['game_id'];
			}

			//获取活动信息
			$activityLog = M('SingleQuiz')
							->where(['single_id'=>['IN',$singleListId],'status'=>1])
							->field('id,single_id,question,option,sort,re_answer')
							->select();
			array_multisort(get_arr_column($activityLog,'sort'),SORT_ASC,$activityLog);
			if ($user_id > 0)
			{
				$singleIdArr = $quizIdArr = array();
				foreach ($activityLog as $k => $v)
				{
					$singleIdArr[$k] = $v['single_id'];
					$quizIdArr[$k] = $v['id'];
					if ($v['re_answer'] >= 0)
					{
						$statusId = 1;//已经出了答案
					}
				}
				//获取登录用户对该活动的选择情况
				$singleIdRes = M('SingleLog')
							->where(['user_id'=>$user_id,'single_id'=>['IN',$singleIdArr],'quiz_id'=>['IN',$quizIdArr]])
							->field('single_id,quiz_id,answer')
							->select();
				//如果问题的个数与答的个数相等
				if (count($activityLog) == count($singleIdRes))
				{
					$statusId = 1;//都已经开始了的标志
                    $statusfont = '已提交';
				}

			}
			foreach ($activityLog as $key => $value)
			{
				//获取选择
				$activityLog[$key]['option'] = json_decode($value['option'],true);
				//标志登录用户是否已经提交筛选了
				$is_quiz = $is_true = false;
				foreach ($singleIdRes as $k => $v)
				{
					if ($v['single_id'] == $value['single_id'] && $value['id'] == $v['quiz_id'])
					{
						foreach ($activityLog[$key]['option'] as $item => $itemV)
						{
							if ($itemV['aid'] == $v['answer'])
							{
								$activityLog[$key]['option'][$item]['is_quiz'] = 1;//标志登录用户选择的答案
								//标志登录用户是否已经猜对
								if ($value['re_answer'] >= 0)
								{
									$activityLog[$key]['is_true'] = $value['re_answer'] == $v['answer'] ? 'win' : 'lose';

									$statusId = 1;//1：活动不能再推荐了
								}
								$is_quiz = true;
								break;
							}
						}

					}

					if ($is_quiz) break;


				}
			}

			//把每个问题每个选项被选择的人数进行转换成百分比
			foreach ($activityLog as $key => $value)
			{
				//获取问题的总选择输
				$sum = $count = $ratioSum = $sign = 0;
				foreach ($value['option'] as $k => $v)
				{
					$sum += $v['num'];
				}
				//统计改题有多少个选项
				$count = count($value['option']);
				foreach ($value['option'] as $k => $v)
				{
					$sign++;
					if ($count <= $sign)
					{
						$activityLog[$key]['option'][$k]['num'] = 100 - $ratioSum;
					}
					else
					{
						//每个选项的百分比的和
						$ratioSum += round($v['num']/$sum*100);
						//每个选项的百分比
						$activityLog[$key]['option'][$k]['num'] = round($v['num']/$sum*100);
					}




				}


			}

			//获取相关的赛事信息
			$gameTable = $titleRes['game_type'] == 1 ? 'GameFbinfo' : 'GameBkinfo';
			$gameFbinfoRes = M($gameTable)
							->where(['game_id'=>['in',$gameIdArr]])
							->field('game_id,union_id,union_name,home_team_id,away_team_id,score,game_state,gtime')
							->select();
			//获取联盟id、主队球队ID、客队球队ID
			$unionIdArr = $teamIdArr  = array();
			foreach ($gameFbinfoRes as $key => $value)
			{
				$unionIdArr[$key] = $value['union_id'];
			}
			
			//获取球队logo
			if ($titleRes['game_type'] == 1)
			{
				setTeamLogo($gameFbinfoRes,1);
			}
			else
			{
				setTeamLogo($gameFbinfoRes,2);
			}
			
			//获取赛事名称的颜色
			$unionTable = $titleRes['game_type'] == 1 ? 'Union' : 'BkUnion';
			$unionColor = M($unionTable)->where(['union_id'=>['IN',$unionIdArr]])->field('union_id,union_color')->select();
			
			$gamesNum = 0;//已经开始比赛的赛事数量
			foreach ($gameFbinfoRes as $key => $value)
			{
//				if (in_array($value['game_state'],[0,1,2,3,4]))
//				{
//					//如果还在比赛就去掉比分
//					unset($gameFbinfoRes[$key]['score']);
//				}

				//判断是否已经开始比赛了
				if ($value['gtime'] < time())
				{

					$gamesNum++;
				}
				//赛事名称格式转换
				$gameFbinfoRes[$key]['union_name'] = substr($value['union_name'],0,stripos($value['union_name'],','));
				//赛事名称的字体颜色
				foreach ($unionColor as $k => $v)
				{
					if ($v['union_id'] == $value['union_id'])
					{
						$gameFbinfoRes[$key]['union_color'] = $v['union_color'];
						break;
					}
				}

			}
			//判断是否活动涉及的赛事已经有开始的了--
			if ($gamesNum >0)
			{
				$statusId = 1;//有已经开始了的标志
			}
			//合并列表
			foreach ($list as $key => $value)
			{
				$list[$key]['home_team_name'] = substr($value['home_team_name'],0,stripos($value['home_team_name'],','));
				$list[$key]['away_team_name'] = substr($value['away_team_name'],0,stripos($value['away_team_name'],','));
				$list[$key]['game_time'] = date('m-d H:i',$value['game_time']);
				$list[$key]['game_time_str'] = $value['game_time'];
				//获取活动信息
				foreach ($activityLog as $k => $v)
				{
					if ($value['id'] == $v['single_id'])
					{
						$list[$key]['game'][$k] = $v;
					}
				}
				//获取相关的赛事信息
				foreach ($gameFbinfoRes as $k => $v)
				{
					if ($value['game_id'] == $v['game_id'])
					{
						$list[$key]['union_name'] = $v['union_name'];//赛事名称
						$list[$key]['union_color'] = $v['union_color'];//赛事名称字体颜色
						$list[$key]['homeTeamLogo'] = $v['homeTeamLogo'];//主队Logo
						$list[$key]['awayTeamLogo'] = $v['awayTeamLogo'];//客队LogohomeTeamLogo
						$list[$key]['score'] = $v['score'];//比分

					}
				}
			}

			//默认主客队logo
			$this->assign('defHomeLogo',staticDomain('/Public/Home/images/common/home_def.png'));
			$this->assign('defAwayLogo',staticDomain('/Public/Home/images/common/away_def.png'));
			$this->assign('list',$list);

		}
		else
		{
			//获取公告资讯
			$publishList = M('PublishList')
				->where(['is_bbs'=>1,'status'=>1])
				->field('id,class_id,remark,short_title,img')
				->order('update_time desc,add_time desc')
				->limit(30)
				->select();
			$classArr = getPublishClass(0);
			foreach ($publishList as &$v) {
				$v['img']  = @Think\Tool\Tool::imagesReplace($v['img']);
				$v['href'] = mNewsUrl($v['id'],$v['class_id'],$classArr);
			}
			$this->assign('publishList',$publishList);

		}


		cookie('userUrl', __SELF__);
		cookie('pageUrl', __SELF__);
		cookie('redirectUrl', __SELF__);
        $ruleText = M('SingleRule')->where(['title_id'=>$activityId])->getField('rule');
        $this->assign('ruleText',$ruleText);

		$this->assign('statusId',$statusId);
        $this->assign('statusfont',$statusfont);

		$this->assign('activityId',$activityId);
		$this->assign('title',$titleRes['single_title']);
		
		//微信分享內容控制
		$this->wxShar();
		
		$this->display();
	}
	/**
	 * 推荐游戏的推荐记录
	 * @User Liangzk
	 * @DateTime 2016-11-30
	 *
	 */
	public function gambleLog()
	{
		$userToken = I('userToken');
		$userInfo = getUserToken($userToken,true);
		if ($userInfo['userid'] > 0)
		{
			$user_id = $userInfo['userid'];
		}
		else
		{
			$user_id = is_login();
			if (empty($user_id))
			{
				$this->redirect('User/login');
				exit;
			}
		}


//		$activityId = I('activityId',0,'int');
//		if ($activityId < 0)
//			$this->error('参数出错！');
//
//		$activityId = M('SingleTitle')->where(['id'=>$activityId])->getField('id');
//		if (empty($activityId))
//			$this->error('参数错误');
//		$this->assign('activityId',$activityId);

		$gambleLogRes = M('SingleLog slo')
			->join('INNER JOIN qc_single_list sl ON slo.single_id = sl.id')
			->where(['slo.user_id'=>$user_id,'sl.status'=>1])
			->field('slo.answer,slo.quiz_id,slo.single_id,slo.add_time,sl.single_title_id,sl.single_multiple')
			->select();
		
		//获取多场的问题id
		$quizIdArr = array();
		foreach ($gambleLogRes as $k => $v)
		{
			if ($v['single_multiple'] == 1)
			{
				$quizIdArr[$k] = $v['quiz_id'];
			}
		}

		if (! empty($quizIdArr))
		{
			$singleQuiz = M('SingleQuiz')->where(['id'=>['IN',$quizIdArr]])->field('single_id,option')->select();
			if (! empty($singleQuiz))
			{
				foreach ($singleQuiz as $k => $v)
				{
					$singleQuiz[$k]['option'] = json_decode($v['option'],true);
				}
			}
		}

		if (! empty($gambleLogRes))
		{
			//获取活动标题ID
			$singleTitleIdArr = get_arr_column($gambleLogRes,'single_title_id');

			$userInfo = M('FrontUser')->where(['id'=>$user_id])->field('nick_name,head')->find();

			//活动列表
			$list = M('SingleTitle')->where(['id'=>['in',$singleTitleIdArr]])->field('id,single_title')->select();

			//把答案转换 0对应A 1对应B。。。。。类推
			$options = ['A','B','C','S','D','F','G','H','J','K','L','Z','X',
				'V','N','M','Q','W','E','R','T','Y','U','I','O','P'];
			sort($options);
			foreach ($list as $key => $value)
			{
				//获取用户信息
				$list[$key]['face'] = frontUserFace($userInfo['head']);
				$list[$key]['nick_name'] = $userInfo['nick_name'];

				//转换答案
				$i = $j = 0;
				foreach ($gambleLogRes as $k => $v)
				{
					if ($value['id'] == $v['single_title_id'])
					{
						$list[$key]['multiple'] = $v['single_multiple'];
						$list[$key]['add_time'] = date('Y-m-d H:i',$v['add_time']);
						$list[$key]['sortTime'] = $v['add_time'];
						//单场
						if ($v['single_multiple'] == 0)
						{
							$i ++;
							$list[$key]['options'] .= $i.'.'.$options[$v['answer']].', ';
						}//多场
						else
						{
							foreach ($singleQuiz as $k1 => $v1)
							{
								if ($v['single_id'] == $v1['single_id'])
								{
									foreach ($v1['option'] as $optionK => $optionV)
									{
										if ($v['answer'] == $optionV['aid'] )
										{
											$j++;
											$list[$key]['options'] .= $j.'.'.$optionV['option'].', ';
										}
									}
								}
							}
						}
					}
				}

				$list[$key]['options'] = substr($list[$key]['options'],0,-2);

			}
		}
		
		$addTimeArr = get_arr_column($list,'sortTime');
		array_multisort($addTimeArr,SORT_DESC,$list);
		
		$this->assign('list',$list);
		$this->display();
	}
	/**
	 * 验证登录（包括APP）
	 * @User Liangzk
	 * @DateTime 2016-12-8
	 *
	 */
	public function appLogin()
	{
		if (!IS_AJAX)
			return;
		$userToken = I('userToken');
		$userInfo = getUserToken($userToken,true);
		if ($userInfo['userid'] > 0)
		{
			$user_id = $userInfo['userid'];
		}
		else
		{
			$user_id = is_login();
		}
		if(!$user_id){
			$this->error('请先登录!',U('User/login'));
		}
		$this->success('success');
	}
	/**
	 * 推荐游戏详情
	 * @User Liangzk
	 * @DateTime 2016-11-30
	 *
	 */
	public function gambleDetails()
	{

		$activityId = I('activityId',0,'int');

		if ($activityId < 1)
		{
			$this->error('参数错误');
		}

		$titleRes = M('SingleTitle')->where(['id'=>$activityId])->field('id,single_title,game_type,end_time')->find();
		if (empty($titleRes['id']))
			$this->error('参数错误');

		$single_multiple = M('SingleList')->where(['single_title_id'=>$activityId])->getField('single_multiple');

        //滚动的banner
        if ($single_multiple > 0)
        {
            //多场推荐游戏广告
            $banner = Tool::getAdList(26,4,4) ?: '';
        }
        else
        {
            //单场推荐游戏广告
            $banner = Tool::getAdList(25,4,4) ?: '';
        }

        $this->assign('bannerCount',count($banner));
        $this->assign('banner',$banner);

		$this->assign('multiple',$single_multiple);//用来区分单场还是多场
		
		$userToken = I('userToken');
		$userInfo = getUserToken($userToken,true);
		if ($userInfo['userid'] > 0)
		{
			$user_id = $userInfo['userid'];
		}
		else
		{
			$user_id = is_login();
		}

		if (empty($user_id))
		{
			redirect('/User/login');
			exit;
		}

		$userInfo = M('FrontUser')->where(['id'=>$user_id])->field('id as user_id,head,nick_name')->find();
		$userInfo['head'] = frontUserFace($userInfo['head']);
		$this->assign('userInfo',$userInfo);


		$list = M('SingleTitle st')
			->join('INNER JOIN qc_single_list sl ON st.id = sl.single_title_id')
			->where(['st.id'=>$activityId,'sl.status'=>1])
			->field('sl.id,st.single_title,sl.single_title_id,sl.game_id,sl.home_team_name,sl.away_team_name,game_time,sl.single_multiple')
			->select();

		if (! empty($list) )
		{
			//活动截止时间
			$this->assign('end_time',date('m月d日 H:i',$titleRes['end_time']));

			//获取活动id和活动相关的赛事ID
			$singleListId = $gameIdArr = array();
			foreach ($list as $k => $v)
			{
				$singleListId[$k] = $v['id'];
				$gameIdArr[$k] = $v['game_id'];
			}

			//获取活动信息
			$activityLog = M('SingleQuiz')
				->where(['single_id'=>['IN',$singleListId],'status'=>1])
				->field('id,single_id,question,option,sort,re_answer')
				->select();
			array_multisort(get_arr_column($activityLog,'sort'),SORT_ASC,$activityLog);
			if ($user_id > 0)
			{
				$singleIdArr = $quizIdArr = array();
				foreach ($activityLog as $k => $v)
				{
					$singleIdArr[$k] = $v['single_id'];
					$quizIdArr[$k] = $v['id'];
				}
				//获取登录用户对该活动的选择情况
				$singleIdRes = M('SingleLog')
					->where(['user_id'=>$user_id,'single_id'=>['IN',$singleIdArr],'quiz_id'=>['IN',$quizIdArr]])
					->field('single_id,quiz_id,answer')
					->select();
			}

			foreach ($activityLog as $key => $value)
			{
				//获取选择
				$activityLog[$key]['option'] = json_decode($value['option'],true);
				//标志登录用户是否已经提交筛选了
				$is_quiz = $is_true = false;
				foreach ($singleIdRes as $k => $v)
				{
					if ($v['single_id'] == $value['single_id'] && $value['id'] == $v['quiz_id'])
					{
						foreach ($activityLog[$key]['option'] as $item => $itemV)
						{
							if ($itemV['aid'] == $v['answer'])
							{
								$activityLog[$key]['option'][$item]['is_quiz'] = 1;//标志登录用户选择的答案
								//标志登录用户是否已经猜对
								if ($value['re_answer'] >= 0)
								{
									$activityLog[$key]['is_true'] = $value['re_answer'] == $v['answer'] ? 'win' : 'lose';

								}
								$is_quiz = true;
								break;
							}
						}

					}

					if ($is_quiz) break;
					
				}
			}

			//把每个问题每个选项被选择的人数进行转换成百分比
			foreach ($activityLog as $key => $value)
			{
				//获取问题的总选择输
				$sum = $count = $ratioSum = $sign = 0;
				foreach ($value['option'] as $k => $v)
				{
					$sum += $v['num'];
				}
				//统计改题有多少个选项
				$count = count($value['option']);
				foreach ($value['option'] as $k => $v)
				{
					$sign++;
					if ($count <= $sign)
					{
						$activityLog[$key]['option'][$k]['num'] = 100 - $ratioSum;
					}
					else
					{
						//每个选项的百分比的和
						$ratioSum += round($v['num']/$sum*100);
						//每个选项的百分比
						$activityLog[$key]['option'][$k]['num'] = round($v['num']/$sum*100);
					}

				}
			}
			
			//获取相关的赛事信息
			$gameTable = $titleRes['game_type'] == 1 ? 'GameFbinfo' : 'GameBkinfo';
			$gameFbinfoRes = M($gameTable)
				->where(['game_id'=>['in',$gameIdArr]])
				->field('game_id,union_id,union_name,home_team_id,away_team_id,score,game_state,gtime')
				->select();
			//获取联盟id、主队球队ID、客队球队ID
			$unionIdArr = $teamIdArr  = array();
			foreach ($gameFbinfoRes as $key => $value)
			{
				$unionIdArr[$key] = $value['union_id'];
			}
			
			//获取球队logo
			if ($titleRes['game_type'] == 1)
			{
				setTeamLogo($gameFbinfoRes,1);
			}
			else
			{
				setTeamLogo($gameFbinfoRes,2);
			}
			
			//获取赛事名称的颜色
			$unionTable = $titleRes['game_type'] == 1 ? 'Union' : 'BkUnion';
			$unionColor = M($unionTable)->where(['union_id'=>['IN',$unionIdArr]])->field('union_id,union_color')->select();
			
		

			$gamesNum = 0;//已经开始比赛的赛事数量
			foreach ($gameFbinfoRes as $key => $value)
			{
				
				//判断是否已经开始比赛了
				if ($value['gtime'] < time())
				{
					
					$gamesNum++;
				}
				//赛事名称格式转换
				$gameFbinfoRes[$key]['union_name'] = substr($value['union_name'],0,stripos($value['union_name'],','));
				//赛事名称的字体颜色
				foreach ($unionColor as $k => $v)
				{
					if ($v['union_id'] == $value['union_id'])
					{
						$gameFbinfoRes[$key]['union_color'] = $v['union_color'];
						break;
					}
				}
				

			}

			//合并列表
			foreach ($list as $key => $value)
			{
				$list[$key]['home_team_name'] = substr($value['home_team_name'],0,stripos($value['home_team_name'],','));
				$list[$key]['away_team_name'] = substr($value['away_team_name'],0,stripos($value['away_team_name'],','));
				$list[$key]['game_time'] = date('m-d H:i',$value['game_time']);
				$list[$key]['game_time_str'] = $value['game_time'];
				//获取活动信息
				foreach ($activityLog as $k => $v)
				{
					if ($value['id'] == $v['single_id'])
					{
						$list[$key]['game'][$k] = $v;
					}
				}
				//获取相关的赛事信息
				foreach ($gameFbinfoRes as $k => $v)
				{
					if ($value['game_id'] == $v['game_id'])
					{
						$list[$key]['union_name'] = $v['union_name'];//赛事名称
						$list[$key]['union_color'] = $v['union_color'];//赛事名称字体颜色
						$list[$key]['homeTeamLogo'] = $v['homeTeamLogo'];//主队Logo
						$list[$key]['awayTeamLogo'] = $v['awayTeamLogo'];//客队Logo
						$list[$key]['score'] = $v['score'];//比分

					}
				}
			}

			//默认主客队logo
			$this->assign('defHomeLogo',staticDomain('/Public/Home/images/common/home_def.png'));
			$this->assign('defAwayLogo',staticDomain('/Public/Home/images/common/away_def.png'));
			$this->assign('list',$list);

			$ruleText = M('SingleRule')->where(['title_id'=>$activityId])->getField('rule');

			$this->assign('ruleText',$ruleText);

		}


		cookie('userUrl', __SELF__);
		cookie('pageUrl', __SELF__);
		cookie('redirectUrl', __SELF__);
		//默认主客队logo
		$this->assign('defHomeLogo',staticDomain('/Public/Home/images/common/home_def.png'));
		$this->assign('defAwayLogo',staticDomain('/Public/Home/images/common/away_def.png'));

		$this->assign('activityId',$activityId);
		$this->assign('list',$list);

		$this->display();
	}
	/**
	 * 推荐游戏规则
	 * @User Liangzk
	 * @DateTime 2016-11-30
	 *
	 */
	public function rule()
	{
		$activityId = I('activityId',0,'int');
		if ($activityId < 0)
			$this->error('参数出错！');

		$ruleText = M('SingleRule')->where(['title_id'=>$activityId])->getField('rule');

		$this->assign('ruleText',$ruleText);
		$this->display();
	}
	/**
	 * 推荐游戏推荐提交和验证
	 * @User Liangzk
	 * @DateTime 2016-11-30
	 *
	 */
	public function requestGamble()
	{
		
		$userToken = I('userToken');
		$userInfo = getUserToken($userToken,true);
		if ($userInfo['userid'] > 0)
		{
			$user_id = $userInfo['userid'];
		}
		else
		{
			$user_id = is_login();

		}
		if (empty($user_id))
			$this->ajaxReturn(['status'=>2,'info'=>'还没登录喔，亲！']);

		$activityId = I('activityId',0,'int');//活动标题ID
		$titleRes = M('SingleTitle')->master(true)->where(['id'=>$activityId])->field('id,end_time,game_type')->find();

		$activityId = $titleRes['id'];
		$strData = I('strData','','string');
		if ($strData === '' || empty($activityId))
			$this->ajaxReturn(['status'=>0,'info'=>'参数出错！']);

		if ($titleRes['end_time'] <= time())
		{
			$this->ajaxReturn(['status'=>-1,'info'=>'推荐已经结束，请留意下一期！']);
		}

		//获取数据库的活动ID、和赛事
		$singleList = M('SingleList')->master(true)->where(['single_title_id'=>$activityId])->field('id,game_id')->select();
		$singleIdArrDB = $gameIdArrDB = array();
		foreach ($singleList as $k => $v)
		{
			$singleIdArrDB[] = $v['id'];
			$gameIdArrDB[] = $v['game_id'];
		}
		//获取问题id
		$quizIdArrDB = M('SingleQuiz')->master(true)->where(['single_id'=>['IN',$singleIdArrDB]])->getField('id',true);


		//获取传过来的数据，并进行验证数据的合法性
		$strData = substr($strData,0,-1);
		$paramArr = explode(',',$strData);
		$singleLog = $gameIdArr = $quizIdArr = $data = array();
		$sign = false;//标志数据的合法性
		foreach ($paramArr as $key => $value)
		{
			$data = explode(':',$value);
			//推荐活动记录
			if (in_array($data[0],$singleIdArrDB))
			{
				$singleLog[$key]['single_id'] = (int)$data[0];//活动ID
			}
			else
			{
				$sign = true;
				break;
			}

			$singleLog[$key]['user_id'] = $user_id;
			$singleLog[$key]['answer'] = (int)$data[1];//问题答案
			if (in_array($data[2],$quizIdArrDB))
			{
				$singleLog[$key]['quiz_id'] = (int)$data[2];//问题ID
				//问题ID
				$quizIdArr[$key] = (int)$data[2];//问题ID
			}
			else
			{
				$sign = true;
				break;
			}

			$singleLog[$key]['add_time'] = time();
			if (in_array($data[3],$gameIdArrDB))
			{
				$singleLog[$key]['game_id'] = (int)$data[3];//赛程ID
				//获取赛程
				$gameIdArr[$key] = (int)$data[3];//赛程ID
			}
			else
			{
				$sign = true;
				break;
			}

			$singleLog[$key]['title_id'] = $activityId;//活动标题ID

		}

		if ($sign || count(array_unique($quizIdArr)) != count($quizIdArr))
		{
			$this->ajaxReturn(['status'=>0,'info'=>'参数出错！']);
		}


		//获取涉及该活动的赛事
		$gameTable = $titleRes['game_type'] == 1 ? 'GameFbinfo' : 'GameBkinfo';
		$gameIdArr = M($gameTable)
				->master(true)
				->where(['game_id'=>['IN',$gameIdArr]])
				->field('game_id,game_state,gtime')
				->select();


		//筛选掉比赛完成的
		$sign = false;//用来标记是否有部分比赛已经结束了
		foreach ($gameIdArr as $key => $value)
		{
			foreach ($singleLog as $k => $v)
			{
				if ($v['game_id'] == $value['game_id'] && $value['gtime'] < time() )
				{
					$sign = true;
				}
			}
			if ($sign)
			{
				break;
			}
		}
		if ($sign)
		{
			$this->ajaxReturn(['status'=>-1,'info'=>'比赛已经开始，不能推荐！']);
		}

		foreach ($singleLog as $k => $v)
		{
			unset($singleLog[$k]['game_id']);//去掉赛事ID
		}

		//检查这些问题是否已经出了结果
		$quizIdArr = M('SingleQuiz')->master(true)->where(['id'=>['IN',$quizIdArr],'re_answer'=>['EGT',0]])->getField('id',true);
		if (! empty($quizIdArr))
		{
			$this->ajaxReturn(['status'=>-1,'info'=>'推荐结果已经公布！']);
		}

		$singleIdArr = get_arr_column($singleLog,'single_id');
		$singleLogId = M('SingleLog')
			->master(true)
			->where(['single_id'=>['IN',$singleIdArr],'user_id'=>$user_id])
			->getField('id');

		if (! empty($singleLogId))
			$this->ajaxReturn(['status'=>-1,'info'=>'已经推荐选过了喔，亲！']);

		$res = M('SingleLog')->addAll($singleLog);

		if ($res === false)
			$this->ajaxReturn(['status'=>0,'info'=>'提交失败，请重新提交！']);

		//获取活动信息
		$activityLog = M('SingleQuiz')
			->master(true)
			->where(['single_id'=>['IN',$singleIdArr],'status'=>1])
			->field('id,single_id,option')
			->select();

		//记录选择的答案的次数+加上原来的基数
		foreach ($activityLog as $key => $value)
		{
			//获取选择，并转换格式
			$activityLog[$key]['option'] = json_decode($value['option'],true);
			$signLog = false;
			foreach ($singleLog as $k => $v)
			{
				if ($value['single_id'] == $v['single_id'])
				{
					foreach ($activityLog[$key]['option'] as $item => $itemV)
					{
						if ($itemV['aid'] == $v['answer'])
						{
							$activityLog[$key]['option'][$item]['num'] += 1;

							$signLog = true;
						}
					}
				}
				if ($signLog) break;
			}
			//转换格式
			$activityLog[$key]['option'] = json_encode($activityLog[$key]['option']);
			M()->query('UPDATE qc_single_quiz set option = '.json_encode($activityLog[$key]['option']).' where id = '.$value['id']);

		}

		$this->ajaxReturn(['status'=>1,'info'=>'提交成功']);
	}

	/**
	 * 免费注册推介
	 * @User Liangzk
	 * @DateTime 2016-12-13
	 */
	public function referral()
	{
		//header('Location:'.U('/'));
		$list = M('MasterList')->where(['status'=>1])
				->field('id,head,master_name,winrate,descript')
				->order('sort asc')
				->select();
		foreach ($list as $k => $v)
		{
			$list[$k]['head'] = frontUserFace($v['head']);
		}

		//获取今天提交数量
//		$dateStart = mktime(0,0,0,date('m'),date('d'),date('Y'));
//		$logCount = M('MasterLog')->where(['add_time'=>['EGT',$dateStart]])->count('id');

//		$this->assign('logCount',$logCount);
		$this->assign('list',$list);
		$this->display();
	}
	/**
	 * 免费注册推介--提交操作
	 * @User Liangzk
	 * @DateTime 2016-12-13
	 */
	public function saveMaster()
	{
		//获取今天提交数量
//		$dateStart = mktime(0,0,0,date('m'),date('d'),date('Y'));
//		$logCount = M('MasterLog')->where(['add_time'=>['EGT',$dateStart]])->count('id');
//		if ($logCount >= 50)
//		{
//			$this->ajaxReturn(['status'=>0,'info'=>'今天推介名额已满！']);
//		}

		$mobile = I('mobile');
		$masterId = I('masterId',0,'int');
		$captcha = I('captcha','','string');

		//验证推介人的id是否正确
		$masterId = M('MasterList')->where(['id'=>$masterId])->getField('id');

		if (empty($mobile) || $masterId === 0 || $captcha === '' || empty($masterId))
		{
			$this->ajaxReturn(['status'=>-1,'info'=>'参数出错！']);
		}

		if (! fn_is_mobile($mobile))
		{
			$this->ajaxReturn(['status'=>-1,'info'=>'手机号码格式不正确！']);
		}

		$phone = M('MasterLog')->where(['phone'=>$mobile])->getField('phone');
		if (! empty($phone))
		{
			$this->ajaxReturn(['status'=>-1,'info'=>'该手机号码已经注册！']);
		}

		$isTrue = A('User')->checkMobileVerify($captcha,$mobile);
		if(! $isTrue)
			$this->ajaxReturn(['status'=>-1,'info'=>'验证码错误或已超时！']);

		$res = M('MasterLog')->add(['master_id'=>$masterId,'add_time'=>time(),'phone'=>$mobile]);

		if ($res === false)
			$this->ajaxReturn(['status'=>-1,'info'=>'提交失败，请重新提交！']);

		$this->ajaxReturn(['status'=>1,'info'=>'提交成功！']);

	}
	/**
	 * 免费注册推介--手机验证
	 * @User Liangzk
	 * @DateTime 2016-12-13
	 */
	public function checkPhone()
	{
		$mobile = I('mobile');
		$this->ajaxReturn(['status'=>fn_is_mobile($mobile) ]);
	}
	/**
	 * 免费注册推介--发送手机号码
	 * @User Liangzk
	 * @DateTime 2016-12-13
	 */
	public function sendMobileMsg()
	{
		//获取今天提交数量
//		$dateStart = mktime(0,0,0,date('m'),date('d'),date('Y'));
//		$logCount = M('MasterLog')->where(['add_time'=>['EGT',$dateStart]])->count('id');
//		if ($logCount >= 50)
//		{
//			$this->ajaxReturn(['status'=>0,'info'=>'今天推介名额已满！']);
//		}

		$mobile = I('mobile');
		if (!fn_is_mobile($mobile))
		{
			$this->ajaxReturn(['status'=>-1,'info'=>'手机号码格式不正确！']);
		}

		$phone = M('MasterLog')->where(['phone'=>$mobile])->getField('phone');
		if (! empty($phone))
		{
			$this->ajaxReturn(['status'=>-1,'info'=>'该手机号码已经注册！']);
		}
		$_POST['platform'] = 4;
		$result = sendCode($mobile, 'recommend');

		if ($result == '-1')
		{
			//已经发送过,需等待60秒
			$this->ajaxReturn(['status'=>-1,'info'=>'您已经发送过验证码,请等待' . C('reSendCodeTime') . '秒后重试!']);
			exit;
		}

		if ($result)
		{
			cookie('verifyCode', $result['token'], C('verifyCodeTime'));  //存返回值
			$msg = $result['mobileSMS'] == 3 ? '验证码将以电话语音形式通知您,请注意接听！' : '验证码将以短信形式通知您,请在' . (C('verifyCodeTime') / 60) . '分钟内完成验证注册！';

			//发送成功
			$this->ajaxReturn(['status'=>1,'info'=>$msg]);
		}

		//发送失败
		$this->ajaxReturn(['status'=>-1,'info'=>'你发送太频繁了，请稍后重试！']);

	}
	
	
	function demo()
	{
		$this->display();
	}
	
	/**
	 * 新年海报
	 * @User Liangzk
	 * @DateTime 2017-01-20
	 */
	public function newYear()
	{
		if(IS_POST)
		{

			//生产图片保存到服务器
			$createCount = S('ActivityNewYear:createCount'.MODULE_NAME);
			$createCount++;
			$baseImg = I('baseImg');
		
			$imgdata = D('Uploads')->uploadFileBase64($baseImg,'mobile,newyear','','newYear'.$createCount);
			S('ActivityNewYear:createCount'.MODULE_NAME,$createCount);
			$this->ajaxReturn(['status'=>1,'imgUrl'=>Think\Tool\Tool::imagesReplace($imgdata['url']),'imgName'=>$imgdata['url']]);

		}
		$imgHeight = 110;
		if(strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone')||strpos($_SERVER['HTTP_USER_AGENT'], 'iPad')){
			$imgHeight = 105;
		}else if(strpos($_SERVER['HTTP_USER_AGENT'], 'Android')){
			$imgHeight = 115;
		}
		$this->assign('imgHeight',$imgHeight);
		//微信分享內容控制
		$this->wxShar();
		
		$this->display();
	}
	/**
	 * 新年海报
	 * @User Liangzk
	 * @DateTime 2017-01-20
	 */
	public function removeNewYear()
	{
		$imgUrl = I('imgUrl','','string');
		if($imgUrl === ''){
			$this->ajaxReturn(['msg'=> '图片删除失败']);
		}
		$imgPath = str_replace('/Uploads','',$imgUrl);
		$removePath = explode("?",$imgPath)[0];
		
		//执行删除
		$return = D('Uploads')->deleteFile([$removePath]);
		if($return['status'] == 1)
		{
			$this->ajaxReturn(['msg'=> '图片删除成功']);
		}
		$this->ajaxReturn(['msg'=> '图片删除失败']);
	}
}
