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
                                @elseif ($history->action === 'loaned') {{ __('loaned to') }} <b>{{ $history->new_value }}</b>@if ($history->old_value) ({{ __('due') }} {{ $history->old_value }})@endif
                                @elseif ($history->action === 'returned') {{ __('returned by') }} <b>{{ $history->old_value }}</b>
                                @elseif ($history->action === 'repair_sent') {{ __('sent for repair to') }} <b>{{ $history->new_value }}</b>
                                @elseif ($history->action === 'repair_returned') {{ __('returned from repair at') }} <b>{{ $history->old_value }}</b>
                                @elseif ($history->action === 'inspected') {{ __('inspection') }}: <b>{{ $history->new_value }}</b>
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
            <div class="bg-white rounded-lg shadow p-5 text-center relative dark:bg-gray-800">
                @if (auth()->user()->isMagazynier())
                    <form method="POST" action="{{ route('items.regenerate-qr', $item) }}" class="absolute top-3 end-3" onsubmit="return confirm('{{ __('Regenerate the inventory number and QR code based on current category/location? An already-printed label will no longer match.') }}');">
                        @csrf
                        <button type="submit" title="{{ __('Regenerate QR code') }}" class="p-1.5 text-gray-400 rounded-md hover:bg-gray-100 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-gray-700 dark:hover:text-gray-300">
                            <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 01-9.201 2.466l-.312-.311h2.433a.75.75 0 000-1.5H3.989a.75.75 0 00-.75.75v4.242a.75.75 0 001.5 0v-2.43l.31.31a7 7 0 0011.712-3.138.75.75 0 00-1.449-.39zm1.23-3.723a.75.75 0 00.219-.53V2.929a.75.75 0 00-1.5 0V5.36l-.31-.31A7 7 0 003.239 8.188a.75.75 0 101.448.389A5.5 5.5 0 0113.89 6.11l.311.31h-2.432a.75.75 0 000 1.5h4.243a.75.75 0 00.53-.219z" clip-rule="evenodd"/></svg>
                        </button>
                    </form>
                @endif
                @if ($item->qr_path)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($item->qr_path) }}" alt="QR" class="mx-auto w-40 h-40">
                @endif
                <p class="mt-2 text-xs font-mono text-gray-400 dark:text-gray-500">{{ $item->inventory_no }}</p>
            </div>

            @if (auth()->user()->isMagazynier())
                @php($contactsEnabled = \App\Support\Modules::isEnabled('contacts'))
                @php($field = 'w-full rounded-md border-gray-300 text-sm dark:border-gray-600')
                <div class="bg-white rounded-lg shadow p-5 space-y-3 dark:bg-gray-800">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Loan') }}</h3>
                    <x-input-error :messages="$errors->get('loan')" />
                    @if ($loan = $item->currentLoan)
                        <p class="text-sm text-gray-800 dark:text-gray-200">{{ __('Loaned to') }}:
                            @if ($contactsEnabled && $loan->contact)
                                <a href="{{ route('contacts.show', $loan->contact) }}" class="font-bold hover:underline">{{ $loan->borrowerLabel() }}</a>
                            @else
                                <b>{{ $loan->borrowerLabel() }}</b>
                            @endif
                        </p>
                        @if ($loan->due_at)
                            <p class="text-sm {{ $loan->isOverdue() ? 'text-red-600' : 'text-gray-500' }}">{{ __('Due date') }}: {{ $loan->due_at->format('d.m.Y') }}</p>
                        @endif
                        <a href="{{ route('loans.protocol.issue', $loan) }}" target="_blank" class="inline-flex items-center gap-1 text-sm text-indigo-600 hover:underline dark:text-indigo-400">📄 {{ __('Issue protocol (PDF)') }}</a>

                        <form method="POST" action="{{ route('loans.return', $loan) }}" class="space-y-2 pt-3 border-t border-gray-100 dark:border-gray-700">
                            @csrf
                            <label for="condition_in" class="block text-xs text-gray-500 dark:text-gray-400">{{ __('Condition when returned') }}</label>
                            <select id="condition_in" name="condition_in" class="{{ $field }}">
                                @foreach (\App\Models\Item::CONDITIONS as $value => $label)
                                    <option value="{{ $value }}" @selected(old('condition_in', $item->condition) === $value)>{{ __($label) }}</option>
                                @endforeach
                            </select>
                            <textarea name="return_notes" rows="2" placeholder="{{ __('Notes on return, e.g. damage (optional)') }}" aria-label="{{ __('Notes on return') }}" class="{{ $field }}">{{ old('return_notes') }}</textarea>
                            <x-input-error :messages="$errors->get('return_notes')" />
                            <button class="w-full px-3 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">{{ __('Register return') }}</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('items.loans.store', $item) }}" class="space-y-2"
                              x-data="{ contact: '{{ old('contact_id', $contacts->isEmpty() ? 'new' : '') }}' }">
                            @csrf
                            @if ($contactsEnabled)
                                <select name="contact_id" x-model="contact" aria-label="{{ __('Loaned to whom') }}" required class="{{ $field }}">
                                    <option value="" disabled>{{ __('Loaned to whom') }} *</option>
                                    <option value="new">+ {{ __('New contact') }}</option>
                                    @foreach ($contacts as $option)
                                        <option value="{{ $option->id }}">{{ $option->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('contact_id')" />
                                <div x-show="contact === 'new'" x-cloak class="space-y-2 ps-3 border-s-2 border-indigo-200 dark:border-indigo-800">
                                    <input type="text" name="new_contact_name" placeholder="{{ __('Name (person or company)') }} *" aria-label="{{ __('Name (person or company)') }}" value="{{ old('new_contact_name') }}" x-bind:required="contact === 'new'" class="{{ $field }}">
                                    <x-input-error :messages="$errors->get('new_contact_name')" />
                                    <input type="tel" name="new_contact_phone" placeholder="{{ __('Phone') }}" aria-label="{{ __('Phone') }}" value="{{ old('new_contact_phone') }}" class="{{ $field }}">
                                    <input type="email" name="new_contact_email" placeholder="{{ __('Email') }}" aria-label="{{ __('Email') }}" value="{{ old('new_contact_email') }}" class="{{ $field }}">
                                    <x-input-error :messages="$errors->get('new_contact_email')" />
                                </div>
                            @else
                                <input type="text" name="borrower_name" placeholder="{{ __('Loaned to whom') }} *" aria-label="{{ __('Loaned to whom') }}" value="{{ old('borrower_name') }}" required class="{{ $field }}">
                                <x-input-error :messages="$errors->get('borrower_name')" />
                            @endif
                            <label for="due_at" class="block text-xs text-gray-500 dark:text-gray-400">{{ __('Due date (optional)') }}</label>
                            <input type="date" id="due_at" name="due_at" value="{{ old('due_at') }}" class="{{ $field }}">
                            <x-input-error :messages="$errors->get('due_at')" />
                            <textarea name="notes" rows="2" placeholder="{{ __('Notes, printed on the protocol (optional)') }}" aria-label="{{ __('Notes') }}" class="{{ $field }}">{{ old('notes') }}</textarea>
                            <button class="w-full px-3 py-2 bg-white border border-gray-300 text-sm font-medium rounded-md hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:hover:bg-gray-700">{{ __('Loan out') }}</button>
                        </form>
                    @endif

                    @if ($pastLoans->isNotEmpty())
                        <div class="pt-3 border-t border-gray-100 dark:border-gray-700">
                            <h4 class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2 dark:text-gray-400">{{ __('Previous loans') }}</h4>
                            <ul class="space-y-1.5 text-sm">
                                @foreach ($pastLoans as $past)
                                    <li class="text-gray-700 dark:text-gray-300">
                                        {{ $past->borrowerLabel() }}
                                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ $past->borrowed_at->format('d.m') }}–{{ $past->returned_at->format('d.m.Y') }}</span>
                                        <span class="block text-xs space-x-2">
                                            <a href="{{ route('loans.protocol.issue', $past) }}" target="_blank" class="text-indigo-600 hover:underline dark:text-indigo-400">{{ __('issue protocol') }}</a>
                                            <a href="{{ route('loans.protocol.return', $past) }}" target="_blank" class="text-indigo-600 hover:underline dark:text-indigo-400">{{ __('return protocol') }}</a>
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                @if ($service)
                    @php($badge = 'text-xs font-medium px-2 py-0.5 rounded-full')
                    @php($summary = 'cursor-pointer text-sm font-medium text-indigo-600 hover:underline dark:text-indigo-400')
                    <div class="bg-white rounded-lg shadow p-5 space-y-3 dark:bg-gray-800">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Service') }}</h3>
                        <x-input-error :messages="$errors->get('repair')" />

                        {{-- Naprawa --}}
                        @if ($repair = $service['openRepair'])
                            <div class="text-sm space-y-1">
                                <p class="text-gray-800 dark:text-gray-200">{{ __('Under repair at') }} <b>{{ $repair->serviceLabel() }}</b></p>
                                <p class="text-gray-500 dark:text-gray-400">
                                    {{ __('since') }} {{ $repair->sent_at->format('d.m.Y') }}
                                    @if ($repair->expected_at)
                                        · <span @class(['text-red-600 dark:text-red-400' => $repair->isOverdue()])>{{ __('expected') }} {{ $repair->expected_at->format('d.m.Y') }}</span>
                                    @endif
                                </p>
                                <p class="text-gray-600 whitespace-pre-line dark:text-gray-400">{{ $repair->fault_description }}</p>
                            </div>
                            <details @if ($errors->hasAny(['returned_at', 'cost', 'work_done'])) open @endif>
                                <summary class="{{ $summary }}">{{ __('Complete repair') }}</summary>
                                <form method="POST" action="{{ route('repairs.complete', $repair) }}" class="space-y-2 mt-2">
                                    @csrf
                                    <label for="returned_at" class="block text-xs text-gray-500 dark:text-gray-400">{{ __('Returned on') }}</label>
                                    <input type="date" id="returned_at" name="returned_at" value="{{ old('returned_at', today()->format('Y-m-d')) }}" required class="w-full rounded-md border-gray-300 text-sm dark:border-gray-600">
                                    <x-input-error :messages="$errors->get('returned_at')" />
                                    <input type="number" name="cost" step="0.01" min="0" placeholder="{{ __('Cost (zł, optional)') }}" aria-label="{{ __('Cost') }}" value="{{ old('cost') }}" class="w-full rounded-md border-gray-300 text-sm dark:border-gray-600">
                                    <x-input-error :messages="$errors->get('cost')" />
                                    <textarea name="work_done" rows="2" placeholder="{{ __('What was done (optional)') }}" aria-label="{{ __('What was done') }}" class="w-full rounded-md border-gray-300 text-sm dark:border-gray-600">{{ old('work_done') }}</textarea>
                                    <label for="repair_condition" class="block text-xs text-gray-500 dark:text-gray-400">{{ __('Condition after repair') }}</label>
                                    <select id="repair_condition" name="condition" class="w-full rounded-md border-gray-300 text-sm dark:border-gray-600">
                                        @foreach (\App\Models\Item::CONDITIONS as $value => $label)
                                            <option value="{{ $value }}" @selected(old('condition', $item->condition) === $value)>{{ __($label) }}</option>
                                        @endforeach
                                    </select>
                                    <button class="w-full px-3 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">{{ __('Complete repair') }}</button>
                                </form>
                            </details>
                        @elseif (! $item->currentLoan)
                            <details @if ($errors->hasAny(['fault_description', 'sent_at', 'expected_at', 'service_name'])) open @endif>
                                <summary class="{{ $summary }}">{{ __('Send for repair') }}</summary>
                                <form method="POST" action="{{ route('items.repairs.store', $item) }}" class="space-y-2 mt-2"
                                      x-data="{ contact: '{{ old('contact_id', '') }}' }">
                                    @csrf
                                    @if ($service['serviceContacts']->isNotEmpty())
                                        <select name="contact_id" x-model="contact" aria-label="{{ __('Repair service') }}" class="w-full rounded-md border-gray-300 text-sm dark:border-gray-600">
                                            <option value="">{{ __('Other / own workshop') }}</option>
                                            @foreach ($service['serviceContacts'] as $option)
                                                <option value="{{ $option->id }}">{{ $option->name }}@if ($option->type !== 'serwis') ({{ $option->typeLabel() }})@endif</option>
                                            @endforeach
                                        </select>
                                    @endif
                                    <input type="text" name="service_name" x-show="contact === ''" placeholder="{{ __('Repair service (empty = own workshop)') }}" aria-label="{{ __('Repair service') }}" value="{{ old('service_name') }}" class="w-full rounded-md border-gray-300 text-sm dark:border-gray-600">
                                    <textarea name="fault_description" rows="2" required placeholder="{{ __('Fault description') }} *" aria-label="{{ __('Fault description') }}" class="w-full rounded-md border-gray-300 text-sm dark:border-gray-600">{{ old('fault_description') }}</textarea>
                                    <x-input-error :messages="$errors->get('fault_description')" />
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label for="sent_at" class="block text-xs text-gray-500 dark:text-gray-400">{{ __('Sent on') }}</label>
                                            <input type="date" id="sent_at" name="sent_at" value="{{ old('sent_at', today()->format('Y-m-d')) }}" required class="w-full rounded-md border-gray-300 text-sm dark:border-gray-600">
                                        </div>
                                        <div>
                                            <label for="expected_at" class="block text-xs text-gray-500 dark:text-gray-400">{{ __('Expected back') }}</label>
                                            <input type="date" id="expected_at" name="expected_at" value="{{ old('expected_at') }}" class="w-full rounded-md border-gray-300 text-sm dark:border-gray-600">
                                        </div>
                                    </div>
                                    <x-input-error :messages="$errors->get('expected_at')" />
                                    <button class="w-full px-3 py-2 bg-white border border-gray-300 text-sm font-medium rounded-md hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:hover:bg-gray-700">{{ __('Send for repair') }}</button>
                                </form>
                            </details>
                        @endif

                        {{-- Gwarancja --}}
                        <div class="pt-3 border-t border-gray-100 text-sm dark:border-gray-700">
                            <span class="text-gray-500 dark:text-gray-400">{{ __('Warranty') }}:</span>
                            @if ($item->warranty_until)
                                <span class="text-gray-800 dark:text-gray-200">{{ __('until') }} {{ $item->warranty_until->format('d.m.Y') }}</span>
                                @if ($item->warrantyState() === 'expired')
                                    <span class="{{ $badge }} bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ __('expired') }}</span>
                                @elseif ($item->warrantyState() === 'soon')
                                    <span class="{{ $badge }} bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300">{{ __('ends soon') }}</span>
                                @endif
                            @else
                                <span class="text-gray-400 dark:text-gray-500">{{ __('not set (edit the item)') }}</span>
                            @endif
                        </div>

                        {{-- Przeglądy --}}
                        <div class="text-sm space-y-2">
                            <p>
                                <span class="text-gray-500 dark:text-gray-400">{{ __('Next inspection') }}:</span>
                                @if ($item->next_inspection_at)
                                    <span class="text-gray-800 dark:text-gray-200">{{ $item->next_inspection_at->format('d.m.Y') }}</span>
                                    @if ($item->inspectionState() === 'overdue')
                                        <span class="{{ $badge }} bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300">{{ __('overdue') }}</span>
                                    @elseif ($item->inspectionState() === 'soon')
                                        <span class="{{ $badge }} bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300">{{ __('soon') }}</span>
                                    @endif
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">{{ __('not scheduled') }}</span>
                                @endif
                                @if ($item->inspection_interval_months)
                                    <span class="block text-xs text-gray-400 dark:text-gray-500">{{ __('every :count months', ['count' => $item->inspection_interval_months]) }}</span>
                                @endif
                            </p>
                            <details @if ($errors->hasAny(['performed_at', 'passed'])) open @endif>
                                <summary class="{{ $summary }}">{{ __('Record an inspection') }}</summary>
                                <form method="POST" action="{{ route('items.inspections.store', $item) }}" class="space-y-2 mt-2">
                                    @csrf
                                    <label for="performed_at" class="block text-xs text-gray-500 dark:text-gray-400">{{ __('Performed on') }}</label>
                                    <input type="date" id="performed_at" name="performed_at" value="{{ old('performed_at', today()->format('Y-m-d')) }}" max="{{ today()->format('Y-m-d') }}" required class="w-full rounded-md border-gray-300 text-sm dark:border-gray-600">
                                    <x-input-error :messages="$errors->get('performed_at')" />
                                    <select name="passed" aria-label="{{ __('Result') }}" class="w-full rounded-md border-gray-300 text-sm dark:border-gray-600">
                                        <option value="1" @selected(old('passed', '1') === '1')>{{ __('Passed') }}</option>
                                        <option value="0" @selected(old('passed') === '0')>{{ __('Failed') }}</option>
                                    </select>
                                    <textarea name="inspection_notes" rows="2" placeholder="{{ __('Notes (optional)') }}" aria-label="{{ __('Notes') }}" class="w-full rounded-md border-gray-300 text-sm dark:border-gray-600">{{ old('inspection_notes') }}</textarea>
                                    <button class="w-full px-3 py-2 bg-white border border-gray-300 text-sm font-medium rounded-md hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:hover:bg-gray-700">{{ __('Save inspection') }}</button>
                                </form>
                            </details>
                        </div>

                        {{-- Historia --}}
                        @if ($service['repairs']->isNotEmpty() || $service['inspections']->isNotEmpty())
                            <div class="pt-3 border-t border-gray-100 dark:border-gray-700 space-y-1.5 text-sm">
                                <h4 class="text-xs font-medium text-gray-500 uppercase tracking-wide dark:text-gray-400">{{ __('Service history') }}</h4>
                                @foreach ($service['repairs'] as $past)
                                    <p class="text-gray-700 dark:text-gray-300">
                                        🔧 {{ $past->returned_at->format('d.m.Y') }} · {{ $past->serviceLabel() }}@if ($past->cost) · {{ number_format((float) $past->cost, 2, ',', ' ') }} zł @endif
                                        <span class="block text-xs text-gray-500 dark:text-gray-400">{{ \Illuminate\Support\Str::limit($past->work_done ?: $past->fault_description, 80) }}</span>
                                    </p>
                                @endforeach
                                @foreach ($service['inspections'] as $inspection)
                                    <p class="text-gray-700 dark:text-gray-300">
                                        ✔ {{ $inspection->performed_at->format('d.m.Y') }} · {{ __('inspection') }}:
                                        <span @class(['text-red-600 dark:text-red-400' => ! $inspection->passed])>{{ $inspection->passed ? __('passed') : __('failed') }}</span>
                                        @if ($inspection->notes)<span class="block text-xs text-gray-500 dark:text-gray-400">{{ \Illuminate\Support\Str::limit($inspection->notes, 80) }}</span>@endif
                                    </p>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                @if (\App\Support\Modules::isEnabled('sales'))
                <div class="bg-white rounded-lg shadow p-5 space-y-3 dark:bg-gray-800">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Sale') }}</h3>
                    @forelse ($item->saleListings as $listing)
                        <div class="text-sm">
                            <p class="text-gray-800 dark:text-gray-200">{{ $listing->title }} — {{ $listing->price ? number_format((float) $listing->price, 2, ',', ' ').' zł' : __('no price') }}</p>
                            <p class="text-gray-400 text-xs uppercase dark:text-gray-500">@if ($listing->externalPlatformName()){{ $listing->externalPlatformName() }} · @endif{{ $listing->statusLabel() }}</p>
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
                @endif

                <form method="POST" action="{{ route('items.destroy', $item) }}" onsubmit="return confirm('{{ __('Remove this item from inventory?') }}');">
                    @csrf @method('DELETE')
                    <button class="w-full px-3 py-2 text-sm font-medium text-red-600 hover:underline dark:text-red-400">{{ __('Delete item') }}</button>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
