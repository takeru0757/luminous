@extends('layout')

@section('content')

<div class="row">
    <div class="col-md-9">
        @yield('main')
    </div>
    <div class="col-md-3">
        <nav>
            <h1 class="h4">Recent Posts</h1>
            <ul class="nav">
                @foreach ($wp->posts('post')->orderBy('created_at', 'desc')->take(5)->get() as $_post)
                <li class="nav-item"><a class="nav-link" href="{{ post_url($_post) }}">{{ $_post->title }}</a></li>
                @endforeach
            </ul>
        </nav>
        <nav>
            <h1 class="h4">Categories</h1>
            <ul class="nav">
                <?php
                $_formatter = function ($term) use (&$_formatter) {
                    ?>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ posts_url('post', ['term' => $term]) }}"><span class="fa fa-folder"></span> {{ $term->name }} ({{ $term->count }})</a>
                        @if (($children = $term->children->get()) && !$children->isEmpty())
                        <ul class="nav p-l">
                            @foreach ($children as $child)
                            {!! $_formatter($child) !!}
                            @endforeach
                        </ul>
                        @endif
                    </li>
                    <?php
                };
                ?>

                @foreach ($wp->terms('category')->whereTerm('parent_id', 0)->get() as $_term)
                {!! $_formatter($_term) !!}
                @endforeach
            </ul>
        </nav>
        <nav>
            <h1 class="h4">Tags</h1>
            <ul class="nav">
                @foreach ($wp->terms('post_tag')->get() as $_term)
                <li class="nav-item"><a class="nav-link" href="{{ posts_url('post', ['term' => $_term]) }}"><span class="fa fa-tag"></span> {{ $_term->name }} ({{ $_term->count }})</a></li>
                @endforeach
            </ul>
        </nav>
        <nav>
            <h1 class="h4">Archives</h1>
            <select class="c-select" style="width:100%" onchange="if (this.value) location.href=this.value;">
                <option value="">Select an Archive</option>
                @foreach ($wp->posts('post')->archives('monthly') as $_archive)
                <option value="{{ posts_url('post', ['archive' => $_archive]) }}">{{ $_archive->format(trans("labels.archive.{$_archive->type}"))." ({$_archive->count})" }}</option>
                @endforeach
            </select>
        </nav>
    </div>
</div>

@endsection
