<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Common\Model;
use Think\Model;

class GameFbinfoModel extends Model{

	/**
	 * 赛事数据处理
	 * @param  array    $aData     赛事信息
	 * @param  array    $aDate     赛事涉及日期
	 * @return boolean
	 */
	public function dealForBf($aData, $aDate, $showDate='')
	{
		if(!is_array($aData) && empty($aData) &&!is_array($aDate) && empty($aDate))
		{
			return false;
		}

		$gDate =implode(',',$aDate);
		$condition['game_date'] = array('in',$gDate);
		$res = $this->field("game_id")->where($condition)->select();

		$aGameId =[];
		$upDate =[];
		$inDate =[];
		//M()->startTrans();
		$res1 = $res2 = $res3 = true;
		if(!empty($res))
		{
			foreach($res as $k=>$v)
			{
				$aGameId[] = $v['game_id'];
			}

			foreach($aData as $k=>$v)
			{
				if(array_search($v['game_id'],$aGameId) ===false)
					$inData[] = $v;
				else
					$upData[] = $v;
			}

			if(!empty($inData))
			{
				foreach($inData as $k => $v)
				{
					$v['show_date'] = $showDate;
					$inData[$k] = $v;
				}
			 	$this->addAll($inData);
			}
			if(!empty($upData))
			{
				foreach($upData as $k=>$v)
				{
					$this->where('game_id='.$v['game_id'])->save($v); ;
				}
			}
		}
		else
		{
			foreach($aData as $k => $v)
			{
				$v['show_date'] = $showDate;
				$aData[$k] = $v;
			}
			$this->addAll($aData);
		}
	}

	/**
	 * 近期赛事数据处理
	 * @param  array    $aData     赛事信息
	 * @param  array    $aDate     赛事涉及日期
	 * @return boolean
	 */
	public function dealForBfList($aData, $aDate)
	{
		if(!is_array($aData) && empty($aData) &&!is_array($aDate) && empty($aDate))
		{
			return false;
		}

		$gDate =implode(',',$aDate);
		$condition['game_date'] = array('in',$gDate);
		$res = $this->field("game_id")->where($condition)->select();

		$aGameId =[];
		$upData =[];
		$inData =[];
		//M()->startTrans();
		$res1 = $res2 = $res3 = true;
		if(!empty($res))
		{
			foreach($res as $k=>$v)
			{
				$aGameId[] = $v['game_id'];
			}

			foreach($aData as $k=>$v)
			{
				if(array_search($v['game_id'],$aGameId) ===false)	$inData[] = $v;
			}

			if(!empty($inData))
			{
				foreach($inData as $k => $v)
				{
					$inData[$k] = $v;
				}
			 	$this->addAll($inData);
			}
		}
		else
		{
			$this->addAll($aData);
		}
	}




	public function dealForBfOdds($aData)
	{
		//var_dump($aData);exit;
		if(!is_array($aData) && empty($aData))
		{
			return false;
		}
		foreach($aData as $k=>$v)
		{
			$this->where('game_id='.$v['game_id'])->save($v); ;
		}
	}
}