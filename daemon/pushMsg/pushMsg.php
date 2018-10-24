<?php
//定时推送消息

require_once(dirname(__FILE__)."/../lib/ConnRedis.php");
require_once(dirname(__FILE__)."/../functions/functions.php");
require_once(dirname(__FILE__)."/../../ThinkPHP/Library/Vendor/Easemob/Easemob.class.php");

set_time_limit(0);
$emPre = 'em_';

$easemobConfig = [
    'client_id'     => 'YXA6X7CPEL6REeWnW19-ianXRA',
    'client_secret' => 'YXA6QpAdj87hhm5gL4hjhkpGIiEqwPI',
    'org_name'      => 'gdquancai',
    'app_name'      => 'qqtyw'
];

$easemob = new Easemob($easemobConfig);
$redis = (new ConnRedis())->handler;

while (1)
{
    $msgid = $redis->rpop($emPre.'push_list');

    if ($msgid)
    {
        $msg = $redis->hmget($emPre.'msg:'.$msgid,['user','content']);

        if ($msg['user'] && $msg['content'])
        {
            $users = json_decode($msg['user'],true);

            // foreach ($users as $k => $v)
            // {
            //     if ($easemob->isOnline($v)['data'][$v] != 'offline') //在线的环信用户不推送
            //         unset($users[$k]);
            // }

            $users = array_chunk($users, 20); //分割20个用户为一批来群发
            $sendTimes = 0;

            foreach ($users as $v)
            {
                $easemob->sendText($from="admin",$target_type='users',$target=$v,$msg['content'],['em_apns_ext'=>['em_push_title'=>$msg['content']]]);
                $sendTimes++;

                if ($sendTimes >= 25 && $sendTimes % 25 == 0) //每推送25次休息一秒
                    sleep(1);

                $redis->incr($emPre.'sendTimes');
            }

            $redis->del($emPre.'msg:'.$msgid); //删除已经发送的消息
        }
    }
    else
    {
        sleep(3);
    }
}
 ?>