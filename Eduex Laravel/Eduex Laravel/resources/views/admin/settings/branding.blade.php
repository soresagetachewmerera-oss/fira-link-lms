@extends('layouts.admin')

@section('title', 'Branding & Assets')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Settings</a></div>
        <div class="breadcrumb-item active">Branding & Assets</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Brand Identity</h4>
                    <a href="{{ route('admin.settings.index') }}" class="btn btn-secondary"><i
                            class="fas fa-arrow-left"></i> Back to Settings</a>
                </div>
                @php use Illuminate\Support\Facades\Storage; @endphp
                <form action="{{ route('admin.settings.branding.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="form-group">
                            <label for="primary_logo">Primary Logo</label>
                            <div class="custom-file">
                                <input type="file" name="primary_logo" id="primary_logo"
                                    class="custom-file-input @error('primary_logo') is-invalid @enderror" accept="image/*">
                                <label class="custom-file-label" for="primary_logo">Choose logo file</label>
                                @error('primary_logo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            @if (!empty($settings['primary_logo_path']))
                                <div class="mt-3">
                                    <small class="text-muted d-block">Current logo</small>
                                    <img src="{{ Storage::disk('public')->exists($settings['primary_logo_path']) ? Storage::url($settings['primary_logo_path']) : asset($settings['primary_logo_path']) }}"
                                        alt="Current Logo" class="img-fluid rounded border" style="max-height: 80px;">
                                </div>
                            @endif
                        </div>
                        <div class="form-group">
                            <label for="dark_logo">Dark Logo</label>
                            <div class="custom-file">
                                <input type="file" name="dark_logo" id="dark_logo"
                                    class="custom-file-input @error('dark_logo') is-invalid @enderror" accept="image/*">
                                <label class="custom-file-label" for="dark_logo">Choose dark logo file</label>
                                @error('dark_logo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            @if (!empty($settings['dark_logo_path']))
                                <div class="mt-3">
                                    <small class="text-muted d-block">Current dark logo</small>
                                    <img src="{{ Storage::disk('public')->exists($settings['dark_logo_path']) ? Storage::url($settings['dark_logo_path']) : asset($settings['dark_logo_path']) }}"
                                        alt="Current Dark Logo" class="img-fluid rounded border" style="max-height: 80px;">
                                </div>
                            @endif
                        </div>
                        <div class="form-group">
                            <label for="favicon">Favicon</label>
                            <div class="custom-file">
                                <input type="file" name="favicon" id="favicon"
                                    class="custom-file-input @error('favicon') is-invalid @enderror" accept="image/*">
                                <label class="custom-file-label" for="favicon">Choose favicon file</label>
                                @error('favicon')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            @if (!empty($settings['favicon_path']))
                                <div class="mt-3">
                                    <small class="text-muted d-block">Current favicon</small>
                                    <img src="{{ Storage::disk('public')->exists($settings['favicon_path']) ? Storage::url($settings['favicon_path']) : asset($settings['favicon_path']) }}"
                                        alt="Current Favicon" class="img-fluid rounded border" style="max-height: 40px;">
                                </div>
                            @endif
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="primary_color">Primary Color</label>
                                <input type="text" name="primary_color" id="primary_color"
                                    class="form-control @error('primary_color') is-invalid @enderror"
                                    value="{{ old('primary_color', $settings['primary_color'] ?? '#6558f5') }}" required>
                                @error('primary_color')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label for="secondary_color">Secondary Color</label>
                                <input type="text" name="secondary_color" id="secondary_color"
                                    class="form-control @error('secondary_color') is-invalid @enderror"
                                    value="{{ old('secondary_color', $settings['secondary_color'] ?? '#f59e0b') }}"
                                    required>
                                @error('secondary_color')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-primary">Save Branding</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
