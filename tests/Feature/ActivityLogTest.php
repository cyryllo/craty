<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AppSetting;
use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Dziennik aktywności: bezpieczeństwo i administracja zawsze, zmiany danych po włączeniu. */
class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin', 'name' => 'Ewa', 'email' => 'ewa@example.com']);
        ActivityLog::query()->delete();
    }

    private function events(): array
    {
        return ActivityLog::orderBy('id')->pluck('event')->all();
    }

    public function test_sign_in_failed_sign_in_and_sign_out_are_recorded_with_ip(): void
    {
        $this->post(route('login'), ['email' => 'ewa@example.com', 'password' => 'zle-haslo']);
        $this->post(route('login'), ['email' => 'ewa@example.com', 'password' => 'password']);
        $this->post(route('logout'));

        $this->assertSame(['auth.failed', 'auth.login', 'auth.logout'], $this->events());
        $failed = ActivityLog::where('event', 'auth.failed')->firstOrFail();
        $this->assertNull($failed->user_id);
        $this->assertSame('ewa@example.com', $failed->user_name);
        $this->assertSame('127.0.0.1', $failed->ip);
        $this->assertSame($this->admin->id, ActivityLog::where('event', 'auth.login')->value('user_id'));
    }

    public function test_account_changes_are_recorded_without_password_hashes(): void
    {
        $this->actingAs($this->admin)->post(route('users.store'), [
            'name' => 'Tomek', 'email' => 'tomek@example.com', 'role' => 'magazynier',
            'password' => 'Haslo#2026x', 'password_confirmation' => 'Haslo#2026x',
        ])->assertSessionHasNoErrors();
        $tomek = User::where('email', 'tomek@example.com')->firstOrFail();

        $this->actingAs($this->admin)->put(route('users.update', $tomek), [
            'name' => 'Tomek', 'email' => 'tomek@example.com', 'role' => 'admin', 'active' => '0',
            'password' => 'Nowe#Haslo2026', 'password_confirmation' => 'Nowe#Haslo2026',
        ])->assertSessionHasNoErrors();

        $entry = ActivityLog::where('event', 'user.updated')->firstOrFail();
        $this->assertSame('Ewa', $entry->user_name);
        $this->assertSame(['magazynier', 'admin'], $entry->properties['changes']['role']);
        $this->assertArrayHasKey('password', $entry->properties['changes']);
        $this->assertNull($entry->properties['changes']['password']);
        $this->assertStringNotContainsString('$2y$', json_encode($entry->properties));
        $this->assertStringContainsString('Administrator', $entry->details());

        $this->actingAs($this->admin)->delete(route('users.destroy', $tomek));
        $this->assertSame(['user.created', 'user.updated', 'user.deleted'], $this->events());
    }

    public function test_settings_changes_are_recorded_and_secrets_masked(): void
    {
        AppSetting::current()->fill(['module_service_enabled' => true, 'mail_password' => 'tajne-haslo'])->save();

        $entry = ActivityLog::where('event', 'settings.updated')->latest('id')->firstOrFail();
        $this->assertStringNotContainsString('tajne-haslo', json_encode($entry->properties));
        $this->assertArrayHasKey('mail_password', $entry->properties['changes']);
        $this->assertStringContainsString(__('Service'), $entry->details());
    }

    public function test_data_changes_are_recorded_only_when_switched_on(): void
    {
        $category = Category::create(['name' => 'Narzędzia', 'code' => 'NAR']);
        $this->assertSame(0, ActivityLog::where('category', 'data')->count());

        $this->actingAs($this->admin)->post(route('settings.activity.update'), ['activity_log_all_enabled' => '1']);
        $this->actingAs($this->admin);
        $item = Item::create(['inventory_no' => 'A-1', 'name' => 'Wiertarka', 'category_id' => $category->id, 'condition' => 'uzywany', 'status' => 'dostepny']);
        $item->update(['status' => 'w_naprawie']);
        $item->delete();

        $data = ActivityLog::where('category', 'data')->orderBy('id')->get();
        $this->assertSame(['data.created', 'data.updated', 'data.deleted'], $data->pluck('event')->all());
        $this->assertSame('Wiertarka', $data[1]->subject_label);
        $this->assertSame(['dostepny', 'w_naprawie'], $data[1]->properties['changes']['status']);
        $this->assertSame('Ewa', $data[1]->user_name);
    }

    public function test_old_entries_are_pruned_after_a_year(): void
    {
        ActivityLog::record('auth.login', null, [], $this->admin);
        $old = ActivityLog::record('auth.login', null, [], $this->admin);
        $old->forceFill(['created_at' => now()->subDays(ActivityLog::DEFAULT_RETENTION_DAYS + 1)])->save();

        $this->artisan('activity:prune')->assertSuccessful();

        $this->assertSame(1, ActivityLog::count());
    }

    public function test_retention_period_can_be_changed_or_switched_off(): void
    {
        $entry = fn (int $daysAgo) => tap(ActivityLog::record('auth.login', null, [], $this->admin))
            ->forceFill(['created_at' => now()->subDays($daysAgo)])->save();
        $entry(10);
        $entry(100);
        $entry(400);

        // Krótszy okres działa od razu po zapisie ustawień.
        $this->actingAs($this->admin)->post(route('settings.activity.update'), ['activity_log_retention_days' => 90])->assertSessionHasNoErrors();
        $this->assertSame(90, ActivityLog::retentionDays());
        $this->assertSame(1, ActivityLog::where('event', 'auth.login')->count());

        $this->actingAs($this->admin)->post(route('settings.activity.update'), ['activity_log_retention_days' => 0])->assertSessionHasNoErrors();
        $entry(5000);
        $this->artisan('activity:prune')->assertSuccessful();
        $this->assertSame(2, ActivityLog::where('event', 'auth.login')->count());

        $this->actingAs($this->admin)->post(route('settings.activity.update'), ['activity_log_retention_days' => 7])->assertSessionHasErrors('activity_log_retention_days');
        $this->actingAs($this->admin)->get(route('settings.activity.index'))->assertSee(__('Who did what and when. Entries are kept without a time limit.'));
    }

    public function test_page_is_admin_only_filters_and_escapes(): void
    {
        $evil = '<script>alert(1)</script>';
        ActivityLog::record('auth.login', null, [], User::factory()->create(['name' => $evil]));
        ActivityLog::record('auth.login', null, [], $this->admin);

        $this->actingAs(User::factory()->create(['role' => 'magazynier']))->get(route('settings.activity.index'))->assertForbidden();

        $this->actingAs($this->admin)->get(route('settings.activity.index'))->assertOk()
            ->assertDontSee($evil, false)->assertSee(e($evil), false)->assertSee(__('Signed in'));
        // Filtr po osobie: zostaje jeden z dwóch wpisów (nazwa drugiej osoby jest tylko na liście wyboru).
        $filtered = $this->actingAs($this->admin)->get(route('settings.activity.index', ['user' => $this->admin->id]))->assertOk()->getContent();
        $this->assertSame(1, substr_count($filtered, '>'.__('Signed in').'<'));
        $this->actingAs($this->admin)->get(route('settings.activity.index', ['category' => 'data']))->assertOk()
            ->assertSee(__('Nothing recorded yet.'));
    }
}
