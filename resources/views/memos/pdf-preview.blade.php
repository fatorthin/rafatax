<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Memo Kesepakatan Kerja</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.5;
            margin: 0;
            padding: 20px 40px;
        }

        .text-center {
            text-align: center;
        }

        .font-bold {
            font-weight: bold;
        }

        .mb-4 {
            margin-bottom: 20px;
        }

        .table-data {
            width: 100%;
            margin-bottom: 10px;
        }

        .table-data td {
            vertical-align: top;
            padding: 2px 0;
        }

        .label-col {
            width: 100px;
        }

        .colon-col {
            width: 10px;
        }

        .list-numbered {
            padding-left: 20px;
            margin-top: 5px;
            margin-bottom: 5px;
        }

        .list-numbered li {
            margin-bottom: 2px;
        }

        .signature-section {
            margin-top: 40px;
            break-inside: avoid;
        }

        .underline {
            text-decoration: underline;
        }

        .bordered-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11pt;
            margin-bottom: 20px;
        }

        .bordered-table th,
        .bordered-table td {
            border: 1px solid black;
            padding: 5px;
            vertical-align: top;
        }

        .check-col {
            width: 30px;
            text-align: center;
        }

        .no-col {
            width: 30px;
            text-align: center;
        }
    </style>
</head>

<body
    style="background-image: url('{{ public_path('images/background.png') }}'); background-repeat: no-repeat; background-position: center; background-size: 70%;">
    <div class="text-center font-bold mb-4" style="margin-bottom: 30px;">
        MEMO KESEPAKATAN KERJA
    </div>

    <div>
        Yang bertanda tangan di bawah ini:
    </div>

    <table class="table-data" style="margin-top: 10px; margin-left: 30px; margin-bottom: 20px;">
        <tr>
            <td class="label-col">NAMA</td>
            <td class="colon-col">:</td>
            <td>ANTIN OKFITASARI</td>
        </tr>
        <tr>
            <td class="label-col">INSTANSI</td>
            <td class="colon-col">:</td>
            <td>KKP ANTIN OKFITASARI</td>
        </tr>
        <tr>
            <td class="label-col">ALAMAT</td>
            <td class="colon-col">:</td>
            <td>NAMPAN RT 02 RW 01 MADEGONDO GROGOL SUKOHARJO</td>
        </tr>
    </table>

    <div style="margin-bottom: 15px;">
        Selanjutnya disebut <span class="font-bold">PIHAK PERTAMA</span>
    </div>

    <table class="table-data" style="margin-left: 30px; margin-bottom: 20px;">
        <tr>
            <td class="label-col">NAMA</td>
            <td class="colon-col">:</td>
            <td>{{ strtoupper($memo->nama_klien) }}</td>
        </tr>
        <tr>
            <td class="label-col">INSTANSI</td>
            <td class="colon-col">:</td>
            <td>{{ strtoupper($memo->instansi_klien) }}</td>
        </tr>
        <tr>
            <td class="label-col">ALAMAT</td>
            <td class="colon-col">:</td>
            <td>{{ strtoupper($memo->alamat_klien) }}</td>
        </tr>
    </table>

    <div style="margin-bottom: 15px;">
        Selanjutnya disebut <span class="font-bold">PIHAK KEDUA</span>
    </div>

    <div style="margin-bottom: 5px; text-align: justify;">
        Bahwa <span class="font-bold">PIHAK PERTAMA</span> melakukan kesepakatan kerja dengan <span
            class="font-bold">PIHAK KEDUA</span> untuk
        mengerjakan atas :
    </div>

    <!-- Defined list of items with checks for active ones -->
    @php
        $selectedWorks = collect($memo->type_work ?? [])
            ->pluck('work_detail')
            ->map(fn($item) => trim($item))
            ->toArray();
        // Standard list from screenshot logic or dynamic?
        // User said "nanti untuk gambar backgroundnya menggunakan gambar [..] dan untuk type worknya ditambahi kolom total fee nya diambil dari data total fee"
        // The screenshot shows a mix of specific items and standard items.
        // Since we only have dynamic items in the DB, I will render the dynamic items in the table.
        // If the user wants a fixed list with checks, they would need to change the data structure.
        // For now, I will render the user's dynamic items as Rows 1..N and check mark V on all of them?
// Or just list them. The screenshot shows checked items.
// I will assume all items "in" the memo are "checked".

$items = $memo->type_work ?? [];
$rowCount = count($items);
$totalFeeFormatted = 'Rp.' . number_format($memo->total_fee, 0, ',', '.');
    @endphp

    <table class="bordered-table">
        @foreach ($items as $index => $work)
            <tr>
                <td class="no-col">{{ $index + 1 }}.</td>
                <td>{{ $work['work_detail'] }}</td>
                <td class="check-col">V</td>
                @if ($index === 0)
                    <td rowspan="{{ $rowCount }}"
                        style="vertical-align: middle; text-align: center; font-weight: bold; width: 150px;">
                        {{ $totalFeeFormatted }}
                    </td>
                @endif
            </tr>
        @endforeach
        {{-- Fill empty rows if needed to look like the screenshot? User didn't ask for generic items, just "type worknya ditambahi kolom total fee". --}}
    </table>

    <div style="margin-top: 15px; margin-bottom: 30px; text-align: justify;">
        Bukti persetujuan Pihak Pertama mengenai hal-hal tersebut di atas adalah dengan
        menandatangani surat ini dan menyerahkan kepada Pihak Kedua.
    </div>

    <div class="signature-section">
        <div>Ditandatangani di Sukoharjo</div>
        <div>Pada tanggal {{ \Carbon\Carbon::parse($memo->tanggal_ttd)->translatedFormat('d F Y') }}</div>
        <div class="font-bold underline" style="margin-bottom: 80px;">Pihak Pertama</div>

        <div class="font-bold underline">ANTIN OKFITASARI, S.E.,S.H.,Msi., Ak., CA.AB., BKP.,CATr.,ACPA</div>
        <div>OWNER KKP ANTIN OKFITASARI (RAFATAX)</div>
    </div>
</body>

</html>
