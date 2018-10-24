<?php
/**
 * 直播站的信号源捉取
 * User: Liangzk <liangzk@qc.com>
 * Date: 2017/2/24
 * Time: 9:40
 */

vendor('QueryList.autoload');
use QL\QueryList;
use Think\Controller;
	
class CaptureController extends Controller
{
	#自建代理信息

	protected $proxyIp = '60.179.251.67:6666';
	
	public function __construct()
	{
		import('phpQuery');
		$this->setMyproxy();
	}
	
	public function setMyproxy()
	{
		if(C('PROXY_USER_PROXYIP') && C('PROXY_FLAG') === true)
		{
			$this->proxyIp = C('PROXY_USER_PROXYIP');
		}
	}
	
	/**
	 * 电视台
	 * @User Liangzk
	 * @DateTime 2017-03-10
	 */
	public function TvVideoUrl()
	{
		if ('tvzhibo' !== I('_key','','string'))
			$this->AjaxReturn(['status'=>0,'error'=>404]);
		
		$rules = [
			'tvName'=>['div ul li a','text'],
			'link'=>['div ul li a','href'],
		];
		
		$data = QueryList::Query('http://zb.zqseo.org.cn/index.php', $rules)->getData(function($item){
			$item['link'] = 'http://zb.zqseo.org.cn/index.php/'.$item['link'];
			return $item;
		});
		
		//获取电视台入口链接
		$tvLink = array();
		foreach ($data as $key => $value)
		{
			$tvLink[] = $value['link'].'&tvName='.$value['tvName'];
		}
	
		if (!empty($tvLink))
		{
			//多线程捉取
			QueryList::run('Multi',[
				//待采集链接集合
				'list' => $tvLink,
				'curl' => [
//				'opt' => array(
//					//这里根据自身需求设置curl参数
//					CURLOPT_SSL_VERIFYPEER => false,
//					CURLOPT_SSL_VERIFYHOST => false,
//					CURLOPT_FOLLOWLOCATION => true,
//					CURLOPT_AUTOREFERER => true,
//					//........
//				),
					'opt' => [
						//这里根据自身需求设置curl参数
						CURLOPT_PROXY => $this->proxyIp
					],
					//设置线程数
					'maxThread' => 8,
					//设置最大尝试数
					'maxTry' => 3
				],
				'success' => function($html){
						
					//采集规则
					$reg = array(
						'channelName'=>['div ul li a','text'],
						'link'=>['div ul li a','href'],
					);
					
					//获取频道入口链接
					$ql = QueryList::Query($html['content'],$reg);
					$res = $ql->getData(function($item){
						$item['vid'] = explode('&',explode('vid=',$item['link'])[1])[0];
						$item['link'] = 'http://zb.zqseo.org.cn/index.php/'.$item['link'];
						return $item;
					});
					
					//获取电视台标识
					$tv_token = explode('&',explode('token=',$html['info']['url'])[1])[0];
					$tv_name = explode('&',explode('tvName=',$html['info']['url'])[1])[0];
					
					//获取该电视台的频道id
					$oddRes = M()->db(1,C('DB_URL'))->query('SELECT id,vid  FROM live.live_tv WHERE tv_token = \''.$tv_token.'\'');
					
					$vidArr = array();
					if (!empty($oddRes))
					{
						foreach ($oddRes as $key => $value)
						{
							$vidArr[] = $value['vid'];
						}
					}
				
					$addDataStr = '';
					foreach ($res as $key => $value)
					{
						if (!in_array($value['vid'],$vidArr))
						{
							//新增
							$addDataStr .= "('".$tv_token."','".$tv_name."',".$value['vid'].",'".$value['channelName']."',".time()."),";
						}
					}
					//拼接SQL
					if (!empty($addDataStr))
					{
						$addSql = 'INSERT INTO live.live_tv(tv_token,tv_name,vid,channel_name,update_time) VALUES'.$addDataStr;
						$addSql = substr($addSql,0,-1);
						M()->db(1,C('DB_URL'))->query($addSql);
						print_r($tv_token.'-------新添加<br/>');
					}
					else
					{
						print_r($tv_token.'----已有<br/>');
					}
//
					
					
					
				}
			]);
		}
		else
		{
			print_r('操作失败');
		}
	
		
	}
	
	/**
	 * @User Liangzk
	 * @DateTime 2017-03-10
	 *  电视台频道更新，添加
	 *
	 */
	public function tvVideoSource()
	{
		$tv_token = I('tv_token','','string');
		$req_tv_id = I('tv_id',0,'int');
		if ($tv_token === '' || 'tvzhiboupdate' !== I('_key','','string'))
		{
			$this->AjaxReturn(['status'=>0,'error'=>404]);
		}
		
		if ($req_tv_id === 0)
		{
			$oddRes = M()->db(1,C('DB_URL'))->query('SELECT vid  FROM live.live_tv WHERE tv_token = \''.$tv_token.'\'');
		}
		else
		{
			$oddRes = M()->db(1,C('DB_URL'))->query('SELECT vid  FROM live.live_tv WHERE id = '.$req_tv_id.' AND tv_token = \''.$tv_token.'\'');
		}
	
		if (!empty($oddRes))
		{
			
			$data = QueryList::Query('http://zb.zqseo.org.cn/index.php', ['link'=>['div ul li a','href']])->getData(function($item){
				$item['tv_token'] = explode('&',explode('token=',$item['link'])[1])[0];
				$item['link'] = 'http://zb.zqseo.org.cn/index.php/'.$item['link'];
				return $item;
			});
			
			//获取电视台入口
			$crawlUrl = '';
			foreach ($data as $key => $value)
			{
				if ($tv_token !== $value['tv_token'])
					continue;
				
				$crawlUrl = $value['link'];
				
				break;
			}
			
			if (!empty($crawlUrl))
			{
				//获取频道入口
				$reg = array(
					'channelName'=>['div ul li a','text'],
					'link'=>['div ul li a','href'],
				);
				$channelData = QueryList::Query($crawlUrl, $reg)->getData(function($item){
					$item['vid'] = explode('&',explode('vid=',$item['link'])[1])[0];
					$item['link'] = 'http://zb.zqseo.org.cn/index.php/'.$item['link'];
					return $item;
				});
				
				$channelUrl = array();
				foreach ($channelData as $key => $value)
				{
					if ($req_tv_id !== 0 && $oddRes[0]['vid'] != $value['vid'])
					{
						continue;
					}
				
					$channelUrl[] = $value['link'].'&SignId='.$tv_token;
				}
			
				
				if (!empty($channelUrl))
				{
					//多线程捉取视频源
					QueryList::run('Multi',[
						//待采集链接集合
						'list' => $channelUrl,
						'curl' => [
							'opt' => [
								//这里根据自身需求设置curl参数
								CURLOPT_PROXY => $this->proxyIp
							],
							//设置线程数
							'maxThread' => 8,
							//设置最大尝试数
							'maxTry' => 3
						],
						'success' => function($html){
							
							//采集规则
							$reg = [
								'urlName'=>['div ul li select option','text'],
								'link'=>['div ul li select option','value'],
							];
							
							//获取频道入口链接
							$ql = QueryList::Query($html['content'],$reg);
							$res = $ql->getData(function($item){
								return $item;
							});
							
							$vid = explode('&',explode('vid=',$html['info']['url'])[1])[0];
							$tv_token = explode('&',explode('SignId=',$html['info']['url'])[1])[0];
							
							$tvRes = M()->db(1,C('DB_URL'))->query('SELECT id FROM live.live_tv WHERE vid = '.$vid.' AND tv_token = \''.$tv_token.'\'');

							if (!empty($tvRes))
							{
								//删除
								M()->db(1,C('DB_URL'))->query('DELETE FROM live.live_tv_video  WHERE tv_id = '.$tvRes[0]['id']);
							}
							
							//添加
							$addDataStr = '';
							foreach ($res as $key => $value)
							{
								//新增
								$addDataStr .= "(".$tvRes[0]['id'].",'".$value['urlName']."','".$value['link']."',".time()."),";
							}
							
							//拼接SQL
							if (!empty($addDataStr))
							{
								$addSql = 'INSERT INTO live.live_tv_video(tv_id,url_name,url,update_time) VALUES'.$addDataStr;
								$addSql = substr($addSql,0,-1);
								M()->db(1,C('DB_URL'))->query($addSql);
								print_r($tv_token.'-------新添加<br/>');
							}
							else
							{
								print_r($tv_token.'>>>>>更新失败');
							}
						}
					]);
				}
				else
				{
					print_r('>>>>>>>该'.$tv_token.'电视台更新失败<br/>');
				}
			}
			else
			{
				print_r('>>>>>>>在网站找不到该'.$tv_token.'电视台<br/>');
			}
			
		}
		else
		{
			print_r('>>>>>>>数据库找不到该'.$tv_token.'电视台<br/>');
		}
		
	}
	/**
	 * Liangzk <Liangzk@qc.com>
	 * @DateTime 2017-02-24
	 * 乐视信号源
	 * 全部暂时有51个信号源
	 *
	 * 注意：建议不要频繁更新51个信号源
	 */
	public function getLeVideoUrl($startSignal = 1 ,$endSignal = 51)
	{
		$updateData = $addData = $channelOnArr = array();
		
		$oddData = M()->db(1,C('DB_URL'))->query('SELECT id,channel_no  FROM live.live_video_source WHERE channel_class = 1');
	
		foreach ($oddData as $key => $value)
		{
			$channelOnArr[] = $value['channel_no'];
		}
		
		if (empty($oddData))
		{
			$oddData = array();
		}
		
		
		for ($i = $startSignal ; $i <= $endSignal; $i++)
		{
			//高清
			$storeLef = $this->curlCapture('http://nba.tmiaoo.com/le/lef.php?url='.$i);
			$m_urlLef = stripslashes(explode('\')',explode('decodeURIComponent(\'',$storeLef)[1])[0]);
			$web_urlLef = urlencode(urldecode($m_urlLef));
//			//超清
			$storeLeflg = $this->curlCapture('http://nba.tmiaoo.com/le/leflg.php?url='.$i);
			$m_urlLeflg = stripslashes(explode('\')',explode('decodeURIComponent(\'',$storeLeflg)[1])[0]);
			$web_urlLeflg = urlencode(urldecode($m_urlLeflg));
			if (in_array($i,$channelOnArr))
			{
				foreach ($oddData as $key => $value)
				{
					if ($value['channel_no'] != $i)
					{
						continue;
					}
					
					$updateData[] = ['id'=>$value['id'],'m_hd_url'=>$m_urlLef,'m_sc_url'=>$m_urlLeflg,'pc_hd_url'=>$web_urlLef,'pc_sc_url'=>$web_urlLeflg];
					break;
				}
				
				
			}
			else
			{
				$addData[] = ['channel_class'=>1,'channel_no'=>$i,'m_hd_url'=>$m_urlLef,'m_sc_url'=>$m_urlLeflg,'pc_hd_url'=>$web_urlLef,'pc_sc_url'=>$web_urlLeflg];
			}
			
			sleep(1);
		}
		//为了更新时间一致
		foreach ($updateData as $key => $value)
		{
			$updateData[$key]['update_time'] = time();
		}
		
		foreach ($addData as $key => $value)
		{
			$addData[$key]['update_time'] = time();
		}
		
		
		$this->AjaxReturn(['status'=>1,'addRes'=>empty($addData) ? null : $addData,'editRes'=>empty($updateData) ? null : $updateData]);
		
		
	}
	
	/**
	 * Liangzk <Liangzk@qc.com>
	 * DateTime 2016-02-23
	 * //针对单一的信号源更新
	 */
	public function sourceUpdate($id,$channel_no)
	{
		
		if ($id > 0 && $channel_no > 0)
		{
			//高清
			$urlHdArr = [
				1 =>'http://nba.tmiaoo.com/le/lef.php?url='.$channel_no,
				2 =>'',
				3 =>'',
			];
			//超清
			$urlScArr = [
				1 =>'http://nba.tmiaoo.com/le/leflg.php?url='.$channel_no,
				2 =>'',
				3 =>'',
			];
			
			//高清
			$storeLef = $this->curlCapture($urlHdArr[1]);
			$m_urlLef = stripslashes(explode('\')',explode('decodeURIComponent(\'',$storeLef)[1])[0]);
			$web_urlLef = urlencode(urldecode($m_urlLef));
//			//超清
			$storeLeflg = $this->curlCapture($urlScArr[1]);
			$m_urlLeflg = stripslashes(explode('\')',explode('decodeURIComponent(\'',$storeLeflg)[1])[0]);
			$web_urlLeflg = urlencode(urldecode($m_urlLeflg));
			
			
			$updateData = ['id'=>$id,'m_hd_url'=>$m_urlLef,'m_sc_url'=>$m_urlLeflg,'pc_hd_url'=>$web_urlLef,'pc_sc_url'=>$web_urlLeflg,'update_time'=>time()];
			
		}
		
		$this->AjaxReturn(['status'=>1,'editRes'=>empty($updateData) ? null : $updateData]);

	}
	/**
	 * Liangzk <Liangzk@qc.com>
	 * @DateTime 2017-02-23
	 * QQ信号源
	 * 全部暂时有10个信号源
	 *
	 */
	public function getQqVideoUrl($id = 1)
	{
//		$res = $this->get_curl("/json/2016-12-03/1.html", "", 'http://nba.tmiaoo.com','get');
		
//		$headers = array(
//			'Accept:application/json, text/javascript, */*; q=0.01',
//			'User-Agent:Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36',
//			'Referer:http://c.tmiaoo.com/po/2.html',
//			'Accept-Encoding:gzip, deflate, sdch',
//			'Accept-Language:zh-CN,zh;q=0.8',
//		);
//
//		$resultArr = $this->curlCapture('http://c.tmiaoo.com/json/2016-12-03/1.shtml',$headers);
		
		$headers = array(
			'Accept:application/json, text/javascript, */*; q=0.01',
			'User-Agent:Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36',
			'Referer:http://p.jrszhibo.com/po/p/1.html',
			'Accept-Encoding:gzip, deflate, sdch',
			'Accept-Language:zh-CN,zh;q=0.8',
			'X-Requested-With:XMLHttpRequest',
			'Cache-Control:max-age=0',
			'Connection:keep-alive'
		);
		
		$mathRand = mt_rand()/mt_getrandmax();
		$resultArr = $this->curlCapture('http://nba.tmiaoo.com/json/2016-12-03/1.php?id='.$mathRand,$headers);
		
		$res = json_decode($resultArr, true);//将json转为数组格式数据
		
		$updateData = $addData = $tempArr = $channelOnArr = array();
		
		$oddData = M()->db(1,C('DB_URL'))->query('SELECT id,channel_no  FROM live.live_video_source WHERE channel_class = 3');
		
		foreach ($oddData as $key => $value)
		{
			$channelOnArr[] = $value['channel_no'];
		}
		
		
		foreach ($res as $key => $value)
		{
			$type = substr($key,0,1);//x:web端  w:M站
			$signalNum = substr($key,1);//演播号
			
			if(in_array($signalNum,$channelOnArr))
			{
				foreach ($oddData as $k => $v)
				{
					if ($signalNum != $v['channel_no'])
					{
						continue;
					}
					
					$updateData[$v['id']]['id'] = $v['id'];
					
					//web信号源
					if ($type == 'x')
					{
						$updateData[$v['id']]['pc_hd_url'] = $value;
					}
					elseif ($type == 'w')//M站信号源
					{
						$updateData[$v['id']]['m_hd_url'] = $value;
					}
					
					break;
				}
			}
			else
			{
				$tempArr[$signalNum]['channel_class'] = 3;
				$tempArr[$signalNum]['channel_no'] = $signalNum;
				$tempArr[$signalNum]['m_sc_url'] = '';
				$tempArr[$signalNum]['pc_sc_url'] = '';
				//web信号源
				if ($type == 'x')
				{
					$tempArr[$signalNum]['pc_hd_url'] = $value;
				}
				elseif ($type == 'w')//M站信号源
				{
					$tempArr[$signalNum]['m_hd_url'] = $value;
				}
//
			}
			
		}
		
		//为了更新时间一致
		foreach ($updateData as $key => $value)
		{
			$updateData[$key]['update_time'] = time();
		}
		//为了更新时间一致
		foreach ($tempArr as $key => $value)
		{
			$addData[] = $value;
		}
		
		if (!empty($addData))
		{
			//为了更新时间一致
			foreach ($addData as $key => $value)
			{
				$addData[$key]['update_time'] = time();
			}
		}
		
		$this->AjaxReturn(['status'=>1,'addRes'=>empty($addData) ? null : $addData,'editRes'=>empty($updateData) ? null : $updateData]);
	}
	
	/**
	 * Liangzk <Liangzk@qc.com>
	 * @DateTime 2017-03-01
	 *	腾讯高清直播无插件
	 *  暂时只有15个频道
	 */
	public function getAllTencentWu($startSignal = 1 ,$endSignal = 15)
	{
		
		
		//请求链接
		$urlList = array();
		for ($i = $startSignal ; $i <= $endSignal; $i++)
		{
			$urlList[] = 'http://w.zhibo.me:8088/qqliveHD'.$i.'.php';
		}
		
		//多线程扩展
		QueryList::run('Multi',[
			//待采集链接集合
			'list' => $urlList,
			'curl' => [
				'opt' => [
					//这里根据自身需求设置curl参数
					CURLOPT_PROXY => $this->proxyIp
				],
				//设置线程数
				'maxThread' => 5,
				//设置最大尝试数
				'maxTry' => 3
			],
			'success' => function($html){
				
//				preg_match('/<video[^>]*\s+src="([^"]*)"[^>]*>/is', $html['content'], $urlVideoArr);
				//PC直播源
				$iframeStr = explode('</iframe>',explode('<iframe',$html['content'])[1])[0];
				$urlPc = explode('\'',explode('id=',$iframeStr)[1])[0];
				
				//M站直播源
				$videoStr = explode('</video>',explode('<video',$html['content'])[1])[0];
				$urlM = explode('\'',explode('src=\'',$videoStr)[1])[0];
				
				//播放室号
				$channel_no = explode('.',explode('qqliveHD',$html['info']['url'])[1])[0];
				
				$data = S('AdminCaptureGetAllTencentWuUrlList');
				$data[$channel_no] = ['urlPc'=>$urlPc,'urlM'=>$urlM];
				S('AdminCaptureGetAllTencentWuUrlList',$data);
			}
		]);
		
		$data = S('AdminCaptureGetAllTencentWuUrlList');
		S('AdminCaptureGetAllTencentWuUrlList',null);
		
		$updateData = $addData = $channelOnArr = array();
		//获取频道
		$oddData = M()->db(1,C('DB_URL'))->query('SELECT id,channel_no  FROM live.live_video_source WHERE channel_class = 4');
		foreach ($oddData as $key => $value)
		{
			$channelOnArr[] = $value['channel_no'];
		}
		
		foreach ($data as $k => $v)
		{
			if (in_array($k,$channelOnArr))
			{
				//编辑
				foreach ($oddData as $key => $value)
				{
					if ($value['channel_no'] != $k)
					{
						continue;
					}
					
					$updateData[] = ['id'=>$value['id'],'m_hd_url'=>$v['urlM'],'pc_hd_url'=>$v['urlPc']];
				}
			}
			else
			{
				//新增
				$addData[] = ['channel_class'=>4,'channel_no'=>$k,'m_hd_url'=>$v['urlM'],'m_sc_url'=>'','pc_hd_url'=>$v['urlPc'],'pc_sc_url'=>''];
			}
		}
		
		//为了更新时间一致
		foreach ($updateData as $key => $value)
		{
			$updateData[$key]['update_time'] = time();
		}
		//为了更新时间一致
		foreach ($addData as $key => $value)
		{
			$addData[$key]['update_time'] = time();
		}
		
		$this->AjaxReturn(['status'=>1,'addRes'=>empty($addData) ? null : $addData,'editRes'=>empty($updateData) ? null : $updateData]);
		
	}
	/**
	 * Liangzk <Liangzk@qc.com>
	 * DateTime 2016-03--01
	 *针对单一的腾讯无插件信号源更新
	 */
	public function updateOneTencentWu($id,$channel_no)
	{
	
		if ($id > 0 && $channel_no > 0)
		{
			$headers = array(
				'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
				'Upgrade-Insecure-Requests:1',
				'User-Agent:Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36',
				'Referer:http://www.5chajian.com/tv/qqlive'.$channel_no.'.html',
				'Accept-Encoding:gzip, deflate, sdch',
				'Accept-Language:zh-CN,zh;q=0.8',
			);
			$store = $this->curlCapture('http://w.zhibo.me:8088/qqliveHD'.$channel_no.'.php',$headers);
		
			$iframeStr = explode('</iframe>',explode('<iframe',$store)[1])[0];
			$urlPc = explode('\'',explode('id=',$iframeStr)[1])[0];
			
			$videoStr = explode('</video>',explode('<video',$store)[1])[0];
			$urlM = explode('\'',explode('src=\'',$videoStr)[1])[0];
			
			$updateData = ['id'=>$id,'m_hd_url'=>$urlM,'pc_hd_url'=>$urlPc,'update_time'=>time()];
			
		}
		
		$this->AjaxReturn(['status'=>1,'editRes'=>empty($updateData) ? null : $updateData]);
		
	}
	/**
	 * Liangzk <Liangzk@qc.com>
	 * @DateTime 2017-03-03
	 *	玮来体育
	 *  暂时只有10个频道
	 */
	public function getJwVideoUrl($startSignal = 1 ,$endSignal = 10)
	{
		if ($startSignal < 1) $startSignal = 1;
		if ($endSignal > 10) $endSignal = 10;
		
		$updateData = $addData = $channelOnArr = array();
		//获取频道
		$oddData = M()->db(1,C('DB_URL'))->query('SELECT id,channel_no  FROM live.live_video_source WHERE channel_class = 5');
		
		foreach ($oddData as $key => $value)
		{
			$channelOnArr[] = $value['channel_no'];
		}
		
		if (empty($oddData))
		{
			$oddData = array();
		}
		for ($i = $startSignal ; $i <= $endSignal; $i++)
		{
			//PC端
			$headersPC = array(
				'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
				'Upgrade-Insecure-Requests:1',
				'User-Agent:Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36',
				'Referer:http://us.141592653589793238462643383.com/cs.html?id='.$i,
				'Accept-Encoding:gzip, deflate, sdch',
				'Accept-Language:zh-CN,zh;q=0.8',
			);
			$storePC = $this->curlCapture('http://c.tmiaoo.com/po/'.$i.'.html',$headersPC);
			$iframeStrPc = explode('"></iframe>',explode('$("#showcontent").html(\'',$storePC)[1])[0];
			$urlPc = explode('id=',explode('"',explode('src="',$iframeStrPc)[1])[0])[1];
			
			//M站
			$headersM = array(
				'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
				'Upgrade-Insecure-Requests:1',
				'User-Agent:Mozilla/5.0 (iPhone; CPU iPhone OS 9_1 like Mac OS X) AppleWebKit/601.1.46 (KHTML, like Gecko) Version/9.0 Mobile/13B143 Safari/601.1',
				'Referer:http://nba.tmiaoo.com/po/'.$i.'w.html',
				'Accept-Encoding:gzip, deflate, sdch',
				'Accept-Language:zh-CN,zh;q=0.8',
			);
			$storeM = $this->curlCapture('http://c.tmiaoo.com/po/w/'.$i.'.html',$headersM);
			
			$iframeStrM = explode('"></video>',explode('$("#showcontent").html(\'',$storeM)[1])[0];
			
			$urlM =explode('"',explode('src="',$iframeStrM)[1])[0];
			
			if (in_array($i,$channelOnArr))
			{
				//编辑
				foreach ($oddData as $key => $value)
				{
					if ($value['channel_no'] != $i)
					{
						continue;
					}
					
					if (!empty($url))
					{
						$updateData[] = ['id'=>$value['id'],'m_hd_url'=>$urlM,'pc_hd_url'=>$urlPc];
					}
				}
			}
			else
			{
				//新增
				$addData[] = ['channel_class'=>5,'channel_no'=>$i,'m_hd_url'=>$urlM,'m_sc_url'=>'','pc_hd_url'=>$urlPc,'pc_sc_url'=>''];
			}
			
			
			sleep(1);
		}
		//为了更新时间一致
		foreach ($updateData as $key => $value)
		{
			$updateData[$key]['update_time'] = time();
		}
		//为了更新时间一致
		foreach ($addData as $key => $value)
		{
			$addData[$key]['update_time'] = time();
		}
		
		$this->AjaxReturn(['status'=>1,'addRes'=>empty($addData) ? null : $addData,'editRes'=>empty($updateData) ? null : $updateData]);
		
	}
	/**
	 * Liangzk <Liangzk@qc.com>
	 * DateTime 2016-03--03
	 *针对单一的玮来体育信号源更新
	 */
	public function updateJwVideoUrl($id,$channel_no)
	{
		if ($id > 0 && $channel_no > 0)
		{
			//PC端
			$headersPC = array(
				'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
				'Upgrade-Insecure-Requests:1',
				'User-Agent:Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36',
				'Referer:http://us.141592653589793238462643383.com/cs.html?id='.$channel_no,
				'Accept-Encoding:gzip, deflate, sdch',
				'Accept-Language:zh-CN,zh;q=0.8',
			);
			$storePC = $this->curlCapture('http://c.tmiaoo.com/po/'.$channel_no.'.html',$headersPC);
			$iframeStrPc = explode('"></iframe>',explode('$("#showcontent").html(\'',$storePC)[1])[0];
			$urlPc = explode('id=',explode('"',explode('src="',$iframeStrPc)[1])[0])[1];
			
			//M站
			$headersM = array(
				'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
				'Upgrade-Insecure-Requests:1',
				'User-Agent:Mozilla/5.0 (iPhone; CPU iPhone OS 9_1 like Mac OS X) AppleWebKit/601.1.46 (KHTML, like Gecko) Version/9.0 Mobile/13B143 Safari/601.1',
				'Referer:http://nba.tmiaoo.com/po/'.$channel_no.'w.html',
				'Accept-Encoding:gzip, deflate, sdch',
				'Accept-Language:zh-CN,zh;q=0.8',
			);
			$storeM = $this->curlCapture('http://c.tmiaoo.com/po/w/'.$channel_no.'.html',$headersM);
			
			$iframeStrM = explode('"></video>',explode('$("#showcontent").html(\'',$storeM)[1])[0];
			
			$urlM =explode('"',explode('src="',$iframeStrM)[1])[0];
			
			$updateData = ['id'=>$id,'m_hd_url'=>$urlM,'pc_hd_url'=>$urlPc,'update_time'=>time()];
			
		}
		
		$this->AjaxReturn(['status'=>1,'editRes'=>empty($updateData) ? null : $updateData]);
		
	}
	/**
	 * Liangzk <Liangzk@qc.com>
	 * @DateTime 2017-03-08
	 *	企鹅直播源捉取--无插件
	 *
	 */
	public function qieTv()
	{
		
		\phpQuery::newDocumentFile('http://www.5chajian.com');
		$artlist = pq(".against");
		$qieTvUrlArr = array();
		foreach($artlist as $tr)
		{
			$td = pq($tr)->find('td');
			$typeName = $td->attr('title');
			if ($typeName == '足球' || $typeName == '篮球')
			{
				
				$aStr = $td->siblings('.live_link')->find('a');
				
				foreach ($aStr as $key => $value)
				{
					$url = $value->getAttribute('href');
					
					if (stripos($url,'/tv/qietv') === false)
						continue;
					
					$arr = explode('../',$url);
					$qieTvUrlArr[] = count($arr) == 1 ? $arr[0] : '/'.$arr[1];
				}
			}
		}
		
		$updateData = $addData = $channelOnArr = array();
		if (!empty($qieTvUrlArr))
		{
			
			$urlList = array();
			foreach ($qieTvUrlArr as $key => $value)
			{
				$urlList[] = 'http://w.zhibo.me:8088/qie.php?id='.explode('.html',explode('/tv/qietv-',$value)[1])[0];
			}
			
			//多线程扩展
			QueryList::run('Multi',[
				//待采集链接集合
				'list' => $urlList,
				'curl' => [
					'opt' => [
						//这里根据自身需求设置curl参数
						CURLOPT_PROXY => $this->proxyIp
					],
					//设置线程数
					'maxThread' => 5,
					//设置最大尝试数
					'maxTry' => 3,
				],
				'success' => function($html){
					
					preg_match('/<video[^>]*\s+src="([^"]*)"[^>]*>/is', $html['content'], $urlArr);
					$qieTvId = explode('?id=',$html['info']['url'])[1];
					
					$data = S('AdminCaptureQieTvUrlList');
					$data[$qieTvId] = [$qieTvId=>$urlArr[1]];
					S('AdminCaptureQieTvUrlList',$data);
				}
			]);
			
			$data = S('AdminCaptureQieTvUrlList');
			S('AdminCaptureQieTvUrlList',null);
			
			
			//获取频道
			$oddData = M()->db(1,C('DB_URL'))->query('SELECT id,channel_no  FROM live.live_video_source WHERE channel_class = 6');
			foreach ($oddData as $key => $value)
			{
				$channelOnArr[] = $value['channel_no'];
			}
			
			foreach ($data as $key => $value)
			{
				if (in_array($key,$channelOnArr))
				{
					//编辑
					foreach ($oddData as $k => $v)
					{
						if ($v['channel_no'] != $key)
						{
							continue;
						}
						
						
						$updateData[] = ['id'=>$v['id'],'m_hd_url'=>$value[$key],'pc_hd_url'=>$value[$key]];
						
					}
				}
				else
				{
					//新增
					$addData[] = ['channel_class'=>6,'channel_no'=>$key,'m_hd_url'=>$value[$key],'m_sc_url'=>'','pc_hd_url'=>$value[$key],'pc_sc_url'=>''];
				}
			}
			
			//为了更新时间一致
			foreach ($updateData as $key => $value)
			{
				$updateData[$key]['update_time'] = time();
			}
			//为了更新时间一致
			foreach ($addData as $key => $value)
			{
				$addData[$key]['update_time'] = time();
			}
			
		}
		
		$this->AjaxReturn(['status'=>1,'addRes'=>empty($addData) ? null : $addData,'editRes'=>empty($updateData) ? null : $updateData]);
	}
	
	/**
	 * Liangzk <Liangzk@qc.com>
	 * @DateTime 2017-03-08
	 *	企鹅直播源捉取--无插件
	 *
	 *
	 */
	public function updateQieTv($id,$channel_no)
	{
		if ($id > 0 && $channel_no > 0)
		{
			//捉取
			$headers = array(
				'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
				'Upgrade-Insecure-Requests:1',
				'User-Agent:Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36',
				'Referer:http://www.5chajian.com/tv/qietv-'.$channel_no.'.html',
				'Accept-Encoding:gzip, deflate, sdch',
				'Accept-Language:zh-CN,zh;q=0.8',
			);
			$store = $this->curlCapture('http://w.zhibo.me:8088/qie.php?id='.$channel_no,$headers);
			
			//根据返回的数据进行过滤
			preg_match('/<video[^>]*\s+src="([^"]*)"[^>]*>/is', $store, $urlArr);
			
			
			$updateData = ['id'=>$id,'m_hd_url'=>$urlArr[1],'pc_hd_url'=>$urlArr[1]];
			
			
		}
		
		$this->AjaxReturn(['status'=>1,'editRes'=>empty($updateData) ? null : $updateData]);
	}
	public function curlCapture($requestUrl,$headers)
	{
		// 初始化 CURL
		$ch = curl_init();

//			$headers = array(
//				'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
//				'Upgrade-Insecure-Requests:1',
//				'User-Agent:Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36',
//				'Referer:'.$referer,
//				'Accept-Encoding:gzip, deflate, sdch',
//				'Accept-Language:zh-CN,zh;q=0.8',
//			);
		
		// 设置 URL
		curl_setopt($ch, CURLOPT_URL,$requestUrl);
		// 让 curl_exec() 获取的信息以数据流的形式返回，而不是直接输出。
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		// 在发起连接前等待的时间，如果设置为0，则不等待
		curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 0);
		// 设置 CURL 最长执行的秒数
		curl_setopt ($ch, CURLOPT_TIMEOUT, 30);
		#自建代理
		curl_setopt($ch, CURLOPT_PROXY, $this->proxyIp);
		curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		// 尝试取得文件内容
		$store = curl_exec ($ch);
		
		
		// 检查文件是否正确取得
		if (curl_errno($ch)){
			//"无法取得 URL 数据";
			return null;
			exit;
		}
		
		// 关闭 CURL
		curl_close($ch);
		
		return $store;
	}
	
	
}