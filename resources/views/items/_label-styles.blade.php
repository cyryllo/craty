{{-- @include('items._label-styles', ['template' => $template, 'showPrice' => $showPrice])
     Style współdzielone między items/label.blade.php (pojedyncza etykieta)
     i items/labels-print.blade.php (druk zbiorczy) — jeden rozmiar/wygląd
     w jednym miejscu, żeby nie rozjechały się przy przyszłej zmianie.

     Trzy szablony do wyboru (życzenie użytkownika, 2026-09-16), patrz
     App\Support\ItemLabelTemplates po pełną definicję wymiarów/pól:
     - 32×20mm: QR + nazwa
     - 35×25mm: sam QR (z ceną: QR mniejszy + linijka ceny pod spodem)
     - 50×30mm: QR + nazwa + numer ewidencyjny
     Każdy z opcjonalną ceną (Item::value). @page ustawia dokładny rozmiar
     strony wydruku (respektowane przez okno drukowania Chrome/Firefoksa
     jako rozmiar "papieru"), więc drukarka etykiet (ciągła rolka albo
     pojedyncze naklejki) dostaje dokładnie taki obszar na etykietę, bez
     marginesów narzucanych przez przeglądarkę. --}}
@php
    $resolved = \App\Support\ItemLabelTemplates::resolve($template);
    $qr = \App\Support\ItemLabelTemplates::qrSize($resolved, $showPrice);
@endphp
<style>
    body { font-family: ui-monospace, monospace; margin: 0; padding: 24px; background: #f4f4f4; }
    .toolbar { margin-bottom: 16px; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }

    @page {
        size: {{ $resolved['width'] }}mm {{ $resolved['height'] }}mm;
        margin: 0;
    }

    .label {
        width: {{ $resolved['width'] }}mm;
        height: {{ $resolved['height'] }}mm;
        box-sizing: border-box;
        padding: 0.5mm;
        background: #fff;
        border: 1px dashed #999;
        border-radius: 1mm;
        display: flex;
        /* flex-start, nie center — wyśrodkowanie grupy (QR + tekst) w pionie
           powoduje, że gdy tekst jest wyższy niż dostępne miejsce, nadmiar
           jest ucinany PO RÓWNO z góry i z dołu naraz, co przy niewielkich
           różnicach wygląda jak nachodzące na siebie linijki, nie jak
           czyste obcięcie na dole. Wyrównanie do góry usuwa tę
           dwuznaczność całkowicie. */
        align-items: flex-start;
        gap: 1mm;
        overflow: hidden;
        /* Widoczny odstęp na ekranie między kolejnymi podglądami etykiet —
           w druku zastępowany przez page-break, patrz niżej. */
        margin-bottom: 4mm;
    }
    .label img { width: {{ $qr }}mm; height: {{ $qr }}mm; flex-shrink: 0; display: block; }

    /* Szablon "sam QR" (35×25) — bez kolumny tekstu, QR wyśrodkowany;
       jeśli cena włączona, układ pionowy (QR nad ceną) zamiast rzędu. */
    .label--qr-only {
        justify-content: center;
        align-items: center;
        flex-direction: column;
    }
    .label--qr-only .price { margin-top: 0.5mm; }

    .label .text {
        /* Zwykły blokowy przepływ, celowo BEZ display:flex tutaj — .no,
           .name i .price to zwykłe elementy blokowe układane jeden pod
           drugim przez normalny "block flow", więc fizycznie nie mają jak
           się nakładać. Jawna wysokość + overflow:hidden ucina WYŁĄCZNIE
           od dołu, gdy tekstu jest za dużo. box-sizing:border-box, żeby
           padding-top (odsunięcie tekstu od górnej krawędzi, zgłoszone
           przez użytkownika) mieścił się W tej samej wysokości zamiast
           dokładać się do niej i ryzykować obcięcie ostatniej linijki. */
        box-sizing: border-box; padding-top: 1mm;
        flex: 1 1 auto; min-width: 0; height: {{ $qr }}mm; overflow: hidden;
    }
    .label .no {
        display: block; font-weight: 700; line-height: 1.3; word-break: break-all;
        margin: 0 0 0.4mm;
        font-size: {{ $resolved['font_no'] ?? 4 }}pt;
    }
    .label .name {
        display: block; line-height: 1.3; margin: 0 0 0.4mm;
        font-size: {{ $resolved['font_name'] ?? 4 }}pt;
    }
    .label .price {
        display: block; font-weight: 700; line-height: 1.3; margin: 0;
        font-size: {{ $resolved['font_price'] ?? 4 }}pt;
    }

    @media print {
        body { background: #fff; padding: 0; margin: 0; }
        .toolbar { display: none; }
        .label {
            /* Na naklejce nie drukujemy ramki podglądu ani odstępu — każda
               etykieta to osobna "strona" dokładnie w rozmiarze szablonu
               (@page wyżej). */
            border: none;
            margin-bottom: 0;
            page-break-after: always;
            break-after: page;
        }
        .label:last-child {
            page-break-after: auto;
            break-after: auto;
        }
    }
</style>
