@extends('layouts.admin')

@section('title', 'All Users')
@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item">Users</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>User Management</h4>
                    <div class="card-header-action">
                        <span class="badge badge-primary">{{ $users->total() }} Total Users</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="users-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>User</th>
                                    <th>Role</th>
                                    <th>Phone</th>
                                    <th>Email Verified</th>
                                    <th>Registered</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $user)
                                    <tr>
                                        <td>{{ $user->id }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <figure class="avatar mr-2 bg-primary text-white">
                                                    @if($user->profile_photo)
                                                        <img src="{{ asset('storage/' . $user->profile_photo) }}" alt="{{ $user->name }}">
                                                    @else
                                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                                    @endif
                                                </figure>
                                                <div>
                                                    <div class="font-weight-bold">{{ $user->name }}</div>
                                                    <div class="small text-muted">{{ $user->email }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if($user->roles->count() > 0)
                                                @foreach($user->roles as $role)
                                                    <span class="badge badge-info">{{ ucfirst($role->name) }}</span>
                                                @endforeach
                                            @elseif(!empty($user->role))
                                                <span class="badge badge-secondary">{{ ucfirst($user->role) }}</span>
                                            @else
                                                <span class="badge badge-light">User</span>
                                            @endif
                                        </td>
                                        <td>{{ $user->phone ?? '—' }}</td>
                                        <td>
                                            @if($user->hasVerifiedEmail())
                                                <span class="badge badge-success"><i class="fas fa-check-circle"></i> Verified</span>
                                            @else
                                                <span class="badge badge-warning"><i class="fas fa-clock"></i> Unverified</span>
                                            @endif
                                        </td>
                                        <td>{{ $user->created_at->format('M d, Y') }}</td>
                                        <td class="text-right">
                                            <a href="{{ route('admin.users.show', $user->id) }}" class="btn btn-sm btn-info" title="View Details">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            @if($user->id !== auth()->id())
                                                <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Are you sure you want to permanently delete this user? This action cannot be undone.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete User">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            <i class="fas fa-users-slash fa-2x mb-2 d-block"></i> No users found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

