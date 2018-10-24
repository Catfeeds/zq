<?php
/**
 * 赛事视频集锦控制器
 *
 * @author dengweijun <406516482@qq.com>
 *
 * @since  2016-3-18
 */
use Think\Tool\Tool;
class HighlightsController extends CommonController {
    public $HighlightsClass = [];
    /**
    *构造函数
    *
    * @return  #
    */
    public function _initialize()
    {
        parent::_initialize();
        //获取分类
        $HighlightsClass = M('HighlightsClass')->where("status=1")->select();
        $this->HighlightsClass = $HighlightsClass;
        //引用Tree类
        $Class = Tool::getTree($HighlightsClass, $pid = 0, $col_id = 'id', $col_pid = 'pid', $col_cid = 'level');
        $this->assign ('HighlightsClass', $Class);
    }
    /**
     * Index页显示
     *
     */
    public function index() {
        //列表过滤器，生成查询Map对象
        $map = $this->_search('HighlightsView');
        if(I('is_home') == 1) {
            $map['is_home'] = 1;
            $isCheckOn = M('Config')->where(['sign'=>'videoCheck'])->getField('config');
            $this->assign('isCheckOn',$isCheckOn);
        }else{
            $map['status'] = ['lt',2];
        }
        //获取列表
        $list = $this->_list(CM("HighlightsView"), $map);
        $classArr = getVideoClass(0);
        foreach ($list as $k => $v) {
            foreach ($this->HighlightsClass as $kk => $vv) {
                if($v['class_id'] == $vv['id']){
                    $list[$k]['className'] = $vv['name'];
                }
            }
            $img = setImgThumb($v['img'],'200');
            $list[$k]['img']  = $img ? $img : '';
            $list[$k]['href'] = videoUrl($v,$classArr);
        }
        $this->assign('list', $list);
        $this->display();
    }

    public function add() {

        $this->display();
    }

    public function edit() {
        $id = I('id');
        $vo = M("Highlights")->find($id);
        if (!$vo){
            $this->error('参数错误');
        }
        $img = Tool::imagesReplace($vo['img']);
        $vo['nick_name'] = M("FrontUser")->where(['id'=>$vo['user_id']])->getField('nick_name');
        $vo['img'] = $img ? $img : '';
        $vo['label'] = explode(',',$vo['label']);
        $this->assign('vo', $vo);
        $this->display("add");
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

        $model = D('Highlights');
        if (!$data = $model->create()) {
            $this->error($model->getError());
        }
        //判断填写的标签是否存在
        $HotRes = M('HotKeyword')->where(['keyword'=>['in',$labelArr]])->getField('keyword',true);
        $tmp = [];
        foreach($labelArr as $val)
        {
            if(!in_array($val,$HotRes)) $tmp[] = $val;
        }
        if($tmp) $this->error('"'.implode(',',$tmp).'"标签不存在,请先添加');
        if($data['game_id'] != ''){
            if(empty($data['game_type'])){
                $this->error('请选择赛事类型！');
            }
        }
        if (empty($id)) {
            $model->label = $label;
            $model->add_time = time();

            //为新增
            $rs = $model->add();

            //上传图片
            if (!empty($_FILES['fileInput']['tmp_name'])) {
                $return = D('Uploads')->uploadImg("fileInput", "highlights", $rs,'',"[[600,600,\"{$rs}\"],[200,200,\"{$rs}_200\"]]");
                //修改路径
                if($return['status'] == 1){
                    M("Highlights")->where(['id'=>$rs])->save(['img'=>$return['url']]);
                }
            }
        }else{
            //为修改
            $model->label = $label;
            $rs = $model->save();
            //是否有上传
            if (!empty($_FILES['fileInput']['tmp_name'])) {
                //先删除原来图片
                $fileArr = array(
                    "/highlights/{$id}.jpg",
                    "/highlights/{$id}.gif",
                    "/highlights/{$id}.png",
                    "/highlights/{$id}.swf",
                    "/highlights/{$id}_200.jpg",
                    "/highlights/{$id}_200.gif",
                    "/highlights/{$id}_200.png",
                    "/highlights/{$id}_200.swf"
                );
                D('Uploads')->deleteFile($fileArr);
                //上传图片
                $return = D('Uploads')->uploadImg("fileInput", "highlights", $id,'',"[[600,600,\"{$id}\"],[200,200,\"{$id}_200\"]]");
                //修改路径 
                if($return['status'] == 1){
                    M("Highlights")->where(['id'=>$id])->save(['img'=>$return['url']]);
                }
            }
        }
        if (false !== $rs) {
            if(I('status') === '0') $this->publishForbid($id);
            //成功提示
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
            "/highlights/{$id}.jpg",
            "/highlights/{$id}.gif",
            "/highlights/{$id}.png",
            "/highlights/{$id}.swf"
        );
        //执行删除
        $return = D('Uploads')->deleteFile($fileArr);
        if($return['status'] == 1){
            //删除路径
            if( M("highlights")->where(['id'=>$id])->save(['img'=>NULL]) ){
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
        $model = M("Highlights");
        if (!empty($model)) {
            $id = $_REQUEST ['id'];
            if (isset($id)) {
                $condition = array('id' => $id);
                if (false !== $model->where($condition)->delete()) {
                    //删除图片
                    $fileArr = array(
                        "/highlights/{$id}.jpg",
                        "/highlights/{$id}.gif",
                        "/highlights/{$id}.png",
                        "/highlights/{$id}.swf"
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
     * 弹窗查找用户（前台用户）
     * return #
     */
    public function findFrontUser(){
        $val = I('val');
        $descript = I('descript', 0, 'intval');
        if(!$val){
            $this->error('请先选择视频分类！');
        }
        $map = $this->_search ("FrontUser");
        unset($map['descript']);

        if($val == 9999){
            $class_id = '9999';
        }else {
            $classArr = getVideoClass(0)[$val];
            if ($classArr['pid'] == 0) {
                $class_id = $classArr['id'];
            } else {
                $class_id = $classArr['pid'];
            }
        }

        switch ($class_id) {
            case '52':
                $map['expert_type'] = 1;//足球
                break;
            case '53':
                $map['expert_type'] = 2;//篮球
                break;
            case '54':
                $map['expert_type'] = 4;//综合
                break;
            case '55':
                $map['expert_type'] = 3;//电竞
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
        $classArr = getVideoClass(0)[$val];
        if($classArr['pid'] == 0){
            $class_id = $classArr['id'];
        }else{
            $class_id = $classArr['pid'];
        }
        switch ($class_id) {
            case '52':
                $map['expert_type'] = 1;//足球
                break;
            case '53':
                $map['expert_type'] = 2;//篮球
                break;
            case '54':
                $map['expert_type'] = 3;//电竞
                break;
            case '55':
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
        $list = M('Highlights')->where(['id'=>$id])->save(['status'=>'0']);

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
        M('Highlights')->where(['id'=>$id])->save(['is_audit'=>'2']);
        //发送站内消息
        $data = M('Highlights p')->field('p.title,f.is_robot,f.id')
            ->join('LEFT JOIN  qc_front_user f on f.id=p.user_id')
            ->where(['p.id'=>$id])
            ->find();
        if($data['is_robot'] != 1)
        {
            sendMsg($data['id'], $data['title'], "您发布的视频".$data['title']."未能达到标准，请按照发布规则撰写比赛，如有问题请联系客服，感谢您的支持");
        }
    }

}