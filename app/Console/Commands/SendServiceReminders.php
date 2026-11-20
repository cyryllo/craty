<?php

namespace App\Console\Commands;

use App\Http\Controllers\ServiceController;
use App\Mail\ServiceReminderMail;
use App\Models\AppSetting;
use App\Models\Repair;
use App\Models\User;
use App\Services\MailSettingsApplier;
use App\Support\Modules;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Cotygodniowe przypomnienie z modułu Serwis (poniedziałek 8:00, patrz
 * routes/console.php): przeglądy po terminie i w ciągu 14 dni, gwarancje
 * kończące się w ciągu 30 dni, naprawy po planowanym powrocie. Te same
 * listy co strona Serwis; do wszystkich aktywnych kont, jak podsumowanie
 * wypożyczeń, i tylko wtedy, gdy jest cokolwiek do zrobienia. Raz w
 * tygodniu, nie codziennie (decyzja właściciela): te same pozycje
 * wisiałyby w skrzynce dzień po dniu.
 */
class SendServiceReminders extends Command
{
    protected $signature = 'notifications:service-due';

    protected $description = 'Send the weekly service reminder (inspections, warranties, overdue repairs) to active users, if enabled';

    public function handle(MailSettingsApplier $applier): int
    {
        if (! Modules::isEnabled('service') || ! AppSetting::current()->service_reminders_enabled) {
            return self::SUCCESS;
        }

        $overview = ServiceController::overview();
        $lists = [
            'inspections' => $overview['inspectionsDue'],
            // Przypominamy o gwarancjach jeszcze trwających, nie o tych, które już minęły.
            'warranties' => $overview['warrantiesEnding']->filter(fn ($item) => $item->warrantyState() !== 'expired')->values(),
            'repairs' => $overview['openRepairs']->filter(fn (Repair $repair) => $repair->isOverdue())->values(),
        ];

        if (collect($lists)->every->isEmpty()) {
            return self::SUCCESS;
        }

        $applier->applyFromDatabase();

        User::query()->where('active', true)->get()->each(
            fn (User $user) => Mail::to($user)->send(new ServiceReminderMail(...$lists))
        );

        return self::SUCCESS;
    }
}
