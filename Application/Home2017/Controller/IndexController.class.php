<?php
/**
 * 首页
 * @author dengweijun <406516482@qq.com>
 * @since  2015-12-7
 */
use Think\Controller;
use Think\Tool\Tool;
class IndexController extends CommonController 
{
	public function index()
	{
		header('X-Frame-Options: deny');
		if(!$HomeNews = S('web_index_HomeNews'))
		{
			//获取手写位
			$HomeNews = M('config')->where(['sign'=>'HomeNews'])->getField('config');
			S('web_index_HomeNews',$HomeNews,300);
		}
		$this->assign('HomeNews', $HomeNews);
		
		//获取首页图片轮播
		if(!$AdCarousel = S('web_index_AdCarousel'))
		{
			$AdCarousel = Tool::getAdList(1,5);
			S('web_index_AdCarousel',json_encode($AdCarousel),300);
		}
		$this->assign('AdCarousel', $AdCarousel);
		
		$prefix = C('IMG_SERVER');
		
		//获取集锦轮播推荐
		if(!$highlights = S('web_Index_index_highlights'))
		{
			$highlights = M("Highlights")
				->where(['is_best'=>1,'status'=>1])
				->field("id,game_id,game_type,title,img,web_url,web_ischain")
				->order("add_time desc")
				->limit(6)
				->select();
			foreach ($highlights as $key => $value)
			{
				//获取缩略图
				$thumbimg = str_replace($value['id'], $value['id'].'_thumb', $value['img']);
				$highlights[$key]['img'] = $prefix.$thumbimg;
			}
			
			S('web_Index_index_highlights',empty($highlights) ? null : $highlights,300);
		}
		$this->assign('highlights', $highlights);
	
		//回报率排行榜
		// if(!$productsList = S('web_Index_index_productsList'))
		// {
		// 	$productsList = M('IntroProducts')->where(['status'=>1])->field('id,name,logo,total_rate')->order('total_rate desc,ten_num desc')->limit(10)->select();
		// 	foreach ($productsList as $key => $value)
		// 	{
		// 		$productsList[$key]['logo'] = $prefix.$value['logo'];
		// 	}
		// 	S('web_Index_index_productsList',empty($productsList) ? null : $productsList,5*60);
		// }
		// $this->assign('productsList', $productsList);
		
		//球王推荐---产品分类及其产品列表
		// if(!$intro = S('web_Index_index_intro'))
		// {
		// 	$blockTime = getBlockTime(1, true);
		// 	$introClass = M('introClass')->where(['status'=>1])->field('id,name')->order('sort asc')->limit(4)->select();
		// 	$classlist = $productIdArr = array();
		// 	foreach ($introClass as $key => $value)
		// 	{
		// 		$tempList = M('IntroProducts')
		// 			->where(['class_id'=>$value['id'],'status'=>1])
		// 			->field('id,class_id,pay_num,name,desc,logo,sale,total_num,game_num,total_rate,ten_num')
		// 			->order('total_rate desc,ten_num desc')
		// 			->limit(4)
		// 			->select();
		// 		foreach ($tempList as $k => $v)
		// 		{
		// 			$tempList[$k]['desc'] = mb_substr($v['desc'],0,110,'utf-8');
		// 			$tempList[$k]['logo'] = $prefix.$v['logo'];
					
		// 			//获取产品ID
		// 			$productIdArr[] = $v['id'];
		// 		}
		// 		$classlist[$key]['list'] = $tempList;
		// 	}

		// 	//查询实际已预购数量
		// 	$buyListTemp = M('IntroBuy')->where(['product_id'=>['IN',$productIdArr],'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]]])->field('product_id,count(id) as buyNum')->group('product_id')->select();
			
		// 	foreach ($classlist as $k => $v)
		// 	{
		// 		foreach ($v['list'] as $kk => $vv)
		// 		{
		// 			//后台手动设置了预购数量
		// 			$buyNum = $vv['pay_num'];
		// 			foreach ($buyListTemp as $b => $bb)
		// 			{
		// 				if ($vv['id'] == $bb['product_id'])
		// 				{
		// 					//加上实际查看人数
		// 					$buyNum += $bb['buyNum'];
		// 				}
		// 			}
		// 			//查看人数
		// 			$classlist[$k]['list'][$kk]['buyNum'] = $buyNum;
		// 			//查看百分比
		// 			$classlist[$k]['list'][$kk]['buyPercent'] = round($buyNum/$vv['total_num']*100);
		// 		}
		// 	}
			
		// 	$intro['introClass'] = $introClass ? : [];
		// 	$intro['classlist']  = $classlist  ? : [];
		// 	S('web_Index_index_intro',$intro,60);
		// }
		// $this->assign('introClass', $intro['introClass']);
		// $this->assign('classlist', $intro['classlist']);
		
		//获取首页图片轮播
		if(!$AdInfoNew = S('web_index_AdInfoNew'))
		{
			$AdInfoNew = Tool::getAdList(34,3);
			S('web_index_AdInfoNew',json_encode($AdInfoNew),60);
		}
		$this->assign('AdInfoNew', $AdInfoNew);
		
		//足球情报
		$this->assign('bfInfoNew',$this->getBfInfoNew());
		//体育资讯
		$this->assign('messageNew',$this->getMessageNew());
		//《大咖用户》
		$this->assign('list',$this->getDaKaList());
		//《超级用户》
		$this->assign('superList',$this->getCjUserList());
		
		
		//获取精品图库
		// if(!$gallery = S('web_index_gallery'))
		// {
		// 	$gallery = M('gallery')->where(['status'=>1,'home_recommend'=>1])->field('id,short_title,img_array')->order("add_time desc")->limit(5)->select();
		// 	foreach ($gallery as $key => $value)
		// 	{
		// 		$gallery[$key]['images'] = Tool::imagesReplace(json_decode($value['img_array'],true)[1]);
		// 		unset($gallery[$key]['img_array']);
		// 	}
		// 	S('web_index_gallery',$gallery,300);
		// }
		// $this->assign('gallery',$gallery);
	
		//获取首页广告与友情链接
		if(!$Adver = S('web_index_adver'))
		{
			$Adver['AdIndex1'] = Tool::getAdList(2,1);
			$Adver['AdIndex2'] = Tool::getAdList(3,1);
			//$Adver['AdIndex3'] = Tool::getAdList(10,1);
			$Adver['AdIndex4'] = Tool::getAdList(32,1);
			$Adver['AdIndex5'] = Tool::getAdList(33,1);
			//获取友情链接
			$Link = M("Link")->where(array('status'=>1,'position'=>1))->order("sort asc")->select();
			$Adver['Link'] = $Link;
			S('web_index_adver',json_encode($Adver),60);
		}
		
		$this->assign('AdIndex1', $Adver['AdIndex1']);
		$this->assign('AdIndex2', $Adver['AdIndex2']);
		//$this->assign('AdIndex3', $Adver['AdIndex3']);
		$this->assign('AdIndex4', $Adver['AdIndex4']);
		$this->assign('AdIndex5', $Adver['AdIndex5']);
		$this->assign('Link', $Adver['Link']);
		
		$this->display();
	}

	//获取我关注的产品
	public function getIntroFollow()
	{
		//球王---我的关注
		$user_id = is_login();
		if ($user_id > 0)
		{
			//获取登录用户关注的产品
			$followList = M('IntroFollow ifo')
						->join('INNER JOIN qc_intro_products ip ON ifo.product_id = ip.id')
						->where(['ifo.user_id'=>$user_id,'ip.status'=>1])
						->field('ifo.product_id,ip.name,ip.pay_num,ip.desc,ip.logo,ip.sale,ip.total_num,ip.game_num,ip.total_rate,ip.ten_num')
						->order('ip.total_rate desc')
						->limit(4)
						->select();
			if ($followList)
			{
				$productIdArr = array_map("array_shift", $followList);
				$blockTime = getBlockTime(1, true);
				//查询实际已预购数量
				$buyListTemp = M('IntroBuy')->where(['product_id'=>['IN',$productIdArr],'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]]])->field('product_id,count(id) as buyNum')->group('product_id')->select();
				$prefix = C('IMG_SERVER');
				foreach ($followList as $key => $value)
				{
					//后台手动设置了预购数量
					$buyNum = $value['pay_num'];
					foreach ($buyListTemp as $k => $v)
					{
						if ($v['product_id'] == $value['product_id'])
						{
							//加上实际查看人数
							$buyNum += $v['buyNum'];
						}
					}
					$followList[$key]['buyNum'] = $buyNum;
					//查看百分比
					$followList[$key]['buyPercent'] = round($buyNum/$value['total_num']*100);
					$followList[$key]['logo'] = $prefix.$value['logo'];
				}
				$this->ajaxReturn(['code'=>1,'data'=>$followList]);
			}
			else
			{
				$this->ajaxReturn(['code'=>3]);
			}
		}
		else
		{
			$this->ajaxReturn(['code'=>2]);
		}
	}
	
	//足球情报
	public function getBfInfoNew()
	{
		if (!$bfInfoNew = S('web_index_bfInfoNew'))
		{
			//《足球情报-情报分析》独家解盘
			$bfInfoNew['infoAnalyze'] = M('PublishList')
			->where(['class_id'=>10,'status'=>1,'is_original'=>1])
			->field("id,title,is_original")->order("add_time desc")
			->limit(15)->select();
			
			//竞彩前瞻
			$bfInfoNew['asiaInfo'] = M('PublishList')
				->where(['class_id'=>54,'status'=>1,'is_original'=>1])
				->field("id,title,is_original")
				->order("add_time desc")
				->limit(6)->select();
			
			//北单推荐
			$bfInfoNew['europeInfo'] = M('PublishList')
				->where(['class_id'=>55,'status'=>1,'is_original'=>1])
				->field("id,title,is_original")
				->order("add_time desc")
				->limit(5)->select();
			
			//《足球情报-篮球情报》读取竞彩篮球
			$bfInfoNew['jcBkInfo'] = M('PublishList')->where(['class_id'=>[10,61,'or'],'status'=>1,'is_original'=>1,'gamebk_id'=>['gt',0]])->field("id,title,is_original")->order("add_time desc")->limit(6)->select();

			//《足球情报-独家秘笈》
			$bfInfoNew['exclusiveInfo'] = M('PublishList')->where(['class_id'=>62,'status'=>1,'is_original'=>1])->field("id,title,is_original")->order("add_time desc")->limit(36)->select();
			
			S('web_index_bfInfoNew',$bfInfoNew,5*60);
		}
		
		return $bfInfoNew;
	}

	//体育资讯 
	public function getMessageNew()
	{
		if (!$messageNew = S('web_index_messageNew'))
		{
			//获取足球原创资讯
			$fbRecommend = M('PublishList')
			->where(['is_recommend'=>['gt',0],'class_id'=>['IN',[13,14,15,16,17,18,27,28]],'status'=>1,'is_original'=>1])
			->field("id,title,is_original,img,label,remark,content")
			->order("add_time desc")->limit(3)->select();

			foreach ($fbRecommend as $k => $v) {
				if(!empty($v['img'])){
				    $fbRecommend[$k]['img'] = Tool::imagesReplace($v['img']);
				}else{
				    //获取第一张图片
				    $img = Tool::getTextImgUrl(htmlspecialchars_decode($v['content']),false)[0];
				    $fbRecommend[$k]['img']  = $img ? http_to_https($img) : '/Public/Home/images/index/310x202.jpg';
				}
				if(empty($v['remark'])){
					$fbRecommend[$k]['remark'] = str_replace(',', ' ', $v['label']);
				}
				unset($fbRecommend[$k]['content'],$fbRecommend[$k]['label']);
			}
			
			//《体育资讯--足球资讯》获取西甲，德甲，法甲，意甲，欧冠
			$fbRecommend_id = array_map("array_shift", $fbRecommend);
			$fbNews = M('PublishList')
			->where(['id'=>['NOT IN',$fbRecommend_id],'class_id'=>['IN',[13,14,15,16,17,18,27,28]],'status'=>1,'is_original'=>1])
			->field("id,title,is_original")->order("add_time desc")->limit(14)->select();

			//合并
			$fbMessage = array_merge($fbRecommend,$fbNews);

			//取重点推荐与推荐
			$bkRecommend = M('PublishList')
			->where(['is_recommend'=>['gt',0],'class_id'=>['IN',[3,4]],'status'=>1,'is_original'=>1])
			->field("id,title,is_original,img,label,remark,content")
			->order("add_time desc")->limit(3)->select();

			foreach ($bkRecommend as $k => $v) {
				if(!empty($v['img'])){
				    $bkRecommend[$k]['img'] = Tool::imagesReplace($v['img']);
				}else{
				    //获取第一张图片
				    $img = Tool::getTextImgUrl(htmlspecialchars_decode($v['content']),false)[0];
				    $bkRecommend[$k]['img']  = $img ? http_to_https($img) : '/Public/Home/images/index/310x202.jpg';
				}
				if(empty($v['remark'])){
					$bkRecommend[$k]['remark'] = str_replace(',', ' ', $v['label']);
				}
				unset($bkRecommend[$k]['content'],$bkRecommend[$k]['label']);
			}
			
			//《体育资讯--蓝球资讯》获取NBA，CBA
			$bkRecommend_id = array_map("array_shift", $bkRecommend);
			$bkNews = M('PublishList')
			->where(['id'=>['NOT IN',$bkRecommend_id],'class_id'=>['IN',[3,4]],'status'=>1,'is_original'=>1])
			->field("id,title,is_original")->order("add_time desc")->limit(14)->select();

			//合并
			$bkMessage = array_merge($bkRecommend,$bkNews);

			$messageNew['fbMessage'] = $fbMessage;
			$messageNew['bkMessage'] = $bkMessage;
			S('web_index_messageNew',$messageNew,5*60);
		}
		
		return $messageNew;
	}

	//《大咖用户》
	public function getDaKaList(){
		if (!$killer = S('web_Index_getDaKaList')){
			//亚盘
			$ypKiller = M('rankingList r')
                 ->join("LEFT JOIN qc_front_user f on f.id=r.user_id")
                 ->field("r.*,f.nick_name,f.lv,f.lv_bk,f.head,f.is_robot,f.gamble_num as five_num")
                 ->where("r.gameType=1 and r.dateType=1 and r.ranking <= 100")
                 ->order('r.end_date desc')
                 ->limit(100)
                 ->select();

            //竞彩
			$jcKiller = M('rankBetting r')
	            ->field("r.id,r.user_id,r.ranking,r.gameType,r.gameCount,r.win,r.transport,r.winrate,r.pointCount,f.head,f.lv,f.lv_bet,f.nick_name,f.bet_num as five_num")
	            ->join('left join qc_front_user f on f.id = r.user_id')
	            ->where("r.gameType=1 and r.dateType=1 and r.ranking <= 100")
	            ->order('r.listDate desc')
	            ->limit(100)
	            ->select();

			foreach ($ypKiller as $k => $v) {
				foreach ($jcKiller as $kk => $vv) {
					if($v['user_id'] == $vv['user_id']){
						//两个榜都上了，取近5中几高的
						if($v['five_num'] > $vv['five_num']){
							unset($jcKiller[$kk]);
						}else{
							unset($ypKiller[$k]);
						}
					}
					//近5中3以下不要
					if($vv['five_num'] < 3){
						unset($jcKiller[$kk]);
					}
				}
				//近5中3以下不要
				if($v['five_num'] < 3){
					unset($ypKiller[$k]);
				}
			}
			$killer = array_merge($ypKiller,$jcKiller);
			shuffle($killer);
			$killer = array_slice($killer, 0,6);

			foreach ($killer as $k => $v) {
				//获取最新一条推荐
				$gamble = M('Gamble')->field('home_team_name,away_team_name,play_type')->where(['user_id'=>$v['user_id']])->order('id desc')->find();
				$killer[$k]['home_team_name'] = switchName(0,$gamble['home_team_name']);
				$killer[$k]['away_team_name'] = switchName(0,$gamble['away_team_name']);
				$killer[$k]['play_type'] = $gamble['play_type'];
				$killer[$k]['face']      = frontUserFace($v['head']);
			}
			S('web_Index_getDaKaList',$killer,15*60);
		}
		return $killer;
	}

	//《超级用户》
	public function getCjUserList(){
		if (!$killer = S('web_Index_getCjUserList'))
		{
			$where['g.create_time'] = ['gt',strtotime('-1 day')];
			$ypKiller = M('FrontUser f')->join("RIGHT JOIN qc_gamble g on g.user_id = f.id")->field('f.id as user_id,f.head,f.nick_name,f.gamble_num as five_num')->where($where)->order('f.gamble_num desc')->limit(100)->group('f.id')->select();
			$jcKiller = M('FrontUser f')->join("RIGHT JOIN qc_gamble g on g.user_id = f.id")->field('f.id as user_id,f.head,f.nick_name,f.bet_num as five_num')->where($where)->order('bet_num desc')->limit(100)->group('f.id')->select();
			foreach ($ypKiller as $k => $v) {
				foreach ($jcKiller as $kk => $vv) {
					if($v['user_id'] == $vv['user_id']){
						//两个都有，取近5中几高的
						if($v['five_num'] > $vv['five_num']){
							unset($jcKiller[$kk]);
						}else{
							unset($ypKiller[$k]);
						}
					}
					//近5中3以下不要
					if($vv['five_num'] < 3){
						unset($jcKiller[$kk]);
					}
				}
				//近5中3以下不要
				if($v['five_num'] < 3){
					unset($ypKiller[$k]);
				}
			}
			$killer = array_merge($ypKiller,$jcKiller);
			shuffle($killer);
			$killer = array_slice($killer, 0,6);

			foreach ($killer as $k => $v) {
				$killer[$k]['face'] = frontUserFace($v['head']);
				//获取最新一条推荐
				$gamble = M('Gamble')->field('home_team_name,away_team_name,play_type')->where(['user_id'=>$v['user_id']])->order('id desc')->find();
				$killer[$k]['home_team_name'] = switchName(0,$gamble['home_team_name']);
				$killer[$k]['away_team_name'] = switchName(0,$gamble['away_team_name']);
				$killer[$k]['play_type'] = $gamble['play_type'];
			}
			S('web_Index_getCjUserList',$killer,15*60);
		}
		return $killer;
	}

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
                $Marvellous[$k]['guestNumber'] = $Marvellous[$k]['mainNumber'] > 0 ? 100 - $Marvellous[$k]['mainNumber'] : round($vawayCount/$letCount*100) ;
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

    public function test()
    {

        $this->display();
    }

    //app下载跳转
    public function erWeiMa()
    {
        $this->display();
    }

    //刷新清除首页redis缓存
    public function delCache(){
    	S('web_index_HomeNews',null);
		S('web_index_AdCarousel',null);
		S('web_Index_index_highlights',null);
		S('web_index_AdInfoNew',null);
		S('web_index_adver',null);
		S('web_index_bfInfoNew',null);
		S('web_index_messageNew',null);
		S('web_Index_getDaKaList',null);
		S('web_Index_getCjUserList',null);
		dump(S('web_index_HomeNews'));
		dump(S('web_index_AdCarousel'));
		dump(S('web_Index_index_highlights'));
		dump(S('web_index_AdInfoNew'));
		dump(S('web_index_adver'));
		dump(S('web_index_bfInfoNew'));
		dump(S('web_index_messageNew'));
		dump(S('web_Index_getDaKaList'));
		dump(S('web_Index_getCjUserList'));
		
    }
}