<?php
/**
 * User: Bruce Qin
 * Date: 2016/5/30
 * Time: 14:39
 */
define('DEBUG','on');
defile('WEBPATH',__DIR__);

require __DIR__.'./vendor/autoload.php';

Swoole\Loader::vendor_init();
$app  = new Swoole\Protocol\AppServer(array());
$app->loadSetting(__DIR__.'/app.ini');
$app->setDocumentRoot(__DIR__.'/static');
$app->setLogger(new Swoole\Log\EchoLog(true));
$app->setAppPath(__DIR__.'/apps');

$server = new Swoole\Network\Server('0.0.0.0','8889');
$server->setProtocol($app);
$server->run(array('worker_num' => 2, 'max_request' => 5000));