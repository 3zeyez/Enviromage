<?php
require 'utlis.php';

function getConfigurations(): array
{
    $memory_limit = ini_get('memory_limit');
    // $memory_limit = return_bytes(ini_get('memory_limit'));
    $max_execution_time = ini_get('max_execution_time');
    $realpath_cache_size = return_bytes(ini_get('realpath_cache_size'));
    $realpath_cache_ttl = ini_get('realpath_cache_ttl');
    $upload_max_filesize = return_bytes(ini_get('upload_max_filesize'));
    $post_max_size = return_bytes(ini_get('post_max_size'));

    return [
        'memory_limit' => $memory_limit,
        'max_execution_time' => $max_execution_time,
        'realpath_cache_size' => $realpath_cache_size,
        'realpath_cache_ttl' => $realpath_cache_ttl,
        'upload_max_filesize' => $upload_max_filesize,
        'post_max_size' => $post_max_size,
    ];
}
