<?php
/**
 *  抽奖记录统计
 *  @author dengwj <406516482@qq.com>
 *  @since  2016-6-21
 */

class EtcGachalogController extends CommonController
{
    /**
    *构造函数
    * @return  #
    */
    public function _initialize()
    {
        parent::_initialize();
        //手动指定显示条数
        $_REQUEST ['numPerPage'] = 1000;
    }
    /**
     * Index 首页
     *
     */
    public function index()
    {
        $map  = $this->_search('EtcGachalog');
        //查询用户名、昵称
        $username_nickname = trim(I('username_nickname'));
        if(!empty($username_nickname))
        {
            $userWhere['username']  = ['like','%'.$username_nickname.'%'];
            $userWhere['nick_name'] = ['like','%'.$username_nickname.'%'];
            $userWhere['_logic'] = 'or';
            $userIdRes = M('FrontUser')->where($userWhere)->getField('id',true);
            ! empty($userIdRes) ? $map['user_id'] = ['IN',$userIdRes] : $map['user_id'] = '';
            unset($userIdRes);
        }
        //时间查询
        if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
            if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                $startTime = strtotime($_REQUEST ['startTime']);
                $endTime   = strtotime($_REQUEST ['endTime'])+86400;
                $map['add_time'] = array('BETWEEN',array($startTime,$endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']);
                $map['add_time'] = array('EGT',$strtotime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime'])+86400;
                $map['add_time'] = array('ELT',$endTime);
            }
        }

        $list = $this->_list(D('EtcGachalog'),$map);
        $this->assign('list',$list);
        $this->display();
    }

    public function logCount()
    {
        //查询用户名、昵称
        $username_nickname = trim(I('username_nickname'));
        if(!empty($username_nickname))
        {
            $userWhere['username']  = ['like','%'.$username_nickname.'%'];
            $userWhere['nick_name'] = ['like','%'.$username_nickname.'%'];
            $userWhere['_logic'] = 'or';
            $userIdRes = M('FrontUser')->where($userWhere)->getField('id',true);
            ! empty($userIdRes) ? $map['user_id'] = ['IN',$userIdRes] : $map['user_id'] = '';
            unset($userIdRes);
        }
        //时间查询
        if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
            if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                $startTime = strtotime($_REQUEST ['startTime']);
                $endTime   = strtotime($_REQUEST ['endTime'])+86400;
                $map['add_time'] = array('BETWEEN',array($startTime,$endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']);
                $map['add_time'] = array('EGT',$strtotime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime'])+86400;
                $map['add_time'] = array('ELT',$endTime);
            }
        }
        $list = M("EtcGachalog l")->join("LEFT JOIN qc_front_user f on f.id=l.user_id")->field('f.username,f.nick_name,l.user_id,count(l.id) log')->where($map)->group('l.user_id')->select();
        
        foreach ($list as $k => $v) {
            $map['user_id'] = $v['user_id'];
            $gacha = M('EtcGachalog')->field('prize_id')->where($map)->select();
            $prize1 = 0;
            $prize2 = 0;
            $prize3 = 0;
            $prize4 = 0;
            $prize5 = 0;
            $prize6 = 0;
            $prize7 = 0;
            $prize8 = 0;
            foreach ($gacha as $kk => $vv) {
                if($vv['prize_id'] == '1') $prize1++;
                if($vv['prize_id'] == '2') $prize2++;
                if($vv['prize_id'] == '3') $prize3++;
                if($vv['prize_id'] == '4') $prize4++;
                if($vv['prize_id'] == '5') $prize5++;
                if($vv['prize_id'] == '6') $prize6++;
                if($vv['prize_id'] == '7') $prize7++;
                if($vv['prize_id'] == '8') $prize8++;
            }
            $list[$k]['prize1'] = $prize1;
            $list[$k]['prize2'] = $prize2;
            $list[$k]['prize3'] = $prize3;
            $list[$k]['prize4'] = $prize4;
            $list[$k]['prize5'] = $prize5;
            $list[$k]['prize6'] = $prize6;
            $list[$k]['prize7'] = $prize7;
            $list[$k]['prize8'] = $prize8;
        }
        $Export = I('Export');
        if($Export == 1)//导出明细统计
        {
            $this->etcChangeExport("抽奖记录统计",$list);
            die;
        }
        $this->assign ( 'totalCount', count($list) );
        $this->assign('list',$list);
        $this->display();
    }
    /**
     *
     * 导出（竞猜币兑换明细统计）Excel操作
     *@param string $filename [文件名，当为空时就以当前日期为文件名]
     *@param list $list [列表数据]
     *@param date startTime [筛选的初始时间]
     *@param date endTime [筛选的结束时间]
     */
    public function etcChangeExport($filename="",$list)
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
        
        if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime']))
        {
            $quantumTime="时间段：\t".$_REQUEST['startTime']."\t"."到 \t".$_REQUEST['endTime'];
            echo iconv('utf-8','gbk',$quantumTime);
            echo "\n\n";
        }

        $title="序号\t 用户名（昵称）\t 抽奖总次数\t 奖品明细";
        $title=iconv('utf-8','gbk',$title);
        echo $title;
        set_time_limit(0);
        $offset= 0;
        $length=100;
        foreach($list as $key=>$row)
        {
            echo "\n";
            echo iconv('utf-8','gbk',$key)."\t";
            echo iconv('utf-8','gbk',is_show_mobile($row['username'])."（".$row['nick_name']."）")."\t";
            echo iconv('utf-8','gbk',$row['log']." 次")."\t";
            $str = "";
            if ($row['prize1'] > 0) $str .= "[ iphone6S(128G)（".$row['prize1']." 次）]";     
            if ($row['prize2'] > 0) $str .= "[ 恒大7月VIP门票（".$row['prize2']." 次）]";      
            if ($row['prize3'] > 0) $str .= "[ 联通/电信100,移动70M（".$row['prize3']." 次）]"; 
            if ($row['prize4'] > 0) $str .= "[ 行车记录仪（".$row['prize4']." 次）]";         
            if ($row['prize5'] > 0) $str .= "[ 2000积分（".$row['prize5']." 次）]";            
            if ($row['prize6'] > 0) $str .= "[ 移动/电信30,联通50M （".$row['prize6']." 次）]"; 
            if ($row['prize7'] > 0) $str .= "[ 1000积分（".$row['prize7']." 次）]";           
            if ($row['prize8'] > 0) $str .= "[ 欧洲杯官方T恤（".$row['prize8']." 次）]";    
            echo iconv('utf-8','gbk',$str)."\t";
        }
        $offset+=$length;


    }

}
?>