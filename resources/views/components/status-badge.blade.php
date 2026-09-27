@props(['status'])

@php
$cls = match(strtolower($status)) {
    'paid','active','recorded','sent','enabled' => 'bg-green-100 text-green-700',
    'late','ending_soon','partial','queued'     => 'bg-yellow-100 text-yellow-700',
    'overdue','failed','disabled','void'        => 'bg-red-100 text-red-700',
    'vacant','pending','needed'                 => 'bg-gray-100 text-gray-600',
    'maintenance','repairs'                     => 'bg-blue-100 text-blue-700',
    default                                     => 'bg-gray-100 text-gray-600',
};
$label = ucfirst(str_replace('_', ' ', $status));
@endphp

<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $cls }}">
    {{ $label }}
</span>
