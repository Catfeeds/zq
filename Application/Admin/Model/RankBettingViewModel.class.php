<?php
/**
 * 足球竞彩排行榜视图
 * @author dengwj <406516482@qq.com>
 * @since  2016-10-27
 */
use Think\Model\ViewModel;
class RankBettingViewModel extends ViewModel
{
    public $viewFields = array(
        'rank_betting' => array(
            'id',
            'user_id',
            'dateType',
            'listDate',
            'ranking',
            'gameCount',
            'win',
            'transport',
            'winrate',
            'pointCount',
            '_as'=>'r',
            '_type'=>'LEFT',
        ),
        'front_user' =>array(
            'username',
            'nick_name',
            'is_robot',
            '_as'=>'f',
            '_on'=>'f.id = r.user_id',
        ),


    );
}
?>