@php
    $items = $items ?? [];
    $isRoot = $isRoot ?? false;
@endphp

<?php foreach ($items as $index => $item): ?>
    @php
        $children = $item['children'] ?? [];
        $hasChildren = !empty($children);
        $url = $item['url'] ?? '#';
        $target = $item['target'] ?? '_self';
        $iconClass = $isRoot ? 'fa-solid fa-chevron-down' : 'fas fa-angle-right';
    @endphp
    <li class="{{ $hasChildren ? 'has-dropdown' : '' }}" data-nav-index="{{ $loopIndex ?? 0 }}-{{ $index }}">
        <a href="{{ $url }}" target="{{ $target }}" class="border-none">
            {{ $item['title'] ?? 'Menu Item' }}
            @if($hasChildren)
                <i class="{{ $iconClass }}"></i>
            @endif
        </a>
        @if($hasChildren)
            <ul class="submenu">
                @include('partials.menu-items', ['items' => $children, 'isRoot' => false, 'loopIndex' => ($loopIndex ?? 0) . '-' . $index])
            </ul>
        @endif
    </li>
<?php endforeach; ?>
