<?php

/**
 * 礼包赠送设置
 *
 * @author liuweitao <cytusc@foxmail.com>
 * @since  2017-02-23
 */
use Think\Controller;
use Think\Tool\Tool;

class GiftsConfController extends CommonController
{
    public $user_arr_public = '';//优惠卷礼包公共用户id变量

    public function index()
    {
        //排序字段 默认为主键名
        $order = $_REQUEST ['_order'] ? : 'id';
        //排序方式默认按照倒序排列
        $sort = $_REQUEST ['_sort'] == 'asc' ? 'asc' : 'desc';
        //昵称查询
        $name = trim(I('name'));
        if (!empty($name)) {
            $map['gc.name'] = ['Like', $name . '%'];
        }
        //类型查询
        $type = trim(I('type'));
        if (!empty($type)) {
            $map['gc.type'] = $type;
        }
        $count = M('GiftsConf')->count('id');
        //获取每页显示的条数
        $pageNum = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
        //获取当前的页码
        $currentPage = !empty($_REQUEST[C('VAR_PAGE')]) ? $_REQUEST[C('VAR_PAGE')] : 1;

        if ($count > 0) {
            $fieldName = 'gc.id,gc.name,gc.type,gc.status,gc.before_img,gc.after_img,gc.start_time,gc.end_time,gc.over_time,gc.over_day,
				( SELECT count(tl.id) FROM qc_ticket_log tl
				WHERE tl.gift_id = gc.id ) AS gifCount ';

            $list = M('GiftsConf gc')
                ->field($fieldName)
                ->where($map)
                ->order($order." ".$sort)
                ->limit($pageNum * ($currentPage - 1), $pageNum)
                ->select();
        }
        foreach ($list as $k => $v) {
            $before_img = Tool::imagesReplace($v['before_img']);
            $list[$k]['before_img'] = $before_img ? $before_img : '';
            $after_img = Tool::imagesReplace($v['after_img']);
            $list[$k]['after_img'] = $after_img ? $after_img : '';
        }
        $this->assign('list', $list);
        $this->assign('totalCount', $count);//当前条件下数据的总条数
        $pageNum = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
        $this->assign('numPerPage', $pageNum); //每页显示多少条
        $this->assign('currentPage', !empty($_REQUEST[C('VAR_PAGE')]) ? $_REQUEST[C('VAR_PAGE')] : 1);//当前页码
        $this->setJumpUrl();
        $this->display();
    }

    /**
     * 添加编辑操作
     * @access
     * @return string
     */
    public function edit()
    {
        $id = I('id', 0, 'int');
        if ($id) {
            $info = M("GiftsConf")->where(['id' => $id])->select();
            $info = $info[0];
            $game_ticket = json_decode($info['game_ticket'], true);
            $game = 0;
            foreach ($game_ticket as $key => $val) {
                $info['ty'][$game]['coins'] = strval($key);
                $info['ty'][$game]['num'] = strval($val);
                $game++;
            }
            $coin = 0;
            $coin_ticket = json_decode($info['coin_ticket'], true);
            foreach ($coin_ticket as $key => $val) {
                $op_tcp = explode('_', $key);
                $info['coin'][$coin]['op'] = $op_tcp[0];
                $info['coin'][$coin]['tcp'] = $op_tcp[1];
                $info['coin'][$coin]['num'] = strval($val);
                $coin++;
            }
            $before_img = Tool::imagesReplace($info['before_img']);
            $info['before_img'] = $before_img ? $before_img : '';
            $after_img = Tool::imagesReplace($info['after_img']);
            $info['after_img'] = $after_img ? $after_img : '';
            if ($info['user_array']) {
                $where['id'] = array('in', $info['user_array']);
                $res = M('FrontUser')->where($where)->getField('nick_name', true);
                $info['user_list'] = implode(",", $res);
            }
        }
        $type = I('type');//接收一个值判断是否为系统赠送
        if ($type == 'system') $this->assign('system', 1);;
        $this->assign('info', $info);
        $this->display();
    }

    /**
     * 数据保存
     * @access
     * @return string
     */
    public function save()
    {
        $id = I('id');
        $data = $this->array_handle($_POST);
        //对时间进行对比判断
        if ($data['start_time']) {
            if ($data['start_time'] > $data['end_time']) {
                $this->error('开始时间晚于结束时间');
            }
//            if ($data['over_time'] <= $data['end_time']) {
//                $this->error('券有效期必须大于活动结束时间!');
//            }
        }
        if (!$data['type']) $data['type'] = 2;

        if ($id) {
            //上传领取前图片
            if (!empty($_FILES['fileInput1']['tmp_name'])) {
                $return = D('Uploads')->uploadImg("fileInput1", "gifts", $id . '_bef');
                if ($return['status'] == 1)
                    $data['before_img'] = $return['url'];
            }
            //上传领取后图片
            if (!empty($_FILES['fileInput2']['tmp_name'])) {
                $return = D('Uploads')->uploadImg("fileInput2", "gifts", $id . '_aft');
                if ($return['status'] == 1)
                    $data['after_img'] = $return['url'];
            }
            $rs = M("GiftsConf")->where(['id' => $id])->save($data);
        } else {
            $res = M("GiftsConf")->add($data);
            //上传领取前图片
            if (!empty($_FILES['fileInput1']['tmp_name'])) {
                $return = D('Uploads')->uploadImg("fileInput1", "gifts", $res . '_bef');
                if ($return['status'] == 1)
                    $bef = $return['url'];
                $img_data['before_img'] = $bef;
            }
            //上传领取后图片
            if (!empty($_FILES['fileInput2']['tmp_name'])) {
                $return = D('Uploads')->uploadImg("fileInput2", "gifts", $res . '_aft');
                if ($return['status'] == 1)
                    $aft = $return['url'];
                $img_data['after_img'] = $aft;
            }
            $data['id'] = $res;
            if ($data['type'] == 2 && $data['status']) {
                $gif_re = $this->giftBag($data['user_array'], $data);//系统赠送
                if($gif_re) $this->gif_msg($data);
            }
            $img_res = M("GiftsConf")->where(['id' => $res])->save($img_data);
            if ($img_res) {
                $rs = $img_res;
            } else {
                $rs = $res;
            }
        }
        if ($rs !== false) {
            $this->success('保存成功');
        } else {
            $this->error('保存失败');
        }
    }

    /**
     * @param $user_arr 用户id
     * @param $res 优惠卷数据
     */
    public function gif_msg($res)
    {
        //推荐体验卷语句生成
        $game = json_decode($res['game_ticket'], true);
        $game_arr = array();
        foreach ($game as $key => $val) {
            $game_arr[] = $key . '金币推荐体验劵' . $val . '张';
        }
        $game_str = implode('，', $game_arr);
        //充值优惠券语句生成
        $coin = json_decode($res['coin_ticket'], true);
        $coin_arr = array();
        foreach ($coin as $key => $val) {
            $k = explode('_', $key);
            $coin_arr[] = $k[0] . '元额度充值优惠券' . $val . '张';
        }
        if($game_str) $comma = '，';//判断是否含有体验劵,用于两个之间之间的拼接
        $coin_str = implode('，', $coin_arr);
        $content = '您好,系统赠送您'.$game_str.$comma.$coin_str.'。请到“我的优惠劵”查看';//生成消息内容
        //发送消息
        sendMsg($this->user_arr_public,'系统赠送通知',$content);
        return true;
    }

    /**
     * 对提交的数据进行数据处理
     * @access
     * @return string
     */
    public function array_handle($arr)
    {
        unset($arr['id']);
        foreach ($arr as $key => $val) {
            if ($val == '') {
                unset($arr[$key]);
            }
        }
        if ($arr['startTime']) {
            $arr['start_time'] = strtotime($arr['startTime']);
            unset($arr['startTime']);
        }
        if ($arr['endTime']) {
            $arr['end_time'] = strtotime($arr['endTime']);
            unset($arr['endTime']);
        }
        if ($arr['over_time']) $arr['over_time'] = strtotime($arr['over_time'])  + 86399;
        if ($arr['ty']) {
            $ty = array();
            foreach ($arr['ty'] as $val) {
                $ty_info = explode('_', $val);
                $ty[$ty_info[0]] = $ty_info[1];
            }
            $arr['game_ticket'] = json_encode($ty);
            unset($arr['ty']);
        }
        if ($arr['cz']) {
            $cz = array();
            foreach ($arr['cz'] as $val) {
                $cz_info = explode('_', $val);
                $cz_key = $cz_info[0] . '_' . $cz_info[1];
                $cz[$cz_key] = $cz_info[2];
            }
            $arr['coin_ticket'] = json_encode($cz);
            unset($arr['cz']);
        }
        if ($arr['FrontUser_nick_name']) unset($arr['FrontUser_nick_name']);
        if ($arr['FrontUser_id']) {
            $arr['user_array'] = $arr['FrontUser_id'];
            unset($arr['FrontUser_id']);
        }
        return $arr;
    }


    /**
     * 批量禁用启用
     * @access
     * @return string
     */
    public function saveAll()
    {
        $ids = isset($_POST['id']) ? I('id') : null;
        if ($ids) {
            $table = "GiftsConf";
            $status = $_REQUEST['status'];
            $idsArr = explode(',', $ids);
            $condition = array("id" => array('in', $idsArr));
            $rs = M($table)->where($condition)->save(['status' => $status]);
            if ($rs !== false) {
                $this->success('设置成功');
            } else {
                $this->error('设置失败');
            }
        } else {
            $this->error('非法操作');
        }
    }

    /**
     * 删除
     *
     * @return string
     *
     */
    public function delAll()
    {
        $id = isset($_POST['id']) ? I('id') : null;
        if (isset ($id)) {
            //拼接要删除的所有id
            $condition = array("id" => array('in', explode(',', $id)));
            if (false !== M("GiftsConf")->where($condition)->delete()) {
                $fileArr = array(
                    "/gifts/{$id}_aft.jpg",
                    "/gifts/{$id}_aft.gif",
                    "/gifts/{$id}_aft.png",
                    "/gifts/{$id}_aft.swf",
                    "/gifts/{$id}_bef.jpg",
                    "/gifts/{$id}_bef.gif",
                    "/gifts/{$id}_bef.png",
                    "/gifts/{$id}_bef.swf",
                );
                D('Uploads')->deleteFile($fileArr);
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }
        } else {
            $this->error('非法操作');
        }
    }

    /**
     * 删除优惠卷领取的图片图片
     */
    public function delTeamPic()
    {
        $id = I('id', 0, 'int');
        $teamType = I('teamType', 0, 'int') === 1 ? 1 : 2;//1：主队  2：客队
        if (empty($id)) {
            $this->error('参数错误!');
        }
        $id = strval($id);
        if ($teamType === 2) {
            $fileArr = array(
                "/gifts/{$id}_aft.jpg",
                "/gifts/{$id}_aft.gif",
                "/gifts/{$id}_aft.png",
                "/gifts/{$id}_aft.swf",
            );
            $img_column = 'after_img';
        } elseif ($teamType === 1) {
            $fileArr = array(
                "/gifts/{$id}_bef.jpg",
                "/gifts/{$id}_bef.gif",
                "/gifts/{$id}_bef.png",
                "/gifts/{$id}_bef.swf",
            );
            $img_column = 'before_img';
        }
        //执行删除
        $return = D('Uploads')->deleteFile($fileArr);
        if ($return['status'] == 1) {
            M("GiftsConf")->where(['id' => $id])->save([$img_column => '']);
            $this->success('删除成功！');
        }

        $this->error('删除失败！');
    }

    /**
     * 注册赠送大礼包
     */
    public function giftBag($user_arr, $res)
    {
        if ($user_arr) {
            $this->user_arr_public = explode(',', $user_arr);
        } else {
            $this->user_arr_public = M("FrontUser")->getfield('id', true);
        }
        $gifit_id = $res['id'];
        $num = $cnum = 0;
//        //推荐和充值体验券
        $data = $cdata = [];
        $game = json_decode($res['game_ticket'], true);
        foreach ($game as $k => $v) {
            for ($i = 0; $i < $v; $i++) {
                foreach ($this->user_arr_public as $val) {
                    $data[$num]['name'] = $k . C('giftPrice');
                    $data[$num]['user_id'] = $val;
                    $data[$num]['type'] = 1;
                    $data[$num]['price'] = $k;
                    $data[$num]['give_coin'] = $k;
                    $data[$num]['get_time'] = NOW_TIME;
                    $data[$num]['over_time'] = NOW_TIME + $res['over_day']*3600*24;
                    $data[$num]['get_type'] = 5;
                    $data[$num]['remark'] = '推荐体验券-系统赠送';
                    $data[$num]['gift_id'] = $gifit_id;
                    $num++;
                }
            }
        }
        $addData = array();
        foreach ($data as $key => $value) {
            $addData[] = $value;
        }
        M('TicketLog')->addAll($addData);


        $coin = json_decode($res['coin_ticket'], true);
        foreach ($coin as $k => $v) {
            $kk = explode('_', $k);
            for ($i = 0; $i < $v; $i++) {
                foreach ($this->user_arr_public as $val) {
                    $cdata[$cnum]['name'] = '满' . $kk[0] . '送' . $kk[1] . '金币';
                    $cdata[$cnum]['user_id'] = $val;
                    $cdata[$cnum]['type'] = 2;
                    $cdata[$cnum]['price'] = $kk[0];
                    $cdata[$cnum]['give_coin'] = $kk[1];
                    $cdata[$cnum]['get_time'] = NOW_TIME;
                    $cdata[$cnum]['over_time'] = NOW_TIME + $res['over_day']*3600*24;
                    $cdata[$cnum]['get_type'] = 5;
                    $cdata[$cnum]['remark'] = '充值优惠券-系统赠送';
                    $cdata[$cnum]['gift_id'] = $gifit_id;
                    $cnum++;
                }
            }
        }
        $caddData = array();
        foreach ($cdata as $key => $value) {
            $caddData[] = $value;
        }
        $rs = M('TicketLog')->addAll($caddData);
        return $rs;

    }
}