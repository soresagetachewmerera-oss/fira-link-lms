@php
    $bannerImage = $bg_image ?? asset('assets/front/img/inner/breadcrumb.jpg');
@endphp
<div class="breadcrumb-wrapper bg-cover"
    style="background-image: url('{{ $bannerImage }}');">
    <div class="container">
        <div class="page-heading">
            <div class="breadcrumb-sub-title">
                <h1>{{ $page_title ?? 'Page Title' }}</h1>
            </div>
            @if(!empty($page_subtitle))
                <p class="breadcrumb-lead-text">{{ $page_subtitle }}</p>
            @endif
            <ul class="breadcrumb-items">
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
