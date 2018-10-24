<?PHP
/**
 +------------------------------------------------------------------------------
 * DealdataService   原始数据处理服务类
 +------------------------------------------------------------------------------
 * Copyright (c) 2015 http://www.qqw.com All rights reserved.
 +------------------------------------------------------------------------------
 * @author Knight <Knight@163.com>
 +------------------------------------------------------------------------------
*/
namespace Common\Services;

class DealdataService
{
	/**
	 * 构造函数
	 */
    public function __construct()
    {

    }

    /**
     * 处理入口
     * @param  string $name    名称
     * @param  string $content 文件内容
     * @return void
     */
	public function dealfor($name, $content,$str)
	{
		switch($name)
		{
			case 'bf':
				$this->dealBf($content);
				break;
			case 'indexAjaxLeague':
				$this->dealIndexAjaxLeague($content);
				break;
			case 'OddsDataDiv':
				$this->dealOddsDataDiv($content);
				break;
			case 'scorelist':
				$this->dealscorelist($content,$str);
				break;
			default:
				break;
		}

	}

	/**
	 * 足球今日赛事处理
	 * @param  string $content 需处理的字符串文本
	 * @return void
	 */
	public function dealBf($content)
	{
		$gDate =[];
		$showDate =0;
		/* $games =[];
		$gameName =[];
		$country =[]; */
		if(preg_match('/matchdate=\"(.*?)\"/is',$content,$data))
		{
			$str = str_replace('月','',$data[1]);
			$str = str_replace('日','',$str);
			$showDate = date('Y',time()).$str;

		}
		if(preg_match_all('/[A-Z]\[\d+\]=\[(.*?)\]/is' ,$content, $dataTxt))
		{
			$aGame =[];
			$A =[];
			$B =[];
			$C =[];
			foreach($dataTxt[0] as $k=>$v)
			{

				$aTemp =[];
				if(strpos($v,'A[') !==false)
				{
					$aTemp = explode(',',$dataTxt[1][$k]);
					$A[] = $aTemp;
				}
				if(strpos($v,'B[') !==false)
				{
					$B[] =explode(',',$dataTxt[1][$k]);
				}

				if(strpos($v,'C[') !==false) $C[] =explode(',',$dataTxt[1][$k]);
			}

			foreach($A as $k=>$v)
			{
				$aTemp2['union_id'] = (int) $v[1];
				$aTemp2['union_name'] =$this->tagFilter($B[$v[1]][1]);
				$aTemp2['game_id'] =(int) $v[0];
				$aTemp2['game_sort_id'] =(int) $v[1];
				$aTemp2['game_state'] =(int) $v[17];
				$aTemp2['home_team_name'] =$this->tagFilter($v[4]);
				$aTemp2['home_team_id'] =$this->tagFilter($v[2]);
				$aTemp2['home_tem_rank'] =$this->tagFilter($v[26]);
				$aTemp2['away_team_name'] =$this->tagFilter($v[7]);
				$aTemp2['away_team_id'] =$this->tagFilter($v[3]);
				$aTemp2['away_team_rank'] =$this->tagFilter($v[27]);
				$aTemp2['fsw_exp'] =$this->tagFilter($v[38]);
				$aTemp2['fsw_ball'] =$this->tagFilter($v[38]);

				$tempDate = $this->tagFilter($v[11].'-'.$v[12].'-'.$v[13]);
				$tempDate =  strtotime('+1 month',strtotime($tempDate));
				$tempDate = date('Ymd',$tempDate);

				$aTemp2['game_date'] = $tempDate;
				$aTemp2['game_time'] = $this->tagFilter($v[10]);
				$aTemp2['game_half_time'] = $this->tagFilter($v[14].':'.$v[15].':'.$v[16]);
				if(array_search($tempDate,$gDate) === false)
				{
					$gDate[] =$tempDate;
				}
				$state=(int) $v[17];

				$aTemp2['score'] =$v[18] . "-" . $v[19];
				$aTemp2['half_score'] =$v[20] . "-" . $v[21];
				$aTemp2['update_time'] =time();

				$aGame[] = $aTemp2;
			}
			if(!empty($aGame))
			{
				$GIObj = new \Common\Model\GameFbinfoModel();
				$GIObj->dealForBf($aGame,$gDate,$showDate);
			}
			unset($aGame);
			unset($gDate);
			unset($A);
			unset($B);
			unset($C);
		}
	}

	public function dealIndexAjaxLeague($content)
	{

	}

	/**
	 * 足球赛事指数
	 * @param  string $content 待处理字符串数据
	 * @return void
	 */
	public function dealOddsDataDiv($content)
	{
		if(S('dealOdds') === false)
		{
			if(preg_match_all('/[a-z]{3,5}\[(.*?)\]=\"(.*?)\"/is' ,$content, $dataTxt))
			{
				$aIndex =[];
				$psw =[];
				$fsw =[];
				$lasw =[];
				$losw =[];
				$lesw =[];
				$compa =[];

				foreach($dataTxt[0] as $k=>$v)
				{
					//$aTemp =[];
					//if(strpos($v,'psw[') !==false)	$psw[] = explode(',',$dataTxt[2][$k]);
					//if(strpos($v,'fsw[') !==false) $fsw[] =explode(',',$dataTxt[2][$k]);
					//if(strpos($v,'lasw[') !==false) $lasw[] =explode(',',$dataTxt[2][$k]);
					//if(strpos($v,'losw[') !==false) $losw[] =explode(',',$dataTxt[2][$k]);
					//if(strpos($v,'lesw[') !==false) $lesw[] =explode(',',$dataTxt[2][$k]);
					//if(strpos($v,'compa[') !==false) $compa[] =explode(',',$dataTxt[2][$k]);
					if(strpos($v,'fsw[') !==false) $fsw[$dataTxt[1][$k]] =explode(',',$dataTxt[2][$k]);
					/*if(isset($aIndex[$dataTxt[2][$k]]))
						$aIndex[$dataTxt[2][$k]] =*/
				}

				foreach($fsw as $k =>$v)
				{
					$aTemp = [];
					$aTemp['game_id'] = $k;
					$aTemp['fsw_exp_home'] = $this->tagFilter($v[0]);
					$aTemp['fsw_exp_away'] = $this->tagFilter($v[2]);
					$aTemp['fsw_ball_home'] = $this->tagFilter($v[4]);
					$aTemp['fsw_ball_away'] = $this->tagFilter($v[6]);
					//$aTemp['fsw_o_home'] = $this->tagFilter($v[8]);
					//$aTemp['fsw_o_away'] = $this->tagFilter($v[10]);
					$aIndex[] = $aTemp;
				}

				if(!empty($aIndex))
				{
					$GIObj = new \Common\Model\GameFbinfoModel();
					$GIObj->dealForBfOdds($aIndex);
				}
				unset($aIndex);
				unset($fsw);
				S('dealOdds','dealOdds',300);
			}
		}
	}

	public function dealscorelist($content ,$str)
	{

		$gDate =[];
		/* $games =[];
		$gameName =[];
		$country =[]; */
		$showDate = str_replace('scorelist-','',$str);
		if(preg_match_all('/[A-Z]\[\d+\]=\[(.*?)\]/is' ,$content, $dataTxt))
		{
			$aGame =[];
			$A =[];
			$B =[];
			$C =[];
			foreach($dataTxt[0] as $k=>$v)
			{

				$aTemp =[];
				if(strpos($v,'A[') !==false)
				{
					$aTemp = explode(',',$dataTxt[1][$k]);
					$A[] = $aTemp;
				}
				if(strpos($v,'B[') !==false)
				{
					$B[] =explode(',',$dataTxt[1][$k]);
				}

				if(strpos($v,'C[') !==false) $C[] =explode(',',$dataTxt[1][$k]);
			}

			foreach($A as $k=>$v)
			{
				$aTemp2['union_id'] = (int) $v[1];
				$aTemp2['union_name'] =$this->tagFilter($B[$v[1]][1]);
				$aTemp2['game_id'] =(int) $v[0];
				$aTemp2['game_sort_id'] =(int) $v[1];
				$aTemp2['game_state'] =(int) $v[12];
				$aTemp2['home_team_name'] =$this->tagFilter($v[4]);
				$aTemp2['home_team_id'] =$this->tagFilter($v[2]);
				//$aTemp2['home_tem_rank'] =$this->tagFilter($v[26]);
				$aTemp2['away_team_name'] =$this->tagFilter($v[7]);
				$aTemp2['away_team_id'] =$this->tagFilter($v[3]);
				//$aTemp2['away_team_rank'] =$this->tagFilter($v[27]);
				$aTemp2['fsw_exp'] =$this->tagFilter($v[25]);
				//$aTemp2['fsw_ball'] =$this->tagFilter($v[38]);

				$sTemp = $this->tagFilter(strip_tags($v[10]));
				$tempDate =	substr($sTemp,0,8);
				$tempDate = date('Ymd',strtotime($tempDate));
				$tempTime =	substr($sTemp,8,5);
				$aTemp2['game_date'] = $tempDate;
				$aTemp2['game_time'] = $this->tagFilter($tempTime);
				$aTemp2['show_date'] = $showDate;

				if(array_search($tempDate,$gDate) === false)
				{
					$gDate[] =$tempDate;
				}
				$state=(int) $v[17];

				$aTemp2['score'] =$v[13] . "-" . $v[14];
				$aTemp2['half_score'] =$v[15] . "-" . $v[16];
				$aTemp2['update_time'] =time();

				$aGame[] = $aTemp2;
			}

			if(!empty($aGame))
			{
				$GIObj = new \Common\Model\GameFbinfoModel();
				$GIObj->dealForBfList($aGame,$gDate);
			}

			unset($aGame);
			unset($gDate);
			unset($A);
			unset($B);
			unset($C);
		}
	}



	/**
	 * 过滤数据值
	 * @param    string  需处理字符串
	 * @return   string  处理后字符串
	 */
	public function tagFilter($str)
    {
		$str = str_replace(array('\'','\'','^'),'',$str);
		$str = strip_tags ($str);
		return $str;
    }

}