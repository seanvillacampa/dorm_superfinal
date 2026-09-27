@props(['title', 'subtitle' => null, 'badge' => null])

<div class="flex items-center justify-between mb-6">
    <div>
        <div class="flex items-center gap-2">
            <h1 class="text-2xl font-bold text-gray-900">{!! $title !!}</h1>
            @if($badge)
                <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">{{ $badge }}</span>
            @endif
        </div>
        @if($subtitle)
            <p class="text-sm text-gray-500 mt-0.5">{{ $subtitle }}</p>
        @endif
    </div>
    @if(isset($actions))
        <div class="flex items-center gap-2">{{ $actions }}</div>
    @endif
</div>
