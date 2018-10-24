<?php
/**

本文件在各版本的Api接口模块通用，修改时请注意兼容

 */

//让分中文显示
function handCpSpread($score)
{
    //是否是汉字"封"
    if(preg_match('/^[\x{4E00}-\x{9FA5}]+$/u', $score) || $score == 100)
    {
        return '封';
    }

    $shou = strpos($score ,  '-') === false ? '' : '受';

    $score = str_replace('-', '', $score);
    if(strpos($score ,  '/') == false){
        $score = floatval($score);
    }
    //根据格式返回
    return $shou.C('score')["$score"];
}

/*
调用框架的S()函数再次封装，生成数据缓存文件
但不受时间控制，只有新增、编辑、删除数据才生成新的缓存文件
*/
function FC($name , $value=''){
    if(false !== strpos($name ,  '/' )){
        $temp = F_DATA_DIR.substr( $name , 0 , strrpos($name , "/"))."/";
        $name = substr( strrchr($name , '/') , 1);
    }else{
        $temp = F_DATA_DIR;
    }
   // $options['temp']     = F_DATA_DIR;
    $options['prefix']   = 'Data_'.$name.'_';
    $options['expire']   = 0;
   // var_dump($options);
    C('DATA_CACHE_SUBDIR' , false);
    C('DATA_CACHE_CHECK' , false);
    C('DATA_CACHE_TYPE' , 'File');
    if('' !== $value){
        if(is_null($value)){
           // $result = S($name , null , $expire=0, $type='File', $options);
            $result = S($name , null , $options);
           // $result = S($name , null , 300);
        }else{
           // S($name, $value , $expire=0, $type='File', $options);
            S($name , $value ,$options);
            // S($name , $value ,300);
        }
        return;
    }
    //$data =  S($name , $value='' , $expire=0 , $type='File', $options);
    $data =  S($name , $value='' ,  $options);
    return $data;
}

/**
 +----------------------------------------------------------
 * 把返回的二位数组转换成Tree
 * 该函数能设置返回的Tree索引的键名为数组内部的字段
 +----------------------------------------------------------
 * @access public
 +----------------------------------------------------------
 * @param array $list 要转换的数据集
 * @param string $pid parent标记字段
 * @param string $level level标记字段
 * @param string $field field数组索引字段，默认是主键字段
 +----------------------------------------------------------
 * @return array
 +----------------------------------------------------------
 */
function array_to_tree($list, $pk='id',$pid = 'pid',$child = '_child', $root=0 , $field='')
{
    // 创建Tree
    $tree = array();
    if(is_array($list)) {
        // 创建基于主键的数组引用
        $refer = array();
        foreach ($list as $key => $data) {
            $refer[$data[$pk]] =& $list[$key];
        }
        foreach ($list as $key => $data) {
            // 指定的数组索引字段
            $field = $field?$field:$pk;
            $f = strtolower($data[$field]);
            // 判断是否存在parent
            $parentId = $data[$pid];
            if ($root == $parentId) {
                if(isset($refer[$root])){
                    // 指定某个节点为根，且将其子级归入队列。
                    $tree[$root] =& $refer[$root];
                    $tree[$root][$child][$f] =& $list[$key];
                }else{
                    // 不指定根起点
                    $tree[$f] =& $list[$key];
                }
            }else{
                if (isset($refer[$parentId])) {
                    $parent =& $refer[$parentId];
                    $parent[$child][$f] =& $list[$key];
                }
                /*
                // 找不到父ID的数据移出队列
                else{
                        $tree[$f] =& $list[$key];
                }*/
            }
        }
    }
    return $tree;
}

 /**
 * 递归获取数组中父级别的信息
 * $data  要处理的数组   必选
 * $id    查询数组中指定键值的ID 必选
 * $field  查询数组的键值字段 可选   默认字段id
 * $parentID  递归查询父ID字段 必须
 * $menu   递归的数组 可选
 * $deep   递归的层级深度 可选
 * @return array
 */
function get_parent($data , $id , $field='id' , $parentID='pid'  ,  $deep = 0 ){
    if(!is_array($data)) return false;
    $menu=array();
    foreach($data as $key=>$val){
        if(strtolower($id) != strtolower($val[$field])) continue;
        $menu = get_parent($data , $val[$parentID] , $field , $parentID  , ++$deep );
        $menu[] = $val;
    }
    return $menu;
}

 /**
 * 模拟数据库查询一条数据，返回一条一维数组信息
 * $data  要处理的数组   必选
 * $id    查询数组中指定键值的ID 必选
 * $field 查询数组的键值字段 可选
 * @return array
 */
function get_one($data , $id , $field='id'){
    $menu = array();
    if(is_array($data)){
        foreach($data as $key=>$val){
            if(strtolower($id)==strtolower($val[$field])){
                $menu = $val;
            }
        }
    }
    return $menu;
}

 /**
 * 模拟数据库查询数据库
 * $data  要处理的数组   必选
 * $id    对应值是否存在数组中
 * $field    查询字段条件，根据该字段进行对比查询
 * 用 “ | ” 符号作为函数分割符，field|function 则会执行函数
 * 过滤查询的字段内容后在进行对比
 * @return 二维数组
 */
function get_fetch_array($data , $id , $field='id'){
    $menu = array();
    if(is_array($data)){
        foreach($data as $key=>$val){
            if(strtolower($id)==strtolower($val[$field])) $menu[] = $val;
        }
    }
    return $menu;
}

// 自动转换字符集 支持数组转换
function auto_charset($fContents, $from='gbk', $to='utf-8') {
    $from = strtoupper($from) == 'UTF8' ? 'utf-8' : $from;
    $to = strtoupper($to) == 'UTF8' ? 'utf-8' : $to;
    if (strtoupper($from) === strtoupper($to) || empty($fContents) || (is_scalar($fContents) && !is_string($fContents))) {
        //如果编码相同或者非字符串标量则不转换
        return $fContents;
    }
    if (is_string($fContents)) {
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($fContents, $to, $from);
        } elseif (function_exists('iconv')) {
            return iconv($from, $to, $fContents);
        } else {
            return $fContents;
        }
    } elseif (is_array($fContents)) {
        foreach ($fContents as $key => $val) {
            $_key = auto_charset($key, $from, $to);
            $fContents[$_key] = auto_charset($val, $from, $to);
            if ($key != $_key)
                unset($fContents[$key]);
        }
        return $fContents;
    }
    else {
        return $fContents;
    }
}

// 获取文件夹下最新修改的文件
function dir_size($dir){
    $dh = @opendir($dir);             //打开目录，返回一个目录流
    $return = array();
    $i = 0;
    while($file = @readdir($dh)){     //循环读取目录下的文件
        if($file!='.' and $file!='..'){
            $path = $dir.'/'.$file;     //设置目录，用于含有子目录的情况
            if(is_dir($path)){
            }elseif(is_file($path)){
                $filesize[] =  round((filesize($path)/1024),2);//获取文件大小
                $filename[] = $path;//获取文件名称
                $filetime[] = date("Y-m-d H:i:s",filemtime($path));//获取文件最近修改日期

                $files[] = $file;
            }
        }
    }
    if(empty($files)) return null;
    @closedir($dh);             //关闭目录流
    //array_multisort($filesize,SORT_DESC,SORT_NUMERIC, $files);//按大小排序
    //array_multisort($filename,SORT_DESC,SORT_STRING, $files);//按名字排序
    array_multisort($filetime,SORT_DESC,SORT_STRING, $files);//按时间排序
    return $files[0];               //返回文件
}

// 生成文件夹
function createDir($path)
{
    if (!file_exists($path))
    {
        createDir(dirname($path));
        mkdir($path, 0777);
    }
}

/**
 * ArraySave(保存数组文件)
 */
function ArraySave($array, $file, $arrayname = false)
{
    $data = var_export($array, TRUE);
    if (!$arrayname) {
       $data = "<?php\n return " .$data.";\n?>";
    } else {
       $data = "<?php\n " .$arrayname . "=\n" .$data . ";\n?>";
    }
    return file_put_contents($file,$data);
}

/**
 * 获取文件后缀
 * @param  string $type 文件类别
 * @return string       文件后缀
 */
function getFileExt($type)
{
    switch($type)
    {
        case 'application/x-javascript':
            $ext = '.js';
            break;
        case 'text/xml':
            $ext = '.xml';
            break;
        case 'text/html':
            $ext = '.html';
            break;
        case 'text/plain':
            $ext = '.txt';
            break;
        default:
            $ext = '.htm';
            break;
    }
    return $ext;
}

/**
 * 检测是否使用手机访问
 * @access public
 * @return bool
 */
function isMobile()
{
    if (isset($_SERVER['HTTP_VIA']) && stristr($_SERVER['HTTP_VIA'], "wap")) {
        return true;
    } elseif (isset($_SERVER['HTTP_ACCEPT']) && strpos(strtoupper($_SERVER['HTTP_ACCEPT']), "VND.WAP.WML")) {
        return true;
    } elseif (isset($_SERVER['HTTP_X_WAP_PROFILE']) || isset($_SERVER['HTTP_PROFILE'])) {
        return true;
    } elseif (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/(blackberry|configuration\/cldc|hp |hp-|htc |htc_|htc-|iemobile|kindle|midp|mmp|motorola|mobile|nokia|opera mini|opera |Googlebot-Mobile|YahooSeeker\/M1A1-R2D2|android|iphone|ipod|mobi|palm|palmos|pocket|portalmmm|ppc;|smartphone|sonyericsson|sqh|spv|symbian|treo|up.browser|up.link|vodafone|windows ce|xda |xda_)/i', $_SERVER['HTTP_USER_AGENT'])) {
        return true;
    } else {
        return false;
    }
}

//大小显示
function handCpTotal($score)
{
    # 0.5, 0.75, 1, 1.25, 1.5, 1.75, 2
    $int  = floor($score);
    $deci = $score - $int;

    if ($deci == 0.25)
    {
        $score1 = $int;
        $score2 = $int+0.5;
        return $score1 .'/'. $score2;
    }

    if ($deci == 0.75)
    {
        $score1 = $int+0.5;
        $score2 = $int+1;
        return $score1 .'/'. $score2;
    }

    return floatval($score);
}

//简繁体截取
function switchName($idx,$name)
{
    $str = explode(',', $name)[$idx];
    if($str == ''){
        //只有简体
        $str = explode(',', $name)[0];
    }
    return explode('(', $str)[0];
}

//比分截取
function switchGoal($goal, $index)
{
    return preg_split("/-/", $goal)[$index];
}


//计算比赛进行的时间
function showGameTime($halfTime,$status)
{
    if(strpos($halfTime,",") !== false){
        $time    = explode(',', $halfTime);
        $time[1] = str_pad($time[1]+1, 2, '0', STR_PAD_LEFT);
        $time[2] = str_pad($time[2], 2, '0', STR_PAD_LEFT);
        $time    = implode('', $time);
    }else{
        $time = $halfTime;
    }

    $goMins = floor((time() - strtotime($time)) / 60);

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
/**
 * 返回数组的维度
 * @param  [type] $arr [description]
 * @return [type]      [description]
 */
function arrayLevel($arr) {
    if (is_array($arr)) {
        #递归将所有值置NULL，目的1、消除虚构层如array("array(\n  ()")，2、print_r 输出轻松点，
        array_walk_recursive($arr, function(&$val){ $val = NULL; });

        $ma = array();
        #从行首匹配[空白]至第一个左括号，要使用多行开关'm'
        preg_match_all("'^\(|^\s+\('m", print_r($arr, true), $ma);
        #回调转字符串长度
        //$arr_size = array_map('strlen', current($ma));
        #取出最大长度并减掉左括号占用的一位长度
        //$max_size = max($arr_size) - 1;
        #数组层间距以 8 个空格列，这里要加 1 个是因为 print_r 打印的第一层左括号在行首
        //return $max_size / 8 + 1;
        return (max(array_map('strlen', current($ma))) - 1) / 8 + 1;
    } else {
        return 0;
    }
}

/**
 * $gamble  要处理的数组
 * $type    字体 0：简体  1：繁体 2：英文 默认为0
 * $handcp  是否处理盘口  默认为false
 * $game_type  1足球  2篮球 默认足球
 * @return  一 / 二维数组
 */
function HandleGamble($gamble,$type=0,$is_do=false,$game_type=1)
{
    if(arrayLevel($gamble) >= 2){
        foreach ($gamble as $k => $v) {
            $gamble[$k]['union_name']     = switchName($type,$v['union_name']);
            $gamble[$k]['home_team_name'] = switchName($type,$v['home_team_name']);
            $gamble[$k]['away_team_name'] = switchName($type,$v['away_team_name']);
            if($game_type == 1) //足球
            {
                if(in_array($v['play_type'], [1,-1])) //亚盘
                {
                    switch ($v['chose_side']) {
                        case '1':  $gamble[$k]['Answer'] = $v['play_type'] == 1 ? $gamble[$k]['home_team_name'] : '大球';break;
                        case '-1': $gamble[$k]['Answer'] = $v['play_type'] == 1 ? $gamble[$k]['away_team_name'] : '小球';break;
                    }
                }
                else //竞彩
                {
                    switch ($v['chose_side']) {
                        case '1':  $gamble[$k]['Answer'] = '胜';break;
                        case '0':  $gamble[$k]['Answer'] = '平';break;
                        case '-1': $gamble[$k]['Answer'] = '负';break;
                    }
                }
            }
            else //篮球
            {
                switch ($v['chose_side']) {
                    case '1':  $gamble[$k]['Answer'] = in_array($v['play_type'], [1,2]) ? $gamble[$k]['home_team_name'] : '大球';break;
                    case '-1': $gamble[$k]['Answer'] = in_array($v['play_type'], [1,2]) ? $gamble[$k]['away_team_name'] : '小球';break;
                }
            }
            if($is_do == true){
                if($v['play_type'] > 0){
                    $handcp = $v['chose_side']*-1*$v['handcp'];
                    if($handcp > 0) $handcp = '+'.$handcp;
                    $gamble[$k]['handcp'] = $handcp;
                }
            }

            //判断语音推介是否通过
            if ($v['is_voice'] == 1) {
                if ($v['voice']) {
                    $gamble[$k]['voice'] = imagesReplace($v['voice']);
                }
            } else {
                $gamble[$k]['voice'] = '';
            }

            //判断推介分析是否通过
            if ($v['desc_check'] != 1) {
                $gamble[$k]['analysis'] = '';
            }
        }
    }elseif(arrayLevel($gamble) == 1){
        $gamble['union_name']     = switchName($type,$gamble['union_name']);
        $gamble['home_team_name'] = switchName($type,$gamble['home_team_name']);
        $gamble['away_team_name'] = switchName($type,$gamble['away_team_name']);
        if($game_type == 1) //足球
        {
            if(in_array($gamble['play_type'], [1,-1])) //亚盘
            {
                switch ($gamble['chose_side']) {
                    case '1':  $gamble['Answer'] = $gamble['play_type'] == 1 ? $gamble['home_team_name'] : '大球';break;
                    case '-1': $gamble['Answer'] = $gamble['play_type'] == 1 ? $gamble['away_team_name'] : '小球';break;
                }
            }
            else //竞彩
            {
                switch ($gamble['chose_side']) {
                    case '1':  $gamble['Answer'] = '胜';break;
                    case '0':  $gamble['Answer'] = '平';break;
                    case '-1': $gamble['Answer'] = '负';break;
                }
            }
        }
        else //篮球
        {
            switch ($gamble['chose_side']) {
                case '1':  $gamble['Answer'] = in_array($gamble['play_type'], [1,2]) ? $gamble['home_team_name'] : '大球';break;
                case '-1': $gamble['Answer'] = in_array($gamble['play_type'], [1,2]) ? $gamble['away_team_name'] : '小球';break;
            }
        }
        if($is_do == true){
            if($gamble['play_type'] > 0){
                $handcp = $gamble['chose_side']*-1*$gamble['handcp'];
                if($handcp > 0) $handcp = '+'.$handcp;
                $gamble['handcp'] = $handcp;
            }
        }

        //判断语音推介是否通过
        if ($gamble['is_voice'] == 1) {
            if ($gamble['voice']) {
                $gamble['voice'] = imagesReplace($gamble['voice']);
            }
        } else {
            $gamble['voice'] = '';
        }

        //判断推介分析是否通过
        if ($gamble['desc_check'] != 1) {
            $gamble['analysis'] = '';
        }
    }

    return $gamble;
}

//获取输入参数
function getParam()
{
    $param = array_merge(I('get.'),I('post.'));
    return $param;
}

//获取错误提示信息
function getErrorMsg($code)
{
    return C('errorCode')[$code];
}

//获取前7天，前30天，前90天的起止日期
function getRankDate($dateType=1)
{
    switch ($dateType)
    {
        case '1':
            $begin   =   date("Ymd",strtotime("-7 day"));
            $end     =   date("Ymd",strtotime("-1 day"));
            break;
        case '2':
            $begin   =   date("Ymd",strtotime("-30 day"));
            $end     =   date("Ymd",strtotime("-1 day"));
            break;
        case '3':
            $begin   =   date("Ymd",strtotime("-90 day"));
            $end     =   date("Ymd",strtotime("-1 day"));
            break;
    }
    return [$begin,$end];
}

//获取前8天，前31天，前91天的起止日期
function getTopRankDate($dateType=1)
{
    switch ($dateType)
    {
        case '1':
            $begin   =   date("Ymd",strtotime("-8 day"));
            $end     =   date("Ymd",strtotime("-2 day"));
            break;
        case '2':
            $begin   =   date("Ymd",strtotime("-31 day"));
            $end     =   date("Ymd",strtotime("-2 day"));
            break;
        case '3':
            $begin   =   date("Ymd",strtotime("-91 day"));
            $end     =   date("Ymd",strtotime("-2 day"));
            break;
    }
    return [$begin,$end];
}

/**
 * 根据类型获取排行榜区间
 * @param  int $gameType  体育类型  1足球  2篮球  默认1
 * @param  int $dateType  榜类型    1周  2月  3季 默认1
 * @return array
 */
function getRankBlockDate($gameType=1,$dateType=1)
{
    //日期筛选
    $segmTime = $gameType == 1 ? strtotime('10:32') : strtotime('12:00');
    list($begin,$end) = time() >= $segmTime ? getRankDate($dateType) : getTopRankDate($dateType);
    return [$begin,$end];
}

/**
 * 拼接用户头像域名地址（web,app共用）
 * @param  faceImg  $faceImg  路径
 * @return mixed   头像路径
 */
function frontUserFace($faceImg)
{
    if(!empty($faceImg)){
        $head = imagesReplace($faceImg);
    }else{
        $HOST_IP = explode('.', $_SERVER['HTTP_HOST']);
        if(is_numeric($HOST_IP[0])){//IP访问
            $head = SITE_URL.$_SERVER['HTTP_HOST']."/Public/Home/images/common/face.png";
        }else{//域名访问
            $head = staticDomain("/Public/Home/images/common/face.png");
        }
    }
    return $head;
}

/**
 * @param $list
 * @param string $pk
 * @param string $pid
 * @param string $child
 * @param int $root
 * @return array
 * 无限分类 数组转 树形结构
 */
function list_to_tree($list, $pk='id', $pid = 'pid', $child = '_child', $root = 0) {
    // 创建Tree
    $tree = array();
    if(is_array($list)) {
        // 创建基于主键的数组引用
        $refer = array();
        foreach ($list as $key => $data) {
            $refer[$data[$pk]] =& $list[$key];
        }
        foreach ($list as $key => $data) {
            // 判断是否存在parent
            $parentId =  $data[$pid];
            if ($root == $parentId) {
                $tree[$data[$pk]] =& $list[$key];
            }else{
                if (isset($refer[$parentId])) {
                    $parent =& $refer[$parentId];
                    $parent[$child][$data[$pk]] =& $list[$key];
                }
            }
        }
    }
    return $tree;
}

/**
 * 获取前台用户的头像（web,app共用）
 * @param  int  $userid     用户id
 * @param  int  $size        有50 100 200 600
 * @return mixed            如没头像返回空字符串
 */
function getfrontUserFace($userid,$size=100)
{
    $face = Think\Tool\Tool::getFileList('/user/'.$userid.'/face/',1);
    if(!$face){
        return  null;
    }
    $suffix = substr($face, strrpos($face, '.'));
    foreach ($face as $k => $v) {
        //正则获取需要尺寸
        if(preg_match("/{$size}/", $v)){
            $faceImg = $v;
        }
    }
    return $faceImg ? $faceImg : null;
}

/**
 * api获取球队logo
 * @param  int  $teamId  球队id
 * @param  int  $side    主客队 1：主，2：客
 * @param  int  $gameType  球队类型 1:足球 2:篮球
 * @return str           图片地址
 */
function getLogoTeam($teamId,$side=1,$gameType=1)
{
    //默认logo
    $defSide = $side == 1 ? 'home_def.png' : 'away_def.png';
    $defLogo = staticDomain('/Public/Home/images/common/'.$defSide);

    if ((iosCheck()) && I('platform') == '2')
        return $defLogo;

    $TeamModel = $gameType == 1 ? 'GameTeam' : 'GameTeambk';
    $logo = M($TeamModel)->where(['team_id'=>$teamId,'status'=>1])->getField('img_url');

    return !empty($logo) ? C('IMG_SERVER').$logo : $defLogo;
}

/**
 * 获取球队logo
 * @param  int  $teamId    赛程数组
 * @param  int  $gameType  球队类型 1:足球 2:篮球
 * @return str  图片地址
 */
function setTeamLogo(&$game,$gameType=1)
{
    $home_team_id = $away_team_id = [];
    foreach ($game as $k => $v) {
        $home_team_id[] = $v['home_team_id'];
        $away_team_id[] = $v['away_team_id'];
    }
    $TeamModel = $gameType == 1 ? 'GameTeam' : 'GameTeambk';
    //获取球队logo
    $team_id = array_merge($home_team_id,$away_team_id);
    //ios审核时默认logo
    if ( iosCheck() || empty($team_id) )
    {
        foreach ($game as $k => $v) {
            $game[$k]['homeTeamLogo'] = staticDomain('/Public/Home/images/common/home_def.png');
            $game[$k]['awayTeamLogo'] = staticDomain('/Public/Home/images/common/away_def.png');
        }
        return;
    }
    //统一查询出来
    $game_team = M($TeamModel)->field('team_id,img_url')->where(['team_id'=>['in',$team_id],'status'=>1])->select();
    $IMG_SERVER = C('IMG_SERVER'); //独立图片服务器地址
    foreach ($game as $k => $v) {
        foreach ($game_team as $kk => $vv) {
            $img_url = $vv['img_url'];
            if($v['home_team_id'] == $vv['team_id']){
                $game[$k]['homeTeamLogo'] = !empty($img_url) ? $IMG_SERVER.$img_url : staticDomain('/Public/Home/images/common/home_def.png');
            }
            if($v['away_team_id'] == $vv['team_id']){
                $game[$k]['awayTeamLogo'] = !empty($img_url) ? $IMG_SERVER.$img_url : staticDomain('/Public/Home/images/common/away_def.png');
            }
        }
    }
}

/**
 * 获取球队logo
 * @param  array  $array  要处理的数组
 * @return array
 */
function getTeamLogo($array,$gameType=1)
{
    $TeamModel = $gameType == 1 ? 'GameTeam' : 'GameTeambk';
    //默认logo
    if(arrayLevel($array) == 2){
        foreach ($array as $k => $v) {
            $home_logo = M($TeamModel)->where(['team_id'=>$v['home_team_id'],'status'=>1])->getField('img_url');
            $away_logo = M($TeamModel)->where(['team_id'=>$v['away_team_id'],'status'=>1])->getField('img_url');
            $array[$k]['home_logo'] = !empty($home_logo) && $gameType == 1 ? C('IMG_SERVER').$home_logo : staticDomain('/Public/Home/images/common/home_def.png');
            $array[$k]['away_logo'] = !empty($away_logo) && $gameType == 1 ? C('IMG_SERVER').$away_logo : staticDomain('/Public/Home/images/common/away_def.png');
        }
    }elseif(arrayLevel($array) == 1){
        $home_logo = M($TeamModel)->where(['team_id'=>$array['home_team_id'],'status'=>1])->getField('img_url');
        $away_logo = M($TeamModel)->where(['team_id'=>$array['away_team_id'],'status'=>1])->getField('img_url');
        $array['home_logo'] = !empty($home_logo) && $gameType == 1 ? C('IMG_SERVER').$home_logo : staticDomain('/Public/Home/images/common/home_def.png');
        $array['away_logo'] = !empty($away_logo) && $gameType == 1 ? C('IMG_SERVER').$away_logo : staticDomain('/Public/Home/images/common/away_def.png');
    }
    return $array;
}

/**
 * 获取足球竞猜输赢
 * @param  $score         比赛比分
 * @param  $play_type     玩法：1亚盘让分 -1亚盘大小 2竞彩不让球 -2竞彩让球
 * @param  $handcp        盘口
 * @param  $chose_side    竞猜结果 主1 客 -1 1 大 -1 小  1 胜  -1 负 0 平
 * @return array
 */
function getTheWin($score,$play_type,$handcp,$chose_side){
    //判断格式如有'/'转换
    if(stripos($handcp,'/')){
        $handcp = changeExpStrToNum($handcp);
    }

    list($homeScore,$awayScore) = explode('-', $score);

    switch ($play_type)
    {
        case  1:  $diff = $homeScore - $awayScore - $handcp;  break; //让分
        case -1:  $diff = $homeScore + $awayScore - $handcp;  break; //大小球
        case  2:  $diff = $homeScore - $awayScore;            break; //不让球
        case -2:  $diff = $homeScore - $awayScore + $handcp;  break; //让球
    }

    switch ($play_type)
    {
        case '1':
        case '-1':
                $diff = $diff * $chose_side; //选 主/大 或 客/小
                switch ($diff)
                {
                    case -0.25:             $result = '-0.5';      break; //输半
                    case 0:                 $result = '2';         break; //平
                    case 0.25:              $result = '0.5';       break; //赢半
                    case $diff < -0.25:     $result = '-1';        break; //输
                    case $diff > 0.25:      $result = '1';         break; //赢
                }
            break;
        case '2':
        case '-2':
                if($chose_side == 0) //选平
                {
                    $result = ($diff == 0 && $chose_side == 0) ? '1' : '-1';
                }else { //选胜负
                    $diff1  = $diff * $chose_side;
                    $result = $diff1 > 0 ? '1' : '-1';
                }
            break;
    }
    return $result;
}

/**
 * 获取篮球竞猜输赢
 * @param  $score         比赛比分
 * @param  $half_score    各小节比分
 * @param  $play_type     玩法
 * @param  $handcp        盘口
 * @param  $chose_side    竞猜结果
 * @return array
 */
function getTheWinBk($score,$half_score,$play_type,$handcp,$chose_side){
    switch ($play_type)
    {
        case 1:
        case -1:  list($homeScore,$awayScore) = explode('-', $score);  break;
        case 2:
        case -2:  list($homeScore,$awayScore) = explode('-', getHalfScore('',$half_score));  break;
    }
    switch ($play_type)
    {
        case 1:
        case 2:  $diff = $homeScore - $awayScore - $handcp;  break; //全场让分 和 半场让分
        case -1:
        case -2: $diff = $homeScore + $awayScore - $handcp;  break; //全场大小 和 半场大小
    }
    $diff = $diff * $chose_side; //选 主/大 或 客/小
    switch ($diff)
    {
        case 0:          $result = '2';         break; //平
        case $diff < 0:  $result = '-1';        break; //输
        case $diff > 0:  $result = '1';         break; //赢
    }
    return $result;
}

/**
 * 计算竞猜胜率
 * @param $win       int  赢的次数
 * @param $half      int  赢半的次数
 * @param $transport int  输的次数
 * @param $donate    int  输半的次数
 * @return  string
 *
*/
function getGambleWinrate($win=0,$half=0,$transport=0,$donate=0)
{
    $winTotal    = $win + $half;
    $gambleTotal = $winTotal + $transport + $donate;
    $winrate     = $gambleTotal ? round(($winTotal/$gambleTotal)*100) : 0;
    return $winrate;
}

/**
 * 计算竞猜胜率（不去掉小数点，用户实际排序）
 * @param $win       int  赢的次数
 * @param $half      int  赢半的次数
 * @param $transport int  输的次数
 * @param $donate    int  输半的次数
 * @return  string
 *
*/
function getGambleWinrateTwo($win=0,$half=0,$transport=0,$donate=0)
{
    $winTotal    = $win + $half;
    $gambleTotal = $winTotal + $transport + $donate;
    $winrate     = $gambleTotal ? (($winTotal/$gambleTotal)*100) : 0;
    return $winrate;
}

/**
 * 发送手机验证码
 * @param $mobile  int     手机号码
 * @param $type    string  操作
 * @param $deviceID string 手机设备标识
 * @return  bool
*/
function sendCode($mobile, $type='registe', $deviceID = '')
{
    $platform = I('platform');
    if(!in_array($platform, [1,2,3,4]))
        return -1;

    $mobile_sign = C('smsPrefix').$mobile.':is_send';
    $ip_sign     = C('smsPrefix').get_client_ip().':is_send';

    if (S($mobile_sign) || S($ip_sign)) //防止刷短信验证码
        return -1;

    switch ($type)
    {
        case 'registe':         $typeStr = '注册';          break;
        case 'editPwd':         $typeStr = '修改密码';      break;
        case 'editExtractPwd':  $typeStr = '修改提款密码';  break;
        case 'resetPwd':        $typeStr = '重置';          break;
        case 'bindPhone':       $typeStr = '绑定手机';      break;
        case 'backDrawPass':    $typeStr = '找回提款密码';  break;
        case '852':             $typeStr = '注册';          break;
        case '853':             $typeStr = '注册';          break;
        case '886':             $typeStr = '注册';          break;
        case 'recommend':       $typeStr = '赠送推荐';      break;
        case 'active':          $typeStr = '活动';          break;
        case 'verifyPhone':     $typeStr = '手机短信';      break;
        default: $typeStr = ''; break;
    }
    $commonConf = getWebConfig('common'); //获取发送短信运营商
    $rank = GetRandStr(4, 'number');  //验证码
    $area_code = M('FrontUser')->where(['username'=>$mobile])->getField('area_code'); //取出国际号码区号
    //判断是否是国际号码
    if(in_array($type, array('852', '853', '886')) || in_array($area_code, array('00852', '00853', '00886')))
    {
        if(S(C('smsPrefix') . $deviceID . ':forbidden')) return -2;

        $num = M('SendcodeLog')->where(['deviceID' => $deviceID, 'send_time' => ['gt', NOW_TIME - 300]])->count();
        //5分钟内最多发送3次
        if ($num >= 3) {
            S(C('smsPrefix') . $deviceID . ':forbidden', 1, 60 * 10);//禁止10分钟
            return -2;
        }
        //同一设备一天内最多发送5次
        $total = M('SendcodeLog')->where(['deviceID' => $deviceID, 'send_time' => ['between', [strtotime('00:00'), strtotime('23:59:59')]]])->count();
        if ($total >= 5) return -2;

        if( ($type == '886' || $area_code == '00886') && (strlen($mobile) == 10) && ($mobile[0] == 0) )
            $mobile = substr($mobile, 1);

        //数据库区号为空时为注册
        if($area_code == '') $area_code = $type;

        $msg    = "【全球体育】 验证码".$rank."，你正在进行".$typeStr."验证，".(C('verifyCodeTime')/60)."分钟内有效，如非本人操作，请忽略。";

        if($commonConf['mobileSMS'] == 4){
            //infobip短信
            $phone = intval($area_code).$mobile;
            $result = sendInfobipSMS($phone, $msg);
        }else{
            //创蓝短信
            $phone = '00'.intval($area_code).$mobile;
            $result = sendChuanglanSMS($phone, $msg);
        }
    }
    else
    {
        switch ($commonConf['mobileSMS'])  //1:圣亚短信  2:大鱼短信  3:大鱼语音  4:infobip短信
        {
            case '1':
                $msg = "验证码" . $rank . "，你正在进行" . $typeStr . "验证，" . (C('verifyCodeTime') / 60) . "分钟内有效，如非本人操作，请忽略。";
                $result = sendingSMS($mobile, $msg);
                break;
            case '2':
                $msg = '【全球体育】 验证码'.$rank.'，您正在进行'.$typeStr.'验证，'.(C('verifyCodeTime') / 60).'分钟内有效，如非本人操作，请忽略。 ';
                $result = sendingDaYuSMS($mobile, $rank, $typeStr);
                break;
            case '3':
                $msg = '【全球体育】 验证码'.$rank.'，您正在进行'.$typeStr.'验证，'.(C('verifyCodeTime') / 60).'分钟内有效，如非本人操作，请忽略。 ';
                $result = sendingDaYuVoice($mobile, $rank, $typeStr);
                break;
            case '4':
                $msg = '【全球体育】 验证码'.$rank.'，您正在进行'.$typeStr.'验证，'.(C('verifyCodeTime') / 60).'分钟内有效，如非本人操作，请忽略。 ';
                $result = sendInfobipSMS('86'.$mobile, $msg);
                break;
        }
    }

    if ($result)
    {
        $token = md5(C('smsPrefix').$mobile.($type == 'resetPwd' ? time().mt_rand(1000,9999) : ''));

        if (S($token,['mobile'=>$mobile,'rank'=>$rank],C('verifyCodeTime')))
        {
            //保存发送记录表
            $data['area_code'] = in_array($type, array('852', '853', '886')) ? '00'.$type : $area_code;
            $data['mobile']    = $mobile;
            $data['deviceID']  = $deviceID;
            $data['ip']        = get_client_ip();
            $data['send_type'] = $commonConf['mobileSMS'];
            $data['content']   = isset($msg) ? $msg : '';
            $data['code']      = $rank;
            $data['platform']  = $platform;
            $data['send_time'] = NOW_TIME;
            M('SendcodeLog')->add($data);

            S($mobile_sign,1,C('reSendCodeTime'));
            S($ip_sign,1,C('reSendCodeTime'));
            return ['token'=>$token,'mobileSMS'=>(int)$commonConf['mobileSMS']];
        }
    }
    else
    {
        return false;
    }
}

/**
 * 发送短信接口  (infobip)
 * @param $mobile  int  手机号码
 * @param $msg     int  内容
 * @return  bool
 *
*/
function sendInfobipSMS($mobile, $text){
    $postUrl = "https://api.infobip.com/sms/1/text/advanced";
    $username = 'QQTY';
    $password = 'Globalsports666';
    $postData = array(
            'bulkId'=>'QQTY'.time(),
            'messages'=>[
                'from'=>'QQTY',
                'destinations'=>[
                    [
                        'to'=>$mobile
                    ],
                ],
                'text'=>$text,
            ],
        );

    $postDataJson = json_encode($postData);

    $ch = curl_init();
    $header = array("Content-Type:application/json","Accept:application/json");
    curl_setopt($ch, CURLOPT_URL, $postUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//https
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postDataJson);
    // response of the POST request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $responseBody = json_decode($response,true);
    curl_close($ch);
    if ($httpCode >= 200 && $httpCode < 300) {
        return true;
        // $messages = $responseBody->messages;
        // foreach ($messages as $message) {
        //     echo "";
        //     echo "" . $message->messageId . "";
        //     echo "" . $message->to . "";
        //     echo "" . $message->status->groupId . "";
        //     echo "" . $message->status->groupName . "";
        //     echo "" . $message->status->id . "";
        //     echo "" . $message->status->name . "";
        //     echo "" . $message->status->description . "";
        //     echo "" . $message->smsCount . "";
        //     echo "";
        // }
    }
    return false;
}

/**
 * 发送短信接口  (圣亚)
 * @param $mobile  int  手机号码
 * @param $msg     int  内容
 * @return  bool
 *
*/
function sendingSMS($mobile,$msg){
    //http提交方式 php示例
    $url = 'http://115.29.194.198/api/http';//调用地址
    $postdata = array(
            'user'     =>  'shengya-ct',//用户名
            'passwd'   =>  '888888',//用户密码
            'phone'    =>   $mobile,//发送号码
            'msg'      =>  '【全球体育】'.$msg,//短信内容
            'sendTime' =>  '',//短信时间可为空，为空为及时发送
            'act'      =>  'sendmsg',//规定动作，发送短信
    );
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL, $url);
    curl_setopt($ch,CURLOPT_TIMEOUT,60);
    curl_setopt($ch,CURLOPT_HEADER,0);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch,CURLOPT_POST,1);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$postdata);
    $data = curl_exec($ch);
    curl_close($ch);

    return $data;//$data为短信序列号（如32787）发送成功
}

/**
 * 发送短信接口  (百信通)
 * @param $mobile  int  手机号码
 * @param $msg     int  内容
 * @return  bool
 *
*/
function BxtSMS($mobile,$msg){
    $url = 'http://open.panzhi.net/Sms1.php';//调用地址
    $postdata = array(
            'act'     => 'SendMsg',
            'orgid'   => '175',  //短信机构
            'usr'     => 'qckj8',//用户帐号
            'pwd'     => '6e5e14ea ',//用户密码
            'msg'     => $msg.' 【全球体育】',   //短信内容
            'phones'  => $mobile,//发送号码（同时发给多个号码时，可用分号“;”隔开）
            'actTime' => '',     //为空为及时发送,格式必须为:yyyy-mm-dd hh:MM:ss
    );
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL, $url);
    curl_setopt($ch,CURLOPT_TIMEOUT,60);
    curl_setopt($ch,CURLOPT_HEADER,0);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch,CURLOPT_POST,1);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$postdata);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;//大于0发送成功
}

/**
 * 发送短信接口  (大鱼)
 * @param $mobile  int     手机号码
 * @param $rank    int     验证码
 * @param $typeStr string  操作
 * @return  bool
 *
*/
function sendingDaYuSMS($mobile,$rank,$typeStr){
    vendor('DayuSMS.TopSdk');
    $c = new TopClient;
    $c->appkey = '23398811';
    $c->secretKey = '4b4e73cdd1b02693392096e7c7ee0b5b';

    $req = new AlibabaAliqinFcSmsNumSendRequest;
    $req->setSmsType("normal");
    $req->setSmsFreeSignName("全球体育");

    $time = C('verifyCodeTime')/60;

    $req->setSmsParam("{\"code\":\"".$rank."\",\"product\":\"".$typeStr."\",\"time\":\"".$time."\"}");
    $req->setRecNum($mobile);
    $req->setSmsTemplateCode("SMS_12340474");
    $resp = $c->execute($req);
    $success = (array)$resp->result->success;
    if($success[0] == true){
        return true;
    }else{
        return false;
    }
}

/**
 * 发送语音验证码  (大鱼)
 * @param $mobile  int     手机号码
 * @param $rank    int     验证码
 * @param $typeStr string  操作
 * @return  bool
 *
*/
function sendingDaYuVoice($mobile,$rank,$typeStr){
    vendor('DayuSMS.TopSdk');
    $c = new TopClient;
    $c->appkey = '23398811';
    $c->secretKey = '4b4e73cdd1b02693392096e7c7ee0b5b';

    $req = new AlibabaAliqinFcTtsNumSinglecallRequest;

    $time = C('verifyCodeTime')/60;

    $req->setTtsParam("{\"code\":\"".$rank."\",\"product\":\"".$typeStr."\",\"time\":\"".$time."\"}");
    $req->setCalledNum($mobile);
    $req->setCalledShowNum("051482043260");
    $req->setTtsCode("TTS_13885443");
    $resp = $c->execute($req);
    $success = (array)$resp->result->success;

    if($success[0] == true){
        return true;
    }else{
        return false;
    }
}

/**
 * 创蓝国际短信
 */
function sendChuanglanSMS($phone, $content){
    Vendor('ChuanglanSMS.ChuanglanSMS');
    $c = new ChuanglanSMS('I6885178', 'WvakRw1gshb1d4');

    $res = $c->sendInternational($phone, $content, $isreport=0);
//    $res = '{"success": true, "id":"16110716121000157856"}';
    $res = json_decode($res, true);
    if($res['success'] == true){
        return true;
    }else{
        return false;
    }
}

/**
 * 创蓝查询国外账户额度
 */
function checkChuanglanBalance(){
    Vendor('ChuanglanSMS.ChuanglanSMS');
    $c = new ChuanglanSMS('I6885178', 'WvakRw1gshb1d4');

    return $c->queryBalanceInternational();
}

/**
 * 充值流量接口
 *
 * @param $mobile  int  手机号码
 * @param $size    int  流量包大小
 * @return  json
*/
function sendingFlow($mobile,$size){
    $sdk = C('THINK_SDK_FLOW');
    $sign = http_build_query(['account'=>$sdk['APP_KEY'],'mobile'=>$mobile,'packageSize'=>$size,'key'=>$sdk['APP_SECRET']]);
    $flow = array(
        'version'      => '1.0',
        'action'       => 'charge',
        'account'      => $sdk['APP_KEY'],
        'mobile'       => $mobile,
        'packageSize'  => $size,
        'sign'         => md5($sign),
        'requestId'    => NOW_TIME
    );
    $httpUrl = 'http://120.76.74.59:8080/customer/api?' . http_build_query($flow);
    $return = file_get_contents($httpUrl);
    return json_decode($return,true);
}

/**
 * 获取随机数
 */
function GetRandStr($len,$charType='')
{
    $letter = [
        "a", "b", "c", "d", "e", "f", "g", "h", "j", "k",
        "m", "n", "p", "q", "r", "s", "t", "u", "v",
        "w", "x", "y", "z"
    ];

    $number = ["2","3", "4", "5", "6", "7", "8", "9"];

    if ($charType == 'letter')
        $chars = $letter;
    else if ($charType == 'number')
        $chars = $number;
    else
        $chars = array_merge($letter,$number);

    $charsLen = count($chars) - 1;
    shuffle($chars);

    $output = "";

    for ($i=0; $i<$len; $i++)
    {
        $output .= $chars[mt_rand(0, $charsLen)];
    }
    return $output;
}

//获取足球赛程日期 11点区间
function getShowDate(){
    $show_date = time() >= strtotime('11:00') ? date('Ymd') : date("Ymd",strtotime("-1 day"));
    return $show_date;
}

//获取篮球赛程日期 12点区间
function getGameDate(){
    $game_date = time() >= strtotime('12:00') ? date('Ymd') : date("Ymd",strtotime("-1 day"));
    $tomorrow  = time() >= strtotime('12:00') ? date("Ymd",strtotime("+1 day")) : date('Ymd');
    return ['game_date'=>$game_date,'tomorrow'=>$tomorrow];
}

//获取赛程分割日期的区间时间
//$gameType 1:足球  2:篮球
//$gamble   是否为竞猜的时间区间
function getBlockTime($gameType=1,$gamble=false)
{

    $segmTime = $gameType == 1 ? strtotime('10:32:00') : strtotime('12:00');

    if ($gameType == 1) //football
    {
        if (time() > $segmTime)
        {
            $beginTime = $gamble == true ? $segmTime : strtotime('8:00:00');
            $endTime   = strtotime('+1 day',$segmTime);
        }
        else
        {
            $beginTime = $gamble == true ? strtotime('-1 day',$segmTime) : strtotime('8:00:00')-3600*24;
            $endTime   = $segmTime;
        }
    }
    else                //basketball
    {
        if (time() > $segmTime)
        {
            $beginTime = $segmTime;
            $endTime   = strtotime('+1 day',$segmTime);
        }
        else
        {
            $beginTime = strtotime('-1 day',$segmTime);
            $endTime   = $segmTime;
        }
    }

    return ['beginTime'=>$beginTime,'endTime'=>$endTime];
}

//添加查询条件(用于篮球时)
function addSearchBk(&$where,$view=false){
    $game_date = getGameDate()['game_date'];
    $tomorrow  = getGameDate()['tomorrow'];
    //是否使用了视图
    if(!$view){
        $where['_string'] = "((game_date = {$game_date} and game_time >= '12:00') or (game_date = {$tomorrow} and game_time <= '12:00'))";
    }else{
        $where['_string'] = "((g.game_date = {$game_date} and g.game_time >= '12:00') or (g.game_date = {$tomorrow} and g.game_time <= '12:00'))";
    }
}

//获取半场比分
function getHalfScore($type,$str){
    $array = explode(',', $str);
    $HomeHalfScore = explode('-', $array[0])[0] + explode('-', $array[1])[0];
    $AwayHalfScore = explode('-', $array[0])[1] + explode('-', $array[1])[1];
    if(empty($type))
    {
        if($HomeHalfScore == 0 && $AwayHalfScore == 0){
            return "--";
        }
        return $HomeHalfScore.'-'.$AwayHalfScore;
    }
    if($HomeHalfScore == 0 && $AwayHalfScore == 0){
        return '';
    }
    switch ($type) {
        case '1':
            $score = $HomeHalfScore;
            break;
        case '2':
            $score = $AwayHalfScore;
            break;
    }
    return $score;
}

//赔率格式转换 把-去掉放在最前面
function changeExpP($str){
    if($str == ''){
        return '';
    }
    if(strpos($str,"-") !== false)
    {
        $temp = str_replace("-","",$str);
        return '-'.$temp;
    }
    return $str;
}

//赔率格式转换 0.25=>0/0.5
function changeExp($str)
{
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
//赔率格式转换 0.25=>0/0.5
function changeExpT($str)
{
    if($str == '' || $str == null) return '';
    if(empty($str) || $str == '-0' || $str == '0') return '0';

    if(strpos($str,"-") !== false)
    {
        $temp = str_replace("-","",$str);
        $res = floatval($temp);

        if(strpos($res,'.25') !== false || strpos($res,'.75'))
        {
            $res =  '-'.($res-0.25) .'/'.(($res+0.25));
        }
        return (string)$res;
    }
    else
    {
        $res = floatval($str);

        if(strpos($res,'.25') !== false || strpos($res,'.75'))
        {
            $res =  ($res-0.25) .'/'.(($res+0.25));
        }
        return (string)$res;
    }
}

//匹配联赛排行
function pregUnionRank($str)
{
    if(empty($str)) return false;
     if(preg_match('/\d+/i',$str,$data))
        return $data[0];
    else
        return $str;
   /*
    #可保留字母
    if(preg_match('/[a-zA-Z]+-\d+$/i',$str,$data))
    {
        return $data[0];
    }
    else
    {
        if(preg_match('/\d+$/i',$str,$data))
            return $data[0];
        else
            return $str;
    }*/
}

//连接Redis
function connRedis($options = [])
{
    return (new \Think\Cache\Driver\Redis($options))->handler;
}
/**
 * @周格式化
 * */
function getWeek($week){
    switch($week){
        case 1:
            return "星期一";
            break;
        case 2:
            return "星期二";
            break;
        case 3:
            return "星期三";
            break;
        case 4:
            return "星期四";
            break;
        case 5:
            return "星期五";
            break;
        case 6:
            return "星期六";
            break;
        case 0:
            return "星期日";
            break;
    }
}
//计算时间几分钟前、几小时前、几....前
function format_date($time){
    $t=time()-$time;
    if($t == 0){
        return '刚刚';
    }
    $f=array(
        '31536000'=>'年',
        '2592000'=>'个月',
        '604800'=>'星期',
        '86400'=>'天',
        '3600'=>'小时',
        '60'=>'分钟',
        '1'=>'秒'
    );
    foreach ($f as $k=>$v)    {
        if (0 !=$c=floor($t/(int)$k)) {
            return $c.$v.'前';
        }
    }
}

/**
 * 字符串截取，支持中文和其他编码
 * static
 * access public
 * @param string $str 需要转换的字符串
 * @param string $start 开始位置
 * @param string $length 截取长度
 * @param string $charset 编码格式
 * @param string $suffix 截断显示字符
 * return string
 */
function msubstr($str, $start=0, $length, $charset="utf-8", $suffix=true) {
    if(function_exists("mb_substr"))
        $slice = mb_substr($str, $start, $length, $charset);
    elseif(function_exists('iconv_substr')) {
        $slice = iconv_substr($str,$start,$length,$charset);
        if(false === $slice) {
            $slice = '';
        }
    }else{
        $re['utf-8']  = "/[x01-x7f]|[xc2-xdf][x80-xbf]|[xe0-xef][x80-xbf]{2}|[xf0-xff][x80-xbf]{3}/";
        $re['gb2312'] = "/[x01-x7f]|[xb0-xf7][xa0-xfe]/";
        $re['gbk']    = "/[x01-x7f]|[x81-xfe][x40-xfe]/";
        $re['big5']   = "/[x01-x7f]|[x81-xfe]([x40-x7e]|xa1-xfe])/";
        preg_match_all($re[$charset], $str, $match);
        $slice = join("",array_slice($match[0], $start, $length));
    }
    return $suffix && mb_strlen($str,'utf-8') > $length ? $slice.'...' : $slice;
}

//获取唯一订单号
function getTradeNo($userId)
{
    $orderNo = '';
    $letter = '';
    for ($i = 1; $i <= 3; $i++)
    {
        $letter .= chr(rand(65, 90));
    }
    $orderNo = $letter.date('YmdHis').$userId.rand(100,999);
    $trade = M('TradeRecord');
    $where['trade_no'] = $orderNo;
    $res = $trade->master(true)->field('id')->where($where)->find();
    if(!empty($res))
        return getTradeNo();
    else
        return $orderNo;
}

/**
 * 执行充值
 * @param  $order_id     订单号
 * @return bool
 */
function ExecutiveRecharge($order_id){
    if(empty($order_id)){
        return false;
    }

    //查询充值记录是否已充值
    if(M('AccountLog')->master(true)->where(['order_id'=>$order_id])->find()){
        logRecord("ExecutiveRecharge 查询充值记录是否已充值：".$order_id,'logCharge.txt');
        return true;
    }
    //查询订单
    $order = M('tradeRecord')->master(true)->where(['trade_no'=>$order_id])->field("trade_no,user_id,pay_fee,trade_state,platform,pay_type,give_coin,telBind")->find();
    if(empty($order) || !in_array($order['trade_state'], [1,2])){
        logRecord("金币添加 查询订单 ：".M()->getLastsql().'====>'.json_encode($order),'logCharge.txt');
        return false;
    }

    $order['pay_fee'] = (int)$order['pay_fee'];
    $user = M('FrontUser')->master(true)->field('coin,unable_coin')->find($order['user_id']);
    if(empty($user))
    {
        logRecord("金币添加 用户不存在 ：".M()->getLastsql().'====>','logCharge.txt');
        return false;
    }

    //如果没有赠送金币，则APP充值赠送金币；如果有就表示使用优惠券就不能获得自动赠送金币
    $chang_num = 0;
    if($order['give_coin'] == 0){
        $config = getWebConfig('recharge')['recharge'];//充值配置
        foreach($config as $k => $v){
            if(intval($v['account']) == intval($order['pay_fee'])){
                $chang_num = $v['number'];
                break;
            }
        }
    }

    $total_coin = $order['pay_fee'] + $chang_num;

    //执行充值
    $rs = M('FrontUser')->where(['id'=>$order['user_id']])->save(['unable_coin'=>['exp',"unable_coin+{$total_coin}"]]);
    if($rs){
        //组装充值信息
        switch ($order['pay_type']) {
            case '1': $type = '支付宝';   break;
            case '2': $type = '微信支付'; break;
            case '3': $type = '易宝支付'; break;
            case '4': $type = '移动支付'; break;
        }
        $array = array(
                'user_id'   => $order['user_id'],
                'log_type'  => 8,
                'log_status'=> 1,
                'log_time'  => time(),
                'change_num'=> $order['pay_fee'],
                'total_coin'=> $user['coin'] + $user['unable_coin'] + $order['pay_fee'],
                'desc'      => $type.'充值',
                'platform'  => $order['platform'],
                'pay_way'   => $order['pay_type'],
                'order_id'  => $order['trade_no'],
                'operation_time' => time(),
            );
        //添加充值记录
        $rs2 = M('AccountLog')->add($array);
        if($rs2){
            if($chang_num > 0){
                $carray = array(
                    'user_id'   => $order['user_id'],
                    'log_type'  => 5,
                    'log_status'=> 1,
                    'log_time'  => time(),
                    'change_num'=> $chang_num,
                    'total_coin'=> $user['coin'] + $user['unable_coin'] + $total_coin,
                    'desc'      => '充值赠送',
                    'platform'  => $order['platform'],
                    'pay_way'   => $order['pay_type'],
                    'order_id'  => $order['trade_no'],
                    'operation_time' => time() + 1,
                );
                $crs = M('AccountLog')->add($carray);
                if(!$crs){
                    logRecord("金币添加 FrontUser赠送记录 ：".M()->getLastsql().'====>'.$crs,'logCharge.txt');
                    return false;
                }
            }

            //充值邀请好友既达标
            D('Common')->checkPay($order['user_id'], $order_id);

            //使用优惠券
            if($order['give_coin']){
                D('Common')->useTicket($order['user_id'], $order_id);
            }

            //APP手机首次绑定赠送金币
            if($order['telBind']){
                D('Common')->bindPayCoin($order['user_id'], $order_id);
            }

            return true;
         }else{
            logRecord("金币添加 充值记录 ：".M()->getLastsql().'====>'.$rs2,'logCharge.txt');
            return false;
        }
    }
    else
    {
        logRecord("金币添加 FrontUser添加 ：".M()->getLastsql().'====>'.$rs,'logCharge.txt');
        return false;
    }
}

/**
 * ios内购充值
 * @param  $userId      用户id
 * @param  $bodyString  ios支付票据
 * @return bool
 */
function ExecutiveIosRecharge($userId,$bodyString){
    $sandbox = 0;
    $postUrl= 'https://buy.itunes.apple.com/verifyReceipt';

    //请求验证支付状态
    $receiptData = '{"receipt-data":"'.$bodyString.'"}';

    $return = httpPost($postUrl,$receiptData);
    $data = json_decode($return['data'],true);

    if($data['status'] == 21007){
        //沙河支付测试
        $sandbox = 1;
        $postUrl = 'https://sandbox.itunes.apple.com/verifyReceipt';
        $return = httpPost($postUrl,$receiptData);
        $data = json_decode($return['data'],true);
    }

    //不为0充值失败
    if($data['status'] != 0){
        return $data['status'];
    }

    //成功支付数据
    $in_app = $data['receipt']['in_app'][0];

    //充值金额
    $payCoin = explode('_', $in_app['product_id'])[1];

    //是否开通VIP操作（198为开通VIP）
    $isVip = $payCoin == 198 ? 1 : 0;

    $order_id = $in_app['transaction_id'];
    //查询订单
    $order = M('tradeRecord')->master(true)->where(['trade_no'=>$order_id])->field("trade_no,user_id,pay_fee,trade_state,platform,pay_type,give_coin,telBind")->find();
    if(empty($order)){
        //添加新订单
        $timestamp = $in_app['purchase_date_ms'] / 1000;
        $addArr = [
            'trade_no'    => $order_id,
            'user_id'     => $userId,
            'total_fee'   => $payCoin,
            'pay_fee'     => $payCoin,
            'title'       => '苹果内购充值',
            'description' => $in_app['product_id'],
            'trade_state' => 2,
            'platform'    => 2,
            'ctime'       => $timestamp,
            'etime'       => $timestamp,
            'pay_type'    => 5,
            'pkg'         => I('pkg'),
            'telBind'     => I('telBind',0,'int'),
        ];
        $rs = M('tradeRecord')->add($addArr);
        if(!$rs){
            return 5002;
        }
    }else{
        //已有该订单充值
        return 4018;
    }

    //ios充值配置
    $rechargeConfig = getWebConfig('iosRecharge');

    //查询用户信息
    $user = M('FrontUser')->master(true)->field('coin, unable_coin,vip_time')->find($userId);
    $time = time();

    //判断是否开通vip操作
    if($isVip == 1){
        if(checkVip($user['vip_time']) == 1){
            return 5006;
        }
        //会员开通赠送金币（无需加充值金币）
        $giveCoin = $rechargeConfig['vip_give'];
        $payFee   = $giveCoin;
        //记录会员开通时间与到期时间
        $saveArray['open_viptime'] = strtotime(date(Ymd));
        $yes_time = C('vip_time');
        $saveArray['vip_time'] = strtotime(date(Ymd)) + $yes_time;
    }else{
        //判断是否赠送金币
        $giveCoin = 0;
        foreach($rechargeConfig['recharge'] as $k => $v){
            if(intval($v['account']) == $payCoin){
                $giveCoin = $v['number'];
            }
            $checkPayCoin[] = $v['account'];
        }
        //判断是否有该充值配置
        if(!in_array($payCoin, $checkPayCoin)){
            return 101;
        }
        $payFee = $giveCoin + $payCoin;
    }

    if($payFee > 0){
        $saveArray['unable_coin'] = ['exp','unable_coin+'.$payFee];
    }

    //为用户增加金币
    if (M('FrontUser')->where(['id'=>$userId])->save($saveArray) === false)
        return 5004;

    //充值金币
    if($payFee > 0 && $isVip != 1){
        //添加充值账户明细
        $rs2 = M('AccountLog')->add([
            'user_id'        => $userId,
            'log_type'       => 8,
            'log_status'     => 1,
            'log_time'       => $time,
            'change_num'     => $payCoin,
            'total_coin'     => $user['coin'] + $user['unable_coin'] + $payCoin,
            'desc'           => '苹果内购',
            'platform'       => 2,
            'pay_way'        => $sandbox != '1' ? 5 : 6, //5：正式上架后的内购充值，6：沙盒内购充值
            'order_id'       => $order_id,
            'operation_time' => $time,
        ]);
    }

    if($giveCoin > 0) {
        //添加赠送明细
        $rs3 = M('AccountLog')->add([
            'user_id'        => $userId,
            'log_type'       => 5,
            'log_status'     => 1,
            'log_time'       => $time + 1,
            'change_num'     => $giveCoin,
            'total_coin'     => $user['coin'] + $user['unable_coin'] + $payFee,
            'desc'           => $isVip != 1 ? '苹果内购赠送' : '开通会员VIP赠送',
            'platform'       => 2,
            'pay_way'        => $sandbox != '1' ? 5 : 6, //5：正式上架后的内购充值，6：沙盒内购充值
            'order_id'       => $order_id,
            'operation_time' => $time,
        ]);
    }

    if($isVip == 1 && $giveCoin > 0){
        //添加开通vip系统消息
        $msg = '恭喜您已成为全球体育年费会员，赠送的'.$giveCoin.'金币已经存入您的账户，并尊享各项会员特权，炫酷的玩耍吧。如有疑问，请联系客服。';
        sendMsg($userId,'年费会员开通成功',$msg);
    }
    return ['result'=>1,'payCoin'=>$payCoin];
}

/**
 * 执行充值——测试用
 * @param  $order_id     订单号
 * @return bool
 */
function ExecutiveRechargeTest($order_id){
    if(empty($order_id)){
        return false;
    }

    //查询充值记录是否已充值
    if(M('AccountLog')->master(true)->where(['order_id'=>$order_id])->find()){
        logRecord("ExecutiveRecharge 查询充值记录是否已充值：".$order_id,'logCharge.txt');
        return true;
    }
    //查询订单
    $order = M('tradeRecord')->master(true)->where(['trade_no'=>$order_id])->field("trade_no,user_id,pay_fee,trade_state,platform,pay_type,give_coin,total_fee,telBind")->find();
    $order['trade_state'] = 2;
    if(empty($order) || !in_array($order['trade_state'], [1,2])){
        logRecord("金币添加 查询订单 ：".M()->getLastsql().'====>'.json_encode($order),'logCharge.txt');
        return false;
    }

//    $order['pay_fee'] = (int)$order['pay_fee'];
    $order['pay_fee'] = (int)$order['total_fee'];
    $user = M('FrontUser')->master(true)->field('coin,unable_coin')->find($order['user_id']);
    if(empty($user))
    {
        logRecord("金币添加 用户不存在 ：".M()->getLastsql().'====>','logCharge.txt');
        return false;
    }

    //如果没有赠送金币，则APP充值赠送金币；如果有就表示使用优惠券就不能获得自动赠送金币
    $chang_num = 0;
    if($order['give_coin'] == 0){
        $config = getWebConfig('recharge')['recharge'];//充值配置
        foreach($config as $k => $v){
            if(intval($v['account']) == intval($order['pay_fee'])){
                $chang_num = $v['number'];
                break;
            }
        }
    }

    $total_coin = $order['pay_fee'] + $chang_num;

    //执行充值
    $rs = M('FrontUser')->where(['id'=>$order['user_id']])->save(['unable_coin'=>['exp',"unable_coin+{$total_coin}"]]);
    if($rs){
        //组装充值信息
        switch ($order['pay_type']) {
            case '1': $type = '支付宝';   break;
            case '2': $type = '微信支付'; break;
            case '3': $type = '易宝支付'; break;
            case '4': $type = '移动支付'; break;
        }
        $array = array(
            'user_id'   => $order['user_id'],
            'log_type'  => 8,
            'log_status'=> 1,
            'log_time'  => time(),
            'change_num'=> $order['pay_fee'],
            'total_coin'=> $user['coin'] + $user['unable_coin'] + $order['pay_fee'],
            'desc'      => $type.'充值',
            'platform'  => $order['platform'],
            'pay_way'   => $order['pay_type'],
            'order_id'  => $order['trade_no'],
            'operation_time' => time(),
        );
        //添加充值记录
        $rs2 = M('AccountLog')->add($array);
        if($rs2){
            if($chang_num > 0){
                $carray = array(
                    'user_id'   => $order['user_id'],
                    'log_type'  => 5,
                    'log_status'=> 1,
                    'log_time'  => time(),
                    'change_num'=> $chang_num,
                    'total_coin'=> $user['coin'] + $user['unable_coin'] + $total_coin,
                    'desc'      => '充值赠送',
                    'platform'  => $order['platform'],
                    'order_id'  => $order['trade_no'],
                    'operation_time' => time() + 1,
                );
                $crs = M('AccountLog')->add($carray);
                if(!$crs){
                    logRecord("金币添加 FrontUser赠送记录 ：".M()->getLastsql().'====>'.$crs,'logCharge.txt');
                    return false;
                }
            }

            //充值邀请好友既达标
            D('Common')->checkPay($order['user_id'], $order_id);

            //使用优惠券
            if($order['give_coin']){
                D('Common')->useTicketTest($order['user_id'], $order_id);
            }

            //APP手机首次绑定赠送金币
            if($order['telBind']){
                D('Common')->bindPayCoinTest($order['user_id'], $order_id);
            }

            return true;
        }else{
            logRecord("金币添加 充值记录 ：".M()->getLastsql().'====>'.$rs2,'logCharge.txt');
            return false;
        }
    }
    else
    {
        logRecord("金币添加 FrontUser添加 ：".M()->getLastsql().'====>'.$rs,'logCharge.txt');
        return false;
    }
}

/**
 * 写日志，方便测试（看网站需求，也可以改成把记录存入数据库）
 * 注意：服务器需要开通fopen配置
 * @param $word 要写入日志里的文本内容 默认值：空值
 * @param $file 文件名 默认值：空值
 */
function logRecord($word='', $file = '') {
    if(empty($file))
        $fp = fopen("Public/log/log.txt","a");
    else
        $fp = fopen('Public/log/'.$file,"a");
    flock($fp, LOCK_EX) ;
    fwrite($fp,"执行日期：".strftime("%Y%m%d%H%M%S",time())."\n".$word."\r\n");
    flock($fp, LOCK_UN);
    fclose($fp);
}

/**
 * 获取数据库配置
 * @param  $sign   配置标识
 * @return array
 */
function getWebConfig($sign)
{
    if(!isset($sign)){
        return false;
    }
    if(!is_array($sign))
    {
        $config = M('config')->where(['sign'=>$sign])->find();
        return json_decode($config['config'],true);
    }
    else
    {
        $config = M('config')->where(['sign'=>['in',$sign]])->select();
        $configArr = [];
        foreach ($config as $k => $v) {
            $configArr[$v['sign']] = json_decode($v['config'],true);
        }
        return $configArr;
    }
}

/**
 * 发送系统消息
 * @param  $user_id   用户id
 * @param  $title     消息标题
 * @param  $content   消息内容
 * @param  $param     更多参数，根据字段key => value
 * @return bool
 */
function sendMsg($user_id,$title,$content,$param=array())
{
    //单个用户转为数组处理兼容
    if(!is_array($user_id))
        $user_id = [$user_id];

    //用户数组
    $Msg = [];
    foreach ($user_id as $k => $v) {
        $Msg[$k]['front_user_id'] = $v;
        $Msg[$k]['title']     = $title;
        $Msg[$k]['content']   = $content;
        $Msg[$k]['send_time'] = NOW_TIME;
        if(!empty($param)){
            //更多参数
            foreach ($param as $kk => $vv) {
                $Msg[$k][$kk] = $vv;
            }
        }
    }
    $rs = M('Msg')->addAll($Msg);
    if($rs){
        $redis  = connRedis();
        //推送mqtt
        foreach ($user_id as $k => $v) {
            //mqtt推送提示
            $opt = [
                'topic'    => 'qqty/api500/'.$v.'/system_notify',
                'payload'  => ['status' => 1, 'data' => ['newMsg' => 1], 'randKey' => $k.rand(0, 1000)],
                'clientid' => md5(time() . $v),
                'qos'      => 1
            ];
            $data = json_encode($opt);
            $redis->lPush('mqtt_common_push_queue', $data);
        }
        return true;
    }
    return false;
}

/**
* oddsChStr  赔率格式转换————转字符串
*/
function oddsChStr($arr)
{
    if(empty($arr) || !is_array($arr)) return false;

    $value = [];
    foreach($arr as $k=>$v)
    {
        $str = implode(',',$v);
        $value[] = $str;
    }
    return implode('^',$value);
}

/**
* oddsChArr  赔率格式转换————转数组
*/
function oddsChArr($str = '')
{
    if(empty($str))
    {
        $str = ',,,,,,,,^,,,,,,,,^,,,,,,,,^,,,,,,,,^,,,,,,,,^,,,,,,,,';
    }

    $arr = explode('^',$str);
    $oddsTemp = [];
    foreach($arr as $k=>$v)
    {
        $oddsTemp[] = explode(',',$v);
    }

    return $oddsTemp;
}

/**
* oddsChStrBk  赔率格式转换————转字符串(篮球)
*/
function oddsChStrBk($arr)
{
    if(empty($arr) || !is_array($arr)) return false;

    $value = [];
    foreach($arr as $k=>$v)
    {
        $str = implode(',',$v);
        $value[] = $str;
    }
    return implode('^',$value);
}

/**
* oddsChArrBk  赔率格式转换————转数组(篮球)
*/
function oddsChArrBk($str = '')
{
    if(empty($str))
    {
        $str = ',,,,,,,,^,,,,,,,,^,,,,,^,,,,,,,,^,,,,,,,,^,,,,';
    }

    $arr = explode('^',$str);
    $oddsTemp = [];
    foreach($arr as $k=>$v)
    {
        $oddsTemp[] = explode(',',$v);
    }

    return $oddsTemp;
}

//格式化打印数组
function pr($arr)
{
    echo '<pre>';
    print_r($arr);
    echo '</pre>';
}

/**
 * 计算近十场的胜率
 * @param $arr 近十场结果的数组
 * @param int  $playType  玩法，1：亚盘；2：竞彩，默认亚盘
 * @return int
 */
function countTenGambleRate($arr,$playType=1,$type=1){
    $num = 0;

    if($playType == 1){//亚盘
        foreach($arr as $v){
            if($v == 1 || $v == 0.5){
                $num++;
            }
        }
    }else if($playType == 2){//竞彩
        foreach($arr as $v){
            if($v == 1){
                $num++;
            }
        }
    }

    if($type == 1){
        return intval($num/10*100);
    }else if($type == 2){
        return intval($num);
    }else if($type == 3){
        return '近10中'.intval($num);
    }
}

function getLivezillaUrl(){
    //客服系统
    $liveChatUrl = 'http://m.customer.qqty.com/#/m/online?a=95249&amp;el=emgtY24_';
    // https://kf.qqty.com/chat.php
    if($id = is_login()){
        $UserData = session('user_auth');
        $liveChatUrl .= '&name='.$UserData['nick_name'].'&tel='.$UserData['username'];
    }
    return $liveChatUrl;
}

/**
 * 敏感词处理
 * @param $sign
 * @param string $input             //匹配的内容
 * @param bool|false $retMatches    //返回第一个匹配到的字符
 * @param bool|false $retFilter     //返回过滤后的内容
 * @return bool|mixed
 */
function matchFilterWords($sign,$input='',$retMatches = false, $retFilter = false)
{
    $keyArrs    = getWebConfig($sign);
    if($retFilter){
        $implode    = implode('|',$keyArrs);
        $pattern    = preg_replace('/([\\\*\.\?\+\$\^\[\]\(\)\{\}\=\/\-%])/','\\\$1', $implode);
        $pattern2   = '/' . $pattern . '/i';
//        echo $pattern2;exit;
        $filter_content = preg_replace($pattern2, '***', $input);
        return $filter_content;
    }else{
        $chunks = array_chunk($keyArrs, 1000);
        for($i=0; $i<count($chunks); $i++){
            $implode    = implode('|',$chunks[$i]);
            $pattern    = preg_replace('/([\\\*\.\?\+\$\^\[\]\(\)\{\}\=\/\-%])/','\\\$1', $implode);

            if(preg_match('/' . $pattern . '/i', $input, $matches))
                return $retMatches ? $matches[0] : false;
        }

        return true;
    }
}

//按价格随机返回商品描述
function getTradeBody($fee)
{
    $tBody = ['曼联钥匙扣','切尔西不锈钢挂扣','利物浦钥匙扣','阿森纳开瓶器','巴塞罗那不锈钢挂扣','皇马钥匙扣','AC米兰不锈钢挂扣','国际米兰钥匙扣','罗马开瓶器','曼城钥匙扣'];
    return $fee.'元'.$tBody[array_rand($tBody)];
}

/**
 * 生成二维码
 * @param string $url 网址
 * @param boolean $outfile 生成文件名
 * @param int $level 容错级别
 * @param int $size 图片大小
 */
function qrcode($url='http://www.qqty.com/', $outfile = false, $level=3, $size=4)
{
    Vendor('phpqrcode.phpqrcode');
    $errorCorrectionLevel = intval($level) ;//容错级别
    $matrixPointSize = intval($size);//生成图片大小

    //生成二维码图片
    return QRcode::png($url, $outfile, $errorCorrectionLevel, $matrixPointSize, 2);
}

/**
 * 昨日用户的竞猜统计
 * @param $userid
 * @param int $gambleType
 * @return array
 */
function ydayGambleRate($userid,$gambleType = 1){
    $blockTime = getBlockTime(1, $gamble = true);
    //计算昨天的胜率
    $where = [
        'user_id'      => $userid,
        'create_time'  => ['between',[$blockTime['beginTime']-86400, $blockTime['endTime']-86400]],
        'result'       => ['NEQ', 0],
        'play_type'    => $gambleType == 2 ? ['IN',[2, -2]] : ['IN',[1, -1]],
    ];

    $gameArray = M('gamble')->where($where)->select();

    //计算昨日胜率
    $win = $half = $transport = $donate = $level = $pointCount = $curr_victs = 0;
    foreach ($gameArray as $vv)
    {
        if($vv['result'] == '1')     $win++;
        if($vv['result'] == '0.5')   $half++;
        if($vv['result'] == '-1')    $transport++;
        if($vv['result'] == '-0.5')  $donate++;
        if($vv['result'] == '2')     $level++;
        if($vv['earn_point'] > 0)    $pointCount += $vv['earn_point'];
        if($vv['result'] == 1 || $vv['result'] == 0.5)     $curr_victs++;

    }

    $winrate = getGambleWinrate($win,$half,$transport,$donate);

    return array(
        "winrate"    =>  $winrate,
        'win'        =>  $win + $half,
        'level'      =>  $level,
        'transport'  =>  $transport + $donate,
        'pointCount' =>  $pointCount,
        'curr_victs'=>  $curr_victs,
    );
}

/**
 * 根据用户的竞猜计算胜率等详情
 * @param $gambleArr
 * @param $gameType
 * @return array
 */
function getGambleRate($gambleArr, $gameType)
{
    //统计每个用户胜率
    $win = $half = $level = $transport = $donate = $pointCount = $gameCount = 0;
    foreach ($gambleArr as $k => $v) {
        $results    = explode(',', $v['result']);
        $points     = explode(',', $v['earn_point']);

        foreach ($results as $kk => $result) {
            if ($result == '1')     $win++;
            if ($result == '0.5')   $half++;
            if ($result == '2')     $level++;
            if ($result == '-1')    $transport++;
            if ($result == '-0.5')  $donate++;
            if ($result > 0)        $pointCount += $points[$kk];
        }

        $winrate = getGambleWinrate($win,$half,$transport,$donate);

        //包括小数点的胜率(用于实际排序)
        $winrateTwo = getGambleWinrateTwo($win,$half,$transport,$donate);

        $userRateArr[] = [
            'user_id'   => $v['user_id'],
            'winrate'   => $winrate,
            'winrateTwo'=> $winrateTwo,
            'win'       => $win,
            'half'      => $half,
            'level'     => $level,
            'transport' => $transport,
            'donate'    => $donate,
            'pointCount'=> $pointCount,
            'gameCount' => count($results),
            'gameType'  => $gameType,
        ];
        $win = $half = $level = $transport = $donate = $pointCount = $gameCount = 0;
    }
    return $userRateArr;
}

/**
 * cutstr    截取字符串
 * @param $content   待截取字符串
 * @param $start     开始字符串
 * @param $end       结束字符串
 */
function cutstr($content, $start, $end)
{
    $p1 = 0;
    $p2 = 0;
    $len = strlen($start);
    if(false === ($p1 = strpos($content, $start)))
    {
        return "";
    }
    if($end == '')
    {
        return substr($content, $p1 + $len);
    }
    else
    {
        if(false === ($p2 = strpos($content, $end, $p1 + $len)))
        {
            return "";
        }
        return substr($content, $p1 + $len, $p2 - $p1 - $len);
    }
}

/**
 * 记录增删改操作日志方法
 * @return boolean
 */
function operationLog()
{
    if(!$config = S('admin_Request_config'))
    {
        $config = getWebConfig('adminRequest');
        S('admin_Request_config',$config,300);
    }
    //只记录后台操作
    if (MODULE_NAME == 'Admin' && $config['adminLogList'] != '' && ($config['adminLogList'] == 1 || (!in_array(CONTROLLER_NAME, explode(',', $config['adminLogList'])) && !in_array(CONTROLLER_NAME.'/'.ACTION_NAME, explode(',', $config['adminLogList'])))))
    {
        $_GET['HTTP_REFERER'] = $_SERVER['HTTP_REFERER'];
        $sql = " INSERT INTO `qc_admin_request` (`user_id`, `last_ip`,`request`,`response`,`request_time`,`response_time`,`module`,`controller`,`action`)
                VALUES ('{$_SESSION['authId']}','".get_client_ip()."','".json_encode(I())."', '".$_SERVER['HTTP_USER_AGENT']."', '".NOW_TIME."', '".NOW_TIME."', '".MODULE_NAME."', '".CONTROLLER_NAME."', '".ACTION_NAME."') ";

        $result = M()->execute($sql); //执行sql
        return $result;
    }

    return false;
}

/**
 * 重写M方法
 * @param string $name
 * @param string $tablePrefix
 * @param string $connection
 * @return mixed
 */
function _M($name='', $tablePrefix='',$connection='') {
    if(strpos($name,':')) {
        list($class,$name)    =  explode(':',$name);
    }else{
        $class      =   'Think\\Model';
    }
    return new $class($name,$tablePrefix,$connection);

}

/**
 * 格式化赔率
 * @param string $str
 * @return mixed
 */
function formatExp($str ='')
{
    if($str == '') return '';
    $str = round($str,2);
    $str = floatval($str);
    return (string)$str;
}

/**
 * 删除空格
 * @param string $str
 * @return mixed
 */
function trimall($str)
{
    $qian=array(" ","　","\t","\n","\r");
    $hou=array("","","","","");
    return str_replace($qian,$hou,$str);
}


/**
 * 执行充值——测试用
 * @param  $order_id     订单号
 * @return bool
 */
function ExecutiveTest($order_id){
    if(empty($order_id)){
        return false;
    }

    //查询充值记录是否已充值
    if(M('AccountLog')->master(true)->where(['order_id'=>$order_id])->find()){
        logRecord("ExecutiveRecharge 查询充值记录是否已充值：".$order_id,'logCharge.txt');
        return true;
    }
    //查询订单
    $order = M('tradeRecord')->master(true)->where(['trade_no'=>$order_id])->field("trade_no,user_id,pay_fee,trade_state,platform,pay_type,total_fee")->find();
    $order['trade_state'] = 1;
    if(empty($order) || !in_array($order['trade_state'], [1,2])){
        logRecord("金币添加 查询订单 ：".M()->getLastsql().'====>'.json_encode($order),'logCharge.txt');
        return false;
    }

//    $order['pay_fee'] = (int)$order['pay_fee'];
    $order['pay_fee'] = (int)$order['total_fee'];
    $user = M('FrontUser')->master(true)->field('coin,unable_coin')->find($order['user_id']);
    if(empty($user))
    {
        logRecord("金币添加 用户不存在 ：".M()->getLastsql().'====>','logCharge.txt');
        return false;
    }

    //APP充值赠送金币
    $config = getWebConfig('recharge')['recharge'];//充值配置
    $chang_num = 0;
    foreach($config as $k => $v){
        if(intval($v['account']) == intval($order['pay_fee'])){
            $chang_num = $v['number'];
            break;
        }
    }

    $total_coin = $order['pay_fee'] + $chang_num;

    //执行充值
    $rs = M('FrontUser')->where(['id'=>$order['user_id']])->save(['unable_coin'=>['exp',"unable_coin+{$total_coin}"]]);
    if($rs){
        //组装充值信息
        switch ($order['pay_type']) {
            case '1': $type = '支付宝';   break;
            case '2': $type = '微信支付'; break;
            case '3': $type = '易宝支付'; break;
            case '4': $type = '移动支付'; break;
        }
        $array = array(
            'user_id'   => $order['user_id'],
            'log_type'  => 8,
            'log_status'=> 1,
            'log_time'  => time(),
            'change_num'=> $order['pay_fee'],
            'total_coin'=> $user['coin'] + $user['unable_coin'] + $order['pay_fee'],
            'desc'      => $type.'充值',
            'platform'  => $order['platform'],
            'pay_way'   => $order['pay_type'],
            'order_id'  => $order['trade_no'],
            'operation_time' => time(),
        );
        //添加充值记录
        $rs2 = M('AccountLog')->add($array);
        if($rs2){
            if($chang_num > 0){
                $carray = array(
                    'user_id'   => $order['user_id'],
                    'log_type'  => 5,
                    'log_status'=> 1,
                    'log_time'  => time(),
                    'change_num'=> $chang_num,
                    'total_coin'=> $user['coin'] + $user['unable_coin'] + $total_coin,
                    'desc'      => '充值赠送',
                    'platform'  => $order['platform'],
                    'order_id'  => $order['trade_no'],
                    'operation_time' => time() + 1,
                );
                $crs = M('AccountLog')->add($carray);
                if(!$crs){
                    logRecord("金币添加 FrontUser赠送记录 ：".M()->getLastsql().'====>'.$crs,'logCharge.txt');
                    return false;
                }
            }
            //充值邀请好友既达标
            D('Common')->checkPay($order['user_id'], $order_id);

            return true;
        }else{
            logRecord("金币添加 充值记录 ：".M()->getLastsql().'====>'.$rs2,'logCharge.txt');
            return false;
        }
    }
    else
    {
        logRecord("金币添加 FrontUser添加 ：".M()->getLastsql().'====>'.$rs,'logCharge.txt');
        return false;
    }
}

/**
 * utf8字符窜按字节数过滤
 * @param string $content 源字串
 * @param int $bytes  要过滤掉的字节数，过滤中文则为3，过滤emoji表情则为4
 * @return mixed
 */
function utf8_filter($content='', $bytes = 4){
    $newstring = preg_replace_callback('/./u', function($match) use ($bytes){
        return strlen($match[0]) >= $bytes ? '' : $match[0];
    }, $content);

    return $newstring;
}

/**
 * App资讯文章内容https兼容替换
 * @param string  $content   文章内容
 * @param int $is_server     是否为图片服务器
 * @return mixed
 */
function http_to_https($content, $is_server = 0)
{
    if(SITE_URL == 'https://')
    {
        if($is_server == 1)
        {
            $IMG_SERVER = str_replace(['http://', 'https://'], '', C('IMG_SERVER'));
            $news = htmlspecialchars_decode($content);
            $res  = str_replace("http://".$IMG_SERVER, SITE_URL.$IMG_SERVER, $news);
        }
        else
        {
            $res  = str_replace('http://', 'https://', $content);
        }
        return $res;
    }
    return $content;
}

/**
 * 获得全部外链
 */
function getOutsideChain(){
    $res = M()->field('m_url as url')
            ->table('qc_highlights')
            ->where("`m_ischain` = 1 AND `status` = 1 AND `m_url` != ''")
            ->union("SELECT web_url as url from qc_highlights where `web_ischain` = 1 AND `status` = 1 AND `web_url` != ''", true)
            ->union("SELECT app_url as url from qc_highlights where `app_ischain` = 1 AND `status` = 1 AND `app_url` != ''", true)
            ->select();

    $url = [];
    foreach($res as $k => $v){
        $url[] = explode('/', $v['url'])[2];
    }

    return $url ? array_values(array_unique($url)) : [];
}

/**
 * 中文替换乱码
 * @param string $str
 * @param string $charset
 * @return string
 */
function sub_str($str, $charset = 'utf-8'){
    $re['utf-8']  = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
    $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
    $re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
    $re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
    preg_match_all($re[$charset], $str, $match);

    return join("", $match[0]);
}

/**
 * 生成推送消息队列
 * @param $userid            // 用户id数组 [1,2,3,4]
 * @param $message          //消息内容
 * @param int $platform     // 平台类型 0：两个都发  2：ios   3:安卓，根据接口对应
 * @param $module           // 1进入资讯；2进入图集；9打开外链；10进入个人中心；11进入足球赛事详情，12进入篮球赛事详情，13进入帖子详情；14进入个人系统通知。15进入产品详情
 * @param $module_value     //对应module的值，比如外链时，填链接
 * @param string $showType  //IOS有效，默认1
 * @return bool
 */
function addMessageToQueue($userids, $message, $platform=0, $module, $module_value, $showType='1')
{
    if(!is_array($userids)){
        $users[] = $userids;
    }else{
        $users = $userids;
    }

    $redis = ConnRedis();

    switch($platform){
        case '0'://友盟队列&APNS队列
            foreach($users as $k => $userid){
                //APNS队列
                $ApnsUser = M('ApnsUsers')->where(['user_id' => $userid])->find();
                if($ApnsUser && $ApnsUser['cert_no']){
                    $data = json_encode([
                        'device_token' => $ApnsUser['device_token'],
                        'message' => $message,
                        'module' => $module,
                        'module_value' => $module_value,
                        'show_type' => $showType,
                        'cert_no' => $ApnsUser['cert_no']
                    ]);
                    $redis->rpush('message_queue_2', $data);
                }else{
                    $data = json_encode([
                        'users' => $userid,
                        'message' => $message,
                        'module' => $module,
                        'module_value' => $module_value,
                        'show_type' => $showType
                    ]);
                    $redis->rpush('message_queue_4', $data);
                }
            }

            $arr = array_chunk($users, 50);
            foreach ($arr as $k => $v){
                $data = json_encode(['alias' => $v, 'message' => $message, 'module' => $module, 'module_value' => $module_value]);
                $redis->rpush('message_queue_3', $data);
            }

            break;

        case '2':
            foreach($users as $k => $userid){
                $ApnsUser = M('ApnsUsers')->where(['user_id' => $userid])->find();
                if($ApnsUser &&  $ApnsUser['cert_no']){
                    $data = json_encode([
                        'device_token' => $ApnsUser['device_token'],
                        'message' => $message,
                        'module' => $module,
                        'module_value' => $module_value,
                        'show_type' => $showType
                    ]);
                    $redis->rpush('message_queue_2', $data);
                }else{
                    $data = json_encode([
                        'users' => $userid,
                        'message' => $message,
                        'module' => $module,
                        'module_value' => $module_value,
                        'show_type' => $showType
                    ]);
                    $redis->rpush('message_queue_4', $data);
                }
            }
            break;

        case '3'://友盟队列
            $arr = array_chunk($users, 50);
            foreach ($arr as $k => $v){
                $data = json_encode(['alias' => $v, 'message' => $message, 'module' => $module, 'module_value' => $module_value]);
                $redis->rpush('message_queue_3', $data);
            }
            break;

        default:
            return false;
    }
    return true;

}

/**
 * 截取小数，默认截取两位
 * @param $num
 * @param int $length
 * @return string
 */
function getFloatNumber($num, $length=2){
    $temp = explode('.', $num);
    //获取小数的位数
    if (sizeof($temp) > 1){
        $decimal = end($temp);
        $count = strlen($decimal);

        //截取
        if($count > $length){
            $end = $count - $length;
            return substr($num, 0, strlen($num)-$end);
        }
    }

    return $num;
}

/**
 * 资讯和图库增加默认点击量
 * @param int $type     类型
 * @param int $class_id 资讯id
 * @param $num int      当前点击量
 * @param $publish_id int 当前资讯id
 * @param $add_num    int 需要增加的点击量
 * @return string
 */
function addClickConfig($type=0, $class_id=0, $num=0, $publish_id=0, $add_num=5){
    if($publish_id){
        return strval($num*$add_num + substr($publish_id, -2, 2));
    }else{
        $conf = C('clickConfig');

        if($type == 1){
            if($conf[$type][$class_id])
                return strval($num * $conf[$type][$class_id][0] + $conf[$type][$class_id][1]);
        }else if($type == 2){
            if($conf[$type])
                return strval($num + $conf[$type][0] + $conf[$type][1]);
        }

        return strval($num);
    }

}

//表单参数验证生成，预防CSRF攻击
function get_form_token($isReturn=false) {
    $token = session('token');
    if(!$token){
        $token = md5(uniqid(rand(), TRUE));
        session('token',$token);
    }
    if($isReturn) return $token;
    echo "<input type='hidden' value='".$token."' name='token'>";
}

//表单参数验证，预防CSRF攻击
function check_form_token() {
    $token   = I('token');
    $session_token = session('token');
    if($token == '' || $session_token == '') return 0;
    if($token != $session_token) return 0;
    return 1;
}

/**
 * mqtt订阅、发布
 * @param $opt
 * @param int $type
 */
function Mqtt($opt,$type=1){
    import('Vendor.mqtt.Mqtt');
    $mqtt = Mqtt::getInstance();
    $options = array_merge(C('MQTT'), $opt);
    $mqtt->broker($options['host'], $options['port'], $options['clientid']);
    $qos = (int)$options['qos'];
    $payload = is_array($options['payload']) ? json_encode($options['payload']) : $options['payload'];
    if(!$mqtt->connect(true, NULL, 'mqtt_appclient_nologin', 'mqtt_appclient_nologin'))
        exit(1);

    if($type==2){//subscribe
        $topics[$options['topic']] = array("qos"=>$qos, "function"=>"procmsg");
        $mqtt->subscribe($topics,$qos);

        while($mqtt->proc()){
        }

        $mqtt->close();

        function procmsg($topic,$msg){
            echo $topic,$msg;
        }

    }else{//publish
        $mqtt->publish($options['topic'], $payload, 0);
        $mqtt->close();
    }

}

/**
 * @param $opt
 */
function mqttPub($opt)
{
    $options = array_merge(C('MQTT'), $opt);
    $client = new Mosquitto\Client(md5(rand(0,9999).time()));
    $client->onConnect(function ($code, $message) use ($client, $options) {
        $payload = is_array($options['payload']) ? json_encode($options['payload']) : $options['payload'];
        $client->publish($options['topic'], $payload, $options['qos']);
        $client->disconnect();
    });

    $client->setCredentials('mqtt_appclient_nologin', 'mqtt_appclient_nologin');
    $client->connect($options['host']);

    $client->loopForever();
}

/**
 * 计算概率
 * 返还率：D = A*B*C /（A*B+B*C+A*C），A：胜；B：平；C：负；
 * 胜的概率 = 返还率 / 胜赔
 * 平的概率 = 返还率 / 平赔
 * 负的概率 = 返还率 / 负赔
 */
function calculateRate($a, $b, $c){
    if(empty($a) || empty($b) || empty($c)) return false;
    $rate = $a*$b*$c/($a*$b + $b*$c + $a*$c);

    $arate = round($rate / $a, 2) * 100;//胜
    $brate = round($rate / $b, 2) * 100;//平
    $crate = round($rate / $c, 2) * 100;//负

    return [$arate, $brate, $crate];
}

/**
 * 返回mongo对象(thinkphp)
 * @param $name
 * @param null $tablePrefix
 * @param string $connection
 * @return mixed
 */
function mongo($name, $tablePrefix=null, $connection='DB_MONGO'){
    $class = 'Think\\MongoModel';
    $db = new $class($name,$tablePrefix,$connection);
    return $db;
}

/**
 * 返回mongo对象
 * @return obj
 */
function mongoService($config=''){
    $mService = new \Common\Services\MongodbService($config);
    return $mService;
}

/**
 * 使用原生mongo
 */
function mongoNative(){
    $DBconfig = C('DB_MONGO');
    if($DBconfig['DB_USER'] != '' && $DBconfig['DB_PWD'] != ''){
        $server = sprintf("mongodb://%s:%s@%s:%s/%s", $DBconfig['DB_USER'], $DBconfig['DB_PWD'], $DBconfig['DB_HOST'], $DBconfig['DB_PORT'], $DBconfig['DB_NAME']);
    }else{
        $server = sprintf("mongodb://%s:%s", $DBconfig['DB_HOST'], $DBconfig['DB_PORT']);
    }
    $m = new \MongoClient($server, array('connect'=>true));

    return $m->selectDB($DBconfig['DB_NAME'])->selectCollection("fb_team");

}

/**
 * 获取聊天记录
* @param int $game_type
* @param $game_id
* @param int $limit
* @param int $start
* @return array
 */
function chatLogs($game_type = 1, $game_id, $limit = 100, $start = 0){
    $room_id = 'qqty_chat_' . $game_type . '_' . $game_id;

    $redis = connRedis();
    $temp_log = $redis->lRange($room_id, $start, $limit);
    $members = $redis->sMembers('qqty_chat_forbid_userids');

    //聊天记录处理
    $chat_log = [];
    foreach ($temp_log as $k => $v) {
        $log = json_decode($v, true);
        if (in_array($log['user_id'], $members)) {
            unset($temp_log[$k]);
        } else {
            $userids[] = $log['user_id'];
            $chat_log[] = json_decode($v, true);;
        }
    }

    return $chat_log;
}

/**
 * 检查远程图片或文件或url是否存在
 * @param $url
 * @return bool
 */
function remoteFileExists($url, $isHttps=false)
{
    $curl = curl_init($url);
    // 不取回数据
    curl_setopt($curl, CURLOPT_NOBODY, true);
    // 发送请求
    if($isHttps){
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    }

    $result = curl_exec($curl);
    $found = false;
    // 如果请求没有发送失败
    if ($result !== false) {
        // 再检查http响应码是否为200
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($statusCode == 200) {
            $found = true;
        }
    }

    curl_close($curl);
    return $found;
}

//检查是否ip黑名单
function checkShieldIp(){
    $ip = get_client_ip();
    $ShieldIp = M('ShieldIp')->getField('ip',true);
    if(in_array($ip, $ShieldIp)){
        return true;
    }
    return false;
}

//使用curl的post请求获取数据
function httpPost($url, $postData, $header = [])
{
    $ch = curl_init();

    //是否添加HTTPHEADER
    if(!empty($header)){
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    }

    curl_setopt($ch, CURLOPT_URL, $url); //设置访问路径
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 将结果缓冲，不立刻输出
    curl_setopt($ch, CURLOPT_TIMEOUT, 20); //20秒超时
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

    //如果是https请求，不验证证书和
    if (stripos($url, "https://") !== FALSE) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);// https请求 不验证证书和hosts
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
    }

    $curlPost = is_array($postData) ? http_build_query($postData) : $postData;
    curl_setopt($ch, CURLOPT_POST, 1);  //是否为post方式
    curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);  //post 数据

    $data    = curl_exec($ch);
    $status  = curl_getinfo($ch);
    $error   = curl_error($ch);
    curl_close($ch);
    return ['http_code' => $status['http_code'], 'error' => $error, 'data' => $data];
}

//赔率格式转换 平手 => 0.25
function changeSnExp($str)
{
    if($str == '' || $str == null) return '';

    $sn = C('score_cnn2');
    $minus = '';
    if(strpos($str,"受让") !== false)
    {
        $temp = str_replace("受让","",$str);
        $minus = '-';
    }
    else
    {
        if(strpos($str,"受") !== false)
        {
            $temp = str_replace("受","",$str);
            $minus = '-';
        }
        else
        {
            $temp = $str;
        }
    }

    if(strpos($temp,'/') !== false)
    {
        $arr = explode('/',$temp);
        return $minus.($sn[$arr[0]]+$sn[$arr[1]])/2;
    }
    else
    {
        return $minus.$sn[$temp];
    }
}

//赔率格式转换 平半 => 0/0.5
function changeSnExpTwo($str)
{
    if($str == '' || $str == null) return '';

    if (preg_match('/\d+/', $str)) {
        if(empty($str) || $str == '-0' || $str == '0') return '0';

        if (preg_match('/\//', $str) && !(preg_match('/-/', $str))) {
            return $str;
        }

        if (preg_match('/\//', $str) && (preg_match('/-/', $str))) {
            $reStr = str_replace("-","",$str);
            return "-".$reStr;
        }

        if(strpos($str,"-") !== false) {
            $temp = str_replace("-","",$str);
            $res = floatval($temp);

            if(strpos($res,'.25') !== false || strpos($res,'.75'))
            {
                $res =  '-'.($res-0.25) .'/'.(($res+0.25));
                return $res;
            }
            return "-".$res;
        }
        else
        {
            $res = floatval($str);

            if(strpos($res,'.25') !== false || strpos($res,'.75'))
            {
                $res =  ($res-0.25) .'/'.(($res+0.25));
            }
            return (string)$res;
        }
    }

    if (preg_match('/(\/)/',$str) && !(preg_match("/([\x{4e00}-\x{9fa5}])/u", $str))) return $str;

    $sn = C('score_cnn2');
    $minus = '';
    if(strpos($str,"受让") !== false)
    {
        $temp = str_replace("受让","",$str);
        $minus = '-';
    }
    else
    {
        if(strpos($str,"受") !== false)
        {
            $temp = str_replace("受","",$str);
            $minus = '-';
        }
        else
        {
            $temp = $str;
        }
    }

    if(strpos($temp,'/') !== false)
    {
        $arr = explode('/',$temp);
        return $minus.$sn[$arr[0]].'/'.$sn[$arr[1]];
    }
    else
    {
        return $minus.$sn[$temp];
    }
}

//赔率格式转换 0/0.5=>0.25
function changeExpStrToNum($str)
{
    if($str == '' || $str == null) return '';
    if(empty($str) || $str == '-0' || $str == '0') return '0';
    if(strpos($str,'/') === false) return $str;
    if(strpos($str,"-") !== false)
    {
        $temp = str_replace("-","",$str);
        $arr = explode('/',$temp);
        return -($arr[0]+$arr[1])/2;
    }
    else
    {
        $arr = explode('/',$str);
        return ($arr[0]+$arr[1])/2;
    }
}

/**
* 判断盘路
* @param $score  比分 格式  1-1
* @param $handcp 盘口
* @param $type   类型 1:亚盘 2:大小
* @param $is_home 主队基准
* @return array
*/
function getHandcpWin($score,$handcp,$type=1,$is_home=1)
{
    if($score == '' || $handcp == '') return '';
    //盘口转为数字格式
    $handcp = changeExpStrToNum($handcp);
    //比分
    $score  = explode('-', $score);
    if($type == 1){
        //计算赢走输 以初盘盘口做判断
        if($is_home == 1){
            //主-客>盘口=赢; 主-客<盘口=输; 主-客=盘口=走
            $winType = $score[0] - $score[1] > $handcp ? '赢' : ($score[0] - $score[1] < $handcp ? '输' : '走');
        }else{
            //主-客>盘口=输; 主-客<盘口=赢; 主-客=盘口=走
            $winType = $score[0] - $score[1] > $handcp ? '输' : ($score[0] - $score[1] < $handcp ? '赢' : '走');
        }
    }else{
        //计算大走小（以初盘盘口做判断 主+客>盘口=大; 主+客<盘口=小; 主+客=盘口=走）
        $winType = $score[0] + $score[1] > $handcp ? '大' : ($score[0] + $score[1] < $handcp ? '小' : '走');
    }
    return $winType;
}

/**
* Monitoring  监控时间和内存
*/
function monitoring($str)
{
    echo "<br>*****************************".$str."*******************************<br>";
    echo date('Y-m-d H:i:s')."<br>";
    echo memory_get_usage()."<br>";
    echo "<br>*****************************".$str."*******************************<br>";
}

/**
* 资讯封面图片处理
* $v 资讯数据  （必须有img content class_id 字段）
* @return array
*/
function newsImgReplace($v){
    $img = $v['img'];
    $content = $v['content'];
    $class_id = $v['class_id'];
    if(!empty($img)){
        $img = imagesReplace($img);
    }else{
        if(in_array($class_id, [10,54,55,62])){
            $img = staticDomain('/Public/Home/images/index/164x114.jpg');
        }else{
            $img = staticDomain('/Public/Images/defalut/newsimg.jpg');
        }
        //获取内容第一张图片
        //$img = Think\Tool\Tool::getTextImgUrl(htmlspecialchars_decode($content),false)[0] ?:'/Public/Home/images/index/164x114.jpg';
    }
    return $img;
}

/**
 * 获取资讯分类tree数组
 * @param int $tree 是否tree  默认是
 * @return array|bool|mixed|string
 */
function getPublishClass($tree = 1){
    if(!$PublishClass = S('cache_publish_class')){
        //获取分类
        $PublishClass = M('PublishClass')->where("status=1")->select();

        S('cache_publish_class',$PublishClass);
    }
    if($tree == 1){
        //引用Tree类
        $PublishClass = list_to_tree($PublishClass);
        return $PublishClass;
    }else{
        $data = [];
        foreach ($PublishClass as $k => $v) {
            $data[$v['id']] = $v;
        }
        return $data;
    }
}

/**
 * 获取视频分类tree数组
 * @param string $tree 是否tree  默认是
 */
function getVideoClass($tree = 1){
    if(!$VideoClass = S('cache_video_class')){
        //获取分类
        $VideoClass = M('HighlightsClass')->where("status=1")->select();

        S('cache_video_class',$VideoClass);
    }
    if($tree == 1){
        //引用Tree类
        $VideoClass = list_to_tree($VideoClass);
        return $VideoClass;
    }else{
        $data = [];
        foreach ($VideoClass as $k => $v) {
            $data[$v['id']] = $v;
        }
        return $data;
    }
}

/**
 * 获取产品分类数组
 * @param string $tree 是否tree  默认是
 */
function getIntroClass($tree = 1){
    if(!$IntroClass = S('cache_Intro_class')){
        //获取分类
        $IntroClass = M('IntroProducts')->where("status=1")->select();

        S('cache_Intro_class',$IntroClass);
    }

    $data = [];
    foreach ($IntroClass as $k => $v) {
        $data[$v['id']] = $v;
    }
    return $data;
}

/**
 * 获取图库分类数组
 * @param string $tree 是否tree  默认是
 */
function getGalleryClass($tree = 1){
    if(!$GalleryClass = S('cache_Gallery_class')){
        //获取分类
        $GalleryClass = M('galleryClass')->where("status=1")->select();

        S('cache_Gallery_class',$GalleryClass);
    }

    if($tree == 1){
        //引用Tree类
        $GalleryClass = list_to_tree($GalleryClass);
        return $GalleryClass;
    }else{
        $data = [];
        foreach ($GalleryClass as $k => $v) {
            $data[$v['id']] = $v;
        }
        return $data;
    }
}

/**
* 组装资讯域名链接
* @param $id  资讯id
* @param $time 资讯时间
* @param $class_id 资讯分类
* @param $classArr 资讯分类数组
* @return string
*/
function newsUrl($id,$time,$class_id,$classArr){
    $newsClass   = $classArr[$class_id];
    //不是顶级时获取顶级
    $parentClass = $newsClass['pid'] != 0 ? $classArr[$newsClass['pid']] : $newsClass;
    if($parentClass['pid'] == 0){
        $domain = $parentClass['domain'] ? : 'www'; //二级域名
        $date = date('Ymd', $time);//日期
        $path = $newsClass['path'] ? : 'news';
        $url = U('/'.$path.'/'.$date.'/'.$id.'@'.$domain);
    }else{
        //继续获取顶级
        $parentsClass = $classArr[$parentClass['pid']];
        $domain = $parentsClass['domain'] ? : 'www'; //二级域名
        $path = $parentClass['path'].'/'.$newsClass['path'];
        $url = U('/'.$path.'/'.$id.'@'.$domain);
    }
    return $url;
}

/**
* 组装m站资讯域名链接
* @param $id  资讯id
* @param $class_id 资讯分类
* @param $classArr 资讯分类数组
* @return string
*/
function mNewsUrl($id,$class_id,$classArr){
    $newsClass   = $classArr[$class_id];
    //不是顶级时获取顶级
    $parentClass = $newsClass['pid'] != 0 ? $classArr[$newsClass['pid']] : $newsClass;
    $url = $parentClass['domain'] ? U('/'.$parentClass['domain'].'/news/'.$id.'@m') : U('/general/news/'.$id.'@m');
    return $url;
}

/**
* 组装资讯栏目页链接
* @param $class_id 资讯分类
* @param $classArr 资讯分类数组
* @return string
*/
function newsClassUrl($class_id,$classArr){
    // 前瞻 战报 href的处理
    if(in_array($class_id, array('108', '109'))){
        return U('/roll');
    }
    $newsClass = $classArr[$class_id];
    //不是顶级时获取顶级
    $parentClass = $newsClass['pid'] != 0 ? $classArr[$newsClass['pid']] : $newsClass;

    if($parentClass['pid'] == 0)
    {
        $domain = $parentClass['domain'];
    }else{
        if($classArr[$newsClass['pid']]['pid'] == 0) {
            $domain = $classArr[$newsClass['pid']]['domain'];
        }else {
            $domain = $classArr[$classArr[$newsClass['pid']]['pid']]['domain'];
            $newsClass['path'] = $classArr[$newsClass['pid']]['path'].'/'.$newsClass['path'];
        }
    }
    if($domain != ''){
        $path = $newsClass['path'] ? : 'news';
        $url = U('/'.$path.'@'.$domain);
    }else{
        $path = $newsClass['path'] ? : 'tag/'.str_replace('资讯', '', $newsClass['name']);
        $url = U('/'.$path.'@www');
    }
    return $url;
}

/**
 * 获取图集链接
 * @param $id  图集id
 * @param $path 图集所属分类路径
 * @param $time 发布时间
 * @return string
 */
function galleryUrl($id, $path, $time){
    return U('/' . $path . '/' . date('Ymd', $time) . '/' . $id . '@photo', '', 'html');
}

/**
 * 获取视频链接
 * @param $id  视频id
 * @param $path 视频所属分类路径
 * @param $time 发布时间
 * @return string
 */
function videoUrl($v,$classArr){
    return U('/' . $classArr[$v['class_id']]['path'] . '/' . date('Ymd', $v['add_time']) . '/' . $v['id'] . '@video', '', 'html');
}

/**
 * 获取球王链接
 * @param $path  所属分类路径
 * @return string
 */
function introUrl($path){
    if(empty($path)){
        return U('/qiuwang@sporttery', '', 'html');
    }
    return U('/qiuwang/'.$path.'@sporttery', '', 'html');
}

/**
 * 替换图片为缩略图
 * @param  $img  图片路径
 * @param $thumb  缩略图尺寸
 * @return string
 */
function setImgThumb($img,$thumb){
    if($img == '')
        return $img;

    $path = pathinfo($img);
    $suffix = explode('?', $path['extension'])[0];
    //gif无需替换
    if($suffix == 'gif') {
        return imagesReplace($img);
    }
    $imgPath = $path['dirname'].'/'.$path['filename'].'_'.$thumb.'.'.$suffix;
    return imagesReplace($imgPath);
}

/**
 * 百度主动推送
 * @param  $urls  链接（一维数组）
 * @return array
 */
function baiduPushNews($urls){
    if(empty($urls)){
        return false;
    }
    $domain = 'www';
    foreach ($urls as $k => $v) {
        $rs = parse_url($v);
        $main_url = $rs["host"];
        $domain = explode('.', $main_url)[0];
    }
    $api = 'http://data.zz.baidu.com/urls?site=https://'.$domain.'.qqty.com&token=uT6ULLJoNJYlAY0s';
    $ch = curl_init();
    $options =  array(
        CURLOPT_URL => $api,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => implode("\n", $urls),
        CURLOPT_HTTPHEADER => array('Content-Type: text/plain'),
    );
    curl_setopt_array($ch, $options);
    $result = curl_exec($ch);
    return $result;
}

/**
 * 对于某些中文导致app闪退的情况 清空含有中文数据的数组直接返回空
 * @param $data 需要清理的多维数组
 * @return [] 返回 [] 或原数组
 */
function errorMsgToNull($data) {
    if ($data == false) {
        return [];
    }
    $result = [];
    array_walk_recursive($data, function ($value) use (&$result) {
       array_push($result, $value);
    });
    $IsChinese= preg_match("/([\x{4e00}-\x{9fa5}])/u", implode("",$result));
    return $IsChinese ? [] : $data;
}

/**
 * 获取中文单词拼音的方法
 * @param $str
 * @param bool $isPy
 * @return string
 */
function getPy($str,$isPy = true)
{
    preg_match_all('/[\x{4e00}-\x{9fff}]+/u', $str, $matches);
    $str =  join('', $matches[0]);
    if($isPy) $str = D('Pinyin')->pinyin($str,'first');
    return $str;
}

/**
 * 将文章内内容的关键字转换成可点击的a标签
 * @param $str  传入文章内容
 * @param $type  平台
 * @param int $num  需要转换关键词的个数
 */
function contKetToUrl($str,$type = 1,$num = 1)
{
    //取出所有含有地址的关键字
    $where['status'] = 1;
    if($type) {
        $where['web_url'] = ['NEQ', ''];
        $field = 'web_url as url';
        $a_html = 'target="_blank"';
    }else{
        $where['m_url'] = ['NEQ',''];
        $field = 'm_url as url';
    }
    if(!$keyWord = S('PublishKey'.$type)){
        $keyWord  = M('PublishKey')->field('name,'.$field)->where($where)->order('sort,id asc')->select();
        S('PublishKey'.$type,$keyWord,300);
    }
    $tmpNum = 1;
    $MaxNum = C('contKetNum');
    if(I('header') == 'no') $urlData = '.html?header=no';
    foreach($keyWord as $val)
    {
        if($tmpNum > $MaxNum) break;

        if(strstr($str,$val['name']))
        {
            $html = "<a href='".$val['url'].$urlData."' title='".$val['name']."' class='contKey' ".$a_html.">".$val['name']."</a>";
            $str = preg_replace('/(?!<[^>]*)'.$val['name'].'(?![^<]*>)/', $html, $str, $num); // 最多替换1次
            $tmpNum++;
        }
    }
    return $str;
}

/**
 * 检查url后缀是否为.html
 * @param $url
 */
function checkUrlExt(){
    $url         = explode('?', $_SERVER['REQUEST_URI']);
    $REQUEST_URI = explode('.', $url[0]);
    $ext = parse_url($REQUEST_URI[1]);
    if($ext['path'] != 'html' || count($REQUEST_URI) > 2){
        return true;
    }
    return false;
}

/**
 * 301永久跳转函数
 * @param $url
 */
function redirect301($url){
    header('HTTP/1.1 301 Moved Permanently');
    redirect($url);
}

//获取链接路径后缀
function getPathExt($file){
    $extension = pathinfo($file, PATHINFO_EXTENSION);
    return $extension;
}

//静态文件资源域名拼接
function staticDomain($path){
    if(strpos($path ,  'http') !== false){
        return $path;
    }
    return C('STATIC_SERVER').$path;
}

//图片独立服务器资源域名拼接
function imagesReplace($url) {
    if(strpos($url ,  'http') !== false || empty($url)){
        return $url;
    }
    $IMG_SERVER = C('IMG_SERVER');
    return $IMG_SERVER . $url;
}

/*
 *  验证文字验证码
 */
function checkVerify($code, $id = ''){
    $verify = new \Think\Verify();
    return $verify->check($code, $id);
}

//根据cookie语言切换，默认简体
function langSwitch($lang1,$lang2,$lang3){
    $langArr = [$lang1,$lang2,$lang3];
    $langType = cookie('lang') ? : 0;
    return $langArr[$langType];
}


// 将数组中字符串数字 转换 int 值 用处于Mongo 获取 get参数转换
function arrayStringToInt($array, $transKey=FALSE) {
	$newArray =[];
	foreach ($array as $key => $value) {
		if (!$transKey) {
			$newArray[intval($key)] = intval($value);
		} else {
			$newArray[$key] = intval($value);
		}
	}
	return $newArray;
}


// 过滤数组中存在或相同的字段 相等或不等 用于mongo 中 类似 leftjoin连接功能
function filterArray($array, $filterArray, $fieldName, $comparison=TRUE) {
	$newArray = [];
	foreach ($array as $key => $value) {
		if ($comparison) {
			foreach ($filterArray as $fkey => $fvalue) {
				if ($value[$fieldName] == $fvalue[$fieldName]) {
					$newArray[$key] = $value;
				}
			}
		} else {
			foreach ($filterArray as $fkey => $fvalue) {
				if ($value[$fieldName] != $fvalue[$fieldName]) {
					$newArray[$key] = $value;
				}
			}
		}
	}
	return $newArray;
}


// 获取当天赛事列表信息
function getTodayGameList() {
	if (time() > strtotime('10:32:00')) {
		$today = date('Y-m-d');
	} else {
		$today = date('Y-m-d', strtotime('-1 day'));
	}
	$mongodb = mongoService();
	$gameList = $mongodb->select('fb_gamelist',['date'=>$today],['game_list'])[0]['game_list'];
	$dateGame = [];
	foreach ($gameList as $key => $value) {
		$dateGame[] = $key;
	}
	return $dateGame;
}


// 如果数组为null 返回空数组
function emptyReturnArray($array) {
	if (empty($array)) {
		return new ArrayObject();
	}
	return $array;
}


	/**
	 * @param $array 需要排序的数组
	 * @param $column 数组里要排序的字段
	 * @return mixed
	 */
function sortToColumn($array, $column) {
	array_multisort(array_column($array, $column), SORT_DESC, $array);
	return $array;
}


	/**
	 * @param $arrayS 数据源
	 * @param $arrayT 接收数据源
	 * @param $columns 配对字段
	 * @param $columnt 配对字段
	 * @param $columntTo 接收字段
	 * @return mixed
	 */
function columnToArray($arrayS, $arrayT, $columns, $columnt, $columntToArray) {
	foreach ($arrayS as $sk => $sv) {
		foreach ($arrayT as $tk => $tv) {
			if ($sv[$columns] == $tv[$columnt]) {
				foreach ($columntToArray as $k => $v) {
					$arrayS[$sk][] = $tv[$v];
				}
				break;
			}
		}
	}
	return $arrayS;
}


// 判断是否是空字符串
function NullString($var)
{
	if (null === $var || !isset($var) || ((sizeof($var) <0) && is_array($var))) {
		return "";
	}
	$value = preg_replace('/ /', ' ', $var);
	return trim($value);
}

// 判断数组是否为空数组或 空字符串
function nullArrayToBool($array) {
	$len = sizeof($array);
	$bool = ($array[0] === '');
	for($i = 1; $i < $len; $i++) {
		$bool = $bool && ($array[$i] === '');
	}
	return $bool;
}

// 判断是否盘口是否为负
function negateive($string) {
	if (strpos($string, "*") !== false) {
		$string_p = preg_replace('/\*/', '', $string);
		return '<span class="text-red">*</span>'.$string_p;
	}
	return $string;
}

// 必然返回时间 防止mongo没有时间导致闪退
function TimeISTrue($game_start_timestamp, $gtime, $game_starttime) {
	if (!empty($game_start_timestamp)) {
		return (int) $game_start_timestamp;
	}
	if (!empty($gtime)) {
		return strtotime($gtime);
	}
	if (!empty($game_starttime)) {
		return $game_starttime->sec;
	}
	return strtotime(date("Y-m-d"));
}

//今日比赛最新时间判断
function TellRealTime($start_time,$game_start_timestamp, $game_starttime,$game_state){
    if(!empty($start_time)){
        $timestamp = !empty($game_start_timestamp) ? date('H:i',$game_start_timestamp) : date('H:i',$game_starttime->sec);
        if($start_time == $timestamp){
            //比赛时间没变化直接返回
            return (int) $game_start_timestamp;
        }
        if(date('H:i') >= '10:30'){
            if($start_time >= '00:00' && $start_time <= '10:30'){
                $date = $game_state == 0 ? date('Y-m-d',strtotime('+1 day')) : date('Y-m-d');
            }else{
                $date = date('Y-m-d');
            }
        }else{
            $gtime = !empty($game_start_timestamp) ? $game_start_timestamp : $game_starttime->sec;
            $date = date('Y-m-d',$gtime);
        }
        return strtotime($date.' '.$start_time);
    }
    if (!empty($game_start_timestamp)) {
        return (int) $game_start_timestamp;
    }
    if (!empty($game_starttime)) {
        return (int) $game_starttime->sec;
    }
}

/**
 * @param $querys 支持四种形式获取
 * 例：city=广州 citycode=101281201 cityid=75 ip=192.168.1.226
 */
function getWeather($querys){
    $host = "http://jisutqybmf.market.alicloudapi.com";
    $path = "/weather/query";
    $method = "GET";
    $appcode = "437082eb74ad42e99279a804d25d449d";
    $headers = array();
    array_push($headers, "Authorization:APPCODE " . $appcode);

    $url = $host . $path . "?" . $querys;

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_FAILONERROR, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, true);
    if (1 == strpos("$".$host, "https://"))
    {
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    }
    $data = curl_exec($curl);
    curl_close($curl);

    $info = array();
    if($data){
        $info = end(explode(PHP_EOL,$data));
        $info = json_decode($info,true);
        $info = $info['result'];
    }
    return $info;
}

//检查密码格式
function fn_is_pwd($str) {
    return preg_match("/^[0-9A-Za-z]{6,15}$/", $str);
}


// 转换周
function weekFormat($n) {
	$week = "";
	switch($n) {
		case 1:$week="周一";break;
		case 2:$week="周二";break;
		case 3:$week="周三";break;
		case 4:$week="周四";break;
		case 5:$week="周五";break;
		case 6:$week="周六";break;
		case 7:$week="周日";break;
	}
	return $week;
}

//球队logo,为空时取默认logo
function replaceTeamLogo($teamLogo,$type=1){
    if(strpos($teamLogo ,  'http') !== false){
        return $teamLogo;
    }
    if($teamLogo){
        $httpUrl = C('IMG_SERVER');
        return $httpUrl .$teamLogo;
    }

    return $type == 1
        ? staticDomain('/Public/Home/images/common/web_player.png')
        : staticDomain("/Public/Mobile/images/schedule/no_image.png");
}

//根据class_id判断是否vip文章
function isVipNews($class_id = 0){
    $is_vip = $class_id == C('vipClassId') ? 1:0;
    return $is_vip;
}

//判断是否vip
function checkVip($vip_time){
    if( $vip_time >= strtotime(date(Ymd)) ){
        return '1';
    }
    return '0';
}


/**
 * object轉PHP數據
 * @param $obj
 * @return array|void
 */
function object_to_array($obj) {
    $obj = (array)$obj;
    foreach ($obj as $k => $v) {
        if (gettype($v) == 'resource') {
            return;
        }
        if (gettype($v) == 'object' || gettype($v) == 'array') {
            $obj[$k] = (array)object_to_array($v);
        }
    }

    return $obj;
}

/**
 * @param $total_money总金额
 * @param $total_num  分成数量
 * @return array
 */
function getRandomDivInt($total_money,$total_num){
    if($total_num > $total_money){
        $total_num = $total_money;
    }
    $total_money=$total_money - $total_num;
    for($i=$total_num;$i>0;$i--){
        $data[$i]=1;
        $ls_money=0;
        if($total_money>0){
            if($i==1){
                $data[$i] +=$total_money;
            }else{
                $max_money=floor($total_money/$i);
                $ls_money=mt_rand(0,$max_money);
                $data[$i]+=$ls_money;
            }
        }
        $total_money -= $ls_money;
    }
    //sort($data);
    return array_values($data);
}

/**
 * 正负数相互转换
 * @param $number
 * @return str
 */
function plusMinusChange($number){
    return $number > 0 ? -1 * $number : abs($number);
}

//计算预测模型每日回报率
function getModelTodayIncome($income,$win,$lost){
    return round($income/($win+$lost),2);
}

/**
 * 拼装批量更新sql
 * @param  $table 表名
 * @param  $fieldArr 字段
 * @param  $data 修改内容
 * @return str
 */
function replaceAllSql($table,$fieldArr,$data){
    $value = implode(',', $data);
    $field = implode(',', $fieldArr);
    foreach ($fieldArr as $k => $v) {
        $fieldStrArr[] = "{$v}=VALUES({$v})";
    }
    $fieldStr = implode(',', $fieldStrArr);
    $sql = "INSERT INTO {$table} ({$field})
            VALUES {$value}
            ON DUPLICATE KEY UPDATE {$fieldStr}";
    return $sql;
}
