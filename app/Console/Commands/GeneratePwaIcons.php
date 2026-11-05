<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Generuje statyczne ikony PWA (192×192, 512×512, obie też jako "maskable")
 * i favicon.ico z jednego rysunku (skrzynka, ten sam motyw co domyślne logo
 * appki, patrz x-application-logo). Uruchamiane RAZ w tym repo przy budowie PWA, wynik
 * commitowany do pwa-icons/ (NIE "icons/" — to zarezerwowana ścieżka:
 * domyślna konfiguracja Apache ma wbudowany alias `/icons/` na własne
 * ikonki do listowania katalogów, `/usr/share/apache2/icons/`, który po
 * cichu przechwytuje KAŻDE żądanie pod tym prefiksem, zanim dotrze do
 * naszego DocumentRoot — realnie złapane: pliki fizycznie istniały,
 * uprawnienia były poprawne, a mimo to każdy request dawał 404 bez
 * żadnego śladu w regułach przepisywania). Świadomie NIE generujemy tego dynamicznie
 * z własnego loga admina wgranego w Ustawienia (patrz TODO.md "PWA"), to
 * osobny, większy temat na później.
 *
 * Rysowane przez GD (nie Imagick/rsvg — appka nie ma dziś zależności do
 * rasteryzacji SVG, a kształt jest prosty na tyle, że łatwiej odtworzyć go
 * wprost jako prostokąty niż dociągać nową zależność dla jednorazowego
 * skryptu).
 */
class GeneratePwaIcons extends Command
{
    protected $signature = 'pwa:icons';

    protected $description = 'Generuje ikony PWA (pwa-icons/) i favicon.ico: skrzynka na ciemnym tle.';

    /**
     * Wygląd od 2026-11-05 (zgłoszenie: dawny cienki biały regał na tle
     * indigo był w małym rozmiarze nieczytelny i nie pasował do reszty):
     * gruba, ciepła skrzynka (Craty = skrzynki) na ciemnym tle w kolorze
     * przycisków appki (gray-900). Kształt jest prosty celowo, żeby był
     * czytelny także jako favicon 16 px.
     */
    private const BG = [17, 24, 39];        // gray-900

    private const FRAME = [251, 191, 36];   // amber-400: rama i ukośna belka

    private const BOARDS = [217, 119, 6];   // amber-600: deski

    /** Rysujemy w 4× większej skali i zmniejszamy: GD nie wygładza wypełnionych kształtów. */
    private const SUPERSAMPLE = 4;

    public function handle(): int
    {
        $dir = public_path('pwa-icons');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        foreach ([192, 512] as $size) {
            imagepng($this->render($size), $dir."/icon-{$size}.png");
        }

        $this->writeIco(public_path('favicon.ico'), [16, 32, 48]);

        $this->info('Gotowe: '.$dir.' oraz favicon.ico');

        return self::SUCCESS;
    }

    /** Rysunek w układzie 512×512, przeskalowany do $size. */
    private function render(int $size): \GdImage
    {
        $big = $size * self::SUPERSAMPLE;
        $k = $big / 512;
        $img = imagecreatetruecolor($big, $big);

        $bg = imagecolorallocate($img, ...self::BG);
        $frame = imagecolorallocate($img, ...self::FRAME);
        $boards = imagecolorallocate($img, ...self::BOARDS);

        // Tło na całą powierzchnię: ikona "maskable" (Android sam przycina kształt).
        imagefilledrectangle($img, 0, 0, $big, $big, $bg);

        // Skrzynka mieści się w bezpiecznym kole ikon maskable (promień 40%).
        [$x1, $y1, $x2, $y2] = [116, 116, 396, 396];
        $t = 40; // grubość ramy i belki

        $this->roundedRect($img, $x1 * $k, $y1 * $k, $x2 * $k, $y2 * $k, 26 * $k, $frame);

        [$ix1, $iy1, $ix2, $iy2] = [$x1 + $t, $y1 + $t, $x2 - $t, $y2 - $t];
        imagefilledrectangle($img, (int) ($ix1 * $k), (int) ($iy1 * $k), (int) ($ix2 * $k), (int) ($iy2 * $k), $boards);

        // Szczeliny między deskami.
        foreach ([$iy1 + ($iy2 - $iy1) / 3, $iy1 + 2 * ($iy2 - $iy1) / 3] as $gapY) {
            imagefilledrectangle($img, (int) ($ix1 * $k), (int) (($gapY - 5) * $k), (int) ($ix2 * $k), (int) (($gapY + 5) * $k), $bg);
        }

        // Ukośna belka z lewego dolnego do prawego górnego rogu wnętrza.
        $h = $t / 2 / M_SQRT2;
        imagefilledpolygon($img, array_map(fn ($v) => (int) round($v * $k), [
            $ix1 - $h, $iy2 - $h,
            $ix2 - $h, $iy1 - $h,
            $ix2 + $h, $iy1 + $h,
            $ix1 + $h, $iy2 + $h,
        ]), $frame);

        $out = imagecreatetruecolor($size, $size);
        imagecopyresampled($out, $img, 0, 0, 0, 0, $size, $size, $big, $big);
        imagedestroy($img);

        return $out;
    }

    private function roundedRect(\GdImage $img, float $x1, float $y1, float $x2, float $y2, float $r, int $color): void
    {
        imagefilledrectangle($img, (int) ($x1 + $r), (int) $y1, (int) ($x2 - $r), (int) $y2, $color);
        imagefilledrectangle($img, (int) $x1, (int) ($y1 + $r), (int) $x2, (int) ($y2 - $r), $color);
        foreach ([[$x1 + $r, $y1 + $r], [$x2 - $r, $y1 + $r], [$x1 + $r, $y2 - $r], [$x2 - $r, $y2 - $r]] as [$cx, $cy]) {
            imagefilledellipse($img, (int) $cx, (int) $cy, (int) (2 * $r), (int) (2 * $r), $color);
        }
    }

    /**
     * favicon.ico z osadzonymi PNG-ami (format obsługiwany przez wszystkie
     * współczesne przeglądarki) — GD nie zapisuje ICO, a nagłówek jest prosty.
     *
     * @param  array<int, int>  $sizes
     */
    private function writeIco(string $path, array $sizes): void
    {
        $images = [];
        foreach ($sizes as $size) {
            ob_start();
            imagepng($this->render($size));
            $images[$size] = ob_get_clean();
        }

        $header = pack('vvv', 0, 1, count($images));
        $offset = 6 + 16 * count($images);
        $entries = '';
        foreach ($images as $size => $png) {
            $entries .= pack('CCCCvvVV', $size % 256, $size % 256, 0, 0, 1, 32, strlen($png), $offset);
            $offset += strlen($png);
        }

        file_put_contents($path, $header.$entries.implode('', $images));
    }
}
