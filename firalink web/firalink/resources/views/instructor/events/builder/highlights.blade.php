@extends('layouts.instructor')

@section('content')
    <section class="section-padding section-bg fix">
        <div class="container">
            <div class="dashboard-layout">
                @include('instructor.partials.sidebar')

                <div class="dashboard-main">
                    <div class="section-title-area wow fadeInUp" style="margin-bottom: 30px;">
                        <div class="section-title">
                            <h6>Create Event</h6>
                            <h2>Highlights</h2>
                            <p>Spotlight the most compelling reasons to attend this event.</p>
                        </div>
                    </div>

                    @include('instructor.events.builder.partials.steps', ['steps' => $steps])

                    <div class="dashboard-content-section wow fadeInUp">
                        <form action="{{ route('instructor.events.highlights.update', $event) }}" method="POST">
                            @csrf
                            @method('PUT')
                            @php
                                $highlights = collect(
                                    old(
                                        'highlights',
                                        $event->highlights
                                            ->map(
                                                fn($highlight) => [
                                                    'title' => $highlight->title,
                                                    'description' => $highlight->description,
                                                ],
                                            )
                                            ->toArray(),
                                    ),
                                )
                                    ->map(
                                        fn($highlight) => [
                                            'title' => $highlight['title'] ?? '',
                                            'description' => $highlight['description'] ?? '',
                                        ],
                                    )
                                    ->values()
                                    ->toArray();

                                while (count($highlights) < 3) {
                                    $highlights[] = ['title' => '', 'description' => ''];
                                }
                            @endphp

                            @error('highlights')
                                <div class="alert alert-danger">{{ $message }}</div>
                            @enderror

                            <div class="row g-4" id="highlights-list">
                                @foreach ($highlights as $index => $highlight)
                                    <div class="col-12 highlight-card">
                                        <div class="card border-0 shadow-sm">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <h5 class="card-title mb-0"><span class="highlight-number">Highlight
                                                            {{ $index + 1 }}</span></h5>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="badge bg-light text-muted">Optional</span>
                                                        <button type="button"
                                                            class="btn btn-outline-danger btn-sm remove-highlight-btn">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Title</label>
                                                    <input type="text" class="form-control"
                                                        name="highlights[{{ $index }}][title]"
                                                        value="{{ $highlight['title'] }}" placeholder="Hands-on learning"
                                                        data-field="title">
                                                </div>
                                                <div class="mb-0">
                                                    <label class="form-label fw-semibold">Description</label>
                                                    <textarea class="form-control" name="highlights[{{ $index }}][description]" rows="3"
                                                        placeholder="Describe why this highlight matters." data-field="description">{{ $highlight['description'] }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @error('highlights.*.description')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror

                            <div class="d-flex justify-content-between align-items-center mt-4">
                                <div class="d-flex gap-3">
                                    <a href="{{ route('instructor.events.schedule.edit', $event) }}" class="back-btn"><i
                                            class="fa-solid fa-arrow-left me-2"></i> Back</a>
                                    <button type="button" class="back-btn" id="add-highlight-btn">
                                        <i class="fa-solid fa-plus me-1"></i> Add Highlight
                                    </button>
                                </div>
                                <button type="submit" class="theme-btn">
                                    Save &amp; Continue <i class="fa-solid fa-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const highlightsList = document.getElementById('highlights-list');
            const addButton = document.getElementById('add-highlight-btn');

            if (!highlightsList || !addButton) {
                return;
            }

            const createHighlightCard = (index) => {
                const wrapper = document.createElement('div');
                wrapper.className = 'col-12 highlight-card';

                wrapper.innerHTML = `
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0"><span class="highlight-number">Highlight ${index + 1}</span></h5>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-light text-muted">Optional</span>
                                <button type="button" class="btn btn-outline-danger btn-sm remove-highlight-btn">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Title</label>
                            <input type="text" class="form-control" name="highlights[${index}][title]" data-field="title" placeholder="Hands-on learning">
                        </div>
                        <div class="mb-0">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea class="form-control" name="highlights[${index}][description]" data-field="description" rows="3" placeholder="Describe why this highlight matters."></textarea>
                        </div>
                    </div>
                </div>
            `;

                return wrapper;
            };

            const reindexHighlights = () => {
                const cards = highlightsList.querySelectorAll('.highlight-card');

                cards.forEach((card, index) => {
                    const number = card.querySelector('.highlight-number');
                    if (number) {
                        number.textContent = `Highlight ${index + 1}`;
                    }

                    card.querySelectorAll('[data-field]').forEach((field) => {
                        const attribute = field.getAttribute('data-field');
                        field.setAttribute('name', `highlights[${index}][${attribute}]`);
                    });

                    const removeBtn = card.querySelector('.remove-highlight-btn');
                    if (removeBtn) {
                        removeBtn.classList.toggle('d-none', cards.length === 1);
                    }
                });
            };

            addButton.addEventListener('click', function(event) {
                event.preventDefault();
                const newIndex = highlightsList.querySelectorAll('.highlight-card').length;
                const newCard = createHighlightCard(newIndex);
                highlightsList.appendChild(newCard);
                reindexHighlights();

                const firstInput = newCard.querySelector('input');
                if (firstInput) {
                    firstInput.focus();
                }
            });

            highlightsList.addEventListener('click', function(event) {
                const removeButton = event.target.closest('.remove-highlight-btn');
                if (!removeButton) {
                    return;
                }

                event.preventDefault();
                const card = removeButton.closest('.highlight-card');
                if (card) {
                    card.remove();
                    reindexHighlights();
                }
            });

            reindexHighlights();
        });
    </script>
@endpush
