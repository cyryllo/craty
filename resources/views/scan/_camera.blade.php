{{-- Podgląd kamery skanera (resources/js/scan.js), wspólny dla zwykłego
     skanu, serii skanów i inwentaryzacji. Parametry: $mode (single|batch),
     $hint, opcjonalnie $recordUrl i $storageKey (inwentaryzacja). --}}
<div id="scanner"
     data-mode="{{ $mode }}"
     data-lookup-url="{{ route('scan.lookup') }}"
     data-resolve-url="{{ route('scan.resolve') }}"
     data-quick-add-url="{{ route('scan.quick-add.create') }}"
     @isset($recordUrl) data-record-url="{{ $recordUrl }}" @endisset
     @isset($storageKey) data-storage-key="{{ $storageKey }}" @endisset
     data-err-no-camera="{{ __('No camera found on this device.') }}"
     data-err-permission="{{ __('Camera access was denied. Allow it in your browser settings and reload the page.') }}"
     data-err-generic="{{ __('Something went wrong while scanning. Try again.') }}"
     data-msg-added="{{ __('Added') }}"
     data-msg-duplicate="{{ __('Already on the list') }}"
     data-msg-unknown="{{ __('Unknown code') }}"
     data-msg-remove="{{ __('Remove from the list') }}"
     class="bg-white rounded-lg shadow p-5 space-y-4 dark:bg-gray-800">

    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $hint }}</p>

    <div class="relative bg-gray-900 rounded-lg overflow-hidden aspect-square">
        <video class="w-full h-full object-cover" muted playsinline></video>

        <div data-panel="starting" class="absolute inset-0 flex items-center justify-center text-white text-sm bg-gray-900/70">
            {{ __('Starting the camera…') }}
        </div>
        <div data-panel="redirecting" class="hidden absolute inset-0 flex items-center justify-center text-white text-sm bg-gray-900/70">
            {{ __('Code found — opening…') }}
        </div>
    </div>

    <div data-panel="error" class="hidden rounded-md bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-700 dark:bg-red-950 dark:border-red-800 dark:text-red-400" data-error-text></div>

    @if ($mode === 'batch')
        <input type="text" data-batch-manual inputmode="text" autocomplete="off" placeholder="{{ __('…or type a code and press Enter') }}" aria-label="{{ __('Code') }}" class="w-full rounded-md border-gray-300 text-sm dark:border-gray-600">
    @endif
</div>
