@extends('layouts.admin')

@section('title', 'Add System Language')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.languages.index') }}">Language Manager</a></div>
        <div class="breadcrumb-item">Add Language</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-lg-8 col-md-10 col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Add New Language</h4>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        Select a language locale to add to your platform. Once created, a directory under <code>resources/lang</code> will be initialized automatically, copying all keys from the default English files.
                    </p>

                    @if (count($availableLocales) > 0)
                        <form action="{{ route('admin.languages.store') }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label for="code">Select Language</label>
                                <select name="code" id="code" class="form-control select2 @error('code') is-invalid @enderror" required>
                                    <option value="" disabled selected>Select Language...</option>
                                    @foreach ($availableLocales as $code => $lang)
                                        <option value="{{ $code }}">
                                            {{ $lang['flag'] }} {{ $lang['name'] }} ({{ $lang['native'] }}) - [{{ $code }}]
                                        </option>
                                    @endforeach
                                </select>
                                @error('code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group text-right">
                                <a href="{{ route('admin.languages.index') }}" class="btn btn-secondary mr-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Initialize Language
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="alert alert-info">
                            All standard predefined languages are already installed.
                        </div>
                        <div class="text-right">
                            <a href="{{ route('admin.languages.index') }}" class="btn btn-secondary">Back to List</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
