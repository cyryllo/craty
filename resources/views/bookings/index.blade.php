<x-app-layout>
    @php($staff = auth()->user()->isMagazynier())
    @php($navParams = fn ($week) => array_filter(['week' => $week->format('Y-m-d'), 'item' => $selected]))
    @php($link = 'px-3 py-1.5 text-sm rounded-md border border-gray-300 bg-white hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700')

    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-200">{{ __('Machine booking') }}</h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('bookings.index', $navParams($weekStart->copy()->subWeek())) }}" class="{{ $link }}" aria-label="{{ __('Previous week') }}">←</a>
                <span class="text-sm font-medium text-gray-700 tabular-nums dark:text-gray-300">{{ $weekStart->format('d.m') }}–{{ $weekStart->copy()->addDays(6)->format('d.m.Y') }}</span>
                <a href="{{ route('bookings.index', $navParams($weekStart->copy()->addWeek())) }}" class="{{ $link }}" aria-label="{{ __('Next week') }}">→</a>
                @unless ($weekStart->isSameDay(today()->startOfWeek(\Illuminate\Support\Carbon::MONDAY)))
                    <a href="{{ route('bookings.index', array_filter(['item' => $selected])) }}" class="{{ $link }}">{{ __('This week') }}</a>
                @endunless
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
        @if ($machines->isEmpty())
            <div class="bg-white rounded-lg shadow p-6 text-sm text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                @if ($staff)
                    {{ __('There are no machines to book yet. Open an item, click Edit and tick "Stationary": it will show up here.') }}
                @else
                    {{ __('There are no machines to book yet.') }}
                @endif
            </div>
        @else
            <form method="GET" action="{{ route('bookings.index') }}" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="week" value="{{ $weekStart->format('Y-m-d') }}">
                <label for="machine_filter" class="text-sm text-gray-600 dark:text-gray-400">{{ __('Show') }}:</label>
                <select id="machine_filter" name="item" onchange="this.form.submit()" class="rounded-md border-gray-300 text-sm dark:border-gray-600">
                    <option value="">{{ __('all machines') }}</option>
                    @foreach ($machines as $machine)
                        <option value="{{ $machine->id }}" @selected($selected === $machine->id)>{{ $machine->name }}</option>
                    @endforeach
                </select>
                <span class="ms-auto flex flex-wrap gap-2 text-xs">
                    @foreach (\App\Models\MachineBooking::PURPOSES as $value => $text)
                        <span class="px-2 py-0.5 rounded {{ \App\Models\MachineBooking::purposeClass($value) }}">{{ __($text) }}</span>
                    @endforeach
                </span>
            </form>

            {{-- Telefon: zamiast tabeli lista dzień po dniu (tabela 7 dni nie mieści się na wąskim ekranie). --}}
            <div class="md:hidden bg-white rounded-lg shadow divide-y divide-gray-100 dark:bg-gray-800 dark:divide-gray-700">
                @foreach ($days as $day)
                    @php($dayBookings = $bookings->flatten()->filter(fn ($b) => $b->starts_at->isSameDay($day))->sortBy('starts_at'))
                    <div @class(['px-4 py-3 space-y-2', 'bg-indigo-50/50 dark:bg-indigo-950/40' => $day->isToday()])>
                        <p @class(['text-xs font-medium uppercase tracking-wide', 'text-indigo-700 dark:text-indigo-300' => $day->isToday(), 'text-gray-500 dark:text-gray-400' => ! $day->isToday()])>
                            {{ $day->translatedFormat('l') }} {{ $day->format('d.m') }}
                        </p>
                        @forelse ($dayBookings as $booking)
                            @include('bookings._entry', ['booking' => $booking, 'showItem' => true, 'showUser' => true, 'withDay' => false])
                        @empty
                            <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('Nothing booked.') }}</p>
                        @endforelse
                    </div>
                @endforeach
            </div>

            {{-- Grafik tygodnia: wiersze to maszyny, kolumny to dni. Kliknięcie
                 rezerwacji rozwija opis (i odwołanie, jeśli wolno). --}}
            <div class="hidden md:block bg-white rounded-lg shadow overflow-x-auto dark:bg-gray-800">
                <table class="w-full min-w-[56rem] text-sm table-fixed">
                    <thead class="bg-gray-50 text-gray-500 text-xs dark:bg-gray-900 dark:text-gray-400">
                        <tr>
                            <th class="text-left px-3 py-3 w-44">{{ __('Machine') }}</th>
                            @foreach ($days as $day)
                                <th @class(['text-left px-2 py-3 font-medium', 'text-indigo-700 bg-indigo-50 dark:text-indigo-300 dark:bg-indigo-950' => $day->isToday()])>
                                    <span class="uppercase">{{ $day->translatedFormat('D') }}</span> {{ $day->format('d.m') }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($shown as $machine)
                            <tr class="align-top">
                                <td class="px-3 py-3 font-medium text-gray-900 dark:text-gray-100">
                                    @if ($staff)
                                        <a href="{{ route('items.show', $machine) }}" class="hover:underline">{{ $machine->name }}</a>
                                    @else
                                        {{ $machine->name }}
                                    @endif
                                    @if ($machine->responsibleContact)
                                        <span class="block text-xs font-normal text-gray-500 dark:text-gray-400">{{ $machine->responsibleRoleLabel() }}: {{ $machine->responsibleContact->name }}</span>
                                    @endif
                                    @if ($machine->status === 'w_naprawie')
                                        <span class="block text-xs font-normal text-red-600 dark:text-red-400">{{ __('Under repair') }}</span>
                                    @endif
                                </td>
                                @foreach ($days as $day)
                                    <td @class(['px-1.5 py-2 space-y-1', 'bg-indigo-50/50 dark:bg-indigo-950/40' => $day->isToday()])>
                                        @foreach (($bookings[$machine->id] ?? collect())->filter(fn ($b) => $b->starts_at->isSameDay($day)) as $booking)
                                            <details class="rounded px-1.5 py-1 text-xs {{ \App\Models\MachineBooking::purposeClass($booking->purpose) }}">
                                                <summary class="cursor-pointer list-none">
                                                    <span class="font-semibold tabular-nums">{{ $booking->timeRange() }}</span>
                                                    <span class="block truncate">{{ $booking->user_name }}</span>
                                                </summary>
                                                <div class="mt-1 space-y-1">
                                                    <p>{{ $booking->purposeLabel() }}@if ($booking->description): {{ $booking->description }}@endif</p>
                                                    @if ($booking->canBeCancelledBy(auth()->user()))
                                                        <form method="POST" action="{{ route('bookings.destroy', $booking) }}" onsubmit="return confirm(@js(__('Cancel this booking?')));">
                                                            @csrf @method('DELETE')
                                                            <button class="underline">{{ __('Cancel booking') }}</button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </details>
                                        @endforeach
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="grid lg:grid-cols-2 gap-6 items-start">
                <div class="bg-white rounded-lg shadow p-5 dark:bg-gray-800">
                    <h3 class="font-medium text-gray-900 mb-3 dark:text-gray-100">{{ __('New booking') }}</h3>
                    @include('bookings._form', ['machines' => $machines, 'purposes' => $purposes, 'preselect' => $selected, 'filter' => $selected])
                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">{{ __('Times go in :step-minute steps. A booking is valid straight away; overlapping bookings of the same machine are refused.', ['step' => \App\Models\MachineBooking::STEP_MINUTES]) }}</p>
                </div>

                <div class="bg-white rounded-lg shadow p-5 space-y-3 dark:bg-gray-800">
                    <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ __('My bookings') }}</h3>
                    @forelse ($mine as $booking)
                        @include('bookings._entry', ['booking' => $booking, 'showItem' => true])
                    @empty
                        <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('You have no upcoming bookings.') }}</p>
                    @endforelse
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
