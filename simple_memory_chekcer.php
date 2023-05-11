<?php
function check_memory_readiness(): string
{
    $memory_limit = ini_get('memory_limit');
    $memory_limit_bytes = return_bytes($memory_limit);
    $memory_usage = memory_get_usage();
    $available_memory = $memory_limit_bytes - $memory_usage;

    // the threshold is 200MB
    if ($available_memory < 1048576 * 200) { // 1048576 bytes = 1 MB
        return 'Error: Not enough memory available';
    } else {
        return 'Memory is ready';
    }
}


