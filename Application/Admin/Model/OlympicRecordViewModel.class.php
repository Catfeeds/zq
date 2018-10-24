<?php
/**
 * 奥运竞猜记录列表视图模型
 *
 * @author dengweijun <406516482@qq.com>
 */
use Think\Model\ViewModel;

class OlympicRecordViewModel extends ViewModel
{
    public $viewFields = array(
		'OlympicRecord' => array(
			'id',
			'user_id',
			'quiz_id',
			'answer_id',
			'create_time',
			'odds',
			'vote_point',
			'earn_point',
			'result',
			'_as'=>'o',
			'_type'=>'LEFT',
		),
		'front_user' => array(
			'nick_name',
			'username',
			'is_robot',
			'_type'=>'LEFT',
			'_on' => 'front_user.id = o.user_id',
		),
		'OlympicQuiz' => array(
			'union_name',
			'title',
			'answer',
			'game_time',
			'_as'=>'qq',
			'_type'=>'LEFT',
			'_on' => 'qq.id = o.quiz_id',
		),
		'answer' => array(
			'title'=>'title_answer',
			'_table'=>'qc_olympic_quiz',
			'_type'=>'LEFT',
			'_as'=>'qz',
			'_on' => 'qz.id = o.answer_id',
		),
	);
}

?>