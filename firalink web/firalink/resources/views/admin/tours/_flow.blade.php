{{-- Slice 8: host business (badge only while its Tier 1 is active), dates, deposit and safety. --}}
@php($t = fn ($k) => __('bookings.admin_flow.' . $k))
<div class="card mt-4">
    <div class="card-body">
        <h6>{{ $t('title') }}</h6>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        <form method="POST" action="{{ route('admin.tours.flow', $tour) }}">
            @csrf
            @method('PATCH')
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>{{ $t('host_fira_id') }}</label>
                    <input name="host_fira_id" class="form-control @error('host_fira_id') is-invalid @enderror" value="{{ old('host_fira_id', $tour->hostOrganization?->fira_id) }}">
                    @error('host_fira_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @if($tour->hostOrganization)<small class="text-muted">{{ $tour->hostOrganization->name }}</small>@endif
                </div>
                <div class="form-group col-md-4">
                    <label>{{ $t('deposit_percent') }}</label>
                    <input name="deposit_percent" type="number" min="1" max="100" class="form-control" value="{{ old('deposit_percent', $tour->deposit_percent ?? 30) }}" required>
                </div>
                <div class="form-group col-md-4">
                    <label>{{ $t('balance_due_days') }}</label>
                    <input name="balance_due_days" type="number" min="0" max="180" class="form-control" value="{{ old('balance_due_days', $tour->balance_due_days ?? 14) }}" required>
                </div>
            </div>
            <div class="form-group">
                <label>{{ $t('dates') }}</label>
                <textarea name="dates_text" rows="2" class="form-control" placeholder="2026-12-12">{{ old('dates_text', collect($tour->available_schedules ?? [])->map(fn ($s) => is_array($s) ? ($s['date'] ?? '') : $s)->join("\n")) }}</textarea>
            </div>
            <div class="form-group">
                <label>{{ $t('safety_notes') }}</label>
                <textarea name="safety_notes" rows="2" class="form-control">{{ old('safety_notes', $tour->safety_notes) }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary">{{ $t('save') }}</button>
        </form>
    </div>
</div>
