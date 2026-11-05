{{-- Protokół wydania / zwrotu (LoanProtocolController) — zwykły HTML pod
    dompdf: bez Tailwinda i Vite, czcionka DejaVu Sans (wbudowana w dompdf,
    ma polskie znaki), tabele zamiast flexboksa (dompdf go nie obsługuje). --}}
@php
    $isReturn = $kind === 'return';
    $date = fn ($value) => $value?->format('d.m.Y');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ $isReturn ? __('Return protocol') : __('Issue protocol') }} {{ $loan->protocolNumber() }}</title>
    <style>
        @page { margin: 18mm 16mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #111; }
        h1 { font-size: 16pt; margin: 0 0 2mm; }
        h2 { font-size: 10.5pt; margin: 7mm 0 2mm; text-transform: uppercase; letter-spacing: .5px; color: #444; }
        .muted { color: #666; }
        table { width: 100%; border-collapse: collapse; }
        .header td { vertical-align: top; }
        .logo { max-height: 14mm; max-width: 50mm; }
        .grid td { border: 1px solid #bbb; padding: 2mm 3mm; vertical-align: top; }
        .grid td.label { width: 32%; background: #f3f3f3; color: #444; }
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
                <div class="muted">{{ __('No.') }} {{ $loan->protocolNumber() }}</div>
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
        <tr><td class="label">{{ __('Name or company') }}</td><td>{{ $loan->borrowerLabel() }}</td></tr>
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

    <h2>{{ __('Item') }}</h2>
    <table class="grid">
        <tr><td class="label">{{ __('Item name') }}</td><td>{{ $item->name }}</td></tr>
        <tr><td class="label">{{ __('Inventory number') }}</td><td>{{ $item->inventory_no }}</td></tr>
        @if ($item->serial_number)
            <tr><td class="label">{{ __('Serial number') }}</td><td>{{ $item->serial_number }}</td></tr>
        @endif
        @if ($item->value)
            <tr><td class="label">{{ __('Value') }}</td><td>{{ number_format((float) $item->value, 2, ',', ' ') }} zł</td></tr>
        @endif
        <tr>
            <td class="label">{{ __('Condition when issued') }}</td>
            <td>{{ \App\Models\Loan::conditionLabelFor($loan->condition_out) ?? '—' }}</td>
        </tr>
        @if ($isReturn)
            <tr>
                <td class="label">{{ __('Condition when returned') }}</td>
                <td @class(['changed' => $loan->condition_in && $loan->condition_out && $loan->condition_in !== $loan->condition_out])>
                    {{ \App\Models\Loan::conditionLabelFor($loan->condition_in) ?? __('not recorded') }}
                    @if ($loan->condition_in && $loan->condition_out && $loan->condition_in !== $loan->condition_out)
                        ({{ __('changed since issue') }})
                    @endif
                </td>
            </tr>
        @endif
    </table>

    <h2>{{ __('Dates') }}</h2>
    <table class="grid">
        <tr><td class="label">{{ __('Issue date') }}</td><td>{{ $date($loan->borrowed_at) }}</td></tr>
        <tr><td class="label">{{ __('Due date') }}</td><td>{{ $date($loan->due_at) ?? __('not set') }}</td></tr>
        @if ($isReturn)
            <tr><td class="label">{{ __('Return date') }}</td><td>{{ $date($loan->returned_at) }}</td></tr>
        @endif
    </table>

    @if ($isReturn)
        <h2>{{ __('Notes on return') }}</h2>
        <div class="notes">{{ $loan->return_notes ?: __('No damage or remarks noted.') }}</div>
    @elseif ($loan->notes)
        <h2>{{ __('Notes') }}</h2>
        <div class="notes">{{ $loan->notes }}</div>
    @endif

    <p style="margin-top: 7mm;">
        @if ($isReturn)
            {{ __('The item described above was returned on the date given, in the condition stated in this protocol.') }}
        @else
            {{ __('The borrower confirms receiving the item described above in the stated condition and undertakes to return it in the same condition by the due date.') }}
        @endif
    </p>

    <table class="signatures">
        <tr>
            <td>
                <div class="line">
                    {{ $isReturn ? __('Received by (staff)') : __('Issued by (staff)') }}
                    @php($staff = $isReturn ? $loan->receiver : $loan->issuer)
                    @if ($staff)<br>{{ $staff->name }}@endif
                </div>
            </td>
            <td>
                <div class="line">
                    {{ $isReturn ? __('Returned by') : __('Received by (borrower)') }}<br>{{ $loan->borrowerLabel() }}
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">{{ $appName }} · {{ __('Generated') }} {{ now()->format('d.m.Y H:i') }}</div>
</body>
</html>
