<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <title>Etykieta — {{ $item->inventory_no }}</title>
    <style>
        body { font-family: ui-monospace, monospace; margin: 0; padding: 24px; background: #f4f4f4; }
        .label {
            width: 70mm; padding: 6mm; background: #fff; border: 1px dashed #999; border-radius: 4px;
            display: flex; gap: 4mm; align-items: center;
        }
        .label img { width: 26mm; height: 26mm; }
        .label .text { font-size: 10pt; line-height: 1.4; }
        .label .no { font-size: 12pt; font-weight: 700; display: block; }
        .toolbar { margin-bottom: 16px; }
        @media print {
            body { background: #fff; padding: 0; }
            .toolbar { display: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button onclick="window.print()">Drukuj etykietę</button>
    </div>
    <div class="label">
        @if ($item->qr_path)
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($item->qr_path) }}" alt="QR">
        @endif
        <div class="text">
            <span class="no">{{ $item->inventory_no }}</span>
            {{ $item->name }}
        </div>
    </div>
</body>
</html>
