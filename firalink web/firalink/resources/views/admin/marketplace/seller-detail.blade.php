@extends('layouts.admin')

@section('title', 'Exporter Profile Audit - ' . $seller->company_name)

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.marketplace.sellers') }}">Coffee Marketplace</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.marketplace.sellers') }}">Exporters</a></div>
        <div class="breadcrumb-item active">{{ $seller->company_name }}</div>
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
                    <h4>Exporter Profile & Compliance Audit</h4>
                    <div class="card-header-action">
                        <a href="{{ route('admin.marketplace.sellers') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Exporters
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-3 text-center border-right pr-4">
                            @if($seller->logo_path)
                                <img src="{{ asset('storage/' . $seller->logo_path) }}" alt="{{ $seller->company_name }}" class="rounded img-thumbnail mb-3" style="max-height: 120px;">
                            @else
                                <div class="bg-dark text-white rounded d-inline-flex align-items-center justify-content-center mb-3" style="width: 100px; height: 100px; font-size: 32px;">
                                    <i class="fas fa-coffee"></i>
                                </div>
                            @endif

                            <h5>{{ $seller->company_name }}</h5>
                            <span class="badge badge-secondary mb-2">{{ ucfirst(str_replace('_', ' ', $seller->business_type ?? 'Exporter')) }}</span>
                            
                            <div class="mt-2">
                                @if($seller->verification_status === 'verified')
                                    <span class="badge badge-success px-3 py-2"><i class="fas fa-check-circle"></i> Verified Exporter</span>
                                @elseif($seller->verification_status === 'under_review')
                                    <span class="badge badge-info px-3 py-2"><i class="fas fa-search"></i> Under Review</span>
                                @elseif($seller->verification_status === 'rejected')
                                    <span class="badge badge-danger px-3 py-2"><i class="fas fa-times-circle"></i> Rejected</span>
                                @else
                                    <span class="badge badge-warning px-3 py-2"><i class="fas fa-clock"></i> Pending Verification</span>
                                @endif
                            </div>

                            @if($seller->verified_at)
                                <div class="small text-muted mt-2">
                                    Verified on: {{ \Carbon\Carbon::parse($seller->verified_at)->format('M d, Y') }}
                                </div>
                            @endif

                            @if($seller->verification_notes)
                                <div class="alert alert-light mt-3 text-left small border">
                                    <strong>Admin Notes:</strong><br>
                                    {{ $seller->verification_notes }}
                                </div>
                            @endif
                        </div>

                        <div class="col-md-9 pl-4">
                            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                                <h5 class="text-dark mb-0">Licensing & Regulatory Compliance</h5>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#verifyModal">
                                        <i class="fas fa-check"></i> Verify Exporter
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger ml-2" data-toggle="modal" data-target="#rejectModal">
                                        <i class="fas fa-ban"></i> Reject Application
                                    </button>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-sm-4 font-weight-bold text-muted">ECTA Export License No:</div>
                                <div class="col-sm-8 font-weight-bold text-primary">{{ $seller->export_license_no ?? 'Not Provided' }}</div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4 font-weight-bold text-muted">TIN Number:</div>
                                <div class="col-sm-8 font-weight-bold">{{ $seller->tin_number ?? 'Not Provided' }}</div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4 font-weight-bold text-muted">License Document:</div>
                                <div class="col-sm-8">
                                    @if($seller->license_document_path)
                                        <a href="{{ app(\App\Services\PrivateDocumentStore::class)->adminUrl('seller-license', $seller->id) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-file-pdf"></i> View Submitted ECTA License
                                        </a>
                                    @else
                                        <span class="text-muted">No document uploaded</span>
                                    @endif
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4 font-weight-bold text-muted">Origin Regions:</div>
                                <div class="col-sm-8">
                                    @if(is_array($seller->origin_regions) && count($seller->origin_regions) > 0)
                                        @foreach($seller->origin_regions as $region)
                                            <span class="badge badge-light mr-1 mb-1">{{ $region }}</span>
                                        @endforeach
                                    @else
                                        <span class="text-muted">General Ethiopia</span>
                                    @endif
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4 font-weight-bold text-muted">Washing Stations / Mills:</div>
                                <div class="col-sm-8">{{ $seller->washing_station_count ?? 1 }} stations</div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4 font-weight-bold text-muted">Annual Volume Capacity:</div>
                                <div class="col-sm-8">{{ $seller->annual_export_volume_mt ? $seller->annual_export_volume_mt . ' Metric Tons (MT)' : 'N/A' }}</div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4 font-weight-bold text-muted">Direct Contact:</div>
                                <div class="col-sm-8">
                                    {{ $seller->contact_email ?? $seller->user?->email }} | {{ $seller->contact_phone ?? $seller->user?->phone ?? 'N/A' }}
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4 font-weight-bold text-muted">Physical Address:</div>
                                <div class="col-sm-8">{{ $seller->address ?? 'Addis Ababa' }}, {{ $seller->city ?? '' }} {{ $seller->country ?? 'Ethiopia' }}</div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4 font-weight-bold text-muted">Company Overview:</div>
                                <div class="col-sm-8">{{ $seller->company_bio ?? 'No company bio provided.' }}</div>
                            </div>

                            @if($seller->organization && $seller->organization->verifications->count() > 0)
                                <div class="border-top pt-3 mt-3">
                                    <h6 class="text-dark font-weight-bold mb-3"><i class="fas fa-shield-alt text-purple mr-1"></i> Multi-Role Compliance Vault Dossiers</h6>
                                    @foreach($seller->organization->verifications as $v)
                                        <div class="card border mb-3 bg-light">
                                            <div class="card-body p-3">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <div>
                                                        <span class="badge badge-primary text-uppercase">{{ $v->target_role }}</span>
                                                        <span class="badge badge-{{ $v->status === 'approved' ? 'success' : ($v->status === 'rejected' ? 'danger' : 'warning') }} ml-1">
                                                            {{ strtoupper($v->status) }}
                                                        </span>
                                                        <span class="small text-muted ml-2">TIN: <strong>{{ $v->tin_number ?? 'N/A' }}</strong> | License: <strong>{{ $v->license_number ?? 'N/A' }}</strong></span>
                                                    </div>
                                                    <div>
                                                        @if($v->status !== 'approved')
                                                            <form action="{{ route('admin.marketplace.verifications.verify', $v->id) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                <button type="submit" class="btn btn-sm btn-success mr-1">
                                                                    <i class="fas fa-check"></i> Approve Role
                                                                </button>
                                                            </form>
                                                        @endif
                                                        @if($v->status !== 'rejected')
                                                            <button type="button" class="btn btn-sm btn-outline-danger" data-toggle="modal" data-target="#rejectRoleModal{{ $v->id }}">
                                                                <i class="fas fa-times"></i> Reject
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>

                                                <!-- Documents in Vault -->
                                                <div class="small mt-2">
                                                    <strong>Vaulted Documents ({{ $v->documents->where('is_valid', true)->count() }}):</strong>
                                                    <div class="d-flex flex-wrap mt-1">
                                                        @forelse($v->documents->where('is_valid', true) as $vDoc)
                                                            <a href="{{ route('marketplace.verification.download', $vDoc->id) }}" class="btn btn-xs btn-outline-dark mr-2 mb-1" target="_blank">
                                                                <i class="fas fa-file-contract text-primary mr-1"></i>
                                                                {{ ucfirst(str_replace('_', ' ', $vDoc->document_type)) }} ({{ $vDoc->file_size_kb }} KB)
                                                            </a>
                                                        @empty
                                                            <span class="text-muted italic">No legal documents vaulted yet.</span>
                                                        @endforelse
                                                    </div>
                                                </div>

                                                @if($v->rejection_reason)
                                                    <div class="alert alert-danger p-2 mt-2 mb-0 small">
                                                        <strong>Rejection Rationale:</strong> {{ $v->rejection_reason }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Reject Role Modal -->
                                        <div class="modal fade" id="rejectRoleModal{{ $v->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                    <form action="{{ route('admin.marketplace.verifications.reject', $v->id) }}" method="POST">
                                                        @csrf
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Reject {{ ucfirst($v->target_role) }} Verification</h5>
                                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Document the specific reason for rejecting {{ $seller->company_name }}'s {{ $v->target_role }} credentials.</p>
                                                            <div class="form-group">
                                                                <label>Rejection Reason (Required):</label>
                                                                <textarea name="rejection_reason" class="form-control" rows="3" required placeholder="Expired ECTA license, missing tax clearance, or mismatch in corporate registry..."></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-danger">Confirm Rejection</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Coffee Listings Section -->
                    <div class="border-top pt-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0 text-dark">Export Coffee Lots Listed by {{ $seller->company_name }} ({{ $seller->listings->count() }})</h5>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Lot Title</th>
                                        <th>Origin Region</th>
                                        <th>Grade & Process</th>
                                        <th>Cup Score</th>
                                        <th>FOB Price / kg</th>
                                        <th>Quantity Available</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($seller->listings as $listing)
                                        <tr>
                                            <td>
                                                <div class="font-weight-bold">{{ $listing->title }}</div>
                                                <div class="small text-muted">Variety: {{ $listing->variety ?? 'Heirloom' }}</div>
                                            </td>
                                            <td>{{ $listing->origin_region }}</td>
                                            <td>
                                                <span class="badge badge-info">{{ $listing->grade }}</span>
                                                <span class="badge badge-light">{{ ucfirst($listing->processing_method) }}</span>
                                            </td>
                                            <td>
                                                @if($listing->cupping_score)
                                                    <span class="font-weight-bold text-success">{{ $listing->cupping_score }} pts</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                <strong>${{ number_format($listing->price_per_kg_usd, 2) }} USD</strong>
                                            </td>
                                            <td>{{ number_format($listing->available_quantity_bags) }} bags ({{ number_format($listing->bag_weight_kg) }} kg)</td>
                                            <td>
                                                @if($listing->status === 'active')
                                                    <span class="badge badge-success">Active</span>
                                                @else
                                                    <span class="badge badge-secondary">{{ ucfirst($listing->status) }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">
                                                No coffee listings created by this exporter yet.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Verify Modal -->
    <div class="modal fade" id="verifyModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('admin.marketplace.sellers.verify', $seller->id) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Approve & Verify Exporter</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>You are verifying <strong>{{ $seller->company_name }}</strong>. This will grant them official Ethiopian exporter status and allow international buyers to submit RFQs and contracts.</p>
                        <div class="form-group">
                            <label>Audit & Verification Notes (Optional):</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="ECTA credentials verified against National Bank of Ethiopia Registry..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Verify Exporter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('admin.marketplace.sellers.reject', $seller->id) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Reject Exporter Application</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Please provide a reason for rejecting <strong>{{ $seller->company_name }}</strong>'s application.</p>
                        <div class="form-group">
                            <label>Reason for Rejection (Required):</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Invalid ECTA license number or missing TIN documentation..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Confirm Rejection</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

