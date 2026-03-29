@php
    $user = auth()->user();
    $notifications = $user?->firebaseNotifications()
        ->latest('notification_user.created_at')
        ->take(5)
        ->get() ?? collect();
@endphp

<div x-data="{ open: false }" class="relative">
    <button @click="open = !open" class="relative p-2 focus:outline-none">
        <x-heroicon-o-bell class="w-6 h-6 text-gray-700 dark:text-gray-300"/>
    </button>

    <div
        x-show="open"
        @click.away="open = false"
        x-cloak
        class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 z-50"
    >
        <div class="p-3 border-b dark:border-gray-700">
            <span class="text-sm font-semibold">Notifiche Firebase</span>
        </div>

        <div class="max-h-80 overflow-y-auto">
            @forelse ($notifications as $notif)
                <div class="px-4 py-3 border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                    <p class="text-sm font-medium">{{ $notif->title ?? 'Notifica' }}</p>
                    <p class="text-xs text-gray-500">{{ $notif->body ?? '' }}</p>
                    <span class="text-[10px] text-gray-400">
                        {{ $notif->pivot->is_read ? 'Letta' : 'Non letta' }}
                        • {{ $notif->created_at->diffForHumans() }}
                    </span>
                </div>
            @empty
                <div class="p-4 text-sm text-gray-500 text-center">
                    Nessuna notifica
                </div>
            @endforelse
        </div>
    </div>
</div>
