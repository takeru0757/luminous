@extends('_content')

@section('meta')
@include('_components.meta-title', ['titles' => '404 Not Found'])
@endsection

@section('breadcrumb')
<li class="active">404 Not Found</li>
@endsection

@section('content')

@parent

<article class="m-b-md">
    <h1>404 Not Found</h1>
</article>

@endsection
