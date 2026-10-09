<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\TestCase;

/**
 * Mapa uprawnień całej aplikacji. Przechodzi przez KAŻDĄ zarejestrowaną
 * trasę, więc nowa trasa dodana bez przemyślenia, kto ma do niej dostęp,
 * od razu wywraca ten test:
 * - publiczne (bez logowania) mogą być wyłącznie trasy z listy PUBLIC_ROUTES,
 * - wszystko pod /users i /ustawienia/* (poza samym rozdzielnikiem Ustawień)
 *   wymaga roli admin,
 * - gość dostaje przekierowanie na logowanie, a magazynier 403 tam, gdzie
 *   trzeba być adminem (sprawdzane na żywo dla tras GET bez parametrów).
 */
class RouteAccessTest extends TestCase
{
    use RefreshDatabase;

    /** "METODA uri" tras, które celowo działają bez logowania. */
    private const PUBLIC_ROUTES = [
        'GET /', // pchli targ = strona główna
        'GET offer/{listing}',
        'GET login',
        'POST login',
        'GET forgot-password',
        'POST forgot-password',
        'GET reset-password/{token}',
        'POST reset-password',
        // Instalator: install.guard blokuje go po instalacji, a done/cleanup
        // pilnuje flaga sesji justInstalled (patrz InstallerTest).
        'GET install',
        'POST install',
        'POST install/test-database',
        'GET install/done',
        'POST install/done/cleanup',
        'GET up', // health check Laravela
    ];

    /** @return array<string, Route> */
    private function routes(): array
    {
        $routes = [];
        foreach (RouteFacade::getRoutes() as $route) {
            $method = collect($route->methods())->reject(fn ($m) => $m === 'HEAD')->first();
            $routes[$method.' '.$route->uri()] = $route;
        }

        return $routes;
    }

    private function hasMiddleware(Route $route, string $prefix): bool
    {
        return collect($route->gatherMiddleware())->contains(fn ($m) => is_string($m) && str_starts_with($m, $prefix));
    }

    public function test_only_explicitly_listed_routes_are_public(): void
    {
        $public = collect($this->routes())
            ->reject(fn (Route $route) => $this->hasMiddleware($route, 'auth'))
            ->keys()->sort()->values()->all();

        $expected = collect(self::PUBLIC_ROUTES)->sort()->values()->all();

        $this->assertSame($expected, $public, 'Pojawiła się trasa dostępna bez logowania. Jeśli to celowe, dopisz ją do PUBLIC_ROUTES.');
    }

    public function test_user_management_and_advanced_settings_require_admin(): void
    {
        $missing = collect($this->routes())
            ->filter(fn (Route $route, string $key) => preg_match('#^\w+ (users|ustawienia/)#', $key))
            ->reject(fn (Route $route) => $this->hasMiddleware($route, 'role:admin') && ! $this->hasMiddleware($route, 'role:admin,'))
            ->keys()->all();

        $this->assertSame([], $missing, 'Te trasy powinny być tylko dla admina (middleware role:admin).');
    }

    public function test_every_route_that_changes_data_requires_a_role(): void
    {
        $missing = collect($this->routes())
            ->filter(fn (Route $route, string $key) => ! str_starts_with($key, 'GET ') && $this->hasMiddleware($route, 'auth'))
            // Własne konto (profil, hasło, język, wylogowanie, weryfikacja e-maila,
            // potwierdzenie hasła) i druk etykiet zmienia tylko sesję/własne dane.
            ->reject(fn (Route $route, string $key) => in_array($key, [
                'PATCH profile', 'DELETE profile', 'PUT password', 'POST locale', 'POST logout',
                'POST email/verification-notification', 'POST confirm-password', 'POST items/etykiety',
            ], true))
            ->reject(fn (Route $route) => $this->hasMiddleware($route, 'role:'))
            ->keys()->all();

        $this->assertSame([], $missing, 'Te trasy zmieniają dane, a nie sprawdzają roli użytkownika.');
    }

    public function test_guests_are_sent_to_login_from_every_protected_page(): void
    {
        foreach ($this->routes() as $key => $route) {
            if (! str_starts_with($key, 'GET ') || str_contains($route->uri(), '{') || ! $this->hasMiddleware($route, 'auth')) {
                continue;
            }

            $this->get('/'.ltrim($route->uri(), '/'))->assertRedirect(route('login'));
        }
    }

    public function test_warehouse_worker_gets_403_on_every_admin_page(): void
    {
        $magazynier = User::factory()->create(['role' => 'magazynier']);

        foreach ($this->routes() as $key => $route) {
            if (! str_starts_with($key, 'GET ') || str_contains($route->uri(), '{') || ! $this->hasMiddleware($route, 'role:admin')
                || $this->hasMiddleware($route, 'role:admin,')) {
                continue;
            }

            $this->actingAs($magazynier)->get('/'.$route->uri())->assertForbidden();
        }
    }

    public function test_installer_finish_pages_need_the_just_installed_session_flag(): void
    {
        $this->get(route('install.done'))->assertRedirect();
        $this->post(route('install.cleanup'))->assertRedirect();
    }
}
