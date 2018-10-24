<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2010 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 囚鸟先生
// +----------------------------------------------------------------------
// $Id: ArticleController.class.php 2014-05-17 23:58:02 $
use Think\Controller;
use Org\Util\Rbac;

class CommonController extends Controller {

    function _initialize() {
        //记录后台日志
        operationLog();

        // 用户权限检查
        if (C('USER_AUTH_ON') && !in_array(CONTROLLER_NAME, explode(',', C('NOT_AUTH_MODULE')))) {
            $nosign = I('nosign'); //自动执行程序无需登录验证
            if (!RBAC::AccessDecision() && $nosign != 'haha') {
                //检查认证识别号
                if (!$_SESSION [C('USER_AUTH_KEY')]) {
                    if(IS_AJAX){
                        echo "<script>window.location.href='".U(C('USER_AUTH_GATEWAY'))."'</script>";
                        die;
                    }
                    //跳转到认证网关
                    redirect(U(C('USER_AUTH_GATEWAY')));
                }
                // 没有权限 抛出错误
                if (C('RBAC_ERROR_PAGE')) {
                    // 定义权限错误页面
                    redirect(C('RBAC_ERROR_PAGE'));
                } else {
                    if (C('GUEST_AUTH_ON')) {
                        $this->assign('jumpUrl', PHP_FILE . C('USER_AUTH_GATEWAY'));
                    }
                    // 提示错误信息
                    $this->error(L('_VALID_ACCESS_'));
                }
            }
        }
        $getNotIsException = $this->NotIsException();//异常未处理记录
        $this->assign('excepLogCount',$getNotIsException['excepLogCount']);
        $this->assign('new_count',$getNotIsException['new_count']);
    }
    /**
     * 判断是否有异常操作未处理
     * @user liangzk <liangzk@qc.com>
     * @DateTime 2016-09-06 15:48
     *  @version v2.1
     */
    public function NotIsException()
    {
        
        //新增的异常数量
        $newException = M('ExceptionLog')->count('id');
        $lastException = S('ExceptionLog'.$_SESSION['authId'].':ExceptionCount');
        if (($newException - $lastException) > 0)
        {
            $data['new_count'] = $newException - $lastException;//新增的异常数量
        }
        else
        {
            $data['new_count'] = 0;
        }
        S('ExceptionLog'.$_SESSION['authId'].':ExceptionCount',$newException);

        //未处理的异常数量
        $untreatedException = M('ExceptionLog')->where(['status'=>0])->count('id');//未处理的异常数
        if ($untreatedException > 0)
        {
            $data['excepLogCount'] = $untreatedException;
        }
        else
        {
            $data['excepLogCount'] = 0;
        }
        return $data;

    }
    /**
      +----------------------------------------------------------
     * Index页显示
     *
     */
    public function index($dwz_db_name = '') {
        //列表过滤器，生成查询Map对象
        $dwz_db_name=$dwz_db_name ? $dwz_db_name : $this->getActionName();
        $model = CM($dwz_db_name);
        $map = $this->_search($dwz_db_name);
        $this->assign("map",$map);
        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }
        if (!empty($model)) {
            $this->_list($model, $map);
        }
        $this->display();
    }


    /**
      +----------------------------------------------------------
     * 根据表单生成查询条件
     * 进行列表过滤
      +----------------------------------------------------------
     * @access protected
      +----------------------------------------------------------
     * @param string $dwz_db_name 数据对象名称
      +----------------------------------------------------------
     * @return HashMap
      +----------------------------------------------------------
     * @throws ThinkExecption
      +----------------------------------------------------------
     */
    protected function _search($dwz_db_name = '') {
        $dwz_db_name=$dwz_db_name ? $dwz_db_name : $this->getActionName();
        //生成查询条件
        $model = CM($dwz_db_name);
        $map = array();
        if($model->viewFields == NULL){
            $fieldArray = $model->getDbFields();
        }else{
            $fieldArray = call_user_func_array('array_merge',$model->viewFields);
        }
        foreach ($fieldArray as $key => $val) {
            if (isset($_REQUEST [$val]) && $_REQUEST [$val] != '') {
                //特别指定一些字段进行模糊查询
                $likeArray = array(
                    'game_id',
                    'nickname',
                    'team_name',
                    'union_name',
                    'name',
                    'title',
                    'username',
                    'nick_name',
                    'true_name',
                    'game_name',
                    'run_name',
                    'player_name',
                    'sport_name',
                    'desc',
                    'sign',
                    'ip',
                    'code',
                    'home_team_name',
                    'away_team_name',
                    'idfa',
                    'device',
                    'gift_name',
                );
                if (in_array($val, $likeArray)){
                    //是否有搜索手机号有字母的权限
                    if($val == 'username' && !is_numeric(trim($_REQUEST [$val])) && getUserPower()['is_search_user'] == 0){
                        $map [$val] = md5(123456);
                        continue;
                    }
                    //模糊查询
                    $map [$val] = array('like', trim($_REQUEST [$val]).'%');
                } else {
                    //精确查询
                    $map [$val] = trim($_REQUEST [$val]);
                }
            }
        }
        return $map;
    }

    /**--------------------------------------------------------
     * 根据表单生成查询条件
     * 进行列表过滤 +----------------------------------------------------------
     * @param Model $model 数据对象
     * @param HashMap $map 过滤条件
     * @param string $sortBy 排序字段,多个以逗号隔开
     * @param boolean $asc 是否正序
     * @param string  $countPk  主键
     * @param string  $field  提取字段
     * @param string  $setJump  是否记录当前链接cookie
     */
    protected function _list($model, $map, $sortBy = '', $asc = false ,$countPk="",$field="*",$setJump=true) {
        //排序字段 默认为主键名
        if (!empty ( $_REQUEST ['_order'] )) {
            $order = $_REQUEST ['_order'];
        } else {
            $order = ! empty ( $sortBy ) ? $sortBy : $model->getPk();
        }
        //排序方式默认按照倒序排列
        //接受 sost参数 0 表示倒序 非0都 表示正序
        if (isset ( $_REQUEST ['_sort'] )) {
            $sort = $_REQUEST ['_sort'] == 'asc' ? 'asc' : 'desc';
        } else {
            $sort = $asc ? 'asc' : 'desc';
        }
        if(!empty($sortBy)){
            $sortType = $asc ? 'asc' : 'desc';
            foreach (explode(',', $sortBy) as $k => $v) {
                $orderBy[] = $v." ".$sortType;
            }
        }
        $orderBy = implode(',', $orderBy) ? implode(',', $orderBy) : $order.' '.$sort;
        if(is_null($asc)){
            $orderBy = $sortBy;
        }
        if (!empty ( $_REQUEST ['_order'] )) {
            $orderBy = $order." ".$sort;
        }
        //取得满足条件的记录数
        $count = $model->master(true)->where($map)->count();
        if ($count > 0) {
            $pageNum =empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
            //分页查询数据
            if($model->viewFields == NULL){
                if($countPk == ''){
                    $voList = $model->master(true)->relation(true)->field($field)->where($map)->order( $orderBy )->limit($pageNum)->page($_REQUEST[C('VAR_PAGE')])->select();
                }else{
                    $voList = $model->master(true)->relation(true)->group($countPk)->field($field)->where($map)->order( $orderBy )->limit($pageNum)->page($_REQUEST[C('VAR_PAGE')])->select();
                }
            }else{
                if($countPk == ''){
                    $voList = $model->master(true)->where($map)->order( $orderBy )->limit($pageNum)->page($_REQUEST[C('VAR_PAGE')])->select();
                }else{
                   $voList = $model->master(true)->where($map)->group($countPk)->order( $orderBy )->limit($pageNum)->page($_REQUEST[C('VAR_PAGE')])->select();
                }
            }

            return $this->_listForPage($voList, $count, $order, $sort, $setJump);
            //列表排序显示
//             $sortImg = $sort; //排序图标
//             $sortAlt = $sort == 'desc' ? '升序排列' : '倒序排列'; //排序提示
//             $sort = $sort == 'desc' ? 1 : 0; //排序方式
//             //模板赋值显示
//             $this->assign('list', $voList);
//             $this->assign('sort', $sort);
//             $this->assign('order', $order);
//             $this->assign('sortImg', $sortImg);
//             $this->assign('sortType', $sortAlt);

//             //囚鸟先生
//             $this->assign ( 'totalCount', $count );
//             $pageNum =empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
//             $this->assign ( 'numPerPage', $pageNum ); //每页显示多少条
//             $this->assign ( 'currentPage', !empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1);
//             if($setJump){
//                 $this->setJumpUrl();
//             }
        
//             //返回列表数据,方便组装数据
//             return $voList;
        }else{
            return false;
        }
    }
    
    /**
     * 根据结果进行分页
     * 
     * @User Administrator
     * @DateTime 2018年6月20日
     *
     * @param unknown $voList
     * @param unknown $count
     * @param unknown $order
     * @param string $sort
     * @param string $setJump
     */
    protected function _listForPage($voList, $count, $order, $sort='DESC', $setJump=true) {
        $pageNum =empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];

        //列表排序显示
        $sortImg = $sort; //排序图标
        $sortAlt = $sort == 'DESC' ? '升序排列' : '倒序排列'; //排序提示
        $sort = $sort == 'DESC' ? 1 : 0; //排序方式
        //模板赋值显示
        $this->assign('list', $voList);
        $this->assign('sort', $sort);
        $this->assign('order', $order);
        $this->assign('sortImg', $sortImg);
        $this->assign('sortType', $sortAlt);

        //囚鸟先生
        $this->assign ( 'totalCount', $count );
        $pageNum =empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
        $this->assign ( 'numPerPage', $pageNum ); //每页显示多少条
        $this->assign ( 'currentPage', !empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1);
        if($setJump){
            $this->setJumpUrl();
        }
    
        //返回列表数据,方便组装数据
        return $voList;
    }

    //记录现在所在的操作列表
    function setJumpUrl(){
        $jumpUrl = $_SERVER['PATH_INFO'];
        if( cookie('_currentUrl_') != $jumpUrl && $_POST['pageNum'] == '' && IS_GET){
            cookie('_currentUrl_', $jumpUrl);
        }
    }

    function insert($dwz_db_name = '') {
        $dwz_db_name=$dwz_db_name ? $dwz_db_name : $this->getActionName();
        $model = CM($dwz_db_name);
        if (false === $model->create()) {
            $this->error($model->getError());
        }
        //保存当前数据对象
        $list = $model->add();
        if ($list !== false) { //保存成功
            $this->success('新增成功!',cookie('_currentUrl_'));
        } else {
            //失败提示
            $this->error('新增失败!');
        }
    }

    function add() {
        $this->display();
    }

    function edit($dwz_db_name = '') {
        $dwz_db_name=$dwz_db_name ? $dwz_db_name : $this->getActionName();
        $model = M($dwz_db_name);
        $id = $_REQUEST [$model->getPk()];
        $vo = $model->getById($id);
        $this->assign('vo', $vo);
        $this->display();
    }

    function update($dwz_db_name = '') {
        $dwz_db_name=$dwz_db_name ? $dwz_db_name : $this->getActionName();
        $model = CM($dwz_db_name);
        if (false === $model->create()) {
            $this->error($model->getError());
        }
        // 更新数据
        $list = $model->save();
        if (false !== $list) {
            //成功提示
            $this->success('编辑成功!',cookie('_currentUrl_'));
        } else {
            //错误提示
            $this->error('编辑失败!');
        }
    }

    /**
      +----------------------------------------------------------
     * 默认删除操作
      +----------------------------------------------------------
     * @access public
      +----------------------------------------------------------
     * @return string
      +----------------------------------------------------------
     * @throws ThinkExecption
      +----------------------------------------------------------
     */
    public function _delete($dwz_db_name = '') {
        $dwz_db_name=$dwz_db_name ? $dwz_db_name : $this->getActionName();
        //删除指定记录
        $model = M($dwz_db_name);
        if (!empty($model)) {
            $pk = $model->getPk();
            $id = $_REQUEST [$pk];
            if (isset($id)) {
                $condition = array($pk => array('in', explode(',', $id)));
                $list = $model->where($condition)->setField('status', - 1);
                if ($list !== false) {
                    $this->success('删除成功！');
                } else {
                    $this->error('删除失败！');
                }
            } else {
                $this->error('非法操作');
            }
        }
    }

    public function foreverdelete($dwz_db_name = '') {
        $dwz_db_name=$dwz_db_name ? $dwz_db_name : $this->getActionName();
        //删除指定记录
        $model = CM($dwz_db_name);
        if (!empty($model)) {
            $pk = $model->getPk();
            $id = $_REQUEST [$pk];
            if (isset($id)) {
                $condition = array($pk => $id);
                if (false !== $model->where($condition)->delete()) {
                    $this->success('删除成功！');
                } else {
                    $this->error('删除失败！');
                }
            } else {
                $this->error('非法操作');
            }
        }
        $this->forward();
    }


    /**
    +----------------------------------------------------------
    * 添加删除操作  (多个删除)
    +----------------------------------------------------------
    * @access public
    +----------------------------------------------------------
    * @return string
    +----------------------------------------------------------
    * @throws ThinkExecption
    +----------------------------------------------------------
    */

    public function delAll(){
        $dwz_db_name=$dwz_db_name ? $dwz_db_name : $this->getActionName();
        $model  = CM ($dwz_db_name);
        $pk     = $model->getPk ();
        $ids    = isset($_POST['id']) ? $_POST['id'] : null;
        $idsArr = explode(',', $ids);
        $condition = array ($pk => array ('in',$idsArr));
        $rs = $model->where($condition)->delete();
        if($rs){
            $this->success('批量删除成功!');
        }else{
            $this->error('批量删除失败!');
        }

    }

    public function _clear($dwz_db_name = '') {
        $dwz_db_name=$dwz_db_name ? $dwz_db_name : $this->getActionName();
        //删除指定记录
        $model = CM($dwz_db_name);
        if (!empty($model)) {
            if (false !== $model->where('status=1')->delete()) {
                $this->success(L('_DELETE_SUCCESS_'),cookie('_currentUrl_'));
            } else {
                $this->error(L('_DELETE_FAIL_'));
            }
        }
        $this->forward();
    }

    /**
      +----------------------------------------------------------
     * 默认禁用操作
     *
      +----------------------------------------------------------
     * @access public
      +----------------------------------------------------------
     * @return string
      +----------------------------------------------------------
     * @throws FcsException
      +----------------------------------------------------------
     */
    public function forbid($dwz_db_name = '') {
        $dwz_db_name=$dwz_db_name ? $dwz_db_name : $this->getActionName();
        $model = CM($dwz_db_name);
        $pk = $model->getPk();
        $id = $_REQUEST [$pk];
        $condition = array($pk => array('in', $id));
        $list = $model->forbid($condition);
        if ($list !== false) {
            $this->success('状态禁用成功',cookie('_currentUrl_'));
        } else {
            $this->error('状态禁用失败！');
        }
    }

    public function checkPass($dwz_db_name = '') {
        $dwz_db_name=$dwz_db_name ? $dwz_db_name : $this->getActionName();
        $model = CM($dwz_db_name);
        $pk = $model->getPk();
        $id = $_GET [$pk];
        $condition = array($pk => array('in', $id));
        if (false !== $model->checkPass($condition)) {
            $this->success('状态批准成功！',cookie('_currentUrl_'));
        } else {
            $this->error('状态批准失败！');
        }
    }

    public function recycle($dwz_db_name = '') {
        $dwz_db_name=$dwz_db_name ? $dwz_db_name : $this->getActionName();
        $model = CM($dwz_db_name);
        $pk = $model->getPk();
        $id = $_GET [$pk];
        $condition = array($pk => array('in', $id));
        if (false !== $model->recycle($condition)) {
            $this->success('状态还原成功！',cookie('_currentUrl_'));
        } else {
            $this->error('状态还原失败！');
        }
    }

    public function _recycleBin($dwz_db_name = '') {
        $map = $this->_search();
        $map ['status'] = - 1;

        $model = CM($dwz_db_name);
        if (!empty($model)) {
            $this->_list($model, $map);
        }
        $this->display();
    }

    /**
      +----------------------------------------------------------
     * 默认恢复操作
     *
      +----------------------------------------------------------
     * @access public
      +----------------------------------------------------------
     * @return string
      +----------------------------------------------------------
     * @throws FcsException
      +----------------------------------------------------------
     */
    function resume($dwz_db_name = '') {
        $dwz_db_name=$dwz_db_name ? $dwz_db_name : $this->getActionName();
        //恢复指定记录
        $model = CM($dwz_db_name);
        $pk = $model->getPk();
        $id = $_GET [$pk];
        $condition = array($pk => array('in', $id));
        if (false !== $model->resume($condition)) {
            $this->success('状态恢复成功！',cookie('_currentUrl_'));
        } else {
            $this->error('状态恢复失败！');
        }
    }

    function _saveSort($dwz_db_name = '') {
        $seqNoList = $_POST ['seqNoList'];
        if (!empty($seqNoList)) {
            $dwz_db_name=$dwz_db_name ? $dwz_db_name : $this->getActionName();
            //更新数据对象
            $model = CM($dwz_db_name);
            $col = explode(',', $seqNoList);
            //启动事务
            $model->startTrans();
            foreach ($col as $val) {
                $val = explode(':', $val);
                $model->id = $val [0];
                $model->sort = $val [1];
                $result = $model->save();
                if (!$result) {
                    break;
                }
            }
            //提交事务
            $model->commit();
            if ($result !== false) {
                //采用普通方式跳转刷新页面
                $this->success('更新成功');
            } else {
                $this->error($model->getError());
            }
        }
    }
    /**
     * 弹窗查找用户（前台用户）
     * return #
     */
    public function findFrontUser(){
        $map = $this->_search ("FrontUser");
        //是否机器人竞猜记录筛选
        $is_robot = I('is_robot');
        if($is_robot != ''){
            if($is_robot == 1){
                $map['is_robot'] = 1;
            }else{
                $map['is_robot'] = ['neq',1];
            }
        }
        $Multiselect = $_REQUEST['Multiselect'] != '' ? $_REQUEST['Multiselect'] : 1;
        $this->assign('Multiselect', $Multiselect);

        //多昵称搜索
        $more_name = trim(I('more_name'), ",");
        $more_name = implode("|", explode(',', $more_name));
        if(!empty($more_name)){
            $map['nick_name'] = ['exp',"regexp '{$more_name}'"];
        }
        //获取列表
        $list = $this->_list ( D('FrontUser'), $map,'',false,'','*',false);
        foreach($list as $k => $v ){
            $list[$k]['live_uniqueid'] = $v['id'].GetRandStr(4, 'number');
        }
        $this->assign('list', $list);
        $tp = "Public:findFrontUserDialog";
        $this->display($tp);
    }
    /**
     * 弹窗查找（后台用户）
     * return #
     */
    public function findUser(){
        $map = $this->_search ("User");
        //获取列表
        $list = $this->_list ( D('User'), $map,'',false,'','*',false);
        $this->assign('list', $list);
        $tp = "Public:findUserDialog";
        $this->display($tp);
    }

    //添加用户查询条件
    public function addUserMap(&$map,$is_true=true)
    {
        $username = trim(I('username'));
        if(!empty($username)) {
            if($is_true){
                $search = ['like','%'.$username.'%'];
            }else{
                $search = $username;
            }
            $userWhere['username']  = $search;
            $userWhere['nick_name'] = $search;
            $userWhere['_logic'] = 'or';
            $map['_complex'] = $userWhere;
        }
    }
     //查询后获取输入
    public function pageSearch()
    {
        foreach ($_REQUEST as $k => $v) {//safeEncoding($v)
            $inputQuest[$k] = mb_convert_encoding($v,'auto','utf8');
        }
        if($inputQuest['actionName'] == 'posts'){
            //获取分类
            $Community = M('Community')->where("status=1")->select();
            //引用Tree类
            $CommunityClass = Think\Tool\Tool::getTree($Community, $pid = 0, $col_id = 'id', $col_pid = 'pid', $col_cid = 'level');
            $this->assign('CommunityClass',$CommunityClass);
            //获取编辑
            $editor = M('user')->select();
            $this->assign('editor', $editor);
        }
        $this->assign('inputQuest',$inputQuest);
        $this->display();
    }

    /**
     * 定时任务，处理每天达到条件的用户
     */
    public function settleInviteData(){
        //未结算的用户
        $data = M('InviteLoginInfo')->master(true)->where(['status' => 0])->select();

        if(!$data)
            die('no data');

        try{
            M()->startTrans();

            foreach($data as $k => $v){
                //20161101之前的已经都拿了，所以不需要结算
                $reg_time = M('FrontUser')->master(true)->where(['id' => $v['user_id']])->getField('reg_time');
                if($reg_time < strtotime('2016-11-01 00:00:00'))
                    continue;

                $second_id = $third_id = 0;
                //推荐人id
                $recommend_id = M('FrontUser')->master(true)->where(['id' => $v['user_id']])->getField('recommend_id');

                //推荐人上级id
                if($recommend_id){
                    $second_id  = M('FrontUser')->master(true)->where(['id' => $recommend_id])->getField('recommend_id');

                    if($second_id){//推荐人上上级id
                        $third_id  = M('FrontUser')->master(true)->where(['id' => $second_id])->getField('recommend_id');
                    }
                }else{//没有邀请人的用户跳过，等下次结算
                    continue;
                }

                if($third_id){
                    $where['user_id']       = $third_id;
                    $where['first_lv_uid']  = $second_id;
                    $where['second_lv_uid'] = $recommend_id;
                    $where['third_lv_uid']  = $v['user_id'];
                }else if($second_id){
                    $where['user_id']       = $second_id;
                    $where['first_lv_uid']  = $recommend_id;
                    $where['second_lv_uid'] = $v['user_id'];
                    $where['third_lv_uid']  = 0;
                }else if($recommend_id){
                    $where['user_id']       = $recommend_id;
                    $where['first_lv_uid']  = $v['user_id'];
                    $where['second_lv_uid'] = 0;
                    $where['third_lv_uid']  = 0;
                }

                //查询注册时候对应获得的金币数
                $coins = M('InviteLog')->master(true)->field('coin, first_coin, second_coin, third_coin')->where($where)->find();
                if($third_id){
                    $thirdInfo = M('InviteInfo')->field('valid_coin, invalid_coin, await_coin')->where(['user_id' => $third_id])->find();
                    $recordInfo[0]['superior_id'] = $third_id;
                    $recordInfo[0]['coin'] = $coins['coin'];
                    $recordInfo[0]['lv'] = 3;
                    $recordInfo[0]['user_id'] = $v['user_id'];
                    $recordInfo[0]['type'] = $v['type'];
                    $recordInfo[0]['before_coin'] = ($v['type'] == 1) ? $thirdInfo['valid_coin'] : $thirdInfo['invalid_coin'];
                    $recordInfo[0]['after_coin']  = ($v['type'] == 1) ? ($thirdInfo['valid_coin'] + $coins['coin']): ($thirdInfo['invalid_coin'] + $coins['coin']);
                    $recordInfo[0]['before_await'] = $thirdInfo['await_coin'];
                    $recordInfo[0]['after_await']  = $thirdInfo['await_coin'] - $coins['coin'];

                    $secondInfo = M('InviteInfo')->field('valid_coin, invalid_coin, await_coin')->where(['user_id' => $second_id])->find();
                    $recordInfo[1]['superior_id'] = $second_id;
                    $recordInfo[1]['coin'] = $coins['first_coin'];
                    $recordInfo[1]['lv'] = 2;
                    $recordInfo[1]['user_id'] = $v['user_id'];
                    $recordInfo[1]['type'] = $v['type'];
                    $recordInfo[1]['before_coin'] = ($v['type'] == 1) ? $secondInfo['valid_coin'] : $secondInfo['invalid_coin'];
                    $recordInfo[1]['after_coin']  = ($v['type'] == 1) ? ($secondInfo['valid_coin'] + $coins['first_coin']): ($secondInfo['invalid_coin'] + $coins['first_coin']);
                    $recordInfo[1]['before_await'] = $secondInfo['await_coin'];
                    $recordInfo[1]['after_await']  = $secondInfo['await_coin'] - $coins['first_coin'];

                    $recommendInfo = M('InviteInfo')->field('valid_coin, invalid_coin, await_coin')->where(['user_id' => $recommend_id])->find();
                    $recordInfo[2]['superior_id'] = $recommend_id;
                    $recordInfo[2]['coin'] = $coins['second_coin'];
                    $recordInfo[2]['lv'] = 1;
                    $recordInfo[2]['user_id'] = $v['user_id'];
                    $recordInfo[2]['type'] = $v['type'];
                    $recordInfo[2]['before_coin'] = ($v['type'] == 1) ? $recommendInfo['valid_coin'] : $recommendInfo['invalid_coin'];
                    $recordInfo[2]['after_coin']  = ($v['type'] == 1) ? ($recommendInfo['valid_coin'] + $coins['second_coin']): ($recommendInfo['invalid_coin'] + $coins['second_coin']);
                    $recordInfo[2]['before_await'] = $recommendInfo['await_coin'];
                    $recordInfo[2]['after_await']  = $recommendInfo['await_coin'] - $coins['second_coin'];
                }else if($second_id){
                    $secondInfo = M('InviteInfo')->field('valid_coin, invalid_coin, await_coin')->where(['user_id' => $second_id])->find();
                    $recordInfo[0]['superior_id'] = $second_id;
                    $recordInfo[0]['coin'] = $coins['coin'];
                    $recordInfo[0]['lv'] = 2;
                    $recordInfo[0]['user_id'] = $v['user_id'];
                    $recordInfo[0]['type'] = $v['type'];
                    $recordInfo[0]['before_coin'] = ($v['type'] == 1) ? $secondInfo['valid_coin'] : $secondInfo['invalid_coin'];
                    $recordInfo[0]['after_coin']  = ($v['type'] == 1) ? ($secondInfo['valid_coin'] + $coins['coin']): ($secondInfo['invalid_coin'] + $coins['coin']);
                    $recordInfo[0]['before_await'] = $secondInfo['await_coin'];
                    $recordInfo[0]['after_await']  = $secondInfo['await_coin'] - $coins['coin'];

                    $recommendInfo = M('InviteInfo')->field('valid_coin, invalid_coin, await_coin')->where(['user_id' => $recommend_id])->find();
                    $recordInfo[1]['superior_id'] = $recommend_id;
                    $recordInfo[1]['coin'] = $coins['first_coin'];
                    $recordInfo[1]['lv'] = 1;
                    $recordInfo[1]['user_id'] = $v['user_id'];
                    $recordInfo[1]['type'] = $v['type'];
                    $recordInfo[1]['before_coin'] = ($v['type'] == 1) ? $recommendInfo['valid_coin'] : $recommendInfo['invalid_coin'];
                    $recordInfo[1]['after_coin']  = ($v['type'] == 1) ? ($recommendInfo['valid_coin'] + $coins['first_coin']): ($recommendInfo['invalid_coin'] + $coins['first_coin']);
                    $recordInfo[1]['before_await'] = $recommendInfo['await_coin'];
                    $recordInfo[1]['after_await']  = $recommendInfo['await_coin'] - $coins['first_coin'];
                }else if($recommend_id){
                    $recommendInfo = M('InviteInfo')->field('valid_coin, invalid_coin, await_coin')->where(['user_id' => $recommend_id])->find();
                    $recordInfo[0]['superior_id'] = $recommend_id;
                    $recordInfo[0]['coin'] = $coins['coin'];
                    $recordInfo[0]['lv'] = 1;
                    $recordInfo[0]['user_id'] = $v['user_id'];
                    $recordInfo[0]['type'] = $v['type'];
                    $recordInfo[0]['before_coin'] = ($v['type'] == 1) ? $recommendInfo['valid_coin'] : $recommendInfo['invalid_coin'];
                    $recordInfo[0]['after_coin']  = ($v['type'] == 1) ? ($recommendInfo['valid_coin'] + $coins['coin']): ($recommendInfo['invalid_coin'] + $coins['coin']);
                    $recordInfo[0]['before_await'] = $recommendInfo['await_coin'];
                    $recordInfo[0]['after_await']  = $recommendInfo['await_coin'] - $coins['coin'];
                }

                //各级入库流水表，更新邀请信息表、用户信息表
                foreach($recordInfo as $k1 => $v1){
                    $recordInfo[$k1]['create_time'] = NOW_TIME;

                    //有效用户
                    if($v['type'] == 1){
                        $infoData['valid_coin']  = ['exp', 'valid_coin+'.$v1['coin']];

                        //邀请人信息更新金币入库
                        $totalCion = M('FrontUser')->master(true)->where(['id'=>$v1['superior_id']])->getField('(coin+unable_coin) as total');
                        $res1 = M('FrontUser')->where(['id'=>$v1['superior_id']])->save(['coin' => ['exp', 'coin+'.$v1['coin']]]);
                        $res2 = M('AccountLog')->add([
                            'user_id'    => $v1['superior_id'],
                            'log_time'   => NOW_TIME,
                            'log_type'   => 13,
                            'log_status' => 1,
                            'change_num' => $v1['coin'],
                            'total_coin' => $totalCion+$v1['coin'],
                            'desc'       => "邀请好友",
                            'platform'   => 1,
                            'invite_id'  => $v1['user_id'],
                        ]);
                    }else{
                        $infoData['invalid_coin']  = ['exp', 'invalid_coin+'.$v1['coin']];
                    }

                    $infoData['await_coin']  = ['exp', 'await_coin-'.$v1['coin']];
                    $res3 = M('InviteInfo')->where(['user_id' => $v1['superior_id']])->save($infoData);

                    if($res1 === false || $res2 === false || $res3 === false) {
                        throw new Exception();
                    }
                }

                //入库流水表
                $res4 = M('InviteRecordInfo')->addAll($recordInfo);

                //标记已结算状态
                $res5 = M('InviteLoginInfo')->where(['id' => $v['id']])->save(['status' => 1, 'update_time' => NOW_TIME]);

                if($res4 === false || $res5 === false) {
                    throw new Exception();
                }

                unset($where, $recordInfo);
            }

            M()->commit();
            echo 'ok';
        }catch(Exception $e) {
            M()->rollback();
            echo 'fail';
        }

        die;
    }


    /**
     * 修改是否开启审核配置
     */
    public function checkOn()
    {
        $type = I('type');
        $res = M('Config')->where(['sign'=>$type])->getField('config');
        $val = (int)!$res;
        $res = M('Config')->where(['sign'=>$type])->save(['config'=>$val]);
        if($res)
        {
            $this->ajaxReturn($val);
        }
    }

    /**
     * Liangzk 《Liangzk@qc.com》
     * date 2017-02-22
     * @param string $table_name
     * @param array  $data
     * @param string $field
     * @return bool|false|int
     */
    //批量修改  data二维数组 field关键字段  参考ci 批量修改函数 传参方式
    public function batch_update($table_name='',$data=array(),$field=''){
        if(!$table_name||!$data||!$field){
            return false;
        }else{
            $sql='UPDATE '.$table_name;
        }
        $con=array();
        $con_sql=array();
        $fields=array();
        foreach ($data as $key => $value) {
            $x=0;
            foreach ($value as $k => $v) {
                if($k!=$field&&!$con[$x]&&$x==0){
                    $con[$x]=" set {$k} = (CASE {$field} ";
                }elseif($k!=$field&&!$con[$x]&&$x>0){
                    $con[$x]="  {$k} = (CASE {$field} ";
                }
                if($k!=$field){
                    $temp=$value[$field];
                    $con_sql[$x].=   " WHEN '{$temp}' THEN '{$v}' ";
                    $x++;
                }
            }
            $temp=$value[$field];
            if(!in_array($temp,$fields)){
                $fields[]=$temp;
            }
        }
        $num=count($con)-1;
        foreach ($con as $key => $value) {
            foreach ($con_sql as $k => $v) {
                if($k==$key&&$key<$num){
                    $sql.=$value.$v.' end),';
                }elseif($k==$key&&$key==$num){
                    $sql.=$value.$v.' end)';
                }
            }
        }
        $str=implode(',',$fields);
        $sql.=" where {$field} in({$str})";
        $res=M($table_name)->execute($sql);
        return $res;
    }

    //获取资讯分类列表
    public function getPublishClass()
    {
        $class = getPublishClass(0);
        $data = [];
        foreach($class as $key=>$val)
        {
            if($val['level'] == '1'){
                if(!$data[$val['name']]) $data[$val['name']] = '';
            }
            if($val['level'] == '2')
            {
                $pName = $class[$val['pid']]['name'];
                $data[$pName][$val['name']] = '';
            }
            if($val['level'] == '3')
            {
                $pName = $class[$val['pid']]['name'];
                $gpName = $class[$class[$val['pid']]['pid']]['name'];
                $tmp = $data[$gpName][$pName];
                $tmp = explode(',',$tmp);
                $tmp[] = $val['name'];
                $tmp = implode(array_filter($tmp),',');
                $data[$gpName][$pName] = $tmp;
            }
        }
        $this->ajaxReturn(['data'=>$data]);
    }

    //将分类转换成id
    public function getClassId($type=1)
    {
        $class = getPublishClass(0);
        $first = I('first'.$type)?:'';
        $second = I('second'.$type)?:'';
        $third = I('third'.$type)?:'';
        $class_id = 0;
        if($first)
        {
            foreach ($class as $key=>$val)
            {
                if($val['level'] == 1 && $val['name'] == $first)
                {
                    $class_id = $key;
                    break;
                }
            }
        }
        if($second)
        {
            foreach ($class as $key=>$val)
            {
                if($val['level'] == 2 && $val['pid'] == $class_id && $val['name'] == $second)
                {
                    $class_id = $key;
                    break;
                }
            }
        }
        if($third)
        {
            foreach ($class as $key=>$val)
            {
                if($val['level'] == 3 && $val['pid'] == $class_id && $val['name'] == $third)
                {
                    $class_id = $key;
                    break;
                }
            }
        }
        return $class_id;
    }

    //将所选分类赋值给页面
    public function selectData($id,$type=0)
    {
        $class = getPublishClass(0);
        if($class[$id]['level'] == 1)
        {
            $data['first'] = $class[$id]['name'];
            if($type) unset($data['first']);
        }
        if($class[$id]['level'] == 2){
            $data['first'] = $class[$class[$id]['pid']]['name'];
            $data['second'] = $class[$id]['name'];
            if($type) unset($data['second']);
        }
        if($class[$id]['level'] == 3){
            $data['first'] = $class[$class[$class[$id]['pid']]['pid']]['name'];
            $data['second'] = $class[$class[$id]['pid']]['name'];
            $data['third'] = $class[$id]['name'];
            if($type) unset($data['third']);
        }
        $this->assign('selectData',$data);
    }
}