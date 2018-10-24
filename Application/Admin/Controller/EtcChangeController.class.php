<?php
/**
 * ETC管理的积分兑换
 *  @author liangzk <1343724998@qq.com>
 *
 *  @since  2016-6-6
 */
class EtcChangeController extends CommonController
{
    /**
     * Index 首页
     *
     */
    public function index()
    {

        //列表过滤器，生成查询对象
        $map = $this->_search('EtcChangeView');
        //ID搜索
        $id=I('id');
        if(!empty($id))
        {
            $id='%'.$id.'%';
            $where['e.id']=['like',$id];
            $map['_complex']=$where;
        }
        //用户ID搜索
        $user_id=I('user_id');
        if(!empty($user_id))
        {
            $user_id='%'.$user_id.'%';
            $where['e.user_id']=['like',$user_id];
            $map['_complex']=$where;
        }
        //昵称关键字查询
        $keyWord = I('keyWord');
        if(!empty($keyWord)){
            $keyWord = '%'.$keyWord.'%';
            $where['nick_name'] = ['like',$keyWord];
            $map['_complex'] = $where;
        }
         //时间查询
        if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
            if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                $startTime = strtotime($_REQUEST ['startTime']);
                $endTime   = strtotime($_REQUEST ['endTime']);
                $map['add_time'] = array('BETWEEN',array($startTime,$endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']);
                $map['add_time'] = array('EGT',$strtotime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime']);
                $map['add_time'] = array('ELT',$endTime);
            }
        }
        $Export=I('Export');
        if(!empty($Export))//导出竞猜币兑换明细统计
        {
            $etcChangeModel = D('EtcChangeView');
            $etcChangelist = $etcChangeModel
                            ->where($map)
                            ->Field('id,user_id,sum(change_integral) as changeIntegralSum,add_time')
                            ->group('user_id')
                            ->order('changeIntegralSum desc')
                            ->select();
            $this->etcChangeExport("竞猜币兑换明细统计",$etcChangelist,$_REQUEST ['startTime'],$_REQUEST ['endTime']);
        }
        else
        {
            $list=$this->_list(D('EtcChangeView'),$map);
            $this->assign('list',$list);
            $this->display();
        }
    }
    /**
     *
     * 导出（竞猜币兑换明细统计）Excel操作
     *@param string $filename [文件名，当为空时就以当前日期为文件名]
     *@param list $list [列表数据]
     *@param date startTime [筛选的初始时间]
     *@param date endTime [筛选的结束时间]
     */
    public function etcChangeExport($filename="",$list,$startTime,$endTime)
    {

        if(empty($filename))
        {
            $filename=date('Y-m-d');
        }
        header("Pragma:public");
        header("Expires:0");
        header("Cache-Control:must-revalidate,post-check=0,pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl;charset=gb2312");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename="'.$filename.'.xls"');
        header("Content-Transfer-Encoding:binary");

        $quantumTime="时间段：\t".$startTime."\t"."到 \t".$endTime;
        $quantumTime=iconv('utf-8','gbk',$quantumTime);
        echo $quantumTime;
        echo "\n\n";


        $title="序号\t 兑换用户ID\t 兑换数量\t 日期";
        $title=iconv('utf-8','gbk',$title);
        echo $title;
        set_time_limit(0);
        $offset= 0;
        $length=100;
        foreach($list as $key=>$row)
        {
            echo "\n";
            echo iconv('utf-8','gbk',$row['id'])."\t";
            echo iconv('utf-8','gbk',$row['user_id'])."\t";
            echo iconv('utf-8','gbk',$row['changeIntegralSum'])."\t";
            echo iconv('utf-8','gbk',date('Y/m/d H:i',$row['add_time']))."\t";
        }
        $offset+=$length;


    }

}
?>