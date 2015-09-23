<?php

if (! function_exists('is_wp')) {
    /**
     * Determine if the request should be handled by WordPress.
     *
     * @uses WP_INSTALLING
     * @uses $pagenow
     * @uses \is_admin()
     *
     * @return bool
     */
    function is_wp()
    {
        global $pagenow;

        $scripts = [
            'wp-activate.php',
            'wp-comments-post.php',
            'wp-cron.php',
            'wp-links-opml.php',
            'wp-login.php',
            'wp-mail.php',
            'wp-signup.php',
            'wp-trackback.php',
            'xmlrpc.php',
        ];

        return defined('WP_INSTALLING') || in_array($pagenow, $scripts) || is_admin();
    }
}

if (! function_exists('framework_base_path')) {
    /**
     * Get the path to the Luminous framework directory.
     *
     * @param string $path
     * @return string
     */
    function framework_base_path($path = '')
    {
        return app()->frameworkBasePath($path);
    }
}

if (! function_exists('asset')) {
    /**
     * Get the path to a versioned file.
     *
     * @param string $file
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    function asset($file)
    {
        static $manifest = null;

        if (is_null($manifest)) {
            $manifest = json_decode(file_get_contents(base_path('public/assets/rev-manifest.json')), true);
        }

        if (isset($manifest[$file])) {
            return url('assets/'.$manifest[$file]);
        }

        throw new InvalidArgumentException("File {$file} not defined in asset manifest.");
    }
}
