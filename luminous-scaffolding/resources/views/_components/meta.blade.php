@if ($tree && ! $tree->isEmpty())
<title>{{ $tree->all()->reverse()->implode('label', ' - ') }} | {{ $site->name }}</title>
@else
<title>@yield('meta:title', e("{$site->name} | {$site->description}"))</title>
@endif

@if ($post)
<meta name="description" content="{{ $post->excerpt }}">
@else
<meta name="description" content="@yield('meta:description', e($site->description))">
@endif
