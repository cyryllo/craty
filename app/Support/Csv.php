<?php

namespace App\Support;

use Generator;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Wspólny zapis i odczyt CSV dla eksportów i importów (przedmioty, kontakty,
 * materiały, raporty). Format pod polskiego Excela: separator ";" (przecinek
 * dziesiętny koliduje z ","), UTF-8 ze znacznikiem BOM, żeby Excel nie
 * pokazał krzaków zamiast "ąęł".
 */
class Csv
{
    public const DELIMITER = ';';

    /**
     * Plik do pobrania. Komórki przechodzą przez safe(): tekst zaczynający się
     * od "=", "+", "-", "@" arkusz potraktowałby jak formułę (CSV injection).
     *
     * @param  array<int, string>  $header
     * @param  iterable<array<int, mixed>>  $rows
     */
    public static function download(string $filename, array $header, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $header, self::DELIMITER);
            foreach ($rows as $row) {
                fputcsv($out, array_map([self::class, 'safe'], $row), self::DELIMITER);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public static function safe(mixed $value): mixed
    {
        if (is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }

    /** Odwrotność safe(): wartość z naszego eksportu wraca importem bez dopisanego apostrofu. */
    public static function unsafe(?string $value): ?string
    {
        if ($value !== null && strlen($value) > 1 && $value[0] === "'" && in_array($value[1], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return substr($value, 1);
        }

        return $value;
    }

    /**
     * Wiersze pliku jako [numer linii => [kolumna => wartość]], z nagłówkiem
     * zamienionym na małe litery. Pliki z Excela w Windows-1250 są
     * przekodowywane, a separator "," jest rozpoznawany, gdy w nagłówku nie
     * ma średników.
     *
     * @return Generator<int, array<string, ?string>>
     */
    public static function read(string $path): Generator
    {
        $sample = (string) file_get_contents($path, false, null, 0, 8192);
        $handle = fopen($path, 'r');
        if (! mb_check_encoding($sample, 'UTF-8')) {
            stream_filter_append($handle, 'convert.iconv.WINDOWS-1250/UTF-8//IGNORE', STREAM_FILTER_READ);
        }
        $firstLine = strtok($sample, "\n") ?: '';
        $delimiter = ! str_contains($firstLine, ';') && str_contains($firstLine, ',') ? ',' : self::DELIMITER;

        $header = fgetcsv($handle, 0, $delimiter) ?: [];
        if (isset($header[0])) {
            $header[0] = preg_replace('/^\x{FEFF}/u', '', (string) $header[0]) ?? $header[0];
        }
        $header = array_map(fn ($cell) => mb_strtolower(trim((string) $cell)), $header);

        $line = 1;
        try {
            while (($record = fgetcsv($handle, 0, $delimiter)) !== false) {
                $line++;
                if ($record === [null] || array_filter($record, fn ($v) => trim((string) $v) !== '') === []) {
                    continue; // pusta linia
                }
                $row = [];
                foreach ($header as $i => $column) {
                    $value = $record[$i] ?? null;
                    $row[$column] = $value === null ? null : self::unsafe(trim($value));
                }
                yield $line => $row;
            }
        } finally {
            fclose($handle);
        }
    }

    /** @return array<int, string> nagłówek pliku (małymi literami), bez czytania reszty */
    public static function header(string $path): array
    {
        foreach (self::read($path) as $row) {
            return array_keys($row);
        }

        // Plik z samym nagłówkiem: read() nic nie zwróci, więc czytamy go wprost.
        $handle = fopen($path, 'r');
        $line = (string) fgets($handle);
        fclose($handle);
        $line = preg_replace('/^\x{FEFF}/u', '', $line) ?? $line;
        $delimiter = ! str_contains($line, ';') && str_contains($line, ',') ? ',' : self::DELIMITER;

        return array_map(fn ($cell) => mb_strtolower(trim((string) $cell)), str_getcsv(trim($line), $delimiter));
    }

    /** Liczba z CSV: "1 250,50", "1250.5", "1250" → float; pusta → null; inna → false. */
    public static function number(?string $value): float|false|null
    {
        $value = str_replace([' ', "\u{00A0}"], '', trim((string) $value));
        if ($value === '') {
            return null;
        }
        $value = str_replace(',', '.', $value);

        return is_numeric($value) ? (float) $value : false;
    }

    /** Data z CSV: "2026-11-25", "25.11.2026", "25-11-2026" → "Y-m-d"; pusta → null; inna → false. */
    public static function date(?string $value): string|false|null
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        foreach (['Y-m-d', 'd.m.Y', 'd-m-Y', 'Y-m-d H:i:s', 'd.m.Y H:i'] as $format) {
            $date = \DateTime::createFromFormat('!'.$format, $value);
            if ($date && $date->format($format) === $value) {
                return $date->format('Y-m-d');
            }
        }

        return false;
    }

    /** Tak/nie z CSV: "tak", "1", "yes", "x" → true; "nie", "0", "no" → false; pusta → null; inna → 'invalid'. */
    public static function bool(?string $value): bool|string|null
    {
        $value = mb_strtolower(trim((string) $value));

        return match (true) {
            $value === '' => null,
            in_array($value, ['tak', '1', 'yes', 'y', 't', 'x', 'true'], true) => true,
            in_array($value, ['nie', '0', 'no', 'n', 'false'], true) => false,
            default => 'invalid',
        };
    }

    /**
     * Klucz z listy (np. "w_naprawie", też ze spacją zamiast "_") albo jego
     * nazwa po angielsku, w języku konta lub po polsku ("W naprawie"), bez
     * względu na wielkość liter; false, gdy nic nie pasuje.
     *
     * @param  array<string, string>  $options  klucz => angielska etykieta
     */
    public static function option(string $raw, array $options): string|false
    {
        $needle = mb_strtolower(trim($raw, " \t."));
        foreach ($options as $key => $label) {
            $names = [$key, str_replace('_', ' ', $key), $label, __($label), __($label, [], 'pl')];
            foreach ($names as $name) {
                if ($needle === mb_strtolower(rtrim($name, '.'))) {
                    return $key;
                }
            }
        }

        return false;
    }

    public static function formatNumber(mixed $value, int $decimals = 2): string
    {
        return $value === null || $value === '' ? '' : number_format((float) $value, $decimals, ',', '');
    }
}
