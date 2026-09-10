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
            'totalItems' => Item::count(),
            'totalValue' => Item::sum('value'),
            'statusCounts' => $statusCounts,
            'overdueLoans' => Loan::with('item', 'borrower')
                ->whereNull('returned_at')
                ->whereDate('due_at', '<', now())
                ->get(),
            'recentItems' => Item::with('category', 'storageLocation.warehouse')->latest()->take(8)->get(),
        ]);
    }
}
