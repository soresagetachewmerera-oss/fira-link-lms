@extends('layouts.admin')

@section('title', 'User Details')
@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></div>
        <div class="breadcrumb-item active">{{ $user->name }}</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>User Profile Details</h4>
                    <div class="card-header-action">
                        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Users
                        </a>
                        @if($user->id !== auth()->id())
                            <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="d-inline-block ml-2" onsubmit="return confirm('Are you sure you want to permanently delete this user?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-trash"></i> Delete User
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center border-right pr-4">
                            <div class="mb-3">
                                @if($user->profile_photo)
                                    <img src="{{ asset('storage/' . $user->profile_photo) }}" alt="{{ $user->name }}" class="rounded-circle img-thumbnail" style="width: 140px; height: 140px; object-fit: cover;">
                                @else
                                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 140px; height: 140px; font-size: 48px; font-weight: bold;">
                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                    </div>
                                @endif
                            </div>
                            <h4 class="mb-1">{{ $user->name }}</h4>
                            <p class="text-muted mb-2">{{ $user->email }}</p>

                            <div class="mb-3">
                                @if($user->roles->count() > 0)
                                    @foreach($user->roles as $role)
                                        <span class="badge badge-primary px-3 py-1">{{ ucfirst($role->name) }}</span>
                                    @endforeach
                                @elseif(!empty($user->role))
                                    <span class="badge badge-secondary px-3 py-1">{{ ucfirst($user->role) }}</span>
                                @else
                                    <span class="badge badge-light px-3 py-1">Standard User</span>
                                @endif
                            </div>

                            <div class="alert {{ $user->hasVerifiedEmail() ? 'alert-success' : 'alert-warning' }} py-2 px-3 text-left small">
                                <i class="fas {{ $user->hasVerifiedEmail() ? 'fa-check-circle' : 'fa-exclamation-triangle' }} mr-1"></i>
                                Email Status: <strong>{{ $user->hasVerifiedEmail() ? 'Verified' : 'Unverified' }}</strong>
                            </div>
                        </div>

                        <div class="col-md-8 pl-4">
                            <h5 class="text-dark mb-3 border-bottom pb-2">Account Information</h5>
                            <div class="row mb-3">
                                <div class="col-sm-4 font-weight-bold text-muted">User ID:</div>
                                <div class="col-sm-8">#{{ $user->id }}</div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4 font-weight-bold text-muted">Full Name:</div>
                                <div class="col-sm-8">{{ $user->name }}</div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4 font-weight-bold text-muted">Email Address:</div>
                                <div class="col-sm-8">
                                    <a href="mailto:{{ $user->email }}">{{ $user->email }}</a>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4 font-weight-bold text-muted">Phone Number:</div>
                                <div class="col-sm-8">{{ $user->phone ?? 'Not specified' }}</div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4 font-weight-bold text-muted">Specialization / Title:</div>
                                <div class="col-sm-8">{{ $user->specialization ?? '—' }}</div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4 font-weight-bold text-muted">Approval Status:</div>
                                <div class="col-sm-8">
                                    @if($user->is_approved)
                                        <span class="badge badge-success">Approved</span>
                                    @else
                                        <span class="badge badge-warning">Pending Approval</span>
                                    @endif
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4 font-weight-bold text-muted">Bio / About:</div>
                                <div class="col-sm-8">{{ $user->bio ?? 'No biography provided.' }}</div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4 font-weight-bold text-muted">Member Since:</div>
                                <div class="col-sm-8">{{ $user->created_at->format('F d, Y - h:i A') }} ({{ $user->created_at->diffForHumans() }})</div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4 font-weight-bold text-muted">Last Updated:</div>
                                <div class="col-sm-8">{{ $user->updated_at->format('F d, Y - h:i A') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

