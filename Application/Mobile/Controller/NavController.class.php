<?php

/**
 * 赌博
 *
 * @author chenzj <443629770@qq.com>
 *
 * @since  2016-04-19
 */
class NavController extends CommonController
{

    //获取头部导航
    public function navHead($navId)
    {
        $data = M('Nav')->field('id,name,sort,ui_type_value as url')->where(['type'=>34,'status'=>1])->order('sort')->select();
        $this->assign('navHead',$data);
        $this->assign('thisNav',$navId);
    }

    //导航编辑页
    public function editNav()
    {
        $backUrl = $_SERVER['HTTP_REFERER']?:'//m.'.DOMAIN;
        $this->assign('backUrl',$backUrl);
        $this->display('Public/editNav');
    }

    //ajax获取导航配置
    public function getNavList()
    {
        if(!$tmp = S('editNavList'))
        {
            $tmp = [];
            $data = M('Nav')->field('name,ui_type_value as url,sign')->where(['type'=>34,'status'=>1,'sign'=>['exp','is not null']])->order('sort')->select();
            foreach($data as $key=>$val)
            {
                if($val['url'] == '') $val['url'] = '//m.'.DOMAIN;
                $tmp[] = $val;
            }
            S('editNavList',$tmp,5000);
        }
        $this->ajaxReturn(['code'=>200,'data'=>$tmp]);
    }
}