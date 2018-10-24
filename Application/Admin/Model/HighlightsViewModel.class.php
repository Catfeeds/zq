<?php
/**
 * 集锦列表视图
 */
use Think\Model\ViewModel;
class HighlightsViewModel extends ViewModel
{
    public $viewFields = array(
        'highlights' => array(
            'id',
            'class_id',
            'user_id',
            'game_id',
            'union_id',
            'game_type',
            'title',
            'remark',
            'click_num',
            'like_num',
            'like_user',
            'add_time', 
            'label',
            'sort',
            'status',
            'is_best', 
            'is_prospect', 
            'is_recommend',
            'top_recommend',
            'img',
            'm_url',
            'm_ischain', 
            'web_url', 
            'web_ischain',
            'app_url',
            'app_ischain',
            'app_isbrowser', 
            '_as' => 'p',
            '_type' => 'LEFT',
        ),
        'front_user' => array(
            'nick_name',
            '_as' => 'f',
            '_type' => 'LEFT',
            '_on' => 'f.id = p.user_id',
        ),
    );
}