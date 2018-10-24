<?php
	set_time_limit(0);//0表示不限时
	/**
	 * 单场竞猜游戏记录
	 *
	 * @author liuweitao <cytusc@foxmail.com>
	 * @since  2016-11-24
	 */
	class SingleLogController extends CommonController {
		
		
		/**
		 * Index页显示
		 *
		 */
		public function index()
		{
			//昵称查询
			$nick_name = trim(I('nick_name'));
			if (!empty($nick_name))
			{
				$map['f.nick_name'] = ['Like',$nick_name.'%'];
			}
			//活动标题查询
			$title_name = trim(I('title_name'));
			if (!empty($title_name))
			{
				$map['st.single_title'] = ['Like',$title_name.'%'];
			}
			
			
			$querySql = M('SingleLog sl')
				->join('INNER JOIN qc_single_title st ON sl.title_id = st.id')
				->join('INNER JOIN qc_front_user f ON f.id = sl.user_id')
				->field('sl.id')
				->where($map)
				->where(['sl.id'=>['GT',0]])
				->group('sl.title_id,sl.user_id')
				->buildSql();
			$count = M()->table($querySql.' s')->count('id');
			//获取每页显示的条数
			$pageNum = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
			//获取当前的页码
			$currentPage =! empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1;
			
			if ($count > 0)
			{
				$fieldName = 'sl.title_id,sl.user_id,st.single_title,f.nick_name,
				( SELECT count(sl1.id) FROM qc_single_log sl1 INNER JOIN qc_single_quiz sq1 ON sl1.quiz_id = sq1.id AND sl1.answer = sq1.re_answer
				WHERE sl1.title_id = sl.title_id AND sl1.user_id = sl.user_id ) AS winCount ';
				
				//排序
				$_order = I('_order');
				if (! empty($_order))
				{
					$_sort = I('_sort') == 'desc' ? 'desc' : 'asc';
				}
				else
				{
					$_order = 'sl.title_id,sl.user_id';
					$_sort = 'desc';
				}
				
				$list = M('SingleLog sl')
					->join('INNER JOIN qc_single_title st ON sl.title_id = st.id')
					->join('INNER JOIN qc_front_user f ON f.id = sl.user_id')
					->field($fieldName)
					->where($map)
					->where(['sl.id'=>['GT',0]])
					->group('sl.title_id,sl.user_id')
					->order($_order.' '.$_sort)
					->limit($pageNum*($currentPage-1),$pageNum)
					->select();
			}
			
			$this->assign('list',$list);
			$this->assign ( 'totalCount', $count );//当前条件下数据的总条数
			$pageNum =empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
			$this->assign ( 'numPerPage', $pageNum ); //每页显示多少条
			$this->assign ( 'currentPage', !empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1);//当前页码
			$this->setJumpUrl();
			$this->display();
			
		}
		
		/**
		 * 竞猜纪录详情
		 *
		 */
		public function ansinfo() {
			$title_id = I('title_id');
			$user_id = I('user_id');
			$single_id = M('SingleList')->where('single_title_id = '.$title_id)->getField('id',true);
			$where['single_id'] = array('in',join(",",$single_id));
			$where['user_id'] = $user_id;
			$list = M('SingleLog')->where($where)->select();
			$en = 'A';
			for($i=0;$i<26;$i++)
			{
				$letter[$i] = $en;
				$en++;
			}
			$list = $this->my_sort($list,'quiz_id');
			foreach($list as &$val)
			{
				$single =M('SingleList')->where('id = '.$val['single_id'])->select();
				$val['game_id'] = $single[0]['game_id'];
				$val['single_title'] = M('SingleTitle')->where('id = '.$single[0]['single_title_id'])->getField('single_title');
				$val['user_name'] = M('FrontUser')->where("id = ".$user_id)->getField('nick_name');
				$quize = M('SingleQuiz')->where('id = '.$val['quiz_id'])->select();
				$quize = $quize[0];
				$val['option']= $this->option($quize);
				$ans = $this->option($quize,$val['answer']);
				$ans = $letter[$ans['aid']].'、'.$ans['option'];
				$val['ans'] = $ans;
				if($val['answer'] == $quize['re_answer'])
				{
					$val['conclusion'] = "<font color='red'>赢</font>";
				}elseif($quize['re_answer'] == '-1'){
					$val['conclusion'] = '-';
				}else{
					$val['conclusion'] = "<font color='green'>输</font>";
				}
			}
			$this->assign('list', $list);
			$this->display();
		}
		
		/*
		 * 处理json格式选项
		 */
		public function option($res,$ans = 'null')
		{
			$str = '';
			$option = json_decode($res['option'], true);
			$i = "A";
			if($ans != 'null')
			{
				return $option[$ans];
			}
			foreach ($option as $v)
			{
				$str .= $i."、".$v['option']."；";
				$i++;
			}
			return $str;
		}
		
		/*
		 * 二维数组排序
		 */
		public function my_sort($arrays,$sort_key,$sort_order=SORT_ASC,$sort_type=SORT_NUMERIC ){
			if(is_array($arrays)){
				foreach ($arrays as $array){
					if(is_array($array)){
						$key_arrays[] = $array[$sort_key];
					}else{
						return false;
					}
				}
			}else{
				return false;
			}
			array_multisort($key_arrays,$sort_order,$sort_type,$arrays);
			return $arrays;
		}
		
	}