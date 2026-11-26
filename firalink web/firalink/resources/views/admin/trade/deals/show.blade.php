@extends('layouts.admin')

@section('title', __('deals.reference') . ' ' . $deal->deal_reference)

@section('main-content')
<div class="row">
    <div class="col-12">
        @include('admin.verification-review._flash')
        @if($errors->any())
            <div class="alert alert-danger" role="alert">
                @foreach($errors->all() as $message)<div>{{ $message }}</div>@endforeach
            </div>
        @endif
        <p><a href="{{ route('admin.trade.deals.index') }}">&larr; {{ __('deals.title') }}</a></p>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">{{ $deal->deal_reference }} · {{ $deal->title }}</h4>
                <span class="badge badge-light">{{ __('deals.statuses.' . $deal->status) }}</span>
            </div>
            <div class="card-body">
                @if($deal->paused_at)
                    <div class="alert alert-secondary">{{ __('deals.paused') }}</div>
                @endif
                <h6>{{ __('deals.terms') }}</h6>
                <table class="table table-sm mb-4">
                    <tr><th>{{ __('deals.seller') }}</th><td>{{ $deal->organization?->name ?? '—' }} @if($deal->organization?->fira_id)<span class="text-muted">· {{ $deal->organization->fira_id }}</span>@endif</td></tr>
                    <tr><th>{{ __('deals.buyer') }}</th><td>{{ $deal->buyerOrganization?->name ?? '—' }} @if($deal->buyerOrganization?->fira_id)<span class="text-muted">· {{ $deal->buyerOrganization->fira_id }}</span>@endif</td></tr>
                    <tr><th>{{ __('deals.lot') }}</th><td>{{ $deal->listing?->title ?? '—' }} @if($deal->listing?->lot_code)<span class="text-muted">· {{ $deal->listing->lot_code }}</span>@endif</td></tr>
                    <tr><th>{{ __('deals.value') }}</th><td>USD {{ number_format($data['target_value_usd'], 2) }}</td></tr>
                    <tr><th>{{ __('deals.incoterm') }}</th><td>{{ $data['incoterm'] }}</td></tr>
                    <tr><th>{{ __('deals.deposit') }}</th><td>USD {{ number_format($data['deposit_usd'], 2) }} ({{ $data['deposit_percent'] }}%)</td></tr>
                    <tr><th>{{ __('deals.balance') }}</th><td>USD {{ number_format($data['balance_usd'], 2) }}</td></tr>
                    <tr>
                        <th>{{ __('deals.signed') }}</th>
                        <td>
                            {{ __('deals.seller') }}: {{ $deal->seller_signed_at ? $deal->seller_signed_at->format('Y-m-d H:i') : __('deals.not_signed') }} ·
                            {{ __('deals.buyer') }}: {{ $deal->buyer_signed_at ? $deal->buyer_signed_at->format('Y-m-d H:i') : __('deals.not_signed') }}
                        </td>
                    </tr>
                    <tr>
                        <th>{{ __('deals.contract_file') }}</th>
                        <td>@if($contractUrl)<a href="{{ $contractUrl }}" target="_blank" rel="noopener">{{ __('deals.view_proof') }}</a>@else <span class="text-muted">{{ __('deals.no_contract_file') }}</span>@endif</td>
                    </tr>
                    <tr>
                        <th>{{ __('deals.stock') }}</th>
                        <td>
                            @if($hold)
                                {{ __('deals.stock_line', ['bags' => $hold->bags, 'status' => __('deals.stock_statuses.' . $hold->status, ['date' => $hold->expires_at?->format('Y-m-d H:i')])]) }}
                            @else
                                <span class="text-muted">{{ __('deals.no_stock') }}</span>
                            @endif
                        </td>
                    </tr>
                </table>

                <h6>{{ __('deals.steps') }}</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr><th>#</th><th>{{ __('deals.current_step') }}</th><th class="text-right">{{ __('deals.amount') }}</th><th>{{ __('deals.status') }}</th><th>{{ __('deals.proof') }}</th></tr></thead>
                        <tbody>
                            @foreach($steps as $i => $step)
                                <tr class="{{ $current && $current->id === $step->id ? 'font-weight-bold' : '' }}">
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ \App\Services\Trade\DealRoomService::STEPS[$step->milestone_key]['title'] ?? $step->title }}</td>
                                    <td class="text-right">{{ $data['milestones'][$i]['amount_usd'] !== null ? 'USD ' . number_format($data['milestones'][$i]['amount_usd'], 2) : '' }}</td>
                                    <td>
                                        {{ __('deals.step_statuses.' . $step->status) }}
                                        @if($step->bank_reference)<div class="text-small text-muted">{{ $step->bank_reference }}</div>@endif
                                    </td>
                                    <td>
                                        @if(isset($proofUrls[$step->id]))
                                            <a href="{{ $proofUrls[$step->id] }}" target="_blank" rel="noopener">{{ __('deals.view_proof') }}</a>
                                        @else
                                            <span class="text-muted">{{ __('deals.no_proof') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h4 class="mb-0">{{ __('deals.activity') }}</h4></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    @foreach($activity as $log)
                        <tr><td class="text-muted text-nowrap">{{ $log->created_at?->format('Y-m-d H:i') }}</td><td>{{ $log->action }}</td></tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        @if($deal->status === \App\Models\DealRoom::STATUS_DISPUTED)
            <div class="card">
                <div class="card-header"><h4 class="mb-0">{{ __('deals.dispute_title') }}</h4></div>
                <div class="card-body">
                    <p><strong>{{ __('deals.dispute_reason') }}:</strong> {{ $deal->dispute_reason }}</p>
                    @if($disputeUrl)<p><a href="{{ $disputeUrl }}" target="_blank" rel="noopener">{{ __('deals.view_evidence') }}</a></p>@endif
                    <p class="text-muted small">{{ __('deals.resolve_help') }}</p>
                    <form method="POST" action="{{ route('admin.trade.deals.dispute', $deal->id) }}">
                        @csrf
                        <div class="form-group">
                            <label for="outcome">{{ __('deals.outcome') }}</label>
                            <select id="outcome" name="outcome" class="form-control" required>
                                @foreach(['resume', 'cancel'] as $o)<option value="{{ $o }}">{{ __('deals.outcomes.' . $o) }}</option>@endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="note">{{ __('deals.decision_note') }}</label>
                            <textarea id="note" name="note" class="form-control" rows="3" required minlength="5" maxlength="2000"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">{{ __('deals.resolve') }}</button>
                    </form>
                </div>
            </div>
        @endif

        @if($canConfirm)
            <div class="card">
                <div class="card-header"><h4 class="mb-0">{{ __('deals.confirm_title') }}</h4></div>
                <div class="card-body">
                    <p class="mb-1"><strong>{{ \App\Services\Trade\DealRoomService::STEPS[$current->milestone_key]['title'] ?? $current->title }}</strong> · {{ __('deals.step_statuses.' . $current->status) }}</p>
                    <p class="text-muted small">{{ __('deals.confirm_help') }}</p>
                    <form method="POST" action="{{ route('admin.trade.deals.confirm', [$deal->id, $current->id]) }}">
                        @csrf
                        <div class="form-group">
                            <label for="decision">{{ __('deals.decision') }}</label>
                            <select id="decision" name="decision" class="form-control" required>
                                @foreach(['achieved', 'waived'] as $d)<option value="{{ $d }}">{{ __('deals.decisions.' . $d) }}</option>@endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="bank_reference">{{ __('deals.bank_reference') }}</label>
                            <input id="bank_reference" name="bank_reference" class="form-control" required maxlength="120" autocomplete="off" value="{{ old('bank_reference') }}">
                        </div>
                        <div class="form-group">
                            <label for="notes">{{ __('deals.notes') }}</label>
                            <textarea id="notes" name="notes" class="form-control" rows="2" maxlength="2000">{{ old('notes') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">{{ __('deals.confirm') }}</button>
                    </form>
                </div>
            </div>
        @endif

        @if(!in_array($deal->status, [\App\Models\DealRoom::STATUS_COMPLETED, \App\Models\DealRoom::STATUS_CANCELLED], true))
            <div class="card">
                <div class="card-header"><h4 class="mb-0">{{ __('deals.cancel_title') }}</h4></div>
                <div class="card-body">
                    <p class="text-muted small">{{ __('deals.cancel_help') }}</p>
                    <form method="POST" action="{{ route('admin.trade.deals.cancel', $deal->id) }}" onsubmit="return confirm(@js(__('deals.cancel_confirm')));">
                        @csrf
                        <div class="form-group">
                            <label for="reason">{{ __('deals.cancel_reason') }}</label>
                            <textarea id="reason" name="reason" class="form-control" rows="2" required minlength="5" maxlength="2000"></textarea>
                        </div>
                        <button type="submit" class="btn btn-danger">{{ __('deals.cancel') }}</button>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
