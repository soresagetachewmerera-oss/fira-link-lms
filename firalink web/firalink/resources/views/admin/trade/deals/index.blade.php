@extends('layouts.admin')

@section('title', __('deals.title'))

@section('main-content')
<div class="row">
    <div class="col-12">
        @include('admin.verification-review._flash')

        <ul class="nav nav-tabs mb-3">
            @foreach(\App\Http\Controllers\Admin\DealAdminController::FILTERS as $key)
                <li class="nav-item">
                    <a class="nav-link {{ $filter === $key ? 'active' : '' }}" href="{{ route('admin.trade.deals.index', ['filter' => $key]) }}">{{ __('deals.filters.' . $key) }}</a>
                </li>
            @endforeach
        </ul>

        <div class="card">
            <div class="card-body p-0">
                @if($deals->isEmpty())
                    <p class="p-4 mb-0 text-muted">{{ __('deals.empty') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('deals.reference') }}</th>
                                    <th>{{ __('deals.seller') }}</th>
                                    <th>{{ __('deals.buyer') }}</th>
                                    <th class="text-right">{{ __('deals.value') }}</th>
                                    <th>{{ __('deals.current_step') }}</th>
                                    <th>{{ __('deals.status') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($deals as $deal)
                                    @php($step = \App\Services\Trade\DealRoomService::currentStep($deal))
                                    <tr>
                                        <td>
                                            <div class="font-weight-bold">{{ $deal->deal_reference }}</div>
                                            <div class="text-small text-muted">{{ $deal->created_at?->format('Y-m-d') }}</div>
                                        </td>
                                        <td>{{ $deal->organization?->name ?? '—' }}</td>
                                        <td>{{ $deal->buyerOrganization?->name ?? '—' }}</td>
                                        <td class="text-right">USD {{ number_format((float) $deal->target_value_usd, 2) }}</td>
                                        <td>
                                            @if($step)
                                                {{ \App\Services\Trade\DealRoomService::STEPS[$step->milestone_key]['title'] ?? $step->title }}
                                                <div class="text-small text-muted">{{ __('deals.step_statuses.' . $step->status) }}</div>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-light">{{ __('deals.statuses.' . $deal->status) }}</span>
                                            @if($deal->paused_at)
                                                <span class="badge badge-secondary">{{ __('trade.admin.deal_paused') }}</span>
                                            @endif
                                        </td>
                                        <td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.trade.deals.show', $deal->id) }}">{{ __('deals.open') }}</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="p-3">{{ $deals->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
