<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Perjanjian Kerja Restitusi PPN -
        {{ $mou->type === 'pt' ? 'PT Aghnia Oasis Konsultindo' : 'KKP Antin Okfitasari' }}</title>
    <style>
        /* Define Page Size and Margins */
        @page {
            size: A4;
            margin: 200px 0px 100px 0px;
            /* Top, Right, Bottom, Left */
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
            margin: 0;
            padding: 0 40px;
            /* Content padding */
        }

        /* Fixed Header */
        header {
            position: fixed;
            top: -220px;
            /* Adjusted based on new margin */
            left: 0;
            right: 0;
            height: 150px;
            text-align: center;
        }

        header img {
            width: 100%;
            height: auto;
        }

        /* Content Styling */
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
        }

        .document-number {
            font-weight: bold;
            text-align: center;
            margin-bottom: 20px;
        }

        .section {
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 14px;
            color: #005e8a;
            border-bottom: 2px solid #005e8a;
            padding-bottom: 5px;
            margin-bottom: 10px;
            text-transform: uppercase;
            font-weight: bold;
        }

        /* Parties Section specific to this file */
        .parties {
            margin-bottom: 20px;
        }

        .party {
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        /* Data Table */
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

        /* Signatures */
        .signatures {
            margin-top: 30px;
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
            margin-top: 60px;
            border-bottom: 1px solid #000;
            margin-bottom: 5px;
        }

        /* List styling to match original */
        ol,
        ul {
            margin-top: 0;
            padding-left: 20px;
        }

        li {
            margin-bottom: 5px;
        }

        /* Watermark */
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
        @if ($mou->type === 'pt')
            <img src="{{ public_path('images/header.png') }}" alt="Header">
        @else
            <img src="{{ public_path('images/header-kkp.jpg') }}" alt="Header">
        @endif
    </header>

    <!-- Watermark -->
    <img src="{{ public_path('images/background.png') }}" class="watermark">

    <!-- Content -->
    <div class="container">
        <!-- Header Content -->
        <div class="section">
            <div class="document-title">Surat Perjanjian Kerja</div>
            <div class="document-subtitle">Supervisi Kewajiban Perpajakan Tahun
                {{ $mou->tahun_pajak ?? \Carbon\Carbon::parse($mou->end_date)->locale('id')->translatedFormat('Y') }}
            </div>
            <div class="document-number">NO: {{ $mou->mou_number }}</div>
        </div>

        <div class="section">
            <p>Kepada yang terhormat,</p>

            <div class="parties">
                <div class="party">
                    <p style="margin:0;"><strong>Bp / Ibu {{ $mou->client->owner_name ?? '-' }}</strong></p>
                    <p style="margin:0;"><strong>{{ $mou->client->position ?? '-' }}</strong> </p>
                    <p style="margin:0;"><strong>{{ $mou->client->company_name ?? '-' }}</strong></p>
                </div>

                <div class="party">
                    <p style="margin:0;">D.a. {{ $mou->client->address ?? '-' }}</p>
                </div>

                <div class="party">
                    <p style="margin:0;"><strong>PERJANJIAN KERJASAMA PERPAJAKAN/PERIKATAN JASA KONSULTASI PERPAJAKAN
                            ATAS JASA PENGURUSAN RESTITUSI PPN TAHUN
                            {{ $mou->tahun_pajak ?? \Carbon\Carbon::parse($mou->end_date)->locale('id')->translatedFormat('Y') }}</strong>
                    </p>
                </div>

                <div class="party">
                    <p style="margin:0;">Dengan hormat,</p>
                </div>

                <div class="party">
                    <p style="margin:0;">Kami ucapkan terima kasih atas kesempatan yang Bapak berikan kepada kami.
                        Bersama ini kami sampaikan PERJANJIAN KERJASAMA untuk perikatan jasa
                        konsultasi perpajakan melalui Restitusi atas nama
                        {{ $mou->client->company_name }} untuk tahun pajak
                        {{ $mou->tahun_pajak ?? \Carbon\Carbon::parse($mou->end_date)->locale('id')->translatedFormat('Y') }},
                        dengan
                        pokok-pokok sebagai berikut:</p>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Tujuan dan Ruang Lingkup</div>
            <p>Tujuan surat perjanjian atas jasa pengurusan restitusi ini adalah, Pihak Kedua dapat
                membantu Pihak Pertama dalam penyelesaian restitusi PPN.</p>
            <p>Ruang lingkup surat perikatan jasa ini meliputi kegiatan untuk melakukan pekerjaan
                supervisi kewajiban perpajakan Pihak Pertama, berdasarkan data – data yang kami
                terima dari Pihak Pertama yang dapat dipertanggung jawabkan sesuai dengan peraturan
                perundang - undangan dan ketentuan umum perpajakan yang berlaku di Indonesia.</p>
        </div>

        <div class="section">
            <div class="section-title">Prosedur Pelaksanaan</div>
            <p>Untuk kelancaran dan dapat dilaksanakannya pekerjaan jasa tersebut di atas, maka
                pihak Pertama wajib memberikan informasi, data/dokumen-dokumen secara tertulis atau
                melalui email yang diperlukan oleh pihak Kedua baik berupa rekap maupun bukti
                transaksi. Data diberikan oleh pihak Pertama sendiri atau pegawai Pihak Pertama yang
                telah mendapatkan wewenang dari Pihak Pertama, untuk mewakili Pihak Pertama dalam
                hal pemberian data/dokumen yang diperlukan oleh Pihak kedua.</p>
            <p>Apabila data/dokumen-dokumen yang dimaksud tidak tersedia sebagaimana mestinya atau
                sengaja tidak diberikan oleh pihak Pertama kepada pihak Kedua, maka pihak Kedua
                tidak bertanggung jawab atas tidak terlaksanannya tugas atas data yang tidak
                disediakan tersebut.</p>

            <h3>Rincian Pekerjaan:</h3>
            <ol>
                <li>Penyusunan Data untuk Restitusi PPN tahun
                    {{ $mou->tahun_pajak ?? \Carbon\Carbon::parse($mou->end_date)->locale('id')->translatedFormat('Y') }}.
                </li>
                <li>Pendampingan proses pencairan Restitusi PPN
                    {{ $mou->tahun_pajak ?? \Carbon\Carbon::parse($mou->end_date)->locale('id')->translatedFormat('Y') }}.
                </li>
                <li>Konsultasi Perpajakan terkait restitusi PPN tahun pajak
                    {{ $mou->tahun_pajak ?? \Carbon\Carbon::parse($mou->end_date)->locale('id')->translatedFormat('Y') }}.
                </li>
            </ol>

            <div style="page-break-inside: avoid;">
                <h3>Prosedur Pelaksanaan:</h3>
                <p>Pihak Kedua akan melaksanakan pekerjaan tersebut dengan menerapkan beberapa prosedur
                    antara lain:</p>
                <ol>
                    <li>Penerapan Prinsip Mengenal Pengguna Jasa dan Pemahaman Bisnis Klien.</li>
                    <li>Penerapan Pemahaman SOP Perusahaan terkait penanggung jawab dokumen informasi
                        keuangan/perpajakan.</li>
                    <li>Penerapan kewajiban perpajakan klien yang sudah ada.</li>
                    <li>Review dokumen terkait penugasan.</li>
                </ol>
            </div>

            <p>Pihak Kedua tidak bertanggung jawab atas ketidaksesuaian data/dokumen yang diberikan
                oleh pihak Pertama dengan kondisi riil maupun kondisi temuan data dari pihak KPP
                atas kewajiban perpajakan TAHUN
                {{ $mou->tahun_pajak ?? \Carbon\Carbon::parse($mou->end_date)->locale('id')->translatedFormat('Y') }}
                yang bertentangan dengan ketentuan hukum, serta ketentuan dan peraturan perpajakan.
                Pihak Kedua juga dibebaskan dari segala tuntutan hukum atas penyalahgunaan
                data/dokumen/laporan oleh pihak ketiga maupun informasi yang tidak lengkap yang
                diperoleh dari pihak Pertama.
            </p>
        </div>

        <div class="section" style="page-break-inside: avoid;">
            <div class="section-title">Fee Jasa Pekerjaan</div>
            <p>Jasa Profesional yang kami bebankan untuk pekerjaan supervisi laporan keuangan
                seperti tersebut di atas adalah sebagai berikut:</p>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>NO</th>
                        <th>JENIS PEKERJAAN</th>
                        <th>QTY</th>
                        <th>SATUAN</th>
                        <th>HARGA</th>
                        <th>TOTAL</th>
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
                            <td style="text-align: right;">Rp
                                {{ number_format($cost->amount, 0, ',', '.') }}</td>
                            <td style="text-align: right;">Rp
                                {{ number_format($cost->total_amount, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <p><em>Fee belum termasuk PPh yang harus dipotong.</em></p>
        </div>

        <div class="section">
            <div class="section-title">Jangka Waktu</div>
            <p>Jangka waktu pelaksanaan pekerjaan adalah sejak perikatan kerjasama ini
                ditandatangani oleh kedua belah pihak, sampai dengan cairnya restitusi</p>
        </div>

        <div class="section">
            <div class="section-title">Penarikan Diri</div>
            <p>Pihak Kedua akan menarik diri dari penugasan ini bilamana terdapat perbedaan yang
                sangat prinsipil dan tidak terselesaikan dengan Pihak Pertama yang berkaitan dengan
                Pihak Pertama atau hal-hal yang menyebabkan sikap independen Pihak Kedua sebagai
                Perusahaan di bidang akuntansi dan perpajakan tidak dapat dipertahankan. Jika hal
                tersebut terjadi, Pihak Pertama membebaskan Pihak kedua dari segala tuntutan apapun,
                termasuk tuntutan untuk mengembalikan bagian fee pekerjaan yang telah diterima oleh
                Pihak Kedua.</p>
        </div>

        <div class="section">
            <div class="section-title">Lain-Lain</div>
            <p>Lingkup pekerjaan hanya terkait dengan segala jasa pengurusan restitusi PPN tahun
                pajak
                {{ $mou->tahun_pajak ?? \Carbon\Carbon::parse($mou->end_date)->locale('id')->translatedFormat('Y') }}
                sebagaimana disebut dalam prosedur pelaksanaan. Adapun jika terdapat pekerjaan di
                luar rincian pekerjaan, <strong>akan di kenakan Fee tersendiri dan dibuatkan
                    kontrak secara tertulis dan terpisah dari surat perjanjian ini.</strong></p>
        </div>

        <div class="section" style="page-break-inside: avoid;">
            <div class="section-title">Penutup</div>
            <p>Bukti Persetujuan Pihak Pertama mengenai hal-hal tersebut di atas dengan
                menandatangani duplikat surat ini dan mengembalikannya kepada Pihak Kedua.</p>
            <p>Ditandatangani di: {{ $mou->client->city ?? 'Sukoharjo' }}</p>
            <p>Pada tanggal {{ \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y') }}
            </p>

            <div class="signatures">
                <div class="signature-box">
                    <p>Pihak Pertama</p>
                    <div class="signature-line" style="border:none; height: 50px;"></div>
                    <div
                        style="border-bottom: 1px solid black; display: inline-block; min-width: 150px; padding-bottom: 2px;">
                        {{ $mou->client->owner_name ?? '-' }}</div>
                    <p>{{ $mou->client->owner_role ?: 'OWNER' }}</p>
                </div>

                <div class="signature-spacer"></div>

                <div class="signature-box">
                    <p>Pihak Kedua</p>
                    <div class="signature-line" style="border:none; height: 50px;"></div>
                    <div
                        style="border-bottom: 1px solid black; display: inline-block; min-width: 150px; padding-bottom: 2px;">
                        ANTIN OKFITASARI, SE.,Msi., Ak., CA.AB.,
                        BKP.,CATr.ACPA</div>
                    <p>DIREKTUR PT AGHNIA OASIS KONSULTINDO (RAFATAX)</p>
                </div>
            </div>
        </div>

        <script type="text/php">
            if (isset($pdf)) {
                $pdf->page_script('
                    $w = $pdf->get_width();
                    $h = $pdf->get_height();
                    
                    // Footer settings
                    $img_h = 90; // Height of footer image
                    $y_pos = $h - $img_h + 10; // Position near bottom
                    
                    $type = "{{ $mou->type }}";
                    
                    if ($type == "pt") {
                        if ($PAGE_NUM < $PAGE_COUNT) {
                            $img_path = public_path("images/footer-v1.png");
                        } else {
                            $img_path = public_path("images/footer-v3.png");
                        }
                    } else {
                         $img_path = public_path("images/footer-kkp.svg");
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
