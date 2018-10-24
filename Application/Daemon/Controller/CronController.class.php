<?php
/**
 * 定时任务
 *
 * @author Hmg <huamgmg@qc.mail>
 *
 * @since  2017-9-22
 */
use Think\Controller;
class CronController extends Controller
{
    /**
     * Index页显示
     */
    public function pubFlashFlag()
    {
        //列表过滤器，生成查询Map对象
        set_time_limit(0);
        //订阅
        ini_set('default_socket_timeout', -1);  //不超时
        $redis = new Redis();
        $redis->connect('192.168.1.223', 6379);
        //$redis->connect('127.0.0.1', 6379);
        $result = $redis->subscribe(array('qqtyNotify'), $this->callback);
    }

    public function callback($instance,$channelName,$message)
    {
        echo $message.'/n'; echo $message.'/n'; echo $message.'/n'; echo $message.'/n'; echo $message.'/n'; echo $message.'/n'; echo $message.'/n'; echo $message.'/n'; echo $message.'/n'; echo $message.'/n'; echo $message.'/n'; echo $message.'/n'; echo $message.'/n'; echo $message.'/n'; echo $message.'/n'; echo $message.'/n'; echo $message.'/n'; echo $message.'/n'; echo $message.'/n'; echo $message.'/n'; echo $message.'/n'; echo $message.'/n'; echo $message.'/n'; echo $message.'/n'; echo $message.'/n'; echo $message.'/n'; echo $message.'/n'; echo $message.'/n'; echo $message.'/n'; echo $message.'/n'; echo $message.'/n'; echo $message.'/n'; echo $message.'/n'; echo $message.'/n'; echo $message.'/n';
        $subarr = explode(':',$message);
        echo "aaaaaaaaaaaaaaaaaaa";exit;
        switch($subarr[0])
        {
            case 'fbChg':
                break;
            default:
                break;
        }
    }

    public function fbChange($arr)
    {


    }

    /**
     * 足球动画状态推送
     */
    public function pubfbFlash()
    {
        set_time_limit(0);
        $configTime = C('gameTime')['fb'];
        while(1)
        {
            if(strtotime($configTime['sTime']) < time())
            {
                $startTime = strtotime($configTime['eTime']);
                $endTime = strtotime($configTime['sTime'])+3600*24;
            }
            else
            {
                $startTime =strtotime($configTime['eTime'])-3600*24;
                $endTime = strtotime($configTime['sTime']);
            }
            $map['a.gtime'] = array(array('gt',$startTime),array('lt',$endTime));
            $map['a.game_state'] = 1;
            //$map['a.game_id'] = array('in','1464526,1462934,1465398');

            $res = M('GameFbinfo')->table('qc_game_fbinfo a')->field('a.id,a.game_id,a.game_state,a.gtime,a.is_video,b.flash_id,b.md_id')->join('LEFT JOIN qc_fb_linkbet b ON a.game_id=b.game_id')->where($map)->order('game_state desc,gtime,a.id')->select();

            if(!empty($res))
            {
                $pubData = [];
                foreach($res as $k=>$v)
                {
                    S('cache_fb_flash_'.$v['game_id'],0);
                    if(S('cache_fb_flash_'.$v['game_id']))
                    {
                        $flag = S('cache_fb_flash_'.$v['game_id']);
                        if(!empty($v['md_id']) )
                        {
                            if($flag == 'afalse')
                            {
                                $pubData[$v['game_id']] = [
                                    0 => $v['game_id'],
                                    1 => $v['is_video'],
                                    2 => '1',
                                ];
                                S('cache_fb_flash_'.$v['game_id'],'atrue',7200);
                            }

                        }
                        else
                        {
                            if($flag == 'atrue')
                            {
                                $pubData[$v['game_id']] =  [
                                    0 => $v['game_id'],
                                    1 => $v['is_video'],
                                    2 => '0',
                                ];
                                S('cache_fb_flash_'.$v['game_id'],'afalse',7200);
                            }
                        }
                    }
                    else
                    {
                        if(!empty($v['md_id']) )
                        {
                            $pubData[$v['game_id']] =  [
                                0 => $v['game_id'],
                                1 => $v['is_video'],
                                2 => '1',
                            ];
                            S('cache_fb_flash_'.$v['game_id'],'atrue',7200);
                        }
                        else
                        {
                            $pubData[$v['game_id']] = [
                                0 => $v['game_id'],
                                1 => $v['is_video'],
                                2 => '0',
                            ];
                            S('cache_fb_flash_'.$v['game_id'],'afalse',7200);
                        }
                    }
                }

                if(!empty($pubData))
                {
                    $publishStr = "qqty/".C('api')."/fb/isflash";
                    $alength = count($pubData);
                    if($alength >20)
                    {
                        $publish = [];
                        $i =1;
                        foreach($pubData as $kk => $vv)
                        {
                            $publish[$kk] = $vv;

                            if(($i)%20 ==0 || $alength == ($i))
                            {
                                $pData = [
                                    'status'  => '1',
                                    'data'    => $publish,
                                    'msg'     => '',
                                ];
                                $opt['topic'] = $publishStr;
                                $opt['clientid'] = time();
                                $opt['payload'] = $pData;
                                Mqtt($opt);
                                echo "mqtt 推送：".json_encode($opt);
                                $publish = [];
                            }
                            $i++;
                        }
                    }
                    else
                    {
                        $pData = [
                            'status'  => '1',
                            'data'    => $pubData,
                            'msg'     => '',
                        ];
                        $opt['topic'] = $publishStr;
                        $opt['clientid'] = time();
                        $opt['payload'] = $pData;
                        Mqtt($opt);
                        echo "mqtt 推送：".json_encode($opt);
                    }
                }
            }
            sleep(3);
        }

    }

}