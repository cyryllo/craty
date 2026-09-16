<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-200">{{ $item->name }}</h2>
                <p class="text-sm font-mono text-gray-400 dark:text-gray-500">{{ $item->inventory_no }}</p>
            </div>
            @if (auth()->user()->isMagazynier())
                <div class="flex gap-2">
                    <a href="{{ route('items.label', $item) }}" target="_blank" class="px-3 py-2 bg-white border border-gray-300 text-sm font-medium rounded-md hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:hover:bg-gray-700">{{ __('Print label') }}</a>
                    <a href="{{ route('items.edit', $item) }}" class="px-3 py-2 bg-white border border-gray-300 text-sm font-medium rounded-md hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:hover:bg-gray-700">{{ __('Edit') }}</a>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8 grid lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 space-y-6">
            @if ($item->photos->isNotEmpty())
                <div class="bg-white rounded-lg shadow p-4 dark:bg-gray-800">
                    <div class="grid grid-cols-3 gap-2">
                        @foreach ($item->photos as $photo)
                            <img src="{{ $photo->url() }}" class="aspect-square object-cover rounded-md w-full">
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="bg-white rounded-lg shadow p-5 space-y-4 dark:bg-gray-800">
                <dl class="grid sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Serial number') }}</dt>
                        <dd class="text-gray-900 font-mono dark:text-gray-100">{{ $item->serial_number ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('EAN code') }}</dt>
                        <dd class="text-gray-900 font-mono dark:text-gray-100">{{ $item->ean ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Category') }}</dt>
                        <dd class="text-gray-900 dark:text-gray-100">{{ $item->category?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Location') }}</dt>
                        <dd class="text-gray-900 dark:text-gray-100">{{ $item->storageLocation?->label() ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Value') }}</dt>
                        <dd class="text-gray-900 dark:text-gray-100">{{ $item->value ? number_format((float) $item->value, 2, ',', ' ').' zł' : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Condition') }}</dt>
                        <dd class="text-gray-900 dark:text-gray-100">{{ $item->conditionLabel() }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Purchase date') }}</dt>
                        <dd class="text-gray-900 dark:text-gray-100">{{ optional($item->purchased_at)->format('d.m.Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Status') }}</dt>
                        <dd class="text-gray-900 dark:text-gray-100">{{ $item->statusLabel() }}</dd>
                    </div>
                </dl>

                @if ($item->description)
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 mb-1 dark:text-gray-400">{{ __('Description') }}</h3>
                        <p class="text-sm text-gray-800 whitespace-pre-line dark:text-gray-200">{{ $item->description }}</p>
                    </div>
                @endif

                @if ($item->attachments->isNotEmpty())
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 mb-1 dark:text-gray-400">{{ __('Attachments') }}</h3>
                        <ul class="text-sm text-indigo-600 list-disc list-inside dark:text-indigo-400">
                            @foreach ($item->attachments as $attachment)
                                <li><a href="{{ $attachment->url() }}" class="hover:underline" target="_blank">{{ $attachment->label }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <div class="bg-white rounded-lg shadow p-5 dark:bg-gray-800">
                <h3 class="text-sm font-medium text-gray-500 mb-3 dark:text-gray-400">{{ __('Change history') }}</h3>
                <ul class="space-y-2 text-sm">
                    @forelse ($item->histories as $history)
                        <li class="flex justify-between text-gray-600 dark:text-gray-400">
                            <span>
                                {{ $history->user?->name ?? __('system') }} —
                                @if ($history->action === 'created') {{ __('item created') }}
                                @elseif ($history->field) {{ __('changed') }} <b>{{ $history->field }}</b>: {{ $history->old_value ?: '—' }} → {{ $history->new_value ?: '—' }}
                                @else {{ $history->action }}
                                @endif
                            </span>
                            <span class="text-gray-400 shrink-0 ms-3 dark:text-gray-500">{{ $history->created_at->format('d.m.Y H:i') }}</span>
                        </li>
                    @empty
                        <li class="text-gray-400 dark:text-gray-500">{{ __('No entries yet.') }}</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-lg shadow p-5 text-center dark:bg-gray-800">
                @if ($item->qr_path)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($item->qr_path) }}" alt="QR" class="mx-auto w-40 h-40">
                @endif
                <p class="mt-2 text-xs font-mono text-gray-400 dark:text-gray-500">{{ $item->inventory_no }}</p>
            </div>

            @if (auth()->user()->isMagazynier())
                <div class="bg-white rounded-lg shadow p-5 space-y-3 dark:bg-gray-800">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Loan') }}</h3>
                    @if ($item->currentLoan)
                        <p class="text-sm text-gray-800 dark:text-gray-200">{{ __('Loaned to') }}: <b>{{ $item->currentLoan->borrowerLabel() }}</b></p>
                        @if ($item->currentLoan->due_at)
                            <p class="text-sm {{ $item->currentLoan->isOverdue() ? 'text-red-600' : 'text-gray-500' }}">{{ __('Due date') }}: {{ $item->currentLoan->due_at->format('d.m.Y') }}</p>
                        @endif
                        <form method="POST" action="{{ route('loans.return', $item->currentLoan) }}">
                            @csrf
                            <button class="w-full mt-1 px-3 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">{{ __('Register return') }}</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('items.loans.store', $item) }}" class="space-y-2">
                            @csrf
                            <input type="text" name="borrower_name" placeholder="{{ __('Loaned to whom') }}" required class="w-full rounded-md border-gray-300 text-sm dark:border-gray-600">
                            <input type="date" name="due_at" class="w-full rounded-md border-gray-300 text-sm dark:border-gray-600">
                            <button class="w-full px-3 py-2 bg-white border border-gray-300 text-sm font-medium rounded-md hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:hover:bg-gray-700">{{ __('Loan out') }}</button>
                        </form>
                    @endif
                </div>

                <div class="bg-white rounded-lg shadow p-5 space-y-3 dark:bg-gray-800">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Sale') }}</h3>
                    @forelse ($item->saleListings as $listing)
                        <div class="text-sm">
                            <p class="text-gray-800 dark:text-gray-200">{{ $listing->title }} — {{ $listing->price ? number_format((float) $listing->price, 2, ',', ' ').' zł' : __('no price') }}</p>
                            <p class="text-gray-400 text-xs uppercase dark:text-gray-500">{{ $listing->platform }} · {{ $listing->statusLabel() }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('This item is not listed for sale yet.') }}</p>
                    @endforelse
                    @if ($item->activeSaleListing)
                        <div class="flex gap-2">
                            <form method="POST" action="{{ route('sale-listings.withdraw', $item->activeSaleListing) }}" onsubmit="return confirm('{{ __('Withdraw this listing from sale?') }}');" class="flex-1">
                                @csrf
                                <button class="w-full px-3 py-2 bg-white border border-gray-300 text-sm font-medium rounded-md hover:bg-gray-50 text-red-600 dark:bg-gray-800 dark:border-gray-600 dark:hover:bg-gray-700 dark:text-red-400">{{ __('withdraw') }}</button>
                            </form>
                            <form method="POST" action="{{ route('sale-listings.mark-sold', $item->activeSaleListing) }}" onsubmit="return confirm('{{ __('Mark as sold?') }}');" class="flex-1">
                                @csrf
                                <button class="w-full px-3 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">{{ __('mark as sold') }}</button>
                            </form>
                        </div>
                    @elseif ($item->draftSaleListing)
                        <div class="flex gap-2">
                            <form method="POST" action="{{ route('sale-listings.withdraw', $item->draftSaleListing) }}" onsubmit="return confirm('{{ __('Withdraw this listing from sale?') }}');" class="flex-1">
                                @csrf
                                <button class="w-full px-3 py-2 bg-white border border-gray-300 text-sm font-medium rounded-md hover:bg-gray-50 text-red-600 dark:bg-gray-800 dark:border-gray-600 dark:hover:bg-gray-700 dark:text-red-400">{{ __('withdraw') }}</button>
                            </form>
                            <form method="POST" action="{{ route('sale-listings.mark-listed', $item->draftSaleListing) }}" onsubmit="return confirm('{{ __('Mark as listed?') }}');" class="flex-1">
                                @csrf
                                <button class="w-full px-3 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">{{ __('list for sale') }}</button>
                            </form>
                        </div>
                    @else
                        <a href="{{ route('items.sale-listing.create', $item) }}" class="block w-full text-center px-3 py-2 bg-white border border-gray-300 text-sm font-medium rounded-md hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:hover:bg-gray-700">
                            {{ __('Prepare sale listing') }}
                        </a>
                    @endif
                </div>

                <form method="POST" action="{{ route('items.destroy', $item) }}" onsubmit="return confirm('{{ __('Remove this item from inventory?') }}');">
                    @csrf @method('DELETE')
                    <button class="w-full px-3 py-2 text-sm font-medium text-red-600 hover:underline dark:text-red-400">{{ __('Delete item') }}</button>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
