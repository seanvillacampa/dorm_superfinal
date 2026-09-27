<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{!! $title ?? 'Dorm Manager' !!}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="h-full flex">

    {{-- ─── Sidebar ──────────────────────────────────────────────────────── --}}
    <aside class="w-52 shrink-0 flex flex-col bg-white border-r border-gray-200 h-screen sticky top-0">

        {{-- Logo --}}
        <div class="flex items-center gap-2.5 px-4 py-4 border-b border-gray-100">
            <div class="w-7 h-7 bg-blue-600 rounded-md flex items-center justify-center text-white font-bold text-sm select-none">D</div>
            <span class="font-semibold text-gray-900 text-sm">Dorm Manager</span>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 overflow-y-auto px-2 py-3 space-y-4 text-sm">

            {{-- MAIN --}}
            <div>
                <p class="px-2 mb-1 text-[10px] font-semibold uppercase tracking-widest text-gray-400">Main</p>
                <x-nav-link route="owner.dashboard" :active="request()->routeIs('owner.dashboard','staff.dashboard')">
                    <x-slot:icon><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg></x-slot:icon>
                    Dashboard
                </x-nav-link>
                <x-nav-link route="rooms.index" :active="request()->routeIs('rooms.*')">
                    <x-slot:icon><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></x-slot:icon>
                    Rooms
                </x-nav-link>
                <x-nav-link route="tenants.index" :active="request()->routeIs('tenants.*')">
                    <x-slot:icon><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/><path d="M19 8v6m-3-3h6" stroke-linecap="round"/></svg></x-slot:icon>
                    Tenants
                </x-nav-link>
                <x-nav-link route="contracts.index" :active="request()->routeIs('contracts.*')">
                    <x-slot:icon><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></x-slot:icon>
                    Contracts
                </x-nav-link>
            </div>

            {{-- BILLING --}}
            <div>
                <p class="px-2 mb-1 text-[10px] font-semibold uppercase tracking-widest text-gray-400">Billing</p>
                <x-nav-link route="invoices.index" :active="request()->routeIs('invoices.*')">
                    <x-slot:icon><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg></x-slot:icon>
                    Invoices
                </x-nav-link>
                <x-nav-link route="payments.index" :active="request()->routeIs('payments.*')">
                    <x-slot:icon><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg></x-slot:icon>
                    Payments
                </x-nav-link>
                <x-nav-link route="meter-readings.index" :active="request()->routeIs('meter-readings.*')">
                    <x-slot:icon><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg></x-slot:icon>
                    Meter Readings
                </x-nav-link>
            </div>

            {{-- OPERATIONS --}}
            <div>
                <p class="px-2 mb-1 text-[10px] font-semibold uppercase tracking-widest text-gray-400">Operations</p>
                <x-nav-link route="reports.index" :active="request()->routeIs('reports.*')">
                    <x-slot:icon><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg></x-slot:icon>
                    Reports
                </x-nav-link>
            </div>

            {{-- OWNER ADMIN --}}
            @if(auth()->user()?->hasRole('owner'))
            <div>
                <p class="px-2 mb-1 text-[10px] font-semibold uppercase tracking-widest text-gray-400">Owner Admin</p>
                <x-nav-link route="employees.index" :active="request()->routeIs('employees.*')">
                    <x-slot:icon><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg></x-slot:icon>
                    Employees &amp; Roles
                </x-nav-link>
                <x-nav-link route="settings.index" :active="request()->routeIs('settings.*')">
                    <x-slot:icon><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg></x-slot:icon>
                    Rates &amp; Settings
                </x-nav-link>
                <x-nav-link route="email-notifications.index" :active="request()->routeIs('email-notifications.*')">
                    <x-slot:icon><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg></x-slot:icon>
                    Email Notifications
                </x-nav-link>
                <x-nav-link route="audit-logs.index" :active="request()->routeIs('audit-logs.*')">
                    <x-slot:icon><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg></x-slot:icon>
                    Audit Logs
                </x-nav-link>
            </div>
            @endif

        </nav>

        {{-- Bottom user card + sign out --}}
        <div class="px-3 py-3 border-t border-gray-100">
            @php $user = auth()->user(); @endphp
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-white text-xs font-semibold shrink-0 select-none">
                    {{ strtoupper(substr($user->name, 0, 1)) }}{{ strtoupper(substr(strstr($user->name, ' '), 1, 1)) }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-medium text-gray-900 truncate">{{ $user->name }}</p>
                    <p class="text-[11px] text-gray-500 capitalize">{{ $user->primaryRole() }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="Sign out"
                            class="text-gray-400 hover:text-red-500 transition-colors p-1 rounded">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- ─── Main area (no topbar) ───────────────────────────────────────────── --}}
    <div class="flex-1 flex flex-col min-h-screen min-w-0">
        <main class="flex-1 overflow-auto p-6">
            {{ $slot }}
        </main>
    </div>

</body>
</html>
