@extends('page._content')

@section('main')

<article class="m-b-lg">
    <h1 class="h2"><a href="{{ post_url($query->post) }}">{{ $query->post->title }}</a></h1>
    <p class="m-b-md text-muted"><time datetime="{{ $query->post->date->toW3cString() }}" pubdate>{{ $query->post->date(wp_option('date_format')) }}</time></p>
    {!! $query->post->content !!}
</article>

@if ($children = $query->post->children->all())
<nav class="m-b-lg">
    <h1 class="h3">Child Pages</h1>
    <ul class="nav">
        @foreach ($children as $child)
        <li class="nav-item"><a class="nav-link" href="{{ post_url($child) }}">{{ $child->title }}</a></li>
        @endforeach
    </ul>
</nav>
@endif

@endsection
