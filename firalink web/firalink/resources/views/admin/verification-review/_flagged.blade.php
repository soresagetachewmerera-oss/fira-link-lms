@php($t = fn ($k, $r = []) => __('trade.admin.' . $k, $r))
<p class="px-3 pt-3 text-muted small mb-0">{{ $t('flagged_hint') }}</p>
@if($flagged->isEmpty())
    <p class="p-4 mb-0 text-muted">{{ $t('flagged_empty') }}</p>
@else
    <div class="table-responsive">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>{{ $t('sent') }}</th>
                    <th>{{ $t('from') }}</th>
                    <th>{{ $t('to') }}</th>
                    <th>{{ $t('found') }}</th>
                    <th>{{ $t('message') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($flagged as $m)
                    @php($c = $m->conversation)
                    @php($to = $c && (int) $m->organization_id === (int) $c->buyer_organization_id ? $c->sellerOrganization : $c?->buyerOrganization)
                    <tr>
                        <td>{{ $m->created_at?->diffForHumans() }}</td>
                        <td>
                            @if($m->organization)<a href="{{ route('admin.verification-review.business', $m->organization_id) }}">{{ $m->organization->name }}</a>@endif
                        </td>
                        <td>{{ $to?->name }}</td>
                        <td>{{ collect($m->flag_kinds ?? [])->map(fn ($k) => $t('kind_' . $k))->join(', ') }}</td>
                        <td style="max-width: 420px; white-space: pre-wrap;">{{ \Illuminate\Support\Str::limit($m->body, 300) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="p-3">{{ $flagged->appends(['tab' => 'flagged'])->links() }}</div>
@endif
