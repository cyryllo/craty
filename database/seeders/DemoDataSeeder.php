<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use App\Models\StorageLocation;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryNumberGenerator;
use App\Services\QrCodeGenerator;
use Illuminate\Database\Seeder;

/**
 * Sam przykładowy spis (kategorie/magazyn/przedmioty), bez fałszywych kont
 * dev-owych — wydzielone z DatabaseSeeder, bo to jedyna część, która ma sens
 * poza środowiskiem deweloperskim: Instalator odpala to jako opcjonalny
 * checkbox "załaduj dane przykładowe" (patrz TODO.md "Instalator aplikacji").
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // created_by na przykładowych przedmiotach to główny admin — czy to
        // konto dev-owe z DatabaseSeeder, czy to założone przez Instalator,
        // w obu przypadkach jest dokładnie jedno i jest to jedyny admin.
        $admin = User::where('role', 'admin')->first();

        $narzedzia = Category::create(['name' => 'Narzędzia', 'code' => 'NAR']);
        $elektronarzedzia = Category::create(['name' => 'Elektronarzędzia', 'code' => 'ELN', 'parent_id' => $narzedzia->id]);
        $materialy = Category::create(['name' => 'Materiały', 'code' => 'MAT']);
        $elektronika = Category::create(['name' => 'Elektronika', 'code' => 'ELE']);

        $warehouse = Warehouse::create([
            'name' => 'Magazyn główny',
            'code' => 'M1',
            'address' => 'Hala warsztatowa',
        ]);

        $lokR3P2 = StorageLocation::create(['warehouse_id' => $warehouse->id, 'rack' => '3', 'shelf' => '2']);
        $lokR3P2K1 = StorageLocation::create(['warehouse_id' => $warehouse->id, 'rack' => '3', 'shelf' => '2', 'bin' => '1']);
        $lokR7 = StorageLocation::create(['warehouse_id' => $warehouse->id, 'rack' => '7']);

        $numbers = app(InventoryNumberGenerator::class);
        $qr = app(QrCodeGenerator::class);

        $items = [
            ['name' => 'Wiertarka udarowa Bosch GSB 13 RE', 'category_id' => $elektronarzedzia->id, 'storage_location_id' => $lokR3P2->id, 'value' => 320, 'condition' => 'uzywany', 'description' => 'Moc 600 W, uchwyt 13 mm, walizka + 2 wiertła', 'serial_number' => 'GSB13RE-00812345', 'ean' => '3165140796337'],
            ['name' => 'Szlifierka kątowa Makita 9557', 'category_id' => $elektronarzedzia->id, 'storage_location_id' => $lokR3P2->id, 'value' => 280, 'condition' => 'uzywany', 'ean' => '0088381136264'],
            ['name' => 'Zestaw wierteł do metalu 1-13mm', 'category_id' => $narzedzia->id, 'storage_location_id' => $lokR3P2K1->id, 'value' => 60, 'condition' => 'nowy'],
            ['name' => 'Profile aluminiowe 20x20 (6 szt.)', 'category_id' => $materialy->id, 'storage_location_id' => $lokR7->id, 'value' => 90, 'condition' => 'nowy'],
            ['name' => 'Multimetr cyfrowy UNI-T UT61E', 'category_id' => $elektronika->id, 'storage_location_id' => $lokR3P2K1->id, 'value' => 210, 'condition' => 'uzywany', 'status' => 'do_sprzedazy', 'serial_number' => 'UT61E-2309-4471'],
        ];

        foreach ($items as $data) {
            $category = Category::find($data['category_id']);
            $location = StorageLocation::find($data['storage_location_id']);

            $item = Item::create([
                ...$data,
                'inventory_no' => $numbers->generate($category, $location),
                'created_by' => $admin?->id,
            ]);

            $item->qr_path = $qr->generateForItem($item);
            $item->saveQuietly();
        }
    }
}
