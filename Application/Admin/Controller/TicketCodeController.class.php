<?php

/**
 * 优惠卷
 *
 * @author
 *
 * @since
 */
use Think\Tool\Tool;
use Think\Controller;
class TicketCodeController extends CommonController {

    public function index()
    {
        $map = $this->_search('TicketPartner');
        $list = $this->_list(CM('TicketPartner',$map));
        $partner_id = array_map('array_shift',$list);
        $code = M('TicketCode')->field("partner_id,is_use,code")->where(['partner_id'=>['in',$partner_id],'is_use'=>1])->select();
        foreach ($list as $k => $v) 
        {
            //已兑换数量
            $use_num = 0;
            $code_arr = [];
            foreach ($code as $kk => $vv) {
                if($v['id'] == $vv['partner_id']){
                    $use_num++;
                    $code_arr[] = $vv['code'];
                }
            }
            $list[$k]['use_num']  = $use_num;
            $list[$k]['code_num'] = M('TicketLog')->where(['code'=>['in',$code_arr],'is_use'=>1])->count() ? : 0;
        }
        $this->assign('list',$list);
        $this->display();
    }

    public function codeLog()
    {
        if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
            if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                $startTime = strtotime($_REQUEST ['startTime']);
                $endTime   = strtotime($_REQUEST ['endTime']);
                $map['over_time'] = array('BETWEEN',array($startTime,$endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']);
                $map['over_time'] = array('EGT',$strtotime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime']);
                $map['over_time'] = array('ELT',$endTime);
            }
        }
        $partner_id = I('partner_id');
        if(!empty($partner_id)){
            $map['partner_id'] = $partner_id;
        }
        $shop_name = (string)I('shop_name');
        if(! empty($shop_name))
            $map['shop_name'] = $shop_name;
        $is_use = I('is_use');
        if($is_use != '')
            $map['is_use'] = $is_use;

        $nick_name = (string)I("nick_name");
        if(! empty($nick_name)){
            $map['nick_name'] = ['like','%'.$nick_name.'%'];
            $countList = M("TicketCode t")->join("left join qc_front_user f on f.id = t.user_id")->where($map)->count('t.id');
        }else{
            $countList = M("TicketCode t")->where($map)->count('t.id');
        }
        $this->assign('totalCount',$countList);
        $pageNum = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
        $list = M('TicketCode t')
            ->field("t.id,t.shop_name,t.user_id,t.code,t.price,t.over_time,t.add_time,t.is_use,t.use_time,t.status,f.nick_name as nick_name")
            ->join("left join qc_front_user f on f.id = t.user_id")
            ->where($map)
            ->where(['t.id'=>['gt',0]])
            ->limit($pageNum)
            ->page(!empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1)
            ->order('t.id desc')
            ->select();

        $this->assign ( 'numPerPage', $pageNum ); //每页显示多少条
        $this->assign('list',$list);
        $this->setJumpUrl();
        $this->assign ( 'currentPage', !empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1);//当前页码
        $this->display();
    }

    //新添体验卷列表
    public function tasteConfig()
    {
        $tasteRe = getWebConfig('ticket');
        if($tasteRe){
            foreach ($tasteRe as $k => $v) {
                $tasteRe[$k]['img'] = Tool::imagesReplace($v['url']);
            }
        } else{
            $tasteRe = [1=>''];
        }
        $this->assign('tasteConfig',$tasteRe);
        $this->display();
    }
    
    //保存更新体验兑换劵
    public function saveTaste()
    {
        $tic = I('tic');
        if(empty($tic)) $this->error('参数错误！');

        //已存在的图片
        $tasteRe = getWebConfig('ticket');
        if($tasteRe) 
        {
            $configKey = array_keys($tasteRe); 
            //新提交的数据
            $ticKey = array_keys($tic); 
            //需要删除的图片key值
            $delImg = array_diff($configKey, $ticKey);
        }

        foreach ($tic as $k => $v) {
            if($v['sort'] == '')  $this->error('序号不能为空');
            if($v['price'] == '') $this->error('金额不能为空');
            if(!is_numeric($v['sort'])) $this->error('序号必须是数字');
            if(!is_numeric($v['price'])) $this->error('金额必须是数字');
        }

        foreach ($tic as $k => $v) {
            if (!empty($_FILES['fileInput_'.$k]['tmp_name'])) 
            {
                //先删除原来图片
                $fileArr = array(
                    "/codeimg/{$k}.jpg",
                    "/codeimg/{$k}.gif",
                    "/codeimg/{$k}.png",
                    "/codeimg/{$k}.swf",
                );
                D('Uploads')->deleteFile($fileArr);
                $return = D('Uploads')->uploadImg("fileInput_".$k, "codeimg", $k);
                $tic[$k]['url'] = $return['status'] == 1 ? $return['url'] : '';
            }
        }
        if($tasteRe){
            $re = M('config')->where(['sign'=>'ticket'])->save(['config'=>json_encode($tic)]);
        }else{
            $re = M('config')->add(['sign'=>'ticket','config'=>json_encode($tic)]);
        }
        
        if($re !== false)
        {
            //删除已删除数据图片
            foreach ($delImg as $k => $v) {
                $delImgArr = array(
                    "/codeimg/{$v}.jpg",
                    "/codeimg/{$v}.gif",
                    "/codeimg/{$v}.png",
                    "/codeimg/{$v}.swf",
                );
                D('Uploads')->deleteFile($delImgArr);
            }
            $this->success('修改成功');
        }
        else
        {
            $this->error('修改失败');
        }
        
    }

    //新添体验卷列表IOS
    public function iosConfig()
    {
        $sign = 'ticketIos';
        $tasteRe = getWebConfig($sign);
        if($tasteRe){
            foreach ($tasteRe as $k => $v) {
                $tasteRe[$k]['img'] = Tool::imagesReplace($v['url']);
            }
        } else{
            $tasteRe = [1=>''];
        }
        $this->assign('tasteConfig',$tasteRe);
        $this->display();
    }

    //保存更新体验兑换劵IOS
    public function saveIos()
    {
        $tic = I('tic');
        if(empty($tic)) $this->error('参数错误！');

        //已存在的图片
        $sign = 'ticketIos';
        $tasteRe = getWebConfig($sign);
        if($tasteRe)
        {
            $configKey = array_keys($tasteRe);
            //新提交的数据
            $ticKey = array_keys($tic);
            //需要删除的图片key值
            $delImg = array_diff($configKey, $ticKey);
        }

        foreach ($tic as $k => $v) {
            if($v['sort'] == '')  $this->error('序号不能为空');
            if($v['price'] == '') $this->error('金额不能为空');
            if(!is_numeric($v['sort'])) $this->error('序号必须是数字');
            if(!is_numeric($v['price'])) $this->error('金额必须是数字');
        }

        foreach ($tic as $k => $v) {
            if (!empty($_FILES['fileInput_'.$k]['tmp_name']))
            {
                //先删除原来图片
                $fileArr = array(
                    "/codeimg/{$k}.jpg",
                    "/codeimg/{$k}.gif",
                    "/codeimg/{$k}.png",
                    "/codeimg/{$k}.swf",
                );
                D('Uploads')->deleteFile($fileArr);
                $return = D('Uploads')->uploadImg("fileInput_".$k, "codeimg", $k);
                $tic[$k]['url'] = $return['status'] == 1 ? $return['url'] : '';
            }
        }
        if($tasteRe){
            $re = M('config')->where(['sign'=>$sign])->save(['config'=>json_encode($tic)]);
        }else{
            $re = M('config')->add(['sign'=>$sign,'config'=>json_encode($tic)]);
        }

        if($re !== false)
        {
            //删除已删除数据图片
            foreach ($delImg as $k => $v) {
                $delImgArr = array(
                    "/codeimg/{$v}.jpg",
                    "/codeimg/{$v}.gif",
                    "/codeimg/{$v}.png",
                    "/codeimg/{$v}.swf",
                );
                D('Uploads')->deleteFile($delImgArr);
            }
            $this->success('修改成功');
        }
        else
        {
            $this->error('修改失败');
        }

    }

    //新添体验卷兑换码
    public function addCode()
    {
        if(IS_POST)
        {
            $totleNum  = (int)I('totle_num');
            if($totleNum <= 0) $this->error('请输入生成数量！');
            $shop_name = I('shop_name');
            $price     = I('price');
            $sale      = I('sale');
            $over_time = strtotime(I('over_time'))  + 86399;
            $add_time  = time();

            $TicketPartner = [
                'shop_name' => $shop_name,
                'price'     => $price,
                'ticket_num'=> $totleNum,
                'over_time' => $over_time,
                'add_time'  => $add_time,
            ];

            $partner_id = M('TicketPartner')->add($TicketPartner);
            
            if(!$partner_id) $this->error('添加失败');

            $dataAll   = [];
            for($i=0; $i<$totleNum; $i++)
            {
                $randStr = $this->randStr().$this->randStr(4,2).$this->randStr().$this->randStr(4,2);
                $dataAll[$i]['code']      = $randStr;
                $dataAll[$i]['shop_name'] = $shop_name;
                $dataAll[$i]['partner_id']= $partner_id;
                $dataAll[$i]['price']     = $price;
                $dataAll[$i]['sale']      = $sale;
                $dataAll[$i]['add_time']  = $add_time;
                $dataAll[$i]['over_time'] = $over_time;
            }
            $re = M('TicketCode')->addAll($dataAll);

            if(!$re) $this->error('添加失败');

            $this->success('添加成功');
        }
        $this->display();
    }

    /**
    * 随机生成指定长度字符串函数
    * @param int $length    #长度
    * @param int $type      #生成类型，1为字母，2为数字
    * @return string
    */
    public function randStr($length=4, $type=1)
    {
        //字母or数字
        $chars = $type == 1 ? 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' : '0123456789';
        $str = '';
        for ( $i = 0; $i < $length; $i++ )
        {
            // 第一种是使用substr 截取$chars中的任意一位字符；第二种是取字符数组$chars 的任意元素
            //$str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
            $str .= $chars[ mt_rand(0, strlen($chars) - 1) ];
        }
        return $str;
    }

    //批量设置禁用启用
    public function onOff()
    {
        $sign = I('get.sign');
        $id = I('post.id');
        if(isset($id))
        {
            $status = $sign == 'open' ? 1 : 0;
            $re = M('TicketCode')->where(['id'=>['in',$id]])->save(['status'=>$status]);
            if($re !== false)
            {
                $this->success('批量设置成功');
            }
            else
            {
                $this->error('批量设置失败');
            }
        }
        else
        {
            $this->error("非法操作");
        }
    }
}