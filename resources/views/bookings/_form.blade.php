{{-- Formularz rezerwacji maszyny: na grafiku (wybór maszyny) i na karcie
     przedmiotu (jedna maszyna, ukryte pole, powrót na kartę). --}}
@php($field = 'w-full rounded-md border-gray-300 text-sm dark:border-gray-600')
@php($label = 'block text-xs text-gray-500 dark:text-gray-400')
<form method="POST" action="{{ route('bookings.store') }}" class="space-y-2">
    @csrf
    @if ($back ?? false)
        <input type="hidden" name="back" value="1">
    @endif
    @if (! empty($filter))
        <input type="hidden" name="filter" value="{{ $filter }}">
    @endif

    @if (($compact ?? false) && $machines->count() === 1)
        <input type="hidden" name="item_id" value="{{ $machines->first()->id }}">
    @else
        <div>
            <label for="booking_item" class="{{ $label }}">{{ __('Machine') }}</label>
            <select id="booking_item" name="item_id" required class="{{ $field }}">
                <option value="">{{ __('Choose a machine…') }}</option>
                @foreach ($machines as $machine)
                    <option value="{{ $machine->id }}" @selected((int) old('item_id', $preselect ?? null) === $machine->id)>{{ $machine->name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('item_id')" />
        </div>
    @endif

    {{-- Wąsko (karta przedmiotu): dzień w osobnym wierszu, godziny obok siebie. --}}
    <div class="grid grid-cols-2 gap-2 {{ ($compact ?? false) ? '' : 'sm:grid-cols-3' }}">
        <div class="col-span-2 {{ ($compact ?? false) ? '' : 'sm:col-span-1' }}">
            <label for="booking_date" class="{{ $label }}">{{ __('Day') }}</label>
            <input type="date" id="booking_date" name="date" value="{{ old('date', today()->format('Y-m-d')) }}" min="{{ today()->format('Y-m-d') }}" required class="{{ $field }}">
        </div>
        <div>
            <label for="booking_from" class="{{ $label }}">{{ __('From') }}</label>
            <input type="time" id="booking_from" name="from" value="{{ old('from') }}" step="{{ \App\Models\MachineBooking::STEP_MINUTES * 60 }}" required class="{{ $field }}">
        </div>
        <div>
            <label for="booking_to" class="{{ $label }}">{{ __('To') }}</label>
            <input type="time" id="booking_to" name="to" value="{{ old('to') }}" step="{{ \App\Models\MachineBooking::STEP_MINUTES * 60 }}" required class="{{ $field }}">
        </div>
    </div>
    <x-input-error :messages="$errors->get('date')" />
    <x-input-error :messages="$errors->get('from')" />
    <x-input-error :messages="$errors->get('to')" />

    <div>
        <label for="booking_purpose" class="{{ $label }}">{{ __('Purpose') }}</label>
        <select id="booking_purpose" name="purpose" required class="{{ $field }}">
            @foreach ($purposes as $value => $text)
                <option value="{{ $value }}" @selected(old('purpose', 'prywatny') === $value)>{{ __($text) }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('purpose')" />
    </div>
    <textarea name="description" rows="2" maxlength="1000" placeholder="{{ __('What for? e.g. project name, class (optional)') }}" aria-label="{{ __('Description') }}" class="{{ $field }}">{{ old('description') }}</textarea>
    <x-input-error :messages="$errors->get('description')" />
    <button class="w-full px-3 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">{{ __('Book') }}</button>
</form>
