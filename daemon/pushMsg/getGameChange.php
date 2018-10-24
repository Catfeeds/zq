<?php
//定时获取变化的赛程

require_once(dirname(__FILE__)."/../lib/ConnRedis.php");
require_once(dirname(__FILE__)."/../lib/medoo.php");

set_time_limit(0);
$emPre = 'em_';

$dbConfig = [
    'database_type' => 'mysql',
    'database_name' => 'cms',
    'server'        => '192.168.1.244',
    'username'      => 'dev',
    'password'      => 'gz1710',
    'charset'       => 'utf8',
    'port'          => 3306,
    'prefix'        => 'qc_',
];


$redis = (new ConnRedis())->handler;

while (1)
{
    $resStr = file_get_contents('http://www.qqty.com/Home/Pcdata/changeTwo');
    $resStr = substr(trim($resStr),1,-1);
    $data   = json_decode($resStr,true);

    if ($data['status'] != 1)
    {
        sleep(3);
        continue;
    }

    $changeList = $data['data'];

    $msgCount = 0; //生成了几条消息
    $gameCount = 0; //更新了几条赛程数据

    foreach ($changeList as $v)
    {
        //是否有人关注了这个赛程
        $users = $redis->smembers($emPre.'game_follow:'.$v[0]);

        if ($users)
        {
            // $change = $redis->hmget($emPre.'game_change:'.$v[0],['state','home_score','away_score','home_red','away_red','home_yellow','away_yellow']);
            $change = $redis->hmget($emPre.'game_change:'.$v[0],['state','home_score','away_score','home_red','away_red']);
            $notic = ''; //推送的消息

            //对比原来的数据看是否有变化
            if ($change['state'] != $v[1] && $v[1] == '-1')
                $notic .= ' 完场 比分: '.$v[2].'-'.$v[3];
            else if ($change['home_score'] != $v[2] || $change['away_score'] != $v[3])
                $notic .= ' 比分: '.$v[2].'-'.$v[3];

            // if ($change['away_score'] != $v[3])
            //     $notic .= ' 客队得分'.$v[3];

            if ($change['home_red'] != $v[6] || $change['away_red'] != $v[7])
                $notic .= ' 红牌: '.$v[6].'-'.$v[7];

            // if ($change['away_red'] != $v[7])
            //     $notic .= ' 客队红牌'.$v[7];

            // if ($change['home_yellow'] != $v[12])
            //     $notic .= ' 主队黄牌'.$v[12];

            // if ($change['away_yellow'] != $v[13])
            //     $notic .= ' 客队黄牌'.$v[13];

            if ($notic != '')
            {
                $db = new medoo($dbConfig);
                $game = $db->get('game_fbinfo',['union_name','home_team_name','away_team_name'],['game_id'=>$v[0]]);
                $union_name     = explode(',',$game['union_name'])[0];
                $home_team_name = explode(',',$game['home_team_name'])[0];
                $away_team_name = explode(',',$game['away_team_name'])[0];

                if ($union_name && $home_team_name && $away_team_name && $v[2] !== false && $v[3] !== false)
                {
                    $notic = $union_name.' '.$home_team_name.' VS '. $away_team_name .' '. $notic;

                    foreach ($users as $kk => $vv)
                    {
                        if ($redis->hget($emPre.'user:'.$vv,'is_push') != 1) //判断用户是否设置了推送
                            unset($users[$kk]);
                    }

                    //生成、保存推送消息到队列
                    $msgid = $redis->incr($emPre.'msgid');
                    $redis->hmset($emPre.'msg:'.$msgid,['user'=>json_encode($users),'content'=>$notic]); //生成消息
                    $redis->lpush($emPre.'push_list',$msgid); //保存消息id到队列

                    $msgCount++;
                }
            }
        }

        //更新赛程变化
        $result = $redis->hmset($emPre.'game_change:'.$v[0],[
            'state'       => $v[1],
            'home_score'  => $v[2],
            'away_score'  => $v[3],
            'home_red'    => $v[6],
            'away_red'    => $v[7],
            // 'home_yellow' => $v[12],
            // 'away_yellow' => $v[13]
        ]);

        //设置赛程变化信息的有效期
        $redis->expire($emPre.'game_change:'.$v[0],3600*24);
        $gameCount++;
    }

    // echo ' update gameCount：'.$gameCount.' create msgCount：'.$msgCount;
    sleep(3);
}
 ?>