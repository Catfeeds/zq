<?php
use Think\Tool\Tool;
class ChatGiftController extends CommonController {

    public function index(){
        //列表过滤器，生成查询Map对象
        $map = $this->_search('ChatGift');
        //手动获取列表
        $list = $this->_list(CM("ChatGift"), $map, 'id');
        foreach ($list as $k => $v) {
            $list[$k]['img'] = Tool::imagesReplace($v['img']);
        }
        $this->assign('list', $list);
        $this->display();
    }

    public function add(){
        $this->display();
    }

    /**
     * 保存/修改记录
     *
     * @return #
     */
    public function save(){
        $id = I('id', 'int');
        $_POST['online_time'] = strtotime(I("online_time"));//转化上架设定开始时间
        $_POST['end_time'] = strtotime(I("end_time"));//转化上架到期时间
        $_POST['add_time'] = time();
        $_POST['update_time'] = time();

        if($_POST['online_time'] > $_POST['end_time']){
            $this->error('开始时间必须小于结束时间!');
            exit;
        }

        $model = D('ChatGift');
        if (false === $model->create()) {
            $this->error($model->getError());
        }
        if (empty($id)) {
            if (empty($_FILES['fileInput']['tmp_name'])) {
                $this->error('请上传图片!');
                exit;
            }

            if (empty($_FILES['zip_file']['tmp_name'])) {
                $this->error('请上传压缩包');
                exit;
            }

            //为新增
            $rs = $model->add();
            //上传图片
            $return = D('Uploads')->uploadImg("fileInput", "chatgift", '' ,$rs);
            if($return['status'] == 1)
                M('ChatGift')->where(['id'=>$rs])->save(['img'=>$return['url']]);

            //上传文件
            $return = D('Uploads')->uploadFile("zip_file", "giftzip", '' ,$rs);
            if($return['status'] == 1)
                M('ChatGift')->where(['id'=>$rs])->save(['zip_file'=>$return['url']]);
        }else{
            //为修改
            $rs = $model->save();
            //是否有上传
            if (!empty($_FILES['fileInput']['tmp_name'])) {
                //先删除原来广告图
                $fileArr = array("/chatgift/{$id}");
                D('Uploads')->deleteFile($fileArr);
                //上传图片
                $return = D('Uploads')->uploadImg("fileInput", "chatgift", '' ,$id);
                if($return['status'] == 1)
                    M('ChatGift')->where(['id'=>$id])->save(['img'=>$return['url']]);
            }

            if (!empty($_FILES['zip_file']['tmp_name'])) {
                $fileArr = array("/giftzip/{$id}");
                D('Uploads')->deleteFile($fileArr);
                //上传文件
                $return2 = D('Uploads')->uploadFile("zip_file", "giftzip", '' ,$id);
                    M('ChatGift')->where(['id'=>$id])->save(['zip_file'=>$return2['url']]);
            }
        }
        if (false !== $rs) {
            //成功提示
            $this->success('保存成功!',cookie('_currentUrl_'));
        } else {
            //错误提示
            $this->error('保存失败!');
        }
    }

    public function edit() {
        $id = I('id');
        $vo = M("ChatGift")->find($id);
        if (!$vo){
            $this->error('参数错误');
        }
        $vo['img'] = Tool::imagesReplace($vo['img']);
        $vo['zip_file'] = Tool::imagesReplace($vo['zip_file']);
        $this->assign('vo', $vo);
        $this->display("add");
    }

    //删除单个
    public function delete() {
        //删除指定记录
        $model = M("ChatGift");
        if (!empty($model)) {
            $id = $_REQUEST ['id'];
            if (isset($id)) {
                $condition = array('id' => $id);
                if (false !== $model->where($condition)->delete()) {
                    //删除图片
                    $fileArr = array(
                        "/chatgift/{$id}",
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
        $model = M("ChatGift");
        if (!empty($model)) {
            $ids = isset($_POST['id']) ? $_POST['id'] : null;
            if ($ids) {
                $idsArr = explode(',', $ids);
                $condition = array ("id" => array ('in',$idsArr));
                if (false !== $model->where($condition)->delete()) {
                    //删除图片
                    foreach ($idsArr as $k => $v) {
                        $fileArr = array(
                            "/adver/{$v}",
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

}