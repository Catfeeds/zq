<?php
/**
 * 全站公用Jwt模型类
 * @author longsheng <2502737229@qq.com> 17-11-10
 */

use Think\Model;
use Think\Tool\Tool;
use Firebase\JWT\JWT;

class JwtModel extends Model
{

    /**
     * @param $jwt token
     * @param bool $loginStatus
     * @return array
     */
    public function verifyJwt($jwt, $loginStatus=false)
    {
        try {
            vendor("php-jwt.src.JWT");
            $token = (array)JWT::decode($jwt,
                openssl_get_publickey(file_get_contents(VENDOR_PATH."php-jwt/JwtKey/publicKey.pem")), array('RS512'));
            $jwtConfig = C("jwtToken");
            $unique_id =$token["unique_id"];
            $user= M('FrontUser')->where(["id"=>$unique_id])->find();
            if (!$loginStatus) {
                if (!($unique_id == $jwtConfig["unique_id"])) {
                    if (!$user) {
                        return array("statusCode" => 427, "codeMessage" => "非法用户签名");
                    }
                }
            }
            if ($loginStatus && (!$user)) {
                return array("statusCode" => 427, "codeMessage" => "游客签名或用户不存在");
            }
        } catch (Exception $exception) {
            return array("statusCode" => 500, "codeMessage" => $exception->getMessage());
        }
    }


    /**
     * 获取jwtToken
     * @param $unique_id 唯一id
     * @return string token
     */
    public function getJwt($unique_id)
    {
        vendor("php-jwt.src.JWT");
        $privateKey = openssl_get_privatekey(file_get_contents(VENDOR_PATH."php-jwt/JwtKey/privateKey.pem"));
        $jwtConfig = C("jwtToken");
        $token = array(
            "unique_id" => $unique_id,
            "nbf" => $jwtConfig["nbf"],
            "iat" => $jwtConfig["iat"],
            "exp" => $jwtConfig["exp"],
        );
        return JWT::encode($token, $privateKey,'RS512');
    }

    /**
     * @param $jwt token
     * @return object 返回解码后的token对象
     */
    public function decodeJwt($jwt)
    {
        vendor("php-jwt.src.JWT");
        return JWT::decode($jwt,
            openssl_get_publickey(file_get_contents(VENDOR_PATH."php-jwt/JwtKey/publicKey.pem")), array('RS512'));
    }

}