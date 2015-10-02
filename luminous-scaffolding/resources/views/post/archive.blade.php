@extends('post.content')

@section('main')

@forelse ($posts as $p)
<article class="m-b-lg">
  <h1 class="h4"><a href="{{ post_url($p) }}">{{ $p->title }}</a></h1>
  <p class="text-muted"><time datetime="{{ $p->date->toW3cString() }}" pubdate>{{ $p->date($site->dateFormat) }}</time></p>
  <p>{{ $p->excerpt }}</p>
</article>
@empty
<p>No posts.</p>
@endforelse

<nav class="text-center">
  @include('_components.pagination', ['paginator' => $posts])
</nav>

@endsection
