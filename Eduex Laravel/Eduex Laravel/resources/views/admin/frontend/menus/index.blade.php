@extends('layouts.admin')

@section('title', 'Menus')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item">Frontend Manager</div>
        <div class="breadcrumb-item">Menus</div>
    </div>
@endsection

@section('main-content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Menus</h4>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Location</th>
                            <th>Description</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Items</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($menus as $menu)
                            <tr>
                                <td>
                                    <strong>{{ $menu->name }}</strong>
                                    <div class="text-muted small">Slug: {{ $menu->slug }}</div>
                                </td>
                                <td>
                                    <span class="badge badge-light">{{ $menu->location }}</span>
                                </td>
                                <td>{{ $menu->description ?? '—' }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $menu->is_active ? 'badge-success' : 'badge-secondary' }}">
                                        {{ $menu->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-center">{{ $menu->all_items_count }}</td>
                                <td class="text-right">
                                    <a href="{{ route('admin.frontend.menus.edit', $menu) }}"
                                        class="btn btn-sm btn-outline-primary">Manage</a>
                                    <form action="{{ route('admin.frontend.menus.destroy', $menu) }}" method="POST"
                                        class="d-inline"
                                        onsubmit="return confirm('Delete this menu? This will remove all associated menu items.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No menus created yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <div class="text-muted small">
                Showing {{ $menus->firstItem() ?? 0 }} to {{ $menus->lastItem() ?? 0 }} of {{ $menus->total() }} menus
            </div>
            {{ $menus->links('vendor.pagination.stisla-default') }}
        </div>
    </div>
@endsection
