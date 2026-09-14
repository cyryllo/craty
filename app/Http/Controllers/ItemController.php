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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
        $item->load(['category', 'storageLocation.warehouse', 'photos', 'attachments', 'histories.user', 'currentLoan.borrower', 'saleListings']);

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

    /** Widok etykiety do wydruku (naklejka z numerem i kodem QR). */
    public function label(Item $item)
    {
        return view('items.label', ['item' => $item]);
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
