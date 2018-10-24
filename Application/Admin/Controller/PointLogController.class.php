<?php
/**
 * 积分明细管理
 *
 * @author dengweijun <406516482@qq.com>
 *
 * @since  2016-1-5
 */

use Think\Controller;
class PointLogController extends CommonController {
    public function index(){

        //生成查询条件
        $map = $this->_search("PointLog");
        //时间查询
        if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
            if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                $startTime = strtotime($_REQUEST ['startTime']);
                $endTime   = strtotime($_REQUEST ['endTime'])+86400;
                $map['log_time'] = array('BETWEEN',array($startTime,$endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']);
                $map['log_time'] = array('EGT',$strtotime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime'])+86400;
                $map['log_time'] = array('ELT',$endTime);
            }
        }
        
        $Export=I('Export');
        if(!empty($Export))//导出Excel
        {
            $integralList=D('PointLog')->where($map)->select();
            $this->pointLogExport("",$integralList);
        }
        else
        {
            $list = $this->_list(D('PointLog'), $map );
            $this->assign('list',$list);
            $this->display();
        }
    }
    /**
     * [pointLogExport description]
     * @param string $filename [文件名，当为空时就以当前日期为文件名]
     * @param list $list [列表数据]
     */
    public function pointLogExport($filename="",$list)
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

        $title="序号\t ID\t 会员名称\t 创建时间\t 记录类型\t 积分数量\t 剩余积分\t  说明\t";
        $title=iconv('utf-8','gbk',$title);
        echo $title;
        set_time_limit(0);
        $offset= 0;
        $length=100;
        foreach ($list as $key => $value) {
            echo "\n";
            echo iconv('utf-8','gbk',++$i)."\t";
            echo iconv('utf-8','gbk',$value['id'])."\t";
            echo iconv('utf-8','gbk',$value['nick_name']."(".is_show_mobile($value['username']).")")."\t";
            echo iconv('utf-8','gbk',date('Y/m/d H:i:s',$value['log_time']))."\t";
            switch ($value['log_type']) {
                case '1':
                    $log_type="收入";
                    break;
                case '2':
                    $log_type="支出";
                    break;
                case '6':
                    $log_type="积分兑换";
                    break;
                case '11':
                    $log_type="登陆赠送";
                    break;
            }
            echo iconv('utf-8', 'gbk', $log_type."\t");
            echo iconv('utf-8','gbk',$value['change_num']."\t");
            echo iconv('utf-8','gbk',$value['total_point']."\t");
            echo iconv('utf-8','gbk',$value['descc']."\t");

        }
        $offset+=$length;

    }


}

?>