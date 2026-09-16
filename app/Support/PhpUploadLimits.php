<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Paczka aktualizacji (z `vendor/` i skompilowanymi assetami, patrz
 * `UpdatePackageBuilder`) waży dziś ~27 MB — na współdzielonym hostingu
 * `upload_max_filesize`/`post_max_size` w php.ini bywają dużo niższe
 * (często 2-8 MB), a appka sama nie może ich podnieść (to dyrektywy
 * ustawiane przed startem skryptu, `ini_set()` w kodzie PHP nic nie da).
 * Patrz TODO.md "Drobne rzeczy zauważone przy budowie".
 */
class PhpUploadLimits
{
    /** Dzisiejsza pełna paczka waży ~27 MB — 35 MB to bezpieczny margines na wzrost, zanim ktoś realnie utknie. */
    public const RECOMMENDED_MIN_BYTES = 35 * 1024 * 1024;

    /** Mniejszy z dwóch limitów php.ini — to on realnie ogranicza upload, niezależnie który jest niższy. */
    public static function maxUploadBytes(): int
    {
        return min(self::uploadMaxFilesize(), self::postMaxSize());
    }

    public static function uploadMaxFilesize(): int
    {
        return self::parseSize((string) ini_get('upload_max_filesize'));
    }

    public static function postMaxSize(): int
    {
        return self::parseSize((string) ini_get('post_max_size'));
    }

    public static function meetsRecommendedMinimum(): bool
    {
        return self::maxUploadBytes() >= self::RECOMMENDED_MIN_BYTES;
    }

    /**
     * Format php.ini dla tych dwóch dyrektyw: "8M", "1G", "512K", gołe bajty
     * bez sufiksu, albo "-1"/"0" (bez limitu — traktujemy jak "bez ograniczeń").
     */
    public static function parseSize(string $value): int
    {
        $value = trim($value);

        if ($value === '' || $value === '-1' || $value === '0') {
            return PHP_INT_MAX;
        }

        $unit = strtolower(substr($value, -1));
        $number = (float) $value;

        return (int) match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }

    /**
     * PHP czyści $_POST/$_FILES CAŁKOWICIE, bez żadnego wyjątku ani
     * ostrzeżenia w logu appki, gdy body żądania przekroczy `post_max_size`
     * — jedyny ślad, że tak się stało, to niepusty nagłówek Content-Length
     * przy jednocześnie pustych obu paczkach danych. Bez tej detekcji admin
     * dostałby mylącą walidację "pole jest wymagane" zamiast prawdziwej
     * przyczyny nieudanego uploadu.
     */
    public static function requestWasTruncated(Request $request): bool
    {
        return (int) $request->server('CONTENT_LENGTH', 0) > 0
            && $request->request->count() === 0
            && $request->files->count() === 0;
    }
}
