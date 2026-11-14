@extends('layouts.admin')

@section('title', __('verification.admin.two_factor_title'))

@section('main-content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <p>{{ __('verification.admin.two_factor_intro') }}</p>
                <div class="text-center my-3">{!! $qrSvg !!}</div>
                <p class="text-muted small">{{ __('verification.admin.two_factor_secret', ['key' => $secret]) }}</p>
                <form method="POST" action="{{ route('staff.two-factor.confirm') }}">
                    @csrf
                    <div class="form-group">
                        <label for="code">{{ __('verification.admin.two_factor_code') }}</label>
                        <input id="code" name="code" class="form-control @error('code') is-invalid @enderror" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required autofocus>
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-primary">{{ __('verification.admin.two_factor_confirm') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
