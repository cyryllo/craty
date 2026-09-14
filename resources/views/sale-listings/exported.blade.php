<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Sale') }}</h2>
    </x-slot>

    <div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8">

        @include('sale-listings._tabs', ['active' => 'exported'])

        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                    <tr>
                        <th class="text-left px-4 py-3">{{ __('Item') }}</th>
                        <th class="text-left px-4 py-3">{{ __('Listing title') }}</th>
                        <th class="text-left px-4 py-3">{{ __('Price') }}</th>
                        <th class="text-left px-4 py-3">{{ __('Exported') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($exported as $listing)
                        <tr>
                            <td class="px-4 py-3">
                                <a href="{{ route('items.show', $listing->item) }}" class="text-gray-900 hover:underline">{{ $listing->item->name }}</a>
                                <div class="text-xs font-mono text-gray-400">{{ $listing->item->inventory_no }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $listing->title }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $listing->price ? number_format((float) $listing->price, 2, ',', ' ').' zł' : '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $listing->exported_at?->format('d.m.Y H:i') }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <form method="POST" action="{{ route('sale-listings.mark-sold', $listing) }}" onsubmit="return confirm('{{ __('Mark as sold?') }}');" class="inline">
                                    @csrf
                                    <button class="text-emerald-700 hover:underline">{{ __('mark as sold') }}</button>
                                </form>
                                <form method="POST" action="{{ route('sale-listings.withdraw', $listing) }}" onsubmit="return confirm('{{ __('Withdraw this listing from sale?') }}');" class="inline ms-3">
                                    @csrf
                                    <button class="text-red-600 hover:underline">{{ __('withdraw') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                                {{ __('Nothing listed yet — export prepared listings from the "Prepared" tab.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $exported->links() }}
    </div>
</x-app-layout>
