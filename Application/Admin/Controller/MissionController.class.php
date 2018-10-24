<?php
/**
 * 积分任务系统的每日任务
 * @author liangzk <1343724998@qq.com>
 * @since 2016-06-21 v1.0
 */
use Think\Tool\Tool;
class MissionController extends CommonController
{

    /**
     * Index 首页
     */
    public function index()
    {

        $map = $this->_search('Mission');
        //判断是每天任务还是我的成就（每天任务：1，我的成就：2）
        $type = I('type') == 1 ? 1 : 2;
        $map['type'] = $type;//筛选
        $list = $this->_list(CM('Mission'),$map);
        //当前日期
        $todayTime = strtotime(date('Ymd'));
   
        foreach ($list as $key => $value)
        {
            //把服务器前缀拼上
            $list[$key]['img'] = Tool::imagesReplace($value['img']);
            if($type == 1){
                //今日完成次数
                $list[$key]['todayNum'] = M('missionLog')->where(['mid'=>$value['id'],'create_time'=>['gt',$todayTime]])->count();
            }
            //历史完成次数
            $list[$key]['AllNum'] = M('missionLog')->where(['mid'=>$value['id']])->count();
        }

        $this->assign('list',$list);
        $this->display();
    }
    /**
     * 修改、添加操作
     *
     */
    public function  edit()
    {
        $id = I('id');
        if(!empty($id))
        {
            $missionRes = M('Mission')->where(['id'=>$id])->find();
            if(!$missionRes)
            {
                $this->error('参数错误');
            }
            $missionRes['img'] = Tool::imagesReplace($missionRes['img']);
        }
        //获取宝箱名称和成就名称说明的配置
        $configSign=C('configSign');
        $this->assign('configSign',$configSign);
        $this->assign('missionRes',$missionRes);
        $this->display();

    }
    /**
     * 保存操作
     */
    //增加修改用户信息
    public function save(){
        $id = I('id');
        $model = D('Mission');

        if (false === $model->create())
        {
            $this->error($model->getError());
        }
        if (empty($id))
        {
            //为新增
            $rs = $model->add();
            if($rs)
            {
                //上传图片
                if (!empty($_FILES['fileInput']['tmp_name']))
                {
                    $return = D('Uploads')->uploadImg("fileInput", "mission", $rs);
                    if($return['status'] == 1)
                        M("Mission")->where(['id'=>$rs])->save(['img'=>$return['url']]);
                }
            }

        }else
        {
            //为修改
            $rs = $model->where(['id'=>$id])->save();

            if(!is_bool($rs))
            {
                $rs = true;
            }
            //是否有上传
            if (!empty($_FILES['fileInput']['tmp_name']))
            {
                //先删除原来图片
                $fileArr = array(
                    "/mission/{$id}.jpg",
                    "/mission/{$id}.gif",
                    "/mission/{$id}.png",
                    "/mission/{$id}.swf",
                );
                D('Uploads')->deleteFile($fileArr);
                //上传图片
                $return = D('Uploads')->uploadImg("fileInput", "mission", $id);
                //修改路径
                if($return['status'] == 1)
                    M("Mission")->where(['id'=>$id])->save(['img'=>$return['url']]);
            }
        }
        if ($rs)
        {
            //成功提示
            $this->success('保存成功!',cookie('_currentUrl_'));
        } else
        {
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
            "/mission/{$id}.jpg",
            "/mission/{$id}.gif",
            "/mission/{$id}.png",
            "/mission/{$id}.swf",
        );
        //执行删除
        $return = D('Uploads')->deleteFile($fileArr);
        if($return['status'] == 1){
            //删除路径
            if(M("Mission")->where(['id'=>$id])->save(['img'=>NULL])){
                $this->success('删除成功！');
            }else{
                $this->error('删除失败！');
            }
        }else{
            $this->error('删除失败！');
        }
    }

    /**
     * 积分任务配置
     */
    public function config(){
        $config = M('config')->where(['sign' => 'mission'])->getField('config');

        if(IS_POST){
            $point = I('point');
            $data['status'] = I('status');

            foreach($point as $k => $v){
                $data['dailySignIn'][$k+1] = $v;
            }

            //修改任务系统的选项状态值
            $rs1 =  M('config')->where(['sign' => 'mission'])->save(['config' => json_encode($data)]);
            $rs2 =  M('Mission')->where(['id' => ['gt', 0]])->save(['status' => $data['status']]);

            if ($rs1 !== false && $rs2 !== false){
                $this->success('修改成功');
            }else{
                $this->error('修改失败!');
            }
        }else{
            $this->assign('config', json_decode($config, true));
        }

        $this->display();
    }

}


?>