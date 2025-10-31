# Troubleshooting: Pengiriman PDF Slip Gaji via WAblas

## Masalah yang Ditemukan

### 1. **Log Level Diperbaiki** ✅

-   Sudah diperbaiki: Mengubah `Log::error()` menjadi `Log::info()` di line 146 WablasService
-   Response normal tidak lagi muncul sebagai error di log

### 2. **Potensi Masalah yang Perlu Dicek**

#### A. Konfigurasi WAblas (.env)

Pastikan environment variables sudah diset dengan benar:

```env
WABLAS_TOKEN=your_token_here
WABLAS_SECRET_KEY=your_secret_key_here
WABLAS_BASE_URL=https://texas.wablas.com/api
WABLAS_AUTH_HEADER=concat
```

**Auth Header Options:**

-   `concat`: Format `Authorization: {token}.{secretKey}` (default)
-   `token`: Format `Authorization: {token}`
-   `bearer`: Format `Authorization: Bearer {token}`

#### B. Endpoint API Document

Endpoint yang digunakan: `/send-document`

**Alternatif yang mungkin perlu dicoba:**

-   `/send-document` (current)
-   `/send-file`
-   `/send-pdf`
-   `/v2/send-document`

Cek dokumentasi WAblas API Anda untuk endpoint yang benar.

#### C. Format Phone Number

Sudah ada validasi otomatis:

-   `081234567890` → `6281234567890`
-   Format: 62 + nomor tanpa 0 di depan

#### D. File Size Limit

-   Maximum: 10MB (validasi sudah ada)
-   WhatsApp document limit: 10MB

---

## Cara Debug

### 1. Cek Log Laravel

```bash
tail -f storage/logs/laravel.log
```

Cari log entries:

-   `Starting sendPayslipWithPdf`
-   `Wablas: Mengirim dokumen`
-   `Wablas Document API Response`

### 2. Response HTTP Codes

| Code | Arti              | Solusi                          |
| ---- | ----------------- | ------------------------------- |
| 200  | Berhasil          | ✅ PDF terkirim                 |
| 401  | Unauthorized      | ⚠️ Cek token & secret key       |
| 500  | Server Error      | ⚠️ Device offline / quota habis |
| 404  | Not Found         | ⚠️ Endpoint salah               |
| 413  | Payload Too Large | ⚠️ File terlalu besar           |

### 3. Test Manual via Postman/cURL

```bash
curl -X POST "https://texas.wablas.com/api/send-document" \
  -H "Authorization: YOUR_TOKEN.YOUR_SECRET" \
  -F "phone=628123456789" \
  -F "document=@/path/to/file.pdf" \
  -F "caption=Test Document"
```

### 4. Fallback System (Sudah Implemented)

Jika direct document gagal, sistem otomatis:

1. Menyimpan PDF ke `public/storage/payslips/`
2. Mengirim link download via WhatsApp
3. Link berlaku sampai file dihapus manual

---

## Checklist Debugging

-   [ ] Token & Secret Key benar di `.env`
-   [ ] Base URL benar (default: `https://texas.wablas.com/api`)
-   [ ] Auth header format benar (`concat`, `token`, atau `bearer`)
-   [ ] Device WAblas online dan terkoneksi
-   [ ] Quota WAblas belum habis
-   [ ] Nomor WhatsApp valid dan aktif
-   [ ] File PDF ter-generate dengan benar (cek `storage/app/temp/`)
-   [ ] Ukuran PDF < 10MB
-   [ ] Server bisa akses internet (curl/wget test)
-   [ ] Folder `storage/app/temp/` writeable (permission 755)

---

## Solusi Alternatif

### Jika Terus Gagal:

1. **Gunakan Fallback Manual**

    - PDF akan tersimpan di `public/storage/payslips/`
    - Staff download manual via link

2. **Ubah Metode Kirim**

    - Kirim via email sebagai alternatif
    - Export batch dan kirim manual

3. **Cek dengan Provider WAblas**
    - Kontak support WAblas
    - Minta endpoint dokumentasi terbaru
    - Cek status device & quota

---

## Test Script

Buat file `test-wablas.php` di root project:

```php
<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = new \App\Services\WablasService();

// Test 1: Send Message
echo "Test 1: Kirim Pesan...\n";
$result1 = $service->sendMessage('628123456789', 'Test pesan dari script');
print_r($result1);

// Test 2: Send Document (ganti dengan path PDF yang valid)
echo "\nTest 2: Kirim Dokumen...\n";
$pdfPath = storage_path('app/temp/test.pdf');
$result2 = $service->sendDocument('628123456789', $pdfPath, 'test.pdf', 'Test caption');
print_r($result2);
```

Jalankan: `php test-wablas.php`

---

## Error Messages Umum & Solusinya

| Error Message          | Penyebab              | Solusi                           |
| ---------------------- | --------------------- | -------------------------------- |
| "File tidak ditemukan" | Path PDF salah        | Cek `storage/app/temp/` ada file |
| "File terlalu besar"   | PDF > 10MB            | Compress PDF atau kurangi konten |
| "Error koneksi"        | CURL timeout          | Cek koneksi internet server      |
| "Unauthorized"         | Token salah           | Cek WABLAS_TOKEN & SECRET_KEY    |
| "Device offline"       | WA device tidak aktif | Hubungkan ulang device WAblas    |
| "Quota habis"          | Limit tercapai        | Top up quota WAblas              |

---

## Support

Jika masalah masih berlanjut setelah semua checklist dijalankan:

1. Simpan log lengkap dari `storage/logs/laravel.log`
2. Screenshot error message
3. Test result dari Postman/cURL
4. Hubungi support WAblas dengan informasi di atas
