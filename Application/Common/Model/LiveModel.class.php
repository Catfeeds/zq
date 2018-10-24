<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: liuweitao <liuwt@qqty.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

use Think\Model;
Vendor('aliyun-openapi.aliyun-php-sdk-core.Config');
use live\Request\V20161101\DescribeLiveStreamsFrameRateAndBitRateDataRequest;
use live\Request\V20161101\ForbidLiveStreamRequest;
use live\Request\V20161101\ResumeLiveStreamRequest;
use live\Request\V20161101\DescribeLiveStreamsPublishListRequest;
use live\Request\V20161101\DescribeLiveRecordNotifyConfigRequest;
use live\Request\V20161101\DescribeLiveStreamRecordContentRequest;
use live\Request\V20161101\DescribeLiveDomainRecordDataRequest;
use live\Request\V20161101\AddLiveAppRecordConfigRequest;
use live\Request\V20161101\RealTimeRecordCommandRequest;
use live\Request\V20161101\DescribeLiveStreamsOnlineListRequest;
use live\Request\V20161101\SetLiveStreamsNotifyUrlConfigRequest;
use live\Request\V20161101\DescribeLiveStreamsNotifyUrlConfigRequest;
use Cdn\Request\V20141111\DescribeLiveRecordConfigRequest;
//use Cdn\Request\V20141111\DescribeLiveStreamRecordContentRequest;
use Cdn\Request\V20141111\CreateLiveStreamRecordIndexFilesRequest;

class LiveModel extends Model
{
    private $client = '';
    private $domain = '';
    private $AppName = '';
    private $PREFIX = '';

    public function __construct(){
        $clientProfile = DefaultProfile::getProfile(
            C('LIVE')['RegionId'],                   # 您的 Region ID
            C('LIVE')['AccessKeyId'],               # 您的 AccessKey ID
            C('LIVE')['AccessKeySecret']            # 您的 AccessKey Secret
        );
        $this->client = new DefaultAcsClient($clientProfile);
        $this->domain = C('LIVE')['domain'];
        $this->AppName = C('LIVE')['AppName'];
        $this->PREFIX = C('LIVE')['PREFIX'];
    }

    /**
     * 獲取直播推流地址
     * @param $num  房間號
     * @param $date 直播開始時間
     * @param int $type 地址類型,1:推流地址,0:拉流地址
     * @return string
     */
    public function getLiveUrl($num,$date,$type=0,$urlType = 0){
        $date = $date > 0?$date:time();
        $time=strtotime(C('LIVE')['time']);//("+1 hours");
        $key=C('LIVE')['key'];
        if($urlType == 1){
            $suffix = '.m3u8';
            $prefix = 'http';
        }else{
            $prefix = 'rtmp';
        }
        $filename="/".$this->AppName."/".$this->PREFIX.$num.'-'.date('YmdHi',$date).$suffix;
        $sstring = $filename."-".$time."-0-0-".$key;
        $md5=md5($sstring);
        $auth_key="auth_key=".$time."-0-0-".$md5;

        if($type == 1){
            $domain = C('LIVE')['domain'];
            $domain="rtmp://video-center.alivecdn.com".$filename."?vhost=".$domain."&";
            $url=$domain.$auth_key;
        }else{
            $domain = $prefix."://".C('LIVE')['domain'];
            $url=$domain.$filename."?".$auth_key;
        }
        return $url;
    }

    /**
     * 中斷,恢復直播
     * @param $name 房間號加開播時間的拼接
     * @param $type 操作類型 1:中斷直播,2:恢復直播
     * @return array|void
     */
    public function ResumeLiveStream($num,$time,$type=1){
        $name = $num.'-'.date('YmdHi',$time);
        if($type == 2)
            $request = new ResumeLiveStreamRequest();
        else
            $request = new ForbidLiveStreamRequest();
        $request->setDomainName($this->domain);
        $request->setAppName($this->AppName);
        $request->setLiveStreamType('publisher');
        $request->setStreamName($this->PREFIX.$name);
        return $this->respond($request);
    }

    /**
     * 查詢直播狀態
     * @param $name
     * @return array|void
     */
    public function DescribeLiveStreamsFrameRateAndBitRateData($name){
        $request = new DescribeLiveStreamsFrameRateAndBitRateDataRequest();
        $request->setDomainName($this->domain);
        $request->setAppName($this->AppName);
        if($name) $request->setStreamName($this->PREFIX.$name);
        return $this->respond($request);
    }

    /**
     * 查詢直播錄製
     * @param $name 
     * @return array|void
     */
    public function DescribeLiveStreamRecordContent($name){
        $request = new DescribeLiveStreamRecordContentRequest();
        $request->setDomainName($this->domain);
        $request->setAppName($this->AppName);
        $request->setStreamName($this->PREFIX.$name);
        $request->setStartTime('2018-08-25T08:00:00Z');
        $request->setEndTime('2018-08-27T20:00:00Z');
        return $this->respond($request);
    }
    
    public function AddLiveAppRecordConfig($name){
        $request = new AddLiveAppRecordConfigRequest();
        $request->setDomainName($this->domain);
        $request->setAppName($this->AppName);
        $request->setOssBucket(C('LIVE')['OssBucket']);
        $request->setOssEndpoint('oss-cn-shanghai.aliyuncs.com');
        $RecordFormat['Format'] = 'm3u8';
        $RecordFormat['OssObjectPrefix'] = 'live/record/'.$this->AppName.'/{StreamName}/{EscapedStartTime}{EscapedEndTime}';
        $RecordFormat['SliceOssObjectPrefix'] = 'live/record/'.$this->AppName.'/{StreamName}/{UnixTimestamp}_{Sequence}';
//        $RecordFormat['CycleDuration'] = '1';
        $request->setRecordFormats([$RecordFormat]);
        if($name) $request->setStreamName($this->PREFIX.$name);
        $request->setStartTime('2018-08-27T08:00:00Z');
        $request->setEndTime('2018-08-27T20:00:00Z');
        return $this->respond($request);
    }

    /**
     * 发起请求并处理返回
     * @param $request  需要發送的數據
     * @return array|void
     */
    public function respond($request){
        # 发起请求并处理返回
        try {
            $response = $this->client->getAcsResponse($request);
            return object_to_array($response);
        } catch(ServerException $e) {
            print "Error: " . $e->getErrorCode() . " Message: " . $e->getMessage() . "\n";
        } catch(ClientException $e) {
            print "Error: " . $e->getErrorCode() . " Message: " . $e->getMessage() . "\n";
        }
    }

    /**
     * 直播录制开始停止
     * @param $name int 房间号
     * @param $type int 1:开始,非1:停止
     * @return array|void
     */
    public function RealTimeRecordCommand($name,$type=1){
        $request = new RealTimeRecordCommandRequest();
        if($type == 1)
            $request->setCommand('start');
        else
            $request->setCommand('stop');

        $request->setDomainName($this->domain);
        $request->setAppName($this->AppName);
        if($name) $request->setStreamName($this->PREFIX.$name);
        return $this->respond($request);
    }

    /**
     * 查询域名下录制配置列表
     * @return array|void
     */
    public function DescribeLiveRecordConfig(){
        $request = new DescribeLiveRecordConfigRequest();
        $request->setDomainName($this->domain);
//        $request->setAppName($this->AppName);
        return $this->respond($request);
    }

    /**
     * 查询域名下录制配置回调
     * @return array|void
     */
    public function DescribeLiveRecordNotifyConfig(){
        $request = new DescribeLiveRecordNotifyConfigRequest();
        $request->setDomainName($this->domain);
//        $request->setAppName($this->AppName);
        return $this->respond($request);
    }

    /**
     * 创建单个回播索引
     * @param $name 房间号
     * @param $startTime 开始时间
     * @param $endTime 结束时间
     * @return array|void
     */
    public function CreateLiveStreamRecordIndexFiles($name,$time){
        $request = new CreateLiveStreamRecordIndexFilesRequest();
        $request->setDomainName($this->domain);
        $request->setAppName($this->AppName);
        $name = $name.'-'.date('YmdHi',$time);
        $request->setStreamName($this->PREFIX.$name);
        $time = $time - 8*65*60;
        $request->setStartTime(date('Y-m-d\TH:i:s\Z',$time));
        $request->setEndTime(date('Y-m-d\TH:i:s\Z',$time+24*3600));
        $request->setOssBucket(C('LIVE')['OssBucket']);
        $request->setOssEndpoint('oss-cn-shanghai.aliyuncs.com');
        $request->setOssObject($this->AppName.'/'.$this->PREFIX.$name.'.m3u8');
        return $this->respond($request)['RecordInfo'];
    }


    /**
     * 查询推流历史
     * @param $name 房间号
     * @param $startTime 开始时间
     * @param $endTime 结束时间
     * @return array|void
     */
    public function DescribeLiveStreamsPublishList($name,$startTime,$endTime){
        $request = new DescribeLiveStreamsPublishListRequest();
        $request->setDomainName($this->domain);
        $request->setAppName($this->AppName);
        $request->setStartTime('2018-08-23T08:00:00Z');
        $request->setEndTime('2018-08-25T20:00:00Z');
        return $this->respond($request);
    }

    /**
     * 查询推流在线列表
     * @return array|void
     */
    public function DescribeLiveStreamsOnlineList(){
        $request = new DescribeLiveStreamsOnlineListRequest();
        $request->setDomainName($this->domain);
        return $this->respond($request);
    }

    /**
     * 查询直播域名录制时长数据
     * @return array|void
     */
    public function DescribeLiveDomainRecordData(){
        $request = new DescribeLiveDomainRecordDataRequest();
        $request->setDomainName($this->domain);
        $request->setStartTime('2018-08-20T08:00:00Z');
        $request->setEndTime('2018-08-29T20:00:00Z');
        return $this->respond($request);
    }

    /**
     * 设置NotifyURL
     * @param $url 设置的地址
     */
    public function SetLiveStreamsNotifyUrlConfig($url){
        $request = new SetLiveStreamsNotifyUrlConfigRequest();
        $request->setDomainName($this->domain);
        $request->setNotifyUrl($url);
        return $this->respond($request);
    }

    /**
     * 查询NotifyURL
     */
    public function DescribeLiveStreamsNotifyUrlConfig(){
        $request = new DescribeLiveStreamsNotifyUrlConfigRequest();
        $request->setDomainName($this->domain);
        return $this->respond($request);
    }


}