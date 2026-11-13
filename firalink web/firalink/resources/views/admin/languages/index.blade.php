@extends('layouts.admin')

@section('title', 'Language Manager')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item">Language Manager</div>
    </div>
@endsection

@section('main-content')
    <div class="row mb-4">
        <div class="col-lg-4 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-primary">
                    <i class="fas fa-language"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Total Languages</h4>
                    </div>
                    <div class="card-body">
                        {{ count($languages) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h4>System Languages</h4>
            <div class="card-header-action">
                <a href="{{ route('admin.languages.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> New Language
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Language</th>
                            <th>Code</th>
                            <th>Native Name</th>
                            <th>Flag</th>
                            <th>Default</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($languages as $code => $lang)
                            <tr>
                                <td><strong>{{ $lang['name'] }}</strong></td>
                                <td><code>{{ $code }}</code></td>
                                <td>{{ $lang['native'] }}</td>
                                <td style="font-size: 1.5rem;">{{ $lang['flag'] }}</td>
                                <td>
                                    @if ($code === 'en')
                                        <span class="badge badge-success">System Default</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('admin.languages.translations.edit', ['locale' => $code]) }}"
                                        class="btn btn-sm btn-info" title="Manage Translations">
                                        <i class="fas fa-edit"></i> Translate Keys
                                    </a>
                                    @if ($code !== 'en')
                                        <form action="{{ route('admin.languages.destroy', ['locale' => $code]) }}"
                                            method="POST" class="d-inline"
                                            onsubmit="return confirm('Are you sure you want to delete this language? This will delete the directory resources/lang/{{ $code }} and all its translation files permanently.');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" type="submit" title="Delete Language">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
