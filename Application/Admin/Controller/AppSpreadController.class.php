<?php
/**
 * 积分推广墙
 * 
 * @User mjf
 * @DateTime 2018年6月19日
 *
 */
class AppSpreadController extends CommonController
{
   
    public function index ()
    {
        $page = I('pageNum', 1);
        $limit = I('numPerPage', 100);
        $where = [];
        
        $idfa = I('idfa');
        $ip = I('ip');
        
        if(!empty($idfa)){
            $where['C.idfa'] = $idfa;
        }
        
        if(!empty($ip)){
            $where['C.ip'] = $ip;
        }
        
        $startTime = strtotime(I('activation_date_start'));
        $endTime = strtotime(I('activation_date_end'));
        
        if(!empty($startTime) && empty($endTime)){
            $where['A.add_time'] = ['egt', $startTime];
        }
        
        if(!empty($endTime) && empty($startTime)){
            $where['A.add_time'] = ['elt', $endTime];
        }
        
        if(!empty($endTime) && !empty($startTime)){
            $where['A.add_time'] = ['between', [$startTime, $endTime]];
        }
        
        $count = M("appSpreadClickLog")->master(true)->table('qc_app_spread_click_log C')
        ->join('LEFT JOIN qc_app_spread_activation_log AS A ON A.idfa=C.idfa AND A.log_status=1')
        ->where($where)->count('DISTINCT(C.idfa)');
        
//         echo M("appSpreadClickLog")->getlastsql();
        
        $list = [];
        $order = 'C.add_time';
        $sort = 'DESC';
        
        if(!empty($count)){
            $query = M("appSpreadClickLog")->master(true)->table('qc_app_spread_click_log C')
            ->join('LEFT JOIN qc_app_spread_activation_log AS A ON A.idfa=C.idfa AND A.log_status=1')
            ->field('C.*, A.id AS activation_id, A.add_date as activation_date')
            ->where($where);
            
            $list = $query->order($order . ' ' . $sort)
            ->limit($limit)
            ->page($page)
            ->group('C.idfa')
            ->select();
//             echo $query->getlastsql();
        }
        
        $this->_listForPage($list, $count, $order, $sort);
        $this->assign('list', $list);
        
        $this->assign('where', $_REQUEST);
        $this->display();
    }
    
    /**
     * http://www.qt.com/qqty_admin/AppSpread/idfaList
     * 
     * @User Administrator
     * @DateTime 2018年6月20日
     *
     */
    public function idfaList() {
        $idfa = '';
        $findidfa = '';
        
        $idfaArray = explode(',', $idfa);
        $findArray = explode(',', $findidfa);
        $nofind = '';
        $count = 0;
        
        foreach ($idfaArray as $idfa){
        if(!empty($idfa) && !in_array($idfa, $findArray)){
                $nofind .= $idfa . PHP_EOL;
                
                $count++;
            }
        }
        echo $count.'--';
        echo ($nofind);
        
    }
    
    
    

}