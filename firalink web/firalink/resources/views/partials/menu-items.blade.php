<?php
    $items = $items ?? [];
    $isRoot = $isRoot ?? false;
?>

<?php foreach ($items as $index => $item): ?>
    <?php
        $children = $item['children'] ?? [];
        $hasChildren = !empty($children);
        $url = $item['url'] ?? '#';
        $target = $item['target'] ?? '_self';
        $iconClass = $isRoot ? 'fa-solid fa-chevron-down' : 'fas fa-angle-right';
        $title = $item['title'] ?? '';
        $isHomeActive = $title === 'Home' && request()->routeIs('home');
        $isCoursesActive = ($title === 'Courses' || str_contains($url, 'courses')) && request()->routeIs('courses*');
        $isEventsActive = ($title === 'Events' || str_contains($url, 'events')) && request()->routeIs('events*');
        $isToursActive = ($title === 'Tours' || str_contains($url, 'tours')) && request()->routeIs('tours*');
        $isFreeToolsActive = ($title === 'Free Tools' || str_contains($url, 'free-tools')) && request()->routeIs('free-tools*');
        $isItemActive = $isHomeActive || $isCoursesActive || $isEventsActive || $isToursActive || $isFreeToolsActive;
    ?>
    <li class="{{ $hasChildren ? 'has-dropdown' : '' }} {{ $isItemActive ? 'active' : '' }}" data-nav-index="{{ $loopIndex ?? 0 }}-{{ $index }}">
        <a href="{{ $url }}" target="{{ $target }}" class="border-none {{ $isItemActive ? 'fira-nav-active' : '' }}">
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
