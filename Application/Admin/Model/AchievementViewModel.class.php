<?php
/**
 * 任务系统管理的我的成就视图
 * @author liangzk <1343724998@QQ.COM>
 * @since v2.0 2016-06-27
 */
use Think\Model\ViewModel;
class AchievementViewModel extends ViewModel{

    public  $viewFields=array(
            'mission'=>array(
                'id',
                'sign',
                'num',
                'status',
                'create_time',
                '_as'=>'m',
                '_type'=>'LEFT',

                ),
            'mission_log'=>array(
                'id',
                'user_id',
                'mid',
                'mtype',
                'create_time',
                '_as'=>'ml',
                '_type'=>'LEFT',
                '_on'=>'ml.mid = m.id'
                ),

        );
}


?>