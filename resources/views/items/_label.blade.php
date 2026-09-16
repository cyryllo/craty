{{-- @include('items._label', ['item' => $item, 'template' => $template, 'showPrice' => $showPrice])
     $template to KLUCZ (np. "32x20"), nie rozwiązana tablica — rozwiązujemy
     go tutaj, żeby wołający (label.blade.php / labels-print.blade.php) nie
     musiał znać wewnętrznego kształtu App\Support\ItemLabelTemplates. --}}
@php
    $resolved = \App\Support\ItemLabelTemplates::resolve($template);
    $fields = $resolved['fields'];
    $price = $showPrice && $item->value !== null
        ? number_format((float) $item->value, 2, ',', ' ').' zł'
        : null;
    $qrOnly = $fields === ['qr'];
@endphp
<div class="label @if ($qrOnly) label--qr-only @endif">
    @if ($item->qr_path)
        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($item->qr_path) }}" alt="QR">
    @endif
    @if ($qrOnly)
        @if ($price)
            <span class="price">{{ $price }}</span>
        @endif
    @else
        <div class="text">
            @if (in_array('no', $fields, true))
                <span class="no">{{ $item->inventory_no }}</span>
            @endif
            @if (in_array('name', $fields, true))
                <span class="name">{{ $item->name }}</span>
            @endif
            @if ($price)
                <span class="price">{{ $price }}</span>
            @endif
        </div>
    @endif
</div>
