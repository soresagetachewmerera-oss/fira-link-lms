<div class="social-link-row border rounded p-3 mb-3">
    <div class="form-row">
        <div class="form-group col-md-4">
            <label>Label</label>
            <input type="text" name="social_links[{{ $index }}][label]"
                value="{{ old('social_links.' . $index . '.label', $link['label'] ?? '') }}" class="form-control">
        </div>
        <div class="form-group col-md-5">
            <label>URL</label>
            <input type="text" name="social_links[{{ $index }}][url]"
                value="{{ old('social_links.' . $index . '.url', $link['url'] ?? '') }}" class="form-control">
        </div>
        <div class="form-group col-md-3">
            <label>Icon Class</label>
            <div class="input-group">
                <input type="text" name="social_links[{{ $index }}][icon]"
                    value="{{ old('social_links.' . $index . '.icon', $link['icon'] ?? '') }}" class="form-control"
                    placeholder="fa-brands fa-facebook">
                <div class="input-group-append">
                    <button class="btn btn-outline-danger remove-social-link" type="button"><i
                            class="fas fa-times"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>
