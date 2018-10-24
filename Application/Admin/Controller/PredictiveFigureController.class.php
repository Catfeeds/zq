<?php
/**
 * 每日预测模型统计列表控制器
 * @author dengwj <406516482@qq.com>
 * @since  2017-8-21
 */
class PredictiveFigureController extends CommonController {
    /**
     * Index页显示
     *
     */
    public function index() {
        $model = M('PredictiveFigure');
        $map = $this->_search('PredictiveFigure');
        //排序字段 默认为主键名
        $order = $_REQUEST ['_order'] ? : 'predictive_date';
        $sort = $_REQUEST ['_sort'] == 'asc' ? 'asc' : 'desc';

        //时间查询
        if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
            if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                $startTime = ($_REQUEST ['startTime']);
                $endTime   = ($_REQUEST ['endTime']);
                $map['predictive_date'] = array('BETWEEN',array($startTime,$endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = ($_REQUEST ['startTime']);
                $map['predictive_date'] = array('EGT',$strtotime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = ($_REQUEST['endTime']);
                $map['predictive_date'] = array('ELT',$endTime);
            }
        }
        //默认当月第一天
        if(empty($map['predictive_date'])){
            $startTime = date('Y-m-01', strtotime(date("Y-m-d")));
            $_REQUEST['startTime'] = $startTime;
            $map['predictive_date'] = array('EGT',$startTime);
        }
        //类型判断
        $predictive_type = I('predictive_type',1,'int');
        
        //取得满足条件的记录数
        $count = $model->alias('p')->where($map)->count();

        if ($count > 0)
        {
            //导出Excel
            $Export=I('Export');
            if(!empty($Export))
            {
                $list = $model->where($map)->order($order." ".$sort)->select();
                $this->downExport($list,$predictive_type);
                exit;
            }

            //分页查询数据
            $list = $model->alias('p')
                ->field("p.*")
                ->where($map)
                ->group('p.id')
                ->order( $order." ".$sort )
                ->select();

            $countArr = $this->getCount($list,$predictive_type);
            $this->assign('countArr', $countArr);

            //模板赋值显示
            $this->assign('list', $list);
            $this->assign ( 'totalCount', $count );
            $this->setJumpUrl();
        }
        
        $this->display();
    }

    //统计预测数
    public function getCount($list,$predictive_type){
        switch ($predictive_type) {
            case '1':
                $strSign = 'asia';
                $strType = '亚盘';
                break;
            case '2':
                $strSign = 'bs';
                $strType = '大小';
                break;
            case '3':
                $strSign = 'smg';
                $strType = '竞彩';
                break;
        }
        foreach ($list as $k => $v) {
            $list[$k]['todayIncome'] = getModelTodayIncome($v[$strSign.'_income'],$v[$strSign.'_win'],$v[$strSign.'_lost']);
        }

        $this->assign ( 'predictive_type', $predictive_type);
        $this->assign ( 'strSign', $strSign);
        $this->assign ( 'strType', $strType);

        $listCount = count($list);

        $countArr = [];
        //总预测数
        $countArr['numCount'] = array_sum(array_column($list,$strSign.'_num'));
        //红
        $countArr['winCount'] = array_sum(array_column($list,$strSign.'_win'));
        //走
        $countArr['drawCount'] = array_sum(array_column($list,$strSign.'_draw'));
        //黑
        $countArr['lostCount'] = array_sum(array_column($list,$strSign.'_lost'));
        //自然平均胜率
        $countArr['source_winrateCount'] = round(array_sum(array_column($list,$strSign.'_source_winrate')) / $listCount);
        //自然总盈亏
        $countArr['source_incomeCount'] = array_sum(array_column($list,$strSign.'_source_income'));
        //人工平均胜率
        $countArr['winrateCount'] = round(array_sum(array_column($list,$strSign.'_winrate')) / $listCount);
        //人工总盈亏
        $countArr['incomeCount'] = array_sum(array_column($list,$strSign.'_income'));
        //总回报率
        $countArr['AllIncomeCount'] = getModelTodayIncome($countArr['incomeCount'] , $countArr['winCount'] , $countArr['lostCount']);
        $countArr['strSign'] = $strSign;
        $countArr['strType'] = $strType;
        return $countArr;
    }

    /**
     * 导出Excel
     * @param string $filename [文件名，当为空时就以当前日期为文件名]
     * @param list $list [列表数据]
     * @param $totalUser 涉及人数
    **/
    public function downExport($list,$predictive_type)
    {
        $countArr = $this->getCount($list,$predictive_type);
        $filename  = date('Y-m-d');
        $strTable  ='<table width="700" border="1">';
        $strTable .= '<tr>';
        $strTable .= '<th colspan="11" style="text-align:center;font-size:12px;" width="*">'.$countArr['strType'].'预测数:'.$countArr['numCount'].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $strTable .= '红:'.$countArr['winCount'].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $strTable .= '走:'.$countArr['drawCount'].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $strTable .= '黑:'.$countArr['lostCount'].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $strTable .= '自然平均胜率:'.$countArr['source_winrateCount'].'%&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $strTable .= '自然总盈亏:'.$countArr['source_incomeCount'].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $strTable .= '人工平均胜率:'.$countArr['winrateCount'].'%&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $strTable .= '总交易额:'.($countArr['numCount'] * 100).'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $strTable .= '人工总盈亏:'.$countArr['incomeCount'].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $strTable .= '总回报:'.$countArr['AllIncomeCount'].'%';
        $strTable .= '</th></tr>';
        $strTable .= '<tr>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">ID</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">日期</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">预测数</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">红</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">走</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">黑</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">自然胜率</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">自然盈亏</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">人工胜率</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">人工盈亏</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">回报率</th>';
        $strTable .= '</tr>';
        foreach($list as $k=>$v){
            $todayIncome = getModelTodayIncome($v[$countArr['strSign'].'_income'],$v[$countArr['strSign'].'_win'],$v[$countArr['strSign'].'_lost']);
            $daoqi = $v['predictive_model_vip'] < strtotime(date(Ymd)) ? '已到期' : '未到期';
            $strTable .= '<tr>'.
                            '<td style="text-align:left;font-size:12px;">'.$v['id'].'</td>'.
                            '<td style="text-align:left;font-size:12px;">'.$v['predictive_date'].'</td>'.
                            '<td style="text-align:left;font-size:12px;">'.$v[$countArr['strSign'].'_num'].'</td>'.
                            '<td style="text-align:left;font-size:12px;">'.$v[$countArr['strSign'].'_win'].'</td>'.
                            '<td style="text-align:left;font-size:12px;">'.$v[$countArr['strSign'].'_draw'].'</td>'.
                            '<td style="text-align:left;font-size:12px;">'.$v[$countArr['strSign'].'_lost'].'</td>'.
                            '<td style="text-align:left;font-size:12px;">'.$v[$countArr['strSign'].'_source_winrate'].'%</td>'.
                            '<td style="text-align:left;font-size:12px;">'.$v[$countArr['strSign'].'_source_income'].'</td>'.
                            '<td style="text-align:left;font-size:12px;">'.$v[$countArr['strSign'].'_winrate'].'%</td>'.
                            '<td style="text-align:left;font-size:12px;">'.$v[$countArr['strSign'].'_income'].'</td>'.
                            '<td style="text-align:left;font-size:12px;">'.$todayIncome.'%</td>'.
                          '</tr>';
        }
        $strTable .='</table>';
        downloadExcel($strTable,$filename);
        exit();
    }
}