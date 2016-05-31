<?php
/**
 * User: Bruce Qin
 * Date: 2016/5/31
 * Time: 14:25
 */
define('DEBUG', 'on');
define("WEBPATH",  __DIR__);
require __DIR__.'/vendor/autoload.php';
Swoole\Loader::vendor_init();
Swoole\Loader::addNameSpace('WebIm',__DIR__.'/imserver/');

$ws = new WebIm\Server(array());
$ws->loadSetting(__DIR__.'/swoole.ini');
$ws->setLogger(new Swoole\Log\EchoLog(true));

$enable_ssl = false;
$server = Swoole\Network\Server::autoCreate('0.0.0.0', 9443, $enable_ssl);
$server->setProtocol($ws);
//$server->daemonize(); //作为守护进程
$server->run(array(
    'worker_num' => 1,
    //'ssl_key_file' => __DIR__.'/ssl/ssl.key',
    //'ssl_cert_file' => __DIR__.'/ssl/ssl.crt',
    'max_request' => 1000,
    //'ipc_mode' => 2,
    //'heartbeat_check_interval' => 40,
    //'heartbeat_idle_time' => 60,
));