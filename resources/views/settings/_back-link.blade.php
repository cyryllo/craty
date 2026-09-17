{{-- Szybki powrót do rozdzielnika Ustawień — każda karta z settings/index.blade.php
     prowadzi na osobną stronę bez własnej ścieżki powrotu poza tym linkiem. --}}
<a href="{{ route('settings.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 hover:underline dark:text-gray-400 dark:hover:text-gray-200">
    &larr; {{ __('Settings') }}
</a>
