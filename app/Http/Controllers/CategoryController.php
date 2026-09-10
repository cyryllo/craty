<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        return view('categories.index', [
            'categories' => Category::with('parent')->withCount('items')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('categories.form', [
            'category' => new Category,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        Category::create($this->validated($request));

        return redirect()->route('categories.index')->with('status', 'Kategoria dodana.');
    }

    public function edit(Category $category)
    {
        return view('categories.form', [
            'category' => $category,
            'categories' => Category::where('id', '!=', $category->id)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $category->update($this->validated($request, $category));

        return redirect()->route('categories.index')->with('status', 'Kategoria zaktualizowana.');
    }

    public function destroy(Category $category)
    {
        if ($category->items()->exists() || $category->children()->exists()) {
            return back()->with('error', 'Nie można usunąć kategorii, która ma przypisane przedmioty lub podkategorie.');
        }

        $category->delete();

        return redirect()->route('categories.index')->with('status', 'Kategoria usunięta.');
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:8', 'unique:categories,code,'.($category?->id)],
            'parent_id' => ['nullable', 'exists:categories,id'],
        ]);

        $data['code'] = mb_strtoupper($data['code']);

        return $data;
    }
}
