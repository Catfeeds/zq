<?php
/**
 * 足球联盟管理mongo
 */
use Think\Tool\Tool;
class FbUnionController extends CommonController
{
    public function index()
    {
        //列表过滤器，生成查询Map对象
        $map = $this->_search('Union');
        //手动获取列表
        $list = $this->_list(CM("Union"), $map);
        $mongo = mongoService();

        foreach ($list as $gk => $gv){
            $unionIds[] = (int)$gv['union_id'];
        }

        //mongo联盟数据
        if($unionIds){
            $_unions = $mongo->select(
                'fb_union',
                ['union_id' => ['$in' => $unionIds]],
                ['union_id', 'union_name_today', 'union_name', 'images', 'level', 'union_color', 'country_id'
                ]
            );
        }

        foreach ($_unions as $mgk => $mgv){
            $mgv['union_logo'] = !empty($mgv['images'][0]) ? str_replace('full/', 'https://img3.qqty.com/', $mgv['images'][0]['path']) : '';
            $unions[$mgv['union_id']] = $mgv;
            $country_ids[] = $mgv['country_id'];
        }

        //mongo国家数据
        if($country_ids){
            $_country = $mongo->select(
                'fb_country',
                ['country_id' => ['$in' => $country_ids]],
                ['country_id', 's_name', 't_name', 'e_name', 'images']
            );
        }


        foreach ($_country as $ck => $cv){
            $cv['con_logo'] = !empty($cv['images'][0]) ? str_replace('full/', 'https://img3.qqty.com/', $cv['images'][0]['path']) : '';
            $country[$cv['country_id']] = $cv;
        }

        //组装数据
        foreach ($list as $k => $v) {
            $union = $unions[$v['union_id']];
            $co = $country[$union['country_id']];
            $img = Tool::imagesReplace($v['img']);
            $list[$k]['img'] = $img ? $img : $union['union_logo'];
            $list[$k]['union_name'] = implode(',', $union['union_name'] ?: $union['union_name_today']);
            $list[$k]['level'] = $union['level'];
            $list[$k]['union_color'] = $union['union_color'];
            $list[$k]['country_id'] = $union['country_id'];

            $list[$k]['country_name'] = implode(',', [$co['s_name'], $co['t_name'], $co['e_name']]);
        }

        $this->assign('list', $list);
        $this->display();
    }

    public function add(){
    	$this->display('edit');
    }

    public function edit() {
        $id = I('id');
        $vo = M("Union")->find($id);
        if (!$vo){
            $this->error('参数错误');
        }
        $img = Tool::imagesReplace($vo['img']);
        $vo['img'] = $img ? $img : '';
        $this->assign('vo', $vo);
        $this->display();
    }

    /**
     * 保存/修改记录
     *
     * @return #
    */
    public function save(){
        $id = I('id', 'int');
        $model = D('Union');
        if (false === $model->create()) {
            $this->error($model->getError());
        }
        /*if (!empty($_FILES['fileInput']['tmp_name'])) {
            $filetype = pathinfo($_FILES["fileInput"]["name"], PATHINFO_EXTENSION);//获取后缀
        }*/
        if (empty($id)) {
            //为新增
            $rs = $model->add();
            //上传图片
            if (!empty($_FILES['fileInput']['tmp_name'])) {
                $return = D('Uploads')->uploadImg("fileInput", "union", $rs);
                //修改路径
                if($return['status'] == 1){
                    M("Union")->where(['id'=>$rs])->save(['img'=>$return['url']]);
                }
            }
        }else{
            //为修改
            $rs = $model->save();
            //是否有上传
            if (!empty($_FILES['fileInput']['tmp_name'])) {
                //先删除原来图片
                $fileArr = array(
                    "/union/{$id}.jpg",
                    "/union/{$id}.gif",
                    "/union/{$id}.png",
                    "/union/{$id}.swf",
                );
                D('Uploads')->deleteFile($fileArr);
                //上传图片
                $return = D('Uploads')->uploadImg("fileInput", "union", $id);
                //修改路径
                if($return['status'] == 1){
                    M("Union")->where(['id'=>$id])->save(['img'=>$return['url']]);
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
        if(empty($id)){
            $this->error('参数错误!');
        }
        $fileArr = array(
            "/union/{$id}.jpg",
            "/union/{$id}.gif",
            "/union/{$id}.png",
            "/union/{$id}.swf",
        );
        //执行删除
        $return = D('Uploads')->deleteFile($fileArr);
        if($return['status'] == 1){
            //删除路径
            if(M("union")->where(['id'=>$id])->save(['img'=>NULL])){
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
        $model = M("union");
        if (!empty($model)) {
            $id = $_REQUEST ['id'];
            if (isset($id)) {
                $condition = array('id' => $id);
                if (false !== $model->where($condition)->delete()) {
                    //删除图片
                    $fileArr = array(
                        "/union/{$id}.jpg",
                        "/union/{$id}.gif",
                        "/union/{$id}.png",
                        "/union/{$id}.swf",
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

}