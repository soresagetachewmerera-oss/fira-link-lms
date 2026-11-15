@php($t = fn ($k, $r = []) => __('trade.admin.' . $k, $r))
@if($payouts->isEmpty())
    <p class="p-4 mb-0 text-muted">{{ $t('payouts_empty') }}</p>
@else
    @foreach($payouts as $a)
        @php($org = $a->organization)
        @php($conflict = $org ? \App\Services\Verification\DocumentReviewService::conflictReason(auth()->user(), $org) : null)
        <div class="border-bottom p-3" id="payout-{{ $a->id }}">
            <div class="d-flex flex-wrap justify-content-between" style="gap: 8px;">
                <div>
                    <strong>{{ $org?->name }}</strong>
                    @if($a->is_change)<span class="badge badge-danger ml-1">{{ $t('change_badge') }}</span>@endif
                    <div class="text-muted small">{{ $t('submitted_ago', ['time' => $a->created_at?->diffForHumans()]) }}</div>
                </div>
                @if($org)<a href="{{ route('admin.verification-review.business', $org->id) }}">{{ __('verification.admin.open_business') }}</a>@endif
            </div>
            <table class="table table-sm mt-2 mb-2" style="max-width: 640px;">
                <tr><th>{{ $t('verified_name') }}</th><td>{{ $org?->name }}</td></tr>
                <tr><th>{{ $t('account_name') }}</th><td>{{ $a->account_name }}</td></tr>
                <tr><th>{{ $t('bank') }}</th><td>{{ $a->bank_name }}</td></tr>
                <tr><th>{{ $t('account') }}</th><td>{{ $t('ending', ['last4' => $a->account_last4]) }}</td></tr>
                @if($a->is_change)
                    <tr><th>{{ $t('phone_on_file') }}</th><td>{{ $org?->owner()?->phone ?: $t('no_phone_on_file') }}</td></tr>
                    <tr><th>{{ $t('paused_until') }}</th><td>{{ $a->active_from?->toDayDateTimeString() }}</td></tr>
                @endif
            </table>
            @if($conflict)
                <p class="text-danger mb-0">{{ __('verification.admin.self_review', ['reason' => __('verification.admin.self_review_' . $conflict)]) }}</p>
            @else
                <form method="POST" action="{{ route('admin.verification-review.payout.confirm', $a->id) }}" class="mb-2">
                    @csrf
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="checks[name_matches]" value="1" id="nm{{ $a->id }}" required>
                        <label class="form-check-label" for="nm{{ $a->id }}">{{ $t('check_name_matches') }}</label>
                    </div>
                    @if($a->is_change)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="checks[called_on_file]" value="1" id="cf{{ $a->id }}" required>
                            <label class="form-check-label" for="cf{{ $a->id }}">{{ $t('check_called') }}</label>
                        </div>
                    @endif
                    <button type="submit" class="btn btn-sm btn-primary mt-2">{{ $t('confirm_payout') }}</button>
                </form>
                <form method="POST" action="{{ route('admin.verification-review.payout.reject', $a->id) }}" class="form-inline" style="gap: 8px;">
                    @csrf
                    <input name="reason" class="form-control form-control-sm" placeholder="{{ $t('reject_reason') }}" required maxlength="1000">
                    <button type="submit" class="btn btn-sm btn-outline-danger">{{ $t('reject_payout') }}</button>
                </form>
            @endif
        </div>
    @endforeach
    <div class="p-3">{{ $payouts->appends(['tab' => 'payouts'])->links() }}</div>
@endif
