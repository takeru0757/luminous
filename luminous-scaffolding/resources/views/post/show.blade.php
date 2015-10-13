@extends('post.content')

@section('main')

<article class="m-b-lg">
    <h1 class="h2"><a href="{{ post_url($post) }}">{{ $post->title }}</a></h1>

    <ul class="m-b-md list-inline">
        <li class="text-muted"><time datetime="{{ $post->date->toW3cString() }}" pubdate>{{ $post->date($site->dateFormat) }}</time></li>
        @if (($_terms = $post->terms('category')) && ! $_terms->isEmpty())
        <li>
            <ul class="list-inline" style="display:inline-block">
                @foreach ($_terms as $_term)
                <li><a href="{{ posts_url($post->type, $_term) }}"><span class="fa fa-folder"></span> {{ $_term->name }}</a></li>
                @endforeach
            </ul>
        </li>
        @endif

        @if (($_terms = $post->terms('post_tag')) && ! $_terms->isEmpty())
        <li>
            <ul class="list-inline" style="display:inline-block">
                @foreach ($_terms as $_term)
                <li><a href="{{ posts_url($post->type, $_term) }}"><span class="fa fa-tag"></span> {{ $_term->name }}</a></li>
                @endforeach
            </ul>
        </li>
        @endif
    </ul>

    {!! $post->content !!}
</article>

<nav>
    @include('_components.pager', ['index' => $post->type, 'newer' => $post->newer, 'older' => $post->older])
</nav>

@endsection
