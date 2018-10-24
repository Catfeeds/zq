<?php

/**
 * 每日任务和成就模型
 * Created by PhpStorm.
 * User: zhangwen
 * Date: 2016/6/22
 * Time: 11:22
 */
use Think\Model;
class MissionModel extends Model
{
    protected $tableName = 'mission';

    /**
     * 获得每日任务的结果
     * @param $userid int 用户id
     * @param $sign string 动作标识
     * @param $num int 动作数量
     * @param $beginTime 开始时间
     * @param $endTime 结束时间
     * @return int
     */
    public function getMission($userid, $sign, $num, $beginTime, $endTime){

        switch ($sign)
        {
            case 'publishGamble':   $result = $this->checkPublishGamble($userid, $num, $beginTime, $endTime);     break;
            case 'buyGamble':       $result = $this->checkBuyGamble($userid, $num, $beginTime, $endTime);         break;
            case 'shareGamble':     $result = $this->checkShare($userid, $num, 1, $beginTime, $endTime);          break;
            case 'shareNews':       $result = $this->checkShare($userid, $num, 2, $beginTime, $endTime);          break;
            case 'replyNews':       $result = $this->checkReply($userid, $num, $beginTime, $endTime);             break;
            case 'publishArticle':  $result = $this->checkArticle($userid, $num, $beginTime, $endTime);           break;
        }

        return $result;
    }

    /**
     * 检查是否有达成发布竞猜条件
     * @param $userid int 用户id
     * @param $num int 数量
     * @param $beginTime 开始时间
     * @param $endTime 结束时间
     * @return int
     */
    public function checkPublishGamble($userid, $num, $beginTime, $endTime){
        $where['user_id'] = (int)$userid;
        $where['create_time'] = ['between', [$beginTime, $endTime]];
        $countNum = M('Gamble')->where($where)->count();
        if($countNum >= $num){
            return 1;
        }
        return 0;
    }

    /**
     * 检查是否有达成购买竞猜条件
     * @param $userid int 用户id
     * @param $num int 数量
     * @param $beginTime 开始时间
     * @param $endTime 结束时间
     * @return int
     */
    public function checkBuyGamble($userid, $num, $beginTime, $endTime){
        $where['user_id'] = (int)$userid;
        $where['game_type'] = 1;//足球
        $where['log_time'] = ['between', [$beginTime, $endTime]];
        $where['coin'] = ['gt', 0];//不是免费
        $countNum = M('QuizLog')->where($where)->count();
        if($countNum >= $num){
            return 1;
        }
        return 0;
    }

    /**
     * 检查是否有达成分享竞猜或资讯条件
     * @param $userid int 用户id
     * @param $num int 数量
     * @param $type int 分享类型，1：竞猜；2：咨讯
     * @param $beginTime 开始时间
     * @param $endTime 结束时间
     * @return int
     */
    public function checkShare($userid, $num, $type, $beginTime, $endTime){
        $where['user_id'] = (int)$userid;
        $where['type'] = $type;
        $where['create_time'] = ['between', [$beginTime, $endTime]];
        $countNum = M('Share')->where($where)->count();
        if($countNum >= $num){
            return 1;
        }
        return 0;
    }

    /**
     * 检查是否有达成评论资讯条件
     * @param $userid int 用户id
     * @param $num int 数量
     * @param $beginTime 开始时间
     * @param $endTime 结束时间
     * @return int
     */
    public function checkReply($userid, $num, $beginTime, $endTime){
        $where['user_id'] = (int)$userid;
        $where['create_time'] = ['between', [$beginTime, $endTime]];
        $countNum = M('Comment')->where($where)->count();

        if($countNum >= $num){
            return 1;
        }
        return 0;
    }

    /**
     * 检查是否有达成发布帖子条件
     * @param $userid int 用户id
     * @param $num int 数量
     * @param $beginTime 开始时间
     * @param $endTime 结束时间
     * @return int
     */
    public function checkArticle($userid, $num, $beginTime, $endTime){
        $where['user_id'] = (int)$userid;
        $where['create_time'] = ['between', [$beginTime, $endTime]];
        $countNum = M('CommunityPosts')->where($where)->count();

        if($countNum >= $num){
            return 1;
        }
        return 0;
    }

    /**
     * 插入积分记录
     * @param $userid int 用户id
     * @param $log_type int 记录类型
     * @param $changeNum int 变化的积分
     * @param $point 积分余额
     * @param $desc 说明
     * @return int  变化的积分
     */
    public function addPointLog($userid, $log_type, $changeNum, $point, $desc){
        M('PointLog')->add([
            'user_id'     => $userid,
            'log_time'    => NOW_TIME,
            'log_type'    => $log_type,
            'change_num'  => $changeNum,
            'total_point' => $point,
            'desc'        => $desc
        ]);
        return $changeNum;
    }

    /**
     * 更新用户积分或签到次数
     * @param $userid int 用户id
     * @param $point int 积分
     * @param $sign_num int 签到天数，可为0
     * @return boolean
     */
    public function updateUserInfo($userid, $point=0, $sign_num=null, $sign_time=0){
        if(empty($point) && $sign_num == null){
            return false;
        }

        if($point){
            $data['point'] = (int)$point;
        }
        if($sign_num != null){
            $data['sign_num'] = (int)$sign_num;
        }
        if($sign_time){
            $data['sign_time'] = (int)$sign_time;
        }

        $rs = M('FrontUser')->where(['id' => $userid])->save($data);
        return $rs;
    }

    /**
     * 获得我的成就的结果
     * @param $userid int 用户id
     * @param $sign string 动作标识
     * @param $num int 动作数量
     * @return int
     */
    public function getAchievements($userid, $sign, $num)
    {
        switch ($sign)
        {
            case 'gambleNum':       $result = $this->countGambleNum($userid, $num);       break;
            case 'winNum':          $result = $this->countGambleNum($userid, $num, 1);    break;
            case 'getBuyNum':       $result = $this->countBuyNum($userid, $num, 1, 1);    break;
            case 'buyNum':          $result = $this->countBuyNum($userid, $num, 2, 1);    break;
            case 'fansNum':         $result = $this->countFansNum($userid, $num);         break;
            case 'publishComment':  $result = $this->countCommentNum($userid, $num);      break;
        }

        return $result;
    }

    /**
     * 统计竞猜场次
     * @param $result_type int 结果类型，默认0，无论结果；1：赢的结果
     * @return int
     */
    public function countGambleNum($userid, $num, $result_type=0){
        if($result_type){
            $where['result'] = ['in', [0.5, 1]];
        }
        $where['user_id'] = (int)$userid;
        $countNum  = M('Gamble')->where($where)->count();
        if($countNum >= $num){
            return 1;
        }
        return 0;
    }

    /**
     * 统计购买次数
     * @param $buy_type int 1:获得购买次数；2：购买别人次数
     * @param $game_type int 默认足球
     * @return int
     */
    public function countBuyNum($userid, $num, $buy_type, $game_type=1){
        if($buy_type == 1){
            $where['cover_id'] = (int)$userid;
        }else if($buy_type == 2){
            $where['user_id'] = (int)$userid;
        }
        $where['game_type'] = (int)$game_type;
        $where['coin'] = ['gt', 0];//不是免费
        $countNum  = M('QuizLog')->where($where)->count();

        if($countNum >= $num){
            return 1;
        }
        return 0;
    }

    /**
     * 统计粉丝数目
     * @param $userid int 用户id
     * @param $num int 比对数量
     * @return int
     */
    public function countFansNum($userid, $num){
        $where['follow_id'] = (int)$userid;
        $countNum = M('FollowUser')->where($where)->count();
        if($countNum >= $num){
            return 1;
        }
        return 0;
    }

    /**
     *  统计评论数，包括资讯和体育圈
     * @param $userid int 用户id
     * @param $num int 比对数量
     * @return int
     */
    public function countCommentNum($userid, $num){
        $where['user_id'] = (int)$userid;
        $num1 = M('Comment')->where($where)->count();
        $num2 = M('CommunityComment')->where($where)->count();

        if(($num1+$num2) >= $num){
            return 1;
        }
        return 0;
    }

    /**
     * 检查是否已领取（任务或成就）
     * @param $userid int 用户id
     * @param $mid int 任务，成就id
     * @param $mtype int 类型
     * @param $beginTime 开始时间
     * @param $endTime 结束时间
     * @return int
     */
    public function checkIsGet($userid, $mid, $mtype, $beginTime=0, $endTime=0){
        $where['user_id'] = (int)$userid;
        $where['mid'] = (int)$mid;
        $where['mtype'] = (int)$mtype;

        if($beginTime && $endTime){
            $where['create_time'] = ['between', [$beginTime, $endTime]];
        }
        $countNum = M('MissionLog')->where($where)->count();

        if($countNum){
            return 1;
        }
        return 0;
    }

    /**
     *  插入任务，成就积分记录
     * @param $userid int 用户id
     * @param $mid int 任务，成就id
     * @param $mtype int 类型
     * @return int
     */
    public function addMissionLog($userid, $mid, $mtype){
        if(empty($userid) || empty($mid) || empty($mtype)){
            return false;
        }

        $data['user_id'] = (int)$userid;
        $data['mid'] = (int)$mid;
        $data['mtype'] = (int)$mtype;
        $data['create_time'] = time();

        return M('MissionLog')->add($data);
    }

    /**
     * 是否已签到
     * @param $userid int 用户id
     * @param $beginTime 开始时间
     * @param $endTime 结束时间
     * @return int
     */
    public function isSign($userid, $beginTime, $endTime){
        $where['user_id'] = (int)$userid;
        $where['log_type'] = 16;
        $where['log_time'] = ['between', [$beginTime, $endTime]];

        return M('PointLog')->where($where)->count();
    }

}