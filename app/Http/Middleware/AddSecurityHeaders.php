<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Nagłówki bezpieczeństwa wysyłane przez samą aplikację, nie przez
 * .htaccess — na realnym hostingu (nginx przed Apache) nagłówki dodane
 * dyrektywą `Header` w .htaccess po cichu znikały po drodze, a te wysłane
 * przez PHP przechodzą. Dotyczy stron generowanych przez Laravela; statyczne
 * pliki (zdjęcia, CSS) obsługuje serwer bez udziału PHP.
 */
class AddSecurityHeaders
{
    public const HEADERS = [
        // Przeglądarka nie zgaduje typu pliku (np. nie uruchomi pliku jako skryptu).
        'X-Content-Type-Options' => 'nosniff',
        // Strony nie da się osadzić w ramce na obcej witrynie (clickjacking).
        'X-Frame-Options' => 'SAMEORIGIN',
        // Pełny adres strony nie wycieka do obcych serwisów (np. po kliknięciu linku do OLX).
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        // Aparat tylko dla tej strony (skanowanie kodów), mikrofon i lokalizacja nigdy.
        'Permissions-Policy' => 'camera=(self), microphone=(), geolocation=()',
        // Okno otwarte z obcej strony nie dostaje dostępu do okna aplikacji (i odwrotnie).
        'Cross-Origin-Opener-Policy' => 'same-origin',
    ];

    /**
     * Przeglądarka, która raz zobaczy ten nagłówek, przez rok łączy się z tą
     * domeną wyłącznie po HTTPS (nawet po wpisaniu http://). Bez
     * includeSubDomains: nie wiemy, co jeszcze stoi na subdomenach właściciela.
     */
    public const HSTS = 'max-age=31536000';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        foreach (self::HEADERS as $name => $value) {
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        if ($this->servedOverHttps($request) && ! $response->headers->has('Strict-Transport-Security')) {
            $response->headers->set('Strict-Transport-Security', self::HSTS);
        }

        return $response;
    }

    /**
     * Na hostingu HTTPS często kończy się na serwerze pośredniczącym (nginx
     * przed Apache), więc samo isSecure() widzi zwykłe HTTP. Stąd też
     * X-Forwarded-Proto i adres aplikacji z instalatora. Fałszywy nagłówek
     * niczego nie psuje: przeglądarki ignorują HSTS otrzymany po zwykłym HTTP.
     */
    private function servedOverHttps(Request $request): bool
    {
        return $request->isSecure()
            || strtolower((string) $request->headers->get('X-Forwarded-Proto')) === 'https'
            || str_starts_with((string) config('app.url'), 'https://');
    }
}
