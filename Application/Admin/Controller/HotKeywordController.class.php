<?php
/**
 * 资讯列表控制器
 *
 * @author dengweijun <406516482@qq.com>
 *
 * @since  2015-12-1
 */
use Think\Tool\Tool;

class HotKeywordController extends CommonController
{
    public $PublishClass;

    /**
     *构造函数
     *
     * @return  #
     */
    public function _initialize()
    {
        parent::_initialize();
        //获取分类
        $PublishClass = getPublishClass(0);
        $this->PublishClass = $PublishClass;
        //引用Tree类
        $PublishClass = Tool::getTree($PublishClass, $pid = 0, $col_id = 'id', $col_pid = 'pid', $col_cid = 'level');
        $this->assign('PublishClass', $PublishClass);
    }
    //热门标签表
    public function index()
    {
        if(I('is_up') == 1) $this->keyForMongo();
        //排序字段 默认为主键名
        $order = $_REQUEST ['_order'] ? : 'id';
        //排序方式默认按照倒序排列
        $sort = $_REQUEST ['_sort'] == 'asc' ? 'asc' : 'desc';
        $map = $this->_search('HotKeyword');
        unset($map['user_id']);
        //获得时间
        if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
            if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime']))
            {
                $startTime = strtotime($_REQUEST ['startTime']);
                $endTime   = strtotime($_REQUEST ['endTime'])+86400;
                $map['update_time'] = array('BETWEEN',array($startTime,$endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']);
                $map['update_time'] = array('EGT',$strtotime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime']);
                $map['update_time'] = array('ELT',$endTime);
            }
        }
        //生成搜索条件
        $keyword = I('keyword');
        if(! empty($keyword))
            $map['h.keyword'] = ['like','%'.$keyword.'%'];
        $url_name = I('url_name');
        if(! empty($url_name))
            $map['h.url_name'] = ['like','%'.$url_name.'%'];
        $id = I('id');
        if($id != '')
            $map['h.id'] = $id;
        unset($map['id'],$map['keyword']);
        $page = !empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1;
        $pageNum = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];

        $countList = M('HotKeyword')->where($map)->count();
        $this->assign('totalCount',$countList);

        $list = M('HotKeyword h')
            ->join('left join qc_publish_class p on p.id = h.class_id')
            ->field('h.*,p.name')
            ->where($map)
            ->page($page)
            ->limit($pageNum)
            ->order($order." ".$sort)
            ->select();
        $pageNum =empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
        $this->assign ( 'numPerPage', $pageNum ); //每页显示多少条
        $this->assign ( 'currentPage', !empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1);//当前页码
        $this->setJumpUrl();
        $this->assign('desc_pag',I('pageNum') ? I('pageNum')-1 : 0 );//用来页面序号的记录
        $this->assign ( 'numPerPage', $pageNum );
        $this->assign('list',$list);
        $this->display();
    }

    //热门标签表编辑
    public function key_edit()
    {
        //获取所有记录
//        $list = M('PublishClass')->select();
//        //引用Tree类
//        $list = Tool::getTree($list, $pid = 0, $col_id = 'id', $col_pid = 'pid', $col_cid = 'level');
//        $this->assign('list', $list);
        $id = I('id');
        if($id)
        {
            $res = M('HotKeyword')->where(['id'=>$id])->find();
            $this->selectData($res['class_id']);
            if($res)
                $this->assign('vo',$res);
            else
                $this->error('参数错误');

        }
        $this->display();
    }

    //热门标签保存
    public function key_save()
    {
        if ($this->getClassId() < 1) $this->error('请选择分类');
        $table = M('HotKeyword');
        $id = I('id');
        if ($id) {
            $data = [
                'class_id' => $this->getClassId(),
                'keyword' => I('keyword'),
                'url_name' => I('url_name'),
                'update_time' => time()
            ];
            $rs = $table->where(['id' => $id])->save($data);
        }else{
            $keyword = I('keyword');
            $url_name = I('url_name');
            if(empty($keyword) || empty($url_name)) $this->error('参数错误');
            if(strpos($keyword,',') !== false) $this->error('请输入单个标签');
            //检查是否已存在要保存内容
            $where['keyword'] = $keyword;
            $where['url_name'] = $url_name;
            $where['_logic'] = 'OR';
            $res = $table->where($where)->getField('keyword,url_name',true);
            $msg = [];
            if(in_array($url_name,$res)) $msg[] = "'$url_name'简写已存在";
            if(in_array($keyword,array_flip($res))) $msg[] = "'$keyword'标签已存在";
            if($msg) $this->error(implode(',',$msg));
            //对数据进行保存
            $tmp = [
                'class_id' => $this->getClassId(),
                'keyword' => $keyword,
                'url_name' => $url_name,
                'update_time' => time()
            ];
            $rs = $table->add($tmp);
        }
        if (false !== $rs) {
            S('cache_hot_keyword',null);
            //成功提示
            $this->success('保存成功!',cookie('_currentUrl_'));
        } else {
            //错误提示
            $this->error('保存失败!');
        }
    }

    /**
    +----------------------------------------------------------
     * 添加删除操作  (多个删除)
    +----------------------------------------------------------
     * @access public
    +----------------------------------------------------------
     * @return string
    +----------------------------------------------------------
     * @throws ThinkExecption
    +----------------------------------------------------------
     */

    public function delAll(){
        //删除指定记录
        $model = M("HotKeyword");
        if (!empty($model)) {
            $ids = isset($_POST['id']) ? $_POST['id'] : null;
            if ($ids) {
                $idsArr = explode(',', $ids);
                $condition = array ("id" => array ('in',$idsArr));
                if (false !== $model->where($condition)->delete()) {
                    $this->success('批量删除成功！');
                } else {
                    $this->error('批量删除失败！');
                }
            } else {
                $this->error('非法操作');
            }
        }
    }

    //删除单个
    public function delete() {
        //删除指定记录
        $model = M("HotKeyword");
        if (!empty($model)) {
            $id = $_REQUEST ['id'];
            if (isset($id)) {
                $condition = array('id' => $id);
                if (false !== $model->where($condition)->delete()) {
                    $this->success('删除成功    ！');
                } else {
                    $this->error('删除失败！');
                }
            } else {
                $this->error('非法操作');
            }
        }
    }

    //ajax获取
    public function getKeyword()
    {
        $content = I('content');
        $type = I('type',1,'int');
        $str = D('Cover')->contGetKey($content,$type);
        $this->success($str);
    }

    //获取mongo数据库数据
    public function keyForMongo()
    {
        //获取分类id
        $classId = M('PublishClass')->getField('id,name',true);
        $keyTmpbt = $keyTmpbp = $keyTmpft = $keyTmpfp = [];//关键词临时记录
        $classTmp = array_values($classId);
        $classTmpVK = array_flip($classId);

//        //篮球数据
//        //获取mongo下的分类id
        $classIdBkM = mongo('bk_union')->field('union_id,union_name')->where(['union_name'=>['in',$classTmp]])->select();
//
//        //搭建mysql与mongo对应关系
        $bkTmp = $bkMapId = $bkTeamData = $bkPlayerData = [];
        foreach($classIdBkM as $val)
        {
            if($classTmpVK[$val['union_name'][3]]) $bkTmp[$val['union_id']] = $classTmpVK[$val['union_name'][3]];
            $bkMapId[] = $val['union_id'];
        }
        $bkMap['union_id'] = ['in',$bkMapId];
//        //篮球球队
        $bkTeam = mongo('bk_team')->field('team_name,union_id')->where($bkMap)->select();
        foreach($bkTeam as $key=>$val)
        {
            $tmp = [];
            $tmp['class_id'] = $bkTmp[$val['union_id']];
            $tmp['keyword'] = getPy($val['team_name'][3],false);
            $tmp['url_name'] = getPy($val['team_name'][3]);
            $tmp['update_time'] = time();
            $keyTmpbt[] = $tmp['keyword'];
            $bkTeamData[] = $tmp;
        }
        $this->addkeyword($bkTeamData,$keyTmpbt);
//        //篮球球员
        $bkPlayer = mongo('bk_player')->field('player_name,union_id')->where($bkMap)->select();
        foreach($bkPlayer as $key=>$val)
        {
            $tmp = [];
            $tmp['class_id'] = $bkTmp[$val['union_id']];
            $tmp['keyword'] = getPy($val['player_name'][3],false);
            $tmp['url_name'] = getPy($val['player_name'][3]);
            $tmp['update_time'] = time();
            $keyTmpbp[] = $tmp['keyword'];
            $bkPlayerData[] = $tmp;
        }
        $this->addkeyword($bkPlayerData,$keyTmpbp);

        /**********************足球***********************************/
        //足球数据


        //获取mongo下的分类id
        $classIdFbM = mongo('fb_union')->field('fb_sporttery_union_name,union_id')->where(['fb_sporttery_union_name'=>['in',$classTmp]])->select();
        //搭建mysql与mongo对应关系
        $fbTmp = $fbMapId = $fbTeamData = $fbPlayerData = [];
        foreach($classIdFbM as $val)
        {
            if($classTmpVK[$val['fb_sporttery_union_name']]) $fbTmp[$val['union_id']] = $classTmpVK[$val['fb_sporttery_union_name']];
            $fbMapId[] = $val['union_id'];
        }
        $fbMap['union_id'] = ['in',$fbMapId];
        //足球球队
        $fbTeamTmp = mongo('fb_union_statistic')->field('team_id,union_id')->where($fbMap)->select();
        //处理球队与union_id关系
        $fbTeamId = $fbUnionTeam = $fbTeamData = $fbPlayerData = [];
        foreach($fbTeamTmp as $val)
        {
            $fbTeamId[] = $val['team_id'];
            $fbUnionTeam[$val['team_id']] = $val['union_id'];
        }
        $fbTeam = mongo('fb_team')->field('team_id,team_name')->where(['team_id'=>['in',$fbTeamId]])->select();
        foreach($fbTeam as $key=>$val)
        {
            $tmp = [];
            $tmp['class_id'] = $fbTmp[$fbUnionTeam[$val['team_id']]];
            $tmp['keyword'] = getPy($val['team_name'][0],false);
            $tmp['url_name'] = getPy($val['team_name'][0]);
            $tmp['update_time'] = time();
            $keyTmpft[] = $tmp['keyword'];
            $fbTeamData[] = $tmp;
        }
        $this->addkeyword($fbTeamData,$keyTmpft);
        $fbPlayer = mongo('fb_player')->field('now_team_id,player_name')->where(['now_team_id'=>['in',$fbTeamId]])->select();
        foreach($fbPlayer as $key=>$val)
        {
            $tmp = [];
            $tmp['class_id'] = $fbTmp[$fbUnionTeam[$val['now_team_id']]];
            $tmp['keyword'] = getPy($val['player_name'][0],false);
            $tmp['url_name'] = getPy($val['player_name'][0]);
            $tmp['update_time'] = time();
            $keyTmpfp[] = $tmp['keyword'];
            $fbPlayerData[] = $tmp;
        }
        $this->addkeyword($fbPlayerData,$keyTmpfp);
    }


    public function addkeyword($arr, $have)
    {
        $hasKey = M('HotKeyword')->field('keyword,url_name')->select();
        $wkey = $wurl = $wtmp = $utmp = [];
        foreach ($hasKey as $val) {
            $wkey[] = $val['keyword'];
            $wurl[] = $val['url_name'];
        }
        $wurl = array_unique($wurl);
        foreach ($arr as $key => $val) {
            if (in_array($val['keyword'], $wkey)) {
                unset($arr[$key]);
            } elseif ($val['keyword'] == '') {
                unset($arr[$key]);
            }
            if (in_array($val['url_name'], $wurl)) {
                unset($arr[$key]);
            }
            if (in_array($val['keyword'], $wtmp))
                unset($arr[$key]);
            else
                $wtmp[] = $val['keyword'];
            if (in_array($val['url_name'], $utmp))
                unset($arr[$key]);
            else
                $utmp[] = $val['url_name'];
        }
        $arr = array_merge($arr);
        M('HotKeyword')->addAll($arr);
    }

}