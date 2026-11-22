{{-- Jedna rezerwacja maszyny w liście (karta przedmiotu, "Moje rezerwacje",
     widok dnia na telefonie). Odwołanie pod opisem, żeby mieściło się też w wąskiej ramce. --}}
<div class="text-sm">
    <p class="text-gray-800 dark:text-gray-200">
        @if ($withDay ?? true){{ $booking->starts_at->translatedFormat('D d.m') }}, @endif<span class="tabular-nums">{{ $booking->timeRange() }}</span>
        · <b>{{ ($showItem ?? false) ? $booking->item->name : $booking->user_name }}</b>
        @if ($showUser ?? false)<span class="text-gray-500 dark:text-gray-400"> · {{ $booking->user_name }}</span>@endif
        @if ($booking->isNow())<span class="ms-1 text-xs font-medium px-2 py-0.5 rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">{{ __('now') }}</span>@endif
    </p>
    <p class="text-xs text-gray-500 mt-0.5 dark:text-gray-400">
        <span class="font-medium px-1.5 py-0.5 rounded {{ \App\Models\MachineBooking::purposeClass($booking->purpose) }}">{{ $booking->purposeLabel() }}</span>
        @if ($booking->description) {{ $booking->description }}@endif
    </p>
    @if ($booking->canBeCancelledBy(auth()->user()))
        <form method="POST" action="{{ route('bookings.destroy', $booking) }}" onsubmit="return confirm(@js(__('Cancel this booking?')));" class="mt-0.5">
            @csrf @method('DELETE')
            <button class="text-xs text-red-600 hover:underline dark:text-red-400">{{ __('Cancel booking') }}</button>
        </form>
    @endif
</div>
