<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Category;
use App\Models\SaleListing;
use App\Support\Modules;
use Illuminate\Http\Request;

/**
 * Jedyna dziś publiczna (bez logowania) część appki — "Flea market" z
 * ofertami wystawionymi do sprzedaży. Patrz TODO.md ("Publiczna witryna
 * „pchli targ”") po pełne uzasadnienie decyzji.
 */
class MarketplaceController extends Controller
{
    public function index(Request $request)
    {
        $setting = $this->enabledSetting();

        // Ten sam wzorzec co ItemController::index() dla /items, ale osobny
        // klucz sesji — wybór gościa na tej stronie nie ma nadpisywać
        // preferencji zalogowanego użytkownika w panelu (i odwrotnie).
        $view = $request->input('view');
        if (in_array($view, ['grid', 'list'], true)) {
            session(['marketplace_view' => $view]);
        } else {
            $view = session('marketplace_view', 'grid');
        }

        // Tylko aktualnie dostępne oferty — sprzedane/wycofane znikają
        // całkowicie (ustalone: prościej niż trzymać nieaktualne pozycje).
        $activeListings = SaleListing::query()->where('status', 'wyeksportowana');

        // Kategorie do filtra po lewej — tylko te, w których faktycznie jest
        // dziś coś wystawione, z liczbą ofert przy każdej.
        $categories = Category::query()
            ->whereHas('items.saleListings', fn ($q) => $q->where('status', 'wyeksportowana'))
            ->withCount(['items as listings_count' => fn ($q) => $q->whereHas(
                'saleListings',
                fn ($q) => $q->where('status', 'wyeksportowana')
            )])
            ->orderBy('name')
            ->get();

        $categoryId = $request->integer('category_id') ?: null;

        $listings = (clone $activeListings)
            ->when($categoryId, fn ($q) => $q->whereHas('item', fn ($q) => $q->where('category_id', $categoryId)))
            // Całe photos (nie tylko primaryPhoto) — lista pokazuje okładkę
            // i licznik zdjęć; pełna galeria jest na stronie oferty (show()).
            ->with('item.photos', 'item.category')
            ->latest('exported_at')
            ->paginate(24)
            ->withQueryString();

        return view('marketplace.index', [
            'listings' => $listings,
            'view' => $view,
            'categories' => $categories,
            'categoryId' => $categoryId,
            'totalCount' => $activeListings->count(),
            'contactEmail' => $setting->public_contact_email,
            'contactPhone' => $setting->public_contact_phone,
            'appName' => $setting->effectiveName(),
        ]);
    }

    /**
     * Strona pojedynczej oferty — pełny opis i cała galeria zdjęć. Tylko
     * oferty aktualnie wystawione; sprzedana/wycofana/szkic to 404, tak
     * samo jak na liście (nie zdradzamy, że taka oferta w ogóle istniała).
     */
    public function show(SaleListing $listing)
    {
        $setting = $this->enabledSetting();

        abort_unless($listing->status === 'wyeksportowana', 404);

        $listing->load('item.photos', 'item.category');

        // "Wróć" zachowuje filtr kategorii/stronę listy, jeśli gość przyszedł
        // z listy — w każdym innym przypadku (link z zewnątrz) po prostu lista.
        $previous = url()->previous();
        $backUrl = parse_url($previous, PHP_URL_PATH) === parse_url(route('marketplace.index'), PHP_URL_PATH)
            ? $previous
            : route('marketplace.index');

        return view('marketplace.show', [
            'listing' => $listing,
            'backUrl' => $backUrl,
            'contactEmail' => $setting->public_contact_email,
            'contactPhone' => $setting->public_contact_phone,
            'appName' => $setting->effectiveName(),
        ]);
    }

    private function enabledSetting(): AppSetting
    {
        $setting = AppSetting::current();

        // Wyłączenie modułu Sprzedaż chowa też pchli targ, niezależnie od
        // jego własnego przełącznika — inaczej pokazywałby zamrożone oferty,
        // których nie dałoby się już obsłużyć (patrz App\Support\Modules).
        abort_unless($setting->public_marketplace_enabled && Modules::isEnabled('sales'), 404);

        return $setting;
    }
}
