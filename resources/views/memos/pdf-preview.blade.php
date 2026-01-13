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
    </style>
</head>

<body>
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
        Selanjutnya disebut PIHAK PERTAMA
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
        Selanjutnya disebut PIHAK KEDUA
    </div>

    <div style="margin-bottom: 10px; text-align: justify;">
        Bahwa PIHAK PERTAMA melakukan kesepakatan kerja dengan PIHAK KEDUA untuk
        menguruskan atas rincian pekerjaan sebagai berikut:
    </div>

    <ol class="list-numbered">
        @if ($memo->type_work && is_array($memo->type_work))
            @foreach ($memo->type_work as $work)
                <li>{{ $work['work_detail'] }}</li>
            @endforeach
        @endif
    </ol>

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
