@extends('layouts.admin')

@section('title', $lot->title)

@section('breadcrumb')
    <div class="breadcrumb-item"><a href="{{ route('admin.verification-review.index', ['tab' => 'lots']) }}">{{ __('verification.admin.back_to_queue') }}</a></div>
@endsection

@section('main-content')
@php($t = fn ($k, $r = []) => __('verification.admin.' . $k, $r))
<div class="row">
    <div class="col-12">
        @include('admin.verification-review._flash')

        <div class="card">
            <div class="card-body">
                <div class="text-muted">
                    {{ $lot->organization?->name }} · {{ __('lots.admin.lot_code') }} <strong>{{ $lot->lot_code }}</strong>
                </div>
                <div class="mt-1">
                    {{ __('lots.admin.level') }}: <strong>{{ __('lots.levels.' . ($lot->evidence_level ?? 'claimed')) }}</strong>
                    @if($lot->evidence_valid_until) · {{ __('verification.public.valid_until', ['date' => $lot->evidence_valid_until->toDateString()]) }}@endif
                    · {{ __('lots.statuses.' . $lot->status) }}
                </div>
                @if($lot->organization)
                    <a class="small" href="{{ route('admin.verification-review.business', $lot->organization_id) }}">{{ __('verification.admin.open_business') }}</a>
                @endif
                @if($conflict)
                    <div class="text-danger mt-2">{{ $t('self_review', ['reason' => $t('self_review_' . $conflict)]) }}</div>
                @endif
            </div>
        </div>

        @foreach($items as $ev)
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h4>{{ __('lots.kinds.' . $ev->kind) }}</h4>
                    <span class="badge badge-light">{{ __('verification.statuses.' . $ev->status) }}</span>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong>{{ $t('pages') }}:</strong>
                        @foreach($ev->pages as $page)
                            <a href="{{ $pageUrls[$page->id] }}" target="_blank" rel="noopener" class="mr-2">{{ $t('page', ['n' => $page->page_number]) }}</a>
                        @endforeach
                    </div>
                    @if($ev->isPhoto())
                        <div><strong>{{ __('lots.admin.captured') }}:</strong> {{ $ev->captured_at?->timezone(config('verification.timezone'))->format('Y-m-d H:i') }}</div>
                        <div>
                            <strong>{{ __('lots.admin.location') }}:</strong>
                            @if($ev->latitude !== null)
                                {{ number_format($ev->latitude, 5) }}, {{ number_format($ev->longitude, 5) }}@if($ev->location_accuracy_m) (± {{ $ev->location_accuracy_m }} m)@endif
                            @else
                                <span class="text-muted">{{ __('lots.admin.no_location') }}</span>
                            @endif
                        </div>
                    @else
                        <div><strong>{{ __('lots.admin.lab_name') }}:</strong> {{ $ev->details['lab_name'] ?? '' }}</div>
                        @if(!empty($ev->details['report_number']))<div><strong>{{ __('lots.admin.report_number') }}:</strong> {{ $ev->details['report_number'] }}</div>@endif
                        @if(!empty($ev->details['issued_at']))<div><strong>{{ $t('issued') }}:</strong> {{ $ev->details['issued_at'] }}</div>@endif
                    @endif
                    @if($ev->expires_at)<div><strong>{{ $t('expires') }}:</strong> {{ $ev->expires_at->toDateString() }}</div>@endif
                    @if($ev->review_reason)<div class="text-muted mt-1">{{ $t('reason') }}: {{ $ev->review_reason }}</div>@endif

                    @if($ev->status === \App\Models\LotEvidence::STATUS_IN_REVIEW && !$conflict)
                        <hr>
                        @php($checks = $ev->isPhoto() ? (config('verification.lots.photo_kind_checks')[$ev->kind] ?? []) : config('verification.lots.lab_report_checks'))
                        <form method="POST" action="{{ route('admin.verification-review.evidence.approve', $ev) }}" class="mb-3">
                            @csrf
                            @foreach($checks as $check)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="checks[{{ $check }}]" value="1" id="c{{ $ev->id }}{{ $check }}" required>
                                    <label class="form-check-label" for="c{{ $ev->id }}{{ $check }}">{{ $t('confirm_check', ['check' => __('lots.checks.' . $check)]) }}</label>
                                </div>
                            @endforeach
                            <button type="submit" class="btn btn-success mt-2">{{ $t('approve') }}</button>
                        </form>

                        <form method="POST" class="form-inline flex-wrap" style="gap: 8px;">
                            @csrf
                            <select name="reason_code" class="form-control">
                                <option value="">{{ $t('reason') }}…</option>
                                @foreach(config('verification.lots.reasons') as $code)
                                    <option value="{{ $code }}">{{ __('lots.reasons.' . $code) }}</option>
                                @endforeach
                            </select>
                            <input name="reason" class="form-control" placeholder="{{ $t('custom_reason') }}" maxlength="500">
                            <button type="submit" formaction="{{ route('admin.verification-review.evidence.changes', $ev) }}" class="btn btn-warning">{{ $t('ask_changes') }}</button>
                            <button type="submit" formaction="{{ route('admin.verification-review.evidence.reject', $ev) }}" class="btn btn-danger">{{ $t('reject') }}</button>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
