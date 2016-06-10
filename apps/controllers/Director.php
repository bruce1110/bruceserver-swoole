<?php
namespace App\Controller;
use Swoole;
/**
 * User: Bruce Qin
 * Date: 2016/6/9
 * Time: 16:12
 */
class Director extends Swoole\Controller
{
    function login()
    {
        $r = Model('Director');
        $r->get(1);
        var_dump($r);
    }
    function ImAuth()
    {
        $rpc = new Swoole\Client\SOA();;
        $rpc->addServers(array('127.0.0.1:9999'));
        $result = $rpc->task('SoaServer\Auth\Director::token');
        var_dump($result);
    }
}