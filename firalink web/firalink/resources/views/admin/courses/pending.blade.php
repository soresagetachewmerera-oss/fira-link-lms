@extends('layouts.admin')

@section('title', 'Pending Courses Approval')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.courses.index') }}">Courses</a></div>
        <div class="breadcrumb-item active">Pending Approval</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Courses Awaiting Moderation</h4>
                    <div class="card-header-action">
                        <span class="badge badge-warning mr-2">{{ $courses->count() }} Pending</span>
                        <a href="{{ route('admin.courses.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> All Courses
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($courses->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Course</th>
                                        <th>Instructor</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Submitted</th>
                                        <th class="text-right">Moderation Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($courses as $course)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    @if($course->featured_image)
                                                        <img src="{{ asset('storage/' . $course->featured_image) }}" alt="{{ $course->title }}" class="rounded mr-2" style="width: 60px; height: 42px; object-fit: cover;">
                                                    @else
                                                        <div class="bg-secondary rounded mr-2 d-flex align-items-center justify-content-center text-white" style="width: 60px; height: 42px;">
                                                            <i class="fas fa-book"></i>
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <a href="{{ route('admin.courses.show', $course->id) }}" class="font-weight-bold text-dark">
                                                            {{ $course->title }}
                                                        </a>
                                                        <div class="small text-muted">ID: #{{ $course->id }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                @if($course->instructor)
                                                    <div>{{ $course->instructor->name }}</div>
                                                    <div class="small text-muted">{{ $course->instructor->email }}</div>
                                                @else
                                                    <span class="text-muted">Unassigned</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge badge-light">{{ $course->category ?? 'General' }}</span>
                                            </td>
                                            <td>
                                                @if($course->pricing_model === 'free' || (float)$course->price == 0)
                                                    <span class="badge badge-success">Free</span>
                                                @else
                                                    <span class="font-weight-bold">{{ currency_format($course->price) }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $course->created_at->format('M d, Y') }}</td>
                                            <td class="text-right">
                                                <a href="{{ route('admin.courses.show', $course->id) }}" class="btn btn-sm btn-info mr-1" title="Review Content">
                                                    <i class="fas fa-eye"></i> Review
                                                </a>

                                                <form action="{{ route('admin.courses.approve', $course->id) }}" method="POST" class="d-inline-block mr-1">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success" title="Approve & Publish" onclick="return confirm('Approve this course and make it live for students?');">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                </form>

                                                <button type="button" class="btn btn-sm btn-danger" data-toggle="modal" data-target="#rejectModal{{ $course->id }}" title="Reject Course">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>

                                                <!-- Reject Modal -->
                                                <div class="modal fade" id="rejectModal{{ $course->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                                    <div class="modal-dialog text-left" role="document">
                                                        <div class="modal-content">
                                                            <form action="{{ route('admin.courses.reject', $course->id) }}" method="POST">
                                                                @csrf
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Reject Course "{{ $course->title }}"</h5>
                                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                        <span aria-hidden="true">&times;</span>
                                                                    </button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <div class="form-group">
                                                                        <label for="reason{{ $course->id }}">Reason for rejection (sent to instructor):</label>
                                                                        <textarea name="reason" id="reason{{ $course->id }}" class="form-control" rows="4" placeholder="Explain what changes are needed before this course can be approved..." required></textarea>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                                    <button type="submit" class="btn btn-danger">Confirm Rejection</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-check-circle fa-3x text-success mb-3 d-block"></i>
                            <h5>No Pending Courses</h5>
                            <p class="text-muted">All submitted courses have been reviewed and moderated.</p>
                            <a href="{{ route('admin.courses.index') }}" class="btn btn-primary mt-2">
                                View Published Courses
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

