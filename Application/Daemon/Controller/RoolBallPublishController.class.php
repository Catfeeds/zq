<?php
/**
 * Mqtt 推送
 * @author longs <longs@qc.mail>
 * @since  2018-5-4
 */

use Api520\Services\AppfbService;
use Think\Controller;

class RoolBallPublishController extends Controller
{
    private $options = null;
    public $redis = null;


    public function _initialize()
    {
        $this->options = C('MQTT');
        $this->redis = connRedis();
        echo json_encode( $this->options)."\r\n";
    }


    /**
     * 滚球预警推送
     */
    public function rollingBallWarning() {
        $topic = $key = 'qqty/api520/fb/rollingBallWarning';
		echo sprintf("重建连接 \r\n");
		 $date   = date("Y-m-d", strtotime("-10 hours -30 Minute"));
		$client = new Mosquitto\Client(md5('rollBall'.time()));
		$client->setCredentials("mqtt_appclient_nologin", "mqtt_appclient_nologin");
		$this->whileConnect($client, $date, $topic,$key);
		unset($client);
		echo sprintf("关闭连接 \r\n");
    }
    
    function whileConnect($client, $date, $topic, $key)
    {
	    $statusCode = $client->connect($this->options['host'], 1883);
	    if ($statusCode != 0) {
		    $client->disconnect();
		    echo sprintf("mqtt连接失败 : %s \r\n", date("Y-m-d H:i:s"));
			$this->whileConnect($client, $date, $topic, $key);
	    } else {
		    echo sprintf("mqtt连接成功 : %s \r\n", date("Y-m-d H:i:s"));
		    $fbService = new AppfbService();
		    $rollBallData = $fbService->getNowGameData($date);
		    $message = json_encode($rollBallData);
		    $isPublish = FALSE;
		
		    // 是否存在原值
		    if ((bool)$this->redis->get($key)) {
			    $value = $this->redis->get($key);
			    if (!($value === $message)) {
				    $isPublish = TRUE;
				    echo "数据更新".date("Y-m-d H:i:s")."\r\n";
				    $this->redis->set($key, $message, 1000);
			    }else {
				    echo "数据无更新".date("Y-m-d H:i:s")."\r\n";
			    }
		    }else {
			    $this->redis->set($key, $message, 1000);
			    $isPublish = TRUE;
		    }
		    if ($isPublish) {
			    echo sprintf("推送时间 : %s \r\n", date("Y-m-d H:i:s"));
			    echo sprintf("推送数据 : 成功 \r\n");
			    $client->publish($topic, $message, 1, false);
			    echo "推送完成\r\n";
		    }
	    }
    }

}