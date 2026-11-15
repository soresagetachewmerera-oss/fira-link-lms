@extends('layouts.admin')

@php($t = fn ($k, $r = []) => __('trade.admin.' . $k, $r))

@section('title', $t('report_title'))

@section('main-content')
<div class="row">
    <div class="col-12">
        @include('admin.verification-review._flash')
        <p><a href="{{ route('admin.verification-review.index', ['tab' => 'reports']) }}">← {{ $t('tab_reports') }}</a></p>

        <div class="card mb-3">
            <div class="card-body">
                <h5 class="mb-1">{{ __('trade.report_reasons.' . ($report->reason_code ?: 'other')) }}</h5>
                <div class="text-muted small">{{ $t('received') }}: {{ $report->created_at?->toDayDateTimeString() }} · {{ $t('status_' . $report->status) }}</div>
                @if($report->details)
                    <p class="mt-3 mb-0" style="white-space: pre-wrap;">{{ $report->details }}</p>
                @endif
                @if($evidenceUrl)
                    <p class="mt-3 mb-0"><a href="{{ $evidenceUrl }}" target="_blank" rel="noopener">{{ $t('open_evidence') }}</a></p>
                @endif
                @if($report->block_requested)
                    <p class="mt-2 mb-0 text-muted">{{ $t('block_requested') }}</p>
                @endif
                <p class="mt-2 mb-0 text-muted small">{{ $t('reporter_private', ['business' => $report->reporterOrganization?->name ?? '–']) }}</p>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <strong>{{ $t('reported_business') }}</strong>
                @if($report->reportedOrganization)
                    <div class="mt-1">
                        <a href="{{ route('admin.verification-review.business', $report->reported_organization_id) }}">{{ $report->reportedOrganization->name }}</a>
                        @if($report->reportedOrganization->status === 'suspended') · <span class="badge badge-danger">{{ __('verification.public.suspended') }}</span>@endif
                    </div>
                    <div class="text-muted small">{{ $t('open_deals', ['n' => $openDeals]) }} · {{ $t('other_reports', ['n' => $otherReports]) }}</div>
                @else
                    <div class="text-muted">–</div>
                @endif
                @if($report->reportedListing)
                    <div class="mt-2">{{ $t('target_lot') }}: {{ $report->reportedListing->title }} · {{ $report->reportedListing->lot_code }}</div>
                @endif
                @if($report->reportedDealRoom)
                    <div class="mt-2">{{ $t('target_deal') }}: {{ $report->reportedDealRoom->deal_reference }} · {{ $report->reportedDealRoom->title }}
                        @if($report->reportedDealRoom->paused_at) · <span class="badge badge-secondary">{{ $t('deal_paused') }}</span>@endif
                    </div>
                @endif
                @if($report->reportedSample)
                    <div class="mt-2">{{ $t('target_sample') }}: {{ $report->reportedSample->sample_reference ?? ('#' . $report->reportedSample->id) }}</div>
                @endif
            </div>
        </div>

        @if($context->isNotEmpty())
            <div class="card mb-3">
                <div class="card-body">
                    <strong>{{ $t('chat_context') }}</strong>
                    <p class="text-muted small">{{ $t('chat_context_hint') }}</p>
                    @foreach($context as $m)
                        <div class="p-2 mb-2 {{ $m->id === $report->reported_message_id ? 'border border-danger' : 'border' }}" style="border-radius: var(--fira-radius, 6px);">
                            <div class="small text-muted">{{ $m->organization?->name }} · {{ $m->created_at?->toDayDateTimeString() }}
                                @if($m->flagged) · {{ collect($m->flag_kinds ?? [])->map(fn ($k) => $t('kind_' . $k))->join(', ') }}@endif
                            </div>
                            <div style="white-space: pre-wrap;">{{ $m->body }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="card mb-3">
            <div class="card-body">
                <strong>{{ $t('decide') }}</strong>
                <p class="text-muted small">{{ $t('decide_hint') }}</p>
                <form method="POST" action="{{ route('admin.verification-review.report.decide', $report->id) }}">
                    @csrf
                    <div class="form-group">
                        <textarea name="notes" class="form-control" rows="3" maxlength="2000" placeholder="{{ $t('notes') }}">{{ old('notes', $report->action_taken_notes) }}</textarea>
                    </div>
                    <button type="submit" name="decision" value="under_review" class="btn btn-outline-primary">{{ $t('decision_under_review') }}</button>
                    <button type="submit" name="decision" value="resolved" class="btn btn-primary">{{ $t('decision_resolved') }}</button>
                    <button type="submit" name="decision" value="dismissed" class="btn btn-outline-secondary">{{ $t('decision_dismissed') }}</button>
                </form>
            </div>
        </div>

        @if($report->reportedOrganization && $report->reportedOrganization->status !== 'suspended')
            <div class="card">
                <div class="card-body">
                    <strong>{{ $t('suspend_title') }}</strong>
                    <p class="text-muted small">{{ $t('suspend_hint', ['n' => $openDeals]) }}</p>
                    @if($conflict)
                        <p class="text-danger mb-0">{{ __('verification.admin.self_review', ['reason' => __('verification.admin.self_review_' . $conflict)]) }}</p>
                    @else
                        <form method="POST" action="{{ route('admin.verification-review.suspend', $report->reported_organization_id) }}" class="form-inline" style="gap: 8px;">
                            @csrf
                            <input name="reason" class="form-control" placeholder="{{ __('verification.admin.suspend_reason') }}" required maxlength="1000" value="{{ $t('suspend_reason_prefill', ['id' => $report->id]) }}">
                            <button type="submit" class="btn btn-outline-danger">{{ __('verification.admin.suspend') }}</button>
                        </form>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
