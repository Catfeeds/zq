<?php
/**
 * 首页
 *
 * @author chenzj <443629770@qq.com>
 *
 * @since  2016-04-19
 */
use Think\Tool\Tool;
class LiveController extends CommonController {
    protected function _initialize() {
    }
    public function index() {
        $this->display();
    }
    public function detail() {
        $type=I('get.type','');
        $array=array(
            'euro'=>'http://aiseet.lszb.atianqi.com/app_4/ozzq.m3u8?bitrate=2000',
            'cctv5hd'=>'http://live.hcs.cmvideo.cn:8088/wd_r2/cctv/cctv5hd/350/01.m3u8?msisdn=3622306160532&mdspid=&spid=699004&netType=5&sid=2201062977&pid=2028597139&timestamp=20160901154339&Channel_ID=0116_22000109-99000-200300270000001&ProgramID=620806337&ParentNodeID=-99&client_ip=221.4.32.7&assertID=2201062977&SecurityKey=20160901154339&mtv_session=b69ffec77f9a7b547fc75cacadabfed9&HlsSubType=1&HlsProfileId=1&encrypt=4711b3829869b63b9d546a1b60972822',
            'cctv5bq'=>'http://live.hcs.cmvideo.cn:8088/wd_r2/cctv/cctv5hd/600/01.m3u8?msisdn=3622307219245&mdspid=&spid=699004&netType=5&sid=2201062977&pid=2028597139&timestamp=20160901154339&Channel_ID=0116_22000109-99000-200300270000001&ProgramID=620806337&ParentNodeID=-99&client_ip=60.211.208.24&assertID=2201062977&SecurityKey=20160901154339&mtv_session=a0a16b2b195e4c83749ddebd5f30488f&HlsSubType=1&HlsProfileId=1&encrypt=99ce749ce6ff58b4c67b2130a357e119',
            'fengyun'=>'http://61.129.131.53/live.aishang.ctlcdn.com/00000110240260_1/encoder/0/playlist.m3u8',
            'beijing'=>'http://58.200.131.2/hls/btv6hd.m3u8',
            'cctv5hdd'=>'http://live.hcs.cmvideo.cn:8088/wd_r2/cctv/cctv5hd/1200/01.m3u8?msisdn=3622307124197&mdspid=&spid=699004&netType=5&sid=2201062977&pid=2028597139&timestamp=20160830173940&Channel_ID=0116_22000109-99000-200300270000001&ProgramID=620806337&ParentNodeID=-99&client_ip=61.156.243.229&assertID=2201062977&SecurityKey=20160830173940&mtv_session=e546a90c9056df4911bb78e50cd85553&HlsSubType=1&HlsProfileId=1&encrypt=58e56534bd29905e6b582eb73b4e530c',
            'zhibo7'=>'http://pili-live-hls.cjzhibo.net/cjzb/577cdc805e77b03720003260.m3u8',
            'zhibo8'=>'http://hls69.310tv.com/live/JRCP5Z9ATvwCf.m3u8',
            'hk'=>'http://222.186.130.97:8082/live/0d63qsTreDCrL.m3u8',
            'gdty'=>'http://125.88.92.166:30001/PLTV/88888956/224/3221227703/1.m3u8',
            'wlzb1'=>'http://222.186.130.97:8082/live/0d63qsTreDCrL.m3u8',
            'wlzb4'=>'http://222.186.130.97:8082/live/MynggJ1Pv9Kco.m3u8',
            'wlzb5'=>'http://zhibo.310tv.com:8083/videos/live/58/4/IE4Zgs6RCEXtH/IE4Zgs6RCEXtH.m3u8',
            'wlzb7'=>'http://222.186.130.97:8082/live/OOMfKKOwTwbzv.m3u8',
            'wlzb8'=>'http://222.186.130.106:8082/live/JRCP5Z9ATvwCf.m3u8',
            'wlzb9'=>'http://222.186.130.97:8082/live/3xCuz9xL6N7Xr.m3u8',
            'wlzb10'=>'http://222.186.130.97:8082/live/XuTngG0sstnbb.m3u8',
        );
        $link=$array[$type];
        $this->assign('link', $link);
        $this->display();
    }
    
}