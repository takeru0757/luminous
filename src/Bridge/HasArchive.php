<?php

namespace Luminous\Bridge;

interface HasArchive
{
    /**
     * Get the route prefix for archive of this instance.
     *
     * @return string
     */
    public function getRoutePrefix();

    /**
     * Wheter this instance has archive.
     *
     * @return bool
     */
    public function hasArchive();
}
