<?php
namespace App\RPC;
use JsonRPC\Server;
/**
 * User: Bruce Qin
 * Date: 2016/6/10
 * Time: 13:45
 */
class Imtoken
{
    public static function getserver()
    {
        $server = new Server();
        $processorHandle = $server->getProcedureHandler();
        $processorHandle->withClassAndMethod('token',new \App\Token\Director(),'identify');
        echo $server->execute();
    }
}