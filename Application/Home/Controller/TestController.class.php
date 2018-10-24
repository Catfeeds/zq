<?php
/**
 * 首页
 * @author longs <2502737229@qq.com>
 * @since  2018-1-24
 */


use Common\Mongo\GambleHallMongo;

class TestController extends CommonController
{

//获取视频、动画直播
    public function getLive()
    {
        
        $game_type = !empty($this->param['game_type']) ? $this->param['game_type'] : 1; // 默认足球
        $game_id = !empty($this->param['game_id']) ? $this->param['game_id'] : '';
        if($game_type == 2){
            $gameModel = M('GameBkinfo');
            $linkModel = M('BkLinkbet');
            $stateArr  = [0,1,2,3,4,5,6,50];
            $flashUrl  = 'https://dh.qqty.com/basketball_animate/basketball_animate.html?game_id='.$game_id;
        }else{
            $gameModel = M('GameFbinfo');
            $linkModel = M('FbLinkbet');
            $stateArr  = [0,1,2,3,4];
            $flashUrl  = C('dh_host') . '/svg-f-animate.html?game_id='.$game_id;
        }
        
        //是否有视频直播
        $videoList  = $gameModel->field(['app_video','gtime'])->where(['game_id'=>$game_id, 'is_video'=>1, 'game_state'=>['IN', $stateArr]])->find();
       
        if (!empty($videoList['app_video'])){
            $videoInfo = json_decode($videoList['app_video'],true);
            foreach ($videoInfo as $k => $v)
            {
                if ($v['appname'] && $v['appurl'])
                {
                    $video[] = [
                        'appname'       => $v['appname'],
                        'appurl'        => htmlspecialchars_decode(stripslashes($v['appurl'])),
                        'app_ischain'   => $v['app_ischain'],
                        'app_isbrowser' => $v['app_isbrowser'],
                    ];
                }
            }
        }

        //是否有动画直播
        $t1 = microtime(1);
        $has = (new GambleHallMongo())->getFbLinkbet($game_id);
        $t2 = microtime(1);
        (new \Think\Log())->write('gitlive请求时间为：'.($t2-$t1), 'info');

        if(!empty($has)){
            if(!empty($videoList['game_state']) && in_array($videoList['game_state'],[0,1,2,3,4]))
            {
               $flash = $flashUrl;
            }
        }
        $this->ajaxReturn([
            'video' => !empty($video) ? $video : [], 
            'flash' => !empty($flash) ? $flash : '', 
            'gtime' => !empty($videoList['gtime']) ? (string)$videoList['gtime'] : '' 
        ]);
    }
}