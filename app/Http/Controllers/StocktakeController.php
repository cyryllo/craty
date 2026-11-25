<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\StorageLocation;
use App\Models\Stocktake;
use App\Support\LocationScope;
use Illuminate\Http\Request;

/**
 * Inwentaryzacja: wybierasz miejsce (lokalizację razem ze wszystkim pod nią
 * albo filtr miejsca z listy przedmiotów), skanujesz serią to, co tam
 * faktycznie leży, i kończysz. Raport: braki, rzeczy nie na swoim miejscu
 * (z "przenieś tutaj", które poprawia lokalizację w kartotece), zgodne oraz
 * wypożyczone/w naprawie, które nie liczą się jako braki. Brakującym nic
 * nie jest zmieniane automatycznie (decyzja właściciela).
 */
class StocktakeController extends Controller
{
    public function index()
    {
        return view('stocktakes.index', [
            'stocktakes' => Stocktake::withCount('scans')->with('starter')->latest()->paginate(20),
            'locations' => StorageLocation::with(['warehouse', 'room'])->get()
                ->sortBy(fn (StorageLocation $location) => $location->label(), SORT_NATURAL)->values(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate(['location_id' => ['nullable', 'integer', 'exists:storage_locations,id']]);

        $scope = $request->filled('location_id')
            ? LocationScope::forLocation(StorageLocation::findOrFail($request->integer('location_id')))
            : LocationScope::fromArray($request->only(LocationScope::PARAMS));

        if ($scope->isEmpty() || ! ($label = $scope->label())) {
            return back()->withErrors(['location_id' => __('Choose the place to count.')]);
        }

        $stocktake = Stocktake::create([
            'scope' => $scope->params,
            'label' => $label,
            'started_by' => $request->user()->id,
        ]);

        return redirect()->route('stocktakes.show', $stocktake);
    }

    public function show(Stocktake $stocktake)
    {
        if (! $stocktake->isFinished()) {
            $expected = $stocktake->expectedItemsQuery()->count();
            $scans = $stocktake->scans()->get();

            return view('stocktakes.scan', [
                'stocktake' => $stocktake,
                'expectedCount' => $expected,
                'inPlaceCount' => $scans->where('in_place', true)->count(),
                'elsewhereCount' => $scans->where('in_place', false)->count(),
            ]);
        }

        return view('stocktakes.report', ['stocktake' => $stocktake, ...self::reportData($stocktake)]);
    }

    /** Części raportu zakończonej inwentaryzacji, wspólne dla strony i pliku CSV. */
    public static function reportData(Stocktake $stocktake): array
    {
        $scans = $stocktake->scans()->with('item.storageLocation.warehouse', 'item.storageLocation.room')->get();
        $scannedIds = $scans->pluck('item_id')->all();
        $expectedIds = $stocktake->expected_item_ids ?? [];
        $awayIds = $stocktake->away_item_ids ?? [];
        $items = Item::with('storageLocation.warehouse', 'storageLocation.room')->whereIn('id', $expectedIds)->get()->keyBy('id');

        return [
            'missing' => $items->only(array_diff($expectedIds, $scannedIds, $awayIds))->values(),
            'away' => $items->only(array_diff($awayIds, $scannedIds))->values(),
            'misplaced' => $scans->where('in_place', false)->values(),
            'matching' => $scans->where('in_place', true)->values(),
        ];
    }

    /** Raport zakończonej inwentaryzacji w CSV: jeden wiersz na przedmiot, z wynikiem. */
    public function reportCsv(Stocktake $stocktake)
    {
        abort_unless($stocktake->isFinished(), 404);
        $data = self::reportData($stocktake);
        $rows = [];
        $add = function (string $result, ?Item $item) use (&$rows) {
            if ($item) {
                $rows[] = [$result, $item->name, $item->inventory_no, $item->storageLocation?->label(), $item->statusLabel()];
            }
        };
        foreach ($data['missing'] as $item) {
            $add(__('Missing'), $item);
        }
        foreach ($data['misplaced'] as $scan) {
            $add(__('Not in its place'), $scan->item);
        }
        foreach ($data['away'] as $item) {
            $add(__('Loaned out or under repair'), $item);
        }
        foreach ($data['matching'] as $scan) {
            $add(__('In place'), $scan->item);
        }

        return \App\Support\Csv::download(
            'craty-inwentaryzacja-'.$stocktake->id.'-'.$stocktake->finished_at->format('Y-m-d').'.csv',
            ['wynik', 'przedmiot', 'nr_ewidencyjny', 'zapisane_miejsce', 'status'],
            $rows,
        );
    }

    /** Zapis jednego skanu (JSON, wołane z serii skanów w resources/js/scan.js). */
    public function scan(Request $request, Stocktake $stocktake)
    {
        abort_if($stocktake->isFinished(), 404);
        $item = Item::with('storageLocation')->findOrFail($request->validate(['item_id' => ['required', 'integer']])['item_id']);

        $inPlace = $stocktake->locationScope()->contains($item->storageLocation);
        $stocktake->scans()->firstOrCreate(['item_id' => $item->id], [
            'in_place' => $inPlace,
            'scanned_by' => $request->user()->id,
        ]);

        return response()->json([
            'in_place' => $inPlace,
            'message' => $inPlace ? __('in place') : __('belongs to: :place', ['place' => $item->storageLocation?->label() ?? __('no location')]),
            'label' => $inPlace ? __('in place') : __('not in its place'),
        ]);
    }

    public function finish(Request $request, Stocktake $stocktake)
    {
        abort_if($stocktake->isFinished(), 404);

        $expected = $stocktake->expectedItemsQuery()->get(['id', 'status']);

        $stocktake->update([
            'expected_item_ids' => $expected->pluck('id')->all(),
            'away_item_ids' => $expected->whereIn('status', Stocktake::AWAY_STATUSES)->pluck('id')->values()->all(),
            'finished_at' => now(),
            'finished_by' => $request->user()->id,
        ]);

        return redirect()->route('stocktakes.show', $stocktake)->with('status', __('Stocktake finished.'));
    }

    /** "Przenieś tutaj": przedmiot znaleziony nie na swoim miejscu dostaje lokalizację liczonego miejsca. */
    public function move(Request $request, Stocktake $stocktake, Item $item)
    {
        $scan = $stocktake->scans()->where('item_id', $item->id)->where('in_place', false)->whereNull('moved_at')->firstOrFail();
        $target = $stocktake->locationScope()->ownLocation();
        abort_unless($target, 404);

        $item->update(['storage_location_id' => $target->id]);
        $scan->update(['moved_at' => now()]);

        return back()->with('status', __(':item moved to :place.', ['item' => $item->name, 'place' => $target->label()]));
    }

    public function destroy(Stocktake $stocktake)
    {
        $stocktake->delete();

        return redirect()->route('stocktakes.index')->with('status', __('Stocktake deleted.'));
    }
}
