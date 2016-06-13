<?php
/**
 * User: Bruce Qin
 * Date: 2016/6/13
 * Time: 11:38
 */
global $php;
$rpc = new \Swoole\Client\SOA();
$rpc->addServers($php->config['rpc']['server']);
return $rpc;