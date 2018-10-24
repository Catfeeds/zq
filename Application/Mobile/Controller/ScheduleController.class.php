<?php
/**
 * 赛程
 *
 * @author chenzj <443629770@qq.com>
 *
 * @since  2016-04-19
 */
class ScheduleController extends CommonController {
    public function index() {
        $this->assign('title','足球比分');
        $GetDate=I("get.date",date('Ymd',NOW_TIME+86400),"intval");
        $date_list=array();
//        for($i=1;$i<8;$i++){
//            $date_list[$i]['date']=strtotime("-$i days");
//            $date_list[$i]['ymd']=date("Ymd",strtotime("-$i days"));
//            $date_list[$i]['week']=getWeek(date('w',strtotime("-$i days")));
//        }
//        krsort($date_list);
        for($i=1;$i<8;$i++){
            $date_list[$i]['date']=strtotime("+$i days");
            $date_list[$i]['ymd']=date("Ymd",strtotime("+$i days"));
            $date_list[$i]['week']=getWeek(date('w',strtotime("+$i days")));
        }
//        if($GetDate<date('Ymd')){
//            $data=$this->get_curl(C('API_URL')."/fbOver","date=$GetDate&key=no",C('CURL_DOMAIN'));
//        }else{
//            $data=$this->get_curl(C('API_URL')."/fbFixtureList","date=$GetDate&key=no",C('CURL_DOMAIN'));
//        }
//        if($data['status']===1){
//            $league=array();
//            foreach ($data['data'] as $k=>&$v){
//                if($v[4]>3){
//                    continue;
//                }
//                if(!empty($v[2][0])){
//                    if($v[4]==0){
//                        $league[1][$v[1]]=$v[2][0];
//                    }else{
//                        $league[$v[4]][$v[1]]=$v[2][0];
//                    }
//                }
//            }
//            //级别筛选
//            ksort($league);
//            $cookName = 'Schedule'.$GetDate;
//            if(cookie($cookName)==null && $GetDate>date('Ymd')){
//                $sche='';
//                foreach ($league as $k=>$v){
//                    //只要一二级
//                        $va= array_flip($v);
//                        $sche.= implode(',', $va);
//                        $sche.=',';
//                }
//                $sche=  substr($sche, 0,-1);
//                cookie($cookName,$sche);
//            }
//            if(cookie('ScheRes')==null && $GetDate<date('Ymd')){
//                $sche='';
//                foreach ($league as $k=>$v){
//                    //只要一二级
//                    if($k<3){
//                        $va= array_flip($v);
//                        $sche.= implode(',', $va);
//                        $sche.=',';
//                    }
//                }
//                $sche=  substr($sche, 0,-1);
//                cookie('ScheRes',$sche);
//            }
//            $this->assign('league',$league);
//            $this->assign('list',$data['data']);
//        }
//        if($GetDate<date('Ymd')){
//            $this->assign('chioce',cookie('ScheRes'));
//        }else{
//            $this->assign('chioce',cookie($cookName));
//        }
        $this->assign('date_now',NOW_TIME);
        $this->assign('GetDate',$GetDate);
        $this->assign('date_list',$date_list);
		cookie('detailsUrl', __SELF__);
        cookie('userUrl', __SELF__);
        if(I('f') == 'no'){
            $this->display('index_f');
            die;
        }
        if($GetDate<date('Ymd')){
            $this->display('over');
        }else{
            $this->display();
        }
    }
    
    public function schedule_bk(){
        $this->assign('title','篮球比分');
        $GetDate=I("get.date",date('Ymd',NOW_TIME+86400),"intval");
        $date_list=[];
        for($i=1;$i<8;$i++){
            $date_list[$i]['date']=strtotime("+$i days");
            $date_list[$i]['ymd']=date("Ymd",strtotime("+$i days"));
            $date_list[$i]['week']=getWeek(date('w',strtotime("+$i days")));
        }
        $data=$this->get_curl(C('API_BKURL')."/bkFuture","date=$GetDate&key=no",C('CURL_DOMAIN'));
        if($data['status']===1){
            $league=array();
            $_BkSche=cookie('BkSchedule');
            $_ScheArr=array();
            if ($_BkSche != null){
                $_ScheArr=explode(',', $_BkSche);
            }
            foreach ($data['data'] as $k=>&$v){
                $v['date']= date('m-d',strtotime($v[5]));
                $league[$v[1]] = $v[2][0];
                //移除没有选的联赛
                if ($_BkSche != null){
                    if(!in_array($v[1], $_ScheArr)){
                        unset($data['data'][$k]);
                    }
                }
            }
            //级别筛选
            ksort($league);
            if(cookie('BkSchedule')==null){
                $sche='';
                foreach ($league as $k=>$v){
                    //只要一二级
                        $sche.= $k.',';
                    
                }
                $sche=  substr($sche, 0,-1);
                cookie('BkSchedule',$sche);
            }
            $this->assign('league',$league);
            //dump($data['data']);
            $this->assign('list',$data['data']);
        }
        $this->assign('chioce',cookie('BkSchedule'));
        $this->assign('list',$data['data']);
        $this->assign('GetDate',$GetDate);
        $this->assign('date_list',$date_list);
		cookie('detailsUrl', __SELF__);
        cookie('userUrl', __SELF__);
        $this->display();
    }

    public function getGameList(){
        $GetDate=I("get.date",date('Ymd',NOW_TIME+86400),"intval");
        if($GetDate<date('Ymd')){
            $data=$this->get_curl(C('API_URL')."/fbOver","date=$GetDate&key=no",C('CURL_DOMAIN'));
        }else{
            $data=$this->get_curl(C('API_URL')."/fbFixtureList","date=$GetDate&key=no",C('CURL_DOMAIN'));
        }
        if($data['status']===1){
            $league=array();
            foreach ($data['data'] as $k=>&$v){
                if($v[4]>3){
                    continue;
                }
                if(!empty($v[2][0])){
                    if($v[4]==0){
                        $league[1][$v[1]]=$v[2][0];
                    }else{
                        $league[$v[4]][$v[1]]=$v[2][0];
                    }
                }
            }
            //级别筛选
            ksort($league);
            $data['union'] = $league;
        }
    }
    
}