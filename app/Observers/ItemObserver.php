<?php

namespace App\Observers;

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
    }
}
