@extends('layouts.admin')

@section('title', __('categories.title'))

@section('main-content')
<div class="row">
    <div class="col-12">
        @include('admin.verification-review._flash')
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">{{ __('categories.title') }}</h4>
                <a href="{{ route('admin.trade.categories.create') }}" class="btn btn-primary">{{ __('categories.add') }}</a>
            </div>
            <div class="card-body">
                <p class="text-muted">{{ __('categories.intro') }}</p>
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('categories.label_en') }}</th>
                                <th>{{ __('categories.role') }}</th>
                                <th>{{ __('categories.activities') }}</th>
                                <th class="text-right">{{ __('categories.businesses') }}</th>
                                <th>{{ __('categories.status') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($categories as $c)
                                <tr>
                                    <td>
                                        <div class="font-weight-bold">{{ $c->label_en }}</div>
                                        <div class="text-small">{{ $c->label_am }}</div>
                                        <div class="text-small text-muted">{{ $c->code }}</div>
                                    </td>
                                    <td>{{ __('categories.roles.' . $c->business_role) }}</td>
                                    <td>
                                        @foreach($c->allowedCapabilities() as $cap)
                                            <span class="badge badge-light mb-1">{{ __('categories.capabilities.' . $cap) }}</span>
                                        @endforeach
                                    </td>
                                    <td class="text-right">{{ $c->organizations_count }}</td>
                                    <td>
                                        <div>{{ $c->is_active ? __('categories.on') : __('categories.off') }}</div>
                                        <div class="text-small text-muted">{{ $c->legally_confirmed ? __('categories.confirmed') : __('categories.not_confirmed') }}</div>
                                    </td>
                                    <td><a href="{{ route('admin.trade.categories.edit', $c->id) }}" class="btn btn-sm btn-outline-primary">{{ __('categories.edit') }}</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
