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
     * @param bool|null $secure
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    function asset($file, $secure = null)
    {
        static $manifest = null;
        static $prefix = null;

        if (is_null($manifest)) {
            $manifestPath = config('assets.manifest', base_path('public/assets/rev-manifest.json'));
            $manifest = json_decode(file_get_contents($manifestPath), true);
            $prefix = trim(config('assets.prefix', '/assets'), '/').'/';
        }

        if (isset($manifest[$file])) {
            return app('url')->asset($prefix.$manifest[$file], $secure);
        }

        throw new InvalidArgumentException("File {$file} not defined in asset manifest.");
    }
}
