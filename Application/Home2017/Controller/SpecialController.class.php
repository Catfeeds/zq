<?php
/**
 * 专题管理控制器
 *
 * @author dengweijun <406516482@qq.com>
 *
 * @since  2016-4-11
 */
class SpecialController extends CommonController {
    //欧冠专题
    public function ouguan()
    {
    	//判断时间获取对应该显示的决赛
    	if(time() < strtotime("20160416")){
    		//获取1/4决赛
    		$type = 1;
    	}elseif (time() >= strtotime("20160416") && time() <= strtotime("20160507")) {
    		//获取半决赛
    		$type = 2;
    	}elseif (time() > strtotime("20160507")) {
    		//获取决赛
    		$type = 3;
    	}
    	$this->assign('type',$type);
    	$game = D("SpecialView")->where(['type'=>$type,'status'=>1])->order("g.game_state desc,g.game_date desc,g.game_time desc")->select();
        if(!$game){
            $game = D("SpecialView")->where(['type'=>1,'status'=>1])->order("g.game_state desc,g.game_date desc,g.game_time desc")->select();
        }
    	$game = HandleGamble($game);
    	$game = getTeamLogo($game);
        $this->assign('game',$game);
    	//获取比赛信息与新闻
        $game_news = $game;
        //获取指数
        $Marvellous = A('Index')->getMarvellous();
        $this->assign('Marvellous', $Marvellous);
    	foreach ($game_news as $k => $v) {
    		$game_news[$k]['new'] = M('PublishList')->where(['status'=>1,'game_id'=>$v['game_id'],'is_channel_push'=>1])->field('id,title')->order("update_time desc")->limit(1)->find();
    		$game_news[$k]['news'] = M('PublishList')->where(['status'=>1,'game_id'=>$v['game_id'],'is_channel_push'=>['neq',1]])->field('id,title')->order("update_time desc")->limit(12)->select();
            if(is_null($game_news[$k]['new']) && is_null($game_news[$k]['news'])){
                unset($game_news[$k]);
            }
    	}
        if(count($game_news) < 4){
            $game_news2 = D("SpecialView")->where(['type'=>1,'status'=>1])->order("g.game_state desc,g.game_date desc,g.game_time desc")->limit(4)->select();
            $game_news2 = HandleGamble($game_news2);
            $game_news2 = getTeamLogo($game_news2);
            foreach ($game_news2 as $k => $v) {
                $game_news2[$k]['new'] = M('PublishList')->where(['status'=>1,'game_id'=>$v['game_id'],'is_channel_push'=>1])->field('id,title')->order("update_time desc")->limit(1)->find();
                $game_news2[$k]['news'] = M('PublishList')->where(['status'=>1,'game_id'=>$v['game_id'],'is_channel_push'=>['neq',1]])->field('id,title')->order("update_time desc")->limit(12)->select();
                if(is_null($game_news2[$k]['new']) && is_null($game_news2[$k]['news'])){
                    unset($game_news2[$k]);
                }
            }
            $game_news = array_merge_recursive($game_news,$game_news2);
        }
    	$this->assign('game_news',$game_news);
    	
        foreach ($game as $key => $value) {
            if($value['game_state'] != 0){
                unset($game[$key]);
            }
        }
    	
    	//获取新闻轮播
    	$carousel = Think\Tool\Tool::getRecommend('ouguan',6);
        $this->assign('carousel',$carousel);
        
        //获取视频集锦
        $highlights = $this->getRecommendJJ();
        $this->assign('highlights',$highlights);
        //获取7位名师
        $teacher = M('FrontUser')->where(['is_expert'=>1,'is_recommend'=>1])->field("id,head,nick_name")->order("sort asc")->limit(7)->select();
        $this->assign('teacher',$teacher);
        foreach ($teacher as $k => $v) {
        	$teacherId[] = $v['id'];
        }
        //获取名师今天发布的文章
        $todayStart = strtotime(date("Ymd"));
        $todayEnd = strtotime(date("Ymd"))+86400;
        $news = M('PublishList')->where(['user_id'=>['in',$teacherId],'update_time'=>['between',[$todayStart,$todayEnd]],'status'=>1,'is_channel_push'=>1])->field('id,short_title')->order("update_time desc")->select();
        $this->assign('news',$news);

        //获取票数
        $vote = M('vote')->select();
        $this->assign('vote',$vote);
        $this->display();
    }
    //切换赛事
    public function changeGame(){
        $type = I('type');
        $game = D("SpecialView")->where(['type'=>$type,'status'=>1])->order("g.game_state desc,g.game_date desc,g.game_time desc")->select();
        $game = HandleGamble($game);
        $game = getTeamLogo($game);
        $lis='';
        $lis .= "<div class=\"ladyScroll als-container\" id=\"lista1\">".
                    "<a class=\"prev als-prev\" href=\"javascript:void(0)\"><i class=\"icon-angle-left\"></i></a>".
                    "<div class=\"scrollWrap\">".
                        "<div class=\"dlList\">". 
                            "<ul class=\"clearfix als-wrapper\">";        
        foreach ($game as $k => $v) {
            $li = "<li class=\"als-item\">".
                    "<div class=\"top_time\">".date('m-d',strtotime($v['game_date'])).' '.$v['game_time']."</div>".
                    "<div class=\"cen_sroce\">".
                        "<p class=\"team\"><a href=\"javascript:;\"><span><img src=\"{$v['home_logo']}\" width=\"20\" height=\"20\"></span><em>{$v['home_team_name']}</em></a></p>";
                        switch ($v['game_state']) {
                            case '-1':
                            case '1':
                            case '2':
                            case '3':
                            case '4':
                                $li .= "<p class=\"text-center sroce\">{$v['score']}</p>";
                                break;
                            case '0':
                                $li .= "<p class=\"text-center will_begin\"><span>未开始</span></p>";
                                break;
                            default:
                                $li .= "<p class=\"text-center will_begin\"><span>".C('game_state')[$v['game_state']]."</span></p>";
                                break;
                        }
                    $li .= "<p class=\"team\"><a href=\"javascript:;\"><span><img src=\"{$v['away_logo']}\" width=\"20\" height=\"20\"></span><em>{$v['away_team_name']}</em></a></p>".
                    "</div>".
                    "<div class=\"bot_video clearfix\">".
                        "<span class=\"text-center pull-left\">";
                        switch ($v['type']) {
                            case '1':
                                $li .="1/4决赛";
                                break;
                            case '2':
                                $li .="半决赛";
                                break;
                            case '3':
                                $li .="决赛";
                                break;
                        }
                        $li .= "</span>";
                        if(empty($v['highlights'])){
                            $li .= "<a href=\"javascript:;\" class=\"pull-left\">视频集锦</a>";
                        }else{
                            $li .= "<a target=\"_blank\" href=\"".U('Video/lives',['type'=>1,'game_id'=>$v['game_id'],'jj_id'=>$v['highlights']])."\" class=\"pull-left\">视频集锦</a>";
                        }
                    $li .= "</div>".
                    "</li>";
                    $lis .= $li;
        }
        $lis .= "</ul></div></div><a class=\"next als-next\" href=\"javascript:void(0)\"><i class=\"icon-angle-right\"></i></a></div>"; 
                        
        $this->success($lis);
    }
    //投票
    public function addVote(){
    	$gamevote = I('gamevote');
    	$vote     = I('vote');
    	if(!isset($gamevote) || !isset($vote)){
    		$this->error("参数错误！");
    	}
    	if($gamevote == 1){
    		$lastvote = cookie('lastvote');
    		if(isset($lastvote)){
    		    $this->error("您已经投过票了，感谢您的参与！");
    		}
    		cookie('lastvote',1,86400);
    	}else{
    		$lastvote2 = cookie('lastvote2');
    		if(isset($lastvote2)){
    		    $this->error("您已经投过票了，感谢您的参与！");
    		}
    		cookie('lastvote2',1,86400);
    	}
    	$rs = M('vote')->where(['id'=>$vote])->setInc('number');
    	if($rs){
    		$this->success('投票成功！');
    	}else{
    		$this->error("投票失败！");
    	}
    }

    //欧洲杯专题
    public function euro2016(){
        //获取图片轮播
        $carousel = Think\Tool\Tool::getRecommend('webOZB',5);
        $this->assign('carousel',$carousel);

        //获取球队资讯手写位
        $EuroNews = M('config')->where(['sign'=>'EuroNews'])->getField('config');
        $this->assign('EuroNews', $EuroNews);

        //获取默认法国资讯战报
        $report = M('PublishList')->where(['status'=>1,'class_id'=>40])->field('id,title')->order("update_time desc")->limit(8)->select();
        $this->assign('report', $report);

        //获取分组赛程
        $game = M('gameFbinfo')->where(['years'=>'2014-2016','union_id'=>67,'runno'=>9852])->field('game_id,home_team_name,away_team_name,gtime,score,game_state,rno')->order("rno asc,gtime asc")->select();

        //获取淘汰赛runno
        $taotai = M('gameFbinfo')->where(['years'=>'2014-2016','union_id'=>67])->field('runno')->order("runno asc")->group('runno')->select();
        foreach ($taotai as $k => $v) {
            if($v['runno'] > 9852){
                $runno[] = $v['runno'];
            }
        }
        $gameEuro = M('gameFbinfo')->where(['years'=>'2014-2016','union_id'=>67,'runno'=>['in',$runno]])->field('game_id,home_team_name,away_team_name,gtime,score,game_state,rno,runno')->order("rno asc,gtime asc")->select();
        //分强
        foreach ($gameEuro as $k => $v) {
            if($v['runno'] == $runno[0])
            {
                $shiliu[] = $v;
            }
            if($v['runno'] == $runno[1])
            {
                $baqiang[] = $v;
            }
            if($v['runno'] == $runno[2])
            {
                $siqiang[] = $v;
            }
            if($v['runno'] == $runno[3])
            {
                $erqiang[] = $v;
            }
        }
        $this->assign('shiliu',  $shiliu);
        $this->assign('baqiang', $baqiang);
        $this->assign('siqiang', $siqiang);
        $this->assign('erqiang', $erqiang);
        //分组
        $gameArr = array();
        foreach ($game as $k => $v) {
            if($v['rno'] == 'A'){
                $gameArr['A'][] = $v;
            }
            if($v['rno'] == 'B'){
                $gameArr['B'][] = $v;
            }
            if($v['rno'] == 'C'){
                $gameArr['C'][] = $v;
            }
            if($v['rno'] == 'D'){
                $gameArr['D'][] = $v;
            }
            if($v['rno'] == 'E'){
                $gameArr['E'][] = $v;
            }
            if($v['rno'] == 'F'){
                $gameArr['F'][] = $v;
            }
        }
        $this->assign('gameArr', $gameArr);
        //获取积分榜
        $euro_integral = M('euro_integral')->order("integral desc,score desc,lose_ball asc")->select();
        //分组
        $integral = array();
        foreach ($euro_integral as $k => $v) {
            if($v['group_letter'] == 'A'){
                $integral['A'][] = $v;
            }
            if($v['group_letter'] == 'B'){
                $integral['B'][] = $v;
            }
            if($v['group_letter'] == 'C'){
                $integral['C'][] = $v;
            }
            if($v['group_letter'] == 'D'){
                $integral['D'][] = $v;
            }
            if($v['group_letter'] == 'E'){
                $integral['E'][] = $v;
            }
            if($v['group_letter'] == 'F'){
                $integral['F'][] = $v;
            }
        }
        $this->assign('integral', $integral);

        //获取射手榜
        $euro_scorer = M('euro_scorer')->order("score desc,penalty desc")->select();
        $this->assign('euro_scorer', $euro_scorer);
        
        //获取视频集锦
        $prefix = C('IMG_SERVER');
        $highlights = M('highlights')->field("id,game_id,title,concat('$prefix',img) img")->where(['union_id'=>67,'web_url'=>['neq','']])->limit(8)->order("add_time desc")->select();
        $this->assign('highlights', $highlights);
        $this->display();
    }

    //异步切换赛事战报
    public function getEuroNews(){
        $class_id = I('class_id');
        $EuroNews = M('PublishList')->where(['status'=>1,'class_id'=>$class_id])->field('id,title')->order("update_time desc")->limit(8)->select();
        foreach ($EuroNews as $k => $v) {
            $li .= "<li><a target=\"_blank\" href=\"".U('/info_n/'.$v['id'].'')."\">".$v['title']."</a></li>";
        }
        $this->success($li);
    }
}