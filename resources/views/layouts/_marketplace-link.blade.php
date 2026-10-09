{{-- Szybkie przejście na pchli targ (publiczna strona główna) obok przełącznika motywu — tylko gdy działa,
     czyli przy włączonym module Sprzedaż. Ta sama karta, z powrotem do panelu prowadzi przycisk "Panel". --}}
@if (\App\Support\Modules::isEnabled('sales'))
    <a href="{{ route('marketplace.index', [], false) }}" title="{{ __('Flea market') }}" aria-label="{{ __('Flea market') }}"
       class="inline-flex items-center justify-center w-9 h-9 rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200">
        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/>
            <path d="M2.5 3.5h2.6l2.4 11.3a1.6 1.6 0 0 0 1.6 1.2h8.4a1.6 1.6 0 0 0 1.6-1.2l1.6-7.3H6.2"/>
        </svg>
    </a>
@endif
