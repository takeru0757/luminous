<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="x-ua-compatible" content="ie=edge">

    @if (isset($query))

    @include('_components.meta-title', ['titles' => $query->tree()->pluck('label')->reverse(), 'page' => $query->page])
    <link rel="canonical" href="{{ $query->canonicalUrl() }}">

    @if ($prev = $query->prev())
    <link rel="prev" href="{{ $prev }}">
    @endif

    @if ($next = $query->next())
    <link rel="next" href="{{ $next }}">
    @endif

    @endif

    @yield('meta')

    <link rel="stylesheet" href="//fonts.googleapis.com/css?family=Lato:100,400,700">
    <link rel="stylesheet" href="{{ asset('css/bundle.css') }}">

    <!--[if lt IE 9]>
    <script src="{{ asset('vendor/html5shiv/html5shiv.min.js') }}"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <![endif]-->
    <!--[if gte IE 9]><!-->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
    <script>window.jQuery || document.write('<script src="{{ asset('vendor/jquery/jquery.min.js') }}"><\/script>')</script>
    <!--<![endif]-->

    <script src="https://cdn.rawgit.com/twbs/bootstrap/v4-dev/dist/js/bootstrap.min.js"></script>
    <script>window.jQuery.fn.alert || document.write('<script src="{{ asset('vendor/bootstrap/bootstrap.min.js') }}"><\/script>')</script>

    <script src="{{ asset('js/bundle.js') }}"></script>
</head>
<body>
    <nav class="navbar navbar-light">
        <div class="container">
            <a class="navbar-brand" href="{{ url('/') }}">{{ wp_option('name') }}</a>
            <p class="header-description pull-right text-muted">{{ wp_option('description') }}</p>
        </div>
    </nav>

    <div class="site-header jumbotron">
        <div class="container">
            <h1>Luminous</h1>
            <p class="lead">Luminous is a <a href="https://wordpress.org/">WordPress</a> theme framework with <a href="http://laravel.com/">Laravel</a> components.<br>This framework will help you to develop WordPress themes like modern web applications using modern PHP.</p>
        </div>
    </div>

    <div class="container">

        @yield('content')

    </div>

    <nav class="footer navbar navbar-light bg-faded">
        <div class="container">
            <address class="footer-address text-muted">{{ $app->version() }}</address>
        </div>
    </nav>
</body>
</html>
