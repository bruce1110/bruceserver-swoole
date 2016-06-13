<?php
/**
 * User: Bruce Qin
 * Date: 2016/6/10
 * Time: 17:47
 */
define('DEBUG', 'on');
define('WEBPATH', __DIR__);
define('APPSPATH',__DIR__.'/soa');
require __DIR__ . '/vendor/autoload.php';
Swoole\Loader::vendor_init();
Swoole\Loader::addNameSpace('SoaServer', __DIR__ . '/soa');

$rpc = new SoaServer\Server();
$rpc->setLogger(new Swoole\Log\EchoLog(true));
$server = Swoole\Network\Server::autoCreate('0.0.0.0', 9999);
$server->setProtocol($rpc);
$server->run(
    array(
        'worker_num' => 1,
        'max_request' => 5000,
        'dispatch_mode' => 3,
        'open_length_check' => 1,
        'package_max_length' => $rpc->packet_maxlen,
        'package_length_type' => 'N',
        'package_body_offset' => \Swoole\Protocol\SOAServer::HEADER_SIZE,
        'package_length_offset' => 0,
    )
);