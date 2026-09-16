<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Item;
use App\Models\StorageLocation;
use App\Models\User;

/**
 * Import masowy istniejącego spisu (TODO.md "Import masowy"). Świadomie
 * tylko CSV, nie prawdziwy .xlsx — arkusz Excela/Google Sheets/LibreOffice
 * eksportuje się do CSV jednym kliknięciem ("Zapisz jako"), a prawdziwy
 * parser .xlsx (np. PhpSpreadsheet przez maatwebsite/laravel-excel) to spora,
 * niebagatelna zależność Composer, która trafiłaby też do KAŻDEJ paczki
 * aktualizacji/instalacyjnej (patrz UpdatePackageBuilder — cały vendor/ jest
 * pakowany) tylko dla wygody "nie trzeba samemu eksportować do CSV". Jeśli
 * to się kiedyś okaże realnie potrzebne, dopisać wsparcie .xlsx osobno.
 *
 * Format zgodny z konwencją już istniejącego eksportu ofert sprzedażowych
 * (SaleListingController::exportCsv()) — płaskie nagłówki bez polskich
 * znaków, separator ";" (przecinek dziesiętny w polskim Excelu koliduje z
 * separatorem ","). Kategoria/lokalizacja dopasowywane po ich krótkim,
 * unikalnym "code" (np. "NAR", "M1-R3-P2") — to samo, co już widać w
 * dropdownach pełnego formularza dodawania przedmiotu i na liście
 * lokalizacji, więc administrator nie musi zgadywać żadnego nowego formatu.
 *
 * Nierozpoznany kod kategorii/lokalizacji NIE odrzuca wiersza (życzenie
 * użytkownika, 2026-09-16 — pierwsza wersja robiła to i została uznana za
 * zbyt surową). Zamiast tego wiersz trafia do "oczekujących" — przedmiot
 * nie jest jeszcze tworzony, dopóki admin nie potwierdzi przyciskiem
 * "Dodaj do nieprzypisanych" na stronie importu (`ItemImportController::
 * confirmUnassigned()`), świadomie dwuetapowo, nie w pełni automatycznie —
 * admin ma zobaczyć, co dokładnie nie zostało rozpoznane, zanim cokolwiek
 * naprawdę powstanie w bazie. "Nieprzypisany" to nie osobna kategoria w
 * bazie, tylko zwykłe `category_id`/`storage_location_id` = NULL, dokładnie
 * to samo, co przy pustej komórce w CSV.
 */
class ItemImporter
{
    public const HEADER = [
        'nazwa', 'kategoria_kod', 'lokalizacja_kod', 'stan', 'status', 'wartosc', 'nr_seryjny', 'ean', 'opis',
    ];

    /** Bezpiecznik na zbyt duży plik — import jest synchroniczny (appka nie ma kolejek), zbyt wiele wierszy ryzykuje przekroczenie max_execution_time. */
    public const MAX_ROWS = 2000;

    public function __construct(
        private readonly InventoryNumberGenerator $numbers,
        private readonly QrCodeGenerator $qr,
        // Nadpisywalne w testach (patrz ItemImporterTest) — budowanie 2000+
        // prawdziwych wierszy tylko po to, żeby dobić do stałej MAX_ROWS,
        // byłoby powolne i niepotrzebne, skoro sama logika obcinania jest
        // identyczna dla dowolnego progu.
        private readonly int $maxRows = self::MAX_ROWS,
    ) {
    }

    /**
     * @return array{
     *     created: int, failed: int, pending: int,
     *     rows: array<int, array{line: int, outcome: 'created'|'failed'|'pending', name: string, inventory_no: ?string, message: ?string}>,
     *     pending_rows: array<int, array<string, mixed>>,
     * }
     */
    public function import(string $csvPath, User $user): array
    {
        $handle = fopen($csvPath, 'r');

        if (! $this->rawLooksLikeUtf8($csvPath)) {
            stream_filter_append($handle, 'convert.iconv.WINDOWS-1250/UTF-8//IGNORE', STREAM_FILTER_READ);
        }

        $header = $this->readHeaderRow($handle);

        // Bez "nazwa" w nagłówku KAŻDY wiersz i tak skończyłby się tym samym
        // błędem "brak nazwy" — jeden czytelny komunikat od razu jest
        // uczciwszy niż powtórzenie go raz na wiersz przy większym pliku.
        if (! in_array('nazwa', $header, true)) {
            fclose($handle);

            return ['created' => 0, 'failed' => 1, 'pending' => 0, 'pending_rows' => [], 'rows' => [
                ['line' => 1, 'outcome' => 'failed', 'name' => '', 'inventory_no' => null, 'message' => __('The file is missing the required "nazwa" column in its header row.')],
            ]];
        }

        $rows = [];
        $pendingRows = [];
        $created = 0;
        $failed = 0;
        $pending = 0;
        $line = 1;

        while (($record = fgetcsv($handle, 0, ';')) !== false) {
            $line++;

            if ($record === [null] || $record === false) {
                continue; // pusta linia
            }

            if ($line - 1 > $this->maxRows) {
                $rows[] = ['line' => $line, 'outcome' => 'failed', 'name' => '', 'inventory_no' => null, 'message' => __('Import stopped after :max rows — split the file into smaller batches.', ['max' => $this->maxRows])];
                $failed++;

                break;
            }

            $data = $this->mapRow($header, $record);
            $result = $this->importRow($data, $user, $line);
            $rows[] = $result['row'];

            match ($result['row']['outcome']) {
                'created' => $created++,
                'failed' => $failed++,
                'pending' => $pending++,
            };

            if ($result['pending'] !== null) {
                $pendingRows[] = $result['pending'];
            }
        }

        fclose($handle);

        return ['created' => $created, 'failed' => $failed, 'pending' => $pending, 'pending_rows' => $pendingRows, 'rows' => $rows];
    }

    /**
     * Tworzy przedmioty z wierszy zostawionych wcześniej jako "oczekujące" —
     * bez kategorii/lokalizacji, których kod się wtedy nie rozpoznał (te pola
     * są w `$pendingRows` już jako NULL, patrz `importRow()`). Te, które SIĘ
     * rozpoznały (tylko jedno z dwóch pól było nierozpoznane), zostają
     * przypisane normalnie.
     *
     * @param  array<int, array<string, mixed>>  $pendingRows
     * @return array{created: int, rows: array<int, array{line: int, outcome: 'created', name: string, inventory_no: string, message: null}>}
     */
    public function confirmUnassigned(array $pendingRows, User $user): array
    {
        $created = 0;
        $rows = [];

        foreach ($pendingRows as $pending) {
            $category = $pending['category_id'] ? Category::find($pending['category_id']) : null;
            $location = $pending['storage_location_id'] ? StorageLocation::find($pending['storage_location_id']) : null;

            $item = new Item([
                'name' => $pending['name'],
                'category_id' => $category?->id,
                'storage_location_id' => $location?->id,
                'condition' => $pending['condition'],
                'status' => $pending['status'],
                'value' => $pending['value'],
                'serial_number' => $pending['serial_number'],
                'ean' => $pending['ean'],
                'description' => $pending['description'],
            ]);
            $item->created_by = $user->id;
            $item->inventory_no = $this->numbers->generate($category, $location);
            $item->save();

            $item->qr_path = $this->qr->generateForItem($item);
            $item->saveQuietly(); // nie duplikujemy wpisu w historii tylko dla qr_path

            $created++;
            $rows[] = ['line' => $pending['line'], 'outcome' => 'created', 'name' => $item->name, 'inventory_no' => $item->inventory_no, 'message' => null];
        }

        return ['created' => $created, 'rows' => $rows];
    }

    /**
     * @param  array<string, string|null>  $data
     * @return array{row: array{line: int, outcome: 'created'|'failed'|'pending', name: string, inventory_no: ?string, message: ?string}, pending: ?array<string, mixed>}
     */
    private function importRow(array $data, User $user, int $line): array
    {
        $name = trim((string) ($data['nazwa'] ?? ''));

        if ($name === '') {
            return $this->outcome($line, 'failed', '', null, __('Missing item name.'));
        }

        // Nierozpoznany kod NIE jest tu jeszcze błędem — zbieramy komunikaty
        // i lecimy dalej, decyzja "pending czy created" zapada na końcu.
        $unmatched = [];

        $category = null;
        if (filled($data['kategoria_kod'] ?? null)) {
            $category = Category::query()->whereRaw('LOWER(code) = ?', [mb_strtolower(trim($data['kategoria_kod']))])->first();

            if (! $category) {
                $unmatched[] = __('Category ":code" was not recognized.', ['code' => trim($data['kategoria_kod'])]);
            }
        }

        $location = null;
        if (filled($data['lokalizacja_kod'] ?? null)) {
            $location = StorageLocation::query()->whereRaw('LOWER(code) = ?', [mb_strtolower(trim($data['lokalizacja_kod']))])->first();

            if (! $location) {
                $unmatched[] = __('Location ":code" was not recognized.', ['code' => trim($data['lokalizacja_kod'])]);
            }
        }

        $condition = trim((string) ($data['stan'] ?? '')) ?: 'uzywany';
        if (! array_key_exists($condition, Item::CONDITIONS)) {
            return $this->outcome($line, 'failed', $name, null, __('Unknown condition value: :value', ['value' => $condition]));
        }

        $status = trim((string) ($data['status'] ?? '')) ?: 'dostepny';
        if (! array_key_exists($status, Item::STATUSES)) {
            return $this->outcome($line, 'failed', $name, null, __('Unknown status value: :value', ['value' => $status]));
        }

        $value = trim((string) ($data['wartosc'] ?? ''));
        if ($value !== '' && ! is_numeric(str_replace(',', '.', $value))) {
            return $this->outcome($line, 'failed', $name, null, __('Value is not a number: :value', ['value' => $value]));
        }

        $attributes = [
            'name' => $name,
            'condition' => $condition,
            'status' => $status,
            'value' => $value !== '' ? (float) str_replace(',', '.', $value) : null,
            'serial_number' => filled($data['nr_seryjny'] ?? null) ? trim($data['nr_seryjny']) : null,
            'ean' => filled($data['ean'] ?? null) ? trim($data['ean']) : null,
            'description' => filled($data['opis'] ?? null) ? trim($data['opis']) : null,
        ];

        if ($unmatched !== []) {
            return [
                'row' => ['line' => $line, 'outcome' => 'pending', 'name' => $name, 'inventory_no' => null, 'message' => implode(' ', $unmatched)],
                'pending' => $attributes + ['line' => $line, 'category_id' => $category?->id, 'storage_location_id' => $location?->id],
            ];
        }

        $item = new Item($attributes + ['category_id' => $category?->id, 'storage_location_id' => $location?->id]);
        $item->created_by = $user->id;
        $item->inventory_no = $this->numbers->generate($category, $location);
        $item->save();

        $item->qr_path = $this->qr->generateForItem($item);
        $item->saveQuietly(); // nie duplikujemy wpisu w historii tylko dla qr_path

        return ['row' => ['line' => $line, 'outcome' => 'created', 'name' => $name, 'inventory_no' => $item->inventory_no, 'message' => null], 'pending' => null];
    }

    /** @return array{row: array{line: int, outcome: 'failed', name: string, inventory_no: null, message: string}, pending: null} */
    private function outcome(int $line, string $outcome, string $name, ?string $inventoryNo, string $message): array
    {
        return ['row' => ['line' => $line, 'outcome' => $outcome, 'name' => $name, 'inventory_no' => $inventoryNo, 'message' => $message], 'pending' => null];
    }

    /** @return array<int, string> */
    private function readHeaderRow($handle): array
    {
        $header = fgetcsv($handle, 0, ';') ?: [];

        // BOM na pierwszej komórce — typowe dla plików zapisanych przez Excela na Windows.
        if (isset($header[0])) {
            $header[0] = preg_replace('/^\x{FEFF}/u', '', $header[0]) ?? $header[0];
        }

        return array_map(fn ($cell) => mb_strtolower(trim((string) $cell)), $header);
    }

    /**
     * @param  array<int, string>  $header
     * @param  array<int, string|null>  $record
     * @return array<string, string|null>
     */
    private function mapRow(array $header, array $record): array
    {
        $data = [];

        foreach ($header as $index => $column) {
            $data[$column] = $record[$index] ?? null;
        }

        return $data;
    }

    /** Excel na Windows domyślnie zapisuje polskie CSV w Windows-1250, nie UTF-8 — bez konwersji "ł"/"ą"/"ę" zamieniłyby się w krzaki. */
    private function rawLooksLikeUtf8(string $path): bool
    {
        $sample = file_get_contents($path, false, null, 0, 8192);

        return $sample === false || mb_check_encoding($sample, 'UTF-8');
    }
}
