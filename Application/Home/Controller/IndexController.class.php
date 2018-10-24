<?php
/**
 * 首页
 * @author dengweijun <406516482@qq.com>
 * @since  2018-1-17
 */
use Think\Controller;
use Think\Tool\Tool;
use Home\Services\WebfbService;

class IndexController extends CommonController
{
	/**
	 * web新首页
	 */
	public function index()
	{
	    $UserData = session('user_auth');
		$from = I('from');
		if(isMobile() && $from != 'm'){
			redirect(U("/@m"));
		}
		//手机站链接适配
		$this->assign('mobileAgent', U('/@m'));
//		G('begin');
		header('X-Frame-Options: deny');
		//导航栏1
		$topNav = D('Home')->getNavList(4, 'name, ui_type_value as url');
		$this->assign('topNav', $topNav);

		//导航栏2
		$topNav2 = D('Home')->getNavList(26, 'name, ui_type_value as url');
		$this->assign('topNav2', $topNav2);

		//导航栏3
		$topNav3 = D('Home')->getNavList(27, 'name, ui_type_value as url');
		$this->assign('topNav3', $topNav3);

		//导航栏4
		$topNav4 = D('Home')->getNavList(28, 'name, ui_type_value as url');
		$this->assign('topNav4', $topNav4);

		//导航栏5
		$topNav5 = D('Home')->getNavList(29, 'name, ui_type_value as url');
		$this->assign('topNav5', $topNav5);

		//第一屏：左侧部分：资讯大标题、小标题、标签
		//随机4个从以下分类的资讯获取：英超,西甲,德甲,意甲,欧冠,亚冠,世界杯,中超,NBA,CBA
		if(!$newsOne = S('web_index_news_one')) {
			$newsOne = D('Home')->getNewsList();
			S('web_index_news_one',json_encode($newsOne), 60*3);
		}
		$this->assign('newsOne', $newsOne);

		//中间部分：轮播图、精彩视频、小标题视频
		//轮播图
		if(!$banner = S('web_index_banner_one')) {
			$banner = Tool::getAdList(1, 5);
			S('web_index_banner_one',json_encode($banner), 60*5);
		}
		$this->assign('banner', $banner);

		//精彩视频、小标题视频
		if(!$highlights = S('web_index_highlight')) {
			$highlights = D('Home')->getHighlights();

			S('web_index_highlight', json_encode($highlights), 60*5);
		}
		$this->assign('highlights', $highlights);

		//右侧部分：显示正在直播比赛
//		$liveGame = D('Home')->getLiveGame();
//		$this->assign('liveGame', $liveGame);

		//第一屏和第二屏中间banner：Web首页广告1
		if(!$bigBanner = S('web_index_big_banner')) {
			$bigBanner = Tool::getAdList(45, 1);

			S('web_index_big_banner', json_encode($bigBanner), 60*5);
		}
		$this->assign('bigBanner', $bigBanner);


		//第二屏足球栏目配置
		if(!$fbNav = S('web_index_fb_nav')) {
			$fbNav = D('Home')->getNavList(5, 'name, ui_type_value as url');

			S('web_index_fb_nav', json_encode($fbNav), 60*20);
		}
		$this->assign('fbNav', $fbNav);

		//第二屏：国际足球，左边资讯
		if(!$newsTwo = S('web_index_new_two')) {
			$newsTwo = D('Home')->getIndexNewsTwo();

			S('web_index_new_two', json_encode($newsTwo), 60*5);
		}
		$this->assign('newsTwo', $newsTwo);

		//第二屏中间
		if(!$newsTwoMiddle = S('web_index_new_two_middle')) {
			$newsTwoMiddle = D('Home')->getIndexNewsTwoMiddle();

			S('web_index_new_two_middle', json_encode($newsTwoMiddle), 60*5);
		}
		$this->assign('newsTwoMiddle', $newsTwoMiddle);

		//第二屏右边，积分榜，射手榜，活动专题
		if(!$newsTwoRight = S('web_index_new_two_right')) {
			$newsTwoRight = D('Home')->getIndexNewsTwoRight(true);
			//dump($newsTwoRight);
			S('web_index_new_two_right', json_encode($newsTwoRight), 60*5);
		}
		$this->assign('newsTwoRight', $newsTwoRight);


		//第二屏：国内足球
		if(!$newsTwoDown = S('web_index_new_two_down')) {
			$newsTwoDown = D('Home')->getIndexNewsTwoDown();

			S('web_index_new_two_down', json_encode($newsTwoDown), 60*5);
		}
		$this->assign('newsTwoDown', $newsTwoDown);

		//第三屏：篮球专栏；
		if(!$newsThird = S('web_index_new_third')) {
			$newsThird = D('Home')->getIndexNewsThird();

			S('web_index_new_third', json_encode($newsThird), 60*5);
		}
		$this->assign('newsThird', $newsThird);

		//第四屏：综合体育
		if(!$newsFour = S('web_index_new_four')) {
			$newsFour = D('Home')->getIndexNewsFour();

			S('web_index_new_four', json_encode($newsFour), 60*5);
		}
		$this->assign('newsFour', $newsFour);

		//第四屏：电竞模块
		if(!$newsFourGame = S('web_index_new_four_game')) {
			$newsFourGame = D('Home')->getIndexNewsFourGame();

			S('web_index_new_four_game', json_encode($newsFourGame), 60*5);
		}

		$this->assign('newsFourGame', $newsFourGame);

		//友情链接
		if(!$linkArr = S('web_index_link')) {
			$linkArr = M('link')->field('name, url')->where(['status' => 1, 'position' => 1])->order('sort asc')->select();
			S('web_index_link', json_encode($linkArr), 600);
		}

		$this->assign('linkArr', $linkArr);

		// 前瞻 战报
		$special = A('Special');
		$prospect = $special->getProspect('all');
		$report = $special->getReport('all');
        if($prospect || $report){
            $prospect_report = True;
        }else{
            $prospect_report = False;
        }
        $this->assign('prospect_report', $prospect_report);
        $this->assign('prospect', $prospect);
        $this->assign('report', $report);

//		G('end');
//		echo G('begin','end').'s';
		$this->display();
	}

	//赛事指数公用方法
	public function getMarvellous()
    {
        if(!$Marvellous = S('web_'.CONTROLLER_NAME.'mar'))
        {
            //获取今日精彩比赛(11点更新)
            $Marvellous  = D('GambleHall')->getGameFbinfo(1);
            //处理字体
            $Marvellous = HandleGamble($Marvellous);
            //获取球队logo
            setTeamLogo($Marvellous);

            //获取推荐人数
            foreach ($Marvellous as $k => $v)
            {
                $gamePlay = M('gamble')->field('play_type,chose_side')->where(array('game_id'=>$v['game_id'],'play_type'=>['in',[1,-1]]))->select();
                $letCount = $homeCount = $awayCount = $sizeCount = $bigCount = $smallCount = 0;
                foreach ($gamePlay as $kk => $vv)
                {
                    switch ($vv['play_type'])
                    {
                        case '1':
                            if($vv['chose_side'] == '1') $homeCount++;  //推荐主队的人数
                            if($vv['chose_side'] == '-1') $awayCount++; //推荐客队的人数
                            break;
                        case '-1':
                            if($vv['chose_side'] == '1') $bigCount++;    //推荐大的人数
                            if($vv['chose_side'] == '-1') $smallCount++; //推荐小的人数
                            break;
                    }
                }
                $letCount  = $homeCount + $awayCount; //推荐让分总人数
                $sizeCount = $bigCount + $smallCount; //推荐大小总人数
                //主队百分比
                $Marvellous[$k]['mainNumber'] = round($homeCount/$letCount*100);
                //客队百分比
                $Marvellous[$k]['guestNumber'] = $Marvellous[$k]['mainNumber'] > 0 ? 100 - $Marvellous[$k]['mainNumber'] : round($awayCount/$letCount*100) ;
                //大百分比
                $Marvellous[$k]['bigNumber'] = round($bigCount/$sizeCount*100);
                //小百分比
                $Marvellous[$k]['smallNumber'] = $Marvellous[$k]['bigNumber'] > 0 ? 100 - $Marvellous[$k]['bigNumber'] : round($smallCount/$sizeCount*100) ;
                //让分与大小总人数(用于排序)
                $Marvellous[$k]['allCount'] = $letCount+$sizeCount;
            }
            //对数组进行排序,按总人数
            foreach ($Marvellous as $v) {
                $allCount[] = $v['allCount'];
            }
            array_multisort($allCount, SORT_DESC,$Marvellous);
            S('web_'.CONTROLLER_NAME.'mar',json_encode($Marvellous),30);
        }
        return $Marvellous;
    }

    //app下载跳转
    public function erWeiMa()
    {
        $this->display();
    }

	/**
	 * 获取联赛积分榜
	 */
	public function getLeagueData(){
		$unionId = I('unionId') ?:0;
		$group   = I('group') ?: 'A';

		$appService = new WebfbService();
        $data  = $appService->getFbUnionRank($unionId,10,$group);

		if($data){
		    foreach ($data as $dk => $dv){
		        $data[$dk]['url'] = U('/team/' . $dv['team_id'] . '@data', '', 'html');
            }
			$this->ajaxReturn(['status' => 1, 'info' => $data]);
		}else{
			$this->ajaxReturn(['status' => 0, 'info' => '没有更多了']);
		}
	}

	//获取联赛射手榜
	public function getArcherData(){
		$unionId = I('unionId') ?:0;

		$appService = new WebfbService();
        $data  = $appService->getFbUnionArcher($unionId);

		if($data){
            foreach ($data as $dk => $dv){
                $data[$dk]['p_url'] = U('/player/' . $dv['player_id'] . '@data', '', 'html');
                $data[$dk]['t_url'] = U('/team/' . $dv['team_id'] . '@data', '', 'html');
            }
			$this->ajaxReturn(['status' => 1, 'info' => $data]);
		}else{
			$this->ajaxReturn(['status' => 0, 'info' => '没有更多了']);
		}
	}

	/**
	 * 获取篮球积分数据
	 * $unionId int 联赛ID（NBA:1，CBA:5）
	 * $type    类型：1、西部联赛积分；2、东部联赛积分；3，得分榜；4，助攻榜；5、篮板榜 PS：CBA没有东西部分开，联赛积分请求1
	 */
	public function getBkData(){
		$unionId = $_POST['unionId'] ?: 0;
		$type    = $_POST['type'] ?: 0;

		$data = D('Home')->getIndexBkData($unionId, $type);

		if($data){
			$this->ajaxReturn(['status' => 1, 'info' => $data]);
		}else{
			$this->ajaxReturn(['status' => 0, 'info' => '没有更多了']);
		}
	}

	/**
	 * 获取首页直播赛事
	 */
	public function getLiveGames(){
		$liveGame = D('Home')->getIndexLiveGames();

		if($liveGame){
			$this->ajaxReturn(['status' => 1, 'info' => $liveGame]);
		}else{
			$this->ajaxReturn(['status' => 0, 'info' => '没有更多了']);
		}
	}

}