<?php

/**
 * 赌博
 *
 * @author chenzj <443629770@qq.com>
 *
 * @since  2016-04-19
 */
class OddsController extends CommonController {
    protected function _initialize() {
        parent::_initialize();
        $last_fb_time  = S('OddsInitLastFbTime'.MODULE_NAME);
        $last_Bk_time  = S('OddsInitLastBkTime'.MODULE_NAME);
        
        if ($last_fb_time < mktime(10,35,0,date('m'),date('d'),date('Y')) && strtotime(date('H:i')) > strtotime('10:35') )
        {
            cookie('M_Companys',null);
            cookie('M_OddsType',null);
            S('OddsInitLastFbTime'.MODULE_NAME,time());
        }
    
        if ($last_Bk_time < mktime(15,35,0,date('m'),date('d'),date('Y')) && strtotime(date('H:i')) > strtotime('15:35')  )
        {
            cookie('BkEvent',null);
            S('OddsInitLastBkTime'.MODULE_NAME,time());
        }
        //足球指数
        cookie('M_Companys') ? cookie('M_Companys') : cookie('M_Companys', '1,3,4,8');
        cookie('M_OddsType') ? cookie('M_OddsType') : cookie('M_OddsType', '8');
        //篮球指数
//        cookie('M_BkCompanys') ? cookie('M_BkCompanys') : cookie('M_BkCompanys', '3');//皇冠
//        cookie('M_BkOddsType') ? cookie('M_BkOddsType') : cookie('M_BkOddsType', '1');//默认NBA
        $this->assign('user_auth',session('user_auth'));
    }

    public function index() {
        //获取赛事-联赛名，设置cookie
        $this->assign('title','足球比分');
//        $data = $this->get_curl(C('API_URL')."/fbInstant", 'key=no',C('CURL_DOMAIN'));
//        $OddsType=cookie('M_OddsType');
//        $_companys=cookie('M_Companys');
//        $_Event=cookie('M_Event');
//        $_Event_arr=array();
//        if ($_Event != null){
//            $_Event_arr=explode(',', $_Event);
//        }
//        $_company_arr=  explode(',', $_companys);
//        if ($data['status'] == 1 && !empty($data['data'])) {
//            $league = array();
//            foreach ($data['data'] as $k => &$v) {
//                //赛事级别
//                $league[$v[7]][$v[1]] = $v[2];
//                
//                //移除之前的比赛
//                if (NOW_TIME >= strtotime($v[6])) {
//                    unset($data['data'][$k]);
//                }
//                //移除没有选的联赛
//                if ($_Event != null){
//                    if(!in_array($v[1], $_Event_arr)){
//                        unset($data['data'][$k]);
//                    }
//                }
//                //移除其他赔率
//                switch ($OddsType) {
//                    case '8':
//                        unset($v[9],$v[10]);
//                        break;
//                    case '9':
//                        unset($v[8],$v[10]);
//                        break;
//                    case '10':
//                        unset($v[8],$v[9]);
//                        break;
//                }
//                //赔率比较
//                foreach($v[$OddsType] as  $kk=> &$vv){
//                    if(!in_array($vv[0], $_company_arr)){
//                        unset($v[$OddsType][$kk]);
//                    }
//                    if(strpos($vv[3],'/')===false){
//                        $vv['res_star']=$vv[3];
//                    }else{
//                        $arr=explode('/', $vv[3]);
//                        $vv['res_star']=($arr[0]+$arr[1])/2;
//                    }
//                    if(strpos($vv[6],'/')===false){
//                        $vv['res_now']=$vv[6];
//                    }else{
//                        $arr=explode('/', $vv[6]);
//                        $vv['res_now']=($arr[0]+$arr[1])/2;
//                    }
//                }
//                ksort($v[9]);
//            }
//            //分类
//            $this->assign('OddsType', cookie('M_OddsType'));
//            $this->assign('list', $data['data']);
//
//            //赛事级别
//            sort($league);
//            if ($_Event == null && !empty($league)) {
//                $sche = '';
//                foreach ($league as $k => $v) {
//                    //赛事级别赛选
//                    if($k<2){
//                        foreach ($v as $k2=>$v2){
//                            $sche.= $k2.',';
//                        }
//                    }
//                }
//                $sche = substr($sche, 0, -1);
//                cookie('M_Event', $sche);
//            }
//            session('league', $league);
//        }

        //获取广告
//        $adver = @Think\Tool\Tool::getAdList(19,5,2);
//        foreach ($adver as $k => $v)
//        {
//            unset($adver[$k]['id']);
//        }
//        $this->assign('adver_list',$adver);
        cookie('userUrl', __SELF__);
		cookie('detailsUrl', __SELF__);
        if(I('f') == 'no'){
            $this->display('index_f');
            die;
        }
        $this->display();
    }

    public function company() {
        $company = C('DB_COMPANY_ODDS');
        $this->assign('FUNCTION_NAME', 'company');
        $this->assign('company', $company);
        $this->display();
    }

    public function event() {

        $data = $this->get_curl(C('API_URL')."/fbInstant", 'key=no',C('CURL_DOMAIN'));
            if ($data['status'] == 1 && !empty($data['data'])) {
                $league = array();
                foreach ($data['data'] as $k => &$v) {
                    if (NOW_TIME >= strtotime($v[6])) {
                        unset($data['data'][$k]);
                    }
                    //赛事级别
                    $league[$v[7]][$v[1]] = $v[2][0];
                    ksort($v[9]);
                }
                //赛事级别
                sort($league);
            }
        $this->assign('league', $league);
        if (cookie('M_Event') == null && !empty($league)) {
            $sche = '';
            foreach ($league as $k => $v) {
                if($k<2){
                    $va = array_flip($v);
                    $sche.= implode(',', $va);
                    $sche.=',';
                }
            }
            $sche = substr($sche, 0, -1);
            cookie('M_Event', $sche);
        }
        $this->assign('FUNCTION_NAME', 'event');
        $this->display();
    }

    //ajax 实时赔率
    public function goal() {
        $data = $this->get_curl(C('API_URL')."/chodds", 'key=no', C('CURL_DOMAIN'));
        if ($data['status'] != 1) {
            $this->error('获取数据失败');
        }
        $M_OddsType = cookie('M_OddsType');
        $type = 'asian';
        switch ($M_OddsType) {
            case '1':
                $type = 'asian';
                break;
            case '2':
                $type = 'europe';
                break;
            case '3':
                $type = 'ball';
                break;
            default:
                $type = 'asian';
                break;
        }
        foreach ($data['data'] as $v) {
            if ($v['name'] == $type) {
                $arr = $v['content'];
            }
        }
        if (empty($arr)) {
            $this->error('没有数据');
        }
        $this->success($arr);
    }
    
    //篮球
    public function odds_bk(){
        $this->assign('title','篮球比分');
        $data = $this->get_curl(C('API_BKURL')."/bkchodds", '',C('CURL_DOMAIN'));//默认是皇冠的
        $_Event=cookie('BkEvent');
        $_Event_arr=array();
        if ($_Event != null){
            $_Event_arr=explode(',', $_Event);
        }
        if ($data['status'] == 1 && !empty($data['data']))
        {
            $league=[];
            foreach ($data['data'] as $k => &$v)
            {
                //移除之前的比赛
                if (NOW_TIME >= strtotime($v[5].' '.$v[6])) {
                    unset($data['data'][$k]);
                }
                $league[$v[1]] = $v[2][0];
                //移除没有选的联赛
                if ($_Event != null){
                    if(!in_array($v[1], $_Event_arr)){
                        unset($data['data'][$k]);
                    }
                }
            }
            //级别筛选
            ksort($league);
            if(cookie('BkEvent')==null){
                $sche='';
                foreach ($league as $k=>$v){
                    //只要一二级
                        $sche.= $k.',';
                    
                }
                $sche=  substr($sche, 0,-1);
                cookie('BkEvent',$sche);
            }
            $this->assign('league',$league);
        }
        $this->assign('chioce',cookie('BkEvent'));
        $this->assign('list', $data['data']);
        cookie('userUrl', __SELF__);
		cookie('detailsUrl', __SELF__);
        $this->display();
    }
    
    public function bkevent() {
            $data = $this->get_curl(C('API_BKURL')."/bkchodds", 'key=no',C('CURL_DOMAIN'));
            if ($data['status'] == 1 && !empty($data['data'])) {
                $league = array();
                foreach ($data['data'] as $k => &$v) {
                    //赛事级别
                    $league[$v[1]] = $v[2];
                }
            }
        $this->assign('league', $league);
        if (cookie('BkEvent') == null && !empty($league)) {
            $sche = '';
            foreach ($league as $k => $v) {
                $sche.= $k.',';
            }
            $sche = substr($sche, 0, -1);
            cookie('BkEvent', $sche);
        }
        $this->display();
    }

    public function bkcompany() {
        $company = C('DB_BK_COMPANY_ODDS');
        $this->assign('FUNCTION_NAME', 'company');
        $this->assign('company', $company);
        $this->display();
    }


    //ajax 实时赔率
    public function bkgoal() {
        $data = $this->get_curl(C('API_BKURL')."/bkodds", 'companyId=3&key=no', C('CURL_DOMAIN'));
        if ($data['status'] != 1) {
            $this->error('获取数据失败');
        }
        if (empty($data)) {
            $this->error('没有数据');
        }
        foreach ($data as $k => $v)
        {
            $data[$k][0] = number_format($v[0],2);
            $data[$k][2] = number_format($v[2],2);
            $data[$k][3] = number_format($v[5],2);
            $data[$k][5] = number_format($v[5],2);
        }
        $this->success($data);
    }

    public function getGameList(){
        $data = $this->get_curl(C('API_URL')."/fbInstant", 'key=no',C('CURL_DOMAIN'));
        $OddsType=cookie('M_OddsType');
        $_companys=cookie('M_Companys');
        $_Event=cookie('M_Event');
        $_Event_arr=array();
        if ($_Event != null){
            $_Event_arr=explode(',', $_Event);
        }
        $_company_arr=  explode(',', $_companys);
        if ($data['status'] == 1 && !empty($data['data'])) {
            $league = array();
            foreach ($data['data'] as $k => &$v) {
                //赛事级别
                $league[$v[7]][$v[1]] = $v[2];

                //移除之前的比赛
                if (NOW_TIME >= strtotime($v[6])) {
                    unset($data['data'][$k]);
                }
                //移除没有选的联赛
                if ($_Event != null){
                    if(!in_array($v[1], $_Event_arr)){
                        unset($data['data'][$k]);
                    }
                }
                //移除其他赔率
                switch ($OddsType) {
                    case '8':
                        unset($v[9],$v[10]);
                        break;
                    case '9':
                        unset($v[8],$v[10]);
                        break;
                    case '10':
                        unset($v[8],$v[9]);
                        break;
                }
                //赔率比较
                foreach($v[$OddsType] as  $kk=> &$vv){
                    if(!in_array($vv[0], $_company_arr)){
                        unset($v[$OddsType][$kk]);
                    }
                    if(strpos($vv[3],'/')===false){
                        $vv['res_star']=$vv[3];
                    }else{
                        $arr=explode('/', $vv[3]);
                        $vv['res_star']=($arr[0]+$arr[1])/2;
                    }
                    if(strpos($vv[6],'/')===false){
                        $vv['res_now']=$vv[6];
                    }else{
                        $arr=explode('/', $vv[6]);
                        $vv['res_now']=($arr[0]+$arr[1])/2;
                    }
                }
                $v[$OddsType] = array_merge($v[$OddsType]);
                ksort($v[9]);
            }
            //分类
            $data['oddsType'] = cookie('M_OddsType');

            //赛事级别
            sort($league);
            if ($_Event == null && !empty($league)) {
                $sche = '';
                foreach ($league as $k => $v) {
                    //赛事级别赛选
                    if($k<2){
                        foreach ($v as $k2=>$v2){
                            $sche.= $k2.',';
                        }
                    }
                }
                $sche = substr($sche, 0, -1);
                cookie('M_Event', $sche);
            }
            session('league', $league);
            $data['data'] = array_merge($data['data']);
        }
        $this->ajaxReturn($data);
    }
}
