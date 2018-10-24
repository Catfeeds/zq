<?php
/**
 * 用户提交列表
 * 
 * @author liuweitao <cytusc@foxmail.com>
 * @since  2016-12-14
 */

class MasterLogController extends CommonController{
	/**
     * 竞猜列表
     * @return string     
    */
    public function index()
	{
        $map = $this->_search('MasterLog');

        //$map['_string'] = 'status=1 AND score>10';
        //活动标题筛选
        $nick_name = I('nick_name');
        if (!empty($nick_name))
        {
            $map['fu.nick_name'] = ['Like',trim($nick_name).'%'];
        }
        $master_name = I('master_name');
        if (!empty($master_name))
        {
            $map['mli.master_name'] = ['Like',trim($master_name).'%'];
        }
        $phone = I('phone');
        if (!empty($phone))
        {
            $map['ml.phone'] = ['Like',trim($phone).'%'];
            $map['phone'] = $map['ml.phone'];
        }
        if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
            if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                $startTime = strtotime($_REQUEST ['startTime']);
                $endTime   = strtotime($_REQUEST ['endTime'])+86400;
                $map['ml.add_time'] = array('BETWEEN',array($startTime,$endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']);
                $map['ml.add_time'] = array('EGT',$strtotime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime'])+86400;
                $map['ml.add_time'] = array('ELT',$endTime);
            }
        }
        $querySql = M('MasterLog ml')
            ->join('LEFT JOIN qc_front_user fu ON ml.phone = fu.username')
            ->join('LEFT JOIN qc_master_list mli ON ml.master_id = mli.id')
            ->field('ml.id')
            ->where($map)
            ->buildSql();
        $count = M()->table($querySql.' m')->count();

        //获取每页显示的条数
        $pageNum = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
        //获取当前的页码
        $currentPage =! empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1;
        if ($count > 0)
        {
            //排序
            $_order = I('_order');
            if (! empty($_order))
            {
                $_sort = I('_sort') == 'desc' ? 'desc' : 'asc';
            }
            else
            {
                $_order = 'ml.add_time';
                $_sort = 'desc';
            }
            $list = M('MasterLog ml')
                ->join('LEFT JOIN qc_front_user fu ON ml.phone = fu.username')
                ->join('LEFT JOIN qc_master_list mli ON ml.master_id = mli.id')
                ->field('ml.id,ml.phone,ml.add_time,mli.master_name,fu.nick_name,fu.reg_time')
                ->where($map)
                ->order($_order.' '.$_sort)
                ->limit($pageNum*($currentPage-1),$pageNum)
                ->select();

        }

        $this->assign('list',$list);
        $this->assign ( 'totalCount', $count );//当前条件下数据的总条数
        $pageNum =empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
        $this->assign ( 'numPerPage', $pageNum ); //每页显示多少条
        $this->assign ( 'currentPage', !empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1);//当前页码
        $this->setJumpUrl();
        $this->display();
    }


}
?>