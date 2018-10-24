<?php

/**
 * 环信、友盟后台推送
 * @author huangzonglong <496331832@qq.com> 2016.01.20
 */
class EasemobUserController extends CommonController
{
    /**
     * 推送显示页
     */
    public function push()
    {
        $group = M('EasemobUsergroup')
            ->field(['group_type', 'group_desc'])
            ->group('group_type')
            ->select();

        foreach ($group as $k => $v) {
            $group[$k]['group_type'] = 'group_' . $v['group_type'];
        }

        $this->assign('userGroup', $group);
        $this->display();
    }

    /**
     * 提交推送消息
     */
    public function createQueue()
    {
        $platform   = I('platform');                    //选择平台,2-IOS,3-Andriod,0-全部
        $module     = I('msgType_module');              //消息打开行为类型
        $mValue     = I('msgType_mValue');              //对应module
        $content    = I('content');                     //推送内容
        $customType = I('userGroup_type');              //选择指定用户
        $taskTime   = (int)strtotime(I('task_time'));   //定时推送时间
        $where      = [];

        //保存推送详情，推送的时候根据详情判断发信息给哪个平台的哪些用户
        $data = [
            'module'        => $module,
            'url'           => $mValue,
            'content'       => $content,
            'platform'      => $platform,
            'create_time'   => NOW_TIME,
            'is_push'       => 0,
            'custom_type'   => $customType,
            'task_time'     => $taskTime
        ];

        $msg_id = M('EasemobMsg')->add($data);
        if (!$msg_id)
            $this->error('保存消息记录失败');

        //根据customType生成查询用户条件
        $userids = [];
        switch ((int)$customType) {
            case 0:
                break;

            case 1://7天未登录
                $beginTime  = mktime(0, 0, 0, date('m'), date('d') - 14, date('Y'));
                $endTime    = mktime(23, 59, 59, date('m'), date('d') - 7, date('Y'));
                $where['login_time'] = ['between', [$beginTime, $endTime]];
                break;

            case 2://7-14天未登录
                $beginTime  = mktime(0, 0, 0, date('m'), date('d') - 29, date('Y'));
                $endTime    = mktime(23, 59, 59, date('m'), date('d') - 15, date('Y'));
                $where['login_time'] = ['between', [$beginTime, $endTime]];
                break;

            case 3://30天未登录
                $endTime    = mktime(23, 59, 59, date('m'), date('d') - 30, date('Y'));
                $where['login_time'] = ['LT', $endTime];
                break;

            default;
                $group = M('EasemobUsergroup')->field(['user_id'])->where(['group_type' => $customType])->find();
                if($group['user_id']){
                    //将推送消息添加到通知
                    $userids = explode(',', $group['user_id']);
                    //sendMsg($userids, '推送通知', $content);
                }
        }

        //保存用户消息对应关系

        switch ((int)$platform) {
            case 0://所有平台
                $this->addApnsQueue($msg_id, $where, 'users_num', $userids);
                if ($customType == '0'){//所有用户
                    $this->sendBroadCast($msg_id, 2, 3, 3);//发友盟广播
                }else{
                    $this->addPushMsg($msg_id, array_merge($where, ['user_type' => 2, 'platform' => 3]), 'um_users_num', $userids);
                }

                break;

            case 2://IOS
                $this->addApnsQueue($msg_id, $where, 'users_num', $userids);
                break;

            case 3://安卓
                if ($customType == '0'){//所有用户
                    $this->sendBroadCast($msg_id, 2, 3, 3);//发友盟广播
                }else{
                    $this->addPushMsg($msg_id, array_merge($where, ['user_type' => 2, 'platform' => 3]), 'um_users_num', $userids);
                }

                break;

            default:
                $this->error('平台选择错误');
                break;
        }

        $this->success('保存消息成功');
    }

    /**
     * 建立友盟、环信消息队列
     * @param $insert_id
     * @param $where
     * @param string $userids
     */
    public function addPushMsg($insert_id, $where, $upField, $userids = '')
    {
        if (!$userids)
            $users = M('EasemobUser')->where($where)->getField('username', true);
        else
            $users = $userids;

        if (empty($users))
            $this->error('发送用户为空！');

        $msg_num = 0;
        foreach ($users as $k => $user_id) {
            $userMsgArr[$k]['msg_id']       = $insert_id;
            $userMsgArr[$k]['push_user']    = $user_id;
            $userMsgArr[$k]['user_type']    = $where['user_type'];
            $userMsgArr[$k]['platform']     = $where['platform'];
            $userMsgArr[$k]['status']       = 0;
            $msg_num ++;
        }

        $res = M('EasemobUsermsg')->addAll($userMsgArr);

        if (!$res)
            $this->error('保存消息记录失败');

        M('EasemobMsg')->where(['id' => $insert_id])->save([$upField => $msg_num]);
    }

    /**
     * 建立apns推送队列
     * @param $insert_id
     * @param $where
     * @param array $userids
     */
    public function addApnsQueue($insert_id, $where, $upField, $userids = [])
    {
        if (!empty($userids)){
            $device_tokens = M('ApnsUsers')
                ->field('device_token,cert_no')
                ->where(['user_id' => ['IN', $userids], 'device_token' => ['neq',''], 'status' => 1])
                ->select();
        }else{
            $where['device_token'] = ['neq', ''];
            $where['status'] = 1;
            $device_tokens = M('ApnsUsers')->field('device_token,cert_no')->where($where) ->select();
        }

        if (!empty($device_tokens)){
            $msg_num = 0;
            foreach ($device_tokens as $k => $token) {
                $pushMsgArr[$k]['msg_id']       = $insert_id;
                $pushMsgArr[$k]['device_token'] = $token['device_token'];
                $pushMsgArr[$k]['platform']     = $where['platform'];
                $pushMsgArr[$k]['cert_no']      = $token['cert_no'];
                $pushMsgArr[$k]['status']       = 0;
                $pushMsgArr[$k]['create_time']  = NOW_TIME;
                $msg_num ++;
            }

            $_msgArr = array_chunk($pushMsgArr, 500);
            foreach($_msgArr as $k2 => $v2){
                M('ApnsQueue')->addAll($v2);
            }

            M('EasemobMsg')->where(['id' => $insert_id])->save([$upField => $msg_num]);
        }
    }

    /**
     * 广播消息处理，不用查询用户
     * @param $insert_id
     * @param $user_type
     * @param $platform
     * @param $broad_cast
     */
    public function sendBroadCast($insert_id, $user_type, $platform, $broad_cast)
    {
        $userMsgArr['msg_id']       = $insert_id;
        $userMsgArr['user_type']    = $user_type;
        $userMsgArr['platform']     = $platform;
        $userMsgArr['broad_cast']   = $broad_cast;
        $userMsgArr['status']       = 0;

        $res = M('EasemobUsermsg')->add($userMsgArr);

        if (!$res)
            $this->error('保存消息记录失败');

        $count = M('EasemobUser')->where(['user_type' => 2, 'platform' => 3])->count();
        M('EasemobMsg')->where(['id' => $insert_id])->save(['um_users_num' => $count]);
    }

    //切换屏蔽状态
    public function switchBlock()
    {
        $username = $_REQUEST['username'];
        $block = $_REQUEST['block'];

        //屏蔽
        if ($block === '1') {
            $out = D('EasemobUser')->kickout($username, 1);

            if ($out === false)
                $this->error('操作失败，请联系管理员');
        }

        $save = M('EasemobUser')->where(['username' => ['eq', $username]])->save(['is_block' => $block]);

        if ($save === false)
            $this->error('操作失败，请重试');

        $this->success('操作成功');
    }

    public function group()
    {
        $list = M('EasemobUsergroup')->select();

        $this->assign('list', $list);
        $this->display();
    }

    public function groupAdd()
    {
        if (IS_POST) {
            $count = M('EasemobUsergroup')->where(['group_type' => I('group_type')])->count();

            if($count > 0)
                $this->error('已存在改类型！');

            $data['user_id'] = I('userids');
            $data['group_type'] = I('group_type');
            $data['group_desc'] = I('group_desc');
            $insert = M('EasemobUsergroup')->add($data);

            if (!$insert)
                $this->error('添加失败！');

            $this->success('添加成功！');

        } else {
            $this->display();
        }

    }

    public function groupEdit()
    {
        if(IS_POST){
            $users = explode(',', I('userids'));
            if($users){
                $data['user_id'] = implode(',', array_filter($users));
            }

            $data['group_type'] = I('group_type');
            $data['group_desc'] = I('group_desc');

            $res = M('EasemobUsergroup')->where(['id' => I('id')])->save($data);

            if (!$res)
                $this->error('编辑失败！');

            $this->success('编辑成功！');
        }else{
            $list = M('EasemobUsergroup')->where(['id' => I('id')])->select();
            $this->assign('list', $list[0]);
            $this->display();
        }

    }

    public function groupDel()
    {
        $res = M('EasemobUsergroup')->where(['id' => I('id')])->delete();
        if (!$res)
            $this->error('删除失败！');

        $this->success('删除成功！');
    }

    public function getGroup(){
        //列表过滤器，生成查询Map对象
        $map = $this->_search('EasemobUsergroup');

        //手动获取列表
        $list = $this->_list(CM("EasemobUsergroup"), $map, 'id', false);
        //获取列表
        $this->assign('list', $list);
        $tp = "Public:pushUserGroup";
        $this->display($tp);
    }
}

?>