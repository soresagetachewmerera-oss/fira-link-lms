@php($t = fn ($k, $r = []) => __('trade.admin.' . $k, $r))
@if($reports->isEmpty())
    <p class="p-4 mb-0 text-muted">{{ $t('reports_empty') }}</p>
@else
    <div class="table-responsive">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>{{ $t('received') }}</th>
                    <th>{{ $t('reported_business') }}</th>
                    <th>{{ $t('reason') }}</th>
                    <th>{{ $t('target') }}</th>
                    <th>{{ $t('status') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($reports as $r)
                    <tr>
                        <td>{{ $r->created_at?->diffForHumans() }}</td>
                        <td>{{ $r->reportedOrganization?->name ?? '–' }}</td>
                        <td>{{ __('trade.report_reasons.' . ($r->reason_code ?: 'other')) }}</td>
                        <td>
                            @if($r->reported_message_id){{ $t('target_message') }}
                            @elseif($r->reported_deal_room_id){{ $t('target_deal') }}
                            @elseif($r->reported_sample_id){{ $t('target_sample') }}
                            @elseif($r->reported_listing_id){{ $t('target_lot') }}: {{ $r->reportedListing?->lot_code }}
                            @else{{ $t('target_business') }}@endif
                        </td>
                        <td>{{ $t('status_' . $r->status) }}</td>
                        <td><a class="btn btn-sm btn-primary" href="{{ route('admin.verification-review.report', $r->id) }}">{{ $t('open_report') }}</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="p-3">{{ $reports->appends(['tab' => 'reports'])->links() }}</div>
@endif
