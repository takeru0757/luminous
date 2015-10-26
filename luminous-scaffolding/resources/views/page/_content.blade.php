@extends('_content')

@section('meta')
<meta name="description" content="{{ $query->post->excerpt }}">
@endsection

@section('content')

@parent

<div class="row">
    <div class="col-md-9">
        @yield('main')
    </div>
    <div class="col-md-3">
        <nav>
            <h1 class="h4">Pages</h1>
            <ul class="nav">
                <?php
                $formatter = function ($post) use (&$formatter) {
                    ?>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ post_url($post) }}">{{ $post->title }}</a>
                        @if ($children = $post->children->all())
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

                @foreach (app('wp')->posts('page')->root() as $post)
                {!! $formatter($post) !!}
                @endforeach
            </ul>
        </nav>
    </div>
</div>

@endsection
