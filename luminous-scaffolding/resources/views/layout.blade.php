<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="x-ua-compatible" content="ie=edge">

    <title>@yield('title', e($site->name))</title>

    @include('script-html5shiv')
    <link rel="stylesheet" href="//fonts.googleapis.com/css?family=Lato:100,400,700">
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">

    @include('script-jquery')

    <script src="https://cdn.rawgit.com/twbs/bootstrap/v4-dev/dist/js/bootstrap.min.js"></script>
    <script>window.jQuery.fn.alert || document.write('<script src="{{ asset('vendor/bootstrap/bootstrap.min.js') }}"><\/script>')</script>

    <script src="{{ asset('js/main.js') }}"></script>
  </head>
  <body>
    <nav class="navbar navbar-light">
      <div class="container">
        <a class="navbar-brand" href="{{ route('home') }}">{{ $site->name }}</a>
        <p class="header-description pull-right text-muted">{{ $site->description }}</p>
      </div>
    </nav>

    <div class="site-header jumbotron">
      <div class="container">
        <h1>Luminous</h1>
        <p class="lead">Luminous is a <a href="https://wordpress.org/">WordPress</a> theme framework based on <a href="http://lumen.laravel.com/">Laravel Lumen</a>.
          <br>This framework will help you to develop WordPress themes like modern web applications using modern PHP.</p>
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
