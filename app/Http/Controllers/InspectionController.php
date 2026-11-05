<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;

/**
 * Moduł Serwis: zapis wykonanego przeglądu. Następny termin liczy się z
 * okresu ustawionego przy przedmiocie (bez okresu: przegląd jednorazowy,
 * termin się czyści).
 */
class InspectionController extends Controller
{
    public function store(Request $request, Item $item)
    {
        $data = $request->validate([
            'performed_at' => ['required', 'date', 'before_or_equal:today'],
            'passed' => ['required', 'boolean'],
            // Własna nazwa pola: na karcie przedmiotu obok jest formularz
            // wypożyczenia z polem "notes", a old() nie rozróżnia formularzy.
            'inspection_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $inspection = $item->inspections()->create([
            'performed_at' => $data['performed_at'],
            'passed' => $data['passed'],
            'notes' => $data['inspection_notes'] ?? null,
            'user_id' => $request->user()->id,
        ]);

        $item->update([
            'next_inspection_at' => $item->inspection_interval_months
                ? $inspection->performed_at->copy()->addMonthsNoOverflow($item->inspection_interval_months)
                : null,
        ]);

        $item->histories()->create([
            'user_id' => $request->user()->id,
            'action' => 'inspected',
            'new_value' => $inspection->passed ? __('passed') : __('failed'),
        ]);

        return back()->with('status', __('Inspection recorded.'));
    }
}
