<?php
    /**
     * 异常记录列表
     *
     * @author liangzk <liangzk@qc.com>
     */
    use Think\Model\ViewModel;
    
    class ExceptionNoticeViewModel extends ViewModel
    {
        public $viewFields = array(
            
            'exception_log' => array(
                'id',
                'exception_id',
                'exception_class',
                'section_time',
                'standard',
                'status',
                'descs',
                'exception_time',
                'create_time',
                'deal_time',
                '_as'=>'el',
                '_type'=>'LEFT',
            ),

            'user'=>array(
                'account',
                'nickname',
                '_as'=>'u',
                '_type'=>'LEFT',
                '_on'=>'u.id = el.admin_id',

            ),
            
        );
    }

?>