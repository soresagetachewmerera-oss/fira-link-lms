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
                            <h6>{{ __('instructor.coupons_discounts') }}</h6>
                            <h2>{{ __('instructor.manage_coupons') }}</h2>
                        </div>
                        <div class="dashboard-header-actions mb-4">
                            <a href="{{ route('instructor.coupons.create') }}" class="theme-btn style-2">
                                <i class="fa-solid fa-plus"></i> New Coupon
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Coupons Table -->
                <div class="dashboard-courses-section wow fadeInUp" data-wow-delay="0.1s" style="margin-top: 30px;">
                    @if ($coupons->count())
                        <div class="transactions-table-wrapper">
                            <table class="students-table">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Type</th>
                                        <th>Value</th>
                                        <th>Course Restriction</th>
                                        <th>Usage</th>
                                        <th>Validity</th>
                                        <th>Status</th>
                                        <th class="text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($coupons as $coupon)
                                        <tr>
                                            <td>
                                                <strong style="color: #6C5CE7;">{{ $coupon->code }}</strong>
                                            </td>
                                            <td>
                                                <span class="badge" style="background-color: #e0f2fe; color: #0369a1; padding: 5px 10px; border-radius: 4px; font-size: 12px;">
                                                    {{ ucfirst($coupon->type) }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($coupon->type === 'percentage')
                                                    {{ (float)$coupon->value }}%
                                                @else
                                                    {{ (float)$coupon->value }}
                                                @endif
                                            </td>
                                            <td>
                                                @if($coupon->course)
                                                    <span style="font-size: 13px;">{{ $coupon->course->title }}</span>
                                                @else
                                                    <span style="color: #94a3b8; font-size: 13px;">Own Courses Only</span>
                                                @endif
                                            </td>
                                            <td>
                                                {{ $coupon->used_count }} / {{ $coupon->usage_limit ?? '∞' }}
                                            </td>
                                            <td>
                                                <span style="font-size: 12px; display: block; line-height: 1.4;">
                                                    From: {{ $coupon->valid_from ? $coupon->valid_from->format('Y-m-d H:i') : 'Always' }}
                                                </span>
                                                <span style="font-size: 12px; display: block; line-height: 1.4;">
                                                    To: {{ $coupon->valid_to ? $coupon->valid_to->format('Y-m-d H:i') : 'Never Expires' }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($coupon->isValidNow())
                                                    <span class="badge" style="background-color: #dcfce7; color: #15803d; padding: 5px 10px; border-radius: 4px; font-size: 12px;">Valid</span>
                                                @else
                                                    <span class="badge" style="background-color: #fee2e2; color: #b91c1c; padding: 5px 10px; border-radius: 4px; font-size: 12px;">Inactive/Expired</span>
                                                @endif
                                            </td>
                                            <td class="text-right">
                                                <a href="{{ route('instructor.coupons.edit', $coupon->id) }}"
                                                    style="color: #6C5CE7; margin-right: 12px; font-size: 16px;">
                                                    <i class="fa-solid fa-edit"></i>
                                                </a>
                                                <form action="{{ route('instructor.coupons.destroy', $coupon->id) }}"
                                                    method="POST" style="display: inline;"
                                                    onsubmit="return confirm('Are you sure you want to delete this coupon?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" style="background: none; border: none; color: #ef4444; font-size: 16px; padding: 0; cursor: pointer;">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div style="margin-top: 20px;">
                            {{ $coupons->links() }}
                        </div>
                    @else
                        <div style="text-align: center; padding: 40px 20px;">
                            <i class="fa-solid fa-tags" style="font-size: 48px; color: #cbd5e1; margin-bottom: 15px;"></i>
                            <p style="color: #64748b;">No coupons created yet. Create a coupon to promote your courses!</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
