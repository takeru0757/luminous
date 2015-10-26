@extends('_layout')

@section('content')

<ol class="breadcrumb">
    <li><a href="{{ url('/') }}">Home</a></li>

    @if (isset($query))

    @foreach ($tree = $query->tree() as $i => $node)
    @if ($query->page === 1 && $i === $tree->count() - 1)
    <li class="active">{{ $node->label() }}</li>
    @else
    <li><a href="{{ $node->url() }}">{{ $node->label() }}</a></li>
    @endif
    @endforeach

    @if ($query->page > 1)
    <li class="active">Page: {{ $query->page }}</li>
    @endif

    @else

    @yield('breadcrumb')

    @endif
</ol>

@endsection
