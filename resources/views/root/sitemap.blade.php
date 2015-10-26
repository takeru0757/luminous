<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>

<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ url('/', true) }}</loc>
        <priority>1.0</priority>
        <changefreq>daily</changefreq>
        <lastmod>{{ wp_option('last_modified')->max($appModified)->toW3cString() }}</lastmod>
    </url>

    @foreach (app('wp')->postTypes() as $type)
    @if (($posts = app('wp')->posts($type)->orderBy('modified_at', 'desc')->get()) && ! $posts->isEmpty())
    @if ($type->hasArchive() && $latest = $posts->first())

    <url>
        <loc>{{ posts_url($type, true) }}</loc>
        <lastmod>{{ $latest->modified_at->max($appModified)->toW3cString() }}</lastmod>
        <priority>0.8</priority>
        <changefreq>weekly</changefreq>
    </url>

    @endif
    @foreach ($posts as $post)

    <url>
        <loc>{{ post_url($post, true) }}</loc>
        <lastmod>{{ $post->modified_at->max($appModified)->toW3cString() }}</lastmod>
        <priority>{{ $type->hierarchical ? '0.8' : '0.6' }}</priority>
        <changefreq>{{ $type->hierarchical ? 'weekly' : 'monthly' }}</changefreq>
    </url>

    @endforeach
    @endif
    @endforeach

</urlset>
