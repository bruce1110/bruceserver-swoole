<?php
require __DIR__ . '/mySql/myPool.php';
$pool = new Pool\mySql\Pool(10);

$pool->create(function () use ($pool) {
    $db = new swoole_mysql;
    $server = array(
        'host' => '10.1.20.84',
        'user' => 'bruce',
        'password' => '123456',
        'database' => 'soap',
    );
    $db->on('close', function ($db) use ($pool) {
        $pool->remove($db);
    });
    return $db->connect($server, function ($db, $result) use ($pool) {
        $pool->join($db);
    });
});

for ($i = 0; $i < 20; $i++) {
    $pool->request(function ($db) use ($pool) {
        $r = $db->query("show tables", function (swoole_mysql $db, $r) use ($pool) {
            global $s;
            if ($r === false) {
                var_dump($db->error, $db->errno);
            } elseif ($r === true) {
                var_dump($db->affected_rows, $db->insert_id);
            }
            echo "count=" . count($r) . ", time=" . (microtime(true) - $s), "\n";
            //var_dump($r);
            //ÊÍ·Å×ÊÔ´
            $pool->release($db);
        });

        if ($r == false) {
            die("xxx\n");
        }

    });
}
