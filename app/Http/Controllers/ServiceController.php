<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Repair;

/**
 * Moduł Serwis: jedna strona "co wymaga uwagi" — naprawy w toku, przeglądy
 * po terminie i zbliżające się, kończące się (i niedawno zakończone) gwarancje.
 */
class ServiceController extends Controller
{
    public function index()
    {
        return view('service.index', self::overview());
    }

    /** Wspólne ze skrótem na pulpicie (DashboardController). */
    public static function overview(): array
    {
        return [
            'openRepairs' => Repair::with(['item', 'contact'])->whereNull('returned_at')->orderBy('sent_at')->get(),
            'inspectionsDue' => Item::inStock()
                ->whereNotNull('next_inspection_at')
                ->whereDate('next_inspection_at', '<=', today()->addDays(Item::INSPECTION_WARNING_DAYS))
                ->orderBy('next_inspection_at')
                ->get(),
            'warrantiesEnding' => Item::inStock()
                ->whereNotNull('warranty_until')
                ->whereDate('warranty_until', '>=', today()->subDays(Item::WARRANTY_WARNING_DAYS))
                ->whereDate('warranty_until', '<=', today()->addDays(Item::WARRANTY_WARNING_DAYS))
                ->orderBy('warranty_until')
                ->get(),
        ];
    }
}
