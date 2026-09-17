<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Regresja: Storage::disk('public')->url() kiedyś budował się ze statycznego
 * APP_URL (config/filesystems.php), więc zdjęcia/QR 404-owały przy wejściu na
 * appkę z innego hosta niż ten w .env — np. z telefonu po adresie IP w sieci
 * lokalnej zamiast "localhost", na którym stoi APP_URL w dev. Patrz komentarz
 * w config/filesystems.php.
 */
class PublicAssetUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_disk_url_is_root_relative_not_tied_to_app_url(): void
    {
        $url = Storage::disk('public')->url('qr/example.svg');

        $this->assertSame('/storage/qr/example.svg', $url);
        $this->assertStringStartsNotWith('http', $url);
    }

    public function test_csv_export_still_uses_absolute_photo_urls_for_use_outside_the_app(): void
    {
        Storage::fake('public');
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $item = Item::create([
            'inventory_no' => 'NAR-BRAK-2026-00001', 'name' => 'Wiertarka', 'condition' => 'nowy', 'status' => 'do_sprzedazy',
        ]);
        $photo = $item->photos()->create(['path' => 'items/1/a.jpg', 'is_primary' => true, 'sort_order' => 0]);
        $item->saleListings()->create(['platform' => 'olx', 'title' => 'Wiertarka']);

        $csv = $this->actingAs($magazynier)->get('/sales/eksport.csv')->streamedContent();

        $this->assertStringContainsString('http://localhost/storage/'.$photo->path, $csv);
    }
}
