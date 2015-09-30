<?php

if (! function_exists('is_wp')) {
    /**
     * Determine if the request should be handled by WordPress.
     *
     * @uses $pagenow
     * @uses \WP_INSTALLING
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
     * @uses \app()
     *
     * @param string $path
     * @return string
     */
    function framework_base_path($path = '')
    {
        return app()->frameworkBasePath($path);
    }
}

if (! function_exists('archive_url')) {
    /**
     * Generate a URL to archive.
     *
     * @uses \route()
     *
     * @param \Luminous\Bridge\Post\Type $postType
     * @param string|array $sub
     * @param array $parameters
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    function archive_url(Luminous\Bridge\Post\Type $postType, $sub = null, $parameters = [])
    {
        if (! $postType->hasArchive()) {
            throw new InvalidArgumentException("{$postType->name} does not have archive.");
        }

        $name = "archive_url@{$postType->name}";

        if (is_array($sub)) {
            $parameters = $sub;
        } elseif ($sub) {
            $name .= "[{$sub}]";
        }

        return route($name, $parameters);
    }
}

if (! function_exists('post_url')) {
    /**
     * Generate a URL to the post.
     *
     * @uses \route()
     *
     * @param \Luminous\Bridge\Post\Entity $post
     * @param array $parameters
     * @return string
     */
    function post_url(Luminous\Bridge\Post\Entity $post, $parameters = [])
    {
        return preg_replace_callback('/\{(.*?)(:.*?)?(\{[0-9,]+\})?\}/', function ($m) use ($post) {
            return $post->urlParameter($m[1]);
        }, route("post_url@{$post->type->name}", $parameters));
    }
}

if (! function_exists('term_url')) {
    /**
     * Generate a URL to the term.
     *
     * @uses \route()
     *
     * @param \Luminous\Bridge\Term\Entity $term
     * @param array $parameters
     * @return string
     */
    function term_url(Luminous\Bridge\Term\Entity $term, $parameters = [])
    {
        $parameters['term'] = $term;
        return route("term_url@{$term->type->name}", $parameters);
    }
}

if (! function_exists('asset')) {
    /**
     * Get the path to a versioned file.
     *
     * @uses \config()
     * @uses \app()
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
            $manifestPath = config('assets.manifest');
            $manifest = json_decode(file_get_contents($manifestPath), true);
            $prefix = trim(config('assets.prefix'), '/').'/';
        }

        if (isset($manifest[$file])) {
            return app('url')->asset($prefix.$manifest[$file], $secure);
        }

        throw new InvalidArgumentException("File {$file} not defined in asset manifest.");
    }
}
