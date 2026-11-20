<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Item;
use App\Models\ItemAttachment;
use App\Models\ItemPhoto;
use App\Models\Room;
use App\Models\StorageLocation;
use App\Models\Warehouse;
use App\Services\InventoryNumberGenerator;
use App\Services\QrCodeGenerator;
use App\Support\ItemLabelTemplates;
use App\Support\LocationScope;
use App\Support\Modules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ItemController extends Controller
{
    /** Parametry listy zapamiętywane w sesji ("pamiętaj ostatnie filtry"). */
    private const LIST_PARAMS = ['q', 'category_id', 'status', 'sort', 'dir', 'storage_location_id', 'warehouse_id', 'room_id', 'rack', 'shelf'];

    private const LOCATION_PARAMS = LocationScope::PARAMS;

    /**
     * Sortowanie listy: klucz => kolumna albo podzapytanie. "created" to
     * dotychczasowa domyślna kolejność (najnowsze na górze).
     */
    public const SORTS = ['created', 'name', 'inventory_no', 'category', 'location', 'value', 'status'];

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

        // Ostatnie wyszukiwanie, filtry i sortowanie też są pamiętane: wejście
        // na samo /items (np. z menu) przywraca je przekierowaniem, żeby adres,
        // paginacja i linki sortowania zawierały pełny stan. Każde jawne użycie
        // filtrów (formularz, link sortowania, link ze Struktury magazynu) ma
        // któryś z parametrów albo znacznik "f" i nadpisuje zapamiętany stan.
        if ($request->boolean('clear')) {
            session()->forget('items_filters');

            return redirect()->route('items.index');
        }
        if ($request->hasAny([...self::LIST_PARAMS, 'f'])) {
            session(['items_filters' => array_filter($request->only(self::LIST_PARAMS), fn ($v) => $v !== null && $v !== '')]);
        } elseif ($saved = session('items_filters')) {
            return redirect()->route('items.index', [...$saved, 'f' => 1]);
        }

        [$sort, $dir] = $this->listSort($request);
        $location = LocationScope::fromArray($request->query());

        $items = Item::query()
            ->with(['category', 'storageLocation.warehouse', 'storageLocation.room', 'primaryPhoto'])
            ->when($request->filled('q'), fn ($q) => $q->where(function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where('name', 'like', $term)
                    ->orWhere('inventory_no', 'like', $term)
                    ->orWhere('serial_number', 'like', $term)
                    ->orWhere('ean', 'like', $term);
            }))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->integer('category_id')))
            ->tap(fn ($q) => $location->apply($q))
            // Bez filtra statusu lista pokazuje tylko to, co jest w magazynie
            // — sprzedane/wycofane tylko po jawnym wybraniu ich filtra.
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')), fn ($q) => $q->inStock())
            ->tap(fn ($q) => $this->applySort($q, $sort, $dir))
            ->paginate(24)
            ->withQueryString();

        $filters = $request->only(['q', 'category_id', 'status']);

        return view('items.index', [
            'items' => $items,
            'view' => $view,
            'categories' => Category::orderBy('name')->get(),
            'filters' => $filters,
            'sort' => $sort,
            'dir' => $dir,
            'hasActiveFilters' => (bool) array_filter($filters) || $request->hasAny(self::LOCATION_PARAMS),
            'locationFilter' => $location->label(),
            'locationBackUrl' => ($warehouseId = $location->warehouseId()) ? route('warehouse-structure.index').'#warehouse-'.$warehouseId : null,
            'locationParams' => $location->params,
            'clearLocationUrl' => route('items.index', [...$request->except([...self::LOCATION_PARAMS, 'page', 'f']), 'f' => 1]),
        ]);
    }

    /**
     * Link do posortowania listy po kolumnie: pierwsze kliknięcie rosnąco
     * (data dodania: od najnowszych), kolejne odwraca kierunek.
     */
    public static function sortUrl(string $column, string $currentSort, string $currentDir): string
    {
        $default = $column === 'created' ? 'desc' : 'asc';
        $dir = $column === $currentSort ? ($currentDir === 'asc' ? 'desc' : 'asc') : $default;

        return route('items.index', [...request()->except(['sort', 'dir', 'page', 'f']), 'sort' => $column, 'dir' => $dir, 'f' => 1]);
    }

    /** Link do konkretnego sortowania (lista wyboru w widoku kafelków). */
    public static function sortLink(string $column, string $dir): string
    {
        return route('items.index', [...request()->except(['sort', 'dir', 'page', 'f']), 'sort' => $column, 'dir' => $dir, 'f' => 1]);
    }

    /** @return array{0: string, 1: string} */
    private function listSort(Request $request): array
    {
        $sort = in_array($request->input('sort'), self::SORTS, true) ? $request->input('sort') : 'created';
        $dir = in_array($request->input('dir'), ['asc', 'desc'], true) ? $request->input('dir') : ($sort === 'created' ? 'desc' : 'asc');

        return [$sort, $dir];
    }

    private function applySort($query, string $sort, string $dir): void
    {
        match ($sort) {
            'category' => $query->orderBy(Category::select('name')->whereColumn('categories.id', 'items.category_id'), $dir),
            'location' => $query->orderBy(StorageLocation::select('code')->whereColumn('storage_locations.id', 'items.storage_location_id'), $dir),
            'name', 'inventory_no', 'value', 'status' => $query->orderBy($sort, $dir),
            default => $query->orderBy('created_at', $dir),
        };

        // Stała kolejność przy równych wartościach (np. ta sama kategoria),
        // żeby paginacja nie gubiła ani nie dublowała przedmiotów.
        $query->orderBy('id', $sort === 'created' ? $dir : 'asc');
    }

    public function create()
    {
        return view('items.form', [
            'item' => new Item,
            'categories' => Category::orderBy('name')->get(),
            'locations' => $this->locationOptions(),
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
        $item->load(['category', 'storageLocation.warehouse', 'storageLocation.room', 'photos', 'attachments', 'histories.user', 'currentLoan.borrower', 'currentLoan.contact', 'currentLoan.group.loans', 'saleListings', 'activeSaleListing', 'draftSaleListing']);

        // Zakończone wypożyczenia (z linkami do protokołów) i lista kontaktów do
        // formularza wypożyczenia — to drugie tylko z włączonym modułem Kontakty.
        $pastLoans = $item->loans()->whereNotNull('returned_at')->with(['contact', 'borrower'])->latest('returned_at')->limit(5)->get();
        $reservations = $item->reservations()->active()->with('contact')->get();
        $contacts = Modules::isEnabled('contacts') ? Contact::orderBy('name')->get(['id', 'name']) : collect();

        // Moduł Serwis: naprawa w toku, ostatnie naprawy i przeglądy, serwisy z Kontaktów.
        $service = Modules::isEnabled('service') ? [
            'openRepair' => $item->openRepair()->with('contact')->first(),
            'repairs' => $item->repairs()->whereNotNull('returned_at')->with('contact')->limit(5)->get(),
            'inspections' => $item->inspections()->with('user')->limit(5)->get(),
            'serviceContacts' => Modules::isEnabled('contacts')
                ? Contact::orderByRaw("type = 'serwis' desc")->orderBy('name')->get(['id', 'name', 'type'])
                : collect(),
        ] : null;

        return view('items.show', compact('item', 'pastLoans', 'contacts', 'service', 'reservations'));
    }

    public function edit(Item $item)
    {
        return view('items.form', [
            'item' => $item,
            'categories' => Category::orderBy('name')->get(),
            'locations' => $this->locationOptions(),
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

    /**
     * Ponowne wygenerowanie numeru ewidencyjnego i kodu QR na podstawie
     * aktualnej kategorii/lokalizacji — przydaje się, gdy przedmiot dodano
     * bez ich uzupełnienia (numer dostaje wtedy segmenty GEN/BRAK, patrz
     * InventoryNumberGenerator) i uzupełniono je dopiero po fakcie. Stary
     * plik QR (nazwany po starym numerze ewidencyjnym) jest usuwany, żeby
     * nie zostawiać osieroconych plików w app-storage/app/public/qr.
     */
    public function regenerateQr(Item $item, InventoryNumberGenerator $numbers, QrCodeGenerator $qr)
    {
        $oldQrPath = $item->qr_path;

        $item->inventory_no = $numbers->generate($item->category, $item->storageLocation);
        $item->qr_path = $qr->generateForItem($item);
        $item->saveQuietly(); // numer/QR nie są śledzone w historii (patrz ItemObserver)

        if ($oldQrPath && $oldQrPath !== $item->qr_path) {
            Storage::disk('public')->delete($oldQrPath);
        }

        return back()->with('status', __('Inventory number and QR code regenerated.'));
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

    /**
     * Przesuwa zdjęcie o jedną pozycję wcześniej/później (zamiana sort_order
     * z sąsiadem w tym kierunku) — brak sąsiada w danym kierunku to po
     * prostu no-op, nie błąd. Zdjęcie na pierwszej pozycji zawsze staje się
     * okładką (is_primary), tak samo jak po usunięciu okładki w destroyPhoto().
     */
    public function movePhoto(Request $request, Item $item, ItemPhoto $photo)
    {
        abort_unless($photo->item_id === $item->id, 404);

        $direction = $request->validate([
            'direction' => ['required', Rule::in(['earlier', 'later'])],
        ])['direction'];

        $neighbor = $direction === 'earlier'
            ? ItemPhoto::where('item_id', $item->id)->where('sort_order', '<', $photo->sort_order)->orderByDesc('sort_order')->first()
            : ItemPhoto::where('item_id', $item->id)->where('sort_order', '>', $photo->sort_order)->orderBy('sort_order')->first();

        if ($neighbor) {
            [$photo->sort_order, $neighbor->sort_order] = [$neighbor->sort_order, $photo->sort_order];
            $photo->save();
            $neighbor->save();

            ItemPhoto::where('item_id', $item->id)->update(['is_primary' => false]);
            $item->photos()->first()?->update(['is_primary' => true]);
        }

        // Zwykłe back() wraca na sam początek strony (przeładowanie nawiguje
        // od nowa) — przy przesuwaniu wielu zdjęć pod rząd to uciążliwe, więc
        // doklejamy kotwicę sekcji zdjęć (items/form.blade.php, #item-photos),
        // żeby przeglądarka od razu przewinęła z powrotem w to miejsce.
        return redirect(url()->previous().'#item-photos')->with('status', __('Photo order updated.'));
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

    /** Opcje pola "Lokalizacja" na formularzu przedmiotu. */
    private function locationOptions()
    {
        return StorageLocation::with(['warehouse', 'room'])->get()
            ->sortBy(fn (StorageLocation $l) => $l->label(), SORT_NATURAL)
            ->values();
    }
}
