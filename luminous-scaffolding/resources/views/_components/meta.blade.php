@if ($tree && ! $tree->isEmpty())
<title>{{ $tree->all()->reverse()->implode('label', ' - ') }} | {{ $site->name }}</title>
@else
<title>@yield('meta:title', "{$site->name} | Luminous is a WordPress theme framework based on Laravel Lumen.")</title>
@endif

@if ($post)
<meta name="description" content="{{ $post->excerpt }}">
@else
<meta name="description" content="@yield('meta:description', "This theme is scaffolding of Luminous child themes.")">
@endif
