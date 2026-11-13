@extends('layouts.instructor')

@section('content')
    <section class="section-padding section-bg fix">
        <div class="container">
            <div class="dashboard-layout">
                @include('instructor.partials.sidebar')

                <!-- Main Content Start -->
                <div class="dashboard-main">
                    <!-- Page Header -->
                    <div class="section-title-area wow fadeInUp section-title-area-spacing">
                        <div class="section-title">
                            <h6>Course Forums</h6>
                            <h2>Manage Discussions</h2>
                            <p>Interact with your students, answer questions, and post announcements for your courses.</p>
                        </div>
                        <div class="dashboard-header-actions">
                            <button class="theme-btn" type="button" data-bs-toggle="collapse" data-bs-target="#newAnnouncementCollapse" aria-expanded="false" aria-controls="newAnnouncementCollapse">
                                Post Announcement
                                <i class="fa-solid fa-bullhorn"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Post Announcement Collapse Form -->
                    <div class="collapse mb-4" id="newAnnouncementCollapse">
                        <div class="card card-body border-0 shadow-sm" style="border-radius: 15px; padding: 24px;">
                            <h4 class="mb-3" style="font-weight: 600;">Create a Forum Thread / Announcement</h4>
                            <form id="new-announcement-form" method="POST" action="">
                                @csrf
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="announcement-course-id" class="form-label" style="font-size: 13px; font-weight: 600;">Select Course <span class="text-danger">*</span></label>
                                        <select class="form-select" id="announcement-course-id" required style="border-radius: 8px; font-size: 14px; height: auto; padding: 10px;">
                                            <option value="" disabled selected>-- Select Course --</option>
                                            @foreach ($courses as $c)
                                                <option value="{{ $c->id }}">{{ $c->title }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="announcement-title" class="form-label" style="font-size: 13px; font-weight: 600;">Title <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="announcement-title" name="title" required placeholder="Announcement title" style="border-radius: 8px; font-size: 14px; padding: 10px;">
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label for="announcement-content" class="form-label" style="font-size: 13px; font-weight: 600;">Content <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="announcement-content" name="content" rows="4" required placeholder="Write the announcement description..." style="border-radius: 8px; font-size: 14px;"></textarea>
                                    </div>
                                    <div class="col-12 mb-3 d-flex gap-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="is_announcement" id="check_is_announcement" value="1" checked>
                                            <label class="form-check-label" for="check_is_announcement" style="font-size: 13px;">Flag as Announcement</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="is_pinned" id="check_is_pinned" value="1">
                                            <label class="form-check-label" for="check_is_pinned" style="font-size: 13px;">Pin to Top</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="theme-btn" style="padding: 10px 24px; font-size: 13px; border-radius: 8px;">Post Thread</button>
                                        <button type="button" class="btn btn-light ms-2" data-bs-toggle="collapse" data-bs-target="#newAnnouncementCollapse" style="padding: 10px 24px; font-size: 13px; border-radius: 8px; border: 1px solid #dee2e6;">Cancel</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Alerts -->
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 8px;">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 8px;">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <!-- Filters Section -->
                    <div class="card border-0 shadow-sm mb-4" style="border-radius: 15px; padding: 20px;">
                        <form method="GET" action="{{ route('instructor.discussions.index') }}">
                            <div class="row g-3 align-items-center">
                                <div class="col-md-5">
                                    <select class="form-select" name="course_id" onchange="this.form.submit()" style="border-radius: 8px; font-size: 13px; height: auto; padding: 10px;">
                                        <option value="">All Courses Discussions</option>
                                        @foreach ($courses as $c)
                                            <option value="{{ $c->id }}" {{ $selectedCourseId == $c->id ? 'selected' : '' }}>{{ $c->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="search" value="{{ $search }}" placeholder="Search discussions..." style="border-radius: 8px 0 0 8px; font-size: 13px; padding: 10px;">
                                        <button class="btn btn-outline-secondary" type="submit" style="border-radius: 0 8px 8px 0; border-color: #ced4da; background: #f8f9fa;">
                                            <i class="fa-solid fa-magnifying-glass"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <a href="{{ route('instructor.discussions.index') }}" class="btn btn-light w-100" style="border-radius: 8px; font-size: 13px; border: 1px solid #dee2e6; padding: 10px 0;">Reset</a>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Discussions Table / List -->
                    <div class="card border-0 shadow-sm" style="border-radius: 15px; overflow: hidden;">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0" style="min-width: 800px;">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Discussion Info</th>
                                        <th>Course</th>
                                        <th>Replies</th>
                                        <th>Likes</th>
                                        <th>Badges</th>
                                        <th class="text-end pe-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($discussions as $d)
                                        <tr>
                                            <td class="ps-4 py-3">
                                                <div class="d-flex align-items-center gap-3">
                                                    <img src="{{ $d->user->profile_photo ? asset('storage/' . $d->user->profile_photo) : asset('assets/front/img/home-1/courses/client-01.png') }}" alt="User" class="rounded-circle" style="width: 36px; height: 36px; object-fit: cover; border: 1px solid #dee2e6;">
                                                    <div>
                                                        <h6 class="mb-0" style="font-weight: 600;">
                                                            <a href="{{ route('instructor.discussions.show', $d->id) }}" class="text-dark text-decoration-none hover-primary">{{ $d->title }}</a>
                                                        </h6>
                                                        <small class="text-muted">By {{ $d->user->name }} • {{ $d->created_at->diffForHumans() }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <small class="text-dark" style="font-weight: 500;">{{ Str::limit($d->course->title, 25) }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border" style="font-size: 11px; padding: 4px 10px;">{{ $d->replies_count }}</span>
                                            </td>
                                            <td>
                                                <span class="text-muted"><i class="fa-regular fa-thumbs-up me-1"></i> {{ $d->likes_count }}</span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1 flex-wrap">
                                                    @if ($d->is_pinned)
                                                        <span class="badge bg-warning text-dark" style="font-size: 10px;">Pinned</span>
                                                    @endif
                                                    @if ($d->is_announcement)
                                                        <span class="badge bg-primary text-white" style="font-size: 10px;">Announcement</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-end pe-4">
                                                <div class="d-flex justify-content-end gap-2">
                                                    <a href="{{ route('instructor.discussions.show', $d->id) }}" class="btn btn-outline-primary btn-sm" style="border-radius: 6px; font-size: 11px; font-weight: 600; padding: 4px 12px;">View</a>
                                                    
                                                    <form method="POST" action="{{ route('instructor.discussions.destroy', $d->id) }}" onsubmit="return confirm('Delete this thread and all replies permanently?')" style="display:inline;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-outline-danger btn-sm" style="border-radius: 6px; font-size: 11px; padding: 4px 10px;"><i class="fa-regular fa-trash-can"></i></button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-5">
                                                <p class="text-muted mb-0">No discussion threads found.</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center mt-4">
                        {!! $discussions->appends(['course_id' => $selectedCourseId, 'search' => $search])->links('pagination::bootstrap-4') !!}
                    </div>
                </div>
                <!-- Main Content End -->
            </div>
        </div>
    </section>

    @push('scripts')
        <script>
            // Set dynamic form action on course select
            var courseSelect = document.getElementById('announcement-course-id');
            var form = document.getElementById('new-announcement-form');
            
            function updateFormAction() {
                var val = courseSelect.value;
                if (val) {
                    form.action = "{{ route('instructor.discussions.store', ['courseId' => ':courseId']) }}".replace(':courseId', val);
                } else {
                    form.action = "";
                }
            }

            courseSelect.addEventListener('change', updateFormAction);
            updateFormAction();
        </script>
    @endpush
@endsection
