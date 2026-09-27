<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sign in — Dorm Manager</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex">

    {{-- Left: branding panel --}}
    <div class="hidden lg:flex w-1/2 bg-blue-600 flex-col justify-between p-12 relative overflow-hidden">
        {{-- Decorative circles --}}
        <div class="absolute -top-24 -left-24 w-96 h-96 rounded-full bg-blue-500 opacity-40"></div>
        <div class="absolute top-1/3 -right-20 w-72 h-72 rounded-full bg-blue-500 opacity-30"></div>
        <div class="absolute -bottom-16 left-1/4 w-56 h-56 rounded-full bg-blue-700 opacity-50"></div>

        <div class="relative z-10 flex items-center gap-2.5">
            <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center text-blue-600 font-bold text-sm">D</div>
            <span class="text-white font-semibold text-base">Dorm Manager</span>
        </div>

        <div class="relative z-10">
            <h1 class="text-4xl font-bold text-white leading-tight mb-4">
                Dormitory billing,<br>on autopilot.
            </h1>
            <p class="text-blue-100 text-sm leading-relaxed mb-8">
                Automatic invoices, PayMongo payments and live status<br>updates for all 28 rooms.
            </p>
            <ul class="space-y-2.5 text-sm text-white">
                <li class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-green-400 shrink-0"></span>
                    Paid status updates in real time
                </li>
                <li class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-yellow-400 shrink-0"></span>
                    Late status updates in real time
                </li>
                <li class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-red-400 shrink-0"></span>
                    Overdue status updates in real time
                </li>
            </ul>
        </div>

        <p class="relative z-10 text-blue-300 text-xs">Placeholder UI · sample data only</p>
    </div>

    {{-- Right: login form --}}
    <div class="flex-1 flex items-center justify-center bg-white px-6">
        <div class="w-full max-w-sm">

            <h2 class="text-2xl font-bold text-gray-900 mb-1">Welcome back</h2>
            <p class="text-sm text-gray-500 mb-8">Sign in to your account</p>

            @if(session('success'))
                <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600">
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-widest text-gray-500 mb-1.5">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                           class="w-full border border-gray-200 rounded-lg px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition" />
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-widest text-gray-500 mb-1.5">Password</label>
                    <div class="relative">
                        <input id="password-input" type="password" name="password" required
                               class="w-full border border-gray-200 rounded-lg px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition pr-10" />
                        <button type="button"
                                onclick="const i=document.getElementById('password-input');i.type=i.type==='password'?'text':'password';this.querySelector('svg').classList.toggle('hidden')"
                                class="absolute inset-y-0 right-3 flex items-center text-gray-400 hover:text-gray-600">
                            {{-- Eye open --}}
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                        <input type="checkbox" name="remember" class="rounded border-gray-300 text-blue-600" />
                        Remember me
                    </label>
                    <a href="{{ route('password.request') }}" class="text-sm text-blue-600 hover:underline">
                        Forgot password?
                    </a>
                </div>

                <button type="submit"
                        class="w-full py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-colors mt-2">
                    Sign in
                </button>
            </form>

            <p class="text-center text-xs text-gray-400 mt-8 leading-relaxed">
                Owners, employees and tenants all sign in here.<br>
                You will be taken to the page for your role.
            </p>
        </div>
    </div>

</body>
</html>
