@extends('layouts.admin')

@section('title', __('verification.admin.queue_title'))

@section('main-content')
<div class="row">
    <div class="col-12">
        @include('admin.verification-review._flash')

        <ul class="nav nav-tabs mb-3">
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'business' ? 'active' : '' }}" href="{{ route('admin.verification-review.index') }}">
                    {{ __('lots.admin.tab_business') }} ({{ $documents->total() }})
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'lots' ? 'active' : '' }}" href="{{ route('admin.verification-review.index', ['tab' => 'lots']) }}">
                    {{ __('lots.admin.tab_lots') }} ({{ $evidence->total() }})
                </a>
            </li>
        </ul>

        <div class="card">
            <div class="card-body p-0">
                @if($tab === 'business')
                    @if($documents->isEmpty())
                        <p class="p-4 mb-0 text-muted">{{ __('verification.admin.queue_empty') }}</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>{{ __('verification.admin.submitted') }}</th>
                                        <th>{{ __('verification.admin.business') }}</th>
                                        <th>{{ __('verification.admin.document') }}</th>
                                        <th>{{ __('verification.admin.tier') }}</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($documents as $doc)
                                        <tr>
                                            <td>{{ $doc->submitted_at?->diffForHumans() }}</td>
                                            <td>{{ $doc->organization?->name }}<div class="text-muted small">{{ __('verification.roles.' . $doc->organization?->roleKey()) }}</div></td>
                                            <td>{{ __('verification.doc_types.' . $doc->document_type) }}</td>
                                            <td>{{ $doc->tier }}</td>
                                            <td><a class="btn btn-sm btn-primary" href="{{ route('admin.verification-review.business', $doc->organization_id) }}">{{ __('verification.admin.open_business') }}</a></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3">{{ $documents->links() }}</div>
                    @endif
                @else
                    @if($evidence->isEmpty())
                        <p class="p-4 mb-0 text-muted">{{ __('lots.admin.queue_empty') }}</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>{{ __('verification.admin.submitted') }}</th>
                                        <th>{{ __('verification.admin.business') }}</th>
                                        <th>{{ __('lots.admin.lot') }}</th>
                                        <th>{{ __('lots.admin.evidence') }}</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($evidence as $ev)
                                        <tr>
                                            <td>{{ $ev->submitted_at?->diffForHumans() }}</td>
                                            <td>{{ $ev->organization?->name }}</td>
                                            <td>{{ $ev->listing?->title }}<div class="text-muted small">{{ $ev->listing?->lot_code }}</div></td>
                                            <td>{{ __('lots.kinds.' . $ev->kind) }}</td>
                                            <td><a class="btn btn-sm btn-primary" href="{{ route('admin.verification-review.lot', $ev->listing_id) }}">{{ __('lots.admin.open_lot') }}</a></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3">{{ $evidence->appends(['tab' => 'lots'])->links() }}</div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
