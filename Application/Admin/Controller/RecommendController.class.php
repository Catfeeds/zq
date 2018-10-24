<?php
/**
 * 推荐位列表控制器
 *
 * @author dengweijun <406516482@qq.com>
 *
 * @since  2016-3-14
 */
use Think\Tool\Tool;
class RecommendController extends CommonController {
    /**
     *构造函数
     *
     * @return  #
     */
    public function _initialize()
    {
        parent::_initialize();
        //获取分类
        $RecommendClass = M('RecommendClass')->where(array('status'=>1))->select();
        $this->assign('RecommendClass', $RecommendClass);
    }
    /**
     * Index页显示
     *
     */
    public function index() {
        //列表过滤器，生成查询Map对象
        $map = $this->_search('Recommend');
        //手动获取列表
        $list = $this->_list(D("RecommendView"), $map);
        foreach ($list as $k => $v) {
            $list[$k]['titleimg'] = Tool::imagesReplace($v['img']);
        }
        $this->assign('list', $list);
        $this->display();
    }

    public function edit() {
        $id = I('id');
        $vo = M("Recommend")->find($id);
        if (!$vo){
            $this->error('参数错误');
        }
        $vo['titleimg'] = Tool::imagesReplace($vo['img']);
        $this->assign('vo', $vo);
        $this->display("add");
    }

    /**
     * 保存/修改记录
     *
     * @return #
     */
    public function save(){
        $id = I('id');

        $model = D('Recommend');
        if (false === $model->create()) {
            $this->error($model->getError());
        }
        if (empty($id)) {
            //为新增
            $model->add_time = time();
            $rs = $model->add();
            //上传图片
            if (!empty($_FILES['fileInput']['tmp_name'])) {
                $return = D('Uploads')->uploadImg("fileInput", "recommend", '' ,$rs);
                if($return['status'] == 1)
                    M("Recommend")->where(['id'=>$rs])->save(['img'=>$return['url']]);
            }
        }else{
            //为修改
            $rs = $model->save();
            //是否有上传
            if (!empty($_FILES['fileInput']['tmp_name'])) {
                //先删除原来图片
                $fileArr = array("/recommend/{$id}");
                D('Uploads')->deleteFile($fileArr);
                //上传图片
                $return = D('Uploads')->uploadImg("fileInput", "recommend", '' ,$id);
                if($return['status'] == 1)
                    M("Recommend")->where(['id'=>$id])->save(['img'=>$return['url']]);
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
    //异步删除图片
    public function delPic(){
        $id = I('id');
        if(empty($id)){
            $this->error('参数错误!');
        }
        $fileArr = array("/recommend/{$id}");
        //执行删除
        $return = D('Uploads')->deleteFile($fileArr);
        if($return['status']==1){
            M("recommend")->where(['id'=>$id])->save(['img'=>NULL]);
            $this->success('删除成功！');
        }else{
            $this->error('删除失败！');
        }
    }
    //删除单个
    public function delete() {
        //删除指定记录
        $model = M("Recommend");
        if (!empty($model)) {
            $id = $_REQUEST ['id'];
            if (isset($id)) {
                $condition = array('id' => $id);
                if (false !== $model->where($condition)->delete()) {
                    //删除图片
                    $fileArr = array(
                        "/recommend/{$id}",
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
     * 添加删除操作  (多个删除)
     */
    public function delAll(){
        //删除指定记录
        $model = M("Recommend");
        if (!empty($model)) {
            $ids = isset($_POST['id']) ? $_POST['id'] : null;
            if ($ids) {
                $idsArr = explode(',', $ids);
                $condition = array ("id" => array ('in',$idsArr));
                if (false !== $model->where($condition)->delete()) {
                    //删除图片
                    foreach ($idsArr as $k => $v) {
                        $fileArr = array(
                            "/recommend/{$v}",
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

    public function HomeNews(){
        $HomeNews = M('config')->where(['sign'=>'HomeNews'])->getField('config');
        $this->assign('HomeNews',$HomeNews);
        if(S('HomeNews') !== false)
        {
            if(S('HomeNews') != session_id())
            {
                $this->assign('msg','该模块处于他人编辑状态,已被锁定,无法进行编辑操作!');
                $this->assign('isedit',1);
            }
        }else{
            S('HomeNews',session_id());
        }
        $this->display();
    }

    public function saveHomeNews(){
        if(S('HomeNews') !== false){
            if(S('HomeNews') != session_id())
            {
                $this->error('保存失败,该模块处于他人编辑状态,已被锁定,无法进行编辑操作!');
            }
        }
        $HomeNews = I('content');
        $is_add = M('config')->where(['sign'=>'HomeNews'])->getField('id');
        if($is_add){
            //修改
            $rs = M('config')->where(['sign'=>'HomeNews'])->save(['config'=>$HomeNews]);
            if(!is_bool($rs)){
                $rs = true;
            }
        }else{
            //新增
            $config['sign'] = 'HomeNews';
            $config['config'] = $HomeNews;
            $rs = M('config')->add($config);
        }
        if($rs){
            S('HomeNews',NULL);
            $this->success("保存成功！");
        }else{
            $this->error('保存失败！');
        }
    }

    public function editstatus()
    {
        S('HomeNews',NULL);
    }

    public function EuroNews(){
        $EuroNews = M('config')->where(['sign'=>'EuroNews'])->getField('config');
        $this->assign('EuroNews',$EuroNews);
        $this->display();
    }

    public function saveEuroNews(){
        $EuroNews = I('content2');
        $is_add = M('config')->where(['sign'=>'EuroNews'])->getField('id');
        if($is_add){
            //修改
            $rs = M('config')->where(['sign'=>'EuroNews'])->save(['config'=>$EuroNews]);
            if(!is_bool($rs)){
                $rs = true;
            }
        }else{
            //新增
            $config['sign'] = 'EuroNews';
            $config['config'] = $EuroNews;
            $rs = M('config')->add($config);
        }
        if($rs){
            $this->success("保存成功！");
        }else{
            $this->error('保存失败！');
        }
    }
    //奥运头条手写位
    public function OlympicNews(){
        if(IS_POST)
        {
            $OlympicNews = I('content3');
            $is_add = M('config')->where(['sign'=>'OlympicNews'])->getField('id');
            if($is_add){
                //修改
                $rs = M('config')->where(['sign'=>'OlympicNews'])->save(['config'=>$OlympicNews]);
                if(!is_bool($rs)){
                    $rs = true;
                }
            }else{
                //新增
                $config['sign'] = 'OlympicNews';
                $config['config'] = $OlympicNews;
                $rs = M('config')->add($config);
            }
            if($rs){
                $this->success("保存成功！");
            }else{
                $this->error('保存失败！');
            }
        }else{
            $OlympicNews = M('config')->where(['sign'=>'OlympicNews'])->getField('config');
            $this->assign('OlympicNews',$OlympicNews);
            $this->display();
        }
    }

    /**
     * @user liangzk <liangzk@qc.com>
     * @datetime 2016-08-11 15；32
     * 本期推荐
     * @version 2.0
     */
    public function period()
    {
        $this->assign('msite',getWebConfig('msite')['intro']);
        $this->display();
    }
    /**
     * @user liangzk <liangzk@qc.com>
     * @datetime 2016-08-11 15；32
     * 编辑链接
     * @version 2.0
     */
    public function urlSave()
    {
        $msite = I('msite');
        if (empty($msite)) $this->error('不能为空！');
        $data = ['config'=>json_encode(['intro'=>$msite])];
        $configRes = M('Config')->where(['sign'=>'msite'])->save($data);
        if (false !== $configRes)
        {
            $this->success('修改成功');
        }
        else
        {
            $this->error('修改失败');
        }
    }
    /**
     * @user liangzk <liangzk@qc.com>
     * @datetime 2016-08-19 9；32
     * 情报分析资讯手写位
     */
    public function intelligence()
    {
        $intellig = M('config')->where(['sign'=>'Intelligence'])->getField('config');
        $this->assign('intelligence',$intellig);
        $this->display();
    }
    public function saveIntellig()
    {
        $intellig = I('content');
        $is_add = M('config')->where(['sign'=>'Intelligence'])->getField('id');
        if($is_add){
            //修改
            $rs = M('config')->where(['sign'=>'Intelligence'])->save(['config'=>$intellig]);
            if(!is_bool($rs)){
                $rs = true;
            }
        }else{
            //新增
            $config['sign'] = 'Intelligence';
            $config['config'] = $intellig;
            $rs = M('config')->add($config);
        }
        if($rs){
            $this->success("保存成功！");
        }else{
            $this->error('保存失败！');
        }
    }

    //首页资讯手推位
    public function news_shouye(){
        $sign = 'news_shouye';
        $config = getWebConfig($sign);

        if(IS_POST){
            $param = I('param');
            $data = json_encode($param);

            if(!empty($config)){
                $rs = M('config')->where(['sign'=>$sign])->save(['config'=>$data]);
            }else{
                $arr['sign']   = $sign;
                $arr['config'] = $data;
                $rs = M('config')->add($arr);
            }

            if($rs){
                S('web_index_news_one', null);
                $this->success("保存成功！");
            }else{
                $this->error('保存失败！');
            }
        }
        $this->assign('config',$config);
        $this->display();
    }

    //英超资讯手推位
    public function news_yingchao(){
        $sign = 'news_yingchao';
        $config = getWebConfig($sign);

        if(IS_POST){
            $param = I('param');
            $data = json_encode($param);

            if(!empty($config)){
                $rs = M('config')->where(['sign'=>$sign])->save(['config'=>$data]);
            }else{
                $arr['sign']   = $sign;
                $arr['config'] = $data;
                $rs = M('config')->add($arr);
            }

            if($rs){
                S('special_'.$sign, null);
                $this->success("保存成功！");
            }else{
                $this->error('保存失败！');
            }
        }
        $this->assign('config',$config);
        $this->display();
    }

    /**
     * 专家说彩手写位
     */
    public function news_zhuanjia(){
        $sign = 'news_zhuanjia';
        $config = getWebConfig($sign);

        if(IS_POST){
            $param = I('param');
            $data = json_encode($param);

            if(!empty($config)){
                $rs = M('config')->where(['sign'=>$sign])->save(['config'=>$data]);
            }else{
                $arr['sign']   = $sign;
                $arr['config'] = $data;
                $rs = M('config')->add($arr);
            }
            if($rs){
                S('web_YpRadar_HomeNews', null);
                $this->success("保存成功！");
            }else{
                $this->error('保存失败！');
            }
        }
        $this->assign('config',$config);
        $this->display();
    }

    /**
     * 意甲手写位
     */
    public function news_yijia(){
        $sign = 'news_yijia';
        $config = getWebConfig($sign);

        if(IS_POST){
            $param = I('param');
            $data = json_encode($param);

            if(!empty($config)){
                $rs = M('config')->where(['sign'=>$sign])->save(['config'=>$data]);
            }else{
                $arr['sign']   = $sign;
                $arr['config'] = $data;
                $rs = M('config')->add($arr);
            }

            if($rs){
                S('special_'.$sign, null);
                $this->success("保存成功！");
            }else{
                $this->error('保存失败！');
            }
        }
        $this->assign('config',$config);
        $this->display();
    }

    /**
     * 欧冠手写位
     */
    public function news_ouguan(){
        $sign = 'news_ouguan';
        $config = getWebConfig($sign);

        if(IS_POST){
            $param = I('param');
            $data = json_encode($param);

            if(!empty($config)){
                $rs = M('config')->where(['sign'=>$sign])->save(['config'=>$data]);
            }else{
                $arr['sign']   = $sign;
                $arr['config'] = $data;
                $rs = M('config')->add($arr);
            }

            if($rs){
                S('special_'.$sign, null);
                $this->success("保存成功！");
            }else{
                $this->error('保存失败！');
            }
        }
        $this->assign('config',$config);
        $this->display();
    }



    /**
     * 亚冠手写位
     */
    public function news_yaguan(){
        $sign = 'news_yaguan';
        $config = getWebConfig($sign);

        if(IS_POST){
            $param = I('param');
            $data = json_encode($param);

            if(!empty($config)){
                $rs = M('config')->where(['sign'=>$sign])->save(['config'=>$data]);
            }else{
                $arr['sign']   = $sign;
                $arr['config'] = $data;
                $rs = M('config')->add($arr);
            }

            if($rs){
                S('special_'.$sign, null);
                $this->success("保存成功！");
            }else{
                $this->error('保存失败！');
            }
        }
        $this->assign('config',$config);
        $this->display();
    }

    /**
     * snooker手写位
     */
    public function news_snooker(){
        $sign = 'news_snooker';
        $config = getWebConfig($sign);

        if(IS_POST){
            $param = I('param');
            $data = json_encode($param);

            if(!empty($config)){
                $rs = M('config')->where(['sign'=>$sign])->save(['config'=>$data]);
            }else{
                $arr['sign']   = $sign;
                $arr['config'] = $data;
                $rs = M('config')->add($arr);
            }

            if($rs){
                S('special_'.$sign, null);
                $this->success("保存成功！");
            }else{
                $this->error('保存失败！');
            }
        }
        $this->assign('config',$config);
        $this->display();
    }

    /**
     * nfl手写位
     */
    public function news_nfl(){
        $sign = 'news_nfl';
        $config = getWebConfig($sign);

        if(IS_POST){
            $param = I('param');
            $data = json_encode($param);

            if(!empty($config)){
                $rs = M('config')->where(['sign'=>$sign])->save(['config'=>$data]);
            }else{
                $arr['sign']   = $sign;
                $arr['config'] = $data;
                $rs = M('config')->add($arr);
            }

            if($rs){
                S('special_'.$sign, null);
                $this->success("保存成功！");
            }else{
                $this->error('保存失败！');
            }
        }
        $this->assign('config',$config);
        $this->display();
    }

    /**
     * 西甲手写位
     */
    public function news_xijia(){
        $sign = 'news_xijia';
        $config = getWebConfig($sign);

        if(IS_POST){
            $param = I('param');
            $data = json_encode($param);

            if(!empty($config)){
                $rs = M('config')->where(['sign'=>$sign])->save(['config'=>$data]);
            }else{
                $arr['sign']   = $sign;
                $arr['config'] = $data;
                $rs = M('config')->add($arr);
            }

            if($rs){
                S('special_'.$sign, null);
                $this->success("保存成功！");
            }else{
                $this->error('保存失败！');
            }
        }
        $this->assign('config',$config);
        $this->display();
    }

    /**
     * 德甲手写位
     */
    public function news_dejia(){
        $sign = 'news_dejia';
        $config = getWebConfig($sign);

        if(IS_POST){
            $param = I('param');
            $data = json_encode($param);

            if(!empty($config)){
                $rs = M('config')->where(['sign'=>$sign])->save(['config'=>$data]);
            }else{
                $arr['sign']   = $sign;
                $arr['config'] = $data;
                $rs = M('config')->add($arr);
            }

            if($rs){
                S('special_'.$sign, null);
                $this->success("保存成功！");
            }else{
                $this->error('保存失败！');
            }
        }
        $this->assign('config',$config);
        $this->display();
    }

    /**
     * lol手写位
     */
    public function news_lol(){
        $sign = 'news_lol';
        $config = getWebConfig($sign);

        if(IS_POST){
            $param = I('param');
            $data = json_encode($param);

            if(!empty($config)){
                $rs = M('config')->where(['sign'=>$sign])->save(['config'=>$data]);
            }else{
                $arr['sign']   = $sign;
                $arr['config'] = $data;
                $rs = M('config')->add($arr);
            }

            if($rs){
                S('special_'.$sign, null);
                $this->success("保存成功！");
            }else{
                $this->error('保存失败！');
            }
        }
        $this->assign('config',$config);
        $this->display();
    }

    /**
     * dota2手写位
     */
    public function news_dota2(){
        $sign = 'news_dota2';
        $config = getWebConfig($sign);

        if(IS_POST){
            $param = I('param');
            $data = json_encode($param);

            if(!empty($config)){
                $rs = M('config')->where(['sign'=>$sign])->save(['config'=>$data]);
            }else{
                $arr['sign']   = $sign;
                $arr['config'] = $data;
                $rs = M('config')->add($arr);
            }

            if($rs){
                S('special_'.$sign, null);
                $this->success("保存成功！");
            }else{
                $this->error('保存失败！');
            }
        }
        $this->assign('config',$config);
        $this->display();
    }

    /**
     * 网球手写位
     */
    public function news_tennis(){
        $sign = 'news_tennis';
        $config = getWebConfig($sign);

        if(IS_POST){
            $param = I('param');
            $data = json_encode($param);

            if(!empty($config)){
                $rs = M('config')->where(['sign'=>$sign])->save(['config'=>$data]);
            }else{
                $arr['sign']   = $sign;
                $arr['config'] = $data;
                $rs = M('config')->add($arr);
            }

            if($rs){
                S('special_'.$sign, null);
                $this->success("保存成功！");
            }else{
                $this->error('保存失败！');
            }
        }
        $this->assign('config',$config);
        $this->display();
    }

    /**
     * 绝地求生手写位
     */
    public function news_pubg(){
        $sign = 'news_pubg';
        $config = getWebConfig($sign);

        if(IS_POST){
            $param = I('param');
            $data = json_encode($param);

            if(!empty($config)){
                $rs = M('config')->where(['sign'=>$sign])->save(['config'=>$data]);
            }else{
                $arr['sign']   = $sign;
                $arr['config'] = $data;
                $rs = M('config')->add($arr);
            }

            if($rs){
                S('special_'.$sign, null);
                $this->success("保存成功！");
            }else{
                $this->error('保存失败！');
            }
        }
        $this->assign('config',$config);
        $this->display();
    }

    /**
     * 棒球手写位
     */
    public function news_baseball(){
        $sign = 'news_baseball';
        $config = getWebConfig($sign);

        if(IS_POST){
            $param = I('param');
            $data = json_encode($param);

            if(!empty($config)){
                $rs = M('config')->where(['sign'=>$sign])->save(['config'=>$data]);
            }else{
                $arr['sign']   = $sign;
                $arr['config'] = $data;
                $rs = M('config')->add($arr);
            }

            if($rs){
                S('special_'.$sign, null);
                $this->success("保存成功！");
            }else{
                $this->error('保存失败！');
            }
        }
        $this->assign('config',$config);
        $this->display();
    }


    /**
     * 中超手写位
     */
    public function news_zhongchao(){
        $sign = 'news_zhongchao';
        $config = getWebConfig($sign);

        if(IS_POST){
            $param = I('param');
            $data = json_encode($param);

            if(!empty($config)){
                $rs = M('config')->where(['sign'=>$sign])->save(['config'=>$data]);
            }else{
                $arr['sign']   = $sign;
                $arr['config'] = $data;
                $rs = M('config')->add($arr);
            }

            if($rs){
                S('special_'.$sign, null);
                $this->success("保存成功！");
            }else{
                $this->error('保存失败！');
            }
        }
        $this->assign('config',$config);
        $this->display();
    }

    /**
     * 王者荣耀写位
     */
    public function news_pvp(){
        $sign = 'news_pvp';
        $config = getWebConfig($sign);

        if(IS_POST){
            $param = I('param');
            $data = json_encode($param);

            if(!empty($config)){
                $rs = M('config')->where(['sign'=>$sign])->save(['config'=>$data]);
            }else{
                $arr['sign']   = $sign;
                $arr['config'] = $data;
                $rs = M('config')->add($arr);
            }

            if($rs){
                S('special_'.$sign, null);
                $this->success("保存成功！");
            }else{
                $this->error('保存失败！');
            }
        }
        $this->assign('config',$config);
        $this->display();
    }

    /**
     * nba手写位
     */
    public function news_nba(){
        $sign = 'news_nba';
        $config = getWebConfig($sign);

        if(IS_POST){
            $param = I('param');
            $data = json_encode($param);

            if(!empty($config)){
                $rs = M('config')->where(['sign'=>$sign])->save(['config'=>$data]);
            }else{
                $arr['sign']   = $sign;
                $arr['config'] = $data;
                $rs = M('config')->add($arr);
            }

            if($rs){
                S('special_'.$sign, null);
                $this->success("保存成功！");
            }else{
                $this->error('保存失败！');
            }
        }
        $this->assign('config',$config);
        $this->display();
    }

    /**
     * cba手写位
     */
    public function news_cba(){
        $sign = 'news_cba';
        $config = getWebConfig($sign);

        if(IS_POST){
            $param = I('param');
            $data = json_encode($param);

            if(!empty($config)){
                $rs = M('config')->where(['sign'=>$sign])->save(['config'=>$data]);
            }else{
                $arr['sign']   = $sign;
                $arr['config'] = $data;
                $rs = M('config')->add($arr);
            }

            if($rs){
                S('special_'.$sign, null);
                $this->success("保存成功！");
            }else{
                $this->error('保存失败！');
            }
        }
        $this->assign('config',$config);
        $this->display();
    }

    /**
     * 乒乓球手写位
     */
    public function news_pingpong(){
        $sign = 'news_pingpong';
        $config = getWebConfig($sign);

        if(IS_POST){
            $param = I('param');
            $data = json_encode($param);

            if(!empty($config)){
                $rs = M('config')->where(['sign'=>$sign])->save(['config'=>$data]);
            }else{
                $arr['sign']   = $sign;
                $arr['config'] = $data;
                $rs = M('config')->add($arr);
            }

            if($rs){
                S('special_'.$sign, null);
                $this->success("保存成功！");
            }else{
                $this->error('保存失败！');
            }
        }
        $this->assign('config',$config);
        $this->display();
    }

    /**
     * 排球手写位
     */
    public function news_vollyball(){
        $sign = 'news_vollyball';
        $config = getWebConfig($sign);

        if(IS_POST){
            $param = I('param');
            $data = json_encode($param);

            if(!empty($config)){
                $rs = M('config')->where(['sign'=>$sign])->save(['config'=>$data]);
            }else{
                $arr['sign']   = $sign;
                $arr['config'] = $data;
                $rs = M('config')->add($arr);
            }

            if($rs){
                S('special_'.$sign, null);
                $this->success("保存成功！");
            }else{
                $this->error('保存失败！');
            }
        }
        $this->assign('config',$config);
        $this->display();
    }

    /**
     * 法甲手写位
     */
    public function news_ligue1(){
        $sign = 'news_ligue1';
        $config = getWebConfig($sign);

        if(IS_POST){
            $param = I('param');
            $data = json_encode($param);

            if(!empty($config)){
                $rs = M('config')->where(['sign'=>$sign])->save(['config'=>$data]);
            }else{
                $arr['sign']   = $sign;
                $arr['config'] = $data;
                $rs = M('config')->add($arr);
            }

            if($rs){
                S('special_'.$sign, null);
                $this->success("保存成功！");
            }else{
                $this->error('保存失败！');
            }
        }
        $this->assign('config',$config);
        $this->display();
    }

    /**
     * 2018世界杯手写位
     */
    public function news_2018worldcup(){
        $sign = 'news_2018worldcup';
        $config = getWebConfig($sign);

        if(IS_POST){
            $param = I('param');
            $data = json_encode($param);

            if(!empty($config)){
                $rs = M('config')->where(['sign'=>$sign])->save(['config'=>$data]);
            }else{
                $arr['sign']   = $sign;
                $arr['config'] = $data;
                $rs = M('config')->add($arr);
            }

            if($rs){
                S('special_'.$sign, null);
                $this->success("保存成功！");
            }else{
                $this->error('保存失败！');
            }
        }
        $this->assign('config',$config);
        $this->display();
    }

    /**
     * 五洲手写位
     */
    public function news_wuzhou(){
        $sign = 'news_wuzhou';
        $config = getWebConfig($sign);

        if(IS_POST){
            $param = I('param');
            $data = json_encode($param);

            if(!empty($config)){
                $rs = M('config')->where(['sign'=>$sign])->save(['config'=>$data]);
            }else{
                $arr['sign']   = $sign;
                $arr['config'] = $data;
                $rs = M('config')->add($arr);
            }

            if($rs){
                S('special_'.$sign, null);
                $this->success("保存成功！");
            }else{
                $this->error('保存失败！');
            }
        }
        $this->assign('config',$config);
        $this->display();
    }

}