<?php
/**
 * 资讯统计控制器
 *
 * @author dengweijun <406516482@qq.com>
 *
 * @since  2016-4-14
 */
use Think\Tool\Tool;
class NewStatisticsController extends CommonController {
    /**
     * 分类列表
     * @return string     
    */
    public function index()
	{
		$type = I("Type");
		switch ($type) {
			case '1':
				$map = $this->_search('user');
				//手动获取列表
				$list = $this->_list(CM("user"), $map);
				foreach ($list as $k => $v) {
					$where['author'] = $v['id'];
					$where['status'] = 1;
					//时间查询
					if(!empty($_REQUEST['startTime']) || !empty($_REQUEST['endTime'])){
					    if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
					        $startTime = strtotime($_REQUEST['startTime']);
					        $endTime   = strtotime($_REQUEST['endTime'])+86400;
					        $where['update_time'] = array('BETWEEN',array($startTime,$endTime));
					    } elseif (!empty($_REQUEST['startTime'])) {
					        $strtotime = strtotime($_REQUEST ['startTime']);
					        $where['update_time'] = array('EGT',$strtotime);
					    } elseif (!empty($_REQUEST['endTime'])) {
					        $endTime = strtotime($_REQUEST['endTime'])+86400;
					        $where['update_time'] = array('ELT',$endTime);
					    }
					}
					if(empty($_REQUEST['startTime']) && empty($_REQUEST['endTime'])){
						$where['update_time'] = array('BETWEEN',array(strtotime(date(Ymd)),strtotime(date(Ymd))+86400));
					}

					$galleryWhere['editor'] = $v['id'];
					$galleryWhere['status'] = 1;
					//时间查询
					if(!empty($_REQUEST['startTime']) || !empty($_REQUEST['endTime'])){
					    if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
					        $startTime = strtotime($_REQUEST['startTime']);
					        $endTime   = strtotime($_REQUEST['endTime'])+86400;
					        $galleryWhere['add_time'] = array('BETWEEN',array($startTime,$endTime));
					    } elseif (!empty($_REQUEST['startTime'])) {
					        $strtotime = strtotime($_REQUEST ['startTime']);
					        $galleryWhere['add_time'] = array('EGT',$strtotime);
					    } elseif (!empty($_REQUEST['endTime'])) {
					        $endTime = strtotime($_REQUEST['endTime'])+86400;
					        $galleryWhere['add_time'] = array('ELT',$endTime);
					    }
					}
					if(empty($_REQUEST['startTime']) && empty($_REQUEST['endTime'])){
						$galleryWhere['add_time'] = array('BETWEEN',array(strtotime(date(Ymd)),strtotime(date(Ymd))+86400));
					}
					$list[$k]['news'] = M('PublishList')->where($where)->field('sum(click_number) click_number,count(id) number')->find();
					$list[$k]['gallery'] = M('gallery')->where($galleryWhere)->field('sum(click_number) click_number,count(id) number')->find();
				}
				break;
			case '2':
				$map = $this->_search('PublishClass');
				//手动获取列表
				$list = $this->_list(CM("PublishClass"), $map);
				foreach ($list as $k => $v) {
					$where['class_id'] = $v['id'];
					$where['status'] = 1;
					//时间查询
					if(!empty($_REQUEST['startTime']) || !empty($_REQUEST['endTime'])){
					    if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
					        $startTime = strtotime($_REQUEST['startTime']);
					        $endTime   = strtotime($_REQUEST['endTime'])+86400;
					        $where['update_time'] = array('BETWEEN',array($startTime,$endTime));
					    } elseif (!empty($_REQUEST['startTime'])) {
					        $strtotime = strtotime($_REQUEST ['startTime']);
					        $where['update_time'] = array('EGT',$strtotime);
					    } elseif (!empty($_REQUEST['endTime'])) {
					        $endTime = strtotime($_REQUEST['endTime'])+86400;
					        $where['update_time'] = array('ELT',$endTime);
					    }
					}
					if(empty($_REQUEST['startTime']) && empty($_REQUEST['endTime'])){
						$where['update_time'] = array('BETWEEN',array(strtotime(date(Ymd)),strtotime(date(Ymd))+86400));
					}
					$list[$k]['news'] = M('PublishList')->where($where)->field('sum(click_number) click_number,count(id) number')->find();
				}
				break;
				case '3':
					$map = $this->_search('GalleryClass');
					//手动获取列表
					$list = $this->_list(CM("GalleryClass"), $map);
					foreach ($list as $k => $v) {
						$where['class_id'] = $v['id'];
						$where['status'] = 1;
						//时间查询
						if(!empty($_REQUEST['startTime']) || !empty($_REQUEST['endTime'])){
						    if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
						        $startTime = strtotime($_REQUEST['startTime']);
						        $endTime   = strtotime($_REQUEST['endTime'])+86400;
						        $where['add_time'] = array('BETWEEN',array($startTime,$endTime));
						    } elseif (!empty($_REQUEST['startTime'])) {
						        $strtotime = strtotime($_REQUEST ['startTime']);
						        $where['add_time'] = array('EGT',$strtotime);
						    } elseif (!empty($_REQUEST['endTime'])) {
						        $endTime = strtotime($_REQUEST['endTime'])+86400;
						        $where['add_time'] = array('ELT',$endTime);
						    }
						}
						if(empty($_REQUEST['startTime']) && empty($_REQUEST['endTime'])){
							$where['add_time'] = array('BETWEEN',array(strtotime(date(Ymd)),strtotime(date(Ymd))+86400));
						}
						$list[$k]['news'] = M('Gallery')->where($where)->field('sum(click_number) click_number,count(id) number')->find();
					}
					break;
		}
		$this->assign ('list', $list);
        $this->display();
    }
    public function infoCount()
    {
        $map['is_robot'] = 0;//正式用户
        $map['is_expert'] = 0;//不是专家
        
        //时间查询
        
        if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime']))
        {
            $startTime = strtotime($_REQUEST['startTime']);
            $endTime   = strtotime($_REQUEST['endTime'])+86400;
            $map['reg_time'] = array('BETWEEN',array($startTime,$endTime));
            $where['update_time'] = array('BETWEEN',array($startTime,$endTime));

            //统计记录的数量
            $timeDayCount = ($endTime-$startTime)/60/60/24;
            $minDate = $startTime;
            $maxDate = $endTime-86400;
            
        } elseif (!empty($_REQUEST['startTime']))
        {
            $startTime = strtotime($_REQUEST ['startTime']);
            $map['reg_time'] = array('EGT',$startTime);
            $where['update_time'] = array('EGT',$startTime);

            //统计记录的数量
            $timeDayCount = (strtotime(date('Ymd'))-$startTime)/60/60/24;
            $minDate = $startTime;
            $maxDate = strtotime(date('Ymd'));
            
        }
        else
        {
            $endTime = strtotime($_REQUEST['endTime']);
            if (! empty($endTime))
            {
                $endTime += 86400;
                $map['reg_time'] = array('ELT',$endTime);
                $where['update_time'] = array('ELT',$endTime);
            }
            $updateMinDate = M('PublishList')
                ->where($where)
                ->where(['_complex'=>['update_time'=>['GT',0]]])
                ->getField('min(update_time)');
            $regMinDate = M('FrontUser')
                ->where($map)
                ->where(['_complex'=>['reg_time'=>['GT',0]]])
                ->getField('min(reg_time)');
            
            $minDate = $regMinDate < $updateMinDate ? $regMinDate : $updateMinDate;
            if (! empty($endTime))
            {
                //统计记录的数量
                $timeDayCount = ($endTime-strtotime(date('Ymd',$minDate)))/60/60/24;
                $minDate = strtotime(date('Ymd',$minDate));
                $maxDate = $endTime-86400;
            }
            else
            {
                //统计记录的数量
                $timeDayCount = (strtotime(date('Ymd'))-strtotime(date('Ymd',$minDate)))/60/60/24;
                $minDate = strtotime(date('Ymd',$minDate));
                $maxDate = strtotime(date('Ymd'));
            }
        }
       
        if ($timeDayCount === 0)
        {
            $timeDayCount = 1;
        }
     
        if ($timeDayCount > 0)
        {
            
            //获取每页显示的条数
            $pageNum = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
            //获取当前的页码
            $currentPage =! empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1;
            $list = array();
            //导出操作
            $export = I('Export');
            if(! empty($export))//导出
            {
                if ($timeDayCount > 3000)
                    $this->error('数据过大，请筛选日期！');
                $i = 0;
                while (true)
                {
                    if (($maxDate - $i*86400 - $maxDate) > 0)//日期筛选
                    {
                        $i++;
                        continue;
                    }
                    $list[$i]['dataDate'] = date('Y-m-d',$maxDate - $i*86400 );
        
                    if (! ($maxDate - $i*86400  - $minDate))//日期筛选
                        break;
    
                    $i++;
                }
            }
            else//列表
            {
                $i = ($currentPage-1)*$pageNum;
                $j = 0 ; //用于记录显示的数据的条数
                while (true)
                {
                    if (($maxDate - $i*86400 - $maxDate) > 0)//日期筛选
                    {
                        $i++;
                        continue;
                    }
                    $list[$i]['dataDate'] = date('Y-m-d',$maxDate - $i*86400);

                    if (! ($maxDate - $i*86400 - $minDate))//日期筛选
                        break;

                    $i++;
                    $j++;
                    if ($j >= $pageNum)
                        break;
                }
//                $j = 0 ; //用于记录显示的数据的条数
//                for ($i = ($currentPage-1)*$pageNum ; $i < 100000 ; $i++)
//                {
//                    if ((strtotime(date('Y-m-d',strtotime('-'.$i.' Day'))) - $maxDate) > 0)//日期筛选
//                        continue;
//
//                    $list[$i]['dataDate'] = date('Y-m-d',strtotime('-'.$i.' Day'));
//
//                    if (! (strtotime(date('Y-m-d',strtotime('-'.$i.' Day'))) - $minDate))//日期筛选
//                        break;
//
//                    $j++;
//                    if ($j >= $pageNum)
//                        break;
//
//                }
                //拼接条件
                $whereDate = '';
                foreach ($list as $key => $value)
                {
                    $whereDate .= "'".$value['dataDate']."',";
                }
                $whereDate = substr($whereDate,0,-1);
    
                $map['_string'] = ' FROM_UNIXTIME(reg_time, \'%Y-%m-%d\') in ('.$whereDate.')';
                $where['_string'] = ' FROM_UNIXTIME(update_time, \'%Y-%m-%d\') in ('.$whereDate.')';
            }
            
    
            //每天注册量
            
            $userArr = M('FrontUser')
                ->where($map)
                ->field('COUNT(id) AS regCount,FROM_UNIXTIME(reg_time,\'%Y-%m-%d\') AS regDate')
                ->group('FROM_UNIXTIME(reg_time,\'%Y%m%d\')')
                ->select();
            //根据修改日期统计点击量
            $publishArr = M('PublishList')
                ->where($where)
                ->field('SUM(click_number) AS clickSum,FROM_UNIXTIME(update_time,\'%Y-%m-%d\') AS updateDate')
                ->group('FROM_UNIXTIME(update_time,\'%Y%m%d\')')
                ->select();
            foreach ($list as $key => $value)
            {
                foreach ($userArr as $k => $v)//每天注册量
                {
                    if ($value['dataDate'] === $v['regDate'])
                    {
                        $list[$key]['regCount'] = $v['regCount'];
                    }
                }
                foreach ($publishArr as $k => $v)//根据修改日期统计点击量
                {
                    if ($value['dataDate'] === $v['updateDate'])
                    {
                        $list[$key]['clickSum'] = $v['clickSum'];
                    }
                }
            }
    
    
            $this->assign ( 'totalCount', $timeDayCount );//当前条件下数据的总条数
            $pageNum =empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
            $this->assign ( 'numPerPage', $pageNum ); //每页显示多少条
            $this->assign ( 'currentPage', !empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1);//当前页码
            $this->setJumpUrl();
        }
        
    
    
        if(! empty($export))
        {
            $this->excelExport($list,'');//导出；
        }
        
        $this->assign('list',$list);
        $this->display();
    }
    /**
    * @param        $list  列表
    * @param string $filename  导出的文件名
    * @param int $gameType  1:足球；2：篮球
    */
    public function excelExport($list,$filename="")
    {
        $filename = empty($filename) ? date('Y-m-d') : $filename;
        $strTable ='<table width="500" border="1">';
        $strTable .= '<tr>';
        $strTable .= '<th style="text-align:center;font-size:12px;width:"*">日期</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">用户注册量</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width=120px;>资讯阅读量</th>';

        $strTable .= '</tr>';
        
        foreach($list as $k=>$val){
            $strTable .= '<tr>';
            
            $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['dataDate'].'</td>';
            $regCount = empty($val['regCount']) ? 0 : $val['regCount'];
            $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$regCount.'</td>';
            $clickSum = empty($val['clickSum']) ? 0 : $val['clickSum'];
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$clickSum.' </td>';
            $strTable .= '</tr>';
        }
        $strTable .='</table>';
        unset($orderList);
        downloadExcel($strTable,$filename);
        exit();
    }

    /**
     * 专家资讯统计
     */
    // public function expertStatistics1(){
    //     $map = $this->_search('user');
    //     $map['is_expert'] = 1;

    //     if(($_REQUEST['nick_name'])) $map['nick_name'] = ['like', $_REQUEST['nick_name'].'%'];

    //     //手动获取列表
    //     $list = $this->_list(CM("FrontUser"), $map);
    //     foreach ($list as $k => $v) {
    //         $where['user_id'] = $v['id'];
    //         $where['status'] = 1;

    //         //时间查询
    //         if(!empty($_REQUEST['startTime']) || !empty($_REQUEST['endTime'])){
    //             if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
    //                 $startTime = strtotime($_REQUEST['startTime']);
    //                 $endTime   = strtotime($_REQUEST['endTime'])+86400;
    //                 $where['update_time'] = array('BETWEEN',array($startTime,$endTime));
    //             } elseif (!empty($_REQUEST['startTime'])) {
    //                 $strtotime = strtotime($_REQUEST ['startTime']);
    //                 $where['update_time'] = array('EGT',$strtotime);
    //             } elseif (!empty($_REQUEST['endTime'])) {
    //                 $endTime = strtotime($_REQUEST['endTime'])+86400;
    //                 $where['update_time'] = array('ELT',$endTime);
    //             }
    //         }

    //         $list[$k]['news'] = M('PublishList')->where($where)->field('sum(click_number) AS click_number, count(id) AS number')->find();
    //     }

    //     $this->assign('list', $list);
    //     $this->display();
    // }

    public function expertStatistics() {
        $model = M('FrontUser');
        $map = $this->_search('FrontUser');
        $map['f.is_expert'] = 1;
        $map['l.status'] = 1;
        //时间查询
        if(!empty($_REQUEST['startTime']) || !empty($_REQUEST['endTime'])){
            if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                $startTime = strtotime($_REQUEST['startTime']);
                $endTime   = strtotime($_REQUEST['endTime'])+86400;
                $map['l.update_time'] = array('BETWEEN',array($startTime,$endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']);
                $map['l.update_time'] = array('EGT',$strtotime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime'])+86400;
                $map['l.update_time'] = array('ELT',$endTime);
            }
        }

        //排序字段 默认为主键名
        $order = $_REQUEST ['_order'] ? : 'number';
        $sort = $_REQUEST ['_sort'] == 'asc' ? 'asc' : 'desc';

        $pageNum     = 99999;
        $currentPage = !empty($_REQUEST[C('VAR_PAGE')]) ? $_REQUEST[C('VAR_PAGE')]:1;

        //分页查询数据
        $list = $model->alias('f')
            ->join('LEFT JOIN qc_publish_list l on l.user_id=f.id')
            ->field("f.id,f.nick_name,f.username,sum(l.click_number) AS click_number, count(l.id) AS number")
            ->where($map)
            ->having("number > 0")
            ->group('f.id')
            ->order($order." ".$sort)
            ->page($currentPage,$pageNum)
            ->select();

        //导出Excel
        $Export=I('Export');
        if(!empty($Export))
        {
            $this->downExport($list);
            exit;
        }


        //模板赋值显示
        $this->assign('list', $list);
        $this->assign ( 'totalCount', count($list) );
        $this->assign ( 'numPerPage', $pageNum ); //每页显示多少条
        $this->assign ( 'currentPage', $currentPage);
        $this->setJumpUrl();
        
        $this->display();
    }

    /**
     * 导出Excel
     * @param list $list [列表数据]
    **/
    public function downExport($list)
    {
        $filename  = date('Y-m-d');
        $strTable  ='<table width="600" border="1">';
        $strTable .= '<tr>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">用户ID</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">编辑日期</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">专家昵称</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">资讯发布数量</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">资讯阅读量</th>';
        $strTable .= '</tr>';
        foreach($list as $k=>$v){
            if($_REQUEST['startTime'] == '' && $_REQUEST['endTime'] == ''){
                $date = date('Y-m-d');
            }else{
                $date = $_REQUEST['startTime'] .'至'. $_REQUEST['endTime'];
            }
            $strTable .= '<tr>'.
                            '<td style="text-align:left;font-size:12px;">'.$v['id'].'</td>'.
                            '<td style="text-align:left;font-size:12px;">'.$date.'</td>'.
                            '<td style="text-align:left;font-size:12px;">'.$v['nick_name'].'（'.$v['username'].'）</td>'.
                            '<td style="text-align:left;font-size:12px;">'.$v['number'].'</td>'.
                            '<td style="text-align:left;font-size:12px;">'.$v['click_number'].'</td>'.
                          '</tr>';
        }
        $strTable .='</table>';
        downloadExcel($strTable,$filename);
        exit();
    }

}