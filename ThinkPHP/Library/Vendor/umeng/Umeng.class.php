<?php
require_once(dirname(__FILE__) . '/' . 'notification/android/AndroidBroadcast.php');
require_once(dirname(__FILE__) . '/' . 'notification/android/AndroidFilecast.php');
require_once(dirname(__FILE__) . '/' . 'notification/android/AndroidGroupcast.php');
require_once(dirname(__FILE__) . '/' . 'notification/android/AndroidUnicast.php');
require_once(dirname(__FILE__) . '/' . 'notification/android/AndroidCustomizedcast.php');

class Umeng
{
    protected $appkey           = NULL;
    protected $appMasterSecret  = NULL;
    protected $timestamp        = NULL;
    protected $validation_token = NULL;

    function __construct($key, $secret)
    {
        $this->appkey           = $key;
        $this->appMasterSecret  = $secret;
        $this->timestamp        = strval(time());
    }

    /**
     * 开发者通过自有的alias进行推送, 可以针对单个或者一批alias进行推送，也可以将alias存放到文件进行发送
     * @param $options
     * @return mixed|string
     */
    function sendAndroidCustomizedcast($options, $more=false) {
        try {
            $customizedcast = new AndroidCustomizedcast();

            $customizedcast->setAppMasterSecret($this->appMasterSecret);
            $customizedcast->setPredefinedKeyValue("appkey", $this->appkey);
            $customizedcast->setPredefinedKeyValue("timestamp", $this->timestamp);

            foreach($options as $k => $v){
                $customizedcast->setPredefinedKeyValue($k, $v);
            }

            return $customizedcast->send();

        } catch (Exception $e) {
            if($more){
                return ['status' => 0,'httpCode' => $e->getCode(), 'msg' => $e->getMessage()];
            }else{
                return $e->getMessage();
            }

        }
    }
    

    /**
     * 向指定的设备发送消息，包括向单个device_token或者单个alias发消息。
     */
    function sendAndroidUnicast($options) {
        try {
            $unicast = new AndroidUnicast();
            $unicast->setAppMasterSecret($this->appMasterSecret);
            $unicast->setPredefinedKeyValue("appkey",           $this->appkey);
            $unicast->setPredefinedKeyValue("timestamp",        $this->timestamp);
            // Set your device tokens here
            foreach($options as $k => $v){
                $unicast->setPredefinedKeyValue($k, $v);
            }

            // Set extra fields
            $unicast->setExtraField("test", "helloworld");

            return  $unicast->send();

        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    function sendAndroidFilecast() {
        try {
            $filecast = new AndroidFilecast();
            $filecast->setAppMasterSecret($this->appMasterSecret);
            $filecast->setPredefinedKeyValue("appkey",           $this->appkey);
            $filecast->setPredefinedKeyValue("timestamp",        $this->timestamp);
            $filecast->setPredefinedKeyValue("ticker",           "Android filecast ticker");
            $filecast->setPredefinedKeyValue("title",            "Android filecast title");
            $filecast->setPredefinedKeyValue("text",             "Android filecast text");
            $filecast->setPredefinedKeyValue("after_open",       "go_app");  //go to app
            print("Uploading file contents, please wait...\r\n");
            // Upload your device tokens, and use '\n' to split them if there are multiple tokens
            $filecast->uploadContents("aa"."\n"."bb");
            print("Sending filecast notification, please wait...\r\n");
            $filecast->send();
            print("Sent SUCCESS\r\n");
        } catch (Exception $e) {
            print("Caught exception: " . $e->getMessage());
        }
    }

    function sendAndroidBroadcast($data) {
        try {
            $brocast = new AndroidBroadcast();
            $brocast->setAppMasterSecret($this->appMasterSecret);
            $brocast->setPredefinedKeyValue("appkey",           $this->appkey);
            $brocast->setPredefinedKeyValue("timestamp",        $this->timestamp);
            $brocast->setPredefinedKeyValue("ticker",           $data['ticker']);
            $brocast->setPredefinedKeyValue("title",            $data['title']);
            $brocast->setPredefinedKeyValue("text",             $data['text']);
            $brocast->setPredefinedKeyValue("after_open",       $data['after_open']);
            $brocast->setPredefinedKeyValue("custom",       	$data['custom']);
            // Set 'production_mode' to 'false' if it's a test device.
            // For how to register a test device, please see the developer doc.
            $brocast->setPredefinedKeyValue("production_mode", $data['production_mode']);
            // [optional]Set extra fields
            // $brocast->setExtraField("test", "helloworld");
            // print("Sending broadcast notification, please wait...\r\n");
            return $brocast->send();
            // print("Sent SUCCESS\r\n");
        } catch (Exception $e) {
            return  $e->getMessage();
        }
    }
}