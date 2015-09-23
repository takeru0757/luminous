<?php

if (! function_exists('luminous_mod_rewrite_rules')) {
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

if (! function_exists('luminous_post_route')) {
    function luminous_post_route($post, $placeholder)
    {
        $entity = app('wp')->post($post);

        $uri = route($entity->type->name);
        $uri = home_url(substr($uri, strlen(app('request')->root())));

        return preg_replace_callback('/\{(.*?)(:.*?)?(\{[0-9,]+\})?\}/', function ($m) use ($entity, $placeholder) {
            return $placeholder && in_array($m[1], ['slug', 'path']) ? $placeholder : $entity->parameter($m[1]);
        }, $uri);
    }
}
