<?php

namespace App\Services;

use App\Models\Item;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/**
 * Generuje kod QR dla przedmiotu — zaszyty jest w nim adres URL do karty
 * przedmiotu, więc zeskanowanie zwykłym aparatem telefonu (Android/iOS)
 * od razu otwiera jego stronę w przeglądarce, bez dedykowanej aplikacji.
 */
class QrCodeGenerator
{
    public function generateForItem(Item $item): string
    {
        $url = Route::has('items.show')
            ? route('items.show', $item)
            : url('/items/'.$item->id);

        $result = (new Builder(
            writer: new SvgWriter(),
            data: $url,
            size: 320,
            margin: 12,
        ))->build();

        $path = 'qr/'.$item->inventory_no.'.svg';
        Storage::disk('public')->put($path, $result->getString());

        return $path;
    }
}
