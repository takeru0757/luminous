@extends('post._content')

@section('main')

@forelse ($query->posts as $post)

<article class="m-b-lg">
    <h1 class="h4"><a href="{{ post_url($post) }}">{{ $post->title }}</a></h1>

    <ul class="m-b-md list-inline">
        <li class="text-muted"><time datetime="{{ $post->date->toW3cString() }}" pubdate>{{ $post->date(wp_option('date_format')) }}</time></li>
        @if ($terms = $post->terms('category')->all())
        <li>
            <ul class="list-inline" style="display:inline-block">
                @foreach ($terms as $term)
                <li><a href="{{ posts_url($post->type, $term) }}"><span class="fa fa-folder"></span> {{ $term->name }}</a></li>
                @endforeach
            </ul>
        </li>
        @endif

        @if ($terms = $post->terms('post_tag')->all())
        <li>
            <ul class="list-inline" style="display:inline-block">
                @foreach ($terms as $term)
                <li><a href="{{ posts_url($post->type, $term) }}"><span class="fa fa-tag"></span> {{ $term->name }}</a></li>
                @endforeach
            </ul>
        </li>
        @endif
    </ul>

    <p>{{ $post->excerpt }}</p>

</article>

@empty

<p>No posts.</p>

@endforelse

<nav class="text-center">
    @include('_components.pagination', ['paginator' => $query->posts])
</nav>

@endsection
