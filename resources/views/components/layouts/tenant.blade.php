<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{!! $title ?? 'Dorm Manager' !!}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full">

    {{-- Top nav --}}
    <header class="bg-white border-b border-gray-200">
        <div class="max-w-3xl mx-auto px-4 flex items-center h-12 gap-6">
            <div class="flex items-center gap-2 mr-4">
                <div class="w-6 h-6 bg-blue-600 rounded-md flex items-center justify-center text-white font-bold text-xs">D</div>
                <span class="font-semibold text-sm text-gray-900">Dorm Manager</span>
            </div>

            @php
                $links = [
                    'tenant.bill'     => 'My Bill',
                    'tenant.payments' => 'Payment History',
                    'tenant.deposit'  => 'Deposit',
                    'tenant.contract' => 'Contract',
                ];
            @endphp
            @foreach($links as $route => $label)
                <a href="{{ route($route) }}"
                   class="text-sm font-medium pb-0.5 {{ request()->routeIs($route)
                        ? 'text-blue-600 border-b-2 border-blue-600'
                        : 'text-gray-500 hover:text-gray-800' }}">
                    {{ $label }}
                </a>
            @endforeach

            <div class="ml-auto flex items-center gap-3">
                <span class="flex items-center gap-1.5 text-xs text-green-600 font-medium">
                    <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse inline-block"></span>
                    Live
                </span>
                @php $u = auth()->user(); @endphp
                <div class="flex items-center gap-1.5">
                    <div class="w-6 h-6 rounded-full bg-blue-600 text-white text-xs font-semibold flex items-center justify-center">
                        {{ strtoupper(substr($u->name,0,1)) }}
                    </div>
                    <span class="text-sm font-medium text-gray-800">{{ $u->name }}</span>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-xs text-gray-400 hover:text-red-500">Sign out</button>
                </form>
            </div>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-4 py-8">
        {{ $slot }}
    </main>

</body>
</html>
