<div class="card faq-item-card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-question-circle text-primary mr-2"></i>
            FAQ Item
        </h5>
        <button type="button" class="btn btn-sm btn-outline-danger remove-faq-item">
            <i class="fas fa-trash-alt mr-1"></i> Remove
        </button>
    </div>
    <div class="card-body">
        <div class="form-group">
            <label for="faqs_{{ $index }}_question">Question</label>
            <input type="text" id="faqs_{{ $index }}_question" name="faqs[{{ $index }}][question]"
                class="form-control @error('faqs.' . $index . '.question') is-invalid @enderror"
                value="{{ old('faqs.' . $index . '.question', $item['question'] ?? '') }}" maxlength="255"
                placeholder="Enter the FAQ question">
            @error('faqs.' . $index . '.question')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
        <div class="form-group mb-0">
            <label for="faqs_{{ $index }}_answer">Answer</label>
            <textarea id="faqs_{{ $index }}_answer" name="faqs[{{ $index }}][answer]"
                class="form-control faq-answer-editor @error('faqs.' . $index . '.answer') is-invalid @enderror"
                placeholder="Provide the answer with any helpful details">{{ old('faqs.' . $index . '.answer', $item['answer'] ?? '') }}</textarea>
            @error('faqs.' . $index . '.answer')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>
