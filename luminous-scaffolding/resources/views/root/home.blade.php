@extends('layout')

@section('content')

<div class="row">
    <div class="col-sm-6">
        <div class="m-b clearfix">
            <h2 class="h4 m-b-0"><span class="fa fa-file-text-o"></span> Pages</h2>
        </div>
        <div class="list-group m-b-md">
            @foreach (app('wp')->posts('page')->orderBy('order')->take(5) as $_post)
            <a href="{{ post_url($_post) }}" class="list-group-item">
                <p class="h5 list-group-item-heading">{{ $_post->title }}</p>
                <p class="list-group-item-text">{{ $_post->excerpt }}</p>
                <p class="list-group-item-text"><small class="text-muted">{{ $_post->date(wp_option('date_format')) }}</small></p>
            </a>
            @endforeach
        </div>
    </div>
    <div class="col-sm-6">
        <div class="m-b clearfix">
            <h2 class="h4 m-b-0 pull-left"><span class="fa fa-volume-up"></span> Posts</h2>
            <p class="pull-right" style="margin:0;padding:0.0625rem 0"><a href="{{ posts_url('post') }}"><span class="fa fa-clock-o"></span> Archives</a></p>
        </div>
        <div class="list-group m-b-md">
            @foreach (app('wp')->posts('post')->orderBy('created_at', 'desc')->take(5) as $_post)
            <a href="{{ post_url($_post) }}" class="list-group-item">
                <p class="h5 list-group-item-heading">{{ $_post->title }}</p>
                <p class="list-group-item-text">{{ $_post->excerpt }}</p>
                <p class="list-group-item-text"><small class="text-muted">{{ $_post->date(wp_option('date_format')) }}</small></p>
            </a>
            @endforeach
        </div>
    </div>
</div>

@endsection
