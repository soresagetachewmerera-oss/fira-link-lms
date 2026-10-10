@extends('layouts.admin')

@section('title', 'Edit Menu')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item">Frontend Manager</div>
        <div class="breadcrumb-item"><a href="{{ route('admin.frontend.menus.index') }}">Menus</a></div>
        <div class="breadcrumb-item">Edit</div>
    </div>
@endsection

@section('main-content')
    @include('admin.frontend.menus.partials.form', [
        'action' => route('admin.frontend.menus.update', $menu),
        'method' => 'PUT',
        'submitLabel' => 'Save Menu',
    ])

    <div class="row mt-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">{{ $currentItem ? 'Edit Menu Item' : 'Add Menu Item' }}</h4>
                    @if ($currentItem)
                        <a href="{{ route('admin.frontend.menus.edit', $menu) }}" class="btn btn-sm btn-light">Cancel
                            editing</a>
                    @endif
                </div>
                <div class="card-body">
                    <form method="POST"
                        action="{{ $currentItem ? route('admin.frontend.menus.items.update', [$menu, $currentItem]) : route('admin.frontend.menus.items.store', $menu) }}">
                        @csrf
                        @if ($currentItem)
                            @method('PUT')
                        @endif

                        <div class="form-group">
                            <label for="item-title">Title <span class="text-danger">*</span></label>
                            <input type="text" id="item-title" name="title"
                                value="{{ old('title', $currentItem->title ?? '') }}"
                                class="form-control @error('title') is-invalid @enderror" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="item-url">URL</label>
                            <input type="text" id="item-url" name="url"
                                value="{{ old('url', $currentItem->url ?? '') }}"
                                class="form-control @error('url') is-invalid @enderror"
                                placeholder="https://example.com/page">
                            @error('url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Provide either a URL or a route name. URL takes precedence
                                if both are provided.</small>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="item-route-name">Route Name</label>
                                <input type="text" id="item-route-name" name="route_name"
                                    value="{{ old('route_name', $currentItem->route_name ?? '') }}"
                                    class="form-control @error('route_name') is-invalid @enderror"
                                    placeholder="e.g. blog.show">
                                @error('route_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label for="item-target">Target</label>
                                <select id="item-target" name="target"
                                    class="form-control @error('target') is-invalid @enderror">
                                    @php
                                        $selectedTarget = old('target', $currentItem->target ?? '_self');
                                    @endphp
                                    <option value="_self" {{ $selectedTarget === '_self' ? 'selected' : '' }}>Same tab
                                    </option>
                                    <option value="_blank" {{ $selectedTarget === '_blank' ? 'selected' : '' }}>New tab
                                    </option>
                                    <option value="_parent" {{ $selectedTarget === '_parent' ? 'selected' : '' }}>Parent
                                        frame</option>
                                    <option value="_top" {{ $selectedTarget === '_top' ? 'selected' : '' }}>Top frame
                                    </option>
                                </select>
                                @error('target')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="item-route-params">Route Parameters (JSON)</label>
                            <textarea id="item-route-params" name="route_parameters_json" rows="2"
                                class="form-control @error('route_parameters_json') is-invalid @enderror" placeholder='{"slug": "my-post"}'>{{ old('route_parameters_json', isset($currentItem) && $currentItem->route_parameters ? json_encode($currentItem->route_parameters) : '') }}</textarea>
                            @error('route_parameters_json')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Optional. Provide valid JSON object.</small>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="parent-id">Parent Item</label>
                                <select id="parent-id" name="parent_id"
                                    class="form-control @error('parent_id') is-invalid @enderror">
                                    <option value="">Top level</option>
                                    @foreach ($flatItems as $item)
                                        @continue(isset($currentItem) && $currentItem->id === $item->id)
                                        <option value="{{ $item->id }}"
                                            {{ old('parent_id', $currentItem->parent_id ?? null) == $item->id ? 'selected' : '' }}>
                                            {{ str_repeat('— ', $item->parent_id ? 1 : 0) }}{{ $item->title }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('parent_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label for="item-order">Display Order</label>
                                <input type="number" id="item-order" name="order"
                                    class="form-control @error('order') is-invalid @enderror"
                                    value="{{ old('order', $currentItem->order ?? null) }}" min="0"
                                    placeholder="Autofill">
                                @error('order')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="custom-control custom-switch mb-4">
                            <input type="checkbox" class="custom-control-input" id="item-active" name="is_active"
                                value="1" {{ old('is_active', $currentItem->is_active ?? true) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="item-active">Item is active</label>
                        </div>

                        <button type="submit"
                            class="btn btn-primary">{{ $currentItem ? 'Update Item' : 'Add Item' }}</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">Menu Structure</h4>
                </div>
                <div class="card-body">
                    @if ($menuItems->isEmpty())
                        <p class="text-muted mb-0">No menu items yet. Use the form to add your first item.</p>
                    @else
                        @include('admin.frontend.menus.partials.items-tree', [
                            'items' => $menuItems,
                            'menu' => $menu,
                        ])
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Reorder Items</h4>
                </div>
                <div class="card-body">
                    @if ($flatItems->isEmpty())
                        <p class="text-muted mb-0">Add menu items to configure ordering.</p>
                    @else
                        <form method="POST" action="{{ route('admin.frontend.menus.items.reorder', $menu) }}">
                            @csrf
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th style="width: 40%">Item</th>
                                            <th>Parent</th>
                                            <th style="width: 120px">Order</th>
                                            <th class="text-center" style="width: 80px">Active</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($flatItems->sortBy('order') as $item)
                                            <tr>
                                                <td>
                                                    <a
                                                        href="{{ route('admin.frontend.menus.edit', [$menu, 'item' => $item->id]) }}">
                                                        {{ $item->title }}
                                                    </a>
                                                    <div class="text-muted small">ID: {{ $item->id }}</div>
                                                </td>
                                                <td>{{ $item->parent?->title ?? '—' }}</td>
                                                <td>
                                                    <input type="number" name="orders[{{ $item->id }}]"
                                                        value="{{ old('orders.' . $item->id, $item->order) }}"
                                                        class="form-control form-control-sm">
                                                </td>
                                                <td class="text-center">
                                                    <span
                                                        class="badge {{ $item->is_active ? 'badge-success' : 'badge-secondary' }}">
                                                        {{ $item->is_active ? 'Yes' : 'No' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-right">
                                <button type="submit" class="btn btn-primary btn-sm">Update Order</button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
