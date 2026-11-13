@extends('layouts.admin')

@section('title', 'Coffee Lots Management & Moderation')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item">Coffee Marketplace</div>
        <div class="breadcrumb-item active">Listings</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-12">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible show fade">
                    <div class="alert-body">
                        <button class="close" data-dismiss="alert"><span>&times;</span></button>
                        {{ session('success') }}
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Published Green Coffee Lots</h4>
                    <div class="card-header-form">
                        <form method="GET" action="{{ route('admin.marketplace.listings') }}" class="d-flex gap-2">
                            <select name="status" class="form-control" onchange="this.form.submit()">
                                <option value="">All Statuses</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="archived" {{ request('status') == 'archived' ? 'selected' : '' }}>Archived</option>
                            </select>
                        </form>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-md">
                            <thead>
                                <tr>
                                    <th>Lot Title</th>
                                    <th>Exporter</th>
                                    <th>Origin & Process</th>
                                    <th>Grade & Score</th>
                                    <th>FOB Price</th>
                                    <th>Available</th>
                                    <th>Featured</th>
                                    <th>Evidence</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($listings as $lot)
                                    <tr>
                                        <td>
                                            <div class="font-weight-bold">{{ $lot->title }}</div>
                                            <div class="text-small text-muted">{{ $lot->crop_year }} • {{ $lot->washing_station ?? 'Direct Farm' }}</div>
                                        </td>
                                        <td>{{ $lot->seller->sellerProfile->company_name ?? $lot->seller->name }}</td>
                                        <td>
                                            <span class="badge badge-primary">{{ $lot->origin_region }}</span>
                                            <span class="badge badge-light text-capitalize">{{ str_replace('_', ' ', $lot->processing_method) }}</span>
                                        </td>
                                        <td>
                                            <span class="font-weight-bold">{{ strtoupper(str_replace('_', ' ', $lot->grade)) }}</span>
                                            @if($lot->cupping_score)
                                                <div class="text-small text-warning"><i class="fas fa-star"></i> {{ number_format($lot->cupping_score, 2) }} SCA</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="font-weight-bold">${{ number_format($lot->indicative_price_usd_per_lb, 2) }} <small class="text-muted">/lb</small></div>
                                            <div class="text-small text-muted">${{ number_format($lot->indicative_price_usd_per_mt, 0) }} / MT</div>
                                        </td>
                                        <td>{{ number_format($lot->available_quantity_bags) }} Bags</td>
                                        <td>
                                            <form method="POST" action="{{ route('admin.marketplace.listings.toggle-featured', $lot->id) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm {{ $lot->is_featured ? 'btn-warning' : 'btn-outline-secondary' }}" title="Toggle Featured">
                                                    <i class="fas fa-star"></i>
                                                </button>
                                            </form>
                                        </td>
                                        <td>
                                            @if($lot->evidence_document_path)
                                                <a href="{{ app(\App\Services\PrivateDocumentStore::class)->adminUrl('listing-evidence', $lot->id) }}" target="_blank" rel="noopener" class="d-block text-small mb-1">
                                                    <i class="fas fa-file-alt"></i> View evidence
                                                </a>
                                            @endif
                                            <form method="POST" action="{{ route('admin.marketplace.listings.evidence-level', $lot->id) }}">
                                                @csrf
                                                <select name="evidence_level" class="form-control form-control-sm mb-1" aria-label="Evidence level">
                                                    @foreach(\App\Models\CoffeeListing::EVIDENCE_LEVELS as $level)
                                                        <option value="{{ $level }}" @selected($lot->evidence_level === $level)>{{ ucfirst(str_replace('_', ' ', $level)) }}</option>
                                                    @endforeach
                                                </select>
                                                <input type="text" name="notes" class="form-control form-control-sm mb-1" placeholder="Review note (required)" required minlength="5" maxlength="1000">
                                                <button type="submit" class="btn btn-sm btn-outline-primary">Save review</button>
                                            </form>
                                        </td>
                                        <td>
                                            <span class="badge {{ $lot->status === 'active' ? 'badge-success' : 'badge-secondary' }} text-capitalize">
                                                {{ $lot->status }}
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" action="{{ route('admin.marketplace.listings.toggle-status', $lot->id) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm {{ $lot->status === 'active' ? 'btn-danger' : 'btn-success' }}" title="Toggle Active">
                                                    <i class="fas {{ $lot->status === 'active' ? 'fa-ban' : 'fa-check' }}"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center py-4 text-muted">No coffee lots found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer text-right">
                    {{ $listings->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection

