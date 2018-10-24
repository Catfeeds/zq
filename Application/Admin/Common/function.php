<?php
// +----------------------------------------------------------------------
// | ThinkPHP
// +----------------------------------------------------------------------
// | Copyright (c) 2007 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id: common.php 2601 2012-01-15 04:59:14Z liu21st $

//公共函数
function toDate($time, $format = 'Y-m-d H:i:s') {
	if (empty ( $time )) {
		return '';
	}
	$format = str_replace ( '#', ':', $format );
	return date ($format, $time );
}

function getStatus($status, $imageShow = true) {
	switch ($status) {
        case 0 :
            $showText = '禁用';
            $showImg = '<IMG SRC="'.__ROOT__.'/Public/Images/locked.gif" WIDTH="20" HEIGHT="20" BORDER="0" ALT="禁用">';
            break;
        case 2 :
            $showText = '待审';
            $showImg = '<IMG SRC="'.__ROOT__.'/Public/Images/prected.gif" WIDTH="20" HEIGHT="20" BORDER="0" ALT="待审">';
            break;
        case - 1 :
            $showText = '删除';
            $showImg = '<IMG SRC="'.__ROOT__.'/Public/Images/del.gif" WIDTH="20" HEIGHT="20" BORDER="0" ALT="删除">';
            break;
        case 1 :
        default :
            $showText = '正常';
            $showImg = '<IMG SRC="'.__ROOT__.'/Public/Images/ok.gif" WIDTH="20" HEIGHT="20" BORDER="0" ALT="正常">';

	}
	return ($imageShow === true) ?  $showImg  : $showText;

}

function getNodeGroupName($id) {
	if (empty ( $id )) {
		return '未分组';
	}
	if (isset ( $_SESSION ['nodeGroupList'] )) {
		return $_SESSION ['nodeGroupList'] [$id];
	}
	$Group = D ( "Group" );
	$list = $Group->getField ( 'id,title' );
	$_SESSION ['nodeGroupList'] = $list;
	$name = $list [$id];
	return $name;
}

//囚鸟先生
function showStatus($status, $id, $callback="", $url, $dwz,$type=array('恢复','禁用')) {
	switch ($status) {
		case 0 :
			$info = '<a href="'.$url.'/resume/id/' . $id . '/navTabId/'.$dwz.'" target="ajaxTodo" callback="'.$callback.'">'.$type[0].'</a>';
			break;
		case 2 :
			$info = '<a href="'.$url.'/checkPass/id/' . $id . '/navTabId/'.$dwz.'" target="ajaxTodo" callback="'.$callback.'">批准</a>';
			break;
		case 1 :
			$info = '<a href="'.$url.'/forbid/id/' . $id . '/navTabId/'.$dwz.'" target="ajaxTodo" callback="'.$callback.'">'.$type[1].'</a>';
			break;
		case - 1 :
			$info = '<a href="'.$url.'/recycle/id/' . $id . '/navTabId/'.$dwz.'" target="ajaxTodo" callback="'.$callback.'">还原</a>';
			break;
	}
	return $info;
}


function getGroupName($id) {
	if ($id == 0) {
		return '无上级组';
	}
	if ($list = F ( 'groupName' )) {
		return $list [$id];
	}
	$dao = D ( "Role" );
	$list = $dao->select( array ('field' => 'id,name' ) );
	foreach ( $list as $vo ) {
		$nameList [$vo ['id']] = $vo ['name'];
	}
	$name = $nameList [$id];
	F ( 'groupName', $nameList );
	return $name;
}

function pwdHash($password, $type = 'md5') {
	$hash    = GetRandStr(10);
	$pwdHash = md5($hash.$password);
	return [$hash,$pwdHash];
}

//CommonModel 自动继承
function CM($name){
	static $_model = array();
	if(isset($_model[$name])){
		return $_model[$name];
	}
	$class=$name."Model";
	import('@.Model.' . $class);

	if(class_exists($class)){
		$return=new $class();
	}else{
		$return=M("CommonModel:".$name);
	}
	$_model[$name]=$return;

    return $return;
}

//获取国家名称
function getCountry($country,$cid)
{
    foreach ($country as $v)
    {
        if ($v['country_id'] == $cid)
            return $v['country_name'];
    }
}
/**
 * 导出excel
 * @author liangzk <1343724998@qq.com>
 * @since V1.0  2016-07-07
 * @param $strTable 表格内容
 * @param $filename 文件名
 */
function downloadExcel($strTable,$filename)
{
    header("Content-type: application/vnd.ms-excel");
    header("Content-Type: application/force-download");
    header("Content-Disposition: attachment; filename=".$filename."_".date('Y-m-d').".xls");
    header('Expires:0');
    header('Pragma:public');
    echo '<html><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'.$strTable.'</html>';
}
/**
 * @User: lianzk <liangzk@qc.com>
 * @date 2016-07-14 @time 15:29
 * 获取数组中的某一列
 * @param type $arr 数组
 * @param type $key_name  列名
 * @return type  返回那一列的数组
 */
function get_arr_column($arr, $key_name)
{
	$arr_column = array();
	foreach($arr as $key => $val){
		$arr_column[] = $val[$key_name];
	}
	return $arr_column;
}
/**
 * @User: lianzk <liangzk@qc.com>
 * @date 2016-08-10 @time 16:29
 * 根据指定数组中的某个值进行分组
 * @param type $arr 二维数组
 * @param type $key_name  列名
 * @return type  返回三维数组
 */
function arr_val_grouping($arr,$key_name)
{
    $groupingArr = array();
    foreach ($arr as $k => $v)
    {
        $groupingArr[$v[$key_name]][] = $v;
    }
    return $groupingArr;
}
/**
 * @User: liuy <liuy@qc.com>
 * @date 2016-09-15 @time 16:29
 * 搜索关键字高亮
 * @param type $name post值
 * @param type $str 遍历出的字符串
 */
function on_str_replace($name,$str)
{
    return str_ireplace(trim($name), "<font style='color:red;font-size:13px;'>".trim($name)."</font>", trim($str));
}

/**
 * @User: dengwj
 * 根据权限隐藏手机号
 * @param type $name post值
 */
function is_show_mobile($mobile)
{
	if(getUserPower()['is_show_mobile'] != 1 && !empty($mobile))
	{
	    $username = substr_replace($mobile, '****', '3','-4');
	}
	else
	{
		$username = $mobile;
	}
    return $username;
}

function getUserPower()
{
	return $_SESSION['user_power'];
}

//返回周几
function returnWeek($date)
{
	return date(N,strtotime($date));
}

//根据榜类型返回前7/30/90天日期
function returnDate($date,$type){
	if($type == 4) return ''; //日榜不返回
	switch ($type) {
		case '1':
			$time = date("Ymd",strtotime("-6 day $date"));
			break;
		case '2':
			$time = date("Ymd",strtotime("-29 day $date"));
			break;
		case '3':
			$time = date("Ymd",strtotime("-89 day $date"));
			break;
	}
	return $time.' -';
}

//iphone型号转换
function iphoneWiki($str){
	return C('iphone_wiki')[$str] ? : $str;
}
