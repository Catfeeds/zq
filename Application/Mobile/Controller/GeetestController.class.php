<?php
// 指定允许其他域名访问
header('Access-Control-Allow-Origin:*');
// 响应类型
header('Access-Control-Allow-Methods:*');
// 响应头设置
header('Access-Control-Allow-Headers:x-requested-with,content-type');
/**
 * 极验接口
 * @author liuweitao <906742852@qq.com>
 * @since  2018-05-04
 */
use Think\Controller;
use Think\Tool\Tool;
class GeetestController extends CommonController {

    public function getKey()
    {
        $res = D('Geetest')->getKey();
        $this->ajaxReturn($res);
    }

    public function validatesKey()
    {
        $res = D('Geetest')->validatesKey();
        $this->ajaxReturn($res);
    }
}