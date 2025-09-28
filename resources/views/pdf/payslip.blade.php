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
        }

        .header {
            text-align: center;
            margin-bottom: 12px;
        }

        .title {
            font-size: 16px;
            font-weight: bold;
        }

        .meta {
            text-align: center;
            font-size: 12px;
            color: #374151;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        td {
            padding: 6px;
            vertical-align: top;
        }

        .section-title {
            font-weight: bold;
            background: #f3f4f6;
            padding: 4px 6px;
            margin-top: 8px;
        }

        .right {
            text-align: right;
        }

        .muted {
            color: #6b7280;
        }

        .line {
            border-bottom: 1px solid #e5e7eb;
            margin: 10px 0;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="title">Slip Gaji</div>
        <div class="meta">{{ optional($detail->payroll)->name }}</div>
    </div>

    <table>
        <tr>
            <td><strong>Nama</strong></td>
            <td>: {{ optional($detail->staff)->name }}</td>
        </tr>
        <tr>
            <td><strong>Jabatan</strong></td>
            <td>: {{ optional(optional($detail->staff)->positionReference)->name ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>TMT Training</strong></td>
            <td>: {{ date('d-m-Y', strtotime($detail->staff->tmt_training)) }}</td>
        </tr>
    </table>

    <div class="section-title">Komponen Gaji</div>
    <table>
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
    </table>

    <div class="section-title">Potongan</div>
    <table>
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
            <td>Pot. Ijin/Cuti/Alfa ({{ $detail->leave_count }} hari)</td>
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
    </table>

    <div class="line"></div>

    <table>
        <tr>
            <td><strong>Total Gaji Dibayarkan</strong></td>
            <td class="right"><strong>{{ number_format($totalGaji, 0, ',', '.') }}</strong></td>
        </tr>
    </table>

    <table>
        <tr>
            <td><strong>Dokumen ini dibuat oleh Bagian Finance.</strong></td>

        </tr>
        <tr>
            <td></td>
            <td class="center">
                <strong>Mengetahui </strong><br>
                <strong>Direktur </strong><br>
                <br>
                <br>
                <br>
                <strong>Antin Okfitasari</strong>
            </td>
        </tr>

    </table>

</body>

</html>
