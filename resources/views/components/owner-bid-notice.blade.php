@props(['message' => "This is your lot — you can't bid on your own listing."])
<div class="rounded-lg p-4 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 flex items-start gap-3">
    <svg class="w-5 h-5 text-gray-500 dark:text-gray-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <div>
        <p class="font-semibold text-gray-800 dark:text-gray-200">You own this lot</p>
        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $message }}</p>
    </div>
</div>
