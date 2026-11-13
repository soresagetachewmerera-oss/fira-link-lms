@php use Illuminate\Support\Str; @endphp
<ul class="list-group list-group-flush">
    @foreach ($items as $item)
        <li class="list-group-item">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <strong>{{ $item['title'] }}</strong>
                    <div class="text-muted small">
                        @if ($item['url'])
                            <span class="mr-2"><i class="fas fa-link"></i> {{ Str::limit($item['url'], 40) }}</span>
                        @elseif($item['route_name'])
                            <span class="mr-2"><i class="fas fa-route"></i> {{ $item['route_name'] }}</span>
                        @else
                            <span class="mr-2 text-warning"><i class="fas fa-exclamation-triangle"></i> No
                                destination</span>
                        @endif
                        <span>Order: {{ $item['order'] }}</span>
                    </div>
                </div>
                <div class="btn-group btn-group-sm">
                    <a href="{{ route('admin.frontend.menus.edit', [$menu, 'item' => $item['id']]) }}"
                        class="btn btn-outline-primary">Edit</a>
                    <form action="{{ route('admin.frontend.menus.items.destroy', [$menu, $item['id']]) }}"
                        method="POST" onsubmit="return confirm('Delete this menu item and all children?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger">Delete</button>
                    </form>
                </div>
            </div>
            @if (!empty($item['children']))
                <div class="mt-3 ml-3">
                    @include('admin.frontend.menus.partials.items-tree', [
                        'items' => $item['children'],
                        'menu' => $menu,
                    ])
                </div>
            @endif
        </li>
    @endforeach
</ul>
