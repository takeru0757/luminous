@extends('_content')

@section('meta')
@include('_components.meta-title', ['titles' => '500 Internal Server Error'])
@endsection

@section('breadcrumb')
<li class="active">500 Internal Server Error</li>
@endsection

@section('content')

@parent

<article class="m-b-md">
    <h1>500 Internal Server Error</h1>
</article>

@endsection
