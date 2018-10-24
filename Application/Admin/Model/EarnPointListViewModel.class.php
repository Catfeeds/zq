<?php
/**
 * 盈利榜视图
 */
use Think\Model\ViewModel;
class EarnPointListViewModel extends ViewModel
{
    public $viewFields = array(
        'EarnPointList' => array(
            'id',
            'user_id',
            'dateType',
            'gameType',
            'listDate',
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
        'frontUser' =>array(
            'username',
            'nick_name',
            'is_robot',
            '_as'=>'f',
            '_on'=>'f.id = r.user_id',
        ),


    );
}
?>