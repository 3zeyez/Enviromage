<?php

/**
 * Get information about a variable, including its type, size, and value.
 *
 * @param mixed $variable
 *   The variable to inspect.
 *
 * @return array
 *   An array of information about the variable.
 */
function get_variable_info($variable)
{
    $info = array();
    $info['type'] = gettype($variable);
    $info['size'] = strlen(serialize($variable));
    $info['value'] = $variable;
    return $info;
}