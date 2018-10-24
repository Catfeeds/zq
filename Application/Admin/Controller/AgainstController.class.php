<?php
/**
 * 赛事对阵列表控制器
 *
 * @author dengweijun <406516482@qq.com>
 *
 * @since  2015-12-21
 */
class AgainstController extends CommonController {
    public $gameType;
    /**
    *构造函数
    *
    * @return  #
    */
    public function _initialize()
    {
        parent::_initialize();
        $gameType = $_REQUEST['gameType'];
        $this->gameType = $gameType;
    }

    /**
     * Index页显示
     *
     */
    public function index() {
        if($this->gameType == 1){
            //列表过滤器，生成查询Map对象
            $map = $this->_search('gameFbinfo');

            //时间查询
            if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
                if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                    $startTime = strtotime($_REQUEST ['startTime']);
                    $endTime   = strtotime($_REQUEST ['endTime']);
                    $map['gtime'] = array('BETWEEN',array($startTime,$endTime));
                } elseif (!empty($_REQUEST['startTime'])) {
                    $strtotime = strtotime($_REQUEST ['startTime']);
                    $map['gtime'] = array('EGT',$strtotime);
                } elseif (!empty($_REQUEST['endTime'])) {
                    $endTime = strtotime($_REQUEST['endTime']);
                    $map['gtime'] = array('ELT',$endTime);
                }
            }
            $is_video = I('isVideo');
            if(!empty($is_video)){
                $map['is_video'] = 1;
            }
            $game_id = I('game_id');
            if(!empty($game_id)){
                $map['game_id'] = $game_id;
            }
            //动画相关赛程筛选
            $gtime = I('gtime');
            if(!empty($gtime)){
                $map['gtime'] = ['BETWEEN',[$gtime-300,$gtime+300]];
            }
            //没搜索条件默认今天起的
            if(empty($map)){
                $map['gtime'] = ['egt',strtotime(date('Ymd'))];
                $desc = 'gtime asc';
            }else{
                $desc = 'gtime desc';
            }
            $list = $this->_list(D("gameFbinfo"), $map,$desc,NULL);
            $list = HandleGamble($list);
			//分组--以比赛时间相同为一组
			$dateTimeGroup = array();
			foreach ($list as $k => $v)
			{
				$dateTimeGroup[strtotime($v['game_date'])][] = [$v['id'],$v['home_team_name'],$v['away_team_name']];
			}
			//判别那个赛程出现相同
			foreach ($dateTimeGroup as $key => $value)
			{
				foreach ($value as $k => $v)
				{
					foreach ($value as $k1 => $v1)
					{
						//不同的赛程id、比赛日期相同，并主队名称、客队名称对应相同时或并主队名称、客队名称交叉相同时
						if (($v[0] != $v1[0] && $v[1] == $v1[1] && $v[2] == $v1[2])
							|| ($v[0] != $v1[0] && $v[1] == $v1[2] && $v[2] == $v1[1]))
						{
							$dateTimeGroup[$key][$k]['background'] = 1;
						}
					}
				}
			}
			foreach ($list as $key => $value)
			{
				foreach ($dateTimeGroup as $k => $v)
				{
					if (strtotime($value['game_date']) == $k)
					{
						foreach ($v as $k1 => $v1)
						{
							if ($v1['background'] == 1 && $v1[0] == $value['id'])
							{
								$list[$key]['background'] = 1;
								break;
							}
						}
						if ($list[$key]['background'] == 1)
							break;
					}
				}
			}

			unset($dateTimeGroup);
            $this->assign('list', $list);
            $this->display();
        }else{
            //列表过滤器，生成查询Map对象
            $map = $this->_search('GameBkinfo');

            //时间查询
            if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
                if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                    $startTime = strtotime($_REQUEST ['startTime']);
                    $endTime   = strtotime($_REQUEST ['endTime']);
                    $map['gtime'] = array('BETWEEN',array($startTime,$endTime));
                } elseif (!empty($_REQUEST['startTime'])) {
                    $strtotime = strtotime($_REQUEST ['startTime']);
                    $map['gtime'] = array('EGT',$strtotime);
                } elseif (!empty($_REQUEST['endTime'])) {
                    $endTime = strtotime($_REQUEST['endTime']);
                    $map['gtime'] = array('ELT',$endTime);
                }
            }

            //动画相关赛程筛选
            $gtime = I('gtime');
            if(!empty($gtime)){
                $map['gtime'] = ['BETWEEN',[$gtime-300,$gtime+300]];
            }

            $is_video = I('isVideo');
            if(!empty($is_video)){
                $map['is_video'] = 1;
                $map['game_date'] = ['egt',date('Ymd')];
            }
            //没搜索条件默认今天起的
            if(empty($map)){
                $map['gtime'] = ['egt',strtotime(date('Ymd'))];
                $desc = 'gtime asc';
            }else{
                $desc = 'gtime desc';
            }
            //手动获取列表
            $list = $this->_list(D("GameBkinfo"), $map,$desc,NULL);//echo M()->_sql();
            /*$listAll = M('GameBkinfo g')
                        ->where($map)
                        ->join('LEFT JOIN qc_bk_union u ON u.union_id = g.union_id')
                        ->join('LEFT JOIN qc_gamblebk_number gn ON gn.game_id = g.game_id')
                        ->order('g.game_date ASC,g.game_time ASC')
                        ->select();
            $listAll = HandleGamble($listAll);*/
            $list = HandleGamble($list);
            //分组--以比赛时间相同为一组
            $dateTimeGroup = array();
            foreach ($list as $k => $v)
            {
                $dateTimeGroup[strtotime($v['game_date'])][] = [$v['id'],$v['home_team_name'],$v['away_team_name']];
            }
            //判别那个赛程出现相同
            foreach ($dateTimeGroup as $key => $value)
            {
                foreach ($value as $k => $v)
                {
                    foreach ($value as $k1 => $v1)
                    {
                        //不同的赛程id、比赛日期相同，并主队名称、客队名称对应相同时或并主队名称、客队名称交叉相同时
                        if (($v[0] != $v1[0] && $v[1] == $v1[1] && $v[2] == $v1[2])
                            || ($v[0] != $v1[0] && $v[1] == $v1[2] && $v[2] == $v1[1]))
                        {
                            $dateTimeGroup[$key][$k]['background'] = 1;
                        }
                    }
                }
            }
            foreach ($list as $key => $value)
            {
                foreach ($dateTimeGroup as $k => $v)
                {
                    if (strtotime($value['game_date']) == $k)
                    {
                        foreach ($v as $k1 => $v1)
                        {
                            if ($v1['background'] == 1 && $v1[0] == $value['id'])
                            {
                                $list[$key]['background'] = 1;
                                break;
                            }
                        }
                        if ($list[$key]['background'] == 1)
                            break;
                    }
                }
            }
            $this->assign('list', $list);
            $this->display("bask_index");
        }
    }
    /**
     * 足球竞彩对阵
     * @author liangzk <1343724998@qq.com>
     * @Date 2016-07-19 Time 14:24
     * @version 1.0
     */
    public function betoddsAgainst()
    {
        $map = $this->_search('GameFbinfo');
        //时间查询
        if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
            if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                $startTime = strtotime($_REQUEST ['startTime']);
                $endTime   = strtotime($_REQUEST ['endTime'])+86400;
                $map['gtime'] = array('BETWEEN',array($startTime,$endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']);
                $map['gtime'] = array('EGT',$strtotime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime'])+86400;
                $map['gtime'] = array('ELT',$endTime);
            }
        }
        //竞彩
        $map['is_betting'] = 1;

        $code = I('code');
        if (! empty($code))
        {
            switch ($code)
            {
                case 1: $map['bet_code'] = ['like','%周一%']; break;
                case 2: $map['bet_code'] = ['like','%周二%']; break;
                case 3: $map['bet_code'] = ['like','%周三%']; break;
                case 4: $map['bet_code'] = ['like','%周四%']; break;
                case 5: $map['bet_code'] = ['like','%周五%']; break;
                case 6: $map['bet_code'] = ['like','%周六%']; break;
                case 7: $map['bet_code'] = ['like','%周日%']; break;
            }

        }
        $list = $this->_list(D('BetoddsAgainstView'),$map,'let_exp');
        $list = HandleGamble($list);
        $this->assign('list',$list);
        $this->display();
    }

    //文字直播数据查看
    public function fb_textliving()
    {
        $game_id = I('game_id');
        $appService = new \Home\Services\AppfbService();
        $fontText = $appService->getTextliving($game_id);
        $fontText = array_reverse($fontText);
        $this->assign('list', $fontText);
        $this->display();
    }

    /*
     * 足球竞猜列表
     * @author liangzk <1343724998@qq.com>
     * @Date 2016-07-15 Time 15:24
     * @version 1.0
     */
    public function fb_betodds()
    {
        //过滤
        $map = $this->_search('FbBetodds');
        //时间查询
        if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
            if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                $startTime = strtotime($_REQUEST ['startTime']);
                $endTime   = strtotime($_REQUEST ['endTime'])+86400;
                $map['update_time'] = array('BETWEEN',array($startTime,$endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']);
                $map['update_time'] = array('EGT',$strtotime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime'])+86400;
                $map['update_time'] = array('ELT',$endTime);
            }
        }
        //竞彩码
        $code = I('code');
        if (! empty($code))
        {
            switch ($code)
            {
                case 1: $map['bet_code'] = ['like','%周一%']; break;
                case 2: $map['bet_code'] = ['like','%周二%']; break;
                case 3: $map['bet_code'] = ['like','%周三%']; break;
                case 4: $map['bet_code'] = ['like','%周四%']; break;
                case 5: $map['bet_code'] = ['like','%周五%']; break;
                case 6: $map['bet_code'] = ['like','%周六%']; break;
                case 7: $map['bet_code'] = ['like','%周日%']; break;
            }

        }
        $list = $this->_list(CM('FbBetodds'),$map);
        //获取赛程名称
        $gameIdArr = [];
        foreach ($list as $k => $v)//（目的不把查询语句写在循环里）
        {
            $gameIdArr[] = $v['game_id'];
        }
        $unionNameRes = M('GameFbinfo')->where(['game_id'=>['in',$gameIdArr]])->Field('union_name,game_id')->select();
        foreach ($list as $key => $value)
        {
            foreach ($unionNameRes as $k => $v)
            {
                if($v['game_id'] == $value['game_id'])
                {
                    $list[$k]['union_name'] = substr($v['union_name'],0,stripos($v['union_name'],',',1));
                }
            }
        }
        unset($unionNameRes);

        $this->assign('list',$list);
        $this->display();
    }

    //赛事前瞻
    public function fb_prematchinfo()
    {
        $game_id = I('game_id');

        $map['game_id'] = $game_id;
        $map['from_web'] = ['in',[0,1]];
        $res = M('FbMatchinfo')->field('home_pre_match_info,away_pre_match_info,from_web')->where($map)->select();

        $rData = [];
        if(!empty($res))
        {
            foreach($res as $k=>$v)
            {
                if(!empty($v['home_pre_match_info']) && $v['from_web'] == 1) $rData['home'] = json_decode($v['home_pre_match_info'],true);
                if(!empty($v['away_pre_match_info']) && $v['from_web'] == 1) $rData['away'] = json_decode($v['away_pre_match_info'],true);
                if(!empty($v['home_pre_match_info']) && $v['from_web'] == 0) $rData['home_w'] = $v['home_pre_match_info'];
                if(!empty($v['away_pre_match_info']) && $v['from_web'] == 0) $rData['away_w'] = $v['away_pre_match_info'];
            }
        }

        $this->assign('list', $rData);
        $this->display();
    }

    //赛事前瞻
    public function save_pmi()
    {
        $addTemp = [];
        $game_id = I('game_id');
        $home_w = I('home_w');
        if(!empty($home_w))
        {
            $addTemp['home_pre_match_info'] = $home_w;
        }
        $away_w = I('away_w');
        if(!empty($away_w))
        {
            $addTemp['away_pre_match_info'] = $away_w;
        }
        if(!empty($addTemp))
        {
            $addTemp['from_web'] = 0;
            $addTemp['game_id'] = $game_id;
            $addTemp['update_time'] = time();
            $res = M('FbMatchinfo')->field('id')->where(['game_id'=>$game_id,'from_web'=>0])->find();
            if(!empty($res))
                M('FbMatchinfo')->where(['game_id'=>$game_id,'from_web'=>0])->save($addTemp);
            else
                M('FbMatchinfo')->add($addTemp);
        }
        else
        {
            M('FbMatchinfo')->where(['game_id'=>$game_id,'from_web'=>0])->delete();
        }

        if(1){
            $this->success('设置成功!');
        }else{
            $this->success('设置失败!');
        }
    }


    public function edit() {
        $id = I('id');
        $vo = M('gameBkinfo')->find($id);
        if (!$vo){
            $this->error('参数错误');
        }
        $this->assign('vo', $vo);
        $this->display();
    }

    public function save() {
        $id = I('id');
        $model = D('gameBkinfo');
        if (false === $model->create()) {
            $this->error($model->getError());
        }
        // 更新数据
        unset($_POST['id']);
        $rs = $model->where(['id'=>$id])->save($_POST);
        if(!is_bool($rs)){
            $rs = true;
        }
        if ($rs) {
            //成功提示
            $this->success('编辑成功!');
        } else {
            //错误提示
            $this->error('编辑失败!');
        }
    }
    //添加视频直播源
    public function addVideo()
    {
        $game_id = I('game_id');
        $gameType = I('gameType');
        $Model = $gameType == 1 ? M('gameFbinfo') : M('gameBkinfo');
        if (IS_POST) {
            //获取Web数据
            $WebArr['webid'] = I('webid');
            $WebArr['webname'] = I('webname');
            $WebArr['weburl'] = I('weburl');
            $WebArr['webformat'] = I('webformat');
            $WebArr['webstart'] = I('webstart');
            $WebArr['web_ischain'] = I('web_ischain');
            foreach ($WebArr as $k => $v) {
                $count = count($WebArr['webname']);
                for ($i = 1; $i < $count; $i++) {
                    $WebData[$i]['webid'] = $i;
                    $WebData[$i]['webname'] = $WebArr['webname'][$i];
                    $WebData[$i]['weburl'] = $WebArr['weburl'][$i];
                    $WebData[$i]['webformat'] = $WebArr['webformat'][$i];
                    $WebData[$i]['webstart'] = strtotime($WebArr['webstart'][$i]);
                    $WebData[$i]['web_ischain'] = (string)$WebArr['web_ischain'][$i];
                }
            }
            //获取App数据
            $AppArr['appid'] = I('appid');
            $AppArr['appname'] = I('appname');
            $AppArr['appurl'] = I('appurl');
//            $AppArr['appformat'] = I('appformat');
            $AppArr['appstart'] = I('appstart');
            $AppArr['app_ischain'] = I('app_ischain');
            $AppArr['app_isbrowser'] = I('app_isbrowser');
            foreach ($AppArr as $k => $v) {
                $count = count($AppArr['appname']);
                for ($i = 1; $i <= $count; $i++) {
                    $AppData[$i]['appid'] = $i;
                    $AppData[$i]['appname'] = $AppArr['appname'][$i];
                    $AppData[$i]['appurl'] = $AppArr['appurl'][$i];
//                    $AppData[$i]['appformat'] = $AppArr['appformat'][$i];
                    $AppData[$i]['appstart'] = strtotime($AppArr['appstart'][$i]);
                    $AppData[$i]['app_ischain'] = (string)$AppArr['app_ischain'][$i];
                    $AppData[$i]['app_isbrowser'] = (string)$AppArr['app_isbrowser'][$i];
                }

                $array = ['web_video' => json_encode($WebData), 'app_video' => json_encode($AppData), 'video_brief' => I('video_brief'), 'label' => I('label')];
                $rs = $Model->where(['game_id' => $game_id])->save($array);
                if (!is_bool($rs)) {
                    $rs = true;
                }
                $updateUrl = D('Common');
                $live_msg = $updateUrl->updateUrl($game_id, $array);
                if ($rs) {
                    //生成文件
                    foreach ($WebData as $k => $v) {
                        if (!empty($v['webname'])) {
                            $file = fopen("./Public/video/{$gameType}-{$game_id}-{$v['webid']}.hls", "w");
                            $txt = "{$v['weburl']}\nrtmp://127.0.0.1:1935/hls/app{$gameType}-{$game_id}-{$v['webid']}\n{$v['webstart']}\n{$v['webformat']}";
                            fwrite($file, $txt);
                            fclose($file);
                        }
                    }
                    $this->success('保存成功' . ',' . $live_msg);
                } else {
                    $this->error('保存失败' . ',' . $live_msg);
                }
            }
        }

        $vo = $Model->where(['game_id' => $game_id])->field("web_video,app_video,video_brief,label")->find();
        $web_video = json_decode($vo['web_video'], true);
        $app_video = json_decode($vo['app_video'], true);
        $web_count = count($web_video) ? count($web_video)+1 : 1;
        $app_count = count($app_video) ? count($app_video)+1 : 1;
        for ($i = $web_count ; $i < 6 ; $i++ )
        {
            $web_video[$i]['webid']     = $i;
            $web_video[$i]['webname']   = '';
            $web_video[$i]['weburl']    = '';
            $web_video[$i]['webformat'] = '';
        }
        for ($i = $app_count ; $i < 6 ; $i++ )
        {
            $app_video[$i]['appid']     = $i;
            $app_video[$i]['appname']   = '';
            $app_video[$i]['appurl']    = '';
            $app_video[$i]['appformat'] = '';
        }
        $this->assign('web_video', $web_video);
        $this->assign('app_video', $app_video);
        $this->assign('video_brief', $vo['video_brief']);
        $this->assign('label', $vo['label']);
        $this->display();
    }

    //修改是否滚球
    public function saveIsGo(){
        $gameModel = $this->gameType==1 ? 'gameFbinfo' : 'gameBkinfo';
        $id = $_REQUEST ['id'];
        $is_go = $_REQUEST ['is_go'];
        $rs = M($gameModel)->where(array('id'=>$id))->data(array('is_go'=>$is_go))->save();
        if($rs){
            $this->success('保存成功');
        }else{
            $this->error('保存失败');
        }
    }
    //修改赛程状态
    public function saveState(){
        $game_id = $_REQUEST['game_id'];
        $gameModel = $this->gameType==1 ? 'gameFbinfo' : 'gameBkinfo';
        $field = $this->gameType==1 ? 'game_state' : 'game_state,total';
        $vo = M($gameModel)->where(['game_id'=>$game_id])->field($field)->find();
        if($this->gameType == 1)
        {
            //足球赛程状态配置
            $game_state = C('game_state');
        }
        else
        {
            //蓝球赛程状态配置
            if($vo['total'] == 2)
            {
                //上下半场
                $game_state = C('_game_state_bk');
            }
            else
            {
                //4小节
                $game_state = C('game_state_bk');
            }
        }
        if(IS_POST)
        {
            $rs = M($gameModel)->where(['game_id'=>$game_id])->save(['game_state'=>$_REQUEST['game_state']]);
            if($rs !== false){
                $this->success('保存成功');
            }else{
                $this->error('保存失败');
            }
        }
        $this->assign('game_state',$game_state);
        $this->assign('vo',$vo);
        $this->display();
    }
    //修改是否直播
    public function saveisVideo(){
        $gameModel = $this->gameType==1 ? 'gameFbinfo' : 'gameBkinfo';
        $where['id'] = $_REQUEST['id'];
        unset($_REQUEST['id']);
        $rs = M($gameModel)->where($where)->save($_REQUEST);
        $updateVideo = D('Common');
        $video_msg = $updateVideo->updateVideo($where['id'],$gameModel,$_REQUEST['is_video']);
        if($rs !== false){
            $this->success('保存成功'.','.$video_msg);
        }else{
            $this->error('保存失败'.','.$video_msg);
        }
    }

    //修改是否直播推荐
    public function saveisRecommend(){
        $gameModel = $this->gameType==1 ? 'gameFbinfo' : 'gameBkinfo';
        $where['id'] = $_REQUEST['id'];
        unset($_REQUEST['id']);
        $rs = M($gameModel)->where($where)->save($_REQUEST);
        if($rs !== false){
            $this->success('设置成功');
        }else{
            $this->error('设置失败');
        }
    }
     //修改是否竞猜
    public function saveIsGamble(){
        $gameModel = $this->gameType==1 ? 'gameFbinfo' : 'gameBkinfo';
        $where['id'] = $_REQUEST['id'];
        unset($_REQUEST['id']);
        $rs = M($gameModel)->where($where)->save($_REQUEST);
        if($rs !== false){
            $this->success('保存成功');
        }else{
            $this->error('保存失败');
        }
    }
    /**
     * 批量设置竞猜
     */
    public function saveIsGambleAll()
    {
        $gameModel = $this->gameType==1 ? 'gameFbinfo' : 'gameBkinfo';
        $ids = isset($_POST['id']) ? $_POST['id'] : null;
        $is_notGamble = (int)$_REQUEST['is_notGamble'];
        if ($ids) {
            $idsArr = explode(',', $ids);
            $condition = array ("id" => array ('in',$idsArr));
            $data = ['is_gamble'=>1,'is_show'=>1];
            $rs = M($gameModel)->where($condition)->save($data);
            if($is_notGamble === 1)
            {
                $rs = M($gameModel)->where($condition)->save(['is_gamble'=>0,'is_show'=>0]);
            }
            if($rs !== false){
                $this->success('设置成功');
            }else{
                $this->error('设置失败');
            }
        } else {
            $this->error('非法操作');
        }
    }
    /**
    * 批量设置直播
    * @access
    * @return string
    */
    public function saveisVideoAll(){
        //删除指定记录
        $gameModel = $this->gameType==1 ? 'gameFbinfo' : 'gameBkinfo';
        $ids = isset($_POST['id']) ? $_POST['id'] : null;
        $is_notVideo = (int)$_REQUEST['is_notVideo'];
        if ($ids)
        {
            $idsArr = explode(',', $ids);
            $condition = array ("id" => array ('in',$idsArr));
            $updateVideo = D('Common');
            if($is_notVideo === 1)//批量取消直播
            {
                $rs = M($gameModel)->where($condition)->save(['is_video'=>0]);
                $video_msg = $updateVideo->updateVideo($condition,$gameModel,0);
            }else{
                $rs = M($gameModel)->where($condition)->save(['is_video'=>1]);
                $video_msg = $updateVideo->updateVideo($condition,$gameModel,1);
            }

            if($rs !== false)
            {
                $this->success('设置成功'.','.$video_msg);
            }
            else
            {
                $this->error('设置失败'.','.$video_msg);
            }
        }
        else
        {
            $this->error('非法操作');
        }
    }

    /**
    * 批量设置直播推荐
    * @access
    * @return string
    */
    public function saveisRecommendAll(){
        //删除指定记录
        $gameModel = $this->gameType==1 ? 'gameFbinfo' : 'gameBkinfo';
        $ids = isset($_POST['id']) ? $_POST['id'] : null;
        $is_notRecommend = (int)$_REQUEST['is_notRecommend'];
        if ($ids)
        {
            $idsArr = explode(',', $ids);
            $condition = array ("id" => array ('in',$idsArr));
            $rs = M($gameModel)->where($condition)->save(['is_recommend'=>1]);
            if($is_notRecommend === 1)
            {
                $rs = M($gameModel)->where($condition)->save(['is_recommend'=>0]);
            }
            if($rs !== false)
            {
                $this->success('设置成功');
            }
            else
            {
                $this->error('设置失败');
            }
        }
        else
        {
            $this->error('非法操作');
        }
    }

    //删除视频源
    public function delSource(){
        $game_id  = I('game_id');
        $arraykey = I('k');
        $type     = I('type');
        $gameType = I('gameType');
        $gameModel = $gameType == 1 ? M('gameFbinfo') : M('gameBkinfo');
        $vo = $gameModel->where(['game_id'=>$game_id])->field("web_video,app_video")->find();
        if($type == 'web'){
            $id = 'webid';
            $source = json_decode($vo['web_video'],true);
        }elseif ($type == 'app') {
            $id = 'appid';
            $source = json_decode($vo['app_video'],true);
        }
        foreach ($source as $key => $value) {
            if($value[$id] == $arraykey){
                foreach ($value as $k => $v) {
                    unset($source[$key][$k]);
                }
            }
        }
        $video = $type == 'web' ? 'web_video' : 'app_video';
        $rs = $gameModel->where(['game_id'=>$game_id])->save([$video=>json_encode($source)]);
        if($rs){
            if($type == 'web'){
                //删除文件
                $path = "./Public/video/{$gameType}-{$game_id}-{$arraykey}.hls";
                if(is_file($path) && file_exists($path)){
                    unlink($path);
                }
            }
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }

    //聊天室管理
    public function chatRoom()
    {
        $game_id = I('game_id');
        $chatroomId = M('EasemobChatroom')->where(['game_id'=>$game_id])->getField('chatroom_id');

        if ($chatroomId)
        {
            import('Vendor.Easemob.Easemob');
            $Easemob = new \Easemob(C('Easemob'));

            $info = $Easemob->getChatRoomDetail($chatroomId);
            $member = $info['data'][0]['affiliations'];

            foreach ($member as $k => $v)
            {
                if (!isset($v['member']))
                {
                    unset($member[$k]);
                    continue;
                }

                $userInfo = M('EasemobUser eu')->field('u.id,u.nick_name')->where(['eu.username'=>$v['member']])->join('LEFT JOIN __FRONT_USER__ u ON u.id = eu.uid')->find();

                if (I('nick_name') && I('nick_name') != $userInfo['nick_name'])
                {
                    unset($member[$k]);
                    continue;
                }

                if (I('is_vistor') && !$userInfo['id'])
                {
                    unset($member[$k]);
                    continue;
                }

                $member[$k]['uid'] = $userInfo ? $userInfo['id'] : '';
                $member[$k]['nick_name'] = $userInfo ? $userInfo['nick_name'] : '';
            }

            $this->member = $member;
        }

        $this->game_id = $game_id;
        $this->display();
    }

    //屏蔽或踢出聊天室
    public function kickout()
    {
        $username = $_REQUEST['username'];
        $outType  = $_REQUEST['outType'];

        $out = D('EasemobUser')->kickout($username,$outType);

        if ($out === false)
            $this->error('操作失败，请联系管理员');

        $this->success('操作成功');
    }

    //阵容添加列表
    public function lineup()
    {
        $game_id = $_REQUEST['game_id'];
        $type = $_REQUEST['type'];

        $Fbinfo = M('gameFbinfo')->where(['game_id'=>$game_id])->field('home_team_id,home_team_name,away_team_id,away_team_name')->find();
        $this->assign('Fbinfo',$Fbinfo);

        if($type == 'home'){
            //主队队员
            $Lineup = M()->table("qc_teamfb_lineup t")->join("qc_playerfb p on t.player_id = p.player_id")->field("t.player_type,t.player_id,t.player_type,t.player_number,p.player_name")->where(['team_id'=>$Fbinfo['home_team_id'],'is_valid'=>1])->select();
            $hasLineup = M('game_lineup_fb')->where(['game_id'=>$game_id,'team_id'=>$Fbinfo['home_team_id']])->select();
        }else{
            //客队队员
            $Lineup = M()->table("qc_teamfb_lineup t")->join("qc_playerfb p on t.player_id = p.player_id")->field("t.player_type,t.player_id,t.player_type,t.player_number,p.player_name")->where(['team_id'=>$Fbinfo['away_team_id'],'is_valid'=>1])->select();
            $hasLineup = M('game_lineup_fb')->where(['game_id'=>$game_id,'team_id'=>$Fbinfo['away_team_id']])->select();
        }
        //区分数据库数据与手动添加数据
        foreach ($hasLineup as $key => $value) {
            if($value['is_sys'] == 1){
                $playerfb = M("playerfb")->where(['player_id'=>$value['player_id']])->field('player_name,player_number')->find();
                $hasLineup[$key]['player_name'] = $playerfb['player_name'];
                $hasLineup[$key]['player_number'] = $playerfb['player_number'];
            }else{
                $hasLineup[$key]['player_name'] = $value['pname'];
                $hasLineup[$key]['player_number'] = $value['pno'];
            }
        }
        //对数组进行排序,数据库数据放前面
        foreach ($hasLineup as $v) {
            $is_sys[] = $v['is_sys'];
            $player_number[] = $v['player_number'];
        }
        array_multisort($is_sys, SORT_DESC,$player_number,SORT_ASC,$hasLineup);
        $this->assign('hasLineup',$hasLineup);
        $this->assign('Lineup',$Lineup);
        $this->display();
    }
    //执行添加
    public function saveLineup()
    {
        //获取数据
        $lineup = array();
        $lineup['player_id']     =   I('player_id');
        $lineup['player_number'] =   I('player_number');
        $lineup['player_name']   =   I('player_name');
        $lineup['player_type']   =   I('player_type');
        $lineup['is_first']      =   I('is_first');
        $lineup['is_sys']        =   I('is_sys');
        foreach ($lineup as $k => $v) {
            $count = count($lineup['player_id']);
            for($i=0;$i<$count;$i++){
                $lineupData[$i]['player_id']    = $lineup['player_id'][$i];
                $lineupData[$i]['player_number']= $lineup['player_number'][$i];
                $lineupData[$i]['player_name']  = $lineup['player_name'][$i];
                $lineupData[$i]['player_type']  = $lineup['player_type'][$i];
                $lineupData[$i]['is_first']     = $lineup['is_first'][$i];
                $lineupData[$i]['is_sys']       = $lineup['is_sys'][$i];
            }
        }
        foreach ($lineupData as $k => $v) {
            if(empty($v['player_name'])){
                $this->error('数据不完整，或没有添加！');
            }
            if($v['is_sys'] == 1){
                //数据库数据
                $lineup_a[] = $v;
            }else{
                //手动添加数据
                $lineup_b[] = $v;
            }
        }
        $game_id = I('game_id');
        $type    = I('type');
        $team_id = $type == 'home' ? I('home_team_id') : I('away_team_id');
        if(!empty($lineup_b)){
            //组装手动添加数据
            foreach ($lineup_b as $k => $v) {
                if(empty($v['player_number']) || empty($v['player_name'])){
                    $this->error('数据不完整，或没有添加！');
                }
                if(!is_numeric($v['player_number'])){
                    $this->error('球衣号为数字！');
                }
                $lineup_b[$k]['pno']     = $v['player_number'];
                $lineup_b[$k]['pname']   = $v['player_name'];
                $lineup_b[$k]['game_id'] = $game_id;
                $lineup_b[$k]['team_id'] = $team_id;
                unset($lineup_b[$k]['player_name']);
                unset($lineup_b[$k]['player_id']);
                unset($lineup_b[$k]['player_number']);
            }
            $rs = M("game_lineup_fb")->addAll($lineup_b);
        }
        if(!empty($lineup_a)){
            //组装数据库数据
            foreach ($lineup_a as $k => $v) {
                $lineup_a[$k]['game_id'] = $game_id;
                $lineup_a[$k]['team_id'] = $team_id;
                unset($lineup_a[$k]['player_name']);
                unset($lineup_a[$k]['player_number']);
            }
            $rs2 = M("game_lineup_fb")->addAll($lineup_a);
        }
        if($rs || $rs2){
            $this->success('设置成功!');
        }else{
            $this->success('设置失败!');
        }
    }
    //异步删除球员
    public function delLineup()
    {
        $id = I('id');
        if(!isset($id)){
            $this->success('参数错误!');
        }
        if(M('game_lineup_fb')->delete($id)){
            $this->success('删除成功!');
        }else{
            $this->success('删除失败!');
        }
    }
    /**
     * 销售统计的查看操作
     */
    public function bettingCheck()
    {
        $log_time = I('log_time');
        $gambleClass ='MarketView';
        $game_type = 1;
        $play_type = I('play_type');
        $nick_name = I('nick_name');
        //初始化Model
        $frontUserModel = M('FrontUser');
        //昵称查询
        if(! empty($nick_name))
        {
            $userArr = $frontUserModel->where(['nick_name' => ['LIKE','%'.$nick_name.'%']])->getField('id',true);
            $whereUser = ' and q.user_id IN (';
            foreach ($userArr as $key => $value)
            {
                $whereUser = $whereUser."'".$value."',";
            }
            $whereUser = substr($whereUser, 1,-1);
            $whereUser .= ')';
        }
        else
        {
            $whereUser = '';
        }
        //竞猜玩法查询
        if(! empty($play_type)){
           $gamblePlay = 'and g.play_type = '.$play_type;
        }
        //获取sql
        $where = ['coin'=>['GT',0],'game_type'=>$game_type,'_string'=>"FROM_UNIXTIME(log_time,'%Y%m%d') = ".mysql_real_escape_string($log_time).' '.$whereUser.' '.$gamblePlay];
        $querySql = D($gambleClass)
                    ->union(['field'=>'q.user_id AS user_id, q.cover_id AS cover_id, q.game_type AS game_type,
                                    q.gamble_id AS gamble_id, q.platform AS platform, q.log_time AS log_time,
                                    q.coin AS coin, g.union_name AS union_name, g.game_date AS game_date, g.game_time AS game_time,
                                    g.home_team_name AS home_team_name, g.score AS score, g.play_type AS play_type,
                                    g.chose_side AS chose_side, g.away_team_name AS away_team_name, g.game_id AS game_id,
                                    g.handcp AS handcp, g.vote_point AS vote_point, g.tradeCoin AS tradeCoin, g.result AS result',
                                'where'=>$where,
                                'table'=>'qc_quiz_log q INNER JOIN qc_gamble_reset g ON q.gamble_id = g.id ',
                            ])
                    ->where($where)
                    ->buildSql();
        //统计记录的数量
        $timeDayCount = M()->table($querySql.' a')->count('user_id');
        //获取每页显示的条数
        $pageNum = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
        //获取当前的页码
        $currentPage =! empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1;
        //导出操作
        $export = I('Export');
        if(!empty($export))
        {
            $list = M()->table($querySql.' b')->order('log_time desc')->select();
        }
        else
        {
            //列表
            $list = M()->table($querySql.' b')->order('log_time desc')->limit($pageNum*($currentPage-1),$pageNum)->select();

        }

        foreach ($list as $key => $value)
        {
            //查看竞猜的用户昵称
            $frontUserRes = $frontUserModel->where(['id'=>$value['user_id']])->Field('nick_name,is_robot')->find();
            $list[$key]['nick_nameIng'] = $frontUserRes['nick_name'];
            $list[$key]['is_robotIng'] = $frontUserRes['is_robot'];

            //被查看竞猜的用户昵称
            $frontUserRes = $frontUserModel->where(['id'=>$value['cover_id']])->Field('nick_name,is_robot')->find();
            $list[$key]['nick_nameBy'] = $frontUserRes['nick_name'];
            $list[$key]['is_robotBy'] = $frontUserRes['is_robot'];



            //格式化
            $list[$key]['home_team_name']=substr($value['home_team_name'], 0,strpos($value['home_team_name'], ','));
            $list[$key]['away_team_name']=substr($value['away_team_name'], 0,strpos($value['away_team_name'], ','));

        }
        $this->assign ( 'totalCount', $timeDayCount );//当前条件下数据的总条数
        $pageNum =empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
        $this->assign ( 'numPerPage', $pageNum ); //每页显示多少条
        $this->assign ( 'currentPage', !empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1);//当前页码
        $this->setJumpUrl();
        if(!empty($export))
        {
            $this->excelExport($list,'',$game_type);//导出；
        }
        $this->assign('list',$list);
        $this->display();
    }
     /*
     * @param        $list  列表
     * @param string $filename  导出的文件名
     * @param int $gameType  1:足球；2：篮球
     */
    public function excelExport($list,$filename="",$game_type = 1)
    {
        $filename = empty($filename) ? date('Y-m-d') : $filename;
        $strTable ='<table width="500" border="1">';
        $strTable .= '<tr>';
        $strTable .= '<th style="text-align:center;font-size:12px;width:"*">购买人的名称</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width=120px;>购买日期</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">购买渠道</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">被购买人的名称</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">比赛时间</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">竞猜玩法</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">主队</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">全场</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">客队</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">竞猜球队</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">盘口</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">竞猜积分</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">金币</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">目前结果</th>';
        $strTable .= '</tr>';

        foreach($list as $k=>$val){
            $strTable .= '<tr>';
            $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['nick_nameIng'].'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.date('Y/m/d H:i:s',$val['log_time']).' </td>';
            $platform = '';
            if ($val['platform'] == 1 ) $platform = 'Web';
            if ($val['platform'] == 2 ) $platform = 'IOS';
            if ($val['platform'] == 3 ) $platform = 'Android';
            if ($val['platform'] == 4 ) $platform = 'M站';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$platform.'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['nick_nameBy'].'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['game_date']." ".$val['game_time'].'</td>';
            $play_type = '';
            if ($val['play_type'] == 1) $play_type = '全场让分';
            if ($val['play_type'] == -1) $game_type == 1 ? $play_type = '竞猜大小' : '全场大小' ;
            if ($val['play_type'] == 2) $play_type = '半场让分';
            if ($val['play_type'] == -2) $play_type = '半场大小';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$play_type.'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['home_team_name'].'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.
                            substr_replace($val['score'],"--",stripos($val['score'],'-'),1)
                        .'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['away_team_name'].'</td>';
            $gambleTeam = "";
            if ($val['play_type'] == 1) {

                $val['chose_side'] == 1 ? $gambleTeam = $val['home_team_name'] : $gambleTeam = $val['away_team_name'];
            }
            else {
                $val['chose_side'] == 1 ? $gambleTeam = '大球' : $gambleTeam = '小球';
            }
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$gambleTeam.'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['handcp'].'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['vote_point'].'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['tradeCoin'].'</td>';
            switch ($val['result'])
            {
                case 1:$result = '赢';   $color = 'color:red;';  break;
                case 0.5:$result = '赢半'; $color = 'color:red;'; break;
                case 2:$result = '平';   $color = 'color:green;';   break;
                case -1:$result = '输';  $color = 'color:blue;';  break;
                case -0.5:$result = '输半';$color = 'color:blue;';break;
                case -10:$result = '取消'; $color = 'color:black;'; break;
                case -11:$result = '待定'; $color = 'color:black;'; break;
                case -12:$result = '腰斩'; $color = 'color:black;'; break;
                case -13:$result = '中断'; $color = 'color:black;'; break;
                case -14:$result = '推迟'; $color = 'color:black;'; break;
                default:$result = '--';$color = 'color:black;';
            }
            $strTable .= '<td style="text-align:left;font-size:12px;'.$color.'">'.$result.'</td>';
            $strTable .= '</tr>';
        }
        $strTable .='</table>';
        unset($orderList);
        downloadExcel($strTable,$filename);
        exit();
    }

    /**
     +------------------------------------------------------------------------------
     * 以下开始为篮球
     +------------------------------------------------------------------------------
    */

    //文字直播数据查看
    public function bk_textliving()
    {
        $game_id = I('game_id');

        $appService = new \Home\Services\AppdatabkService();
        $fontText = $appService->bkSituationList($game_id);

        $this->assign('list', $fontText['live']);
        $this->display();
    }

    //篮球阵容数据查看
    public function bk_lineup()
    {
        $game_id = I('game_id');
        $data=M('BkLive')->where('game_id='.$game_id)->getField('tech');
        $lineup=[];
        if($data)
        {
            $dataArr=  explode('$', $data);
            unset($dataArr[0]);
            $dataArr=array_merge($dataArr);
            foreach ($dataArr as &$v){
                $v=  explode('!', $v);
            }
            foreach ($dataArr as $k=>&$vv){
                unset($dataArr[$k][count($vv)-1]);
                unset($dataArr[$k][count($vv)-1]);

                foreach ($vv as $key => &$val){
                    $val1=  explode('^', $val);
                    $vo=[$val1[1],$val1[2],$val1[3],$val1[5]];
                    $lineup[$k][]=$vo;
                }
            }
        }

        $res = M('gameBkinfo')->field('home_team_name,away_team_name')->where(['game_id'=>$game_id])->find();
        $h_name = explode(',',$res['home_team_name']);
        $a_name = explode(',',$res['home_team_name']);
        $this->assign('list', $lineup);
        $this->assign('h_name', $h_name[0]);
        $this->assign('a_name', $a_name[1]);
        $this->display();
    }

}