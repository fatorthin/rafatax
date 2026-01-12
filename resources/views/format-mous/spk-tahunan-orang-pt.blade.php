<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Perjanjian Kerja - PT Aghnia Oasis Konsultindo</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            height: 100%;
            /* Diperlukan agar tabel bisa mengisi tinggi */
        }

        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            /* Hapus margin default body */
            /* background: url('{{ asset('images/background.png') }}') no-repeat center center fixed; */
            /* DIHAPUS */
            /* background-size: cover; */
            /* DIHAPUS */
        }

        .page-table {
            width: 100%;
            height: 100%;
            border-collapse: collapse;
        }

        .page-table>thead>tr>td,
        .page-table>tfoot>tr>td {
            padding: 0;
            /* Hapus padding jika gambar mengisi penuh */
        }

        .page-table>tbody>tr>td {
            vertical-align: top;
            /* Konten mulai dari atas */
            position: relative;
            /* Agar z-index container bisa bekerja di atas watermark body */
        }

        .header-img-cell img,
        .footer-img-cell img {
            width: 100%;
            display: block;
            height: auto;
            /* Biarkan tinggi menyesuaikan gambar */
        }

        .container {
            max-width: 800px;
            margin: 20px auto;
            /* Margin atas bawah untuk jarak dari header/footer tabel */
            padding: 20px;
            background-color: rgba(255, 255, 255, 0.97);
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            position: relative;
            /* Untuk watermark jika masih ada */
            /* min-height: auto; Tidak perlu min-height jika di dalam tabel body */
            border-left: 8px solid #005e8a;
            border-right: 8px solid #005e8a;
            z-index: 20;
            /* Jika ada elemen lain */
        }

        /* == STYLING KONTEN LAMA ANDA == */
        /* (Sebagian besar styling konten lama Anda (.header, .document-title, dll) */
        /*  kemungkinan masih bisa dipakai di dalam .container, tapi perlu dicek) */
        .header {
            text-align: center;
            position: relative;
        }

        .header::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 0;
            width: 100%;
            height: 10px;
            background-color: #f9f9f9;
        }

        .logo {
            max-width: 150px;
            margin-bottom: 15px;
        }

        .document-title {
            font-size: 18px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: bold;
        }

        .document-number {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .content {
            padding: 20px;
        }

        .section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 20px;
            color: #005e8a;
            border-bottom: 2px solid #005e8a;
            padding-bottom: 10px;
            margin-bottom: 15px;
            text-transform: uppercase;
        }

        .parties {
            margin-bottom: 20px;
        }

        .party {
            margin-bottom: 15px;
        }

        .party-title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        ul,
        ol {
            margin-left: 20px;
            margin-bottom: 15px;
        }

        li {
            margin-bottom: 10px;
        }

        .divider {
            height: 2px;
            background-color: #005e8a;
            margin: 30px 0;
        }

        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
        }

        .signature-box {
            width: 45%;
            text-align: center;
        }

        .signature-line {
            height: 1px;
            background-color: #333;
            width: 100%;
            margin: 60px 0 10px;
        }

        .signature-name {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .signature-title {
            font-style: italic;
        }

        /* .footer (CSS untuk text footer lama) mungkin tidak relevan jika footer hanya gambar */

        table {
            /* Ini untuk tabel di dalam konten, bukan .page-table */
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        table,
        th,
        td {
            border: 1px solid #ddd;
        }

        th,
        td {
            padding: 12px;
            text-align: justify;
        }

        th {
            background-color: #f2f2f2;
            color: #333;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .parties-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
            margin-bottom: 20px;
        }

        .parties-table tr {
            background-color: transparent !important;
        }

        .parties-table td {
            border: none;
            padding: 5px;
            vertical-align: top;
        }

        .watermark {
            /* Style watermark untuk mode normal DAN sebagai dasar untuk print */
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.5;
            /* NAIKKAN OPACITY .watermark untuk tes */
            z-index: 1;
            /* Di belakang konten */
            pointer-events: none;
            /* Tidak bisa diklik */
        }

        .watermark img {
            max-width: 200px;
            /* Ukuran lebar maksimal watermark */
            /* width: 100vw; */
            /* DIHAPUS - agar max-width bekerja */
            opacity: 1;
            /* NAIKKAN OPACITY gambar di dalam .watermark juga untuk tes */
            display: block;
            /* Menghilangkan spasi ekstra di bawah gambar */
        }

        /* == AKHIR STYLING KONTEN LAMA ANDA == */

        @media (max-width: 900px) {

            /* Styling responsif untuk .container dan mungkin ukuran font */
            .container {
                max-width: 98vw;
                padding: 5vw 2vw;
                margin: 10px auto;
                /* Sesuaikan margin untuk mobile */
                border-left: none;
                border-right: none;
            }

            /* Anda mungkin perlu menyesuaikan .header-img-cell img dan .footer-img-cell img juga */
        }

        @media print {
            html {
                height: 100%;
                overflow: visible;
            }

            body {
                background: none !important;
                padding-top: 0;
                padding-bottom: 80px;
                height: auto;
                overflow: visible;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                position: relative;
                z-index: 0;
            }

            /* Komentari/Hapus .header-img dan .footer-img display:none jika ingin header/footer tabel terlihat */
            /* .header-img {
                display: none;
            }
            .footer-img {
                display: none;
            } */
            .page-table {
                width: 100%;
                /* Hapus height: 100% untuk print agar bisa multi-page */
            }

            .page-table>thead,
            .page-table>tfoot {
                display: table-header-group;
                /* Agar header berulang */
            }

            .page-table>thead td,
            .page-table>tfoot td {
                /* Pastikan tidak ada padding/margin tak terduga di sel header/footer tabel */
                padding: 0;
                margin: 0;
            }

            .page-table>thead td {
                /* Jarak antara gambar header dan konten di bawahnya saat print - DIHAPUS DARI SINI */
            }

            .page-table>tfoot {
                display: table-footer-group;
                /* Agar footer berulang */
            }

            .page-table>tfoot td {
                /* Tambahkan styling di sini */
                position: relative;
                z-index: 5 !important;
                /* Pastikan footer di atas watermark */
            }

            /* Pastikan gambar di header/footer cell tidak punya margin/padding yang aneh */
            .page-table>thead .header-img-cell img {
                /* Target header image specifically */
                margin-bottom: 20px;
                /* Jarak setelah gambar header saat print */
            }

            .header-img-cell img,
            .footer-img-cell img {
                margin: 0;
                padding: 0;
            }

            .page-table>tbody>tr>td {
                /* HAPUS padding-top dari sini */
                /* padding-top: 20px; */
                /* Jarak antara header dan konten - DIHAPUS */
            }

            .container {
                box-shadow: none;
                border: none;
                background-color: transparent !important;
                /* Konten transparan agar watermark di body terlihat */
                margin: 0 auto;
                padding: 0px;
                /* Padding internal container dasar */
                width: 100% !important;
                max-width: 100% !important;
                position: relative;
                /* Untuk z-index jika perlu */
                z-index: 10;
                /* Konten di atas watermark body */
            }

            .section,
            .party,
            .signatures,
            table,
            .party p,
            .signatures p {
                page-break-inside: avoid;
            }

            h1,
            h2,
            h3,
            p {
                page-break-after: avoid;
            }

            .watermark {
                /* Style watermark KHUSUS untuk print - pastikan fixed & z-index benar */
                position: fixed !important;
                /* Paksa fixed */
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                opacity: 0.5 !important;
                /* NAIKKAN OPACITY & PAKSA PENTING */
                z-index: 1 !important;
                /* Pastikan di belakang konten & PAKSA PENTING */
                pointer-events: none;
            }

            .watermark img {
                max-width: 200px;
                /* Pastikan ukuran sama dengan mode normal atau sesuaikan untuk print */
                opacity: 1 !important;
                /* NAIKKAN OPACITY & PAKSA PENTING */
                display: block;
            }
        }
    </style>

    @if (isset($printMode) && $printMode)
        <script>
            window.onload = function() {
                // Delay print dialog sedikit untuk memastikan semua aset sudah terload
                setTimeout(function() {
                    window.print();
                }, 500);
            }
        </script>
    @endif
</head>

<body>
    <div class="watermark">
        <img src="{{ asset('images/background.png') }}" alt="Watermark Background">
        <!-- <img src="/images/background.png" alt="Watermark Background"> Coba path ini jika asset() bermasalah di print -->
    </div>

    <table class="page-table">
        <thead>
            <tr>
                <td class="header-img-cell">
                    <img src="{{ asset('images/header.png') }}" alt="Header">
                </td>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <td class="footer-img-cell">
                    <img src="{{ asset('images/footer-v1.png') }}" alt="Footer">
                </td>
            </tr>
        </tfoot>
        <tbody>
            <tr>
                <td>
                    <div class="container">
                        <!-- <div class="watermark"> DIHAPUS DARI SINI -->
                        <!--     <img src="{{ asset('images/background.png') }}" alt="Watermark Background"> -->
                        <!-- </div> -->

                        <!-- Header KONTEN (BUKAN GAMBAR HEADER HALAMAN) -->
                        <header class="header">
                            <p class="document-title">Surat Perjanjian Kerja</p>
                            <p class="document-subtitle">Supervisi Kewajiban Perpajakan Tahun
                                {{ $mou->tahun_pajak ?? \Carbon\Carbon::parse($mou->end_date)->locale('id')->translatedFormat('Y') }}
                            </p>
                            <p class="document-number">NO: {{ $mou->mou_number }}</p>
                        </header>

                        <!-- Main Content -->
                        <div class="content">
                            <div class="section">
                                <p>Yang bertanda tangan di bawah ini masing-masing:</p>

                                <table class="parties-table">
                                    <tr>
                                        <td style="width: 20px; font-weight: bold;">1.</td>
                                        <td style="width: 100px; font-weight: bold;">Nama</td>
                                        <td style="width: 10px;">:</td>
                                        <td>{{ $mou->client->owner_name ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td style="font-weight: bold;">Jabatan</td>
                                        <td>:</td>
                                        <td>{{ $mou->client->owner_role ?: 'OWNER' }}</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td style="font-weight: bold;">Alamat</td>
                                        <td>:</td>
                                        <td>{{ $mou->client->address ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td colspan="3">dan selanjutnya disebut <strong>PIHAK PERTAMA</strong></td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" style="height: 10px;"></td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: bold;">2.</td>
                                        <td style="font-weight: bold;">Nama</td>
                                        <td>:</td>
                                        <td>ANTIN OKFITASARI</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td style="font-weight: bold;">Jabatan</td>
                                        <td>:</td>
                                        <td>DIREKTUR</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td style="font-weight: bold;">Alamat</td>
                                        <td>:</td>
                                        <td>DK NAMPAN RT 01 RW 02 MADEGONDO GROGOL SUKOHARJO</td>
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
                                    pihak Pertama dan pihak Kedua sepakat untuk mengadakan <strong>Perikatan Jasa
                                        Konsultasi Perpajakan Atas Pekerjaan Konsultasi Kewajiban Perpajakan PERORANGAN
                                        Untuk Tahun Yang Berakhir
                                        {{ $mou->tahun_pajak ?? \Carbon\Carbon::parse($mou->end_date)->locale('id')->translatedFormat('Y') }}</strong>,
                                    seperti diatur dalam pasal-pasal Surat Perikatan di bawah ini:</p>
                            </div>

                            <div class="section">
                                <h2 class="section-title">Tujuan dan Ruang Lingkup</h2>
                                <p>Tujuan perikatan jasa supervisi kewajiban perpajakan ini adalah, Pihak Kedua dapat
                                    membantu Pihak Pertama dalam supervisi dan penyusunan kewajiban perpajakan sesuai
                                    dengan UU KUP Perpajakan yang berlaku di Indonesia.</p>
                                <p>Ruang lingkup surat perikatan jasa ini meliputi kegiatan untuk melakukan pekerjaan
                                    supervisi kewajiban perpajakan Pihak Pertama, berdasarkan data – data yang kami
                                    terima dari Pihak Pertama yang dapat dipertanggung jawabkan sesuai dengan peraturan
                                    perundang - undangan dan ketentuan umum perpajakan yang berlaku di Indonesia.</p>
                            </div>

                            <div class="section">
                                <h2 class="section-title">Prosedur Pelaksanaan</h2>
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
                                    <li>Penyusunan SPT Masa dan Tahunan Pajak
                                        {{ $mou->tahun_pajak ?? \Carbon\Carbon::parse($mou->end_date)->locale('id')->translatedFormat('Y') }}.
                                    </li>
                                </ol>

                                <h3>Prosedur Pelaksanaan:</h3>
                                <p>Pihak Kedua akan melaksanakan pekerjaan tersebut dengan menerapkan beberapa prosedur
                                    antara lain:</p>
                                <ol>
                                    <li>Penerapan Prinsip Mengenal Pengguna Jasa dan Pemahaman Bisnis Klien.</li>
                                    <li>Penerapan Pemahaman SOP Perusahaan terkait penanggung jawab dokumen informasi
                                        keuangan/perpajakan.</li>
                                    <li>Penerapan kewajiban perpajakan klien yang sudah ada dan tahun sebelumnya.</li>
                                    <li>Permintaan data informasi terkait perpajakan secara berkala.</li>
                                    <li>Review Pembukuan dan supervisi berkala terkait Akuntansi Perpajakan</li>
                                </ol>

                                <h3>Laporan yang akan diterbitkan:</h3>
                                <ol>
                                    <li>Laporan SPT Tahunan tahun pajak
                                        {{ $mou->tahun_pajak ?? \Carbon\Carbon::parse($mou->end_date)->locale('id')->translatedFormat('Y') }}.
                                    </li>
                                </ol>

                                <p>Pihak Kedua tidak bertanggung jawab atas ketidaksesuaian data/dokumen yang diberikan
                                    oleh pihak Pertama dengan kondisi riil maupun kondisi temuan data dari pihak KPP
                                    atas kewajiban perpajakan TAHUN
                                    {{ $mou->tahun_pajak ?? \Carbon\Carbon::parse($mou->end_date)->locale('id')->translatedFormat('Y') }}
                                    yang bertentangan dengan
                                    ketentuan hukum, serta ketentuan dan peraturan perpajakan. Pihak Kedua juga
                                    dibebaskan dari segala tuntutan hukum atas penyalahgunaan data/dokumen/laporan oleh
                                    pihak ketiga maupun informasi yang tidak lengkap yang diperoleh dari pihak Pertama.
                                </p>
                            </div>

                            <div class="section">
                                <h2 class="section-title">Fee Jasa Pekerjaan</h2>
                                <p>Jasa Profesional yang kami bebankan untuk pekerjaan supervisi laporan keuangan
                                    seperti tersebut di atas adalah sebagai berikut:</p>

                                <table>
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

                                <p><em>Fee belum termasuk PPh yang harus dipotong, dengan rincian pembayaran yang di
                                        sepakati akan di bayarkan setiap bulan berjalan mulai masa pajak masa Januari
                                        {{ \Carbon\Carbon::parse($mou->start_date)->format('Y') }} sampai dengan masa
                                        pajak Desember {{ \Carbon\Carbon::parse($mou->start_date)->format('Y') }}</em>
                                </p>
                            </div>

                            <div class="section">
                                <h2 class="section-title">Jangka Waktu</h2>
                                <p>Jangka waktu pelaksanaan pekerjaan adalah sejak perikatan kerjasama ini
                                    ditandatangani oleh kedua belah pihak, sampai dengan bulan Januari
                                    {{ \Carbon\Carbon::parse($mou->end_date)->addYear()->locale('id')->translatedFormat('Y') }}.
                                </p>
                            </div>

                            <div class="section">
                                <h2 class="section-title">Penarikan Diri</h2>
                                <p>Pihak Kedua akan menarik diri dari penugasan ini bilamana terdapat perbedaan yang
                                    sangat prinsipil dan tidak terselesaikan dengan Pihak Pertama yang berkaitan dengan
                                    Pihak Pertama atau hal-hal yang menyebabkan sikap independen Pihak Kedua sebagai
                                    Perusahaan di bidang akuntansi dan perpajakan tidak dapat dipertahankan. Jika hal
                                    tersebut terjadi, Pihak Pertama membebaskan Pihak kedua dari segala tuntutan apapun,
                                    termasuk tuntutan untuk mengembalikan bagian fee pekerjaan yang telah diterima oleh
                                    Pihak Kedua.</p>
                            </div>

                            <div class="section">
                                <h2 class="section-title">Lain-Lain</h2>
                                <p>Lingkup pekerjaan hanya terkait dengan kewajiban perpajakan tahun pajak 2025
                                    sebagaimana disebut dalam <strong>rincian pekerjaan</strong> dan <strong>tidak
                                        termasuk SP2DK, pemeriksaan, pengurusan restitusi</strong>, <strong>ataupun
                                        pekerjaan yang lain</strong>. Adapun jika terdapat pekerjaan di luar rincian
                                    pekerjaan, <strong>akan di kenakan Fee tersendiri dan dibuatkan kontrak secara
                                        tertulis dan terpisah dari surat perjanjian ini.</strong></p>
                            </div>

                            <div class="section">
                                <h2 class="section-title">Penutup</h2>
                                <p>Bukti Persetujuan Pihak Pertama mengenai hal-hal tersebut di atas dengan
                                    menandatangani duplikat surat ini dan mengembalikannya kepada Pihak Kedua.</p>
                                <p>Ditandatangani di: {{ $mou->client->city ?? 'Sukoharjo' }}</p>
                                <p>Pada tanggal {{ \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y') }}
                                </p>

                                <div class="signatures">
                                    <div class="signature-box">
                                        <p>Pihak Pertama</p>
                                        <br>
                                        <div class="signature-line"></div>
                                        <p class="signature-name">{{ $mou->client->owner_name ?? '-' }}</p>
                                        <p class="signature-title">{{ $mou->client->owner_role ?: 'OWNER' }}</p>
                                    </div>

                                    <div class="signature-box">
                                        <p>Pihak Kedua</p>
                                        <br>
                                        <div class="signature-line"></div>
                                        <p class="signature-name">ANTIN OKFITASARI, SE.,Msi., Ak., CA.AB.,
                                            BKP.,CATr.ACPA</p>
                                        <p class="signature-title">DIREKTUR PT AGHNIA OASIS KONSULTINDO (RAFATAX)</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</body>

</html>
