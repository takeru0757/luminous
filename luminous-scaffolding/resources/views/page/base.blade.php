@extends('page.content')

@section('main')

<article class="m-b-lg">
  <h1 class="h2"><a href="{{ post_url($post) }}">{{ $post->title }}</a></h1>
  <p class="m-b-md text-muted"><time datetime="{{ $post->date->toW3cString() }}" pubdate>{{ $post->date('F j, Y') }}</time></p>
  {!! $post->content !!}
</article>

@endsection
