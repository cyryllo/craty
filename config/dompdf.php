<?php

/*
 * Nadpisuje tylko to, czego potrzebuje Craty — reszta to domyślna
 * konfiguracja pakietu barryvdh/laravel-dompdf (mergeConfigFrom łączy oba).
 * Appka nie ma katalogu public/ (patrz CLAUDE.md "Project layout"), a pakiet
 * domyślnie szuka właśnie base_path('public') i bez tego rzuca "Cannot
 * resolve public path" przy pierwszym PDF-ie (protokoły wypożyczeń).
 */
return [
    'public_path' => base_path(),
];
