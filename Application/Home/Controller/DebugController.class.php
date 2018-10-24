<?php
/**
 * 测试控制器
 * @author chenzj <443629770@qq.com>
 * @since  2016-6-2
 */
use Think\Controller;
class DebugController extends Controller{
    //put your code here
    public function index(){
        session('chan','session');
        cookie('cook','cookie');
        echo '<a href="http://beta-dev.qqty.com/debug/test">点我点我</a>';
    }
    public function test(){
        var_dump(session('chan'));
        echo '<br/>';
        var_dump(cookie('cook'));
    }
   
}
