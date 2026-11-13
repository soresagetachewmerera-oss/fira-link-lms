@extends('layouts.admin')

@section('title', 'Coffee Minimum Prices — Market Intelligence')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item">Market Intelligence</div>
        <div class="breadcrumb-item active">Coffee Minimum Prices</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-primary">
                    <i class="fas fa-database"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Total Datasets</h4>
                    </div>
                    <div class="card-body">
                        {{ number_format($stats['total_datasets']) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Published Active</h4>
                    </div>
                    <div class="card-body">
                        {{ number_format($stats['published_count']) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon" style="background: #6D1A95; color: #fff;">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Stored Records</h4>
                    </div>
                    <div class="card-body">
                        {{ number_format($stats['total_records']) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-info">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Latest Effective</h4>
                    </div>
                    <div class="card-body" style="font-size: 15px; padding-top: 5px;">
                        {{ $stats['latest_effective'] ? \Carbon\Carbon::parse($stats['latest_effective'])->format('d M Y') : 'None' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h4 class="mb-0">Coffee Minimum Export Price Datasets</h4>
                <div class="text-muted small mt-1">Official ECTA reference schedules and verified market observation catalogs</div>
            </div>
            <div class="card-header-action mt-2 mt-sm-0">
                <a href="{{ route('admin.coffee-prices.import') }}" class="btn btn-primary" style="background: #6D1A95; border-color: #6D1A95;">
                    <i class="fas fa-file-excel mr-1"></i> Import Excel Dataset
                </a>
            </div>
        </div>
        <div class="card-body">
            <!-- Filter & Search Toolbar -->
            <form method="GET" action="{{ route('admin.coffee-prices.index') }}" class="row g-2 mb-4">
                <div class="col-md-5 col-12">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search by dataset title, source, or version..." value="{{ request('search') }}">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit" style="background: #6D1A95; border-color: #6D1A95;">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <select name="status" class="form-control" onchange="this.form.submit()">
                        <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>All Statuses</option>
                        <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Published Only</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Drafts</option>
                        <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>Archived</option>
                    </select>
                </div>
                <div class="col-md-2 col-6">
                    @if(request()->hasAny(['search', 'status']))
                        <a href="{{ route('admin.coffee-prices.index') }}" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-times mr-1"></i> Reset
                        </a>
                    @endif
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Dataset Title &amp; Version</th>
                            <th>Effective Date</th>
                            <th>Source</th>
                            <th>Records</th>
                            <th>Status</th>
                            <th>Fira AI Synthesis</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($datasets as $d)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.coffee-prices.show', $d->id) }}" class="font-weight-bold text-dark text-decoration-none">
                                        {{ $d->title }}
                                    </a>
                                    <div class="text-muted small">
                                        <i class="fas fa-code-branch mr-1 text-primary"></i> {{ $d->version ?? 'v1.0' }}
                                        &bull; <i class="fas fa-file-alt ml-1 mr-1"></i> {{ $d->file_name }}
                                    </div>
                                </td>
                                <td>
                                    <div class="font-weight-bold">
                                        {{ $d->effective_date ? $d->effective_date->format('d M Y') : '—' }}
                                    </div>
                                    <div class="text-muted small">
                                        Uploaded {{ $d->created_at->diffForHumans() }}
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-light border text-dark">
                                        {{ $d->source }}
                                    </span>
                                </td>
                                <td>
                                    <span class="font-weight-bold text-dark">{{ number_format($d->valid_records_count) }}</span> valid
                                    @if($d->duplicate_records > 0)
                                        <div class="text-muted small">({{ $d->duplicate_records }} dups skipped)</div>
                                    @endif
                                </td>
                                <td>
                                    @if($d->status === 'published')
                                        <span class="badge badge-success px-2 py-1">
                                            <i class="fas fa-check-circle mr-1"></i> Published
                                        </span>
                                    @elseif($d->status === 'draft')
                                        <span class="badge badge-warning px-2 py-1">
                                            <i class="fas fa-clock mr-1"></i> Draft
                                        </span>
                                    @else
                                        <span class="badge badge-secondary px-2 py-1">Archived</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!empty($d->ai_analysis))
                                        <span class="badge px-2 py-1" style="background: #F4ECF8; color: #6D1A95; border: 1px solid #E8D5F2;">
                                            <i class="fas fa-sparkles mr-1"></i> Synthesized
                                        </span>
                                        <div class="text-muted small" style="font-size: 11px;">
                                            {{ $d->ai_analyzed_at ? $d->ai_analyzed_at->diffForHumans() : 'Available' }}
                                        </div>
                                    @else
                                        <button class="btn btn-sm btn-outline-primary py-0 px-2 btn-trigger-ai" data-id="{{ $d->id }}" style="font-size: 11px;">
                                            <i class="fas fa-robot mr-1"></i> Synthesize
                                        </button>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <div class="btn-group">
                                        <a href="{{ route('admin.coffee-prices.show', $d->id) }}" class="btn btn-sm btn-light" title="Inspect records & AI analysis">
                                            <i class="fas fa-eye text-primary"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-light text-danger btn-delete-dataset" data-id="{{ $d->id }}" data-title="{{ $d->title }}" title="Delete dataset">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="empty-state" data-height="200">
                                        <div class="empty-state-icon" style="background: #F4ECF8; color: #6D1A95;">
                                            <i class="fas fa-file-excel"></i>
                                        </div>
                                        <h6 class="mt-3">No Coffee Minimum Price Datasets Found</h6>
                                        <p class="text-muted small">Import an official ECTA spreadsheet to populate price benchmarks and activate Fira AI market synthesis.</p>
                                        <a href="{{ route('admin.coffee-prices.import') }}" class="btn btn-primary btn-sm" style="background: #6D1A95; border-color: #6D1A95;">
                                            <i class="fas fa-plus mr-1"></i> Import First Dataset
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $datasets->links() }}
            </div>
        </div>
    </div>

    <!-- Hidden Delete Form -->
    <form id="delete-dataset-form" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Delete Dataset Confirmation
    document.querySelectorAll('.btn-delete-dataset').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const title = this.dataset.title;
            if (confirm(`Are you sure you want to delete "${title}" and all its price records? This action cannot be undone.`)) {
                const form = document.getElementById('delete-dataset-form');
                form.action = `/admin/coffee-prices/${id}`;
                form.submit();
            }
        });
    });

    // Quick AI Trigger
    document.querySelectorAll('.btn-trigger-ai').forEach(function(btn) {
        btn.addEventListener('click', async function() {
            const id = this.dataset.id;
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Analyzing...';
            try {
                const res = await fetch(`/admin/coffee-prices/${id}/run-ai`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Analysis failed');
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-robot mr-1"></i> Retry';
                }
            } catch(e) {
                alert('Connection error');
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-robot mr-1"></i> Retry';
            }
        });
    });
});
</script>
@endpush
