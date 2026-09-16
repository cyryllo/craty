<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Generuje statyczne ikony PWA (192×192, 512×512, obie też jako "maskable")
 * z tego samego motywu co domyślne logo appki (regał z półkami — patrz
 * x-application-logo). Uruchamiane RAZ w tym repo przy budowie PWA, wynik
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

    protected $description = 'Generuje ikony PWA (pwa-icons/) z domyślnego motywu logo appki.';

    /** Tło: indigo-600, ten sam odcień co akcenty w reszcie UI. */
    private const BG = [79, 70, 229];

    private const FG = [255, 255, 255];

    public function handle(): int
    {
        $dir = public_path('pwa-icons');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        foreach ([192, 512] as $size) {
            $this->render($size, $dir."/icon-{$size}.png");
        }

        $this->info('Gotowe: '.$dir);

        return self::SUCCESS;
    }

    private function render(int $size, string $path): void
    {
        $img = imagecreatetruecolor($size, $size);
        imagesavealpha($img, true);

        $bg = imagecolorallocate($img, ...self::BG);
        imagefill($img, 0, 0, $bg);

        // Glyph rysowany w układzie viewBox 0..32 (jak w application-logo.blade.php),
        // przeskalowany tak, żeby zmieścić się w bezpiecznej strefie ok. 62%
        // środka kanwy — margines na wypadek maskowania przez system (Android
        // przycina maskable ikony do własnego kształtu, poza centralnym kołem).
        $boxSize = $size * 0.62;
        $scale = $boxSize / 24; // 24 = szerokość/wysokość ramki w viewBox (4..28)
        $offset = ($size - $boxSize) / 2;
        $map = fn (float $v) => $offset + ($v - 4) * $scale;

        $fg = imagecolorallocate($img, ...self::FG);
        imageantialias($img, true);
        imagesetthickness($img, max(1, (int) round(2 * $scale)));

        // Ramka regału.
        imagerectangle($img, (int) $map(4), (int) $map(4), (int) $map(28), (int) $map(28), $fg);
        // Dwie półki.
        imageline($img, (int) $map(4), (int) $map(12), (int) $map(28), (int) $map(12), $fg);
        imageline($img, (int) $map(4), (int) $map(20), (int) $map(28), (int) $map(20), $fg);

        // Przedmioty na półkach (te same prostokąty co w SVG).
        foreach ([
            [7, 6.5, 12, 11.5],
            [16.5, 7.5, 20.5, 11.5],
            [7, 14.5, 21, 19.5],
            [7, 22.5, 11, 26.5],
            [14, 21.5, 21, 27],
        ] as [$x1, $y1, $x2, $y2]) {
            imagefilledrectangle($img, (int) $map($x1), (int) $map($y1), (int) $map($x2), (int) $map($y2), $fg);
        }

        imagepng($img, $path);
        imagedestroy($img);
    }
}
