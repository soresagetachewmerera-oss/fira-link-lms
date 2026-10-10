@extends('layouts.admin')

@section('title', 'Frontend Settings')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item">Frontend Manager</div>
        <div class="breadcrumb-item">Footer &amp; Contact</div>
    </div>
@endsection

@section('main-content')
    <form method="POST" action="{{ route('admin.frontend.settings.update') }}">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Footer Content</h4>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="about_text">About Text</label>
                            <textarea id="about_text" name="about_text" rows="4"
                                class="form-control @error('about_text') is-invalid @enderror" placeholder="Describe your platform...">{{ old('about_text', $branding['about_text'] ?? '') }}</textarea>
                            @error('about_text')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="tagline">Tagline</label>
                            <input type="text" id="tagline" name="tagline"
                                value="{{ old('tagline', $branding['tagline'] ?? '') }}"
                                class="form-control @error('tagline') is-invalid @enderror"
                                placeholder="Empowering learners worldwide">
                            @error('tagline')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-0">
                            <label for="copyright">Copyright Text</label>
                            <input type="text" id="copyright" name="copyright"
                                value="{{ old('copyright', $branding['copyright'] ?? '') }}"
                                class="form-control @error('copyright') is-invalid @enderror"
                                placeholder="© {{ date('Y') }} Eduex. All rights reserved.">
                            @error('copyright')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Contact Information</h4>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="contact-email">Email</label>
                            <input type="email" id="contact-email" name="contact[email]"
                                value="{{ old('contact.email', $contact['email'] ?? '') }}"
                                class="form-control @error('contact.email') is-invalid @enderror">
                            @error('contact.email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="contact-phone">Phone</label>
                            <input type="text" id="contact-phone" name="contact[phone]"
                                value="{{ old('contact.phone', $contact['phone'] ?? '') }}"
                                class="form-control @error('contact.phone') is-invalid @enderror">
                            @error('contact.phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="contact-address">Address</label>
                            <textarea id="contact-address" name="contact[address]" rows="2"
                                class="form-control @error('contact.address') is-invalid @enderror">{{ old('contact.address', $contact['address'] ?? '') }}</textarea>
                            @error('contact.address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-0">
                            <label for="contact-hours">Office Hours</label>
                            <input type="text" id="contact-hours" name="contact[hours]"
                                value="{{ old('contact.hours', $contact['hours'] ?? '') }}"
                                class="form-control @error('contact.hours') is-invalid @enderror">
                            @error('contact.hours')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Social Links</h4>
                <button type="button" class="btn btn-sm btn-outline-primary" id="add-social-link"
                    data-max="{{ $maxSocialLinks }}"><i class="fas fa-plus"></i> Add Link</button>
            </div>
            <div class="card-body">
                <p class="text-muted">Manage your footer and header social icons. Provide a label, URL, and optional icon
                    class (Font Awesome).</p>
                <div id="social-links-wrapper">
                    @php
                        $oldSocials = collect(old('social_links', $socialLinks))->values();
                    @endphp
                    @forelse($oldSocials as $index => $link)
                        @include('admin.frontend.settings.partials.social-row', [
                            'index' => $index,
                            'link' => $link,
                        ])
                    @empty
                        @include('admin.frontend.settings.partials.social-row', [
                            'index' => 0,
                            'link' => ['label' => null, 'url' => null, 'icon' => null],
                        ])
                    @endforelse
                </div>
                <template id="social-link-template">
                    @include('admin.frontend.settings.partials.social-row-template')
                </template>
            </div>
        </div>

        <div class="text-right mt-3">
            <button type="submit" class="btn btn-primary">Save Settings</button>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (function() {
            const wrapper = document.getElementById('social-links-wrapper');
            const addBtn = document.getElementById('add-social-link');
            const template = document.getElementById('social-link-template');
            if (!wrapper || !addBtn) {
                return;
            }

            addBtn.addEventListener('click', function(event) {
                this.blur();
                event.preventDefault();
                const max = parseInt(this.getAttribute('data-max'), 10) || 8;
                const current = wrapper.querySelectorAll('.social-link-row').length;
                if (current >= max) {
                    alert('Maximum of ' + max + ' social links allowed.');
                    return;
                }

                if (!template) {
                    return;
                }

                const index = Date.now().toString() + Math.floor(Math.random() * 1000);
                const clone = template.innerHTML.replace(/__INDEX__/g, index);
                wrapper.insertAdjacentHTML('beforeend', clone);
            });

            wrapper.addEventListener('click', function(event) {
                if (event.target.matches('.remove-social-link')) {
                    event.preventDefault();
                    const row = event.target.closest('.social-link-row');
                    if (row) {
                        row.remove();
                    }
                }
            });
        })();
    </script>
@endpush
