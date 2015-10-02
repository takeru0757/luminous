@if ($tree && ! $tree->isEmpty())

<ol class="breadcrumb">
  <li><a href="{{ url('/') }}">{{ trans('labels.home') }}</a></li>

  @foreach (($_nodes = $tree->all()) as $_i => $_node)

  @if ($_i === $_nodes->count() - 1)
  <li class="active">{{ $_node->label }}</li>
  @else
  <li><a href="{{ $_node->url }}">{{ $_node->label }}</a></li>
  @endif

  @endforeach

</ol>

@endif
