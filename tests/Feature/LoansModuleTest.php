<?php

namespace Tests\Feature;

use App\Mail\LoanDueSummaryMail;
use App\Models\AppSetting;
use App\Models\Contact;
use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Moduł Wypożyczenia: domyślnie włączony, po wyłączeniu znika z każdego miejsca, dane zostają. */
class LoansModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Item $item;

    private Loan $loan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'admin']);
        $this->item = Item::create(['inventory_no' => 'L-1', 'name' => 'Wiertarka', 'condition' => 'uzywany', 'status' => 'dostepny']);
        $contact = Contact::create(['name' => 'Jan Nowak']);
        $this->loan = Loan::lend($this->item, $contact, 'Jan Nowak', $this->user, ['due_at' => today()->subDay()]);
    }

    private function switchOff(): void
    {
        AppSetting::current()->fill(['module_loans_enabled' => false])->save();
    }

    public function test_module_is_on_by_default(): void
    {
        $this->assertTrue(\App\Support\Modules::isEnabled('loans'));
        $this->actingAs($this->user)->get(route('loans.index'))->assertOk();
        $this->actingAs($this->user)->get(route('dashboard'))->assertSee(__('Overdue loans'));
    }

    public function test_switched_off_every_loan_route_is_404(): void
    {
        $this->switchOff();
        $other = Item::create(['inventory_no' => 'L-2', 'name' => 'Poziomica', 'condition' => 'uzywany', 'status' => 'dostepny']);

        foreach ([route('loans.index'), route('loan-groups.create'), route('loans.protocol.issue', $this->loan)] as $url) {
            $this->actingAs($this->user)->get($url)->assertNotFound();
        }
        $this->actingAs($this->user)->post(route('items.loans.store', $other), ['borrower_name' => 'Jan'])->assertNotFound();
        $this->actingAs($this->user)->post(route('loans.return', $this->loan))->assertNotFound();
        $this->actingAs($this->user)->post(route('items.reservations.store', $other), ['starts_on' => today()->toDateString(), 'ends_on' => today()->toDateString()])->assertNotFound();

        $this->assertNull($this->loan->fresh()->returned_at);
        $this->assertSame(1, Loan::count());
    }

    public function test_switched_off_the_ui_hides_loans_everywhere(): void
    {
        $this->switchOff();

        $this->actingAs($this->user)->get(route('dashboard'))->assertOk()
            ->assertDontSee(route('loans.index'))->assertDontSee(__('Overdue loans'))->assertDontSee(__('Loaned out'));
        $this->actingAs($this->user)->get(route('items.show', $this->item))->assertOk()
            ->assertDontSee(route('loans.return', $this->loan))->assertDontSee(__('Issue protocol (PDF)'));
        $this->actingAs($this->user)->get(route('items.index', ['f' => 1]))->assertOk()->assertDontSee(__('Loan out selected'));
        $this->actingAs($this->user)->get(route('scan.batch'))->assertOk()->assertDontSee(__('Loan out as a kit'));
        $contact = Contact::firstOrFail();
        $this->actingAs($this->user)->get(route('contacts.show', $contact))->assertOk()->assertDontSee(__('Loan history'));
        $this->actingAs($this->user)->get(route('settings.notifications.edit'))->assertOk()->assertDontSee('loan_due_notifications_enabled');
    }

    public function test_switched_off_no_overdue_email_and_the_setting_survives_saving(): void
    {
        Mail::fake();
        AppSetting::current()->fill(['loan_due_notifications_enabled' => true])->save();
        $this->switchOff();

        $this->artisan('notifications:loan-due')->assertSuccessful();
        Mail::assertNothingSent();

        $this->actingAs($this->user)->post(route('settings.notifications.update'), ['password_reset_enabled' => '1']);
        $this->assertTrue(AppSetting::current()->fresh()->loan_due_notifications_enabled);

        AppSetting::current()->fill(['module_loans_enabled' => true])->save();
        $this->artisan('notifications:loan-due')->assertSuccessful();
        Mail::assertSent(LoanDueSummaryMail::class);
    }
}
