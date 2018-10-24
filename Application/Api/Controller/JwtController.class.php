<?php
/**
 * JwtToken获取类
 * @author longsheng <2502737229@qq.com> 17-11-10
 */

use Think\Controller;

class JwtController extends Controller
{
    /**
     * 获取JwtToken
     */
    public function getJwtToken()
    {
        vendor("php-jwt.src.JWT");
        $res = [
            'code'  => 427,
            'time'  => time(),
            'debug' => "",
            'data'  => ""
        ];
        if ($_SERVER['HTTP_AUTHORIZATION']){
            $authorization = explode(" ", $_SERVER['HTTP_AUTHORIZATION'])[1];
            $verifyStatus = D("Jwt")->verifyJwt($authorization);
            if ($verifyStatus){
                $res['msg'] = $verifyStatus["codeMessage"];
                $this->ajaxReturn($res);
            }
            $token = (array) D("Jwt")->decodeJwt($authorization);
            $jwt = D("Jwt")->getJwt($token["unique_id"]);
            $this->ajaxReturn(array("access_token" => $jwt, "token_type" => "Bearer"));
        }
        $requestParam = getParam();
        if ($requestParam["unique_id"]) {
            $unique_id= $requestParam["unique_id"];
            $jwt = D("Jwt")->getJwt($unique_id);
            $this->ajaxReturn(array("access_token" => $jwt, "token_type" => "Bearer"));
        }
        $res["msg"] = "参数传递错误";
        $this->ajaxReturn($res);
    }
}