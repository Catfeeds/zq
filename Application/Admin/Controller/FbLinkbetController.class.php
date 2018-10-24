<?php
/**
 * 动画赛程管理控制器
 *
 * @author dengweijun <406516482@qq.com>
 *
 * @since  2016-09-09
 */
class FbLinkbetController extends CommonController {
    /**
    *构造函数
    *
    * @return  #
    */
    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * Index页显示
     *
     */
    public function index() 
    {
        $map = $this->_search('FbLinkbet');
        //时间查询
        if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
            if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                $startTime = strtotime($_REQUEST ['startTime']);
                $endTime   = strtotime($_REQUEST ['endTime']);
                $map['gtime'] = array('BETWEEN',array($startTime,$endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']);
                $map['gtime'] = array('EGT',$strtotime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime']);
                $map['gtime'] = array('ELT',$endTime);
            }
        }
        $keyWord = I('keyWord');
        if(!empty($keyWord)){
            if(strlen($keyWord) > 8 ){
                $map['flash_id'] = $keyWord;
            }else{
                $map['game_id'] = $keyWord;
            }
        }
        //没搜索条件默认今天起的
        if(empty($map)){
            $map['gtime'] = ['lt',strtotime(date('Ymd'))+86400];
        }
        $list = $this->_list(CM('FbLinkbet'),$map,'gtime desc,id desc',NULL);
        $this->assign('list', $list);
        $this->display();
    }

    public function doLink()
    {
        $id = I('id');
        $vo = M('FbLinkbet')->find($id);
        if(!$vo){
            $this->error("参数错误！");
        }
        $game = M('gameFbinfo')
                ->field('id,gtime,game_id,union_name,home_team_id,home_team_name,away_team_id,away_team_name')
                ->where(['game_id'=>$vo['game_id']])
                ->find();
        
        $this->assign('game',$game);

        $this->assign('vo',$vo);
        $this->display();
    }

    public function save()
    {
        $id         = I('id');    
        $game_id    = I('game_id');
        //动画赛程表数据
        $fbLinkbet  = M('FbLinkbet')->field('id,home_team_name,away_team_name')->find($id);
        //网站赛程表数据
        $game = M('gameFbinfo')
                ->field('id,game_id,home_team_id,home_team_name,away_team_id,away_team_name')
                ->where(['game_id'=>$game_id])
                ->find();
        if(!$game){
            $this->error('请输入正确的赛程id');
        }
        //主队是否已关联
        $home_link = M('FbTeamlinkbet')->where(['team_id'=>$game['home_team_id'],'team_name_bet'=>$fbLinkbet['home_team_name']])->delete();
        //添加主队关联
        M('FbTeamlinkbet')->add([
                    'team_name'     => $game['home_team_name'],
                    'team_id'       => $game['home_team_id'],
                    'team_name_bet' => $fbLinkbet['home_team_name'],
                ]);
        //客队是否已关联
        $away_link = M('FbTeamlinkbet')->where(['team_id'=>$game['away_team_id'],'team_name_bet'=>$fbLinkbet['away_team_name']])->delete();
        //添加客队关联
        M('FbTeamlinkbet')->add([
                    'team_name'     => $game['away_team_name'],
                    'team_id'       => $game['away_team_id'],
                    'team_name_bet' => $fbLinkbet['away_team_name'],
                ]);
        //修改为已关联
        M('FbLinkbet')->where(['id'=>$id])->save([
                        'game_id'      => $game_id,
                        'game_id_new'  => $game['id'],
                        'is_link'      => 1,
                    ]);

        $this->success("关联成功！");
        
    }

}