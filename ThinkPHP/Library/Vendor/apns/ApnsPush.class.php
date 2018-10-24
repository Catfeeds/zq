<?php

/**
 * ApplePush 苹果消息推送
 */
class ApnsPush
{
	protected $retryTimes = 3;//
	protected $retryInterval = 1000000;//毫秒
	protected $passphrase = '';

	const WRITE_INTERVAL = 10000;
	const STATUS_CODE_INTERNAL_ERROR = 999;
	const ERROR_RESPONSE_SIZE = 6;
	const ERROR_RESPONSE_COMMAND = 8;

	protected $_errorResponseMessages = array(
			0 => 'No errors encountered',
			1 => 'Processing error',
			2 => 'Missing device token',
			3 => 'Missing topic',
			4 => 'Missing payload',
			5 => 'Invalid token size',
			6 => 'Invalid topic size',
			7 => 'Invalid payload size',
			8 => 'Invalid token',
			self::STATUS_CODE_INTERNAL_ERROR => 'Internal error'
	);

	protected $pem = [
			0 => 'cer/development.pem',
			1 => 'cer/distribution.pem'
	];
	protected $env = 0;
	/**
	 * APNS server url
	 *
	 * @var string
	 */
	public $apns_urls = [
			0 => 'ssl://gateway.sandbox.push.apple.com:2195',
			1 => 'ssl://gateway.push.apple.com:2195'
	];

	private $payload_json;

	public $fp;
	public $logStr = '';
	public $debug = '';

	/**
	 * ApplePush constructor.
	 * @param int $env
	 */
	public function __construct($env = 0, $passphrase)
	{
		$this->env = $env;
		$this->passphrase = $passphrase;
	}

	/**
	 * 设置推送的消息
	 * @param $body
	 * @return bool
	 */
	public function setBody($body)
	{
		if (empty($body)) {
			return false;
		} else {
			$this->payload_json = json_encode($body);
		}
		return true;
	}

	/**
	 * 获取apns连接
	 * @return bool
	 */
	public function connect()
	{
		$bConnected = false;
		$nRetry = 0;
		while (!$bConnected) {
			$bConnected = $this->_connect();
			if (!$bConnected && $nRetry <= $this->retryTimes) {
				$this->logStr .=  "重新链接到 '{$this->apns_urls[$this->env]}'\r\n";
				usleep($this->retryInterval);
			} else {
				break;
			}
			$nRetry++;
		}
	}

	/**
	 * 打开APNS服务器连接
	 * @return bool
	 */
	public function _connect()
	{
		$ctx = stream_context_create();
		stream_context_set_option($ctx, 'ssl', 'local_cert', __DIR__ . '/'.$this->pem[$this->env]);
		stream_context_set_option($ctx, 'ssl', 'passphrase', $this->passphrase);
		$fp = stream_socket_client($this->apns_urls[$this->env], $err, $errstr, 60, STREAM_CLIENT_CONNECT, $ctx);
		if (!$fp) {
			$this->logStr .= "未能连接到 '{$this->apns_urls[$this->env]}': {$err} ({$errstr}) \r\n";
			return false;
		}
		$this->fp = $fp;
		stream_set_blocking($this->fp, 0);
		stream_set_write_buffer($this->fp, 0);

		$this->logStr .= "成功链接到 {$this->apns_urls[$this->env]} \r\n";
		return true;
	}

	/**
	 * 推送
	 * @param $token
	 * @param $msgid
	 * @return bool|int
	 */
	public function send($token, $msgid)
	{
		if (!isset($this->payload_json)) {
			return false;
		}

		$msg = pack('CNNnH*', 1, $msgid, 864000, 32, $token) . pack('n', strlen($this->payload_json)) . $this->payload_json;
		$nLen = strlen($msg);
		if ($nLen !== ($nWritten = (int)@fwrite($this->fp, $msg, $nLen))) {
			//错误消息
			$aErrorMessage = array(
					'identifier' => $msgid,
					'statusCode' => self::STATUS_CODE_INTERNAL_ERROR,
					'statusMessage' => sprintf('%s (%d bytes written instead of %d bytes)',
							$this->_errorResponseMessages[self::STATUS_CODE_INTERNAL_ERROR], $nWritten, $nLen
					)
			);
			$this->logStr .= 'fwrite 错误：'. json_decode($aErrorMessage)."\r\n";
			$this->debug = $aErrorMessage;
		}

		$this->logStr .= "send success !\r\n";
		usleep(self::WRITE_INTERVAL);
		//usleep(self::WRITE_INTERVAL);
		return isset($aErrorMessage)?false:true;
	}

	/**
	 * 获取错误
	 * @return array|bool
	 */
	public function readErrMsg()
	{
		$errInfo1 = @fread($this->fp, self::ERROR_RESPONSE_SIZE);
		if ($errInfo1 === false || strlen($errInfo1) != self::ERROR_RESPONSE_SIZE) {
			return true;
		}

		$errInfo = unpack('Ccommand/CstatusCode/Nidentifier', $errInfo1);
		if (!is_array($errInfo) || empty($errInfo)) {
			return true;
		}
		if (!isset($errInfo['command'], $errInfo['statusCode'], $errInfo['identifier'])) {
			return true;
		}
		if ($errInfo['command'] != self::ERROR_RESPONSE_COMMAND) {
			return true;
		}
		$errInfo['timeline'] = time();
		$errInfo['statusMessage'] = 'None (unknown)';
		$errInfo['errorIdentifier'] = $errInfo['identifier'];
		if (isset($this->_errorResponseMessages[$errInfo['statusCode']])) {
			$errInfo['statusMessage'] = $this->_errorResponseMessages[$errInfo['statusCode']];
		}
		return $errInfo;
	}

	/**
	 * @return array
	 */
	public function feedback()
	{
		$nFeedbackTupleLen = 38;

		$feedback = array();
		$sBuffer = '';
		while (!feof($this->fp)) {
			$sBuffer .= $sCurrBuffer = fread($this->fp, 8192);
			$nBufferLen = strlen($sBuffer);
			if ($nBufferLen >= $nFeedbackTupleLen) {
				$nFeedbackTuples = floor($nBufferLen / $nFeedbackTupleLen);
				for ($i = 0; $i < $nFeedbackTuples; $i++) {
					$sFeedbackTuple = substr($sBuffer, 0, $nFeedbackTupleLen);
					$feedback[] = unpack('Ntimestamp/ntokenLength/H*deviceToken', $sFeedbackTuple);
				}
			}

			$read = array($this->fp);
			$nChangedStreams = stream_select($read, $null=null, $null=null, 0, 1000000);
			if ($nChangedStreams === false) {
				echo 'WARNING: Unable to wait for a stream availability.';
				break;
			}
		}
		return $feedback;
	}

	/**
	 * Close APNS server 关闭APNS服务器连接
	 *
	 */
	public function close()
	{
		fclose($this->fp);
		$this->logStr .= "fclose \r\n";
		return true;
	}
}

?>
