<?php
/**
 * 竞猜竞猜
 * @author huangjiezhen <418832673@qq.com> 2015.12.21
 */

class GambleController extends CommonController
{
    //是否竞猜某赛事
    public function isGamble()
    {
        $gamble = M('Gamble')->field(['play_type','chose_side','is_impt'])->where(['user_id'=>$this->userInfo['userid'],'game_id'=>$this->param['game_id']])->select();
        list($normLeftTimes,$imptLeftTimes) = D('GambleHall')->gambleLeftTimes($this->userInfo['userid'],$gameType=1);
        $leftTimes = ['normLeftTimes'=>$normLeftTimes,'imptLeftTimes'=>$imptLeftTimes];

        $this->ajaxReturn(['gamble'=>$gamble,'leftTimes'=>$leftTimes]);
    }

    //足球竞猜
    public function gamble()
    {
        $res = D('GambleHall')->gamble($this->userInfo['userid'],$this->param,$this->userInfo['platform']);
        $this->ajaxReturn($res);
    }

    //查看竞猜（交易）
    public function trade()
    {
        $userInfo  = $this->userInfo;
        $gamble_id = $this->param['gamble_id'];

        //执行查看交易
        $tradeRes = D('Common')->trade(
            $userInfo['userid'],
            $gamble_id,
            $userInfo['platform'],
            1
        );

        if ($tradeRes['code'] != 'success')
            $this->ajaxReturn($tradeRes['code']);

        $this->ajaxReturn([
            'gamble_id'   => $gamble_id, 
            'coin'        => $tradeRes['userCoin']['coin'], 
            'unable_coin' => $tradeRes['userCoin']['unableCoin']
        ]);
    }

}


 ?>