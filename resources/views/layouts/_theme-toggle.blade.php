{{-- Przycisk przełącznika jasny/ciemny motyw — patrz layouts/_theme-head dla
     samej logiki (localStorage + klasa "dark" na <html>). Zwykły onclick na
     window.toggleTheme(), nie Alpine — ta funkcja musi istnieć od razu przy
     wczytaniu strony (zdefiniowana w <head>, wykonuje się przed jakimkolwiek
     JS-em appki), więc nie ma tu żadnego problemu z kolejnością inicjalizacji,
     ale i tak nie ma powodu dokładać zależności od Alpine tylko po to. --}}
<button type="button" onclick="toggleTheme()"
        title="{{ __('Toggle dark mode') }}"
        class="inline-flex items-center justify-center w-9 h-9 rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200">
    <x-icon name="sun" class="w-5 h-5 hidden dark:block" />
    <x-icon name="moon" class="w-5 h-5 dark:hidden" />
</button>
