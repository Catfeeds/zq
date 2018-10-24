<?php
/**
 * 用户等级程序控制器
 *
 * @author dengweijun <406516482@qq.com>
 *
 * @since  2016-6-2
 */
class UserLvController extends CommonController {
    /**
    *构造函数
    *
    * @return  #     
    */
    public function _initialize()
    {
        parent::_initialize();
    }
    /**
     * Index页显示
     */
    public function index() {
        //列表过滤器，生成查询Map对象
        $map = $this->_search('FrontUser');

        //是否机器人竞猜记录筛选
        $is_robot = I('is_robot');
        if($is_robot != ''){
            $robotId = M('FrontUser')->where(['is_robot'=>1])->field('id')->select();
            foreach ($robotId as $key => $value) {
                $userIdArr[] = $value['id'];
            }
            if($is_robot == 1){
                $map['user_id'] = ['IN',$userIdArr];
            }else{
                $map['user_id'] = ['not in',$userIdArr];
            }
        }
        $gameType = I('gameType');
        $sort = $gameType == 1 ? 'lv' : 'lv_bk';
        $userLv = I('userLv');
        if($userLv != '')
        {
            $map[$sort] = $userLv;
            //手动指定显示条数
            $_REQUEST ['numPerPage'] = 999999;
        }
        //手动获取列表
        $list = $this->_list(CM("FrontUser"), $map , $sort);
        //获取配置
        $sign = $gameType == 1 ? 'fbConfig' : 'bkConfig';
        $gameConf = getWebConfig($sign);
        $this->assign('userLvDays',$gameConf['userLvDays']);
        foreach ($list as $k => $v) {
            //获取上周或上月竞猜数据
            $CountWinrate = D('GambleHall')->CountWinrate($v['id'],$gameType,$gameConf['userLvDays'],true);
            $list[$k]['winrate']    = $CountWinrate['winrate'];
            $list[$k]['count']      = $CountWinrate['count'];
            $list[$k]['win']        = $CountWinrate['win'];
            $list[$k]['half']       = $CountWinrate['half'];
            $list[$k]['level']      = $CountWinrate['level'];
            $list[$k]['transport']  = $CountWinrate['transport'];
            $list[$k]['donate']     = $CountWinrate['donate'];
            $list[$k]['pointCount'] = $CountWinrate['pointCount'];
        }
        //对数组进行排序,先按等级->胜率->积分->场次->赢场次->赢半场次
        foreach ($list as $v) {
            $lv[]       = $v[$sort];
            $winrate[]  = $v['winrate'];
            $ponit[]    = $v['pointCount'];
            $count[]    = $v['count'];
            $win[]      = $v['win'];
            $half[]     = $v['half'];
        }
        array_multisort($lv, SORT_DESC,$winrate, SORT_DESC,$ponit ,SORT_DESC,$count, SORT_DESC,$win, SORT_DESC,$half, SORT_DESC, $list);
        $this->assign('list', $list);
        $this->display();
    }
	//用户竞彩等级排行列表
    public function BettingLv() {
        //列表过滤器，生成查询Map对象
        $map = $this->_search('FrontUser');

        //是否机器人竞猜记录筛选
        $is_robot = I('is_robot');
        if($is_robot != ''){
            $robotId = M('FrontUser')->where(['is_robot'=>1])->field('id')->select();
            foreach ($robotId as $key => $value) {
                $userIdArr[] = $value['id'];
            }
            if($is_robot == 1){
                $map['user_id'] = ['IN',$userIdArr];
            }else{
                $map['user_id'] = ['not in',$userIdArr];
            }
        }
        $gameType = 1;
        $sort = 'lv_bet';
        $userLv = I('userLv');
        if($userLv != '')
        {
            $map[$sort] = $userLv;
            //手动指定显示条数
            $_REQUEST ['numPerPage'] = 999999;
        }
        //手动获取列表
        $list = $this->_list(CM("FrontUser"), $map , $sort);
        //获取配置
        $sign = 'betConfig';
        $gameConf = getWebConfig($sign);
        $this->assign('userLvDays',$gameConf['userLvDays']);
        foreach ($list as $k => $v) {
            //获取上周或上月竞猜数据
            $CountWinrate = D('GambleHall')->CountWinrate($v['id'],$gameType,$gameConf['userLvDays'],true,false,0,2);
            $list[$k]['winrate']    = $CountWinrate['winrate'];
            $list[$k]['count']      = $CountWinrate['count'];
            $list[$k]['win']        = $CountWinrate['win'];
            $list[$k]['half']       = $CountWinrate['half'];
            $list[$k]['level']      = $CountWinrate['level'];
            $list[$k]['transport']  = $CountWinrate['transport'];
            $list[$k]['donate']     = $CountWinrate['donate'];
            $list[$k]['pointCount'] = $CountWinrate['pointCount'];
        }
        //对数组进行排序,先按等级->胜率->积分->场次->赢场次->赢半场次
        foreach ($list as $v) {
            $lv[]       = $v[$sort];
            $winrate[]  = $v['winrate'];
            $ponit[]    = $v['pointCount'];
            $count[]    = $v['count'];
            $win[]      = $v['win'];
            $half[]     = $v['half'];
        }
        array_multisort($lv, SORT_DESC,$winrate, SORT_DESC,$ponit ,SORT_DESC,$count, SORT_DESC,$win, SORT_DESC,$half, SORT_DESC, $list);
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 刷新用户等级
     */
    public function breakUserLv() {
        if(date(N) != 1){
            $this->error('只能周一结算');
        }
        $gameType = $_REQUEST['gameType'] ? : 1;
        $betting = $_REQUEST['betting'] ? : 0;
        //日期筛选
        list($begin,$end) = getRankBlockDate($gameType,1);
        //判断足球还是篮球,获取对应配置
        switch ($gameType) {
            case '1':
                $Lv = $betting == 1 ? 'lv_bet' : 'lv';
                $sign = $betting == 1 ? 'betConfig' :'fbConfig';
                $gameModel = 'qc_gamble';
                $time = C('fb_gamble_time');
                $where['g.play_type'] = $betting == 1 ? ['in', [-2,2]] : ['in', [-1,1]];
                break;
            case '2':
                $Lv = 'lv_bk';
                $sign = 'bkConfig';
                $gameModel = 'qc_gamblebk';
                $time = C('bk_gamble_time');
                $where['g.play_type'] = ['in', [-1,1]];
                break;
        }
        
        //获取等级配置
        $gameConf = getWebConfig($sign);
        $oneLv = $gameConf['userLv'][1]['gameTimes']; //1级等级条件

        //找出所有用户
        //$FrontUser = M("FrontUser")->field('id as user_id')->select();
        
        $where['g.result']     = array("IN",array('1','0.5','2','-1','-0.5'));
        $where['g.create_time']  = array( "between",array( strtotime($begin.$time) , strtotime($end.$time)+86400 ) );

        $FrontUser = M("FrontUser f")
            ->join("LEFT JOIN {$gameModel} g on g.user_id = f.id")
            ->where(array('f.status'=>1))->where($where)
            ->field('f.id as user_id,f.username,f.nick_name,f.is_robot,count(g.id) as gameCount,group_concat(g.result) as result')
            ->group('f.id')->having("gameCount >= {$oneLv}")->select();

        $userRanking = getGambleRate($FrontUser, $gameType);
    
        foreach ($userRanking as $k => $v) {
            //计算等级
            $UserLv = self::getUserLv($v['winrate'],$v['gameCount'],$gameConf['userLv']);
            $saveUserArray[] = '('.implode(',', [
                $v['user_id'],
                $UserLv
            ]).')';
        }
        //等级清0
        $rs = M('FrontUser')->where(['id'=>['gt',0]])->save([$Lv => 0]);
        
        $UserSql = $this->replaceSql('qc_front_user',['id',$Lv],$saveUserArray);
        M()->execute($UserSql);
        echo '刷新成功！';
        die;
    }

    //拼装推荐记录数据sql
    public function replaceSql($table,$fieldArr,$data){
        $value = implode(',', $data);
        $field = implode(',', $fieldArr);
        foreach ($fieldArr as $k => $v) {
            $fieldStrArr[] = "{$v}=VALUES({$v})";
        }
        $fieldStr = implode(',', $fieldStrArr);
        $sql = "INSERT INTO {$table} ({$field})
                VALUES {$value}
                ON DUPLICATE KEY UPDATE {$fieldStr}";
        return $sql;
    }

    /**
     * 获取用户等级
     * $winrate   胜率
     * $count     竞猜场数
     * $userLv    等级配置
     */
    public function getUserLv($winrate,$count,$userLv)
    {
        foreach ($userLv as $k => $v) 
        {
            //是否达到等级要求
            if($count >= $v['gameTimes'] && $winrate >= $v['winPercent'])
            {
                $LvArray[] = $k;
            }
        }
        if(is_array($LvArray))
        {
            //返回最大等级
            return max($LvArray);
        }else{
            //返回0级
            return 0;
        }
    }

    //数据重置
    public function resetGambleData()
    {
        $user_id = $_REQUEST['user_id'];
        $gambleType = $_REQUEST['gambleType'];
        $rs = D('GambleHall')->resetGambleData($user_id,1,$gambleType);
        switch ($rs) {
            case '1072':
                $this->error('金币不足');
                break;
            case '1073':
                $this->error('重置失败');
                break;
            case '1074':
                $this->error('无数据重置哦');
                break;
            case '1':
                $this->success('重置成功');
                break;
        }
    }
}