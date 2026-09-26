{{-- Przycisk do tej samej oferty na OLX/Allegro — tylko gdy link podano przy tworzeniu oferty. --}}
@if ($listing->external_url)
    @php $platformName = $listing->externalPlatformName(); @endphp
    <a href="{{ $listing->external_url }}" target="_blank" rel="noopener noreferrer nofollow"
       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600">
        <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z"/><path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z"/></svg>
        {{ $platformName ? __('View on :platform', ['platform' => $platformName]) : __('View listing') }}
    </a>
@endif
