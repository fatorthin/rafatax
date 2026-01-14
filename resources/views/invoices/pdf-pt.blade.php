<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice PDF (PT)</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        .container {
            padding: 20px;
        }

        .header {
            text-align: left;
            margin-bottom: 10px;
        }

        .header img {
            max-width: 100%;
            width: 330px;
        }

        .meta {
            float: right;
            text-align: right;
        }

        .invoice-number {
            text-align: center;
            font-weight: bold;
            margin: 8px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 8px;
        }

        th {
            background: #f5f5f5;
        }

        .text-right {
            text-align: right;
        }

        .total-row td {
            font-weight: bold;
        }

        .footer-section {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
        }

        .transfer-info {
            text-align: left;
            flex: 1;
        }

        .transfer-info u {
            text-decoration: underline;
        }

        .signature-section {
            text-align: right;
            flex: 1;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            @if (!empty($headerImage))
                <img src="{{ $headerImage }}" alt="Kop Invoice PT">
            @endif
            <div class="meta">
                <div>Sukoharjo, {{ now()->locale('id')->isoFormat('D MMMM Y') }}</div>
                <div>Kepada :</div>
                <div>
                    <strong>{{ $client_name ?? (optional($invoice->mou->client)->name ?? (optional($invoice->mou->client)->company_name ?? '')) }}</strong>
                </div>
            </div>
            <div style="clear: both;"></div>
        </div>

        <h3 class="invoice-number">{{ $invoice->invoice_number ?? 'Invoice' }}</h3>

        <table>
            <thead>
                <tr>
                    <th style="width:60%;">Keterangan</th>
                    <th style="width:20%;" class="text-right">Nominal</th>
                    <th style="width:20%;" class="text-right">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($costLists as $index => $item)
                    <tr>
                        <td>{{ $item->description ?? 'Fee' }}</td>
                        <td class="text-right">{{ number_format($item->amount, 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($item->amount, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td style="text-align:left;">Terbilang
                        :<br><em>{{ \App\Helpers\TerbilangHelper::terbilang($costLists->sum('amount')) }}</em></td>
                    <td class="text-right">TOTAL</td>
                    <td class="text-right">{{ number_format($costLists->sum('amount'), 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <table style="width: 100%; border: none; margin-top: 40px;">
            <tr>
                <td style="border: none; width: 70%; vertical-align: top; padding: 0;">
                    <div class="transfer-info">
                        <u>TRANSFER VIA</u><br>
                        BCA.IDR<br>
                        A.C : 785 - 1260 - 513<br>
                        A.N : PT. Aghnia Oasis Konsultindo
                    </div>
                </td>
                <td style="border: none; width: 30%; text-align: center; vertical-align: top; padding: 0;">
                    <div style="text-align: center;">
                        <div>Hormat Kami,</div>
                        @if (!empty($signatureImage))
                            <div style="margin: 5px 0;">
                                <img src="{{ $signatureImage }}" alt="Tanda Tangan" style="height: 60px;">
                            </div>
                        @else
                            <div style="margin-top: 40px;"></div>
                        @endif
                        <div>( Kasir )</div>
                    </div>
                </td>
            </tr>
        </table>

        @if (!empty($error))
            <div style="color: red; margin-top: 12px;">PDF generation error: {{ $error }}</div>
        @endif
    </div>
</body>

</html>
