<div class="card marquee-item-card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-bolt text-primary mr-2"></i> Item
        </h5>
        <button type="button" class="btn btn-sm btn-outline-danger remove-marquee-item">
            <i class="fas fa-trash-alt mr-1"></i> Remove
        </button>
    </div>
    <div class="card-body">
        <div class="form-group">
            <label for="marquee_{{ $index }}_text">Display Text</label>
            <input type="text" id="marquee_{{ $index }}_text" name="items[{{ $index }}][text]"
                class="form-control @error('items.' . $index . '.text') is-invalid @enderror"
                value="{{ old('items.' . $index . '.text', $item['text'] ?? '') }}" maxlength="120"
                placeholder="Enter marquee text">
            @error('items.' . $index . '.text')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="marquee_{{ $index }}_image">Icon/Image Path <span
                    class="text-muted">(optional)</span></label>
            <input type="text" id="marquee_{{ $index }}_image" name="items[{{ $index }}][image]"
                class="form-control @error('items.' . $index . '.image') is-invalid @enderror"
                value="{{ old('items.' . $index . '.image', $item['image'] ?? '') }}" maxlength="255"
                placeholder="e.g. assets/front/img/home-1/icon1.png">
            @error('items.' . $index . '.image')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
            @php
                $existingImage = old('items.' . $index . '.image', $item['image'] ?? '');
                if ($existingImage && !\Illuminate\Support\Str::startsWith($existingImage, ['http://', 'https://'])) {
                    $existingImage = asset(ltrim($existingImage, '/'));
                }
            @endphp
            @if (!empty($existingImage))
                <div class="mt-2">
                    <img src="{{ $existingImage }}" alt="Marquee icon preview" style="height:40px;">
                </div>
            @endif
        </div>
        <div class="form-group mb-0">
            <label for="marquee_{{ $index }}_upload">Upload Icon/Image <span
                    class="text-muted">(optional)</span></label>
            <input type="file" id="marquee_{{ $index }}_upload"
                name="items[{{ $index }}][image_file]"
                class="form-control-file @error('items.' . $index . '.image_file') is-invalid @enderror"
                accept=".jpg,.jpeg,.png,.svg,.webp">
            <small class="form-text text-muted">Uploading a new file replaces the existing image.</small>
            @error('items.' . $index . '.image_file')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>
