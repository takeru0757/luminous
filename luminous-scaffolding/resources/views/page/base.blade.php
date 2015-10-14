@extends('page.content')

@section('main')

<article class="m-b-lg">
    <h1 class="h2"><a href="{{ post_url($post) }}">{{ $post->title }}</a></h1>
    <p class="m-b-md text-muted"><time datetime="{{ $post->date->toW3cString() }}" pubdate>{{ $post->date(wp_option('date_format')) }}</time></p>
    {!! $post->content !!}
</article>

@if ($_children = $post->children->getOrNull())
<nav class="m-b-lg">
    <h1 class="h3">Child Pages</h1>
    <ul class="nav">
        @foreach ($_children as $_child)
        <li class="nav-item"><a class="nav-link" href="{{ post_url($_child) }}">{{ $_child->title }}</a></li>
        @endforeach
    </ul>
</nav>
@endif

@endsection
