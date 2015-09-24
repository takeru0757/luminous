<?php

if (! function_exists('luminous_mod_rewrite_rules')) {
    /**
     * Get the mod rewitre rules for Luminous.
     *
     * @uses \get_stylesheet_directory_uri()
     * @uses \is_child_theme()
     * @uses \wp_upload_dir()
     * @uses \home_url()
     *
     * @return string
     */
    function luminous_mod_rewrite_rules()
    {
        $publicUrl = get_stylesheet_directory_uri().(is_child_theme() ? '/public' : '/luminous-scaffolding/public');
        $uploads = wp_upload_dir();

        $rewriteBase = parse_url(home_url(), PHP_URL_PATH) ?: '/';
        $uploadsPath = $uploads['basedir'];
        $uploadsBase = parse_url($uploads['baseurl'], PHP_URL_PATH);
        $publicPath  = base_path('public');
        $publicBase  = parse_url($publicUrl, PHP_URL_PATH);

        return <<<EOT
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews
    </IfModule>

    RewriteEngine On
    RewriteBase {$rewriteBase}

    # Redirect Trailing Slashes
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)/$ /$1 [L,R=301]

    # uploads
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond {$uploadsPath}%{REQUEST_URI} -f
    RewriteRule ^uploads/(.*)$ {$uploadsBase}/$1 [L]

    # public
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond {$publicPath}%{REQUEST_URI} -f
    RewriteRule ^(.*)$ {$publicBase}/$1 [L]

    # WordPress
    RewriteRule ^index\.php$ - [L]
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule . /index.php [L]
</IfModule>
EOT;
    }
}

if (! function_exists('luminous_archive_url')) {
    /**
     * Generate a URL to archive. (for admin)
     *
     * @uses \home_url()
     *
     * @param \Luminous\Bridge\HasArchive $archiveFor
     * @return string
     */
    function luminous_archive_url(Luminous\Bridge\HasArchive $archiveFor)
    {
        $uri = archive_url($archiveFor);
        return home_url(substr($uri, strlen(app('request')->root())));
    }
}

if (! function_exists('luminous_post_url')) {
    /**
     * Generate a URL to the post. (for admin)
     *
     * @uses \home_url()
     *
     * @param \Luminous\Bridge\Post\Entities\Entity $post
     * @param string $placeholder
     * @return string
     */
    function luminous_post_url(Luminous\Bridge\Post\Entities\Entity $post, $placeholder = null)
    {
        if ($placeholder) {
            $parameters = [$post->type->hierarchical ? 'path' : 'slug' => $placeholder];
        } else {
            $parameters = [];
        }

        $uri = post_url($post, $parameters);
        return home_url(substr($uri, strlen(app('request')->root())));
    }
}
