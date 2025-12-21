<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Slip Bonus - {{ $detail->staff->name }}</title>
    <style>
        body {
            font-family: Helvetica, sans-serif;
            font-size: 12px;
            margin: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .header h2 {
            margin: 5px 0;
        }

        .info-section {
            margin-bottom: 20px;
        }

        .info-section table {
            width: 100%;
        }

        .info-section td {
            padding: 5px 0;
        }

        .info-section td:first-child {
            width: 150px;
            font-weight: bold;
        }

        .detail-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .detail-table th,
        .detail-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }

        .detail-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .detail-table td.number {
            text-align: right;
        }

        .detail-table td.center {
            text-align: center;
        }

        .total-section {
            margin-top: 20px;
            text-align: right;
        }

        .total-box {
            display: inline-block;
            border: 2px solid #000;
            padding: 15px 30px;
            background-color: #f9f9f9;
        }

        .total-box .label {
            font-size: 14px;
            font-weight: bold;
        }

        .total-box .amount {
            font-size: 18px;
            font-weight: bold;
            color: #2563eb;
        }

        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="header">
        <h2>SLIP BONUS KARYAWAN</h2>
        <p>{{ $detail->payrollBonus->description }}</p>
        <p>Periode: {{ \Carbon\Carbon::parse($detail->payrollBonus->start_date)->format('d M Y') }} - {{ \Carbon\Carbon::parse($detail->payrollBonus->end_date)->format('d M Y') }}</p>
    </div>

    <div class="info-section">
        <table>
            <tr>
                <td>Nama</td>
                <td>: {{ $detail->staff->name }}</td>
            </tr>
            <tr>
                <td>Posisi</td>
                <td>: {{ $detail->staff->positionReference->name ?? '-' }}</td>
            </tr>
            <tr>
                <td>Tanggal Cetak</td>
                <td>: {{ \Carbon\Carbon::now()->format('d F Y H:i') }}</td>
            </tr>
        </table>
    </div>

    <h3>Detail Bonus dari Case Project</h3>
    <table class="detail-table">
        <thead>
            <tr>
                <th class="center">No</th>
                <th>Deskripsi Project</th>
                <th>Client</th>
                <th>Tanggal Project</th>
                <th class="number">Bonus (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($caseProjectDetails as $index => $cpDetail)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td>{{ $cpDetail->caseProject->description }}</td>
                    <td>{{ $cpDetail->caseProject->client->company_name ?? '-' }}</td>
                    <td class="center">{{ \Carbon\Carbon::parse($cpDetail->caseProject->project_date)->format('d M Y') }}</td>
                    <td class="number">{{ number_format($cpDetail->bonus, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" style="text-align: right;">TOTAL BONUS</th>
                <th class="number">{{ number_format($detail->amount, 0, ',', '.') }}</th>
            </tr>
        </tfoot>
    </table>

    <div class="total-section">
        <div class="total-box">
            <div class="label">Total Bonus Diterima:</div>
            <div class="amount">Rp {{ number_format($detail->amount, 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="footer">
        <p>Dokumen ini dicetak otomatis oleh sistem. Slip ini merupakan bukti pembayaran bonus yang sah.</p>
        <p>Dicetak pada: {{ \Carbon\Carbon::now()->format('d F Y H:i:s') }}</p>
    </div>
</body>

</html>
