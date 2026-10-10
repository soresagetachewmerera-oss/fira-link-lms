@extends('layouts.instructor')

@section('content')
    <section class="section-padding section-bg fix">
        <div class="container">
            <div class="dashboard-layout">
                @include('instructor.partials.sidebar')

                <div class="dashboard-main">
                    <div class="dashboard-content-section"
                        style="background: white; border-radius: 20px; padding: 40px; box-shadow: 0 4px 20px rgba(0,0,0,0.03);">
                        <div class="d-flex justify-content-between align-items-center mb-5">
                            <h3
                                style="font-size: 28px; font-weight: 800; margin: 0; color: var(--heading); display: flex; align-items: center;">
                                <i class="fa-solid fa-bell me-3 text-theme" style="font-size: 24px;"></i>Notifications
                            </h3>
                            <a href="{{ route('instructor.notifications.readAll') }}" class="theme-btn"
                                style="padding: 10px 25px; border-radius: 8px; font-weight: 700; background-color: #004f44; color: white;">Mark
                                All Read</a>
                        </div>

                        <div class="notif-list-container">
                            @forelse($notifications as $notification)
                                @php
                                    $isUnread = !$notification->read_at;
                                    $notifType = $notification->data['type'] ?? '';

                                    $bgColor = 'rgba(0, 0, 0, 0.02)';
                                    $accentColor = 'var(--theme)';
                                    $iconClass = 'fa-bell';

                                    if ($isUnread) {
                                        $bgColor = '#fffaf3';
                                        $accentColor = '#ff6b00';

                                        if ($notifType == 'course') {
                                            $iconClass = 'fa-graduation-cap';
                                        } elseif ($notifType == 'author' || $notifType == 'instructor') {
                                            $iconClass = 'fa-user-tie';
                                            $accentColor = '#ff6b00';
                                        } elseif ($notifType == 'student') {
                                            $iconClass = 'fa-user-graduate';
                                        }
                                    } else {
                                        $bgColor = '#f8f9fa';
                                        $accentColor = '#6c757d';
                                    }
                                @endphp

                                <div class="notif-card-minimal-new {{ $isUnread ? 'is-unread' : '' }}"
                                    style="background: {{ $bgColor }}; padding: 30px; border-radius: 20px; margin-bottom: 20px; border-left: {{ $isUnread ? '4px solid ' . $accentColor : 'none' }}; position: relative; border: {{ $isUnread ? 'none' : '1px solid rgba(0,0,0,0.03)' }};">

                                    <div class="d-flex align-items-start justify-content-between">
                                        <div class="d-flex align-items-center gap-4 flex-grow-1">
                                            <div class="notif-icon-circle"
                                                style="background: #fff; width: 55px; height: 55px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 15px rgba(0,0,0,0.05); flex-shrink: 0;">
                                                <i class="fa-solid {{ $iconClass }}"
                                                    style="color: {{ $isUnread ? '#ff6b00' : '#888' }}; font-size: 18px;"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center gap-2 mb-2">
                                                    <h5
                                                        style="font-size: 17px; color: {{ $isUnread ? '#ff6b00' : '#333' }}; margin: 0; font-weight: 800;">
                                                        {{ $notification->data['title'] ?? 'Notification' }}
                                                    </h5>
                                                    @if($isUnread)
                                                        <span class="badge bg-theme-light text-theme ms-2"
                                                            style="font-size: 10px; padding: 4px 8px; font-weight: 700; text-transform: uppercase;">New</span>
                                                    @endif
                                                </div>
                                                <p style="font-size: 15px; color: #555; margin: 0 0 12px 0; line-height: 1.5;">
                                                    {{ $notification->data['message'] ?? '' }}
                                                </p>
                                                <div
                                                    style="font-size: 13px; color: #999; display: flex; align-items: center; gap: 5px; font-weight: 500;">
                                                    <i class="fa-regular fa-clock" style="font-size: 12px;"></i>
                                                    {{ $notification->created_at->diffForHumans() }}
                                                </div>
                                            </div>
                                        </div>

                                        <div class="text-end d-flex flex-column justify-content-between align-items-end"
                                            style="min-height: 110px;">
                                            @if($isUnread)
                                                <a href="{{ route('instructor.notifications.read', $notification->id) }}"
                                                    class="btn btn-link text-theme p-0 text-decoration-none"
                                                    style="font-size: 13px; font-weight: 600;">
                                                    Mark as Read
                                                </a>
                                            @else
                                                <span>&nbsp;</span>
                                            @endif

                                            <a href="{{ route('instructor.notifications.read', $notification->id) }}"
                                                class="btn-view-details"
                                                style="padding: 10px 24px; border-radius: 12px; font-size: 14px; border: 1.5px solid #e0e0e0; color: #333; text-decoration: none; font-weight: 700; background: white; transition: all 0.3s ease; display: inline-block;">
                                                View Details
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-5">
                                    <div class="mb-4">
                                        <div
                                            style="width: 80px; height: 80px; background: #f8f9fa; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;">
                                            <i class="fa-regular fa-bell-slash fa-2x text-muted"></i>
                                        </div>
                                    </div>
                                    <h4 class="text-dark fw-bold">All Caught Up!</h4>
                                    <p class="text-muted">You have no notifications at this time.</p>
                                </div>
                            @endforelse
                        </div>

                        <div class="mt-5 custom-pagination-wrapper">
                            {{ $notifications->links() }}
                        </div>
                    </div>
                </div>

                <style>
                    .notif-card-minimal-new {
                        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.01);
                        transition: all 0.3s ease;
                    }

                    .notif-card-minimal-new:hover {
                        transform: translateY(-3px);
                        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
                    }

                    .btn-view-details:hover {
                        background: #f8f9fa !important;
                        border-color: #333 !important;
                        color: #000 !important;
                    }

                    .dashboard-content-section h3 i {
                        background: rgba(0, 79, 68, 0.1);
                        width: 50px;
                        height: 50px;
                        display: inline-flex;
                        align-items: center;
                        justify-content: center;
                        border-radius: 12px;
                    }

                    .custom-pagination-wrapper .pagination {
                        justify-content: center;
                        gap: 8px;
                    }

                    .custom-pagination-wrapper .page-item .page-link {
                        border-radius: 12px;
                        border: 1px solid #eee;
                        background: #fff;
                        color: #333;
                        padding: 12px 20px;
                        font-weight: 700;
                        transition: all 0.2s ease;
                    }

                    .custom-pagination-wrapper .page-item.active .page-link {
                        background: #004f44;
                        color: #fff;
                        border-color: #004f44;
                    }

                    .custom-pagination-wrapper .page-item:not(.active) .page-link:hover {
                        background: #f8f9fa;
                        border-color: #ccc;
                    }
                </style>
            </div>
        </div>
    </section>
@endsection
