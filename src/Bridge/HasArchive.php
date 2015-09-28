<?php

namespace Luminous\Bridge;

interface HasArchive
{
    /**
     * Get the route type for this instance.
     *
     * @return string
     */
    public function getRouteType();

    /**
     * Determine if this instance allows to show archive.
     *
     * @return bool
     */
    public function allowArchive();
}
