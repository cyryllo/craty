<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
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

        // Tylko aktualnie dostępne oferty — sprzedane znikają całkowicie
        // (ustalone: prościej niż trzymać nieaktualne pozycje na stronie).
        $listings = SaleListing::query()
            ->where('status', 'wyeksportowana')
            ->with('item.primaryPhoto')
            ->latest('exported_at')
            ->paginate(24)
            ->withQueryString();

        return view('marketplace.index', [
            'listings' => $listings,
            'view' => $view,
            'contactEmail' => $setting->public_contact_email,
            'contactPhone' => $setting->public_contact_phone,
            'appName' => $setting->effectiveName(),
        ]);
    }
}
