<?php
require "utils.php";

function check_memory_readiness(int $memory_limit): string
{
    if ($memory_limit == -1): // there is no limit
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
