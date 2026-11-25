<?php

namespace App\Observers;

use App\Models\Contact;
use App\Models\Item;

/**
 * Zapisuje historię zmian przedmiotu (kto, kiedy, co) do item_histories —
 * moduł "Historia i audyt" z dokumentu koncepcyjnego.
 */
class ItemObserver
{
    /** Pola, których zmiana jest warta odnotowania w historii. */
    private const TRACKED_FIELDS = [
        'name', 'value', 'condition', 'status', 'category_id', 'storage_location_id',
    ];

    public function created(Item $item): void
    {
        $item->histories()->create([
            'user_id' => auth()->id(),
            'action' => 'created',
        ]);
    }

    public function updated(Item $item): void
    {
        foreach (self::TRACKED_FIELDS as $field) {
            if (! $item->wasChanged($field)) {
                continue;
            }

            $item->histories()->create([
                'user_id' => auth()->id(),
                'action' => 'updated',
                'field' => $field,
                'old_value' => (string) $item->getOriginal($field),
                'new_value' => (string) $item->getAttribute($field),
            ]);
        }

        // Osoba odpowiedzialna jako jeden czytelny wpis ("Opiekun: Jan Kowalski"), nie dwa numery.
        if ($item->wasChanged('responsible_contact_id') || $item->wasChanged('responsible_role')) {
            $item->histories()->create([
                'user_id' => auth()->id(),
                'action' => 'responsible_changed',
                'old_value' => self::responsibleLabel($item->getOriginal('responsible_contact_id'), $item->getOriginal('responsible_role')),
                'new_value' => self::responsibleLabel($item->responsible_contact_id, $item->responsible_role),
            ]);
        }
    }

    private static function responsibleLabel(mixed $contactId, ?string $role): ?string
    {
        if (! $contactId) {
            return null;
        }
        $name = Contact::find($contactId)?->name ?? '#'.$contactId;

        return __(Item::RESPONSIBLE_ROLES[$role] ?? Item::RESPONSIBLE_ROLES['opiekun']).': '.$name;
    }
}
