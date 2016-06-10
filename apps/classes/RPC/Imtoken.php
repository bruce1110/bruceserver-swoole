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
    public function __construct()
    {
        $server = new Server();
        $processorHandle = $server->getProcedureHandler();
        $processorHandle->withClassAndMethod('token','\App\Token\Director','identify');
        echo $server->execute();
    }
}