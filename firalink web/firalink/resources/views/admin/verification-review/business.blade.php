@extends('layouts.admin')

@section('title', $org->name)

@section('breadcrumb')
    <div class="breadcrumb-item"><a href="{{ route('admin.verification-review.index') }}">{{ __('verification.admin.back_to_queue') }}</a></div>
@endsection

@section('main-content')
@php($t = fn ($k, $r = []) => __('verification.admin.' . $k, $r))
<div class="row">
    <div class="col-12">
        @include('admin.verification-review._flash')

        <div class="card">
            <div class="card-body">
                <div class="text-muted">{{ __('verification.roles.' . $org->roleKey()) }} · {{ $org->entity_type }} · {{ $org->country }}</div>
                <div class="mt-1"><strong>{{ $t('current_tier', ['tier' => $org->hasActiveTier(1) ? $org->verification_tier : 0]) }}</strong>
                    @if($org->status === 'suspended') · <span class="badge badge-danger">{{ __('verification.public.suspended') }}</span>@endif
                </div>
                @if($org->fira_id)<div class="text-muted small mt-1">{{ __('verification.public.fira_id') }} {{ $org->fira_id }}</div>@endif

                @if($conflict || $org->needs_manual_review || $sharedIdNames->isNotEmpty())
                    <div class="mt-3">
                        <strong>{{ $t('flags') }}</strong>
                        <ul class="mb-0">
                            @if($conflict)<li class="text-danger">{{ $t('self_review', ['reason' => $t('self_review_' . $conflict)]) }}</li>@endif
                            @if($org->needs_manual_review)<li>{{ $t('flag_manual_review') }}</li>@endif
                            @if($sharedIdNames->isNotEmpty())<li>{{ $t('flag_owner_id_match', ['names' => $sharedIdNames->join(', ')]) }}</li>@endif
                        </ul>
                    </div>
                @endif
            </div>
        </div>

        @foreach($documents as $doc)
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h4>{{ __('verification.doc_types.' . $doc->document_type) }} · {{ $t('tier') }} {{ $doc->tier }}</h4>
                    <span class="badge badge-light">{{ __('verification.statuses.' . $doc->status) }}</span>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong>{{ $t('pages') }}:</strong>
                        @foreach($doc->pages as $page)
                            <a href="{{ $pageUrls[$page->id] }}" target="_blank" rel="noopener" class="mr-2">{{ $t('page', ['n' => $page->page_number]) }}</a>
                        @endforeach
                    </div>
                    @if($doc->document_number)<div><strong>{{ $t('number') }}:</strong> {{ $doc->document_number }}</div>@endif
                    @if($doc->issued_at)<div><strong>{{ $t('issued') }}:</strong> {{ $doc->issued_at->toDateString() }}</div>@endif
                    @if($doc->expires_at)<div><strong>{{ $t('expires') }}:</strong> {{ $doc->expires_at->toDateString() }}</div>@endif
                    @foreach(($doc->details ?? []) as $field => $value)
                        <div><strong>{{ __('verification.fields.' . $field) }}:</strong> {{ $value }}</div>
                    @endforeach
                    @if($doc->review_reason)<div class="text-muted mt-1">{{ $t('reason') }}: {{ $doc->review_reason }}</div>@endif

                    @if($doc->status === \App\Models\VerificationDocument::STATUS_IN_REVIEW && !$conflict)
                        <hr>
                        <form method="POST" action="{{ route('admin.verification-review.approve', $doc) }}" class="mb-3">
                            @csrf
                            @foreach((\App\Services\Verification\TierService::documentTypeConfig($doc->document_type)['checks'] ?? []) as $check)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="checks[{{ $check }}]" value="1" id="c{{ $doc->id }}{{ $check }}" required>
                                    <label class="form-check-label" for="c{{ $doc->id }}{{ $check }}">{{ $t('confirm_check', ['check' => __('verification.checks.' . $check)]) }}</label>
                                </div>
                            @endforeach
                            @if($doc->tier >= 2 && !$steppedUpFresh)
                                @include('admin.verification-review._step_up', ['tier' => $doc->tier])
                            @endif
                            <button type="submit" class="btn btn-success mt-2">{{ $t('approve') }}</button>
                        </form>

                        <form method="POST" class="form-inline flex-wrap" style="gap: 8px;">
                            @csrf
                            <select name="reason_code" class="form-control">
                                <option value="">{{ $t('reason') }}…</option>
                                @foreach(config('verification.reasons') as $code)
                                    <option value="{{ $code }}">{{ __('verification.reasons.' . $code) }}</option>
                                @endforeach
                            </select>
                            <input name="reason" class="form-control" placeholder="{{ $t('custom_reason') }}" maxlength="500">
                            <button type="submit" formaction="{{ route('admin.verification-review.changes', $doc) }}" class="btn btn-warning">{{ $t('ask_changes') }}</button>
                            <button type="submit" formaction="{{ route('admin.verification-review.reject', $doc) }}" class="btn btn-danger">{{ $t('reject') }}</button>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach

        @if(\App\Services\Verification\TierService::maxTier($org) === 3 && !$conflict)
            <div class="card">
                <div class="card-header"><h4>{{ $t('tier3_title') }}</h4></div>
                <div class="card-body">
                    @if($tier3Grant)
                        <p>{{ $t('tier3_active', ['date' => $tier3Grant->expires_at->toDateString()]) }}</p>
                        <form method="POST" action="{{ route('admin.verification-review.tier3.revoke', $org) }}" class="form-inline" style="gap: 8px;">
                            @csrf
                            <input name="reason" class="form-control" placeholder="{{ $t('suspend_reason') }}" required maxlength="1000">
                            <button type="submit" class="btn btn-outline-danger">{{ $t('tier3_revoke') }}</button>
                        </form>
                    @elseif($computed['tier'] < 2)
                        <p class="text-muted mb-0">{{ $t('tier3_needs_tier2') }}</p>
                    @else
                        <form method="POST" action="{{ route('admin.verification-review.tier3.grant', $org) }}">
                            @csrf
                            <p class="text-muted">{{ $t('tier3_checklist') }}</p>
                            @foreach(config('verification.tiers.3.checklist', []) as $item)
                                <div class="form-group mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="checklist[{{ $item }}]" value="1" id="t3{{ $item }}" required>
                                        <label class="form-check-label" for="t3{{ $item }}">{{ __('verification.checklist.' . $item) }}</label>
                                    </div>
                                    <input name="checklist[{{ $item }}_note]" class="form-control form-control-sm mt-1" maxlength="500" placeholder="{{ $t('tier3_notes') }}">
                                </div>
                            @endforeach
                            <textarea name="notes" class="form-control mb-2" rows="2" maxlength="2000" placeholder="{{ $t('tier3_notes') }}"></textarea>
                            @if(!$steppedUpFresh)
                                @include('admin.verification-review._step_up', ['tier' => 3])
                            @endif
                            <button type="submit" class="btn btn-primary">{{ $t('tier3_grant') }}</button>
                        </form>
                    @endif
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ $org->status === 'suspended' ? route('admin.verification-review.unsuspend', $org) : route('admin.verification-review.suspend', $org) }}" class="form-inline" style="gap: 8px;">
                    @csrf
                    <input name="reason" class="form-control" placeholder="{{ $t('suspend_reason') }}" required maxlength="1000">
                    <button type="submit" class="btn {{ $org->status === 'suspended' ? 'btn-outline-primary' : 'btn-outline-danger' }}">
                        {{ $org->status === 'suspended' ? $t('unsuspend') : $t('suspend') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
