<?php

/**
 * 环信推送的消息
 * @author huangjiezhen <418832673@qq.com> 2016.01.21
 */
class EasemobMsgController extends CommonController
{

    public function index()
    {
        $map = $this->_search('EasemobMsg');
        $list = $this->_list(CM("EasemobMsg"), $map, 'id', false);
        $custom_type = array_unique(array_column($list, 'custom_type'));//msg custom type

        $groupInfo = M('EasemobUsergroup')->where(['group_type' => ['IN' , $custom_type]])->select();

        foreach ($groupInfo as $k1 => $v1){
            $groupInfoMap[$v1['group_type']] = $v1['group_desc'];
        }


        foreach ($list as $k => $v) {
//            switch ($v['platform']) {
//                case 0://所有平台
//                    $emSuccTiems = M('ApnsQueue')->where(['status' => 1, 'msg_id' =>$v['id']])->count();
//                    $list[$k]['em_nums'] = $v['users_num'];
//                    $list[$k]['um_nums'] = $v['um_users_num'];
//                    $list[$k]['em_succ_times'] = $emSuccTiems;
//                    $umSuccTiems = M('EasemobUsermsg')->where(['status' => 1, 'msg_id' =>$v['id']])->count();
//                    if($umSuccTiems == 1)
//                        $umSucc = $v['um_users_num'];
//                    else
//                        $umSucc = 0;
//                    $list[$k]['um_succ_times'] = $umSucc;
//                    break;
//
//                case 2://apns
//                    $emSuccTiems = M('ApnsQueue')->where(['status' => 1, 'msg_id' =>$v['id']])->count();
//                    $list[$k]['em_nums'] = $v['users_num'];
//                    $list[$k]['um_nums'] = 0;
//                    $list[$k]['em_succ_times'] = $emSuccTiems;
//                    $list[$k]['um_succ_times'] = 0;
//                    break;
//
//                case 3://友盟
//                    $umSuccTiems = $this->getCount(['user_type' => 2, 'platform' => 3,'status' => 1, 'msg_id' => $v['id']]);
//                    $list[$k]['em_nums'] = 0;
//                    $list[$k]['um_nums'] = $v['um_users_num'];
//                    if($umSuccTiems == 1)
//                        $umSucc = $v['um_users_num'];
//                    else
//                        $umSucc = 0;
//                    $list[$k]['um_succ_times'] = $umSucc;
//                    $list[$k]['em_succ_times'] = 0;
//                    break;
//            }

            $list[$k]['custom_type'] = $groupInfoMap[$v['custom_type']];
        }
        $this->assign('list', $list);
        $this->display();
    }

    //获取统计数据
    public function getCount($where){
        $count = M('EasemobUsermsg')->where($where)->count();
        return $count?:0;
    }

    public function msgType(){
        $this->display('Public:pushTypeDialog');
    }

}

?>