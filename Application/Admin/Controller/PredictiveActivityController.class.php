<?php
/**
 * 预测模型免费日活动列表控制器
 * @author dengwj <406516482@qq.com>
 * @since  2017-8-16
 */
class PredictiveActivityController extends CommonController {
    /**
     * Index页显示
     *
     */
    public function index() {
        $model = M('PredictiveActivity');
        $map = $this->_search('PredictiveActivity');
        //排序字段 默认为主键名
        $order = $_REQUEST ['_order'] ? : 'id';
        $sort = $_REQUEST ['_sort'] == 'asc' ? 'asc' : 'desc';

        $status = I('status');
        if ($status != '')
        {
            unset($map['status']);
            $map['p.status'] = $status;
        }

        //取得满足条件的记录数
        $count = $model->alias('p')
        ->join('LEFT JOIN qc_user u on u.id=p.admin_id')
        ->where($map)->count();

        if ($count > 0)
        {
            $pageNum     = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
            $currentPage = !empty($_REQUEST[C('VAR_PAGE')]) ? $_REQUEST[C('VAR_PAGE')]:1;
            //分页查询数据
            $list = $model->alias('p')
                ->join('LEFT JOIN qc_user u on u.id=p.admin_id')
                ->field("p.*,u.nickname")
                ->where($map)
                ->group('p.id')
                ->order( $order." ".$sort )
                ->page($currentPage,$pageNum)
                ->select();

            //模板赋值显示
            $this->assign('list', $list);
            $this->assign ( 'totalCount', $count );
            $this->assign ( 'numPerPage', $pageNum ); //每页显示多少条
            $this->assign ( 'currentPage', $currentPage);
            $this->setJumpUrl();
        }
        
        $this->display();
    }
    public function add() {

        $this->display();
    }

    public function edit() {
        $id = I('id');
        $vo = M("PredictiveActivity")->find($id);
        if (!$vo){
            $this->error('参数错误');
        }
        $this->assign('vo', $vo);
        $this->display("add");
    }

    /**
     * 保存/修改记录
     *
     * @return #
    */
    public function save(){
        $id = I('id', 'int');
        $model = D('PredictiveActivity');
        if (!$data = $model->create()) {
            $this->error($model->getError());
        }
        $model->start_time = strtotime($model->start_time);
        $model->end_time   = strtotime($model->end_time);
        if($model->start_time > $model->end_time){
            $this->error('活动开始时间必须小于结束时间!');
        }

        if (empty($id)) {
            //为新增
            $model->create_time  = time();
            $model->admin_id     = $_SESSION['authId'];
            $rs = $model->add();
        }else{
            //为修改
            $rs = $model->save();
        }
        if ($rs !== false) {
            //成功提示
            $this->success('保存成功!',cookie('_currentUrl_'));
        } else {
            //错误提示
            $this->error('保存失败!');
        }
    }

    /**
     * 统计会员开通列表
     */
    public function vipList(){
        $map = $this->_search('FrontUser');
        $map['predictive_model_vip'] = ['gt',0];

        $vip_type = I('vip_type');
        if($vip_type != ''){
            if($vip_type == 1){
                //已到期
                $map['predictive_model_vip'] = [['gt',0],['lt',strtotime(date(Ymd))]];
            }else{
                //未到期
                $map['predictive_model_vip'] = ['egt',strtotime(date(Ymd))];
            }
        }

        //到期时间查询
        if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
            if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                $startTime = strtotime($_REQUEST ['startTime']);
                $endTime   = strtotime($_REQUEST ['endTime'])+86399;
                $map['predictive_model_vip'] = array('BETWEEN',array($startTime,$endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']);
                $map['predictive_model_vip'] = array('EGT',$strtotime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime']) +86399;
                $map['predictive_model_vip'] = array(array('gt',0),array('ELT',$endTime));
            }
        }
        //导出Excel
        $Export=I('Export');
        if(!empty($Export))
        {
            if(empty($map)) $limit = 1000;
            $list = CM('FrontUser')->where($map)->order("predictive_model_vip desc")->limit($limit)->select();
            $this->downExport($list);
            exit;
        }
        $list = $this->_list(CM('FrontUser'),$map,"predictive_model_vip");
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 导出Excel
     * @param string $filename [文件名，当为空时就以当前日期为文件名]
     * @param list $list [列表数据]
     * @param $totalUser 涉及人数
    **/
    public function downExport($list)
    {
        $filename  = date('Y-m-d');
        $strTable  ='<table width="500" border="1">';
        $strTable .= '<tr>';
        $strTable .= '<th style="text-align:center;font-size:12px;">统计开通总人数:</th>';
        $strTable .= '<td style="text-align:center;font-size:12px;color:red;">'.count($list).'</td>';
        $strTable .= '</tr>';
        $strTable .= '<tr>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">用户ID</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">昵称</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">会员到期时间</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">是否到期</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">注册时间</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">上次登录</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">登录ip</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">登录版本</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">登录次数</th>';
        $strTable .= '</tr>';
        foreach($list as $k=>$v){
            $daoqi = $v['predictive_model_vip'] < strtotime(date(Ymd)) ? '已到期' : '未到期';
            $strTable .= '<tr>'.
                            '<td style="text-align:left;font-size:12px;">'.$v['id'].'</td>'.
                            '<td style="text-align:left;font-size:12px;">'.$v['nick_name'].'</td>'.
                            '<td style="text-align:left;font-size:12px;">'.date('Y/m/d',$v['predictive_model_vip']).'</td>'.
                            '<td style="text-align:left;font-size:12px;">'.$daoqi.'</td>'.
                            '<td style="text-align:left;font-size:12px;">'.date('Y/m/d H:i:s',$v['reg_time']).'</td>'.
                            '<td style="text-align:left;font-size:12px;">'.date('Y/m/d H:i:s',$v['login_time']).'</td>'.
                            '<td style="text-align:left;font-size:12px;">'.$v['last_ip'].'</td>'.
                            '<td style="text-align:left;font-size:12px;">'.$v['last_login_ver'].'</td>'.
                            '<td style="text-align:left;font-size:12px;">'.$v['login_count'].'</td>'.
                          '</tr>';
        }
        $strTable .='</table>';
        downloadExcel($strTable,$filename);
        exit();
    }
}