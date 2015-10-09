<?php

namespace Luminous\Support;

use Illuminate\Support\Str;

class Url
{
    /**
     * Determine if the given path is a valid URL.
     *
     * @todo Fix options for 'filter_var()'.
     *
     * @param mixed $path
     * @return bool
     */
    public static function valid($path)
    {
        if (! is_string($path)) {
            return false;
        } elseif (Str::startsWith($path, ['//', '#', '?', 'javascript:', 'mailto:', 'tel:', 'sms:'])) {
            return true;
        }

        return filter_var($path, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Join paths.
     *
     * @param array|string,... $paths
     * @return string
     */
    public static function join($paths)
    {
        $paths = is_array($paths) ? $paths : func_get_args();

        $paths = array_map(function ($path) {
            return trim($path, '/');
        }, $paths);

        return implode('/', array_filter($paths, 'strlen'));
    }
}
