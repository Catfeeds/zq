<?php
class EasemobUserModel extends CommonModel {
    protected $_link =  array(
        'FrontUser' => [
            'mapping_type'   => self::BELONGS_TO,
            'foreign_key'    => 'uid',
            'mapping_fields' => 'nick_name',
            'as_fields'      => 'nick_name:binduser',
        ],
    );

    //屏蔽或踢出聊天室
    public function kickout($username,$outType)
    {
        import('Vendor.Easemob.Easemob');
        $Easemob = new \Easemob(C('Easemob'));

        //踢出所有的聊天室
        $joinRooms = @$Easemob->getChatRoomJoined($username)['data'];

        foreach ($joinRooms as $k => $v)
        {
            //发送透传消息
            $Easemob->sendCmd($from="admin",$target_type='chatrooms',$target=[$v['id']],$action='kickout',$ext=['username'=>$username]);

            // $info = $Easemob->deleteChatRoomMember($v['id'],$username);
        }

        //设置屏蔽
        if ($outType == 1){
            $data['is_block'] = 1;
            $data['block_time'] = time();
        }else{
            $data['block_time'] = time() + 60 * 10; //block 10 mins
        }

         M('EasemobUser')->where(['username' => ['EQ', $username]])->save($data);
    }

}