<?php

/**
 * 文章关键词内链表
 *
 * @author liuweitao   <liuwt@qqty.com>
 *
 * @since  2018-04-17
 */
class VideoShareurlController extends CommonController
{

    /**
     * 分类列表
     * @return string
     */
    public function index()
    {
        $type = I('tabType')?:1;
        if($type == 1)
            $joinTab = 'qc_game_fbinfo';
        else
            $joinTab = 'qc_game_bkinfo';

        $map['v.game_type'] = $type;
        //排序字段 默认为主键名
        $order = $_REQUEST ['_order'] ? : 'id';
        //排序方式默认按照倒序排列
        $sort = $_REQUEST ['_sort'] == 'asc' ? 'asc' : 'desc';
        //名字查询
        $name = trim(I('nick_name'));
        if (!empty($name)) {
            $map['u.nick_name'] = ['Like', $name . '%'];
        }
        //数据id
        $id = trim(I('id'));
        if (!empty($id)) {
            $map['v.id'] = $id;
        }

        //视频状态
        $status = trim(I('status'));
        if ($status !== '') {
            $map['v.status'] = $status;
        }

        //添加时间查询
        if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
            if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                $startTime = strtotime($_REQUEST ['startTime']);
                $endTime   = strtotime($_REQUEST ['endTime'])+86400;
                $map['v.add_time'] = array('BETWEEN',array($startTime,$endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']);
                $map['v.add_time'] = array('EGT',$strtotime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime'])+86400;
                $map['v.add_time'] = array('ELT',$endTime);
            }
        }

        //审核时间查询
        if(!empty($_REQUEST ['upstartTime']) || !empty($_REQUEST ['upendTime'])){
            if(!empty($_REQUEST ['upstartTime']) && !empty($_REQUEST ['upendTime'])){
                $upstartTime = strtotime($_REQUEST ['upstartTime']);
                $upendTime   = strtotime($_REQUEST ['upendTime'])+86400;
                $map['v.update_time'] = array('BETWEEN',array($upstartTime,$upendTime));
            } elseif (!empty($_REQUEST['upstartTime'])) {
                $strtotime = strtotime($_REQUEST ['upstartTime']);
                $map['v.update_time'] = array('EGT',$strtotime);
            } elseif (!empty($_REQUEST['upendTime'])) {
                $upendTime = strtotime($_REQUEST['upendTime'])+86400;
                $map['v.update_time'] = array('ELT',$upendTime);
            }
        }

        $count = M('VideoShareurl v')->join('LEFT JOIN '.$joinTab.' g on g.game_id = v.game_id')->join('LEFT JOIN qc_front_user u on u.id = v.user_id')->where($map)->count('v.id');
        //获取每页显示的条数
        $pageNum = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
        //获取当前的页码
        $currentPage = !empty($_REQUEST[C('VAR_PAGE')]) ? $_REQUEST[C('VAR_PAGE')] : 1;
        if ($count > 0) {
            $list = M('VideoShareurl v')
                ->field('v.*,g.home_team_name,g.away_team_name,g.gtime,g.union_name,u.nick_name')
                ->join('LEFT JOIN '.$joinTab.' g on g.game_id = v.game_id')
                ->join('LEFT JOIN qc_front_user u on u.id = v.user_id')
                ->where($map)
                ->order($order." ".$sort)
                ->limit($pageNum * ($currentPage - 1), $pageNum)
                ->select();
            foreach ($list as $key =>$val)
            {
                switch($val['status'])
                {
                    case 1:
                        $list[$key]['status'] = '已通过';
                        break;
                    case 2:
                        $list[$key]['status'] = '未通过';
                        break;
                    default:
                        $list[$key]['status'] = '待审核';
                        break;
                }
            }
        }
        $this->assign('list', $list);
        $this->assign('totalCount', $count);//当前条件下数据的总条数
        $this->assign('numPerPage', $pageNum); //每页显示多少条
        $this->assign('currentPage', $currentPage);//当前页码
        $this->assign('tabType',$type);
        $this->setJumpUrl();
        $this->display();
    }

    /**
     * 编辑指定记录
     *
     * @return string
     *
     */
    public function edit()
    {
        if (I('id')) {
            //获取所有记录
            $list = M('VideoShareurl')->where(['id' => I('id')])->find();
            $this->assign('vo', $list);
        }
        $this->display();
    }

    /**
     * 添加/编辑分类表数据
     * @return #
     */
    public function save()
    {
        //是否为修改标志
        $id = I('id');
        //检验数据
        $data['status'] = I('status');
        if (!empty($id)) {
            $data['update_time'] = time();
            $rs = M('VideoShareurl')->where(['id' => $id])->save($data);
            //审核通过或者未通过给用户发布消息
            //组装消息标题
            $urlInfo = M('VideoShareurl')->where(['id' => $id])->find();
            $user_id = $urlInfo['user_id'];
            //查询足球or篮球赛事信息
            if($urlInfo['game_type'] == 1)
                $gameTab = 'GameFbinfo';
            else
                $gameTab = 'GameBkinfo';
            $game = M($gameTab)->field('home_team_name,away_team_name')->where(['game_id'=>$urlInfo['game_id']])->find();
            //如果主客队名数据可查询出来就发送消息给用户
            if(!empty($game['home_team_name']) && !empty($game['away_team_name']))
            {
                //生成消息标题
                $title = '直播视频：'.explode(',',$game['home_team_name'])[0].'VS'.explode(',',$game['away_team_name'])[0];
                switch(I('status'))
                {
                    case 1:
                        sendMsg($user_id,$title,'您分享的直播链接已审核通过，感谢支持');
                        break;
                    case 2:
                        sendMsg($user_id,$title,'您分享的直播源链接审核未通过，如有问题，请联系客服，谢谢');
                        break;
                }
            }
        }
        if (false !== $rs) {
            S('cache_publish_class', null);
            //成功提示
            $this->success('保存成功!', cookie('_currentUrl_'));
        } else {
            //错误提示
            $this->error('保存失败!');
        }
    }

    //删除单个
    public function delete()
    {
        //删除指定记录
        $model = M("VideoShareurl");
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

}