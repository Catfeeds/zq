<?php
/**
 * M站直播观看数量统计列表
 * User: Liangzk <liangzk@qc.com>
 * Date: 2016/11/14
 * Time: 17:47
 */
class MoblieLiveController extends CommentController
{
	//列表
	public function index()
	{
		
		
		$where = ' gl.id > 0 ';
		//时间查询
		if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
			if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
				$startTime = strtotime($_REQUEST ['startTime']);
				$endTime   = strtotime($_REQUEST ['endTime']);
				$where .= ' and gl.game_time BETWEEN '.$startTime.' and '.$endTime;
			} elseif (!empty($_REQUEST['startTime'])) {
				$startTime = strtotime($_REQUEST ['startTime']);
				$where .= ' and gl.game_time >= '.$startTime;
			} elseif (!empty($_REQUEST['endTime'])) {
				$endTime = strtotime($_REQUEST['endTime']);
				$where .= ' and gl.game_time <= '.$endTime;
			}
		}
		
		//赛程类型
		$game_class = I('game_class','','string');
		if ($game_class !== '')
		{
			$where .= ' and gl.game_class = '.$game_class;
		}
		//比赛状态筛选
		$status = I('status','','string');
		if ($status !== '')
		{
			$where .= ' and gl.status = '.$status;
		}
		
		//赛事名称查询
		$union_name = I('union_name','','string');
		if ($union_name !== '')
		{
			$where .= "and gl.union_name LIKE '".$union_name."%'";
		}
		
		//球队名称查询
		$team_name = I('team_name','','string');
		if ($team_name !== '')
		{
			$where .= " and gl.team_name LIKE '".$team_name."%'";
		}
		
		//统计记录的数量
		$listCount = M()->db(1,C('DB_URL'))
			->query('SELECT COUNT(id) as listCount FROM live.live_game_live gl WHERE  '.$where
			); //获取每页显示的条数
	
		$pageNum = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
		//获取当前的页码
		$currentPage =! empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1;
		if ($listCount[0]['listCount'] > 0)
		{
			$fieldName = ' gl.id AS id,gl.game_id AS game_id,gl.union_name AS union_name,gl.team_name AS team_name,
						gl.home_name AS home_name,gl.away_name AS away_name,gl.game_time AS game_time,
						gl.check_live AS check_live,gl.status AS status,gc.name AS name ';
			$list = M()->db(1,C('DB_URL'))->query('SELECT '.$fieldName.' FROM live.live_game_live gl LEFT JOIN live.live_game_class gc ON gc.id = gl.game_class '
				.' Where '.$where
				.' ORDER BY gl.status desc,gl.game_time asc LIMIT '.$pageNum*($currentPage-1).','.$pageNum);
		}
		
		$this->assign ( 'totalCount', $listCount[0]['listCount'] );//当前条件下数据的总条数
		$pageNum =empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
		$this->assign ( 'numPerPage', $pageNum ); //每页显示多少条
		$this->assign ( 'currentPage', !empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1);//当前页码
		$this->setJumpUrl();
		$gameClassRes = M()->db(1,C('DB_URL'))->query('SELECT id,name  FROM live.live_game_class');

		$this->assign('gameClassRes',$gameClassRes);//赛事类型
		$this->assign('list',$list);
		$this->display();
	}
	
}