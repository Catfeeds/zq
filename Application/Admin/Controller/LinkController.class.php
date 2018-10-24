<?php
/**
 * 友情链接控制器
 *
 * @author dengweijun <406516482@qq.com>
 *
 * @since  2015-12-8
 */
class LinkController extends CommonController {

    public function edit() {
        $id = I('id');
        $vo = M("Link")->find($id);
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
        $model = D('Link');
        if (false === $model->create()) {
            $this->error($model->getError());
        }
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

    public function share()
    {
        $sign = 'share';
        $shareConf = M('config')->where(['sign'=>$sign])->find();
        $configArr = json_decode($shareConf['config'],true);
        if (IS_POST)
        {
            $config['sign'] = $sign;
            $share['shareTitle'] = I('shareTitle');
            //是否有上传
            if (!empty($_FILES['fileInput']['tmp_name']))
            {
                $fileArr = array(
                    "/common/share.jpg",
                    "/common/share.gif",
                    "/common/share.png",
                    "/common/share.swf",
                );
                //执行删除
                D('Uploads')->deleteFile($fileArr);
                $return = D('Uploads')->uploadImg("fileInput", "common",'share');
                if ($return['status'] == 0){
                    $this->error('图片上传失败!');
                }
                $share['img'] = $return['url'];
            }else{
                $share['img'] = $configArr['img'];
            }
            $config['config'] = json_encode($share);
            if(!$shareConf){
                //新增
                $rs = M('config')->add($config);
            }else{
                //修改
                $rs = M('config')->where(['sign'=>$sign])->save($config);
                if(!is_bool($rs)){
                    $rs = true;
                }
            }
            if ($rs)
                $this->success('修改成功');

            $this->error('修改失败!');
        }
        if(!empty($configArr['img'])){
            $titleimg = \Think\Tool\Tool::imagesReplace($configArr['img']);
        }else{
            $titleimg = '';
        }
        $this->assign('titleimg',$titleimg);
        $this->assign('config',$configArr);
        $this->display();
    }

    //异步删除图片
    public function delPic(){
        $id = I('id');
        if(empty($id))
        {
            $this->error('参数错误!');
        }
        //执行删除
        $fileArr = array(
            "/common/share.jpg",
            "/common/share.gif",
            "/common/share.png",
            "/common/share.swf",
        );
        //执行删除
        $return = D('Uploads')->deleteFile($fileArr);
        if($return['status'] == 1){
            $config = M('config')->where(['sign'=>'share'])->getField('config');
            $configArr = json_decode($config,true);
            unset($configArr['img']);
            $rs = M('config')->where(['sign'=>'share'])->save(['config'=>json_encode($configArr)]);
            $this->success('删除成功！');
        }else{
            $this->error('删除失败!');
        }
    }
    
    /**
     * 猎仇者SEO的友情链接
     * @User liangzk <liangzk@qc.com>
     * @2016-09-02
     */
    public function foxSeo()
    {
        $foxSeo = M('config')->where(['sign'=>'FoxSeo'])->getField('config');
        $this->assign('foxSeo',$foxSeo);
        $this->display();
    }
    /**
     * 猎仇者SEO的友情链接---保存操作
     * @User liangzk <liangzk@qc.com>
     * @2016-09-02
     */
    public function saveFoxSeo()
    {
        $foxSeo = I('content');
        $is_add = M('config')->where(['sign'=>'FoxSeo'])->getField('id');
        if($is_add){
            //修改
            $rs = M('config')->where(['sign'=>'FoxSeo'])->save(['config'=>$foxSeo]);
            if(!is_bool($rs)){
                $rs = true;
            }
        }else{
            //新增
            $config['sign'] = 'FoxSeo';
            $config['config'] = $foxSeo;
            $rs = M('config')->add($config);
        }
        if($rs){
            $this->success("保存成功！");
        }else{
            $this->error('保存失败！');
        }
    }
}