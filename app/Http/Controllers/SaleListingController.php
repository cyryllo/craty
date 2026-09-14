<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\SaleListing;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SaleListingController extends Controller
{
    /** Podstrona "Przygotowane" — oferty-szkice, jeszcze nie wyeksportowane. */
    public function index()
    {
        $draft = SaleListing::with('item.category', 'item.storageLocation.warehouse')
            ->where('status', 'szkic')->latest()->get();

        return view('sale-listings.index', [
            'draft' => $draft,
            'draftCount' => $draft->count(),
            'exportedCount' => SaleListing::where('status', 'wyeksportowana')->count(),
        ]);
    }

    /**
     * Podstrona "Wystawione" — oferty już wyeksportowane do CSV (status
     * ustawiany automatycznie w exportCsv()), czyli faktycznie wystawione
     * na sprzedaż. Stąd można oznaczyć przedmiot jako sprzedany.
     */
    public function exported()
    {
        return view('sale-listings.exported', [
            'exported' => SaleListing::with('item.category', 'item.storageLocation.warehouse')
                ->where('status', 'wyeksportowana')->latest('exported_at')->paginate(24),
            'draftCount' => SaleListing::where('status', 'szkic')->count(),
            'exportedCount' => SaleListing::where('status', 'wyeksportowana')->count(),
        ]);
    }

    /** Oznacza wystawioną ofertę jako sprzedaną — kończy jej cykl życia. */
    public function markSold(SaleListing $listing)
    {
        $listing->update(['status' => 'sprzedana']);
        $listing->item->update(['status' => 'sprzedany']);

        return back()->with('status', __('Item marked as sold.'));
    }

    /**
     * Wycofuje wystawioną ofertę ze sprzedaży — znika z zakładki "Wystawione"
     * i z pchlego targu, a przedmiot wraca do statusu "dostępny" (to nie to
     * samo co Item::STATUSES['wycofany'] — tamto oznacza wycofanie całego
     * przedmiotu z użytku, nie tylko z tej jednej oferty sprzedaży).
     */
    public function withdraw(SaleListing $listing)
    {
        $listing->update(['status' => 'wycofana']);
        $listing->item->update(['status' => 'dostepny']);

        return back()->with('status', __('Listing withdrawn from sale.'));
    }

    /** Formularz z podpowiedzianą treścią ogłoszenia (krok B z koncepcji: asystent treści). */
    public function create(Item $item)
    {
        $listing = new SaleListing([
            'title' => $item->name,
            'description' => $this->suggestDescription($item),
            'price' => $item->value,
        ]);

        return view('items.sale-listing', ['item' => $item, 'listing' => $listing]);
    }

    public function store(Request $request, Item $item)
    {
        $data = $request->validate([
            'platform' => ['required', 'in:olx,allegro,inne'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $item->saleListings()->create($data);
        $item->update(['status' => 'do_sprzedazy']);

        return redirect()->route('sale-listings.index')->with('status', __('Sale listing prepared.'));
    }

    /** Krok A z koncepcji: uniwersalny eksport CSV wszystkich przygotowanych ofert (zakładka Sprzedaż). */
    public function exportCsv(): StreamedResponse
    {
        $listings = SaleListing::with('item.category', 'item.storageLocation.warehouse', 'item.photos')
            ->where('status', 'szkic')
            ->get();

        $callback = function () use ($listings) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'nr_ewidencyjny', 'ean', 'tytul', 'opis', 'cena', 'kategoria', 'stan', 'platforma', 'zdjecia',
            ], ';');

            foreach ($listings as $listing) {
                $item = $listing->item;
                fputcsv($handle, [
                    $item->inventory_no,
                    $item->ean,
                    $listing->title,
                    $listing->description,
                    $listing->price,
                    $item->category?->name,
                    $item->conditionLabel(),
                    $listing->platform,
                    $item->photos->map(fn ($p) => $p->url())->implode(', '),
                ], ';');
            }

            fclose($handle);
        };

        $listings->each->update(['status' => 'wyeksportowana', 'exported_at' => now()]);

        // Niezależnie od tego, jak powstała oferta, przedmiot faktycznie
        // wystawiony na sprzedaż ma mieć taki status (chyba że już sprzedany).
        Item::whereIn('id', $listings->pluck('item_id'))
            ->where('status', '!=', 'sprzedany')
            ->update(['status' => 'do_sprzedazy']);

        return response()->streamDownload(
            $callback,
            'oferty-sprzedazy-'.now()->format('Y-m-d').'.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    private function suggestDescription(Item $item): string
    {
        $lines = [$item->name];

        if ($item->specification) {
            $lines[] = $item->specification;
        }
        if ($item->ean) {
            $lines[] = 'EAN: '.$item->ean;
        }
        $lines[] = __('Condition').': '.$item->conditionLabel();
        if ($item->value) {
            $lines[] = __('Estimated value').': '.number_format((float) $item->value, 2).' zł';
        }

        return implode("\n", $lines);
    }
}
