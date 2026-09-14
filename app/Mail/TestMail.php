<?php

namespace App\Mail;

use App\Models\AppSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Wysyłana z Ustawienia → Poczta, przyciskiem "wyślij testową wiadomość". */
class TestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __(':name — test email', ['name' => AppSetting::current()->effectiveName()]),
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<p>'.e(__('This is a test email confirming your SMTP settings work correctly.')).'</p>',
        );
    }
}
