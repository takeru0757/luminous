@extends('post.content')

@section('main')

@forelse ($posts as $_post)
<article class="m-b-lg">
    <h1 class="h4"><a href="{{ post_url($_post) }}">{{ $_post->title }}</a></h1>

    <ul class="m-b-md list-inline">
        <li class="text-muted"><time datetime="{{ $_post->date->toW3cString() }}" pubdate>{{ $_post->date($site->dateFormat) }}</time></li>
        @if (($_terms = $_post->terms('category')) && ! $_terms->isEmpty())
        <li>
            <ul class="list-inline" style="display:inline-block">
                @foreach ($_terms as $_term)
                <li><a href="{{ posts_url($_post->type, $_term) }}"><span class="fa fa-folder"></span> {{ $_term->name }}</a></li>
                @endforeach
            </ul>
        </li>
        @endif

        @if (($_terms = $_post->terms('post_tag')) && ! $_terms->isEmpty())
        <li>
            <ul class="list-inline" style="display:inline-block">
                @foreach ($_terms as $_term)
                <li><a href="{{ posts_url($_post->type, $_term) }}"><span class="fa fa-tag"></span> {{ $_term->name }}</a></li>
                @endforeach
            </ul>
        </li>
        @endif
    </ul>

    <p>{{ $_post->excerpt }}</p>
</article>
@empty
<p>No posts.</p>
@endforelse

<nav class="text-center">
    @include('_components.pagination', ['paginator' => $posts])
</nav>

@endsection
