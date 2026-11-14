@extends('layouts.admin')

@section('title', __('verification.admin.recovery_title'))

@section('main-content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <p>{{ __('verification.admin.recovery_intro') }}</p>
                <ul class="list-unstyled" style="font-family: monospace; font-size: 16px;">
                    @foreach($codes as $code)
                        <li>{{ $code }}</li>
                    @endforeach
                </ul>
                <a href="{{ $next }}" class="btn btn-primary">{{ __('verification.admin.recovery_done') }}</a>
            </div>
        </div>
    </div>
</div>
@endsection
