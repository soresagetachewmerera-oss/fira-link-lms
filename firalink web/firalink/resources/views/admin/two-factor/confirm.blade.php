@extends('layouts.admin')

@section('title', __('security.confirm.title'))

@section('main-content')
<div class="row justify-content-center">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
                <h1 class="h5">{{ __('security.confirm.title') }}</h1>
                @error('fresh_auth')<div class="alert alert-info" role="status">{{ $message }}</div>@enderror
                <p>{{ __('security.confirm.intro', ['minutes' => $minutes]) }}</p>
                <form method="POST" action="{{ route('staff.confirm.store') }}">
                    @csrf
                    <div class="form-group">
                        <label for="code">{{ __('security.confirm.code') }}</label>
                        <input id="code" name="code" class="form-control @error('code') is-invalid @enderror" inputmode="numeric" autocomplete="one-time-code" maxlength="32" autofocus>
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label for="password">{{ __('security.confirm.password') }}</label>
                        <input id="password" name="password" type="password" class="form-control" autocomplete="current-password" maxlength="200">
                    </div>
                    <button type="submit" class="btn btn-primary">{{ __('security.confirm.submit') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
