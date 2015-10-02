<?php

return [

    // -------------------------------------------------------------------------
    // View Resources Paths
    // -------------------------------------------------------------------------

    'paths' => [
        realpath(base_path('resources/views')),
        realpath(framework_base_path('resources/views')),
    ],

    // -------------------------------------------------------------------------
    // Compiled View Path
    // -------------------------------------------------------------------------

    'compiled' => realpath(storage_path('framework/views')),

];
