<?php

/**
 * Get size of a variable
 *
 * These are done:
 * null     = 0 bytes in the heap
 * bool     = 4 bytes in the heap
 * int      = 4 bytes in the heap
 * float    = 8 bytes in the heap
 * string   = each char is 1 byte in the heap
 *
 * array    = each key has a Zend string size
 *            and each value has a size as a Zval variable
 * To Do:
 * object   =
 * callable =
 * resource =
 *
 * @param mixed $var
 *   The variable to inspect.
 *
 * @return int
 *   the actual size of the variable
 */

function get_var_size(mixed $var): int
{
    // as every var in php is a zval
    // a zval takes automaticly 128 bit
    $size = 128 / 8; // these is in bytes

    $var_type = gettype($var);

    if ($var_type === 'boolean' || $var_type === 'integer') {
        $size += 4;
    } else if ($var_type === 'double') {
        $size += 8;
    } else if ($var_type === 'string') {
        $size += strlen($var);
    } else if ($var_type === 'array') {
        foreach ($var as $key => $value):
            $size += get_var_size($key);
            $size += get_var_size($value);
        endforeach;

    }

    return $size;

}
