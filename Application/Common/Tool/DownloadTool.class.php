<?php
namespace Common\Tool;


/**
 * 
 * @User Administrator
 * @DateTime 2018年6月22日
 *
 */
class DownloadTool {
    
    // 控制下载速度
    private $_speed = 512;

    /**
     * __construct()
     *
     * @param $config
     * @return object
     */
    public function __construct($config = []) {
       
    }

    /**
     * 下载操作
     * 
     * @access public
     * @param $file 要下载的文件路径
     * @param $name 文件名称 为空则与下载的文件名一致
     * @param $reload 是否开启断点续传
     * @return mixed
     */
    public function download($file, $name = '', $reload = FALSE) {
        if (!file_exists($file)) {
            return FALSE;
        }
        
        if ($name == '') {
            $name = basename($file);
        }
        $fp = fopen($file, 'rb');
        $file_size = filesize($file);
        $ranges = $this->_getRange($file_size);
        
        header('cache-control:public');
        header('content-type:application/octet-stream');
        header('content-disposition:attachment; filename=' . $name);
        if ($reload && $ranges != NULL) { // 使用续传
            header('HTTP/1.1 206 Partial Content');
            
            header('Accept-Ranges:bytes');
            // 剩余长度
            header(sprintf('content-length:%u', $ranges['end'] - $ranges['start']));
            
            // range信息
            header(sprintf('content-range:bytes %s-%s/%s', $ranges['start'], $ranges['end'], $file_size));
            
            // fp指针跳到断点位置
            fseek($fp, sprintf('%u', $ranges['start']));
        } else {
            header('HTTP/1.1 200 OK');
            header('content-length:' . $file_size);
        }
        while (!feof($fp)) {
            echo fread($fp, round($this->_speed * 1024, 0));
            ob_flush();
        }
        ($fp != N) && fclose($fp);
        
        return TRUE;
    }

    /**
     * 下载远程文件
     * 
     * @access public
     * @param string $file 远程文件路径
     * @param string $name 文件名称
     * @return void
     */
    public function remoteDownload($file, $name = '') {
        if ($name == '') {
            $name = basename($file);
        }
        header('Content-Type: application/force-download');
        header('Content-Disposition: attachment; filename=' . $name);
        readfile($file);
    }

    /**
     * 设置下载速度
     * 
     * @access public
     * @param $speed 指定下载速度 单位KB
     * @return void
     */
    public function setSpeed($speed) {
        if (is_int($speed) && $speed > 16 && $speed < 4096) {
            $this->_speed = $speed;
        }
    }

    /**
     * 获取header range信息
     * 
     * @access private
     * @param $file_size 文件大小
     * @return mixed - array or NULL
     */
    private function _getRange($file_size) {
        if (isset($_SERVER['HTTP_RANGE']) && !empty($_SERVER['HTTP_RANGE'])) {
            $range = $_SERVER['HTTP_RANGE'];
            $range = preg_replace('/[\s|,].*/', '', $range);
            $range = explode('-', substr($range, 6));
            if (count($range) < 2) {
                $range[1] = $file_size;
            }
            $range = array_combine(array(
                'start', 
                'end' 
            ), $range);
            if (empty($range['start'])) {
                $range['start'] = 0;
            }
            if (empty($range['end'])) {
                $range['end'] = $file_size;
            }
            return $range;
        }
        return NULL;
    }

    /**
     * 强行下载 - 支持下载字符串
     * 
     * @access public
     * @param string $filename 文件名
     * @param string $data 文件内容
     * @return void
     */
    public function forceDownload($filename = '', $data = '') {
        if ($filename === '' or $data === '') {
            return;
        } elseif ($data === NULL) {
            if (@is_file($filename) && ($filesize = @filesize($filename)) !== FALSE) {
                $filepath = $filename;
                $filename = explode('/', str_replace(DIRECTORY_SEPARATOR, '/', $filename));
                $filename = end($filename);
            } else {
                return;
            }
        } else {
            $filesize = strlen($data);
        }
    
        // Set the default MIME type to send
        $mime = 'application/octet-stream';
    
        $x = explode('.', $filename);
        $extension = end($x);
    
        /*
         * It was reported that browsers on Android 2.1 (and possibly older as well)
         * need to have the filename extension upper-cased in order to be able to
         * download it.
         *
         * Reference: http://digiblog.de/2011/04/19/android-and-the-download-file-headers/
        */
        if (count($x) !== 1 && isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/Android\s(1|2\.[01])/', $_SERVER['HTTP_USER_AGENT'])) {
            $x[count($x) - 1] = strtoupper($extension);
            $filename = implode('.', $x);
        }
    
        if ($data === NULL && ($fp = @fopen($filepath, 'rb')) === FALSE) {
            return;
        }
    
        // Clean output buffer
        if (ob_get_level() !== 0 && @ob_end_clean() === FALSE) {
            @ob_clean();
        }
    
        // Generate the server headers
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . $filesize);
    
        // Internet Explorer-specific headers
        if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== FALSE) {
            header('Cache-Control: no-cache, no-store, must-revalidate');
        }
    
        header('Pragma: no-cache');
    
        // If we have raw data - just dump it
        if ($data !== NULL) {
            exit($data);
        }
    
        // Flush 1MB chunks of data
        while (!feof($fp) && ($data = fread($fp, round($this->_speed * 1024, 0))) !== FALSE) {
            echo $data;
        }
    
        fclose($fp);
        exit();
    }
}