@extends('post._content')

@section('meta')
<meta name="description" content="{{ $query->post->excerpt }}">
@endsection

@section('main')

<article class="m-b-lg">
    <h1 class="h2"><a href="{{ post_url($query->post) }}">{{ $query->post->title }}</a></h1>

    <ul class="m-b-md list-inline">
        <li class="text-muted"><time datetime="{{ $query->post->date->toW3cString() }}" pubdate>{{ $query->post->date(wp_option('date_format')) }}</time></li>
        @if (($terms = $query->post->terms('category')) && ! $terms->isEmpty())
        <li>
            <ul class="list-inline" style="display:inline-block">
                @foreach ($terms as $term)
                <li><a href="{{ posts_url($query->post->type, $term) }}"><span class="fa fa-folder"></span> {{ $term->name }}</a></li>
                @endforeach
            </ul>
        </li>
        @endif

        @if (($terms = $query->post->terms('post_tag')) && ! $terms->isEmpty())
        <li>
            <ul class="list-inline" style="display:inline-block">
                @foreach ($terms as $term)
                <li><a href="{{ posts_url($query->post->type, $term) }}"><span class="fa fa-tag"></span> {{ $term->name }}</a></li>
                @endforeach
            </ul>
        </li>
        @endif
    </ul>

    {!! $query->post->content !!}
</article>

<nav>
    @include('_components.pager', ['index' => $query->post->type, 'newer' => $query->post->newer, 'older' => $query->post->older])
</nav>

@endsection
