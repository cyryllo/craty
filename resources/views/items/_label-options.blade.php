{{-- @include('items._label-options', ['template' => $template, 'showPrice' => $showPrice])
     Wybór szablonu/ceny osadzony w <form> wołającej strony (GET dla
     pojedynczej etykiety, POST dla druku zbiorczego) — zmiana od razu
     przeładowuje/resubmituje formularz, żeby dało się wyklikać ustawienia
     PRZED kliknięciem samego "Drukuj" (życzenie użytkownika). Bez
     Tailwind (te strony celowo nie ładują @vite, patrz CLAUDE.md) — sam
     układ w .toolbar z items/_label-styles.blade.php. --}}
<label>
    {{ __('Template') }}:
    <select name="template" onchange="this.form.submit()">
        @foreach (\App\Support\ItemLabelTemplates::keys() as $key)
            <option value="{{ $key }}" @selected($template === $key)>{{ \App\Support\ItemLabelTemplates::label($key) }}</option>
        @endforeach
    </select>
</label>
<label>
    <input type="checkbox" name="price" value="1" @checked($showPrice) onchange="this.form.submit()">
    {{ __('Show price') }}
</label>
