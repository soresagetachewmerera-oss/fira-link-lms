@if (session('impersonating_admin_id'))
    @php
        $currentUser = auth()->user();
        $admin = \App\Models\User::find(session('impersonating_admin_id'));
        $isInstructor = session('impersonating_instructor_id');
        $isStudent = session('impersonating_student_id');
        $roleType = $isInstructor ? 'Instructor' : ($isStudent ? 'Student' : 'User');
    @endphp
    <div class="alert alert-warning alert-dismissible fade show mb-0" role="alert"
        style="border-radius: 0; border-left: 4px solid #ffc107; background-color: #fff3cd; margin-bottom: 0 !important;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8 col-sm-12">
                    <strong><i class="fas fa-user-secret"></i> Secret Login Active:</strong>
                    You are currently viewing as <strong>{{ $currentUser->name }}</strong> ({{ $roleType }}).
                    @if ($admin)
                        <br><small>Original admin: <strong>{{ $admin->name }}</strong></small>
                    @endif
                </div>
                <div class="col-md-4 col-sm-12 text-md-right text-left mt-2 mt-md-0 text-end">
                    <form action="{{ route('admin.stop-impersonating') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-warning"
                            style="background-color: #ffc107; border-color: #ffc107; color: #000;">
                            <i class="fas fa-arrow-left"></i> Return to Admin Dashboard
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endif
