<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Fira Export Docs Studio') | Fira Link</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Tailwind CSS (CDN for ultra-fast, standalone high-fidelity UI) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#faf5ff',
                            100: '#f3e8ff',
                            200: '#e9d5ff',
                            300: '#d8b4fe',
                            400: '#c084fc',
                            500: '#a855f7',
                            600: '#9333ea',
                            700: '#6D1A95', // Fira Signature Purple
                            800: '#581c87',
                            900: '#3b0764',
                        },
                        gold: {
                            500: '#D97706',
                            600: '#B45309',
                        }
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    }
                }
            }
        }
    </script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
        }
        .a4-paper {
            width: 210mm;
            min-height: 297mm;
            padding: 15mm;
            background: #ffffff;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            margin: 0 auto;
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>

    @stack('styles')
</head>
<body class="h-full flex flex-col antialiased text-slate-800">

    <!-- Top Navigation Bar -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Brand / Logo -->
                <div class="flex items-center space-x-6">
                    <a href="{{ route('export-docs.index') }}" class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-[#6D1A95] to-[#9333ea] flex items-center justify-center text-white shadow-md shadow-purple-900/20">
                            <i class="fa-solid fa-file-invoice text-lg"></i>
                        </div>
                        <div>
                            <div class="font-bold text-lg tracking-tight text-slate-900 flex items-center gap-2">
                                <span>Fira Export Docs</span>
                                <span class="bg-purple-100 text-[#6D1A95] text-[10px] font-extrabold px-2 py-0.5 rounded-full uppercase tracking-wider">Studio</span>
                            </div>
                            <div class="text-xs text-slate-400 font-medium">Ethiopian Coffee Trade Documentation</div>
                        </div>
                    </a>

                    <!-- Navigation Links -->
                    <nav class="hidden md:flex items-center space-x-1 pl-4 border-l border-slate-200">
                        <a href="{{ route('export-docs.index') }}" 
                           class="px-3 py-2 rounded-lg text-sm font-semibold transition {{ request()->routeIs('export-docs.index*') && !request()->routeIs('export-docs.shipments*') ? 'bg-purple-50 text-[#6D1A95]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                            <i class="fa-solid fa-table-cells mr-2"></i>Templates Catalog
                        </a>
                        <a href="{{ route('export-docs.shipments.index') }}" 
                           class="px-3 py-2 rounded-lg text-sm font-semibold transition {{ request()->routeIs('export-docs.shipments*') ? 'bg-purple-50 text-[#6D1A95]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                            <i class="fa-solid fa-ship mr-2"></i>Shipments (Single Source)
                        </a>
                    </nav>
                </div>

                <!-- Right Side Organization & User -->
                <div class="flex items-center space-x-4">
                    <a href="{{ route('home') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-900 flex items-center gap-1.5 transition">
                        <i class="fa-solid fa-arrow-left"></i>
                        <span>Return to Fira Link</span>
                    </a>

                    <div class="h-4 w-[1px] bg-slate-200"></div>

                    @auth
                        <div class="flex items-center space-x-3">
                            <div class="text-right hidden sm:block">
                                <div class="text-xs font-bold text-slate-900">{{ auth()->user()->name }}</div>
                                <div class="text-[11px] text-slate-500">{{ auth()->user()->primaryOrganization()->name ?? 'Specialty Exporter' }}</div>
                            </div>
                            <div class="w-8 h-8 rounded-full bg-purple-100 text-[#6D1A95] font-bold text-xs flex items-center justify-center border border-purple-200">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="text-xs font-bold bg-[#6D1A95] hover:bg-purple-800 text-white px-3.5 py-2 rounded-lg transition shadow-sm">
                            Sign In
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <!-- Global Toast / Alert Notifications -->
    @if(session('success'))
        <div class="bg-emerald-50 border-b border-emerald-200 px-4 py-3">
            <div class="max-w-7xl mx-auto flex items-center justify-between text-emerald-800 text-sm font-medium">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-circle-check text-emerald-600"></i>
                    <span>{{ session('success') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-rose-50 border-b border-rose-200 px-4 py-3">
            <div class="max-w-7xl mx-auto flex items-center justify-between text-rose-800 text-sm font-medium">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation text-rose-600"></i>
                    <span>{{ session('error') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.parentElement.remove()" class="text-rose-500 hover:text-rose-700">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>
    @endif

    <!-- Main Content Area -->
    <main class="flex-1 flex flex-col">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>

