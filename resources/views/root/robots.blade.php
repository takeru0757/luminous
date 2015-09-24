Sitemap: {{ url('sitemap.xml') }}

User-agent: *
Disallow: {{ parse_url(admin_url('/'), PHP_URL_PATH) }}
Disallow: {{ parse_url(includes_url('/'), PHP_URL_PATH) }}
Disallow: {{ parse_url(content_url('/'), PHP_URL_PATH) }}
