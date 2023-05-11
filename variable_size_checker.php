<?php

/**
 * Get size of a variable
 *
 * null     = 0 bytes in the heap
 * bool     = 4 bytes in the heap
 * int      = 4 bytes in the heap
 * float    = 8 bytes in the heap
 * string   = each char is 1 byte in the heap
 * array    =
 * object   =
 * callable =
 * resource =
 *
 * @param mixed $variable
 *   The variable to inspect.
 *
 * @return int
 *   the actual size of the variable
 */

function get_var_size(mixed $var)
{
    // as every var in php is a zval
    // a zval takes automaticly 128 bit
    $size = 128 % 8; // these is in bytes

    $var_type = gettype($var);

    if ($var_type == 'boolean' || $var_type == 'integer') {
        $size += 4;
    } else if ($var_type == 'double') {
        $size += 8;
    } else if ($var_type == 'string') {
        $size += strlen($var);
    }

    return $size;

}
