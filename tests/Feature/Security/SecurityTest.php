<?php

namespace Tests\Feature\Security;

use App\Http\Middleware\AddSecurityHeaders;
use App\Models\AppSetting;
use App\Models\Item;
use App\Models\SaleListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Typowe dziury w aplikacjach webowych, sprawdzane na tej aplikacji:
 * wgrywanie plików, wstrzykiwanie kodu (XSS), nadawanie sobie uprawnień,
 * wyłączone konta, zgadywanie haseł i nagłówki bezpieczeństwa. Uprawnienia
 * do poszczególnych tras: RouteAccessTest.
 */
class SecurityTest extends TestCase
{
    use RefreshDatabase;

    private function item(array $attributes = []): Item
    {
        return Item::create($attributes + [
            'inventory_no' => 'NAR-BRAK-2026-00001', 'name' => 'Wiertarka', 'condition' => 'uzywany', 'status' => 'dostepny',
        ]);
    }

    // ---------- nagłówki ----------

    public function test_pages_send_security_headers(): void
    {
        foreach (['/', '/login'] as $url) {
            $response = $this->get($url);
            foreach (AddSecurityHeaders::HEADERS as $name => $value) {
                $response->assertHeader($name, $value);
            }
        }
    }

    // ---------- wgrywanie plików ----------

    /** Załącznik .php w publicznym /storage/ mógłby zostać uruchomiony na serwerze. */
    public function test_php_and_html_files_are_rejected_as_attachments(): void
    {
        Storage::fake('public');
        $item = $this->item();
        $magazynier = User::factory()->create(['role' => 'magazynier']);

        foreach ([['shell.php', 'application/x-php'], ['page.html', 'text/html'], ['icon.svg', 'image/svg+xml']] as [$name, $mime]) {
            $this->actingAs($magazynier)->put(route('items.update', $item), [
                'name' => 'Wiertarka', 'condition' => 'uzywany', 'status' => 'dostepny',
                'attachments' => [UploadedFile::fake()->createWithContent($name, '<?php echo 1; ?>')->mimeType($mime)],
            ])->assertSessionHasErrors('attachments.0');
        }

        $this->assertSame(0, $item->attachments()->count());
    }

    public function test_a_pdf_attachment_is_still_accepted(): void
    {
        Storage::fake('public');
        $item = $this->item();

        $this->actingAs(User::factory()->create(['role' => 'magazynier']))->put(route('items.update', $item), [
            'name' => 'Wiertarka', 'condition' => 'uzywany', 'status' => 'dostepny',
            'attachments' => [UploadedFile::fake()->create('faktura.pdf', 50, 'application/pdf')],
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, $item->attachments()->count());
    }

    public function test_a_php_file_renamed_to_jpg_is_rejected_as_a_photo(): void
    {
        Storage::fake('public');
        $item = $this->item();

        $this->actingAs(User::factory()->create(['role' => 'magazynier']))->put(route('items.update', $item), [
            'name' => 'Wiertarka', 'condition' => 'uzywany', 'status' => 'dostepny',
            'photos' => [$this->realUpload('photo.jpg', '<?php system($_GET["c"]); ?>')],
        ])->assertSessionHasErrors('photos.0');
    }

    /**
     * Prawdziwy plik tymczasowy zamiast UploadedFile::fake() — fałszywe pliki
     * Laravela podają typ MIME z rozszerzenia nazwy, więc .php nazwany .jpg
     * udawałby zdjęcie. Prawdziwe wgranie rozpoznaje typ po zawartości (finfo).
     */
    private function realUpload(string $name, string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'upload');
        file_put_contents($path, $content);

        return new UploadedFile($path, $name, null, null, true);
    }

    public function test_a_php_file_renamed_to_pdf_is_rejected_as_an_attachment(): void
    {
        Storage::fake('public');
        $item = $this->item();

        $this->actingAs(User::factory()->create(['role' => 'magazynier']))->put(route('items.update', $item), [
            'name' => 'Wiertarka', 'condition' => 'uzywany', 'status' => 'dostepny',
            'attachments' => [$this->realUpload('faktura.pdf', '<?php system($_GET["c"]); ?>')],
        ])->assertSessionHasErrors('attachments.0');
    }

    /** SVG może zawierać skrypt, a favicon leży w publicznym /storage/ w domenie aplikacji. */
    public function test_svg_favicon_and_logo_are_rejected(): void
    {
        Storage::fake('public');
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>';

        $this->actingAs(User::factory()->create(['role' => 'admin']))->post(route('settings.app.update'), [
            'favicon' => UploadedFile::fake()->createWithContent('favicon.svg', $svg),
            'logo' => UploadedFile::fake()->createWithContent('logo.svg', $svg),
        ])->assertSessionHasErrors(['favicon', 'logo']);
    }

    // ---------- XSS ----------

    public function test_html_in_item_names_is_escaped_in_the_panel_and_on_the_public_page(): void
    {
        $evil = '<script>alert("xss")</script>';
        $item = $this->item(['name' => $evil, 'status' => 'do_sprzedazy']);
        $listing = SaleListing::create([
            'item_id' => $item->id, 'platform' => 'olx', 'title' => $evil, 'description' => $evil,
            'price' => 10, 'status' => 'wyeksportowana', 'exported_at' => now(),
        ]);

        $this->actingAs(User::factory()->create(['role' => 'magazynier']))->get(route('items.index'))
            ->assertDontSee($evil, false)->assertSee(e($evil), false);
        $this->get('/')->assertDontSee($evil, false);
        $this->get(route('marketplace.show', $listing))->assertDontSee($evil, false);
    }

    // ---------- uprawnienia i konta ----------

    public function test_a_user_cannot_make_themselves_admin_through_the_profile_form(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);

        $this->actingAs($magazynier)->patch(route('profile.update'), [
            'name' => 'Ja', 'email' => $magazynier->email, 'role' => 'admin', 'active' => true,
        ]);

        $this->assertSame('magazynier', $magazynier->fresh()->role);
    }

    public function test_warehouse_worker_cannot_change_other_accounts(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($magazynier)->put(route('users.update', $admin), [
            'name' => 'X', 'email' => $admin->email, 'role' => 'magazynier',
        ])->assertForbidden();
        $this->actingAs($magazynier)->delete(route('users.destroy', $admin))->assertForbidden();

        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_a_deactivated_account_cannot_log_in(): void
    {
        $user = User::factory()->create(['role' => 'magazynier', 'active' => false]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    /** Wyłączenie konta działa od razu, także w już otwartej sesji. */
    public function test_a_deactivated_account_is_logged_out_on_its_next_request(): void
    {
        $user = User::factory()->create(['role' => 'magazynier']);
        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $user->forceFill(['active' => false])->save();

        $this->actingAs($user)->get(route('items.index'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_login_is_blocked_after_five_wrong_passwords(): void
    {
        $user = User::factory()->create(['role' => 'magazynier']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'zle-haslo']);
        }

        // Szósta próba, nawet z dobrym hasłem, jest zablokowana.
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    // ---------- strona publiczna ----------

    /** Na stronie publicznej nie ma szkiców, sprzedanych ani wycofanych ofert, ani niczego, gdy Sprzedaż jest wyłączona. */
    public function test_public_page_never_shows_listings_that_are_not_published(): void
    {
        $item = $this->item(['status' => 'do_sprzedazy']);
        foreach (['szkic' => 'Szkic oferty', 'sprzedana' => 'Sprzedana oferta', 'wycofana' => 'Wycofana oferta'] as $status => $title) {
            $listing = SaleListing::create(['item_id' => $item->id, 'platform' => 'olx', 'title' => $title, 'price' => 1, 'status' => $status]);
            $this->get('/')->assertDontSee($title);
            $this->get(route('marketplace.show', $listing))->assertNotFound();
        }

        $published = SaleListing::create(['item_id' => $item->id, 'platform' => 'olx', 'title' => 'Opublikowana', 'price' => 1, 'status' => 'wyeksportowana', 'exported_at' => now()]);
        AppSetting::current()->fill(['module_sales_enabled' => false])->save();
        $this->get('/')->assertDontSee('Opublikowana');
        $this->get(route('marketplace.show', $published))->assertNotFound();
    }
}
