<?php
/**
 * 推荐位视图模型
 * 
 * @author dengweijun <406516482@qq.com>
 */
use Think\Model\ViewModel;

class RecommendViewModel extends ViewModel
{
    public $viewFields = array(
        'recommend' => array(
            'id',
            'class_id',
            'title',
            'url',
            'img',
            'add_time',
            'type',
            'status',
            'sort',
            'remark',
            '_type'=>'LEFT',
        ),  
        'recommend_class' => array(
            'name',
            '_on' => 'recommend.class_id = recommend_class.id',
        ),  
    );
}

?>