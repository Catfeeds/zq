<?php
/**
 * 推荐产品列表控制器
 *
 * @author dengweijun <406516482@qq.com>
 *
 * @since  2017-3-9
 */
use Think\Tool\Tool;
class IntroProductsController extends CommonController {
    /**
     * Index页显示
     */
    public function index() 
    {
        $model = M('IntroProducts p');
        //排序字段 默认为主键名
        $order = $_REQUEST ['_order'] ? : 'id';
        //排序方式默认按照倒序排列
        $sort = $_REQUEST ['_sort'] == 'asc' ? 'asc' : 'desc';
        
        if($_REQUEST['name'] != ''){
            $map['p.name'] = ['like',"%".$_REQUEST['name']."%"];
        }
        if($_REQUEST['class_name'] != ''){
            $map['c.name'] = ['like',"%".$_REQUEST['class_name']."%"];
        }
        if($_REQUEST['status'] != ''){
            $map['p.status'] = $_REQUEST['status'];
        }
        
        $nickname = I('nickname');
        if (!empty($nickname))
		{
			$map['u.nickname'] = ['like',"%".$nickname."%"];
		}
        
        //取得满足条件的记录数
        $count = $model->master(true)->join('LEFT JOIN qc_intro_class c on c.id=p.class_id')->join('LEFT JOIN qc_user u on u.id=p.admin_id')->where($map)->count();
        if ($count > 0) 
        {
            $pageNum     = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
            $currentPage = !empty($_REQUEST[C('VAR_PAGE')]) ? $_REQUEST[C('VAR_PAGE')]:1;
            //分页查询数据
            $list = $model
				->master(true)
				->join('LEFT JOIN qc_intro_class c on c.id=p.class_id')
				->join('LEFT JOIN qc_user u on u.id=p.admin_id')
				->field("p.*,c.name as class_name,u.nickname")
				->where($map)
				->group('p.id')
				->order( $order." ".$sort )
				->page($currentPage,$pageNum)
				->select();
			//模板赋值显示
            $blockTime  = getBlockTime(1, true);
            foreach ($list as $k => $v) {
                $list[$k]['logo'] = Tool::imagesReplace($v['logo']);
            }
            $productArr = array_map('array_shift', $list);
            $product_id = implode(',', $productArr);
            //预购数量
            $IntroBuy = M('IntroBuy')
                        ->field('product_id,count(id) as buy_num')
                        ->where("list_id = 0 AND product_id in (".$product_id.")")
                        ->group('product_id')
                        ->select();
            //今日是否已发布产品
            $pushArr = M('IntroLists')
                    ->where([ 'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]], 'product_id' => ['in',$productArr]])
                    ->group('product_id')
                    ->getField('product_id',true);

            // //不是admin只有属于自己的发布产品
            // $admin_id = $_SESSION['authId'];
            // if (!in_array($admin_id, C('RBAC_LOGIN_USER')))
            // {
            //     $map['p.admin_id'] = $admin_id;
            // }
            foreach ($list as $k => $v) 
            {
                foreach ($IntroBuy as $kk => $vv) {
                    if($v['id'] == $vv['product_id']){
                        $list[$k]['buy_num'] = $vv['buy_num'];
                    }
                }

                $list[$k]['is_push'] = in_array($v['id'], $pushArr) ? 1: 0;
            }
            $this->assign('list', $list);
            $this->assign ( 'totalCount', $count );
            $this->assign ( 'numPerPage', $pageNum ); //每页显示多少条
            $this->assign ( 'currentPage', $currentPage);
            $this->setJumpUrl();
        }
        $this->display();
    }
    public function add() {
        $IntroClass = M('IntroClass')->where(['status'=>1])->select();
        $this->assign ( 'IntroClass', $IntroClass );
        $this->assign('adminArr', M('User')->field('id,nickname')->select());
        $this->display();
    }
	
    public function addList()
	{
        $product_id = I('product_id',0,'int');
		
		$product = M('IntroProducts')->field("name,pay_num,total_num,game_num,admin_id")->where(['id'=>$product_id])->find();
		if (empty($product))
        {
            $this->error('参数错误');
        }
        //不是admin只有属于自己的发布产品
        // $admin_id = $_SESSION['authId'];
        // if (!in_array($admin_id, C('RBAC_LOGIN_USER')) && $product['admin_id'] != $admin_id)
        // {
        //     $this->error('您没有权限！');
        // }
        
        $blockTime  = getBlockTime(1, true);

        //今天已预购数量
        $IntroBuyNum = M('IntroBuy')->field('user_id')->where(['product_id'=>$product_id,'list_id'=>0,'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]]])->count();

        //剩余数量计算
        $pay_num = $product['pay_num'] + $IntroBuyNum;//相加
        $show_remain_num = $product['total_num']-$pay_num;
        $this->assign ( 'show_remain_num', $show_remain_num );

        if(IS_POST){
            $lists = M('IntroLists')
                ->where([ 'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]], 'product_id' => $product_id])
                ->find();
            if($lists) $this->error("请产品今天已经发过推介，请明天再发布！");

            $game_num = I('game_num');
            $pub_time   = I('pub_time') ? strtotime(I('pub_time')): time()+1800;
            //处理推介赛事
            for ($i=1; $i <= $game_num; $i++) 
            { 
                $game_id    = I('game'.$i.'_game_id');
                $union_id   = I('game'.$i.'_union_id');
                $odds       = I('game'.$i.'_odds');
                $handcp     = I('game'.$i.'_handcp');
                $odds_other = I('game'.$i.'_odds_other');
                $play_type  = I('game'.$i.'_play_type');
                $chose_side = I('game'.$i.'_chose_side');
                $union_name = I('game'.$i.'_union_name');
                $gtime      = I('game'.$i.'_gtime');
                $home_team_name = I('game'.$i.'_home_team_name');
                $away_team_name = I('game'.$i.'_away_team_name');

                if($odds == '') $this->error("请选择赛事玩法（{$i}）");
                if($pub_time > $gtime) $this->error("发布时间不能大于比赛开始时间（{$i}）");
                
                if($game_id != '')
                {
                    $game[] = [
                        'game_id'    => $game_id,
                        'union_id'   => $union_id,
                        'odds'       => $odds,
                        'handcp'     => $handcp,
                        'odds_other' => $odds_other,
                        'play_type'  => $play_type,
                        'chose_side' => $chose_side, 
                        'union_name' => $union_name, 
                        'gtime'      => $gtime, 
                        'home_team_name' => $home_team_name, 
                        'away_team_name' => $away_team_name, 
                    ];

                    $gameT[$game_id.$play_type] = $game_id;
                    if($gtime - time() <= 1800){
                        $this->error("请检查赛事,比赛开场前30分钟不能推介！"); 
                    }
                }
            }
            if (count($gameT) != $game_num) $this->error("请检查是否有相同的赛事,玩法不能相同！"); 

            //推介数据
        
            $total_num  = I('total_num');
//            $remain_num = I('remain_num');
            $remain_num = $show_remain_num;

            if($remain_num > $total_num) $this->error("剩余数量不能大于产品限购数量！"); 
            
            if($remain_num > $show_remain_num) $this->error("今天已有{$IntroBuyNum}人预购，手动设置已预购人数{$product['pay_num']}人，剩余数量不能大于产品剩余限购数量！"); 

            //添加推介记录
            $IntroLists = [
                'product_id'  => $product_id,
                'pub_time'    => $pub_time,
                'create_time' => time(),
                'remain_num'  => $remain_num,
                'admin_id'    => $_SESSION['authId']
            ];

            $list_id = M('IntroLists')->add($IntroLists);

            if(!$list_id) $this->error('添加推介失败！');

            //查询是否有预购
            $IntroBuy = M('IntroBuy')->field('user_id')->where(['product_id'=>$product_id,'list_id'=>0])->select();

            $IntroBuy_user = [];
            if($IntroBuy)
            {
                //修改最新推介list_id
                M('IntroBuy')->where(['product_id'=>$product_id,'list_id'=>0])->save(['list_id'=>$list_id]);
                //发送短信和app推送
                $msg = "您订阅的{$product['name']}已经发布推荐信息，请登陆全球体育进行查看。"; //消息内容
                foreach ($IntroBuy as $k => $v) {
                    $IntroBuy[$k]['list_id']   = $list_id;
                    $IntroBuy[$k]['content']   = $msg;
                    $IntroBuy[$k]['send_type'] = 0; //短信和推送
                    $IntroBuy[$k]['state']     = 0;
                    $IntroBuy[$k]['is_send']   = 0;
                    $IntroBuy[$k]['module']    = 16;
                    $IntroBuy[$k]['module_value']   = $product_id;
                    $IntroBuy[$k]['send_time'] = $pub_time;
                    $IntroBuy_user[] = $v['user_id'];
                }

                M('mobileMsg')->addAll($IntroBuy);
            }

            //是否有关注
            $FollowMap['product_id'] = $product_id;
            if(!empty($IntroBuy_user)){
                $FollowMap['user_id'] = ['not in',$IntroBuy_user];
            }
            $introFollow = M('IntroFollow')->field('user_id')->where($FollowMap)->select();

            if($introFollow){
                $message = "您关注的{$product['name']}已发布赛事，请前往查看！";
                foreach ($introFollow as $k => $v) {
                    $introFollow[$k]['list_id']   = $list_id;
                    $introFollow[$k]['content']   = $message;
                    $introFollow[$k]['send_type'] = 2;
                    $introFollow[$k]['state']     = 0;
                    $introFollow[$k]['is_send']   = 0;
                    $introFollow[$k]['module']    = 16;
                    $introFollow[$k]['module_value']   = $product_id;
                    $introFollow[$k]['send_time'] = $pub_time;
                }
                M('mobileMsg')->addAll($introFollow);
            }

            //添加推介赛事
            foreach ($game as $k => $v) 
            {
                $game[$k]['list_id']    = $list_id;
                $game[$k]['product_id'] = $product_id;
                $game[$k]['create_time'] = time();
            }

            if( M('IntroGamble')->addAll($game) )
            {
                $this->success('推介成功！',cookie('_currentUrl_'),'',true);
            }
            else
            {
                $this->success('添加推介赛事失败！');
            }

        }
        
        $this->assign ( 'product', $product );
        $this->assign ( 'IntroBuyNum', $IntroBuyNum );
        $this->display();
    }

    /**
     * 弹窗查找赛事
     */
    public function findGame(){
        list($game) = D('GambleHall')->matchList(1);
        //去掉已开始的赛事
        foreach ($game as $k => $v) {
            if($v['game_state'] != 0 or time() > $v['gtime']){
                unset($game[$k]);
            }
        }
        foreach ($game as $k => $v) {
            if (array_key_exists($v['union_id'],$union))
            {
                $union[$v['union_id']]['union_num'] = (string)($union[$v['union_id']]['union_num']+1);
            }
            else
            {
                $union[$v['union_id']] = ['union_id'=>$v['union_id'],'union_name'=>$v['union_name'],'union_num'=>'1','union_color'=>$v['union_color']];
            }
        }
        $union = array_values($union);
        $this->assign('game', $game);
        $this->assign('union',$union);
        $this->display();
    }

    public function edit() {
        $id = I('id');
        $vo = M("IntroProducts")->find($id);
        if (!$vo){
            $this->error('参数错误');
        }
        $IntroClass = M('IntroClass')->where(['status'=>1])->select();
        $this->assign ( 'IntroClass', $IntroClass );
        $vo['logo'] = Tool::imagesReplace($vo['logo']);
		
        $this->assign('adminArr', M('User')->field('id,nickname')->select());
        $this->assign('vo', $vo);
        $this->display("add");
    }
	
    /**
     * 保存/修改记录
     *
     * @return #
    */
    public function save(){
        $id = I('id', 'int');

        $model = D('IntroProducts');
        if (false === $model->create()) {
            $this->error($model->getError());
        }
        //只有admin才能编辑发布者
        // if (!in_array($_SESSION['authId'], C('RBAC_LOGIN_USER')))
        // {
        //     unset($model->admin_id);
        // }
        if (empty($id)) {
            if (empty($_FILES['fileInput']['tmp_name'])) {
                $this->error('请上传产品logo!');
                exit;
            }
            $model->create_time = time();
            //为新增
            $rs = $model->add();
            //上传图片
            if (!empty($_FILES['fileInput']['tmp_name'])) {
                $return = D('Uploads')->uploadImg("fileInput", "introproducts" ,$rs);
                if($return['status'] == 1)
                    M('IntroProducts')->where(['id'=>$rs])->save(['logo'=>$return['url']]);
            }
        }else{
            //为修改
            $rs = $model->save();
            //是否有上传
            if (!empty($_FILES['fileInput']['tmp_name'])) {
                //先删除原来广告图
                $fileArr = array(
                    "/introproducts/{$id}.jpg",
                    "/introproducts/{$id}.gif",
                    "/introproducts/{$id}.png",
                    "/introproducts/{$id}.swf",
                );
                D('Uploads')->deleteFile($fileArr);
                //上传图片
                $return = D('Uploads')->uploadImg("fileInput", "introproducts" ,$id);
                if($return['status'] == 1)
                    M('IntroProducts')->where(['id'=>$id])->save(['logo'=>$return['url']]);
            }
        }
        if (false !== $rs) {
            //成功提示
            S('cache_Intro_class',null);
            $this->success('保存成功!',cookie('_currentUrl_'));
        } else {
            //错误提示
            $this->error('保存失败!');
        }
    }
    //异步删除图片
    public function delPic(){
        $id = I('id');
        if(empty($id)){
            $this->error('参数错误!');
        }
        $fileArr = array(
            "/introproducts/{$id}.jpg",
            "/introproducts/{$id}.gif",
            "/introproducts/{$id}.png",
            "/introproducts/{$id}.swf",
        );
        //执行删除
        $return = D('Uploads')->deleteFile($fileArr);
        if($return['status'] == 1){
            M("IntroProducts")->where(['id'=>$id])->save(['logo'=>'']);
            $this->success('删除成功！');
        }else{
            $this->error('删除失败！');
        }
    }
    //删除单个
    public function delete() {
        //删除指定记录
        $model = M("IntroProducts");
        if (!empty($model)) {
            $id = $_REQUEST ['id'];
            if (isset($id)) {
                $condition = array('id' => $id);
                if (false !== $model->where($condition)->delete()) {
                    //删除图片
                    $fileArr = array(
                        "/introproducts/{$id}.jpg",
                        "/introproducts/{$id}.gif",
                        "/introproducts/{$id}.png",
                        "/introproducts/{$id}.swf",
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

    //批量设置禁用启用
    public function onOff()
    {
        $sign = I('get.sign');
        $id = I('post.id');
        if(isset($id))
        {
            $status = $sign == 'open' ? 1 : 0;
            $re =M('IntroProducts')->where(['id'=>['in',$id]])->save(['status'=>$status]);
            if($re !== false)
            {
                $this->success('批量设置成功');
            }
            else
            {
                $this->error('批量设置失败');
            }
        }
        else
        {
            $this->error("非法操作");
        }
    }
}