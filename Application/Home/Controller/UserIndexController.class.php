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
        $id = I('user_id', 0, 'intval');
        if(empty($id)){
            $this->_empty();
        }
        $game_type = I('game_type', 1, 'intval');
        $gamble_type = I('gamble_type', 1, 'intval');
        if(explode('?', $_SERVER['REQUEST_URI'])[1] == 'bet' && $game_type == 1){
            $gamble_type = 2;
            $_REQUEST['gamble_type'] = 2;
        }
        $user = M('FrontUser')->where(['id'=>$id])->field('id,lv,lv_bk,lv_bet,nick_name,descript,head,is_expert,avder')->find();
        if($game_type==3 && $user['is_expert']!=1) $game_type = 1;
        $this->assign('game_type',$game_type);
        $this->assign('gamble_type',$gamble_type);
        if(!$user){
            $this->_empty();
        }
        switch ($game_type)  //对应等级
        {
            case '1':
                $Lv = $gamble_type == 1 ? $user['lv'] : $user['lv_bet'];
                $map['play_type'] = $gamble_type == 1 ? ['in',[1,-1]] : ['in',[2,-2]];
                break;
            case '2':
                $Lv = $user['lv_bk'];
                break;
        }
        $this->assign('Lv', $Lv);
        $user['face'] = frontUserFace($user['head']);
        $user['number'] = M('FollowUser')->where(['follow_id'=>$id])->count();//获取粉丝条数

        if($game_type == 3)
        {
            //内容列表
            $where = array();
            $where['user_id'] = $id;
            $where['status'] = 1;
            $where['class_id'] = 10;
            $where['add_time'] = ['lt',time()];
            $list = $this->_list(M('PublishList'),$where,'5','add_time desc','id,class_id,title,remark,img,add_time,click_number','',"",2);
            $classArr = getPublishClass(0);
            foreach($list as $key=>$value)
            {
                if(!empty($value['img'])){
                    $list[$key]['img'] = Tool::imagesReplace($value['img']);
                }else{
                    //获取第一张图片
                    $list[$key]['img']  = Tool::getTextImgUrl(htmlspecialchars_decode($value['content']),false)[0] ?:staticDomain('/Public/Home/images/common/loading.png');
                }
                $list[$key]['add_time'] = date("Y-m-d H:i",$value['add_time']);
                $list[$key]['click_number'] = addClickConfig(1, $value['class_id'],$value['click_number'], $value['id']);
                $list[$key]['href'] = newsUrl($value['id'], $value['add_time'], $value['class_id'], $classArr);
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

        //查看粉丝信息
        $user_id = is_login();//获取会员登录id
        if($user_id)
        {
            $follow = M('FollowUser')->where(['user_id'=>$user_id,'follow_id'=>$id])->find();
            $this->assign('follow',$follow);

            //已查看的推荐
            $gambleIdArr = array_map("array_shift", $history);
            $checkArr = M('quizLog')->master(true)->where(['user_id'=>$user_id,'gamble_id'=>['in',$gambleIdArr],'game_type'=>$game_type])->getField('gamble_id',true);
            $this->assign('checkArr',$checkArr);
        }

        $this->assign('history',$history);
        if(!$rankingData = S('web_userindex_rank'))
        {
            //显示左侧足球排行榜
//            $footRankWeek  = $this->getRankingData(1,1,null,true,10);
//            $this->getBallRecord($footRankWeek);
//            $rankingData['footRankWeek']  = $footRankWeek;

            // $footRankMonth  = $this->getRankingData(1,2,null,true,10);
            // $this->getBallRecord($footRankMonth);
            // $rankingData['footRankMonth'] = $footRankMonth;

            // $footRankSeason = $this->getRankingData(1,3,null,true,10);
            // $this->getBallRecord($footRankSeason);
            // $rankingData['footRankSeason']= $footRankSeason;

            // //显示左侧足球竞彩排行榜
            // $BettingWeek   = D('Common')->getRankBetting(1,1,null,10,true);
            // $this->getBallRecord($BettingWeek,2);
            // $rankingData['BettingWeek']   = $BettingWeek;

            // $BettingMonth  = D('Common')->getRankBetting(1,2,null,10,true);
            // $this->getBallRecord($BettingMonth,2);
            // $rankingData['BettingMonth']  = $BettingMonth;

            // $BettingSeason = D('Common')->getRankBetting(1,3,null,10,true);
            // $this->getBallRecord($BettingSeason,2);
            // $rankingData['BettingSeason'] = $BettingSeason;

            //S('web_userindex_rank',json_encode($rankingData),86400);
        }

//        $this->assign('footRankWeek',  $rankingData['footRankWeek']  );
        // $this->assign('footRankMonth', $rankingData['footRankMonth'] );
        // $this->assign('footRankSeason',$rankingData['footRankSeason']);
        // $this->assign('BettingWeek',   $rankingData['BettingWeek']   );
        // $this->assign('BettingMonth',  $rankingData['BettingMonth']  );
        // $this->assign('BettingSeason', $rankingData['BettingSeason'] );

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

    //ajax加载个人中心左侧排行榜
    public function ajaxRank(){
        $change = I('change',1,'int');
        if(I('type') == 2)
            $data = D('Common')->getRankBetting(1,$change,null,10,true);
        else
            $data  = $this->getRankingData(1,$change,null,true,10);
        $this->getBallRecord($data);
        $tmp = '';
        foreach($data as $k=>$v)
        {
            if($v['ranking'] < 4)
                $class = 'rank-order';
            else
                $class = 'rank-order02';
            $ten_tmp = '';
            foreach($v['Winning']['tenGambleArr'] as $vv)
            {
                switch($vv)
                {
                    case 1:
                        $ten_tmp .= '<em class="text-red">胜</em>';
                        break;
                    case 0.5:
                        $ten_tmp .= '<em class="text-red">胜半</em>';
                        break;
                    case 2:
                        $ten_tmp .= '<em class="text-8a">平</em>';
                        break;
                    case -1:
                        $ten_tmp .= '<em class="text-green">负</em>';
                        break;
                    case -0.5:
                        $ten_tmp .= '<em class="text-green">负半</em>';
                        break;

                }
            }
            if($v['gameType'] == 2) $class_hidden = ' hidden';
            $tmp .= '<tr>
                        <td>
                            <span class="'.$class.'">'.$v['ranking'].'</span>
                        </td>
                        <td class="td02">
                            <a href="//www.'.DOMAIN.'/userindex/'.$v['user_id'].'.html" target="_blank" class="text-orange">'.$v['nick_name'].'</a>
                            <div class="myRecord" style="display: none; opacity: 1;">
                                <div class="arrow-l"></div>
                                <ul>
                                    <li class="re-li user-name">'.$v['nick_name'].'</li>
                                    <li class="re-li"><span class="even01">当前连胜：<em class="text-red">'.$v['Winning']['curr_victs'].'</em></span><span class="even02">最大连胜：<em class="text-red">'.$v['Winning']['max_victs'].'</em></span></li>
                                    <li class="re-li">
                                        <div class="ten">
                                            <span>近10场</span>
                                            '.$ten_tmp.'    
                                         </div>
                                        <div class="week clearfix">
                                            <div class="pull-left title">周成绩</div>
                                            <div class="pull-left data">
                                                <ul clearfix="">
                                                    <li class="w_40"><em>'.$v['RankWeek']['count'].'</em><span>场</span></li>
                                                    <li class="w_40"><em class="text-red mr">'.$v['RankWeek']['win'].'</em><span>胜</span></li>
                                                    <li class="w_50 "><em class="text-red mr'.$class_hidden.'">'.$v['RankWeek']['half'].'</em><span>胜半</span></li>
                                                    <li class="w_40"><em class="text-8a mr">'.$v['RankWeek']['level'].'</em><span>平</span></li>
                                                    <li class="w_40"><em class="text-green mr">'.$v['RankWeek']['transport'].'</em><span>负</span></li>
                                                    <li class="w_50 "><em class="text-green mr'.$class_hidden.'">'.$v['RankWeek']['donate'].'</em><span>负半</span></li>
                                                    <li class="w_62"><span>胜率</span><em class="text-red ml">'.$v['RankWeek']['winrate'].'%</em></li>
                                                    <li><span>获得积分</span><em class="text-orange ml">'.$v['RankWeek']['pointCount'].'</em></li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="month clearfix">
                                            <div class="pull-left title">月成绩</div>
                                            <div class="pull-left data">
                                                <ul clearfix="">
                                                    <li class="w_40"><em>'.$v['RankMonth']['count'].'</em><span>场</span></li>
                                                    <li class="w_40"><em class="text-red mr">'.$v['RankMonth']['win'].'</em><span>胜</span></li>
                                                    <li class="w_50 "><em class="text-red mr'.$class_hidden.'">'.$v['RankMonth']['half'].'</em><span>胜半</span></li>
                                                    <li class="w_40"><em class="text-8a mr">'.$v['RankMonth']['level'].'</em><span>平</span></li>
                                                    <li class="w_40"><em class="text-green mr">'.$v['RankMonth']['transport'].'</em><span>负</span></li>
                                                    <li class="w_50 "><em class="text-green mr'.$class_hidden.'">'.$v['RankMonth']['donate'].'</em><span>负半</span></li>
                                                    <li class="w_62"><span>胜率</span><em class="text-red ml">'.$v['RankMonth']['winrate'].'%</em></li>
                                                    <li><span>获得积分</span><em class="text-orange ml">'.$v['RankMonth']['pointCount'].'</em></li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="season clearfix">
                                            <div class="pull-left title">季成绩</div>
                                            <div class="pull-left data">
                                                <ul clearfix="">
                                                    <li class="w_40"><em>'.$v['RankSeason']['count'].'</em><span>场</span></li>
                                                    <li class="w_40"><em class="text-red mr">'.$v['RankSeason']['win'].'</em><span>胜</span></li>
                                                    <li class="w_50 "><em class="text-red mr'.$class_hidden.'">'.$v['RankSeason']['half'].'</em><span>胜半</span></li>
                                                    <li class="w_40"><em class="text-8a mr">'.$v['RankSeason']['level'].'</em><span>平</span></li>
                                                    <li class="w_40"><em class="text-green mr">'.$v['RankSeason']['transport'].'</em><span>负</span></li>
                                                    <li class="w_50 "><em class="text-green mr'.$class_hidden.'">'.$v['RankSeason']['donate'].'</em><span>负半</span></li>
                                                    <li class="w_62"><span>胜率</span><em class="text-red ml">'.$v['RankSeason']['winrate'].'%</em></li>
                                                    <li><span>获得积分</span><em class="text-orange ml">'.$v['RankSeason']['pointCount'].'</em></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </td>
                        <td class="text-555">推'.$v["gameCount"].'W'.($v["win"] + $v["half"]).'</td>
                        <td class="text-red">'.$v['winrate'].'%</td>
                    </tr>';
        }

        $html = '<tbody>
                    <tr class="tr01">
                        <td class="text-999">排名</td>
                        <td class="text-999">昵称</td>
                        <td class="text-999">场次/胜</td>
                        <td class="text-999">胜率</td>
                    </tr>                        
                  '.$tmp.'
                 </tbody>';
        echo $html;
    }


    //专家个人主页
    public function expUser(){
        //获取专家信息
        $user_id = I('user_id');
//        $user_id = 2193;
        $user = A("Home/Video")->author_info($user_id);
        if($user['is_expert'] != 1) redirect(U('userindex/'.$user_id));
        $user['followNum'] = M('FollowUser')->where(['follow_id'=>$user_id])->count();
        $this->assign('user',$user);
        //获取独家解盘
        $map['class_id'] = 10;
        $map['status']   = 1;

        $classArr = getPublishClass(0);
        $quiz = M('publishList')->where($map)->field("id,add_time,class_id,title")->order("update_time desc")->limit(8)->select();
        foreach ($quiz as $k => $v) {
            $quiz[$k]['href'] = newsUrl($v['id'], $v['add_time'], $v['class_id'], $classArr);
        }
        //获取推荐指数
        $Marvellous = A('Index')->getMarvellous();
        $this->assign('Marvellous', $Marvellous);
        $this->assign('quiz',$quiz);
        //判断是否关注
        if(is_login() > 0)
        {
            $follow = M("FollowUser")->where(['follow_id'=>$user_id,'user_id'=>is_login()])->find();
            if($follow) $this->assign('is_follow',1);
        }
        $data['news'] = $this->NewListHtml($user_id,1,0,time());//资讯文章
        $data['tuijian'] = $this->NewListHtml($user_id,2,0,time());//推荐文章
        $data['video'] = $this->NewListHtml($user_id,3,0,time());//视频
        $this->assign('listH',$data);
        $this->display();
    }

    //ajax加载专家个人主页列表
    public function ajaxNewList()
    {
        $user_id = I('user_id',0,'int');
        $class = I('classType',0,'int');//2为推荐,3为视频集锦,其他为资讯
        $page = I('page',0,'int');
        $time = I('time',0,'int');

        if($user_id > 0)
        {
            $data = $this->NewListHtml($user_id,$class,$page,$time);
            $this->success(['html'=>$data['html'],'page'=>$data['page']]);
        }else{
            echo '';
        }
    }

    //获取组装的html
    public function NewListHtml($user_id,$class,$page,$time)
    {
        $data = $this->expNewList($user_id,$class,$page,$time);
        if($data['list'])
        {
            $list = $data['list'];
            $className = $data['class'];
            $html = '';
            if($class == 3)
                $classArr = getVideoClass(0); //视频分类数组
            else
                $classArr = getPublishClass(0); //资讯分类数组
            foreach($list as $v)
            {
                $class_name = '';
                if($class == 3)
                    $url = videoUrl($v,$classArr);
                else
                    $url = newsUrl($v['id'],$v['add_time'],$v['class_id'],$classArr);
                $html .= '<li class="liInfor clearfix"> 
                            <a href="'.$url.'" title="'.$v['title'].'" target="_blank" class="pull-left inforImg"><img src="'.$v['img'].'" width="180" height="124" alt="'.$v['title'].'"></a>
                            <a href="'.$url.'" title="'.$v['title'].'" target="_blank" class="pull-left inforArt">
                                <h2 class="text-hidden">'.$v['title'].'</h2>
                                <p class="text-999 inforArtP">'.$v['remark'].'</p>
                                <div class="clearfix author">
                                    <span class="pull-left text-999 leftUser">'.date('Y-m-d H:i',$v['add_time']).'</span>
                                    <span class="pull-right text-999 rightEye">'.$v['click_number'].'</span>
                                </div>
                            </a>
                        </li>';
            }
        }else{
            $html = '';
        }
        $data['html'] = $html;
        return $data;
    }

    //专家个人主页文章列表处理方法,ajax调用
    public function expNewList($user_id,$class_type,$page=0,$time)
    {
        //内容列表
        $time = $time?$time:time();
        $num = 30;
        $where['user_id'] = $user_id;
        $where['status'] = 1;
        $where['add_time'] = ['lt',$time];
        $table = M('PublishList');
        $class = M('PublishClass');
        switch($class_type) {
            case 2:
                $where['class_id'] = ['eq', 10];
                $url = '/listType/2';
                $field = 'content,click_number';
                break;
            case 3:
//                $where['class_id'] = ['eq', 10];
                $table = M('Highlights');
                $class = M('HighlightsClass');
                $field = 'click_num as click_number,web_ischain';
                break;
            default:
                $where['class_id'] = ['neq', 10];
                $field = 'content,click_number';
                break;
        }
        $limit_page = $page * $num;
        $list = $table->field('id,class_id,title,remark,img,add_time,status,remark,'.$field)->where($where)->order('add_time desc')->limit($limit_page.','.$num)->select();
        $class_id = [];
        foreach($list as $key=>$value)
        {
            $class_id[] = $value['class_id'];
            $list[$key]['img']  = newsImgReplace($value);
            $list[$key]['click_number'] = addClickConfig(1, $value['class_id'],$value['click_number'], $value['id']);
            //处理简介
            if(empty($value['remark']) && !empty($value['content']))
            {
                $content = strip_tags(htmlspecialchars_decode($value['content']));
                $list[$key]['remark'] = mb_substr($content,0,90,'utf-8').'...';
            }
            unset($list[$key]['content']);
        }
        $class_id = array_filter(array_unique($class_id));
        if($class_id)
        {
            $class_data = $class->where(['id'=>['in',$class_id]])->getField('id,name');
        }

        //处理分页
        $news_count = $table->field('id,class_id,title,remark,img,add_time,status,remark,'.$field)->where($where)->count();
        $page = A('Home/GalleryIndex')->getPage($news_count, $page+1, $num,6);

        return ['list'=>$list,'class'=>$class_data,'page'=>$page];
    }

}