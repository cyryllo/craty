<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('Labels') }} — {{ count($items) }}</title>
    @include('items._label-styles', ['template' => $template, 'showPrice' => $showPrice])
</head>
<body>
    {{-- POST, nie GET — resubmituje ten sam zestaw ID (ukryte pola niżej)
         za każdym razem, gdy zmieni się szablon/cena, żeby dało się
         wyklikać ustawienia bez wracania do /items i zaznaczania od nowa. --}}
    <form method="POST" action="{{ route('items.labels.print') }}" class="toolbar">
        @csrf
        @foreach ($items as $item)
            <input type="hidden" name="items[]" value="{{ $item->id }}">
        @endforeach
        @include('items._label-options', ['template' => $template, 'showPrice' => $showPrice])
        <button type="button" onclick="window.print()">{{ __('Print labels') }}</button>
    </form>
    @foreach ($items as $item)
        @include('items._label', ['item' => $item, 'template' => $template, 'showPrice' => $showPrice])
    @endforeach
</body>
</html>
