<?php

/**
 * 热更新
 * @author huangzl 2016.01.20
 */
class HotfixController extends CommonController
{
    public function index()
    {
        $map = ['sign' => ['LIKE','%Hotfix']];
        $list = $this->_list(CM('Config'),$map);


        foreach($list as $k => $v){
            $vv = json_decode($v['config'], true);

            $list[$k]['version']    = str_replace('Hotfix','',$v['sign']);
            $list[$k]['ios_config'] = $vv[2];
            $list[$k]['android_config'] = $vv[3];

            unset($list[$k]['sign']);
            unset($list[$k]['config']);
        }

        $this->assign('list',$list);
        $this->display();
    }

    public function doAdd(){
        $module   = strtolower(I('module'));

        $sign   = $module . 'Hotfix';
        $res    = M('Config')->where(['sign' => $sign])->find();

        if($res)
            $this->error('该版本已存在补丁，请在列表中更新');

        $config = ['2' => ['code' => $_REQUEST['iosHotfix'], 'desc' => I('iosDesc')], '3' => ['code' => $_REQUEST['androidHotfix'], 'desc' => I('androidDesc')]];

        $data   = ['sign' => $sign,'config' => json_encode($config)];
        $insert = M('Config')->add($data);

        if($insert)
            $this->success('添加成功！');

    }

    public function edit(){
        $list  = M('Config')->where(['id' => I('id')])->find();

        $list['version']    = str_replace('Hotfix', '', $list['sign']);
        $config             = json_decode($list['config'], true);

        $list['ios_config'] = $config[2];
        $list['android_config'] = $config[3];

        unset($list['sign']);

        $this->assign('list', $list);
        $this->display();
    }

    public function save(){
        $module = strtolower(I('module'));
        $sign   = $module . 'Hotfix';
        $config = ['2' =>['code' => $_REQUEST['iosHotfix'], 'desc' => I('iosDesc')], '3' => ['code' => $_REQUEST['androidHotfix'], 'desc' => I('androidDesc')]];
        $data   = ['sign' => $sign,'config' => json_encode($config)];

        $insert = M('Config')->where(['id' => I('id')])->save($data);

        if($insert)
            $this->success('更新成功！');
        else
            $this->error('内容没变动或者更新失败');
    }

    //安卓热修复列表
    public function androidHot()
    {
        $map = $this->_search('androidHot');
        $list = $this->_list(CM('androidHot'),$map);
        $this->display();
    }

    //安卓热修复统计列表
    public function androidCount()
    {
        $map = $this->_search('androidCount');
        $baseAppId = I('baseAppId');
        if($baseAppId != ''){
            $map['baseAppId'] = ['like','%'.$baseAppId.'%'];
        }
        $list = $this->_list(CM('androidCount'),$map);
        $this->display();
    }

    //安卓热修复编辑
    public function editHot()
    {
        $id = I('id');
        $hot = M('androidHot')->find($id);
        $data = json_decode($hot['data'],true);
        $this->assign('vo', $hot);
        $this->assign('data', $data);
        $this->display('addHot');
    }

    //安卓修复保存
    public function saveAndroid()
    {
        $id = I('id');

        $baseAppId = I('baseAppId');
        $isMustUpdate = I('isMustUpdate');
        $status    = I('status');
        $remark    = I('remark');
        $data      = I('data');

        $hot['add_time'] = NOW_TIME;
        $hot['status']   = $status;
        $hot['remark']   = $remark;
        $hot['isMustUpdate'] = $isMustUpdate;
        $hot['baseAppId'] = $baseAppId;

        for ($i=1; $i <=5 ; $i++) { 
            //是否有上传
            if (!empty($_FILES['patchUrl_'.$i]['tmp_name'])) 
            {
                //是否已上传，先删除
                if($data[$i]['patchUrl'] != '')
                {
                    $delPath = explode("?", $data[$i]['patchUrl'])[0];
                    D('Uploads')->deleteFile([str_replace('/Uploads', '', $delPath)]);
                }
                //上传文件
                $return = D('Uploads')->uploadFile("patchUrl_".$i, "androidhot", pathinfo($_FILES['patchUrl_'.$i]['name'])['filename']);
                if($return['status'] == 1){
                    $data[$i]['patchUrl'] = $return['url'];
                }  
            }
        }
        $hot['data'] = json_encode($data);

        if(!$id){
            $rs = M('androidHot')->add($hot);
        }else{
            $rs = M('androidHot')->where(['id'=>$id])->save($hot);
        }
        if($rs !== false){
            $this->success('保存成功！');
        }else{
            $this->error('保存失败！');
        }
    }

    //删除安卓热修复
    public function delHot()
    {
        $id = $_REQUEST['id'];
        if($id == '') $this->error('参数错误！');
        $hot = M('androidHot')->find($id);
        $data = json_decode($hot['data'],true);
        if(M('androidHot')->where(['id'=>$id])->delete()){
            foreach ($data as $k => $v) {
                if($v['patchUrl'] != ''){
                    $patchUrl = explode("?", $v['patchUrl'])[0];
                    $delPath[] = str_replace('/Uploads', '', $patchUrl);
                }
            }
            D('Uploads')->deleteFile($delPath);
            $this->success('删除成功！');
        }else{
            $this->error('删除失败！');
        }
    }

}

?>