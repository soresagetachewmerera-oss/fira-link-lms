@extends('layouts.admin')

@section('title', 'Newsletter Subscribers')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
                <div class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Settings</a></div>
        <div class="breadcrumb-item active">Newsletter Subscribers</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h4>Send Newsletter</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.settings.newsletter.send') }}" id="newsletterSendForm">
                        @csrf
                        <div class="form-group">
                            <label for="newsletter-subject">Subject</label>
                            <input type="text" name="subject" id="newsletter-subject" class="form-control @error('subject') is-invalid @enderror" value="{{ old('subject') }}" required>
                            @error('subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="newsletter-message">Message</label>
                            <textarea name="message" id="newsletter-message" rows="6" class="form-control @error('message') is-invalid @enderror" placeholder="Write your newsletter content..." required>{{ old('message') }}</textarea>
                            @error('message')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <input type="hidden" name="recipients" id="selectedRecipients" value="{{ old('recipients') }}">
                        <div class="alert alert-info">
                            If no recipients are selected, the newsletter will be sent to all subscribers.
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-paper-plane"></i> Send Newsletter
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Subscribers <span class="badge badge-light">{{ $subscribers->total() }}</span></h4>
                    <button class="btn btn-outline-secondary btn-sm" type="button" id="selectAllSubscribers">Select All</button>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th width="40"><input type="checkbox" id="toggleAll"></th>
                                <th>Email</th>
                                <th>Subscribed</th>
                                <th>Source</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($subscribers as $subscriber)
                                <tr>
                                    <td><input type="checkbox" class="subscriber-checkbox" value="{{ $subscriber->email }}"></td>
                                    <td>{{ $subscriber->email }}</td>
                                    <td>{{ $subscriber->created_at->format('M d, Y') }}</td>
                                    <td>{{ ucfirst($subscriber->source ?? 'Unknown') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No subscribers yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $subscribers->links('vendor.pagination.stisla-default') }}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggleAll = document.getElementById('toggleAll');
            const selectAllBtn = document.getElementById('selectAllSubscribers');
            const checkboxes = document.querySelectorAll('.subscriber-checkbox');
            const selectedRecipients = document.getElementById('selectedRecipients');
            const form = document.getElementById('newsletterSendForm');

            const preselected = selectedRecipients.value
                ? selectedRecipients.value.split(',').map(email => email.trim()).filter(Boolean)
                : [];

            checkboxes.forEach(cb => {
                if (preselected.includes(cb.value)) {
                    cb.checked = true;
                }
            });

            const syncToggle = () => {
                if (!toggleAll) {
                    return;
                }
                const allChecked = [...checkboxes].length > 0 && [...checkboxes].every(cb => cb.checked);
                toggleAll.checked = allChecked;
            };

            syncToggle();

            toggleAll?.addEventListener('change', function () {
                checkboxes.forEach(cb => cb.checked = toggleAll.checked);
            });

            selectAllBtn?.addEventListener('click', function () {
                const allSelected = [...checkboxes].every(cb => cb.checked);
                checkboxes.forEach(cb => cb.checked = !allSelected);
                syncToggle();
            });

            checkboxes.forEach(cb => cb.addEventListener('change', syncToggle));

            form?.addEventListener('submit', function () {
                const emails = [...checkboxes]
                    .filter(cb => cb.checked)
                    .map(cb => cb.value);
                selectedRecipients.value = emails.join(',');
            });
        });
    </script>
@endpush

