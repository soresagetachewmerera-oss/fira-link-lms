@extends('layouts.admin')

@section('title', __('messages.preview.title'))

@section('main-content')
<div class="card">
    <div class="card-body">
        <h1 class="h5">{{ __('messages.preview.title') }}</h1>
        <p class="text-muted">{{ __('messages.preview.intro') }}</p>

        @foreach ($groups as $group => $rows)
            <h2 class="h6 mt-4">{{ __('messages.preview.group', ['group' => $group]) }}</h2>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('messages.preview.type') }}</th>
                            <th scope="col">{{ __('messages.preview.class') }}</th>
                            <th scope="col">{{ __('messages.preview.channels') }}</th>
                            <th scope="col">{{ __('messages.preview.open') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td><code>{{ $row['type']->value }}</code></td>
                                <td>{{ $row['class'] }}</td>
                                <td>
                                    @foreach ($row['templates'] as $channel => $ready)
                                        <span class="badge {{ $ready ? 'badge-primary' : 'badge-light' }}">{{ $channel }}{{ $ready ? '' : ' · ' . __('messages.preview.not_written') }}</span>
                                    @endforeach
                                </td>
                                <td>
                                    @if ($row['templates']['mail'] ?? false)
                                        <a href="{{ route('admin.messages.show', ['type' => $row['type']->value, 'locale' => 'en']) }}" target="_blank" rel="noopener">EN</a> ·
                                        <a href="{{ route('admin.messages.show', ['type' => $row['type']->value, 'locale' => 'am']) }}" target="_blank" rel="noopener">AM</a> ·
                                        <a href="{{ route('admin.messages.show', ['type' => $row['type']->value, 'locale' => 'en', 'view' => 'text']) }}" target="_blank" rel="noopener">{{ __('messages.preview.text') }}</a>
                                    @endif
                                    @if ($row['templates']['sms'] ?? false)
                                        · <a href="{{ route('admin.messages.show', ['type' => $row['type']->value, 'locale' => 'am', 'view' => 'sms']) }}" target="_blank" rel="noopener">SMS</a>
                                    @endif
                                    @if ($row['templates']['push'] ?? false)
                                        · <a href="{{ route('admin.messages.show', ['type' => $row['type']->value, 'view' => 'push']) }}" target="_blank" rel="noopener">{{ __('messages.preview.push') }}</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>
</div>
@endsection
