<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Storage;

/**
 * Ikona aplikacji (PWA, ikona na ekranie telefonu) z logo wgranego w
 * Ustawieniach, gdy admin zaznaczy "Użyj logo jako ikony aplikacji".
 * Logo ląduje na białym kwadracie, przeskalowane do bezpiecznej strefy ikon
 * "maskable" (Android przycina ikonę do koła lub zaokrąglonego kwadratu).
 * Pliki mają w nazwie skrót ścieżki logo, więc nowe logo = nowy adres i
 * telefony pobierają nową ikonę (patrz App\Support\PwaIcons).
 */
class AppIconFromLogo
{
    public const SIZES = [192, 512];

    /** Część boku zajmowana przez logo: róg kwadratu mieści się w kole 80% (strefa maskable). */
    private const LOGO_FRACTION = 0.56;

    /** Ścieżka na dysku "public" (null = brak logo albo opcja wyłączona). */
    public static function path(AppSetting $setting, int $size): ?string
    {
        if (! $setting->logo_as_app_icon || ! $setting->logo_path) {
            return null;
        }

        return 'branding/app-icon-'.substr(sha1($setting->logo_path), 0, 12)."-{$size}.png";
    }

    /** Tworzy brakujące ikony i usuwa te zrobione ze starszego logo. Zwraca false, gdy logo nie da się odczytać. */
    public function sync(AppSetting $setting): bool
    {
        $disk = Storage::disk('public');
        $current = array_filter(array_map(fn ($size) => self::path($setting, $size), self::SIZES));

        foreach ($disk->files('branding') as $file) {
            if (str_starts_with(basename($file), 'app-icon-') && ! in_array($file, $current, true)) {
                $disk->delete($file);
            }
        }

        if (! $current) {
            return true;
        }

        $source = @imagecreatefromstring((string) $disk->get($setting->logo_path));
        if (! $source) {
            return false;
        }

        foreach (self::SIZES as $size) {
            ob_start();
            imagepng($this->render($source, $size));
            $disk->put(self::path($setting, $size), ob_get_clean());
        }
        imagedestroy($source);

        return true;
    }

    private function render(\GdImage $source, int $size): \GdImage
    {
        $icon = imagecreatetruecolor($size, $size);
        imagefill($icon, 0, 0, imagecolorallocate($icon, 255, 255, 255));
        imagealphablending($icon, true);

        // Logo (także szerokie) mieści się w kwadracie LOGO_FRACTION boku, proporcje zachowane.
        $box = $size * self::LOGO_FRACTION;
        $w = imagesx($source);
        $h = imagesy($source);
        $scale = min($box / $w, $box / $h);
        $tw = max(1, (int) round($w * $scale));
        $th = max(1, (int) round($h * $scale));

        imagecopyresampled($icon, $source, (int) (($size - $tw) / 2), (int) (($size - $th) / 2), 0, 0, $tw, $th, $w, $h);

        return $icon;
    }
}
