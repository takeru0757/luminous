<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>

<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ url('/', true) }}</loc>
        <priority>1.0</priority>
        <changefreq>daily</changefreq>
        <lastmod>{{ $wp->lastModified()->max($appModified)->toW3cString() }}</lastmod>
    </url>

    @foreach ($wp->postTypes() as $_type)
    @if ($_posts = $wp->posts($_type)->orderBy('modified_at', 'desc')->get())
    @if ($_type->hasArchive() && $_latest = $_posts->first())

    <url>
        <loc>{{ posts_url($_type, [], true) }}</loc>
        <lastmod>{{ $_latest->modified_at->max($appModified)->toW3cString() }}</lastmod>
        <priority>0.8</priority>
        <changefreq>weekly</changefreq>
    </url>

    @endif
    @foreach ($_posts as $_post)

    <url>
        <loc>{{ post_url($_post, [], true) }}</loc>
        <lastmod>{{ $_post->modified_at->max($appModified)->toW3cString() }}</lastmod>
        <priority>{{ $_type->hierarchical ? '0.8' : '0.6' }}</priority>
        <changefreq>{{ $_type->hierarchical ? 'weekly' : 'monthly' }}</changefreq>
    </url>

    @endforeach
    @endif
    @endforeach

</urlset>
