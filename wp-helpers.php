<?php

use Luminous\Bridge\Post\Entities\AttachmentEntity;

if (! function_exists('luminous_mod_rewrite_rules')) {
    /**
     * Get the mod rewitre rules for Luminous.
     *
     * @uses \Luminous\Bridge\Post\Entities\AttachmentEntity::attachmentPath()
     * @uses \get_stylesheet_directory_uri()
     * @uses \is_child_theme()
     * @uses \wp_upload_dir()
     * @uses \home_url()
     *
     * @return string
     */
    function luminous_mod_rewrite_rules()
    {
        $rewriteBase = parse_url(home_url('/'), PHP_URL_PATH);

        $uploadUrlReal = wp_upload_dir()['baseurl'];
        $uploadUrlBase = AttachmentEntity::attachmentPath($uploadUrlReal);
        $uploadDir = parse_url($uploadUrlReal, PHP_URL_PATH);

        $publicUrlReal = get_stylesheet_directory_uri().(is_child_theme() ? '/public' : '/luminous-scaffolding/public');
        $publicDirPath = base_path('public');
        $publicDir = parse_url($publicUrlReal, PHP_URL_PATH);

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
    RewriteRule ^{$uploadUrlBase}/(.*)$ {$uploadDir}/$1 [L]

    # public
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond {$publicDirPath}%{REQUEST_URI} -f
    RewriteRule ^(.*)$ {$publicDir}/$1 [L]

    # WordPress
    RewriteRule ^index\.php$ - [L]
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule . /index.php [L]
</IfModule>
EOT;
    }
}
