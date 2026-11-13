<div class="breadcrumb-wrapper bg-cover"
    style="background-image: url('{{ asset('assets/front/img/inner/breadcrumb.jpg') }}');">
    <div class="container">
        <div class="page-heading">
            <div class="breadcrumb-sub-title">
                <h1 class="wow fadeInUp" data-wow-delay=".3s">{{ $page_title ?? 'Page Title' }}</h1>
            </div>
            <ul class="breadcrumb-items wow fadeInUp" data-wow-delay=".5s">
                <li>
                    <a href="{{ $base_url ?? '/' }}">
                        Home
                    </a>
                </li>
                <li>
                    <i class="fa-solid fa-chevron-right"></i>
                </li>
                <li>
                    {{ $page_title ?? 'Page Title' }}
                </li>
            </ul>
        </div>
    </div>
</div>
