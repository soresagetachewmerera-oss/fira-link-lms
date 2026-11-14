@extends('layouts.admin')

@section('title', __('verification.admin.queue_title'))

@section('main-content')
<div class="row">
    <div class="col-12">
        @include('admin.verification-review._flash')
        <div class="card">
            <div class="card-body p-0">
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
            </div>
        </div>
    </div>
</div>
@endsection
