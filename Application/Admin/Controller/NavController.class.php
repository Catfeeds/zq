<?php
/**
 * 筛选导航
 *
 * @author liuyi <718545204@qq.com>
 *
 * @since  2016-08-10
 */
use Think\Tool\Tool;
class NavController extends CommonController {
    /**
     * 筛选导航
     * @return string
    */
    public function index()
	{
        $map = $this->_search('nav');

        if (empty($map['type'])){
            $map['type'] = 3;//默认是首页导航列表
            $_REQUEST['type'] = 3;
        }
            
        if($map['type'] == 43)
            $sort = 'sign';
        else
            $sort = 'sort';
		$list = $this->_list(CM('Nav'),$map,$sort,true);
        foreach ($list  as $k => $v)
        {
            $list[$k]['icon']  = Tool::imagesReplace($v['icon']);
            $list[$k]['icon2'] = Tool::imagesReplace($v['icon2']);
        }
		$this->assign('list',$list);
		$this->display();
	}

    public function add()
    {
        $this->display('edit');
    }

	public function edit()
	{
		$id = I("id");
        $vo = M('Nav')->find($id);

        if (!$vo) $this->error('参数错误');

        $vo['icon']  = Tool::imagesReplace($vo['icon']);
        $vo['icon2'] = Tool::imagesReplace($vo['icon2']);

        $this->assign ('vo', $vo);
        $this->display();
	}

    public function save()
    {
        $id = I('id');
        $model = D('Nav');
        if(!$data = $model->create())
        {
            $this->error($model->getError());
        }

        if(! empty($id))//编辑
        {
            $res = $model->save();

            //是否有上传图标1
            if (!empty($_FILES['fileInput1']['tmp_name']))
            {
                //先删除原来图片
                $fileArr = array(
                    "/nav/{$id}.jpg",
                    "/nav/{$id}.gif",
                    "/nav/{$id}.png",
                    "/nav/{$id}.swf",
                );
                D('Uploads')->deleteFile($fileArr);
                //上传图片
                $return = D('Uploads')->uploadImg("fileInput1", "nav", $id);
                //修改路径
                if($return['status'] == 1)
                    M("nav")->where(['id'=>$id])->save(['icon'=>$return['url']]);
            }
            //是否有上传图标2
            if (!empty($_FILES['fileInput2']['tmp_name']))
            {
                //先删除原来图片
                $fileArr = array(
                    "/nav/{$id}".'_2'.".jpg",
                    "/nav/{$id}".'_2'.".gif",
                    "/nav/{$id}".'_2'.".png",
                    "/nav/{$id}".'_2'.".swf",
                );
                D('Uploads')->deleteFile($fileArr);
                //上传图片
                $return = D('Uploads')->uploadImg("fileInput2", "nav", $id.'_2');
                //修改路径
                if($return['status'] == 1)
                    M("nav")->where(['id'=>$id])->save(['icon2'=>$return['url']]);
            }
        }
        else//新增
        {
            //首页导航必须上传图标
            if ( $model->type == 3 && (empty($_FILES['fileInput1']['tmp_name']) || empty($_FILES['fileInput2']['tmp_name']) ))
            {
                $this->error('请上传导航图标');
            }

            $res = $model->add();

            //上传图标1
            if (! empty($_FILES['fileInput1']['tmp_name'])) {
                $return = D('Uploads')->uploadImg("fileInput1", "nav", $res);
                if($return['status'] == 1)
                    M("nav")->where(['id'=>$res])->save(['icon'=>$return['url']]);
            }

            //上传图标2
            if (! empty($_FILES['fileInput2']['tmp_name'])) {
                $return = D('Uploads')->uploadImg("fileInput2", "nav", $res.'_2');
                if($return['status'] == 1)
                    M("nav")->where(['id'=>$res])->save(['icon2'=>$return['url']]);
            }
        }
        if (false !== $res) {
            S('qqty_nav_list',null);
            //成功提示
            $this->success('保存成功!',cookie('_currentUrl_'));
        } else {
            //错误提示
            $this->error('保存失败!');
        }
    }
    /**
     * 删除指定记录
     *
     * @return string
     *
     */
    public function delete() {
        //删除指定记录
        $model = M("nav");
        if (!empty($model)) {
            $id = $_REQUEST ['id'];
            if (isset($id)) {
                $condition = array('id' => $id);
                if (false !== $model->where($condition)->delete()) {
                    //删除图片
                    $fileArr = array(
                        "/nav/{$id}.jpg",
                        "/nav/{$id}.gif",
                        "/nav/{$id}.png",
                        "/nav/{$id}.swf",
                        "/nav/{$id}".'_2'.".jpg",
                        "/nav/{$id}".'_2'.".gif",
                        "/nav/{$id}".'_2'.".png",
                        "/nav/{$id}".'_2'.".swf"
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
    
    //异步删除图片
    public function delPic(){
        $id = I('id');
        if(empty($id)){
            $this->error('参数错误!');
        }
        $num = I('num');
        if($num == 1){
            $fileArr = array(
                "/nav/{$id}.jpg",
                "/nav/{$id}.gif",
                "/nav/{$id}.png",
                "/nav/{$id}.swf",
            );
            $icon = 'icon';
        }elseif($num == 2){
            $fileArr = array(
                "/nav/{$id}".'_2'.".jpg",
                "/nav/{$id}".'_2'.".gif",
                "/nav/{$id}".'_2'.".png",
                "/nav/{$id}".'_2'.".swf"
            );
            $icon = 'icon2';
        }
        
        //执行删除
        $return = D('Uploads')->deleteFile($fileArr);
        if($return['status'] == 1){
            //删除路径
            if(M("nav")->where(['id'=>$id])->save([$icon=>NULL])){
                $this->success('删除成功！');
            }else{
                $this->error('删除失败！');
            }
        }else{
            $this->error('删除失败！');
        }
    }
}
