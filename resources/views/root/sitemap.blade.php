{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>{{ url('/') }}</loc>
    <priority>1.0</priority>
    <changefreq>daily</changefreq>
    <lastmod>{{ $modified->toW3cString() }}</lastmod>
  </url>
  @foreach ($types as $type)

  <?php $posts = $wp->posts($type)->orderBy('updated_at', 'desc')->get(); ?>

  @if ($type->hasArchive() && $latest = $posts->first())
  <url>
    <loc>{{ archive_url($type) }}</loc>
    <lastmod>{{ $latest->updated_at->toW3cString() }}</lastmod>
    <priority>0.8</priority>
    <changefreq>weekly</changefreq>
  </url>
  @endif

  @foreach ($posts as $post)
  <url>
    <loc>{{ post_url($post) }}</loc>
    <lastmod>{{ $post->updated_at->toW3cString() }}</lastmod>
    @if ($type->hierarchical)
    <priority>0.8</priority>
    <changefreq>weekly</changefreq>
    @else
    <priority>0.6</priority>
    <changefreq>monthly</changefreq>
    @endif
  </url>
  @endforeach

  @endforeach
</urlset>
