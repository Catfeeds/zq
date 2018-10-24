<?php

class ConnRedis
{
    private $host = '192.168.1.241';
    private $port = 6379;
    private $auth = '';
    public $handler;

    public function __construct() {
        if ( !extension_loaded('redis') ) {
            exit('_NOT_SUPPERT_:redis');
        }

        $this->handler  = new Redis;
        $this->handler->connect($this->host, $this->port);

        if($this->auth != null)
        {
            $this->handler->auth($this->auth);
        }

        return $this->handler;
    }
}
 ?>