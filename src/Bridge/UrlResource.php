<?php

namespace Luminous\Bridge;

interface UrlResource
{
    /**
     * Get the URL path.
     *
     * @param string $key
     * @return string
     */
    public function urlPath($key);

    /**
     * Get the array to build URL.
     *
     * @return array
     */
    public function forUrl();
}
