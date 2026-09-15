<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Services\InventoryNumberGenerator;
use App\Services\QrCodeGenerator;
use Illuminate\Http\Request;

/**
 * Skanowanie kamerą (QR/kod kreskowy) — patrz TODO.md "PWA". Własny QR z
 * etykiety Craty koduje już pełny URL karty przedmiotu, więc jego obsługa
 * jest w 100% po stronie klienta (zwykłe window.location). Ten kontroler
 * obsługuje tylko drugą ścieżkę: goły kod kreskowy producenta (EAN/UPC), który
 * trzeba dopiero dopasować do istniejącego przedmiotu albo zaproponować
 * "szybkie dodanie" nowego.
 */
class ScanController extends Controller
{
    public function show()
    {
        return view('scan.show');
    }

    /** AJAX pod skaner — szuka DOKŁADNEGO dopasowania (nie "like") po EAN/numerze seryjnym. */
    public function lookup(Request $request)
    {
        $code = trim((string) $request->query('code', ''));

        $item = $code === '' ? null : Item::query()
            ->where('ean', $code)
            ->orWhere('serial_number', $code)
            ->first();

        return response()->json($item
            ? ['found' => true, 'url' => route('items.show', $item)]
            : ['found' => false]);
    }

    public function quickAddCreate(Request $request)
    {
        return view('scan.quick-add', ['code' => $request->query('code', '')]);
    }

    /**
     * Uproszczony formularz z założenia — tylko zdjęcie/nazwa/kod, bez
     * kategorii/lokalizacji/stanu (te dostają DB-owe defaulty). Ma zająć
     * kilka sekund w warsztacie, nie zastępować pełnego /items/create.
     */
    public function quickAddStore(Request $request, InventoryNumberGenerator $numbers, QrCodeGenerator $qr)
    {
        // Pole nazywa się "item_name", nie "name" — na telefonie zwykłe pole
        // <input name="name"> jest przez Chrome traktowane jak "imię i
        // nazwisko" i podpowiada autouzupełnienie danymi z konta Google nad
        // polem, myląc z formularzem nazwy przedmiotu (realnie zgłoszony bug).
        $data = $request->validate([
            'item_name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:64'],
            'photo' => ['nullable', 'image', 'max:8192'],
        ]);

        $item = new Item(['name' => $data['item_name'], 'ean' => $data['code']]);
        $item->created_by = $request->user()->id;
        $item->inventory_no = $numbers->generate(null, null);
        $item->forceFill(['needs_completion' => true]);
        $item->save();

        $item->qr_path = $qr->generateForItem($item);
        $item->saveQuietly(); // nie duplikujemy wpisu w historii tylko dla qr_path

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('items/'.$item->id, 'public');
            $item->photos()->create(['path' => $path, 'is_primary' => true, 'sort_order' => 0]);
        }

        return redirect()->route('items.show', $item)->with('status', __('Item quickly added — complete its details when you have a moment.'));
    }
}
