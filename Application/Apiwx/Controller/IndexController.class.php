<?php
/**
 * 首页其他
 * @author huangjiezhen <418832673@qq.com> 2015.12.25
 */

class IndexController extends PublicController
{
    //index
    public function index()
    {
        $this->ajaxReturn('hello,this is the api index');
    }

    //获取配置参数
    public function config()
    {
        $config = getConfig();
        $this->ajaxReturn(['config'=>$config]);
    }

    //规则帮助页面
    public function help()
    {
        if(iosCheck()){
            $this->display(T('Index/help_ios'));
        }else{
            $this->display(T('Index/help'));
        }
    }

    //服务协议页面
    public function agreement()
    {
        $this->pkg = $this->param['pkg'] ?: '';//现在有彩票分析大师版本

        if(iosCheck()){
            $this->display(T('Index/agreement_ios'));
        }else{
            $this->display(T('Index/agreement'));
        }
    }

    /**
     * 新手指导
     */
    public function noviceGuide()
    {
        $this->display('NoviceGuide/index');
    }

    /**
     * 新手指引的跳转
     */
    public function noviceGuideJump()
    {
        $num = $this->param['num'];
        $this->display('NoviceGuide/guide0'.$num);
    }

    /**
     * 关于我们
     */
    public function aboutUs(){
        $res = getWebConfig('aboutUs');
        $this->ajaxReturn(['result' => trim($res)]);
    }

    /**
     * 获得全部外链
     */
    public function getOutsideChain(){
        if($this->param['type'] != 'go')
            return false;

        $outsideChain = getOutsideChain();
        $this->ajaxReturn(['result' => $outsideChain]);
    }


}


 ?>