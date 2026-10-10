@extends('layouts.admin')

@section('title', 'All Students')
@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item">Students</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <form method="GET" action="{{ route('admin.students.index') }}">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search"
                                        placeholder="Search students..." value="{{ request('search') }}">
                                    <div class="input-group-btn">
                                        <button class="btn btn-primary" type="submit">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-6 text-right">
                            <span class="badge badge-info">Total: {{ $totalStudents }} students</span>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped" id="students-table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Enrollments</th>
                                    <th>Completed</th>
                                    <th>Total Spent</th>
                                    <th>Joined Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($students as $student)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if ($student->profile_photo)
                                                    <img src="{{ asset('storage/' . $student->profile_photo) }}"
                                                        alt="{{ $student->name }}" class="mr-3 rounded-circle"
                                                        style="width: 50px; height: 50px; object-fit: cover;">
                                                @else
                                                    <div class="mr-3 bg-secondary rounded-circle d-flex align-items-center justify-content-center"
                                                        style="width: 50px; height: 50px;">
                                                        <i class="fas fa-user text-white"></i>
                                                    </div>
                                                @endif
                                                <div>
                                                    <h6 class="mb-0">{{ $student->name }}</h6>
                                                    <small class="text-muted">{{ $student->email }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-primary">{{ $student->total_enrollments ?? 0 }}</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-success">{{ $student->completed_courses ?? 0 }}</span>
                                        </td>
                                        <td>
                                            <strong>{{ currency_format($student->total_spent ?? 0) }}</strong>
                                        </td>
                                        <td>
                                            <small>{{ $student->created_at->format('M d, Y') }}</small>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <form action="{{ route('admin.impersonate.student', $student->id) }}"
                                                    method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-primary"
                                                        title="Login as Student">
                                                        <i class="fas fa-user-secret"></i>
                                                    </button>
                                                </form>
                                                <a href="{{ route('admin.students.edit', $student->id) }}"
                                                    class="btn btn-sm btn-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="{{ route('admin.students.show', $student->id) }}"
                                                    class="btn btn-sm btn-info" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">
                                            <div class="py-4">
                                                <i class="fas fa-user-graduate fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">No students found.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-4">
                        {{ $students->links('vendor.pagination.stisla-default') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
