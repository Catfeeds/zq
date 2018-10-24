<?php

function S($name,$value='',$expire='')
{
    require_once(dirname(__FILE__)."/../lib/ConnRedis.php");
    $redis = (new ConnRedis())->handler;

    if ($value == '')
    {
        return $redis->get($name);
    }
    else
    {
        if ($expire == '')
            return $redis->set($name, $value);
        else
            return $redis->setex($name, $expire, $value);
    }
}

 ?>