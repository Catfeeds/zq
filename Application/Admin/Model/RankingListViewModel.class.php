<?php
/**
 *
 * 足球、篮球排行榜视图
 * User: liangzk <1343724998@qq.com>
 * Date: 2016/7/14
 * Time: 10:19
 */
use Think\Model\ViewModel;
class RankingListViewModel extends ViewModel
{
    public $viewFields = array(
        'ranking_list' => array(
            'id',
            'user_id',
            'dateType',
            'gameType',
            'begin_date',
            'end_date',
            'ranking',
            'gameCount',
            'win',
            'half',
            'level',
            'transport',
            'donate',
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