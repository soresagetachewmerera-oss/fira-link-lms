<div class="main-sidebar sidebar-style-2">
    <aside id="sidebar-wrapper">
        <div class="sidebar-brand">
            <?php $siteName = site_name(); ?>
            <a href="{{ route('admin.dashboard') }}">
                <img src="{{ branding_asset(['primary_logo_path', 'logos.primary'], 'assets/front/img/logo/white-logo.svg') }}"
                    alt="{{ $siteName }} logo" class="img-fluid mt-3" style="max-height: 40px;">
            </a>
        </div>
        <ul class="sidebar-menu">
            <li class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <a href="{{ route('admin.dashboard') }}" class="nav-link"><i
                        class="fas fa-home"></i><span>Dashboard</span></a>
            </li>

            <li class="dropdown {{ request()->routeIs('admin.courses.*') ? 'active' : '' }}">
                <a href="#" class="nav-link has-dropdown" data-toggle="dropdown"><i class="fas fa-book"></i>
                    <span>Courses</span></a>
                <ul class="dropdown-menu">
                    <li class="{{ request()->routeIs('admin.courses.*') ? 'active' : '' }}"><a class="nav-link"
                            href="{{ route('admin.courses.index') }}">All Courses</a></li>
                    <li class=""><a class="nav-link"
                            href="{{ route('admin.courses.index', ['status' => 'pending']) }}">Pending Courses</a>
                    </li>
                    <li class=""><a class="nav-link"
                            href="{{ route('admin.courses.index', ['status' => 'published']) }}">Published
                            Courses</a></li>
                    <li class=""><a class="nav-link"
                            href="{{ route('admin.courses.index', ['status' => 'draft']) }}">Draft Courses</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.courses.index', ['status' => 'archived']) }}"><a class="nav-link"
                            href="{{ route('admin.courses.index', ['status' => 'archived']) }}">Archived
                            Courses</a></li>
                    <li class="{{ request()->routeIs('admin.bundles.*') ? 'active' : '' }}"><a class="nav-link"
                            href="{{ route('admin.bundles.index') }}">Course Bundles</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.course-languages.*') ? 'active' : '' }}"><a class="nav-link"
                            href="{{ route('admin.course-languages.index') }}">Languages</a>
                    </li>
                </ul>
            </li>

            <li
                class="dropdown {{ request()->routeIs('admin.blog-categories.*') || request()->routeIs('admin.course-categories.*') ? 'active' : '' }}">
                <a href="#" class="nav-link has-dropdown" data-toggle="dropdown"><i class="fas fa-layer-group"></i>
                    <span>Categories</span></a>
                <ul class="dropdown-menu">
                    <li class="{{ request()->routeIs('admin.blog-categories.*') ? 'active' : '' }}"><a class="nav-link"
                            href="{{ route('admin.blog-categories.index') }}">Blog
                            Categories</a></li>
                    <li class="{{ request()->routeIs('admin.course-categories.*') ? 'active' : '' }}"><a
                            class="nav-link" href="{{ route('admin.course-categories.index') }}">Course
                            Categories</a></li>
                </ul>
            </li>

            <li class="dropdown {{ request()->routeIs('admin.instructors.*') ? 'active' : '' }}">
                <a href="#" class="nav-link has-dropdown" data-toggle="dropdown"><i class="fas fa-user-tie"></i>
                    <span>Instructors</span></a>
                <ul class="dropdown-menu">
                    <li
                        class="{{ request()->routeIs('admin.instructors.index') && !request()->has('status') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.instructors.index') }}">All Instructors</a>
                    </li>
                    <li
                        class="{{ request()->routeIs('admin.instructors.pending') || (request()->routeIs('admin.instructors.index') && request('status') == 'pending') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.instructors.pending') }}">Pending
                            Instructors</a>
                    </li>
                </ul>
            </li>

            <li class="{{ request()->routeIs('admin.students.*') ? 'active' : '' }}">
                <a href="{{ route('admin.students.index') }}" class="nav-link"><i class="fas fa-user-graduate"></i>
                    <span>Students</span></a>
            </li>

            <li class="dropdown {{ request()->routeIs('admin.enrollments.*') ? 'active' : '' }}">
                <a href="#" class="nav-link has-dropdown" data-toggle="dropdown"><i class="fas fa-clipboard-list"></i>
                    <span>Enrollments</span></a>
                <ul class="dropdown-menu">
                    <li
                        class="{{ request()->routeIs('admin.enrollments.index') && !request()->routeIs('admin.enrollments.pending') && !request()->routeIs('admin.enrollments.completed') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.enrollments.index') }}">All Enrollments</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.enrollments.pending') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.enrollments.pending') }}">Pending
                            Enrollments</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.enrollments.completed') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.enrollments.completed') }}">Completed
                            Enrollments</a>
                    </li>
                </ul>
            </li>

            <li
                class="dropdown {{ request()->routeIs('admin.quiz-attempts.*') || request()->routeIs('admin.assignment-submissions.*') ? 'active' : '' }}">
                <a href="#" class="nav-link has-dropdown" data-toggle="dropdown"><i class="fas fa-chart-line"></i>
                    <span>Learning Activity</span></a>
                <ul class="dropdown-menu">
                    <li class="{{ request()->routeIs('admin.quiz-attempts.*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.quiz-attempts.index') }}">Quiz Attempts</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.assignment-submissions.*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.assignment-submissions.index') }}">Assignment
                            Submissions</a>
                    </li>
                </ul>
            </li>

            <li class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('admin.reports.index') }}">
                    <i class="fas fa-chart-pie"></i> <span>Reports &amp; Analytics</span>
                </a>
            </li>

            <li class="{{ request()->routeIs('admin.certificates.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('admin.certificates.index') }}">
                    <i class="fas fa-certificate"></i> <span>Certificates</span>
                </a>
            </li>

            <li class="{{ request()->routeIs('admin.events.*') ? 'active' : '' }}">
                <a href="{{ route('admin.events.index') }}" class="nav-link">
                    <i class="fas fa-calendar"></i> <span>Events</span>
                </a>
            </li>

            <li
                class="dropdown {{ request()->routeIs('admin.frontend.menus.*') || request()->routeIs('admin.frontend.settings.*') || request()->routeIs('admin.frontend.faq.*') || request()->routeIs('admin.frontend.seo.*') || request()->routeIs('admin.frontend.marquee.*') || request()->routeIs('admin.frontend.home.*') || request()->routeIs('admin.frontend.about.*') || request()->routeIs('admin.frontend.contact.*') || request()->routeIs('admin.custom-pages.*') || request()->routeIs('admin.frontend.theme.*') ? 'active' : '' }}">
                <a href="#" class="nav-link has-dropdown" data-toggle="dropdown"><i class="fas fa-paint-brush"></i>
                    <span>Frontend Manager</span></a>
                <ul class="dropdown-menu">
                    <li class="{{ request()->routeIs('admin.frontend.menus.*') ? 'active' : '' }}"><a class="nav-link"
                            href="{{ route('admin.frontend.menus.index') }}">Menus</a></li>
                    <li class="{{ request()->routeIs('admin.frontend.home.*') ? 'active' : '' }}"><a class="nav-link"
                            href="{{ route('admin.frontend.home.edit') }}">Homepage Content</a></li>
                    <li class="{{ request()->routeIs('admin.frontend.theme.*') ? 'active' : '' }}"><a class="nav-link"
                            href="{{ route('admin.frontend.theme.edit') }}">Homepage Theme</a></li>
                    <li class="{{ request()->routeIs('admin.frontend.about.*') ? 'active' : '' }}"><a class="nav-link"
                            href="{{ route('admin.frontend.about.edit') }}">About Page</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.frontend.contact.*') ? 'active' : '' }}"><a class="nav-link"
                            href="{{ route('admin.frontend.contact.edit') }}">Contact Page</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.frontend.faq.*') ? 'active' : '' }}"><a class="nav-link"
                            href="{{ route('admin.frontend.faq.edit') }}">FAQ Content</a></li>
                    <li class="{{ request()->routeIs('admin.frontend.marquee.*') ? 'active' : '' }}"><a class="nav-link"
                            href="{{ route('admin.frontend.marquee.edit') }}">Marquee
                            Items</a></li>
                    <li class="{{ request()->routeIs('admin.frontend.seo.*') ? 'active' : '' }}"><a class="nav-link"
                            href="{{ route('admin.frontend.seo.edit') }}">SEO Metadata</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.custom-pages.*') ? 'active' : '' }}"><a class="nav-link"
                            href="{{ route('admin.custom-pages.index') }}">Custom Pages</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.banners.*') ? 'active' : '' }}"><a class="nav-link"
                            href="{{ route('admin.banners.index') }}">App Banners</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.frontend.settings.*') ? 'active' : '' }}"><a
                            class="nav-link" href="{{ route('admin.frontend.settings.edit') }}">Footer &amp;
                            Contact</a></li>
                </ul>
            </li>

            <li class="dropdown {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}">
                <a href="#" class="nav-link has-dropdown" data-toggle="dropdown"><i class="fas fa-money-bill"></i>
                    <span>Payments</span></a>
                <ul class="dropdown-menu">
                    <li
                        class="{{ request()->routeIs('admin.payments.index') && !request()->routeIs('admin.payments.pending') && !request()->routeIs('admin.payments.offline-pending') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.payments.index') }}">All Payments</a>
                    </li>
                    <li
                        class="{{ request()->routeIs('admin.payments.pending') && !request()->routeIs('admin.payments.offline-pending') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.payments.pending') }}">Pending Payments</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.payments.offline-pending') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.payments.offline-pending') }}">
                            Offline Pending
                            @php
                                $offlinePendingCount = \App\Models\Payment::where('payment_method', 'offline')
                                    ->where('status', 'pending')
                                    ->count();
                            @endphp
                            @if ($offlinePendingCount > 0)
                                <span class="badge badge-warning "
                                    style="width: 20px; margin-left: 5px;">{{ $offlinePendingCount }}</span>
                            @endif
                        </a>
                    </li>

                </ul>
            </li>

            <li class="{{ request()->routeIs('admin.withdrawals.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('admin.withdrawals.index') }}">
                    <i class="fas fa-money-bill"></i> <span>Payout Requests</span>
                </a>
            </li>

            <li class="dropdown {{ request()->routeIs('admin.subscription-plans.*') || request()->routeIs('admin.subscriptions.*') ? 'active' : '' }}">
                <a href="#" class="nav-link has-dropdown" data-toggle="dropdown"><i class="fas fa-id-card"></i>
                    <span>Subscriptions</span></a>
                <ul class="dropdown-menu">
                    <li class="{{ request()->routeIs('admin.subscription-plans.*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.subscription-plans.index') }}">Package Plans</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.subscriptions.*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.subscriptions.index') }}">User Subscriptions</a>
                    </li>
                </ul>
            </li>

            <li class="{{ request()->routeIs('admin.coupons.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('admin.coupons.index') }}">
                    <i class="fas fa-tags"></i> <span>Coupons</span>
                </a>
            </li>

            <li class="dropdown {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}">
                <a href="#" class="nav-link has-dropdown" data-toggle="dropdown"><i class="fas fa-star"></i>
                    <span>Reviews</span></a>
                <ul class="dropdown-menu">
                    <li
                        class="{{ request()->routeIs('admin.reviews.index') && !request()->routeIs('admin.reviews.pending') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.reviews.index') }}">All Reviews</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.reviews.pending') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.reviews.pending') }}">
                            Pending Reviews
                            @php
                                $pendingReviewsCount = \App\Models\Review::where('is_approved', false)->count();
                            @endphp
                            @if ($pendingReviewsCount > 0)
                                <span class="badge badge-warning">{{ $pendingReviewsCount }}</span>
                            @endif
                        </a>
                    </li>
                </ul>
            </li>

            <li class="{{ request()->routeIs('admin.account-deletions.*') ? 'active' : '' }}">
                <a href="{{ route('admin.account-deletions.index') }}" class="nav-link">
                    <i class="fas fa-user-slash"></i> <span>Account Deletion Requests</span>
                </a>
            </li>

            <li class="dropdown {{ request()->routeIs('admin.blog-posts.*') ? 'active' : '' }}">
                <a href="#" class="nav-link has-dropdown" data-toggle="dropdown"><i class="fas fa-book"></i> <span>Blogs
                        & News</span></a>
                <ul class="dropdown-menu">
                    <li class="{{ request()->routeIs('admin.blog-posts.index') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.blog-posts.index') }}">All Posts</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.blog-posts.create') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.blog-posts.create') }}">Create Post</a>
                    </li>

                </ul>
            </li>
            <li class="{{ request()->routeIs('admin.blog-comments.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('admin.blog-comments.index') }}">
                    <i class="fas fa-comments"></i> <span>Blog Comments</span>
                </a>
            </li>

            <li class="{{ request()->routeIs('admin.settings.newsletter.index') ? 'active' : '' }}">
                <a href="{{ route('admin.settings.newsletter.index') }}" class="nav-link">
                    <i class="fas fa-envelope"></i> <span>Newsletter</span>
                </a>
            </li>
            <li class="{{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}">
                <a href="{{ route('admin.notifications.index') }}" class="nav-link">
                    <i class="fas fa-bell"></i> <span>Push Notifications</span>
                </a>
            </li>
            <li class="{{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                <a href="{{ route('admin.settings.index') }}" class="nav-link">
                    <i class="fas fa-cog"></i> <span>Settings & Config</span>
                </a>
            </li>
            <li class="{{ request()->routeIs('admin.languages.*') ? 'active' : '' }}">
                <a href="{{ route('admin.languages.index') }}" class="nav-link">
                    <i class="fas fa-language"></i> <span>Language Manager</span>
                </a>
            </li>
            <li class="dropdown {{ request()->routeIs('admin.settings.system.*') || request()->routeIs('admin.updater.*') ? 'active' : '' }}">
                <a href="#" class="nav-link has-dropdown" data-toggle="dropdown">
                    <i class="fas fa-wrench"></i>
                    <span>
                        System Maintenance
                    </span>
                </a>
                <ul class="dropdown-menu">
                    <li class="{{ request()->routeIs('admin.settings.system.status') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.settings.system.status') }}">System
                            Status</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.settings.system.cache') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.settings.system.cache') }}">Cache
                            Management</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.settings.system.logs') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.settings.system.logs') }}">Error Logs</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.updater.*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.updater.index') }}">System Updater</a>
                    </li>
                </ul>
            </li>
        </ul>

        <!-- Profile Card -->
        @php($adminUser = auth()->user())
        @if ($adminUser)
            <div class="mt-4 mb-3 hide-sidebar-mini">
                <div class="card card-primary">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            @if ($adminUser->profile_photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($adminUser->profile_photo))
                                <img alt="image" src="{{ asset('storage/' . $adminUser->profile_photo) }}"
                                    class="rounded-circle" width="60" height="60" style="object-fit: cover;">
                            @else
                                <img alt="image" src="{{ asset('assets/images/avatar-1.png') }}" class="rounded-circle"
                                    width="60" height="60">
                            @endif
                        </div>
                        <h6 class="mb-1">{{ \Illuminate\Support\Str::limit($adminUser->name, 30) }}</h6>
                        <p class="text-muted small mb-2">{{ $adminUser->email }}</p>
                        <span class="badge badge-light mb-3 text-uppercase">{{ $adminUser->role ?? 'Admin' }}</span>
                        <a href="{{ route('admin.profile.edit') }}" class="btn btn-light btn-sm btn-block">
                            <i class="fas fa-user"></i> Edit Profile
                        </a>
                    </div>
                </div>
            </div>
        @endif

        <div class="mt-2 mb-4 p-3 hide-sidebar-mini">
            <a href="{{ route('logout') }}" class="btn btn-primary btn-lg btn-block btn-icon-split">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>
</div>