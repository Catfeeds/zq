<?php
use Think\Tool\Tool;
use Think\Controller;
/**
 * 大数据模型
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/18
 * Time: 17:12
 */
class BigDataController extends CommonController
{

    /**
     * 大数据分类
     */
    public function index(){
        $map = $this->_search('BigdataClass');

        $name = I('name') ?: '';
        if($name) $map['name'] = ['like', "%$name%"];//筛选

        $status = I('status');
        if(isset($status) && is_numeric($status) && $status == 0){
            $map['status'] = 0;
        }else if(isset($status) && $status == 1){
            $map['status'] = 1;
        }

        $list = $this->_list(CM('BigdataClass'), $map);

        foreach ($list as $key => &$v) {
            //把服务器前缀拼上
            $v['img'] = Tool::imagesReplace($v['img']);
        }

        $this->assign('list',$list);
        $this->display();
    }

    /**
     * 修改、添加操作
     *
     */
    public function  edit()
    {
        $id = I('id');
        if(!empty($id)) {
            $res = M('BigdataClass')->where(['id'=>$id])->find();
            if(!$res) $this->error('参数错误');

            $res['img'] = Tool::imagesReplace($res['img']);
        }
        //获取宝箱名称和成就名称说明的配置

        $this->assign('data', $res);
        $this->display();
    }

    /**
     * 保存操作
     */
    //增加修改用户信息
    public function save(){
        $id = I('id');
        $model = D('BigdataClass');

        if (false === $model->create()) $this->error($model->getError());

        if (empty($id)) {
            //为新增
            $rs = $model->add();
            if($rs) {
                //上传图片
                if (!empty($_FILES['fileInput']['tmp_name'])) {
                    $return = D('Uploads')->uploadImg("fileInput", "bigdataclass", $rs);
                    if($return['status'] == 1) M("BigdataClass")->where(['id'=>$rs])->save(['img'=>$return['url']]);
                }
            }
        }else {
            //为修改
            $rs = $model->where(['id'=>$id])->save();

            if(!is_bool($rs)) $rs = true;

            //是否有上传
            if (!empty($_FILES['fileInput']['tmp_name'])) {
                //先删除原来图片
                $fileArr = array(
                    "/bigdata/{$id}.jpg",
                    "/bigdata/{$id}.gif",
                    "/bigdata/{$id}.png",
                    "/bigdata/{$id}.swf",
                );

                D('Uploads')->deleteFile($fileArr);
                //上传图片
                $return = D('Uploads')->uploadImg("fileInput", "bigdataclass", $id);
                //修改路径
                if($return['status'] == 1)
                    M("BigdataClass")->where(['id'=>$id])->save(['img'=>$return['url']]);
            }
        }
        if ($rs) {
            //接口首页模型列表清空缓存
            S('modelListIndex', null);
            //成功提示
            $this->success('保存成功!', cookie('_currentUrl_'));
        } else {
            //错误提示
            $this->error('保存失败!');
        }
    }

    //异步删除图片
    public function delPic(){
        $id = I('id');
        $type = I('type');
        if(empty($id)){
            $this->error('参数错误!');
        }
        $fileArr = array(
            "/bigdata/{$id}.jpg",
            "/bigdata/{$id}.gif",
            "/bigdata/{$id}.png",
            "/bigdata/{$id}.swf",
        );
        //执行删除
        $return = D('Uploads')->deleteFile($fileArr);
        if($return['status'] == 1){
            //删除路径
            $model = $type == 1 ? M("BigdataClass") : M("BigdataList");
            if($model->where(['id'=>$id])->save(['img'=>''])){
                $this->success('删除成功！');
            }else{
                $this->error('删除失败！');
            }
        }else{
            $this->error('删除失败！');
        }
    }

    /**
     * 大数据列表
     */
    public function listIndex(){
        $map = $this->_search('BigdataList');

        $name = I('name') ?: '';
        if($name) $map['name'] = ['like', "%$name%"];//筛选

        $class = I('class') ?: '';
        if($class) $map['class'] = $class;//筛选

        $status = I('status');
        if(isset($status) && is_numeric($status) && $status == 0){
            $map['status'] = 0;
        }else if(isset($status) && $status == 1){
            $map['status'] = 1;
        }

        $list = $this->_list(CM('BigdataList'), $map);

        foreach ($list as $key => &$v) {
            //把服务器前缀拼上
            $v['img'] = Tool::imagesReplace($v['img']);
        }

        //所有分类
        $classList = M("BigdataClass")->order('sort asc')->getField('sign, name');

        $this->assign('list', $list);
        $this->assign('classList', $classList);
        $this->display();
    }

    /**
     * 修改、添加操作
     *
     */
    public function  listEdit()
    {
        $id = I('id');
        if(!empty($id)) {
            $res = M('BigdataList')->where(['id'=>$id])->find();
            if(!$res) $this->error('参数错误');

            $res['img'] = Tool::imagesReplace($res['img']);
        }

        //所有分类
        $classList = M("BigdataClass")->order('sort asc')->getField('sign, name');

        $this->assign('classList', $classList);
        $this->assign('data', $res);
        $this->display();
    }

    /**
     * 保存操作
     */
    //增加修改用户信息
    public function listSave(){
        $id = I('id');
        $model = D('BigdataList');

        if (false === $model->create()) $this->error($model->getError());

        if (empty($id)) {
            //为新增
            $rs = $model->add();
            if($rs) {
                //上传图片
                if (!empty($_FILES['fileInput']['tmp_name'])) {
                    $return = D('Uploads')->uploadImg("fileInput", "bigdata", $rs);
                    if($return['status'] == 1) M("BigdataList")->where(['id'=>$rs])->save(['img'=>$return['url']]);
                }
            }
        }else {
            //为修改
            $rs = $model->where(['id'=>$id])->save();

            if(!is_bool($rs)) $rs = true;

            //是否有上传
            if (!empty($_FILES['fileInput']['tmp_name'])) {
                //先删除原来图片
                $fileArr = array(
                    "/bigdata/{$id}.jpg",
                    "/bigdata/{$id}.gif",
                    "/bigdata/{$id}.png",
                    "/bigdata/{$id}.swf",
                );

                D('Uploads')->deleteFile($fileArr);
                //上传图片
                $return = D('Uploads')->uploadImg("fileInput", "bigdata", $id);
                //修改路径
                if($return['status'] == 1)
                    M("BigdataList")->where(['id'=>$id])->save(['img'=>$return['url']]);
            }
        }
        if ($rs) {
            //接口问球页面模型列表清空缓存
            S('askTheBall_class', null);
            //成功提示
            $this->success('保存成功!', cookie('_currentUrl_'));
        } else {
            //错误提示
            $this->error('保存失败!');
        }
    }

    /**
     * 配置
     */
    public function config(){
        $config = M('config')->where(['sign' => 'bigDataAsk'])->getField('config');

        if(IS_POST) {
            $data = I();
            $rs =  M('config')->where(['sign' => 'bigDataAsk'])->save(['config' => json_encode($data)]);
            if ($rs)
                $this->success('修改成功');

            $this->error('修改失败!');
        }else{
            $this->assign('data', json_decode($config, true));
        }

        $this->display();
    }
	
	
	/**
	 * 滚球配置
	 */
    public function rollBallConfigList()
    {
    	$rollBallConfig = M("config")->where(['sign' => 'rollBallWarning'])->getField('config');
		$data = json_decode($rollBallConfig, true);
	    if(IS_POST) {
	        $type = I('class_type');
	        if ($type == '') {
		        $this->assign('data', $data);
	        } elseif ($type == 1) {
	        	$_data = [];
	        	foreach ($data as $key => $value) {
	        		if ($value['type'] == $type) {
	        			$_data[$key] = $value;
			        }
		        }
		        $this->assign("data", $_data);
	        } elseif ($type == 2) {
		        $_data = [];
		        foreach ($data as $key => $value) {
			        if ($value['type'] == $type) {
				        $_data[$key] = $value;
			        }
		        }
		        $this->assign("data", $_data);
	        }
	        $this->assign("class_type", $type);
	    }else{
		    $this->assign('data', $data);
	    }
	    $this->setJumpUrl();
    	$this->display();
    }
	
	
	/**
	 * 滚球编辑
	 */
    public function rollBallEdit()
    {
        $id = I('id');
	    $rollBallConfig = M("config")->where(['sign' => 'rollBallWarning'])->getField('config');
	    $data = json_decode($rollBallConfig, true);
	    if ($id != '') {
		    $this->assign("data", $data[(int) $id]);
	    }
		$this->assign("id", $id);
	    $this->display();
    }
	
	
	/**
	 * 删除
	 */
    public function delete()
    {
	    $id = $_REQUEST['id'];
	    $rollBallConfig = M("config")->where(['sign' => 'rollBallWarning'])->getField('config');
	    $data = json_decode($rollBallConfig, true);
		$_data = [];
		foreach ($data as $key => $value) {
			if ($id != $key) {
				$_data[] = $value;
			}
		}
		$rs = M("config")->where(['sign' => 'rollBallWarning'])->save(['config' => json_encode($_data)]);
		if ($rs) {
			$this->success('保存成功!');
		} else {
			$this->error('保存失败!');
		}
    }
	
	
	/**
	 * 滚球保存
	 */
	public function rollBallSave() {
		$id = I('id');
		$type = I('type');
		if ($type == 1) {
			$condition_start = I('condition_start_1');
			$condition_end = I('condition_end_1');
			$teamBlock = I('team_block_1');
			$string= I('string_1');
		} elseif ($type == 2) {
			$condition_start = I('condition_start_2');
			$condition_end = I('condition_end_2');
			$teamBlock = I('team_block_2');
			$string= I('string_2');
		} else {
			$this->error('不存在类型!');
		}
		$update = date("Y-m-d H:i");
		$rollBallConfig = M("config")->where(['sign' => 'rollBallWarning'])->getField('config');
		if (!empty($rollBallConfig)){
			$data = json_decode($rollBallConfig, true);
		} else {
			$data = [];
			$config['sign'] = 'rollBallWarning';
			$config['config'] = $data;
			$rs = M('config')->add($config);
			if (!$rs) {
				$this->error('保存失败!');
			}
		}
		if ($id == '') {
			//为新增
			$value['condition_start'] = $condition_start;
			$value['condition_end'] = $condition_end;
			$value['string'] = $string;
			$value['type'] = $type;
			$value['update'] = $update;
			$value['team_block'] = $teamBlock;
			$temp[] = $value;
			$data = array_merge($data, $temp);
		} else {
			//为修改
			$value['condition_start'] = $condition_start;
			$value['condition_end'] = $condition_end;
			$value['string'] = $string;
			$value['type'] = $type;
			$value['update'] = $update;
			$value['team_block'] = $teamBlock;
			$data[$id] = $value;
		}
		$rs = M("config")->where(['sign' => 'rollBallWarning'])->save(['config' => json_encode($data)]);
		if ($rs) {
			$this->success('保存成功!');
		} else {
			$this->error('保存失败!');
		}
	}
	
	public function predictiveModelUpdateCron()
	{
		$requestDate = I('date');
		$now = time();
		// 当时间超过12点时不再更新昨天数据 更新为今日数据 最后一次更新由earningsFigureCron 2:30分更新曲线图而更新
		$segmentation = strtotime('14:30:00');
		// 定时任务增加请求时间的灵活设置
		if (empty($requestDate)) {
			if ($segmentation > $now) {
				$date = date("Y-m-d", strtotime("-1 day"));
			} else {
				$date = date("Y-m-d");
			}
		} else {
			$date = $requestDate;
		}
		$update = $this->updateLostGame($date);
		foreach ($update as $key => $value) {
			$map['game_id'] = $value['game_id'];
			$map['predictive_type'] = $value['predictive_type'];
			M("predictiveModel")->where($map)->save($value);
		}
	}
	
	// 模型预测数据创建任务
	public function predictiveModelCreateCron()
	{
		$date = I('date');
		if (empty($date)) {
			$date = date("Y-m-d");
		}
		$service = new \Api530\Services\AppfbService();
		$data = [];
		// 获取让球所有数据
		$predictiveData = $service->getPredictiveModel($date, 1);
		// 获取大小球所有数据
		$bigSmallData = $service->getPredictiveModel($date, 2);
		// 获取竞彩所有数据
		$SMGData = $service->getPredictiveModel($date, 3);
		
		// 插入mysql数据库前的清洗
		if (!empty($predictiveData)) {
			$data = $this->dataReorganization($predictiveData, $data, $date, 1);
		}
		if (!empty($bigSmallData)) {
			$data = $this->dataReorganization($bigSmallData, $data, $date, 2);
		}
		if (!empty($SMGData)) {
			$data = $this->dataReorganization($SMGData, $data, $date, 3);
		}
		foreach ($data as $k => $v) {
			$map['game_id'] = $v['game_id'];
			$map['predictive_type'] = $v['predictive_type'];
			M('predictiveModel')->where($map)->add($v);
		}
	}
	
	
	// 回报率统计定时任务 建议每分钟执行一次
	public function earningsFigureCron()
	{
		$requestDate = I('date');
		$now = time();
		$segmentation = strtotime('15:00:00');
		// 定时任务增加请求时间的灵活设置
		if (empty($requestDate)) {
			if ($segmentation > $now) {
				$date = date("Y-m-d", strtotime("-1 day"));
			} else {
				$date = date("Y-m-d");
			}
		} else {
			$date = $requestDate;
		}
		$map['game_state'] = -1;
		$map['disabled_state'] = 1;
		$map['predictive_date'] = $date;
		$all_data = M('predictiveModel')->where($map)->field('game_id, state, source_state, predictive_date, admin_id, score, predictive_type, odds, recommend, source_recommend')->select();
		
		$earningsData = [];
		// 清洗数据结构 拼装数据
		$asia_num = $asia_win = $asia_draw = $asia_lost = $asia_source_win = $asia_source_winrate = $asia_winrate = $asia_source_income = $asia_income = 0;
		$bs_num = $bs_win = $bs_draw = $bs_lost = $bs_source_win =$bs_source_winrate = $bs_winrate = $bs_source_income =  $bs_income = 0;
		$smg_num = $smg_win = $smg_draw = $smg_lost = $smg_source_win = $smg_source_winrate = $smg_winrate = $smg_source_income = $smg_income = 0;
		foreach ($all_data as $item) {
			if ($item['predictive_type'] == 1) {
				$tempOdds = explode('|', $item['odds']);
				if ($item['state'] > 0 && $item['state'] <= 1) {
					$asia_win++;
				} else if ($item['state'] == 2) {
					$asia_draw++;
				} else if($item['state'] >= -1 && $item['state'] < 0) {
					$asia_lost++;
				}
				if ($item['state'] > 0 && $item['state'] <= 1) {
					$asia_source_win++;
				}
				$asia_num++;
				$asia_profitability = $this->profitability($item['state'], $tempOdds, $item['recommend'], $item['predictive_type'], $item['game_id']);
				$asia_source_profitability = $this->profitability($item['source_state'], $tempOdds, $item['source_recommend'], $item['predictive_type'], $item['game_id']);
				$asia_income += $asia_profitability;
				$asia_source_income += $asia_source_profitability;
			} else if ($item['predictive_type'] == 2) {
				$tempOdds = explode('|', $item['odds']);
				if ($item['state'] > 0 && $item['state'] <= 1) {
					$bs_win++;
				} else if ($item['state'] == 2) {
					$bs_draw++;
				} else if($item['state'] >= -1 && $item['state'] < 0) {
					$bs_lost++;
				}
				if ($item['state'] > 0 && $item['state'] <= 1) {
					$bs_source_win++;
				}
				$bs_num++;
				$bs_profitability = $this->profitability($item['state'], $tempOdds, $item['recommend'], $item['predictive_type'], $item['game_id']);
				$bs_source_profitability = $this->profitability($item['source_state'], $tempOdds, $item['source_recommend'], $item['predictive_type'], $item['game_id']);
				$bs_income += $bs_profitability;
				$bs_source_income += $bs_source_profitability;
			} else if ($item['predictive_type'] == 3) {
				$tempOdds = explode('|', $item['odds']);
				if ($item['state'] > 0 && $item['state'] <= 1) {
					$smg_win++;
				} else if ($item['state'] == 2) {
					$smg_draw++;
				} else if($item['state'] >= -1 && $item['state'] < 0) {
					$smg_lost++;
				}
				if ($item['state'] > 0 && $item['state'] <= 1) {
					$smg_source_win++;
				}
				$smg_num++;
				$smg_profitability = $this->profitability($item['state'], $tempOdds, $item['recommend'], $item['predictive_type'], $item['game_id'], $item['score']);
				$smg_source_profitability = $this->profitability($item['source_state'], $tempOdds, $item['source_recommend'], $item['predictive_type'], $item['game_id'], $item['score']);
				$smg_income += $smg_profitability;
				$smg_source_income += $smg_source_profitability;
			}
		}
		$asia_winrate = ($asia_win / ($asia_win + $asia_lost)) * 100;
		$asia_source_winrate = ($asia_source_win / ($asia_win + $asia_lost)) * 100;
		$bs_winrate = ($bs_win / ($bs_win + $bs_lost)) * 100;
		$bs_source_winrate = ($bs_source_win / ($bs_win + $bs_lost)) * 100;
		$smg_winrate = ($smg_win / ($smg_win + $smg_lost)) * 100;
		$smg_source_winrate = ($smg_source_win / ($smg_win + $smg_lost)) * 100;
		
		$earningsData['predictive_date'] = $date;
		$earningsData['asia_num'] = $asia_num;
		$earningsData['asia_win'] = $asia_win;
		$earningsData['asia_draw'] = $asia_draw;
		$earningsData['asia_lost'] = $asia_lost;
		$earningsData['asia_source_winrate'] = $asia_source_winrate;
		$earningsData['asia_winrate'] = $asia_winrate;
		$earningsData['asia_source_income'] = round($asia_source_income * 100);
		$earningsData['asia_income'] = round($asia_income * 100);
		$earningsData['bs_num'] = $bs_num;
		$earningsData['bs_win'] =$bs_win;
		$earningsData['bs_draw'] = $bs_draw;
		$earningsData['bs_lost'] = $bs_lost;
		$earningsData['bs_source_winrate'] = $bs_source_winrate;
		$earningsData['bs_winrate'] = $bs_winrate;
		$earningsData['bs_source_income'] = round($bs_source_income * 100);
		$earningsData['bs_income'] = round($bs_income * 100);
		$earningsData['smg_num'] = $smg_num;
		$earningsData['smg_win'] =$smg_win;
		$earningsData['smg_draw'] = $smg_draw;
		$earningsData['smg_lost'] = $smg_lost;
		$earningsData['smg_source_winrate'] = $smg_source_winrate;
		$earningsData['smg_winrate'] = $smg_winrate;
		$earningsData['smg_source_income'] = round($smg_source_income * 100);
		$earningsData['smg_income'] = round($smg_income * 100);
		
		$map['predictive_date'] = $date;
		$bool = M('predictiveFigure')->where($map)->getField('id');
		if (!$bool) {
			M('predictiveFigure')->add($earningsData);
		} else {
			M('predictiveFigure')->where($map)->save($earningsData);
		}
	}
	
	
	// 清洗数据入库
	public function dataReorganization($data, &$array, $date, $type, $isUpdate=False)
	{
		foreach ($data as $k => $v) {
			$temp = [];
			$temp['game_id'] = $v['game_id'];
			$temp['game_start_timestamp'] = $v['game_start_timestamp'];
			$temp['game_state'] = $v['game_state'];
			$temp['union_id'] = $v['union_id'];
			$temp['union_name'] = $v['union_name'];
			$temp['home_team_id'] = $v['home_team_id'];
			$temp['home_team_name'] = $v['home_team_name'];
			$temp['away_team_id'] = $v['away_team_id'];
			$temp['away_team_name'] = $v['away_team_name'];
			$temp['score'] = $v['score'];
			$temp['half_score'] = $v['half_score'];
			if ($isUpdate === True) {
				$temp['predictive_type'] = $v['predictive_type'];
			} else {
				$temp['predictive_type'] = $type;
			}
			$temp['handcp'] = $v['handcp'];
			$temp['odds'] = $v['odds'];
			$temp['state'] = $v['state'];
			$temp['source_state'] = $v['state'];
			$temp['recommend'] = $v['recommend'];
			$temp['source_recommend'] = $v['recommend'];
			$temp['smg_code'] = $v['smg_code'] ?: '';
			$temp['predictive_date'] = $date;
			$temp['comment'] = $v['comment'] ?:'';
			$temp['forecast_rate'] = $v['percentage'];
			$temp['create_date_time'] = time();
			$temp['update_date_time'] = time();
			$array[] = $temp;
		}
		return $array;
	}
	
	
	
	// 更新失落数据
	public function updateLostGame($date)
	{
		$service = new \Api530\Services\AppfbService();
		// 如果数据交替过程中某些数据未被选中 那么 也更新其数据
		$map['disabled_state'] = 1;
		$map['predictive_date'] = $date;
		$all_data = M('predictiveModel')->where($map)->select();
		
		$mongoId = $service->DateGameId($all_data, 'predictive_date', $date);
		$mongoGameId = array_map('intval', array_column($mongoId, 'game_id'));
		$mongo = mongoService();
		$fbGame = $mongo->select('fb_game',['game_id'=>['$in'=>$mongoGameId]], ['game_id','score','game_state', 'start_time']);
		if(!$all_data) return [];
		$gameData = $this->setArrayKey($fbGame, 'game_id');
		$allData= [];
		foreach ($all_data as $key => $value) {
			if ($value['predictive_type'] != 3) {
				$odds = explode('|', $value['odds']);
				$state = $service->getState($value['predictive_type'], $value['recommend'], $odds[1],$gameData[$value['game_id']]['score'], $gameData[$value['game_id']]['game_state']);
				$sourceState = $service->getState($value['predictive_type'], $value['source_recommend'], $odds[1],$gameData[$value['game_id']]['score'], $gameData[$value['game_id']]['game_state']);
			} else {
				// 竞彩不让球 不需要盘口
				$state = $service->getState($value['predictive_type'], $value['recommend'],0,$gameData[$value['game_id']]['score'], $gameData[$value['game_id']]['game_state']);
				$sourceState = $service->getState($value['predictive_type'], $value['source_recommend'],0,$gameData[$value['game_id']]['score'], $gameData[$value['game_id']]['game_state']);
			}
			$value['state'] = $state;
			$value['source_state'] = $sourceState;
			$value['game_state'] = (string) $gameData[$value['game_id']]['game_state'];
			$value['score'] = $gameData[$value['game_id']]['game_state'] == -1?$gameData[$value['game_id']]['score']:'';
			$value['start_time'] = $gameData[$value['game_id']]['start_time'];
			$value['update_date_time'] = time();
			$allData[] = $value;
		}
		return $allData;
	}

	
	// 获取数据组键
	public function  setArrayKey($array, $key, $value = false)
	{
		$data = [];
		foreach ($array as $k => $v) {
			$game_id = $v[$key];
			if ($value === false) {
				$odds = $v;
			} else {
				$odds = $v[$value];
			}
			$data[$game_id] = $odds;
		}
		return $data;
	}
	
	// 计算收益率
	public function profitability($state, $odds, $recommend, $type, $gameId,$score='')
	{
		$thisOdds = $plus = 0;
		if ($type == '1') {
			if ($recommend == '1') {
				$thisOdds = $odds[0];
			} else if ($recommend == '2') {
				$thisOdds = $odds[2];
			}
		} else if ($type == '2') {
			if ($recommend == '1') {
				$thisOdds = $odds[0];
			} else if ($recommend == '2') {
				$thisOdds = $odds[2];
			}
		} else if ($type == '3') {
			$thisOdds =  $this->smgPlus($recommend, $state, $odds, $score);
		}
		$map['game_id'] = $gameId;
		$map['predictive_type'] = $type;
		switch ($state) {
			case '1':
				$plus =  $thisOdds;
				break;
			case '0.5':
				$plus = $thisOdds/2;
				break;
			case '-1':
				if ($type == 3 && $recommend >= 4) {
					$plus = -2;
				} else {
					$plus = -1;
				}
				break;
			case '-0.5':
				$plus = -0.5;
				break;
			default:
				$plus = 0;
				break;
		}
		return $plus;
	}
	
	// 竞彩计算收益率公式
	public function smgPlus($recommend, $state, $odds, $score)
	{
		$home = explode('-', $score)[0];
		$away = explode('-', $score)[1];
		if ($state == '1') {
			switch ($recommend) {
				case 1:
					return $odds[0] - 1;
					break;
				case 2:
					return $odds[1] - 1;
					break;
				case 3:
					return $odds[2] - 1;
					break;
				case 4:
					if ($home >$away) {
						return $odds[0] - 2;
					} else if ($home == $away) {
						return $odds[1] - 2;
					}
					break;
				case 5:
					if ($home > $away) {
						return $odds[0] - 2;
					} else if ($home < $away) {
						return $odds[2] - 2;
					}
					break;
				case 6:
					if ($home == $away) {
						return $odds[1] - 2;
					} else if ($home < $away) {
						return $odds[2] - 2;
					}
					break;
			}
		}
		return 0;
	}
	
}