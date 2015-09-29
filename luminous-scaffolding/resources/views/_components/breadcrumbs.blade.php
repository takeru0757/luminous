@if(isset($tree))

<ol class="breadcrumb">
  <li><a href="{{ url('/') }}">Home</a></li>

  @foreach($tree->parents() as $n)
    <li><a href="{{ $n->url }}">{{ $n->label }}</a></li>
  @endforeach

  @if($a = $tree->active())
  <li class="active">{{ $a->label }}</li>
  @endif

</ol>

@endif
