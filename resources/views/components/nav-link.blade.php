@props(['route' => '#', 'active' => false])

@php
    $href = ($route !== '#') ? route($route) : '#';
    $base = 'flex items-center gap-2.5 w-full px-2 py-1.5 rounded-md text-sm transition-colors';
    $cls  = $active
        ? $base . ' bg-blue-50 text-blue-700 font-medium'
        : $base . ' text-gray-600 hover:bg-gray-100 hover:text-gray-900';
@endphp

<a href="{{ $href }}" class="{{ $cls }}">
    @isset($icon)
        <span class="{{ $active ? 'text-blue-600' : 'text-gray-400' }}">{{ $icon }}</span>
    @endisset
    {{ $slot }}
</a>
