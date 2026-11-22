<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\MachineBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Moduł "Rezerwacje maszyn": tygodniowy grafik przedmiotów stacjonarnych
 * (wiersze: maszyny, kolumny: dni), zapis rezerwacji i odwołanie. Dostęp
 * mają obsługa i rola Użytkownik (trasy: role + module:bookings). Rezerwacja
 * jest ważna od razu; nakładające się terminy tej samej maszyny są odrzucane.
 */
class MachineBookingController extends Controller
{
    public function index(Request $request)
    {
        try {
            $weekStart = Carbon::parse((string) $request->query('week', today()->toDateString()))->startOfWeek(Carbon::MONDAY)->startOfDay();
        } catch (\Throwable) {
            $weekStart = today()->startOfWeek(Carbon::MONDAY);
        }
        $weekEnd = $weekStart->copy()->addWeek();

        $machines = self::machines();
        $selected = $request->integer('item') ?: null;
        $shown = $selected ? $machines->where('id', $selected)->values() : $machines;

        $bookings = MachineBooking::whereIn('item_id', $shown->pluck('id'))
            ->overlapping($weekStart, $weekEnd)
            ->with('item')
            ->orderBy('starts_at')
            ->get()
            ->groupBy('item_id');

        return view('bookings.index', [
            'weekStart' => $weekStart,
            'days' => collect(range(0, 6))->map(fn ($i) => $weekStart->copy()->addDays($i)),
            'machines' => $machines,
            'shown' => $shown,
            'selected' => $selected,
            'bookings' => $bookings,
            'mine' => MachineBooking::where('user_id', $request->user()->id)->upcoming()->with('item')->orderBy('starts_at')->get(),
            'purposes' => self::purposesFor($request->user()->isMagazynier()),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'item_id' => ['required', 'integer', Rule::exists('items', 'id')->where('stationary', true)->whereNotIn('status', Item::OUT_OF_STOCK_STATUSES)],
            'date' => ['required', 'date_format:Y-m-d'],
            'from' => ['required', 'date_format:H:i'],
            'to' => ['required', 'date_format:H:i'],
            'purpose' => ['required', Rule::in(array_keys(self::purposesFor($request->user()->isMagazynier())))],
            'description' => ['nullable', 'string', 'max:1000'],
        ], ['item_id.exists' => __('Choose a stationary item that can be booked.')]);

        $start = Carbon::createFromFormat('Y-m-d H:i', $data['date'].' '.$data['from']);
        $end = Carbon::createFromFormat('Y-m-d H:i', $data['date'].' '.$data['to']);

        if ($start->minute % MachineBooking::STEP_MINUTES || $end->minute % MachineBooking::STEP_MINUTES) {
            throw ValidationException::withMessages(['from' => __('Times go in :step-minute steps.', ['step' => MachineBooking::STEP_MINUTES])]);
        }
        if ($end->lte($start)) {
            throw ValidationException::withMessages(['to' => __('The end must be after the start.')]);
        }
        // Liczy się koniec, nie początek: trwający slot da się jeszcze zapisać,
        // a przesunięcie strefy czasowej serwera nie zablokuje prawidłowej rezerwacji.
        if ($end->lte(now())) {
            throw ValidationException::withMessages(['date' => __('This time has already passed.')]);
        }

        $booking = DB::transaction(function () use ($data, $start, $end, $request) {
            // Blokada wiersza maszyny: dwie osoby klikające ten sam termin naraz
            // nie przejdą obie przez sprawdzenie kolizji.
            Item::whereKey($data['item_id'])->lockForUpdate()->first();

            $clash = MachineBooking::where('item_id', $data['item_id'])->overlapping($start, $end)->orderBy('starts_at')->first();
            if ($clash) {
                throw ValidationException::withMessages(['from' => __('Already booked :time by :name.', [
                    'time' => $clash->starts_at->format('d.m').' '.$clash->timeRange(), 'name' => $clash->user_name,
                ])]);
            }

            return MachineBooking::create([
                'item_id' => $data['item_id'],
                'user_id' => $request->user()->id,
                'user_name' => $request->user()->name,
                'starts_at' => $start,
                'ends_at' => $end,
                'purpose' => $data['purpose'],
                'description' => $data['description'] ?? null,
            ]);
        });

        $message = __('Booked: :item, :date :time.', [
            'item' => $booking->item->name, 'date' => $booking->starts_at->format('d.m.Y'), 'time' => $booking->timeRange(),
        ]);

        // Z karty przedmiotu wracamy na kartę, z grafiku na tydzień z rezerwacją.
        if ($request->boolean('back')) {
            return back()->with('status', $message);
        }

        return redirect()->route('bookings.index', array_filter(['week' => $data['date'], 'item' => $request->integer('filter') ?: null]))
            ->with('status', $message);
    }

    public function destroy(Request $request, MachineBooking $booking)
    {
        abort_unless($booking->canBeCancelledBy($request->user()), 403);

        $booking->delete();

        return back()->with('status', __('Booking cancelled.'));
    }

    /** Maszyny do rezerwacji: przedmioty stacjonarne, bez sprzedanych i wycofanych. */
    public static function machines()
    {
        return Item::where('stationary', true)->whereNotIn('status', Item::OUT_OF_STOCK_STATUSES)->orderBy('name')->get(['id', 'name', 'status']);
    }

    /** @return array<string, string> */
    public static function purposesFor(bool $staff): array
    {
        return $staff ? MachineBooking::PURPOSES : array_diff_key(MachineBooking::PURPOSES, array_flip(MachineBooking::STAFF_ONLY_PURPOSES));
    }
}
