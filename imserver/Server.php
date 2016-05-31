<?php
/**
 * User: Bruce Qin
 * Date: 2016/5/31
 * Time: 14:25
 */
namespace WebIm;
use Swoole;

class Server extends Swoole\Protocol\WebSocket
{
    function onMessage($client_id, $message)
    {
        // TODO: Implement onMessage() method.
        $this->log("onMessage: ".$client_id.' = '.$message['message']);
        $this->send($client_id, 'Server is ok!');
    }
}