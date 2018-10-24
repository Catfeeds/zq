<?php

/**
 * 首页
 *
 * @author liuweitao liuwt@qc.com
 *
 * @since  2017-06-08
 */
use Think\Tool\Tool;
class ActivityController extends CommonController {

    public function index() {
        #code...
    }

	/**
	 * 推荐游戏首页
	 * @User Liangzk
	 * @DateTime 2016-11-30
	 *
	 */
    public function gamble()
	{
		C('HTTP_CACHE_CONTROL','no-cache,no-store');
		$activityId = I('activityId');
		if($activityId){
			$titleRes = M('SingleTitle')->field('id,single_title,game_type,trophy,explain_title,explain_cont,end_time,single_multiple')->where(['id'=>$activityId,'status'=>1])->limit(1)->find();
			if(!$titleRes) $this->error('参数错误');
		}else{
			$titleRes = M('SingleTitle')->field('id,single_title,game_type,trophy,explain_title,explain_cont,end_time,single_multiple')->where(['status'=>1])->order('id desc')->limit(1)->find();
			$activityId = $titleRes['id'];
		}
		
		if (empty($titleRes['id']))
			$this->error('参数错误');

		$single_multiple = $titleRes['single_multiple'];
        $statusfont = '提交答案';

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

		$list = M('SingleList sl')
				->join('LEFT JOIN qc_single_title st ON st.id = sl.single_title_id')
				->where(['sl.single_title_id'=>$activityId,'sl.status'=>1])
				->field('sl.id,st.single_title,sl.single_title_id,sl.game_id,sl.home_team_name,sl.away_team_name,sl.game_time,st.single_multiple')
				->order('sl.game_time asc')
				->select();

		if (! empty($list))
		{
			//活动截止时间
			$this->assign('end_time',date('Y年m月d日 H:i',$titleRes['end_time']));
			//活动详情
			$this->assign('titleRes',$titleRes);
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
			$sort = $gtime = [];
			foreach ($list as $key => $value)
			{
				$list[$key]['home_team_name'] = substr($value['home_team_name'],0,stripos($value['home_team_name'],','));
				$list[$key]['away_team_name'] = substr($value['away_team_name'],0,stripos($value['away_team_name'],','));
				$list[$key]['game_time'] = date('m-d H:i',$value['game_time']);
				$list[$key]['game_time_str'] = $value['game_time'];
				$gtime[] = $value['game_time'];
				//获取活动信息
				foreach ($activityLog as $k => $v)
				{
					if ($value['id'] == $v['single_id'])
					{
						$list[$key]['game'][$k] = $v;
						$sort[] = $v['sort'];
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
			array_multisort($sort,SORT_ASC,$gtime,SORT_ASC,$list);
			//默认主客队logo
			$httpUrl = C('STATIC_SERVER');
			$this->assign('defHomeLogo',$httpUrl.'/Public/Home/images/common/home_def.png');
			$this->assign('defAwayLogo',$httpUrl.'/Public/Home/images/common/away_def.png');
			//dump($list);
			$this->assign('list',$list);
		}
		else
		{
			//获取公告资讯
			$publishList = M('PublishList')
				->where(['is_bbs'=>1,'status'=>1])
				->field('id,remark,short_title,img')
				->order('update_time desc,add_time desc')
				->limit(30)
				->select();

			foreach ($publishList as &$v) {
				$v['img'] = @Think\Tool\Tool::imagesReplace($v['img']);
			}
			$this->assign('publishList',$publishList);
		}

		cookie('userUrl', __SELF__);
		cookie('pageUrl', __SELF__);
		cookie('redirectUrl', __SELF__);
        $ruleText = M('SingleRule')->where(['title_id'=>$activityId])->getField('rule');
        $this->assign('ruleText',$ruleText);

		$this->assign('statusId',$statusId);

		if ($titleRes['end_time'] < time())
		{
			$statusId = 1;
			$statusfont = '活动已结束';
		}
        $this->assign('statusfont',$statusfont);
		$this->assign('activityId',$activityId);
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
        $user_id = is_login();
        if (empty($user_id))
        {
            redirect('/User/login');
            exit;
        }




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
            $userInfo['head'] = frontUserFace($userInfo['head']);
            $this->assign('userInfo',$userInfo);

			//活动列表
			$list = M('SingleTitle')->where(['id'=>['in',$singleTitleIdArr]])->field('id,single_title,end_time')->select();

			//把答案转换 0对应A 1对应B。。。。。类推
			$options = ['A','B','C','S','D','F','G','H','J','K','L','Z','X',
				'V','N','M','Q','W','E','R','T','Y','U','I','O','P'];
			sort($options);
			foreach ($list as $key => $value)
			{
				//获取用户信息
                if($value['end_time'] > time())
                {
                    $list[$key]['status'] = 'ing';
                }else{
                    $list[$key]['status'] = 'end';
                }

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
			$httpUrl = C('STATIC_SERVER');
			$this->assign('defHomeLogo',$httpUrl.'/Public/Home/images/common/home_def.png');
			$this->assign('defAwayLogo',$httpUrl.'/Public/Home/images/common/away_def.png');
			$this->assign('list',$list);

			$ruleText = M('SingleRule')->where(['title_id'=>$activityId])->getField('rule');

			$this->assign('ruleText',$ruleText);

		}


		cookie('userUrl', __SELF__);
		cookie('pageUrl', __SELF__);
		cookie('redirectUrl', __SELF__);
		//默认主客队logo
		$httpUrl = C('STATIC_SERVER');
		$this->assign('defHomeLogo',$httpUrl.'/Public/Home/images/common/home_def.png');
		$this->assign('defAwayLogo',$httpUrl.'/Public/Home/images/common/away_def.png');

		$this->assign('activityId',$activityId);
		$this->assign('list',$list);
        $this->assign('statusId',1);

		$this->display('gamble');
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

        $user_id = is_login();
        if($user_id){
            $userInfo = M('FrontUser')->where(['id'=>$user_id])->field('nick_name,head')->find();
            $userInfo['head'] = frontUserFace($userInfo['head']);
            $this->assign('userInfo',$userInfo);
        }
		$ruleText = M('SingleRule')->where(['title_id'=>$activityId])->getField('rule');
		$this->assign('ruleText',$ruleText);
        $this->assign('activityId',$activityId);
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

	function demo()
	{
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
}
