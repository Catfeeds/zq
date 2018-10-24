<?php
use Think\Controller;
/**
 * 大数据预测管理
 * Created by PhpStorm.
 * User: dengwj
 */
class PredictiveModelController extends CommonController
{

    /**
     * 大数据预测列表
     */
    public function index(){
        $_REQUEST['numPerPage'] = 999999;
        $model = M('PredictiveModel');
        $map = $this->_search('PredictiveModel');
        //排序字段 默认为主键名
        $order = $_REQUEST ['_order'] ? : 'forecast_rate';
        $sort = $_REQUEST ['_sort'] == 'asc' ? 'asc' : 'desc';

        //预测类型  默认让球
        $predictive_type = I('predictive_type','1','int');
        $map['predictive_type'] = $predictive_type;
        if(empty($map['predictive_date'])){
            //默认显示最后一日
            $map['predictive_date'] = M('PredictiveModel')->order('predictive_date desc')->getField('predictive_date');
            $_REQUEST['predictive_date'] = $map['predictive_date'];
        }

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
                ->order( $order." ".$sort.",game_start_timestamp asc" )
                ->page($currentPage,$pageNum)
                ->select();

            //计算胜率
            $return_rate = $win = $half = $transport = $donate = $level = 0;
            foreach ($list as $k => $v) 
            {
                $yingkui = $this->getUseOdds($v['predictive_type'],$v['odds'],$v['score'],$v['state'],$v['recommend']);
                //盈亏
                $list[$k]['yingkui'] = $yingkui;
                //总盈亏
                $return_rate += $yingkui;
                //计算输赢平数量
                if($v['state'] == '1'){
                    $win++;
                }
                if($v['state'] == '0.5'){
                    $half++;
                }
                if($v['state'] == '-1'){
                    $transport++;
                }
                if($v['state'] == '-0.5'){
                    $donate++;
                }
                if($v['state'] == '2'){
                    $level++;
                }
                //判断选择显示
                switch ($v['predictive_type']) {
                    case '1':
                        //让球
                        $answer = $v['recommend'] == 1 ? switchName(0,$v['home_team_name']) : switchName(0,$v['away_team_name']);break;
                    case '2':
                        //大小
                        $answer = $v['recommend'] == 1 ? '大球' : '小球';break;
                    case '3':
                        //竞彩 1 胜 2平 3  4 胜-平 5 胜-负 6 平-负'
                        switch ($v['recommend']) {
                            case '1':$answer = '胜';break;
                            case '2':$answer = '平';break;
                            case '3':$answer = '负';break;
                            case '4':$answer = '胜-平';break;
                            case '5':$answer = '胜-负';break;
                            case '6':$answer = '平-负';break;
                        }
                    break;
                }
                $list[$k]['answer'] = $answer;
            }
            //计算胜率
            $winrate = getGambleWinrate($win,$half,$transport,$donate);
            $this->assign('winrate', [
                'win'       => $win,
                'half'      => $half,
                'level'     => $level,
                'transport' => $transport,
                'donate'    => $donate,
                'winrate'   => $winrate,
                'return_rate' => $return_rate,
            ]);
            //模板赋值显示
            $this->assign('list', $list);
            $this->assign ( 'totalCount', $count );
            $this->assign ( 'numPerPage', $pageNum ); //每页显示多少条
            $this->assign ( 'currentPage', $currentPage);
            $this->setJumpUrl();
        }
        
        $this->display();
    }

    /**
     * 根据类型与选择获取对应赔率
     * @param  $predictive_type  预测模型预测类型 1 让球 2 大小 3 竞彩
     * @param  $oddss            所计算的赔率 
     * @param  $score            比分
     * @param  $state            推荐的结果 0:未出 1:红 2:走 -1:黑 0.5红半 -0.5黑半
     * @param  $recommend        推荐的队伍 让球: 1 主队 2 客队 大小球： 1 大球 2 小球  竞彩：1 胜 2平 3负 4 胜-平 5 胜-负 6 平-负
     */
    public function getUseOdds($predictive_type,$oddss,$score,$state,$recommend){
        if(in_array($state, [0,2])){
            //未出和平返回0
            return 0;
        }
        //赔率分割
        $oddsArr = explode('|', $oddss);
        //根据选择获取对应赔率
        switch ($predictive_type) {
            case '1':
            case '2':
                //让球和大小
                switch ($recommend) {
                    case '1':
                        $odds = $oddsArr[0];
                        break;
                    case '2':
                        $odds = $oddsArr[2];
                }
                break;
            case '3':
                //竞彩
                $scoreArr = explode('-', $score);
                $home_score = $scoreArr[0];
                $away_score = $scoreArr[1];
                //竞彩 1 胜 2平 3  4 胜-平 5 胜-负 6 平-负'
                switch ($recommend) {
                    case '1':
                        //胜
                        $odds = $oddsArr[0]-1;
                        break;
                    case '2':
                        //平
                        $odds = $oddsArr[1]-1;
                        break;
                    case '3':
                        //负
                        $odds = $oddsArr[2]-1;
                        break;
                    case '4':
                        //胜-平
                        if($home_score > $away_score){
                            $odds = $oddsArr[0]-2;
                        }
                        if($home_score == $away_score){
                            $odds = $oddsArr[1]-2;
                        }
                        break;
                    case '5':
                        //胜-负
                        if($home_score > $away_score){
                            $odds = $oddsArr[0]-2;
                        }
                        if($home_score < $away_score){
                            $odds = $oddsArr[2]-2;
                        }
                        break;
                    case '6':
                        //平-负
                        if($home_score == $away_score){
                            $odds = $oddsArr[1]-2;
                        }
                        if($home_score < $away_score){
                            $odds = $oddsArr[2]-2;
                        }
                        break;
                }
            break;
        }
        //根据推荐的结果计算该场预测盈亏
        if($state == '1'){
            $return_rate = $odds * 100; //该场比赛赢的回报率：+100%X赔率
        }elseif($state == '0.5'){
            $return_rate = $odds * 50; //该场比赛赢半的回报率：+50%X赔率
        }elseif($state == '-1'){
            //该场比赛输的回报率-100(如果为竞彩并且推荐两个-200)
            $return_rate = $predictive_type == 3 && in_array($recommend, [4,5,6]) ? -200 : -100;
        }elseif($state == '-0.5'){
            $return_rate = -50; //该场比赛输半的回报率:-50
        }
        return $return_rate;
    }

    /**
     * 修改、添加操作
     *
     */
    public function  edit()
    {
        $id = I('id');
        if(!empty($id)) {
            $vo = M('PredictiveModel')->where(['id'=>$id])->find();
            if(!$vo) $this->error('参数错误');
        }

        $this->assign('vo', $vo);
        $this->display();
    }

    /**
     * 保存操作
     */
    //增加修改用户信息
    public function save(){
        $id = I('id');
        if(empty($id)){
            $this->error('参数错误!');
        }
        $model = D('PredictiveModel');

        if (false === $model->create()) $this->error($model->getError());

        //保存后台操作人id
        $model->admin_id = $_SESSION['authId'];
        $rs = $model->where(['id'=>$id])->save();

        if ($rs) {
            //成功提示
            $this->success('保存成功!', cookie('_currentUrl_'));
        } else {
            //错误提示
            $this->error('保存失败!');
        }
    }

    /**
     * 修改、添加操作
     *
     */
    public function  listEdit()
    {
        $id = I('id');
        if(!empty($id)) {
            $res = M('PredictiveModelList')->where(['id'=>$id])->find();
            if(!$res) $this->error('参数错误');

            $res['img'] = Tool::imagesReplace($res['img']);
        }

        //所有分类
        $classList = M("PredictiveModel")->where(['status' => 1])->order('sort asc')->getField('sign, name');

        $this->assign('classList', $classList);
        $this->assign('data', $res);
        $this->display();
    }

    /**
     * 配置
     */
    public function config(){
        $config = M('config')->where(['sign' => 'PredictiveModelConfig'])->getField('config');

        if(IS_POST) {
            $data = I('config');
            if(empty($config)){
                $rs =  M('config')->add(['sign' => 'PredictiveModelConfig','config' => json_encode($data)]);
            }else{
                $rs =  M('config')->where(['sign' => 'PredictiveModelConfig'])->save(['config' => json_encode($data)]);
            }
            
            if ($rs !== false)
                $this->success('保存成功');

            $this->error('保存失败!');
        }else{
            $this->assign('config', json_decode($config, true));
        }

        $this->display();
    }
	
    
    public function dataView()
    {
	    $start = I('startTime');
	    $end = I('endTime');
	    
	    if (empty($start)) {
	    	$start =  C('predictiveModelStartDate');
	    }
	    
	    if (empty($end)) {
	    	$end = date('Y-m-d');
	    }
	    
	    if ($start > $end) {
	    	$start = C('predictiveModelStartDate');
		    $end = date('Y-m-d');
	    }
		$data = $this->getDateAccumulative($start, $end);
		$this->assign('data',json_encode($data));
	    $this->display();
    }
    
	
	
	public function getDateAccumulative($start, $end)
	{
		$map['predictive_date']= [['elt', $end], ['egt', $start]];
		$data  = M('predictiveFigure')->field('asia_income, asia_win, asia_draw, asia_lost ,bs_income, bs_win, bs_draw, bs_lost,smg_income, smg_win, smg_draw, smg_lost, predictive_date')->where($map)->order('predictive_date desc')->select();
		$data = array_reverse($data);
		$service = new \Api530\Services\AppfbService();
		$accumulative = $service->accumulativeIncome($data);
		foreach ($accumulative as $key => $value) {
			$accumulative[$key]['asia_accumulative'] = round($value['asia_accumulative']);
			$accumulative[$key]['bs_accumulative'] = round($value['bs_accumulative']);
			$accumulative[$key]['smg_accumulative'] = round($value['smg_accumulative']);
		}
		return $accumulative;
	}
 
 
}