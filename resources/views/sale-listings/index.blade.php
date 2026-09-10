<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Sale') }}</h2>
            @if ($draft->isNotEmpty())
                <a href="{{ route('sale-listings.export') }}" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                    {{ __('Export CSV') }} ({{ $draft->count() }})
                </a>
            @endif
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8">

        @include('sale-listings._tabs', ['active' => 'draft'])

        <div class="bg-sky-50 border border-sky-200 text-sky-800 text-sm rounded-md p-4 mb-6">
            {{ __('OLX does not offer a public bulk-listing export for regular accounts — the CSV file below contains ready-made titles/descriptions for manual posting or for uploading to an intermediary tool (e.g. BaseLinker). Once exported, a listing automatically moves to the "Listed" tab. You prepare the listing for the next item on its own item page, with the "Prepare sale listing" button.') }}
        </div>

        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                    <tr>
                        <th class="text-left px-4 py-3">{{ __('Item') }}</th>
                        <th class="text-left px-4 py-3">{{ __('Listing title') }}</th>
                        <th class="text-left px-4 py-3">{{ __('Price') }}</th>
                        <th class="text-left px-4 py-3">{{ __('Platform') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($draft as $listing)
                        <tr>
                            <td class="px-4 py-3">
                                <a href="{{ route('items.show', $listing->item) }}" class="text-gray-900 hover:underline">{{ $listing->item->name }}</a>
                                <div class="text-xs font-mono text-gray-400">{{ $listing->item->inventory_no }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $listing->title }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $listing->price ? number_format((float) $listing->price, 2, ',', ' ').' zł' : '—' }}</td>
                            <td class="px-4 py-3 text-gray-500 uppercase text-xs">{{ $listing->platform }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('items.sale-listing.create', $listing->item) }}" class="text-indigo-600 hover:underline">{{ __('edit content') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                                {{ __('No prepared listings yet. Go to an item page and click "Prepare sale listing".') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
