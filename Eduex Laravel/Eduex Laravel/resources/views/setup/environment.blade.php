@extends('setup.layout')

@section('content')
    <h2>Environment configuration</h2>
    <p class="setup-lead">
        Update the values below to match your deployment environment. The wizard will safely persist the changes to your
        <code>.env</code> file.
    </p>

    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-error">
            <strong>We spotted a few issues.</strong> Please review the highlighted fields and try again.
        </div>
    @endif

    <form action="{{ route('setup.environment.store') }}" method="POST">
        @csrf

        <div class="field-group field-group-lg">
            @foreach ($sections as $section)
                <section class="setup-section">
                    <header class="setup-section-header">
                        <h3 class="setup-section-title">{{ $section['title'] }}</h3>
                        <p class="setup-section-description">{{ $section['description'] ?? '' }}</p>
                    </header>

                    <div class="field-group">
                        @foreach ($section['fields'] as $key => $field)
                            <div class="field">
                                <label for="{{ $key }}">{{ $field['label'] }}</label>

                                @php
                                    $value = old(
                                        $key,
                                        $values[$key] ?? ($field['default'] ?? '')
                                    );
                                    $type = $field['type'] ?? 'text';
                                    $inputName = $key;
                                @endphp

                                @if ($type === 'select')
                                    <select name="{{ $inputName }}" id="{{ $key }}">
                                        @foreach ($field['options'] as $optionValue => $label)
                                            <option value="{{ $optionValue }}"
                                                {{ (string) $value === (string) $optionValue ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                @elseif ($type === 'toggle')
                                    @php
                                        $checked = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                                    @endphp
                                    <label class="toggle">
                                        <input type="hidden" name="{{ $inputName }}" value="false">
                                        <input type="checkbox" name="{{ $inputName }}" value="true"
                                            {{ $checked ? 'checked' : '' }}>
                                        <span class="track">
                                            <span class="thumb"></span>
                                        </span>
                                    </label>
                                @else
                                    <input
                                        type="{{ $type === 'number' ? 'number' : ($type === 'password' ? 'password' : 'text') }}"
                                        id="{{ $key }}"
                                        name="{{ $inputName }}"
                                        value="{{ $type === 'password' ? '' : $value }}"
                                        autocomplete="off">
                                @endif

                                @error($key)
                                    <small style="color:#dc2626;">{{ $message }}</small>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>

        <div class="setup-actions">
            <a class="btn btn-secondary" href="{{ route('setup.permissions') }}">
                ← Back
            </a>

            <button type="submit" class="btn btn-primary">
                Save &amp; continue
            </button>
        </div>
    </form>
@endsection

