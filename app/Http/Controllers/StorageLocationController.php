<?php

namespace App\Http\Controllers;

use App\Models\StorageLocation;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class StorageLocationController extends Controller
{
    public function index()
    {
        return view('storage-locations.index', [
            'locations' => StorageLocation::with('warehouse')->withCount('items')->get(),
        ]);
    }

    public function create()
    {
        return view('storage-locations.form', [
            'location' => new StorageLocation,
            'warehouses' => Warehouse::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        StorageLocation::create($this->validated($request));

        return redirect()->route('storage-locations.index')->with('status', __('Location added.'));
    }

    public function edit(StorageLocation $storageLocation)
    {
        return view('storage-locations.form', [
            'location' => $storageLocation,
            'warehouses' => Warehouse::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, StorageLocation $storageLocation)
    {
        $storageLocation->update($this->validated($request));

        return redirect()->route('storage-locations.index')->with('status', __('Location updated.'));
    }

    public function destroy(StorageLocation $storageLocation)
    {
        if ($storageLocation->items()->exists()) {
            return back()->with('error', __('This location cannot be deleted because it has items in it.'));
        }

        $storageLocation->delete();

        return redirect()->route('storage-locations.index')->with('status', __('Location deleted.'));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'rack' => ['nullable', 'string', 'max:32'],
            'shelf' => ['nullable', 'string', 'max:32'],
            'bin' => ['nullable', 'string', 'max:32'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
