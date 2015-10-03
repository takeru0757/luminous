<ul class="pager">
    @if (isset($index))
    <li><a href="{{ archive_url($index) }}" title="{{ $index->label }}">Index</a></li>
    @endif

    @if (isset($newer))
    <li class="pager-prev"><a href="{{ post_url($newer) }}" title="{{ $newer->title }}">Newer</a></li>
    @endif

    @if (isset($older))
    <li class="pager-next"><a href="{{ post_url($older) }}" title="{{ $older->title }}">Older</a></li>
    @endif
</ul>
