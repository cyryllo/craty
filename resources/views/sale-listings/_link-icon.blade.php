@if ($listing->external_url)
    <a href="{{ $listing->external_url }}" target="_blank" rel="noopener noreferrer" @click.stop
       title="{{ $listing->externalPlatformName() ? __('View on :platform', ['platform' => $listing->externalPlatformName()]) : __('View listing') }}"
       class="ms-1 text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">🔗</a>
@endif
