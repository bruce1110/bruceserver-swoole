<?php
$cache['session'] = array(
    'type' => 'FileCache',
    'cache_dir' => WEBPATH.'/cache/filecache/',
);
$cache['master'] = array(
    /*'type' => 'Memcache',*/
    'type' => 'FileCache',
);
return $cache;