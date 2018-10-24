<?php
/**
 *ETC车宝合作数据统计的显示参与竞猜用户的信息
 *
 * @author liangzk <1343724998@qq.com>
 * @since 2016-06-15
 *
 */
use Think\Model\ViewModel;
class partakeViewModel extends ViewModel{

    public $viewFields=array(
        'etc_quiz'=>array(
            'id',
            'user_id',
            'game_id',
            'bet_coin',
            'add_time',
            '_as'=>'q',
            '_type'=>'LEFT',
            ),
        'game_fbinfo'=>array(
            'home_team_name',
            'away_team_name',
            '_as'=>'g',
            '_type'=>'LEFT',
            '_on'=>'q.game_id = g.game_id',
            ),



        );


}


?>