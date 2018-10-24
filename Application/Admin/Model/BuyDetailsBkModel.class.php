<?php
/**
 * 竞猜记录购买详情列表视图模型
 *
 * @author liangZhiKe <1343724998@qq.com>
 */
use Think\Model\ViewModel;

class BuyDetailsBkModel extends ViewModel
{
    public $viewFields = array(

        'quiz_log' => array(
            'id',
            'user_id',
            'cover_id',
            'coin',
            'log_time',
            'platform',
            'ticket_id',
            '_as'=>'q',

        ),
        'front_user' => array(
            'nick_name',
            'is_robot'=>'is_robot',
            'device_token',
            'last_ip',
            '_type'=>'LEFT',
            '_as'=>'f',
            '_on' => 'f.id = q.user_id',
        ),
        'fu' => array(
            '_table'=>"qc_front_user",
            'nick_name'=>'nick_name_by',
            'is_robot'=>'is_robot_by',
            'device_token'=> 'device_token_by',
            'last_ip'     => 'last_ip_by',
            '_type'=>'LEFT',
            '_as'=>'fu',
            '_on' => 'fu.id = q.cover_id',
        ),
        'gamblebk' => array(
            'union_name',
            'game_date',
            'game_time',
            'home_team_name',
            'score',
            'play_type',
            'chose_side',
            'away_team_name',
            'game_id',
            'handcp',
            'vote_point',
            'tradeCoin',
            'result',
            '_as'=>'g',
            '_type'=>'LEFT',
            '_on'=>'q.gamble_id = g.id',
        ),

    );
}

?>