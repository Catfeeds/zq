<?php
/**
 * @滚动新闻
 * @author liuweitao 20180508
 */

use Think\Controller;
use Think\Tool\Tool;

class RollController extends CommonController{

    //文章首页
    public function index()
    {
        //设置分页查询数据
        $page = I('p',1,'int');
        if($page < 1) $page = 1;
        $limit = 35;
        //生成查询条件
        //搜索类型
        $type = I('type',1,'int');
        //关键字
        $keyword = htmlspecialchars_decode(I('searchKey'));
        if($keyword)
        {
            if ($type == 1)
                $keyname = 'title';
            else
                $keyname = 'content';
            $keyword = explode(' ',$keyword);
            if(count($keyword) == 1) {
                $where[$keyname] = ['like', "%$keyword[0]%"];
            }else{
                $sqlWhere = [];
                foreach($keyword as $val)
                {
                    $sqlWhere[] = '('.$keyname.' like "%'.$val.'%")';
                }
                $where['_string'] = implode($sqlWhere,' OR ');
            }
        }
        //日期
        $endTime = strtotime(date('Ymd')) + 86400;//当天24点时间戳
        $timeType = I('time','','string');
        switch($timeType)
        {
            case 'day':
                $startTime = $endTime - 86400;
                $where['add_time'] = array('BETWEEN',array($startTime,$endTime));
                break;
            case 'week':
                $startTime = $endTime - 86400*7;
                $where['add_time'] = array('BETWEEN',array($startTime,$endTime));
                break;
            case 'mon':
                $startTime = $endTime - 86400*30;
                $where['add_time'] = array('BETWEEN',array($startTime,$endTime));
                break;
        }
        $where['status'] = 1;
        //必须为原创
        $where['is_original'] = 1;
        //处理分页数据
        $pageCount = M('PublishList')->where($where)->count();
        $pageCount = $hasData = (int)ceil($pageCount/$limit);
        //防止页码溢出
        if($page > $pageCount) $page = $pageCount;
        //防止js插件报错
        if($pageCount == 0) $page = $pageCount = 1;
        $this->assign('pageCount',$pageCount);
        $this->assign('p',$page);

        //列表查询
        if($hasData > 0)
        {
            $list = M('PublishList')->field('id,class_id,title,add_time')->where($where)->order('add_time desc')->page($page,$limit)->select();
            $classArr  = getPublishClass(0);
            foreach ($list as $key => $value) {
                $list[$key]['class_name'] = $classArr[$value['class_id']]['name'];
                $list[$key]['href'] = newsUrl($value['id'], $value['add_time'], $value['class_id'], $classArr);
            }
            $this->assign('list',$list);
        }else{
            $this->assign('pageNone','1');
        }
        //设置seo
        $seo = [
            'seo_title' => '滚动新闻_全球体育',
            'seo_keys' => '新闻中心,最新新闻,实时新闻',
            'seo_desc' => '体育滚动新闻为您提供中超、英超、西甲、意甲、德甲、欧冠等频道的最新消息。',
        ];
        $this->setSeo($seo);
        $this->display();
    }
}
?>