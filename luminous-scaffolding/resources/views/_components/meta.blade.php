@if ($tree && ! $tree->isEmpty())
<title>{{ $tree->all()->reverse()->implode('label', ' - ') }} | {{ wp_option('name') }}</title>
@else
<title>@yield('meta:title', e(wp_option('name').' | '.wp_option('description')))</title>
@endif

@if ($post)
<meta name="description" content="{{ $post->excerpt }}">

@if ($post instanceof Luminous\Bridge\Post\Entities\NonHierarchicalEntity)
@if ($_prev = $post->newer)
<link rel="prev" href="{{ post_url($_prev) }}">
@endif
@if ($_next = $post->older)
<link rel="next" href="{{ post_url($_next) }}">
@endif
@endif

@else
<meta name="description" content="@yield('meta:description', e(wp_option('description')))">
@endif
