@extends('layouts.admin')

@section('title', 'Contact Details')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Settings</a></div>
        <div class="breadcrumb-item active">Contact Details</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Primary Contact Information</h4>
                    <a href="{{ route('admin.settings.index') }}" class="btn btn-secondary"><i
                            class="fas fa-arrow-left"></i> Back to Settings</a>
                </div>
                <form action="{{ route('admin.settings.contact.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="form-group">
                            <label for="support_email">Support Email</label>
                            <input type="email" name="support_email" id="support_email"
                                class="form-control @error('support_email') is-invalid @enderror"
                                value="{{ old('support_email', $settings['support_email'] ?? '') }}" required>
                            @error('support_email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="support_phone">Support Phone</label>
                            <input type="text" name="support_phone" id="support_phone"
                                class="form-control @error('support_phone') is-invalid @enderror"
                                value="{{ old('support_phone', $settings['support_phone'] ?? '') }}">
                            @error('support_phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="address_line_1">Address Line 1</label>
                            <input type="text" name="address_line_1" id="address_line_1"
                                class="form-control @error('address_line_1') is-invalid @enderror"
                                value="{{ old('address_line_1', $settings['address_line_1'] ?? '') }}">
                            @error('address_line_1')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="address_line_2">Address Line 2</label>
                            <input type="text" name="address_line_2" id="address_line_2"
                                class="form-control @error('address_line_2') is-invalid @enderror"
                                value="{{ old('address_line_2', $settings['address_line_2'] ?? '') }}">
                            @error('address_line_2')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="city">City</label>
                                <input type="text" name="city" id="city"
                                    class="form-control @error('city') is-invalid @enderror"
                                    value="{{ old('city', $settings['city'] ?? '') }}">
                                @error('city')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label for="country">Country</label>
                                <input type="text" name="country" id="country"
                                    class="form-control @error('country') is-invalid @enderror"
                                    value="{{ old('country', $settings['country'] ?? '') }}">
                                @error('country')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-primary">Save Contact Info</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
