<?php

namespace Tests\Feature;

use App\Mail\LoanDueSummaryMail;
use App\Models\AppSetting;
use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendLoanDueNotificationsTest extends TestCase
{
    use RefreshDatabase;

    // Bez tego domyślny "milczący" admin z TestCase::setUp() (patrz CLAUDE.md
    // "Web installer") dostałby też kopię i psuł dokładne liczenie odbiorców.
    protected bool $withoutDefaultInstalledUser = true;

    public function test_no_mail_is_sent_when_the_setting_is_disabled(): void
    {
        Mail::fake();
        $this->createOverdueLoan();

        $this->artisan('notifications:loan-due')->assertExitCode(0);

        Mail::assertNothingSent();
    }

    public function test_no_mail_is_sent_when_there_are_no_overdue_loans(): void
    {
        Mail::fake();
        AppSetting::current()->fill(['loan_due_notifications_enabled' => true])->save();

        $this->artisan('notifications:loan-due')->assertExitCode(0);

        Mail::assertNothingSent();
    }

    public function test_active_users_receive_a_summary_when_enabled_and_a_loan_is_overdue(): void
    {
        Mail::fake();
        AppSetting::current()->fill(['loan_due_notifications_enabled' => true])->save();
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        $magazynier = User::factory()->create(['role' => 'magazynier', 'active' => true]);
        $inactive = User::factory()->create(['role' => 'magazynier', 'active' => false]);
        $this->createOverdueLoan();

        $this->artisan('notifications:loan-due')->assertExitCode(0);

        Mail::assertSent(LoanDueSummaryMail::class, 2);
        Mail::assertSent(LoanDueSummaryMail::class, fn ($mail) => $mail->hasTo($admin->email));
        Mail::assertSent(LoanDueSummaryMail::class, fn ($mail) => $mail->hasTo($magazynier->email));
        Mail::assertNotSent(LoanDueSummaryMail::class, fn ($mail) => $mail->hasTo($inactive->email));
    }

    public function test_mail_lists_the_item_name_and_borrower(): void
    {
        Mail::fake();
        AppSetting::current()->fill(['loan_due_notifications_enabled' => true])->save();
        User::factory()->create(['role' => 'admin', 'active' => true]);
        $this->createOverdueLoan();

        $this->artisan('notifications:loan-due');

        Mail::assertSent(LoanDueSummaryMail::class, function (LoanDueSummaryMail $mail) {
            $rendered = $mail->render();

            return str_contains($rendered, 'Wiertarka') && str_contains($rendered, 'Jan Testowy');
        });
    }

    private function createOverdueLoan(): Loan
    {
        $item = Item::create([
            'inventory_no' => 'NAR-BRAK-2026-00001', 'name' => 'Wiertarka',
            'condition' => 'uzywany', 'status' => 'wypozyczony',
        ]);

        return Loan::create([
            'item_id' => $item->id, 'borrower_name' => 'Jan Testowy',
            'borrowed_at' => now()->subDays(10), 'due_at' => now()->subDay(),
        ]);
    }
}
