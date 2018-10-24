<?php
/**
 * 资讯列表控制器
 *
 * @author dengweijun <406516482@qq.com>
 *
 * @since  2015-12-1
 */
use Think\Tool\Tool;

class PublishListController extends CommonController {
    public $PublishClass;
    /**
    *构造函数
    *
    * @return  #
    */
    public function _initialize()
    {
        parent::_initialize();
        //获取分类
        $PublishClass = getPublishClass(0);
        $this->PublishClass = $PublishClass;
        //引用Tree类
        $PublishClass = Tool::getTree($PublishClass, $pid = 0, $col_id = 'id', $col_pid = 'pid', $col_cid = 'level');
        $this->assign ('PublishClass', $PublishClass);
    }

    /**
     * Index页显示
     *
     */
    public function index() {
        //列表过滤器，生成查询Map对象
        $map = $this->_search('PublishListView');
        $class_id = I('class_id');
        if($class_id == 10){
            $isCheckOn = M('Config')->where(['sign'=>'dujiaCheck'])->getField('config');
            $this->assign('checkType','dujiaCheck');
        }
        if(I('no_class'))
        {
            $isCheckOn = M('Config')->where(['sign'=>'newCheck'])->getField('config');
            $this->assign('checkType','newCheck');
            $map['class_id'] = ['neq',I('no_class')];
        }
        $this->assign('isCheckOn',$isCheckOn);
        if(!empty($class_id)){
            //获取问题分类
            $PublishClass = $this->PublishClass;
            //无限级分类中获取一个分类下的所有分类的ID,包括查找的父ID
            $PublishClassIds = Tool::getAllSubCategoriesID( $PublishClass, $class_id );
            $map['class_id'] = array( 'in', $PublishClassIds );
        }
        if(!empty($_REQUEST['startTime']) || !empty($_REQUEST['endTime'])){
            if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                $startTime = strtotime($_REQUEST['startTime']);
                $endTime   = strtotime($_REQUEST['endTime'])+86400;
                $map['update_time'] = array('BETWEEN',array($startTime,$endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']);
                $map['update_time'] = array('EGT',$strtotime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime'])+86400;
                $map['update_time'] = array('ELT',$endTime);
            }
        }
        if(I('is_home') == 1)
        {
            $map['p.is_home'] = "1";
        }else{
            $map['p.status'] = ['lt',2];
        }
        if(I('user_name'))
        {
            $where['nick_name'] = ['like','%'.I('user_name').'%'];
            $userIdArr = M('FrontUser')->where($where)->getField('id',true);
            $map['p.user_id'] = ['in',$userIdArr];
        }
        if(I('label') && I('label') != ',')
        {
            $map['p.label'] = ['like','%'.I('label').'%'];
        }
        //手动获取列表
        $list = $this->_list(D('PublishListView'), $map,'update_time desc');
        foreach ($list as $key => $value) {
            $list[$key]['img'] = Tool::imagesReplace($value['img']);
            $list[$key]['is_add'] = $list[$key]['img'] ? explode('&', $list[$key]['img'])[1] : 0;
            $list[$key]['href']   = newsUrl($value['id'],$value['add_time'],$value['class_id'],$this->PublishClass);
            $publish_id_arr[] = $value['id'];
        }
        //评论数
        $commentCount = M('Comment')
                        ->where(['publish_id'=>['IN',$publish_id_arr]])
                        ->field('publish_id,count(id) as commentNum')
                        ->group('publish_id')
                        ->select();
        //评论人数
        $whereIn = '('.implode(',', $publish_id_arr).')';
        $discussantCount = M()
                         ->query('select count(c.id) as discussantNum,c.publish_id from (SELECT id,publish_id FROM `qc_comment` where publish_id in '.$whereIn.' GROUP BY publish_id,user_id ) c GROUP BY c.publish_id');

        //获取编辑
        $editor = M('user')->select();

        foreach ($list as $key => $value)
        {
            foreach ($commentCount as $k => $v)
            {
                if ($v['publish_id'] == $value['id'])
                {
                    $list[$key]['commentNum'] = $v['commentNum'];
                }
            }
            foreach ($discussantCount as $k => $v)
            {
                if($v['publish_id'] == $value['id'])
                {
                    $list[$key]['discussantNum'] = $v['discussantNum'];
                }
            }
            foreach ($editor as $k => $v) {
                if($v['id'] == $value['author']){
                    $list[$key]['authorName'] = $v['nickname'];
                }
                if($v['id'] == $value['editor']){
                    $list[$key]['editorName'] = $v['nickname'];
                }
            }
        }

        //判断是否为杯赛直通车
        $pid = $this->PublishClass[$class_id]['pid'];
        if($pid == 111) $this->assign('showShort',1);

        $this->assign('editor', $editor);
        $this->assign('list', $list);
        $this->display();
    }

    //刷评论
    public function addComment()
    {
        $id = I('id');
        if(IS_POST){
            $comment  = I('comment');
            $update_time = I('update_time');
            if(empty($update_time)){
                $this->error("该资讯未编辑过，不能发布评论！");
            }
            //获取机器人用户
            $robotUser = M('FrontUser')->where(['is_robot'=>1])->field('id')->select();
            shuffle($robotUser); //打乱数组
            foreach ($comment as $k => $v) {
                if(!empty($v['content'])){
                    $v['publish_id'] = $id;
                    $v['filter_content'] = $v['content'];
                    $v['platform']   = rand(1,3);
                    $user_id = $robotUser[$k]['id'];
                    $v['user_id'] = $user_id;
                    $v['create_time'] = rand($update_time,time());
                    $arr[]  =  $v;
                }
            }
            $rs = M('Comment')->addAll($arr);
            if ($rs) {
                $this->success('发布成功!');
            } else {
                $this->error('发布失败!');
            }
            exit;
        }
        $vo = M("PublishList")->field("id,title,update_time")->find($id);
        if(!$vo) $this->error("参数错误!");
        $this->assign('vo', $vo);
        $this->display();
    }

    public function add(){
        $user_id = I('user_id');
        if(!empty($user_id)){
            $nick_name = M('FrontUser')->where(['id'=>$user_id])->getField('nick_name');
            $vo['user_id'] = $user_id;
            $vo['nick_name'] = $nick_name;
            $this->assign('vo', $vo);
        }
        $class_id = M('PublishClass')->where(['id'=>I('class_id')])->getField('pid');
        $this->selectData(I('class_id'));
        $this->assign('randStr',rand(0,999));
        $this->assign('is_edit',1);
        $this->assign('pclassid',$this->PublishClass[I('class_id')]['pid']);
        $this->display();
    }

    /**
     * 弹窗查找赛事
     */
    public function findGame(){
        $data = D('Cover')->findGameData(1);
        $this->assign('fbgame', $data['game']);
        $this->assign('fbunion',$data['union']);
        $bkdata = D('Cover')->findGameData(2);
        $this->assign('bkgame', $bkdata['game']);
        $this->assign('bkunion',$bkdata['union']);
        $this->display();
    }


    public function edit() {
        $id = I('id');
        $where['id'] = $id;
        $vo = M("PublishList")->where($where)->find();
        $this->assign('pclassid',$this->PublishClass[$vo['class_id']]['pid']);
        $this->selectData($vo['class_id']);
        if($vo['class_id'] == 10 || $vo['class_id'] == 54) $this->assign('show_play',1);
        $vo['img'] = Tool::imagesReplace($vo['img']);
        $editorId = [$vo['editor']];
        $authorId = [$vo['author']];
        $name = M('user')->field('id,nickname')->where(['id'=>['in',array_merge($editorId,$authorId)]])->select();
        foreach ($name as $k => $v) {
            if($v['id'] = $vo['editor']){
                $vo['editorName'] = $v['nickname'];
            }
            if($v['id'] = $vo['author']){
                $vo['authorName'] = $v['nickname'];
            }
        }
        if(!empty($vo['user_id'])){
            $nick_name = M('FrontUser')->where(['id'=>$vo['user_id']])->getField('nick_name');
            $vo['nick_name'] = $nick_name;
        }
        if($vo['game_id'] > 0) $game_id_edit = ['game_id'=>$vo['game_id'],'game_id_type'=>1];
        if($vo['gamebk_id'] > 0) $game_id_edit = ['game_id'=>$vo['gamebk_id'],'game_id_type'=>2];
        $vo['label'] = explode(',',$vo['label']);
        $this->assign('game_id_edit', $game_id_edit);
        $this->assign('vo', $vo);
        $this->assign('randStr',rand(0,999));
        $this->assign('is_edit',1);
        $this->display("add");
    }

    /**
     * 弹窗查找用户（前台用户）
     * return #
     */
    public function findFrontUser(){
        $val = I('val');
        $descript = I('descript', 0, 'intval');
        if(!$val){
            $this->error('请先选择资讯分类！');
        }
        $map = $this->_search ("FrontUser");
        unset($map['descript']);

        if($val == 9999){
            $class_id = '9999';
        }else {
            $classArr = getPublishClass(0)[$val];
            if ($classArr['pid'] == 0) {
                $class_id = $classArr['id'];
            } else {
                $class_id = $classArr['pid'];
            }
        }

        switch ($class_id) {
            case '28':
            case '27':
            case '18':
            case '17':
            case '16':
            case '15':
            case '14':
            case '13':
                $map['expert_type'] = 1;//足球
                break;
            case '3':
            case '4':
                $map['expert_type'] = 2;//篮球
                break;
            case '72':
            case '71':
            case '70':
            case '69':
                $map['expert_type'] = 3;//电竞
                break;
            case '67':
            case '66':
            case '65':
            case '64':
                $map['expert_type'] = 4;//综合
                break;
            case '9999':
                $map['expert_type'] = 5;//运营专用
                break;
        }
        
        //是否机器人竞猜记录筛选
        $map['is_robot'] = 1;
        $map['is_expert'] = 1;

        //多昵称搜索
        $more_name = trim(I('more_name'), ",");
        $more_name = implode("|", explode(',', $more_name));
        if(!empty($more_name)){
            $map['nick_name'] = ['exp',"regexp '{$more_name}'"];
        }
        //获取列表
        $list = $this->_list ( D('FrontUser'), $map,'',false,'','*',false);
        $this->assign('list', $list);
        $this->assign('descript', $descript);

        $tp = "Public:findFrontUserDialog";
        $this->display($tp);
    }

    /**
     * 随机返回一个专家
     */
    public function setFrontUser(){
        $val = I('val');
        $classArr = getPublishClass(0)[$val];
        if($classArr['pid'] == 0){
            $class_id = $classArr['id'];
        }else{
            $class_id = $classArr['pid'];
        }

        switch ($class_id) {
            case '28':
            case '27':
            case '18':
            case '17':
            case '16':
            case '15':
            case '14':
            case '13':
                $map['expert_type'] = 1;//足球
                break;
            case '3':
            case '4':
                $map['expert_type'] = 2;//篮球
                break;
            case '72':
            case '71':
            case '70':
            case '69':
                $map['expert_type'] = 3;//电竞
                break;
            case '67':
            case '66':
            case '65':
            case '64':
                $map['expert_type'] = 4;//综合
                break;
        }

        $map['is_robot'] = 1;
        $map['is_expert'] = 1;
        $user = M('FrontUser')->field('id,nick_name')->where($map)->select();
        shuffle($user);
        $this->success($user[0]);
    }

    /**
     * 保存/修改记录
	 *
     * @return #
    */
    public function save(){
        $labelArr = array_filter(I('label'));
        $label = implode(',',$labelArr);
		$id = I('id', 'int');
        if(empty($id))
        {
            $where['user_id'] = $_POST['user_id'];
            $where['add_time'] = ['gt',time()-30];
            $res = M('PublishList')->field('title')->where($where)->order('add_time desc')->limit(1)->find();
            if(isset($res))
            {
                if($res['title'] == $_POST['title']) $this->success('保存成功!','',true);
            }
        }
        if(I('is_cup') == 1)
        {
            if(I('short_title') == '') $this->error('请填写文章大标题！');
            if($_FILES['fileInput']['name'] == '' && empty($id))
            {
                $this->error('请上传封面图！');
            }else{
                $img = M('PublishList')->where(['id'=>$id])->getField('img');
                if($_FILES['fileInput']['name'] == '' && empty($img)) $this->error('请上传封面图！');
            }
        }
        $_POST['class_id'] = $this->getClassId();
        $_POST['class_id2'] = $this->getClassId(2);
        //判断填写的标签是否存在
        $HotRes = M('HotKeyword')->where(['keyword'=>['in',$labelArr]])->getField('keyword',true);
        $KeyRes = M('PublishKey')->where(['name'=>['in',$labelArr],'status'=>1])->getField('name',true);
        $KeyRes = array_merge((array)$HotRes,(array)$KeyRes);
        $tmp = [];
        foreach($labelArr as $val)
        {
            if(!in_array($val,$KeyRes)) $tmp[] = $val;
        }
        if($tmp) $this->error('"'.implode(',',$tmp).'"标签不存在,请先添加');

		$model = D('PublishList');
		if (!$data = $model->create()) {
        	$this->error($model->getError());
        }

        $model->title = htmlspecialchars_decode($_POST['title']);//标题
        $model->short_title = htmlspecialchars_decode($_POST['short_title']);//短标题
        $model->is_cup = $_POST['is_cup']?:0;//短标题
        $model->update_time = time();//修改时间
        
		if (empty($id)) {
            $time = time();
            //为新增
            $model->add_time = $time;
            $model->author = $_SESSION['authId'];
            $class_id = $model->class_id;
            //资讯赛事数据
            if($class_id == 10 || $class_id == 54){
                //处理赔率详情
                $game_id    = $_POST['game_game_id'];
                $odds       = $_POST['game_odds'];       //赔率
                $handcp     = $_POST['game_handcp'];     //盘口
                $play_type  = $_POST['game_play_type'];  //玩法
                $chose_side = $_POST['game_chose_side']; //选择
                $game_type  = $_POST['game_game_type'];  //类型 1足球 2篮球
                $gtime      = $_POST['game_gtime'];      //比赛时间

                if($game_id === '' || $odds === '' || $handcp === '' || $play_type === '' || $chose_side === '' || $game_type === ''){
                    $this->error('发布失败，请选择赛事数据！');
                }
                //另一队赔率
                switch($play_type)
                {
                    case '1':
                        $odds_other = $chose_side == 1 ? $_POST['game_fsw_exp_away'] : $_POST['game_fsw_exp_home'];
                        break;
                    case '-1':
                        $odds_other = $chose_side == 1 ? $_POST['game_fsw_ball_away'] : $_POST['game_fsw_ball_home'];
                        break;
                    case '2':
                    case '-2':
                        $odds_other = [
                            "home_odds"    => $_POST['game_home_odds'],
                            "draw_odds"    => $_POST['game_draw_odds'],
                            "away_odds"    => $_POST['game_away_odds'],
                            "home_letodds" => $_POST['game_home_letodds'],
                            "draw_letodds" => $_POST['game_draw_letodds'],
                            "away_letodds" => $_POST['game_away_letodds']
                        ];
                        $odds_other = json_encode($odds_other);
                        break;
                }
                
                if($game_type == 1){
                    $model->game_id = $game_id;
                    $gtype = 0;
                }elseif ($game_type == 2) {
                    $model->gamebk_id = $game_id;
                    $gtype = 1;
                }

                $model->odds       = $odds;
                $model->odds_other = $odds_other;
                $model->handcp     = $handcp;
                $model->play_type  = $play_type;
                $model->chose_side = $chose_side;
            }
            $app_time = $gtime ?: $time;
            //保存比赛时间为APP显示时间
            $model->app_time = $app_time;
            $model->label = $label;
	        $rs = $model->add();

            if($class_id == 10 || $class_id == 54){
                if (empty($_FILES['fileInput']['tmp_name'])) {
                    $_FILES['fileInput'] = D("Cover")->cover($rs,$game_id,$gtype);
                }
            }

            if (!empty($_FILES['fileInput']['tmp_name'])) {
                $return = D('Uploads')->uploadImg("fileInput", "publish", $rs,'',"[[400,400,{$rs}]]");
                if($return['status'] == 1)
                    M("PublishList")->where(['id'=>$rs])->save(['img'=>$return['url']]);
            }

            //如果有第二个资讯分类id，就多保存一份
            if($_POST['class_id2']){
                $two = M('PublishList')->where(['id' => $rs])->find();
                $two["class_id"] = $_POST['class_id2'];
                unset($two["id"]);
                M('PublishList')->add($two);
            }

            //同步彩票APP
            if($_POST['is_update'] == 1){
                $cp['classId']     = 1;
                $cp['isRecommend'] = $_POST['app_recommend'];
                $cp['title']       = $_POST['title'];
                $cp['remark']      = $_POST['remark'];
                $cp['content']     = $_POST['content'];
                $cp['label']       = $label;
                $cp['img']         = isset($return['url']) ?: '';
                $cp['appTime']     = $app_time;
                $cp['updateTime']  = $time;
                $cp['seoKeywords'] = $_POST['seo_keys'];
                $cp['qqtyId']      = $rs;

                M('PublishList', 'qc_', C('DB_CONFIG2'))->add($cp);
            }
            //百度主动推送
            $urls = array(
                newsUrl($rs,$time,$class_id,$this->PublishClass),
            );
            $result = baiduPushNews($urls);
		}else{
			//为修改
            $model->editor = $_SESSION['authId'];
            $model->label = $label;
			$rs = $model->save();
            
            if($rs){
                S('api_articleDetail'.$id, NULL);
                //百度主动推送
                $urls = array(
                    newsUrl($id,$data['add_time'],$data['class_id'],$this->PublishClass),
                );
                $result = baiduPushNews($urls);
            }
            //是否有上传
            if (!empty($_FILES['fileInput']['tmp_name'])) {
                //先删除原来图片
                $fileArr = array(
                    "/publish/{$id}.jpg",
                    "/publish/{$id}.gif",
                    "/publish/{$id}.png",
                    "/publish/{$id}.swf",
                );
                D('Uploads')->deleteFile($fileArr);
                //上传图片
                $return = D('Uploads')->uploadImg("fileInput", "publish", $id,'',"[[400,400,{$id}]]");
                //修改路径
                if($return['status'] == 1)
                    M("PublishList")->where(['id'=>$id])->save(['img'=>$return['url']]);
            }

            //如果有第二个资讯分类id，就多保存一份
            if($_POST['class_id2']){
                $two = M('PublishList')->where(['id' => $id])->find();
                $two["class_id"] = $_POST['class_id2'];
                unset($two["id"]);
                M('PublishList')->add($two);
            }

            //同步彩票APP
            if($_POST['is_update'] == 1){
                $cp['classId']     = 1;
                $cp['isRecommend'] = $_POST['app_recommend'];
                $cp['title']       = $_POST['title'];
                $cp['remark']      = $_POST['remark'];
                $cp['content']     = $_POST['content'];
                $cp['label']       = $label;
                $cp['img']         = isset($return['url']) ?: '';
                $cp['appTime']     = $_POST['app_time'];
                $cp['updateTime']  = time();
                $cp['seoKeywords'] = $_POST['seo_keys'];

                //判断是否修改，否则新增
                if(M('PublishList', 'qc_', C('DB_CONFIG2'))->where(['qqtyId' => $id])->count()){
                    M('PublishList', 'qc_', C('DB_CONFIG2'))->where(['qqtyId' => $id])->save($cp);
                }else{
                    $cp['qqtyId'] = $id;
                    M('PublishList', 'qc_', C('DB_CONFIG2'))->add($cp);
                }
            }
		}
		if (false !== $rs) {
            if(I('status') === '0') $this->publishForbid($id);
            //成功提示
            $this->success('保存成功!'.$result,cookie('_currentUrl_'),'',true);
        } else {
            //错误提示
            $this->error('编辑失败!');
        }
	}

    //异步删除图片
    public function delPic(){
        $id = I('id');
        if(empty($id)){
            $this->error('参数错误!');
        }
        $fileArr = array(
            "/publish/{$id}.jpg",
            "/publish/{$id}.gif",
            "/publish/{$id}.png",
            "/publish/{$id}.swf",
        );
        //执行删除
        $return = D('Uploads')->deleteFile($fileArr);
        if($return['status'] == 1){
            //删除路径
            if(M("PublishList")->where(['id'=>$id])->save(['img'=>NULL])){
                $this->success('删除成功！');
            }else{
                $this->error('删除失败！');
            }
        }else{
            $this->error('删除失败！');
        }
    }

    //删除单个
    public function delete() {
        //删除指定记录
        $model = M("PublishList");
        if (!empty($model)) {
            $id = $_REQUEST ['id'];
            if (isset($id)) {
                $condition = array('id' => $id);
                if (false !== $model->where($condition)->delete()) {
                    //删除图片
                    $fileArr = array(
                        "/publish/{$id}.jpg",
                        "/publish/{$id}.gif",
                        "/publish/{$id}.png",
                        "/publish/{$id}.swf",
                    );
                    //执行删除
                    $return = D('Uploads')->deleteFile($fileArr);
                    $this->success('删除成功！');
                } else {
                    $this->error('删除失败！');
                }
            } else {
                $this->error('非法操作');
            }
        }
    }

    /**
    * 添加删除操作  (多个删除)
    * @access
    * @return string
    */
    public function delAll(){
        //删除指定记录
        $model = M("PublishList");
        if (!empty($model)) {
            $ids = isset($_POST['id']) ? $_POST['id'] : null;
            if ($ids) {
                $idsArr = explode(',', $ids);
                $condition = array ("id" => array ('in',$idsArr));
                if (false !== $model->where($condition)->delete()) {
                    //删除图片
                    foreach ($idsArr as $k => $v) {
                        $fileArr = array(
                            "/publish/{$v}.jpg",
                            "/publish/{$v}.gif",
                            "/publish/{$v}.png",
                            "/publish/{$v}.swf",
                        );
                        //执行删除
                        $return = D('Uploads')->deleteFile($fileArr);
                    }
                    $this->success('批量删除成功！');
                } else {
                    $this->error('批量删除失败！');
                }
            } else {
                $this->error('非法操作');
            }
        }
    }
    //删除30天前 未编辑 禁用的资讯
    public function delWeek()
    {
        $time = strtotime('-30 day');
        $where['status'] = 0;
        $where['update_time'] = 0;
        $where['add_time'] = ['lt',$time];
        $rs = M('PublishList')->where($where)->delete();
        if($rs){
            $this->success('删除成功！');
        }else{
            $this->error('删除失败！');
        }
    }

    //手动修改推介结果
    public function editResult()
    {
        $res = M('PublishList')->where(['id'=>I('id')])->save(['result'=>I('result')]);
        if (false !== $res) {
            $this->success('推介结果修改成功！',cookie('_currentUrl_'));
        } else {
            $this->error('推介结果修改失败！');
        }
    }

    //推介结果结算
    public function runQuiz()
    {
        $news = M('PublishList p')
                ->join("LEFT JOIN qc_game_fbinfo gf on gf.game_id = p.game_id")
                ->field("p.handcp,p.id,p.play_type,p.chose_side,p.result,gf.game_state,gf.score")
                ->where(['p.class_id'=>'10','p.status'=>1,'p.game_id'=>['gt',0],'p.result'=>['in',['0','-10','-11','-12','-13','-14']],'gf.game_state'=>'-1'])
                ->select();
                
        $newsbk = M('PublishList p')
                ->join("LEFT JOIN qc_game_bkinfo gf on gf.game_id = p.gamebk_id")
                ->field("p.handcp,p.id,p.play_type,p.chose_side,p.result,gf.game_state,gf.score,gf.half_score")
                ->where(['p.class_id'=>'10','p.status'=>1,'p.gamebk_id'=>['gt',0],'p.result'=>['in',['0','-10','-2','-12','-13','-14','-5']],'gf.game_state'=>'-1'])
                ->select();

        $num = $numbk = 0;
        //足球资讯推荐结算
        foreach ($news as $k => $v)
        {
            if($v['score']=='' || $v['play_type']=='' || $v['handcp']=='' || $v['chose_side']==''){
                continue;
            }
            //获取输赢
            $result = getTheWin( $v['score'],$v['play_type'],$v['handcp'],$v['chose_side'] );
            //修改推介结果
            if( M('PublishList')->where(['id'=>$v['id']])->save(['result'=>$result]) ) $num++;
        }
        //蓝球资讯推荐结算
        foreach ($newsbk as $k => $v)
        {   
            if($v['score']=='' || $v['play_type']=='' || $v['handcp']=='' || $v['chose_side']=='' || $v['half_score']==''){
                continue;
            }
            //获取输赢
            $result = getTheWinBk($v['score'],$v['half_score'],$v['play_type'],$v['handcp'],$v['chose_side']);

            //修改推介结果
            if( M('PublishList')->where(['id'=>$v['id']])->save(['result'=>$result]) ) $num++;
        }
        echo '更新了 <b style="color:red;font-size:15px;">'.$num.'</b> 条足球资讯推介数据<br/>';
        echo '更新了 <b style="color:red;font-size:15px;">'.$numbk.'</b> 条篮球资讯推介数据<br/>';
    }


    //流水数据和审核数据添加
    function sendAccountLog($publish, $expert_fee=false)
    {
        $count= M("FrontUser")->master(true)->field("coin, unable_coin")->where(["id" => $publish["user_id"]])->select()[0];
        $coin = $count["coin"];
        $unable_coin = $count["unable_coin"];
        if ($expert_fee == false) {
            $expert_fee = $publish["expert_fee"];
        }
        M("FrontUser")->where(["id" => $publish["user_id"]])->save(["coin" => $coin + $expert_fee]);
        M("PublishList")->where(["id" => $publish["id"]])->save(["is_audit" =>1]);
        $AccountArray = [
            "user_id" => $publish["user_id"],
            "log_time" => time(),
            "log_type" => 20,
            "log_status" => 1,
            "change_num" => $expert_fee,
            "total_coin" => $coin + $expert_fee + $unable_coin,
            "desc" => "您发布的" . $publish["short_title"] . "的文章获得" . $expert_fee. "金币稿费",
            'operation_time' => time()
        ];
        M('AccountLog')->data($AccountArray)->add();
    }


    public function expertFeeQuiz()
    {
        //可选文章 (专家发表, 并且未审核的文章)
        $expertArticleMap = [
            "class_id" => 10,
            "is_audit" => 0,
            "add_time" => ["between", [1513569600, strtotime(date('Y-m-d')) + 38100]]
        ];

        $expertArticle = M("PublishList")
            ->field("id, user_id, add_time, result, title, content, click_number, game_id, gamebk_id,  is_settlement, expert_fee")
            ->where($expertArticleMap)
            ->select();

        //稿费配置信息
        $expertConfig = json_decode(M('config')->where(['sign' => 'expertConfig'])->getField('config'), true)["expertConfig"];

        //字数规则
        function wordCountRule($content)
        {
            $other = preg_match_all("/<img .*?>|<br\/>|&nbsp;/is", htmlspecialchars_decode($content));
            preg_match_all("/./us", preg_replace("/<.*?>|&nbsp;/is", "", htmlspecialchars_decode($content)), $match);
            return count($match[0]) + $other;
        }

        //连胜规则
        function streakCountRule($article, $streakConfig)
        {
            $sevenMap =['add_time' => ["between",[($article['add_time']-7*24*60*60), $article['add_time']]],
                'user_id' => $article['user_id'],
                "class_id" => 10,
                "result" => ["in", ["-1", "-0.5", "0.5", "1", "2"]]
            ];
            $sevenDay = M("PublishList")
                ->field("result")
                ->where($sevenMap)
                ->order(["add_time" => "desc"])
                ->select();
            $victoryCount = array_count_values(array_column($sevenDay, 'result'));
            $victoryRate = getGambleWinrate($victoryCount["1"], $victoryCount["0.5"], $victoryCount["-1"], $victoryCount["-0.5"]);
            M('PublishList')->where(['id' => $article['id']])->save(["sevenday_rate" => $victoryRate]);
            $streakMap = ["user_id" => ["eq", $article['user_id']],
                "result" => ["in", ["-1", "-0.5", "0.5", "1", "2","-10","-11","-12","-13","-14"]],
                "class_id" => 10,
                "add_time" => ["lt", $article['add_time']]
            ];
            $streakCount = M("PublishList")
                ->field("result,id,user_id,add_time")
                ->where($streakMap)
                ->order("add_time desc")
                ->limit(11)
                ->select();
            $common = null;

            if (in_array($article["result"], ["-1", "-0.5", "2","-10","-11","-12","-13","-14"]) || $streakCount === null) {
                $common = $streakConfig["one"];
                echo "发表此篇文章时失败或异常了或第一次赢, ";
            } else if (in_array($article["result"], ["1", "0.5"])) {
                $i = 1;
                foreach ($streakCount as $streak) {
                    if ($streak['result'] == "1" || $streak["result"] == "0.5") {
                        $i++;
                    }
                    if (in_array($streak['result'],  ["2","-10","-11","-12","-13","-14"])) {
                        continue;
                    }
                    if ($streak['result'] == "-0.5" || $streak["result"] == "-1") {
                        break;
                    }
                }
                if ($i > 1 && $i <= 5) {
                    $common = $streakConfig["twoToFive"];
                } else if ($i > 5 && $i <= 10) {
                    $common = $streakConfig["sixToTen"];
                } else if ($i > 10) {
                    $common = $streakConfig["elevenToMore"];
                } else if ($i == 1) {
                    $common = $streakConfig["one"];
                }
                echo "发表此篇文章时连胜了".$i."盘, ";
            }
            $articlePrices = (1 + ($victoryRate / 100)) * $common;
            echo "发表此篇文章时胜率为".$victoryRate.", 此文章的连胜总价格为:".$articlePrices;
            return $articlePrices;
        }

        //评论规则
        function commentRule($publish_id, $user_id)
        {
            $commonMap = ["publish_id" => $publish_id];
            $common = M("comment")
                ->field("user_id")
                ->where($commonMap)
                ->group("user_id")
                ->select();
            if (in_array($user_id, array_column($common, "user_id"))) {
                return count($common) - 1;
            } else {
                return count($common);
            }
        }

        //发布时间规则
        function releaseTimeRule($article, $releaseTime)
        {
            if ($article["game_id"] != "0"){
                $gameStartTime = M("gameFbinfo")
                    ->field("gtime")
                    ->where(["game_id" => $article["game_id"]])
                    ->select();
            } else if ($article["gamebk_id"] != "0") {
                $gameStartTime = M("gameBkinfo")
                    ->field("gtime")
                    ->where(["game_id" => $article["gamebk_id"]])
                    ->select();
            }
            $hour = ($gameStartTime[0]["gtime"] - $article["add_time"]) / 3600;
            echo "发布时间大于" . $hour."小时.";
            if ($hour > 0 && $hour <= 3) {
                return $releaseTime["zeroToThree"];
            } else if ($hour > 3 && $hour <= 5) {
                return $releaseTime["threeToFive"];
            } else if ($hour > 5 && $hour <= 10) {
                return $releaseTime["fiveToTen"];
            } else if ($hour > 10) {
                return $releaseTime["tenToMore"];
            } else {
                return 0;
            }
        }

        //遍历专家资讯
        foreach ($expertArticle as $article) {
            // 如果24小时未审核改审核系数为2
            if ($article["is_settlement"] == 1 && $article["is_audit"] == 0) {
                M('PublishList')->where(['id' => $article['id']])->save(["is_settlement" => 2]);
                continue;
            }

            //如果48小时未审核 则自动审核
            if ($article["is_settlement"] == 2 && $article["is_audit"] == 0) {
                $this->sendAccountLog($article);
                echo "文章id : ".$article['id']."自动审核完成<br><br>";
                continue;
            }
            echo "文章id : ".$article['id']."<br><br>";
            $expertArticleFee = 0;
            if ($article["game_id"] != "0" || $article["gamebk_id"] != "0") {
                //分析稿费
                //字数规则
                $wordCount = wordCountRule($article["content"]);
                $wordUnit = $expertConfig["wordCountRule"]['unit'];
                $wordMax = $expertConfig["wordCountRule"]['max'];
                $wordPrice = $wordCount * $wordUnit > $wordMax ? $wordMax : $wordCount * $wordUnit;
                echo "字数为: " . $wordCount.",字数价格为: " . $wordPrice;
                $expertArticleFee += $wordPrice;
                echo "<br><br>";

                //连胜规则
                $streakCount = streakCountRule($article, $expertConfig["streakCountRule"]);
                $expertArticleFee += $streakCount;
                echo "<br><br>";

                //阅读规则
                $reading = $article["click_number"];
                $readUnit = $expertConfig["readingsRule"]["unit"];
                $readMax = $expertConfig["readingsRule"]["max"];
                $readingPrice = $readUnit * $reading > $readMax ? $readMax : $readUnit * $reading;
                echo "阅读量:" . $reading.",阅读价格:".$readingPrice;
                $expertArticleFee += $readingPrice;
                echo "<br><br>";

                //评论规则
                $commonCount = commentRule($article["id"], $article["user_id"]);
                $commonUnit = $expertConfig["commentsRule"]["unit"];
                $commonMax = $expertConfig["commentsRule"]["max"];
                $commonPrice = $commonCount * $commonUnit > $commonMax ? $commonMax : $commonUnit * $commonCount;
                echo "评论数量为:" . $commonCount.", 评论价格为:" . $commonPrice;
                $expertArticleFee += $commonPrice;
                echo "<br><br>";

                //发布时间规则
                $releaseTimePrice = releaseTimeRule($article, $expertConfig["releaseTimeRule"]);
                $expertArticleFee += $releaseTimePrice;
                $expertArticleFee = $expertArticleFee * $expertConfig["analysisCoefficient"];
                echo "时间价格总数为: " . $releaseTimePrice;
                echo "<br><br>";
                $expertStatus = 0;
            } else {
                //资讯稿费
                //字数规则
                $wordCount = wordCountRule($article["content"]);
                $wordUnit = $expertConfig["wordCountRule"]['unit'];
                $wordMax = $expertConfig["wordCountRule"]['max'];
                $wordPrice = $wordCount * $wordUnit > $wordMax ? $wordMax : $wordCount * $wordUnit;
                echo "字数为: " . $wordCount.",字数价格为: " . $wordPrice;
                $expertArticleFee += $wordPrice;
                echo "<br><br>";

                //阅读规则
                $reading = $article["click_number"];
                $readUnit = $expertConfig["readingsRule"]["unit"];
                $readMax = $expertConfig["readingsRule"]["max"];
                $readingPrice = $readUnit * $reading > $readMax ? $readMax : $readUnit * $reading;
                echo "阅读量:" . $reading.",阅读价格:".$readingPrice;
                $expertArticleFee += $readingPrice;
                echo "<br><br>";

                //评论规则
                $commonCount = commentRule($article["id"], $article["user_id"]);
                $commonUnit = $expertConfig["commentsRule"]["unit"];
                $commonMax = $expertConfig["commentsRule"]["max"];
                $commonPrice = $commonCount * $commonUnit > $commonMax ? $commonMax : $commonUnit * $commonCount;
                $expertArticleFee += $commonPrice;
                $expertArticleFee = $expertArticleFee * $expertConfig["InformationCoefficient"];
                echo "评论数量为:" . $commonCount.", 评论价格为:" . $commonPrice;
                echo "<br><br>";
                $expertStatus = 1;
            }
            $total = round($expertArticleFee);
            echo "四舍五入后总稿费为: ".$total;
            echo "<br><br>";
            try {
                if ($expertStatus == 1 || $expertStatus == 0 && $article['result'] != 0) {
                    if (M('PublishList')->where(['id' => $article['id']])->save(['expert_fee' => $total, "is_settlement" => 1])) {
                        sendMsg($article["user_id"], $article["title"], "恭喜您，发布的" . $article["title"] . "的文章获得" . $total . "金币稿费，请您等待48小时后审核通过进行发放");
                        echo "用户消息已发送,结算成功";
                    } else {
                        echo "结算失败";
                    }
                }
            } catch (Exception $exception) {
                $exception->getMessage();
            }
            echo "<br><br>";
            echo "=======================================================================";
            echo "<br><br>";
        }
    }


    /**
     * 专家稿费审核列表
     */
    public function expertFeeAudit()
    {
        //列表过滤器，生成查询Map对象
        $map = $this->_search('PublishListView');
        if (!empty($_REQUEST['startTime']) || !empty($_REQUEST['endTime'])) {
            if (!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])) {
                $startTime = strtotime($_REQUEST['startTime']);
                $endTime = strtotime($_REQUEST['endTime']) + 86400;
                $map['update_time'] = array('BETWEEN', array($startTime, $endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']);
                $map['update_time'] = array('EGT', $strtotime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime']) + 86400;
                $map['update_time'] = array('ELT', $endTime);
            }
        }

        if (!empty($_REQUEST["is_settlement"]) || $_REQUEST["is_settlement"] == 1) {
            $map["is_settlement"] = array("egt", $_REQUEST["is_settlement"]);
        }
        //手动获取列表
        $list = $this->_list(D('PublishListView'), $map, 'update_time desc');
        foreach ($list as $key => $value) {
            $list[$key]['img'] = Tool::imagesReplace($value['img']);
            $list[$key]['is_add'] = $list[$key]['img'] ? explode('&', $list[$key]['img'])[1] : 0;
            $list[$key]['href']   = newsUrl($value['id'],$value['add_time'],$value['class_id'],$this->PublishClass);
            $publish_id_arr[] = $value['id'];
        }
        //评论数
        $commentCount = M('Comment')
            ->where(['publish_id' => ['IN', $publish_id_arr]])
            ->field('publish_id,count(id) as commentNum')
            ->group('publish_id')
            ->select();
        //评论人数
        $whereIn = '(' . implode(',', $publish_id_arr) . ')';
        $discussantCount = M()
            ->query('select count(c.id) as discussantNum,c.publish_id from (SELECT id,publish_id FROM `qc_comment` where publish_id in ' . $whereIn . ' GROUP BY publish_id,user_id ) c GROUP BY c.publish_id');

        //获取编辑
        $editor = M('user')->select();

        foreach ($list as $key => $value) {
            foreach ($commentCount as $k => $v) {
                if ($v['publish_id'] == $value['id']) {
                    $list[$key]['commentNum'] = $v['commentNum'];
                }
            }
            foreach ($discussantCount as $k => $v) {
                if ($v['publish_id'] == $value['id']) {
                    $list[$key]['discussantNum'] = $v['discussantNum'];
                }
            }
            foreach ($editor as $k => $v) {
                if ($v['id'] == $value['author']) {
                    $list[$key]['authorName'] = $v['nickname'];
                }
                if ($v['id'] == $value['editor']) {
                    $list[$key]['editorName'] = $v['nickname'];
                }
            }
        }

        if (!empty($map['nick_name'])) {
            $map_like['nick_name'] = $map['nick_name'];
            $other = M("FrontUser")->field("id")->where($map_like)->select();
            $rs = array_column($other,"id");
            unset($map['nick_name']);
            $totalData= M("PublishList")
                ->where(["user_id" => ["in", $rs]])
                ->where($map)->field("sum(expert_fee) as user_total_expert_fee")->group("user_id")->select();
        }else {
            $totalData= M("PublishList")->where($map)->field("sum(expert_fee) as user_total_expert_fee")->group("user_id")->select();
        }
        $totalAmount = array_sum(array_column($totalData,'user_total_expert_fee'));
        $this->assign('totalAmount', $totalAmount);
        $this->assign('totalUser', count($totalData));
        $this->assign('editor', $editor);
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 稿费配置信息
     */
    public function expertFeeConfig()
    {
        $expertConfig = M('config')->where(['sign' => 'expertConfig'])->getField('config');
        if (IS_POST) {
            $expertConfig = I("expertConfig");
            $expert["expertConfig"] = $expertConfig;
            if (M('config')->where(['sign' => 'expertConfig'])->find()) {
                $rs = M('config')->where(['sign' => 'expertConfig'])->save(['config' => json_encode($expert)]);
            } else {
                $rs = M('config')->add(['sign' => 'expertConfig','config' => json_encode($expert)]);
            }
            if ($rs)
                $this->success('修改成功');
            $this->error('修改失败!');
        } else {
            $this->assign('expertConfig', json_decode($expertConfig, true));
        }
        $this->display();
    }

    /**
     * 审核操作
     */
    public function managerAudit()
    {
        $id = I('id');
        $publish = M("PublishList")->field("id, user_id, title, short_title, result,is_settlement, expert_fee, is_audit, remarks, sevenday_rate, app_recommend")->find($id);
        if (IS_POST) {
            $is_settlement = I("is_settlement");
            $expert_fee = I("expert_fee");
            $is_audit = I("is_audit");
            $title = I("title");
            $user_id = I("user_id");
            $remarks = I("remarks");
            $app_recommend = I("app_recommend");
            if (M('PublishList')->where(['id' => $id])->save(['expert_fee' => $expert_fee, "is_settlement" => $is_settlement, "is_audit" => $is_audit, "remarks" => $remarks, "app_recommend" => $app_recommend])) {
                if ($is_audit == "1" && $publish["is_audit"] == 0) {
                    $this->sendAccountLog($publish, $expert_fee);
                }
                if ($is_audit == "2") {
                    sendMsg($user_id, $title, "由于您，发布的" . $title . "的文章不通过审核，所以稿费被取消，请后续规范文章的内容");
                }
                $this->success('修改成功!');
            } else {
                $this->error("修改失败!");
            }
            exit();
        }
        if (!$publish) $this->error("参数错误!");
        $this->assign('vo', $publish);
        $this->display();
    }

    /**
     * 批量审核操作
     */
    public function AuditAll()
    {
        //删除指定记录
        if (!empty(M("PublishList"))) {
            $ids = isset($_POST['id']) ? $_POST['id'] : null;
            if ($ids) {
                $idsArr = explode(',', $ids);
                foreach ($idsArr as $id) {
                    $publish = M("PublishList")->where(["id" => $id])->find();
                    if ($publish["is_settlement"] != 0 && $publish["is_audit"] == 0) {
                        $this->sendAccountLog($publish);
                    } else {
                        $this->error("拥有未结算或非未审核信息!");
                        break;
                    }
                }
                $this->success("批量审核成功!");
            } else {
                $this->error('非法操作');
            }
        }
    }


    //热门标签表
    public function keyword()
    {
        //排序字段 默认为主键名
        $order = $_REQUEST ['_order'] ? : 'id';
        //排序方式默认按照倒序排列
        $sort = $_REQUEST ['_sort'] == 'asc' ? 'asc' : 'desc';
        $map = $this->_search('HotKeyword');
        unset($map['user_id']);
        //获得时间
        if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
            if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime']))
            {
                $startTime = strtotime($_REQUEST ['startTime']);
                $endTime   = strtotime($_REQUEST ['endTime'])+86400;
                $map['get_time'] = array('BETWEEN',array($startTime,$endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']);
                $map['get_time'] = array('EGT',$strtotime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime']);
                $map['get_time'] = array('ELT',$endTime);
            }
        }

        $page = !empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1;
        $pageNum = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];

        $countList = M('HotKeyword')->where($map)->count();
        $this->assign('totalCount',$countList);

        $list = M('HotKeyword h')
            ->join('left join qc_publish_class p on p.id = h.class_id')
            ->field('h.*,p.name')
            ->where($map)
            ->page($page)
            ->limit($pageNum)
            ->order($order." ".$sort)
            ->select();
        $this->setJumpUrl();
        $this->assign ( 'numPerPage', $pageNum );
        $this->assign('list',$list);
        $this->display();
    }

    //热门标签表编辑
    public function key_edit()
    {
        //获取所有记录
        $list = M('PublishClass')->select();
        //引用Tree类
        $list = Tool::getTree($list, $pid = 0, $col_id = 'id', $col_pid = 'pid', $col_cid = 'level');
        $this->assign('list', $list);
        $this->display();
    }

    //热门标签保存
    public function key_save()
    {
        if (I('class_id') < 1) $this->error('请选择分类');
        $data = [
            'class_id' => I('class_id'),
            'keyword' => I('keyword'),
            'url_name' => I('url_name'),
            'update_time' => time()
        ];
        $id = I('id');
        if ($id) {
            $rs = M('HotKeyword')->where(['id' => $id])->save($data);
        }else{
            $rs = M('HotKeyword')->add($data);
        }
        if (false !== $rs) {
            S('cache_hot_keyword',null);
            //成功提示
            $this->success('保存成功!',cookie('_currentUrl_'));
        } else {
            //错误提示
            $this->error('保存失败!');
        }
    }

    /**
    +----------------------------------------------------------
     * 文章禁用操作
     *
    +----------------------------------------------------------
     * @access public
    +----------------------------------------------------------
     * @return string
    +----------------------------------------------------------
     * @throws FcsException
    +----------------------------------------------------------
     */
    public function forbids() {
        $dwz_db_name=$dwz_db_name ? $dwz_db_name : $this->getActionName();
        $model = CM($dwz_db_name);
        $pk = $model->getPk();
        $id = $_GET [$pk];
        $list = M('PublishList')->where(['id'=>$id])->save(['status'=>'0']);

        if ($list !== false) {
            $this->publishForbid($id);
            $this->success('状态禁用成功',cookie('_currentUrl_'));
        } else {
            $this->error('状态禁用失败！');
        }
    }

    //禁用后连带操作
    public function publishForbid($id)
    {
        //将审核状态修改为不通过
        M('PublishList')->where(['id'=>$id])->save(['is_audit'=>'2']);
        //发送站内消息
        $data = M('PublishList p')->field('p.title,f.is_robot,f.id')
            ->join('LEFT JOIN  qc_front_user f on f.id=p.user_id')
            ->where(['p.id'=>$id])
            ->find();
        if($data['is_robot'] != 1)
        {
            sendMsg($data['id'], $data['title'], "您发布的资讯".$data['title']."未能达到标准，请按照发布规则撰写比赛，如有问题请联系客服，感谢您的支持");
        }
    }

}