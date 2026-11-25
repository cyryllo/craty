<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Consumable;
use App\Models\Contact;
use App\Models\StorageLocation;
use App\Models\User;
use App\Support\Csv;

/**
 * Import i eksport kontaktów i materiałów eksploatacyjnych (Import i eksport,
 * dostępne z Ustawień). Prostsze niż przedmioty: bez podglądu, rekordy
 * dopasowywane po nazwie (bez rozróżniania wielkości liter). Istniejący
 * dostaje wartości z niepustych komórek, nowy powstaje. Ilość materiału
 * zmienia się tylko przez Consumable::adjust(), więc trafia do historii ruchów.
 */
class SimpleCsvImporter
{
    public const CONTACT_COLUMNS = ['nazwa', 'typ', 'telefon', 'email', 'adres', 'notatki'];

    public const CONSUMABLE_COLUMNS = ['nazwa', 'jednostka', 'ilosc', 'stan_minimalny', 'kategoria_kod', 'lokalizacja_kod', 'notatki'];

    public const MAX_ROWS = 2000;

    public static function contactRow(Contact $contact): array
    {
        return [$contact->name, $contact->type, $contact->phone, $contact->email, $contact->address, $contact->notes];
    }

    public static function consumableRow(Consumable $consumable): array
    {
        return [
            $consumable->name, $consumable->unit, Csv::formatNumber($consumable->quantity, 3),
            Csv::formatNumber($consumable->min_quantity, 3), $consumable->category?->code,
            $consumable->storageLocation?->code, $consumable->notes,
        ];
    }

    /** @return array{created: int, updated: int, unchanged: int, errors: list<string>} */
    public function importContacts(string $path): array
    {
        return $this->each($path, function (array $data) {
            $values = array_filter([
                'type' => isset($data['typ']) && $data['typ'] !== '' ? $this->option($data['typ'], Contact::TYPES) : null,
                'phone' => $data['telefon'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['adres'] ?? null,
                'notes' => $data['notatki'] ?? null,
            ], fn ($v) => $v !== null && $v !== '');

            if (($values['type'] ?? null) === false) {
                return __('Unknown contact type: :value', ['value' => $data['typ']]);
            }
            if (isset($values['email']) && ! filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
                return __('Not an e-mail address: :value', ['value' => $values['email']]);
            }

            $contact = Contact::whereRaw('LOWER(name) = ?', [mb_strtolower($data['nazwa'])])->first();
            if (! $contact) {
                Contact::create(['name' => mb_substr($data['nazwa'], 0, 255)] + $values);

                return 'created';
            }
            $contact->fill($values);
            if (! $contact->isDirty()) {
                return 'unchanged';
            }
            $contact->save();

            return 'updated';
        });
    }

    /** @return array{created: int, updated: int, unchanged: int, errors: list<string>} */
    public function importConsumables(string $path, User $user): array
    {
        return $this->each($path, function (array $data) use ($user) {
            $values = [];
            if (($data['jednostka'] ?? '') !== '') {
                $values['unit'] = $this->option($data['jednostka'], Consumable::UNITS);
                if ($values['unit'] === false) {
                    return __('Unknown unit: :value (use szt, opak, m, l or kg)', ['value' => $data['jednostka']]);
                }
            }
            foreach (['ilosc' => 'quantity', 'stan_minimalny' => 'min_quantity'] as $column => $attribute) {
                if (($data[$column] ?? '') !== '') {
                    $number = Csv::number($data[$column]);
                    if ($number === false || $number < 0) {
                        return __('Not a valid quantity: :value', ['value' => $data[$column]]);
                    }
                    $values[$attribute] = $number;
                }
            }
            foreach (['kategoria_kod' => [Category::class, 'category_id'], 'lokalizacja_kod' => [StorageLocation::class, 'storage_location_id']] as $column => [$model, $attribute]) {
                if (($data[$column] ?? '') !== '') {
                    $found = $model::whereRaw('LOWER(code) = ?', [mb_strtolower($data[$column])])->first();
                    if (! $found) {
                        return __('Code ":code" was not recognized.', ['code' => $data[$column]]);
                    }
                    $values[$attribute] = $found->id;
                }
            }
            if (($data['notatki'] ?? '') !== '') {
                $values['notes'] = $data['notatki'];
            }
            $quantity = $values['quantity'] ?? null;
            unset($values['quantity']);

            $consumable = Consumable::whereRaw('LOWER(name) = ?', [mb_strtolower($data['nazwa'])])->first();
            if (! $consumable) {
                $consumable = Consumable::create(['name' => mb_substr($data['nazwa'], 0, 255)] + $values + ['unit' => 'szt']);
                if ($quantity) {
                    $consumable->adjust($quantity, __('CSV import'), $user);
                }

                return 'created';
            }

            $consumable->fill($values);
            $changed = $consumable->isDirty();
            $consumable->save();
            if ($quantity !== null && abs($quantity - (float) $consumable->quantity) >= 0.0005) {
                $consumable->adjust($quantity - (float) $consumable->quantity, __('CSV import (stock correction)'), $user);
                $changed = true;
            }

            return $changed ? 'updated' : 'unchanged';
        });
    }

    /** Pętla po wierszach z liczeniem wyników; $handler zwraca created|updated|unchanged albo komunikat błędu. */
    private function each(string $path, callable $handler): array
    {
        $result = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'errors' => []];
        if (! in_array('nazwa', Csv::header($path), true)) {
            $result['errors'][] = __('The file is missing the required "nazwa" column in its header row.');

            return $result;
        }

        $count = 0;
        foreach (Csv::read($path) as $line => $data) {
            if (++$count > self::MAX_ROWS) {
                $result['errors'][] = __('Import stopped after :max rows, split the file into smaller batches.', ['max' => self::MAX_ROWS]);
                break;
            }
            if (($data['nazwa'] ?? '') === '') {
                $result['errors'][] = __('Line :line: :message', ['line' => $line, 'message' => __('Missing name.')]);

                continue;
            }
            $outcome = $handler($data);
            if (in_array($outcome, ['created', 'updated', 'unchanged'], true)) {
                $result[$outcome]++;
            } else {
                $result['errors'][] = __('Line :line: :message', ['line' => $line, 'message' => $outcome]);
            }
        }

        return $result;
    }

    private function option(string $raw, array $options): string|false
    {
        return Csv::option($raw, $options);
    }
}
