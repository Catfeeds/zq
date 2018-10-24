<?php
/**
 * 摇一摇控制器
 *
 * @since  2017-3-9
 */
use Think\Tool\Tool;
class ShakeController extends CommonController {

    public function index() {
        $shake = M('Config')->where(['sign' => 'shake'])->find();
        $vo = json_decode($shake['config'], true);
        $vo['sign'] = $shake['sign'];
        $vo['bg_logo'] = Tool::imagesReplace($vo['bg_logo']);;
        $this->assign('vo', $vo);
        $this->display();
    }

    /**
     * 保存/修改记录
     *
     * @return #
    */
    public function save(){
        $sign      = I('sign');
        $over_time = strtotime(I('over_time')) + 86399;
        if (empty($sign)) {
            if (empty($_FILES['fileInput']['tmp_name'])) {
                $this->error('请上传背景图!');
                exit;
            }

            //上传图片
            $saveData =[];
            if (!empty($_FILES['fileInput']['tmp_name'])) {
                $return = D('Uploads')->uploadImg("fileInput", "shake" , getRandStr(4).time());
                if($return['status'] == 1)
                    $saveData['bg_logo'] = $return['url'];
            }

            $saveData['over_time']  = $over_time;
            $saveData['create_time']= NOW_TIME;
            $saveData['nums']= 3000;
            $saveData['price']      = I('price');
            $saveData2['sign']      = 'shake';
            $saveData2['config']    = json_encode($saveData);

            //为新增
            $rs = M('Config')->add($saveData2);
        }else{
            $res = M('Config')->where(['sign' => 'shake'])->find();
            $shake = json_decode($res['config'], true);
            $saveData['get_logo'] = $shake['get_logo'];
            $saveData['bg_logo'] = $shake['bg_logo'];
            $saveData['create_time'] = $shake['create_time'];
            $saveData['nums'] = $shake['nums'];

            //是否有上传
            if (!empty($_FILES['fileInput']['tmp_name'])) {
                //先删除原来广告图
                $bg_logo = explode('?',str_replace('/Uploads','',$shake['bg_logo']))[0];
                D('Uploads')->deleteFile([$bg_logo]);
                //上传图片
                $return = D('Uploads')->uploadImg("fileInput", "shake" ,'');
                if($return['status'] == 1)
                    $saveData['bg_logo'] = $return['url'];
            }

            $saveData['over_time']  = $over_time;
            $saveData['price']      = I('price');
            $saveData2['sign']      = 'shake';

            $saveData2['config']    = json_encode($saveData);

            $rs = M('Config')->where(['sign' => 'shake'])->save($saveData2);
        }

        if (false !== $rs) {
            //成功提示
            $this->success('保存成功!',cookie('_currentUrl_'));
        } else {
            //错误提示
            $this->error('保存失败!');
        }
    }

}