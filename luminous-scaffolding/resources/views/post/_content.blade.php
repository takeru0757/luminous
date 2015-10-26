@extends('_content')

@section('content')

@parent

<div class="row">
    <div class="col-md-9">
        @yield('main')
    </div>
    <div class="col-md-3">
        <nav>
            <h1 class="h4">Recent Posts</h1>
            <ul class="nav">
                @foreach (app('wp')->posts('post')->orderBy('created_at', 'desc')->take(5) as $post)
                <li class="nav-item"><a class="nav-link" href="{{ post_url($post) }}">{{ $post->title }}</a></li>
                @endforeach
            </ul>
        </nav>
        <nav>
            <h1 class="h4">Categories</h1>
            <ul class="nav">
                <?php
                $formatter = function ($term) use (&$formatter) {
                    ?>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ posts_url('post', $term) }}"><span class="fa fa-folder"></span> {{ $term->name }} ({{ $term->count }})</a>
                        @if ($children = $term->children->all())
                        <ul class="nav p-l">
                            @foreach ($children as $child)
                            {!! $formatter($child) !!}
                            @endforeach
                        </ul>
                        @endif
                    </li>
                    <?php
                };
                ?>

                @foreach (app('wp')->terms('category')->root() as $term)
                {!! $formatter($term) !!}
                @endforeach
            </ul>
        </nav>
        <nav>
            <h1 class="h4">Tags</h1>
            <ul class="nav">
                @foreach (app('wp')->terms('post_tag') as $term)
                <li class="nav-item"><a class="nav-link" href="{{ posts_url('post', $term) }}"><span class="fa fa-tag"></span> {{ $term->name }} ({{ $term->count }})</a></li>
                @endforeach
            </ul>
        </nav>
        <nav>
            <h1 class="h4">Archives</h1>
            <select class="c-select" style="width:100%" onchange="if (this.value) location.href=this.value;">
                <option value="">Select an Archive</option>
                @foreach (app('wp')->posts('post')->archives('monthly') as $date)
                <option value="{{ posts_url('post', $date) }}">{{ $date->format(trans("date.{$date->type}"))." ({$date->count})" }}</option>
                @endforeach
            </select>
        </nav>
    </div>
</div>

@endsection
