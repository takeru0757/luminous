@extends('layout')

@section('content')

<div class="row">
    <div class="col-md-9">
        @yield('main')
    </div>
    <div class="col-md-3">
        <nav>
            <h1 class="h4">Archives</h1>
            <select class="c-select" style="width:100%" onchange="if (this.value) location.href=this.value;">
                <option value="">Select an Archive</option>
                @foreach ($wp->posts($tree->postType)->archives('monthly') as $_archive)
                <option value="{{ posts_url($tree->postType, ['archive' => $_archive]) }}">{{ $_archive->format(trans("labels.archive.{$_archive->type}"))." ({$_archive->count})" }}</option>
                @endforeach
            </select>
        </nav>
    </div>
</div>

@endsection
