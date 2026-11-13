@extends('layouts.admin')

@section('title', 'Custom Pages')

@section('breadcrumb')
    <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
    <div class="breadcrumb-item">Custom Pages</div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Custom Pages</h4>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">
                        Manage the static content displayed on the site such as Privacy Policy, Terms &amp; Conditions, and
                        GDPR Compliance.
                    </p>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Description</th>
                                    <th>Slug</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pages as $page)
                                    <tr>
                                        <td>{{ $page->title }}</td>
                                        <td>{{ $page->description ?? '—' }}</td>
                                        <td><code>{{ $page->slug }}</code></td>
                                        <td class="text-right">
                                            <a href="{{ route('admin.custom-pages.edit', $page) }}"
                                                class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit mr-1"></i> Edit
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            <i class="fas fa-file-alt fa-2x mb-2"></i>
                                            <p class="mb-0">No custom pages found. Use the seeder or create entries
                                                manually.</p>
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
@endsection
