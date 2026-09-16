<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('Label') }} — {{ $item->inventory_no }}</title>
    @include('items._label-styles', ['template' => $template, 'showPrice' => $showPrice])
</head>
<body>
    <form method="GET" class="toolbar">
        @include('items._label-options', ['template' => $template, 'showPrice' => $showPrice])
        <button type="button" onclick="window.print()">{{ __('Print label') }}</button>
    </form>
    @include('items._label', ['item' => $item, 'template' => $template, 'showPrice' => $showPrice])
</body>
</html>
