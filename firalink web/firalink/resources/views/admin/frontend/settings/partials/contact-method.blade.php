<div class="card contact-method-card mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">Contact Method</h6>
            <button type="button" class="btn btn-sm btn-outline-danger remove-contact-method">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="form-row">
            <div class="form-group col-md-3">
                <label>Icon Class</label>
                <input type="text" name="methods[{{ $index }}][icon]" class="form-control"
                    value="{{ old("methods.$index.icon", $method['icon'] ?? '') }}" placeholder="fa-light fa-phone">
            </div>
            <div class="form-group col-md-3">
                <label>Label</label>
                <input type="text" name="methods[{{ $index }}][label]" class="form-control"
                    value="{{ old("methods.$index.label", $method['label'] ?? '') }}" maxlength="120"
                    placeholder="Call us anytime">
            </div>
            <div class="form-group col-md-3">
                <label>Type</label>
                <input type="text" name="methods[{{ $index }}][type]" class="form-control"
                    value="{{ old("methods.$index.type", $method['type'] ?? '') }}" maxlength="40"
                    placeholder="phone, email, address">
            </div>
            <div class="form-group col-md-3">
                <label>Link (optional)</label>
                <input type="text" name="methods[{{ $index }}][link]" class="form-control"
                    value="{{ old("methods.$index.link", $method['link'] ?? '') }}" maxlength="255"
                    placeholder="tel:+18005551234">
            </div>
        </div>
        <div class="form-group">
            <label>Value</label>
            <input type="text" name="methods[{{ $index }}][value]" class="form-control"
                value="{{ old("methods.$index.value", $method['value'] ?? '') }}" maxlength="255"
                placeholder="+1 (800) 555-1234">
        </div>
    </div>
</div>
