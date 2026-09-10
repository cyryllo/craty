<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\SaleListing;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SaleListingController extends Controller
{
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

        return redirect()->route('items.show', $item)->with('status', 'Oferta sprzedaży przygotowana.');
    }

    /** Krok A z koncepcji: uniwersalny eksport CSV wszystkich przygotowanych ofert. */
    public function exportCsv(): StreamedResponse
    {
        $listings = SaleListing::with('item.category', 'item.storageLocation.warehouse', 'item.photos')
            ->where('status', 'szkic')
            ->get();

        $callback = function () use ($listings) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'nr_ewidencyjny', 'tytul', 'opis', 'cena', 'kategoria', 'stan', 'platforma', 'zdjecia',
            ], ';');

            foreach ($listings as $listing) {
                $item = $listing->item;
                fputcsv($handle, [
                    $item->inventory_no,
                    $listing->title,
                    $listing->description,
                    $listing->price,
                    $item->category?->name,
                    Item::CONDITIONS[$item->condition] ?? $item->condition,
                    $listing->platform,
                    $item->photos->map(fn ($p) => $p->url())->implode(', '),
                ], ';');
            }

            fclose($handle);
        };

        $listings->each->update(['status' => 'wyeksportowana', 'exported_at' => now()]);

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
        $lines[] = 'Stan: '.(Item::CONDITIONS[$item->condition] ?? $item->condition);
        if ($item->value) {
            $lines[] = 'Wartość szacunkowa: '.number_format((float) $item->value, 2).' zł';
        }

        return implode("\n", $lines);
    }
}
