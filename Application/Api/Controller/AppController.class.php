<?php
set_time_limit(300);
/**
 * 安卓下载程序
 * @author dengwj <406516482@qq.com> 2016.12.7
 */

use Think\Controller;

class AppController extends PublicController
{
    public $param = null;

    public function _initialize()
    {
        $this->param = getParam(); //获取传入的参数
    }
    public function download()
    {
        $ip            = get_client_ip();
        $rand          = I('rand');
        $ua            = $_SERVER['HTTP_USER_AGENT'];
        //$dateTime      = strtotime(date('Ymd'));
        //$where['time'] = ['BETWEEN' , [ $dateTime, $dateTime + 86399 ] ];
        $where['rand'] = $rand;

        if($rand != '')
        {
            $downLog = M('apkDownload')->master(true)->where( $where )->find();

            if ( $downLog['number'] >= 5) //同一个ip每天最多下载5次
            {
                M('apkDownload')->where( $where )->save(['number'=>['exp','number+1'],'time'=>NOW_TIME]);
                die;
            }

            if(!$downLog)
            {
                M('apkDownload')->add(['ip'=>$ip,'rand'=>$rand,'ua'=>$ua,'time'=>NOW_TIME]);
            }
            else
            {
                M('apkDownload')->where( $where )->save(['number'=>['exp','number+1'],'time'=>NOW_TIME]);
            } 
        }

        $file = './Uploads/App/qqty.apk';//apk所在的路径地址

        if(file_exists($file) && is_file($file))
        {
            $filesize = filesize($file);
            $fileName = 'qqty.apk';
            $offset = 0;
            $length = $filesize;

            if ( isset($_SERVER['HTTP_RANGE']) ) {
                // if the HTTP_RANGE header is set we're dealing with partial content

                $partialContent = true;

                // find the requested range
                // this might be too simplistic, apparently the client can request
                // multiple ranges, which can become pretty complex, so ignore it for now
                preg_match('/bytes=(\d+)-(\d+)?/', $_SERVER['HTTP_RANGE'], $matches);

                $offset = intval($matches[1]);
                $length = intval($matches[2]) - $offset;
            } else {
                $partialContent = false;
            }

            $file = fopen($file, 'r');

            // seek to the requested offset, this is 0 if it's not a partial content request
            fseek($file, $offset);

            $data = fread($file, $length);

            fclose($file);

            if ( $partialContent ) {
                // output the right headers for partial content

                header('HTTP/1.1 206 Partial Content');

                header('Content-Range: bytes ' . $offset . '-' . ($offset + $length) . '/' . $filesize);
            }
            header('Content-Type: application/vnd.android.package-archive');
            header('ETag: 5844eddd-c7b5d3');

            // header('Content-Type: ' . $ctype);
            // header('Content-Length: ' . $filesize);
            header('Content-Length: ' . $length);
            // header('Content-Disposition: attachment; filename="' . $fileName . '"');
            header('Accept-Ranges: bytes');

            // don't forget to send the data too
            print($data);

            flush();
            // // output the regular HTTP headers
            // header('Content-Type: ' . $ctype);
            // header('Content-Length: ' . $filesize);
            // header('Content-Disposition: attachment; filename="' . $fileName . '"');
            // header('Accept-Ranges: bytes');

            // // don't forget to send the data too
            // print($data);

            // flush();
        }

        // if(file_exists($local_file) && is_file($local_file))
        // {
        //     $download_max = filesize($local_file) + 5*1024*1024;  //允许最大下载大小
        //     $download_rate = 10000;                               //每秒速度    
        //     header('Content-Description: File Transfer');
        //     header('Cache-control: private');
        //     header('Content-Type: application/octet-stream');
        //     header('Content-Length: '.filesize($local_file));
        //     header('Content-Disposition: attachment; filename="'.basename($local_file).'"');

        //     flush();
        //     $file = fopen($local_file, "r");
        //     while(!feof($file))
        //     {
        //         // send the current file part to the browser
        //         $now = $download_rate * 1024;
        //         $count += $now;
        //         if ($count>$download_max)
        //         {
        //             die('0');
        //         }
        //         print fread($file, round($now));
        //         // flush the content to the browser
        //         flush();
        //         // sleep one second
        //         sleep(1);
        //     }
        //     fclose($file);
        // }
    }
    

    /**
     * 推广渠道包排重
     * https://www.qqty.com/Api/app/appSpreadIdfaPc?
     * @User mjf
     * @DateTime 2018年6月14日
     *
     */
    public function appSpreadIdfaPc() {
        // 去我们平台查找
        $param = getParam();
        
        $returnData = array(
            'status' => 0,
            'info' => 'idfa empty!',
            'data' => array()
        );
        
        if(!empty($param['idfa'])){
            $find = M('appLog')->field(['id'])->where(array('idfa'=>$param['idfa']))->find();
            
            if(empty($find)){
                // 我们平台没有则去推广激活里面
                $find = M('appSpreadActivationLog')->field(['id'])->where(array('idfa'=>$param['idfa']))->find();
            }
            
            $info = 'idfa not exist';
            $data[$param['idfa']] = 0;
            
            if(!empty($find)){
                $data[$param['idfa']] = 1;
                $returnData['status'] = 1;
                $info = 'idfa alerady exist!';
            }
            
            $returnData['data'] = $data;
            $returnData['info'] = $info;
        }
        
        $this->ajaxReturn($returnData);
    }
    
    /**
     * 推广渠道包点击上报记录
     * https://www.qqty.com/Api/app/appSpreadClickTask?
     * 
     * @User mjf
     * @DateTime 2018年6月14日
     *
     */
    public function appSpreadClickTask() {
        /*mac idfa appid source callback ip version=1.1 */
    
        $param = getParam();
        
        $info = '点击上报失败';
        $idfaExistInfo = 'idfa 已经存在!';
        
        $status = 0;
        
        $returnData = [
            'info' => $info, 
            'status' => $status,
            'data' => $param
        ];
        if(empty($param['idfa'])){
            $returnData['info'] = 'idfa 为空';
            $this->ajaxReturn($returnData);
        }

        $find = M('appLog')->field(['id'])->where(array('idfa'=>$param['idfa']))->find();
        if(!empty($find)){
            // 我们的日志记录有idfa，则终止
            $returnData['info'] = $idfaExistInfo;
            $this->ajaxReturn($returnData);
        }
        
        $find = M('appSpreadClickLog')->field(['id'])->where(array('idfa'=>$param['idfa']))->find();
        if(!empty($find)){
            // 之前有点击记录则终止
            $returnData['info'] = $idfaExistInfo;
            $this->ajaxReturn($returnData);
        }
        
        $data['mac']   = !empty($param['mac']) ? $param['mac'] : '';
        $data['idfa']     = $param['idfa'];
        $data['appid'] = $param['appid'] ? :'';
        $data['source']       = !empty($param['source']) ? $param['source'] : '';
        $data['callback']      = !empty($param['callback']) ? $param['callback'] : '';
        $data['version']  = !empty($param['version']) ? $param['version'] : '1.1';
        $data['ip']       = empty($param['callback']) ? get_client_ip() : $param['callback'];
        $data['log_status'] = 1;
        
        $time = $_SERVER['REQUEST_TIME'];
        $data['add_time'] = $time;
        $data['add_date'] = date('Y-m-d H:i:s', $time);
        
        $rs = M('appSpreadClickLog')->add($data);
        
        if(!empty($rs)){
            $returnData['info'] = '上报成功!';
            $returnData['status'] = 1;
        }
        
        $this->ajaxReturn($returnData);
    }
    
    /**
     * 推广渠道包激活上报记录
     * https://www.qqty.com/Api/app/appSpreadTaskActivate
     * @User mjf
     * @DateTime 2018年6月14日
     *
     */
    public function appSpreadTaskActivate() {
        $param = getParam();
        
        $info = '激活失败';
        $idfaExistInfo = 'idfa 已经存在!';
        $status = 0;
        
        $returnData = [
            'info' => $info,
            'status' => $status,
            'data' => $param
        ];
        if(empty($param['idfa'])){
            $returnData['info'] = 'idfa 为空';
            $this->ajaxReturn($returnData);
        }
        
//         $find = M('appLog')->field(['id'])->where(array('idfa'=>$param['idfa']))->find();
//         if(empty($find)){
//             // 我们的日志记录没有idfa，则终止
//             $returnData['info'] = '无app操作记录，请重新操作激活';
//             $this->ajaxReturn($returnData);
//         }
        
        $find = M('appSpreadClickLog')->field(['id'])->where(array('idfa'=>$param['idfa']))->find();
        if(empty($find)){
            // 上报记录没有则终止
            $returnData['info'] = 'idfa没有点击上报';
            $this->ajaxReturn($returnData);
        }
        
        $find = M('appSpreadActivationLog')->field(['id'])->where(array('idfa'=>$param['idfa']))->find();
        if(!empty($find)){
            // 之前有激活记录则终止
            $returnData['info'] = $idfaExistInfo;
            $this->ajaxReturn($returnData);
        }
        
        $data['mac']   = !empty($param['mac']) ? $param['mac'] : '';
        $data['idfa']     = $param['idfa'];
        $data['appid'] = $param['appid'] ? :'';
        $data['source']       = !empty($param['source']) ? $param['source'] : '';
        $data['callback']      = !empty($param['callback']) ? $param['callback'] : '';
        $data['version']  = !empty($param['version']) ? $param['version'] : '1.1';
        $data['ip']       = empty($param['callback']) ? get_client_ip() : $param['callback'];
       
        $time = $_SERVER['REQUEST_TIME'];
        $data['add_time'] = $time;
        $data['add_date'] = date('Y-m-d H:i:s', $time);
        
        $data['log_status'] = 1;
        
        $rs = M('appSpreadActivationLog')->add($data);
        
        if(!empty($rs)){
            $returnData['info'] = '激活成功!';
            $returnData['status'] = 1;
        }
        
        $this->ajaxReturn($returnData);
    }
    
    
    public function diffDate() {
        $idfa = I('idfa');
        $data = M('appLog')->where('idfa in ('.$idfa.')')->order('add_time asc')->select();
       
        $newData1 = [];
        $newData2 = [];
        foreach ($data as $dt){
            if(1 == $dt['type']){
                $newData1[$dt['idfa']][] = $dt;
            }
            
            if(2 == $dt['type']){
                $newData2[$dt['idfa']][] = $dt;
            }
        }
        
        $logData = [];
        
        foreach ($newData1 as $key=>$data1){
            $closeData = $newData2[$key];
            foreach ($data1 as $num=>$dt1){
                $times = $closeData[$num]['add_time'] - $dt1['add_time'];
                $currTime = 0;
                
                if($times > 0){
                     $hour = floor($times/3600);  
                    $minute = floor(($times-3600 * $hour)/60);  
                    $second = floor((($times-3600 * $hour) - 60 * $minute) % 60);  
                    $currTime = $hour.':'.$minute.':'.$second;  
                }
                $logData[$key][$dt1['add_time']] = array(
                    'start_time' => $dt1['add_time'],
                    'close_time' => $closeData[$num]['add_time'],
                    'curr_time' => $currTime,
                );
                
                echo $key.'在线时长：'.$currTime.PHP_EOL;
            }
            
        }
        
//         var_dump($logData);
    }

    //提供上传图片
    public function uploadFileData()
    {
        //表单名称
        $fileData = $_FILES['file'];

        if (empty($fileData))
            $this->ajaxReturn(1029);

        $result = D('Uploads')->uploadFile("file", 'filedata');
        if($result['status'] == 1)
            $this->ajaxReturn(['result'=>1,'imgUrl'=>imagesReplace($result['url'])]);
        else
            $this->ajaxReturn(1031);
    }
}


 ?>