<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Surat Kuasa Khusus & Surat Pernyataan - {{ $poa->wp_badan_nama ?? $poa->pemberi_nama ?? 'Dokumen Kuasa' }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 20mm 25mm 20mm 25mm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11.5pt;
            line-height: 1.4;
            color: #000;
            margin: 0;
            padding: 0;
        }

        .text-center {
            text-align: center;
        }

        .text-justify {
            text-align: justify;
        }

        .text-right {
            text-align: right;
        }

        .font-bold {
            font-weight: bold;
        }

        .uppercase {
            text-transform: uppercase;
        }

        .doc-title {
            font-size: 12pt;
            font-weight: bold;
            text-align: center;
            margin-bottom: 2px;
            text-transform: uppercase;
        }

        .doc-subtitle {
            font-size: 11.5pt;
            text-align: center;
            margin-top: 0;
            margin-bottom: 16px;
        }

        p {
            margin: 6px 0;
            text-align: justify;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 4px 0;
        }

        .data-table td {
            padding: 2px 0;
            vertical-align: top;
            font-size: 11.5pt;
        }

        .checkbox-box {
            display: inline-block;
            width: 13px;
            height: 13px;
            border: 1px solid #000;
            text-align: center;
            line-height: 12px;
            font-size: 10px;
            font-weight: bold;
            margin-right: 4px;
            vertical-align: middle;
        }

        .indented-block {
            margin-left: 20px;
        }

        ol.kuasa-list {
            margin: 4px 0 6px 20px;
            padding-left: 5px;
        }

        ol.kuasa-list li {
            margin-bottom: 3px;
            text-align: justify;
        }

        .signatures-table {
            width: 100%;
            margin-top: 15px;
            border-collapse: collapse;
        }

        .signatures-table td {
            vertical-align: top;
            width: 50%;
            text-align: center;
            font-size: 11.5pt;
        }

        .meterai-box {
            width: 85px;
            height: 48px;
            border: 1px dashed #666;
            text-align: center;
            line-height: 48px;
            font-size: 9pt;
            color: #555;
            margin: 8px auto;
        }

        .footnotes {
            margin-top: 20px;
            font-size: 9pt;
            font-style: italic;
            border-top: 0.5px solid #ccc;
            padding-top: 4px;
        }

        .page-break {
            page-break-after: always;
            page-break-inside: avoid;
            clear: both;
        }
    </style>
</head>
<body>

    {{-- ========================================================= --}}
    {{-- HALAMAN 1: SURAT KUASA KHUSUS                             --}}
    {{-- ========================================================= --}}
    <div class="page-container">
        <div class="doc-title">
            SURAT KUASA KHUSUS WAJIB PAJAK {{ strtoupper($poa->jenis_wp ?? 'BADAN') }}
        </div>
        <div class="doc-subtitle">
            Nomor {{ $poa->nomor_surat_kuasa ?? '........................................' }} &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Tanggal {{ $poa->tanggal_surat_kuasa ? \Carbon\Carbon::parse($poa->tanggal_surat_kuasa)->locale('id')->translatedFormat('d F Y') : '........................................' }}
        </div>

        <p>Yang bertanda tangan di bawah ini:</p>
        <table class="data-table" style="margin-left: 5px;">
            <tr>
                <td style="width: 150px;">nama</td>
                <td style="width: 15px;">:</td>
                <td>{{ $poa->pemberi_nama ?? '..................................................................' }}</td>
            </tr>
            <tr>
                <td>NPWP</td>
                <td>:</td>
                <td>{{ $poa->pemberi_npwp ?? '..................................................................' }}</td>
            </tr>
            <tr>
                <td>jabatan</td>
                <td>:</td>
                <td>{{ $poa->pemberi_jabatan ?? '..................................................................' }}</td>
            </tr>
            <tr>
                <td colspan="3" style="padding-top: 4px;">bertindak selaku: *)</td>
            </tr>
            <tr>
                <td colspan="3" style="padding-left: 10px;">
                    <span class="checkbox-box">{!! ($poa->bertindak_selaku ?? '') === 'wajib_pajak' ? '&#10003;' : '&nbsp;' !!}</span> Wajib Pajak
                </td>
            </tr>
            <tr>
                <td colspan="3" style="padding-left: 10px;">
                    <span class="checkbox-box">{!! ($poa->bertindak_selaku ?? 'wakil_wajib_pajak') === 'wakil_wajib_pajak' ? '&#10003;' : '&nbsp;' !!}</span> Wakil Wajib Pajak:
                </td>
            </tr>
            <tr>
                <td style="padding-left: 35px;">nama</td>
                <td>:</td>
                <td>{{ $poa->wp_badan_nama ?? '..................................................................' }}</td>
            </tr>
            <tr>
                <td style="padding-left: 35px;">NPWP</td>
                <td>:</td>
                <td>{{ $poa->wp_badan_npwp ?? '..................................................................' }}</td>
            </tr>
        </table>

        <p style="margin-top: 6px;">dengan ini memberikan kuasa khusus kepada: *)</p>
        <table class="data-table" style="margin-left: 10px;">
            <tr>
                <td colspan="3">
                    <span class="checkbox-box">{!! ($poa->kuasa_kategori ?? 'konsultan_pajak') === 'konsultan_pajak' ? '&#10003;' : '&nbsp;' !!}</span> Konsultan Pajak
                </td>
            </tr>
            <tr>
                <td colspan="3">
                    <span class="checkbox-box">{!! ($poa->kuasa_kategori ?? '') === 'pihak_lain' ? '&#10003;' : '&nbsp;' !!}</span> Pihak lain
                </td>
            </tr>
            <tr>
                <td colspan="3">
                    <span class="checkbox-box">{!! ($poa->kuasa_kategori ?? '') === 'keluarga' ? '&#10003;' : '&nbsp;' !!}</span> keluarga Wajib Pajak,
                </td>
            </tr>
            <tr>
                <td style="width: 220px; padding-left: 25px;">nama</td>
                <td style="width: 15px;">:</td>
                <td>{{ $poa->penerima_nama ?? '..................................................................' }}</td>
            </tr>
            <tr>
                <td style="padding-left: 25px;">NPWP</td>
                <td>:</td>
                <td>{{ $poa->penerima_npwp ?? '..................................................................' }}</td>
            </tr>
            <tr>
                <td style="padding-left: 25px;">Nomor Izin Konsultan Pajak/SKT</td>
                <td>:</td>
                <td>{{ $poa->penerima_izin_no ?? '..................................................................' }}</td>
            </tr>
            <tr>
                <td style="padding-left: 25px;">status hubungan keluarga</td>
                <td>:</td>
                <td>{{ $poa->penerima_status_keluarga ?? '-' }}</td>
            </tr>
        </table>

        <p style="margin-top: 6px;">
            untuk melaksanakan hak dan/atau memenuhi kewajiban perpajakan terkait
            <strong>{{ $poa->kewajiban_terkait ?? '............................................................' }}</strong>
            berkenaan dengan jenis pajak
            <strong>{{ $poa->jenis_pajak ?? '............................................................' }}</strong>
            {{ $poa->masa_pajak_pilihan ?? 'Masa Pajak/Bagian Tahun Pajak/Tahun Pajak' }} **)
            <strong>{{ $poa->masa_tahun_pajak ?? '........................' }}</strong>.
        </p>

        <p>
            Surat Kuasa Khusus ini berlaku mulai tanggal
            <strong>{{ $poa->tanggal_mulai ? \Carbon\Carbon::parse($poa->tanggal_mulai)->locale('id')->translatedFormat('d F Y') : '....' }}</strong>
            sampai dengan tanggal
            <strong>{{ $poa->tanggal_selesai ? \Carbon\Carbon::parse($poa->tanggal_selesai)->locale('id')->translatedFormat('d F Y') : '....' }}</strong>.
        </p>

        <p>Untuk keperluan tersebut, penerima kuasa dikuasakan untuk:</p>
        <ol type="a" class="kuasa-list">
            @if(!empty($poa->rincian_kuasa) && is_array($poa->rincian_kuasa))
                @foreach($poa->rincian_kuasa as $index => $rincian)
                    @php
                        $text = is_array($rincian) ? ($rincian['text'] ?? '') : $rincian;
                        $isLast = $loop->last;
                        $isSecondLast = $loop->count > 1 && $loop->iteration === ($loop->count - 1);
                    @endphp
                    @if(trim($text) !== '')
                        <li>{{ rtrim($text, ';.,') }}{{ $isSecondLast ? '; dan/atau' : ($isLast ? '.' : ';') }}</li>
                    @endif
                @endforeach
            @else
                <li>menghadap dan melakukan pembahasan dengan pejabat atau pegawai Direktorat Jenderal Pajak;</li>
                <li>menyampaikan klarifikasi, tanggapan, jawaban baik secara lisan maupun tertulis, atau dokumen kepada pejabat atau pegawai Direktorat Jenderal Pajak;</li>
                <li>menandatangani Surat Pemberitahuan Tahunan atau Surat Pemberitahuan Masa;</li>
                <li>menandatangani surat-surat, formulir-formulir, atau dokumen perpajakan lainnya yang diperlukan dalam pelaksanaan hak dan/atau pemenuhan kewajiban perpajakan yang dikuasakan kepadanya.</li>
            @endif
        </ol>

        <p>
            Dengan ini, pemberi kuasa menyatakan bahwa dalam hal hak dan/atau kewajiban perpajakan yang dikuasakan dilakukan secara elektronik, penerima kuasa berhak untuk mengakses akun elektronik pemberi kuasa pada sistem elektronik Direktorat Jenderal Pajak.
        </p>

        <p>
            Demikian surat kuasa khusus ini dibuat untuk dipergunakan sebagaimana mestinya.
        </p>

        <table class="signatures-table">
            <tr>
                <td>Penerima kuasa,</td>
                <td>Pemberi kuasa,</td>
            </tr>
            <tr>
                <td style="height: 60px;"></td>
                <td>
                    <div class="meterai-box">Meterai</div>
                </td>
            </tr>
            <tr>
                <td>
                    <strong>{{ $poa->penerima_nama ?? '..................................................' }}</strong>
                </td>
                <td>
                    <strong>{{ $poa->pemberi_nama ?? '..................................................' }}</strong>
                </td>
            </tr>
        </table>

        <div class="footnotes">
            *) Beri tanda " &radic; " pada kolom yang sesuai.<br>
            **) Pilih salah satu yang sesuai.
        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- PAGE BREAK ANTARA SURAT KUASA & SURAT PERNYATAAN          --}}
    {{-- ========================================================= --}}
    <div class="page-break"></div>

    {{-- ========================================================= --}}
    {{-- HALAMAN 2: SURAT PERNYATAAN                               --}}
    {{-- ========================================================= --}}
    <div class="page-container">
        <div class="doc-title" style="margin-top: 15px;">
            SURAT PERNYATAAN
        </div>
        <div class="doc-subtitle">
            Nomor {{ $poa->pernyataan_nomor ?? $poa->nomor_surat_kuasa ?? '........................................' }} &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Tanggal {{ $poa->pernyataan_tanggal ? \Carbon\Carbon::parse($poa->pernyataan_tanggal)->locale('id')->translatedFormat('d F Y') : ($poa->tanggal_surat_kuasa ? \Carbon\Carbon::parse($poa->tanggal_surat_kuasa)->locale('id')->translatedFormat('d F Y') : '........................................') }}
        </div>

        <p>Yang bertanda tangan di bawah ini:</p>
        <table class="data-table" style="margin-left: 5px;">
            <tr>
                <td style="width: 120px;">nama</td>
                <td style="width: 15px;">:</td>
                <td>{{ $poa->pemberi_nama ?? '..................................................................' }}</td>
            </tr>
            <tr>
                <td>NPWP</td>
                <td>:</td>
                <td>{{ $poa->pemberi_npwp ?? ($poa->wp_badan_npwp ?? '..................................................................') }}</td>
            </tr>
            <tr>
                <td>alamat</td>
                <td>:</td>
                <td>{{ $poa->pernyataan_pemberi_alamat ?? ($poa->caseProject?->client?->address ?? '..................................................................') }}</td>
            </tr>
        </table>

        <p style="margin-top: 16px;">
            dengan ini menyatakan dengan sebenarnya bahwa penerima kuasa
        </p>

        <table class="data-table" style="margin-left: 5px;">
            <tr>
                <td style="width: 120px;">nama</td>
                <td style="width: 15px;">:</td>
                <td>{{ $poa->penerima_nama ?? '..................................................................' }}</td>
            </tr>
            <tr>
                <td>NPWP</td>
                <td>:</td>
                <td>{{ $poa->penerima_npwp ?? '..................................................................' }}</td>
            </tr>
            <tr>
                <td>alamat</td>
                <td>:</td>
                <td>{{ $poa->pernyataan_penerima_alamat ?? 'Dk. Nampan RT 02 RW 01, Madegondo, Grogol, Sukoharjo' }}</td>
            </tr>
            <tr>
                <td>merupakan</td>
                <td>:</td>
                <td>{{ $poa->pernyataan_status_hubungan ?? 'Konsultan Pajak yang memenuhi persyaratan sesuai ketentuan peraturan perundang-undangan perpajakan' }}.</td>
            </tr>
        </table>

        <p style="margin-top: 20px; line-height: 1.5;">
            Demikian Surat Pernyataan ini dibuat sesuai dengan keadaan yang sebenarnya. Apabila di kemudian hari ditemukan bahwa informasi dalam pernyataan ini tidak benar, saya bersedia menerima segala konsekuensi hukum sesuai dengan ketentuan peraturan perundang-undangan yang berlaku.
        </p>

        <table class="signatures-table" style="margin-top: 40px;">
            <tr>
                <td style="width: 50%;"></td>
                <td style="width: 50%;">
                    Pemberi kuasa,
                    <div class="meterai-box" style="margin: 15px auto;">Meterai</div>
                    <strong>{{ $poa->pemberi_nama ?? '..................................................' }}</strong>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
