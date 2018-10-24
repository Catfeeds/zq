<?php
/**
 * 篮球球队
 */
use Think\Model\ViewModel;
class BkTeamListViewModel extends ViewModel
{
    public $viewFields = array(
        'game_teambk' => array(
            'id',
            'team_id',
            'team_name',
            'union_id',
            'country_id',
            'img_url',
            'status',
            'short_team_name',
            '_as' => 'g',
            '_type' => 'LEFT',
        ),
        'bk_country' => array(
            'country_name',
            '_as' => 'c',
            '_type' => 'LEFT',
            '_on' => 'g.country_id = c.country_id',
        ),
        'bk_union' => array(
            'union_name',
            'is_sub',
            '_as' => 'u',
            '_type' => 'LEFT',
            '_on' => 'g.union_id = u.union_id',
        ),

    );
}
?>