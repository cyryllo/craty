<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Loan;

class DashboardController extends Controller
{
    public function index()
    {
        $statusCounts = Item::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('dashboard', [
            // Liczba i wartość tylko tego, co jest w magazynie — sprzedane nie.
            'totalItems' => Item::inStock()->count(),
            'totalValue' => Item::inStock()->sum('value'),
            'statusCounts' => $statusCounts,
            'overdueLoans' => Loan::with('item', 'borrower')
                ->whereNull('returned_at')
                ->whereDate('due_at', '<', now())
                ->get(),
            // Nazwa jedyne, co pokazuje widok (patrz dashboard.blade.php) — bez
            // eager-loadów category/storageLocation, których już nie wyświetla.
            'recentItems' => Item::inStock()->latest()->take(8)->get(),
            'needsCompletionItems' => Item::inStock()->where('needs_completion', true)->latest()->take(8)->get(),
        ]);
    }
}
