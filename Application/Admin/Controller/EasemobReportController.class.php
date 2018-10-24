<?php
/**
 * 聊天室举报信息管理
 * @author huangjiezhen <418832673@qq.com> 2016.04.13
 */
class EasemobReportController extends CommonController
{
    //屏蔽或踢出聊天室
    public function kickout()
    {
        $username = $_REQUEST['username'];
        $outType  = $_REQUEST['outType'];
        $out = D('EasemobUser')->kickout($username,$outType);

        if ($out === false)
            $this->error('操作失败，请联系管理员');

        M('EasemobChatlist')->where(['user_id'=>['eq', $username]])->save(['is_done'=>1]);

        //标识屏蔽用户相关联的举报为已处理
        $save = M('EasemobReport')->where(['username'=>['eq',$username],'user_type' => 1])->save(['is_done'=>1]);

        if ($save === false)
            $this->error('操作失败，请重试');

        $this->success('操作成功');
    }
}

?>