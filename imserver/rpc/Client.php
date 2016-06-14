<?php
namespace WebIm\Rpc;

use Swoole;

/**
 * User: Bruce Qin
 * Date: 2016/6/13
 * Time: 17:33
 */
class Client
{
    public static $host = array(
        '127.0.0.1:9999'
    );

    public static function connect()
    {
        $rpc = new \Swoole\Client\SOA();
        $rpc->addServers(self::$host);
        return $rpc;
    }
}