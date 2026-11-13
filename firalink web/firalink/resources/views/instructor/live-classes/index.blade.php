@extends('layouts.instructor')

@section('content')
<section class="section-padding section-bg fix">
    <div class="container">
        <div class="dashboard-layout">
            @include('instructor.partials.sidebar')

            <div class="dashboard-main">
                <!-- Page Header -->
                <div class="dashboard-header wow fadeInUp">
                    <div class="section-title-area text-left" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                        <div class="section-title">
                            <h6>Live Classes Module</h6>
                            <h2>Manage Live Classes</h2>
                        </div>
                        <div class="dashboard-header-actions mb-4">
                            <a href="{{ route('instructor.live-classes.create') }}" class="theme-btn style-2">
                                <i class="fa-solid fa-plus"></i> Schedule Live Class
                            </a>
                        </div>
                    </div>
                </div>

                @if(session('success'))
                    <div class="alert alert-success mt-3" style="background-color: #dcfce7; border-color: #bbf7d0; color: #15803d; padding: 15px; border-radius: 6px;">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger mt-3" style="background-color: #fee2e2; border-color: #fecaca; color: #b91c1c; padding: 15px; border-radius: 6px;">
                        {{ session('error') }}
                    </div>
                @endif

                <!-- Live Classes Table -->
                <div class="dashboard-courses-section wow fadeInUp" data-wow-delay="0.1s" style="margin-top: 30px;">
                    @if ($liveClasses->count())
                        <div class="transactions-table-wrapper">
                            <table class="students-table">
                                <thead>
                                    <tr>
                                        <th>Course & Lesson</th>
                                        <th>Title / Topic</th>
                                        <th>Scheduled Time</th>
                                        <th>Duration</th>
                                        <th>Status</th>
                                        <th class="text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($liveClasses as $liveClass)
                                        <tr>
                                            <td>
                                                <div style="font-size: 14px; font-weight: 600; color: #1e293b;">
                                                    {{ $liveClass->course->title }}
                                                </div>
                                                <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                                                    Lesson: {{ $liveClass->lesson ? $liveClass->lesson->title : 'N/A' }}
                                                </div>
                                            </td>
                                            <td>
                                                <strong style="color: #6C5CE7; font-size: 14px;">{{ $liveClass->title }}</strong>
                                                @if($liveClass->meeting_id)
                                                    <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">Meeting ID: {{ $liveClass->meeting_id }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                <span style="font-size: 13px; font-weight: 500; display: block; color: #334155;">
                                                    {{ $liveClass->scheduled_at->format('M d, Y @ h:i A') }}
                                                </span>
                                            </td>
                                            <td>
                                                <span style="font-size: 13px; color: #475569;">
                                                    {{ $liveClass->duration_minutes }} mins
                                                </span>
                                            </td>
                                            <td>
                                                @if($liveClass->status === 'scheduled')
                                                    <span class="badge" style="background-color: #e0f2fe; color: #0369a1; padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                                                        Scheduled
                                                    </span>
                                                @elseif($liveClass->status === 'live')
                                                    <span class="badge" style="background-color: #fef08a; color: #854d0e; padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: 600; animation: blinker 1.5s linear infinite;">
                                                        ● Live Now
                                                    </span>
                                                @elseif($liveClass->status === 'ended')
                                                    <span class="badge" style="background-color: #f1f5f9; color: #475569; padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                                                        Ended
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-right">
                                                <div style="display: flex; gap: 8px; justify-content: flex-end; align-items: center;">
                                                    @if($liveClass->status === 'scheduled')
                                                        <form action="{{ route('instructor.live-classes.start', $liveClass->id) }}" method="POST" style="display: inline;">
                                                            @csrf
                                                            <button type="submit" class="theme-btn py-2 px-3" style="font-size: 12px; border-radius: 4px; background-color: #10b981; border: none; color: white;">
                                                                <i class="fa-solid fa-play"></i> Start
                                                            </button>
                                                        </form>
                                                    @elseif($liveClass->status === 'live')
                                                        <button type="button" class="theme-btn py-2 px-3 btn-end-session" data-id="{{ $liveClass->id }}" data-title="{{ $liveClass->title }}" style="font-size: 12px; border-radius: 4px; background-color: #ef4444; border: none; color: white;">
                                                            <i class="fa-solid fa-stop"></i> End Session
                                                        </button>
                                                    @elseif($liveClass->status === 'ended' && ($liveClass->recording_url || $liveClass->recording_path))
                                                        <a href="{{ $liveClass->recording_url ?: asset('storage/' . $liveClass->recording_path) }}" target="_blank" class="theme-btn py-2 px-3" style="font-size: 12px; border-radius: 4px; background-color: #6c757d; border: none; color: white;">
                                                            <i class="fa-solid fa-circle-play"></i> View Recording
                                                        </a>
                                                    @endif

                                                    @if($liveClass->status !== 'ended')
                                                        <a href="{{ route('instructor.live-classes.edit', $liveClass->id) }}" class="btn-action-edit" style="color: #6C5CE7; font-size: 16px; margin: 0 5px;" title="Edit">
                                                            <i class="fa-solid fa-edit"></i>
                                                        </a>
                                                    @endif

                                                    <form action="{{ route('instructor.live-classes.destroy', $liveClass->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to cancel/delete this live class?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" style="background: none; border: none; color: #ef4444; font-size: 16px; padding: 0; cursor: pointer;" title="Delete">
                                                            <i class="fa-solid fa-trash-can"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">
                            {{ $liveClasses->links() }}
                        </div>
                    @else
                        <div class="text-center" style="background: #fff; padding: 50px 20px; border-radius: 8px; border: 1px solid #f1f5f9;">
                            <img src="{{ asset('assets/front/img/empty.svg') }}" alt="No Live Classes" style="max-width: 150px; opacity: 0.5; margin-bottom: 20px;" onerror="this.style.display='none'">
                            <h4 style="color: #64748b;">No Live Classes Scheduled</h4>
                            <p style="color: #94a3b8; max-width: 400px; margin: 10px auto 20px;">Schedule your first live video session, link it to a lesson, and connect directly with your enrolled students.</p>
                            <a href="{{ route('instructor.live-classes.create') }}" class="theme-btn">
                                <i class="fa-solid fa-plus"></i> Schedule First Class
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>

<!-- End Session Recording Modal -->
<div id="endSessionModal" class="custom-modal" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center;">
    <div class="custom-modal-content" style="background-color: #fff; margin: auto; padding: 25px; border: 1px solid #e2e8f0; border-radius: 8px; width: 100%; max-width: 500px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 20px;">
            <h4 style="margin: 0; color: #1e293b;">End Live Session</h4>
            <span class="close-modal" style="color: #94a3b8; font-size: 24px; font-weight: bold; cursor: pointer;">&times;</span>
        </div>
        <form id="endSessionForm" action="" method="POST" enctype="multipart/form-data">
            @csrf
            <div style="margin-bottom: 15px;">
                <p style="color: #475569; font-size: 14px;">You are about to end the live session for <strong id="modal-class-title"></strong>. Students will no longer be able to join. You can optionally attach the session recording below.</p>
            </div>
            
            <div class="form-group mb-3">
                <label for="recording_url" style="font-weight: 600; display: block; margin-bottom: 6px;">Recording Video URL (Optional)</label>
                <input type="url" id="recording_url" name="recording_url" class="form-control" placeholder="e.g. https://youtube.com/watch?v=... or Google Drive URL" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 4px;">
            </div>

            <div class="form-group mb-4">
                <label for="recording_file" style="font-weight: 600; display: block; margin-bottom: 6px;">Or Upload Video File (Optional, Max 512MB)</label>
                <input type="file" id="recording_file" name="recording_file" class="form-control" accept="video/*" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
            </div>

            <div style="text-align: right; border-top: 1px solid #f1f5f9; padding-top: 15px;">
                <button type="button" class="theme-btn close-modal-btn" style="background-color: #cbd5e1; color: #334155; border: none; padding: 10px 20px; border-radius: 4px; margin-right: 10px; cursor: pointer;">Cancel</button>
                <button type="submit" class="theme-btn" style="background-color: #ef4444; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;">End Session & Save</button>
            </div>
        </form>
    </div>
</div>

<style>
@keyframes blinker {
    50% { opacity: 0.3; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('endSessionModal');
    const form = document.getElementById('endSessionForm');
    const modalTitle = document.getElementById('modal-class-title');
    const closeBtns = document.querySelectorAll('.close-modal, .close-modal-btn');
    
    document.querySelectorAll('.btn-end-session').forEach(button => {
        button.addEventListener('click', function() {
            const classId = this.dataset.id;
            const classTitle = this.dataset.title;
            
            form.action = `/instructor/live-classes/${classId}/end`;
            modalTitle.textContent = classTitle;
            modal.style.display = 'flex';
        });
    });

    closeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            modal.style.display = 'none';
        });
    });

    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
});
</script>
@endsection
