<?php
/**
 * 新闻资讯管理控制器
 *
 * @author dengweijun <406516482@qq.com>
 *
 * @since  2016-3-14
 */
use Think\Tool\Tool;
class PublishIndexController extends CommonController {
    //新闻资讯主页
    public function index()
    {
    	//获取轮播资讯
        $carousel = Tool::getAdList(1,5);
        $this->assign('carousel',$carousel);

        //获取名师解盘
        $teacher = M('frontUser')->where(['status'=>1,'is_expert'=>1,'is_recommend'=>1])->order("sort asc")->field('id,nick_name,head,avder')->limit(4)->select();
        foreach ($teacher as $k => $v) {
            $teacher[$k]['article'] = M('PublishList')->where(['class_id'=>10,'user_id'=>$v['id'],'status'=>1,'is_original'=>1])->order('update_time desc')->field("id,title,remark")->find();
        }
        $this->assign('teacher',$teacher);

        //获取足球分类
        $footPublish = [['class_id'=>10,'limit'=>16],['class_id'=>54,'limit'=>14],['class_id'=>55,'limit'=>12],['class_id'=>61,'limit'=>12],['class_id'=>62,'limit'=>12]];
        foreach ($footPublish as $k => $v) {
            $footPublish[$k]['news'] = M("publishList")->where(['class_id'=>$v['class_id'],'status'=>1,'is_original'=>1])->field("id,title")->order("update_time desc")->limit($v['limit'])->select();
        }
        $this->assign('footPublish',$footPublish);

        //获取集锦轮播推荐
        $highlights = M("Highlights")
            ->where(['is_best'=>1,'status'=>1])
            ->field("id,game_id,game_type,title,img,web_url,web_ischain")
            ->order("add_time desc")
            ->limit(6)
            ->select();
        foreach ($highlights as $key => $value)
        {
            $highlights[$key]['img'] = Tool::imagesReplace($value['img']);
        }
        $this->assign('highlights', $highlights);

        $this->display();
    }

    //新闻资讯分类页
    public function publishClass()
    {
        $class_id = I('class_id');
        $class_flag = $class_id;

        //获取分类列表
        if(is_numeric($class_id) || in_array($class_id, ['football','NBA','yc'])){
            //获取分类
            if(!$PublishClass = S('web_PublishClass')){
                $PublishClass = M('PublishClass')->where("status=1")->field("id,pid,level")->select();
                S('web_PublishClass',$PublishClass,300);
            }
            switch ($class_id) {
                case 'football':
                    //足球 包括国际足球与中国足球
                    $worldClassIds = Tool::getAllSubCategoriesID( $PublishClass, 1 );
                    $chinaClassIds = Tool::getAllSubCategoriesID( $PublishClass, 2 );
                    $PublishClassIds = array_merge($worldClassIds,$chinaClassIds);
                    $className = '足球';
                    $where['class_id'] = array( 'in', $PublishClassIds );
                    break;
                case 'NBA':
                    $className = 'NBA';
                    $where['class_id'] = 4;
                    break;
                case 'yc':
                    $where['is_original'] = 1;
                    $className = '深度原创';
                    break;
                default:
                    $PublishClassIds = Tool::getAllSubCategoriesID( $PublishClass, $class_id );
                    $className = M('PublishClass')->where(['id'=>$class_id])->getField('name'); //获取分类名称
                    $where['class_id'] = array( 'in', $PublishClassIds );
                    break;
            }
        }else{
            //标签搜索
            $where['label'] = ['like','%'.urldecode($class_id).'%'];
            $className = '"'.urldecode($class_id)."\"相关资讯";
        }
        $where['status']   = 1;
        $where['is_original'] = 1;
        $this->assign('className', $className);
        $list = $this->_list(M('PublishList'),$where,'15','update_time desc','','',"/list_n/{$class_flag}/%5BPAGE%5D.html");
        foreach ($list as $key => $value) {
            //$list[$key]['comment']  = M('comment')->where(['publish_id'=>$value['id']])->count();
            if($class_id == 10){
                $userInfo = M('frontUser')->where(['id'=>$value['user_id']])->field('nick_name,head')->find();
                $list[$key]['nick_name']  = $userInfo['nick_name'];
                $list[$key]['face']  = frontUserFace($userInfo['head']);
            }
            if(in_array($value['class_id'], [4,18])){
                $list[$key]['img'] = '/Public/Home/images/index/164x114.jpg';
            }else{
                if(!empty($value['img'])){
                    $list[$key]['img'] = Tool::imagesReplace($value['img']);
                }else{
                    //获取第一张图片
                    $list[$key]['img']  = Tool::getTextImgUrl(htmlspecialchars_decode($value['content']),false)[0] ?:'/Public/Home/images/index/164x114.jpg';
                }
            }
            $list[$key]['label'] = explode(',', $value['label']);
            //增加资讯点击量的默认值
            $list[$key]['click_number'] = addClickConfig(1, $value['class_id'], $value['click_number'], $value['id']);
            if(empty($list[$key]['app_time'])) $list[$key]['app_time'] = $list[$key]['update_time'];
        }
        $this->assign('list', $list);

        //获取独家解盘分类
        $quiz = M('publishList')->where(['class_id'=>10,'status'=>1,'is_original'=>1])->field("id,title,is_original")->order("update_time desc")->limit(10)->select();
        $this->assign('quiz',$quiz);
        //获取推荐指数
        $Marvellous = A('Index')->getMarvellous();
        $this->assign('Marvellous', $Marvellous);
        //获取视频推荐集锦
        $highlights = $this->getRecommendJJ();
        $this->assign('highlights',$highlights);

        //获取视频直播
        $live = $this->getVideoLive();
        $this->assign('live',$live);

        if($class_id == 10){
            $this->display('teacher');
            exit;
        }
        $this->display();
    }

    //新闻资讯内容页
    public function publishContent()
    {
        C('HTTP_CACHE_CONTROL','no-cache,no-store');
        $id = I('id');
        $this->assign("publishid",$id);
        $where['l.id'] = $id;
        $where['l.status'] = 1;
        if(stripos($_SERVER['HTTP_REFERER'], 'qqty_admin')){
            unset($where['l.status']);
        }
        //获取资讯
        if(!$news = S('web_news_'.$id.'_'.get_client_ip())) //缓存5分钟
        {
            $news = array();
            $list = M('PublishList l')
                    ->join("LEFT JOIN qc_publish_class c on c.id = l.class_id")
                    ->join("LEFT JOIN qc_front_user f on f.id = l.user_id")
                    ->field("l.odds,l.handcp,l.odds_other,l.id,l.title,l.is_original,l.class_id,l.user_id,l.game_id,l.gamebk_id,l.remark,l.content,l.source,l.label,l.click_number,l.add_time,l.update_time,l.app_time,c.name,f.nick_name,f.lv,f.head,l.play_type,l.chose_side,l.result")
                    ->where($where)
                    ->find();
            if($list){
                //点击量加1
                $rs = M('PublishList')->where(array('id'=>$id,'status'=>1))->setInc('click_number');
            }else{
                $this->_empty();
            }
            $list['head'] = frontUserFace($list['head']);
            if(in_array($list['class_id'], [4,18])){
                $list['content'] = htmlspecialchars_decode($list['content']);
                $list['content'] = preg_replace('/<img.*\>/isU', "", $list['content']);
            }
            //获取相关新闻
            $beSimilar = M('PublishList l')
                        ->join("LEFT JOIN qc_publish_class c on c.id = l.class_id")
                        ->field("l.id,l.title,l.is_original,l.class_id,l.update_time,l.app_time,c.name")
                        ->where(['l.status'=>1,'l.class_id'=>$list['class_id'],'l.id'=>array("neq",$id),'l.is_original'=>1])
                        ->order("l.update_time desc")
                        ->limit(5)
                        ->select();
            $list['label'] = explode(',', $list['label']);
            $news['list'] = $list;
            $news['beSimilar'] = $beSimilar;

            //根据ID获取相邻的两篇文章title、ID
            $rLeftArr['next'] = M('PublishList')->where(['status'=>1,'id'=>['GT',$id],'is_original'=>1])->field("id,title,is_original")->order("id asc")->limit(1)->find();
            $rLeftArr['posts'] = M('PublishList')->where(['status'=>1,'id'=>['lt',$id],'is_original'=>1])->field("id,title,is_original")->order("id desc")->limit(1)->find();

            $news['rLeftArr'] = $rLeftArr;

            //获取云标签
            $date = strtotime(date('Y-m-d',strtotime('-30 day')));
            $yunLabel = M('PublishList l')->where(['l.status'=>1,'l.update_time'=>['GT',$date],'l.label'=>['neq','']])->field("l.label")->order("l.click_number desc")->limit(10)->select();
            $yunLabelArr = array();
            foreach ($yunLabel as $key => $value)
            {
                $arr = explode(',',$value['label']);
                foreach ($arr as $k => $v)
                {
                    if (count($yunLabelArr) == 15 ) break;
                    
                    if (!in_array($v,$yunLabelArr) && !empty($v)) $yunLabelArr[] = $v;
                }
            }
            $news['yunLabelArr'] = $yunLabelArr ? : [];
            if(($list['gamebk_id'] || $list['game_id']) && $list['class_id'] == "10")
            {
                $game_info = array();
                if(abs((int)$list['play_type']) == 1)
                {
                    $game_info['fsw_exp_home'] = $list['odds'];
                    $game_info['fsw_exp'] = $list['handcp'];
                    $game_info['fsw_exp_away'] = $list['odds_other'];
                }elseif(abs((int)$list['play_type']) == 2)
                {
                    $odds = json_decode($list['odds_other'],true);
                    $game_info['handcp'] = $list['handcp'];
                    $game_info = array_merge($game_info,$odds);
                }
                if($list['game_id'])
                {
                    $M = M("GameFbinfo");
                    $game_info['game_id'] = $game_id = $list['game_id'];
                    $game_info['gtype'] = '1';
                }else{
                    $M = M("GameBkinfo");
                    $game_info['gamebk_id'] = $game_id = $list['gamebk_id'];
                    $game_info['gtype'] = '2';
                }
                $game = $M->field('home_team_name,away_team_name,union_name,gtime,game_state,score,home_team_id,away_team_id,bet_code')->where(['game_id'=>$game_id])->find();
                if($game['game_state'] == '-1') $game_info['score'] = $game['score'];
                $game_info['game_state'] = $game['game_state'];
                $game_info['gtime'] = $game['gtime'];
                $game_info['home_team_id'] = $game['home_team_id'];
                $game_info['away_team_id'] = $game['away_team_id'];
                $game_info['home_team_name'] = $game['home_team_name'];
                $game_info['away_team_name'] = $game['away_team_name'];
                $game_info['union_name'] = $game['union_name'];
                $game_info['bet_code'] = $game['bet_code'];
                $_tmp[]=$game_info;
                setTeamLogo($_tmp,$game_info['gtype']);
                $against = $_tmp[0];

            }
            $news['against'] = $against;
            S('web_news_'.$id.'_'.get_client_ip(),$news,C('newsCacheTime'));
        }
        //增加资讯点击量的默认值
        $news['list']['click_number'] = addClickConfig(1, $news['list']['class_id'], $news['list']['click_number'], $news['list']['id']);
        //赋值模版
        $this->assign("rLeftArr",$news['rLeftArr']);
        $this->assign("list",$news['list']);
        $this->assign("beSimilar",$news['beSimilar']);
		$this->assign("yunLabelArr",$news['yunLabelArr']);

        $Adver = Tool::getAdList(40,1);
        $this->assign('Adver',$Adver);
        //获取独家解盘
        $map['class_id'] = 10;
        $map['status']   = 1;
        $map['is_original'] = 1;
        $quiz = M('publishList')->where($map)->field("id,title,is_original")->order("update_time desc")->limit(8)->select();
        $this->assign('quiz',$quiz);
        
        $is_login = is_login();
        if($is_login)
        {
            $is_follow = M("FollowUser")->where(['user_id'=>$is_login,'follow_id'=>$news['list']['user_id']])->getField('id');
        }
        $this->assign('is_follow',$is_follow);
        $this->assign("game",$news['against']);

        //获取评论列表
        //$comment = $this->getCommlist(0,$comment=array(),$id,$sign);
        //评论总数
        //$commentCount = M('comment')->where(['publish_id'=>$id])->count();
        //$this->assign("commentCount",$commentCount);
        //$this->assign("comment",$comment);
        $this->display();
    }

    //相关推荐
    public function getUserRank($game_id){
        $gambleArr = D("GambleView")->where(['game_id'=>$game_id])->select();
        if(!$gambleArr){
            $gambleArr = D("GambleView")->where(['result'=>'0','tradeCoin'=>['gt',0],'lv'=>['gt',2]])->order("rand()")->limit(5)->select();
        }
        if(!$gambleArr) return array();
        foreach ($gambleArr as $k => $v) {
            //周胜率和连胜
            $Winrate = D('GambleHall')->CountWinrate($v['user_id']);
            $Winning = D('GambleHall')->getWinning($v['user_id']);
            $gambleArr[$k]['curr_victs'] = $Winning['curr_victs'];
            $gambleArr[$k]['Winrate'] = $Winrate;
        }
        //分开付费和免费
        $freeGamble = array();
        $payGamble  = array();
        foreach ($gambleArr as $k => $v) {
            if($v['tradeCoin'] == 0){
                $freeGamble[] = $v; //免费
            }else{
                $payGamble[] = $v;  //付费
            }
        }
        //付费排序
        $payGamble  = $this->sortGamble($payGamble);
        //免费排序
        $freeGamble = $this->sortGamble($freeGamble);
        //付费与免费合并
        $gamble = array_merge_recursive($payGamble,$freeGamble);
        return $gamble;
    }

    //排序 "周胜率＞连胜＞等级＞发布时间"
    public function sortGamble($Gamble)
    {
        foreach ($Gamble as $k => $v)
        {
            $sort_Winrate[]    = $v['Winrate'];    //周胜率
            $sort_curr_victs[] = $v['curr_victs']; //连胜
            $sort_lv[]         = $v['lv'];         //等级
            $sort_time[]       = $v['create_time'];//发布时间
        }
        array_multisort($sort_Winrate,SORT_DESC,$sort_curr_victs,SORT_DESC,$sort_lv,SORT_DESC,$sort_time,SORT_DESC,$Gamble);
        return $Gamble;
    }

    /**
    *递归获取评论列表
    */
    public function getCommlist($pid = 0,&$comment = array(),$id,$sign='new',$limitpage=0,$num=10){
        $order = $sign == 'new' ? 'create_time desc' : 'like_num desc,create_time desc';
        $where['pid'] = $pid;
        $where['publish_id'] = $id;
        if($sign == 'host'){
            $where['like_num'] = ['gt',0];
        }
        $arr = M('Comment c')->join("LEFT JOIN qc_front_user f on f.id = c.user_id")
                            ->field("c.*,f.head,f.nick_name,f.is_gag")
                            ->where($where)
                            ->order($order)
                            ->limit($limitpage,$num)
                            ->select();
        if(empty($arr)){
            return array();
        }
        foreach ($arr as $cm) {
            $thisArr=&$comment[];
            $cm["children"] = M("comment")->table("qc_comment c")->join("left join qc_front_user f on c.user_id = f.id")->join("left join qc_front_user f2 on c.by_user = f2.id")->where(['top_id'=>$cm['id'],'publish_id'=>$id])->field("c.id,c.pid,c.publish_id,c.user_id,c.by_user,c.filter_content,c.like_num,c.like_user,c.status,c.create_time,f.nick_name,f.head,f2.nick_name by_username")->order($order)->select();
            $thisArr = $cm;
        }
        return $comment;
    }

    /**
    *添加评论
    */
    public function addComment(){
        $data=array();
        if((isset($_POST["comment"]))&&(!empty($_POST["comment"]))){
            $cm = json_decode($_POST["comment"],true);
            $user = M('frontUser')->where(['id'=>$cm['user_id']])->field('nick_name,is_gag,head,is_robot')->find();

            if($user['is_robot'] != 1) //机器人无需限制
            {
                $lastcomment = M('comment')->field('publish_id,create_time,content')->where(['user_id'=>$cm['user_id']])->order('create_time desc')->limit(1)->find();
                if($lastcomment && time() - $lastcomment['create_time'] < 30) //评论30秒限制
                {
                    $this->error("30秒内不能多次评论哦！");
                }
                if((trim($lastcomment['content']) == trim($cm['content'])) && ($lastcomment['publish_id'] == $cm['publish_id']))
                {
                    $this->error("不能重复评论！");
                }
            }

            //查看是否禁言
            if($user['is_gag'] == 1){
                $this->error("您已被管理员禁言，请联系客服！");
            }

            if ( $cm['pid'] != 0) //是否已达20评论数上限
            {
                $num = M('comment')->where(['pid'=>$cm['pid']])->count();
                if( $num >= 20){
                    $this->error("已达评论数上限，请另外发表评论");
                }
            }

            $cm['create_time'] = time();
            $cm['reg_ip'] = get_client_ip();
            //过滤内容
            $FilterWords = getWebConfig("FilterWords");
            foreach ($FilterWords as $key => $value) {
                $Words[] = '/'.$value.'/';
            }
            $cm['filter_content'] = preg_replace($Words, '***', $cm['content']);
            $rs = M('comment')->add($cm);
            $cm['id']           = $rs;
            $cm['face']         = frontUserFace($user['head']);
            $cm['create_time']  = format_date($cm['create_time']);
            $cm['nick_name']    = $user['nick_name'];
            $cm['commentCount'] = M('comment')->where(['publish_id'=>$cm['publish_id']])->count();
            $cm['info']         = "发布评论成功！";
            if($rs){
                $this->success($cm);
            }else{
                $this->error("发布评论失败,请稍后再试！");
            }
        }else{
            $this->error("发布评论失败,请稍后再试！");
        }
    }

    //添加举报
    public function addReport(){
        $id = I('id');
        $report_content = I('report_content');
        $user_id = session('user_auth')['id'];
        if( isset($id) && isset($user_id) && !empty($report_content) ){
            $comment = M('comment')->where(['id'=>$id])->field('user_id,report_user,report_content')->find();
            if($user_id == $comment['user_id']){
                $this->error("亲，不可举报自己哦！");
            }
            $is_report = explode(",", $comment['report_user']);
            //是否已举报
            if(in_array($user_id, $is_report)){
                $this->error("您已经举报过该评论了哦！");
                exit;
            }
            array_push($is_report,$user_id);
            $report_user = ltrim(implode(",", $is_report),',');
            //是否已存在的举报类型
            $report = explode(",", $comment['report_content']);
            if(!in_array($report_content, $report)){
                array_push($report,$report_content);
                $report_content = ltrim(implode(",", $report),',');
            }
            $rs = M('comment')->where(['id'=>$id])->save(['report_num'=>['exp','report_num+1'],'report_user'=>$report_user,'report_content'=>$report_content]);
            if($rs){
                $this->success("举报成功！");
            }else{
                $this->error("举报失败,请稍后再试！");
            }
        }else{
            $this->error("举报失败,请稍后再试！");
        }
    }

    /**
    *顶
    */
    public function addLikeNum(){
        if((isset($_POST["id"]))&&(!empty($_POST["user_id"]))){
            $id = I('id');
            $user_id = I('user_id');
            $is_like = M('comment')->where(['id'=>$id])->field('user_id,like_user')->find();
            if($user_id == $is_like['user_id']){
                $this->error("亲，不可点赞自己哦！");
            }
            $is_like = explode(",", $is_like['like_user']);
            if(in_array($user_id, $is_like)){
                $this->error("您已经顶过该评论了哦！");
                exit;
            }
            array_push($is_like,$user_id);
            $like_user = ltrim(implode(",", $is_like),',');
            $rs = M('comment')->where(['id'=>$id])->save(['like_num'=>['exp','like_num+1'],'like_user'=>$like_user]);
            if($rs){
                $this->success("操作成功！");
            }else{
                $this->error("操作失败！");
            }
        }else{
            $this->error("操作失败！");
        }
    }
    //获取更多评论
    public function send(){
        $p = isset($_POST['k'])?intval(trim($_POST['k'])):0;
        $publishid = I('publishid');
        $sign      = I('sign') ? I('sign') : 'new';

        $total = M('Comment')->where(['publish_id'=>$publishid])->count();//数据记录总数

        $num = 10;//每页记录数
        $totalpage = ceil($total/$num);//总计页数
        $limitpage = ($p-1)*$num;//每次查询取记录

        if($p>$totalpage){
            //超过最大页数，退出
            $this->error("没有了");
        }
        $comment = $this->getCommlist(0,$comment=array(),$publishid,$sign,$limitpage,$num);
        foreach ($comment as $k => $v) {
            $comment[$k]['face']    = frontUserFace($v['head']);
            $comment[$k]['is_like'] = !in_array(is_login(), explode(",", $v['like_user'])) || !is_login() ? "ds-post-likes" : "ds-post-likes-true";
            $comment[$k]['create_time'] = format_date($v['create_time']);
            $comment[$k]['cm_count'] = count($v['children']);
            if(!empty($v['children'])){
                foreach ($v['children'] as $k2 => $v2) {
                    $comment[$k]['children'][$k2]['face']    = frontUserFace($v2['head']);
                    $comment[$k]['children'][$k2]['is_like'] = !in_array(is_login(), explode(",", $v2['like_user'])) || !is_login() ? "ds-post-likes" : "ds-post-likes-true";
                    $comment[$k]['children'][$k2]['create_time'] = format_date($v2['create_time']);
                }
            }
        }
        if(count($comment)>0){
            // pr($comment);
            // die;
            $this->success($comment);
        }else{
            $this->error("没有了");
        }

    }
    //获取更多新闻
    public function sendNews()
    {
        $p = isset($_POST['k'])?intval(trim($_POST['k'])):0;
        $class_id = I('class_id');
        if(is_numeric($class_id) || in_array($class_id, ['football','NBA'])){
            //获取分类
            $PublishClass = M('PublishClass')->where("status=1")->field("id,pid,level")->select();
            //无限级分类中获取一个分类下的所有分类的ID,包括查找的父ID
            if($class_id == 'football')  //足球 包括国际足球与中国足球
            {
                $worldClassIds = Tool::getAllSubCategoriesID( $PublishClass, 1 );
                $chinaClassIds = Tool::getAllSubCategoriesID( $PublishClass, 2 );
                $PublishClassIds = array_merge($worldClassIds,$chinaClassIds);
            }
            else
            {
                if($class_id == 'NBA') $class_id = 4;
                $PublishClassIds = Tool::getAllSubCategoriesID( $PublishClass, $class_id );
            }
            $where['class_id'] = array( 'in', $PublishClassIds );
        }else{
            //标签搜索
            $where['label'] = ['like','%'.urldecode($class_id).'%'];
        }
        $where['status']   = 1;
        $where['is_original'] = 1;
        $total = M('PublishList')->where($where)->count();//数据记录总数

        $num = 15;//每页记录数
        $totalpage = ceil($total/$num);//总计页数
        $limitpage = ($p-1)*$num;//每次查询取记录

        if($p>$totalpage){
            //超过最大页数，退出
            $this->error("没有了");
        }
        $list = M('PublishList')->where($where)->field('id,user_id,title,content,click_number,remark,img,update_time')->order('update_time desc')->limit($limitpage,$num)->select();
        foreach ($list as $key => $value) {
            if(!empty($value['img'])){
                $list[$key]['img'] = Tool::imagesReplace($value['img']);
            }else{
                //获取第一张图片
                $list[$key]['img']  = Tool::getTextImgUrl(htmlspecialchars_decode($value['content']),false)[0] ?:'/Public/Home/images/index/164x114.jpg';
            }
            $list[$key]['comment']  = M('comment')->where(['publish_id'=>$value['id']])->count();
            $list[$key]['update_time']  = date('Y-m-d H:i', $value['update_time']);
            $list[$key]['remark']   = mb_substr($value['remark'], 0,65,'utf-8');
            if($class_id == 10){
                $userInfo = M('frontUser')->where(['id'=>$value['user_id']])->field('nick_name,head')->find();
                $list[$key]['nick_name']  = $userInfo['nick_name'];
                $list[$key]['userFace'] = frontUserFace($userInfo['head']);
            }
        }
        if(count($list)>0){
            $this->success($list);
        }else{
            $this->error("没有了");
        }
    }
    /**
     * @User liangzk <liangzk@qc.com>
     * @DateTime 2016-08-15
     * @version 2.1
     * 情报分析
     */
    public function analysts()
    {
        $class_id = I('class_id')  ? I('class_id','int') : 10;
        $num = 10;//每页记录数
        if ($class_id == 10)
        {
            $field = 'id,user_id,title,is_original,click_number,remark,update_time,add_time';
        }
        else
        {
            $field = 'id,user_id,title,is_original,click_number,remark,img,content,update_time,add_time';
        }
        if (IS_AJAX)
        {
            $page = I('page') ? I('page','int') : 2;
            $total = M('PublishList')->where(['id'=>['GT',0],'status'=>1,'class_id'=>$class_id,'is_original'=>1])->count('id');//数据记录总数
            $totalpage = ceil($total/$num);//总计页数
            $limitpage = ($page-1)*$num;//每次查询取记录
            if($page>$totalpage){
                //超过最大页数，退出
                $this->error("没有了");
            }
            $list = M('PublishList')
                ->where(['id'=>['GT',0],'status'=>1,'class_id'=>$class_id,'is_original'=>1])
                ->limit($limitpage,$num)
                ->field($field)
                ->order('update_time desc')
                ->select();
            $userIdArr = array_map(function ($element){return $element['user_id'];},$list);
            $userInfo = M('FrontUser')->where(['id'=>['IN',array_unique($userIdArr)]])->field('id,nick_name,head')->select();
            foreach ($list as $key => $value)
            {
                $list[$key]['update_time']  = date('Y-m-d H:i', $value['update_time']);
                $list[$key]['remark']   = mb_substr($value['remark'], 0,65,'utf-8');
                if ($class_id != 10)
                {
                    if(!empty($value['img']))
                    {
                        $list[$key]['img'] = Tool::imagesReplace($value['img']);
                    }else
                    {
                        //获取第一张图片
                        $list[$key]['img'] = Tool::getTextImgUrl(htmlspecialchars_decode($value['content']),false)[0]?:'/Public/Home/images/index/164x114.jpg';
                    }
                }
                foreach ($userInfo as $k => $v)
                {
                    if ($v['id'] == $value['user_id'])
                    {
                        $list[$key]['nick_name']  = $v['nick_name'];
                        $list[$key]['head'] = $v['head'];
                    }
                }
                $list[$key]['face'] = frontUserFace($list[$key]['head']);
            }
            count($list)>0 ? $this->success($list) : $this->error('没有了');
            exit;
        }
        //初始化数据
        $teacher_list = M('PublishList')
                ->where(['id'=>['GT',0],'status'=>1,'class_id'=>10,'is_original'=>1])
                ->limit($num)
                ->field($field)
                ->order('update_time desc')
                ->select();
        $userIdArr = array_map(function ($element){return $element['user_id'];},$teacher_list);
        $userInfo = M('FrontUser')->where(['id'=>['IN',array_unique($userIdArr)]])->field('id,nick_name,head')->select();
        foreach ($teacher_list as $key => $value)
        {
            $teacher_list[$key]['update_time']  = date('Y-m-d H:i', $value['update_time']);
            foreach ($userInfo as $k => $v)
            {
                if ($v['id'] == $value['user_id'])
                {
                    $teacher_list[$key]['nick_name']  = $v['nick_name'];
                    $teacher_list[$key]['head'] = $v['head'];
                }
            }
            $teacher_list[$key]['face'] = frontUserFace($teacher_list[$key]['head']);
            //增加资讯点击量的默认值
            $teacher_list[$key]['click_number'] = addClickConfig(1, $value['class_id'], $value['click_number'], $value['id']);
        }
        //竞彩前瞻
        $race_list = M('PublishList')
                        ->where(['id'=>['GT',0],'status'=>1,'class_id'=>54,'is_original'=>1])
                        ->limit(0,10)
                        ->field('id,user_id,title,is_original,click_number,remark,img,content,update_time,add_time')
                        ->order('update_time desc')
                        ->select();
        $race_list = $this->dataConver($race_list);
        //北单推荐
        $north_list = M('PublishList')
                        ->where(['id'=>['GT',0],'status'=>1,'class_id'=>55,'is_original'=>1])
                        ->limit(0,10)
                        ->field('id,user_id,title,is_original,click_number,remark,img,content,update_time,add_time')
                        ->order('update_time desc')
                        ->select();
        $north_list = $this->dataConver($north_list);

        //获取广告
        $this->assign('adIndex',Tool::getAdList(22,4));
        //获取情报分析资讯手写位
        $this->assign('intelligence', M('config')->where(['sign'=>'Intelligence'])->getField('config'));

        //获取推荐指数
        $Marvellous = A('Index')->getMarvellous();
        $this->assign('Marvellous', $Marvellous);
        $this->assign('teacher_list',$teacher_list);
        $this->assign('race_list',$race_list);
        $this->assign('north_list',$north_list);
        $this->display();
    }

    /**
     * @User liangzk <liangzk@qc.com>
     * analysts()的代码片段
     */
    public function dataConver($list)
    {
        foreach ($list as $key => $value)
        {
            $list[$key]['update_time']  = date('Y-m-d H:i', $value['update_time']);
            if(!empty($value['img']))
            {
                $list[$key]['img'] = Tool::imagesReplace($value['img']);
            }else
            {
                //获取第一张图片
                $list[$key]['img'] = Tool::getTextImgUrl(htmlspecialchars_decode($value['content']),false)[0] ?:'/Public/Home/images/index/164x114.jpg';
            }
            //增加资讯点击量的默认值
            $list[$key]['click_number'] = addClickConfig(1, $value['class_id'], $value['click_number'], $value['id']);
        }
        return $list;
    }
    /**
     * * @User liangzk <liangzk@qc.com>
     * @DataTime 2016-08-18
     * 获取连胜多（前50）的用户的推荐-----连胜统计更新一次-----显示推荐30分钟更新一次
     */
    public function getWinningGame()
    {

        $dateTime = S('userWinningArr:dateTime');
        $time = $dateTime - date('Ymd');
        $userWinningArr = S('userWinningArr');
        if (empty($userWinningArr) || $time < 0 )//每天更新一次
        {
            S('userWinningArr',null);
            S('userWinningArr:rand_user',null);
            S('userWinningArr:dateTime',null);
            $userWinningArr = $this->getUserWinning(50);
            S('userWinningArr',$userWinningArr);
            S('userWinningArr:dateTime',date('Ymd'));
        }
        $rand_user_arr = S('userWinningArr:rand_user');
        $blockTime = getBlockTime(1,true);
        if (empty($rand_user_arr) || date('i') == '30' || date('i') == '0')
        {
            if (empty($userWinningArr))
                $this->error('出错了');
            $userIdArr = array();
            foreach($userWinningArr as $key => $val)
            {
                $userIdArr[] = $val['user_id'];
            }
            //获取连胜多并今天有推荐（不免费）的用户id
            
            $gambleUserArr = M('Gamble')
                ->where(['user_id'=>['IN',$userIdArr],'result'=>'0','tradeCoin'=>['GT',0],
                         'create_time'=>['between',[$blockTime['beginTime'],$blockTime['endTime']]]])
                ->group('user_id')
                ->getField('user_id',true);
            //随机获取数组中不同的三个user_id
            $rand_user_arr = array();
            while (true)
            {
                if (count($rand_user_arr)>=3)
                    break;
                $rand_user_id = array_rand($gambleUserArr,1);
                if (in_array($gambleUserArr[$rand_user_id],$rand_user_arr))
                    continue;
                $rand_user_arr[] = $gambleUserArr[$rand_user_id];
            }
            S('userWinningArr:rand_user',$rand_user_arr);
        }


        //获取连胜前三名的用户推荐
        $gambleOne = D('GambleView')->where(['user_id'=>$rand_user_arr[0],
                                             'result'=>'0',
                                             'tradeCoin'=>['GT',0],
                                             'create_time'=>['between',[$blockTime['beginTime'],$blockTime['endTime']]]])
                                            ->order('create_time')
                                            ->limit(0,1)
                                            ->find();
        if (! empty($gambleOne)) $list_gamble[] = $gambleOne;

        $gambleSec = D('GambleView')
                ->where(['user_id'=>$rand_user_arr[1],
                         'result'=>'0',
                         'tradeCoin'=>['GT',0],
                        'create_time'=>['between',[$blockTime['beginTime'],$blockTime['endTime']]]])
                ->order('create_time')->limit(0,1)->find();
        if (! empty($gambleSec)) $list_gamble[] = $gambleSec;

        $gambleThree = D('GambleView')
            ->where(['user_id'=>$rand_user_arr[2],
                     'result'=>'0',
                     'tradeCoin'=>['GT',0],
                     'create_time'=>['between',[$blockTime['beginTime'],$blockTime['endTime']]]])
                    ->order('create_time')
                    ->limit(0,1)
                    ->find();
        if (! empty($gambleThree)) $list_gamble[] = $gambleThree;

        if (empty($list_gamble)) $this->success($list_gamble);//如果该次更新没有推荐就直接返回

        $list_gamble = HandleGamble($list_gamble);
        $user_id = is_login();
        foreach ($list_gamble as $key => $value)
        {
            $list_gamble[$key]['game_date'] = date('m/d',strtotime($value['game_date']));
            $list_gamble[$key]['head'] = frontUserFace($value['head']);
            //获取连胜数
            foreach ($userWinningArr as $k => $v)
            {
                if ($value['user_id'] == $v['user_id'])
                {
                    $list_gamble[$key]['winningNum'] = $v['num'];
                }
            }
            //判断推荐是否被查看或是否为用户自己的推荐
            if ($user_id)
            {
                if($value['user_id'] != $user_id )
                {
                    //是否已被查看
                    $list_gamble[$key]['is_check'] = M('quizLog')->where(['user_id'=>$user_id,'gamble_id'=>$value['id'],'game_type'=>1])->getField('id');
                }
                else
                {
                    $list_gamble[$key]['login_user'] = $user_id;
                }
            }

        }
        foreach ($list_gamble as $k => $v) {
            $winningNum[] = $v['winningNum'];
        }
        array_multisort($winningNum,SORT_DESC,$list_gamble);//根据连胜多的排序
        $this->success($list_gamble);
    }

}