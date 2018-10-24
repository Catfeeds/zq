<?php
/**
 * 排行榜管理---排行奖励记录视图
 * User: lianzk <1343724998@qq.com>
 * Date: 2016/7/14
 * Time: 12:31
 * version 2.0
 */
use Think\Model\ViewModel;
class RewardLogViewModel extends ViewModel
{
  public $viewFields = array(
      'reward_log' => array(
          'id',
          'user_id',
          'ranking',
          'date_type',
          'game_type',
          'begin_date',
          'end_date',
          'coin',
          '_as'=>'r',
          '_type'=>'LEFT',
      ),
      'front_user' => array(
          'username',
          'nick_name',
          '_as'=>'f',
          '_on'=>'f.id = r.user_id',
      ),

  );
}
?>