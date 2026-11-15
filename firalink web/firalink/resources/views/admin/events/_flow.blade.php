{{-- Slice 8: details from the app flows. The organizer badge shows only while the linked business has an active Tier 1. --}}
@php($t = fn ($k) => __('bookings.admin_flow.' . $k))
<div class="card mt-4">
    <div class="card-body">
        <h6>{{ $t('title') }}</h6>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        <form method="POST" action="{{ route('admin.events.flow', $event) }}">
            @csrf
            @method('PATCH')
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>{{ $t('category') }}</label>
                    <select name="category" class="form-control">
                        <option value="">—</option>
                        @foreach(['trade_fair', 'training', 'cupping', 'networking'] as $c)
                            <option value="{{ $c }}" @selected(old('category', $event->category) === $c)>{{ $t('cat_' . $c) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label>{{ $t('member_price') }}</label>
                    <input name="member_price" type="number" step="0.01" min="0" class="form-control" value="{{ old('member_price', $event->member_price) }}">
                </div>
                <div class="form-group col-md-4">
                    <label>{{ $t('service_fee') }}</label>
                    <input name="service_fee" type="number" step="0.01" min="0" class="form-control" value="{{ old('service_fee', $event->service_fee) }}">
                </div>
            </div>
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="is_online" value="1" id="ev-online" @checked(old('is_online', $event->is_online))>
                <label class="form-check-label" for="ev-online">{{ $t('is_online') }}</label>
            </div>
            <div class="form-group">
                <label>{{ $t('agenda') }}</label>
                <textarea name="agenda_text" rows="4" class="form-control" placeholder="09:00 | …">{{ old('agenda_text', collect($event->agenda ?? [])->map(fn ($a) => trim(($a['time'] ?? '') . ' | ' . ($a['title'] ?? ''), ' |'))->join("\n")) }}</textarea>
            </div>
            <div class="form-group">
                <label>{{ $t('refund_policy') }}</label>
                <textarea name="refund_policy" rows="2" class="form-control">{{ old('refund_policy', $event->refund_policy) }}</textarea>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>{{ $t('organizer_fira_id') }}</label>
                    <input name="organizer_fira_id" class="form-control @error('organizer_fira_id') is-invalid @enderror" value="{{ old('organizer_fira_id', $event->organizerOrganization?->fira_id) }}">
                    @error('organizer_fira_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @if($event->organizerOrganization)<small class="text-muted">{{ $event->organizerOrganization->name }}</small>@endif
                </div>
                <div class="form-group col-md-6 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="organized_by_fira_link" value="1" id="ev-fira" @checked(old('organized_by_fira_link', $event->organized_by_fira_link))>
                        <label class="form-check-label" for="ev-fira">{{ $t('organized_by_fira_link') }}</label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">{{ $t('save') }}</button>
        </form>
    </div>
</div>
