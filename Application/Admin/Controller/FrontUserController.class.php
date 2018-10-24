<?php
set_time_limit(0);//0表示不限时
/**
 * 账户管理
 *
 * @author dengweijun <406516482@qq.com>
 * @since  2016-6-30
 */

class FrontUserController extends CommonController {

    public function index(){
        $map = $this->_search('FrontUser');

        //注册时间查询
        if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
            if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                $startTime = strtotime($_REQUEST ['startTime']);
                $endTime   = strtotime($_REQUEST ['endTime'])+86399;
                $map['reg_time'] = array('BETWEEN',array($startTime,$endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']);
                $map['reg_time'] = array('EGT',$strtotime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime']) +86399;
                $map['reg_time'] = array('ELT',$endTime);
            }
        }
        $no_login = I('no_login');
        if($no_login == ''){
            //登录时间查询
            if(!empty($_REQUEST ['startTimeLogin']) || !empty($_REQUEST ['endTimeLogin'])){
                if(!empty($_REQUEST ['startTimeLogin']) && !empty($_REQUEST ['endTimeLogin'])){
                    $startTimeLogin = strtotime($_REQUEST ['startTimeLogin']);
                    $endTimeLogin   = strtotime($_REQUEST ['endTimeLogin'])+86399;
                    $map['login_time'] = array('BETWEEN',array($startTimeLogin,$endTimeLogin));
                } elseif (!empty($_REQUEST['startTimeLogin'])) {
                    $startTimeLogin = strtotime($_REQUEST ['startTimeLogin']);
                    $map['login_time'] = array('EGT',$startTimeLogin);
                } elseif (!empty($_REQUEST['endTimeLogin'])) {
                    $endTimeLogin = strtotime($_REQUEST['endTimeLogin'])+86399;
                    $map['login_time'] = array('ELT',$endTimeLogin);
                }
            }
        }else{
            if(!empty($_REQUEST ['startTimeLogin']) || !empty($_REQUEST ['endTimeLogin'])){
                if(!empty($_REQUEST ['startTimeLogin']) && !empty($_REQUEST ['endTimeLogin'])){
                    $startTimeLogin = strtotime($_REQUEST ['startTimeLogin']);
                    $endTimeLogin   = strtotime($_REQUEST ['endTimeLogin'])+86399;
                    $map['login_time'] = ['exp',"< {$startTimeLogin} or login_time > {$endTimeLogin} or login_time is null"];
                } elseif (!empty($_REQUEST['startTimeLogin'])) {
                    $startTimeLogin = strtotime($_REQUEST ['startTimeLogin']);
                    $map['login_time'] = ['exp',"< {$startTimeLogin} or login_time is null"];
                } elseif (!empty($_REQUEST['endTimeLogin'])) {
                    $endTimeLogin = strtotime($_REQUEST['endTimeLogin'])+86399;
                    $map['login_time'] = ['exp',"> {$endTimeLogin} or login_time is null"];
                }
            }
        }

        //最后登录版本
        $lastLoginVer = I('lastLoginVer');
        if(! empty($lastLoginVer))
        {
            $map['last_login_ver'] = ['like','%'.$lastLoginVer.'%'];
        }

        if(I('inviteFriend') == 1)//邀请好友列表的好友详情
        {
            if(I('friendList') == 1)
            {
                $where['f.id'] = I('user_id');
            }
            else
            {
                $where['user_id'] = I('user_id');
            }
            $where['i.id'] = ['GT',0];
            switch (I('invite_lv')) {//根据等级筛选
                case 1:$where['_string'] = 'i.lv = 1'; break;
                case 2:$where['_string'] = 'i.lv = 2';break;
                case 3:$where['_string'] = 'i.lv = 3';break;
            }
            $InviteRelation = M('InviteRelation i');
            $order = !empty ( $_REQUEST ['_order'] ) ? $_REQUEST ['_order'] : 'i.id';

            $InviteRelationCount = $InviteRelation->where($where)->count('id');//统计条数
            $this->assign('totalCount',$InviteRelationCount);//当前条件下数据的总条数
            $pageNum = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
            //列表
            $sort = $_REQUEST ['_sort'] == 'asc' ? 'asc' : 'desc';
            $list = $InviteRelation
                ->join('LEFT JOIN qc_front_user f on i.invited_id = f.id')
                ->where($where)
                ->where($map)
                ->limit($pageNum)
                ->group('invited_id')
                ->order($order.' '.$sort)
                ->page(!empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1)
                ->select();
            $export = (int)I('Export');
            if($export === 1)
            {
                $Invitelist = $InviteRelation
                    ->join('LEFT JOIN qc_front_user f on i.invited_id = f.id')
                    ->where($where)
                    ->where($map)
                    ->limit($pageNum)
                    ->order($order.' '.$sort)
                    ->select();
                    $this->frontUserExport('',$export,$Invitelist);
            }
            if((int)I('invite_lv') !== 0)
            {
                $lv = I('invite_lv');
                $user_id = I('user_id');
                $InviteRelationCoin = $InviteRelation
                                    ->field('sum(coin)')
                                    ->join('left join qc_invite_record_info iri on i.invited_id = iri.user_id ')
                                    ->where(['i.user_id'=>$user_id,'i.lv'=>$lv])
                                    ->group('type')
                                    ->select();
                //分配有效金币和无效金币
                foreach ($InviteRelationCoin as $k => $v)
                {
                    if($InviteRelationCoin['type'] == 1)
                    {
                        $existCoin = $v['sum(coin)'];//邀请所获得金币
                    }
                    if($InviteRelationCoin['type'] == 2)
                    {
                        $frostCoin = $v['sum(coin)'];
                    }
                }
            }
            if((int)I('invite_lv') === 0)//点击总人数执行
            {
                $user_id = I('user_id');
                $userIdCoin = M('InviteInfo')
                            ->where(['user_id'=>$user_id])
                            ->field('await_coin,valid_coin,invalid_coin')
                            ->find();
            }
            $this->assign('existCoin',$existCoin);//有用金币
            $this->assign('frostCoin',$frostCoin);//无用金币
            $this->assign ('await_coin',$userIdCoin['await_coin']);
            $this->assign ('valid_coin',$userIdCoin['valid_coin']);
            $this->assign ( 'invalid_coin', $userIdCoin['invalid_coin'] );
            $this->assign ( 'numPerPage', $pageNum ); //每页显示多少条
            $this->assign ( 'currentPage', !empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1);//当前页码
            $this->setJumpUrl();
        }
        else
        {
            //点击渠道查询中的昵称所传过来的user_id,进行筛选
            $user_id=I('get.user_id');
            if(!empty($user_id))
            {
                $map['id'] = $user_id;
                $this->assign('backUrl','/qqty_admin/FrontUser/channelCode');
            }

            if(isset($map['is_robot']) && $map['is_robot'] == 1){
                $sort = 'id';
            }else{
                $sort = 'reg_time';
            }
            $list = $this->_list(D('FrontUser'),$map, $sort);
        }
        foreach ($list as $k => $v) {
            $list[$k]['robot_conf'] = json_decode($v['robot_conf'],true);
        }
        $userArr = implode(',', array_map("array_shift", $list));
        //最后充值时间
        $lastCoinArr = M('AccountLog')->field('user_id,max(log_time) log_time')->where("user_id in (".$userArr.") and log_type = 8")->group('user_id')->order('id desc')->select();
        //查询待结算金币
        $incomeArr = M('gamble')
                    ->field('user_id,sum(income) as income')
                    ->where("result = 0 AND tradeCoin > 0 AND quiz_number > 0 AND is_back = 0 AND user_id in (".$userArr.")")
                    ->group('user_id')
                    ->select();
        //查询体验/优惠券数量
        $ticketArr = M('ticketLog')
                    ->field('user_id,count(id) as ticket_num')
                    ->where("is_use = 0 AND user_id in (".$userArr.")")
                    ->group('user_id')
                    ->select();
        foreach ($list as $k => $v) 
        {
            foreach ($incomeArr as $kk => $vv) {
                if($v['id'] == $vv['user_id']){
                    $list[$k]['wait_coin'] = $vv['income'];
                }
            }
            foreach ($ticketArr as $kkk => $vvv) {
                if($v['id'] == $vvv['user_id']){
                    $list[$k]['ticket_num'] = $vvv['ticket_num'];
                }
            }
            foreach ($lastCoinArr as $l => $ll) {
                if($v['id'] == $ll['user_id']){
                    $list[$k]['last_coin'] = $ll['log_time'];
                }
            }
        }
        if (I('Export') == 1)//导出操作
        {
            if(count($list) > 1000)
            {
                $this->error('导出数据量过大请在1000条以内，请根据条件筛选后再导出');
            }
            $this->excelExportUser($list);
        }
        $this->assign('list',$list);
        $this->display();
    }

	//用户异常登录查询
    public function abnormal()
    {
        //排序方式默认按照倒序排列
        $sort = $_REQUEST ['_sort'] == 'asc' ? 'asc' : 'desc';

        $currentPage = !empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1;
        $pageNum = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];

        $page = $pageNum * ($currentPage - 1);
        $pageStr = $page.','.$pageNum;

        $reg_ip = I('reg_ip');
        $reg_where = $reg_ip != '' ? " and reg_ip = '{$reg_ip}'" : '';

        $last_ip = I('last_ip');
        $last_where = $last_ip != '' ? " and last_ip = '{$last_ip}'" : '';

        $device_token = I('device_token');
        $device_where = $device_token != '' ? " and device_token = '{$device_token}'" : '';

        $abnormal = I('abnormal') ? : 1;
        $this->assign('abnormal',$abnormal);
        switch ($abnormal) {
            case '1': $sql = "select group_concat(status) as status,group_concat(nick_name) as nick_name,device_token,count(*) as count from qc_front_user where user_type = 1 and is_robot = 0 and device_token <> '' ".$device_where." group by device_token having count>1 order by count ".$sort.' limit '.$pageStr;break;
            case '2': $sql = "select group_concat(status) as status,group_concat(nick_name) as nick_name,last_ip,count(*) as count from qc_front_user where user_type = 1 and is_robot = 0 and last_ip <> '' ".$last_where." group by last_ip having count>1 order by count ".$sort.' limit '.$pageStr;break;
            case '3': $sql = "select group_concat(status) as status,group_concat(nick_name) as nick_name,reg_ip,count(*) as count from qc_front_user where user_type = 1 and is_robot = 0 and reg_ip <> '' ".$reg_where." group by reg_ip having count>1 order by count ".$sort.' limit '.$pageStr;break;
        }

        $list = M()->query($sql);

        $this->assign('totalCount',99999);
        $this->assign('hiddenCount',1);
        $this->setJumpUrl();
        $this->assign ( 'numPerPage', $pageNum );
        $this->assign ( 'page', $page );
        $this->assign('list',$list);
        $this->assign ( 'currentPage', $currentPage);
        $this->display();
    }

	//用户异常批量禁用
    public function saveAbnormal()
    {
        if(IS_POST){
            $device_token = I('device_token');
            $last_ip      = I('last_ip');
            $reg_ip       = I('reg_ip');
            $status_desc  = I('status_desc');
            if($status_desc == ''){
                $this->error('请输入禁用原因！');
            }
            $where['user_type'] = 1;
            $where['is_robot']  = 0;
            if($device_token != ''){
                $where['device_token'] = $device_token; 
            }
            if($last_ip != ''){
                $where['last_ip'] = $last_ip; 
            }
            if($reg_ip != ''){
                $where['reg_ip'] = $reg_ip; 
            }

            $rs = M('FrontUser')->where($where)->save(['status'=>0,'status_desc'=>$status_desc]);
            if($rs !== false){
                $this->success('禁用成功！');
            }else{
                $this->error('禁用失败！');
            }
        }
        $this->display();
    }

     /**
     * 导出Excel
     * @param list $list [列表数据]
     * @param string $filename [文件名，当为空时就以当前日期为文件名]
     */
    public function excelExportUser($list,$filename="")
    {
        $filename = empty($filename)?date('Ymd'):$filename;
        $strTable ='<table width="500" border="1">';
        $strTable .="<tr>";
        $strTable .= '<th style="text-align:center;font-size:13px;" width="*">编号</th>';
        $strTable .= '<th style="text-align:center;font-size:13px;" width="*">昵称（手机号）</th>';
        $strTable .= '<th style="text-align:center;font-size:13px;" width="*">积分</th>';
        if(getUserPower()['is_show_user'] == 1){
            $strTable .= '<th style="text-align:center;font-size:13px;" width="*">总金币</th>';
            $strTable .= '<th style="text-align:center;font-size:13px;" width="*">可提款</th>';
            $strTable .= '<th style="text-align:center;font-size:13px;" width="*">不可提</th>';
            $strTable .= '<th style="text-align:center;font-size:13px;" width="*">待结算</th>';
            $strTable .= '<th style="text-align:center;font-size:13px;" width="*">冻结金币</th>';
            $strTable .= '<th style="text-align:center;font-size:13px;" width="*">上次登录</th>';
            $strTable .= '<th style="text-align:center;font-size:13px;" width="*">登录ip</th>';
            $strTable .= '<th style="text-align:center;font-size:13px;" width="*">登录版本</th>';
            $strTable .= '<th style="text-align:center;font-size:13px;" width="*">登录次数</th>';
        }
        $strTable .= '<th style="text-align:center;font-size:13px;" width="*">体验/优惠券</th>';
        $strTable .= '<th style="text-align:center;font-size:13px;" width="*">注册时间</th>';
        $strTable .= '<th style="text-align:center;font-size:13px;" width="*">上次充值</th>';
        $strTable .= '<th style="text-align:center;font-size:13px;" width="*">注册ip</th>';
        $strTable .= '<th style="text-align:center;font-size:13px;" width="*">设备号</th>';
        foreach ($list as $key => $vo)
        {
            $strTable .= "<tr>";
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$vo['id'].'</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$vo['nick_name'].'（'.is_show_mobile($vo['username']).'）</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$vo['point'].'</th>';
            if(getUserPower()['is_show_user'] == 1){
                $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.($vo['unable_coin']+$vo['coin']).'</th>';
                $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$vo['coin'].'</th>';
                $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$vo['unable_coin'].'</th>';
                $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$vo['wait_coin'].'</th>';
                $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$vo['frozen_coin'].'</th>';
                $login_time = $vo['login_time'] != '' ? date('Y-m-d H:i',$vo['login_time']) : '';
                $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$login_time.'</th>';
                $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$vo['last_ip'].'</th>';
                $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$vo['last_login_ver'].'</th>';
                $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$vo['login_count'].'</th>';
            }
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$vo['ticket_num'].'</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" >'.date('Y-m-d H:i',$vo['reg_time']).' </th>';
            $last_coin = $vo['last_coin'] != '' ? date('Y-m-d H:i',$vo['last_coin']) : '';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$last_coin.'</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$vo['reg_ip'].'</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$vo['device_token'].'</th>';
            $strTable .= "</tr>";
        }
        $strTable .= "</table>";
        downloadExcel($strTable,$filename);
        exit();
    }

    //机器人规则设置
    public function setRobot()
    {
        $id = I('user_id');
        $vo = M('FrontUser')->where(['id'=>$id])->field('robot_conf')->find();
        if (IS_POST)
        {
            unset($_POST['user_id']);

            $rs = M('FrontUser')->where(['id'=>$id])->save(['robot_conf'=>json_encode($_POST)]);
            if(!is_bool($rs)) $rs = true;
            if($rs)
                $this->success("设置成功！");
            else
                $this->error('设置失败！');
        }
        $vo = json_decode($vo['robot_conf'],true);
        $this->assign('vo', $vo);
        $this->display();
    }

    //机器人发布金币规则设置
    public function setRobotCoin()
    {
        $sign = "robotCoin";
        $vo = getWebConfig($sign);
        if (IS_POST)
        {
            if($vo){
                //修改
                $rs = M('config')->where(['sign'=>$sign])->save(['config'=>json_encode($_POST)]);
            }else{
                //新增
                $rs = M('config')->add(['sign'=>$sign,'config'=>json_encode($_POST)]);
            }
            if($rs)
                $this->success("设置成功！");
            else
                $this->error('设置失败！');
        }
        $this->assign('vo', $vo);
        $this->display();
    }

    public function edit() {
        $id = I('id');
        $vo = M('FrontUser')->where(['id'=>$id])->find();
        if (!$vo){
            $this->error('参数错误');
        }
        $vo['face'] = frontUserFace($vo['head']);
        //判断vip
        $vo['is_vip'] = checkVip($vo['vip_time']);
        $this->assign('vo', $vo);
        $this->display("add");

    }

    //用户部分信息编辑
    public function editUser()
    {
        $id = I('id');
        $vo = M('FrontUser')->where(['id'=>$id])->find();
        if (!$vo){
            $this->error('参数错误');
        }
        $this->assign('vo', $vo);
        $this->display();
    }

    //用户部分信息编辑
    public function saveUser()
    {
        $id = I('id');
        $model = D('FrontUser');
        if (false === $model->create()) {
            $this->error($model->getError());
        }
        //屏蔽帖子评论
        if(!in_array($model->community_status, [0,2]))
        {
            $model->community_status = time()+86400;
        }
        //为修改
        $rs = $model->where(['id'=>$id])->save();
        if(I('is_gag') == 1){
            //禁用所有资讯评论
            M('comment')->where(['user_id'=>$id])->save(['status'=>0]);
        }
        if(I('community_status') == 2){
            //禁用所有帖子和评论
            M('communityPosts')->where(['user_id'=>$id])->save(['status'=>0]);
            M('communityComment')->where(['user_id'=>$id])->save(['status'=>0]);
        }
        //是否有上传
        if (!empty($_FILES['fileInput']['tmp_name'])) {
            //先删除原来头像
            $fileArr = array("/user/{$id}/face");
            D('Uploads')->deleteFile($fileArr);
            //上传图片
            $fileInput = $_FILES['fileInput'];
            $return = D('Uploads')->uploadImg("fileInput", "user,{$id}", '200' ,'face',"[[200,200,200]]");
            if($return['status'] == 1){
                M("frontUser")->where(['id'=>$id])->save(['head'=>$return['url']]);
            }
        }
        if ($rs !== false) {
            //成功提示
            $this->success('保存成功!',cookie('_currentUrl_'));
        } else {
            //错误提示
            $this->error('保存失败!');
        }
    }

    //增加修改用户信息
    public function save(){
        $id = I('id');
        $model = D('FrontUser');
        if (false === $model->create()) {
            $this->error($model->getError());
        }
        $username = I('username');
        if (! $username && ! I('qq_unionid') && ! I('weixin_unionid') && ! I('sina_unionid') && ! I('mm_unionid')) {
            $this->error('注意：手机号、QQ绑定、微信绑定、微博绑定、移动绑定至少填一个');
        }
        //屏蔽帖子评论
        if(!in_array($model->community_status, [0,2]))
        {
            $model->community_status = time()+86400;
        }

        $is_vip = I('is_vip');
        if($is_vip != ''){
            if($is_vip == 1){
                //手动开通vip
                $vip_time = I('vip_time');
                if(empty($vip_time)){
                    $this->error('请选择会员到期时间！');
                }
                if(strtotime($vip_time) < strtotime(date(Ymd))){
                    $this->error('会员到期时间有误！');
                }
                $model->open_viptime = strtotime(date(Ymd));
                $model->vip_time = strtotime($vip_time);
            }else{
                //取消vip
                $model->vip_time = 0;
            }
        }

        if (empty($id)) {
            if (! empty($username)) {
                if (M('FrontUser')->where(['username'=>$username])->find()) $this->error('手机号已经被注册过！');
            }
            //为新增
            $rs = $model->add();
            if($rs){
                //上传图片
                if (!empty($_FILES['fileInput']['tmp_name'])) {
                    $fileInput = $_FILES['fileInput'];
                    $return = D('Uploads')->uploadImg("fileInput", "user,{$rs}", '200' ,'face',"[[200,200,200]]");
                    if($return['status'] == 1){
                        M("frontUser")->where(['id'=>$rs])->save(['head'=>$return['url']]);
                    }
                }
            }
        }else{
            if (! empty($username)) {
                if (M('FrontUser')->where(['username'=>$username,'id'=>['neq',$id]])->find()) $this->error('手机号已经被注册过！');
            }
            if(empty($_POST['password'])) {
                unset($model->password);
            }
            //为修改
            $rs = $model->where(['id'=>$id])->save();
            if(!is_bool($rs)){
                $rs = true;
            }
            if(I('is_gag') == 1){
                //禁用所有资讯评论
                M('comment')->where(['user_id'=>$id])->save(['status'=>0]);
            }
            if(I('community_status') == 2){
                //禁用所有帖子和评论
                M('communityPosts')->where(['user_id'=>$id])->save(['status'=>0]);
                M('communityComment')->where(['user_id'=>$id])->save(['status'=>0]);
            }
            //是否有上传
            if (!empty($_FILES['fileInput']['tmp_name'])) {
                //先删除原来头像
                $fileArr = array("/user/{$id}/face");
                D('Uploads')->deleteFile($fileArr);
                //上传图片
                $fileInput = $_FILES['fileInput'];
                $return = D('Uploads')->uploadImg("fileInput", "user,{$id}", '200' ,'face',"[[200,200,200]]");
                if($return['status'] == 1){
                    M("frontUser")->where(['id'=>$id])->save(['head'=>$return['url']]);
                }
            }
        }
        if ($rs) {
            //成功提示
            $this->success('保存成功!',cookie('_currentUrl_'));
        } else {
            //错误提示
            $this->error('保存失败!');
        }
    }

    /*
     * 邀请好友列表
     */
    public function inviteFriend()
    {
        //生成查询条件
        $map=$this->_search('FrontUser');
        //时间过滤
        if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
            if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                $startTime = strtotime($_REQUEST ['startTime']);
                $endTime   = strtotime($_REQUEST ['endTime'])+86400;
                $map['reg_time'] = array('BETWEEN',array($startTime,$endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']);
                $map['reg_time'] = array('EGT',$strtotime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime'])+86400;
                $map['reg_time'] = array('ELT',$endTime);
            }
        }
        //查询单个客户邀请总人数
        switch (I('totalNum')) {
            case '1':
                $map['total_num']=[['gt',10],['lt',50]];
                break;
            case '2':
                $map['total_num']=[['gt',51],['lt',100]];
                break;
            case '3':
                $map['total_num']=[['gt',101],['lt',200]];
                break;
            case '4':
                $map['total_num']=[['gt',201],['lt',500]];
                break;
            case '5':
                $map['total_num']=['gt',501];
                break;
        }
        $pageNum = I('pageNum');
        //列表
        $InviteView = D('InviteView');
        $list = $this->_list($InviteView,$map,'total_coin,total_num,first_num,second_num,third_num');
        foreach ($list as $key => $value) {
            $totalCount[] = $value['total_coin'];//总金币
            $valid_coin[] = $value['valid_coin'];//可提金币
            $invalid_coin[] = $value['invalid_coin'];//不可提金币
            //$await_coin[] = $value['await_coin'];//待考核金币
         }
        $sumTotalCount = array_sum($totalCount);
        $sumValid_coin = array_sum($valid_coin);
        $sumInvalid_coin = array_sum($invalid_coin);
        $this->assign('sumTotalCount',$sumTotalCount);
        $this->assign('sumValid_coin',$sumValid_coin);
        $this->assign('sumInvalid_coin',$sumInvalid_coin);
        $this->assign('list',$list);
        $this->assign('desc_pag',empty($pageNum) ? 0 : $pageNum-1);
        $this->display();
    }
    /*
     *机器人好友记录编辑
     */
    public function invite_log_edit()
    {
        if (IS_POST)
        {
            $id = I('id');
            $model = D('InviteInfo');
            if(false === $model->create())
                $this->error($model->getError());
            $model->total_num = I('first_num')+I('second_num')+I('third_num');
            $model->total_coin = I('first_num')*5+I('second_num')+I('third_num');
            if (empty($id))
            {
                $userData = M('FrontUser')->where(['id'=>I('userId')])->field('id,is_robot')->find();
                if($userData['is_robot'] == 1)
                {
                    $user_id = M('InviteInfo')->where(['user_id' => $userData['id']])->getField('user_id');
                    if ($user_id)
                    {

                        $this->error('该机器人已有记录！');
                    }
                    else
                    {
                        $model->create_time = time();
                        $model->user_id = $userData['id'];
                        $re = $model->add();
                        if($re !== false)
                        {
                            $this->success('操作成功',cookie('_currentUrl_'));
                        }
                        else
                        {
                            $this->error('操作失败',cookie('_currentUrl_'));
                        }
                    }
                }
                else
                {
                    $this->error('请输入机器人ID号！');
                }
            }
            else
            {
                $model->update_time = time();
                $re = $model->where(['id'=>$id])->save();
                if($re !== false)
                    $this->success('操作成功',cookie('_currentUrl_'));
                else
                    $this->error('操作失败',cookie('_currentUrl_'));
            }
        }
        else
        {
            $id = I('id');
            if (! empty($id))
            {
                $vo = D('InviteView')->where(['id'=>$id])->find();

                if(!$vo) $this->error('参数错误');
            }
            $this->assign('vo',$vo);
            $this->display();
        }
    }
    /**
     * 渠道查询
     *
     */
    public function channelCode()
    {
        //过滤
        $map=$this->_search('FrontUser');
        //时间查询
        if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
            if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                $startTime = strtotime($_REQUEST ['startTime']);
                $endTime   = strtotime($_REQUEST ['endTime']);
                $map['reg_time'] = array('BETWEEN',array($startTime,$endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']);
                $map['reg_time'] = array('EGT',$strtotime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime']);
                $map['reg_time'] = array('ELT',$endTime);
            }
        }
        //时间查询条件
        $startTime = empty($_REQUEST ['startTime'])?" ":"AND reg_time > ".strtotime($_REQUEST ['startTime'])." ";
        $endTime = empty($_REQUEST ['endTime'])?" ":"AND reg_time < ".(strtotime($_REQUEST ['endTime']))." ";

        //筛选掉机器人
        $map['is_robot'] = 0;

        //用户名查询
        $username = I('username');
        $username = empty($username)?" ":" AND f.username LIKE '".$username."%' ";

        //昵称查询
        $nick_name = I('nick_name');
        $nick_name=empty($nick_name)?" ":" AND f.nick_name LIKE '".$nick_name."%' ";

        //渠道id查询
        $channel_code = I('channel_code');
        if ($channel_code == 'web') {
            $map['channel_code'] = ['IN',['','web']];
            $map['platform'] = 1;
            $channel_code = " AND (f.channel_code = 'web' or f.channel_code = '') AND f.platform = 1 ";
        } else {
            $channel_code = empty($channel_code) ? "" : " AND f.channel_code = '" . $channel_code . "' ";
        }

        //金币流动筛选
        $flow = I('flow');
		
        if ($flow == 1)//充值大于0
        {
            $flow = '  having rechargeSum > 0';
            $map['_string'] = '  (SELECT SUM(change_num) FROM qc_account_log WHERE (f.id = user_id) AND (log_type = 1 OR log_type =8)) > 0';
        }
        elseif ($flow == 2)//消费大于0
        {
            $flow = '  having consumptionSum > 0';
            $map['_string'] = ' (SELECT SUM(change_num) FROM qc_account_log WHERE (f.id = user_id) AND (log_type = 3)) > 0';
        }
        else
        {
            $flow = ' ';
        }

        //渠道名查询
        $channel_name = I('channel_name');
        if(!empty($channel_name))
        {
            switch ($channel_name) {
                case 'ios':
                    $platform_and = "AND f.platform = 2";
                    $map['platform'] = 2;
                    break;
                case 'andriod':
                    $platform_and = "AND f.platform = 3";
                    $map['platform'] = 3;
                    break;
            }  
        }
        else
        {
            //渠道名查询(旧版本的平台搜索)
            $platform_and = '';
        }
        //根据ID、充值、消费排序
        $order = empty($_REQUEST['_order'])?'f.id':$_REQUEST['_order'];
        $desc = empty($_REQUEST['_sort'])?'DESC':$_REQUEST['_sort'];

        //获取渠道名
        $channel_code_conf = C('channel_code');
        $user = M('user')->field('id,nickname,channel_code')->find($_SESSION['authId']);
        if(!empty($user['channel_code'])){
            $channelArr = explode(',', $user['channel_code']);
            //特定渠道号筛选权限
            foreach ($channel_code_conf as $k => $v) {
                if(!in_array($k, $channelArr)){
                    unset($channel_code_conf[$k]);
                }
            }
            if(empty($channel_code)){
                //销售客服只有特定渠道号筛选权限  
                foreach ($channelArr as $k => $v) {
                    $channelStr .= '\''.$v.'\',';
                }
                $channel_code = "AND f.channel_code in(".rtrim($channelStr,',').")";
            }
        }
        $this->assign('channel_code_conf',$channel_code_conf);

        //统计人数
        $userArr = M('FrontUser')
                  ->query("SELECT id,username,(SELECT SUM(change_num) FROM qc_account_log WHERE (f.id = user_id) AND (log_type in(1,7,8))) AS rechargeSum,
                    (SELECT SUM(change_num) FROM qc_account_log WHERE (f.id = user_id) AND (log_type in(3,14,15,17,19,25,26))) AS consumptionSum
                    FROM qc_front_user AS f
                    WHERE is_robot=0
                        ".$username."
                        ".$nick_name."
                        ".$channel_code."
                        ".$platform_and."
                        ".$startTime."
                        ".$endTime."
                        ".$flow);
        foreach ($userArr as $k => $v)
        {
            //获取用户id，用于根据筛选条件统计总产值和总消费
            $userCount[]=$v['id'];
        }
        $userNum = count($userCount);
        if($userNum > 0)//取得满足条件的记录数
        {
            //获取每页显示的条数
            $pageNum = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
            //获取当前的页码
            $currentPage =! empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1;
            $list = M('FrontUser')
                  ->query("SELECT id,username,nick_name,point,(coin+unable_coin+frozen_coin) AS 'balance',channel_code,reg_time,platform,status,
                    (SELECT SUM(change_num) FROM qc_account_log WHERE (f.id = user_id) AND (log_type in(1,7,8))) AS rechargeSum,
                    (SELECT SUM(change_num) FROM qc_account_log WHERE (f.id = user_id) AND (log_type in(3,14,15,17,19,25,26))) AS consumptionSum
                    FROM qc_front_user AS f
                    WHERE is_robot=0
                        ".$username."
                        ".$nick_name."
                        ".$channel_code."
                        ".$platform_and."
                        ".$startTime."
                        ".$endTime."
                        ".$flow."
                    ORDER BY ".$order." ".$desc.
                    " limit ".$pageNum*($currentPage-1).",".$pageNum//分页操作
            );
        }
        
        //初始化
        $accountLogModel = M('AccountLog');
        //所以人的总充值
        $rechargeSum = $accountLogModel->where(['log_type'=>['in',[1,7,8]],'user_id'=>['in',$userCount]])->sum('change_num');
        //充值总人数
        $rechargeUserSum = $accountLogModel->where(['log_type'=>['in',[1,7,8]],'user_id'=>['in',$userCount]])->count('DISTINCT user_id');
        //所有人的总消费
        $consumptionSum = $accountLogModel->where(['log_type'=>['in',[3,14,15,17,19,25,26]],'user_id'=>['in',$userCount]])->sum('change_num');
        //消费总人数
        $consumptionUserSum  = $accountLogModel->where(['log_type'=>['in',[3,14,15,17,19,25,26]],'user_id'=>['in',$userCount]])->count('DISTINCT user_id');


        $this->assign ( 'totalCount', $userNum );//当前条件下数据的总条数
        $pageNum =empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
        $this->assign ( 'numPerPage', $pageNum ); //每页显示多少条
        $this->assign ( 'currentPage', !empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1);//当前页码
        $this->setJumpUrl();
        $this->assign('userNum',$userNum);

        $this->assign('rechargeSum',$rechargeSum);
        $this->assign('rechargeUserSum',$rechargeUserSum);
        $this->assign('consumptionSum',$consumptionSum);
        $this->assign('consumptionUserSum',$consumptionUserSum);
        $this->assign('list',$list);
        $this->display();
    }

    //会员竞猜信息
    public function userDetails()
    {
        $map = $this->_search('FrontUser');
        //用户类型筛选
        $user_type = I('usertype');
        switch ($user_type)
        {
            case '1':
                $map['is_robot']  = ['neq',1];
                $map['is_expert'] = ['neq',1];
                break;
            case '2': $map['is_expert'] = ['eq',1]; break;
            case '3': $map['is_robot']  = ['eq',1]; break;
            case '4': $map['user_type'] = ['eq',2]; break;
        }
        $list = $this->_list(CM('FrontUser'),$map,'id');
        $gameType = I('gameType') ? : 1;
        $dateType = I('dateType') ? : 4;
        foreach ($list as $k => $v)
        {
            //交易明细
            $rechargeSum    = 0;
            $consumptionSum = 0;
            $AccountLog = M('AccountLog')->field('log_type,log_status,change_num')->where(['user_id'=>$v['id'],'log_type'=>['in',[3,8,14]]])->select();
            foreach ($AccountLog as $kk => $vv)
            {
                switch ($vv['log_type']) {
                    case '8':
                        $rechargeSum += $vv['change_num'];
                        break;
                    case '3':
                    case '14':
                        $consumptionSum += $vv['change_num'];
                        break;
                }
            }
            $list[$k]['rechargeSum']    = $rechargeSum;
            $list[$k]['consumptionSum'] = $consumptionSum;

            //获取竞猜数据
            if($dateType != 4){
                $CountWinrate = D('GambleHall')->CountWinrate($v['id'],$gameType,$dateType,true);
            }else{
                $CountWinrate = A('RedList')->YestWinrate('',$gameType,$v['id']);
            }
            $list[$k]['CountWinrate'] = $CountWinrate;
            //竞彩数据
            $list[$k]['colorQuiz'] = D('GambleHall')->CountWinrate($v['id'],$gameType,$dateType,true,false,0,2);
            //获取榜排名
            if($dateType == 4)
            {
                $ranking = M('redList')->field('ranking')->where(['game_type'=>$gameType,'list_date'=>date('Ymd',strtotime("-1 day")),'user_id'=>$v['id']])->find();
            }
            else
            {
                list($begin,$end)  = getRankDate($dateType);
                $rankMap['gameType']   = $gameType;
                $rankMap['dateType']   = $dateType;
                $rankMap['begin_date'] = array("between",array($begin,$end));
                $rankMap['end_date']   = array("between",array($begin,$end));
                $rankMap['user_id']    = $v['id'];
                $ranking = M('rankingList')->field('ranking')->where($rankMap)->find();
                $colorRank = M('rankBetting')->field('ranking')->where(['gameType'=>$gameType,'dateType'=>$dateType,'listDate'=>$end,'user_id'=>$v['id']])->find();
            }
            $list[$k]['ranking'] = $ranking['ranking'];
            $list[$k]['colorRank'] = $colorRank['ranking'];
        }
        $this->assign('list',$list);
        $this->display();
    }

    //竞猜规则设置
    public function setTime(){
        $gameType = I('gameType');
        $sign = $gameType == 1 ? 'fbRobot' : 'bkRobot';
        $data = M('config')->where(['sign'=>$sign])->find();
        $this->assign('config',json_decode($data['config'],true));
        $this->display();
    }
    //保存规则
    public function saveConfig(){
        $gameType = I('gameType');
        $sign = $gameType == 1 ? 'fbRobot' : 'bkRobot';
        $data = M('config')->where(['sign'=>$sign])->find();
        $config['sign'] = $sign;
        $config['config'] = json_encode(I('config'));
        if($data){
            //修改
            $rs = M('config')->where(['sign'=>$sign])->save($config);
            if(!is_bool($rs))
                $rs = true;
        }else{
            //新增
            $rs = M('config')->add($config);
        }
        if($rs){
            $this->success("设置成功！");
        }else{
            $this->error("设置失败！");
        }
    }

    //异步删除图片
    public function delPic(){
        $id = I('id');
        if(empty($id)){
            $this->error('参数错误!');
        }
        $fileArr = array("/user/{$id}/face");
        //执行删除
        $return = D('Uploads')->deleteFile($fileArr);
        if($return['status'] == 1){
            M("FrontUser")->where(['id'=>$id])->save(['head'=>NULL]);
            $this->success('删除成功！');
        }else{
            $this->error('删除失败！');
        }
    }

    //设置专家
    public function saveIsExpert(){
        $id = $_REQUEST['id'];
        $is_expert = $_REQUEST['is_expert'];
        $rs = M('FrontUser')->where(['id'=>$id])->save(['is_expert'=>$is_expert]);
        if($rs){
            $this->success("设置成功！");
        }else{
            $this->error("设置成功！");
        }
    }

    //设置机器人
    public function saveIsRobot(){
        $id = $_REQUEST['id'];
        $is_robot = $_REQUEST['is_robot'];
        $rs = M('FrontUser')->where(['id'=>$id])->save(['is_robot'=>$is_robot]);
        if($rs){
            $this->success("设置成功！");
        }else{
            $this->error("设置成功！");
        }
    }
    //设置专家推荐
    public function saveIsRecommend(){
        $id = $_REQUEST['id'];
        $is_recommend = $_REQUEST['is_recommend'];
        $rs = M('FrontUser')->where(['id'=>$id])->save(['is_recommend'=>$is_recommend]);
        if($rs){
            $this->success("设置成功！");
        }else{
            $this->error("设置成功！");
        }
    }

    //重置密码
    public function resetPwd() {
        $id  =  $_POST['id'];
        $password = $_POST['password'];
        $User = M('front_user');
        if(!empty($password)){
            $User->password =   md5($password);
        }
        if(!empty($_POST['bank_extract_pwd'])){
            $User->bank_extract_pwd =   md5($_POST['bank_extract_pwd']);
        }
        $User->id           =   $id;
        $result =   $User->save();
        if(false !== $result) {
            $this->success("修改成功！");
        }else {
            $this->error('修改失败！');
        }
    }

    //运行机器人竞猜
    public function ReleaseQuiz(){
        $gameType = I('get.gameType') ? : 1;
        $betting  = I('get.betting') ? : 1;
        //$blockTime = getBlockTime($gameType);
        //获取比赛
        // switch ($gameType) {
        //     case '1':
        //         if($betting == 1){
        //         $sql = "SELECT DISTINCT
        //                 g.game_id,u.union_id,u.union_name,u.is_sub, g.gtime,g.home_team_name , g.away_team_name,
        //                 g.fsw_exp_home, g.fsw_exp, g.fsw_exp_away, g.fsw_ball_home, g.fsw_ball, g.fsw_ball_away,gn.let_home_num,gn.let_away_num,gn.size_big_num,gn.size_small_num
        //             FROM __PREFIX__game_fbinfo g
        //             LEFT JOIN __PREFIX__union u ON g.union_id = u.union_id
        //             LEFT JOIN __PREFIX__gamble_number gn ON gn.game_id = g.game_id
        //             WHERE
        //                 g.status = 1
        //             AND gtime between {$blockTime['beginTime']} AND {$blockTime['endTime']}
        //             AND (u.is_sub < 3 or g.is_show =1)
        //             AND g.is_gamble     = 1
        //             AND g.fsw_exp       != ''
        //             AND g.fsw_ball      != ''
        //             AND g.fsw_exp_home  != ''
        //             AND g.fsw_exp_away  != ''
        //             AND g.fsw_ball_home != ''
        //             AND g.fsw_ball_away != ''
        //             AND g.game_state    =  0
        //             AND g.gtime > ".(time() + 300);
        //         }
        //         elseif ($betting == 2) 
        //         {
        //             //如果没有过今天的10:32，code显示昨天的
        //             $weekArray = array("周日", "周一", "周二", "周三", "周四", "周五", "周六"); //日期数组
        //             if(NOW_TIME > strtotime('10:32')){
        //                 $today = $weekArray[date("w")];
        //             }else{
        //                 $today = $weekArray[date("w", strtotime('-1 day'))];
        //             }
        //             $sql = "SELECT DISTINCT
        //                     g.game_id, g.union_id, u.union_name,u.is_sub ,g.gtime,g.home_team_name,g.away_team_name,
        //                     bet.home_odds,bet.draw_odds,bet.away_odds,bet.let_exp,bet.home_letodds,bet.draw_letodds,bet.away_letodds,
        //                     gn.let_win_num,gn.let_draw_num,gn.let_lose_num,gn.not_win_num,gn.not_draw_num,gn.not_lose_num
        //                 FROM __PREFIX__game_fbinfo g
        //                 LEFT JOIN __PREFIX__union u ON g.union_id = u.union_id
        //                 LEFT JOIN __PREFIX__fb_betodds bet ON bet.game_id = g.game_id
        //                 LEFT JOIN __PREFIX__gamble_number gn ON gn.game_id = g.game_id
        //                 WHERE
        //                     g.status = 1
        //                 AND gtime between {$blockTime['beginTime']} AND ".strtotime('+1 day', $blockTime['endTime'])."
        //                 AND bet.bet_code like '{$today}%'
        //                 AND g.is_color = 1
        //                 AND g.is_betting = 1
        //                 AND g.game_state = 0
        //                 AND g.gtime > ".(time() + 300);
        //         }
        //         $sign = 'fbRobot';
        //         break;
        //     case '2':
        //         $sql = "SELECT DISTINCT
        //             g.game_id,u.union_id,u.union_name,u.is_sub,g.home_team_name ,g.gtime ,g.away_team_name,
        //             g.fsw_exp_home, g.fsw_exp, g.fsw_exp_away,g.fsw_total_home,g.fsw_total,g.fsw_total_away,
        //             g.psw_exp_home,g.psw_exp,g.psw_exp_away,g.psw_total_home,g.psw_total,g.psw_total_away,gn.all_home_num,gn.all_away_num ,gn.all_big_num,gn.all_small_num,gn.half_home_num,gn.half_away_num,gn.half_big_num,gn.half_small_num
        //         FROM __PREFIX__game_bkinfo g
        //         LEFT JOIN __PREFIX__bk_union u ON g.union_id = u.union_id
        //         LEFT JOIN __PREFIX__gamblebk_number gn ON gn.game_id = g.game_id
        //         WHERE
        //             g.status = 1
        //         AND gtime between {$blockTime['beginTime']} AND {$blockTime['endTime']}
        //         AND (u.is_sub <= 3 or g.is_show = 1)
        //         AND g.is_gamble = 1
        //         AND (fsw_exp!='' or fsw_total!='' or psw_exp!='' or psw_total!='')
        //         AND g.game_state  =  0
        //         AND g.gtime > ".(time() + 300);
        //         $sign = 'bkRobot';
        //         break;
        // }

        // $game  = M()->query($sql);
        if($gameType == 1){
            $game = D('GambleHall')->getGameFbinfo($betting);
            $sign = 'fbRobot';
        }else{
            $game = D('GambleHall')->basketballList()[0];
            $sign = 'bkRobot';
        }
        foreach ($game as $k => $v) {
            if($v['game_state'] != 0){
                unset($game[$k]);
            }
        }
        if(!$game){
            $this->error("没有可竞猜的比赛");
        }
        
        $RobotIdArr = D('Robot')->getRobot($gameType,$betting);

        // if($isDoGamble == 1)
        // {
        //     //对赛事竞猜数量少于10的优先竞猜
        //     $num = 0;
        //     foreach ($game as $k => $v)
        //     {
        //         if($gameType == 1) //足球
        //         {
        //             if($betting == 1) //亚盘
        //             {
        //                 if($v['let_home_num'] + $v['let_away_num'] < 10){
        //                     //让球添加竞猜
        //                     $num_let = D('Robot')->dogamble($v,1,$RobotIdArr,$gameType);
        //                     $num += $num_let;
        //                 }
        //                 if($v['size_big_num'] + $v['size_small_num'] < 10){
        //                     //大小添加竞猜
        //                     $num_size = D('Robot')->dogamble($v,-1,$RobotIdArr,$gameType);
        //                     $num += $num_size;
        //                 }
        //             }
        //             elseif ($betting == 2) //竞彩
        //             {
        //                 if($v['not_win_num'] + $v['not_draw_num'] + $v['not_lose_num'] < 10){
        //                     //竞彩不让球添加竞猜
        //                     $num_let = D('Robot')->dogamble($v,2,$RobotIdArr,$gameType,$betting);
        //                     $num += $num_let;
        //                 }
        //                 if($v['let_win_num'] + $v['let_draw_num'] + $v['let_lose_num'] < 10){
        //                     //竞彩让球添加竞猜
        //                     $num_size = D('Robot')->dogamble($v,-2,$RobotIdArr,$gameType,$betting);
        //                     $num += $num_size;
        //                 }
        //             }
        //         }
        //         if($gameType == 2) //篮球
        //         {
        //             if($v['all_home_num'] + $v['all_away_num'] < 10){
        //                 //全场让球添加竞猜
        //                 $all_num_let = D('Robot')->dogamble($v,1,$RobotIdArr,$gameType);
        //                 $num += $all_num_let;
        //             }
        //             if($v['all_big_num'] + $v['all_small_num'] < 10){
        //                 //全场大小添加竞猜
        //                 $all_num_size = D('Robot')->dogamble($v,-1,$RobotIdArr,$gameType);
        //                 $num += $all_num_size;
        //             }
        //         }
        //     }
        //     $this->success("对赛事竞猜数量少于10的竞猜发布了{$num}条竞猜");
        //     exit;
        // }

        $gameConf = getWebConfig($sign);
        $number = $gameConf[date('G')];
        $p = 0;
        // dump($number);
        // dump($game);
        // die;
        if($number > 0)
        {
            $randGame = array();
            foreach ($RobotIdArr as $k => $v) {
                //获取随机竞猜记录
                $randGamble = D('Robot')->getRandGamble($v,$gameType,$game,$betting);
                if(!empty($randGamble)){
                    $randGame[] = $randGamble;
                    $p++;
                    //大于等于配置数量退出循环
                    if($p >= $number){
                        break;
                    }
                }else{
                    continue;
                }
            }

            if(!empty($randGame))  //添加竞猜记录与数量
            {
                $GambleModel = $gameType == 1 ? M('Gamble') : M('Gamblebk');
                $rs = $GambleModel->addAll($randGame);
                //添加竞猜记录数量
                $Model = $gameType == 1 ? M('gambleNumber') : M('gamblebkNumber');
                foreach ($randGame as $k => $v) {
                    $is_has = $Model->master(true)->where(['game_id'=>$v['game_id']])->getField('id');
                    if ($gameType == 1) //足球
                    {
                        switch ($v['play_type'])
                        {
                            case '1':
                                //亚盘让球
                                $gambleStr = $param['chose_side'] == 1 ? 'let_home_num' : 'let_away_num';
                                break;
                            case '-1':
                                //亚盘让球
                                $gambleStr = $param['chose_side'] == 1 ? 'size_big_num' : 'size_small_num';
                                break;
                            case '2':
                                //竞彩不让球
                                switch ($v['chose_side']) {
                                    case  '1': $gambleStr = 'not_win_num';  break;
                                    case  '0': $gambleStr = 'not_draw_num'; break;
                                    case '-1': $gambleStr = 'not_lose_num'; break;
                                }
                                break;
                            case '-2':
                                //竞彩让球
                                switch ($v['chose_side']) {
                                    case  '1': $gambleStr = 'let_win_num';  break;
                                    case  '0': $gambleStr = 'let_draw_num'; break;
                                    case '-1': $gambleStr = 'let_lose_num'; break;
                                }
                                break;
                        }
                    }
                    elseif($gameType == 2) //篮球
                    {
                        switch ($v['play_type'])
                        {
                            case '1':
                                //全场让球
                                $gambleStr = $v['chose_side'] == 1 ? 'all_home_num' : 'all_away_num';
                                break;
                            case '-1':
                                //全场大小
                                $gambleStr = $v['chose_side'] == 1 ? 'all_big_num' : 'all_small_num';
                                break;
                            // case '2':
                            //     //半场让球
                            //     $gambleStr = $v['chose_side'] == 1 ? 'half_home_num' : 'half_away_num';
                            //     break;
                            // case '-2':
                            //     //半场大小
                            //     $gambleStr = $v['chose_side'] == 1 ? 'half_big_num' : 'half_small_num';
                            //     break;
                        }
                    }
                    if($is_has) //更新数量
                    {
                        $Model->where(['game_id'=>$v['game_id']])->setInc($gambleStr);
                    }
                    else //添加新记录
                    {
                        $Model->add(['game_id'=>$v['game_id'],$gambleStr=>1]);
                    }
                }
            }
        }
        $this->success("发布了{$p}条竞猜");
        die;
    }

    //自动增加粉丝程序 目前只针对足球
    public function addFollow()
    {
        //获取足球红人榜前50名
        $where['list_date']  = date('Ymd',strtotime("-1 day"));
        $where['game_type']   = 1;
        $redList = M('redList')->where($where)->field('user_id,ranking')->order('ranking asc')->limit(50)->select();

        //获取足球周排行前50名
        list($begin,$end) = getRankBlockDate(1,1);
        $where['gameType']   = 1;
        $where['dateType']   = 1;
        $where['begin_date'] = array("between",array($begin,$end));
        $where['end_date']   = array("between",array($begin,$end));
        $rankingList = D('rankingList')->where($where)->field('user_id,ranking')->order('ranking asc')->limit(50)->select();

        //获取所有机器人id
        $RobotIdArr = M('FrontUser')->where(['status'=>1,'is_robot'=>1])->field("id as user_id")->select();

        //排行榜增加粉丝
        foreach ($rankingList as $k => $v) {
            if ($v['ranking'] > 0 && $v['ranking'] <= 10) {
                //1—10名粉丝数随机从1-4中进行增加
                $RandFollow[] = $this->doAddFollow($v['user_id'],1,4,$RobotIdArr);
            }
            if ($v['ranking'] > 10 && $v['ranking'] <= 30) {
                //11—30名粉丝数随机从1-3中进行增加
                $RandFollow[] = $this->doAddFollow($v['user_id'],1,3,$RobotIdArr);
            }
            if ($v['ranking'] > 30 && $v['ranking'] <= 50) {
                //30—50名粉丝数随机从1-2中进行增加
                $RandFollow[] = $this->doAddFollow($v['user_id'],1,2,$RobotIdArr);
            }
        }

        //处理红人榜去掉出现在周榜的
        $this->checkRank($rankingList,$redList);

        //红人榜增加粉丝
        foreach ($redList as $k => $v) {
            if ($v['ranking'] > 0 && $v['ranking'] <= 10) {
                //1—10名粉丝数随机从1-6中进行增加
                $RandFollow[] = $this->doAddFollow($v['user_id'],1,6,$RobotIdArr);
            }
            if ($v['ranking'] > 10 && $v['ranking'] <= 30) {
                //11—30名粉丝数随机从1-5中进行增加
                $RandFollow[] = $this->doAddFollow($v['user_id'],1,5,$RobotIdArr);
            }
            if ($v['ranking'] > 30 && $v['ranking'] <= 50) {
                //30—50名粉丝数随机从1-4中进行增加
                $RandFollow[] = $this->doAddFollow($v['user_id'],1,4,$RobotIdArr);
            }
        }
        //合并成一个数组
        foreach ($RandFollow as $k => $v) {
            foreach ($v as $kk => $vv) {
                $Follow[] = $vv;
            }
        }
        M('FollowUser')->addAll($Follow);
        $p = count($Follow);
        $this->success("自动增加了{$p}个粉丝");
    }

    //获取随机关注机器人数据
    public function doAddFollow($user_id,$start,$end,$RobotIdArr)
    {
        //获取已有关注机器人
        $hasFollow = M('FollowUser')->where(['follow_id'=>$user_id])->field('user_id')->select();

        //去掉已关注的粉丝
        $this->unsetHasFollow($RobotIdArr,$hasFollow);

        //从可用机器人中随机获取$start到$end个
        $RandNumber = rand($start,$end);
        $RandFollow = array_slice($RobotIdArr,0, $RandNumber);

        foreach ($RandFollow as $k => $v)
        {
            if($v['user_id'] != $user_id){
                $RandFollow[$k]['follow_id']   = $user_id;
                $RandFollow[$k]['follow_time'] = rand(time()-21600,time()+36000);
            }else{
                unset($RandFollow[$k]);
            }
        }
        return $RandFollow;
    }

    //去掉已关注的粉丝
    public function unsetHasFollow(&$RobotIdArr,$hasFollow)
    {
        $hasFollowId = [];
        foreach ($hasFollow as $k => $v)
        {
            $hasFollowId[] = $v['user_id'];
        }
        foreach ($RobotIdArr as $k => $v)
        {
            if(in_array($v['user_id'], $hasFollowId))
            {
                unset($RobotIdArr[$k]);
            }
        }
        //打乱顺序
        shuffle($RobotIdArr);
    }

    //判断是否出现在周排行中,出现则只用周榜
    public function checkRank($rankingList,&$redList)
    {
        $rankingId = [];
        foreach ($rankingList as $k => $v)
        {
            $rankingId[] = $v['user_id'];
        }
        foreach ($redList as $k => $v)
        {
            if(in_array($v['user_id'], $rankingId))
            {
                unset($redList[$k]);
            }
        }
    }

    //登陆赠送金币配置
    public function loginGiftSet()
    {
        $sign = 'loginGift';
        $config = M('config')->where(['sign'=>$sign])->getField('config');
        $config = json_decode($config,true);

        if (IS_POST)
        {
            $data = [
                'is_on'           => I('is_on'),
                'begin'           => strtotime(I('begin')),
                'end'             => strtotime(I('end')),
                'coinType'        => I('coinType'),
                'giftCoin'        => I('giftCoin'),
                'reg_limit_time'  => I('reg_limit_time'),
                'reg_limit_count' => I('reg_limit_count'),
            ];

            if (M('config')->where(['sign'=>$sign])->save(['config'=>json_encode($data)]) === false)
                $this->error('修改失败!');
            else
                $this->success('修改成功');
        }

        $this->assign('config',$config);
        $this->display();
    }
      //邀请好友记录列表
    public function friendList()
    {
        $nickName = I('nick_name');
        if(! empty($nickName))
        {
            $map['nick_name'] = ['like', '%'.$nickName.'%'];
        }
        $order = I('_order') ? I('_order').' '.I('_sort') : 'id desc';
        $pageNum =empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
        $InviteLog = M('InviteLog i');
        $list = $InviteLog
                ->field('i.id,i.user_id,f.username,f.nick_name,i.coin,i.first_lv_uid,i.first_coin,i.second_lv_uid,i.second_coin,i.third_lv_uid,i.third_coin,i.create_time')
                ->join('LEFT JOIN qc_front_user f ON i.user_id = f.id')
                ->where(['i.id'=>['GT',0]])
                ->where($map)
                ->limit($pageNum)
                ->order($order)
                ->page(!empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1)
                ->select();
        foreach ($list as $key => $value) {
            $list[$key]['Lv1_nick_name'] = M('FrontUser')->where(['id'=>$value['first_lv_uid']])->getField('nick_name');
            $list[$key]['lv2_nick_name'] = M('FrontUser')->where(['id'=>$value['second_lv_uid']])->getField('nick_name');
            $list[$key]['lv3_nick_name'] = M('FrontUser')->where(['id'=>$value['third_lv_uid']])->getField('nick_name');
        }
        $InviteLog = $InviteLog->where(['id'=>['GT',0]])->count('id'); //统计条数
        $this->assign ( 'totalCount', $InviteLog );
        $this->assign ( 'numPerPage', $pageNum );//每页显示多少条
        $this->assign ( 'currentPage', !empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1);//当前页码
        $this->setJumpUrl();
        $this->assign('list',$list);
        $this->display();
    }

    /**
     * 邀請好友配置
     */
    public function inviteConfig(){
        $inviteConfig = M('config')->where(['sign' => 'invite'])->getField('config');

        if(IS_POST) {

            $top     = I('top');
            $first   = I('first');
            $second  = I('second');
            $third   = I('third');
            $data['login_days'] = I('login_days');
            $data['login_times'] = I('login_times');
            $data[0] = I('register');

            foreach($top as $k => $v){
                $kk = $k + 1;
                $data[$kk]['top']    = $v;
                $data[$kk]['first']  = $first[$k];
                $data[$kk]['second'] = $second[$k];
                $data[$kk]['third']  = $third[$k];
            }

            $rs =  M('config')->where(['sign' => 'invite'])->save(['config' => json_encode($data)]);
            if ($rs)
                $this->success('修改成功');

            $this->error('修改失败!');
        }else{
            $this->assign('inviteConfig', json_decode($inviteConfig, true));
        }

        $this->display();
    }

    //用户登录列表
    public function loginNum()
    {
        if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
            if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                $startTime = strtotime($_REQUEST ['startTime']);
                $endTime   = strtotime($_REQUEST ['endTime']);
                $map['login_time'] = array('BETWEEN',array($startTime,$endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']);
                $map['login_time'] = array('EGT',$strtotime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime']);
                $map['login_time'] = array('ELT',$endTime);
            }
        }
        $username = I('username');
        if(!empty($username))
        {
            $map['username'] = ['like','%'.$username.'%'];
        }
        $nick_name = I('nick_name');
        if(! empty($nick_name))
        {
            $map['nick_name'] = ['like','%'.$nick_name.'%'];
        }
        $type = I('type');
        if(! empty($type))
        {
            $map['type'] = ['eq',$type];
        }
        $status = I('status');
        if($status != '')
        {
            $map['status'] = ['eq',$status];
        }
        $export = (int)I('Export');
        if($export === 2)
        {
            $loginNumList = $this->_list(D('InviteLoginInfoView'),$map,'login_num');
            $this->frontUserExport('',$export,$loginNumList);
            echo M()->_sql();
        }
        $list = $this->_list(D('InviteLoginInfoView'),$map,'id');
        $this->assign('list',$list);
        $this->display();
    }
    //用户结算流水列表
    public function currentAccount()
    {
        $superior_id = I('superior_id');
        if(!empty($superior_id))
            $map['superior_id'] = ['eq', $superior_id];


        $user_id = I('user_id');
        if(!empty($user_id))
            $map['user_id'] = ['eq', $user_id];

        if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
            if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                $startTime = strtotime($_REQUEST ['startTime']);
                $endTime   = strtotime($_REQUEST ['endTime']);
                $map['create_time'] = array('BETWEEN',array($startTime,$endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']);
                $map['create_time'] = array('EGT',$strtotime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime']);
                $map['create_time'] = array('ELT',$endTime);
            }
        }

        $type = I('type');
        if(! empty($type))
        {
            $map['type'] = ['eq',$type];
        }
        $list = $this->_list(D('InviteRecordInfoView'), $map, 'create_time,coin');
        if($list)
        {
            foreach ($list as $key => $value) {
                $list[$key]['parentUserName']  = M('FrontUser')->where(['id'=>$value['superior_id'],'create_time'=>$map])->getField('username');
                $list[$key]['parentNick_name'] = M('FrontUser')->where(['id'=>$value['superior_id'],'create_time'=>$map])->getField('nick_name');
            }

            $export = (int)I('Export');
            if($export === 3)
            {
                $InviteRecordInfoView = $this->_list(D('InviteRecordInfoView'),$map,'create_time,coin');
                foreach ($list as $key => $value)
                {
                    $InviteRecordInfoView[$key]['parentUserName']  = M('FrontUser')->where(['id'=>$value['superior_id'],'create_time'=>$map])->getField('username');
                    $InviteRecordInfoView[$key]['parentNick_name'] = M('FrontUser')->where(['id'=>$value['superior_id'],'create_time'=>$map])->getField('nick_name');
                }
                $this->frontUserExport('',$export,$InviteRecordInfoView);
            }
        }
        $this->assign('list',$list);
        $this->display();
    }


     /**
     * 导出Excel
     * @param string $filename [文件名，当为空时就以当前日期为文件名]
     * @param list $list [列表数据]
     * @param $export 区分导出
     */
    public function frontUserExport($filename="",$export,$list)
    {
        $filename = empty($filename)?date('Ymd'):$filename;
        $strTable ='<table width="500" border="1">';
        $strTable .="<tr>";
        if($export === 1)
        {
            $strTable .= '<th style="text-align:center;font-size:13px;" width="*">编号</th>';
            $strTable .= '<th style="text-align:center;font-size:13px;" width="*">手机号</th>';
            $strTable .= '<th style="text-align:center;font-size:13px;" width="*">昵称</th>';
            $strTable .= '<th style="text-align:center;font-size:13px;" width="*">QQ</th>';
            $strTable .= '<th style="text-align:center;font-size:13px;" width="*">微信</th>';
            $strTable .= '<th style="text-align:center;font-size:13px;" width="*">微博</th>';
            $strTable .= '<th style="text-align:center;font-size:13px;" width="*">移动绑定</th>';
            $strTable .= '<th style="text-align:center;font-size:13px;" width="*">积分</th>';
            $strTable .= '<th style="text-align:center;font-size:13px;" width="*">总金币/可提款/冻结金币</th>';
            $strTable .= '<th style="text-align:center;font-size:13px;" width="*">注册时间</th>';
            $strTable .= '<th style="text-align:center;font-size:13px;" width="*">上次登录</th>';
            $strTable .= '<th style="text-align:center;font-size:13px;" width="*">最后登录ip</th>';
            $strTable .= '<th style="text-align:center;font-size:13px;" width="*">登录次数</th>';
            $strTable .= '<th style="text-align:center;font-size:13px;" width="*">是否专家</th>';
            foreach ($list as $key => $vo)
            {
                $strTable .= "<tr>";
                $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$vo['id'].'</th>';
                $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.is_show_mobile($vo['username']).'</th>';
                $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$vo['nick_name'].'</th>';
                $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$vo['qq_unionid'].'</th>';
                $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$vo['weixin_unionid'].'</th>';
                $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$vo['sina_unionid'].'</th>';
                $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$vo['mm_unionid'].'</th>';
                $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$vo['point'].'</th>';
                $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.($vo['unable_coin']+$vo['coin'])."/".$vo['coin']."/".$vo['frozen_coin'].'</th>';
                $strTable .= '<th style="text-align:center;font-size:12px;" >'.date('Y-m-d H:i:s',$vo['reg_time']).' </th>';
                $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.date('Y-m-d H:i:s',$vo['login_time']).'</th>';
                $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$vo['last_ip'].'</th>';
                $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$vo['login_count'].'</th>';
                $is_expert = $vo['is_expert'] == 1 ? "是" : "否";
                $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$is_expert.'</th>';
                $strTable .= "</tr>";
            }
        }
        elseif($export === 2)//用户登录列表
        {
            $strTable .= '<th style="text-align:center;font-size:13px;" width="*">用户ID</th>';
            $strTable .= '<th style="text-align:center;font-size:13px;" width="*">用户名（昵称）</th>';
            $strTable .= '<th style="text-align:center;font-size:13px;" width="*">注册时间</th>';
            $strTable .= '<th style="text-align:center;font-size:13px;" width="*">最后登录时间</th>';
            $strTable .= '<th style="text-align:center;font-size:13px;" width="*">最后登录次数</th>';
            $strTable .= '<th style="text-align:center;font-size:13px;" width="*">是否有效</th>';
            $strTable .= '<th style="text-align:center;font-size:13px;" width="*">是否结算</th>';
            $strTable .= '<th style="text-align:center;font-size:13px;" width="*">创建时间</th>';
            foreach ($list as $key => $vo) {
                $strTable .= "<tr>";
                $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$vo['user_id'].'</th>';
                $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.is_show_mobile($vo['username']).'</th>';
                $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$vo['register_time'].'</th>';
                $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$vo['login_time'].'</th>';
                $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$vo['login_num'].'</th>';
                $type = $vo['type'] == 1 ? "有效" : "无效";
                $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$type.'</th>';
                $status = $vo['status'] == 1 ? "结算" : "未结算";
                $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$status.'</th>';
                $strTable .= '<th style="text-align:center;font-size:12px;" >'.date('Y-m-d H:i:s',$vo['create_time']).' </th>';
                $strTable .= "</tr>";
            }
        }
        elseif ($export === 3)
        {
             $strTable .= '<th style="text-align:center;font-size:13px;" width="*">上级用户ID</th>';
            $strTable .= '<th style="text-align:center;font-size:13px;" width="*">上级用户名</th>';
            $strTable .= '<th style="text-align:center;font-size:13px;" width="*">获得金币</th>';
            $strTable .= '<th style="text-align:center;font-size:13px;" width="*">等级</th>';
            $strTable .= '<th style="text-align:center;font-size:13px;" width="*">下级用户ID</th>';
            $strTable .= '<th style="text-align:center;font-size:13px;" width="*">下级用户名</th>';
            $strTable .= '<th style="text-align:center;font-size:13px;" width="*">是否有效</th>';
            $strTable .= '<th style="text-align:center;font-size:13px;" width="*">变更前金币<span style="font-size:10px;font-weight:normal">(可提或无效)</span></th>';
            $strTable .= '<th style="text-align:center;font-size:13px;" width="*">变更后金币<span style="font-size:10px;font-weight:normal">(可提或无效)</span></th>';
            $strTable .= '<th style="text-align:center;font-size:13px;" width="*">待考核前金币</th>';
            $strTable .= '<th style="text-align:center;font-size:13px;" width="*">待考核后金币</th>';
            $strTable .= '<th style="text-align:center;font-size:13px;" width="*">创建时间</th>';
            foreach ($list as $key => $vo) {
                $strTable .= "<tr>";
                $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$vo['superior_id'].'</th>';
                $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$vo['parentUserName']."(".$vo['parentNick_name'].")".'</th>';
                $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$vo['coin'].'</th>';
                $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$vo['userlv'].'</th>';
                $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$vo['user_id'].'</th>';
                $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.is_show_mobile($vo['username'])."(".$vo['nick_name'].")".'</th>';
                $type = $vo['type'] == 1 ? "有效" : "无效";
                $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$type.'</th>';
                $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$vo['before_coin'].'</th>';
                $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$vo['after_coin'].'</th>';
                $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$vo['before_await'].'</th>';
                $strTable .= '<th style="text-align:center;font-size:12px;" width="*">'.$vo['after_await'].'</th>';
                $strTable .= '<th style="text-align:center;font-size:12px;" >'.date('Y-m-d H:i:s',$vo['create_time']).' </th>';
                $strTable .= "</tr>";
            }
        }
        $strTable .= "</tr>";
        $strTable .= "</table>";
        downloadExcel($strTable,$filename);
        exit();
    }

    public function FrontSee()
    {
        //排序字段 默认为主键名
        $order = $_REQUEST ['_order'] ? : 'count_number';
        //排序方式默认按照倒序排列
        $sort = $_REQUEST ['_sort'] == 'asc' ? 'asc' : 'desc';
        //名字查询
        $nick_name = trim(I('nick_name'));
        if (!empty($nick_name)) {
            $map['u.nick_name'] = ['Like', '%'.$nick_name . '%'];
        }
        $map['date'] = date(Ymd);
        $where['log_time'] = ['EGT',strtotime(date(Ymd))];
        //登录时间查询
        if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
            if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                $startTime = $_REQUEST['startTime'];
                $endTime   = $_REQUEST['endTime'];
                $map['date'] = array('BETWEEN',array($startTime,$endTime));
                $where['log_time'] = array('BETWEEN',array(strtotime($startTime),strtotime($endTime)));
            } elseif (!empty($_REQUEST['startTime'])) {
                $startTime = $_REQUEST['startTime'];
                $map['date'] = array('EGT',$startTime);
                $where['log_time'] = ['EGT',strtotime($startTime)];
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = $_REQUEST['endTime'];
                $map['date'] = array('ELT',$endTime);
                $where['log_time'] = ['ELT',strtotime($endTime)];
            }
        }else{
            $_REQUEST['startTime'] = date('Ymd',time());
        }

        if(!empty($nick_name)){
            $count = M('FrontSee f')
            ->join('LEFT JOIN qc_front_user u on u.id = f.user_id')->where($map)->count('f.id');
        }else{
            $count = M('FrontSee f')->where($map)->count('f.id');
        }

        //获取每页显示的条数
        $pageNum = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
        //获取当前的页码
        $currentPage = !empty($_REQUEST[C('VAR_PAGE')]) ? $_REQUEST[C('VAR_PAGE')] : 1;

        if ($count > 0) {
            $list = M('FrontSee f')
                ->Field('f.id,f.user_id,f.date,sum(f.app_number) as app_number,sum(f.web_number) as web_number,sum(f.m_number) as m_number,sum(f.count_number) as count_number,u.username,u.nick_name')
                ->join('LEFT JOIN qc_front_user u on u.id = f.user_id')
                ->where($map)
                ->order($order." ".$sort)
                ->group('f.user_id')
                ->limit($pageNum * ($currentPage - 1), $pageNum)
                ->select();
        }
        foreach ($list as $k => $v) {
            $userId[] = $v['user_id'];
        }
        $where['cover_id'] = ['in',$userId];
        $quizLog = M('quizLog')->where($where)->select();
        foreach ($list as $k => $v) {
            $fb_num = $bk_num = 0;
            foreach ($quizLog as $kk => $vv) {
                if($v['user_id'] == $vv['cover_id']){
                    if($vv['game_type'] == 1) $fb_num++;
                    if($vv['game_type'] == 2) $bk_num++;
                }
            }
            $list[$k]['fb_num'] = $fb_num;
            $list[$k]['bk_num'] = $bk_num;
        }
        $this->assign('list', $list);
        $this->assign('totalCount', $count);//当前条件下数据的总条数
        $this->assign('numPerPage', $pageNum); //每页显示多少条
        $this->assign('currentPage', $currentPage);//当前页码
        $this->setJumpUrl();
        $this->display();
    }

    /**
     * 专家申请审核列表
     */
    public function expertApplyList(){
        $map = $this->_search('FrontUser');
        $map['expert_status'] = ['gt', 0];

        //注册时间查询
        if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
            if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                $startTime = strtotime($_REQUEST ['startTime']);
                $endTime   = strtotime($_REQUEST ['endTime'])+86399;
                $map['reg_time'] = array('BETWEEN',array($startTime,$endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']);
                $map['reg_time'] = array('EGT',$strtotime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime']) +86399;
                $map['reg_time'] = array('ELT',$endTime);
            }
        }

        if(!empty($_REQUEST ['startRegisterTime']) || !empty($_REQUEST ['endRegisterTime'])){
            if(!empty($_REQUEST ['startRegisterTime']) && !empty($_REQUEST ['endRegisterTime'])){
                $startTime = strtotime($_REQUEST ['startRegisterTime']);
                $endTime   = strtotime($_REQUEST ['endRegisterTime'])+86399;
                $map['expert_register_time'] = array('BETWEEN',array($startTime,$endTime));
            } elseif (!empty($_REQUEST['startRegisterTime'])) {
                $strtotime = strtotime($_REQUEST ['startRegisterTime']);
                $map['expert_register_time'] = array('EGT',$strtotime);
            } elseif (!empty($_REQUEST['endRegisterTime'])) {
                $endTime = strtotime($_REQUEST['endRegisterTime']) +86399;
                $map['expert_register_time'] = array('ELT',$endTime);
            }
        }

        $no_login = I('no_login');
        if($no_login == ''){
            //登录时间查询
            if(!empty($_REQUEST ['startTimeLogin']) || !empty($_REQUEST ['endTimeLogin'])){
                if(!empty($_REQUEST ['startTimeLogin']) && !empty($_REQUEST ['endTimeLogin'])){
                    $startTimeLogin = strtotime($_REQUEST ['startTimeLogin']);
                    $endTimeLogin   = strtotime($_REQUEST ['endTimeLogin'])+86399;
                    $map['login_time'] = array('BETWEEN',array($startTimeLogin,$endTimeLogin));
                } elseif (!empty($_REQUEST['startTimeLogin'])) {
                    $startTimeLogin = strtotime($_REQUEST ['startTimeLogin']);
                    $map['login_time'] = array('EGT',$startTimeLogin);
                } elseif (!empty($_REQUEST['endTimeLogin'])) {
                    $endTimeLogin = strtotime($_REQUEST['endTimeLogin'])+86399;
                    $map['login_time'] = array('ELT',$endTimeLogin);
                }
            }
        }else{
            if(!empty($_REQUEST ['startTimeLogin']) || !empty($_REQUEST ['endTimeLogin'])){
                if(!empty($_REQUEST ['startTimeLogin']) && !empty($_REQUEST ['endTimeLogin'])){
                    $startTimeLogin = strtotime($_REQUEST ['startTimeLogin']);
                    $endTimeLogin   = strtotime($_REQUEST ['endTimeLogin'])+86399;
                    $map['login_time'] = ['exp',"< {$startTimeLogin} or login_time > {$endTimeLogin} or login_time is null"];
                } elseif (!empty($_REQUEST['startTimeLogin'])) {
                    $startTimeLogin = strtotime($_REQUEST ['startTimeLogin']);
                    $map['login_time'] = ['exp',"< {$startTimeLogin} or login_time is null"];
                } elseif (!empty($_REQUEST['endTimeLogin'])) {
                    $endTimeLogin = strtotime($_REQUEST['endTimeLogin'])+86399;
                    $map['login_time'] = ['exp',"> {$endTimeLogin} or login_time is null"];
                }
            }
        }

        //审核状态
        if($_REQUEST['expert_status']){
            $map['expert_status'] = $_REQUEST['expert_status'];
            if($map['expert_status'] == 1)
                $map['is_expert'] = 1;
        }

        $abnormal = I('abnormal');
        if($abnormal != ''){
            $map['id'] = ['in',explode(",", $abnormal)];
        }
        $list = $this->_list(D('FrontUser'),$map,'reg_time');

        foreach ($list as $k => $v) {
            $list[$k]['robot_conf'] = json_decode($v['robot_conf'],true);
        }

        $userArr = implode(',', array_map("array_shift", $list));
        //查询待结算金币
        $incomeArr = M('gamble')
            ->field('user_id,sum(income) as income')
            ->where("result = 0 AND tradeCoin > 0 AND quiz_number > 0 AND is_back = 0 AND user_id in (".$userArr.")")
            ->group('user_id')
            ->select();
        //查询体验/优惠券数量
        $ticketArr = M('ticketLog')
            ->field('user_id,count(id) as ticket_num')
            ->where("is_use = 0 AND user_id in (".$userArr.")")
            ->group('user_id')
            ->select();

        foreach ($list as $k => $v)
        {
            foreach ($incomeArr as $kk => $vv) {
                if($v['id'] == $vv['user_id']){
                    $list[$k]['wait_coin'] = $vv['income'];
                }
            }
            foreach ($ticketArr as $kkk => $vvv) {
                if($v['id'] == $vvv['user_id']){
                    $list[$k]['ticket_num'] = $vvv['ticket_num'];
                }
            }

            if ($v["expert_register_time"] == 0) {
                $list[$k]["expert_register_time"] = "";
            }

            if ($v["expert_allow_time"] == 0) {
                $list[$k]["expert_allow_time"] = "";
            }
        }

        $this->assign('list', $list);
        $this->display();
    }


    /**
     * 专家审核资料页面
     */
    public function expertInfo(){
        $id = I('id');
        if(IS_POST){
            $data['expert_status'] = I('expert_status');
            if(I('reason'))
                $data['reason'] = I('reason');

            if($data['expert_status'] == 1)
                $data['is_expert'] = 1;

            $data['expert_allow_time'] = time();

            $rs = M('FrontUser')->where(['id'=>$id])->save($data);
            if($rs){
                $this->success("审核成功！", cookie('_currentUrl_'));
            }else{
                $this->error("审核失败！");
            }
        }else{
            $vo = M('FrontUser')->where(['id'=>$id])->find();
            $vo['face'] = frontUserFace($vo['head']);
            $vo['identfy_pic'] = frontUserFace($vo['identfy_pic']);
            if (!$vo){
                $this->error('参数错误');
            }
            $this->assign('vo', $vo);
            $this->display();
        }
    }

    /**
     * 统计会员开通列表
     */
    public function vipList(){
        $map = $this->_search('FrontUser');
        $map['open_viptime'] = ['gt',0];

        $vip_type = I('vip_type');
        if($vip_type != ''){
            if($vip_type == 1){
                //已到期
                $map['vip_time'] = ['lt',strtotime(date(Ymd))];
            }else{
                //未到期
                $map['vip_time'] = ['egt',strtotime(date(Ymd))];
            }
        }
        //注册时间查询
        if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
            if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                $startTime = strtotime($_REQUEST ['startTime']);
                $endTime   = strtotime($_REQUEST ['endTime'])+86399;
                $map['open_viptime'] = array('BETWEEN',array($startTime,$endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']);
                $map['open_viptime'] = array('EGT',$strtotime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime']) +86399;
                $map['open_viptime'] = array(array('gt',0),array('ELT',$endTime));
            }
        }
        $list = $this->_list(CM('FrontUser'),$map,"open_viptime");
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 足球机器人聊天配置
     */
    public function fbRobotConfig(){
        $robotConfig = M('config')->where(['sign' => 'fbGameRobot'])->getField('config');

        if(IS_POST) {
            $data = I();
            $rs =  M('config')->where(['sign' => 'fbGameRobot'])->save(['config' => json_encode($data)]);
            if ($rs)
                $this->success('修改成功');

            $this->error('修改失败!');
        }else{
            $this->assign('robotConfig', json_decode($robotConfig, true));
        }

        $this->display();
    }

    /**
     * 篮球机器人聊天配置
     */
    public function bkRobotConfig(){
        $robotConfig = M('config')->where(['sign' => 'bkGameRobot'])->getField('config');

        if(IS_POST) {
            $data = I();
            $rs =  M('config')->where(['sign' => 'bkGameRobot'])->save(['config' => json_encode($data)]);
            if ($rs)
                $this->success('修改成功');

            $this->error('修改失败!');
        }else{
            $this->assign('robotConfig', json_decode($robotConfig, true));
        }

        $this->display();
    }

}