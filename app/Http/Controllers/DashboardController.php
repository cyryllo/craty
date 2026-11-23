<?php

namespace App\Http\Controllers;

use App\Models\Consumable;
use App\Models\Item;
use App\Models\Loan;
use App\Models\MachineBooking;
use App\Models\Reservation;
use App\Support\Modules;

class DashboardController extends Controller
{
    public function index()
    {
        // Rola Użytkownik nie ma Panelu: od razu grafik maszyn (albo informacja,
        // że moduł jest wyłączony, zamiast 404 zaraz po zalogowaniu).
        if (auth()->user()->isMember()) {
            return Modules::isEnabled('bookings') ? redirect()->route('bookings.index') : view('bookings.unavailable');
        }

        $statusCounts = Item::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('dashboard', [
            // Liczba i wartość tylko tego, co jest w magazynie — bez sprzedanych/wycofanych.
            'totalItems' => Item::inStock()->count(),
            'totalValue' => Item::inStock()->sum('value'),
            'statusCounts' => $statusCounts,
            'loansEnabled' => Modules::isEnabled('loans'),
            'overdueLoans' => ! Modules::isEnabled('loans') ? collect() : Loan::with('item', 'borrower')
                ->whereNull('returned_at')
                ->whereDate('due_at', '<', now())
                ->get(),
            // Nazwa jedyne, co pokazuje widok (patrz dashboard.blade.php) — bez
            // eager-loadów category/storageLocation, których już nie wyświetla.
            'recentItems' => Item::inStock()->latest()->take(8)->get(),
            // Moduł Materiały: to, czego jest na minimum albo mniej.
            'lowConsumables' => Modules::isEnabled('consumables') ? Consumable::low()->orderBy('name')->get() : collect(),
            // Rezerwacje do wydania dziś i jutro.
            'upcomingReservations' => ! Modules::isEnabled('loans') ? collect() : Reservation::active()->whereDate('starts_on', '<=', today()->addDay())->with(['item', 'contact'])->orderBy('starts_on')->get(),
            'needsCompletionItems' => Item::inStock()->where('needs_completion', true)->latest()->take(8)->get(),
            // Moduł Serwis: naprawy w toku, przeglądy i gwarancje wymagające uwagi.
            'service' => Modules::isEnabled('service') && auth()->user()->isMagazynier() ? ServiceController::overview() : null,
            // Moduł Rezerwacje maszyn: kto dziś korzysta z maszyn.
            'todayBookings' => Modules::isEnabled('bookings')
                ? MachineBooking::with('item')->overlapping(today()->startOfDay(), today()->endOfDay())->orderBy('starts_at')->get()
                : collect(),
        ]);
    }
}
