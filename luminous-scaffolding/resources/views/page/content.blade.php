@extends('layout')

@section('content')

<div class="row">
    <div class="col-md-9">
        @yield('main')
    </div>
    <div class="col-md-3">
        <nav>
            <h1 class="h4">Pages</h1>
            <ul class="nav">
                <?php
                $_formatter = function ($post) use (&$_formatter) {
                    ?>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ post_url($post) }}">{{ $post->title }}</a>
                        @if ($children = $post->children->getOrNull())
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

                @foreach ($wp->posts('page')->root() as $_post)
                {!! $_formatter($_post) !!}
                @endforeach
            </ul>
        </nav>
    </div>
</div>

@endsection
