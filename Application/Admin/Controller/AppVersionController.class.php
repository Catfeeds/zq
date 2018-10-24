<?php
/**
 * app版本管理
 * @author huangjiezhen <418832673@qq.com> 2016.01.25
 */

class AppVersionController extends CommonController
{
    public function edit() {
        $id = I('id');
        $vo = M("AppVersion")->find($id);
        if (!$vo){
            $this->error('参数错误');
        }
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
        $model = D('AppVersion');
        if (false === $model->create()) {
            $this->error($model->getError());
        }
        $model->update_time = NOW_TIME;
        if (empty($id)) {
            //为新增
            $rs = $model->add();
        }else{
            //为修改
            $rs = $model->save();
        }
        if (false !== $rs) {
            //成功提示
            $this->success('保存成功!',cookie('_currentUrl_'));
        } else {
            //错误提示
            $this->error('保存失败!');
        }
    }

    //增加
    function insert()
    {
        $info = $info = $this->uploadApk();

        if(!is_array($info))
            $this->error($info);

        $_POST['app_url']     = $info['app_url']['savepath'].$info['app_url']['savename'];
        $_POST['update_time'] = NOW_TIME;

        parent::insert();
    }

    //编辑
    function update()
    {
        // 如果有上传修改apk包
        if ($_FILES['app_url']['size'] > 0)
        {
            $oldname = './Uploads'.I('post.app_url');

            if (file_exists($oldname)) //备份原文件
            {
                $bakname = $oldname.'bak';
                rename($oldname, $bakname);
            }

            $info = $this->uploadApk(); //上传新文件

            if(!is_array($info)) //上传失败
            {
                if (isset($bakname)) //还原原文件
                    rename($bakname, $oldname);

                $this->error($info);
            }

            if (isset($bakname)) //上传成功，删除备份文件
                unlink($bakname);

            $_POST['app_url'] = $info['app_url']['savepath'].$info['app_url']['savename'];
        }

        $_POST['update_time'] = NOW_TIME;

        parent::update();
    }

    //上传文件
    public function uploadApk()
    {
        $upload = new \Think\Upload();
        $upload->saveName = 'qqty' ;            //用文件名保存
        $upload->exts     = array('apk');   // 设置附件上传类型
        $upload->savePath = '/App/';        //'./Uploads/' 保存的根路径
        $upload->autoSub  = false;          //设置子目录
        $upload->replace  = true;           //存在同名文件是否是覆盖
        $info = $upload->upload();

        if(!$info)
            return $upload->getError();
        return $info;
    }
}