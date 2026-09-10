<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $item->name }}</h2>
                <p class="text-sm font-mono text-gray-400">{{ $item->inventory_no }}</p>
            </div>
            @if (auth()->user()->isMagazynier())
                <div class="flex gap-2">
                    <a href="{{ route('items.label', $item) }}" target="_blank" class="px-3 py-2 bg-white border border-gray-300 text-sm font-medium rounded-md hover:bg-gray-50">Drukuj etykietę</a>
                    <a href="{{ route('items.edit', $item) }}" class="px-3 py-2 bg-white border border-gray-300 text-sm font-medium rounded-md hover:bg-gray-50">Edytuj</a>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8 grid lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 space-y-6">
            @if ($item->photos->isNotEmpty())
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="grid grid-cols-3 gap-2">
                        @foreach ($item->photos as $photo)
                            <img src="{{ $photo->url() }}" class="aspect-square object-cover rounded-md w-full">
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="bg-white rounded-lg shadow p-5 space-y-4">
                <dl class="grid sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500">Numer seryjny</dt>
                        <dd class="text-gray-900 font-mono">{{ $item->serial_number ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Kod EAN</dt>
                        <dd class="text-gray-900 font-mono">{{ $item->ean ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Kategoria</dt>
                        <dd class="text-gray-900">{{ $item->category?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Lokalizacja</dt>
                        <dd class="text-gray-900">{{ $item->storageLocation?->label() ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Wartość</dt>
                        <dd class="text-gray-900">{{ $item->value ? number_format((float) $item->value, 2, ',', ' ').' zł' : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Stan techniczny</dt>
                        <dd class="text-gray-900">{{ \App\Models\Item::CONDITIONS[$item->condition] ?? $item->condition }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Data zakupu</dt>
                        <dd class="text-gray-900">{{ optional($item->purchased_at)->format('d.m.Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Status</dt>
                        <dd class="text-gray-900">{{ $item->statusLabel() }}</dd>
                    </div>
                </dl>

                @if ($item->description)
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 mb-1">Opis</h3>
                        <p class="text-sm text-gray-800 whitespace-pre-line">{{ $item->description }}</p>
                    </div>
                @endif

                @if ($item->specification)
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 mb-1">Specyfikacja</h3>
                        <p class="text-sm text-gray-800 whitespace-pre-line">{{ $item->specification }}</p>
                    </div>
                @endif

                @if ($item->attachments->isNotEmpty())
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 mb-1">Załączniki</h3>
                        <ul class="text-sm text-indigo-600 list-disc list-inside">
                            @foreach ($item->attachments as $attachment)
                                <li><a href="{{ $attachment->url() }}" class="hover:underline" target="_blank">{{ $attachment->label }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <div class="bg-white rounded-lg shadow p-5">
                <h3 class="text-sm font-medium text-gray-500 mb-3">Historia zmian</h3>
                <ul class="space-y-2 text-sm">
                    @forelse ($item->histories as $history)
                        <li class="flex justify-between text-gray-600">
                            <span>
                                {{ $history->user?->name ?? 'system' }} —
                                @if ($history->action === 'created') utworzono przedmiot
                                @elseif ($history->field) zmieniono <b>{{ $history->field }}</b>: {{ $history->old_value ?: '—' }} → {{ $history->new_value ?: '—' }}
                                @else {{ $history->action }}
                                @endif
                            </span>
                            <span class="text-gray-400 shrink-0 ms-3">{{ $history->created_at->format('d.m.Y H:i') }}</span>
                        </li>
                    @empty
                        <li class="text-gray-400">Brak wpisów.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-lg shadow p-5 text-center">
                @if ($item->qr_path)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($item->qr_path) }}" alt="QR" class="mx-auto w-40 h-40">
                @endif
                <p class="mt-2 text-xs font-mono text-gray-400">{{ $item->inventory_no }}</p>
            </div>

            @if (auth()->user()->isMagazynier())
                <div class="bg-white rounded-lg shadow p-5 space-y-3">
                    <h3 class="text-sm font-medium text-gray-500">Wypożyczenie</h3>
                    @if ($item->currentLoan)
                        <p class="text-sm text-gray-800">Wypożyczono: <b>{{ $item->currentLoan->borrowerLabel() }}</b></p>
                        @if ($item->currentLoan->due_at)
                            <p class="text-sm {{ $item->currentLoan->isOverdue() ? 'text-red-600' : 'text-gray-500' }}">Termin zwrotu: {{ $item->currentLoan->due_at->format('d.m.Y') }}</p>
                        @endif
                        <form method="POST" action="{{ route('loans.return', $item->currentLoan) }}">
                            @csrf
                            <button class="w-full mt-1 px-3 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">Zarejestruj zwrot</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('items.loans.store', $item) }}" class="space-y-2">
                            @csrf
                            <input type="text" name="borrower_name" placeholder="Komu wypożyczono" required class="w-full rounded-md border-gray-300 text-sm">
                            <input type="date" name="due_at" class="w-full rounded-md border-gray-300 text-sm">
                            <button class="w-full px-3 py-2 bg-white border border-gray-300 text-sm font-medium rounded-md hover:bg-gray-50">Wypożycz</button>
                        </form>
                    @endif
                </div>

                <div class="bg-white rounded-lg shadow p-5 space-y-3">
                    <h3 class="text-sm font-medium text-gray-500">Sprzedaż</h3>
                    @forelse ($item->saleListings as $listing)
                        <div class="text-sm">
                            <p class="text-gray-800">{{ $listing->title }} — {{ $listing->price ? number_format((float) $listing->price, 2, ',', ' ').' zł' : 'bez ceny' }}</p>
                            <p class="text-gray-400 text-xs uppercase">{{ $listing->platform }} · {{ $listing->status }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">Przedmiot nie jest jeszcze wystawiony na sprzedaż.</p>
                    @endforelse
                    <a href="{{ route('items.sale-listing.create', $item) }}" class="block w-full text-center px-3 py-2 bg-white border border-gray-300 text-sm font-medium rounded-md hover:bg-gray-50">
                        Przygotuj ofertę sprzedaży
                    </a>
                </div>

                <form method="POST" action="{{ route('items.destroy', $item) }}" onsubmit="return confirm('Usunąć przedmiot z ewidencji?');">
                    @csrf @method('DELETE')
                    <button class="w-full px-3 py-2 text-sm font-medium text-red-600 hover:underline">Usuń przedmiot</button>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
