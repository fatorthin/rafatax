<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice PDF (KKP)</title>
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

        .signature {
            margin-top: 40px;
            text-align: right;
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
                <img src="{{ $headerImage }}" alt="Kop Invoice KKP">
            @endif
            <div class="meta">
                <div>Sukoharjo, {{ \Carbon\Carbon::parse($invoice->invoice_date)->locale('id')->isoFormat('D MMMM Y') }}
                </div>
                <div>Kepada :</div>
                @php
                    $resolvedClientName = '';

                    if (!empty($invoice->memo_id) && empty($invoice->client_id)) {
                        $resolvedClientName = $client_name ?? '';
                    } elseif (!empty($invoice->memo_id) && !empty($invoice->client_id)) {
                        $resolvedClientName = optional($invoice->client)->company_name ?? '';
                    } elseif (!empty($invoice->mou_id) && empty($invoice->memo_id) && empty($invoice->client_id)) {
                        $resolvedClientName = optional($invoice->mou->client)->company_name ?? '';
                    }
                @endphp
                <div>
                    <strong>{{ $resolvedClientName }}</strong>
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
                @php
                    $isIncludePph = $invoice->is_include_pph23 ?? false;
                    $totalAmount = 0;
                @endphp
                @foreach ($costLists as $index => $item)
                    @php
                        $amount = $item->amount;
                        if ($isIncludePph) {
                            $amount = $amount / 0.98;
                        }
                        $totalAmount += $amount;
                    @endphp
                    <tr>
                        <td>{{ $item->description ?? 'Fee' }}</td>
                        <td class="text-right">{{ number_format($amount, 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($amount, 0, ',', '.') }}</td>
                    </tr>
                @endforeach

                @if ($isIncludePph)
                    @php
                        $pphAmount = $totalAmount * 0.02;
                    @endphp
                    <tr>
                        <td>Potongan Pajak PPH 23</td>
                        <td class="text-right">2%</td>
                        <td class="text-right">-{{ number_format($pphAmount, 0, ',', '.') }}</td>
                    </tr>
                @endif
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
                <td style="border: none; width: 40%; vertical-align: top; padding: 0;">
                    <div class="transfer-info">
                        <u>TRANSFER VIA</u><br>
                        BCA.IDR<br>
                        A.C : 785 - 1135 - 425<br>
                        A.N : Antin Okfitasari
                    </div>
                </td>
                <td style="border: none; width: 30%; text-align: center; vertical-align: middle; padding: 0;">
                    @if (isset($invoice) && strtolower($invoice->invoice_status) === 'paid')
                        @php
                            $stampPath = public_path('images/cap-lunas.png');
                            // Ambil tanggal lunas: tgl_transfer jika ada (atau dari relation cash report)
                            $paidDate = $invoice->tgl_transfer ?? $invoice->created_at;
                        @endphp
                        @if (file_exists($stampPath))
                            <img src="data:image/png;base64,{{ base64_encode(file_get_contents($stampPath)) }}" alt="LUNAS" style="height: 80px; opacity: 0.8;">
                            @if ($paidDate)
                                <div style="margin-top: 5px; font-weight: bold; font-size: 15px; color: #1E0CD3;">
                                    {{ \Carbon\Carbon::parse($paidDate)->format('d/m/Y') }}
                                </div>
                            @endif
                        @endif
                    @endif
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
