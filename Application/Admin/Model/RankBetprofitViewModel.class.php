<?php
/**
 * 竞彩盈利榜视图
 */
use Think\Model\ViewModel;
class RankBetprofitViewModel extends ViewModel
{
    public $viewFields = array(
        'RankBetprofit' => array(
            'id',
            'user_id',
            'dateType',
            'gameType',
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