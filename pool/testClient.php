<?php
use Pool\mySql\myPool;
/**
 * Created by PhpStorm.
 * User: qinchong
 * Date: 2016/8/19
 * Time: 11:02
 */

$config = array(
    'host'=>'10.2.2.105',
    'database'=>'soap',
    'user'=>'bruce',
    'password'=>'123456'
    );
$pool = new myPool($config);
var_dump($pool);