{{-- Grafika "brak zdjęcia" zamiast pustego pola — wypełnia rodzica (musi mieć "relative" i wymiary).
     size: sm (sama ikonka, np. miniatura w liście), md (ikonka + podpis), lg (większa, np. strona oferty). --}}
@props(['size' => 'md'])
@php
    $icon = ['sm' => 'w-4 h-4', 'md' => 'w-10 h-10', 'lg' => 'w-16 h-16'][$size] ?? 'w-10 h-10';
@endphp
<div {{ $attributes->merge(['class' => 'absolute inset-0 flex flex-col items-center justify-center gap-1.5 bg-gradient-to-br from-gray-100 to-gray-200 text-gray-500 dark:from-gray-700 dark:to-gray-800 dark:text-gray-400']) }} aria-hidden="true">
    <svg class="{{ $icon }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 8 12 3 3 8v8l9 5 9-5V8Z"/>
        <path d="m3 8 9 5 9-5"/>
        <path d="M12 13v8"/>
        <path d="m7.5 5.5 9 5"/>
    </svg>
    @if ($size !== 'sm')
        <span class="text-xs font-medium">{{ __('No photo') }}</span>
    @endif
</div>
