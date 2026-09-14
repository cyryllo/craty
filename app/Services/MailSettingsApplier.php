<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\App;

/**
 * Nadpisuje konfigurację poczty ustawieniami z bazy (Ustawienia → Poczta),
 * tuż przed wysyłką — nie globalnym middleware'em na każde żądanie HTTP
 * (mail wysyła się rzadko, więc nie ma sensu płacić za to na każdym request).
 *
 * Każde miejsce w kodzie, które faktycznie wysyła maila (dziś: tylko test
 * z Ustawienia → Poczta; w przyszłości: powiadomienia), musi wywołać
 * applyFromDatabase() zanim użyje Mail::/Notification::.
 */
class MailSettingsApplier
{
    /**
     * Nadpisuje config('mail.*') wartościami z AppSetting, jeśli admin je
     * ustawił. Gdy w bazie nic nie ma, appka zostaje na tym, co jest w .env —
     * dzięki temu dev/Docker (MAIL_MAILER=log) nie przestaje działać.
     */
    public function applyFromDatabase(): void
    {
        $setting = AppSetting::current();

        if (! $setting->hasCustomMailSettings()) {
            return;
        }

        $this->applyFromArray([
            'host' => $setting->mail_host,
            'port' => $setting->mail_port,
            'encryption' => $setting->mail_encryption,
            'username' => $setting->mail_username,
            'password' => $setting->mail_password,
            'from_address' => $setting->mail_from_address,
            'from_name' => $setting->mail_from_name,
        ]);
    }

    /**
     * To samo, ale z tablicy (formularz testowej wysyłki — testujemy dokładnie
     * to, co administrator właśnie wpisał, niezależnie od tego, czy to już
     * zapisane w bazie).
     */
    public function applyFromArray(array $data): void
    {
        // Nowoczesny Laravel/Symfony Mailer nie ma już osobnego klucza
        // "encryption" — szyfrowanie ustala się przez "scheme" DSN-a:
        // 'smtps' = niejawne TLS od razu przy połączeniu (zwykle port 465),
        // 'smtp' = zwykłe połączenie z opcjonalnym STARTTLS negocjowanym
        // automatycznie, jeśli serwer je obsługuje (zwykle port 587/25).
        $scheme = $data['encryption'] === 'ssl' ? 'smtps' : 'smtp';

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.scheme' => $scheme,
            'mail.mailers.smtp.host' => $data['host'],
            'mail.mailers.smtp.port' => $data['port'] ?: 587,
            'mail.mailers.smtp.username' => $data['username'] ?: null,
            'mail.mailers.smtp.password' => $data['password'] ?: null,
            'mail.from.address' => $data['from_address'] ?: config('mail.from.address'),
            'mail.from.name' => $data['from_name'] ?: config('mail.from.name'),
        ]);

        // MailManager cache'uje raz zbudowany mailer — bez tego appka
        // wysłałaby maila starą konfiguracją, jeśli coś już go wcześniej użyło.
        App::make('mail.manager')->purge('smtp');
    }
}
