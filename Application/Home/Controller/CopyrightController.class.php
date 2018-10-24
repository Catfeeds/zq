<?php
/**
 *
 *关于我们
 * @author wangkaimao   <527993759@qq.com>
 * @since   2015-12-29
 */
use Think\Tool\Tool;

class CopyrightController extends CommonController {

    //关于我们
	public function about(){
		
		$this->display();
	}
    //联系我们
	public function contact(){
		
		$this->display();
	}
    //商务合作
	Public function cooperate(){
		
		$this->display();
	}
    //法律声明
    public function service(){
		
        $this->display();
    }
    //用户反馈
    public function feedback(){
    	if(IS_AJAX && IS_POST){
            if(!check_form_token()){
                $this->error('发送失败，请稍后重试！');
            }
            $userInfo = session('user_auth');
            if(!$userInfo){
                $this->error('请先登录');
            }
            $feedback_sign = $userInfo['id'].'feedback_sign';
            if(S($feedback_sign)){
                $this->error('请等待60秒后重新发送');
            }
            $content = I('content');
    		$feedback['content']     = $content;
    		$feedback['phone']       = I('phone');
    		$feedback['user_id']     = $userInfo['id'];
    		$feedback['create_time'] = time();
    		$result = M('feedback')->add($feedback);
            if($result){
                //发送短信通知运营
                $feedbackConfig = C('feedbackConfig');
                if($feedbackConfig['mobile'] != ''){
                    sendingSMS($feedbackConfig['mobile'],"用户昵称：{$userInfo['nick_name']}，反馈内容：{$content}");
                }
                S($feedback_sign,1,$feedbackConfig['sendTime']);
			    $this->success('反馈成功');
		    }else{
      	        $this->error('反馈失败');
            }
    	}
		$this->display();
	}

    //人才招聘
	public function recruit(){
		
        $recruitClass = M('RecruitClass')->where(array('status'=>1))->order("sort asc")->select();
        $this->assign('recruitClass',$recruitClass);
        $where['status'] = 1;
        $class_id = I('class_id');
        if(!empty($class_id)){
            $where['class_id'] = $class_id;
        }
        $recruitList = M('recruitList')->where($where)->order("sort asc")->select();
        $this->assign('recruitList',$recruitList);
		$this->display();
	}
	
    //网站地图
    public function map(){
        //资讯专题与栏目页
        $newsClass = getPublishClass();
        $newsMap = [];
        foreach ($newsClass as $k => $v) {
            if($v['domain'] != ''){
                if($v['pid'] == 0){
                    $url = U('@'.$v['domain']);
                }elseif ($v['pid'] != 0) {
                    $url = newsClassUrl($v['id'],$newsClass);
                }
                $newsMap[$k]['name'] = $v['name'];
                $newsMap[$k]['href'] = $url;
            }
        }
        $this->assign('newsMap',$newsMap);
        $this->display();
    }

    //友情链接
    public function blogroll(){
        $map['position'] = 4;
        $map['status'] = ['neq',0];
        $list = M('link')->where($map)->order('sort asc,add_time asc')->select();
        $listArr = array_chunk($list,9);
        $this->assign('listArr',$listArr);
        $this->display();
    }
}