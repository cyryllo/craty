<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Item;
use App\Models\StorageLocation;
use App\Models\User;
use App\Support\Csv;

/**
 * Import i eksport przedmiotów w CSV (Import i eksport, dostępne z Ustawień).
 * Świadomie tylko CSV, nie .xlsx: każdy arkusz zapisuje CSV jednym
 * kliknięciem, a parser .xlsx to duża zależność, która trafiłaby do każdej
 * paczki aktualizacji.
 *
 * Dwa kroki: analyze() czyta plik i układa plan (nowe, zmiany z wartościami
 * przed i po, bez zmian, błędy) bez zapisu do bazy; apply() wykonuje plan
 * dopiero po zatwierdzeniu podglądu. Wiersz z wypełnioną kolumną
 * nr_ewidencyjny zmienia istniejący przedmiot (masowa edycja: eksport →
 * Excel → import), bez niej powstaje nowy. Przy zmianie pusta komórka
 * znaczy "bez zmian", a kolumn, których w pliku nie ma, import nie dotyka.
 *
 * Nierozpoznany kod kategorii/lokalizacji przy NOWYM przedmiocie nie blokuje
 * wiersza (życzenie właściciela z 2026-09-16): przedmiot powstanie bez niej,
 * co podgląd wyraźnie pokazuje. Przy ZMIANIE to błąd wiersza, żeby literówka
 * w kodzie nie wyczyściła przypisania.
 */
class ItemImporter
{
    /** Kolumny, które import czyta (poza nr_ewidencyjny). */
    public const IMPORT_COLUMNS = [
        'nazwa', 'kategoria_kod', 'lokalizacja_kod', 'stan', 'status', 'wartosc', 'nr_seryjny', 'ean',
        'data_zakupu', 'gwarancja_do', 'przeglad_co_miesiecy', 'nastepny_przeglad', 'stacjonarny', 'opis',
    ];

    /** Kolumny eksportu: najpierw te z importu, na końcu informacyjne (import je pomija). */
    public const EXPORT_COLUMNS = [
        'nr_ewidencyjny', ...self::IMPORT_COLUMNS, 'kategoria', 'lokalizacja', 'wypozyczone_komu', 'termin_zwrotu',
    ];

    /** Bezpiecznik: import jest synchroniczny (appka nie ma kolejek). */
    public const MAX_ROWS = 2000;

    /** Kolumna CSV → atrybut przedmiotu. */
    private const FIELDS = [
        'nazwa' => 'name', 'kategoria_kod' => 'category_id', 'lokalizacja_kod' => 'storage_location_id',
        'stan' => 'condition', 'status' => 'status', 'wartosc' => 'value', 'nr_seryjny' => 'serial_number',
        'ean' => 'ean', 'data_zakupu' => 'purchased_at', 'gwarancja_do' => 'warranty_until',
        'przeglad_co_miesiecy' => 'inspection_interval_months', 'nastepny_przeglad' => 'next_inspection_at',
        'stacjonarny' => 'stationary', 'opis' => 'description',
    ];

    public function __construct(
        private readonly InventoryNumberGenerator $numbers,
        private readonly QrCodeGenerator $qr,
        // Nadpisywalne w testach, żeby nie budować 2000 wierszy dla sprawdzenia limitu.
        private readonly int $maxRows = self::MAX_ROWS,
    ) {
    }

    /** Wiersz eksportu dla przedmiotu (kolejność jak EXPORT_COLUMNS). */
    public static function exportRow(Item $item): array
    {
        return [
            $item->inventory_no,
            $item->name,
            $item->category?->code,
            $item->storageLocation?->code,
            $item->condition,
            $item->status,
            Csv::formatNumber($item->value),
            $item->serial_number,
            $item->ean,
            $item->purchased_at?->format('Y-m-d'),
            $item->warranty_until?->format('Y-m-d'),
            $item->inspection_interval_months,
            $item->next_inspection_at?->format('Y-m-d'),
            $item->stationary ? 'tak' : 'nie',
            $item->description,
            $item->category?->name,
            $item->storageLocation?->label(),
            $item->currentLoan?->borrowerLabel(),
            $item->currentLoan?->due_at?->format('Y-m-d'),
        ];
    }

    /**
     * Analiza pliku bez zapisu do bazy.
     *
     * @return array{
     *     counts: array{create: int, update: int, unchanged: int, error: int},
     *     rows: list<array{line: int, action: string, name: string, inventory_no: ?string, changes: array<string, array{0: mixed, 1: mixed}>, messages: list<string>}>,
     *     plan: list<array{action: string, line: int, item_id: ?int, attributes: array<string, mixed>}>,
     * }
     */
    public function analyze(string $csvPath): array
    {
        $result = ['counts' => ['create' => 0, 'update' => 0, 'unchanged' => 0, 'error' => 0], 'rows' => [], 'plan' => []];

        $header = Csv::header($csvPath);
        if (! in_array('nazwa', $header, true) && ! in_array('nr_ewidencyjny', $header, true)) {
            $result['counts']['error'] = 1;
            $result['rows'][] = $this->row(1, 'error', '', null, [], [__('The file needs a "nazwa" column (new items) or an "nr_ewidencyjny" column (changes to existing items) in its header row.')]);

            return $result;
        }

        $count = 0;
        foreach (Csv::read($csvPath) as $line => $data) {
            if (++$count > $this->maxRows) {
                $result['counts']['error']++;
                $result['rows'][] = $this->row($line, 'error', '', null, [], [__('Import stopped after :max rows, split the file into smaller batches.', ['max' => $this->maxRows])]);
                break;
            }

            [$row, $plan] = $this->analyzeRow($line, $data);
            $result['rows'][] = $row;
            $result['counts'][$row['action']]++;
            if ($plan) {
                $result['plan'][] = $plan;
            }
        }

        return $result;
    }

    /**
     * Wykonanie planu z analyze() (po zatwierdzeniu podglądu).
     *
     * @param  list<array{action: string, line: int, item_id: ?int, attributes: array<string, mixed>}>  $plan
     * @return array{created: int, updated: int, rows: list<array{line: int, action: string, name: string, inventory_no: ?string}>}
     */
    public function apply(array $plan, User $user): array
    {
        $done = ['created' => 0, 'updated' => 0, 'rows' => []];

        foreach ($plan as $step) {
            if ($step['action'] === 'update') {
                $item = Item::find($step['item_id']);
                if (! $item) {
                    continue; // usunięty między podglądem a zatwierdzeniem
                }
                $item->update($step['attributes']); // ItemObserver zapisze historię
                $done['updated']++;
                $done['rows'][] = ['line' => $step['line'], 'action' => 'update', 'name' => $item->name, 'inventory_no' => $item->inventory_no];

                continue;
            }

            $attributes = $step['attributes'];
            $category = isset($attributes['category_id']) ? Category::find($attributes['category_id']) : null;
            $location = isset($attributes['storage_location_id']) ? StorageLocation::find($attributes['storage_location_id']) : null;

            $item = new Item($attributes);
            $item->created_by = $user->id;
            $item->inventory_no = $this->numbers->generate($category, $location);
            $item->save();
            $item->qr_path = $this->qr->generateForItem($item);
            $item->saveQuietly(); // bez dodatkowego wpisu w historii tylko dla qr_path

            $done['created']++;
            $done['rows'][] = ['line' => $step['line'], 'action' => 'create', 'name' => $item->name, 'inventory_no' => $item->inventory_no];
        }

        return $done;
    }

    /** @return array{0: array, 1: ?array} wiersz podglądu i krok planu */
    private function analyzeRow(int $line, array $data): array
    {
        $inventoryNo = trim((string) ($data['nr_ewidencyjny'] ?? ''));
        $item = null;
        if ($inventoryNo !== '') {
            $item = Item::where('inventory_no', $inventoryNo)->first();
            if (! $item) {
                return [$this->row($line, 'error', (string) ($data['nazwa'] ?? ''), $inventoryNo, [], [__('There is no item with inventory number :number.', ['number' => $inventoryNo])]), null];
            }
        }

        $errors = [];
        $warnings = [];
        $values = [];
        foreach (self::FIELDS as $column => $attribute) {
            $raw = $data[$column] ?? null;
            if ($raw === null || trim($raw) === '') {
                continue; // brak kolumny albo pusta komórka: bez zmian / wartość domyślna
            }
            $parsed = $this->parse($column, trim($raw), $item !== null, $errors, $warnings);
            if ($parsed !== '__skip') {
                $values[$attribute] = $parsed;
            }
        }

        $name = (string) ($values['name'] ?? $item?->name ?? '');
        if ($errors) {
            return [$this->row($line, 'error', $name, $item?->inventory_no, [], $errors), null];
        }

        if ($item) {
            $changes = [];
            foreach ($values as $attribute => $new) {
                $old = $this->comparable($item, $attribute);
                if ($old !== $new && ! (is_float($new) && $old !== null && abs((float) $old - $new) < 0.005)) {
                    $changes[$attribute] = [$old, $new];
                }
            }
            if (! $changes) {
                return [$this->row($line, 'unchanged', $name, $item->inventory_no, [], $warnings), null];
            }

            $display = [];
            foreach ($changes as $attribute => [$old, $new]) {
                $display[$attribute] = [$this->display($attribute, $old), $this->display($attribute, $new)];
            }

            return [
                $this->row($line, 'update', $name, $item->inventory_no, $display, $warnings),
                ['action' => 'update', 'line' => $line, 'item_id' => $item->id, 'attributes' => array_map(fn ($c) => $c[1], $changes)],
            ];
        }

        if ($name === '') {
            return [$this->row($line, 'error', '', null, [], [__('Missing item name.')]), null];
        }
        $values += ['condition' => 'uzywany', 'status' => 'dostepny'];

        return [
            $this->row($line, 'create', $name, null, [], $warnings),
            ['action' => 'create', 'line' => $line, 'item_id' => null, 'attributes' => $values],
        ];
    }

    /** Wartość z komórki w postaci zapisywanej w bazie; błąd dopisuje do $errors i zwraca "__skip". */
    private function parse(string $column, string $raw, bool $updating, array &$errors, array &$warnings): mixed
    {
        switch ($column) {
            case 'kategoria_kod':
                $category = Category::query()->whereRaw('LOWER(code) = ?', [mb_strtolower($raw)])->first();
                if ($category) {
                    return $category->id;
                }
                $message = __('Category ":code" was not recognized.', ['code' => $raw]);
                if ($updating) {
                    $errors[] = $message;
                } else {
                    $warnings[] = $message.' '.__('The item will be added without a category.');
                }

                return '__skip';
            case 'lokalizacja_kod':
                $location = StorageLocation::query()->whereRaw('LOWER(code) = ?', [mb_strtolower($raw)])->first();
                if ($location) {
                    return $location->id;
                }
                $message = __('Location ":code" was not recognized.', ['code' => $raw]);
                if ($updating) {
                    $errors[] = $message;
                } else {
                    $warnings[] = $message.' '.__('The item will be added without a location.');
                }

                return '__skip';
            case 'stan':
                return $this->option($raw, Item::CONDITIONS, __('Unknown condition value: :value', ['value' => $raw]), $errors);
            case 'status':
                return $this->option($raw, Item::STATUSES, __('Unknown status value: :value', ['value' => $raw]), $errors);
            case 'wartosc':
                $number = Csv::number($raw);
                if ($number === false || $number < 0) {
                    $errors[] = __('Value is not a number: :value', ['value' => $raw]);

                    return '__skip';
                }

                return round($number, 2);
            case 'przeglad_co_miesiecy':
                if (! ctype_digit($raw) || (int) $raw < 1 || (int) $raw > 120) {
                    $errors[] = __('Inspection interval must be a whole number of months from 1 to 120: :value', ['value' => $raw]);

                    return '__skip';
                }

                return (int) $raw;
            case 'data_zakupu':
            case 'gwarancja_do':
            case 'nastepny_przeglad':
                $date = Csv::date($raw);
                if ($date === false) {
                    $errors[] = __('Not a date (use 2026-11-25 or 25.11.2026): :value', ['value' => $raw]);

                    return '__skip';
                }

                return $date;
            case 'stacjonarny':
                $bool = Csv::bool($raw);
                if ($bool === 'invalid') {
                    $errors[] = __('Stationary must be "tak" or "nie": :value', ['value' => $raw]);

                    return '__skip';
                }

                return $bool;
            case 'nazwa':
            case 'nr_seryjny':
            case 'ean':
                return mb_substr($raw, 0, 255);
            default:
                return $raw;
        }
    }

    /** Kod z listy (np. "uzywany") albo jego polska lub angielska nazwa (np. "używany", "Used"). */
    private function option(string $raw, array $options, string $error, array &$errors): mixed
    {
        $key = Csv::option($raw, $options);
        if ($key !== false) {
            return $key;
        }
        $errors[] = $error;

        return '__skip';
    }

    /** Obecna wartość przedmiotu w tej samej postaci co z parse(), do porównania. */
    private function comparable(Item $item, string $attribute): mixed
    {
        $value = $item->getAttribute($attribute);

        return match (true) {
            $value instanceof \DateTimeInterface => $value->format('Y-m-d'),
            $attribute === 'value' => $value === null ? null : round((float) $value, 2),
            $attribute === 'stationary' => (bool) $value,
            $attribute === 'inspection_interval_months', $attribute === 'category_id', $attribute === 'storage_location_id' => $value === null ? null : (int) $value,
            default => $value,
        };
    }

    /** Wartość do podglądu: kod zamiast numeru kategorii/lokalizacji, nazwa stanu i statusu, tak/nie. */
    private function display(string $attribute, mixed $value): string
    {
        return match (true) {
            $value === null || $value === '' => '',
            $attribute === 'category_id' => (string) Category::find($value)?->code,
            $attribute === 'storage_location_id' => (string) StorageLocation::find($value)?->code,
            $attribute === 'condition' => __(Item::CONDITIONS[$value] ?? (string) $value),
            $attribute === 'status' => __(Item::STATUSES[$value] ?? (string) $value),
            $attribute === 'stationary' => $value ? __('yes') : __('no'),
            $attribute === 'value' => Csv::formatNumber($value),
            default => \Illuminate\Support\Str::limit((string) $value, 80),
        };
    }

    private function row(int $line, string $action, string $name, ?string $inventoryNo, array $changes, array $messages): array
    {
        return compact('line', 'action', 'name', 'changes', 'messages') + ['inventory_no' => $inventoryNo];
    }
}
