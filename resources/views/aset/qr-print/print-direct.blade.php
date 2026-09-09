<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Label QR Code Aset AMANA</title>
    <style>
        @page {
            @if($template['paper_size'] === 'thermal_50x65')
                size: 50mm 65mm portrait;
                margin: 2mm;
            @elseif($template['paper_size'] === 'custom_103')
                size: 205mm 165mm portrait;
                margin: 5mm 6mm;
            @else
                size: a4 portrait;
                margin: 8mm 6mm;
            @endif
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            padding: 0;
            background: #ffffff;
            color: #0f172a;
        }

        .no-print {
            background: #0f172a;
            color: #ffffff;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 13px;
        }

        .no-print button {
            background: #059669;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
        }

        .no-print button:hover {
            background: #10b981;
        }

        @media print {
            .no-print {
                display: none !important;
            }
        }

        .sheet {
            page-break-after: always;
            padding: 6mm;
        }

        .sheet:last-child {
            page-break-after: avoid;
        }

        .grid-container {
            display: grid;
            @if($template['key'] === 'thermal_single')
                grid-template-columns: 1fr;
                gap: 0;
            @elseif($template['key'] === 'a4_grid_12')
                grid-template-columns: repeat(3, 1fr);
                gap: 3mm;
            @else
                grid-template-columns: repeat(4, 1fr);
                gap: 2.5mm;
            @endif
        }

        .label-card {
            border: 1px dashed #94a3b8;
            border-radius: 6px;
            padding: 2mm 1.5mm;
            background: #ffffff;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            overflow: hidden;
            height: {{ $template['label_height'] }};
        }

        .label-header {
            width: 100%;
            font-size: 7.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #0f172a;
            border-bottom: 0.5px solid #e2e8f0;
            padding-bottom: 1.5px;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .qr-wrapper {
            margin: 1.5px auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .qr-wrapper img {
            @if($template['key'] === 'a4_grid_12')
                width: 30mm;
                height: 30mm;
            @elseif($template['key'] === 'a4_grid_24')
                width: 20mm;
                height: 20mm;
            @else
                width: 24mm;
                height: 24mm;
            @endif
        }

        .footer-info {
            width: 100%;
            border-top: 0.5px solid #e2e8f0;
            padding-top: 1.5px;
            margin-top: 2px;
        }

        .row1-text {
            font-size: 9px;
            font-weight: bold;
            color: #0f172a;
            line-height: 1.1;
            margin-bottom: 1px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .row2-text {
            font-family: monospace;
            font-size: 8.5px;
            font-weight: bold;
            color: #047857;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Thermal Layout */
        .thermal-card {
            text-align: center;
            padding: 4mm 3mm;
            border: 1px dashed #94a3b8;
            border-radius: 8px;
        }

        .thermal-title {
            font-size: 9px;
            font-weight: 900;
            text-transform: uppercase;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 2px;
            margin-bottom: 3mm;
        }

        .thermal-qr {
            width: 32mm;
            height: 32mm;
            margin: 0 auto 3mm auto;
        }

        .thermal-qr img {
            width: 100%;
            height: 100%;
        }
    </style>
</head>
<body>

    <!-- Non-print action toolbar -->
    <div class="no-print">
        <div>
            <strong>Dialog Cetak Otomatis</strong> — Jika dialog cetak tidak muncul, klik tombol di sebelah kanan.
        </div>
        <div style="display: flex; gap: 8px;">
            <button onclick="window.print()">Cetak Sekarang</button>
            <button onclick="window.close()" style="background: #475569;">Tutup Tab</button>
        </div>
    </div>

    @foreach($pages as $pageLabels)
        <div class="sheet">
            <div class="grid-container">
                @foreach($pageLabels as $lbl)
                    @if($template['key'] === 'thermal_single')
                        <div class="thermal-card">
                            <div class="thermal-title">{{ $lbl['title'] }}</div>
                            <div class="thermal-qr">
                                <img src="data:image/svg+xml;base64,{{ $lbl['qr_base64'] }}" alt="QR Code">
                            </div>
                            <div class="footer-info">
                                <div class="row1-text">{{ $lbl['row1_text'] }}</div>
                                <div class="row2-text">{{ $lbl['row2_text'] }}</div>
                            </div>
                        </div>
                    @else
                        <div class="label-card">
                            <div class="label-header">{{ $lbl['title'] }}</div>
                            <div class="qr-wrapper">
                                <img src="data:image/svg+xml;base64,{{ $lbl['qr_base64'] }}" alt="QR Code">
                            </div>
                            <div class="footer-info">
                                <div class="row1-text" title="{{ $lbl['row1_text'] }}">{{ $lbl['row1_text'] }}</div>
                                <div class="row2-text">{{ $lbl['row2_text'] }}</div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endforeach

    <script>
        window.addEventListener('load', () => {
            setTimeout(() => {
                window.print();
            }, 300);
        });
    </script>
</body>
</html>

