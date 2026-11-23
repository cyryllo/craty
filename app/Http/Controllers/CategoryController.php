<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Kategorie jako drzewo (jak Struktura magazynu): kategorie główne to karty,
 * podkategorie rozwijane pod nimi na dowolną głębokość. Usuwanie jest na
 * dole formularza edycji, jak przy magazynach i lokalizacjach.
 */
class CategoryController extends Controller
{
    public function index()
    {
        // Liczniki jak w Strukturze magazynu: tylko przedmioty w magazynie (bez sprzedanych i wycofanych).
        $all = Category::withCount(['items' => fn ($q) => $q->inStock()])->orderBy('name')->get();
        $children = $all->groupBy(fn (Category $c) => $c->parent_id ?? 0);

        $totals = [];
        $total = function (Category $category, array $seen = []) use (&$total, &$totals, $children): int {
            if (isset($seen[$category->id])) {
                return 0; // zabezpieczenie przed pętlą w starych danych
            }
            $seen[$category->id] = true;

            return $totals[$category->id] = $category->items_count
                + $children->get($category->id, collect())->sum(fn (Category $child) => $total($child, $seen));
        };

        $roots = $children->get(0, collect());
        // Kategoria z nadrzędną, której już nie ma (albo z pętlą), też musi być widoczna: pokazujemy ją jako główną.
        $reachable = self::reachableIds($roots, $children);
        $roots = $roots->concat($all->reject(fn (Category $c) => isset($reachable[$c->id]) || $c->parent_id === null))->values();
        $roots->each(fn (Category $c) => $total($c));

        return view('categories.index', [
            'roots' => $roots,
            'children' => $children,
            'totals' => $totals,
        ]);
    }

    public function create(Request $request)
    {
        return view('categories.form', [
            'category' => new Category(['parent_id' => $request->integer('parent_id') ?: null]),
            'options' => self::treeOptions(),
        ]);
    }

    public function store(Request $request)
    {
        Category::create($this->validated($request));

        return redirect()->route('categories.index')->with('status', __('Category added.'));
    }

    public function edit(Category $category)
    {
        return view('categories.form', [
            'category' => $category->loadCount(['items', 'children']),
            'options' => self::treeOptions($category),
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $category->update($this->validated($request, $category));

        return redirect()->route('categories.index')->with('status', __('Category updated.'));
    }

    public function destroy(Category $category)
    {
        if ($category->items()->exists() || $category->children()->exists()) {
            return back()->with('error', __('This category cannot be deleted because it has items or subcategories assigned.'));
        }

        $category->delete();

        return redirect()->route('categories.index')->with('status', __('Category deleted.'));
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:8', 'alpha_dash:ascii', 'unique:categories,code,'.($category?->id)],
            'parent_id' => ['nullable', 'exists:categories,id', function ($attribute, $value, $fail) use ($category) {
                // Kategoria nie może trafić pod samą siebie ani pod swoją podkategorię: powstałaby pętla.
                if ($category && in_array((int) $value, [$category->id, ...self::descendantIds($category)], true)) {
                    $fail(__('A category can\'t be placed inside itself or its own subcategory.'));
                }
            }],
        ]);

        $data['code'] = mb_strtoupper($data['code']);

        return $data;
    }

    /**
     * Kategorie do listy "nadrzędna" w kolejności drzewa, z głębokością do
     * wcięcia; bez edytowanej kategorii i jej podkategorii.
     *
     * @return Collection<int, array{category: Category, depth: int}>
     */
    public static function treeOptions(?Category $exclude = null): Collection
    {
        $children = Category::orderBy('name')->get()->groupBy(fn (Category $c) => $c->parent_id ?? 0);
        $skip = $exclude ? [$exclude->id => true] : [];
        $options = collect();

        $walk = function (Collection $nodes, int $depth) use (&$walk, &$options, &$skip, $children) {
            foreach ($nodes as $node) {
                if (isset($skip[$node->id])) {
                    continue;
                }
                $skip[$node->id] = true;
                $options->push(['category' => $node, 'depth' => $depth]);
                $walk($children->get($node->id, collect()), $depth + 1);
            }
        };
        $walk($children->get(0, collect()), 0);

        // Osierocone (nadrzędnej już nie ma) na końcu, żeby nie zniknęły z listy.
        $excluded = $exclude ? array_flip(self::descendantIds($exclude)) : [];
        $children->flatten()->each(function (Category $c) use (&$options, $skip, $excluded) {
            if (! isset($skip[$c->id]) && ! isset($excluded[$c->id])) {
                $options->push(['category' => $c, 'depth' => 0]);
            }
        });

        return $options;
    }

    /** @return list<int> identyfikatory wszystkich podkategorii (na każdym poziomie) */
    public static function descendantIds(Category $category): array
    {
        $children = Category::get(['id', 'parent_id'])->groupBy(fn (Category $c) => $c->parent_id ?? 0);
        $ids = [];
        $queue = [$category->id];
        while ($queue) {
            foreach ($children->get(array_shift($queue), collect()) as $child) {
                if (! in_array($child->id, $ids, true) && $child->id !== $category->id) {
                    $ids[] = $child->id;
                    $queue[] = $child->id;
                }
            }
        }

        return $ids;
    }

    /** @return array<int, true> */
    private static function reachableIds(Collection $roots, Collection $children): array
    {
        $seen = [];
        $queue = $roots->all();
        while ($queue) {
            $node = array_shift($queue);
            if (isset($seen[$node->id])) {
                continue;
            }
            $seen[$node->id] = true;
            array_push($queue, ...$children->get($node->id, collect())->all());
        }

        return $seen;
    }
}
