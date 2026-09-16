{{-- Stopka współdzielona przez wszystkie niezależne "powłoki" HTML appki
     (layouts/app, layouts/guest, marketplace, kreator instalacji) — nie ma
     tu jednego wspólnego layoutu bazowego, więc ten partial jest @include'owany
     osobno w każdym z nich, zawsze tuż przed zamknięciem <body>/głównej treści.
     Celowo pokazuje nazwę PROJEKTU ("Craty"), nie AppSetting::effectiveName()
     (nazwa appki wybrana przez admina w Ustawieniach) — to stopka w stylu
     "Powered by", ma zostać "Craty" nawet gdy ktoś nazwie swoją instalację
     inaczej, tak samo jak dotychczasowe "Craty v{wersja}" w Ustawieniach. --}}
<footer class="py-6 text-center text-xs text-gray-400 dark:text-gray-500">
    Craty v{{ app(\App\Support\AppVersion::class)->current() }}
    &middot;
    <a href="https://github.com/cyryllo/craty" target="_blank" rel="noopener" class="hover:underline hover:text-gray-500 dark:hover:text-gray-400">GitHub</a>
</footer>
