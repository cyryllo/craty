<?php

namespace Tests\Feature;

use App\Mail\ServiceReminderMail;
use App\Models\AppSetting;
use App\Models\Item;
use App\Models\Repair;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Cotygodniowe przypomnienie z modułu Serwis (notifications:service-due). */
class ServiceReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        AppSetting::current()->fill(['module_service_enabled' => true, 'service_reminders_enabled' => true])->save();
    }

    private function item(array $attributes): Item
    {
        static $no = 0;

        return Item::create($attributes + ['inventory_no' => 'X-'.++$no, 'condition' => 'uzywany', 'status' => 'dostepny']);
    }

    public function test_sends_the_reminder_to_active_users_with_whats_due(): void
    {
        $this->item(['name' => 'Gaśnica', 'next_inspection_at' => today()->subDay()]);
        $this->item(['name' => 'Laptop', 'warranty_until' => today()->addDays(10)]);
        $this->item(['name' => 'Stara drukarka', 'warranty_until' => today()->subDays(5)]);
        $this->item(['name' => 'Daleko', 'next_inspection_at' => today()->addMonths(3)]);
        $repaired = $this->item(['name' => 'Wiertarka', 'status' => 'w_naprawie']);
        Repair::create(['item_id' => $repaired->id, 'fault_description' => 'X', 'sent_at' => today()->subWeeks(3), 'expected_at' => today()->subWeek()]);
        $active = User::factory()->create(['role' => 'magazynier']);
        $inactive = User::factory()->create(['role' => 'magazynier', 'active' => false]);

        $this->artisan('notifications:service-due')->assertSuccessful();

        Mail::assertSent(ServiceReminderMail::class, fn ($mail) => $mail->hasTo($active->email));
        Mail::assertNotSent(ServiceReminderMail::class, fn ($mail) => $mail->hasTo($inactive->email));

        $html = (new ServiceReminderMail(...$this->lastMailLists()))->render();
        foreach (['Gaśnica', 'Laptop', 'Wiertarka'] as $name) {
            $this->assertStringContainsString($name, $html);
        }
        $this->assertStringNotContainsString('Stara drukarka', $html, 'Gwarancja, która już minęła, nie jest przypomnieniem.');
        $this->assertStringNotContainsString('Daleko', $html);
    }

    public function test_nothing_is_sent_when_nothing_is_due_or_the_option_or_module_is_off(): void
    {
        $this->artisan('notifications:service-due');
        Mail::assertNothingSent();

        $this->item(['name' => 'Gaśnica', 'next_inspection_at' => today()]);

        AppSetting::current()->fill(['service_reminders_enabled' => false])->save();
        $this->artisan('notifications:service-due');
        AppSetting::current()->fill(['service_reminders_enabled' => true, 'module_service_enabled' => false])->save();
        $this->artisan('notifications:service-due');

        Mail::assertNothingSent();
    }

    public function test_runs_weekly_on_monday_morning(): void
    {
        $event = collect(app(Schedule::class)->events())->first(fn ($e) => str_contains($e->command, 'notifications:service-due'));

        $this->assertNotNull($event);
        $this->assertSame('0 8 * * 1', $event->expression);
    }

    public function test_settings_option_appears_only_with_the_module_and_survives_saving_without_it(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('settings.notifications.edit'))->assertSee('name="service_reminders_enabled"', false);

        AppSetting::current()->fill(['module_service_enabled' => false])->save();
        $this->actingAs($admin)->get(route('settings.notifications.edit'))->assertDontSee('name="service_reminders_enabled"', false);
        $this->actingAs($admin)->post(route('settings.notifications.update'), ['password_reset_enabled' => 1]);

        $this->assertTrue(AppSetting::current()->service_reminders_enabled);
    }

    /** Te same listy, które komenda wysłała, do sprawdzenia treści maila. */
    private function lastMailLists(): array
    {
        $mail = Mail::sent(ServiceReminderMail::class)->first();

        return ['inspections' => $mail->inspections, 'warranties' => $mail->warranties, 'repairs' => $mail->repairs];
    }
}
