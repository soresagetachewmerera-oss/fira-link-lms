@if($courses->hasPages())
<div class="page-nav-wrap wow fadeInUp" data-wow-delay=".3s">
    <ul>
        @if($courses->onFirstPage())
            <li><span class="page-numbers disabled"><i class="fa-solid fa-chevron-left"></i></span></li>
        @else
            <li><a class="page-numbers" href="{{ $courses->previousPageUrl() }}"><i class="fa-solid fa-chevron-left"></i></a></li>
        @endif

        @foreach($courses->getUrlRange(1, $courses->lastPage()) as $page => $url)
            @if($page == $courses->currentPage())
                <li class="active"><span class="page-numbers">{{ $page }}</span></li>
            @else
                <li><a class="page-numbers" href="{{ $url }}">{{ $page }}</a></li>
            @endif
        @endforeach

        @if($courses->hasMorePages())
            <li><a class="page-numbers" href="{{ $courses->nextPageUrl() }}"><i class="fa-solid fa-chevron-right"></i></a></li>
        @else
            <li><span class="page-numbers disabled"><i class="fa-solid fa-chevron-right"></i></span></li>
        @endif
    </ul>
</div>
@endif

