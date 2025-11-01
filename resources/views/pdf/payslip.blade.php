<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <title>Slip Gaji</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111827;
            margin: 20px;
        }

        /* Header */
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            margin: 0 0 4px 0;
        }

        .meta {
            text-align: center;
            font-size: 12px;
            color: #374151;
        }

        /* General table styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        th,
        td {
            padding: 8px;
            vertical-align: top;
        }

        .detail-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
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

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        /* Info section */
        .info-section table {
            width: 100%;
        }

        .info-section td:first-child {
            width: 180px;
            font-weight: bold;
        }

        .info-section td {
            padding: 6px 0;
        }

        /* Section titles */
        .section-title {
            font-weight: bold;
            background: #f3f4f6;
            padding: 6px 8px;
            margin-top: 16px;
            border: 1px solid #e5e7eb;
        }

        /* Total box */
        .total-section {
            margin-top: 16px;
            text-align: right;
        }

        .total-box {
            display: inline-block;
            border: 2px solid #000;
            padding: 12px 20px;
            background-color: #f9f9f9;
        }

        .total-box .label {
            font-size: 13px;
            font-weight: bold;
        }

        .total-box .amount {
            font-size: 18px;
            font-weight: bold;
            color: #2563eb;
        }

        /* Footer / signature */
        .signature-table td {
            border: none;
        }

        .footer-note {
            font-size: 10px;
            color: #6b7280;
            text-align: center;
            margin-top: 24px;
        }
    </style>
    <!-- Keep content intact; only visual styling/layout enhanced -->
    <!-- Fonts must be embeddable for PDF (DejaVu Sans is supported by dompdf) -->
    <!-- No external resources are loaded to keep PDF generation self-contained -->

</head>

<body>
    <div class="header">
        <div class="title">Slip Gaji</div>
        <div class="meta">{{ optional($detail->payroll)->name }}</div>
    </div>

    <div class="info-section">
        <table>
            <tr>
                <td>Nama</td>
                <td>: {{ optional($detail->staff)->name }}</td>
            </tr>
            <tr>
                <td>Jabatan</td>
                <td>: {{ optional(optional($detail->staff)->positionReference)->name ?? '-' }}</td>
            </tr>
            <tr>
                <td>TMT Training</td>
                <td>: {{ date('d-m-Y', strtotime($detail->staff->tmt_training)) }}</td>
            </tr>
        </table>
    </div>

    <div class="section-title">Komponen Gaji</div>
    <table class="detail-table">
        <tbody>
            <tr>
                <td>Gaji Pokok</td>
                <td class="right">{{ number_format($detail->salary, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Tunjangan Jabatan</td>
                <td class="right">{{ number_format($detail->bonus_position, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Tunjangan Kompetensi</td>
                <td class="right">{{ number_format($detail->bonus_competency, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Bonus Lembur ({{ $detail->overtime_count }} jam)</td>
                <td class="right">{{ number_format($bonusLembur, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Bonus Visit Solo ({{ $detail->visit_solo_count }} kali)</td>
                <td class="right">{{ number_format($bonusVisitSolo, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Bonus Visit Luar Solo ({{ $detail->visit_luar_solo_count }} kali)</td>
                <td class="right">{{ number_format($bonusVisitLuar, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Bonus Lain</td>
                <td class="right">{{ number_format($detail->bonus_lain, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="right"><strong>Total Bonus</strong></td>
                <td class="right"><strong>{{ number_format($totalBonus, 0, ',', '.') }}</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Potongan</div>
    <table class="detail-table">
        <tbody>
            <tr>
                <td>BPJS Kesehatan</td>
                <td class="right">{{ number_format($detail->cut_bpjs_kesehatan, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>BPJS Ketenagakerjaan</td>
                <td class="right">{{ number_format($detail->cut_bpjs_ketenagakerjaan, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Pot. Sakit ({{ $detail->sick_leave_count }} hari)</td>
                <td class="right">{{ number_format($cutSakit, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Pot. Tengah Hari ({{ $detail->halfday_count }} kali)</td>
                <td class="right">{{ number_format($cutHalfday, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Pot. Ijin/Alfa ({{ $detail->leave_count }} hari)</td>
                <td class="right">{{ number_format($cutIjin, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Pot. Lain</td>
                <td class="right">{{ number_format($detail->cut_lain, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Pot. Hutang</td>
                <td class="right">{{ number_format($detail->cut_hutang, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="right"><strong>Total Potongan</strong></td>
                <td class="right"><strong>{{ number_format($totalPot, 0, ',', '.') }}</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="total-section">
        <div class="total-box">
            <div class="label">Total Gaji Dibayarkan</div>
            <div class="amount">{{ number_format($totalGaji, 0, ',', '.') }}</div>
        </div>
    </div>

    <table class="signature-table" style="margin-top: 30px;">
        <tr>
            <td style="width: 60%;"><strong>Dokumen ini dibuat oleh Bagian Finance.</strong></td>
            <td class="center" style="width: 40%;">
                <strong>Mengetahui</strong><br>
                <strong>Direktur</strong><br>
                <br>
                <br>
                <br>
                <strong>Antin Okfitasari</strong>
            </td>
        </tr>
    </table>

    <div class="footer-note">Dokumen ini dicetak otomatis oleh sistem.</div>

</body>

</html>
