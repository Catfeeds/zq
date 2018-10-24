<?php
/**
 * ETC管理的车宝参与活动统计
 * @author liangzk <1343724998@qq.com>
 * @since 2016-6-15 v1.0
 */
class EtcCountController extends CommonController
{
    /**
     * Index 首页
     */
    public function index()
    {
        //过滤列表
        $map=$this->_search('PartakeView');
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

        $partakeModel=D('PartakeView');
        //列表数据
        $partakeList=$this->_list($partakeModel,$map,'user_id,add_time');
        //参与总人数
        $partakeNum=$partakeModel->where($map)->count("DISTINCT q.user_id");
        //ETC用户的总人数
        $etcUserNum=M('EtcUser')->count('DISTINCT uid');
        //竞猜总场次
        $gambleNum=$partakeModel->where($map)->count("DISTINCT q.id");
        //截取显示的字符串
        foreach ($partakeList as $key => $value) {
            $partakeList[$key]['home_team_name']=substr($value['home_team_name'], 0,strpos($value['home_team_name'], ','));
            $partakeList[$key]['away_team_name']=substr($value['away_team_name'], 0,strpos($value['away_team_name'], ','));
        }
        $Export=I('Export');
        if(!empty($Export))//导出ETC车宝合作数据统计的参与竞猜用户的信息
        {


            //导出操作
            $etcCountList=$partakeModel->where($map)->order('user_id desc,add_time desc')->select();
            $this->partakeExport("ETC车宝合作数据统计的参与竞猜用户的信息",$etcCountList,$etcUserNum,$partakeNum,$gambleNum,$_REQUEST ['startTime'],$_REQUEST ['endTime']);
        }
        else
        {
            $this->assign('etcUserNum',$etcUserNum);
            $this->assign('gambleNum',$gambleNum);
            $this->assign('partakeNum',$partakeNum);
            $this->assign('list',$partakeList);
            $this->display();
        }

    }
    /**
     * 导出（ETC车宝合作数据统计的参与竞猜用户的信息）Excel操作
     *@param string $filename [文件名，当为空时就以当前日期为文件名]
     *@param list $list [列表数据]
     *@param int etcUserNum [注册总人数]
     *@param int [gambleNum] [竞猜总场次]
     *@param intpartakeNum [竞猜参与人数]
     *@param date startTime [筛选的初始时间]
     *@param date endTime [筛选的结束时间]
     */
    public function partakeExport($filename="",$list,$etcUserNum,$partakeNum,$gambleNum,$startTime,$endTime)
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

        //注册总人数
        $etcUserNum="注册总人数：\t".$etcUserNum;
        $etcUserNum=iconv('utf-8','gbk',$etcUserNum);
        echo $etcUserNum;
        echo "\n";
        //时间段
        $quantumTime="时间段：\t".$startTime."\t"."到 \t".$endTime;
        $quantumTime=iconv('utf-8','gbk',$quantumTime);
        echo $quantumTime;
        echo "\n";
        //参与总人数
        $partakeNum="参与总人数：\t ".$partakeNum."\t ";
        $partakeNum=iconv('utf-8','gbk',$partakeNum);
        echo $partakeNum;
        echo "\n";
        //竞猜总场次
        $gambleNum="竞猜总场次：\t ".$gambleNum."\t ";
        $gambleNum=iconv('utf-8','gbk',$gambleNum);
        echo $gambleNum;
        echo "\n\n";

        $title="ID\t 参与用户ID\t 参与场次\t 竞猜额\t 竞猜时间";
        $title=iconv('utf-8','gbk',$title);
        echo $title;


        foreach ($list as $key => $value) {

            echo "\n";
            echo iconv('utf-8','gbk',$value['id'])."\t";
            echo iconv('utf-8','gbk',$value['user_id'])."\t";
            echo iconv('utf-8','gbk',substr($value['home_team_name'], 0,strpos($value['home_team_name'], ','))."VS".substr($value['away_team_name'], 0,strpos($value['away_team_name'], ',')))."\t";
            echo iconv('utf-8','gbk',$value['bet_coin'])."\t";
            echo iconv('utf-8','gbk',date('Y/m/d H:i:s',$value['add_time']))."\t";

        }

    }

}


?>