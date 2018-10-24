<?php
/**
 * 单场竞猜游戏列表及活动内容编辑
 *
 * @author liuweitao <cytusc@foxmail.com>
 * @since  2016-11-24
 */

class SingleListController extends CommonController{
	/**
     * 竞猜列表
     * @return string     
    */
    public function index()
    {
        $map  = $this->_search('SingleTitle');
        $list = $this->_list(CM('SingleTitle'),$map);
        foreach ($list as $k => $v) {
            $listId[] = $v['id'];
        }
        //赛事
        $SingleList = M('SingleList')->where(['single_title_id'=>['in',$listId]])->select();
        foreach ($SingleList as $k => $v) {
            $quizId[] = $v['id'];
        }
        //竞猜
        $SingleQuiz = M('SingleQuiz')->where(['single_id'=>['in',$quizId]])->order('sort asc')->select();
        foreach ($SingleList as $k => $v) {
            foreach ($SingleQuiz as $kk => $vv) {
                if($v['id'] == $vv['single_id']){
                    $option = json_decode($vv['option'],true);
                    $optionStr = [];
                    foreach ($option as $o => $oo) {
                        if($vv['re_answer'] == $oo['aid']){
                            $str = "<font color='red'>" . ($oo['aid'] + 1) .'、'.$oo['option'] . "</font>";
                        }else{
                            $str = ($oo['aid'] + 1) .'、'.$oo['option'];
                        }
                        $optionStr[] = $str;
                    }
                    $vv['option'] = implode("; ", $optionStr);
                    $SingleList[$k]['quiz'][] = $vv;
                }
            }
        }
        //组装数据
        foreach ($list as $k => $v) {
            foreach ($SingleList as $kk => $vv) {
                if($v['id'] == $vv['single_title_id']){
                    $list[$k]['game'][] = $vv;
                }
            }
        }
        //dump($list);
        $this->assign('list',$list);
        $this->display();
    }

    /**
     *
     * 添加竞猜内容
     *
     * @return string
     *
     */
    function editquiz() {
        $titleId   = I('titleId'); //活动id
        $id        = I('id');      //竞猜id
        $gameid    = I('gameid');  //赛事id
        $game_type = I('game_type'); //赛事类型
        $this->assign('titleId',$titleId);
        $type      = $_REQUEST['type']; //操作类型
        $single    = M('SingleQuiz');
        if($type == "add")
        {
            $SingleTitle = M('SingleTitle')->find($titleId);
            $vo['single_title'] = $SingleTitle['single_title'];
            if (!$vo){
                $this->error('参数错误');
            }
        }elseif($type == 'post'){
            if($gameid)
            {
                //赛事推荐
                $gameinfo = $this->gameinfo( $gameid,$game_type );
                $listData['single_title_id'] = $titleId;
                $listData['game_id']         = $gameid;
                $listData['home_team_name']  = $gameinfo['home_team_name'];
                $listData['away_team_name']  = $gameinfo['away_team_name'];
                $listData['game_time']       = $gameinfo['gtime'];
                $listData['add_time']        = time();

                if($id)
                {
                    M('SingleList')->where('id='.M('SingleQuiz')->where('id='.$_POST['id'])->getField('single_id'))->save($listData);
                }else{
                    $single_id = M('SingleList')->add($listData);
                }
            }
            //竞猜选项
            $arr = array();
            for ($i=0;$i<=max(array_keys(I('ids')));$i++)
            {
                $arr[$i]['aid'] = $i;
                $arr[$i]['option'] = I('option')[$i];
                $arr[$i]['num'] = I('num')[$i];
            }
            
            $data['question']  = empty($_POST['question'])?'-':I('question');
            $data['option']    = json_encode($arr);
            $data['re_answer'] = I('answer','-1','int');
            $data['sort']      = I('sort');
            $data['add_time']  = time();
            if($id)
            {
                $rs = M('SingleQuiz')->where(['id'=>$id])->save($data);
            }else{
                $data['single_id'] = $single_id ? : M('SingleList')->where(['single_title_id'=>$titleId])->getField('id');
                $rs = M('SingleQuiz')->add($data);
            }
            if($rs){
                $this->success('保存成功');
            }else{
                $this->error('保存失败');
            }

        }elseif($type == 'edit'){
            $vo = M('SingleQuiz')->where(['id'=>$id])->find();
            $SingleList = M('SingleList')->where("id = ".$vo['single_id'])->find();
            $vo['single_title'] = M("SingleTitle")->where('id = '.$SingleList['single_title_id'])->getField('single_title');
            $vo['option'] = json_decode($vo['option'],true);
            $vo['game_id'] = $SingleList['game_id'];
            foreach($vo['option'] as &$val)
            {
                $peop = M('SingleLog')->where("quiz_id = ".$vo['id']." AND answer = ".$val['aid'])->count();
                $val['peop'] = $peop;
            }
            if(I('mult'))
            {
                $res = M('SingleList')->where("id = ".M('SingleQuiz')->where('id = '.I('id'))->getField('single_id'))->select();
                $res = $res[0];
                $str = "<td>对阵赛事: ". switchName(0,$res['home_team_name']) . "&nbsp;<font color='red'>VS</font>&nbsp;". switchName(0,$res['away_team_name'])."</td>".
                    "</tr>".
                    "<tr>".
                    "<td>开赛时间: ".date("Y-m-d H:i",$res['game_time']) . "</td>";
                $vo['str'] = $str;
            }
        }
        if(I('mult')) $this->assign('mult','1');
        //查出所有选项
        $this->assign('vo',$vo);
        $this->display ('add');
    }

    public function savesingle(){
        $id = I('id');
        $model = D('SingleTitle');
        if (!$data = $model->create()) {
            $this->error($model->getError());
        }
        $data['end_time'] = strtotime($data['end_time']);
        if (empty($id)) {
            $data['add_time'] = time();
            //为新增
            $rs = $model->add($data);

            $gameinfo = $this->gameinfo(I('game_id'),I('game_type'));
            $SingleList['single_title_id'] = $rs;
            $SingleList['game_id'] = I('game_id');
            $SingleList['home_team_name'] = $gameinfo['home_team_name'];
            $SingleList['away_team_name'] = $gameinfo['away_team_name'];
            $SingleList['game_time'] = $gameinfo['gtime'];
            $SingleList['add_time'] = time();

            $rs2 = M('SingleList')->add($SingleList);
            if(I('single_multiple'))
            {
                $quiz = array();
                $arr = array();
                for ($i=0;$i<=max(array_keys(I('ids')));$i++)
                {
                    $arr[$i]['aid'] = $i;
                    $arr[$i]['option'] = I('option')[$i];
                    $arr[$i]['num'] = I('num')[$i];
                }
                $quiz['single_id'] = $rs2;
                $quiz['question']  = I('question');
                $quiz['option']    = json_encode($arr);
                $quiz['re_answer'] = I('answer','-1','int');
                $quiz['add_time']  = time();
                $rs3 = M('SingleQuiz')->add($quiz);
            }
        }else{
            //为修改
            $rs = $model->save($data);
        }
        if (false !== $rs) {
            //成功提示
            $this->success('保存成功!',cookie('_currentUrl_'));
        } else {
            //错误提示
            $this->error('保存失败!');
        }
    }

	/**
     * 删除指定记录
	 *
     * @return string
	 *
    */
	public function del() {
		$id = $_REQUEST['id'];
		if (isset ( $id )) {
			$condition = array ("id" => $id );
            $quiz = M('SingleQuiz');
            if($_REQUEST['mult'])
            {
                $single_id = $quiz->where($condition)->getField('single_id');
                if (false !== M('SingleList')->where ( "id = ".$single_id)->delete ()) {

                } else {
                    $this->error ( '删除失败' );
                }
            }
			if (false !== $quiz->where ( $condition )->delete ()) {

				$this->success ( '删除成功' );
			} else {
				$this->error ( '删除失败' );
			}	
		} else {
			$this->error ( '非法操作' );
		}
	}

    function addsingle() {
        $id = I('id');
        if($id){
            $vo = M('SingleTitle')->find($id);
            $this->assign('vo',$vo);
        }
        $this->display();
    }

    /**
     * ajax查询主队客队
     * @return string
     *
     */
    function ajaxget()
    {
        $gameid = $_GET['gid'];
        $res = $this->gameinfo($gameid,$_GET['type']);
        if($res)
        {
            $gameinfo['home'] =  switchName(0,$res['home_team_name']);
            $gameinfo['away'] = switchName(0,$res['away_team_name']);
            $gameinfo['gtime'] = date("Y-m-d H:i",$res['gtime']);
            echo json_encode($gameinfo);exit;
        }else{
            echo json_encode("没有相关数据");exit;
        }

    }

    /**
     * 查询赛事信息
     * @return array
     *
     */
    function gameinfo($gameid = "",$table) {
        $gid = $gameid;
        switch ($table)
        {
            case 1:
                $type = 'fbinfo';
                break;
            case 2:
                $type = 'bkinfo';
                break;
        }
        $info = M("game_".$type);
        $res = $info->where("game_id = ".$gameid)->select();
        if(empty($res))
        {
            return false;
        }
        return $res[0];

    }

    /*
     * 竞猜活动问答列表
     * @return string
     */
    public function quizlist()
    {
        $single_title = I('single_title');
        if (!empty($single_title))
        {
            $map['st.single_title'] = ['Like',trim($single_title).'%'];
        }
        $id = empty($_REQUEST['num'])?I('id'):I('num');
        $en = 'A';
        for($i=0;$i<26;$i++)
        {
            $eng[$i] = $en;
            $en++;
        }
        $single = M('SingleList')->where('single_title_id ='.$id)->getField('id');
        //列表过滤器，生成查询Map对象
        $map['single_id'] = $single;
        if(I('time'))
        {
            $str = I('time');
            $arr = explode("-",$str);
            $start=mktime(0,0,0,date($arr[1]),date("$arr[2]"),date("$arr[0]"));
            $end=mktime(0,0,0,date($arr[1]),date("$arr[2]")+1,date("$arr[0]"))-1;
            $map['add_time'] = array(array('gt',$start),array('lt',$end));
        }
        if(I('question'))
        {
            $quiz_id = M('SingleQuiz')->where("question like '".I('question')."%'")->getField('id',true);
            $map['id'] = array('in',join(",",$quiz_id));
        }
        $answer = I('answer','-1','int');
        if($answer != -1)
        {
            if($answer)
            {
                $where['re_answer'] = array('eq',-1);
            }else{
                $where['re_answer'] = array('egt',0);
            }
            $ans = M('SingleQuiz')->where($where)->getField('id',true);
            $map['id'] = array('in',join(",",$ans));
        }
        $status = I('status','-1','int');
        if($status != -1)
        {
            $map['status'] = I('status');
        }
        //获取列表
        $list = $this->_list(CM("SingleQuiz"), $map);
        if($list)
        {
            foreach($list as &$val)
            {
                $str = '';
                $option = json_decode($val['option'], true);
                $i = 1;
                foreach ($option as $v)
                {
                    if($val['re_answer'] == $v['aid'])
                    {
                        $str .= $eng[$v['aid']]."、<font color='red'>".$v['option']."</font>；";
                    }else{
                        $str .= $eng[$v['aid']]."：".$v['option']."；";
                    }
                    $i++;
                }
                $val['option'] = $str;
            }
        }
        $this->assign('single',$single);
        $this->assign('list',$list);
        $this->assign('num',$id);
        $this->display();
    }

    public function mostlist(){
        $id = empty($_POST['num'])?I('id'):I('num');
        $game_id = $_POST['game_id']?" AND game_id = ".I('game_id'):'';
        $singleid = M('SingleList')->where("single_title_id =".$id.$game_id)->getField('id',true);
        $map['single_id']= array('in',join(",",$singleid));
        $answer = I('answer','-1','int');
        if($answer != -1)
        {
            if($answer)
            {
                $where['re_answer'] = array('eq',-1);
            }else{
                $where['re_answer'] = array('egt',0);
            }
            $ans = M('SingleQuiz')->where($where)->getField('id',true);
            $map['id'] = array('in',join(",",$ans));
        }
        $status = I('status','-1','int');
        if($status != -1)
        {
            $map['status'] = I('status');
        }
        $list= M('SingleQuiz')->where($map)->select();
        if($list)
        {
            $en = 'A';
            for($i=0;$i<26;$i++)
            {
                $eng[$i] = $en;
                $en++;
            }
            $single = M('SingleList');
            foreach($list as &$val)
            {
                $str = '';
                $option = json_decode($val['option'], true);
                $i = 1;
                foreach ($option as $v)
                {
                    if($val['re_answer'] == $v['aid'])
                    {
                        $str .= $eng[$v['aid']]."、<font color='red'>".$v['option']."</font>；";
                    }else{
                        $str .= $eng[$v['aid']]."：".$v['option']."；";
                    }
                    $i++;
                }
                $val['option'] = $str;
                $res = $single->where('id ='.$val['single_id'])->select();
                $res = $res[0];
                $val['game_id'] = $res['game_id'];
                $val['home_team_name'] = $res['home_team_name'];
                $val['away_team_name'] = $res['away_team_name'];
                $val['game_time'] = $res['game_time'];
                $val[''] = $res[''];
            }
        }
        $this->assign('list',$list);
        $this->assign('titleid',I('sid'));
        $this->assign('num',$id);
        $this->display();
    }

    public function delsingle()
    {
        $id = $_GET['id'];
        if (isset ( $id )) {
            if (false !== M('SingleTitle')->where ( "id = ".$id )->delete ()) {

                $SingleListId = M('SingleList')->where ( ['single_title_id'=>$id ] )->getField ('id',true);

                M('SingleList')->where ( ['single_title_id'=>$id ] )->delete ();
                
                M('SingleQuiz')->where ( ['single_id'=>['in',$SingleListId]] )->delete ();
                $this->success ( '删除成功' );
            }else {
                $this->error ( '删除失败' );
            }
        } else {
            $this->error ( '非法操作' );
        }
    }

    //禁用
    public function forbid() {
        $model = CM('SingleTitle');
        $pk = $model->getPk();
        $id = $_REQUEST [$pk];
        $condition = array($pk => array('in', $id));
        $list = $model->forbid($condition);
        if ($list !== false) {
            $this->success('状态禁用成功',cookie('_currentUrl_'));
        } else {
            $this->error('状态禁用失败！');
        }
    }
    //启用
    public function resume() {
        //恢复指定记录
        $model = CM('SingleTitle');
        $pk = $model->getPk();
        $id = $_GET [$pk];
        $condition = array($pk => array('in', $id));
        if (false !== $model->resume($condition)) {
            $this->success('状态恢复成功！',cookie('_currentUrl_'));
        } else {
            $this->error('状态恢复失败！');
        }
    }

}
?>