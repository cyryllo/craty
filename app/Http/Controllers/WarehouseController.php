<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index()
    {
        return view('warehouses.index', [
            'warehouses' => Warehouse::withCount('storageLocations')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('warehouses.form', ['warehouse' => new Warehouse]);
    }

    public function store(Request $request)
    {
        Warehouse::create($this->validated($request));

        return redirect()->route('warehouses.index')->with('status', __('Warehouse added.'));
    }

    public function edit(Warehouse $warehouse)
    {
        return view('warehouses.form', ['warehouse' => $warehouse]);
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $warehouse->update($this->validated($request, $warehouse));

        return redirect()->route('warehouses.index')->with('status', __('Warehouse updated.'));
    }

    public function destroy(Warehouse $warehouse)
    {
        if ($warehouse->storageLocations()->exists()) {
            return back()->with('error', __('This warehouse cannot be deleted because it has locations defined.'));
        }

        $warehouse->delete();

        return redirect()->route('warehouses.index')->with('status', __('Warehouse deleted.'));
    }

    private function validated(Request $request, ?Warehouse $warehouse = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:8', 'unique:warehouses,code,'.($warehouse?->id)],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $data['code'] = mb_strtoupper($data['code']);

        return $data;
    }
}
