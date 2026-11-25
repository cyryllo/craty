<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-200">{{ __('Import and export') }}</h2>
    </x-slot>

    @php
        $card = 'bg-white rounded-lg shadow p-6 space-y-4 dark:bg-gray-800';
        $button = 'inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700';
        $select = 'mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm dark:border-gray-700 dark:text-gray-300';
        $report = session('import_report');
    @endphp

    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
        <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ __('Files are CSV with a semicolon separator, which Excel, LibreOffice and Google Sheets open directly. Save your changes as CSV again before importing: .xlsx files are not accepted.') }}
        </p>

        @if ($report)
            <div class="{{ $card }}" data-import-report>
                <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ __('Import result') }}: {{ $report['title'] }}</h3>
                <p class="text-sm">
                    <span class="text-emerald-700 font-medium dark:text-emerald-400">{{ __('Added: :count', ['count' => $report['created']]) }}</span>
                    · <span class="text-indigo-700 font-medium dark:text-indigo-400">{{ __('Changed: :count', ['count' => $report['updated']]) }}</span>
                    · <span class="text-gray-600 dark:text-gray-400">{{ __('Unchanged: :count', ['count' => $report['unchanged']]) }}</span>
                    @if ($report['errors'])
                        · <span class="text-red-700 font-medium dark:text-red-400">{{ __('Errors: :count', ['count' => count($report['errors'])]) }}</span>
                    @endif
                </p>
                @if ($report['errors'])
                    <ul class="text-sm text-red-700 list-disc pl-5 space-y-1 dark:text-red-400">
                        @foreach ($report['errors'] as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif

        {{-- Przedmioty --}}
        <div class="{{ $card }}">
            <div class="flex items-center gap-3">
                <x-icon name="items" class="w-7 h-7 text-indigo-500 shrink-0 dark:text-indigo-400" />
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Items') }}</h3>
            </div>

            <form method="GET" action="{{ route('items.export') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                <div>
                    <x-input-label for="export_category" :value="__('Category')" />
                    <select id="export_category" name="category_id" class="{{ $select }}">
                        <option value="">{{ __('All') }}</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }} ({{ $category->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="export_status" :value="__('Status')" />
                    <select id="export_status" name="status" class="{{ $select }}">
                        <option value="">{{ __('All (in stock)') }}</option>
                        <option value="all">{{ __('All, including sold and retired') }}</option>
                        @foreach (\App\Models\Item::STATUSES as $key => $label)
                            <option value="{{ $key }}">{{ __($label) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <button type="submit" class="{{ $button }} w-full justify-center">⬇ {{ __('Export items') }}</button>
                </div>
            </form>

            <div class="border-t border-gray-100 pt-4 space-y-3 dark:border-gray-700">
                <h4 class="font-medium text-gray-900 dark:text-gray-100">{{ __('Import items') }}</h4>
                <ul class="text-sm text-gray-600 list-disc pl-5 space-y-1 dark:text-gray-400">
                    <li>{{ __('A row without an inventory number adds a new item; "nazwa" is required then.') }}</li>
                    <li>{{ __('A row with an inventory number changes that item. Export, edit in a spreadsheet and import the file back to change many items at once. Empty cells and missing columns leave the values as they are.') }}</li>
                    <li>{{ __('Category and location are given by their code (e.g. :category, :location). Status and condition accept their names or codes, "stacjonarny" accepts tak/nie, dates are YYYY-MM-DD or DD.MM.YYYY.', ['category' => 'NAR', 'location' => 'M1-R3-P2']) }}</li>
                    <li>{{ __('"osoba_odpowiedzialna" is the name of an existing contact (owner or caretaker of the item), "rola_osoby" is opiekun or wlasciciel.') }}</li>
                    <li>{{ __('Before anything is saved you see a preview with every change, and only "Confirm import" writes it.') }}</li>
                </ul>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Columns') }}: <code>{{ implode('; ', \App\Services\ItemImporter::EXPORT_COLUMNS) }}</code>. {{ __('The last four are for information only and are skipped on import.') }}</p>
                <a href="{{ route('items.export', ['wzor' => 1]) }}" class="{{ $button }}">⬇ {{ __('Empty template') }}</a>

                <form method="POST" action="{{ route('data-transfer.items.preview') }}" enctype="multipart/form-data" class="flex flex-col sm:flex-row gap-3 sm:items-end">
                    @csrf
                    <div class="grow">
                        <x-input-label for="file" :value="__('CSV file')" />
                        <input id="file" name="file" type="file" accept=".csv,text/csv,text/plain" required class="mt-1 block w-full text-sm">
                        <x-input-error :messages="$errors->get('file')" class="mt-1" />
                    </div>
                    <x-primary-button>{{ __('Check the file') }}</x-primary-button>
                </form>
            </div>
        </div>

        @foreach (array_filter([
            'contacts' => \App\Support\Modules::isEnabled('contacts') ? [__('Contacts'), 'contacts', \App\Services\SimpleCsvImporter::CONTACT_COLUMNS, __('Matched by name: an existing contact gets the filled-in cells, a new name adds a contact. "typ" is osoba, firma or serwis.')] : null,
            'consumables' => \App\Support\Modules::isEnabled('consumables') ? [__('Consumables'), 'consumables', \App\Services\SimpleCsvImporter::CONSUMABLE_COLUMNS, __('Matched by name. A different "ilosc" is saved as a stock correction in the movement history, so the import also works as a stock count.')] : null,
        ]) as $key => [$title, $icon, $columns, $help])
            <div class="{{ $card }}">
                <div class="flex items-center gap-3">
                    <x-icon :name="$icon" class="w-7 h-7 text-indigo-500 shrink-0 dark:text-indigo-400" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $title }}</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $help }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Columns') }}: <code>{{ implode('; ', $columns) }}</code></p>
                <a href="{{ route("data-transfer.{$key}.export") }}" class="{{ $button }}">⬇ {{ __('Export') }}</a>
                <form method="POST" action="{{ route("data-transfer.{$key}.import") }}" enctype="multipart/form-data" class="flex flex-col sm:flex-row gap-3 sm:items-end">
                    @csrf
                    <div class="grow">
                        <x-input-label :for="$key.'_file'" :value="__('CSV file')" />
                        <input id="{{ $key }}_file" name="{{ $key }}_file" type="file" accept=".csv,text/csv,text/plain" required class="mt-1 block w-full text-sm">
                        <x-input-error :messages="$errors->get($key.'_file')" class="mt-1" />
                    </div>
                    <x-primary-button>{{ __('Import') }}</x-primary-button>
                </form>
            </div>
        @endforeach

        {{-- Raporty: tylko do pobrania --}}
        <div class="{{ $card }}">
            <div class="flex items-center gap-3">
                <x-icon name="activity" class="w-7 h-7 text-indigo-500 shrink-0 dark:text-indigo-400" />
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Reports') }}</h3>
            </div>

            @if (\App\Support\Modules::isEnabled('loans'))
                <form method="GET" action="{{ route('data-transfer.loans.export') }}" class="flex flex-col sm:flex-row gap-3 sm:items-end">
                    <div class="grow">
                        <x-input-label for="loans_from" :value="__('Loan history, loaned out from (optional)')" />
                        <x-text-input id="loans_from" name="from" type="date" class="mt-1 block w-full" />
                    </div>
                    <button type="submit" class="{{ $button }} justify-center">⬇ {{ __('Loan history') }}</button>
                </form>
            @endif

            <form method="GET" action="{{ $stocktakes->isNotEmpty() ? route('stocktakes.report-csv', $stocktakes->first()) : '#' }}" class="flex flex-col sm:flex-row gap-3 sm:items-end"
                  onsubmit="this.action = this.querySelector('select').value">
                <div class="grow">
                    <x-input-label for="stocktake_report" :value="__('Stocktake report')" />
                    <select id="stocktake_report" class="{{ $select }}" @disabled($stocktakes->isEmpty())>
                        @forelse ($stocktakes as $stocktake)
                            <option value="{{ route('stocktakes.report-csv', $stocktake) }}">{{ $stocktake->finished_at->format('Y-m-d') }} · {{ $stocktake->label }}</option>
                        @empty
                            <option>{{ __('No finished stocktakes yet.') }}</option>
                        @endforelse
                    </select>
                </div>
                <button type="submit" class="{{ $button }} justify-center" @disabled($stocktakes->isEmpty())>⬇ {{ __('Stocktake report') }}</button>
            </form>

            @if (auth()->user()->isAdmin())
                <div class="flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('The whole activity log. Filtered exports are on the log page itself.') }}</p>
                    <a href="{{ route('settings.activity.export') }}" class="{{ $button }} justify-center">⬇ {{ __('Activity log') }}</a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
