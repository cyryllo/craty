@php($active = $active ?? 'draft')
<div class="flex gap-6 border-b border-gray-200 mb-6 dark:border-gray-700">
    <a href="{{ route('sale-listings.index') }}"
       class="pb-3 text-sm font-medium border-b-2 {{ $active === 'draft' ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700' }} dark:text-gray-400">
        {{ __('Prepared') }} @isset($draftCount)<span class="text-gray-400 dark:text-gray-500">({{ $draftCount }})</span>@endisset
    </a>
    <a href="{{ route('sale-listings.exported') }}"
       class="pb-3 text-sm font-medium border-b-2 {{ $active === 'exported' ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700' }} dark:text-gray-400">
        {{ __('Listed offers') }} @isset($exportedCount)<span class="text-gray-400 dark:text-gray-500">({{ $exportedCount }})</span>@endisset
    </a>
</div>
