<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <title>Slip Gaji</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111827;
            margin: 10px 15px;
        }

        /* Header */
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
        }

        .title {
            font-size: 16px;
            font-weight: bold;
            margin: 0 0 2px 0;
        }

        .meta {
            text-align: center;
            font-size: 11px;
            color: #374151;
        }

        /* General table styles */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        .detail-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        .detail-table th,
        .detail-table td {
            border: 1px solid #000;
            padding: 4px;
            text-align: left;
        }

        .detail-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .right {
            text-align: right !important;
        }

        .center {
            text-align: center;
        }

        /* Info section */
        .info-section {
            margin-bottom: 10px;
        }

        .info-section table {
            width: 100%;
        }

        .info-section td:first-child {
            width: 130px;
            font-weight: bold;
        }

        .info-section td {
            padding: 3px 0;
            border: none;
        }

        /* Section titles */
        .section-title {
            font-weight: bold;
            background: #f3f4f6;
            padding: 4px 6px;
            border: 1px solid #e5e7eb;
        }

        /* Total box */
        .total-section {
            margin-top: 12px;
            text-align: right;
        }

        .total-box {
            display: inline-block;
            border: 2px solid #000;
            padding: 8px 15px;
            background-color: #f9f9f9;
        }

        .total-box .label {
            font-size: 12px;
            font-weight: bold;
        }

        .total-box .amount {
            font-size: 16px;
            font-weight: bold;
            color: #2563eb;
        }

        /* Footer / signature */
        .signature-table td {
            border: none;
            padding: 4px;
        }

        .footer-note {
            font-size: 9px;
            color: #6b7280;
            text-align: center;
            margin-top: 15px;
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
                <td>: {{ $detail->staff_id ? optional($detail->staff)->name : $detail->nama_non_staff }}</td>
            </tr>
            <tr>
                <td>Jabatan</td>
                <td>:
                    {{ $detail->staff_id ? optional(optional($detail->staff)->positionReference)->name ?? '-' : 'Non Staff' }}
                </td>
            </tr>
            <tr>
                <td>TMT Training</td>
                <td>:
                    {{ $detail->staff_id && $detail->staff->tmt_training ? date('d-m-Y', strtotime($detail->staff->tmt_training)) : '-' }}
                </td>
            </tr>
        </table>
    </div>

    <table style="width: 100%; border: none; margin-top: 5px;">
        <tr>
            <td style="width: 50%; vertical-align: top; padding: 0 4px 0 0; border: none;">
                <div class="section-title">Komponen Gaji</div>
                <table class="detail-table">
                    <tbody>
                        @if ($detail->salary > 0)
                            <tr>
                                <td>Gaji Pokok</td>
                                <td class="right">{{ number_format($detail->salary, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                        @if ($detail->bonus_position > 0)
                            <tr>
                                <td>Tunjangan Jabatan</td>
                                <td class="right">{{ number_format($detail->bonus_position, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                        @if ($detail->bonus_competency > 0)
                            <tr>
                                <td>Tunjangan Kompetensi</td>
                                <td class="right">{{ number_format($detail->bonus_competency, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                        @if ($bonusLembur > 0)
                            <tr>
                                <td>Bonus Lembur ({{ $detail->overtime_count }} jam)</td>
                                <td class="right">{{ number_format($bonusLembur, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                        @if ($bonusVisitSolo > 0)
                            <tr>
                                <td>Visit Solo ({{ $detail->visit_solo_count }}x)</td>
                                <td class="right">{{ number_format($bonusVisitSolo, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                        @if ($bonusVisitLuar > 0)
                            <tr>
                                <td>Visit Luar Solo ({{ $detail->visit_luar_solo_count }}x)</td>
                                <td class="right">{{ number_format($bonusVisitLuar, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                        @if ($detail->bonus_lain > 0)
                            <tr>
                                <td>Bonus Lain</td>
                                <td class="right">{{ number_format($detail->bonus_lain, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td><strong>Total Komp. Gaji</strong></td>
                            <td class="right">
                                <strong>{{ number_format($detail->salary + $detail->bonus_position + $detail->bonus_competency + $totalBonus, 0, ',', '.') }}</strong>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>

            <td style="width: 50%; vertical-align: top; padding: 0 0 0 4px; border: none;">
                <div class="section-title">Potongan</div>
                <table class="detail-table">
                    <tbody>
                        @if ($detail->cut_bpjs_kesehatan > 0)
                            <tr>
                                <td>BPJS Kesehatan</td>
                                <td class="right">{{ number_format($detail->cut_bpjs_kesehatan, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                        @if ($detail->cut_bpjs_ketenagakerjaan > 0)
                            <tr>
                                <td>BPJS Ketenagakerjaan</td>
                                <td class="right">{{ number_format($detail->cut_bpjs_ketenagakerjaan, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endif
                        @if ($cutSakit > 0)
                            <tr>
                                <td>Pot. Sakit ({{ $detail->sick_leave_count }} hari)</td>
                                <td class="right">{{ number_format($cutSakit, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                        @if ($cutHalfday > 0)
                            <tr>
                                <td>Pot. Tengah Hari ({{ $detail->halfday_count }} kali)</td>
                                <td class="right">{{ number_format($cutHalfday, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                        @if ($cutIjin > 0)
                            <tr>
                                <td>Pot. Ijin/Alfa ({{ $detail->leave_count }} hari)</td>
                                <td class="right">{{ number_format($cutIjin, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                        @if ($detail->cut_lain > 0)
                            <tr>
                                <td>Pot. Lain</td>
                                <td class="right">{{ number_format($detail->cut_lain, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                        @if ($detail->cut_hutang > 0)
                            <tr>
                                <td>Pot. Hutang</td>
                                <td class="right">{{ number_format($detail->cut_hutang, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="right"><strong>Total Potongan</strong></td>
                            <td class="right"><strong>{{ number_format($totalPot, 0, ',', '.') }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <div class="total-section">
        <div class="total-box">
            <div class="label">Total Gaji Dibayarkan</div>
            <div class="amount">{{ number_format($totalGaji, 0, ',', '.') }}</div>
        </div>
    </div>

    <table class="signature-table" style="margin-top: 15px;">
        <tr>
            <td style="width: 60%;"></td>
            <td class="center" style="width: 40%;">
                <strong>Mengetahui</strong><br>
                <strong>Direktur</strong><br>
                <img src="{{ public_path('images/ttd_antin.png') }}" style="width: 120px; height: auto; margin: 5px 0;" alt="Tanda Tangan"><br>
                <strong>Antin Okfitasari</strong>
            </td>
        </tr>
    </table>

    <div class="footer-note">Dokumen ini dibuat oleh Bagian Finance & dicetak otomatis oleh sistem.</div>

</body>

</html>
