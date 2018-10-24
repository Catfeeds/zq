<?php
use Think\Model\RelationModel;
class CommonModel extends RelationModel {

	// 获取当前用户的ID
    public function getMemberId() {
        return isset($_SESSION[C('USER_AUTH_KEY')])?$_SESSION[C('USER_AUTH_KEY')]:0;
    }

   /**
     +----------------------------------------------------------
     * 根据条件禁用表数据
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param array $options 条件
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     */
    public function forbid($options,$field='status'){

        if(FALSE === $this->where($options)->setField($field,0)){
            $this->error =  L('_OPERATION_WRONG_');
            return false;
        }else {
            return True;
        }
    }

	 /**
     +----------------------------------------------------------
     * 根据条件批准表数据
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param array $options 条件
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     */

    public function checkPass($options,$field='status'){
        if(FALSE === $this->where($options)->setField($field,1)){
            $this->error =  L('_OPERATION_WRONG_');
            return false;
        }else {
            return True;
        }
    }


    /**
     +----------------------------------------------------------
     * 根据条件恢复表数据
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param array $options 条件
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     */
    public function resume($options,$field='status'){
        if(FALSE === $this->where($options)->setField($field,1)){
            $this->error =  L('_OPERATION_WRONG_');
            return false;
        }else {
            return True;
        }
    }

    /**
     +----------------------------------------------------------
     * 根据条件恢复表数据
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param array $options 条件
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     */
    public function recycle($options,$field='status'){
        if(FALSE === $this->where($options)->setField($field,0)){
            $this->error =  L('_OPERATION_WRONG_');
            return false;
        }else {
            return True;
        }
    }
    /**
     * 获取近十场竞猜结果
     *
     * @param int  $id        会员id
     * @param int  $gameType  赛事类型(1:足球   2:篮球   默认为1)
     *
     * @return  array
    */
    public function getTenGamble($id,$gameType=1,$playType=1){
        //条件会员id
        $where['user_id']    = $id;
        //赛事类型
        $Model = $gameType == 1 ? M('gamble') : M('gamblebk');
        //过滤掉未出结果的
        $where['result']     = array('in',[1,0.5,2,-1,-0.5]);
        if($gameType == 1){
            $where['play_type'] = $playType == 1 ? ['in',[1,-1]] : ['in',[2,-2]];
        }
        //近10场比赛结果
        $tenArray = $Model->where($where)->order("id desc")->limit(10)->field('result')->select();
        return $tenArray;
    }

    //近10中几
    public function getWinNum($id,$gameType=1,$playType=1)
    {
        //近十场
        $TenGamble = $this->getTenGamble($id,$gameType,$playType);
        $num = 0;
        foreach ($TenGamble as $k => $v) {
            if($v['result'] == '1' || $v['result'] == '0.5'){
                $num++;
            }
        }
        return ['num'=>count($TenGamble),'win'=>$num];
    }

/**
     * 同步更改直播开关
     *
     * @param int  $infoId       全球体育赛事信息id
     * @param string  $gameModel 赛事类型
     * @param int  $isVideo 直播状态
     *
     * @return  array
     */
    public function updateVideo($infoId,$gameModel,$isVideo){
        //初始化条件
        $where = '';
        //M站跨库操作实例化模型
        $video = M('live.Game_live','live_');
        //通过id获取到对应的gameId
        if(is_array($infoId))
        {
            //批量处理的条件生成
            $res_info = M($gameModel)->where($infoId)->getField('game_id',true);
            $condition = array ("game_id" => array ('in',$res_info));
            $res_live = $video->where($condition)->getField('id',true);
            $where = array ("id" => array ('in',$res_live));
        }else{
            //单条数据的条件生成
            $res_info = M($gameModel)->where("id = $infoId")->select();
            $game_id = $res_info[0]['game_id'];
            $res_live = $video->where("game_id = $game_id")->select();
            $live_id = $res_live[0]['id'];
            $where = "`id` = $live_id";
        }
        if($where != '')
        {
            //查询M站相应赛事id值进行状态修改
            $array['status'] = $isVideo;
            $res = $video->where($where)->save($array);
            if(false !== $res || 0 !== $res){
                $msg = "M站修改成功";
            }else{
                $msg = "M站修改失败";
            }
        }else{
            $msg = "M站没有相关数据";
        }
        return $msg;
    }

    // /**
    //  * 同步更改直播开关
    //  *
    //  * @param int  $infoId       全球体育赛事信息id
    //  * @param string  $gameModel 赛事类型
    //  * @param int  $isVideo 直播状态
    //  *
    //  * @return  array
    //  */
    // public function updateVideo($infoId,$gameModel,$isVideo){
    //     //初始化条件
    //     $where = '';
    //     $model = M()->db(1,DB_URL);
    //     //通过id获取到对应的gameId
    //     if(is_array($infoId))
    //     {
    //         //批量处理的条件生成
    //         $video = M('live.Game_live','live_');
    //         $res_info = M($gameModel)->where($infoId)->getField('game_id',true);
    //         $condition = array ("game_id" => array ('in',$res_info));
    //         $res_live = $video->where($condition)->getField('id',true);
    //         $str = '';
    //         foreach ($res_live as $val) {
    //             $str .= "'$val'".",";
    //         }
    //         $str = rtrim($str, ','); 
    //         $where = "`id` in ($str)";
    //     }else{
    //         //单条数据的条件生成
    //         $res_info = M($gameModel)->where("id = $infoId")->select();
    //         $game_id = $res_info[0]['game_id'];
    //         $res_live = $model->db(1)->query("SELECT * FROM `live_game_live` WHERE ( game_id = $game_id )");
    //         $live_id = $res_live[0]['id'];
    //         $where = "`id` = $live_id";
    //     }
    //         //查询M站相应赛事id值进行状态修改
    //         $res = $model->db(1,DB_URL)->execute("UPDATE `live_game_live` SET status = $isVideo WHERE ( $where )");
    //         if(false != $res || 0 != $res){
    //             $msg = "M站修改成功";
    //         }else{
    //             $msg = "M站无需修改或没有相关数据";
    //         }
    //         return $msg;
    // }

    /**
     * 实时更新直播地址
     *
     * @param int  $game_id        gameid
     * @param array  $videoUrl 直播地址
     *
     * @return  array
     */
    public function updateUrl($game_id,$videoUrl){
        //M站跨库操作实例化模型
        $live = M('live.Game_live','live_');
        //通过gameId获取到M站相应数据
        $res_live = $live->where("game_id = $game_id")->select();
        if($res_live != '')
        {
            //获取M站赛事信息的的id值
            $live_id = $res_live[0]['id'];
            //修改直播地址链接
            $array = array('web_video'=>$videoUrl['web_video'],'app_video'=>$videoUrl['app_video'],'game_id'=>$res_live[0]['game_id']);
            $res = $live->where("id = $live_id")->save($array);
            if(false !== $res || 0 !== $res){
                $msg = "M站修改成功";
            }else{
                $msg = "M站修改失败";
            }
        }else{
            $msg = "M站没有相关数据";
        }

        return $msg;
    }

}
?>