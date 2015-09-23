@extends('post.content')

@section('main')

@forelse ($posts as $post)
<article class="m-b-lg">
  <h1 class="h4"><a href="{{ route('post', $post->parameters('year', 'month', 'day', 'slug')) }}">{{ $post->title }}</a></h1>
  <p class="text-muted"><time datetime="{{ $post->date->toW3cString() }}" pubdate>{{ $post->date('F j, Y') }}</time></p>
  <p>{{ $post->excerpt }}</p>
</article>
@empty
<p>No posts.</p>
@endforelse

{!! $posts->render() !!}

@endsection
