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

if (! function_exists('replace_url_parameters_with_entity')) {
     /**
      * Replace parameters with the entity.
      *
      * @param string $uri
      * @param \Luminous\Bridge\HasParameter $entity
      * @return string
      */
    function replace_url_parameters_with_entity($uri, Luminous\Bridge\HasParameter $entity)
    {
        return preg_replace_callback('/\{(.*?)(:.*?)?(\{[0-9,]+\})?\}/', function ($m) use ($entity) {
            return $entity->parameter($m[1]);
        }, $uri);
    }
}

if (! function_exists('archive_url')) {
    /**
     * Generate a URL to archive.
     *
     * @param \Luminous\Bridge\HasArchive $archiveFor
     * @param string $sub
     * @param array $parameters
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    function archive_url(Luminous\Bridge\HasArchive $archiveFor, $sub = null, $parameters = [])
    {
        if (! $archiveFor->hasArchive()) {
            throw new InvalidArgumentException("{$archiveFor} does not have archive.");
        }

        $names = [$archiveFor->getRoutePrefix(), 'archive'];

        if (is_array($sub)) {
            $parameters = $sub;
        } elseif ($sub) {
            $names[] = $sub;
        }

        $uri = route(implode(':', $names), $parameters);

        if ($archiveFor instanceof Luminous\Bridge\HasParameter) {
            $uri = replace_url_parameters_with_entity($uri, $archiveFor);
        }

        return $uri;
    }
}

if (! function_exists('post_url')) {
    /**
     * Generate a URL to the post.
     *
     * @param \Luminous\Bridge\Post\Entities\Entity $post
     * @param array $parameters
     * @return string
     */
    function post_url(Luminous\Bridge\Post\Entities\Entity $post, $parameters = [])
    {
        $uri = route($post->getRouteName(), $parameters);
        return replace_url_parameters_with_entity($uri, $post);
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
