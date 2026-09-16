<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Category;
use App\Models\SaleListing;
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
        $setting = AppSetting::current();

        abort_unless($setting->public_marketplace_enabled, 404);

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
            // Wcześniej tylko primaryPhoto — dorzucamy całą galerię (patrz
            // TODO.md "Drobne rzeczy zauważone przy budowie", pasek
            // miniaturek zamiast pełnej podstrony/lightboxa).
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
}
