<?php

namespace App\Mail;

use App\Models\AppSetting;
use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * Dzienne podsumowanie przeterminowanych wypożyczeń — wysyłane do aktywnych
 * użytkowników appki (admin/magazynier), nie bezpośrednio do pożyczających.
 * Część wypożyczeń jest "na samo imię i nazwisko" (Loan::borrowed_by puste),
 * bez konta/e-maila w appce, więc nie ma dokąd wysłać bezpośrednio — patrz
 * App\Console\Commands\SendLoanDueNotifications.
 */
class LoanDueSummaryMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param  Collection<int, Loan>  $loans */
    public function __construct(public Collection $loans)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __(':name — overdue loans (:count)', [
                'name' => AppSetting::current()->effectiveName(),
                'count' => $this->loans->count(),
            ]),
        );
    }

    public function content(): Content
    {
        $rows = $this->loans->map(fn (Loan $loan) => '<li>'
            .e($loan->item->name).' — '.e($loan->borrowerLabel())
            .' — '.e(__('due')).' '.$loan->due_at->format('d.m.Y')
            .'</li>')->implode('');

        return new Content(
            htmlString: '<p>'.e(__('The following loans are overdue:')).'</p><ul>'.$rows.'</ul>',
        );
    }
}
