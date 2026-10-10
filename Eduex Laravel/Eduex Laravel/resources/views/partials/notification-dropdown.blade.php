<?php 
    $unreadCount = $currentUser->unreadNotifications()->count();
    $headerNotifications = $currentUser->notifications()->latest()->take(5)->get();
    $notifRoutePrefix = $currentUser->role === 'instructor' ? 'instructor' : 'student';
?>

<div class="custom-dropdown me-3 position-relative" id="notificationWrapper" style="margin-right: 15px;">
    <a href="javascript:void(0)" class="position-relative text-decoration-none" id="customNotifToggle">
        <i class="fa-regular fa-bell" style="font-size: 24px; color: var(--heading);"></i>
        @if($unreadCount > 0)
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                style="font-size: 10px;">
                {{ $unreadCount }}
            </span>
        @endif
    </a>

    <div class="custom-dropdown-menu shadow-lg" id="customNotifMenu">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center" style="background: white;">
            <h6 class="m-0" style="font-weight: 800; color: #002b24;">Notifications</h6>
            @if($unreadCount > 0)
                <a href="{{ route($notifRoutePrefix . '.notifications.readAll') }}" class="text-decoration-none small" style="color: #004f44; font-weight: 700;">Mark all read</a>
            @endif
        </div>
        <div class="notif-scroll-area">
            @if($headerNotifications->count() > 0)
                @foreach($headerNotifications as $notification)
                <?php 
                    $isUnread = !$notification->read_at;
                    $notifType = $notification->data['type'] ?? '';
                    
                    // Minimal logic for dropdown
                    $bgColor = $isUnread ? '#fffaf3' : '#fff';
                    $accentColor = $isUnread ? '#ff6b00' : '#333';
                    $iconClass = 'fa-bell';
                    
                    if ($notifType == 'course') {
                        $iconClass = 'fa-graduation-cap';
                    } elseif ($notifType == 'author' || $notifType == 'instructor') {
                        $iconClass = 'fa-user-tie';
                    }
                ?>

                <div class="notif-item p-3 {{ $isUnread ? 'is-unread-item' : '' }}"
                    style="background: {{ $bgColor }}; border-bottom: 1px solid rgba(0,0,0,0.03); border-left: {{ $isUnread ? '3px solid #ff6b00' : 'none' }}; transition: all 0.2s ease;">
                    <div class="d-flex align-items-start gap-3">
                        <div class="notif-icon-small" style="background: white; width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.05); flex-shrink: 0;">
                            <i class="fa-solid {{ $iconClass }}" style="color: {{ $isUnread ? '#ff6b00' : '#888' }}; font-size: 13px;"></i>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="mb-1 fw-bold" style="color: {{ $accentColor }}; font-size: 14px;">
                                {{ $notification->data['title'] ?? 'Notification' }}</p>
                            <p class="mb-2 text-muted" style="font-size: 12px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                {{ $notification->data['message'] ?? '' }}</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted" style="font-size: 10px; font-weight: 500;">
                                    <i class="fa-regular fa-clock me-1"></i>{{ $notification->created_at->diffForHumans() }}
                                </small>
                                <a href="{{ route($notifRoutePrefix . '.notifications.read', $notification->id) }}"
                                    class="fw-bold text-decoration-none"
                                    style="font-size: 11px; color: {{ $isUnread ? '#ff6b00' : '#004f44' }}">
                                    View <i class="fa-solid fa-arrow-right fa-xs ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            @else
                <div class="p-4 text-center">
                    <i class="fa-regular fa-bell-slash text-muted mb-2"></i>
                    <p class="text-muted mb-0 small">No notifications found</p>
                </div>
            @endif
        </div>
        <div class="p-3 text-center bg-white border-top">
            <a href="{{ route($notifRoutePrefix . '.notifications.index') }}" class="small fw-bold text-decoration-none"
                style="color: #004f44; font-size: 13px;">View All Notifications</a>
        </div>
    </div>
</div>

<style>
    .custom-dropdown-menu {
        display: none;
        position: absolute;
        top: 100%;
        right: 0;
        width: 340px;
        background: #fff;
        border-radius: 20px;
        z-index: 1000;
        margin-top: 15px;
        overflow: hidden;
        border: none;
        box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15) !important;
        transform: translateY(10px);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .custom-dropdown-menu.show {
        display: block;
        transform: translateY(0);
    }

    .notif-scroll-area {
        max-height: 380px;
        overflow-y: auto;
        scrollbar-width: thin;
    }

    .notif-item:hover {
        background: #f8f9fa !important;
    }
    
    .mark-read-dropdown:hover {
        color: #ff6b00 !important;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggle = document.getElementById('customNotifToggle');
        const menu = document.getElementById('customNotifMenu');
        const wrapper = document.getElementById('notificationWrapper');
        if (toggle && menu) {
            toggle.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                menu.classList.toggle('show');
            });
            document.addEventListener('click', function (e) {
                if (wrapper && !wrapper.contains(e.target)) {
                    menu.classList.remove('show');
                }
            });
        }
    });
</script>