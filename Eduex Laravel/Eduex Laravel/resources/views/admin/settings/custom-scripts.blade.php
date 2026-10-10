@extends('layouts.admin')

@section('title', 'Custom CSS & JS')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/admin/modules/codemirror/lib/codemirror.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/modules/codemirror/theme/duotone-dark.css') }}">
    <style>
        .CodeMirror {
            height: 250px;
            border: 1px solid #e4e6fc;
            border-radius: 5px;
        }
    </style>
@endpush

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Settings</a></div>
        <div class="breadcrumb-item active">Custom CSS & JS</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Custom Scripts & Styling</h4>
                    <a href="{{ route('admin.settings.index') }}" class="btn btn-secondary"> <i
                            class="fas fa-arrow-left"></i> Back to Settings</a>
                </div>
                <form action="{{ route('admin.settings.custom_scripts.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Use these fields to inject custom code into your platform. These changes will reflect on the frontend.
                        </div>

                        <div class="form-group">
                            <label for="custom_css">Custom CSS</label>
                            <p class="text-muted small">Enter CSS code without <code>&lt;style&gt;</code> tags. This will be injected into the <code>&lt;head&gt;</code> of all pages.</p>
                            <textarea name="custom_css" id="custom_css" class="form-control">{{ old('custom_css', $settings['custom_css'] ?? '') }}</textarea>
                            @error('custom_css')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="custom_js">Custom JavaScript</label>
                            <p class="text-muted small">Enter JS code without <code>&lt;script&gt;</code> tags. This will be injected before the <code>&lt;/body&gt;</code> tag.</p>
                            <textarea name="custom_js" id="custom_js" class="form-control">{{ old('custom_js', $settings['custom_js'] ?? '') }}</textarea>
                            @error('custom_js')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr>

                        <div class="form-group">
                            <label for="header_script">Header Scripts (Full Script Tags)</label>
                            <p class="text-muted small">Enter full HTML tags (e.g., <code>&lt;script&gt;...&lt;/script&gt;</code>, <code>&lt;meta&gt;</code>). Injected into the <code>&lt;head&gt;</code>.</p>
                            <textarea name="header_script" id="header_script" class="form-control">{{ old('header_script', $settings['header_script'] ?? '') }}</textarea>
                            @error('header_script')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="footer_script">Footer Scripts (Full Script Tags)</label>
                            <p class="text-muted small">Enter full HTML tags (e.g., <code>&lt;script&gt;...&lt;/script&gt;</code>). Injected before the <code>&lt;/body&gt;</code>.</p>
                            <textarea name="footer_script" id="footer_script" class="form-control">{{ old('footer_script', $settings['footer_script'] ?? '') }}</textarea>
                            @error('footer_script')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-primary">Save Custom Scripts</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/admin/modules/codemirror/lib/codemirror.js') }}"></script>
    <script src="{{ asset('assets/admin/modules/codemirror/mode/javascript/javascript.js') }}"></script>
    <script src="{{ asset('assets/admin/modules/codemirror/mode/css/css.js') }}"></script>
    <script src="{{ asset('assets/admin/modules/codemirror/mode/htmlmixed/htmlmixed.js') }}"></script>
    <script src="{{ asset('assets/admin/modules/codemirror/mode/xml/xml.js') }}"></script>
    <script>
        $(document).ready(function() {
            var cssEditor = CodeMirror.fromTextArea(document.getElementById("custom_css"), {
                lineNumbers: true,
                mode: "css",
                theme: "duotone-dark",
            });
            var jsEditor = CodeMirror.fromTextArea(document.getElementById("custom_js"), {
                lineNumbers: true,
                mode: "javascript",
                theme: "duotone-dark",
            });
            var headerEditor = CodeMirror.fromTextArea(document.getElementById("header_script"), {
                lineNumbers: true,
                mode: "htmlmixed",
                theme: "duotone-dark",
            });
            var footerEditor = CodeMirror.fromTextArea(document.getElementById("footer_script"), {
                lineNumbers: true,
                mode: "htmlmixed",
                theme: "duotone-dark",
            });
        });
    </script>
@endpush
