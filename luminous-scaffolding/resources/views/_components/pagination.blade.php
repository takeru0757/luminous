@if ($paginator->hasPages())

<ul class="pagination">

  @if ($_prev = $paginator->previousPageUrl())
  <li><a href="{{ $_prev }}" aria-label="Previous"><span aria-hidden="true">&laquo;</span></a></li>
  @else
  <li class="disabled"><a href="#" aria-label="Previous"><span aria-hidden="true">&laquo;</span></a></li>
  @endif

  <?php list($_f, $_c, $_l) = [1, $paginator->currentPage(), $paginator->lastPage()]; ?>

  @foreach (array_unique([$_f, $_f + 1, max($_f, $_c - 1), $_c, min($_l, $_c + 1), $_l - 1, $_l]) as $_page)

  @if ($_page === $_c)
  <li class="active"><a href="{{ $paginator->url($_page) }}">{{ $_page }}</a></li>
  @elseif (! in_array($_page, [$_f + 1, $_l - 1]) || abs($_c - $_page) < 3)
  <li><a href="{{ $paginator->url($_page) }}">{{ $_page }}</a></li>
  @else
  <li class="disabled"><a href="#">...</a></li>
  @endif

  @endforeach

  @if ($_next = $paginator->nextPageUrl())
  <li><a href="{{ $_next }}" aria-label="Next"><span aria-hidden="true">&raquo;</span></a></li>
  @else
  <li class="disabled"><a href="#" aria-label="Next"><span aria-hidden="true">&raquo;</span></a></li>
  @endif

</ul>

@endif
