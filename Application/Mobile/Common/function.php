<?php

/**
 * 前台公共库文件
 * 主要定义前台公共函数库
 */

/**
 * 是否登录,返回id,否则返回false
 * @author 邓伟军
 */
function is_login() {
    $user = session('user_auth');
    if (empty($user)) {
        return false;
    } else {
        return $user['id'];
    }
}

//模板减法
function template_minus($a, $b) {
    return(intval($a) - intval($b));
}
//模板除法
function template_rate($a, $b,$num=2) {
    $rsl=intval($a) / intval($b)*100;
    return(sprintf("%1\$.".$num."f",$rsl));
}

//计算比赛进行的时间
function MshowGameTime($halfTime, $status) {
    $goMins = floor((time() - strtotime($halfTime)) / 60);

    switch ($status) {
        case '1':
            if ($goMins > 45)
                $goMins = "45+";
            if ($goMins < 1)
                $goMins = "1";
            break;
        case '3':
            $goMins += 46;
            if ($goMins > 90)
                $goMins = "90+";
            if ($goMins < 1)
                $goMins = "46";
            break;
    }

    return $goMins;
}
/**
 * 检查邮箱格式
 * @param type $str
 * @return bool 
 */
function fn_is_email($str){
    return preg_match("/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/", $str);
}
function fn_is_mobile($str) {
    return preg_match("/^1[3456789]{1}\d{9}$/", $str);
}

/** 图片局部打马赛克
 * @param  String  $source 原图
 * @param  Stirng  $dest   生成的图片
 * @param  int     $deep   深度，数字越大越模糊
 * @return boolean
 */
function imageMosaics($source, $dest, $name) {

    // 判断原图是否存在
    if (!file_exists($source)) {
        return false;
    }

    // 获取原图信息
    list($owidth, $oheight, $otype) = getimagesize($source);

    // 判断区域是否超出图片
    if ($x1 > $owidth || $x1 < 0 || $x2 > $owidth || $x2 < 0 || $y1 > $oheight || $y1 < 0 || $y2 > $oheight || $y2 < 0) {
        return false;
    }

    switch ($otype) {
        case 1: $source_img = imagecreatefromgif($source);
            break;
        case 2: $source_img = imagecreatefromjpeg($source);
            break;
        case 3: $source_img = imagecreatefrompng($source);
            break;
        default:
            return false;
    }
    $fontFile = './Public/Mobile/other/msyh.ttf';
    $black = imagecolorallocate($source_img, 30, 27, 20); //设置一个颜色变量为黑色
    $num = rand(100000, 399999);
    //imagestring($source_img, 40, 525, 463, $name, $black); //水平的将字符串输出到图像中
    //imagestring($source_img, 10, 560, 478, $num, $black); //水平的将字符串输出到图像中
    //imagefttext($source_img, 10, 3, 525, 478, $black, $fontFile , $name); //将字符串输出到图像中
    //imagefttext($source_img, 10, 3, 565, 492, $black, $fontFile , $num); //将字符串输出到图像中

    imagefttext($source_img, 10, 1, 436, 467, $black, $fontFile, $name); //将字符串输出到图像中
    imagefttext($source_img, 10, 2, 520, 478, $black, $fontFile, $num);

    // 生成图片
    switch ($otype) {
        case 1: imagegif($source_img, $dest);
            break;
        case 2: imagejpeg($source_img, $dest);
            break;
        case 3: imagepng($source_img, $dest);
            break;
    }

    return is_file($dest) ? true : false;
}

/** 图片局部打马赛克
 * @param  String  $source 原图
 * @param  Stirng  $dest   生成的图片
 * @param  Stirng  $name   名字
 * @param  Stirng  $desc   说明
 * @return boolean
 */
function imageText($source, $dest, $name,$airpore,$half_score,$score,$people,$date,$time) {

    // 判断原图是否存在
    if (!file_exists($source)) {
        return false;
    }
    // 获取原图信息
    list($owidth, $oheight, $otype) = getimagesize($source);

    // 判断区域是否超出图片
    if ($x1 > $owidth || $x1 < 0 || $x2 > $owidth || $x2 < 0 || $y1 > $oheight || $y1 < 0 || $y2 > $oheight || $y2 < 0) {
        return false;
    }

    switch ($otype) {
        case 1: $source_img = imagecreatefromgif($source);
            break;
        case 2: $source_img = imagecreatefromjpeg($source);
            break;
        case 3: $source_img = imagecreatefrompng($source);
            break;
        default:
            return false;
    }
    
    include 'pinyin.php';
    $pinyin= pinyin($name);
    $airporep=pinyin($airpore);
    //$fontFile = './Public/Mobile/other/hsgw.otf';
    //华文中宋
    $fontName= './Public/Mobile/other/hwzs.ttf';
    $arial= './Public/Mobile/other/arial.ttf';
    $white = imagecolorallocate($source_img, 255, 255, 255); //设置一个颜色变量为白色
    $black = imagecolorallocate($source_img, 21, 18, 11); //设置一个颜色变量为黑色
    $red = imagecolorallocate($source_img, 198, 51, 51); //设置一个颜色变量为红色
    $textColor=imagecolorallocate($source_img, 109, 85, 67); //设置一个文字颜色
    $titleColor=imagecolorallocate($source_img, 78, 33, 0); //标题颜色
    //比分
    imagefttext($source_img, 36, 0, 317, 513, $red, $arial, $score); //将字符串输出到图像中
    //imagefttext($source_img, 36, 0, 318, 514, $red, $arial, $score); //将字符串输出到图像中
    imagefttext($source_img, 24, 0, 366, 556, $red, $arial, $half_score); //将字符串输出到图像中
    //imagefttext($source_img, 24, 0, 367, 557, $red, $arial, $half_score); //将字符串输出到图像中
    //机票
    imagefttext($source_img, 12, 2, 110, 861, $black, $fontName, $pinyin); //将字符串输出到图像中
    imagefttext($source_img, 10, 2, 110, 874, $black, $fontName, $name); //将字符串输出到图像中
    imagefttext($source_img, 12, 2, 530, 848, $black, $fontName, $pinyin); //将字符串输出到图像中
    imagefttext($source_img, 10, 2, 530, 864, $black, $fontName, $name); //将字符串输出到图像中
    
    imagefttext($source_img, 10, 2, 140, 900, $black, $fontName, $airpore); //将字符串输出到图像中
    imagefttext($source_img, 8, 2, 140, 916, $black, $arial, $airporep); //将字符串输出到图像中
    imagefttext($source_img, 8, 2, 554, 884, $black, $arial, $airporep); //将字符串输出到图像中
    imagefttext($source_img, 8, 2, 300, 852, $black, $arial, 'CZ3219 '.$date.' G'); //将字符串输出到图像中
    imagefttext($source_img, 8, 2, 536, 924, $black, $arial, 'CZ3219 '.$date.' G'); //将字符串输出到图像中
    imagefttext($source_img, 14, 2, 290, 950, $black, $arial, $time); //将字符串输出到图像中
    
    imagefttext($source_img, 30, 0, 120, 1168, $red, $arial, $people); //将字符串输出到图像中
    imagefttext($source_img, 30, 0, 121, 1169, $red, $arial, $people); //将字符串输出到图像中
//    imagefttext($source_img, 30, 0, 310, 806, $red, $fontFile, $people); //将字符串输出到图像中
//    $colorLeft=strlen($people)*30+310;
//    imagefttext($source_img, 30, 0, $colorLeft, 806, $titleColor, $fontFile, '位助威者'); //将字符串输出到图像中
//    $nameLeft=(15-strlen($name))*13+430;
//    imagefttext($source_img, 30, 0, $nameLeft-150, 1070, $textColor, $fontFile, '助威人：'); //将字符串输出到图像中
//    imagefttext($source_img, 30, 0, $nameLeft, 1070, $textColor, $fontName, $name); //将字符串输出到图像中
//    if(mb_strlen($desc,'UTF8')>15){
//        $arr[0]=mb_substr($desc,0,16,'utf-8');
//        $arr[1]=mb_substr($desc,16,16,'utf-8');
//        $left=(15-mb_strlen($arr[1],'UTF8'))*20+80;
//        imagefttext($source_img, 30, 0, 80, 960, $textColor, $fontFile, $arr[0]);
//        imagefttext($source_img, 30, 0, $left, 1010, $textColor, $fontFile, $arr[1]);
//    }else{
//        $left=(15-mb_strlen($desc,'UTF8'))*20+80;
//        imagefttext($source_img, 30, 0, $left, 1000, $textColor, $fontFile, $desc);
//    }
    // 生成图片
    switch ($otype) {
        case 1: imagegif($source_img, $dest);
            break;
        case 2: imagejpeg($source_img, $dest);
            break;
        case 3: imagepng($source_img, $dest);
            break;
    }

    return is_file($dest) ? true : false;
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