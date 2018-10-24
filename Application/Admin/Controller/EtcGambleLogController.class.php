<?php
/**
 * ETC管理的足球竞猜记录
 *
 * @author liangzk <1343724998@qq.com>
 *
 * @since  2016-6-8
 */
class EtcGambleLogController extends CommonController{

    /**
     * Index首页
     *
     */
    public  function index()
    {
        //列表过滤器，生成查询Map对象
        $map = $this->_search('EtcGame');
        
        //手动获取列表,并以赛程ID分组
         $list=$this->_list(CM('EtcGame'),$map,'',false,'game_id');
         foreach ($list as $key => $value) {

            //买该竞猜为赢的人数
             $list[$key]['gambleWinNum']=M('EtcQuiz')->where(['game_id'=>$value['game_id'],'bet_type'=>1])->count();

            // //买该竞猜为平的人数
            $list[$key]['gambleFlatNum']=M('EtcQuiz')->where(['game_id'=>$value['game_id'],'bet_type'=>2])->count();

            // //买该竞猜为负的人数
            $list[$key]['gambleLoseNum']=M('EtcQuiz')->where(['game_id'=>$value['game_id'],'bet_type'=>3])->count();

         }
         $this->assign('list',$list);
         $this->display();
    }
    /**
     * 查看操作
     *
     */
    public function check()
    {
        $game_id=I('game_id');
        //列表过滤器，生成查询Map对象
        $map = $this->_search('GambleLogView');
        //筛选赛程
        $map['game_id']=$game_id;
        //关键字查询
         $keyWord = I('keyWord');
         if(!empty($keyWord)){
            $keyWord = '%'.$keyWord.'%';
            $where['union_name'] = ['like',$keyWord];
            $where['home_team_name']  = ['like',$keyWord];
            $where['away_team_name']  = ['like',$keyWord];
            $where['_logic'] = 'or';
            $map['_complex'] = $where;
         }
         //ID查询
         $id = I('id');
         if(!empty($id)){
            $id = '%'.$id.'%';
            $where['g.id'] = ['like',$id];
            $map['_complex'] = $where;
         }
         //时间查询
        if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
            if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                $startTime = strtotime($_REQUEST ['startTime']);
                $endTime   = strtotime($_REQUEST ['endTime'])+86400;
                $map['create_time'] = array('BETWEEN',array($startTime,$endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']);
                $map['create_time'] = array('EGT',$strtotime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime'])+86400;
                $map['create_time'] = array('ELT',$endTime);
            }
        }

        $list=$this->_list(D('GambleLogView'),$map);
        $this->assign('list',$list);
        $this->display();
    }
}
?>