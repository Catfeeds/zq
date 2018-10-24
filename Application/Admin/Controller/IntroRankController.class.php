<?php

/**
 * 产品推荐列表
 *
 * @author liuweitao <cytusc@foxmail.com>
 *
 * @since
 */
use Think\Controller;
use Think\Tool\Tool;

class IntroRankController extends CommonController
{
    /**
     * Index页显示
     *
     */
    public function index() {
        $update = I('update');
        if (! empty($update))//手动更新全球体育的直播赛程数据
        {
            $data = $this->getrank();
//			$this->success('数据更新完成！');
        }
        //列表过滤器，生成查询Map对象
        $map = $this->_search('IntroRank');
        if($map['create_time']) $map['create_time'] = strtotime($map['create_time']);
        //手动获取列表
        $list = $this->_list(CM("IntroRank"), $map);
        $where_id = $this->_list(CM("IntroRank"), $map,'','','','product_id');
        $pro_id = array();
        foreach($where_id as $val)
        {
            $pro_id[$val['product_id']] = $val['product_id'];
        }
        $where['id'] = array('in',$pro_id);
        $where['status'] = 1;
        $introlist = M('IntroProducts')->where($where)->select();
        foreach ($list as &$val) {
            $val['logo'] = Tool::imagesReplace($val['logo']);
        }
        foreach ($list as &$val) {
            foreach ($introlist as $v) {
                $v['logo'] = Tool::imagesReplace($v['logo']);
                if ($val['product_id'] == $v['id']) {
                    $val['name'] = $v['name'];
                    $val['logo'] = $v['logo'];
                }
            }
        }
        $this->assign('list', $list);
        $this->display();
    }

    //编辑
    public function edit()
    {
        $id = I('id');
        $model = M('IntroRank');
        $vo = $model->where(['id'=>$id])->find();
        if(!$vo){
            $this->error('参数错误!');
        }
        $this->assign('vo',$vo);
        $this->display();
    }

    /**
     * 获取全球体育的直播赛程数据
     */
    public function getrank()
    {
        $curlobj = curl_init();
        curl_setopt($curlobj,CURLOPT_URL,'http://www.'.DOMAIN.'/Home/Intro/remain_num.html');
        curl_setopt($curlobj,CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($curlobj);
        if (curl_errno($curlobj) != 0) {
            return false;
        }
        curl_close($curlobj);
        return $res;
    }

}