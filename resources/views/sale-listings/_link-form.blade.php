{{-- Edycja linku do oferty na OLX/Allegro w popupie podglądu — wspólne dla "Przygotowane" i "Wystawione". Puste pole usuwa link. --}}
<form :action="listing.linkUrl" method="POST" class="pt-4 border-t border-gray-100 dark:border-gray-700">
    @csrf
    @method('PATCH')
    <div class="flex items-center justify-between">
        <x-input-label for="external_url" :value="__('Link to the listing on OLX / Allegro (optional)')" />
        <a x-show="listing.externalUrl" x-cloak :href="listing.externalUrl" target="_blank" rel="noopener noreferrer" class="text-xs text-indigo-600 hover:underline dark:text-indigo-400">{{ __('Open') }} ↗</a>
    </div>
    <div class="mt-1 flex gap-2">
        <input id="external_url" name="external_url" type="url" placeholder="https://" :value="listing.externalUrl"
               class="block w-full rounded-md border-gray-300 text-sm dark:border-gray-600">
        <button class="inline-flex items-center px-3 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 shrink-0">{{ __('Save') }}</button>
    </div>
    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('If provided, the public flea market shows a button leading to this listing.') }}</p>
</form>
