<?php
require __DIR__ . '/mySql/myPool.php';
require __DIR__ . '/mySql/mySqlPool.php';

$pool = new \Pool\mySql\mySqlPool(10);
echo $pool->query('selct name from user');
