@extends('page.content')

@section('main')

<article class="m-b-lg">
  <h1 class="h2"><a href="{{ route('page', $post->parameters('path')) }}">{{ $post->title }}</a></h1>
  <p class="m-b-md text-muted"><time datetime="{{ $post->date() }}" pubdate>{{ $post->date('F j, Y') }}</time></p>
  {!! $post->content !!}
</article>

@endsection
