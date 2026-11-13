@extends('layouts.admin')

@section('title', 'Send Notification')

@section('main-content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Send New Notification</h4>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    <form action="{{ route('admin.notifications.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label>Title</label>
                            <input type="text" name="title" class="form-control" required placeholder="Notification Title">
                        </div>
                        <div class="form-group">
                            <label>Message</label>
                            <textarea name="message" class="form-control" rows="4" required
                                placeholder="Notification content..."></textarea>
                        </div>

                        <div class="form-group">
                            <label>Target Audience</label>
                            <select name="target_audience" class="form-control" required>
                                <option value="all">All Users</option>
                                <option value="student">Students Only</option>
                                <option value="instructor">Instructors Only</option>
                            </select>
                            <small class="text-muted">Select who should receive this notification.</small>
                        </div>

                        <div class="form-group">
                            <label>Type</label>
                            <select name="type" id="type-select" class="form-control" required>
                                <option value="text">Text Only</option>
                                <option value="course">Course</option>
                                <option value="url">External URL</option>
                                <option value="image">Image</option>
                                <option value="author">Author/Instructor</option>
                            </select>
                        </div>

                        <div class="form-group d-none" id="course-group">
                            <label>Select Course</label>
                            <select name="course_id" class="form-control select2">
                                <option value="">Select a Course</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}">{{ $course->title }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group d-none" id="author-group">
                            <label>Select Instructor</label>
                            <select name="author_id" class="form-control select2">
                                <option value="">Select an Instructor</option>
                                @foreach($instructors as $instructor)
                                    <option value="{{ $instructor->id }}">{{ $instructor->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group d-none" id="url-group">
                            <label>External URL</label>
                            <input type="url" name="url" class="form-control" placeholder="https://example.com">
                        </div>

                        <div class="form-group d-none" id="image-group">
                            <label>Upload Image</label>
                            <input type="file" name="image" class="form-control">
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-lg">Send Notification</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Sent Notifications History --}}
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Sent Notifications History</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Title</th>
                                    <th>Target</th>
                                    <th>Type</th>
                                    <th>Recipients</th>
                                    <th>Sent By</th>
                                    <th>Sent At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($sentNotifications as $notification)
                                    <tr>
                                        <td>{{ $notification->id }}</td>
                                        <td>
                                            <strong>{{ \Illuminate\Support\Str::limit($notification->title, 30) }}</strong>
                                            <br>
                                            <small class="text-muted">{{ \Illuminate\Support\Str::limit($notification->message, 50) }}</small>
                                        </td>
                                        <td>
                                            @if($notification->target_audience === 'all')
                                                <span class="badge badge-primary">All Users</span>
                                            @elseif($notification->target_audience === 'student')
                                                <span class="badge badge-info">Students</span>
                                            @else
                                                <span class="badge badge-warning">Instructors</span>
                                            @endif
                                        </td>
                                        <td>{{ ucfirst($notification->type) }}</td>
                                        <td><span class="badge badge-secondary">{{ $notification->recipient_count }}</span></td>
                                        <td>{{ $notification->sender->name ?? 'System' }}</td>
                                        <td>{{ $notification->created_at->format('M d, Y H:i') }}</td>
                                        <td>
                                            <form action="{{ route('admin.notifications.resend', $notification->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to resend this notification?');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success" title="Resend Notification">
                                                    <i class="fas fa-redo"></i> Resend
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">No notifications sent yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $sentNotifications->links('vendor.pagination.stisla-default') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            $('#type-select').change(function () {
                var type = $(this).val();
                $('#course-group, #author-group, #url-group, #image-group').addClass('d-none');
                $('#course-group select, #author-group select, #url-group input').prop('required', false);

                if (type == 'course') {
                    $('#course-group').removeClass('d-none');
                    $('#course-group select').prop('required', true);
                }
                if (type == 'author') {
                    $('#author-group').removeClass('d-none');
                    $('#author-group select').prop('required', true);
                }
                if (type == 'url') {
                    $('#url-group').removeClass('d-none');
                    $('#url-group input').prop('required', true);
                }
                if (type == 'image') {
                    $('#image-group').removeClass('d-none');
                }
            });
        });
    </script>
@endpush