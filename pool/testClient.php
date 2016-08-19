<?php
use Pool\mySql\myPool;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/mySql/myPool.php';
/**
 * Created by PhpStorm.
 * User: qinchong
 * Date: 2016/8/19
 * Time: 11:02
 */
$db = new swoole_mysql;
$server = array(
    'host' => '10.2.2.105',
    'user' => 'bruce',
    'password' => '12345',
    'database' => 'soap',
);

$db->connect($server, function ($db, $r) {
    if ($r === false) {
        var_dump($db->connect_errno, $db->connect_error);
        die;
    }
    $sql = 'show tables';
    $db->query($sql, function (swoole_mysql $db, $r) {
        global $s;
        if ($r === false) {
            var_dump($db->error, $db->errno);
        } elseif ($r === true) {
            var_dump($db->affected_rows, $db->insert_id);
        }
        var_dump($r);
        $db->close();
    });
});
