<?php
/**
 * 专家资讯列表视图
 * User: liangzk 《1343724998@qq.com》
 * Date: 2016/7/15
 * Time: 10:44
 */
use Think\Model\ViewModel;
class PublishListViewModel extends ViewModel
{
    public $viewFields = array(
        'publish_list' => array(
            'id',
            'user_id',
            'is_recommend',
            'web_recommend',
            'is_channel_push',
            'is_original',
            'class_id',
            'title',
            'short_title',
            'source',
            'author',
            'editor',
            'click_number',
            'status',
            'capture_url',
            'top_recommend',
            'img',
            'chose_side',
            'play_type',
            'result',
            'add_time',
            'app_time',
            'update_time',
            'app_recommend',
            'worldcup_recommend',
            'odds',
            'handcp',
            'expert_fee',
            'is_audit',
            'is_settlement',
            'sevenday_rate',
            'remarks',
            'odds_other',
            '_as' => 'p',
            '_type' => 'LEFT',
        ),
        'front_user' => array(
            'nick_name',
            'username',
            '_as' => 'f',
            '_type' => 'LEFT',
            '_on' => 'f.id = p.user_id',
        ),
        'publish_class' => array(
            'name',
            '_as' => 'pc',
            '_type' => 'LEFT',
            '_on' => 'pc.id = p.class_id',
        ),
    );
}