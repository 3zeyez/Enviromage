<?php

// simple PHP memory checker
function check_memory_readiness(int $memory_limit): string
{
    if ($memory_limit == -1):
        return 'Memory is ready';
    endif;

    $memory_limit = return_bytes($memory_limit);
    $memory_usage = memory_get_usage();
    $available_memory = $memory_limit - $memory_usage;

    // the threshold is 200MB
    if ($available_memory < 1048576 * 200) { // 1048576 bytes = 1 MB
        return 'Error: Not enough memory available';
    } else {
        return 'Memory is ready';
    }
}

// simple PHP variable size checker
function get_variable_size(mixed $variable): array
{
    $start_memory = memory_get_usage();
    $variable_size = strlen(serialize($variable));
    $end_memory = memory_get_usage();

    $size_in_memory = $end_memory - $start_memory;

    return array(
        'size' => $variable_size,
        'memory' => $size_in_memory,
    );
}

// simple PHP environment configuration checker
function getConfigurations(): array
{
    $memory_limit = ini_get('memory_limit');
    // $memory_limit = return_bytes(ini_get('memory_limit'));
    $max_execution_time = ini_get('max_execution_time');
    $realpath_cache_size = return_bytes(ini_get('realpath_cache_size'));
    $realpath_cache_ttl = return_bytes(ini_get('realpath_cache_ttl'));
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

function return_bytes(string $size): int
{
    $last = strtolower($size[strlen($size) - 1]);
    $size = (int) substr($size, 0, -1);

    switch ($last) {
        case 'g':
            $size *= 1024;
        case 'm':
            $size *= 1024;
        case 'k':
            $size *= 1024;
    }

    return $size;
}

// test
$conf = getConfigurations();
print_r($conf);

echo "\n";

$memory_limit = $conf['memory_limit'];
echo check_memory_readiness($memory_limit);

echo "\n";

print_r(get_variable_size($memory_limit));
