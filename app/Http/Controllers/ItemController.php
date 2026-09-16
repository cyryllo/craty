<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Models\Category;
use App\Models\Item;
use App\Models\ItemAttachment;
use App\Models\ItemPhoto;
use App\Models\StorageLocation;
use App\Services\InventoryNumberGenerator;
use App\Services\QrCodeGenerator;
use App\Support\ItemLabelTemplates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        // Widok kafelkowy albo lista — wybór zapamiętywany w sesji, żeby nie
        // trzeba było przełączać za każdym wejściem na listę przedmiotów.
        $view = $request->input('view');
        if (in_array($view, ['grid', 'list'], true)) {
            session(['items_view' => $view]);
        } else {
            $view = session('items_view', 'grid');
        }

        $items = Item::query()
            ->with(['category', 'storageLocation.warehouse', 'primaryPhoto'])
            ->when($request->filled('q'), fn ($q) => $q->where(function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where('name', 'like', $term)
                    ->orWhere('inventory_no', 'like', $term)
                    ->orWhere('serial_number', 'like', $term)
                    ->orWhere('ean', 'like', $term);
            }))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->integer('category_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(24)
            ->withQueryString();

        return view('items.index', [
            'items' => $items,
            'view' => $view,
            'categories' => Category::orderBy('name')->get(),
            'filters' => $request->only(['q', 'category_id', 'status']),
        ]);
    }

    public function create()
    {
        return view('items.form', [
            'item' => new Item,
            'categories' => Category::orderBy('name')->get(),
            'locations' => StorageLocation::with('warehouse')->get(),
        ]);
    }

    public function store(StoreItemRequest $request, InventoryNumberGenerator $numbers, QrCodeGenerator $qr)
    {
        $item = new Item($request->validated());
        $item->created_by = $request->user()->id;
        $item->inventory_no = $numbers->generate(
            $item->category_id ? Category::find($item->category_id) : null,
            $item->storage_location_id ? StorageLocation::find($item->storage_location_id) : null,
        );
        $item->save();

        $item->qr_path = $qr->generateForItem($item);
        $item->saveQuietly(); // nie duplikujemy wpisu w historii tylko dla qr_path

        $this->syncPhotos($item, $request);
        $this->syncAttachments($item, $request);

        return redirect()->route('items.show', $item)->with('status', __('Item added to inventory.'));
    }

    public function show(Item $item)
    {
        $item->load(['category', 'storageLocation.warehouse', 'photos', 'attachments', 'histories.user', 'currentLoan.borrower', 'saleListings', 'activeSaleListing', 'draftSaleListing']);

        return view('items.show', ['item' => $item]);
    }

    public function edit(Item $item)
    {
        return view('items.form', [
            'item' => $item,
            'categories' => Category::orderBy('name')->get(),
            'locations' => StorageLocation::with('warehouse')->get(),
        ]);
    }

    public function update(UpdateItemRequest $request, Item $item)
    {
        $item->update($request->validated());

        // Skoro ktoś przeszedł przez pełną edycję, uznajemy że "dodany naprędce
        // ze skanera" przedmiot został już przejrzany — flaga znika, niezależnie
        // od tego, czy akurat uzupełnił kategorię/lokalizację (patrz TODO.md "PWA").
        if ($item->needs_completion) {
            $item->forceFill(['needs_completion' => false])->saveQuietly();
        }

        $this->syncPhotos($item, $request);
        $this->syncAttachments($item, $request);

        return redirect()->route('items.show', $item)->with('status', __('Changes saved.'));
    }

    public function destroy(Item $item)
    {
        foreach ($item->photos as $photo) {
            Storage::disk('public')->delete($photo->path);
        }
        foreach ($item->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->path);
        }
        if ($item->qr_path) {
            Storage::disk('public')->delete($item->qr_path);
        }

        $item->delete();

        return redirect()->route('items.index')->with('status', __('Item removed from inventory.'));
    }

    /**
     * Widok etykiety do wydruku. Szablon (rozmiar/zawartość) i włącznik
     * ceny wybiera się PO otwarciu podglądu (patrz toolbar w
     * items/label.blade.php) — zwykły GET z query stringiem, bo ID
     * przedmiotu i tak już jest w adresie, więc zmiana szablonu to tylko
     * przeładowanie tej samej strony, bez utraty żadnych danych.
     */
    public function label(Request $request, Item $item)
    {
        return view('items.label', [
            'item' => $item,
            'template' => $this->resolveTemplateKey($request),
            'showPrice' => $request->boolean('price'),
        ]);
    }

    /**
     * Druk wielu etykiet naraz (zaznaczone checkboxami na /items, patrz
     * items/index.blade.php) — jedna strona z etykietą na etykietę,
     * oddzielone page-break, żeby każda wydrukowała się osobno na
     * naklejce/etykiecie z drukarki termicznej. Otwierana w nowej karcie
     * (target="_blank"), żeby nie tracić filtrów/zaznaczenia na /items.
     * Szablon/cenę da się zmienić bez wracania do /items — toolbar na
     * labels-print.blade.php resubmituje ten sam zestaw ID (przeniesiony
     * jako ukryte pola), tylko z innym szablonem/ceną.
     */
    public function printLabels(Request $request)
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['integer', 'exists:items,id'],
            'template' => ['nullable', Rule::in(ItemLabelTemplates::keys())],
            'price' => ['nullable', 'boolean'],
        ]);

        // orderBy + whereIn nie gwarantuje kolejności zaznaczenia — kolejność
        // po nazwie jest przewidywalna i wystarczająca (etykiety i tak trafiają
        // do jednej rolki/arkusza, nie ma znaczenia "kto pierwszy").
        $items = Item::query()->whereIn('id', $data['items'])->orderBy('name')->get();

        return view('items.labels-print', [
            'items' => $items,
            'template' => $data['template'] ?? ItemLabelTemplates::DEFAULT,
            'showPrice' => $request->boolean('price'),
        ]);
    }

    private function resolveTemplateKey(Request $request): string
    {
        $key = $request->query('template');

        return in_array($key, ItemLabelTemplates::keys(), true) ? $key : ItemLabelTemplates::DEFAULT;
    }

    public function destroyPhoto(Item $item, ItemPhoto $photo)
    {
        abort_unless($photo->item_id === $item->id, 404);

        Storage::disk('public')->delete($photo->path);
        $wasPrimary = $photo->is_primary;
        $photo->delete();

        // Jeśli usunięto zdjęcie główne, a zostały inne — któreś musi przejąć rolę okładki.
        if ($wasPrimary) {
            $item->photos()->first()?->update(['is_primary' => true]);
        }

        return back()->with('status', __('Photo removed.'));
    }

    public function destroyAttachment(Item $item, ItemAttachment $attachment)
    {
        abort_unless($attachment->item_id === $item->id, 404);

        Storage::disk('public')->delete($attachment->path);
        $attachment->delete();

        return back()->with('status', __('Attachment removed.'));
    }

    private function syncPhotos(Item $item, Request $request): void
    {
        foreach ($request->file('photos', []) as $index => $photo) {
            $path = $photo->store('items/'.$item->id, 'public');

            $item->photos()->create([
                'path' => $path,
                'is_primary' => $item->photos()->count() === 0 && $index === 0,
                'sort_order' => $item->photos()->count(),
            ]);
        }
    }

    private function syncAttachments(Item $item, Request $request): void
    {
        $labels = $request->input('attachment_labels', []);

        foreach ($request->file('attachments', []) as $index => $file) {
            $path = $file->store('items/'.$item->id.'/attachments', 'public');

            $item->attachments()->create([
                'path' => $path,
                'label' => $labels[$index] ?? $file->getClientOriginalName(),
            ]);
        }
    }
}
