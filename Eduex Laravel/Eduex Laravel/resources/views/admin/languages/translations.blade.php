@extends('layouts.admin')

@section('title', 'Manage Translations - ' . $languageDetails['name'])

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.languages.index') }}">Language Manager</a></div>
        <div class="breadcrumb-item">Translations [{{ $locale }}]</div>
    </div>
@endsection

@section('main-content')
    <div class="row mb-4">
        <div class="col-12">
            <div class="card card-hero bg-primary text-white">
                <div class="card-inner">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center">
                                <span style="font-size: 3.5rem;" class="mr-3">{{ $languageDetails['flag'] }}</span>
                                <div>
                                    <h2 class="mb-1 text-white">{{ $languageDetails['name'] }} Translations</h2>
                                    <p class="mb-0">
                                        Translate system keys for this language. Native name: <strong>{{ $languageDetails['native'] }}</strong> | Locale code: <code>{{ $locale }}</code>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-md-right mt-3 mt-md-0">
                            <a href="{{ route('admin.languages.index') }}" class="btn btn-outline-white">
                                <i class="fas fa-arrow-left"></i> Back to Languages
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Translation Groups list -->
        <div class="col-lg-3 col-md-4">
            <div class="card">
                <div class="card-header">
                    <h4>Translation Groups</h4>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @foreach ($groups as $groupFile)
                            <a href="{{ route('admin.languages.translations.edit', ['locale' => $locale, 'group' => $groupFile]) }}"
                                class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $currentGroup === $groupFile ? 'active text-white' : '' }}">
                                <span><i class="fas fa-file-alt mr-2"></i>{{ ucfirst($groupFile) }}</span>
                                <span class="badge {{ $currentGroup === $groupFile ? 'badge-light text-primary' : 'badge-primary' }} badge-pill">
                                    {{ $groupFile === $currentGroup ? $translations->total() : count(trans($groupFile, [], $locale)) }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Translation Grid -->
        <div class="col-lg-9 col-md-8">
            <div class="card">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                    <h4>Keys in group: <code>{{ $currentGroup }}.php</code></h4>
                    <div class="card-header-action d-flex">
                        <button type="button" class="btn btn-success" data-toggle="modal" data-target="#addKeyModal">
                            <i class="fas fa-plus"></i> Add New Key
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Search Filter Form -->
                    <form method="GET" action="{{ route('admin.languages.translations.edit', ['locale' => $locale]) }}" class="mb-4">
                        <input type="hidden" name="group" value="{{ $currentGroup }}">
                        <div class="form-row">
                            <div class="col-md-9 mb-2">
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control"
                                        placeholder="Search for translation keys or content..." value="{{ $search }}">
                                    <div class="input-group-append">
                                        <button class="btn btn-primary" type="submit">
                                            <i class="fas fa-search"></i> Search
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-2">
                                @if ($search)
                                    <a href="{{ route('admin.languages.translations.edit', ['locale' => $locale, 'group' => $currentGroup]) }}" class="btn btn-secondary btn-block">
                                        <i class="fas fa-times"></i> Clear Search
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>

                    <!-- Translations Form -->
                    @if ($translations->count() > 0)
                        <form action="{{ route('admin.languages.translations.update', ['locale' => $locale]) }}" method="POST">
                            @csrf
                            <input type="hidden" name="group" value="{{ $currentGroup }}">
                            <input type="hidden" name="page" value="{{ $translations->currentPage() }}">
                            <input type="hidden" name="search" value="{{ $search }}">

                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th style="width: 35%;">Key</th>
                                            <th style="width: 65%;">Translation Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($translations as $key => $value)
                                            <tr>
                                                <td class="align-middle">
                                                    <span class="d-block font-weight-bold text-dark">{{ $key }}</span>
                                                    <small class="text-muted"><code>{{ $currentGroup }}.{{ $key }}</code></small>
                                                </td>
                                                <td>
                                                    <textarea name="values[{{ $key }}]" class="form-control" rows="2" style="resize: vertical;">{{ $value }}</textarea>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="card-footer p-0 pt-3 d-flex flex-wrap justify-content-between align-items-center">
                                <div>
                                    Showing {{ $translations->firstItem() ?? 0 }} to {{ $translations->lastItem() ?? 0 }} of
                                    {{ $translations->total() }} keys
                                </div>
                                <div class="my-2">
                                    {{ $translations->appends(request()->query())->links('vendor.pagination.stisla-default') }}
                                </div>
                                <div class="my-2 text-right">
                                    <button type="submit" class="btn btn-lg btn-primary shadow-primary">
                                        <i class="fas fa-save"></i> Save Current Page
                                    </button>
                                </div>
                            </div>
                        </form>
                    @else
                        <div class="text-center py-5">
                            <i class="far fa-folder-open text-muted mb-3" style="font-size: 3rem;"></i>
                            <p class="text-muted mb-0">No translation keys found matching your criteria.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Add Key Modal -->
    <div class="modal fade" id="addKeyModal" tabindex="-1" role="dialog" aria-labelledby="addKeyModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('admin.languages.translations.add-key', ['locale' => $locale]) }}" method="POST">
                    @csrf
                    <input type="hidden" name="group" value="{{ $currentGroup }}">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addKeyModalLabel">Add Translation Key to <code>{{ $currentGroup }}.php</code></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info py-2">
                            <small><i class="fas fa-info-circle mr-1"></i> Adding a new key will automatically insert it into all system languages to keep them in sync.</small>
                        </div>

                        <div class="form-group">
                            <label for="new_key">Translation Key</label>
                            <input type="text" name="key" id="new_key" class="form-control" placeholder="e.g. welcome_back_message" required pattern="^[a-zA-Z0-9_\-\.]+$">
                            <small class="form-text text-muted">Use lowercase, underscores, or dots (e.g. <code>title_landing_page</code>)</small>
                        </div>

                        <div class="form-group">
                            <label for="new_value">Value (Default / English)</label>
                            <textarea name="value" id="new_value" class="form-control" rows="3" placeholder="Enter translation text..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Translation Key</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
