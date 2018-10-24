<?php

/**
 * 发起者赛事玩法列表
 * @since
 */
use Think\Controller;

class CupquizGambleController extends CommonController
{
    public function index()
    {
        $name = trim(I('act_name'));
        if (!empty($name)) {
            $id = M('CupquizActivities')->where(['title' => ['Like', '%' . $name . '%']])->getField('id', true);
            $map['CG.act_id'] = ['IN', $id];
        }

        $nickname = trim(I('nickname'));
        if (!empty($nickname)) {
            $id = M('FrontUser')->where(['nick_name' => ['Like', '%' . $nickname . '%']])->getField('id', true);

            $csid = M('CupquizSponsor')->alias('CS')->where(['user_id' => ['IN', $id]])->getField('id', true);

            $map['CG.launch_id'] = ['IN', $csid];
        }

        $user_id = trim(I('user_id'));
        if (!empty($user_id)) {
            $csid = M('CupquizSponsor')->alias('CS')->where(['user_id' => $user_id])->getField('id', true);


            $map['CG.launch_id'] = ['IN', $csid];
        }

        $count = M('CupquizGamble')->alias('CG')->where($map)->count();

        //获取每页显示的条数
        $pageNum = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
        //获取当前的页码
        $currentPage = !empty($_REQUEST[C('VAR_PAGE')]) ? $_REQUEST[C('VAR_PAGE')] : 1;
        $list = M('CupquizGamble')
            ->alias('CG')
            ->field('CG.id,CG.launch_id, CG.act_id, CG.game_id, CG.union_name, CG.gtime, CG.home_team_name, 
            CG.away_team_name, CG.play_type, CG.chose_side, CG.result, CG.result_time, U.nick_name,U.username,CA.title,CA.game_options')
            ->join('LEFT JOIN qc_cupquiz_sponsor as CS ON CS.id = CG.launch_id')
            ->join('LEFT JOIN qc_front_user as U ON U.id = CS.user_id')
            ->join('LEFT JOIN qc_cupquiz_activities as CA ON CA.id = CG.act_id')
            ->where($map)
            ->order('CG.id DESC')
            ->limit($pageNum)
            ->page($_REQUEST[C('VAR_PAGE')])
            ->select();

        //玩法
        $_playType = M('CupquizPlaytype')->select();

        foreach ($_playType as $pk => $pv){
            $pv['options'] = json_decode($pv['options']);
            $playType[$pv['id']] = $pv;
        }

        foreach($list as $k => $v){
            $list[$k]['play_type_name'] = $playType[$v['play_type']]['name'];
            //是否已经设置玩法答案
            $game_options = json_decode($v['game_options']);
            foreach($game_options as $gk => $gv){
                if($gv[0] == $v['game_id'] && $v['play_type'] == $gv[1]){
                    if(isset($gv[2])){
                        foreach ($playType[$v['play_type']]['options'] as $pk => $pv){
                            if($pv[0] == $gv[2]){
                                $list[$k]['answer'] = $gv[2] . ".$pv[1]";
                            }
                        }
                    }
                }
            }

            //用户答案显示
            foreach ($playType[$v['play_type']]['options'] as $pk2 => $pv2){
                if($pv2[0] == $v['chose_side']){
                    $list[$k]['chose_side'] = $v['chose_side'] . ".$pv2[1]";
                }
            }

//            $list[$k]['bg'] = $v['act_id'] % 2 == 0 ? 'style="background-color: #eff9fd"' : '';
        }
        $this->assign('list', $list);
        $this->assign('playType', $playType);
        $this->assign('totalCount', $count);//当前条件下数据的总条数
        $this->assign('numPerPage', $pageNum); //每页显示多少条
        $this->assign('currentPage', $currentPage);//当前页码
        $this->setJumpUrl();
        $this->display();
    }

    //结算
    public function settle(){
        var_dump(I(''));exit;
    }

    //编辑
    public function edit()
    {
        $id = I('id');
        $model = M('CupquizGamble');
        $vo = $model->where(['id'=>$id])->find();
        if(!$vo){
            $this->error('参数错误!');
        }
        $this->assign('vo',$vo);
        $this->display();
    }

    public function update()
    {
        $model =  M('CupquizGamble');
        if (false === $model->create()) {
            $this->error($model->getError());
        }

        // 更新数据
        $list = $model->save();
        if (false !== $list) {
            //成功提示
            $this->success('编辑成功!',cookie('_currentUrl_'));
        } else {
            //错误提示
            $this->error('编辑失败!');
        }
    }

    public function forbidAll()
    {
        $model =  M('CupquizGamble');

        // 更新数据
        $list = $model->where(['id' => ['IN', I('id')]])->save(['status' => 0]);
        if (false !== $list) {
            //成功提示
            $this->success('编辑成功!',cookie('_currentUrl_'));
        } else {
            //错误提示
            $this->error('编辑失败!');
        }
    }


}