<?php
/**
 * 个人中心专家管理
 * @author liuweitao <liuwt@qc.com> 2017-08-10
 */
use Think\Tool\Tool;
class ExpertController extends HomeController
{


    protected $tableName = 'game_fbinfo';

    //文章列表
    public function list_e()
    {
        $user_id = is_login();
        $class_type = I("listType");
        $user = M("FrontUser f")->field("f.expert_status,f.is_expert,f.id,f.nick_name,f.head,f.descript,(select count(fu.id) from qc_follow_user fu where fu.follow_id = f.id) as follow,(select count(pl.id) from qc_publish_list pl where pl.user_id = f.id) as publish_total")->where(['f.id'=>$user_id])->find();
        $user['head'] = frontUserFace($user['head']);
        if($user['is_expert'] != 1) {
            $this->ident();
            exit;
        }

        //内容列表
        $where['user_id'] = $user_id;
        $where['status'] = 1;
        $where['add_time'] = ['lt',time()];
        $url = '';
        if($class_type == "expert"){
            $where['class_id'] = 10;
            $url = '/listType/expert';
        }
        $list = $this->_list(M('PublishList'),$where,'5','add_time desc','id,title,remark,img,add_time,click_number','',"/Expert/list_e".$url."/p/%5BPAGE%5D.html");
        foreach($list as $key=>$value)
        {
            if(!empty($value['img'])){
                $list[$key]['img'] = Tool::imagesReplace($value['img']);
            }else{
                //获取第一张图片
                $list[$key]['img']  = Tool::getTextImgUrl(htmlspecialchars_decode($value['content']),false)[0] ?:SITE_URL.'www.'.DOMAIN.'/Public/Home/images/common/loading.png';
            }
            $list[$key]['add_time'] = date("Y-m-d H:i",$value['add_time']);
            $list[$key]['click_number'] = addClickConfig(1, $value['class_id'],$value['click_number'], $value['id']);
        }
        $is_login = is_login();

        $count = M("PublishList")->field()->where($where)->count();
        $user['count'] = $count;
        $user['type'] = 'index';
        $this->assign('user',$user);
        $this->assign('list',$list);

        $this->display();
    }

    public function publish()
    {
        $user_info = M('FrontUser')->where(['id'=>is_login()])->find();
        if($user_info['is_expert'] != 1) {
            $this->ident();
            exit;
        }
        $user_info['type'] = 'article';
        $game = $this->findGame(1);
        $bk = $this->findGame(2);
        $this->assign('game',$game);
        $this->assign('bk',$bk);
        $this->assign('user',$user_info);

        $this->display('list_e');
    }

    public function ident()
    {
        $token = md5(uniqid(rand(), TRUE));
        session('token',$token);
        $this->assign('token',$token);
        $user_info = M('FrontUser')->where(['id'=>is_login()])->find();
        if($user_info['is_expert'] == 1) $this->redirect('Expert/list_e');
        $user['status'] = $user_info['expert_status'];
        if($user_info['expert_status'] == 2)
        {
            $user['type'] = 'prompt';
            $this->assign('user',$user);
            $this->display('list_e');
            exit;
        }
        if($user_info['expert_status'] == 3)
        {
            $user['type'] = 'prompt';
            $user['reason'] = $user_info['reason'];
            $this->assign('user',$user);
            $this->display('list_e');
            exit;
        }
        if($user_info['username']) $info['phone'] = $user_info['username'];
        if($user_info['identfy']) $info['identfy'] = $user_info['identfy'];
        if($user_info['true_name']) $info['true_name'] = $user_info['true_name'];
        if($user_info['head']) $info['head'] = frontUserFace($user_info['head']);
        if($user_info['nick_name'])
        {
            $info['name'] = $user_info['nick_name'];
        }else{
            $info['name'] = $user_info['username'];
        }
        if($info['phone'] && $info['identfy'] && $info['head'])
        {
            $user['type'] = 'ident';
        }else{
            $user['type'] = 'prompt';
        }
        $this->assign('info',$info);
        $this->assign('user',$user);
        $this->display('list_e');
        exit;
    }

    /**
     * 保存/修改记录
     *
     * @return #
     */
    public function save(){
        $where['user_id'] = is_login();
        $where['add_time'] = ['gt',time()-30];
        $res = M('PublishList')->where($where)->order('add_time desc')->limit(1)->find();
        if(isset($res))
        {
            if(($res['game_id'] == $_POST['game_id'] || $res['gamebk_id'] == $_POST['game_id']) && $res['title'] == $_POST['title'] && $res['play_type'] == $_POST['play_type']) $this->redirect('Expert/list_e');
        }
        $_POST['user_id'] = is_login();
        if($_POST['game_id'])
        {
            $_POST['class_id'] = 10;
        }else{
            $_POST['class_id'] = 10;
        }
        $_POST['is_original'] = 1;
        $_POST['ajax'] = 1;
        $model = D('PublishList');
        if (false === $model->create()) {
            $this->error($model->getError());
        }
        $model->add_time = time();
        $model->title = htmlspecialchars_decode($_POST['title']);
        $model->short_title = htmlspecialchars_decode($_POST['title']);
        /*if (!empty($_FILES['fileInput']['tmp_name'])) {
            $filetype = pathinfo($_FILES["fileInput"]["name"], PATHINFO_EXTENSION);//获取后缀
        }*/
        $model->update_time = time();
        switch ($_POST['play_type'])
        {
            case '1':
            case '-1':
                if($_POST['gtype'] == 'bk')
                {
                    $tmp[] = $_POST;
                    D('GambleHall')->getBkGoal($tmp,9);
                    $_POST = $tmp[0];
                    if(empty($_POST['fsw_exp_home']) && empty($_POST['fsw_exp_away']) && empty($_POST['fsw_total_home']) && empty($_POST['fsw_total_away']))
                    {
                        $tmp = M("GameBkinfo")->field('fsw_exp_home,fsw_exp,fsw_exp_away,fsw_total_home,fsw_total,fsw_total_away')->where(['game_id'=>$_POST['game_id']])->find();
                        $_POST = array_merge((array)$_POST,(array)$tmp);
                    }
                    switch ($_POST['play_type'])
                    {
                        case '1':
                            $fsw_home = $_POST['fsw_exp_home'];
                            $fsw = $_POST['fsw_exp'];
                            $fsw_away = $_POST['fsw_exp_away'];
                            break;
                        case '-1':
                            $fsw_home = $_POST['fsw_total_home'];
                            $fsw = $_POST['fsw_total'];
                            $fsw_away = $_POST['fsw_total_away'];
                            break;
                    }
                    switch ($_POST['chose_side'])
                    {
                        case '1':
                            $model->odds = $fsw_home;
                            $model->handcp = $fsw;
                            $model->odds_other = $fsw_away;
                            break;

                        case '-1':
                            $model->odds = $fsw_away;
                            $model->handcp = $fsw;
                            $model->odds_other = $fsw_home;
                            break;
                    }
                }else{
                    D('GambleHall')->getHandcpAndOdds($_POST);
                    switch ($_POST['chose_side'])
                    {
                        case '1':
                            $model->odds = $_POST['odds'];
                            $model->handcp = $_POST['handcp'];
                            $model->odds_other = $_POST['odds_other'];
                            break;

                        case '-1':
                            $model->odds = $_POST['odds_other'];
                            $model->handcp = $_POST['handcp'];
                            $model->odds_other = $_POST['odds'];
                            break;
                    }
                }
                break;

            case '2':
            case '-2':
                D('GambleHall')->getHandcpAndOddsBet($_POST);
                $model->odds = $_POST['odds'];
                $model->odds_other = $_POST['odds_other'];
                $model->handcp = $_POST['handcp'];
                break;
        }
        if($_POST['gtype'] == 'bk')
        {
            $gameinfo = M('GameBkinfo');
            $model->gamebk_id = $_POST['game_id'];
            $model->game_id = 0;
            $gtype = 1;
            unset($_POST['gtype'],$_POST['exp_value'],$_POST['fsw_exp_home'],$_POST['fsw_exp'],$_POST['fsw_exp_away'],$_POST['fsw_total_home'],$_POST['fsw_total'],$_POST['fsw_total_away']);
        }else{
            $gameinfo = M('GameFbinfo');
        }
        //保存比赛时间为APP显示时间
        $app_time = $gameinfo->where(['game_id' => $_POST['game_id']])->getField('gtime');
        $model->app_time = $app_time?$app_time:time();
        if (empty($id)) {
            if(empty($_POST['add_time'])) $model->add_time = time();
            //为新增
            $model->author = $_SESSION['authId'];
            $rs = $model->add();
            if($_POST['game_id'] || $_POST['gamebk_id'])
            {
                //上传图片
                if (!empty($_FILES['fileInput']['tmp_name'])) {
                    $return = D('Uploads')->uploadImg("fileInput", "publish", $rs);
                }else{
                    $_FILES['fileInput'] = D("Cover")->cover($rs,$_POST['game_id'],$gtype);
                    $return = D('Uploads')->uploadImg("fileInput", "publish", $rs);
                }
                if($return['status'] == 1)
                    M("PublishList")->where(['id'=>$rs])->save(['img'=>$return['url']]);
            }
            //百度主动推送
            $urls = array(
                'https://www.qqty.com/news/'.$rs.'.html',
            );
            $api = 'http://data.zz.baidu.com/urls?site=www.qqty.com&token=uT6ULLJoNJYlAY0s';
            $ch = curl_init();
            $options =  array(
                CURLOPT_URL => $api,
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS => implode("\n", $urls),
                CURLOPT_HTTPHEADER => array('Content-Type: text/plain'),
            );
            curl_setopt_array($ch, $options);
            $result = curl_exec($ch);
        }
        $this->redirect('Expert/list_e');
    }

    /**
     * 弹窗查找赛事
     */
    public function findGame($game_type){
        if($game_type == 1)
        {
            list($_ya) = $this->matchList(1);
            list($_ji) = $this->matchList(2);
        }else{
            list($_ya) = $this->matchList(1,2);
        }

        $ya = array();
        foreach ($_ya as $k => $v) {
            if(!($v['game_state'] != 0 or time() > $v['gtime'])){
                $ya[$v['game_id']] = $v;
            }
        }
        foreach ($_ji as $k => $v) {
            if($v['game_state'] != 0 or time() > $v['gtime']){
                unset($_ji[$k]);
            }else{
                if($ya[$v['game_id']])
                {
                    $_ji[$k]['fsw_exp_home'] = $ya[$v['game_id']]['fsw_exp_home'];
                    $_ji[$k]['fsw_exp'] = $ya[$v['game_id']]['fsw_exp'];
                    $_ji[$k]['fsw_exp_away'] = $ya[$v['game_id']]['fsw_exp_away'];
                    $_ji[$k]['fsw_ball_home'] = $ya[$v['game_id']]['fsw_ball_home'];
                    $_ji[$k]['fsw_ball'] = $ya[$v['game_id']]['fsw_ball'];
                    $_ji[$k]['fsw_ball_away'] = $ya[$v['game_id']]['fsw_ball_away'];
                    unset($ya[$v['game_id']]);
                }
            }
        }
        $game = array_values(array_merge((array)$ya,(array)$_ji));
        foreach ($game as $k => $v) {
            if (array_key_exists($v['union_id'],$union))
            {
                $union[$v['union_id']]['union_num'] = (string)($union[$v['union_id']]['union_num']+1);
            }
            else
            {
                $union[$v['union_id']] = ['union_id'=>$v['union_id'],'union_name'=>$v['union_name'],'union_num'=>'1','union_color'=>$v['union_color']];
            }
        }
        $game = $this->arraySequence($game,'gtime','SORT_ASC');
        $union = array_values($union);
        $res = ['game'=>$game,'union'=>$union];
        return $res;
    }

    public function arraySequence($array, $field, $sort = 'SORT_DESC')
    {
        $arrSort = array();
        foreach ($array as $uniqid => $row) {
            foreach ($row as $key => $value) {
                $arrSort[$key][$uniqid] = $value;
            }
        }
        array_multisort($arrSort[$field], constant($sort), $array);
        return $array;
    }

    public function checkVerify()
    {
        $username = I('mobile');
        $isTrue = A('User')->checkMobileVerify(I('captcha'),$username);
        if(! $isTrue)
        {
            $this->error('验证码错误或已超时');
        }else{
            $this->success('验证成功！');
        }
    }

    public function saveIdent()
    {
        $rs = is_login();
        //上传图片
        if (!empty($_FILES['fileInput']['tmp_name'])) {
            $return = D('Uploads')->uploadImg("fileInput", "identfypic", $rs);
            if($return['status'] == 1)
                M("FrontUser")->where(['id'=>$rs])->save(['identfy_pic'=>$return['url'],'descript'=>$_POST['descript'],'expert_status'=>2]);
                $this->redirect('Expert/ident');
        }
    }

    //重新申请专家
    public function re_repert()
    {
        $user_id = is_login();
        M("FrontUser")->where(['id'=>$user_id])->save(['expert_status'=>0,'reason'=>'']);
        $this->redirect('Expert/ident');
    }

    //判断是否能够发布竞猜
    public function is_gamble()
    {
        if(IS_POST)
        {
            $play_type = I("game_type");
            $game_id = I("game_id");
            $gtype = I("gtype");
            $where['user_id'] = is_login();
            $where['play_type'] = $play_type;
            if($gtype == 'bk')
            {
                $where['gamebk_id'] = $game_id;
            }else{
                $where['game_id'] = $game_id;
            }
            $rs = M("PublishList")->where($where)->getField('id');
            if($rs)
            {
                $this->error('该场赛事玩法已经推荐过啦!');
            }else{
                $this->success('可以推荐');
            }
        }else{
            $this->error('数据异常!');
        }
    }


    //足球推荐大厅
    public function matchList($type=1,$gtype=1)
    {
        $game = $this->getGameFbinfo($type,$gtype);
        $union = $sort_game_state = $sort_gtime = $sort_union = $sort_union2 = [];
        foreach ($game as $k => $v)
        {
            if(
                ($v['gtime'] + 60 < time() && $v['game_state'] == 0)  //过了开场时间未开始
                || ($v['game_state'] == -14 || $v['game_state'] == -11)  //屏蔽待定和推迟
                || ($v['gtime'] + 8400 < time() && array_search($v['game_state'],[1,2,3,4]) !== false) //140分钟还没结束
            )
            {
                unset($game[$k]);
                continue;
            }

            //分解时间
            $game[$k]['game_date'] = date('Ymd',$v['gtime']);
            $game[$k]['game_time'] = date('H:i',$v['gtime']);

            //增加排序的条件
            $sort_gtime[]      = $v['gtime'];

            if (stristr(MODULE_NAME,'Api'))
            {
                if ($v['game_state'] < 0)
                {
                    $v['game_state'] = abs($v['game_state']);
                }
                else if ($v['game_state'] > 0)
                {
                    $v['game_state'] += 15; //纯粹为了排序 -.-!!!
                }
                $sort_game_state[] = $v['game_state'];
            }
            else
            {
                $sort_game_state[] = $v['game_state'];
            }

            //获取联盟中球队数量
            if (array_key_exists($v['union_id'],$union))
            {
                $union[$v['union_id']]['union_num'] = (string)($union[$v['union_id']]['union_num']+1);
            }
            else
            {
                $union[$v['union_id']] = ['union_id'=>$v['union_id'],'union_name'=>$v['union_name'],'union_num'=>'1','union_color'=>$v['union_color']];
                $sort_union[] = $v['is_sub'];
                $sort_union2[] = $v['sort'];
            }
            unset($game[$k]['is_sub']);
        }

        //获取球队logo
        setTeamLogo($game,1);

        $union = array_values($union);
        array_multisort($sort_union,SORT_ASC,$sort_union2,SORT_ASC,$union);

        if (stristr(MODULE_NAME,'Api'))
        {
            foreach ($game as $k => $v)
            {
                $game[$k]['union_name']     = explode(',', $v['union_name']);
                $game[$k]['home_team_name'] = explode(',', $v['home_team_name']);
                $game[$k]['away_team_name'] = explode(',', $v['away_team_name']);

                if ($v['game_half_time']) //半场时间转换
                {
                    $halfTime    = explode(',', $v['game_half_time']);
                    $halfTime[1] = str_pad($halfTime[1]+1, 2, '0', STR_PAD_LEFT); //js的月份+1为正常月份
                    $game[$k]['game_half_time'] = implode('', $halfTime);
                }
                //接口删除盘口赔率等不需要返回的字段
                unset($game[$k]['home_team_id']);
                unset($game[$k]['away_team_id']);
                unset($game[$k]['fsw_exp']);
                unset($game[$k]['fsw_ball']);
                unset($game[$k]['fsw_exp_home']);
                unset($game[$k]['fsw_exp_away']);
                unset($game[$k]['fsw_ball_home']);
                unset($game[$k]['fsw_ball_away']);
            }

            foreach ($union as $k => $v)
            {
                $union[$k]['union_name'] = explode(',', $v['union_name']);
            }
            array_multisort($sort_game_state,SORT_ASC, $sort_gtime,SORT_ASC, $game);
        }
        else
        {
            //获取即时数据
            if(!empty($game) && $type == 1)
            {
                array_multisort($sort_game_state,SORT_DESC, $sort_gtime,SORT_ASC, $game);
                D("GambleHall")->getFbGoal($game);
            }
        }
        return [$game,$union];
    }


    /**
     * 获取亚盘或竞彩赛事
     * @param  int $type    1:亚盘  2:竞彩
     * @return array
     */
    public function getGameFbinfo($type,$gtype)
    {
        $blockTime = $this->getBlockTime(1);
        if($type == 1)
        {
            if($gtype == 1)
            {
                $sql1 = ', g.fsw_ball_home, g.fsw_ball, g.fsw_ball_away';
                $sql2 = "
                AND g.fsw_ball      != ''
                AND g.fsw_ball_home != ''
                AND g.fsw_ball_away != ''";
                $table = 'game_fbinfo';
                $union_table = 'union';
            }else{
                $sql1 = ', g.fsw_total_home as fsw_ball_home, g.fsw_total as fsw_ball, g.fsw_total_away as fsw_ball_away';
                $sql2 = "
                AND g.fsw_total      != ''
                AND g.fsw_total_home != ''
                AND g.fsw_total_away != ''";
                $table = 'game_bkinfo';
                $union_table = 'bk_union';
            }
            $sql = "SELECT DISTINCT
                    g.game_id, g.union_id, u.union_name, u.union_color, u.is_sub, u.sort ,g.gtime, g.game_half_time, g.game_state,
                    g.home_team_name, g.home_team_id, g.score, g.half_score, g.away_team_name, g.away_team_id,
                    g.fsw_exp_home, g.fsw_exp, g.fsw_exp_away".$sql1."
                FROM __PREFIX__".$table." g
                LEFT JOIN __PREFIX__".$union_table." u ON g.union_id = u.union_id
                WHERE
                    g.status = 1
                AND gtime between {$blockTime['beginTime']} AND {$blockTime['endTime']}
                AND (u.is_sub < 3 or g.is_show =1)
                AND g.is_gamble = 1
                AND g.fsw_exp       != ''
                AND g.fsw_exp_home  != ''
                AND g.fsw_exp_away  != ''".$sql2;
            $game  = M()->query($sql);
        }
        else if($type == 2)
        {
            //如果没有过今天的10:32，code显示昨天的
            $weekArray = array("周日", "周一", "周二", "周三", "周四", "周五", "周六"); //日期数组
            if(NOW_TIME > strtotime('10:32')){
                $today = $weekArray[date("w")];
                $tom   = $weekArray[date("w", strtotime('+1 day'))];
            }else{
                $today = $weekArray[date("w", strtotime('-1 day'))];
                $tom   = $weekArray[date("w")];
            }
            //获取竞彩赛事（如果今天没有赛事取后一天）
            $game = D("GambleHall")->getBettingGame($today,$blockTime);
            if(!$game){
                $game = D("GambleHall")->getBettingGame($tom,$blockTime);
            }
        }
        return $game;
    }

    //获取赛程分割日期的区间时间
//$gameType 1:足球  2:篮球
//$gamble   是否为竞猜的时间区间
    function getBlockTime($gameType=1,$gamble=false)
    {

        $segmTime = $gameType == 1 ? strtotime('10:32:00') : strtotime('12:00');

        if ($gameType == 1) //football
        {
            if (time() > $segmTime)
            {
                $beginTime = $gamble == true ? $segmTime : strtotime('8:00:00');
                $endTime   = strtotime('+2 day',$segmTime);
            }
            else
            {
                $beginTime = $gamble == true ? strtotime('-1 day',$segmTime) : strtotime('8:00:00')-3600*24;
                $endTime   = $segmTime;
            }
        }
        else                //basketball
        {
            if (time() > $segmTime)
            {
                $beginTime = $segmTime;
                $endTime   = strtotime('+2 day',$segmTime);
            }
            else
            {
                $beginTime = strtotime('-1 day',$segmTime);
                $endTime   = $segmTime;
            }
        }

        return ['beginTime'=>$beginTime,'endTime'=>$endTime];
    }




}

 ?>