{{-- Protokół wydania / zwrotu (LoanProtocolController) — zwykły HTML pod
    dompdf: bez Tailwinda i Vite, czcionka DejaVu Sans (wbudowana w dompdf,
    ma polskie znaki), tabele zamiast flexboksa (dompdf go nie obsługuje).
    Przedmioty zawsze jako tabela: jeden przy zwykłym wypożyczeniu, kilka
    przy zestawie. --}}
@php
    $isReturn = $kind === 'return';
    $date = fn ($value) => $value?->format('d.m.Y');
    $condition = fn ($value) => \App\Models\Loan::conditionLabelFor($value);
    $withNotes = $loans->filter(fn ($loan) => $loan->return_notes);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ $isReturn ? __('Return protocol') : __('Issue protocol') }} {{ $number }}</title>
    <style>
        @page { margin: 18mm 16mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #111; }
        h1 { font-size: 16pt; margin: 0 0 2mm; }
        h2 { font-size: 10.5pt; margin: 7mm 0 2mm; text-transform: uppercase; letter-spacing: .5px; color: #444; }
        .muted { color: #666; }
        .small { font-size: 8.5pt; color: #555; }
        table { width: 100%; border-collapse: collapse; }
        .header td { vertical-align: top; }
        .logo { max-height: 14mm; max-width: 50mm; }
        .grid td, .grid th { border: 1px solid #bbb; padding: 2mm 3mm; vertical-align: top; text-align: left; }
        .grid td.label { width: 32%; background: #f3f3f3; color: #444; }
        .grid th { background: #f3f3f3; color: #444; font-weight: normal; font-size: 9pt; }
        .notes { border: 1px solid #bbb; padding: 3mm; min-height: 14mm; white-space: pre-line; }
        .changed { font-weight: bold; }
        .signatures { margin-top: 22mm; }
        .signatures td { width: 50%; text-align: center; padding: 0 6mm; vertical-align: top; }
        .line { border-top: 1px solid #333; padding-top: 1.5mm; font-size: 9pt; color: #444; }
        .footer { margin-top: 10mm; font-size: 8pt; color: #888; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td>
                <h1>{{ $isReturn ? __('Return protocol') : __('Issue protocol') }}</h1>
                <div class="muted">{{ __('No.') }} {{ $number }}</div>
            </td>
            <td style="text-align: right;">
                @if ($logo)
                    <img src="{{ $logo }}" class="logo" alt=""><br>
                @endif
                <b>{{ $appName }}</b>
            </td>
        </tr>
    </table>

    <h2>{{ __('Borrower') }}</h2>
    <table class="grid">
        <tr><td class="label">{{ __('Name or company') }}</td><td>{{ $borrowerName }}</td></tr>
        @if ($contact?->phone)
            <tr><td class="label">{{ __('Phone') }}</td><td>{{ $contact->phone }}</td></tr>
        @endif
        @if ($contact?->email)
            <tr><td class="label">{{ __('Email') }}</td><td>{{ $contact->email }}</td></tr>
        @endif
        @if ($contact?->address)
            <tr><td class="label">{{ __('Address') }}</td><td style="white-space: pre-line;">{{ $contact->address }}</td></tr>
        @endif
    </table>

    <h2>{{ $loans->count() > 1 ? __('Items (:count)', ['count' => $loans->count()]) : __('Item') }}</h2>
    <table class="grid">
        <tr>
            <th style="width: 6%;">#</th>
            <th>{{ __('Item name') }}</th>
            <th style="width: 14%;">{{ __('Value') }}</th>
            <th style="width: 16%;">{{ __('Condition when issued') }}</th>
            @if ($isReturn)
                <th style="width: 18%;">{{ __('Condition when returned') }}</th>
            @endif
        </tr>
        @foreach ($loans as $loan)
            @php($changed = $loan->condition_in && $loan->condition_out && $loan->condition_in !== $loan->condition_out)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>
                    {{ $loan->item->name }}
                    <div class="small">{{ __('Inventory number') }}: {{ $loan->item->inventory_no }}@if ($loan->item->serial_number) · {{ __('Serial number') }}: {{ $loan->item->serial_number }}@endif</div>
                </td>
                <td>{{ $loan->item->value ? number_format((float) $loan->item->value, 2, ',', ' ').' zł' : '—' }}</td>
                <td>{{ $condition($loan->condition_out) ?? '—' }}</td>
                @if ($isReturn)
                    <td @class(['changed' => $changed])>
                        {{ $condition($loan->condition_in) ?? __('not recorded') }}
                        @if ($changed)<div class="small">({{ __('changed since issue') }})</div>@endif
                    </td>
                @endif
            </tr>
        @endforeach
    </table>

    <h2>{{ __('Dates') }}</h2>
    <table class="grid">
        <tr><td class="label">{{ __('Issue date') }}</td><td>{{ $date($issueDate) }}</td></tr>
        <tr><td class="label">{{ __('Due date') }}</td><td>{{ $date($dueDate) ?? __('not set') }}</td></tr>
        @if ($isReturn)
            <tr><td class="label">{{ __('Return date') }}</td><td>{{ $date($returnDate) }}</td></tr>
        @endif
    </table>

    @if ($isReturn)
        <h2>{{ __('Notes on return') }}</h2>
        <div class="notes">@if ($withNotes->isEmpty()){{ __('No damage or remarks noted.') }}@elseif ($loans->count() === 1){{ $withNotes->first()->return_notes }}@else @foreach ($withNotes as $loan){{ $loan->item->name }}: {{ $loan->return_notes }}
@endforeach @endif</div>
    @elseif ($notes)
        <h2>{{ __('Notes') }}</h2>
        <div class="notes">{{ $notes }}</div>
    @endif

    <p style="margin-top: 7mm;">
        @if ($isReturn && $loans->count() > 1)
            {{ __('The items described above were returned on the date given, in the condition stated in this protocol.') }}
        @elseif ($isReturn)
            {{ __('The item described above was returned on the date given, in the condition stated in this protocol.') }}
        @elseif ($loans->count() > 1)
            {{ __('The borrower confirms receiving the items described above in the stated condition and undertakes to return them in the same condition by the due date.') }}
        @else
            {{ __('The borrower confirms receiving the item described above in the stated condition and undertakes to return it in the same condition by the due date.') }}
        @endif
    </p>

    <table class="signatures">
        <tr>
            <td>
                <div class="line">
                    {{ $isReturn ? __('Received by (staff)') : __('Issued by (staff)') }}
                    @php($staff = $isReturn ? $receiver : $issuer)
                    @if ($staff)<br>{{ $staff->name }}@endif
                </div>
            </td>
            <td>
                <div class="line">
                    {{ $isReturn ? __('Returned by') : __('Received by (borrower)') }}<br>{{ $borrowerName }}
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">{{ $appName }} · {{ __('Generated') }} {{ now()->format('d.m.Y H:i') }}</div>
</body>
</html>
