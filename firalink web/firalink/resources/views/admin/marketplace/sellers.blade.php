@extends('layouts.admin')

@section('title', 'Marketplace Sellers & Exporters Verification')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item">Coffee Marketplace</div>
        <div class="breadcrumb-item active">Exporters</div>
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
                    <h4>Ethiopian Coffee Exporters & Producers</h4>
                    <div class="card-header-form">
                        <form method="GET" action="{{ route('admin.marketplace.sellers') }}" class="d-flex gap-2">
                            <select name="status" class="form-control" onchange="this.form.submit()">
                                <option value="">All Verification Statuses</option>
                                <option value="verified" {{ request('status') == 'verified' ? 'selected' : '' }}>Verified</option>
                                <option value="under_review" {{ request('status') == 'under_review' ? 'selected' : '' }}>Under Review</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                            </select>
                        </form>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-md">
                            <thead>
                                <tr>
                                    <th>Company / Exporter</th>
                                    <th>Business Type</th>
                                    <th>ECTA License #</th>
                                    <th>Origins</th>
                                    <th>Wet Mills</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($sellers as $seller)
                                    <tr>
                                        <td>
                                            <div class="font-weight-bold">{{ $seller->company_name }}</div>
                                            <div class="text-small text-muted">{{ $seller->contact_email ?? $seller->user->email }}</div>
                                        </td>
                                        <td class="text-capitalize">{{ str_replace('_', ' ', $seller->business_type) }}</td>
                                        <td><code>{{ $seller->export_license_no ?? 'N/A' }}</code></td>
                                        <td>
                                            @if(!empty($seller->origin_regions))
                                                @foreach($seller->origin_regions as $reg)
                                                    <span class="badge badge-light">{{ $reg }}</span>
                                                @endforeach
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>{{ $seller->washing_station_count }} Mills</td>
                                        <td>
                                            {{-- Slice 3: the tier comes only from reviewed documents. --}}
                                            @php($org = $seller->organization)
                                            @if($org && $org->status === 'suspended')
                                                <div class="badge badge-danger">{{ __('verification.public.suspended') }}</div>
                                            @elseif($org && $org->hasActiveTier(1))
                                                <div class="badge badge-success">{{ __('verification.public.verified_tier', ['tier' => $org->verification_tier]) }}</div>
                                            @else
                                                <div class="badge badge-secondary">{{ __('verification.statuses.not_uploaded') }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($org)
                                                <a href="{{ route('admin.verification-review.business', $org->id) }}" class="btn btn-sm btn-primary">
                                                    {{ __('verification.admin.open_business') }}
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">No exporters found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer text-right">
                    {{ $sellers->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection

