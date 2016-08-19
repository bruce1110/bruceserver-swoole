<?php
namespace Pool\mySql;

use Swoole\Async\MySQL;

/**
 * Created by PhpStorm.
 * User: qinchong
 * Date: 2016/8/19
 * Time: 10:48
 */
class myPool extends MySQL
{
    public function __construct(array $config, $pool_size)
    {
        parent::__construct($config, 10);
    }
}
