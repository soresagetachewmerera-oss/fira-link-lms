@extends('layouts.admin')

@section('title', $category->exists ? __('categories.edit') : __('categories.add'))

@section('main-content')
<div class="row">
    <div class="col-lg-8">
        @include('admin.verification-review._flash')
        @if($errors->any())
            <div class="alert alert-danger" role="alert">
                @foreach($errors->all() as $message)<div>{{ $message }}</div>@endforeach
            </div>
        @endif
        <div class="card">
            <div class="card-header"><h4 class="mb-0">{{ $category->exists ? __('categories.edit') : __('categories.add') }}</h4></div>
            <div class="card-body">
                <form method="POST" action="{{ $category->exists ? route('admin.trade.categories.update', $category->id) : route('admin.trade.categories.store') }}">
                    @csrf
                    @if($category->exists) @method('PUT') @endif

                    <div class="form-group">
                        <label for="code">{{ __('categories.code') }}</label>
                        @if($category->exists)
                            <input id="code" class="form-control" value="{{ $category->code }}" disabled>
                        @else
                            <input id="code" name="code" class="form-control" required maxlength="40" pattern="[a-z][a-z0-9_]*" value="{{ old('code') }}">
                            <small class="form-text text-muted">{{ __('categories.code_help') }}</small>
                        @endif
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="label_en">{{ __('categories.label_en') }}</label>
                            <input id="label_en" name="label_en" class="form-control" required maxlength="120" value="{{ old('label_en', $category->label_en) }}">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="label_am">{{ __('categories.label_am') }}</label>
                            <input id="label_am" name="label_am" class="form-control" required maxlength="120" lang="am" value="{{ old('label_am', $category->label_am) }}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="description_en">{{ __('categories.description_en') }}</label>
                            <textarea id="description_en" name="description_en" class="form-control" rows="2" maxlength="500">{{ old('description_en', $category->description_en) }}</textarea>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="description_am">{{ __('categories.description_am') }}</label>
                            <textarea id="description_am" name="description_am" class="form-control" rows="2" maxlength="500" lang="am">{{ old('description_am', $category->description_am) }}</textarea>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="business_role">{{ __('categories.role') }}</label>
                        <select id="business_role" name="business_role" class="form-control" required @disabled($inUse)>
                            @foreach(\App\Support\BusinessRole::values() as $role)
                                <option value="{{ $role }}" @selected(old('business_role', $category->business_role) === $role)>{{ __('categories.roles.' . $role) }}</option>
                            @endforeach
                        </select>
                        @if($inUse)<input type="hidden" name="business_role" value="{{ $category->business_role }}">@endif
                        <small class="form-text text-muted">{{ __('categories.role_help') }}</small>
                    </div>

                    <fieldset class="form-group">
                        <legend class="col-form-label pt-0">{{ __('categories.activities') }}</legend>
                        @php($allowed = old('capabilities', $category->allowedCapabilities()))
                        @foreach(\App\Support\Capability::values() as $cap)
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="cap_{{ $cap }}" name="capabilities[]" value="{{ $cap }}" @checked(in_array($cap, (array) $allowed, true))>
                                <label class="custom-control-label" for="cap_{{ $cap }}">{{ __('categories.capabilities.' . $cap) }}</label>
                            </div>
                        @endforeach
                        <small class="form-text text-muted">{{ __('categories.activities_help') }}</small>
                    </fieldset>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="sort_order">{{ __('categories.sort_order') }}</label>
                            <input id="sort_order" name="sort_order" type="number" min="0" class="form-control" value="{{ old('sort_order', $category->sort_order ?? 0) }}">
                        </div>
                    </div>
                    <div class="custom-control custom-checkbox mb-2">
                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" @checked(old('is_active', $category->is_active))>
                        <label class="custom-control-label" for="is_active">{{ __('categories.active') }}</label>
                    </div>
                    <div class="custom-control custom-checkbox mb-4">
                        <input type="checkbox" class="custom-control-input" id="legally_confirmed" name="legally_confirmed" value="1" @checked(old('legally_confirmed', $category->legally_confirmed))>
                        <label class="custom-control-label" for="legally_confirmed">{{ __('categories.legally_confirmed') }}</label>
                    </div>

                    <button type="submit" class="btn btn-primary">{{ __('categories.save') }}</button>
                    <a href="{{ route('admin.trade.categories.index') }}" class="btn btn-link">{{ __('categories.cancel') }}</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
