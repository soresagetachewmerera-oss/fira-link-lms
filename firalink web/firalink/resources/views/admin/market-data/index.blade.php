@extends('layouts.admin')

@section('title', __('market.admin.title'))

@section('main-content')
<div class="row">
    <div class="col-12">
        @include('admin.verification-review._flash')

        <div class="card mb-4">
            <div class="card-header"><h4 class="mb-0">{{ __('market.admin.sources') }}</h4></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('market.admin.source') }}</th>
                                <th>{{ __('market.admin.series') }}</th>
                                <th>{{ __('market.admin.last_success') }}</th>
                                <th>{{ __('market.admin.last_error') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sources as $source)
                                <tr>
                                    <td>
                                        <strong>{{ $source->name }}</strong>
                                        <div>
                                            @if($source->enabled)
                                                <span class="badge badge-success"><i class="fas fa-check" aria-hidden="true"></i> {{ __('market.admin.enabled') }}</span>
                                            @else
                                                <span class="badge badge-secondary"><i class="fas fa-pause" aria-hidden="true"></i> {{ __('market.admin.disabled') }}</span>
                                            @endif
                                        </div>
                                        @if($source->terms_note)
                                            <div class="text-muted small">{{ __('market.admin.terms') }}: {{ $source->terms_note }}</div>
                                        @endif
                                    </td>
                                    <td class="small">{{ $source->series->pluck('symbol')->implode(', ') }}</td>
                                    <td class="small">{{ $source->last_success_at?->toDayDateTimeString() ?? __('market.admin.never') }}</td>
                                    <td class="small text-danger">{{ $source->last_error }}</td>
                                    <td class="text-nowrap">
                                        <form method="POST" action="{{ route('admin.market-data.toggle', $source) }}" class="d-inline" onsubmit="return confirm(@js(__('market.admin.confirm_toggle')))">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-primary">{{ $source->enabled ? __('market.admin.turn_off') : __('market.admin.turn_on') }}</button>
                                        </form>
                                        @if($source->enabled)
                                            <form method="POST" action="{{ route('admin.market-data.fetch', $source) }}" class="d-inline">
                                                @csrf
                                                <button class="btn btn-sm btn-primary">{{ __('market.admin.fetch_now') }}</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if($manualSeries->isNotEmpty())
            <div class="card mb-4">
                <div class="card-header"><h4 class="mb-0">{{ __('market.admin.enter_title') }}</h4></div>
                <div class="card-body">
                    <p class="text-muted small">{{ __('market.admin.enter_hint') }}</p>
                    <form method="POST" action="{{ route('admin.market-data.enter') }}" class="row g-3">
                        @csrf
                        <div class="col-md-3">
                            <label class="form-label" for="enter-series">{{ __('market.admin.enter_series') }}</label>
                            <select id="enter-series" name="series_id" class="form-select" required>
                                @foreach($manualSeries as $series)
                                    <option value="{{ $series->id }}" @selected(old('series_id') == $series->id)>{{ $series->name }} ({{ $series->currency }}/{{ $series->unit }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="enter-value">{{ __('market.admin.enter_value') }}</label>
                            <input id="enter-value" name="value" class="form-control" inputmode="decimal" required value="{{ old('value') }}">
                            @error('value')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="enter-date">{{ __('market.admin.enter_date') }}</label>
                            <input id="enter-date" name="date" type="date" class="form-control" required max="{{ now()->toDateString() }}" value="{{ old('date', now()->toDateString()) }}">
                            @error('date')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="enter-url">{{ __('market.admin.enter_url') }}</label>
                            <input id="enter-url" name="source_url" type="url" class="form-control" required placeholder="https://" value="{{ old('source_url') }}">
                            @error('source_url')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary">{{ __('market.admin.enter_submit') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-header"><h4 class="mb-0">{{ __('market.admin.review_title') }}</h4></div>
            <div class="card-body p-0">
                @if($reviews->isEmpty())
                    <p class="p-4 mb-0 text-muted">{{ __('market.admin.review_empty') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('market.admin.series') }}</th>
                                    <th>{{ __('market.admin.value') }}</th>
                                    <th>{{ __('market.admin.source_time') }}</th>
                                    <th>{{ __('market.admin.why_held') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reviews as $price)
                                    <tr>
                                        <td>{{ $price->series->name }}<div class="text-muted small">{{ $price->series->source->name }}</div></td>
                                        <td>{{ $price->value }} {{ $price->currency }}/{{ $price->unit }}</td>
                                        <td class="small">{{ $price->source_time->toDayDateTimeString() }} UTC</td>
                                        <td class="small">
                                            {{ $price->review_reason }}
                                            @if(!empty($price->raw_ref['entered_by']))
                                                <div class="text-muted">{{ __('market.admin.entered_by', ['name' => $enteredBy[$price->raw_ref['entered_by']] ?? '#' . $price->raw_ref['entered_by']]) }}</div>
                                            @endif
                                            @if(!empty($price->raw_ref['source_url']) && str_starts_with($price->raw_ref['source_url'], 'https://'))
                                                <a href="{{ $price->raw_ref['source_url'] }}" target="_blank" rel="noopener noreferrer">{{ __('market.admin.source_link') }}</a>
                                            @endif
                                        </td>
                                        <td style="min-width: 260px">
                                            <form method="POST" class="mb-2">
                                                @csrf
                                                <label class="small mb-1" for="reason-{{ $price->id }}">{{ __('market.admin.reason') }}</label>
                                                <input id="reason-{{ $price->id }}" name="reason" class="form-control form-control-sm mb-2" required minlength="5" maxlength="1000">
                                                <button class="btn btn-sm btn-success" formaction="{{ route('admin.market-data.approve', $price) }}">{{ __('market.admin.approve') }}</button>
                                                <button class="btn btn-sm btn-outline-danger" formaction="{{ route('admin.market-data.reject', $price) }}">{{ __('market.admin.reject') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="p-3">{{ $reviews->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
