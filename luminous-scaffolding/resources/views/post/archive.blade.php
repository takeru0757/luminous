@extends('post.content')

@section('main')

@forelse ($posts as $_post)
<article class="m-b-lg">
    <h1 class="h4"><a href="{{ post_url($_post) }}">{{ $_post->title }}</a></h1>
    <p class="text-muted"><time datetime="{{ $_post->date->toW3cString() }}" pubdate>{{ $_post->date($site->dateFormat) }}</time></p>
    <p>{{ $_post->excerpt }}</p>
</article>
@empty
<p>No posts.</p>
@endforelse

<nav class="text-center">
    @include('_components.pagination', ['paginator' => $posts])
</nav>

@endsection
