{{-- Pole "komu" (BorrowerResolver): kontakt z listy albo nowy dodany w locie,
     a bez modułu Kontakty zwykły tekst. Wymaga na formularzu
     x-data="{ contact: ... }". Parametry: $contacts, $field (klasy pól),
     opcjonalnie $label (domyślnie "Komu wypożyczono"). --}}
@php($label ??= __('Loaned to whom'))
@if (\App\Support\Modules::isEnabled('contacts'))
    <select name="contact_id" x-model="contact" aria-label="{{ $label }}" required class="{{ $field }}">
        <option value="" disabled>{{ $label }} *</option>
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
    <input type="text" name="borrower_name" placeholder="{{ $label }} *" aria-label="{{ $label }}" value="{{ old('borrower_name') }}" required class="{{ $field }}">
    <x-input-error :messages="$errors->get('borrower_name')" />
@endif
