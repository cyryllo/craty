<?php

namespace App\Console\Commands;

use App\Mail\LoanDueSummaryMail;
use App\Models\AppSetting;
use App\Models\Loan;
use App\Models\User;
use App\Services\MailSettingsApplier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Codzienne podsumowanie przeterminowanych wypożyczeń — patrz Ustawienia →
 * Powiadomienia. Wymaga, żeby hosting miał faktycznie skonfigurowany cron
 * wołający `php artisan schedule:run` co minutę (patrz routes/console.php)
 * — appka sama sobie crona nie założy.
 */
class SendLoanDueNotifications extends Command
{
    protected $signature = 'notifications:loan-due';

    protected $description = 'Send a daily overdue-loans summary to active users, if enabled in Settings → Notifications';

    public function handle(MailSettingsApplier $applier): int
    {
        if (! AppSetting::current()->loan_due_notifications_enabled) {
            return self::SUCCESS;
        }

        $loans = Loan::with('item', 'borrower')
            ->whereNull('returned_at')
            ->whereDate('due_at', '<', now())
            ->get();

        if ($loans->isEmpty()) {
            return self::SUCCESS;
        }

        $applier->applyFromDatabase();

        // Podsumowanie dla personelu (admin/magazynier), nie bezpośrednio do
        // pożyczających — część wypożyczeń jest "na samo imię i nazwisko",
        // bez konta/e-maila w appce (patrz LoanDueSummaryMail).
        User::query()->where('active', true)->get()->each(
            fn (User $user) => Mail::to($user)->send(new LoanDueSummaryMail($loans))
        );

        return self::SUCCESS;
    }
}
