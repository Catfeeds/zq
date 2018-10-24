<?php
/**
 * 异常记录
 ** @user liangzk <liangzk@qc.com>
 *  @DateTime 2016-09-06 15:48
 *  @version v2.1
 */

class ExceptionNoticeController extends CommentController
{
    /**
     * 异常记录列表
     */
    public function index()
    {
        $map = $this->_search('ExceptionNoticeView');
        //创建查询
        if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
            if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                $startTime = strtotime($_REQUEST ['startTime']);
                $endTime   = strtotime($_REQUEST ['endTime']);
                $map['create_time'] = array('BETWEEN',array($startTime,$endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']);
                $map['create_time'] = array('EGT',$strtotime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime']);
                $map['create_time'] = array('ELT',$endTime);
            }
        }
        $list = $this->_list(D('ExceptionNoticeView'),$map,'status asc,create_time desc');
        $excetionConf = M('config')->where(['sign'=>'alert'])->find();
        $excetionConf = json_decode($excetionConf['config'],true);
        foreach ($list as $key => $value)
        {
            foreach ($excetionConf as $k => $v)
            {
                if ($value['exception_class'] == $k)
                {
                    $list[$key]['exception_name'] = $v['exception_name'];
                }
            }
            
        }

        $this->assign('section_time_arr',M('ExceptionLog')->where(['section_time'=>['GT',0]])->group('section_time')->getField('section_time',true));
        $this->assign('standard_arr',M('ExceptionLog')->where(['standard'=>['GT',0]])->group('standard')->getField('standard',true));
        $this->assign('list',$list);
        $this->display();
    }
    /**
     * 异常处理状态的修改
     * @user liangzk <liangzk@qc.com>
     * @DateTime 2016-09-012 14:00
     *  @version v2.1
     */
    public function editStatus()
    {
        $id = I('id');
        $status = I('status');
        $res = M('ExceptionLog')->where(['id'=>$id])->save(['status'=>$status,'deal_time'=>time(),'admin_id'=>$_SESSION['authId']]);
        if ($res !== false)
        {
            $this->success('操作成功！');
        }
        $this->error('操作失败！');
    }
    /**
     * 异常监控配置
     * @user liangzk <liangzk@qc.com>
     * @DateTime 2016-09-14 11:30
     *  @version v2.2
     */
    public function exception_config()
    {
        $excetionConf = M('config')->where(['sign'=>'alert'])->find();
        $list = json_decode($excetionConf['config'],true);
        if (IS_POST)//编辑
        {
            $exception_name = I('exception_name');
            $standard = I('standard');
            foreach ($list as $key => $value)
            {
                if ($key !== 1)
                {
                    if (empty($exception_name[$key]) || empty($standard[$key]))
                    {
                        $this->error('参数错误！');
                    }
                    $list[$key]['section_time'] = $exception_name[$key];
                    $list[$key]['standard'] = $standard[$key];
                }
            }
            $res = M('config')->where(['sign'=>'alert'])->save(['config'=>json_encode($list)]);
            if ($res !== false)
            {
                $this->success('操作成功！',cookie('_currentUrl_'));
            }
            $this->success('操作失败！');
            
        }
      
        foreach ($list as $k => $v)
        {
            $list[$k]['descs'] = str_replace('section_time',$v['section_time'],$v['descs']);
            $list[$k]['descs'] = str_replace('standard',$v['standard'],$list[$k]['descs']);
        }
        $this->assign('list',$list);
        $this->display();
        
        
    }

    /**
     * 用户异常异常监控
     * @user liangzk <liangzk@qc.com>
     * @DateTime 2016-09-06 15:48
     *  @version v2.1
     */
    public function getException()
    {
        $this->getExceptionAccount();
        sleep(1);
        $this->getExceptionPoint(2);
        sleep(1);
        $this->getExceptionPoint(3);
        sleep(2);
        $this->getExceptionComment();
        sleep(1);
        $this->getExceptionPost(6);
        sleep(1);
        $this->getExceptionPost(7);


    }
    
    /**
     * 查询金币小于0的用户账号
     * @user liangzk <liangzk@qc.com>
     * @DateTime 2016-09-06 15:48
     *  @version v2.1
     */
    public function getExceptionAccount()
    {

        $userIdArr = M('FrontUser')->where(['coin'=>['LT',0],'status'=>1])->getField('id',true);
        if (! empty($userIdArr))
        {
            //获取异常记录
            $exceptionIdArr = M('ExceptionLog')
                            ->where(['exception_class'=>1,'status'=>0,'exception_id'=>['IN',$userIdArr]])
                            ->getField('exception_id',true);

            foreach ($userIdArr as $key => $value)
            {
                foreach ($exceptionIdArr as $k => $v)
                {
                    if ($value === $v )
                    {
                        unset($userIdArr[$key]);
                    }
                }

            }
            unset($exceptionIdArr);
            if (! empty($userIdArr))
            {
                $dataList = array();
                foreach ($userIdArr as $k => $v)
                {
                    $dataList[] = ['exception_id'=>$v,'exception_class'=>1,'descs'=>'单一用户账户出现负数','create_time'=>time()];
                    if (count($dataList)%50 === 0)
                    {
                        M('ExceptionLog')->addAll($dataList);//预防数据过量大，所有拆分插入
                        unset($dataList);
                    }
                }
                if (! empty($dataList))
                {
                    M('ExceptionLog')->addAll($dataList);
                }
//                $returnData = [
//                    'info' => '有新的用户账户发现异常，请处理！',
//                    'status' => 1,
//                ];
//                $this->ajaxReturn($returnData);
            }

//            $returnData = [
//                'info' => '有用户账户异常未处理！',
//                'status' => 2,
//            ];
//            $this->ajaxReturn($returnData);
        }
        else
        {
//            $returnData = [
//                'info' => '未发现用户账户异常！',
//                'status' => 3,
//            ];
//            $this->ajaxReturn($returnData);
        }


    }
    /**
     * 积分兑换异常
     * @user liangzk <liangzk@qc.com>
     * @DateTime 2016-09-07 11:48
     *  @version v2.1
     */
    public function getExceptionPoint($exception_class = 0)
    {
        $excetionConf = M('config')->where(['sign'=>'alert'])->find();//异常监控配置
        $excetionConf = json_decode($excetionConf['config'],true);

        if ($exception_class === 2)//单一账户10分钟内兑换100万及以上的积分
        {
            $executeTime = S('getExceptionPointExchange:executeTime');//获取要查询数据时大于的时间
            $executeTime = empty($executeTime) ? time() - 1800 : $executeTime ;
            $section_time = $excetionConf[2]['section_time'] * 60;//异常监控时间区间
            
            $resultArr = M()->query('select user_id,FLOOR(log_time / '.$section_time.' ) * '.$section_time.' as exception_time from qc_point_log
                                  WHERE log_time > '.$executeTime.' AND log_type = 6  GROUP BY exception_time,user_id HAVING SUM(change_num) >= '.$excetionConf[2]['standard']);
            $descs = '单一账户'.$excetionConf[2]['section_time'].'分钟内兑换'.$excetionConf[2]['standard'].'及以上的积分';
            
            //缓存最新的时间（目的就是防止对监控过的数据重新操作）----FLOOR(create_time / 600) * 600
            S('getExceptionPointExchange:executeTime',floor(time()/$section_time)*$section_time);
        }
        elseif ($exception_class === 3)//单一账户10分钟内获得1万及以上的积分
        {
            $executeTime = S('getExceptionPointGain:executeTime');//获取要查询数据时大于的时间
            $executeTime = empty($executeTime) ? time() - 1800 : $executeTime ;
            $section_time = $excetionConf[3]['section_time'] * 60;//异常监控时间区间
            $resultArr = M()->query('select user_id,FLOOR(log_time /  '.$section_time.' ) * '.$section_time.' as exception_time from qc_point_log
                                  WHERE  log_time > '.$executeTime.'  AND log_type NOT IN (2,6,19)
                                  GROUP BY exception_time,user_id HAVING SUM(change_num) >= '.$excetionConf[3]['standard']);
            $descs = '单一账户'.$excetionConf[3]['section_time'].'分钟内获得'.$excetionConf[3]['standard'].'及以上的积分';
            
            //缓存最新的时间（目的就是防止对监控过的数据重新操作）----FLOOR(create_time / 600) * 600
            S('getExceptionPointGain:executeTime',floor(time()/$section_time)*$section_time);
        }
        else
        {
            $this->ajaxReturn(['info' => '参数出错！', 'status' => -1,]);
        }


        $userIdArr = get_arr_column($resultArr,'user_id');//获取user_id的列
        if (! empty($userIdArr))
        {
            $exceptionArr = M('ExceptionLog')
                            ->where(['exception_class'=>$exception_class,'status'=>0,'exception_id'=>['IN',$userIdArr]])
                            ->field('exception_id,exception_time')
                            ->select();
            foreach ($resultArr as $key => $value)
            {
                foreach ($exceptionArr as $k => $v)
                {
                    if ($value['user_id'] === $v['exception_id'] && $value['exception_time'] === $v['exception_time'])
                    {
                        unset($resultArr[$key]);
                    }
                }

            }
            unset($exceptionArr);

            if (! empty($resultArr))
            {
                $dataList = array();
                foreach ($resultArr as $k => $v)
                {
                    $dataList[] = [
                        'exception_id'=>$v['user_id'],
                        'exception_time'=>$v['exception_time'],
                        'exception_class'=>$exception_class,
                        'section_time'=>$excetionConf[$exception_class]['section_time'],
                        'standard'=>$excetionConf[$exception_class]['standard'],
                        'descs'=>$descs,
                        'create_time'=>time()
                    ];
                    if (count($dataList)%50 === 0)
                    {
                        M('ExceptionLog')->addAll($dataList);//预防数据过量大，所有拆分插入
                        unset($dataList);
                    }
                }
                if (! empty($dataList))
                {
                    M('ExceptionLog')->addAll($dataList);
                }
//                if ($exception_class === 2)//单一账户10分钟内兑换100万及以上的积分
//                {
//                    $returnData['info'] = '发现新的积分兑换异常，请处理！';
//                }
//                elseif ($exception_class === 3)//单一账户10分钟内获得1万及以上的积分
//                {
//                    $returnData['info'] = '发现新的积分获取异常，请处理！';
//                }
//                $returnData['status'] = 1;
//
//                $this->ajaxReturn($returnData);
            }

//            if ($exception_class === 2)//单一账户10分钟内兑换100万及以上的积分
//            {
//                $returnData['info'] = '有积分兑换异常未处理！';
//            }
//            elseif ($exception_class === 3)//单一账户10分钟内获得1万及以上的积分
//            {
//                $returnData['info'] = '有积分获取异常未处理！';
//            }
//            $returnData['status'] = 2;
//
//            $this->ajaxReturn($returnData);
        }
        else
        {
//            if ($exception_class === 2)//单一账户10分钟内兑换100万及以上的积分
//            {
//                $returnData['info'] = '未发现积分兑换异常！';
//            }
//            elseif ($exception_class === 3)//单一账户10分钟内获得1万及以上的积分
//            {
//                $returnData['info'] = '未发现积分获取异常未处理！';
//            }
//            $returnData['status'] = 3;
//
//            $this->ajaxReturn($returnData);
        }
    }
    /**
     * 新闻资讯评论异常----单一账户10分钟内发布20条评论（非机器人用户）
     * @user liangzk <liangzk@qc.com>
     * @DateTime 2016-09-07 17:00
     *  @version v2.1
     */
    public function getExceptionComment()
    {
        $excetionConf = M('config')->where(['sign'=>'alert'])->find();//异常监控配置
        $excetionConf = json_decode($excetionConf['config'],true);
        
        $executeTime = S('getExceptionComment:executeTime');//获取要查询数据时大于的时间
        $executeTime = empty($executeTime) ? time() - 1800 : $executeTime ;
        $section_time = $excetionConf[5]['section_time'] * 60;//异常监控时间区间
        
        $resultArr = M()->query('select user_id,FLOOR(create_time / '.$section_time.' ) * '.$section_time.'  as exception_time from qc_comment c'
                                .' WHERE create_time > '.$executeTime.' AND EXISTS (select 1 FROM qc_front_user f '
                                        .' WHERE f.id > 0 AND c.user_id = f.id AND f.is_robot = 0 AND f.status = 1)'
                                .' GROUP BY exception_time,user_id HAVING COUNT(id) >= '.$excetionConf[5]['standard']);

        //缓存最新的时间（目的就是防止对监控过的数据重新操作）----FLOOR(create_time / 600) * 600
        S('getExceptionComment:executeTime',floor(time()/$section_time)*$section_time);
        
        $userIdArr = get_arr_column($resultArr,'user_id');//获取user_id的列

        if (! empty($userIdArr))
        {
            $exceptionArr = M('ExceptionLog')
                        ->where(['exception_class'=>5,'status'=>0,'exception_id'=>['IN',$userIdArr]])
                        ->field('exception_id,exception_time')
                        ->select();
            foreach ($resultArr as $key => $value)
            {
                foreach ($exceptionArr as $k => $v)
                {
                    if ($value['user_id'] === $v['exception_id'] && $value['exception_time'] === $v['exception_time'])
                    {
                        unset($resultArr[$key]);
                    }
                }

            }
            unset($exceptionArr);
            if (! empty($resultArr))
            {
                $dataList = array();
                foreach ($resultArr as $k => $v)
                {
                    $dataList[] = [
                        'exception_id'=>$v['user_id'],
                        'exception_time'=>$v['exception_time'],
                        'exception_class'=>5,
                        'section_time'=>$excetionConf[5]['section_time'],
                        'standard'=>$excetionConf[5]['standard'],
                        'descs'=>'单一账户'.$excetionConf[5]['section_time'].'分钟内发布'.$excetionConf[5]['standard'].'条评论（非机器人用户）',
                        'create_time'=>time()
                    ];
                    if (count($dataList)%50 === 0)
                    {
                        M('ExceptionLog')->addAll($dataList);//预防数据过量大，所有拆分插入
                        unset($dataList);
                    }
                }
                if (! empty($dataList))
                {
                    M('ExceptionLog')->addAll($dataList);
                }
//                $returnData = [
//                    'info' => '发现新的新闻资讯评论异常，请处理！',
//                    'status' => 1,
//                ];
//                $this->ajaxReturn($returnData);
            }

//            $returnData = [
//                'info' => '有新闻资讯评论异常未处理！',
//                'status' => 2,
//            ];
//            $this->ajaxReturn($returnData);
        }
//        else
//        {
//            $returnData = [
//                'info' => '未发现新闻资讯评论异常！',
//                'status' => 3,
//            ];
//            $this->ajaxReturn($returnData);
//        }
    }
    /**
     * 发帖异常---
     * @user liangzk <liangzk@qc.com>
     * @DateTime 2016-09-07 17:35
     *  @version v2.1
     */
    public function getExceptionPost($exception_class = 0)
    {
        $excetionConf = M('config')->where(['sign'=>'alert'])->find();//异常监控配置
        $excetionConf = json_decode($excetionConf['config'],true);
        
        if ($exception_class === 6)//单一账户10分钟内发布60条帖子（非机器人用户）
        {
            $executeTime = S('getExceptionPostMessage:executeTime');//获取要查询数据时大于的时间
            $executeTime = empty($executeTime) ? time() - 1800 : $executeTime ;
            $section_time = $excetionConf[6]['section_time'] * 60;//异常监控时间区间
            
            $resultArr = M()->query('SELECT user_id, FLOOR(create_time / '.$section_time.' ) * '.$section_time.' as exception_time FROM qc_community_posts c'
                                .' WHERE create_time > '.$executeTime.' AND EXISTS (select 1 FROM qc_front_user f '
                                    .' WHERE f.id > 0 AND c.user_id = f.id AND f.is_robot = 0 AND f.status = 1)'
                                .' GROUP BY exception_time,user_id HAVING COUNT(id) >= '.$excetionConf[6]['standard']);
            $descs = '单一账户'.$excetionConf[6]['section_time'].'分钟内发布'.$excetionConf[6]['standard'].'条帖子（非机器人用户）';
            
            //缓存最新的时间（目的就是防止对监控过的数据重新操作）----FLOOR(create_time / 600) * 600
            S('getExceptionPostMessage:executeTime',floor(time()/$section_time)*$section_time);
        }
        elseif ($exception_class === 7)//单一账户10分钟内60条回帖（非机器人用户）
        {
            $executeTime = S('getExceptionPostReturn:executeTime');//获取要查询数据时大于的时间
            $executeTime = empty($executeTime) ? time() - 1800 : $executeTime ;
            $section_time = $excetionConf[7]['section_time'] * 60;//异常监控时间区间
            
            $resultArr = M()->query('SELECT user_id, FLOOR(create_time / '.$section_time.' ) * '.$section_time.' as exception_time FROM qc_community_comment c'
                                .' WHERE create_time > '.$executeTime.' AND EXISTS (select 1 FROM qc_front_user f '
                                    .' WHERE f.id > 0 AND c.user_id = f.id AND f.is_robot = 0 AND f.status = 1)'
                                .' GROUP BY exception_time,user_id HAVING COUNT(id) >= '.$excetionConf[7]['standard']);
            $descs = '单一账户'.$excetionConf[7]['section_time'].'分钟内回帖'.$excetionConf[7]['standard'].'条（非机器人用户）';
            //缓存最新的时间（目的就是防止对监控过的数据重新操作）----FLOOR(create_time / 600) * 600
            S('getExceptionPostReturn:executeTime',floor(time()/$section_time)*$section_time);
        }
        else
        {
            $this->ajaxReturn(['info' => '参数出错！', 'status' => -1,]);
        }

        $userIdArr = get_arr_column($resultArr,'user_id');
        if (! empty($userIdArr))
        {
            $exceptionArr = M('ExceptionLog')
                ->where(['exception_class'=>$exception_class,'status'=>0,'exception_id'=>['IN',$userIdArr]])
                ->field('exception_id,exception_time')->select();
            foreach ($resultArr as $key => $value)
            {
                foreach ($exceptionArr as $k => $v)
                {
                    if ($value['user_id'] === $v['exception_id'] && $value['exception_time'] === $v['exception_time'])
                    {
                        unset($resultArr[$key]);
                    }
                }

            }
            unset($exceptionArr);

            if (! empty($resultArr))
            {
                $dataList = array();
                foreach ($resultArr as $k => $v)
                {
                    $dataList[] = [
                        'exception_id'=>$v['user_id'],
                        'exception_time'=>$v['exception_time'],
                        'exception_class'=>$exception_class,
                        'section_time'=>$excetionConf[$exception_class]['section_time'],
                        'standard'=>$excetionConf[$exception_class]['standard'],
                        'descs'=>$descs,
                        'create_time'=>NOW_TIME
                    ];
                    if (count($dataList)%50 === 0)
                    {
                        M('ExceptionLog')->addAll($dataList);//预防数据过量大，所有拆分插入
                        unset($dataList);
                    }
                }
                if (! empty($dataList))
                {
                    M('ExceptionLog')->addAll($dataList);
                }
//                if ($exception_class === 6)//单一账户10分钟内发布60条帖子（非机器人用户）
//                {
//                    $returnData['info'] = '发现新的发帖异常，请处理！';
//                }
//                elseif ($exception_class === 7)//单一账户10分钟内60条回帖（非机器人用户）
//                {
//                    $returnData['info'] = '发现新的回帖异常，请处理！';
//                }
//                $returnData['status'] = 1;
//
//                $this->ajaxReturn($returnData);
            }

//            if ($exception_class === 6)//单一账户10分钟内发布60条帖子（非机器人用户）
//            {
//                $returnData['info'] = '有发帖异常未处理！';
//            }
//            elseif ($exception_class === 7)//单一账户10分钟内60条回帖（非机器人用户）
//            {
//                $returnData['info'] = '有回帖异常未处理！';
//            }
//            $returnData['status'] = 2;
//
//            $this->ajaxReturn($returnData);
        }
//        else
//        {
//            if ($exception_class === 6)//单一账户10分钟内发布60条帖子（非机器人用户）
//            {
//                $returnData['info'] = '未发现发帖异常！';
//            }
//            elseif ($exception_class === 7)//单一账户10分钟内60条回帖（非机器人用户）
//            {
//                $returnData['info'] = '未发现回帖异常！';
//            }
//            $returnData['status'] = 3;
//
//            $this->ajaxReturn($returnData);
//        }
    }
}
?>