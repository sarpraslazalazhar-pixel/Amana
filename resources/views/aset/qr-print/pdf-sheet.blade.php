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
                margin: 4mm 4mm;
            @else
                size: 210mm 297mm portrait;
                margin: 6mm 5mm;
            @endif
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #ffffff;
            color: #0f172a;
        }

        .sheet {
            width: 100%;
            page-break-after: always;
        }

        .sheet:last-child {
            page-break-after: avoid;
        }

        /* Tabel Grid Layout untuk DomPDF */
        table.grid-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 2mm;
            table-layout: fixed;
        }

        td.label-cell {
            vertical-align: middle;
            text-align: center;
            border: 0.8pt dashed #94a3b8;
            border-radius: 5px;
            padding: 1.5mm 1.5mm;
            background: #ffffff;
            overflow: hidden;
            @if($template['key'] === 'a4_grid_12')
                height: 64mm;
            @elseif($template['key'] === 'a4_grid_24')
                height: 42mm;
            @elseif($template['key'] === 'sticker_103')
                height: 31mm;
            @else
                height: 50mm;
            @endif
        }

        /* Konten Stiker Kotak Centered */
        .label-header {
            font-size: 6.5pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.2pt;
            color: #0f172a;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            padding-bottom: 1mm;
            border-bottom: 0.4pt solid #e2e8f0;
            margin-bottom: 1.5mm;
        }

        .qr-wrapper {
            text-align: center;
            margin: 0 auto;
        }

        .qr-img {
            @if($template['key'] === 'a4_grid_12')
                width: 32mm;
                height: 32mm;
            @elseif($template['key'] === 'a4_grid_24')
                width: 20mm;
                height: 20mm;
            @elseif($template['key'] === 'sticker_103')
                width: 16mm;
                height: 16mm;
            @else
                width: 25mm;
                height: 25mm;
            @endif
            display: block;
            margin: 0 auto;
        }

        .footer-info {
            margin-top: 1.5mm;
            padding-top: 1mm;
            border-top: 0.4pt solid #e2e8f0;
        }

        .row1-text {
            font-size: 7.5pt;
            font-weight: bold;
            color: #0f172a;
            text-align: center;
            line-height: 1.15;
            margin-bottom: 0.8mm;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .row2-text {
            font-family: 'Courier New', Courier, monospace;
            font-size: 7pt;
            font-weight: bold;
            color: #047857; /* emerald-700 */
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Thermal Single Specific */
        .thermal-wrapper {
            text-align: center;
            padding: 3mm 2mm;
            border: 0.8pt dashed #94a3b8;
            border-radius: 6px;
        }

        .thermal-title {
            font-size: 8pt;
            font-weight: 900;
            text-transform: uppercase;
            border-bottom: 0.5pt solid #e2e8f0;
            padding-bottom: 1.5mm;
            margin-bottom: 2.5mm;
            color: #0f172a;
        }

        .thermal-qr-img {
            width: 32mm;
            height: 32mm;
            margin: 0 auto 2.5mm auto;
            display: block;
        }

        .thermal-row1 {
            font-size: 8.5pt;
            font-weight: bold;
            margin-bottom: 1mm;
            white-space: nowrap;
            overflow: hidden;
        }

        .thermal-row2 {
            font-family: 'Courier New', Courier, monospace;
            font-size: 8pt;
            font-weight: bold;
            color: #047857;
        }
    </style>
</head>
<body>

    @foreach($pages as $pageLabels)
        <div class="sheet">
            @if($template['key'] === 'thermal_single')
                @foreach($pageLabels as $lbl)
                    <div class="thermal-wrapper">
                        <div class="thermal-title">{{ $lbl['title'] }}</div>
                        <img src="data:image/svg+xml;base64,{{ $lbl['qr_base64'] }}" alt="QR Code" class="thermal-qr-img">
                        <div class="footer-info">
                            <div class="thermal-row1">{{ $lbl['row1_text'] }}</div>
                            <div class="thermal-row2">{{ $lbl['row2_text'] }}</div>
                        </div>
                    </div>
                @endforeach
            @else
                @php
                    $cols = $template['cols'] ?? 4;
                    $chunkedRows = array_chunk($pageLabels, $cols);
                @endphp

                <table class="grid-table">
                    @foreach($chunkedRows as $row)
                        <tr>
                            @foreach($row as $lbl)
                                <td class="label-cell" style="width: {{ 100 / $cols }}%;">
                                    <div class="label-header">{{ $lbl['title'] }}</div>
                                    <div class="qr-wrapper">
                                        <img src="data:image/svg+xml;base64,{{ $lbl['qr_base64'] }}" alt="QR Code" class="qr-img">
                                    </div>
                                    <div class="footer-info">
                                        <div class="row1-text" title="{{ $lbl['row1_text'] }}">{{ $lbl['row1_text'] }}</div>
                                        <div class="row2-text">{{ $lbl['row2_text'] }}</div>
                                    </div>
                                </td>
                            @endforeach

                            {{-- Isi cell kosong jika baris terakhir kurang dari kolom --}}
                            @for($i = count($row); $i < $cols; $i++)
                                <td style="width: {{ 100 / $cols }}%; border: none;"></td>
                            @endfor
                        </tr>
                    @endforeach
                </table>
            @endif
        </div>
    @endforeach

</body>
</html>

