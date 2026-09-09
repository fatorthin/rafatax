<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Perjanjian Kerja - {{ $mou->type === 'kkp' ? 'KKP Antin Okfitasari' : 'PT Aghnia Oasis Konsultindo' }}</title>
    <style>
        @page {
            size: A4;
            margin: 200px 0px 100px 0px;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
            margin: 0;
            padding: 0 40px;
        }

        header {
            position: fixed;
            top: -220px;
            left: 0;
            right: 0;
            height: 150px;
            text-align: center;
        }

        header img {
            width: 100%;
            height: auto;
        }

        .container {
            width: 100%;
        }

        .document-title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .document-subtitle {
            text-align: center;
            margin-top: 0;
            margin-bottom: 5px;
            font-size: 13px;
        }

        .document-number {
            font-weight: bold;
            text-align: center;
            margin-bottom: 20px;
        }

        .section {
            margin-bottom: 18px;
        }

        .section-title {
            font-size: 13px;
            color: #005e8a;
            border-bottom: 2px solid #005e8a;
            padding-bottom: 4px;
            margin-bottom: 8px;
            text-transform: uppercase;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .parties-table td {
            vertical-align: top;
            padding: 2px 5px;
        }

        .parties-table tr {
            background-color: transparent !important;
        }

        .data-table {
            width: 100%;
            border: 1px solid #000;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #000;
            padding: 5px;
        }

        .data-table th {
            background-color: #f2f2f2;
            text-align: center;
        }

        .signatures {
            margin-top: 25px;
            width: 100%;
        }

        .signature-box {
            display: inline-block;
            width: 45%;
            vertical-align: top;
            text-align: center;
        }

        .signature-spacer {
            display: inline-block;
            width: 8%;
        }

        .signature-line {
            margin-top: 50px;
            border-bottom: 1px solid #000;
            margin-bottom: 5px;
        }

        ol, ul {
            margin-top: 0;
            padding-left: 20px;
        }

        li {
            margin-bottom: 4px;
        }

        .watermark {
            position: fixed;
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: -1000;
            width: 40%;
        }
    </style>
</head>

<body>
    <!-- Fixed Header -->
    <header>
        @if ($mou->type === 'kkp')
            <img src="{{ public_path('images/header-kkp.jpg') }}" alt="Header KKP">
        @else
            <img src="{{ public_path('images/header.png') }}" alt="Header PT">
        @endif
    </header>

    <!-- Watermark -->
    <img src="{{ public_path('images/background.png') }}" class="watermark">

    <div class="container">
        <!-- Title & Number -->
        <div class="section">
            <div class="document-title">Surat Perjanjian Kerja Sama</div>
            <div class="document-subtitle">
                {{ $mou->categoryMou->name ?? 'Pekerjaan Jasa Perpajakan' }} Tahun
                {{ $mou->tahun_pajak ?? \Carbon\Carbon::parse($mou->end_date)->locale('id')->translatedFormat('Y') }}
            </div>
            <div class="document-number">NO: {{ $mou->mou_number }}</div>
        </div>

        <!-- Identitas Para Pihak -->
        <div class="section">
            <p>Yang bertanda tangan di bawah ini masing-masing:</p>
            <table class="parties-table">
                <tr>
                    <td width="20">1.</td>
                    <td width="100"><strong>Nama</strong></td>
                    <td width="10">:</td>
                    <td>{{ $mou->client->owner_name ?? '-' }}</td>
                </tr>
                <tr>
                    <td></td>
                    <td><strong>Jabatan</strong></td>
                    <td>:</td>
                    <td>{{ $mou->client->owner_role ?: 'OWNER' }}</td>
                </tr>
                <tr>
                    <td></td>
                    <td><strong>Perusahaan</strong></td>
                    <td>:</td>
                    <td>{{ $mou->client->company_name ?? '-' }}</td>
                </tr>
                <tr>
                    <td></td>
                    <td><strong>Alamat</strong></td>
                    <td>:</td>
                    <td>{{ $mou->client->address ?? '-' }}</td>
                </tr>
                <tr>
                    <td></td>
                    <td colspan="3">dan selanjutnya disebut <strong>PIHAK PERTAMA</strong></td>
                </tr>
                <tr>
                    <td colspan="4" height="10"></td>
                </tr>
                <tr>
                    <td>2.</td>
                    <td><strong>Nama</strong></td>
                    <td>:</td>
                    <td>ANTIN OKFITASARI</td>
                </tr>
                <tr>
                    <td></td>
                    <td><strong>Jabatan</strong></td>
                    <td>:</td>
                    <td>{{ $mou->type === 'kkp' ? 'PIMPINAN' : 'DIREKTUR' }}</td>
                </tr>
                <tr>
                    <td></td>
                    <td><strong>Perusahaan</strong></td>
                    <td>:</td>
                    <td>{{ $mou->type === 'kkp' ? 'KANTOR KONSULTAN PAJAK (KKP) ANTIN OKFITASARI' : 'PT AGHNIA OASIS KONSULTINDO' }}</td>
                </tr>
                <tr>
                    <td></td>
                    <td><strong>Alamat</strong></td>
                    <td>:</td>
                    <td>DK NAMPAN RT 02 RW 01 MADEGONDO GROGOL SUKOHARJO</td>
                </tr>
                <tr>
                    <td></td>
                    <td colspan="3">dan selanjutnya disebut <strong>PIHAK KEDUA</strong></td>
                </tr>
            </table>

            <p>Pada hari ini
                {{ \Carbon\Carbon::parse($mou->start_date)->locale('id')->translatedFormat('l') }},
                tanggal
                {{ \Carbon\Carbon::parse($mou->start_date)->locale('id')->translatedFormat('d F Y') }}
                pihak Pertama dan pihak Kedua sepakat untuk mengadakan perikatan kerja sama 
                <strong>{{ $mou->categoryMou->name ?? 'Konsultasi Kewajiban Perpajakan' }}
                {{ $mou->client->company_name }} Untuk Tahun Pajak 
                {{ $mou->tahun_pajak ?? \Carbon\Carbon::parse($mou->end_date)->locale('id')->translatedFormat('Y') }}</strong>,
                seperti yang diatur dalam pasal-pasal di bawah ini:</p>
        </div>

        @php
            // Helper placeholder replacement
            $replacePlaceholders = function($text) use ($mou, $costLists) {
                if (!$text) return '';
                $totalNominal = $costLists ? $costLists->sum('total_amount') : 0;
                $startDate = $mou->start_date ? \Carbon\Carbon::parse($mou->start_date)->locale('id')->translatedFormat('d F Y') : '-';
                $endDate = $mou->end_date ? \Carbon\Carbon::parse($mou->end_date)->locale('id')->translatedFormat('d F Y') : '-';
                
                $replacements = [
                    '{NAMA_KLIEN}' => $mou->client->company_name ?? '-',
                    '{PERUSAHAAN}' => $mou->client->company_name ?? '-',
                    '{NAMA_PEMILIK}' => $mou->client->owner_name ?? '-',
                    '{ALAMAT}' => $mou->client->address ?? '-',
                    '{TAHUN_PAJAK}' => $mou->tahun_pajak ?? ($mou->end_date ? \Carbon\Carbon::parse($mou->end_date)->year : '-'),
                    '{MOU_NUMBER}' => $mou->mou_number ?? '-',
                    '{TANGGAL_AWAL}' => $startDate,
                    '{TANGGAL_AKHIR}' => $endDate,
                    '{TOTAL_NOMINAL}' => 'Rp ' . number_format($totalNominal, 0, ',', '.'),
                ];
                return strtr($text, $replacements);
            };

            $sections = $mou->custom_sections ?? [];
        @endphp

        <!-- Loop Custom Sections / Pasal -->
        @foreach ($sections as $index => $section)
            <div class="section" style="page-break-inside: auto;">
                <div class="section-title">
                    Pasal {{ $index + 1 }}: {{ $section['title'] ?? 'Pasal' }}
                </div>
                
                @if (!empty($section['content']))
                    <div>
                        {!! $replacePlaceholders($section['content']) !!}
                    </div>
                @endif

                {{-- Tabel Cost List jika di-toggle aktif pada section ini --}}
                @if (!empty($section['include_cost_table']) && $costLists && $costLists->count() > 0)
                    <table class="data-table" style="margin-top: 10px;">
                        <thead>
                            <tr>
                                <th style="width: 5%;">No</th>
                                <th style="width: 45%;">Rincian Pekerjaan</th>
                                <th style="width: 10%;">Qty</th>
                                <th style="width: 10%;">Satuan</th>
                                <th style="width: 15%;">Harga</th>
                                <th style="width: 15%;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($costLists as $i => $cost)
                                <tr>
                                    <td style="text-align: center;">{{ $i + 1 }}</td>
                                    <td>
                                        <strong>{{ $cost->description }}</strong><br>
                                        <small>{{ $cost->coa->name ?? '-' }}</small>
                                    </td>
                                    <td style="text-align: center;">{{ $cost->quantity }}</td>
                                    <td style="text-align: center;">{{ $cost->satuan_quantity }}</td>
                                    <td style="text-align: right;">Rp {{ number_format($cost->amount, 0, ',', '.') }}</td>
                                    <td style="text-align: right;">Rp {{ number_format($cost->total_amount, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                            <tr style="background-color: #f9f9f9; font-weight: bold;">
                                <td colspan="5" style="text-align: right;">Total Keseluruhan</td>
                                <td style="text-align: right;">Rp {{ number_format($costLists->sum('total_amount'), 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>

                    @if ($mou->tanggal_tagih_awal && $mou->tanggal_tagih_akhir)
                        <p style="font-size: 11px; margin-top: 5px;">
                            <em>* Termin penagihan dijadwalkan mulai masa pajak 
                            {{ \Carbon\Carbon::parse($mou->tanggal_tagih_awal)->locale('id')->translatedFormat('F Y') }} sampai dengan masa pajak 
                            {{ \Carbon\Carbon::parse($mou->tanggal_tagih_akhir)->locale('id')->translatedFormat('F Y') }}.</em>
                        </p>
                    @endif
                @endif
            </div>
        @endforeach

        <!-- Penutup & Tanda Tangan -->
        <div class="section" style="page-break-inside: avoid;">
            <div class="section-title">Penutup</div>
            <p>Demikian Surat Perjanjian Kerja Sama ini dibuat dalam rangkap 2 (dua) bermaterai cukup dan masing-masing mempunyai kekuatan hukum yang sama, untuk dipatuhi dan dilaksanakan oleh kedua belah pihak.</p>
            <p>Ditandatangani di: {{ $mou->client->city ?? 'Sukoharjo' }}</p>
            <p>Pada tanggal: {{ \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y') }}</p>

            <div class="signatures">
                <div class="signature-box">
                    <p><strong>Pihak Pertama</strong><br>{{ $mou->client->company_name ?? '-' }}</p>
                    <div class="signature-line" style="border:none; height: 50px;"></div>
                    <div style="border-bottom: 1px solid black; display: inline-block; min-width: 160px; padding-bottom: 2px;">
                        <strong>{{ $mou->client->owner_name ?? '-' }}</strong>
                    </div>
                    <p>{{ $mou->client->owner_role ?: 'OWNER' }}</p>
                </div>

                <div class="signature-spacer"></div>

                <div class="signature-box">
                    <p><strong>Pihak Kedua</strong><br>{{ $mou->type === 'kkp' ? 'KKP ANTIN OKFITASARI' : 'PT AGHNIA OASIS KONSULTINDO' }}</p>
                    @if (isset($withSignature) && $withSignature)
                        <img src="{{ public_path('images/ttd_antin.png') }}" alt="Tanda Tangan" style="height: 80px; margin-top: 5px;">
                    @else
                        <div style="height: 80px;"></div>
                    @endif
                    <div style="border-bottom: 1px solid black; display: inline-block; min-width: 160px; padding-bottom: 2px;">
                        <strong>ANTIN OKFITASARI, S.E.,S.H.,M.Si., Ak., CA.AB., BKP.,CATr.,ACPA</strong>
                    </div>
                    <p>{{ $mou->type === 'kkp' ? 'PIMPINAN KKP ANTIN OKFITASARI' : 'DIREKTUR PT AGHNIA OASIS KONSULTINDO' }}</p>
                </div>
            </div>
        </div>

        <script type="text/php">
            if (isset($pdf)) {
                $pdf->page_script('
                    $w = $pdf->get_width();
                    $h = $pdf->get_height();
                    $img_h = 90;
                    $y_pos = $h - $img_h + 10;
                    
                    if ($PAGE_NUM < $PAGE_COUNT) {
                        $img_path = public_path("images/footer-v1.png");
                    } else {
                        $img_path = public_path("images/footer-v3.png");
                    }
                    
                    if (file_exists($img_path)) {
                        $pdf->image($img_path, 0, $y_pos, $w, $img_h);
                    }
                ');
            }
        </script>
    </div>
</body>

</html>
