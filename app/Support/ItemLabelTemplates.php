<?php

namespace App\Support;

/**
 * Trzy fizyczne rozmiary naklejek do wyboru (życzenie użytkownika,
 * 2026-09-16) — jedno źródło prawdy dla kontrolera (walidacja `template`)
 * i widoków (opcje w <select>, wymiary/wielkość QR/czcionek w CSS), żeby
 * kontroler i widok nigdy się nie rozjechały co do tego, jakie klucze są
 * w ogóle poprawne.
 *
 * "35x25" to jedyny szablon "sam QR" (`fields = ['qr']`) — gdy ktoś włączy
 * do niego cenę, układ w items/_label.blade.php zmienia się z "QR obok
 * tekstu" na "QR nad ceną" (stąd osobny, mniejszy `qr_with_price`, żeby
 * zrobić miejsce na linijkę ceny pod spodem).
 */
class ItemLabelTemplates
{
    public const DEFAULT = '32x20';

    public const TEMPLATES = [
        '32x20' => [
            'width' => 32, 'height' => 20, 'qr' => 16,
            'fields' => ['qr', 'name'],
            'font_name' => 4.5, 'font_price' => 4.5,
        ],
        '35x25' => [
            'width' => 35, 'height' => 25, 'qr' => 23, 'qr_with_price' => 18,
            'fields' => ['qr'],
            'font_price' => 6,
        ],
        '50x30' => [
            'width' => 50, 'height' => 30, 'qr' => 26,
            'fields' => ['qr', 'name', 'no'],
            'font_name' => 6, 'font_no' => 6.5, 'font_price' => 6.5,
        ],
    ];

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::TEMPLATES);
    }

    /** @return array<string, mixed> */
    public static function resolve(?string $key): array
    {
        return self::TEMPLATES[$key] ?? self::TEMPLATES[self::DEFAULT];
    }

    public static function label(string $key): string
    {
        return match ($key) {
            '32x20' => __('32×20 mm — QR + item name'),
            '35x25' => __('35×25 mm — QR only'),
            '50x30' => __('50×30 mm — QR + item name + inventory no.'),
            default => $key,
        };
    }

    /** Rozmiar QR-a do użycia — mniejszy wariant, gdy szablon go przewiduje i cena jest włączona. */
    public static function qrSize(array $template, bool $withPrice): float
    {
        if ($withPrice && isset($template['qr_with_price'])) {
            return $template['qr_with_price'];
        }

        return $template['qr'];
    }
}
