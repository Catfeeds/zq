<?php
/**
 * 足球球队信息列表控制器
 *
 * @author dengweijun <406516482@qq.com>
 *
 * @since  2015-12-22
 */
use Think\Tool\Tool;
class TeamListController extends CommonController {

    protected $gameType;
    protected $mongoGTb;
    protected $mongoSTb;

    /**
     *构造函数
     *
     * @return  #
     */
    public function _initialize()
    {
        $this->gameType = I('gameType') ?: 1;
        $this->mongoGTb = $this->gameType == 2 ? 'fb_365_jb_team_match' : 'fb_365_jb_team_match';
        $this->mongoSTb = $this->gameType == 2 ? 'bk_game_schedule' : 'fb_game';
        parent::_initialize();
    }

    /**
     * Index页显示
     *
     */
    public function index() {
        //列表过滤器，生成查询Map对象
        $map = $this->_search('TeamListView');

        if(I('post.team_name')) $map['g.team_name'] = ['like', I('post.team_name').'%'];

        if(I('post.union_name')) $map['u.union_name'] = ['like', I('post.union_name').'%'];

        if(I('post.country_id')) $map['g.country_id'] = (int)I('post.country_id');

        if (I('post.logo') == 2)
        {
            $map['g.img_url'] = array('exp',' is not null');
        }
        elseif (I('post.logo') == 3)
        {
            $map['g.img_url'] = array('exp',' is null');
        }

        if(I('post.is_sub') == 1){
            $map['u.is_sub'] = ['in', [0, 1]];
        }else if(I('post.is_sub') == 2){
            $map['u.is_sub'] = 2;
        }else if(I('post.is_sub') == 3){
            $map['u.is_sub'] = 3;
        }else if(I('post.is_sub') == 4){
            $map['u.is_sub'] = ['not in', [0, 1, 2, 3]];
        }

        //手动获取列表
        $list = $this->_list(D("TeamListView"), $map);

        foreach ($list as $k => $v) {
            $teamLogo = Tool::imagesReplace($v['img_url']);
        	$list[$k]['img_url'] = $teamLogo ? $teamLogo : '/Public/Home/images/common/logo-default.jpg';
//            $list[$k]['country_name'] = M('country')->where(['country_id'=>$v['country_id']])->getField('country_name');
        }
        $this->assign('list', $list);
        $this->display();
    }
    //修改状态
    public function changeStatus(){
        $id = $_REQUEST ['id'];
        $status = $_REQUEST ['status'];
        $rs = M('gameTeam')->where(array('id'=>$id))->data(array('status'=>$status))->save();
        if($rs){
            $this->success('保存成功');
        }else{
            $this->error('保存失败');
        }
    }
    //修改Wifi推送
    public function changeWifi(){
        $id = $_REQUEST ['id'];
        $wifi= $_REQUEST ['wifi'];
        $rs = M('gameTeam')->where(array('id'=>$id))->data(array('wifi_push'=>$wifi))->save();
        if($rs){
            $this->success('保存成功');
        }else{
            $this->error('保存失败');
        }
    }
    // 批量修改Wifi推送
    public function changeWifiAll(){
        $id = $_REQUEST ['id'];
        $wifi= $_REQUEST ['wifi'];
        $idsArr = explode(',', $id);
        $condition = array ("id" => array ('in',$idsArr));
        $rs = M('gameTeam')->where($condition)->data(array('wifi_push'=>$wifi))->save();
        if($rs){
            $this->success('保存成功');
        }else{
            $this->error('保存失败');
        }
    }
    public function edit() {
        $id = I('id');
        $vo = D("TeamListView")->find($id);
        if (!$vo){
            $this->error('参数错误');
        }
        $teamLogo = Tool::imagesReplace($vo['img_url']);
        $vo['img_url'] = $teamLogo ? $teamLogo : '/Public/Home/images/common/logo-default.jpg';
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
        $model = D('gameTeam');
        if (false === $model->create()) {
            $this->error($model->getError());
        }
        //$imgname = $_FILES["fileInput"]["name"]; //获取上传的文件名称
        //$filetype = pathinfo($imgname, PATHINFO_EXTENSION);//获取后缀
        if (empty($id)) {
            //为新增
            $rs = $model->add();
            //上传图片
            if (!empty($_FILES['fileInput']['tmp_name'])) {
                $return = D('Uploads')->uploadImg("fileInput", "team,{$_POST['country_id']}", $_POST['team_id']);
                //修改路径
                if($return['status'] == 1){
                    M("gameTeam")->where(['id'=>$rs])->save(['img_url'=>$return['url']]);
                }
            }
        }else{
            //为修改
            $team = M("gameTeam")->find($id);
            $rs = $model->save();
            //是否有上传
            if (!empty($_FILES['fileInput']['tmp_name'])) {
                //先删除原来logo
                $fileArr = array(
                    "/team/{$team['country_id']}/{$team['team_id']}.jpg",
                    "/team/{$team['country_id']}/{$team['team_id']}.gif",
                    "/team/{$team['country_id']}/{$team['team_id']}.png",
                    "/team/{$team['country_id']}/{$team['team_id']}.swf"
                );
                D('Uploads')->deleteFile($fileArr);
                //上传logo
                $return = D('Uploads')->uploadImg("fileInput", "team,{$_POST['country_id']}", $_POST['team_id']);
                //修改路径
                if($return['status'] == 1){
                    M("gameTeam")->where(['id'=>$id])->save(['img_url'=>$return['url']]);
                }
            }
        }
        if (false !== $rs) {
            //成功提示
            $this->success('保存成功!');
        } else {
            //错误提示
            $this->error('保存失败!');
        }
    }
    //异步删除图片
    public function delPic(){
        $id = I('id');
        $team = M("gameTeam")->find($id);
        if(empty($team)){
            $this->error('参数错误!');
        }
        //删除logo
        $fileArr = array(
            "/team/{$team['country_id']}/{$team['team_id']}.jpg",
            "/team/{$team['country_id']}/{$team['team_id']}.gif",
            "/team/{$team['country_id']}/{$team['team_id']}.png",
            "/team/{$team['country_id']}/{$team['team_id']}.swf"
        );
        //执行删除
        $return = D('Uploads')->deleteFile($fileArr);
        if($return['status'] == 1){
            //删除路径
            if(M("gameTeam")->where(['id'=>$id])->save(['img_url'=>NULL])){
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
        $model = M("gameTeam");
        if (!empty($model)) {
            $id = $_REQUEST ['id'];
            if (isset($id)) {
                $condition = array('id' => $id);
                $team = M("gameTeam")->find($id);
                if (false !== $model->where($condition)->delete()) {
                    //删除logo
                    $fileArr = array(
                        "/team/{$team['country_id']}/{$team['team_id']}.jpg",
                        "/team/{$team['country_id']}/{$team['team_id']}.gif",
                        "/team/{$team['country_id']}/{$team['team_id']}.png",
                        "/team/{$team['country_id']}/{$team['team_id']}.swf"
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
        //删除指定记录
        $model = M("gameTeam");
        if (!empty($model)) {
            $ids = isset($_POST['id']) ? $_POST['id'] : null;
            if ($ids) {
                $idsArr = explode(',', $ids);
                $condition = array ("id" => array ('in',$idsArr));
                $teamArr = $model->where($condition)->select();
                if (false !== $model->where($condition)->delete()) {
                    //删除图片
                    foreach ($teamArr as $k => $v) {
                        $fileArr = array(
                            "/team/{$v['country_id']}/{$v['team_id']}.jpg",
                            "/team/{$v['country_id']}/{$v['team_id']}.gif",
                            "/team/{$v['country_id']}/{$v['team_id']}.png",
                            "/team/{$v['country_id']}/{$v['team_id']}.swf"
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

    /**
     * 足球球队关联管理
     * @return string
     */
    public function teamlink()
    {
        $map = $this->_search('Fb_teamlinkbet');
        unset($map['status']);
        //队名查找
        $team_name = I('team_name');
        if (!empty($single_title))
        {
            $map['team_name'] = ['Like',trim($team_name).'%'];
        }
        $count = M('FbTeamlinkbet')
            ->where($map)
            ->count('id');

        //获取每页显示的条数
        $pageNum = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
        //获取当前的页码
        $currentPage =! empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1;
        if ($count > 0)
        {
            //排序
            $list = M('FbTeamlinkbet')
                ->where($map)
                ->limit($pageNum*($currentPage-1),$pageNum)
                ->select();

        }

        $this->assign('list',$list);
        $this->assign ( 'totalCount', $count );//当前条件下数据的总条数
        $pageNum =empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
        $this->assign ( 'numPerPage', $pageNum ); //每页显示多少条
        $this->assign ( 'currentPage', !empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1);//当前页码
        $this->setJumpUrl();
        $this->display();
    }

    //编辑队名
    public function teamedit()
    {
        $id = I('id');
        if($id)
        {
            $vo = M('FbTeamlinkbet')->where(['id'=>$id])->find();
            $this->assign('vo',$vo);
        }
        $this->display();
    }

    //保存队名
    public function saveteam()
    {
        $data['team_name'] = I('team_name');
        $data['team_id'] = I('team_id');
        $data['team_name_bet'] = I('team_name_bet');
        $id = I('id');
        if($id)
        {
            $rs = M('FbTeamlinkbet')->where(['id'=>$id])->save($data);
        }else{
            $rs = M('FbTeamlinkbet')->add($data);
        }
        if($rs){
            $this->success('保存成功');
        }else{
            $this->error('保存失败');
        }
    }

    //删除
    public function delteam()
    {
        $id = $_GET['id'];
        $rs = M('FbTeamlinkbet')->where(['id'=>$id])->delete();
        if($rs){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }

    /**
     * 批量删除
     *
     * @return string
     *
     */
    public function delteamAll() {
        $id = I('id');
        if (isset ( $id )) {
            //拼接要删除的所有id
            $condition = array ("id" => array ('in', explode ( ',', $id ) ) );
            if (false !== M('FbTeamlinkbet')->where ( $condition )->delete ()) {
                $this->success ( '删除成功' );
            } else {
                $this->error ( '删除失败' );
            }
        } else {
            $this->error ( '非法操作' );
        }
    }



    public function wifi() {
        //列表过滤器，生成查询Map对象
        $map = $this->_search('TeamListView');

        if(I('post.team_name')) $map['g.team_name'] = ['like', I('post.team_name').'%'];

        if(I('post.union_name')) $map['u.union_name'] = ['like', I('post.union_name').'%'];

        if(I('post.country_id')) $map['g.country_id'] = (int)I('post.country_id');

        if(I('post.wifi_push') || I('post.wifi_push') == '0'){
            $map['wifi_push'] = (int)I('post.wifi_push');
        }else{
            $_REQUEST['wifi_push'] = null;
        }

        if (I('post.logo') == 2)
        {
            $map['g.img_url'] = array('exp',' is not null');
        }
        elseif (I('post.logo') == 3)
        {
            $map['g.img_url'] = array('exp',' is null');
        }

        if(I('post.is_sub') == 1){
            $map['u.is_sub'] = ['in', [0, 1]];
        }else if(I('post.is_sub') == 2){
            $map['u.is_sub'] = 2;
        }else if(I('post.is_sub') == 3){
            $map['u.is_sub'] = 3;
        }else if(I('post.is_sub') == 4){
            $map['u.is_sub'] = ['not in', [0, 1, 2, 3]];
        }

        //手动获取列表
        $list = $this->_list(D("TeamListView"), $map);
        foreach ($list as $k => $v) {
            $teamLogo = Tool::imagesReplace($v['img_url']);
            $list[$k]['img_url'] = $teamLogo ? $teamLogo : '/Public/Home/images/common/logo-default.jpg';
//            $list[$k]['country_name'] = M('country')->where(['country_id'=>$v['country_id']])->getField('country_name');
        }
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 足球球队关联管理
     * @return string
     */
    public function teamlinkM()
    {
        $mongo = mongoService();
        $team_name = I('team_name');
        $map = [];
        if($team_name)
        {
            $map = ['$or' => [
                ['jb_team_name' => new mongoRegex("/".$team_name.".*/")],
                ['bet_team_name' => new mongoRegex("/".$team_name.".*/")],
            ]];
        }

        $count = $mongo->count($this->mongoGTb, $map);

        //获取每页显示的条数
        $pageNum = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
        //获取当前的页码
        $currentPage =! empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1;
        if ($count > 0)
        {
            $skip = ($currentPage - 1) * $pageNum;
            $list = $mongo->select($this->mongoGTb, $map, [], [], $pageNum, $skip);
        }
        $this->assign('list',$list);
        $this->assign ( 'totalCount', $count );//当前条件下数据的总条数
        $pageNum =empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
        $this->assign ( 'numPerPage', $pageNum ); //每页显示多少条
        $this->assign ( 'currentPage', !empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1);//当前页码
        $this->setJumpUrl();
        $this->display('TeamList/teamlinkM');
    }
    //编辑队名
    public function teameditM()
    {
        $mongo = mongoService();
        $id = I('id');
        if($id)
        {
            $map['_id'] = $id;
            $data = $mongo->select($this->mongoGTb, $map)[0];
            $data['jb_team_name'] = implode(',',$data['jb_team_name']);
            $data['id'] = $data['_id'];
            $this->assign('vo',$data);
        }
        $this->display();
    }

    //保存队名
    public function saveteamM()
    {
        $mongo = mongoService();
        $data['jb_team_name'] = explode(',',I('jb_team_name'));
        $data['jb_team_id'] = I('jb_team_id');
        $data['bet_team_name'] = I('bet_team_name');
        $id = I('id');
        if($id)
        {
            $data['_id'] = $id;
        }else{
            $_id = $mongo->select($this->mongoGTb, ['jb_team_id'=>$data['jb_team_id']],['_id']);
            if($_id) $data['_id'] = $_id[0]['_id'];
        }
        $rs = $mongo->save($this->mongoGTb, $data,true);
        if($rs){
            $this->success('保存成功');
        }else{
            $this->error('保存失败');
        }
    }
    //删除
    public function delteamM()
    {
        $id = $_GET['id'];
        if(!$id)
            $arr = explode(',',$_POST['id']);
        else
            $arr[] = $id;
        $mongo = mongoService();
        foreach($arr as $val)
        {
            $map['jb_team_id'] = $val;
            $res = $mongo->delete($this->mongoGTb,$map,true,true);
        }
        if($res['ok']){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }
}