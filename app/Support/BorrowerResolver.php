<?php

namespace App\Support;

use App\Http\Controllers\ContactController;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * "Komu" z formularza: wspólne dla wypożyczenia, zestawu i rezerwacji.
 * Z modułem Kontakty: istniejący kontakt (contact_id) albo nowy dodany w
 * locie (contact_id = "new" + pola new_contact_*). Bez modułu: zwykły
 * tekst (borrower_name). Zawsze zwraca też nazwę z tej chwili, zapisywaną
 * obok kontaktu, żeby historia przetrwała usunięcie kontaktu.
 */
class BorrowerResolver
{
    /** @return array{0: ?Contact, 1: string} */
    public static function resolve(Request $request): array
    {
        if (! Modules::isEnabled('contacts')) {
            return [null, $request->validate(['borrower_name' => ['required', 'string', 'max:255']])['borrower_name']];
        }

        $request->validate(['contact_id' => ['required']], [
            'contact_id.required' => __('Choose who the item is loaned to.'),
        ]);

        if ($request->input('contact_id') === 'new') {
            $new = $request->validate(ContactController::rules('new_contact_'), [
                'new_contact_name.required' => __('Enter the name of the new contact.'),
            ]);
            $contact = Contact::create([
                'name' => $new['new_contact_name'],
                'type' => $new['new_contact_type'] ?? 'osoba',
                'phone' => $new['new_contact_phone'] ?? null,
                'email' => $new['new_contact_email'] ?? null,
            ]);
        } else {
            $request->validate(['contact_id' => [Rule::exists('contacts', 'id')]]);
            $contact = Contact::findOrFail($request->input('contact_id'));
        }

        return [$contact, $contact->name];
    }
}
