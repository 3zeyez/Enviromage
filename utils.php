<?php

function return_bytes(string $size): int
{
    $last = strtolower($size[strlen($size) - 1]);
    $size = (int) substr($size, 0, -1);

    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        switch ($last) {
            case 'g':
                $size *= 1024;
            case 'm':
                $size *= 1024;
            case 'k':
                $size *= 1024;
        }
    } else {
        switch ($last) {
            case 'g':
                $size *= 1000;
            case 'm':
                $size *= 1000;
            case 'k':
                $size *= 1000;
        }
    }
    return $size;
}
