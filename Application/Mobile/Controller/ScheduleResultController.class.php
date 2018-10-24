<?php
/**
 * 首页
 *
 * @author chenzj <443629770@qq.com>
 *
 * @since  2016-04-19
 */
class ScheduleResultController extends CommonController {
    //主页
    public function index() {
        $GetDate=I("get.date",date('Ymd',NOW_TIME-86400),"intval");
        $date_list=array();
        for($i=1;$i<8;$i++){
            $date_list[$i]['date']=strtotime("-$i days");
            $date_list[$i]['ymd']=date("Ymd",strtotime("-$i days"));
            $date_list[$i]['week']=getWeek(date('w',strtotime("-$i days")));
        }
        krsort($date_list);
//        //获取即时页面数据
//        $data=$this->get_curl(C('API_URL')."/fbOver","date=$GetDate&key=no",C('CURL_DOMAIN'));
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
//            $cook_name = 'ScheRes'.$GetDate;
//            if(cookie($cook_name)==null){
//                $sche='';
//                foreach ($league as $k=>$v){
//                    //只要一二级
//                    $va= array_flip($v);
//                    $sche.= implode(',', $va);
//                    $sche.=',';
//                }
//                $sche=  substr($sche, 0,-1);
//                cookie($cook_name,$sche);
//            }
//            $this->assign('league',$league);
//            $this->assign('list',$data['data']);
//        }
//        $this->assign('chioce',cookie($cook_name));
        $this->assign('date_now',NOW_TIME);
        $this->assign('GetDate',$GetDate);
        $this->assign('date_list',$date_list);
        cookie('userUrl', __SELF__);
		cookie('detailsUrl', __SELF__);
        $this->assign('title','足球比分');
        if(I('f') == 'no'){
            $this->display('index_f');
            die;
        }
        $this->display();
    }
    
    public function res_bk(){
        $this->assign('title','篮球比分');
        $GetDate=I("get.date",date('Ymd',NOW_TIME-86400),"intval");
        $date_list=array();
        for($i=1;$i<8;$i++){
            $date_list[$i]['date']=strtotime("-$i days");
            $date_list[$i]['ymd']=date("Ymd",strtotime("-$i days"));
            $date_list[$i]['week']=getWeek(date('w',strtotime("-$i days")));
        }
        krsort($date_list);
        $data = $this->get_curl(C('API_BKURL')."/bkOver", "date=$GetDate&key=no",C('CURL_DOMAIN'));
        if($data['status']===1){
            $league=array();
            $_BkScheRes=cookie('BkScheRes');
            $_ResArr=array();
            if ($_BkScheRes != null){
                $_ResArr=explode(',', $_BkScheRes);
            }
            foreach ($data['data'] as $k=>&$v){
                $v['home_ot']=  explode('-', $v[20]);
                $v['away_ot']=  explode('-', $v[21]);
                $v['date']= date('m-d',strtotime($v[5]));
                $league[$v[1]]=$v[2][0];
                //移除没有选的联赛
                if ($_BkScheRes != null){
                    if(!in_array($v[1], $_ResArr)){
                        unset($data['data'][$k]);
                    }
                }
            }
            //级别筛选
            ksort($league);
            if(cookie('BkScheRes')==null){
                $sche='';
                foreach ($league as $k=>$v){
                        $sche.= $k.',';
                }
                $sche=  substr($sche, 0,-1);
                cookie('BkScheRes',$sche);
            }
            $this->assign('league',$league);
            $this->assign('list',$data['data']);
        }
        $this->assign('chioce',cookie('BkScheRes'));
        $this->assign('language',cookie('language'));
        $this->assign('GetDate',$GetDate);
        $this->assign('date_list',$date_list);
		cookie('detailsUrl', __SELF__);
        cookie('userUrl', __SELF__);
        $this->display();
    }

    public function getGameList(){

        $GetDate=I("get.date",date('Ymd',NOW_TIME-86400),"intval");
        //获取即时页面数据
        $data=$this->get_curl(C('API_URL')."/fbOver","date=$GetDate&key=no",C('CURL_DOMAIN'));
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
        $this->ajaxReturn($data);
    }
}