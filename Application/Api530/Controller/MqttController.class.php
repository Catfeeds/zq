<?php
use Think\Controller;

/**
 * emq相关业务接口
 * Class MqttController
 */
class MqttController extends PublicController
{
    private $oRedis;

    /**
     * 初始化，需要接收的额外的参数包括:deviceID, pushID, userToken, mqttClientID, sceneID
     */
    public function _initialize()
    {
        parent::_initialize();

        $this->oRedis = connRedis();
    }

    /**
     * auth：给用户返回当前要连接的emq账号/密码；
     * HSET mqtt_user:<username> is_superuser 1
     * HSET mqtt_user:<username> password "passwd"
     *
     * acl：连接成功后，用户进入哪个场景，则为用户设置对应的topic设置acl规则（TO:DO）
     */
    public function initMqttUser()
    {
        $loginInfo = getUserToken($this->param['userToken']);


        if ($this->param['mqttClientID']) {
            $mqtt['userName'] = md5('mqtt_nolongin_' . $this->param['mqttClientID']);
            $mqtt['password'] = md5('mqtt_longin_' . $this->param['mqttClientID'] . 'qqty_mqtt' . NOW_TIME);

            $key = 'mqtt_user:' . $mqtt['userName'];
            $this->oRedis->hset($key, 'password', $mqtt['password']);
            $this->oRedis->expire($key, 3600 * 24 * 3);
        }
        if ($loginInfo) {
            $mqtt['userName'] = md5('mqtt_longin_' . $loginInfo['userid']);
            $mqtt['password'] = md5('mqtt_longin_' . $loginInfo['userid'] . 'qqty_mqtt' . NOW_TIME);
            $key = 'mqtt_user:' . $mqtt['userName'];
            $this->oRedis->hset($key, 'password', $mqtt['password']);
            $this->oRedis->expire($key, 3600 * 24 * 3);
        }

        $this->ajaxReturn($mqtt ?: '');
    }
}


