 <?php
/**
 * APP下载点击统计控制器
 *
 * @author dengweijun <406516482@qq.com>
 *
 * @since  2016-08-26
 */

class AppJumpController extends CommonController {
    /**
     * 分类列表
     * @return string     
    */
    public function index()
	{
		//列表过滤器，生成查询Map对象
		$map = $this->_search ("AppJump");
        //时间查询
        if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
            if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                $startTime = strtotime($_REQUEST ['startTime']);
                $endTime   = strtotime($_REQUEST ['endTime'])+86400;
                $map['time'] = array('BETWEEN',array($startTime,$endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']);
                $map['time'] = array('EGT',$strtotime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime'])+86400;
                $map['time'] = array('ELT',$endTime);
            }
        }
        if(empty($_REQUEST['startTime']) && empty($_REQUEST['endTime'])){
            $map['time'] = array('BETWEEN',array(strtotime(date(Ymd)),strtotime(date(Ymd))+86400));
        }
		//获取列表
		$list = $this->_list ( CM('AppJump'), $map,'',false,'sign','id,sign,sum(number) number,count(sign) click_number');
		$this->assign ('list', $list);
        $this->display();
    }

    public function check()
    {
        //列表过滤器，生成查询Map对象
        $map = $this->_search ("AppJump");
        //时间查询
        if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
            if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                $startTime = strtotime($_REQUEST ['startTime']);
                $endTime   = strtotime($_REQUEST ['endTime'])+86400;
                $map['time'] = array('BETWEEN',array($startTime,$endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']);
                $map['time'] = array('EGT',$strtotime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime'])+86400;
                $map['time'] = array('ELT',$endTime);
            }
        }
        //获取列表
        $list = $this->_list ( CM('AppJump'), $map);
        $this->assign ('list', $list);
        $this->display();
    }
}