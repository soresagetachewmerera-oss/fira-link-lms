@extends('layouts.admin')

@section('title', __('verification.admin.two_factor_title'))

@section('main-content')
<div class="row justify-content-center">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
                <p>{{ __('verification.admin.two_factor_challenge') }}</p>
                <form method="POST" action="{{ route('staff.two-factor.verify') }}">
                    @csrf
                    <div class="form-group">
                        <label for="code">{{ __('verification.admin.two_factor_code') }}</label>
                        <input id="code" name="code" class="form-control @error('code') is-invalid @enderror" autocomplete="one-time-code" maxlength="32" required autofocus>
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-primary">{{ __('verification.admin.two_factor_continue') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
