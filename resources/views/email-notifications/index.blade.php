<x-layouts.app title="Email Notifications — Dorm Manager">

    <x-page-header title="Email Notifications" badge="Owner only"
        subtitle="Who gets emailed, and a log of every email sent" />

    <div class="grid grid-cols-[320px_1fr] gap-4 items-start">

        {{-- Left: alert preferences --}}
        <div class="space-y-4">
            <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-5">
                <h2 class="text-sm font-semibold text-gray-900 mb-4">My email alerts</h2>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-900">Payment received</p>
                            <p class="text-xs text-gray-400">Email me when a tenant pays</p>
                        </div>
                        <span class="w-10 h-5 bg-blue-600 rounded-full inline-block relative cursor-not-allowed opacity-60"
                              title="Coming soon"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-900">Payment missed</p>
                            <p class="text-xs text-gray-400">Email me when a tenant misses a due date</p>
                        </div>
                        <span class="w-10 h-5 bg-blue-600 rounded-full inline-block relative cursor-not-allowed opacity-60"
                              title="Coming soon"></span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-5">
                <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-3">Tenant emails (owner setting)</p>
                <div class="space-y-3">
                    @foreach([
                        'Send payment receipt to tenant',
                        'Send missed-payment notice to tenant',
                    ] as $label)
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-gray-700">{{ $label }}</p>
                            <span class="w-10 h-5 bg-blue-600 rounded-full inline-block cursor-not-allowed opacity-60"
                                  title="Coming soon"></span>
                        </div>
                    @endforeach
                </div>
                <p class="text-xs text-gray-400 mt-4">Toggle controls coming in a future update. Emails use the activation / password-reset infrastructure already in place.</p>
            </div>
        </div>

        {{-- Right: email log --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-900">Recent email activity</h2>
                <p class="text-xs text-gray-400">Tenant activation emails are logged here</p>
            </div>

            @if($emailLogs->isEmpty())
                <div class="px-4 py-10 text-center text-sm text-gray-400">
                    No email events logged yet. Emails are recorded here when tenants are registered or invoices are due.
                </div>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-xs text-gray-400 font-medium uppercase tracking-wide">
                            <th class="px-4 py-3 text-left">Time</th>
                            <th class="px-4 py-3 text-left">Recipient</th>
                            <th class="px-4 py-3 text-left">Type</th>
                            <th class="px-4 py-3 text-left">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($emailLogs as $log)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-400 text-xs whitespace-nowrap">
                                    {{ $log->created_at->format('M d, g:i A') }}
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ $log->user_label ?? '—' }}</td>
                                <td class="px-4 py-3"><x-status-badge status="Sent" /></td>
                                <td class="px-4 py-3 text-xs text-gray-500">{{ $log->description }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

</x-layouts.app>
