<?php
/**
 * 前台公共库文件
 * 主要定义前台公共函数库
 */

/**
 * 是否登录,返回id,否则返回false
 * @author dengwj
 */
function is_login(){
    $user = session('user_auth');
    if(empty($user)){
        return false;
    }else{
        return $user['id'];
    }
}

//模板减法
function template_minus($a, $b) {
    return(intval($a) - intval($b));
}

//计算比赛进行的时间
function MshowGameTime($halfTime,$status)
{
    $goMins = floor((time() - strtotime($halfTime)) / 60);

    switch ($status)
    {
        case '1':
            if ($goMins > 45)  $goMins = "45+";
            if ($goMins < 1)   $goMins = "1";
        break;
        case '3':
            $goMins += 46;
            if ($goMins > 90)  $goMins = "90+";
            if ($goMins < 1)   $goMins = "46";
        break;
    }

    return $goMins;
}
/*
 * @User liangzk <liangzk@qc.com>
 * @DataTime 2016-08-26
 * @version v2.1
 * 用户名、邮箱、手机账号中间字符串以*隐藏
 *
 */
function hideStar($str)
{
    if (strpos($str, '@'))
    {
        $email_array = explode("@", $str);
        $prevfix = (strlen($email_array[0]) < 4) ? "" : substr($str, 0, 3); //邮箱前缀
        $count = 0;
        $str = preg_replace('/([\d\w+_-]{0,100})@/', '***@', $str, -1, $count);
        $rs = $prevfix . $str;
    }
    else
    {
        $pattern = '/(1[3458]{1}[0-9])[0-9]{4}([0-9]{4})/i';
        if (preg_match($pattern, $str))
        {
            $rs = preg_replace($pattern, '$1****$2', $str); // substr_replace($name,'****',3,4);
        }
        else
        {
            $rs = substr($str, 0, 3) . "***" ;//$rs = substr($str, 0, 3) . "***" . substr($str, -1);
        }
    }
    return $rs;
}

/*
 * 计算让球盘路结果
 * @param  score  $score 比分（0-1）
 * @param  int    $exp   让球盘口
 * @return int    输赢结果
 * @author huangmg 2016-12-28
 */
function getExpWinFb($score = '',$exp = '')
{
    if($score == '' || $score == null || $exp == '' || $exp == null || strpos($score ,'-') ===false) return '';

    $exp = (string) $exp;
    $res = '';
    $scores = explode('-',$score);
    if(strpos($exp,'-') !== false)
    {
        if(($scores[0]-$exp) > $scores[1])
        {
            $res = -1;
        }
        else if(($scores[0]-$exp) < $scores[1])
        {
            $res = 1;
        }
        else
        {
            $res = 0;
        }
    }
    else
    {
        if(($scores[0]-$exp) > $scores[1])
        {
            $res = 1;
        }
        else if(($scores[0]-$exp) < $scores[1])
        {
            $res = -1;
        }
        else
        {
            $res = 0;
        }
    }
    return (string)$res;
}

/*
 * 计算大小结果
 * @param  score  $score 比分（0-1）
 * @param  int    $exp   大小盘口
 * @return int    输赢结果
 * @author huangmg 2016-12-28
 */
function getBallWinFb($score = '',$ball = '')
{
    if($score == '' || $score == null || $ball == '' || $ball == null || strpos($score ,'-') ===false) return '';

    $ball = changeExpStrToNum($ball);

    $res = '';
    $scores = explode('-',$score);
    if(($scores[0]+$scores[1]) > $ball)
    {
        $res = 1;
    }
    else if(($scores[0]+$scores[1]) < $ball)
    {
        $res = -1;
    }
    else
    {
        $res = 0;
    }
    return (string)$res;
}

/*
 * 计算半/全结果
 * @param  string  $score 比分（0-1）
 * @return int    1主，0平，-1客
 * @author huangmg 2017-01-20
 */
function getScoreWinFb($score = '',$is_home=1)
{
    if(empty($score)) return false;

    $temp = explode('-',$score);
    $res = '';
    if($temp[0] > $temp[1])
        $res = $is_home == 1 ? 1 : -1;
    else if($temp[0] == $temp[1])
        $res = 0;
    else
        $res = $is_home == 1 ? -1 : 1;
    return (string)$res;
}

//赔率格式转换 0.25=>0/0.5
function changeExpMat($str)
{
	if (stripos($str,'.25') === false && stripos($str,'.75') === false )
	{
		return $str == '0' ? 0 : number_format((float)$str,2);
	}
	if($str == '' || $str == null) return '';
	if(empty($str) || $str == '-0' || $str == '0') return '0';
	$score = C('score_sprit');
	$res = '';
	if(strpos($str,"-") !== false)
	{
		$temp = str_replace("-","",$str);
		if(isset($score[$temp])) $res = '-'.$score[$temp];
	}
	else
	{
		if(isset($score[$str])) $res = $score[$str];
	}
	if(!empty($res))
		return (string)$res;
	else
		return (string)$str;
}

//返回比分页设置cookie数值
function checkFbCookie($serial)
{
    $fbCookie = cookie('fbCookie');
    //默认为1
    if(!$fbCookie) return $serial == 10 ? 0 : 1;
    //返回对应数值
    return explode('^', $fbCookie)[$serial];
}

/*
 * 截取时间
 * @param  string  $content  文本
 * @return string
 * @author huangmg 2017-02-17
 */
function get_game_time($content)
{
    $game_time = cutstr($content, "TI=", ";");
    if(!empty($game_time))
        return $game_time;
    else
        return "";
}

/*
 * 赛事是否结束
 * @param  string  $game_id  动画ID
 * @param  string  $content  文本
 * @return string
 * @author huangmg 2017-02-17
 */
function is_game_end($game_id, $content)
{
    if(false !== strpos($content, "F|IN;EM=1;TO=".$game_id.";|") && false !== strpos($content, "EMPTY"))
    {
        return true;
    }

    $arr = explode($game_id, $content);
    if(isset($arr[1]))
    {
        $content = $arr[1];
        if(2 == substr_count($content, "At Full-Time"))
        {
            return true;
        }
    }
    return false;
}
/**
 * @User: lianzk <liangzk@qc.com>
 * @date 2016-10-20 @time 14:29
 * 获取数组中的指定列
 * @param type $arr 数组
 * @param type $key_name  列名 字符串/数组
 * @return type  返回那一列的数组
 */
function get_arr_column($arr, $key_name)
{
    $arr_column = array();
    if (is_array($key_name))
    {
        foreach($arr as $key => $val)
        {
            foreach ($key_name as $k => $v)
            {
                $arr_column[$key][$v] = $val[$v];
            }

        }
    }
    else
    {
        foreach($arr as $key => $val)
        {
            $arr_column[] = $val[$key_name];
        }
    }

    return $arr_column;
}

/**
 * @ 获取赔率  滚球->即时->初盘
 * @param $vv array 赔率数据
 * @param $type string  类型  fsw 全场   half 半场
 * @return array
 * */
function do_odds($vv, $type)
{
    $whole = $type == 'fsw' ? $vv[0] : $vv[3];  //全场
    if ($whole[6] != '' || $whole[7] != '' || $whole[8] != '') {
        //全场滚球
        if ($whole[6] == 100 || $whole[7] == 100 || $whole[7] == 100) {
            $odds[$type . '_exp_home'] = '';
            $odds[$type . '_exp'] = '封';
            $odds[$type . '_exp_away'] = '';
        } else {
            $odds[$type . '_exp_home'] = $whole[6];
            $odds[$type . '_exp'] = $whole[7];
            $odds[$type . '_exp_away'] = $whole[8];
        }
    } elseif ($whole[3] != '' || $whole[4] != '' || $whole[5] != '') {
        //全场即时
        if ($whole[3] == 100 || $whole[4] == 100 || $whole[5] == 100) {
            $odds[$type . '_exp_home'] = '';
            $odds[$type . '_exp'] = '封';
            $odds[$type . '_exp_away'] = '';
        } else {
            $odds[$type . '_exp_home'] = $whole[3];
            $odds[$type . '_exp'] = $whole[4];
            $odds[$type . '_exp_away'] = $whole[5];
        }
    } elseif ($whole[0] != '' || $whole[1] != '' || $whole[2] != '') {
        //初盘
        if ($whole[0] == 100 || $whole[1] == 100 || $whole[2] == 100) {
            $odds[$type . '_exp_home'] = '';
            $odds[$type . '_exp'] = '封';
            $odds[$type . '_exp_away'] = '';
        } else {
            $odds[$type . '_exp_home'] = $whole[0];
            $odds[$type . '_exp'] = $whole[1];
            $odds[$type . '_exp_away'] = $whole[2];
        }
    }

    $size = $type == 'fsw' ? $vv[2] : $vv[5];  //大小
    if ($size[6] != '' || $size[7] != '' || $size[8] != '') {
        //大小滚球
        if ($size[6] == 100 || $size[7] == 100 || $size[8] == 100) {
            $odds[$type . '_ball_home'] = '';
            $odds[$type . '_ball'] = '封';
            $odds[$type . '_ball_away'] = '';
        } else {
            $odds[$type . '_ball_home'] = $size[6];
            $odds[$type . '_ball'] = $size[7];
            $odds[$type . '_ball_away'] = $size[8];
        }
    } elseif ($size[3] != '' || $size[4] != '' || $size[5] != '') {
        //大小即时
        if ($size[3] == 100 || $size[4] == 100 || $size[5] == 100) {
            $odds[$type . '_ball_home'] = '';
            $odds[$type . '_ball'] = '封';
            $odds[$type . '_ball_away'] = '';
        } else {
            $odds[$type . '_ball_home'] = $size[3];
            $odds[$type . '_ball'] = $size[4];
            $odds[$type . '_ball_away'] = $size[5];
        }
    } elseif ($size[0] != '' || $size[1] != '' || $size[2] != '') {
        //大小初盘
        if ($size[0] == 100 || $size[1] == 100 || $size[2] == 100) {
            $odds[$type . '_ball_home'] = '';
            $odds[$type . '_ball'] = '封';
            $odds[$type . '_ball_away'] = '';
        } else {
            $odds[$type . '_ball_home'] = $size[0];
            $odds[$type . '_ball'] = $size[1];
            $odds[$type . '_ball_away'] = $size[2];
        }
    }

    $europe = $type == 'fsw' ? $vv[1] : $vv[4];  //欧赔
    if ($europe[6] != '' || $europe[7] != '' || $europe[8] != '') {
        //欧赔滚球
        if ($europe[6] == 100 || $europe[7] == 100 || $europe[8] == 100) {
            $odds[$type . '_europe_home'] = '';
            $odds[$type . '_europe'] = '封';
            $odds[$type . '_europe_away'] = '';
        } else {
            $odds[$type . '_europe_home'] = $europe[6];
            $odds[$type . '_europe'] = $europe[7];
            $odds[$type . '_europe_away'] = $europe[8];
        }
    } elseif ($europe[3] != '' || $europe[4] != '' || $europe[5] != '') {
        //欧赔即时
        if ($europe[3] == 100 || $europe[4] == 100 || $europe[5] == 100) {
            $odds[$type . '_europe_home'] = '';
            $odds[$type . '_europe'] = '封';
            $odds[$type . '_europe_away'] = '';
        } else {
            $odds[$type . '_europe_home'] = $europe[3];
            $odds[$type . '_europe'] = $europe[4];
            $odds[$type . '_europe_away'] = $europe[5];
        }
    } elseif ($europe[0] != '' || $europe[1] != '' || $europe[2] != '') {
        //欧赔初盘
        if ($europe[0] == 100 || $europe[1] == 100 || $europe[2] == 100) {
            $odds[$type . '_europe_home'] = '';
            $odds[$type . '_europe'] = '封';
            $odds[$type . '_europe_away'] = '';
        } else {
            $odds[$type . '_europe_home'] = $europe[0];
            $odds[$type . '_europe'] = $europe[1];
            $odds[$type . '_europe_away'] = $europe[2];
        }
    }
    return $odds;
}

/**
 * 设置mqtt账号密码
 * @return mixed
 */
function setMqttUser(){
    $userId = is_login();
    $redis = connRedis();

    if ($userId) {
        $mqtt['userName'] = md5('mqtt_longin_' . $userId);
        $mqtt['password'] = md5('mqtt_longin_' . $userId . 'qqty_mqtt' . NOW_TIME);
        $key = 'mqtt_user:' . $mqtt['userName'];
        $redis->hset($key, 'password', $mqtt['password']);
        $redis->expire($key, 3600 * 8);
    }else{
        $uid = md5(get_client_ip() . microtime(true));
        $mqtt['userName'] = md5('mqtt_nolongin_' . $uid);
        $mqtt['password'] = md5('mqtt_nolongin_' . $uid . 'qqty_mqtt' . NOW_TIME);

        $key = 'mqtt_user:' . $mqtt['userName'];
        $redis->hset($key, 'password', $mqtt['password']);
        $redis->expire($key, 3600 * 8);
    }

    return $mqtt;
}